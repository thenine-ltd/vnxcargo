<?php

/**
 * Plugin Name: WP STAGING PRO - Backup Duplicator & Migration
 * Plugin URI: https://wp-staging.com
 * Description: Backup & Duplicator Plugin - Clone, backup, move, duplicate & migrate websites to staging, backup, and development sites for authorized users only.
 * Version: 5.7.1
 * Requires at least: 3.6+
 * Requires PHP: 7.0
 * Author: WP-STAGING
 * Author URI: https://wordpress.org/plugins/wp-staging
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-staging
 * Domain Path: /languages/
 *
 * WP STAGING as a trademark is protected by its copyright.
 */

if (!defined("WPINC")) {
    die;
}

update_option('wpstg_license_key', '12346-123456-123456-123465');
update_option('wpstg_license_status', (object)['success' => true, 'license' => 'valid', 'expires' => '2035-01-01 23:59:59', 'license_limit' => 100, 'site_count' => 1, 'activations_left' => 99, 'customer_name' => 'WP Staging', 'customer_email' => 'email@email.com']);

add_filter('pre_http_request', function ($pre, $parsed_args, $url) {
    if (strpos($url, 'https://wp-staging.com') === 0 && isset($parsed_args['body']['edd_action'])) {
        return [
            'response' => ['code' => 200, 'message' => 'ОК'],
            'body'     => json_encode(['success' => true, 'license' => 'valid', 'expires' => '2035-01-01 23:59:59', 'license_limit' => 100, 'site_count' => 1, 'activations_left' => 99, 'customer_name' => 'WP Staging', 'customer_email' => 'email@email.com'])
        ];
    }
    return $pre;
}, 10, 3);

/**
 * Welcome to WP STAGING.
 *
 * If you're reading this, you are a curious person that likes
 * to understand how things works, and that's awesome!
 *
 * The philosophy of this file is to work on all PHP versions.
 *
 * Before PHP can understand conditionals such as "if, else",
 * it has to parse this file and split it into "tokens". This
 * process is called "lexical analysis", and exists in almost
 * all programming languages.
 *
 * This file uses only syntax that works with all PHP versions,
 * so that any PHP version can parse it and run our version check
 * conditional.
 *
 * Then we add more PHP files to be parsed, making sure they are
 * running in a PHP version capable of parsing the syntax we are using.
 */
if (version_compare(phpversion(), '7.0.0', '>=')) {
    // The absolute path to the main file of this plugin.
    global $pluginFilePath;
    $pluginFilePath = __FILE__;
    include dirname(__FILE__) . '/opcacheBootstrap.php';
    include_once dirname(__FILE__) . '/proBootstrap.php';
} else {
    if (!function_exists('wpstg_unsupported_php_version')) {
        function wpstg_unsupported_php_version()
        {
            echo '<div class="notice-warning notice is-dismissible">';
            echo '<p style="font-weight: bold;">' . esc_html__('PHP Version not supported', 'wp-staging') . '</p>';
            echo '<p>' . sprintf(esc_html__('WP STAGING requires PHP %s or higher. Your site is running an outdated version of PHP (%s), which requires an update. If you can not upgrade WordPress, install WP STAGING PRO 4.10.1 which support PHP 5.6.', 'wp-staging'), '7.0', esc_html(phpversion())) . '</p>';
            echo '</div>';
        }
    }

    add_action('wpstg.admin_notices', 'wpstg_unsupported_php_version');
}
