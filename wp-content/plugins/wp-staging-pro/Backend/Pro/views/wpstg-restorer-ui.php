<?php

use WPStaging\Framework\Facades\Escape;

/**
 * @see WPStaging\Backend\Administrator::getRestorerPage
 *
 * @var object $license
 */
?>
<div class="wpstg_admin" id="wpstg-clonepage-wrapper">
<?php
    require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/header.php');

    $isActiveRestorerPage = true;
    require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/main-navigation.php');
?>
    <div class="wpstg-metabox-holder wpstg-restorer-wrapper">
        <h2><?php esc_html_e('What is WP Staging | Restore?', 'wp-staging');?></h2>
        <p>
            <?php printf(esc_html__('%s is a standalone tool for WP Staging Pro license owners that runs without WordPress and can perform the following tasks:', 'wp-staging'), 'WP Staging | Restore');?>
            <ul>
                <li><?php esc_html_e('Extract a backup to a directory of your choice to check the contents or extract individual files from it.', 'wp-staging');?></li>
                <li><?php esc_html_e('Restore a backup, even if WordPress is defective and can not load.', 'wp-staging');?></li>
                <li><?php esc_html_e('Install and set up WordPress automatically on a new server and restore a backup file there.', 'wp-staging');?></li>
            </ul>
        </p>
            <h2><?php esc_html_e('How to use WP Staging | Restore:', 'wp-staging');?></h2>
        <p>
            <ol class="wpstg-how-to-use-restorer">
                <li><?php printf(esc_html__('Download below the file %s.', 'wp-staging'), '<code>wpstg-restore.php</code>');?></li>
                <li><?php esc_html_e('Place the file in the root directory of the website that you want to restore or extract the backup file.', 'wp-staging');?></li>
                <li><?php echo sprintf(esc_html__('Start the tool by opening in the browser the URL %s.', 'wp-staging'), '<code>https://example.com/wpstg-restore.php</code>');?></li>
                <li><?php echo sprintf(esc_html__('Paste the full name of the uploaded backup file into the authentication form, e.g. %s', 'wp-staging'), '<code>example.com.20240405-110721_c1d442862ad1.wpstg</code>');?></li>
            </ol>
        </p>
        <p>
            <?php esc_html_e('The restore tool searches for existing backup files in:', 'wp-staging');?>
            <ul>
                <li><?php esc_html_e('Root directory of a WordPress site.', 'wp-staging');?></li>
                <li><?php printf(esc_html__('WP Staging backup directory (Default: %s)', 'wp-staging'), '<code>wp-content/uploads/wp-staging/backups/</code>');?></li>
            </ul>
        </p>
        <?php if (isset($license->license) && $license->license === 'valid') : ?>
        <form method="post" action="<?php echo esc_url(admin_url("admin-post.php?action=wpstg_download_restorer")) ?>">
            <input type="submit" name="wpstg-download-restorer" id="wpstg-download-restorer" class="wpstg-button wpstg-blue-primary" value="<?php esc_html_e('Download WP Staging | Restore', 'wp-staging');?>">
            <?php wp_nonce_field('wpstg_restorer_nonce', 'wpstg_restorer_nonce');?>
        </form>
        <?php else : ?>
            <form method="post" action="<?php echo esc_url(admin_url("admin-post.php?action=wpstg_download_restorer")) ?>">
                <input type="submit" disabled name="wpstg-download-restorer" id="wpstg-download-restorer" class="wpstg-button wpstg-blue-primary" value="<?php esc_html_e('Download WP Staging | Restore', 'wp-staging');?>">
            </form>
            <p class="wpstg-activate-restorer-license-message">
                <?php printf(esc_html__('You need a valid license in order to download the %s.', 'wp-staging'), '<strong>WP Staging Restore</strong>');?>
            </p>
            <p>
                <?php
                    printf(
                        Escape::escapeHtml(
                            __('Please <a href="%s">activate</a> your license key or buy one from <a href="%s" rel="noopener" target="new">wp-staging.com</a>', 'wp-staging')
                        ),
                        esc_url(get_admin_url()) . 'admin.php?page=wpstg-license',
                        'https://wp-staging.com/'
                    );
                ?>
            </p>
        <?php endif; ?>
    </div>
</div>
<?php
require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/footer.php');
