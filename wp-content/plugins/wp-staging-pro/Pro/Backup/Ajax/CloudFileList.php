<?php

namespace WPStaging\Pro\Backup\Ajax;

use WPStaging\Backend\Pro\Licensing\Licensing;
use WPStaging\Backup\Entity\ListableBackup;
use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\SiteInfo;
use WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Uploader as DriveUploader;
use WPStaging\Pro\Backup\Storage\Storages\Amazon\S3Uploader as S3Uploader;
use WPStaging\Pro\Backup\Storage\Storages\SFTP\Uploader as SftpUploader;
use WPStaging\Pro\Backup\Storage\Storages\DigitalOceanSpaces\Uploader as DigitalOceanUploader;
use WPStaging\Pro\Backup\Storage\Storages\GenericS3\Uploader as GenericS3Uploader;
use WPStaging\Pro\Backup\Storage\Storages\Wasabi\Uploader as WasabiS3Uploader;
use WPStaging\Pro\Backup\Storage\RemoteUploaderInterface;

class CloudFileList extends AbstractTemplateComponent
{
    /** @var Sanitize */
    private $sanitize;

    /** @var RemoteUploaderInterface */
    private $remoteStorageProvider;

    /** @var Providers */
    private $providers;

    /**
     * @param TemplateEngine $templateEngine
     * @param Sanitize $sanitize
     * @param Providers $providers
     */
    public function __construct(TemplateEngine $templateEngine, Sanitize $sanitize, Providers $providers)
    {
        parent::__construct($templateEngine);
        $this->sanitize = $sanitize;
        $this->providers = $providers;

        try {
            $this->lazyLoadRemoteStorageProvider();
        } catch (\Throwable $th) {
            wp_send_json_error('Fail to get backups! Error message: ' . $th->getMessage());
        }
    }

    /**
     * Render a view file
     * @return void
     */
    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        // Early bail: if no provider set.
        if (!$this->remoteStorageProvider) {
            wp_send_json_error();
        }

        $listableBackups = $this->remoteStorageProvider->getBackups();

        /**
         * Javascript expects an array with keys in natural order
         *
         * @var ListableBackup[] $listableBackups
         */

        // Sort backups by the highest created/upload date, newest first.
        usort($listableBackups, function ($item, $nextItem) {
            $nextItemDateUploadedTimestamp = empty($nextItem->dateUploadedTimestamp) ? 0 : $nextItem->dateUploadedTimestamp;
            $nextItemDateCreatedTimestamp  = empty($nextItem->dateCreatedTimestamp) ? 0 : $nextItem->dateCreatedTimestamp;
            $dateUploadedTimestamp         = empty($item->dateUploadedTimestamp) ? 0 : $item->dateUploadedTimestamp;
            $dateCreatedTimestamp          = empty($item->dateCreatedTimestamp) ? 0 : $item->dateCreatedTimestamp;

            /**
             * @var ListableBackup $item
             * @var ListableBackup $nextItem
             */
            return ((int)max($nextItemDateUploadedTimestamp, $nextItemDateCreatedTimestamp)) - ((int)max($dateUploadedTimestamp, $dateCreatedTimestamp));
        });

        // Returns a HTML template
        if (isset($_GET['withTemplate']) && $this->sanitize->sanitizeBool($_GET['withTemplate'])) {
            $output = '';

            $isValidLicenseKey = (new SiteInfo())->isStagingSite() || (new Licensing())->isValidOrExpiredLicenseKey();

            if (!empty($listableBackups) && $isValidLicenseKey) {
                /** @var ListableBackup $listable */
                foreach ($listableBackups as $listable) {
                    $output .= $this->renderTemplate(
                        'Backend/views/backup/listing-single-cloud-backup.php',
                        [
                            'backup' => $listable,
                            'urlAssets'   => trailingslashit(WPSTG_PLUGIN_URL) . 'assets/',
                        ]
                    );
                }
            }

            wp_send_json($output);
        }

        // Returns a JSON response
        wp_send_json($listableBackups);
    }

    /**
     * Delete backup from cloud providers
     * @return bool|void
     */
    public function deleteCloudFile()
    {
        if (!WPStaging::make(Auth::class)->isAuthenticatedRequest()) {
            return;
        }

        $fileId = !empty($_REQUEST['file']) ? $this->sanitize->sanitizeString($_REQUEST['file']) : "";
        if (empty($fileId)) {
            return false;
        }

        $response = $this->remoteStorageProvider->deleteFile($fileId);
        wp_send_json($response);
    }

    /**
     * Get enabled cloud providers list
     * @return void
     */
    public function getStorageList()
    {
        if (!WPStaging::make(Auth::class)->isAuthenticatedRequest()) {
            return;
        }

        $storages = [];
        foreach ($this->providers->getStorages(true) as $storage) {
            if ($storage['id'] === 'dropbox') {
                continue;
            }

            $isActivated = $this->providers->isActivated($storage['authClass']);
            if ($isActivated === true) {
                array_push($storages, strtolower($storage['id']));
            }
        }

        wp_send_json($storages);
    }

    /** @return bool */
    protected function lazyLoadRemoteStorageProvider(): bool
    {
        $storageProviderName = !empty($_REQUEST['storageProviderName']) ? $this->sanitize->sanitizeString($_REQUEST['storageProviderName']) : "";

        if (empty($storageProviderName)) {
            return false;
        }

        $providerMap = [
            'googledrive'         => DriveUploader::class,
            'amazons3'            => S3Uploader::class,
            'sftp'                => SftpUploader::class,
            'digitalocean-spaces' => DigitalOceanUploader::class,
            'generic-s3'          => GenericS3Uploader::class,
            'wasabi-s3'           => WasabiS3Uploader::class,
        ];
        if (array_key_exists($storageProviderName, $providerMap)) {
            $this->remoteStorageProvider = WPStaging::make($providerMap[$storageProviderName]);
            // If the provider is Google Drive, call the setFolderId method
            if ($storageProviderName === 'googledrive') {
                /** @var DriveUploader */
                $remoteStorageProvider = $this->remoteStorageProvider;
                $remoteStorageProvider->setFolderId();
            }

            return true;
        }

        return false;
    }
}
