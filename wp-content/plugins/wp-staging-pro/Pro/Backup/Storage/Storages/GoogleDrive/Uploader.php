<?php

namespace WPStaging\Pro\Backup\Storage\Storages\GoogleDrive;

use Exception;
use WPStaging\Backup\Dto\Interfaces\RemoteUploadDtoInterface;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Backup\Exceptions\StorageException;
use WPStaging\Pro\Backup\Storage\RemoteUploaderInterface;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Pro\Backup\Dto\Job\JobCloudDownloadDataDto;
use WPStaging\Framework\Facades\PhpAdapter;
use WPStaging\Vendor\Google\Client as GoogleClient;
use WPStaging\Vendor\Google\Service\Drive as GoogleDriveService;
use WPStaging\Vendor\Google\Service\Drive\DriveFile as GoogleDriveFile;
use WPStaging\Vendor\Google\Http\MediaFileUpload as GoogleMediaFileUpload;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Vendor\Psr\Http\Message\RequestInterface;

use function WPStaging\functions\debug_log;

class Uploader implements RemoteUploaderInterface
{
    use WithBackupIdentifier;

    /** @var GoogleClient */
    private $client;

    /** @var RemoteUploadDtoInterface|JobCloudDownloadDataDto */
    private $jobDataDto;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $filePath;

    /** @var string */
    private $fileName;

    /** @var string|null */
    private $folderId;

    /** @var string */
    private $folderName;

    /** @var int */
    private $maxBackupsToKeep;

    /** @var GoogleDriveService */
    private $service;

    /** @var FileObject */
    private $fileObject;

    /** @var GoogleMediaFileUpload */
    private $media;

    /** @var int */
    private $chunkSize;

    /** @var Auth */
    private $auth;

    /** @var bool|string */
    private $error;

    /** @var Strings */
    private $strings;

    public function __construct(Auth $auth, Strings $strings)
    {
        $this->error = false;
        $this->auth = $auth;
        $this->strings = $strings;

        if (!$this->auth->isGuzzleAvailable()) {
            $this->error = __('cURL extension is missing. Backup is still available locally.', 'wp-staging');
            return;
        }

        if (!$this->auth->isAuthenticated()) {
            $this->error = __('Google Drive is not authenticated. Backup is still available locally.', 'wp-staging');
            return;
        }

        $this->client = $auth->setClientWithAuthToken();
        $options = $this->auth->getOptions();
        $this->folderName = isset($options['folderName']) ? $this->auth->sanitizeGoogleDriveLocation($options['folderName']) : Auth::FOLDER_NAME;
        $this->maxBackupsToKeep = isset($options['maxBackupsToKeep']) ? $options['maxBackupsToKeep'] : 15;
        $this->maxBackupsToKeep = intval($this->maxBackupsToKeep);
        $this->maxBackupsToKeep = $this->maxBackupsToKeep > 0 ? $this->maxBackupsToKeep : 15;
        $this->folderId = null;
    }

    public function getProviderName(): string
    {
        return 'Google Drive';
    }

    /**
     * @param LoggerInterface $logger
     * @param RemoteUploadDtoInterface $jobDataDto
     * @param int $chunkSize = MB_IN_BYTES
     * @return void
     */
    public function setupUpload(LoggerInterface $logger, RemoteUploadDtoInterface $jobDataDto, $chunkSize = MB_IN_BYTES)
    {
        $this->logger     = $logger;
        $this->jobDataDto = $jobDataDto;
        $this->chunkSize  = $chunkSize;
        $this->createBackupsDestination();
    }

    /**
     * @param LoggerInterface $logger
     * @param RemoteUploadDtoInterface $jobDataDto
     * @param int $chunkSize = MB_IN_BYTES
     * @return void
     */
    public function setupDownload(LoggerInterface $logger, JobCloudDownloadDataDto $jobDataDto, int $chunkSize = MB_IN_BYTES)
    {
        $this->logger     = $logger;
        $this->jobDataDto = $jobDataDto;
        $this->chunkSize  = $chunkSize < 10 * MB_IN_BYTES ? 10 * MB_IN_BYTES : $chunkSize;
    }

    /**
     * @param int $backupSize
     * @throws DiskNotWritableException
     */
    public function checkDiskSize($backupSize)
    {
        $this->service = new GoogleDriveService($this->client);

        if (!$this->doExceedGoogleDiskLimit($backupSize)) {
            throw new DiskNotWritableException($this->error);
        }
    }

