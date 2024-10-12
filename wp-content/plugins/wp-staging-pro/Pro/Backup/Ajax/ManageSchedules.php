<?php

namespace WPStaging\Pro\Backup\Ajax;

use UnexpectedValueException;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Framework\Utils\Times;

/**
 * @todo Add dependency injection for remaining classes and move the ui related code in view in the last step PR.
 */
class ManageSchedules
{
    /** @var BackupScheduler */
    private $backupScheduler;

    public function __construct(BackupScheduler $backupScheduler)
    {
        $this->backupScheduler = $backupScheduler;
    }

    /**
     * Ajax callback to edit a schedule.
     */
    public function editSchedule()
    {
        if (!current_user_can((new Capabilities())->manageWPSTG())) {
            return;
        }

        if (!(new Nonce())->requestHasValidNonce(Nonce::WPSTG_NONCE)) {
            return;
        }

        if (empty($_POST['scheduleId']) || empty($_POST['formValues'])) {
            return;
        }

        $scheduleId = Sanitize::sanitizeString($_POST['scheduleId']);
        $formValues = Sanitize::sanitizeArray($_POST['formValues'], [
            'scheduleRecurrence' => 'string',
            'scheduleTime' => 'string',
            'scheduleRotation' => 'string'
        ]);

        try {
            $result = $this->updateScheduleInDB($scheduleId, $formValues);
            if ($result) {
                wp_send_json_success();
            } else {
                wp_send_json_error();
            }
        } catch (UnexpectedValueException $error) {
            wp_send_json_error($error->getMessage());
        }
    }

    /**
     * @param string $scheduleId
     * @param array $schedulesInfo
     * @return bool
     * @throws UnexpectedValueException
     */
    public function updateScheduleInDB(string $scheduleId, array $schedulesInfo): bool
    {
        $schedules   = $this->backupScheduler->getSchedules();
        $newSchedule = array_map(function ($schedule) use ($scheduleId, $schedulesInfo) {
            if ($schedule['scheduleId'] === $scheduleId) {
                $schedule['schedule'] = $schedulesInfo['scheduleRecurrence'];
                $schedule['rotation'] = $schedulesInfo['scheduleRotation'];
                $schedule['time']     = explode(':', $schedulesInfo['scheduleTime']);
            }

            return $schedule;
        }, $schedules);

        if ($newSchedule === get_option(BackupScheduler::OPTION_BACKUP_SCHEDULES, [])) {
            // Make sure to reschedule again the cron jobs just in case this is a reattempt due to a previous failing attempt.
            $this->backupScheduler->reCreateCron($scheduleId);
            return false;
        }

        if (!update_option(BackupScheduler::OPTION_BACKUP_SCHEDULES, $newSchedule, false)) {
            throw new UnexpectedValueException(__('Failed to update the backup schedule!', 'wp-staging'));
        }

        if ($this->backupScheduler->reCreateCron($scheduleId)) {
            return true;
        }

        throw new UnexpectedValueException(__('Failed to update the WordPress cron jobs. Please try again or contact us.', 'wp-staging'));
    }

