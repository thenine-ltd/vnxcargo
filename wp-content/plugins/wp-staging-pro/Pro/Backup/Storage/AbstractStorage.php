<?php

namespace WPStaging\Pro\Backup\Storage;

use Exception;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\DataEncryption;
use WPStaging\Vendor\GuzzleHttp\Client as GuzzleClient;
use WPStaging\Backup\BackupRetentionHandler;
use WPStaging\Backup\Storage\Providers;

use function WPStaging\functions\debug_log;

abstract class AbstractStorage
{
    /** @var string */
    protected $identifier;

    /** @var string */
    protected $label;

    /** @var string */
    protected $error;

    /** @var Auth */
    protected $wpstagingAuth;

    /** @var BackupRetentionHandler */
    protected $backupRetention;

    /** @var Providers */
    protected $storageProviders;

    public function __construct(Auth $wpstagingAuth)
    {
        $this->wpstagingAuth = $wpstagingAuth;
        $this->backupRetention = WPStaging::make(BackupRetentionHandler::class);
        $this->storageProviders = WPStaging::make(Providers::class);
    }

    abstract public function authenticate();
    abstract public function testConnection();
    abstract public function revoke();
    abstract public function cleanBackups();
    abstract public function getFiles();
    abstract public function updateSettings($settings);
    abstract public function deleteFile(string $file): bool;

    /**
     * Compute the Hash of the file for the given provider
     * Should be overridden by each provider
     *
     * @param string $file
     * @return string
     */
    public function computeFileHash(string $file): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return BackupRetentionHandler
     */
    public function getBackupRetentionHandler(): BackupRetentionHandler
    {
        return $this->backupRetention;
    }

    /**
     * @return array
     */
    public function getRetainedBackups(): array
    {
        return $this->backupRetention->getBackupsRetention($this->storageProviders->getStorageByIdentifier($this->identifier));
    }

    /**
     * @param string retainedBackupId
     *
     * @return bool
     */
    public function unsetStorageFromRetainedBackups(string $retainedBackupId): bool
    {
        return $this->backupRetention->unsetStorageFromBackupsRetention($retainedBackupId, (string)$this->storageProviders->getStorageByIdentifier($this->identifier));
    }

    /**
     * Check if the storage is authenticated or not
     *
     * @return bool Returns true if the storage is authenticated, false otherwise
     */
    public function isAuthenticated()
    {
        $options = $this->getOptions();
        if (isset($options['isAuthenticated'])) {
            return $options['isAuthenticated'];
        }

        return false;
    }

    public function getOptions()
    {
        $optionName  = $this->getOptionName();
        $optionValue = get_option($optionName, []);
        return $this->decryptCredential($optionName, $optionValue);
    }

    private function getOptionName()
    {
        return 'wpstg_' . $this->identifier;
    }

    /**
     * Save the storage configuration
     *
     * @param $options
     *
     * @return bool Returns true if the value was updated, false otherwise
     */
    public function saveOptions($options = [])
    {
        $optionName  = $this->getOptionName();
        $optionValue = $options;

        if (apply_filters('wpstg.framework.security.dataEncryption', true)) {
            $optionValue = $this->encryptCredential($optionName, $options);
        }

        return update_option($optionName, $optionValue, false);
    }

    /**
     * @return void
     */
    public function showAdminNotices()
    {
        $this->showAuthenticateSuccessFailureMessage();
        $this->showAuthenticatedUserInfo();
    }

    /**
     * Display storage success message
     *
     * @return void
     */
    public function showAuthenticateSuccessFailureMessage()
    {
        if (empty($_GET['auth-storage']) || empty($_GET['sub']) || strtolower(sanitize_text_field($_GET['sub'])) !== strtolower($this->identifier) || !$this->isAuthenticated()) {
            return;
        }

        if (!current_user_can(WPStaging::make(Capabilities::class)->manageWPSTG())) {
            return;
        }

        switch ($_GET['auth-storage']) {
            case 'true':
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php printf(esc_html__('The %s storage is authenticated successfully!', 'wp-staging'), esc_html($this->label)); ?>
                    </p>
                </div>
                <?php
                break;
            case 'false':
                ?>
                <div class="wpstg--notice wpstg--error is-dismissible">
                    <p>
                        <?php printf(esc_html__('The %s storage authentication failed!', 'wp-staging'), esc_html($this->label)); ?>
                    </p>
                </div>
                <?php
                break;
        }
    }

