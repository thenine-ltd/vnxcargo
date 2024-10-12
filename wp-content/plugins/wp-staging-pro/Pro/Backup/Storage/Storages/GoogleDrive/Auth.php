<?php

namespace WPStaging\Pro\Backup\Storage\Storages\GoogleDrive;

use Exception;
use InvalidArgumentException;
use WPStaging\Framework\Security\Auth as WPStagingAuth;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Backup\BackupProcessLock;
use WPStaging\Backup\Exceptions\ProcessLockedException;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Pro\Backup\Storage\AbstractStorage;
use WPStaging\Vendor\Google\Client as GoogleClient;
use WPStaging\Vendor\Google\Service\Drive as GoogleDriveService;
use WPStaging\Vendor\GuzzleHttp\Client as GuzzleClient;
use WPStaging\Vendor\GuzzleHttp\Exception\ClientException;
use WPStaging\Vendor\GuzzleHttp\Exception\RequestException;
use WPStaging\Vendor\Google\Service\Drive\FileList;
use WPStaging\Vendor\GuzzleHttp\Exception\ConnectException;

use function WPStaging\functions\debug_log;

class Auth extends AbstractStorage
{
    /** @var string */
    const REDIRECT_URL = 'https://auth.wp-staging.com/googledrive';

    /** @var string */
    const REFRESH_URL = 'https://auth.wp-staging.com/googledrive/refresh';

    /** @var string */
    const CLIENT_ID = '742905498798-io5jrk3au4fi1qeu9u3c3krbro97ofl1.apps.googleusercontent.com';

    /** @var string */
    const FOLDER_NAME = 'wpstaging-backups';

    /** @var string */
    const GOOGLEDRIVE_NONCE_KEY = 'wpstg-googledrive-nonce';

    /** @var GoogleClient */
    private $client;

    /** @var string */
    private $redirectURI;

    /** @var BackupProcessLock */
    private $backupProcessLock;

    /** @var Sanitize */
    protected $sanitize;

    public function __construct(GoogleClient $client, BackupProcessLock $backupProcessLock, Sanitize $sanitize, WPStagingAuth $wpstagingAuth)
    {
        parent::__construct($wpstagingAuth);
        $this->identifier = 'googledrive';
        $this->label = 'Google Drive';
        $this->redirectURI = add_query_arg(
            [
                'action' => 'wpstg-googledrive-api-auth',
            ],
            network_admin_url('admin-post.php')
        );
        $this->client = $client;
        $this->backupProcessLock = $backupProcessLock;
        $this->maybeOverrideClientConfig();
        $this->sanitize = $sanitize;
    }

