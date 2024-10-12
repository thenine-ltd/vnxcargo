<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload;

use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\AbstractCloudDownloadTask;

class GenericS3DownloadTask extends AbstractCloudDownloadTask
{

    public static function getTaskName(): string
    {
        return 'download_backup_from_generic_s3';
    }

    public static function getTaskTitle(): string
    {
        return 'Downloading backup from GenericS3';
    }
}
