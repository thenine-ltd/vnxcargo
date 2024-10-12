<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobRemoteUpload;

use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class FinishUploadTask extends RemoteUploadTask
{
    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
    }

    /**
     * @return string
     */
    public static function getTaskName(): string
    {
        return 'remote_upload_finish';
    }

    /**
     * @return string
     */
    public static function getTaskTitle(): string
    {
        return 'Finishing Upload';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute(): TaskResponseDto
    {
        $this->stepsDto->finish();

        $this->jobDataDto->setEndTime(time());

        return $this->generateResponse();
    }
}
