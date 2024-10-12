<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobRemoteUpload;

use RuntimeException;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class UploadRequirementsCheckTask extends RemoteUploadTask
{
    /** @var SystemInfo */
    protected $systemInfo;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param SystemInfo $systemInfo
     */
    public function __construct(
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        SystemInfo $systemInfo
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->systemInfo = $systemInfo;
    }

    /**
     * @return string
     */
    public static function getTaskName(): string
    {
        return 'remote_upload_requirements_check';
    }

    /**
     * @return string
     */
    public static function getTaskTitle(): string
    {
        return 'Requirements Check';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute(): TaskResponseDto
    {
        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal(1);
        }

        try {
            $this->logger->info('#################### Start Remote Upload Job ####################');
            $this->logger->writeLogHeader();

            $this->shouldWarnIfRunning32Bits();
            $this->cannotBackupWithNoStorage();
        } catch (RuntimeException $e) {
            $this->logger->critical($e->getMessage());

            return $this->generateResponse(false);
        }

        $this->logger->info(__('Remote Upload requirements passed...', 'wp-staging'));

        return $this->generateResponse();
    }

    /**
     * @return void
     */
    protected function shouldWarnIfRunning32Bits()
    {
        if (PHP_INT_SIZE === 4) {
            $this->logger->warning(__('You are running a 32-bit version of PHP. 32-bits PHP can\'t handle backups larger than 2GB. You might face a critical error. Consider upgrading to 64-bit.', 'wp-staging'));
        }
    }

    /**
     * @return void
     */
    protected function cannotBackupWithNoStorage()
    {
        if (empty($this->jobDataDto->getStorages())) {
            throw new RuntimeException(__('You must select at least one storage to upload backup.', 'wp-staging'));
        }
    }
}