    /**
     * @return bool
     * @throws StorageException
     */
    public function setBackupFilePath($backupFilePath, $fileName)
    {
        $this->fileName = $fileName;
        $this->filePath = $backupFilePath;
        $this->fileObject = new FileObject($this->filePath, FileObject::MODE_READ);
        $this->service = new GoogleDriveService($this->client);

        $this->folderId = $this->auth->getFolderIdByLocation($this->folderName, $this->service);

        $fileMetadata = new GoogleDriveFile([
            'name' => $fileName,
            'parents' => [$this->folderId],
        ]);

        // We set the defer above so instead of returning the expected output that is DriveFile object, it will return the request
        $this->client->setDefer(true);

        /** @var RequestInterface $request */
        $request = $this->service->files->create($fileMetadata);
        $this->media = new GoogleMediaFileUpload(
            $this->client,
            $request,
            'application/octet-stream',
            null,
            true,
            $this->chunkSize
        );

        $this->media->setFileSize($this->fileObject->getSize());

        $uploadMetadata = $this->jobDataDto->getRemoteStorageMeta();
        if (!array_key_exists($this->fileName, $uploadMetadata)) {
            $resumeURI = $this->getResumeUri();
            $this->setMetadata($resumeURI, 0);
            $this->logger->info('Starting upload of file:' . $this->fileName);
            return true;
        }

        $fileMetadata = $uploadMetadata[$this->fileName];

        $resumeURI = $fileMetadata['ResumeURI'];
        $this->media->resume($resumeURI);

        $newResumeURI = $this->getResumeUri();
        if ($newResumeURI !== $resumeURI) {
            $this->setMetadata($newResumeURI, $fileMetadata['Offset']);
        }

        return true;
    }

    /**
     * @param string $filePath
     * @param StepsDto $stepsDto
     * @param int $chunkSize
     *
     * @return int
     */
    public function chunkUpload()
    {
        $status = false;
        $fileMetadata = $this->jobDataDto->getRemoteStorageMeta()[$this->fileName];
        $offset = $fileMetadata['Offset'];

        $this->fileObject->fseek($offset);
        $chunk = $this->fileObject->fread($this->chunkSize);
        $status = $this->media->nextChunk($chunk);

        $chunkSize = strlen($chunk);
        $offset += $chunkSize;

        if ($status !== false) {
            throw new FinishedQueueException();
        }

        $this->setMetadata($fileMetadata['ResumeURI'], $offset);
        return $chunkSize;
    }

    /**
     * @param string $filePath
     * @param string $remoteFileName
     * @return bool
     */
    public function uploadFile($filePath, $remoteFileName = '')
    {
        $fileObject = new FileObject($filePath, FileObject::MODE_READ);

        if (empty($remoteFileName)) {
            $remoteFileName = $fileObject->getBasename();
        }

        $this->service = new GoogleDriveService($this->client);

        if ($this->folderId === null) {
            $this->folderId = $this->auth->getFolderIdByLocation($this->folderName);
        }

        $fileMetadata = new GoogleDriveFile([
            'name' => $remoteFileName,
            'parents' => [$this->folderId],
        ]);

        // We set the defer above so instead of returning the expected output that is DriveFile object, it will return the request
        $this->client->setDefer(true);

        /** @var RequestInterface $request */
        $request = $this->service->files->create($fileMetadata);
        $this->media = new GoogleMediaFileUpload(
            $this->client,
            $request,
            'application/octet-stream',
            null,
            true,
            $fileObject->getSize()
        );

        $this->media->setFileSize($fileObject->getSize());

        $fileObject->fseek(0);
        $chunk = $fileObject->fread($fileObject->getSize());

        try {
            $this->media->nextChunk($chunk);
        } catch (Exception $ex) {
            //debug_log("Error: " . $ex->getMessage());
            return false;
        }

        return true;
    }

    public function stopUpload()
    {
        $this->client->setDefer(false);
    }

    /** @return string */
    public function getError()
    {
        return $this->error;
    }

