<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobRemoteUpload;

use WPStaging\Backup\Task\AbstractTask;
use WPStaging\Pro\Backup\Dto\Job\JobRemoteUploadDataDto;

abstract class RemoteUploadTask extends AbstractTask
{
    /** @var JobRemoteUploadDataDto $jobDataDto */
    protected $jobDataDto;
}
