<?php

namespace WPStaging\Pro\Backup\Storage\Storages\SFTP;

use WPStaging\Backup\Exceptions\StorageException;
use WPStaging\Framework\Security\Auth as WPStagingAuth;
use WPStaging\Pro\Backup\Storage\AbstractStorage;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Pro\Backup\Storage\Storages\SFTP\Clients\ClientInterface;
use WPStaging\Pro\Backup\Storage\Storages\SFTP\Clients\FtpClient;
use WPStaging\Pro\Backup\Storage\Storages\SFTP\Clients\FtpCurlClient;
use WPStaging\Pro\Backup\Storage\Storages\SFTP\Clients\FtpException;
use WPStaging\Pro\Backup\Storage\Storages\SFTP\Clients\SftpClient;

use function WPStaging\functions\debug_log;

class Auth extends AbstractStorage
{
    /** @var string */
    const CONNECTION_TYPE_FTP  = 'ftp';

    /** @var string */
    const CONNECTION_TYPE_SFTP = 'sftp';

    /** @var int */
    const FTP_UPLOAD_MODE_PUT          = 1;

    /** @var int */
    const FTP_UPLOAD_MODE_APPEND       = 2;

    /** @var int */
    const FTP_UPLOAD_MODE_NON_BLOCKING = 3;

    /** @var Sanitize */
    protected $sanitize;

    /** @var ClientInterface */
    protected $client;

    public function __construct(WPStagingAuth $wpstagingAuth, Sanitize $sanitize)
    {
        parent::__construct($wpstagingAuth);
        $this->identifier = 'sftp';
        $this->label      = 'FTP / SFTP';
        $this->sanitize   = $sanitize;
    }

    public function authenticate()
    {
        // no-op
    }

    /**
     * @return bool
     */
    public function testConnection()
    {
        $options = [];

        $options['ftpType']         = isset($_POST['ftp_type']) ? $this->sanitize->sanitizeString($_POST['ftp_type']) : '';
        $options['host']            = isset($_POST['host']) ? $this->sanitize->sanitizeString($_POST['host']) : '';
        $options['port']            = isset($_POST['port']) ? $this->sanitize->sanitizeInt($_POST['port']) : '';
        $options['username']        = isset($_POST['username']) ? $this->sanitize->sanitizeString($_POST['username']) : '';
        $options['password']        = isset($_POST['password']) ? $this->sanitize->sanitizePassword($_POST['password']) : '';
        $options['passphrase']      = isset($_POST['passphrase']) ? $this->sanitize->sanitizePassword($_POST['passphrase']) : '';
        $options['key']             = isset($_POST['key']) ? $this->sanitize->sanitizeString($_POST['key'], $performURLDecode = false) : '';
        $options['ssl']             = !empty($_POST['ssl']) && $this->sanitize->sanitizeBool($_POST['ssl']);
        $options['passive']         = !empty($_POST['passive']) && $this->sanitize->sanitizeBool($_POST['passive']);
        $options['useFtpExtension'] = !empty($_POST['use_ftp_extension']) && $this->sanitize->sanitizeBool($_POST['use_ftp_extension']);
        $options['ftpMode']         = isset($_POST['ftp_mode']) ? $this->sanitize->sanitizeInt($_POST['ftp_mode']) : self::FTP_UPLOAD_MODE_PUT;
        $options['location']        = isset($_POST['location']) ? $this->sanitize->sanitizeString($_POST['location']) : null;

        $client = $this->getClient($options);
        if ($client === false) {
            return false;
        }

        $result = $client->login();
        if ($result === false) {
            debug_log("Test Connection Error: " . $client->getError());
            return $result;
        }

        $client->setMode($options['ftpMode']);
        $result = $this->isWriteableStoragePath($client, $options['location']);
        if ($result === false) {
            debug_log("(Test Connection) Backup path does not exist or has no write permission!");
            $this->error = 'Backup path does not exist or has no write permission!';
        }

        return $result;
    }

