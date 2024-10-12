<?php

namespace WPStaging\Backend\Pro\Licensing;

use WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Facades\Escape;

use function WPStaging\functions\debug_log;

class Licensing
{
    /**
     * The license key
     * @var string
     */
    private $licenseKey = '';

    /**
     * @var string
     */
    const WPSTG_LICENSE_KEY = 'wpstg_license_key';

    /** @var string 'valid' or 'invalid' */
    const WPSTG_LICENSE_STATUS = 'wpstg_license_status';

    /**
     * @var string
     */
    const WPSTG_STORE_URL = 'https://wp-staging.com';

    /**
     * @var string
     */
    const WPSTG_ITEM_NAME = 'WP STAGING PRO';

    /**
     * @var string
     */
    const WPSTG_AUTHOR_NAME = 'Rene Hermenau';

    /**
     * @var string
     */
    const LICENSE_EXPIRED = 'expired';

    /**
     * @var string
     */
    const LICENSE_VALID = 'valid';

    public function __construct()
    {
        $this->registerHooks();
    }

    /**
     * @return void
     */
    private function registerHooks()
    {
        static $isRegistered = false;
        if ($isRegistered) {
            return;
        }

        add_action('admin_notices', [$this, 'adminNotices']);
        add_action('admin_init', [$this, 'activateLicense']);
        add_action('admin_init', [$this, 'deactivateLicense']);
        add_action('wpstg_daily_event', [$this, 'updateLicenseData']);

        if (!defined('WPSTG_STORE_URL')) {
            define('WPSTG_STORE_URL', self::WPSTG_STORE_URL);
        }

        if (!defined('WPSTG_ITEM_NAME')) {
            define('WPSTG_ITEM_NAME', self::WPSTG_ITEM_NAME);
        }

        $this->licenseKey = trim(get_option(self::WPSTG_LICENSE_KEY));
        // Initialize the EDD software licensing API
        $this->pluginUpdater();

        $isRegistered = true;
    }

    /**
     * EDD software licensing API
     * @return void
     */
    public function pluginUpdater()
    {
        // Check for 'undefined' here because WPSTG_PLUGIN_FILE will be undefined if plugin is uninstalled to prevent issue #216
        $pluginFile = !defined('WPSTG_PLUGIN_FILE') ? null : WPSTG_PLUGIN_FILE;

        new EDD_SL_Plugin_Updater(
            WPSTG_STORE_URL,
            $pluginFile,
            [
                'version'   => WPStaging\Core\WPStaging::getVersion(),
                'license'   => $this->licenseKey,
                'item_name' => WPSTG_ITEM_NAME,
                'author'    => self::WPSTG_AUTHOR_NAME,
                'beta'      => $this->isBetaVersion()
            ]
        );
    }

