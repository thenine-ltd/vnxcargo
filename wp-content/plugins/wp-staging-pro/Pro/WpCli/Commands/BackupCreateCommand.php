<?php

/**
 * The command that will handle the creation of a Backup from the command line.
 *
 * @package WPStaging\Pro\WpCli\Commands
 */

namespace WPStaging\Pro\WpCli\Commands;

use WP_CLI;
use WPStaging\Core\WPStaging;
use WPStaging\Backup\BackgroundProcessing\Backup\PrepareBackup;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\Cron\Cron;

/**
 * Class BackupCreateCommand
 *
 * @package WPStaging\Pro\WpCli\Commands
 */
class BackupCreateCommand implements CommandInterface
{
    /**
     * Creates the Backup using the Background Processing system.
     *
     * @param array               $args      A list of the positional arguments provided by the user, already validated.
     * @param array<string,mixed> $assocArgs A map of the associative arguments, options and flags, provided by the user.
     * @return mixed This method will return mixed values depending on the class that is invoked.
     * @throws WP_CLI\ExitException If the Backup preparation fails, then a message will be provided to the user
     *                              detailing the reason.
     */
    public function __invoke(array $args = [], array $assocArgs = [])
    {
        $options = $this->extractOptionsFromArgs($args);
        if (isset($assocArgs['validate']) && defined('WPSTG_DEBUG') && constant('WPSTG_DEBUG')) {
            $options['validate'] = $assocArgs['validate'];
        }

        $data = $this->setupBackupCreationData($options);
        $data = array_merge($data, $this->setupBackupScheduleData($options));

        try {
            $jobId = WPStaging::make(PrepareBackup::class)->prepare($data);

            if ($jobId instanceof \WP_Error) {
                WP_CLI::error('Failed to create Backup: ' . $jobId->get_error_message());
            }

            $quiet = isset($assocArgs['quiet']);
            if (!$quiet) {
                WP_CLI::success(
                    sprintf(
                        "Backup prepared with Job ID %s\nUse the \"%s\" command to check its status.",
                        $jobId,
                        "wp wpstg backup-status '$jobId'"
                    )
                );
            } else {
                WP_CLI::line($jobId);
            }
        } catch (\Exception $e) {
            WP_CLI::error('Exception thrown while preparing the Backup: ' . $e->getMessage());
        }
    }

    protected function extractOptionsFromArgs(array $args): array
    {
        $options = [];
        foreach ($args as $arg) {
            $parts = explode('=', $arg);
            if (count($parts) === 2) {
                $options[$parts[0]] = $parts[1];
            }
        }

        return $options;
    }

    protected function setupBackupCreationData(array $options): array
    {
        $data = [];
        if (isset($options['name'])) {
            $data['name'] = str_replace(['"', "'"], '', $options['name']);
        }

        if (isset($options['includes'])) {
            $includes = explode(',', $options['includes']);
            $data     = $this->validateIncludes($includes, $data);
        }

        if (isset($options['excludes']) && !isset($options['includes'])) {
            $excludes = explode(',', $options['excludes']);
            $data     = $this->validateExcludes($excludes, $data);
        }

        if (isset($options['advanced-excludes'])) {
            $advancedExcludes = explode(',', $options['advanced-excludes']);
            $data             = $this->validateAdvancedExcludes($advancedExcludes, $data);
        }

        if (isset($options['storages'])) {
            $storages         = explode(',', $options['storages']);
            $data['storages'] = $this->validateStorages($storages);
        }

        $data['isValidateBackupFiles'] = false;
        if (isset($options['validate'])) {
            $data['isValidateBackupFiles'] = $options['validate'];
        }

        $data['isWpCliRequest'] = true;

        // On single site backup type is always single
        if (!is_multisite()) {
            $data['backupType'] = BackupMetadata::BACKUP_TYPE_SINGLE;
            return $data;
        }

        if (!isset($options['type'])) {
            $data['backupType'] = BackupMetadata::BACKUP_TYPE_MULTISITE;
            return $data;
        }

        $data['backupType'] = $this->validateBackupType($options['type']);

        if ($data['backupType'] === BackupMetadata::BACKUP_TYPE_MULTISITE) {
            return $data;
        }

        if (isset($options['subsite_blog_id'])) {
            $data['subsiteBlogId'] = (int)$options['subsite_blog_id'];
        } else {
            $data['subsiteBlogId'] = 1;
        }

        $this->validateSubsiteBlogId($data['subsiteBlogId']);

        return $data;
    }

