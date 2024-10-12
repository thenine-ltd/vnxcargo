<?php

namespace WPStaging\Backend\Pro;

use WPStaging\Framework\Filesystem\FileObject;

class WpstgRestore
{
    public function downloadFile()
    {
        if (!current_user_can("update_plugins") || !check_admin_referer('wpstg_restorer_nonce', 'wpstg_restorer_nonce') || !defined('WPSTGPRO_VERSION')) {
            wp_die('Invalid access', 'WP Staging Restore', ['response' => 403, 'back_link' => true]);
        }

        $wpstgRestoreFile = WPSTG_PLUGIN_DIR . 'wpstg-restore.php';
        if (!file_exists($wpstgRestoreFile) && defined('WPSTG_DEV') && (bool)WPSTG_DEV && file_exists(WPSTG_PLUGIN_DIR . 'wpstg-restore/dist/wpstg-restore.php')) {
            $wpstgRestoreFile = WPSTG_PLUGIN_DIR . 'wpstg-restore/dist/wpstg-restore.php';
        }

        if (!file_exists($wpstgRestoreFile)) {
            wp_die(esc_html__('The wpstg-restore.php file not found. Please contact support@wp-staging for help.', 'wp-staging'), 'WP Staging Restore', ['response' => 200, 'back_link' => true]);
        }

        // @see dev/docs/wpstg-restore/README.md
        $token = get_option(implode('', array_map(function ($integer) {
            return chr($integer);
        }, array_reverse([121,101,107,95,101,115,110,101,99,105,108,95,103,116,115,112,119]))));

        if (empty($token)) {
            exit('Invalid access');
        }

        // @see dev/docs/wpstg-restore/README.md
        $embed = implode(',', array_map(function ($string) {
            return ord($string);
        }, array_reverse(str_split($token))));

        $output     = '';
        $fileObject = new FileObject($wpstgRestoreFile, FileObject::MODE_READ);
        while ($fileObject->valid()) {
            $content = $fileObject->fgets();
            if (strpos($content, '/**@wpstg-restorer-halt**/') !== false) {
                continue;
            }

            // @see dev/docs/wpstg-restore/README.md
            $output .= str_replace('[53,98,55,55,51,48,101,101,102,57,57,99]', '[' . $embed . ']', $content);
        }
        $fileObject = null;

        if (empty($output)) {
            wp_die('The wpstg-restore.php file is empty', 'WP Staging Restore', ['response' => 200, 'back_link' => true]);
        }

        nocache_headers();
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="wpstg-restore.php"');
        header('Content-Length: ' . strlen($output));
        exit($output); // phpcs:ignore
    }
}
