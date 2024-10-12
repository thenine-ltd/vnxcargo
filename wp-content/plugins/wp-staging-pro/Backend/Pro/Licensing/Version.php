<?php

namespace WPStaging\Backend\Pro\Licensing;

class Version
{
    const TRANSIENT_NAME_DAILY_VERSION_UPDATE  = 'wpstg_daily_version_update';
    const TRANSIENT_NAME_WEEKLY_VERSION_UPDATE = 'wpstg_weekly_version_update';

    /**
     * @var Licensing
     */
    private $licensing;

    /**
     * @param Licensing $licensing
     */
    public function __construct(Licensing $licensing)
    {
        // Load some hooks
        add_action('wpstg_daily_event', [$this, 'updateDailyLatestWPStagingVersion']);
        add_action('wpstg_weekly_event', [$this, 'updateWeeklyLatestWPStagingVersion']);

        // For testing the hooks above uncomment these lines
        //add_action('admin_init', [$this, 'updateDailyLatestWPStagingVersion']);
        //add_action('admin_init', [$this, 'updateWeeklyLatestWPStagingVersion']);

        // this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
        if (!defined('WPSTG_STORE_URL')) {
            define('WPSTG_STORE_URL', 'https://wp-staging.com');
        }

        $this->licensing = $licensing;
    }

    /**
     * Get and store the latest WP Staging version every day.
     * Call only the wp-staging.com API daily if the license is valid and not expired.
     *
     * @access  public
     * @return  void
     * @since   2.0.3
     */
    public function updateDailyLatestWPStagingVersion()
    {
        // Bail if license is expired or invalid to reduce number of API requests to wp-staging.com
        if ($this->licensing->isInvalidOrExpiredLicenseKey()) {
            return;
        }

        if ($this->alreadyMadeApiRequest(DAY_IN_SECONDS, self::TRANSIENT_NAME_DAILY_VERSION_UPDATE)) {
            return;
        }

        $this->requestApiAndUpdateVersionNumber();
    }


    /**
     * Get and store the latest WP Staging version every 7 days for all licenses that are invalid or expired.
     *
     * @access  public
     * @return  void
     * @since   2.0.3
     */
    public function updateWeeklyLatestWPStagingVersion()
    {
        // Bail if license is valid to reduce number of API requests to wp-staging.com
        if (!$this->licensing->isInvalidOrExpiredLicenseKey()) {
            return;
        }

        if ($this->alreadyMadeApiRequest(WEEK_IN_SECONDS, self::TRANSIENT_NAME_WEEKLY_VERSION_UPDATE)) {
            return;
        }

        $this->requestApiAndUpdateVersionNumber();
    }

    /**
     * @param int $interval
     * @param string $transientName
     * @return bool
     */
    private function alreadyMadeApiRequest(int $interval, string $transientName): bool
    {
        $transient = get_transient($transientName);

        if ($transient) {
            return true;
        }

        set_transient($transientName, true, $interval);
        return false;
    }

    /**
     * @return void
     */
    private function requestApiAndUpdateVersionNumber()
    {
        $api_params = [
            'edd_action' => 'get_version',
            'item_id'    => 11,
            'url'        => home_url()
        ];

        $response = wp_remote_post(
            WPSTG_STORE_URL,
            [
                'timeout'   => 15,
                'sslverify' => false,
                'body'      => $api_params,
            ]
        );

        if (is_wp_error($response)) {
            return;
        }

        $license = json_decode(wp_remote_retrieve_body($response));
        update_option('wpstg_version_latest', $license->stable_version);
    }
}
