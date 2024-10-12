<?php

use WPStaging\Backup\BackupScheduler;
use WPStaging\Framework\Facades\UI\Checkbox;

/**
 * @var \WPStaging\Core\Forms\Form $form
 */

?>
<tr class="wpstg-settings-row">
    <td class="wpstg-settings-row th">
        <b class="wpstg-settings-title"><?php esc_html_e('Send Email Error Report', 'wp-staging') ?></b>
        <p class="wpstg-settings-message">
            <?php esc_html_e('If a scheduled backup fails, send an email.', 'wp-staging') ?>
        </p>
    </td>
    <td>
        <?php
        $isCheckboxChecked = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_ERROR_REPORT) === 'true';
        Checkbox::render('wpstg-send-schedules-error-report', 'wpstg_settings[schedulesErrorReport]', 'true', $isCheckboxChecked, ['classes' => 'wpstg-settings-field']);
        ?>
    </td>
</tr>
<tr class="wpstg-settings-row">
    <td>
        <b class="wpstg-settings-title"><?php esc_html_e('Email Address', 'wp-staging') ?></b>
        <p class="wpstg-settings-message">
            <?php esc_html_e('Send emails to this address', 'wp-staging') ?>
        </p>
    </td>
    <td>
        <input type="text" id="wpstg-send-schedules-report-email" name="wpstg_settings[schedulesReportEmail]" class="wpstg-settings-field" value="<?php echo esc_attr(get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL)) ?>"/>
    </td>
</tr>
<?php if (defined('WPSTG_ENABLE_COMPRESSION') && constant('WPSTG_ENABLE_COMPRESSION')) : ?>
<tr class="wpstg-settings-row">
    <td class="wpstg-settings-row th" colspan="2">
        <div class="col-title">
            <strong><?php
                echo esc_html('Backups') ?></strong>
            <span class="description"></span>
        </div>
    </td>
</tr>
<!-- Compressed Backups -->
<tr class="wpstg-settings-row">
    <td class="wpstg-settings-row th">
        <div class="col-title">
            <?php
            $form->renderLabel("wpstg_settings[enableCompression]") ?>
            <span class="description">
                <?php
                echo wp_kses_post(
                    __(
                        'Compresses the backup to reduce size. Especially useful with big databases.',
                        'wp-staging'
                    )
                ); ?>
            </span>
        </div>
    </td>
    <td>
        <?php
        $form->renderInput("wpstg_settings[enableCompression]") ?>
    </td>
</tr>
<?php endif; ?>