    /**
    * @return void
    */
    public function showAuthenticatedUserInfo()
    {
        if (empty($_GET['sub']) || strtolower(sanitize_text_field($_GET['sub'])) !== strtolower($this->identifier)) {
            return;
        }

        if (!current_user_can(WPStaging::make(Capabilities::class)->manageWPSTG())) {
            return;
        }

        $options = $this->getOptions();
        if (empty($options['isAuthenticated']) || empty($options['userData']) || empty($options['storageInfo'])) {
            return;
        }

        $userData         = $options['userData'];
        $storageUsed      = $options['storageInfo']['used'];
        $storageAvailable = $options['storageInfo']['allocation']['allocated'] - $storageUsed;
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php printf(esc_html__('Name: %s. %s quota usage: %s used,  %s available.', 'wp-staging'), esc_html($userData['displayName']), esc_html($this->label), esc_html(size_format($storageUsed, 2)), esc_html(size_format($storageAvailable, 2))); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Whether Guzzle available to work
     *
     * @return bool
     */
    public function isGuzzleAvailable()
    {
        try {
            $http = new GuzzleClient([
                "verify" => $this->getCertPath()
            ]);
        } catch (Exception $ex) {
            debug_log($ex->getMessage());
            return false;
        }

        return true;
    }

    /** @return string */
    public function getCertPath()
    {
        return WPSTG_PLUGIN_DIR . 'Pro/Backup/cacert.pem';
    }

    /** @return string */
    public function getError()
    {
        return $this->error;
    }

    /** @return array */
    private function getCredentialOptionKeys()
    {
        return [
            'wpstg_googledrive'        => [
                'googleClientId',
                'googleClientSecret'
            ],
            'wpstg_sftp'               => [
                'username',
                'password',
                'key',
                'passphrase'
            ],
            'wpstg_amazons3'           => [
                'accessKey',
                'secretKey'
            ],
            'wpstg_digitalocean-space' => [
                'accessKey',
                'secretKey'
            ],
            'wpstg_wasabi'             => [
                'accessKey',
                'secretKey'
            ],
            'wpstg_generic-s3'         => [
                'accessKey',
                'secretKey'
            ]
        ];
    }

    /** @return object */
    private function dataEncryption()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new DataEncryption();
        }

        return $inst;
    }

    /** @return bool */
    public function isEncrypted()
    {
        $optionName     = $this->getOptionName();
        $optionValue    = $this->getOptions();
        $credentialKeys = $this->getCredentialOptionKeys();
        if (!empty($optionValue) && is_array($optionValue) && array_key_exists($optionName, $credentialKeys)) {
            foreach ($credentialKeys[$optionName] as $key) {
                if (!empty($optionValue[$key]) && $this->dataEncryption()->isEncrypted($optionValue[$key])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $optionName
     * @param mixed $optionValue
     * @return array
     */
    private function encryptCredential($optionName, $optionValue)
    {
        $credentialKeys = $this->getCredentialOptionKeys();
        if ($optionValue === '' || !array_key_exists($optionName, $credentialKeys)) {
            return $optionValue;
        }

        if (empty($optionValue) || !is_array($optionValue)) {
            return $optionValue;
        }

        foreach ($credentialKeys[$optionName] as $key) {
            if (!empty($optionValue[$key])) {
                $optionValue[$key] = $this->dataEncryption()->encrypt($optionValue[$key]);
            }
        }

        return $optionValue;
    }

    /**
     * @param string $optionName
     * @param mixed $optionValue
     * @return array
     */
    private function decryptCredential($optionName, $optionValue)
    {
        $credentialKeys = $this->getCredentialOptionKeys();
        if ($optionValue === '' || !array_key_exists($optionName, $credentialKeys)) {
            return $optionValue;
        }

        if (empty($optionValue) || !is_array($optionValue)) {
            return $optionValue;
        }

        foreach ($credentialKeys[$optionName] as $key) {
            if (!empty($optionValue[$key])) {
                $optionValue[$key] = $this->dataEncryption()->decrypt($optionValue[$key]);
            }
        }

        return $optionValue;
    }
}
