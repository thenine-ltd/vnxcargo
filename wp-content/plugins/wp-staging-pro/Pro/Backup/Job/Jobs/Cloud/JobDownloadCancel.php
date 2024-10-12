<?php

namespace WPStaging\Pro\Backup\Job\Jobs\Cloud;

use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Backup\Job\AbstractJob;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\CleanupDownloadTmpFilesTask;

class JobDownloadCancel extends AbstractJob
{
    /** @var string[] List of tasks to execute for this job. Populated at init(). */
    private $tasks = [];

    /**
     * @return string
     */
    public static function getJobName(): string
    {
        return 'restore_cancel';
    }

    /**
     * @return string[]
     */
    protected function getJobTasks(): array
    {
        return $this->tasks;
    }

    /**
     * @return TaskResponseDto
     */
    protected function execute(): TaskResponseDto
    {
        try {
            $response = $this->getResponse($this->currentTask->execute());
        } catch (\Exception $e) {
            $this->currentTask->getLogger()->critical($e->getMessage());
            $response = $this->getResponse($this->currentTask->generateResponse(false));
        }

        return $response;
    }

    /**
     * @return void
     */
    protected function init()
    {
        $this->tasks[] = CleanupDownloadTmpFilesTask::class;
    }
}