    protected function validateIncludes($includes, $data): array
    {
        $includes = array_map('trim', $includes);
        $includes = array_filter($includes, function ($include) {
            return !empty($include);
        });

        if (empty($includes)) {
            WP_CLI::error('No valid includes found. Valid includes are: plugins, mu-plugins, themes, uploads, others, database');
        }

        $data['isExportingPlugins']             = in_array('plugins', $includes);
        $data['isExportingMuPlugins']           = in_array('mu-plugins', $includes);
        $data['isExportingThemes']              = in_array('themes', $includes);
        $data['isExportingUploads']             = in_array('uploads', $includes);
        $data['isExportingOtherWpContentFiles'] = in_array('others', $includes);
        $data['isExportingDatabase']            = in_array('database', $includes);

        return $data;
    }

    protected function validateExcludes($excludes, $data): array
    {
        $excludes = array_map('trim', $excludes);
        $excludes = array_filter($excludes, function ($exclude) {
            return !empty($exclude);
        });

        if (empty($excludes)) {
            WP_CLI::error('No valid excludes found. Valid excludes are: plugins, mu-plugins, themes, uploads, others, database');
        }

        $data['isExportingPlugins']             = !in_array('plugins', $excludes);
        $data['isExportingMuPlugins']           = !in_array('mu-plugins', $excludes);
        $data['isExportingThemes']              = !in_array('themes', $excludes);
        $data['isExportingUploads']             = !in_array('uploads', $excludes);
        $data['isExportingOtherWpContentFiles'] = !in_array('others', $excludes);
        $data['isExportingDatabase']            = !in_array('database', $excludes);

        return $data;
    }

    protected function validateAdvancedExcludes($advancedExcludes, $data): array
    {
        $advancedExcludes = array_map('trim', $advancedExcludes);
        $advancedExcludes = array_filter($advancedExcludes, function ($advancedExcludes) {
            return !empty($advancedExcludes);
        });

        if (empty($advancedExcludes)) {
            WP_CLI::error('No valid advanced-excludes found. Valid advanced-excludes are: logs, caches, deactivated-plugins, unused-themes, post-revisions, spam-comments');
        }

        $data['isSmartExclusion']              = true;
        $data['isExcludingLogs']               = in_array('logs', $advancedExcludes);
        $data['isExcludingCaches']             = in_array('caches', $advancedExcludes);
        $data['isExcludingDeactivatedPlugins'] = in_array('deactivated-plugins', $advancedExcludes);
        $data['isExcludingUnusedThemes']       = in_array('unused-themes', $advancedExcludes);
        $data['isExcludingPostRevision']       = in_array('post-revisions', $advancedExcludes);
        $data['isExcludingSpamComments']       = in_array('spam-comments', $advancedExcludes);

        return $data;
    }

    protected function validateStorages($storages): array
    {
        /** @var Providers */
        $storageProviders = WPStaging::make(Providers::class);
        $enabledStorages  = $storageProviders->getStorages(true);

        $validStorages = array_map(function ($storage) {
            return $storage['cli'];
        }, $enabledStorages);

        $validStorages[] = 'local-storage';

        $storages = array_map('trim', $storages);
        $storages = array_filter($storages, function ($storage) use ($validStorages) {
            return in_array($storage, $validStorages);
        });

        if (empty($storages)) {
            WP_CLI::error('No valid storage found. Valid storages are: ' . implode(', ', $validStorages));
        }

        $validatedStorages = [];
        if (in_array('local-storage', $storages)) {
            $validatedStorages[] = 'localStorage';
        }

        array_walk($enabledStorages, function ($storage) use (&$validatedStorages, $storages, $storageProviders) {
            $authClass = $storage['authClass'];
            if (empty($authClass)) {
                return;
            }

            if (!class_exists($authClass)) {
                return;
            }

            if (!$storageProviders->isActivated($authClass)) {
                return;
            }

            if (in_array($storage['cli'], $storages)) {
                $validatedStorages[] = $storage['id'];
            }
        });

        if (empty($validatedStorages)) {
            WP_CLI::error('No valid storage found. Valid storages are: ' . implode(', ', $validStorages) . '. Please make sure you have activated these storages.');
        }

        return $validatedStorages;
    }

