<?php

namespace WPStaging\Pro\Backup\Storage\Storages\Dropbox;

use UnexpectedValueException;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Security\Auth as WPStagingAuth;
use WPStaging\Pro\Backup\Storage\AbstractStorage;

use function WPStaging\functions\debug_log;

class Auth extends AbstractStorage
{
    /** @var string */
    const FOLDER_NAME = 'wpstaging-backups';

    /** @var string */
    const DROPBOX_API_URL = 'https://api.dropboxapi.com';

    /** @var string */
    const DROPBOX_API_V2_URL = self::DROPBOX_API_URL . '/2';

    /** @var string */
    const REDIRECT_BASE_URL = 'https://auth.wp-staging.com/dropbox';

    /** @var string */
    protected $dropboxCode;

    /** @var array */
    protected $options;

    /** @var Sanitize */
    protected $sanitize;

    public function __construct(WPStagingAuth $wpstagingAuth, Sanitize $sanitize)
    {
        parent::__construct($wpstagingAuth);
        $this->identifier = 'dropbox';
        $this->label      = 'Dropbox';
        $this->sanitize   = $sanitize;
        $this->options    = $this->getOptions();
    }

    /**
     * @param  array $options
     * @return bool
     */
    public function saveOptions($options = [])
    {
        $toReturn      = parent::saveOptions($options);
        $this->options = $this->getOptions();
        return $toReturn;
    }

    /**
     * @param string $file
     * @return string
     * @throws UnexpectedValueException on failure
     */
    public function computeFileHash(string $file): string
    {
        clearstatcache();
        if (!file_exists($file) || !filesize($file)) {
            throw new UnexpectedValueException('File does not exist or is empty');
        }

        $fh = fopen($file, 'r');
        if (!$fh) {
            throw new UnexpectedValueException('Could not open file');
        }

        $hashes = '';
        do {
            $chunk   = fread($fh, 4 * MB_IN_BYTES);
            $hashes .= hash('sha256', $chunk, true);
        } while (!feof($fh));

        fclose($fh);

        return hash('sha256', $hashes);
    }

    /**
     * Get Dropbox Authorization URL
     *
     * @return string Dropbox Authorization URL
     */
    public function getAuthenticationURL()
    {
        $link = add_query_arg(
            [
                'state' => urlencode(admin_url('admin-post.php')),
            ],
            self::REDIRECT_BASE_URL
        );
        return $link;
    }

