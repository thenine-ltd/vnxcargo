<?php

/**
 * The purpose of the pre-bootstrap process is to make sure the environment is able to run
 * the plugin without any errors, such as making sure there are no other WPSTAGING instances
 * active at the same time.
 *
 * It works at a low level, without the autoloader, using anonymous callbacks and local variables
 * to make sure we always use and execute the expected code.
 *
 * Since it uses closures, you can't dequeue those actions, but this is expected.
 *
 * @var string $pluginFilePath The absolute path to the main file of this plugin.
 */

add_action('plugins_loaded', function () use ($pluginFilePath) {
    // Unused $pluginFilePath: Other code will fail if removed it
    try {
        require __DIR__ . '/runtimeRequirements.php';
        require_once __DIR__ . '/bootstrap.php';
    } catch (Exception $e) {
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log('WP STAGING: ' . $e->getMessage());
        }
    }
}, 11, 0); // The priority of this hook must be larger than 10 for the runtime requirement check to detect older versions of WPSTAGING.

register_activation_hook($pluginFilePath, function () use ($pluginFilePath) {
    // Unused $pluginFilePath: Other code will fail if removed it
    try {
        require __DIR__ . '/runtimeRequirements.php';
        require_once __DIR__ . '/bootstrap.php';
        require_once __DIR__ . '/install.php';
    } catch (Exception $e) {
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log('WP STAGING: ' . $e->getMessage());
        }
    }

    add_filter('wpstg.deactivation_hook.skip_mu_delete', function ($value) {
        return true;
    });

    // Deactivate WPSTAGING Free when activating Pro
    delete_site_transient('wpstgUpgradingFreeToPro');
    delete_site_transient('wpstgDisableLicenseNotice');

    // Deactivate free plugin on network site
    if (is_multisite()) {
        foreach (wp_get_active_network_plugins() as $networkwidePlugin) {
            if (strpos($networkwidePlugin, 'wp-staging.php') !== false) {
                set_site_transient('wpstgUpgradingFreeToPro', true, 1 * HOUR_IN_SECONDS);
                set_site_transient('wpstgDisableLicenseNotice', true, 1 * HOUR_IN_SECONDS);
            }
        }
    }
    foreach (wp_get_active_and_valid_plugins() as $sitewidePlugin) {
        if (strpos($sitewidePlugin, 'wp-staging.php') !== false) {
            set_site_transient('wpstgUpgradingFreeToPro', true, 1 * HOUR_IN_SECONDS);
            set_site_transient('wpstgDisableLicenseNotice', true, 1 * HOUR_IN_SECONDS);
        }
    }
});

register_deactivation_hook($pluginFilePath, function () use ($pluginFilePath) {
    if (!class_exists('WPStaging\Deactivate')) {
        require_once __DIR__ . '/Deactivate.php';
    }

    new WPStaging\Deactivate($pluginFilePath);
});
