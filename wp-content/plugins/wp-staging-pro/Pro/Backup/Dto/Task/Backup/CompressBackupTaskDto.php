<?php

namespace WPStaging\Pro\Backup\Dto\Task\Backup;

use WPStaging\Backup\Dto\AbstractTaskDto;

class CompressBackupTaskDto extends AbstractTaskDto
{
    /** @var bool */
    public $bigFile;

    /** @var int */
    public $bigFileBytesRead;

    /** @var bool */
    public $bigFileIsCompressed;

    /** @var int|false */
    public $bigFileCompressedStart;

    /** @var int */
    public $chunks;
}
