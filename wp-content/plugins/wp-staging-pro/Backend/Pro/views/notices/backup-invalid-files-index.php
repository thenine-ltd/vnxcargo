<?php

/**
 * @see \WPStaging\Pro\Notices\Notices::backupInvalidFileIndexNotice
 */

?>

<div class="notice notice-error">
    <p>
        <strong><?php
            esc_html_e('WP Staging: Backup file corrupted!', 'wp-staging');
        ?></strong> <br/> 
        <?php
            esc_html_e('One or more backup files have a missing or corrupted file index! It is highly recommended to create a new backup if you have not done so already.', 'wp-staging');
        ?>
        <br>
        <?php
            echo sprintf(esc_html__('Please send us the log files so that we can investigate this issue. %s', 'wp-staging'), '<a href="https://wp-staging.com/support/" target="_blank">' . esc_html__('Contact us', 'wp-staging') . '</a>');
        ?>
    </p>
</div>
