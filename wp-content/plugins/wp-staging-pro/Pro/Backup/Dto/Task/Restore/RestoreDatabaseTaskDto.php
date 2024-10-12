<?php

namespace WPStaging\Pro\Backup\Dto\Task\Restore;

use WPStaging\Backup\Dto\AbstractTaskDto;

class RestoreDatabaseTaskDto extends AbstractTaskDto
{
    /** @var int */
    public $currentSubsiteId;
}
