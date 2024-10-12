<?php

namespace WPStaging\Pro\Backup\Storage\Storages\BaseS3;

use Exception;
use WPStaging\Backup\Dto\Interfaces\RemoteUploadDtoInterface;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Backup\Exceptions\StorageException;
use WPStaging\Pro\Backup\Storage\RemoteUploaderInterface;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Vendor\Aws\S3\Exception\S3Exception;
use WPStaging\Vendor\Aws\S3\S3Client;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Vendor\Aws\Result;
use WPStaging\Core\WPStaging;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Pro\Backup\Dto\Job\JobCloudDownloadDataDto;

use function WPStaging\functions\debug_log;

abstract class S3Uploader implements RemoteUploaderInterface
{
    use WithBackupIdentifier;

    /** @var RemoteUploadDtoInterface|JobCloudDownloadDataDto */
    private $jobDataDto;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $bucketName;

    /** @var string */
    private $path;

    /** @var string */
    private $objectKey;

    /** @var int */
    private $maxBackupsToKeep;

    /** @var FileObject */
    private $fileObject;

    /** @var string */
    private $filePath;

    /** @var string */
    private $fileName;

    /** @var int */
    private $chunkSize;

    /** @var S3Auth */
    private $auth;

    /** @var S3Client */
    private $client;

    /** @var bool|string */
    private $error;

    /** @var Strings */
    private $strings;

    /** @var bool */
    private $isObjectLocked = false;

    /** @var Sanitize */
    private $sanitize;

    public function __construct(S3Auth $auth, Strings $strings)
    {
        $this->error = false;
        $this->auth = $auth;

        if (!$this->auth->isGuzzleAvailable()) {
            $this->error = __('cURL extension is missing. Backup is still available locally.', 'wp-staging');
            return;
        }

        if (!$this->auth->isAuthenticated()) {
            $this->error = $this->getProviderName() . __(' service is not authenticated. Backup is still available locally.', 'wp-staging');
            return;
        }

        $this->strings = $strings;
        $this->client  = $auth->getClient();

        $options = $this->auth->getOptions();
        $location = $this->auth->getLocation();
        $this->bucketName = $location[0];
        $this->path = $location[1];
        $this->maxBackupsToKeep = isset($options['maxBackupsToKeep']) ? $options['maxBackupsToKeep'] : 15;
        $this->maxBackupsToKeep = intval($this->maxBackupsToKeep);
        $this->maxBackupsToKeep = $this->maxBackupsToKeep > 0 ? $this->maxBackupsToKeep : 15;
    }

    public function setupUpload(LoggerInterface $logger, RemoteUploadDtoInterface $jobDataDto, $chunkSize = 5 * 1024 * 1024)
    {
        $this->logger = $logger;
        $this->jobDataDto = $jobDataDto;
        $this->chunkSize = $chunkSize;
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
        $this->chunkSize  = $chunkSize;
    }

    /**
     * @throws DiskNotWritableException
     */
    public function setBackupFilePath($backupFilePath, $fileName)
    {
        $this->fileName = $fileName;
        $this->filePath = $backupFilePath;
        $this->fileObject = new FileObject($this->filePath, FileObject::MODE_READ);

        $this->objectKey = $this->path . $this->fileName;

        // Amazon S3 support only allow 10,000 parts for a single file upload.
        // This will make sure that these parts are below 10,000 by adjusting chunkSize accordingly
        while (($this->fileObject->getSize() / 10000) > $this->chunkSize) {
            $chunkSize = 5 * 1024 * 1024;
            $this->chunkSize += $chunkSize;
        }

        $this->isObjectLocked = $this->getIsObjectLocked();
        $uploadMetadata = $this->jobDataDto->getRemoteStorageMeta();
        if (!array_key_exists($this->fileName, $uploadMetadata)) {
            $model = $this->client->createMultipartUpload([
                'Bucket' => $this->bucketName,
                'Key' => $this->objectKey,
                'ContentType' => 'application/octet-stream',
                'Metadata' => []
            ]);

            $this->setMetadata($model['UploadId'], 0, []); // @phpstan-ignore-line
            $this->logger->info('Starting upload of file:' . $this->fileName);
            return true;
        }

        return true;
    }

