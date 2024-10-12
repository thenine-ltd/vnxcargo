<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload;

use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\AbstractCloudDownloadTask;

class AmazonS3DownloadTask extends AbstractCloudDownloadTask
{

    public static function getTaskName(): string
    {
        return 'download_backup_from_s3';
    }

    public static function getTaskTitle(): string
    {
        return 'Downloading backup from S3';
    }
}
