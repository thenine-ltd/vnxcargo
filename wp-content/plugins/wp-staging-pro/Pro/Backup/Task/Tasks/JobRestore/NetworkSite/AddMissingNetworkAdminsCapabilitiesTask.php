<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobRestore\NetworkSite;

use RuntimeException;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * Class AddMissingNetworkAdminsCapabilitiesTask
 *
 * This class will add missing admin capabilities to network admins from a network backup on the destination single site or subsite.
 *
 * @package WPStaging\Pro\Backup\Task\Tasks\JobRestore\NetworkSite
 */
class AddMissingNetworkAdminsCapabilitiesTask extends RestoreTask
{
    /** @var TableService */
    protected $tableService;

    /** @var \wpdb */
    protected $wpdb;

    /** @var string */
    protected $tmpPrefix;

    /** @var JobRestoreDataDto $jobDataDto */
    protected $jobDataDto;

    /** @var string[] */
    protected $currentSuperAdmins;

    public function __construct(
        TableService $tableService,
        JobDataDto $jobDataDto,
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->tableService = $tableService;
        // @phpstan-ignore-next-line
        $this->jobDataDto   = $jobDataDto;
    }

    public static function getTaskName(): string
    {
        return 'backup_restore_network_admins';
    }

    public static function getTaskTitle(): string
    {
        return 'Restore Network Admins';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute(): TaskResponseDto
    {
        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal(1);
        }

        try {
            $this->restoreNetworkAdmins();
        } catch (RuntimeException $ex) {
            $this->logger->warning('Cannot restore network admins. Backup will still be restored...');
        }

        return $this->generateResponse();
    }

    /**
     * @return void
     */
    protected function restoreNetworkAdmins()
    {
        $networkAdmins = $this->jobDataDto->getBackupMetadata()->getNetworkAdmins();
        if (empty($networkAdmins)) {
            $this->logger->info('No network admin found to be restored...');
            return;
        }

        $this->tmpPrefix    = $this->jobDataDto->getTmpDatabasePrefix();
        $this->wpdb         = $this->tableService->getDatabase()->getWpdb();
        $currentSitePrefix  = $this->tableService->getDatabase()->getPrefix();

        if (is_multisite()) {
            $this->currentSuperAdmins = get_super_admins();
        }

        $networkAdminsRestored = [];
        foreach ($networkAdmins as $networkAdmin) {
            $networkAdminId = $this->getUserIdFromUsername($networkAdmin);
            if ($this->isNetworkAdminAlreadyExistsForSite($networkAdminId, $currentSitePrefix)) {
                continue;
            }

            // When importing on subsite
            if ($this->isAlreadyNetworkAdmin($networkAdmin)) {
                continue;
            }

            $this->restoreNetworkAdmin($networkAdminId, $currentSitePrefix);
            $networkAdminsRestored[] = $networkAdminId;
        }

        if (empty($networkAdminsRestored)) {
            $this->logger->info('All network admins already restored...');
            return;
        }

        $this->logger->info('Restored network admins successfully...');
    }

    /**
     * @param string $username
     * @return int
     */
    protected function getUserIdFromUsername(string $username): int
    {
        $user = $this->wpdb->get_row($this->wpdb->prepare("SELECT ID FROM {$this->tmpPrefix}users WHERE user_login = %s", $username));
        if (empty($user)) {
            throw new RuntimeException(sprintf('User with username "%s" not found.', $username));
        }

        // Should not happen, required for PHPSTAN
        if (!isset($user->ID)) {
            throw new RuntimeException(sprintf('User with username "%s" not found.', $username));
        }

        return (int) $user->ID;
    }

    /**
     * @param int $userId
     * @param string $currentSitePrefix
     * @return bool
     */
    protected function isNetworkAdminAlreadyExistsForSite(int $userId, string $currentSitePrefix): bool
    {
        $networkAdmin = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->tmpPrefix}usermeta WHERE user_id = %d AND meta_key = %s", $userId, $currentSitePrefix . 'capabilities'));
        if (empty($networkAdmin)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $username
     * @return bool
     */
    protected function isAlreadyNetworkAdmin(string $username): bool
    {
        return is_multisite() && in_array($username, $this->currentSuperAdmins);
    }

    /**
     * @param int $userId
     * @param string $currentSitePrefix
     * @return void
     */
    protected function restoreNetworkAdmin(int $userId, string $currentSitePrefix)
    {
        $this->wpdb->query($this->wpdb->prepare("INSERT INTO {$this->tmpPrefix}usermeta (user_id, meta_key, meta_value) VALUES (%d, %s, %s)", $userId, $currentSitePrefix . 'capabilities', serialize(['administrator' => true])));
        $this->wpdb->query($this->wpdb->prepare("INSERT INTO {$this->tmpPrefix}usermeta (user_id, meta_key, meta_value) VALUES (%d, %s, %s)", $userId, $currentSitePrefix . 'user_level', 10));
    }
}
