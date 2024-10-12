<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobRemoteUpload;

use RuntimeException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Entity\MultipartMetadata;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class PrepareUploadTask extends RemoteUploadTask
{
    /** @var Directory */
    protected $directory;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param Directory $directory
     */
    public function __construct(
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        Directory $directory
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->directory = $directory;
    }

    /**
     * @return string
     */
    public static function getTaskName(): string
    {
        return 'remote_upload_prepare';
    }

    /**
     * @return string
     */
    public static function getTaskTitle(): string
    {
        return 'Preparing backup to upload';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute(): TaskResponseDto
    {
        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal(1);
        }

        try {
            $backupDirectory = $this->directory->getBackupDirectory();
            $this->jobDataDto->setStartTime(time());
            $backupFile = $this->jobDataDto->getFile();
            $backupPath = $backupDirectory . $backupFile;
            /** @var BackupMetadata */
            $metadata = WPStaging::make(BackupMetadata::class);
            $metadata = $metadata->hydrateByFilePath($backupPath);

            $this->jobDataDto->setTotalBackupSize($metadata->getBackupSize());

            if (!$metadata->getIsMultipartBackup()) {
                $this->jobDataDto->setFilesToUpload([
                    $backupFile => $backupPath
                ]);
            } else {
                $this->setMultipartFilesToUpload($backupDirectory, $metadata);
            }
        } catch (RuntimeException $e) {
            $this->logger->critical($e->getMessage());

            return $this->generateResponse(false);
        }

        $this->logger->info(__('Backup prepared to be uploaded...', 'wp-staging'));

        return $this->generateResponse();
    }

    /**
     * @param string $backupDirectory
     * @param BackupMetadata $metadata
     * @return void
     */
    protected function setMultipartFilesToUpload(string $backupDirectory, BackupMetadata $metadata)
    {
        $filesToUpload = [];

        /** @var MultipartMetadata */
        $multipart = $metadata->getMultipartMetadata();

        foreach ($multipart->getBackupParts() as $part) {
            $fullPath = $backupDirectory . $part;
            $filesToUpload[$part] = $fullPath;
        }

        $this->jobDataDto->setFilesToUpload($filesToUpload);
    }
}