    /**
     * Ajax callback to display the modal to edit a schedule.
     */
    public function editScheduleModal()
    {
        if (!current_user_can((new Capabilities())->manageWPSTG())) {
            return;
        }

        if (!(new Nonce())->requestHasValidNonce(Nonce::WPSTG_NONCE)) {
            return;
        }

        if (empty($_POST['scheduleId'])) {
            return;
        }

        $schedules  = $this->backupScheduler->getSchedules();
        $scheduleId = Sanitize::sanitizeString($_POST['scheduleId']);

        $currentSchedule = current(array_filter($schedules, function ($schedule) use ($scheduleId) {
            return $schedule['scheduleId'] === $scheduleId;
        }));

        try {
            ob_start();
            $timeFormatOption = get_option('time_format');

            $time      = WPStaging::make(Times::class);
            $urlAssets = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';

            $recurInterval = (defined('WPSTG_DEV') && WPSTG_DEV) ? 'PT1M' : 'PT15M';

            $recurInterval = apply_filters('wpstg.schedulesBackup.interval', $recurInterval);

            $recurrenceTimes = $time->range('midnight', 'tomorrow - 1 minutes', $recurInterval); ?>
            <form id="editBackupScheduleForm" name="editBackupScheduleForm">
                <div data-confirmButtonText="<?php esc_attr_e('Save Changes', 'wp-staging') ?>">
                    <div class="wpstg-advanced-options" style="text-align: left;">
                        <!-- BACKUP CHECKBOXES -->
                        <div class="wpstg-advanced-options-site">
                            <div class="wpstg-backup-options-section">
                                <div class="wpstg-backup-scheduling-options wpstg-container">
                                    <div class="hidden2" data-show-if-unchecked="repeatBackupOnSchedule">
                                        <div class="wpstg-edit-schedule-option">
                                            <label for="backupScheduleRecurrence">
                                                <?php esc_html_e('How often:', 'wp-staging'); ?>
                                            </label>
                                            <select name="backupScheduleRecurrence" id="wpstgEditBackupScheduleRecurrence">
                                                <option <?php echo (Cron::HOURLY === $currentSchedule['schedule']) ? 'selected' : ''; ?> value="<?php echo esc_attr(Cron::HOURLY); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::HOURLY)); ?></option>
                                                <option <?php echo (Cron::SIX_HOURS === $currentSchedule['schedule']) ? 'selected' : ''; ?> value="<?php echo esc_attr(Cron::SIX_HOURS); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::SIX_HOURS)); ?></option>
                                                <option <?php echo (Cron::TWELVE_HOURS === $currentSchedule['schedule']) ? 'selected' : ''; ?> value="<?php echo esc_attr(Cron::TWELVE_HOURS); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::TWELVE_HOURS)); ?></option>
                                                <option <?php echo (Cron::DAILY === $currentSchedule['schedule']) ? 'selected' : ''; ?> value="<?php echo esc_attr(Cron::DAILY); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::DAILY)); ?></option>
                                                <option <?php echo (Cron::EVERY_TWO_DAYS === $currentSchedule['schedule']) ? 'selected' : ''; ?> value="<?php echo esc_attr(Cron::EVERY_TWO_DAYS); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::EVERY_TWO_DAYS)); ?></option>
                                                <option <?php echo (Cron::WEEKLY === $currentSchedule['schedule']) ? 'selected' : ''; ?> value="<?php echo esc_attr(Cron::WEEKLY); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::WEEKLY)); ?></option>
                                                <option <?php echo (Cron::EVERY_TWO_WEEKS === $currentSchedule['schedule']) ? 'selected' : ''; ?> value="<?php echo esc_attr(Cron::EVERY_TWO_WEEKS); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::EVERY_TWO_WEEKS)); ?></option>
                                                <option <?php echo (Cron::MONTHLY === $currentSchedule['schedule']) ? 'selected' : ''; ?> value="<?php echo esc_attr(Cron::MONTHLY); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::MONTHLY)); ?></option>
                                            </select>
                                        </div>
                                        <div class="wpstg-edit-schedule-option">
                                            <div class="wpstg-edit-schedule-option-inner">
                                                <label for="backupScheduleTime">
                                                    <?php esc_html_e('Start Time:', 'wp-staging'); ?>
                                                </label>
                                                <select name="backupScheduleTime" id="wpstgEditBackupScheduleTime">
                                                    <?php $currentTime   = (new \DateTime('now', $time->getSiteTimezoneObject()))->format($timeFormatOption);
                                                    $currentScheduleTime = $currentSchedule['time'][0] . ':' . $currentSchedule['time'][1]; ?>
                                                    <?php foreach ($recurrenceTimes as $recurTime) : ?>
                                                        <option <?php echo ($recurTime->format('H:i') === $currentScheduleTime) ? 'selected' : ''; ?> value="<?php echo esc_attr($recurTime->format('H:i')) ?>">
                                                            <?php echo esc_html($recurTime->format($timeFormatOption)) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div id="backup-schedule-current-time">
                                                    <span><?php echo esc_html__('Current Time', 'wp-staging'); ?></span>
                                                    <br/>
                                                    <span><?php echo esc_html($currentTime); ?></span>
                                                </div>
                                            </div>
                                            <div class="wpstg--tooltip">
                                                <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/info-outline.svg" alt="info"/>
                                                <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                                    <?php echo sprintf(
                                                        Escape::escapeHtml(__('Relative to current server time, which you can change in <a href="%s">WordPress Settings</a>.', 'wp-staging')),
                                                        esc_url(admin_url('options-general.php#timezone_string'))
                                                    ); ?>
                                                    <br>
                                                    <br>
                                                    <?php echo sprintf(esc_html__('Current Server Time: %s', 'wp-staging'), esc_html($time->getCurrentTime())); ?>
                                                    <br>
                                                    <?php echo sprintf(esc_html__('Site Timezone: %s', 'wp-staging'), esc_html($time->getSiteTimezoneString())); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="wpstg-edit-schedule-option">
                                            <div class="wpstg-edit-schedule-option-inner">
                                                <label for="backupScheduleRotation">
                                                    <?php esc_html_e('Retention:', 'wp-staging'); ?>
                                                </label>
                                                <select name="backupScheduleRotation" id="wpstgEditBackupScheduleRotation">
                                                    <?php for ($i = 1; $i <= 10; $i++) : ?>
                                                        <option <?php echo ($i === (int)$currentSchedule['rotation']) ? 'selected' : ''; ?> value="<?php echo esc_attr($i) ?>">
                                                            <?php echo sprintf(esc_html__('Keep last %d backup%s', 'wp-staging'), (int)$i, (int)$i > 1 ? 's' : ''); ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="wpstg--tooltip">
                                                <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/info-outline.svg" alt="info"/>
                                                <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                                    <?php esc_html_e('How many local backups to keep before deleting old ones to free up storage space.', 'wp-staging') ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <?php
            wp_send_json_success(ob_get_clean());
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}