    /**
     * Get Google Authorization URL
     *
     * @return string|false Google Authentication URL
     */
    public function getAuthenticationURL()
    {
        $this->client->setApprovalPrompt('force');
        $state = add_query_arg(
            [
                'tab'    => 'remote-storages',
                'action' => 'wpstg-googledrive-auth',
                'sub' => 'googledrive',
                'nonce' => wp_create_nonce(self::GOOGLEDRIVE_NONCE_KEY),
            ],
            admin_url('admin-post.php')
        );
        $this->client->setState($state);

        try {
            return $this->client->createAuthUrl();
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * @return void
     */
    public function testConnection()
    {
        // no-op
    }

    /**
     * Authentication of the storage
     * @return void
     */
    public function authenticate()
    {
        if (!$this->wpstagingAuth->isAuthenticatedRequest(self::GOOGLEDRIVE_NONCE_KEY)) {
            return;
        }

        $options = $this->getOptions();
        $options = array_merge($options, [
            'isAuthenticated' => true,
            'refreshToken'    => isset($_GET['refresh-token']) ? $this->sanitize->decodeBase64AndSanitize($_GET['refresh-token']) : '',
            'accessToken'     => isset($_GET['access-token']) ? $this->sanitize->decodeBase64AndSanitize($_GET['access-token']) : '',
            'expiresIn'       => isset($_GET['expires-in']) ? $this->sanitize->sanitizeInt($_GET['expires-in']) : 0,
            'created'         => isset($_GET['created']) ? $this->sanitize->sanitizeInt($_GET['created']) : 0,
            'showNotice'      => false
        ]);
        parent::saveOptions($options);

        $this->saveStorageAccountInfo();

        $redirectURL = add_query_arg(
            [
                'page' => 'wpstg-settings',
                'tab' => 'remote-storages',
                'sub' => 'googledrive',
                'auth-storage' => 'true',
            ],
            admin_url('admin.php')
        );

        wp_redirect($redirectURL);
    }

    /**
     * Save storage account info in options(displayed in storage page)
     *
     * @return void
     */
    public function saveStorageAccountInfo()
    {
        $options                                           = $this->getOptions();
        $options['userData']['displayName']                = $this->getUserData(null, 'displayName');
        $options['storageInfo']['used']                    = $this->getStorageInfo(null, 'usageInDrive');
        $options['storageInfo']['allocation']['allocated'] = $this->getStorageInfo(null, 'limit');
        if (!$options['userData']['displayName'] || !$options['storageInfo']['used'] || !$options['storageInfo']['allocation']['allocated']) {
            return;
        }

        $this->saveOptions($options);
    }

    /**
     * Authenticate when user set his own API credentials
     */
    public function apiAuthenticate()
    {
        $options = $this->getOptions();
        $googleClient = new GoogleClient();
        $googleClient->setClientId($options['googleClientId']);
        $googleClient->setClientSecret($options['googleClientSecret']);
        $googleClient->setRedirectUri($this->redirectURI);
        $authorizedScopesRequiredAsArr = [
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/drive.file'
        ];

        $googleClient->setScopes($authorizedScopesRequiredAsArr);
        $googleClient->setAccessType('offline');

        $userAuthorizedScopesAsStr     = filter_input(INPUT_GET, 'scope');
        $userAuthorizedScopesAsArr     = array_filter(explode(' ', $userAuthorizedScopesAsStr));
        $isAuthorizedAllRequiredScopes = true;
        foreach ($authorizedScopesRequiredAsArr as $authorizedScopesRequired) {
            if (!in_array($authorizedScopesRequired, $userAuthorizedScopesAsArr)) {
                $isAuthorizedAllRequiredScopes = false;
                break;
            }
        }

        if (!$isAuthorizedAllRequiredScopes) {
            echo sprintf('<strong style="font-family: arial,sans-serif;font-size:12px;">%s</strong>', esc_html__('You have not granted permissions required by the WP STAGING plugin. Please go back and retry the authorization.', 'wp-staging'));
            die;
        }

        $code  = isset($_GET['code']) ? $this->sanitize->sanitizeString($_GET['code']) : '';
        $token = $googleClient->fetchAccessTokenWithAuthCode($code);
        $state = filter_input(INPUT_GET, 'state', FILTER_SANITIZE_URL);
        if (empty($token)) {
            $urlToRedirect = $state . '&action=wpstg-googledrive-auth-fail';
        } else {
            $urlToRedirect = add_query_arg([
                'action'        => 'wpstg-googledrive-auth',
                'access-token'  => base64_encode($token['access_token']),
                'refresh-token' => base64_encode($token['refresh_token']),
                'expires-in'    => intval($token['expires_in']),
                'created'       => intval($token['created']),
                'nonce'         => wp_create_nonce(self::GOOGLEDRIVE_NONCE_KEY),
            ], $state);
        }

        header('Location: ' . $urlToRedirect);
    }

    /**
     * @param array $settings
     * @return bool
     */
    public function updateSettings($settings)
    {
        $options                       = $this->getOptions();
        $backupLocation                = isset($settings['folder_name']) ? $settings['folder_name'] : self::FOLDER_NAME;
        $options['folderName']         = $this->sanitizeGoogleDriveLocation($backupLocation);
        $options['maxBackupsToKeep']   = isset($settings['max_backups_to_keep']) ? $settings['max_backups_to_keep'] : 0;
        $options['maxBackupsToKeep']   = $options['maxBackupsToKeep'] > 0 ? $options['maxBackupsToKeep'] : 15;
        $options['googleClientId']     = isset($settings['google_client_id']) ? $settings['google_client_id'] : '';
        $options['googleClientSecret'] = isset($settings['google_client_secret']) ? $settings['google_client_secret'] : '';

        $options['lastUpdated'] = time();

        return $this->saveOptions($options);
    }

    /**
     * Get folders from Google Drive Location
     *
     * @param string $backupLocation
     *
     * @return array
     */
    public function getFoldersFromLocation($backupLocation)
    {
        $locationURI = explode('/', $backupLocation);
        return array_filter(array_map('trim', $locationURI), function ($folder) {
            return !empty($folder);
        });
    }

    /**
     * @param  GoogleDriveService $service
     * @param  string $attr
     * @return mixed
     */
    public function getUserData($service = null, $attr = null)
    {
        if ($service === null) {
            $service = new GoogleDriveService($this->setClientWithAuthToken());
        }

        $userField = $service->about->get(['fields' => 'user']);
        if (!method_exists($userField, 'getUser')) {
            return false;
        }

        $res = $userField->getUser();
        if ($attr && isset($res->$attr)) {
            return $res->$attr;
        }

        return $res;
    }

    /**
     * @param  GoogleDriveService $service
     * @param  string $attr
     * @return mixed
     */
    public function getStorageInfo($service = null, $attr = null)
    {
        if ($service === null) {
            $service = new GoogleDriveService($this->setClientWithAuthToken());
        }

        $storageField = $service->about->get(['fields' => 'storageQuota']);
        if (!method_exists($storageField, 'getStorageQuota')) {
            return false;
        }

        $res = $storageField->getStorageQuota();
        if ($attr && isset($res->$attr)) {
            return $res->$attr;
        }

        return $res;
    }

    /**
     * Revoke both access and refresh token,
     * Also unauthenticate the provider
     *
     * @return bool
     */
    public function revoke(): bool
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to do this.', 'wp-staging'));
        }

        $options = $this->getOptions();
        if ($options['refreshToken'] !== '') {
            // revoke refresh token
            try {
                $this->client->revokeToken($options['refreshToken']);
            } catch (Exception $ex) {
                debug_log("Fail to revoke google drive token. Error: " . $ex->getMessage());
            }
        }

        $options                    = [];
        $options['isAuthenticated'] = false;
        $options['accessToken']     = '';
        $options['refreshToken']    = '';

        return parent::saveOptions($options);
    }

