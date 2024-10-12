<?php

namespace WPStaging\Pro\Backup\Storage;

use Exception;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Backup\BackupProcessLock;
use WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Auth as GoogleDriveStorage;
use WPStaging\Pro\Backup\Storage\Storages\Dropbox\Auth as DropboxStorage;
use WPStaging\Vendor\Google\Client as GoogleClient;
use WPStaging\Vendor\Google\Service\Drive as GoogleDriveService;
use WPStaging\Vendor\Google\Service\PeopleService as GooglePeopleService;
use WPStaging\Vendor\GuzzleHttp\Client as GuzzleClient;
use WPStaging\Pro\Backup\Ajax\Download;
use WPStaging\Pro\Backup\Ajax\CloudFileList;
use WPStaging\Pro\Backup\Ajax\CancelDownload;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\AmazonS3DownloadTask;
use WPStaging\Pro\Backup\Storage\RemoteUploaderInterface;
use WPStaging\Pro\Backup\Storage\Storages\Amazon\S3Uploader;
use WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Uploader as GoogleDriveUploader;
use WPStaging\Pro\Backup\Storage\Storages\SFTP\Uploader as SftpUploader;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\DigitalOceanSpacesDownloadTask;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\GenericS3DownloadTask;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\GoogleDriveDownloadTask;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\SFTPDownloadTask;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\WasabiDownloadTask;
use WPStaging\Pro\Backup\Storage\Storages\DigitalOceanSpaces\Uploader as DigitalOceanUploader;
use WPStaging\Pro\Backup\Storage\Storages\GenericS3\Uploader as GenericS3Uploader;
use WPStaging\Pro\Backup\Storage\Storages\Wasabi\Uploader as WasabiUploader;

use function WPStaging\functions\debug_log;

class StoragesServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        $this->setupGoogleDrive();

        $this->container->singleton(SettingsTab::class);

        $this->container->when(AmazonS3DownloadTask::class)
                        ->needs(RemoteUploaderInterface::class)
                        ->give(S3Uploader::class);
        $this->container->when(DigitalOceanSpacesDownloadTask::class)
                        ->needs(RemoteUploaderInterface::class)
                        ->give(DigitalOceanUploader::class);
        $this->container->when(GenericS3DownloadTask::class)
                        ->needs(RemoteUploaderInterface::class)
                        ->give(GenericS3Uploader::class);
        $this->container->when(GoogleDriveDownloadTask::class)
                        ->needs(RemoteUploaderInterface::class)
                        ->give(GoogleDriveUploader::class);
        $this->container->when(SFTPDownloadTask::class)
                        ->needs(RemoteUploaderInterface::class)
                        ->give(SftpUploader::class);
        $this->container->when(WasabiDownloadTask::class)
                        ->needs(RemoteUploaderInterface::class)
                        ->give(WasabiUploader::class);
    }

    protected function addHooks()
    {
        add_filter('wpstg_main_settings_tabs', $this->container->callback(SettingsTab::class, 'addRemoteStoragesSettingsTab'), 10, 1);

        add_action('admin_post_wpstg-googledrive-auth', $this->container->callback(GoogleDriveStorage::class, 'authenticate'), 10, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('admin_post_wpstg-googledrive-api-auth', $this->container->callback(GoogleDriveStorage::class, 'apiAuthenticate'), 10, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg-provider-authenticate', $this->container->callback(StorageBase::class, 'authenticate'), 10, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg-provider-revoke', $this->container->callback(StorageBase::class, 'revoke'), 10, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg-provider-settings', $this->container->callback(StorageBase::class, 'updateSettings'), 10, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg-provider-test-connection', $this->container->callback(StorageBase::class, 'testConnection'), 10, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wpstg.all_admin_notices', $this->container->callback(GoogleDriveStorage::class, 'showAdminNotices'), 10, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wpstg.all_admin_notices', $this->container->callback(DropboxStorage::class, 'showAdminNotices'), 10);// phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('admin_post_wpstg-dropbox-auth', $this->container->callback(DropboxStorage::class, 'authenticate'), 10, 0);// phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--download--cloud-backup', $this->container->callback(Download::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--cloud--delete', $this->container->callback(CloudFileList::class, 'deleteCloudFile')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--cloud--file-list', $this->container->callback(CloudFileList::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--download--cancel', $this->container->callback(CancelDownload::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg-provider-delete-settings', $this->container->callback(StorageBase::class, 'deleteSettings'), 10, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }

    private function setupGoogleDrive()
    {
        /* $this->container->setVar('googleClientId', apply_filters('wpstg.backup.storage.googledrive.client_id',
        '425321582825-hl320nnpa8cc3sv5j9mtktjdibgac5je.apps.googleusercontent.com'));*/
        $this->container->setVar('googleClientId', apply_filters('wpstg.backup.storage.googledrive.client_id', GoogleDriveStorage::CLIENT_ID));
        $this->container->setVar('googleClientSecret', apply_filters('wpstg.backup.storage.googledrive.client_secret', ''));
        $this->container->setVar('googleRedirectURL', apply_filters('wpstg.backup.storage.googledrive.callback_url', GoogleDriveStorage::REDIRECT_URL));
        $container = $this->container;
        $this->container->bind(GoogleClient::class, function () use (&$container) {
            $googleClient = new GoogleClient();

            $config = [
                "verify" => WPSTG_PLUGIN_DIR . 'Pro/Backup/cacert.pem'
            ];

            $http = null;
            try {
                $http = new GuzzleClient($config);
                $googleClient->setHttpClient($http);
            } catch (Exception $ex) {
                debug_log($ex->getMessage());
            }

            $googleClient->setClientId($container->getVar('googleClientId'));
            if ($container->getVar('googleClientSecret') !== '') {
                $googleClient->setClientSecret($container->getVar('googleClientSecret'));
            }

            $googleClient->setRedirectUri($container->getVar('googleRedirectURL'));
            $googleClient->setScopes([GooglePeopleService::USERINFO_PROFILE, GoogleDriveService::DRIVE_FILE]);
            $googleClient->setAccessType('offline');
            $googleClient->setPrompt('consent');
            return $googleClient;
        });

        $this->container->singleton(GoogleDriveStorage::class, function () use (&$container) {
            $googleClient = $container->make(GoogleClient::class);
            $backupProcessLock = $container->make(BackupProcessLock::class);
            $sanitize = $container->make(Sanitize::class);
            $wpstagingAuth = $container->make(Auth::class);
            $googleDriveStorage =  new GoogleDriveStorage($googleClient, $backupProcessLock, $sanitize, $wpstagingAuth);
            if ($googleDriveStorage->isAuthenticated()) {
                $googleDriveStorage->setClientWithAuthToken();
            }

            return $googleDriveStorage;
        });

        // @todo code below doesn't work. Check if we need it when we upgrade to DI52 V3
        /*
        $this->container->bind(RemoteUploaderInterface::class, AmazonS3Uploader::class);

        $this->container->when(GoogleDriveStorageTask::class)
                        ->needs(RemoteUploaderInterface::class)
                        ->give(GoogleDriveUploader::class);

        $this->container->when(AmazonS3StorageTask::class)
                        ->needs(RemoteUploaderInterface::class)
                        ->give(AmazonS3Uploader::class);
        */
    }
}
