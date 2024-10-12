<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobBackup\RemoteStorageTasks;

use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Pro\Backup\Storage\Storages\GenericS3\Auth;
use WPStaging\Pro\Backup\Storage\Storages\GenericS3\Uploader;
use WPStaging\Pro\Backup\Task\Tasks\JobBackup\AbstractStorageTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class GenericS3StorageTask extends AbstractStorageTask
{
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Uploader $remoteUploader, Auth $auth)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $remoteUploader, $auth);
    }

    public function getStorageProvider()
    {
        return 'S3 Compatible';
    }

    public static function getTaskName()
    {
        return 'backup_generic_s3_upload';
    }

    public static function getTaskTitle()
    {
        return 'Uploading Backup to Generic S3';
    }
}
