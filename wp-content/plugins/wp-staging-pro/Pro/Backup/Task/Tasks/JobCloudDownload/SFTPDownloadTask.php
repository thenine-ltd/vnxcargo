<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload;

use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\AbstractCloudDownloadTask;

class SFTPDownloadTask extends AbstractCloudDownloadTask
{

    public static function getTaskName(): string
    {
        return 'download_backup_from_sftp';
    }

    public static function getTaskTitle(): string
    {
        return 'Downloading backup from SFTP';
    }
}
