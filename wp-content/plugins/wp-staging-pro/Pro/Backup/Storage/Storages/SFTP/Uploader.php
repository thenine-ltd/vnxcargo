<?php

namespace WPStaging\Pro\Backup\Storage\Storages\SFTP;

use Exception;
use WPStaging\Backup\Dto\Interfaces\RemoteUploadDtoInterface;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Backup\Exceptions\StorageException;
use WPStaging\Pro\Backup\Storage\RemoteUploaderInterface;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Pro\Backup\Storage\Storages\SFTP\Auth;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Core\WPStaging;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Pro\Backup\Dto\Job\JobCloudDownloadDataDto;

use function WPStaging\functions\debug_log;

class Uploader implements RemoteUploaderInterface
{
    use WithBackupIdentifier;

    /** @var RemoteUploadDtoInterface|JobCloudDownloadDataDto */
    private $jobDataDto;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $filePath;

    /** @var string */
    private $fileName;

    /** @var string */
    private $path;

    /** @var string */
    private $remotePath;

    /** @var int */
    private $maxBackupsToKeep;

    /** @var FileObject */
    private $fileObject;

    /** @var int */
    private $chunkSize;

    /** @var Auth */
    private $auth;

    /** @var object */
    private $client;

    /** @var bool|string */
    private $error;

    /** @var Strings */
    private $strings;

    /** @var string */
    private $protocol;

    public function __construct(Auth $auth, Strings $strings)
    {
        $this->error = false;
        $this->auth = $auth;
        if (!$this->auth->isAuthenticated()) {
            $this->error = __('FTP / SFTP service is not authenticated. Backup is still available locally.', 'wp-staging');
            return;
        }

        $this->strings = $strings;
        $this->client  = $auth->getClient();
        $this->client->close();

        $options = $this->auth->getOptions();
        $this->path = !empty($options['location']) ? trailingslashit($options['location']) : '';
        $this->maxBackupsToKeep = isset($options['maxBackupsToKeep']) ? $options['maxBackupsToKeep'] : 15;
        $this->maxBackupsToKeep = intval($this->maxBackupsToKeep);
        $this->maxBackupsToKeep = $this->maxBackupsToKeep > 0 ? $this->maxBackupsToKeep : 15;
        $this->protocol = !empty($options['ftpType']) ? trailingslashit($options['ftpType']) : 'ftp';
    }

    public function getProviderName()
    {
        return 'SFTP / FTP';
    }

    /**
     * @param int $backupSize
     * @throws DiskNotWritableException
     */
    public function checkDiskSize($backupSize)
    {
        //no-op
    }