    /**
     * @return GoogleClient
     */
    public function setClientWithAuthToken()
    {
        $options     = $this->getOptions();
        $accessToken = [
            'access_token' => isset($options['accessToken']) ? $options['accessToken'] : 'someInvalidToken',
            'expires_in'   => isset($options['expiresIn']) ? $options['expiresIn'] : 3599,
            'created'      => (isset($options['created']) && is_int($options['created'])) ? $options['created'] : time() - 3600
        ];

        try {
            $this->client->setAccessToken($accessToken);
        } catch (InvalidArgumentException $ex) {
            debug_log($ex->getMessage());
            return $this->client;
        }

        if (!$this->client->isAccessTokenExpired()) {
            return $this->client;
        }

        // Check whether the backup process running
        try {
            $this->backupProcessLock->checkProcessLocked();
            $isBackupProcessRunning = true;
        } catch (ProcessLockedException $e) {
            $isBackupProcessRunning = false;
        }

        // Early bail if backup process not running
        // Filter wpstg.tests.googleAuth.accessToken.validateOnEachRequest is for internal use only
        if (!$isBackupProcessRunning && !Hooks::applyFilters('wpstg.tests.googleAuth.accessToken.validateOnEachRequest', false)) {
            return $this->client;
        }

        $clientSecret = isset($options['googleClientSecret']) ? $options['googleClientSecret'] : '';
        $refreshResult = true;
        if (apply_filters('wpstg.backup.storage.googledrive.client_secret', $clientSecret) === '') {
            $refreshResult = $this->refreshAccessTokenRemotely($options['refreshToken']);
        } else {
            $refreshResult = $this->refreshAccessToken($options['refreshToken']);
        }

        if (!$refreshResult) {
            $this->showLoginIssueNotice();
            $this->revoke();
            debug_log("Fail to refresh google drive access and refresh token.");
        }

        return $this->client;
    }