    public function getBackups()
    {
        $files = $this->auth->getFiles();

        $backups = [];
        foreach ($files as $key => $file) {
            if ($this->strings->endsWith($file->getName(), '.wpstg') || $this->strings->endsWith($file->getName(), '.sql')) {
                $date = new \DateTime($file['createdTime']);
                $backups[$key] = $file;
                $backups[$key]->dateCreatedTimestamp = $date->format('Y-m-d H:i:s');
                $backups[$key]->storageProviderName = $this->auth->getIdentifier();
                $backups[$key]->type = $this->getProviderName();
            }
        }

        return $backups;
    }

    public function deleteOldestBackups()
    {
        $retainedBackups = $this->auth->getRetainedBackups();
        if (count($retainedBackups) < $this->maxBackupsToKeep) {
            return true;
        }

        $this->service      = new GoogleDriveService($this->client);
        $remoteBackupsFiles = $this->getBackups();

        foreach ($retainedBackups as $retainedBackupId => $retainedBackup) {
            if (count($retainedBackups) < $this->maxBackupsToKeep) {
                break;
            }

            foreach ($remoteBackupsFiles as $file) {
                $fileName = $file->getName();
                if (strpos($fileName, $retainedBackupId) !== false) {
                    $this->service->files->delete($file->getId());
                }
            }

            $this->auth->unsetStorageFromRetainedBackups($retainedBackupId);
            unset($retainedBackups[$retainedBackupId]);
        }

        return true;
    }

    /**
     * @param array $uploadsToVerify
     * @return bool
     */
    public function verifyUploads(array $uploadsToVerify): bool
    {
        $files = $this->auth->getFiles();
        $uploadsConfirmed = [];
        foreach ($files as $file) {
            $fileName = $file->getName();
            $fileSize = (int)$file->getSize();

            if (!array_key_exists($fileName, $uploadsToVerify)) {
                continue;
            }

            $toVerify = $uploadsToVerify[$fileName];
            if ($toVerify['size'] !== $fileSize) {
                continue;
            }

            $uploadsConfirmed[] = $fileName;
        }

        $this->auth->saveStorageAccountInfo();

        return count($uploadsConfirmed) === count($uploadsToVerify);
    }

    /**
     * @return string
     * @throws StorageException
     */
    protected function getResumeUri(): string
    {
        try {
            return $this->media->getResumeUri();
        } catch (Exception $ex) {
            $this->error = 'Fail to get resume Uri for ' . $this->fileName . '.';
            $googleError = $ex->getMessage();
            if (!PhpAdapter::jsonValidate($googleError)) {
                $this->error .= ' Error: ' . esc_html($googleError);
                throw new StorageException($this->error);
            }

            $jsonError = json_decode($googleError, true);
            if (!isset($jsonError['error']) || !isset($jsonError['error']['message'])) {
                $this->error .= ' Error: ' . esc_html($googleError);
                throw new StorageException($this->error);
            }

            $this->error .= ' Error: ' . esc_html($jsonError['error']['message']);

            $errorCode = isset($jsonError['error']['code']) ? $jsonError['error']['code'] : 0;

            throw new StorageException($this->error, $errorCode);
        }
    }

    protected function setMetadata($resumeURI, $offset)
    {
        $this->jobDataDto->setRemoteStorageMeta([
            $this->fileName => [
                'ResumeURI' => $resumeURI,
                'Offset' => $offset,
            ]
        ]);
    }