    protected function setupBackupScheduleData(array $options): array
    {
        $data = $this->getDefaultScheduleOptions();

        if (!isset($options['schedule'])) {
            return $data;
        }

        $data['repeatBackupOnSchedule'] = true;
        $data['scheduleRecurrence']     = $this->validateRecurrence($options['schedule']);

        if (isset($options['rotation'])) {
            $data['scheduleRotation'] = $this->validateRotation($options['rotation']);
        }

        if (isset($options['time'])) {
            $data['scheduleTime'] = $this->validateTime($options['time']);
        }

        return $data;
    }

    protected function getDefaultScheduleOptions($repeat = false): array
    {
        return [
            'repeatBackupOnSchedule' => $repeat,
            'scheduleRotation'       => 1,
            'scheduleTime'           => ['0', '0'],
            'scheduleRecurrence'     => Cron::DAILY,
        ];
    }

    protected function validateRotation($rotation): int
    {
        if (!is_numeric($rotation)) {
            WP_CLI::error('Rotation must be a number');
        }

        if ($rotation < 1) {
            WP_CLI::error('Rotation must be greater than 0');
        }

        if ($rotation > 10) {
            WP_CLI::error('Rotation must be less than 10');
        }

        return (int)$rotation;
    }

    protected function validateTime($time): array
    {
        if ($time === 'now') {
            $time = date("h:i", time() + 1);
        }

        $time = explode(':', $time);
        if (count($time) !== 2) {
            WP_CLI::error('Time must be in the format HH:MM');
        }

        if (!is_numeric($time[0]) || !is_numeric($time[1])) {
            WP_CLI::error('Time must be in the format HH:MM');
        }

        if ($time[0] < 0 || $time[0] > 23) {
            WP_CLI::error('Time must be in the format HH:MM');
        }

        if ($time[1] < 0 || $time[1] > 59) {
            WP_CLI::error('Time must be in the format HH:MM');
        }

        return $time;
    }

    protected function validateRecurrence($recurrence): string
    {
        $supportedRecurrence = [
            'hourly'             => Cron::HOURLY,
            'every-six-hours'    => Cron::SIX_HOURS,
            'every-twelve-hours' => Cron::TWELVE_HOURS,
            'daily'              => Cron::DAILY,
            'every-two-days'     => Cron::EVERY_TWO_DAYS,
            'weekly'             => Cron::WEEKLY,
            'every-two-weeks'    => Cron::EVERY_TWO_WEEKS,
            'monthly'            => Cron::MONTHLY,
        ];

        if (!in_array($recurrence, array_keys($supportedRecurrence))) {
            WP_CLI::error('Recurrence must be one of ' . implode(', ', array_keys($supportedRecurrence)) . '.');
        }

        return $supportedRecurrence[$recurrence];
    }

    protected function validateBackupType(string $type): string
    {
        $supportedTypes = [
            'multisite' => BackupMetadata::BACKUP_TYPE_MULTISITE,
            'subsite'   => BackupMetadata::BACKUP_TYPE_NETWORK_SUBSITE,
        ];

        if (!in_array($type, array_keys($supportedTypes))) {
            WP_CLI::error('Backup type must be one of ' . implode(', ', array_keys($supportedTypes)) . '.');
        }

        return $supportedTypes[$type];
    }

    /**
     * @param int $subsiteBlogId
     * @return void
     */
    protected function validateSubsiteBlogId(int $subsiteBlogId)
    {
        if (!is_multisite()) {
            return;
        }

        if ($subsiteBlogId < 0) {
            WP_CLI::error('Subsite Blog ID must be greater than 0.');
        }

        if (!get_blog_details($subsiteBlogId)) {
            WP_CLI::error('Subsite Blog ID does not exist.');
        }
    }
}