    /**
     * @param LoggerInterface $logger
     * @param RemoteUploadDtoInterface $jobDataDto
     * @param $chunkSize
     * @return void
     */
    public function setupUpload(LoggerInterface $logger, RemoteUploadDtoInterface $jobDataDto, $chunkSize = 1 * 1024 * 1024)
    {
        $this->logger     = $logger;
        $this->jobDataDto = $jobDataDto;
        $this->chunkSize  = $chunkSize;
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

    public function setBackupFilePath($backupFilePath, $fileName)
    {
        $this->fileName   = $fileName;
        $this->filePath   = $backupFilePath;
        $this->fileObject = new FileObject($this->filePath, FileObject::MODE_READ);
        $this->remotePath = $this->path . $this->fileObject->getBasename();

        if (!$this->client->login()) {
            $this->error = 'Unable to connect to ' . $this->getProviderName();
            return false;
        }

        $uploadMetadata = $this->jobDataDto->getRemoteStorageMeta();
        if (!array_key_exists($this->fileName, $uploadMetadata)) {
            $this->setMetadata(0);
            $this->logger->info('SFTP: Starting upload of backup file:' . $this->fileName);
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

        if ($this->client->getIsSupportNonBlockingUpload() && $this->getIsNonBlockingUploadEnabled()) {
            return $this->performNonBlockingUpload($offset);
        }

        $this->fileObject->fseek($offset);
        $chunk = $this->fileObject->fread($this->chunkSize);

        $chunkSize = strlen($chunk);
        try {
            $this->client->upload($this->path, $this->fileName, $chunk, $offset);
            $offset += $chunkSize;
        } catch (StorageException $ex) {
            throw new StorageException($ex->getMessage());
        } catch (Exception $ex) {
            debug_log("Error: " . $ex->getMessage());
        }

        if ($offset >= $this->fileObject->getSize()) {
            throw new FinishedQueueException();
        }

        $this->setMetadata($offset);
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

        $fileObject->fseek(0);
        $chunk = $fileObject->fread($fileObject->getSize());

        return $this->uploadFileRetry($remoteFileName, $chunk, 0);
    }

    public function stopUpload()
    {
        $this->client->close();
    }

    /**
     * @return bool|string|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function getBackups()
    {
        if ($this->client === false) {
            $this->error = 'Unable to Initiate a Client';
            return [];
        }

        if (!$this->client->login()) {
            $this->error = "Unable to connect to " . $this->client->getError();
            return [];
        }

        try {
            $files = $this->client->getFiles($this->path);
            $this->client->close();
            if (!is_array($files)) {
                $this->error = $this->client->getError() . ' - ' . __('Unable to fetch existing backups for cleanup', 'wp-staging');
                return [];
            }

            $backups = [];
            foreach ($files as $key => $file) {
                if ($this->strings->endsWith($file['name'], '.wpstg') || $this->strings->endsWith($file['name'], '.sql')) {
                    $date = new \DateTime();
                    $date->setTimestamp($file['time']);
                    $backups[$key]                       = json_decode(json_encode($file));
                    $backups[$key]->storageProviderName  = $this->auth->getIdentifier();
                    $backups[$key]->type                 = $this->getProviderName();
                    $backups[$key]->id                   = $file['name'];
                    $backups[$key]->dateCreatedTimestamp = $date->format('Y-m-d H:i:s');
                }
            }

            return $backups;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            return [];
        }
    }

    /**
     * @return bool
     */
    public function deleteOldestBackups()
    {
        if ($this->client === false) {
            $this->error = 'Unable to Initiate a Client';
            return false;
        }

        if (!$this->client->login()) {
            $this->error = "Unable to connect to " . $this->client->getError();
            return false;
        }

        try {
            $retainedBackups = $this->auth->getRetainedBackups();
            if (count($retainedBackups) < $this->maxBackupsToKeep) {
                return true;
            }

            $files = $this->getBackups();

            foreach ($retainedBackups as $retainedBackupId => $retainedBackup) {
                if (count($retainedBackups) < $this->maxBackupsToKeep) {
                    return true;
                }

                $this->client->setPath($this->path);

                foreach ($files as $file) {
                    $fileName = $file->name;
                    if (strpos($fileName, $retainedBackupId) !== false) {
                        $isFileDeleted = $this->deleteFile($fileName);
                        $this->client->close();
                        if ($isFileDeleted === false) {
                            $this->error = $this->client->getError();
                            debug_log("Fail to delete oldest backups. Error message: " . $this->error);
                            return false;
                        }
                    }
                }

                $this->auth->unsetStorageFromRetainedBackups($retainedBackupId);
                unset($retainedBackups[$retainedBackupId]);
            }

            return true;
        } catch (Exception $ex) {
            debug_log("Delete oldest backup");
            $this->error = $ex->getMessage();
            return false;
        }
    }

    /**
     * @param array $uploadsToVerify array of backup files to verify
     * @return bool
     */
    public function verifyUploads(array $uploadsToVerify): bool
    {
        if (!$this->client->login()) {
            $this->error = "Unable to connect to " . $this->client->getError();
            return false;
        }

        $files = $this->client->getFiles($this->path);
        $this->client->close();
        if (!is_array($files)) {
            $this->error = $this->client->getError() . ' - ' . __('Unable to fetch existing backups for verification', 'wp-staging');
            return false;
        };

        $uploadsConfirmed = [];
        foreach ($files as $file) {
            $fileName = str_replace($this->path, '', $file['name']);

            if (!array_key_exists($fileName, $uploadsToVerify)) {
                continue;
            }

            $toVerify = $uploadsToVerify[$fileName];
            if ((is_null($file['size']) || $toVerify['size'] === $file['size'])) {
                $uploadsConfirmed[] = $fileName;
            }
        }

        return count($uploadsConfirmed) === count($uploadsToVerify);
    }

    /**
     * @param int $offset
     */
    protected function setMetadata($offset = 0)
    {
        $this->jobDataDto->setRemoteStorageMeta([
            $this->fileName => [
                'Offset' => $offset,
            ]
        ]);
    }

    /**
     * @param string $remoteFileName
     * @param string $chunk
     * @param int $offset
     * @param int $retry
     * @return bool
     *
     * @throws StorageException
     */
    protected function uploadFileRetry($remoteFileName, $chunk, $offset, $retry = 3)
    {
        try {
            if (!$this->client->login()) {
                debug_log("Login Error: " . $this->client->getError());
                return false;
            }

            $this->client->upload($this->path, $remoteFileName, $chunk, $offset);
            $this->client->close();
        } catch (StorageException $ex) {
            debug_log("Storage Error: " . $ex->getMessage());
            $this->client->close();
            throw new StorageException($ex->getMessage());
        } catch (Exception $ex) {
            if ($retry > 0) {
                debug_log("Error: " . $ex->getMessage() . '... Trying again!');
                return $this->uploadFileRetry($remoteFileName, $chunk, $offset, $retry - 1);
            }

            $this->client->close();
            return false;
        }

        return true;
    }

    /**
     * @param string $file
     * @param bool $addPath
     * @param int $retry
     * @return bool
     */
    public function deleteFile($file, $addPath = true, $retry = 3)
    {
        if ($this->client === false) {
            $this->error = 'Unable to Initiate a Client';
            return false;
        }

        if (!$this->client->login()) {
            $this->error = "Unable to connect to " . $this->client->getError();
            return false;
        }

        $this->client->setPath($this->path);
        $file = ($addPath && $this->path !== null) ? $this->path . $file : $file;
        $response = $this->client->deleteFile($file);
        if ($response) {
            return true;
        }

        if ($retry > 0) {
            debug_log($this->client->getError() . '... Trying again!');
            usleep(500);
            return $this->deleteFile($file, $addPath, $retry - 1);
        }

        return false;
    }

    /**
     * Save backup file from sftp to server
     *
     * @param string $fileId
     * @param int $fileSize
     * @param int $chunkStart
     * @return int|bool
     */
    public function chunkDownloadCloudFileToFolder(string $fileId, int $fileSize, int $chunkStart)
    {
        if (!$this->client->login()) {
            $this->error = "Unable to connect to " . $this->client->getError();
            return false;
        }

        $tmpDirectory  = WPStaging::make(Directory::class)->getDownloadsDirectory();
        $filePath      = $tmpDirectory . $fileId;

        $chunkSize  = $this->chunkSize;
        $chunkStart = filesize($filePath);
        $chunkEnd   = $chunkStart + $chunkSize;

        if ($chunkStart < $fileSize) {
            $this->client->setPath($this->path);
            $status = $this->client->downloadAsChunks($this->path, $filePath, $fileId, $chunkStart, $chunkSize);
        }

        if ($chunkStart >= $fileSize || ($status && $chunkEnd >= $fileSize)) {
            $backupsDirectory = WPStaging::make(BackupsFinder::class)->getBackupsDirectory();
            $destination = $backupsDirectory . $fileId;
            // move backup from tmp to backup folder
            rename($filePath, $destination);
            unlink($filePath);
            throw new FinishedQueueException($fileId);
        }

        $chunkEnd = ($status) ? $chunkEnd : 0;
        return $chunkEnd;
    }

    /**
     * @param int $offset
     * @return int
     */
    protected function performNonBlockingUpload(int $offset): int
    {
        $this->setMetadata($offset);

        $remoteFile = $this->path;
        if (!empty($remoteFile)) {
            $remoteFile = trailingslashit($remoteFile);
        }

        try {
            $newChunkAdded = $this->client->nonBlockingUpload($remoteFile . $this->fileName, $this->filePath, $offset);
            $offset       += $newChunkAdded;
        } catch (StorageException $ex) {
            throw new StorageException($ex->getMessage());
        } catch (FinishedQueueException $ex) {
            throw new FinishedQueueException();
        } catch (Exception $ex) {
            debug_log("Error: " . $ex->getMessage());
        }

        if ($offset >= $this->fileObject->getSize()) {
            throw new FinishedQueueException();
        }

        $this->setMetadata($offset);

        return $newChunkAdded;
    }

    /**
     * @return bool
     */
    protected function getIsNonBlockingUploadEnabled(): bool
    {
        $options = $this->auth->getOptions();
        return isset($options['ftpMode']) && $options['ftpMode'] === Auth::FTP_UPLOAD_MODE_NON_BLOCKING;
    }
}