    /**
     * @return int
     */
    public function chunkUpload()
    {
        $fileMetadata = $this->jobDataDto->getRemoteStorageMeta()[$this->fileName];
        $offset = $fileMetadata['Offset'];

        $partNumber = (int)ceil(($offset - 1) / $this->chunkSize);
        $partNumber++;

        $this->fileObject->fseek($offset);
        $chunk = $this->fileObject->fread($this->chunkSize);

        $parts = $fileMetadata['Parts'];
        $uploadId = $fileMetadata['UploadId'];

        $chunkSize = 0;
        try {
            $uploadParams = [
                'Bucket' => $this->bucketName,
                'Key' => $this->objectKey,
                'UploadId' => $uploadId,
                'PartNumber' => $partNumber,
                'Body' => $chunk,
            ];

            if ($this->isObjectLocked) {
                $uploadParams['ContentMD5'] = base64_encode(md5($chunk, true));
            }

            $result = $this->client->uploadPart($uploadParams);

            $parts['Parts'][$partNumber] = [
                'PartNumber' => $partNumber,
                'ETag' => $result['ETag'], // @phpstan-ignore-line
            ];

            $chunkSize = strlen($chunk);
            $offset += $chunkSize;

            if ($offset >= $this->fileObject->getSize()) {
                $result = $this->client->completeMultipartUpload([
                    'Bucket' => $this->bucketName,
                    'Key' => $this->objectKey,
                    'UploadId' => $uploadId,
                    'MultipartUpload' => $parts,
                ]);

                throw new FinishedQueueException();
            }
        } catch (FinishedQueueException $ex) {
            throw new FinishedQueueException($ex->getMessage());
        } catch (Exception $ex) {
            $result = $this->client->abortMultipartUpload([
                'Bucket' => $this->bucketName,
                'Key' => $this->objectKey,
                'UploadId' => $uploadId
            ]);

            debug_log($ex->getMessage());
            throw new StorageException("Unable to Upload to S3 Storage: " . $ex->getMessage());
        }

        $this->setMetadata($uploadId, $offset, $parts);
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

        $this->objectKey = $this->path . $remoteFileName;

        $this->isObjectLocked = $this->getIsObjectLocked();

        $model = $this->client->createMultipartUpload([
            'Bucket' => $this->bucketName,
            'Key' => $this->objectKey,
            'ContentType' => 'application/octet-stream',
            'Metadata' => []
        ]);

        $fileObject->fseek(0);
        $chunk = $fileObject->fread($fileObject->getSize());
        $partNumber = 1;

        $uploadParams = [
            'Bucket' => $this->bucketName,
            'Key' => $this->objectKey,
            'UploadId' => $model['UploadId'], // @phpstan-ignore-line
            'PartNumber' => $partNumber,
            'Body' => $chunk,
        ];

        if ($this->isObjectLocked) {
            $uploadParams['ContentMD5'] = base64_encode(md5($chunk, true));
        }

        try {
            $result = $this->client->uploadPart($uploadParams);

            $parts['Parts'][$partNumber] = [
                'PartNumber' => $partNumber,
                'ETag' => $result['ETag'], // @phpstan-ignore-line
            ];

            $result = $this->client->completeMultipartUpload([
                'Bucket' => $this->bucketName,
                'Key' => $this->objectKey,
                'UploadId' => $model['UploadId'], // @phpstan-ignore-line
                'MultipartUpload' => $parts,
            ]);
        } catch (Exception $ex) {
            debug_log("Error: " . $ex->getMessage());

            $result = $this->client->abortMultipartUpload([
                'Bucket' => $this->bucketName,
                'Key' => $this->objectKey,
                'UploadId' => $model['UploadId'] // @phpstan-ignore-line
            ]);

            return false;
        }

        return true;
    }

    public function stopUpload()
    {
        // no-op
    }

    public function getError()
    {
        return $this->error;
    }

    public function getBackups()
    {
        if ($this->client === false) {
            return [];
        }

        try {
            $files = $this->auth->getFiles();

            // Sort by date in ascending order
            uasort($files, function ($object1, $object2) {
                $date1 = (new \DateTime($object1['LastModified']));
                $date2 = (new \DateTime($object2['LastModified']));

                return $date1 < $date2 ? -1 : 1;
            });

            $backups = [];
            foreach ($files as $key => $file) {
                if ($this->strings->endsWith($file['Key'], '.wpstg') || $this->strings->endsWith($file['Key'], '.sql')) {
                    $date                                = new \DateTime($file['LastModified']);
                    $backups[$key]                       = json_decode(json_encode($file));
                    $backups[$key]->name                 = basename($file['Key']);
                    $backups[$key]->size                 = $file['Size'];
                    $backups[$key]->storageProviderName  = $this->auth->getIdentifier();
                    $backups[$key]->type                 = $this->getProviderName();
                    $backups[$key]->id                   = $file['Key'];
                    $backups[$key]->dateCreatedTimestamp = $date->format('Y-m-d H:i:s');
                }
            }

            return $backups;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            return [];
        }
    }