    /**
     * @param int $backupSize
     * @param GoogleDriveService $service
     * @return bool
     */
    private function doExceedGoogleDiskLimit($backupSize, $service = null)
    {
        if (apply_filters('wpstg.googleDrive.bypassDiskSpace', false)) {
            return true;
        }

        if ($service === null) {
            $service = $this->service;
        }

        try {
            $storage = $this->auth->getStorageInfo($service);
            $totalQuota = $storage->getLimit();
            $usedQuota = $storage->getUsage();
        } catch (Exception $ex) {
            return true;
        }

        if (!is_numeric($totalQuota) || !is_numeric($usedQuota)) {
            $this->logger->warning('Unable to get size of used or available storage space. Continuing with Upload to Google Drive!');
            return true;
        }

        $availableQuota = $totalQuota - $usedQuota;
        if (empty($availableQuota) || !is_numeric($availableQuota) || $availableQuota < 0) {
            return true;
        }

        if ($backupSize > $availableQuota) {
            $this->error = sprintf(__('Could not upload backup to Google Drive. Reason: Disk Quota Exceeded. Increase google drive space or delete old data! Backup Size: %s. Space Available: %s. Backup is still available locally.', 'wp-staging'), size_format($this->fileObject->getSize(), 2), size_format($availableQuota, 2));
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function createBackupsDestination(): bool
    {
        $location       = $this->getBackupsLocation();
        $locationURI    = $this->auth->getFoldersFromLocation($location);
        $parentFolderId = 'root';
        $this->service  = new GoogleDriveService($this->client);
        foreach ($locationURI as $folder) {
            $folderId = $this->auth->getFolderIdByName($folder, $parentFolderId, $this->service);
            if ($folderId === false && $parentFolderId) {
                $folderId = $this->createFolder($folder, $parentFolderId);
            }

            if ($folderId === false) {
                return false; // Early bail: fail to get or to create the current folder, no need to continue the loop. Something is wrong!
            }

            $parentFolderId = $folderId;
        }

        return true;
    }

    /**
     * @return string|false
     */
    private function createFolder(string $path, string $parentFolderId)
    {
        if (empty($parentFolderId)) {
            return false;
        }

        try {
            $fileMetadata = new GoogleDriveFile([
                'name' => $path,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentFolderId],
            ]);

            $file = $this->service->files->create($fileMetadata, [
                'fields' => 'id'
            ]);

            if (empty($file->id)) {
                return false;
            }

            return $file->id;
        } catch (Exception $e) {
            debug_log("Fail to create folder $path with parent id $parentFolderId. Error: " . $e->getMessage());
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getBackupsLocation(): string
    {
        $options = $this->auth->getOptions();
        return !empty($options['folderName']) ? $options['folderName'] : Auth::FOLDER_NAME;
    }

    /**
     * @return void
     */
    public function setFolderId()
    {
        if ($this->folderId === null) {
            $this->folderId = $this->auth->getFolderIdByName($this->folderName);
        }
    }

    /**
     * Delete backup from google drive
     *
     * @param string $fileId
     * @param string $client
     * @return void|bool
     */
    public function deleteFile($fileId, $client = null)
    {
        if ($client === null) {
            $client = $this->client;
        }

        $this->service = new GoogleDriveService($client);
        if ($this->service->files->delete($fileId)) {
            return true;
        }

        return false;
    }

    /**
     * Save backup file from google drive to server
     *
     * @param string $fileId
     * @param int $fileSize
     * @param int $chunkStart
     * @return int
     */
    public function chunkDownloadCloudFileToFolder(string $fileId, int $fileSize, int $chunkStart)
    {
        $chunkSize           = $this->chunkSize;
        $downloadedChunkSize = 0;

        $this->service = new GoogleDriveService($this->client);
        $file          = $this->service->files->get($fileId);
        $tmpDirectory  = WPStaging::make(Directory::class)->getDownloadsDirectory();
        $fileName      = $file->getName();
        $filePath      = $tmpDirectory . $fileName;
        $fileHandle    = fopen($filePath, 'a+');

        $chunkStart = filesize($filePath);
        $chunkEnd   = $chunkStart + $chunkSize;

        if ($chunkStart < $fileSize) {
            $http = $this->client->authorize();
            // There is no Google Drive API to download a file in chunks. So we use Guzzle to download the file in chunks.
            // @todo: do more research about this later. see https://developers.google.com/drive/api/guides/manage-downloads#partial_download
            $response = $http->request(
                'GET',
                sprintf('https://www.googleapis.com/drive/v3/files/%s', $fileId),
                [
                    'query' => ['alt' => 'media'],
                    'headers' => [
                        'Range' => sprintf('bytes=%s-%s', empty($chunkStart) ? 0 : $chunkStart, $chunkEnd)
                    ]
                ]
            );

            fwrite($fileHandle, $response->getBody()->getContents());
            $contentLength = $response->getHeaderLine('Content-Length');
            $downloadedChunkSize = intval($contentLength) > 0 ? $chunkEnd : $downloadedChunkSize;
        }

        if ($chunkStart >= $fileSize || $downloadedChunkSize >= $fileSize) {
            // close the file pointer
            fclose($fileHandle);
            $backupsDirectory = WPStaging::make(BackupsFinder::class)->getBackupsDirectory();
            $destination      = $backupsDirectory . $fileName;
            // move backup from tmp to backup folder
            rename($filePath, $destination);
            unlink($filePath);
            throw new FinishedQueueException($fileName);
        }

        return $downloadedChunkSize;
    }
}
