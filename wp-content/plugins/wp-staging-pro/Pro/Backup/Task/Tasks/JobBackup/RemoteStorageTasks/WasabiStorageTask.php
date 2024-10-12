<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobBackup\RemoteStorageTasks;

use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Pro\Backup\Storage\Storages\Wasabi\Auth;
use WPStaging\Pro\Backup\Storage\Storages\Wasabi\Uploader;
use WPStaging\Pro\Backup\Task\Tasks\JobBackup\AbstractStorageTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class WasabiStorageTask extends AbstractStorageTask
{
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Uploader $remoteUploader, Auth $auth)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $remoteUploader, $auth);
    }

    public function getStorageProvider()
    {
        return 'Wasabi S3';
    }

    public static function getTaskName()
    {
        return 'backup_wasabi_upload';
    }

    public static function getTaskTitle()
    {
        return 'Uploading Backup to Wasabi';
    }
}
