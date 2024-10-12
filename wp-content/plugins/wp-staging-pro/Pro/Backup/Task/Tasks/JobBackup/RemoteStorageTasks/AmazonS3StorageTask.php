<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobBackup\RemoteStorageTasks;

use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Pro\Backup\Storage\Storages\Amazon\S3;
use WPStaging\Pro\Backup\Storage\Storages\Amazon\S3Uploader;
use WPStaging\Pro\Backup\Task\Tasks\JobBackup\AbstractStorageTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class AmazonS3StorageTask extends AbstractStorageTask
{
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, S3Uploader $remoteUploader, S3 $auth)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $remoteUploader, $auth);
    }

    public function getStorageProvider()
    {
        return 'Amazon S3';
    }

    public static function getTaskName()
    {
        return 'backup_amazon_s3_upload';
    }

    public static function getTaskTitle()
    {
        return 'Uploading Backup to Amazon S3';
    }
}