    /**
     * @param array $options Optional
     *
     * @return ClientInterface|false
     */
    public function getClient($options = null)
    {
        if ($options === null) {
            $options = $this->getOptions();
        }

        if ($options['ftpType'] === self::CONNECTION_TYPE_SFTP) {
            return new SftpClient($options['host'], $options['username'], $options['password'] ?: '', $options['key'] ?? '', $options['passphrase'] ?? '', $options['port']);
        }

        $useFtpExtension = array_key_exists('useFtpExtension', $options) ? $options['useFtpExtension'] : false;
        $useFtpExtension = apply_filters_deprecated(
            'wpstg.ftpclient.forceUseFtpExtension', // filter name
            [$useFtpExtension], // args including the default value
            '5.3.1', // version from which it is deprecated
            '', // new filter to use i.e. none in this case as we will remove this filter
            esc_html__('This filter will be removed in the upcoming version, use the option provided in FTP settings UI instead.', 'wp-staging')
        );
        if ($useFtpExtension === false) {
            try {
                $ftpClient = new FtpCurlClient($options['host'], $options['username'], $options['password'], $options['ssl'], $options['passive'], $options['port']);
                isset($options['ftpMode']) ? $ftpClient->setMode($options['ftpMode']) : $ftpClient->setMode(self::FTP_UPLOAD_MODE_PUT);
                return $ftpClient;
            } catch (FtpException $ex) {
                debug_log("Curl Extension Not Loaded");
            }
        }

        try {
            $ftpClient = new FtpClient($options['host'], $options['username'], $options['password'], $options['ssl'], $options['passive'], $options['port']);
            isset($options['ftpMode']) ? $ftpClient->setMode($options['ftpMode']) : $ftpClient->setMode(self::FTP_UPLOAD_MODE_PUT);
            return $ftpClient;
        } catch (FtpException $ex) {
            debug_log("FTP Extension Not Loaded");
        }

        return false;
    }

    /**
     * @param array<string, mixed> $settings
     * @return bool
     */
    public function updateSettings($settings)
    {
        $options         = $this->getOptions();
        $ftpType         = !empty($settings['ftp_type']) ? $this->sanitize->sanitizeString($settings['ftp_type']) : '';
        $host            = !empty($settings['host']) ? $this->sanitize->sanitizeString($settings['host']) : '';
        $port            = !empty($settings['port']) ? $this->sanitize->sanitizeInt($settings['port']) : '';
        $username        = !empty($settings['username']) ? $this->sanitize->sanitizeString($settings['username']) : '';
        $password        = !empty($settings['password']) ? $this->sanitize->sanitizePassword($settings['password']) : null;
        $key             = !empty($settings['key']) ? $this->sanitize->sanitizeString($settings['key'], $performURLDecode = false) : null;
        $ssl             = isset($settings['ssl']) ? $this->sanitize->sanitizeBool($settings['ssl']) : false;
        $passive         = isset($settings['passive']) ? $this->sanitize->sanitizeBool($settings['passive']) : false;
        $useFtpExtension = isset($settings['use_ftp_extension']) ? $this->sanitize->sanitizeBool($settings['use_ftp_extension']) : false;
        $ftpMode         = isset($settings['ftp_mode']) ? $this->sanitize->sanitizeInt($settings['ftp_mode']) : self::FTP_UPLOAD_MODE_PUT;
        $passphrase      = !empty($settings['passphrase']) ? $settings['passphrase'] : null;
        $location        = isset($settings['location']) ? sanitize_text_field($settings['location']) : null;
        $backupsToKeep   = isset($settings['max_backups_to_keep']) ? $this->sanitize->sanitizeInt($settings['max_backups_to_keep']) : 2;
        $backupsToKeep   = $backupsToKeep > 0 ? $backupsToKeep : 15;

        if (!in_array($ftpType, [self::CONNECTION_TYPE_FTP, self::CONNECTION_TYPE_SFTP])) {
            $this->error = 'Invalid FTP type!';
            return false;
        }

        if (!in_array($ftpMode, [self::FTP_UPLOAD_MODE_PUT, self::FTP_UPLOAD_MODE_APPEND, self::FTP_UPLOAD_MODE_NON_BLOCKING])) {
            $this->error = 'Invalid FTP mode!';
            return false;
        }

        $options['ftpType']          = $ftpType;
        $options['useFtpExtension']  = $useFtpExtension;
        $options['host']             = $host;
        $options['port']             = $port;
        $options['username']         = $username;
        $options['password']         = $password;
        $options['passphrase']       = $passphrase;
        $options['key']              = $key;
        $options['location']         = $location;
        $options['maxBackupsToKeep'] = $backupsToKeep;
        $options['ssl']              = $ssl;
        $options['passive']          = $passive;
        $options['ftpMode']          = $ftpMode;

        $options['isAuthenticated'] = false;

        $client = $this->getClient($options);
        if ($client === false) {
            $options['lastUpdated'] = time();
            return $this->saveOptions($options);
        }

        $result = $client->login();
        if ($result === false) {
            debug_log($client->getError());
        }

        $options['isAuthenticated'] = $result;
        $options['lastUpdated']     = time();

        return $this->saveOptions($options);
    }