    /**
     * @return false|string Returns false if the request fails, otherwise returns the response.
     */
    public function testConnection()
    {
        if (empty($this->options['accessToken'])) {
            return false;
        }

        $args = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->options['accessToken'],
            ],
            'body'    => '{}',
        ];
        return $this->runRemoteRequest(self::DROPBOX_API_V2_URL . '/check/user', $args);
    }

    /**
     * Refresh the access token based on the refresh token
     *
     * @return bool
     */
    public function refreshToken()
    {
        if (empty($this->options['refreshToken'])) {
            return false;
        }

        $body = [
            'refresh_token' => $this->options['refreshToken'],
        ];
        $args = [
            'body' => $body,
        ];
        $link = self::REDIRECT_BASE_URL . '/refreshToken';

        /*
        Array[
            access_token: "sl.AbX9y6Fe3AuH5o66-gmJpR032jwAwQPIVVzWXZNkdzcYT02akC2de219dZi6gxYPVnYPrpvISRSf9lxKWJzYLjtMPH-d9fo_0gXex7X37VIvpty4-G8f4-WX45AcEPfRnJJDwzv-"
            token_type: "bearer"
            expires_in:14400
        ]
        @see https://www.dropbox.com/developers/documentation/http/documentation#oauth2-token
        */
        $response = $this->runRemoteRequest($link, $args);
        if (isset($response['access_token'])) {
            $this->options['accessToken']     = $response['access_token'];
            $this->options['expiresIn']       = $response['expires_in'];
            $this->options['isAuthenticated'] = true;
            return $this->saveOptions($this->options);
        }

        return false;
    }

    /**
     * Authentication of the storage
     *
     * @return void
     */
    public function authenticate()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to access this page.', 'wp-staging'));
        }

        $this->options['accessToken']  = isset($_GET['access_token']) ? $this->sanitize->decodeBase64AndSanitize($_GET['access_token']) : '';
        $this->options['refreshToken'] = isset($_GET['refresh_token']) ? $this->sanitize->decodeBase64AndSanitize($_GET['refresh_token']) : '';
        $this->options['accountId']    = isset($_GET['account_id']) ? $this->sanitize->decodeBase64AndSanitize($_GET['account_id']) : 0;

        if (empty($this->options['accessToken']) || empty($this->options['refreshToken']) || empty($this->options['accountId'])) {
            debug_log('Fail to authenticate to dropbox account some data are missing.');
            wp_die(esc_html__('Authentication failed please try again.', 'wp-staging'));
        }

        $this->options['expiresIn']       = isset($_GET['expires_in']) ? $this->sanitize->sanitizeInt($_GET['expires_in']) : 0;
        $this->options['isAuthenticated'] = true;

        $this->saveOptions($this->options);
        $this->saveStorageAccountInfo();

        $redirectURL = add_query_arg(
            [
                'page'         => 'wpstg-settings',
                'tab'          => 'remote-storages',
                'sub'          => 'dropbox',
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
        $options = $this->getOptions();
        $response = $this->getUserData();
        if (!empty($response['name'])) {
            $options['userData'] = $response;
            $options['userData']['displayName'] = $response['name']['display_name'];
        }

        $response = $this->getStorageInfo();
        if (!empty($response['allocation'])) {
            $options['storageInfo'] = $response;
        }

        $this->saveOptions($options);
    }

    /**
     * Authenticate when user set his own API credentials
     */
    public function apiAuthenticate()
    {
        // no-op
    }

    /**
     * @param array $settings
     * @return bool
     */
    public function updateSettings($settings)
    {
        $backupLocation                    = !empty($settings['folder_name']) ? sanitize_text_field($settings['folder_name']) : self::FOLDER_NAME;
        $this->options['folderName']       = $backupLocation;
        $this->options['maxBackupsToKeep'] = !empty($settings['max_backups_to_keep']) && $settings['max_backups_to_keep'] > 0 ? sanitize_text_field($settings['max_backups_to_keep']) : 2;
        $this->options['lastUpdated']      = time();

        return $this->saveOptions($this->options);
    }

    /**
     * Revoke both access and refresh token,
     * Also unauthenticate the provider
     * @return bool
     */
    public function revoke()
    {
        if (!empty($this->options['accessToken'])) {
            $args = [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->options['accessToken'],
                ],
                'body'    => 'null',
            ];
            $this->runRemoteRequest(self::DROPBOX_API_V2_URL . '/auth/token/revoke', $args);
        }

        $this->options                    = [];
        $this->options['isAuthenticated'] = false;
        $this->options['accessToken']     = '';
        $this->options['refreshToken']    = '';
        $this->options['expiresIn']       = '';
        $this->options['accountId']       = '';
        $this->options['userData']        = [];
        $this->options['storageInfo']     = [];

        return $this->saveOptions($this->options);
    }

    /**
     * @return array
     *
     * @see https://www.dropbox.com/developers/documentation/http/documentation#files-list_folder for more info
     */
    public function getFiles()
    {
        $toReturn = [];
        if (empty($this->options['accessToken'])) {
            return $toReturn;
        }

        $folderName = empty($this->options['folderName']) ? self::FOLDER_NAME : $this->options['folderName'];
        $body = [
            "include_deleted"                     => false,
            "include_has_explicit_shared_members" => false,
            "include_media_info"                  => false,
            "include_mounted_folders"             => false,
            "include_non_downloadable_files"      => true,
            "path"                                => '/' . trim($folderName, '/'),
            "recursive"                           => false
        ];

        $args = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->options['accessToken'],
            ],
            'body'    => json_encode($body),
        ];

        $responseBody = $this->runRemoteRequest(self::DROPBOX_API_V2_URL . '/files/list_folder', $args);
        if (!empty($responseBody['entries'])) {
            return $responseBody['entries'];
        }

        return $toReturn;
    }

    /**
     * @return false|array Returns false on api request failure.
     *  Returns an array of user information, including the account ID, name, email, and other details.
     *
     * The array structure is as follows:
     *
     *     account_id: The user's account ID.
     *
     *     name: An array with five fields:
     *         given_name: The user's given name.
     *         surname: The user's surname.
     *         familiar_name: The user's familiar name.
     *         display_name: The user's display name.
     *         abbreviated_name: The user's abbreviated name.
     *
     *     email: The user's email address.
     *
     *     email_verified: A boolean indicating whether the user's email has been verified (true/false)
     *
     *     disabled: A boolean indicating whether the user's account is disabled (true/false)
     *
     *     is_teammate: A boolean indicating whether the user is a teammate (true/false)
     *
     * @see https://www.dropbox.com/developers/documentation/http/documentation#users-get_current_account for more information.
     */
    public function getUserData()
    {
        if (empty($this->options['accessToken'])) {
            return false;
        }

        $args = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->options['accessToken'],
            ],
            'body'    => 'null',
        ];
        return $this->runRemoteRequest(self::DROPBOX_API_V2_URL . '/users/get_current_account', $args);
    }

    /**
     * @return false|array Returns false on api request failure.
     *  Returns an array of storage information, including the used and allocated space.
     *
     * The array structure is as follows:
     *
     *     used:The user's total space usage (bytes).
     *
     *     allocation: The user's space allocation:
     *         .tag: The type of space allocation. Space is allocated differently based on the type of account.(e.g: "individual").
     *          allocated: Space is allocated.
     *
     * @see https://www.dropbox.com/developers/documentation/http/documentation#users-get_space_usage for more information.
     */
    public function getStorageInfo()
    {
        if (empty($this->options['accessToken'])) {
            return false;
        }

        $args = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->options['accessToken'],
            ],
            'body'    => 'null',
        ];
        return $this->runRemoteRequest(self::DROPBOX_API_V2_URL . '/users/get_space_usage', $args);
    }

    /**
     * TODO: move this code to uploader to respect DRY. While moving this, update the function to make sure only backups created
     *       on this site will be deleted. Right now the function delete every files inside the backup folder, but it is fine
     *       since this is used during e2e tests only so far. Do this for other storages as well.
     *
     * Delete all backup files
     * @return mixed
     */
    public function cleanBackups()
    {
        $backupsFiles = $this->getFiles();

        $toDelete = [];
        foreach ($backupsFiles as $value) {
            $toDelete[] = [
                'path' => $value['path_lower'],
            ];
        }

        $args = [
            'headers' => [
                'Content-Type'    => 'application/json',
                'Authorization'   => 'Bearer ' . $this->options['accessToken'],
            ],
            'body'    => json_encode([
                'entries' => $toDelete,
            ]),
        ];
        $response = $this->runRemoteRequest(self::DROPBOX_API_V2_URL . '/files/delete_batch', $args);
        if (isset($response['async_job_id'])) {
            return $this->checkDeleteBatchStatus($response['async_job_id']);
        }

        return true;
    }

    /**
     * TODO: to be deleted after moving cleanBackups()
     *
     * @param  string $asyncJobId
     *
     * @return bool
     */
    public function checkDeleteBatchStatus($asyncJobId)
    {
        $args = [
            'headers' => [
                'Content-Type'    => 'application/json',
                'Authorization'   => 'Bearer ' . $this->options['accessToken'],
            ],
            'body'    => json_encode([
                'async_job_id' => $asyncJobId,
            ]),
        ];

        $tagStatus = 'in_progress';
        $i         = 0;
        do {
            $response = $this->runRemoteRequest(Auth::DROPBOX_API_V2_URL . '/files/delete_batch/check', $args);
            if (isset($response['.tag'])) {
                if ($response['.tag'] === 'complete') {
                    return true;
                }

                $tagStatus = $response['.tag'];
                usleep(1000);
            } elseif (!isset($response['.tag']) || $i >= 20) {
                $tagStatus = '';
            }
            $i++;
        } while ($tagStatus === 'in_progress');

        debug_log('Dropbox warning: fail to check delete batch status. response: ' . print_r($response, true));
        return false;
    }

    /**
     * @param string $file
     * @return bool
     */
    public function deleteFile(string $file): bool
    {
        $backupsFiles = $this->getFiles();

        $toDelete = [];
        foreach ($backupsFiles as $value) {
            if ($value['path_lower'] !== $file && $value['name'] !== $file) {
                continue;
            }

            $toDelete[] = [
                'path' => $value['path_lower'],
            ];
        }

        $args = [
            'headers' => [
                'Content-Type'    => 'application/json',
                'Authorization'   => 'Bearer ' . $this->options['accessToken'],
            ],
            'body'    => json_encode([
                'entries' => $toDelete,
            ]),
        ];
        $response = $this->runRemoteRequest(self::DROPBOX_API_V2_URL . '/files/delete_batch', $args);
        if (isset($response['async_job_id'])) {
            return $this->checkDeleteBatchStatus($response['async_job_id']);
        }

        return true;
    }

    /**
     * @param  mixed $url
     * @param  mixed $args
     *
     * @return false|array
     */
    protected function runRemoteRequest($url, $args = [])
    {
        $defaults = [
            'timeout'     => 40,
            'httpversion' => '1.0',
            'sslverify'   => true,
        ];
        $args = wp_parse_args($args, $defaults);

        $response = wp_remote_post($url, $args);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $errorMessage = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
            $this->error = $errorMessage;
            if ($url === self::DROPBOX_API_V2_URL . '/files/list_folder' && strpos($errorMessage, 'path/not_found') !== false) {
                return false;
            }

            debug_log("WP STAGING dropbox api request failed! url: $url; Error Message: $errorMessage");
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
