<?php

/**
 * @see \WPStaging\Pro\Notices\Notices::renderNotices
 */

?>
<div class="notice notice-error">
    <p>
        <strong><?php esc_html_e('WP STAGING - Please sign in to Google Drive again.', 'wp-staging'); ?></strong> <br/>
        <?php
            echo sprintf(
                esc_html__('Google Drive disconnected unexpectedly, please go to %s and sign in again.', 'wp-staging'),
                '<a href="' . esc_url(admin_url('admin.php?page=wpstg-settings&tab=remote-storages&sub=googleDrive')) . '" target="_blank">' . esc_html__('settings page', 'wp-staging') . '</a>'
            );
            ?>
    </p>
</div>
