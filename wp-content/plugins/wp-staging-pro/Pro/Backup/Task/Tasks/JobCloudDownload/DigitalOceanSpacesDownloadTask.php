<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload;

use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\AbstractCloudDownloadTask;

class DigitalOceanSpacesDownloadTask extends AbstractCloudDownloadTask
{

    public static function getTaskName(): string
    {
        return 'download_backup_from_digital_ocean_spaces';
    }

    public static function getTaskTitle(): string
    {
        return 'Downloading backup from Digital Ocean Spaces';
    }
}
