<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobBackup;

use Exception;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Dto\Interfaces\RemoteUploadDtoInterface;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Backup\Exceptions\StorageException;
use WPStaging\Backup\Task\AbstractTask;
use WPStaging\Pro\Backup\Storage\AbstractStorage;
use WPStaging\Pro\Backup\Storage\BackupUploadedException;
use WPStaging\Pro\Backup\Storage\RemoteUploaderInterface;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

use function WPStaging\functions\debug_log;

abstract class AbstractStorageTask extends AbstractTask
{
    /** @var int */
    const MAX_RETRY  = 3;

    /** @var int Chunk size in MB */
    const CHUNK_SIZE = 5;

    /** @var int */
    protected $retried = 0;

    /** @var RemoteUploaderInterface */
    protected $remoteUploader;

    /** @var RemoteUploadDtoInterface */
    protected $jobDataDto; // @phpstan-ignore-line

    /** @var AbstractStorage */
    protected $storage;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param RemoteUploaderInterface $remoteUploader
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, RemoteUploaderInterface $remoteUploader, AbstractStorage $storage)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->remoteUploader = $remoteUploader;
        $this->storage        = $storage;
    }

    abstract public function getStorageProvider();

    /**
     * @return TaskResponseDto
     */
    public function execute(): TaskResponseDto
    {
        if ($this->remoteUploader->getError() !== false) {
            $this->logger->warning($this->remoteUploader->getError());
            return $this->generateResponse(false);
        }

        $chunkSize = apply_filters('wpstg.remoteStorages.chunkSize', self::CHUNK_SIZE * MB_IN_BYTES);

        $this->remoteUploader->setupUpload($this->logger, $this->jobDataDto, $chunkSize);

        try {
            $this->prepareBackupUpload();
        } catch (BackupUploadedException $ex) {
            $this->logger->info(sprintf(esc_html__('%s: Backup already uploaded.', 'wp-staging'), $this->getProviderName()));
            return $this->finishUpload();
        }

        if ($this->stepsDto->isFinished()) {
            return $this->finishUpload();
        }

        foreach ($this->jobDataDto->getFilesToUpload() as $fileToUpload => $filePath) {
            if (array_key_exists($fileToUpload, $this->jobDataDto->getUploadedFiles())) {
                continue;
            }

            return $this->upload($filePath, $fileToUpload);
        }

        $this->stepsDto->finish();
        return $this->finishUpload();
    }

    /**
     * @param string $backupFilePath
     * @param string $fileName
     * @return TaskResponseDto
     */
    protected function upload(string $backupFilePath, string $fileName): TaskResponseDto
    {
        try {
            $canUpload = $this->remoteUploader->setBackupFilePath($backupFilePath, $fileName);
        } catch (StorageException $ex) {
            $this->sendReport();
            debug_log('Fail to upload. Error: ' . $ex->getMessage());
            return $this->cancelUpload();
        }

        if (!$canUpload) {
            $this->logger->warning('Notice: ' . $this->remoteUploader->getError());
            $this->remoteUploader->stopUpload();
            return $this->generateResponse(false);
        }

        $uploaded = 0;
        $fileSize = filesize($backupFilePath);
        $fileSizeFormatted = size_format($fileSize, 2);
        $this->retried = 0;

        // Delay in requests in milliseconds
        $delay = apply_filters('wpstg.remoteStorages.delayBetweenRequests', 0);
        $delay = filter_var($delay, FILTER_VALIDATE_INT);
        // make sure delay cannot be less than 0
        $delay = max(0, $delay);
        // make sure delay cannot be more than 1 second
        $delay = min(1000, $delay);

        while (!$this->isThreshold()) {
            try {
                $chunkSizeUploaded = $this->remoteUploader->chunkUpload();
                $this->stepsDto->setCurrent($this->stepsDto->getCurrent() + $chunkSizeUploaded);
                $uploaded = $this->jobDataDto->getRemoteStorageMeta()[$fileName]['Offset'];
            } catch (FinishedQueueException $exception) {
                $this->logger->info($this->getProviderName() . ': Uploaded ' . $fileSizeFormatted . '/' . $fileSizeFormatted . ' of backup ' . $fileName);
                $this->jobDataDto->setUploadedFile($fileName, $fileSize, $this->storage->computeFileHash($backupFilePath));
                return $this->generateResponse(false);
            } catch (StorageException $exception) {
                $this->logger->error($exception->getMessage());
                $this->remoteUploader->stopUpload();
                return $this->generateResponse(false);
            } catch (DiskNotWritableException $exception) {
                // Probably disk full. No-op, as this is handled elsewhere.
            } catch (Exception $exception) {
                // Last chunk maybe. No-op
                debug_log('Upload Error: ' . $exception->getMessage());
            }

            if ($uploaded === 0) {
                $this->retried++;
            }

            if ($this->retried > self::MAX_RETRY) {
                $this->sendReport();
                debug_log('Fail to upload file after retrying.');
                return $this->cancelUpload();
            }

            if ($delay > 0) {
                // convert milliseconds to microseconds for usleep function
                usleep($delay * 1000);
            }
        }

        $uploadedSizeFormatted = size_format($uploaded, 2);
        $this->logger->info($this->getProviderName() . ': Uploaded ' . $uploadedSizeFormatted . '/' . $fileSizeFormatted . ' of backup ' . $fileName);
        $this->remoteUploader->stopUpload();
        $this->stepsDto->setManualPercentage(100 * $uploaded / $fileSize);
        return $this->generateResponse(false);
    }

    /**
     * @return string
     */
    protected function getProviderName(): string
    {
        return $this->remoteUploader->getProviderName();
    }

    /**
     * @return bool
     * @throws BackupUploadedException
     */
    public function prepareBackupUpload(): bool
    {
        if ($this->stepsDto->getTotal() > 0) {
            return true;
        }

        $this->jobDataDto->setRemoteStorageMeta([]);

        if (!$this->shouldCleanOldBackupsForRemoteUpload()) {
            $this->stepsDto->setTotal($this->jobDataDto->getTotalBackupSize());
            $this->stepsDto->setCurrent(0);
            $this->logger->info($this->getProviderName() . ': Initiate backup upload.');
            return true;
        }

        $deleted = $this->remoteUploader->deleteOldestBackups();
        if (!$deleted) {
            $this->logger->info($this->remoteUploader->getError());
            return false;
        }

        $this->logger->info(sprintf(esc_html__('%s: Cleaned old backups if any.', 'wp-staging'), $this->getProviderName()));
        $this->stepsDto->setTotal($this->jobDataDto->getTotalBackupSize());
        $this->stepsDto->setCurrent(0);
        $this->logger->info($this->getProviderName() . ': Initiate backup upload.');
        return true;
    }

    /**
     * @return array
     */
    public function getBackupsNames(): array
    {
        $backups = $this->remoteUploader->getBackups();

        $backupsNames = [];

        foreach ($backups as $backup) {
            // S3 Objects
            if (is_object($backup) && property_exists($backup, 'Key')) {
                $backupsNames[] = $backup->Key;
                continue;
            }

            // Dropbox
            if (is_array($backup) && array_key_exists('name', $backup)) {
                $backupsNames[] = $backup['name'];
                continue;
            }

            // Ftp, Sftp
            if (is_object($backup) && property_exists($backup, 'name')) {
                $backupsNames[] = $backup->name;
                continue;
            }

            // Google Drive
            if (is_object($backup) && method_exists($backup, 'getName')) {
                $backupsNames[] = $backup->getName();
                continue;
            }
        }

        return $backupsNames;
    }

    /**
     * Whether to clean old backups or not. Used during Standalone remote storage upload
     * @return bool
     *
     * @throws BackupUploadedException in case all backup files already uploaded
     */
    protected function shouldCleanOldBackupsForRemoteUpload(): bool
    {
        // Early bail: if it is not a upload only job
        if (!$this->jobDataDto->getIsOnlyUpload()) {
            return true;
        }

        $backupsNames        = $this->getBackupsNames();
        $backupFilesToUpload = $this->jobDataDto->getFilesToUpload();
        $backupFilesExist    = 0;
        $backupFilesVerified = [];

        foreach ($backupFilesToUpload as $backupFile => $filePath) {
            if (!in_array($backupFile, $backupsNames)) {
                continue;
            }

            $backupFilesExist++;
            $verified = $this->remoteUploader->verifyUploads([
                $backupFile => [
                    'size' => filesize($filePath),
                    'hash' => $this->storage->computeFileHash($filePath)
                ]
            ]);

            if ($verified) {
                $backupFilesVerified[$backupFile] = [
                    'size' => filesize($filePath),
                    'hash' => $this->storage->computeFileHash($filePath)
                ];
            } else {
                // Let delete the unverified backup file (different size)
                $this->storage->deleteFile($backupFile);
            }
        }

        // If no file exists let continue normally deleting old backups
        if ($backupFilesExist === 0) {
            return true;
        }

        // If all backup files already backup up, we can skip the upload
        $this->jobDataDto->setUploadedFiles($backupFilesVerified);
        if (count($backupFilesVerified) === count($backupFilesToUpload)) {
            throw new BackupUploadedException();
        }

        return false;
    }

    /**
     * @return TaskResponseDto
     */
    protected function finishUpload(): TaskResponseDto
    {
        $this->remoteUploader->stopUpload();
        $this->jobDataDto->setEndTime(time());
        $this->logger->info($this->getProviderName() . ': Backup successfully uploaded.');
        $this->logger->info($this->getProviderName() . ': Checking the uploaded backup.');
        if (!$this->remoteUploader->verifyUploads($this->jobDataDto->getUploadedFiles())) {
            $this->logger->warning($this->getProviderName() . ': Couldn\'t verify uploaded backup.');
            return $this->generateResponse(false);
        }

        $this->logger->info($this->getProviderName() . ': Uploaded backup is intact and functional.');
        // Reset the uploaded files for next remote storage
        $this->jobDataDto->setUploadedFiles([]);
        return $this->generateResponse(false);
    }

    /**
     * @return TaskResponseDto
     */
    private function cancelUpload(): TaskResponseDto
    {
        $this->remoteUploader->stopUpload();
        $this->jobDataDto->setEndTime(time());
        $this->logger->warning($this->getStorageProvider() . ' - Upload Cancelled: Unable to upload backup');
        return $this->generateResponse(false);
    }

    /**
     * @return void
     */
    private function sendReport()
    {
        if (!$this->jobDataDto->getIsAutomatedBackup()) {
            return;
        }

        /** @var BackupScheduler */
        $backupScheduler = WPStaging::make(BackupScheduler::class);
        $backupScheduler->sendErrorEmailReport("Unable to upload to remote storage provider: " . $this->getStorageProvider());
    }
}
