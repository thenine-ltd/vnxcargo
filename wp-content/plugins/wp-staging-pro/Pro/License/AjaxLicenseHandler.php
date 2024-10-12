<?php

namespace WPStaging\Pro\License;

use WPStaging\Backend\Pro\Licensing\Licensing;
use WPStaging\Framework\Security\Auth;

class AjaxLicenseHandler
{
    /**
     * @var string
     */
    private $licenseKey = '';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var Licensing
     */
    private $licensing;

    /**
     * @param Auth $auth
     * @param Licensing $licensing
     */
    public function __construct(Auth $auth, Licensing $licensing)
    {
        $this->licensing  = $licensing;
        $this->auth       = $auth;
        $this->licenseKey = trim(get_option($this->licensing::WPSTG_LICENSE_KEY));
    }


    /**
     * @return void
     */
    public function ajaxRefreshLicenseStatus()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        // Early bail if license key missing
        if (empty($this->licenseKey)) {
            wp_send_json(['success' => false]);
        }

        $this->licensing->updateLicenseData();
        $licenseStatus = get_option($this->licensing::WPSTG_LICENSE_STATUS);
        if (empty($licenseStatus)) {
            wp_send_json(['success' => false]);
        }

        wp_send_json(['success' => true]);
    }
}