    /** @return array */
    public function getFiles()
    {
        $this->setClientWithAuthToken();
        $options        = $this->getOptions();
        $backupLocation = isset($options['folderName']) ? $options['folderName'] : self::FOLDER_NAME;
        $service        = new GoogleDriveService($this->client);
        $folderId       = $this->getFolderIdByLocation($backupLocation, $service);

        if (!$folderId) {
            return [];
        }

        try {
            return $service->files->listFiles([
                'q'       => 'trashed = false and "' . $folderId . '" in parents',
                'fields'  => 'nextPageToken, files(id, name, mimeType, size, createdTime, modifiedTime)',
                'orderBy' => 'modifiedTime'
            ]);
        } catch (\Throwable $th) {
        }

        return [];
    }

    /**
     * Delete all backup files
     * Used by /tests/webdriverBackup/Backup/GoogleDriveUploadCest.php
     * @return void
     * @throws Exception
     */
    public function cleanBackups()
    {
        $service = new GoogleDriveService($this->client);
        foreach ($this->getFiles() as $file) {
            $service->files->delete($file->getId());
        }
    }

    /**
     * @param string $location A folder name or path separated with slashes to the backup file
     * @param GoogleDriveService $service
     * @return false|string
     * @throws Exception
     */
    public function getFolderIdByLocation($location, $service = null)
    {
        if ($service === null) {
            try {
                $service = new GoogleDriveService($this->client);
            } catch (Exception $e) {
                return false;
            }
        }

        $locationURI = $this->getFoldersFromLocation($location);
        $folderId    = 'root';
        foreach ($locationURI as $folder) {
            $folderId = $this->getFolderIdByName($folder, $folderId, $service);
            if ($folderId === false) {
                return false;
            }
        }

        return $folderId;
    }

    /**
     * @param string $path
     * @param string $parent 'root'
     * @param GoogleDriveService $service
     * @return false|string
     * @throws Exception
     */
    public function getFolderIdByName($path, $parent = 'root', $service = null)
    {
        if ($service === null) {
            try {
                $service = new GoogleDriveService($this->client);
            } catch (Exception $e) {
                return false;
            }
        }

        try {
            /** @var FileList */
            $response = $service->files->listFiles([
                'q'      => "name ='" . $path . "' and '" . $parent . "' in parents and mimeType = 'application/vnd.google-apps.folder'",
                'fields' => 'nextPageToken, files(id, name, mimeType)',
            ]);
        } catch (Exception $e) {
            $options = $this->getOptions();
            $this->refreshAccessTokenRemotely($options['refreshToken']);
            $this->showLoginIssueNotice();
            throw new Exception(sprintf(esc_html__('Can not list files from Google Drive. Please reconnect Google Drive via WP STAGING > Settings > Storage Providers. Error: %s', 'wp-staging'), $e->getMessage()));
        }

        if (!method_exists($response, 'getFiles')) {
            return false;
        }

        if (sizeof($response->getFiles()) === 0) {
            return false;
        }

        foreach ($response->getFiles() as $file) {
            return $file->getId();
        }

        // Should not happen
        return false;
    }