    /**
     * Activate the license key
     * @return void
     */
    public function activateLicense()
    {
        if (isset($_POST['wpstg_activate_license']) && !empty($_POST[self::WPSTG_LICENSE_KEY])) {
            // Early bail if nonce is invalid
            if (!check_admin_referer('wpstg_license_nonce', 'wpstg_license_nonce')) {
                return;
            }

            $licenseKey = trim(Sanitize::sanitizeString($_POST[self::WPSTG_LICENSE_KEY]));
            update_option(self::WPSTG_LICENSE_KEY, $licenseKey);

            $apiParams = [
                'edd_action' => 'activate_license',
                'license'    => $licenseKey,
                'item_name'  => urlencode(WPSTG_ITEM_NAME),
                'url'        => home_url()
            ];

            $response = wp_remote_post(
                WPSTG_STORE_URL,
                [
                    'timeout'   => 15,
                    'sslverify' => false,
                    'body'      => $apiParams
                ]
            );

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                if (is_wp_error($response)) {
                    $message = $response->get_error_message();
                } else {
                    $message = sprintf(__('An error occurred, please try again. Response: %s', 'wp-staging'), print_r($response, true));
                }
                $message .= sprintf(Escape::escapeHtml(__('<br/>You may find a solution in <a href="%s" target="_blank">this article</a>.', 'wp-staging')), esc_url('https://wp-staging.com/docs/curl-error-35-unknown-ssl-protocol-error-in-connection/'));
            } else {
                $licenseData = json_decode(wp_remote_retrieve_body($response));

                if ($licenseData->success === false) {
                    switch ($licenseData->error) {
                        case 'expired':
                            $message = sprintf(
                                __('Your license key expired on %s. Renew the license key on wp-staging.com or contact support@wp-staging to get help.', 'wp-staging'),
                                date_i18n(get_option('date_format'), strtotime($licenseData->expires, current_time('timestamp')))
                            );
                            break;
                        case 'revoked':
                        case 'disabled':
                            $message = __('Your license key has been disabled. Please contact support@wp-staging.com.', 'wp-staging');
                            break;
                        case 'missing':
                        case 'key_mismatch':
                            $message = __('Your License key is invalid. Get a new license key from wp-staging.com or contact support@wp-staging.com for help.', 'wp-staging');
                            break;
                        case 'missing_url':
                            $message = __('Could not activate license. URL not provided. Get a new license key from wp-staging.com or contact support@wp-staging.com for help.', 'wp-staging');
                            break;
                        case 'license_not_activable':
                            $message = __('Attempting to activate a bundle\'s parent license. Get a new license key from wp-staging.com or contact support@wp-staging.com for help.', 'wp-staging');
                            break;
                        case 'invalid':
                            $message = __('Your license key is invalid. Get a new license key from wp-staging.com or contact support@wp-staging.com for help.', 'wp-staging');
                            break;
                        case 'site_inactive':
                            $message = __('This site URL has been disabled for this license key. Get a new license key from wp-staging.com or contact support@wp-staging.com for help.', 'wp-staging');
                            break;
                        case 'item_name_mismatch':
                            $message = sprintf(__('This appears to be an invalid license key for %s. Get a new license key from wp-staging.com or contact support@wp-staging.com for help.', 'wp-staging'), WPSTG_ITEM_NAME);
                            break;
                        case 'invalid_item_id':
                            $message = __('Could not activate license. Invalid Item ID. Get a new license key from wp-staging.com or contact support@wp-staging.com for help.', 'wp-staging');
                            break;
                        case 'no_activations_left':
                            $message = __('Your license key has reached its activation limit. Upgrade your license key on wp-staging.com for more active sites.', 'wp-staging');
                            break;
                        default:
                            debug_log('Activate License Response: ' . wp_strip_all_tags(json_encode($response)));
                            $message = sprintf(
                                __('This license key is not valid. You can buy one from %s or contact %s', 'wp-staging'),
                                '<a href="https://wp-staging.com/#pricing" target="_blank">wp-staging.com</a>',
                                '<a href="mailto:support@wp-staging.com" target="_blank">support@wp-staging.com</a>'
                            );
                            break;
                    }
                }
            }

            if (!empty($message)) {
                $baseUrl  = admin_url('admin.php?page=wpstg-license');
                $redirect = add_query_arg(['wpstg_licensing' => 'false', 'message' => urlencode($message)], $baseUrl);
                if (!empty($licenseData)) {
                    update_option(self::WPSTG_LICENSE_STATUS, $licenseData);
                }

                wp_redirect($redirect);
                exit();
            }

            // $licenseData->license will be either "valid" or "invalid"
            update_option(self::WPSTG_LICENSE_STATUS, $licenseData);
            wp_redirect(admin_url('admin.php?page=wpstg-license'));
            exit();
        }
    }

    /**
     * Deactivate the license key
     * @return void
     */
    public function deactivateLicense()
    {
        if (isset($_POST['wpstg_deactivate_license'])) {
            // Early bail if nonce is invalid
            if (!check_admin_referer('wpstg_license_nonce', 'wpstg_license_nonce')) {
                return;
            }

            $license = trim(get_option(self::WPSTG_LICENSE_KEY));

            $apiParams = [
                'edd_action' => 'deactivate_license',
                'license'    => $license,
                'item_name'  => urlencode(WPSTG_ITEM_NAME), // the name of our product in EDD
                'url'        => home_url()
            ];

            $response = wp_remote_post(
                WPSTG_STORE_URL,
                [
                    'timeout'   => 15,
                    'sslverify' => false,
                    'body'      => $apiParams
                ]
            );

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                if (is_wp_error($response)) {
                    $message = $response->get_error_message();
                } else {
                    $message = __('An error occurred, please try again.', 'wp-staging');
                }

                $baseUrl  = admin_url('admin.php?page=wpstg-license');
                $redirect = add_query_arg(['wpstg_licensing' => 'false', 'message' => urlencode($message)], $baseUrl);
                wp_redirect($redirect);
                exit();
            }

            $licenseData = json_decode(wp_remote_retrieve_body($response));
            if ($licenseData->license === 'deactivated' || $licenseData->license === 'failed') {
                delete_option(self::WPSTG_LICENSE_STATUS);
                delete_option(self::WPSTG_LICENSE_KEY);
            }

            wp_redirect(admin_url('admin.php?page=wpstg-license'));
            exit();
        }
    }

    /**
     * Check if license key is valid. Usually called via cron once per day
     *
     * @access  public
     * @return  void
     * @since   2.0.3
     */
    public function updateLicenseData()
    {
        if (empty($this->licenseKey)) {
            return;
        }

        $apiParams = [
            'edd_action' => 'check_license',
            'license'    => $this->licenseKey,
            'item_name'  => urlencode(WPSTG_ITEM_NAME),
            'url'        => home_url()
        ];

        $response = wp_remote_post(
            WPSTG_STORE_URL,
            [
                'timeout'   => 15,
                'sslverify' => false,
                'body'      => $apiParams
            ]
        );

        if (is_wp_error($response)) {
            return;
        }

        $licenseData = json_decode(wp_remote_retrieve_body($response));
        if (!empty($licenseData)) {
            update_option(self::WPSTG_LICENSE_STATUS, $licenseData);
        }
    }

    /**
     * This is a means of catching errors from the activation method above and displaying it to the customer
     * @return void
     */
    public function adminNotices()
    {
        static $isDisplayed = [];
        if (isset($_GET['wpstg_licensing']) && !empty($_GET['message'])) {
            $message = filter_input(INPUT_GET, 'message');

            if (!empty($isDisplayed[$message])) {
                return;
            }

            switch ($_GET['wpstg_licensing']) {
                case 'false':
                    ?>
                    <div class="wpstg--notice wpstg--error">
                        <p>
                        <?php
                            esc_html_e('WP STAGING - Can not activate license key! ', 'wp-staging');
                            echo wp_kses_post($message);
                        ?>
                        </p>
                    </div>
                    <?php
                    $isDisplayed[$message] = true;
                    break;
                case 'true':
                default:
                    // You can add a custom success message here if activation is successful
                    break;
            }
        }
    }

    /**
     * Most pro features are available even if a license has been expired.
     * The only requirement is that a license was valid in the past or still is it.
     * @return bool
     */
    public function isValidOrExpiredLicenseKey(): bool
    {
        if (wpstg_is_local()) {
            return true;
        }

        if (!($licenseData = get_option(self::WPSTG_LICENSE_STATUS))) {
            return false;
        }

        if (isset($licenseData->license) && $licenseData->license === self::LICENSE_VALID) {
            return true;
        }

        if (isset($licenseData->error) && $licenseData->error === self::LICENSE_EXPIRED) {
            return true;
        }

        return false;
    }


    /**
     * @return bool
     */
    public function isInvalidOrExpiredLicenseKey(): bool
    {

        if (!($licenseData = get_option(self::WPSTG_LICENSE_STATUS))) {
            return true;
        }

        if (isset($licenseData->error) && $licenseData->error === self::LICENSE_EXPIRED) {
            return true;
        }

        if (isset($licenseData->license) && $licenseData->license === self::LICENSE_VALID) {
            return false;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isBetaVersion(): bool
    {
        return defined('WPSTG_IS_BETA') && WPSTG_IS_BETA === true;
    }
}