    /**
     * Clean all FTP / SFTP Settings,
     * Also unauthenticate the provider
     *
     * @return bool|array
     */
    public function revoke()
    {
        if (!$this->wpstagingAuth->isAuthenticatedRequest()) {
            return false;
        }

        $options = $this->getOptions();

        // Early bail if already unauthenticated
        if ($options['isAuthenticated'] === false) {
            return true;
        }

        $options['isAuthenticated'] = false;
        $options['ftpType']         = '';
        $options['useFtpExtension'] = false;
        $options['host']            = '';
        $options['username']        = '';
        $options['password']        = '';
        $options['key']             = '';
        $options['passphrase']      = '';
        $options['location']        = '';

        return parent::saveOptions($options);
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        $this->error  = '';
        $options      = $this->getOptions();
        $this->client = $this->getClient($options);
        $this->client->login();

        $files = [];
        try {
            $files = $this->client->getFiles($options['location']);
        } catch (StorageException $ex) {
            $this->error = $this->client->getError();
            return [];
        }

        return $files;
    }

    /**
     * @return bool
     */
    public function cleanBackups()
    {
        $options = $this->getOptions();
        $files   = $this->getFiles();
        $this->client->setPath($options['location']);
        foreach ($files as $file) {
            $result = $this->client->deleteFile($file['name']);
            if ($result === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $file
     * @return bool
     */
    public function deleteFile(string $file): bool
    {
        $options = $this->getOptions();
        $this->client->setPath($options['location']);

        return $this->client->deleteFile($file);
    }

    /**
     * @param ClientInterface $client
     * @param string $path absolute path to check
     * @return bool
     */
    public function isWriteableStoragePath(ClientInterface $client, string $path): bool
    {
        $this->error  = '';
        $testFileName = 'wpstaging-write-upload-test.file';
        $txtToUpload  = 'testing upload';
        $result       = $client->upload($path, $testFileName, $txtToUpload);
        if (!$result || !empty($this->error)) {
            debug_log("Ftp's path checking: fail to create test file!");
            return false;
        }

        $files = [];
        try {
            $files = $client->getFiles($path);
        } catch (StorageException $ex) {
            debug_log('Unable to fetch files: ' . $ex->getMessage());
            return false;
        }

        $fileUploaded = false;
        foreach ($files as $file) {
            if ($file['name'] !== $testFileName) {
                continue;
            }

            $fileUploaded = true;
            if ($file['size'] !== strlen($txtToUpload)) {
                debug_log("Ftp's path checking: file size not matched on upload!");
                return false;
            }
        }

        if (!$fileUploaded) {
            debug_log("Ftp's path checking: fail to upload test file!");
            return false;
        }

        $client->login();
        $txtToAppend  = 'testing append on existing upload';
        $result       = $client->upload($path, $testFileName, $txtToAppend, strlen($txtToUpload));
        if (!$result || !empty($this->error)) {
            debug_log("Ftp's path checking: fail to append test file!");
            $client->deleteFile(trailingslashit($path) . $testFileName);
            return false;
        }

        $files = $client->getFiles($path);
        $fileUploaded = false;
        foreach ($files as $file) {
            if ($file['name'] !== $testFileName) {
                continue;
            }

            $fileUploaded = true;
            if ($file['size'] !== strlen($txtToUpload . $txtToAppend)) {
                debug_log("Ftp's path checking: fail size not matched on append!");
                $client->deleteFile(trailingslashit($path) . $testFileName);
                return false;
            }
        }

        if (!$fileUploaded) {
            debug_log("Ftp's path checking: fail to append test file!");
        }

        $result = $client->deleteFile(trailingslashit($path) . $testFileName);
        if (!$result || !empty($this->error)) {
            debug_log("Ftp's path checking: fail to delete test file!");
            return false;
        }

        return true;
    }
}