    public function deleteOldestBackups()
    {
        if ($this->client === false) {
            return false;
        }

        try {
            $retainedBackups = $this->auth->getRetainedBackups();
            if (count($retainedBackups) < $this->maxBackupsToKeep) {
                return true;
            }

            $remoteBackupsFiles = $this->getBackups();

            foreach ($retainedBackups as $retainedBackupId => $retainedBackup) {
                if (count($retainedBackups) < $this->maxBackupsToKeep) {
                    return true;
                }

                foreach ($remoteBackupsFiles as $file) {
                    $fileName = $file->Key;
                    if (strpos($fileName, $retainedBackupId) !== false) {
                        $this->client->deleteObject([
                            'Bucket' => $this->bucketName,
                            'Key'    => $fileName
                        ]);
                    }
                }

                $this->auth->unsetStorageFromRetainedBackups($retainedBackupId);
                unset($retainedBackups[$retainedBackupId]);
            }

            return true;
        } catch (S3Exception $ex) {
            $this->error = $ex->getMessage();
            debug_log('S3 deleting oldest backups error: ' . $this->error);
            return false;
        }
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
            $fileName = str_replace($this->path, '', $file['Key']);
            if (!array_key_exists($fileName, $uploadsToVerify)) {
                continue;
            }

            $toVerify = $uploadsToVerify[$fileName];
            $fileSize = (int)$file['Size'];
            if ($toVerify['size'] !== $fileSize) {
                continue;
            }

            $uploadsConfirmed[] = $fileName;
        }

        return count($uploadsConfirmed) === count($uploadsToVerify);
    }

    /**
     * @param int $backupSize
     * @throws DiskNotWritableException
     */
    public function checkDiskSize($backupSize)
    {
        //no-op
    }

    protected function setMetadata($uploadId, $offset, $parts)
    {
        $this->jobDataDto->setRemoteStorageMeta([
            $this->fileName => [
                'UploadId' => $uploadId,
                'Offset' => $offset,
                'Parts' => $parts
            ]
        ]);
    }

    /** @return bool */
    protected function getIsObjectLocked()
    {
        try {
            $result = $this->client->getObjectLockConfiguration([
                'Bucket' => $this->bucketName
            ]);

            return $result['ObjectLockConfiguration']['ObjectLockEnabled'] === 'Enabled'; // @phpstan-ignore-line
        } catch (Exception $ex) {
            if (strpos($ex->getMessage(), 'ObjectLockConfigurationNotFoundError') !== false) {
                return false;
            }

            debug_log($ex->getMessage(), 'info', false);
            if ($this->logger !== null) {
                $this->logger->warning(__('IAM user does not have s3:GetBucketObjectLockConfiguration permission. Therefore cannot retrieve the Object Lock Configuration! Treatment as Object Lock Disabled. If upload fails extend user permission.', 'wp-staging'));
            }

            return false;
        }
    }

    /**
     * @param string $fileId
     * @param int $fileSize
     * @param int $chunkStart
     * @return int
     */
    public function chunkDownloadCloudFileToFolder(string $fileId, int $fileSize, int $chunkStart)
    {
        $tmpDirectory = WPStaging::make(Directory::class)->getDownloadsDirectory();
        $fileName     = basename($fileId);
        $filePath     = $tmpDirectory . $fileName;
        $fileHandle   = fopen($filePath, 'a+');
        if (!$fileHandle) {
            debug_log("S3 cannot create file! Error message: ");
            throw new Exception("S3 cannot create file! Error message: ");
        }

        $chunkSize           = $this->chunkSize;
        $chunkStart          = filesize($filePath);
        $chunkEnd            = $chunkStart + $chunkSize;
        $downloadedChunkSize = 0;

        if ($chunkStart < $fileSize) {
            /** @var Result $file */
            $file = $this->client->getObject([
                'Bucket' => $this->bucketName,
                'Key' => $fileId,
                'Range' => sprintf('bytes=%s-%s', ($chunkStart === 0) ? 0 : $chunkStart, $chunkEnd)
            ]);
            $body = $file->get('Body');
            fwrite($fileHandle, $body);
            $contentLength = strlen($body);
            $downloadedChunkSize = $contentLength > 0 ? $chunkEnd : $downloadedChunkSize;
        }

        if ($chunkStart >= $fileSize || $downloadedChunkSize >= $fileSize) {
            // close the file pointer
            fclose($fileHandle);
            $backupsDirectory = WPStaging::make(BackupsFinder::class)->getBackupsDirectory();
            $destination = $backupsDirectory . $fileName;
            // move backup from tmp to backup folder
            rename($filePath, $destination);
            unlink($filePath);
            throw new FinishedQueueException($fileName);
        }

        return $downloadedChunkSize;
    }

    /**
     * Delete backup from s3
     *
     * @param string $fileId
     * @return void|bool
     */
    public function deleteFile($fileId)
    {
        if ($this->client === false) {
            return false;
        }

        if ($this->client->deleteObject(['Bucket' => $this->bucketName, 'Key' => $fileId])) {
            return true;
        }

        return false;
    }
}
