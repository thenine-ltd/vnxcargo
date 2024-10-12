<?php

namespace WPStaging\Pro\Backup\Job\Jobs;

use Exception;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Backup\Job\AbstractJob;
use WPStaging\Pro\Backup\Dto\Job\JobRemoteUploadDataDto;
use WPStaging\Pro\Backup\Task\Tasks\JobRemoteUpload\FinishUploadTask;
use WPStaging\Pro\Backup\Task\Tasks\JobRemoteUpload\PrepareUploadTask;
use WPStaging\Pro\Backup\Task\Tasks\JobRemoteUpload\UploadRequirementsCheckTask;

class JobRemoteUpload extends AbstractJob
{
    use RemoteUploadTasksTrait;

    /** @var JobRemoteUploadDataDto $jobDataDto */
    protected $jobDataDto;

    /** @var array The array of tasks to execute for this job. Populated at init(). */
    protected $tasks = [];

    /**
     * @return string
     */
    public static function getJobName(): string
    {
        return 'remote_upload_job';
    }

    /**
     * @return array
     */
    protected function getJobTasks(): array
    {
        return $this->tasks;
    }

    /**
     * @return TaskResponseDto
     */
    protected function execute()
    {
        try {
            $response = $this->getResponse($this->currentTask->execute());
        } catch (Exception $e) {
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
        $this->tasks[] = UploadRequirementsCheckTask::class;
        $this->tasks[] = PrepareUploadTask::class;

        $this->addStoragesTasks();

        $this->tasks[] = FinishUploadTask::class;
    }
}