    /**
     * @param $path
     * @param $service
     * @return false|string string|false
     * @throws Exception
     */
    public function getFileInfo($path, $service = null)
    {
        $this->setClientWithAuthToken();
        if ($service === null) {
            $service = new GoogleDriveService($this->client);
        }

        $options        = $this->getOptions();
        $backupLocation = isset($options['folderName']) ? $options['folderName'] : self::FOLDER_NAME;
        $folderId       = $this->getFolderIdByLocation($backupLocation, $service);

        $response = $service->files->listFiles([
            'q'      => "name ='" . $path . "' and '" . $folderId . "' in parents",
            'fields' => 'nextPageToken, files(id, name, mimeType)'
        ]);

        foreach ($response->getFiles() as $file) {
            return $file->getId();
        }

        return false;
    }

    /**
     * This will refresh access token by directly calling Google OAuth api
     * @param string $refreshToken
     *
     * @return bool
     */
    public function refreshAccessToken(string $refreshToken): bool
    {
        if (empty($refreshToken)) {
            return false;
        }

        $accessToken = null;
        try {
            $accessToken = $this->client->refreshToken($refreshToken);
        } catch (Exception $ex) {
            debug_log($ex->getMessage());
            return false;
        }

        if (!is_null($accessToken) && isset($accessToken['access_token'])) {
            $options                = $this->getOptions();
            $options['accessToken'] = $accessToken['access_token'];
            $options['created']     = $accessToken['created'];
            parent::saveOptions($options);

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getRedirectURI()
    {
        return $this->redirectURI;
    }

    /**
     * This will refresh access token by calling our auth api or
     * as specified by the user remotely
     * @param string $refreshToken
     *
     * @return bool
     */
    protected function refreshAccessTokenRemotely(string $refreshToken): bool
    {
        if (empty($refreshToken)) {
            return false;
        }

        $config = [
            "verify" => $this->getCertPath()
        ];

        $http = null;
        try {
            $http = new GuzzleClient($config);
        } catch (Exception $ex) {
            debug_log($ex->getMessage());
            return false;
        }

        $response = false;
        try {
            $response = $http->post(apply_filters('wpstg.backup.storage.googledrive.refresh_url', self::REFRESH_URL), [
                'form_params' => [
                    'refresh_token' => base64_encode($refreshToken)
                ]
            ]);
        } catch (Exception $ex) {
            debug_log($ex->getMessage());
            return false;
        }

        $responseJson = json_decode($response->getBody());
        if (!property_exists($responseJson, 'success')) {
            return false;
        }

        if ($responseJson->success !== true) {
            return false;
        }

        $accessToken = base64_decode($responseJson->accessToken);
        $created     = $responseJson->created;
        if (!empty($accessToken)) {
            $options                = $this->getOptions();
            $options['accessToken'] = $accessToken;
            $options['created']     = $created;
            parent::saveOptions($options);
            return true;
        }

        $this->revoke();
        return false;
    }

    protected function maybeOverrideClientConfig()
    {
        $options = $this->getOptions();

        // only override the config from options,
        // if they are set in options and
        // no filters are applied already
        if (
            isset($options['googleClientId']) &&
            $options['googleClientId'] !== '' &&
            $this->client->getClientId() === self::CLIENT_ID
        ) {
            $this->client->setClientId($options['googleClientId']);
            $this->client->setClientSecret($options['googleClientSecret']);
            $this->client->setRedirectUri($this->redirectURI);
        }
    }

    /**
     * Trim extra spaces from each folder name
     *
     * @param string $backupLocation
     * @return string
     */
    public function sanitizeGoogleDriveLocation($backupLocation)
    {
        $locationURI = $this->getFoldersFromLocation(trim($backupLocation, '/'));
        return implode('/', $locationURI);
    }

    /**
     * @param string $file
     * @return bool
     */
    public function deleteFile(string $file): bool
    {
        $service = new GoogleDriveService($this->client);
        $files   = $this->getFiles();

        foreach ($files as $fileInfo) {
            if ($fileInfo->getName() === $file) {
                $service->files->delete($fileInfo->getId());
                return true;
            }
        }

        return false;
    }

    /**
     * @return void
     */
    private function showLoginIssueNotice()
    {
        $options               = $this->getOptions();
        $options['showNotice'] = true;

        parent::saveOptions($options);

        return;
    }
}
