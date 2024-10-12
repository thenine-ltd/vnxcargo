<?php

namespace WPStaging\Pro\Backup\Dto\Task\Restore;

use WPStaging\Backup\Dto\AbstractTaskDto;

class ImportSubsiteUsersTaskDto extends AbstractTaskDto
{
    /** @var bool */
    public $started;

    /** @var int */
    public $totalRows;

    /** @var int */
    public $rowsCopied;
}
