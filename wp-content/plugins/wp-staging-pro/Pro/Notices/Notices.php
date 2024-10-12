<?php

namespace WPStaging\Pro\Notices;

use WPStaging\Framework\Notices\Notices as NoticesBase;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Traits\NoticesTrait;
use WPStaging\Framework\Utils\Cache\TransientCache;
use WPStaging\Framework\Utils\ServerVars;
use WPStaging\Framework\Utils\PluginInfo;
use WPStaging\Backup\Service\BackupsFinder;

/**
 * Class Notices
 * @package WPStaging\Pro\Notices
 */
class Notices
{
    use NoticesTrait;

    /** @var bool */
    protected $multisite = false;

    /** @var object */
    private $license;

    /** @var bool */
    private $showAllNotices;

    /** @var string */
    private $proNoticesViewPath;

    /** @var SiteInfo */
    private $siteInfo;

    /** @var TransientCache */
    private $transientCache;

    /** @var BackupsFinder */
    private $backupsFinder;

    /** @var BackupsDifferentPrefixNotice */
    private $backupsPrefixNotice;

    /** @var EntireNetworkCloneServerConfigNotice */
    private $entireNetworkCloneServerConfigNotice;

    /** @var PluginInfo */
    private $pluginInfo;

    /**
     * @param TransientCache $transientCache
     * @param BackupsFinder $backupsFinder
     * @param SiteInfo $siteInfo
     */
    public function __construct(TransientCache $transientCache, BackupsFinder $backupsFinder, SiteInfo $siteInfo, PluginInfo $pluginInfo)
    {
        $this->transientCache = $transientCache;
        $this->backupsFinder  = $backupsFinder;
        $this->siteInfo       = $siteInfo;
        $this->pluginInfo     = $pluginInfo;
        $this->showAllNotices = NoticesBase::SHOW_ALL_NOTICES;

        $this->proNoticesViewPath = $this->getPluginPath() . "Backend/Pro/views/notices/";
        $this->noticesViewPath    = $this->getPluginPath() . "Backend/views/notices/";
        $this->multisite          = is_multisite();

        // To avoid dependency injection hell, we use WPStaging::make() to instantiate the notices
        $this->backupsPrefixNotice                  = WPStaging::make(BackupsDifferentPrefixNotice::class);
        $this->entireNetworkCloneServerConfigNotice = WPStaging::make(EntireNetworkCloneServerConfigNotice::class);
    }

    /** @return void */
    public function renderNotices()
    {
        $this->license = get_option('wpstg_license_status');

        // Don't show on staging sites but on all pages to all users
        if (!$this->siteInfo->isStagingSite() && $this->pluginInfo->canShowAdminMenu()) {
            $this->getLicenseKeyInvalidNotice();
        }

        $this->backupsDifferentPrefixNotice();
        $this->entireNetworkCloneServerConfigNotice();
        $this->backupsDifferentPrefixMultisiteNotice();
        $this->backupInvalidFileIndexNotice();

        // Show only on WP STAGING admin pages and to administrators
        if ($this->showAllNotices || (current_user_can("update_plugins") && $this->isWPStagingAdminPage())) {
            $this->getLicenseKeyExpiredNotice();
            $this->getWPVersionCompatibleNotice();
            $this->showGoogleUnauthenticatedNotice();
        }
    }

    /**
     * Show notice if backup is created on version 4.0.2 or lower
     */
    public function backupsDifferentPrefixNotice()
    {
        if ($this->showAllNotices || $this->backupsPrefixNotice->isEnabled()) {
            require $this->proNoticesViewPath . "backups-different-prefix.php";
        }
    }

    /**
     * Show notice if entire clone and main site
     */
    public function entireNetworkCloneServerConfigNotice()
    {
        if ($this->showAllNotices || $this->entireNetworkCloneServerConfigNotice->isEnabled()) {
            // Lazy initialization of ServerVars to reduce memory usage
            /** @var ServerVars */
            $serverVars = WPStaging::make(ServerVars::class);
            $server = $serverVars->getServerSoftware();
            $isApache = $serverVars->isApache();

            require $this->proNoticesViewPath . "entire-clone-server-config.php";
        }
    }

    /**
     * Show license key invalid notice on all admin pages to all users
     */
    public function getLicenseKeyInvalidNotice()
    {
        // Customer never used any valid license key at all. A valid (expired) license key is needed to make use of all wp staging pro features
        // So show this admin notice on all pages to make sure customer is aware that license key must be entered
        if (!$this->showAllNotices && get_site_transient('wpstgDisableLicenseNotice')) {
            // When activating the plugin for the first time, do not show the license notice.
            // Instead, we show a friendly notice telling the user to enter the license.
            delete_site_transient('wpstgDisableLicenseNotice');
            return;
        }

        if ($this->showAllNotices || ((isset($this->license->error) && $this->license->error !== 'expired') || $this->license === false)) {
            require_once $this->proNoticesViewPath . 'license-key-invalid.php';
        }
    }

    /** @return bool */
    public function getIsMultisite()
    {
        return $this->multisite;
    }

    /**
     * Show warning if license key is expired on WP STAGING admin pages only
     */
    public function getLicenseKeyExpiredNotice()
    {
        if ($this->showAllNotices || (isset($this->license->error) && $this->license->error === 'expired') || (isset($this->license->license) && $this->license->license === 'expired')) {
            $licensekey = get_option('wpstg_license_key', '');
            require_once $this->proNoticesViewPath . 'license-key-expired.php';
        }
    }

    /**
     * Show notice if google drive has been unauthenticated unexpectedly.
     */
    public function showGoogleUnauthenticatedNotice()
    {
        $googleDriveSettings = (array)get_option('wpstg_googledrive', []);
        if ($this->showAllNotices || !empty($googleDriveSettings['showNotice'])) {
            require $this->proNoticesViewPath . "google-unauthenticated.php";
        }
    }

    protected function backupInvalidFileIndexNotice()
    {
        $isInvalidBackup = $this->transientCache->get(TransientCache::KEY_INVALID_BACKUP_FILE_INDEX, 3600, [$this->backupsFinder, 'hasInvalidFileIndex']);

        if ($this->showAllNotices || $isInvalidBackup) {
            require $this->proNoticesViewPath . "backup-invalid-files-index.php";
        }
    }

    /**
     * Show warning if WordPress version is not supported
     */
    private function getWPVersionCompatibleNotice()
    {
        if ($this->showAllNotices || version_compare(WPStaging::getInstance()->get('WPSTG_COMPATIBLE'), get_bloginfo("version"), "<")) {
            require_once $this->proNoticesViewPath . 'wp-version-compatible-message.php';
        }
    }

    /**
     * Show notice if backup is created on version 4.3.0 or lower
     */
    private function backupsDifferentPrefixMultisiteNotice()
    {
        if ($this->showAllNotices || (is_multisite() && $this->backupsPrefixNotice->isEnabled())) {
            require $this->proNoticesViewPath . "backups-different-prefix.php";
        }
    }
}
