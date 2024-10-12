<?php

namespace WPStaging\Pro\Backup\Job\Jobs\Cloud;

use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Pro\Backup\Dto\Job\JobCloudDownloadDataDto;
use WPStaging\Backup\Job\AbstractJob;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\AmazonS3DownloadTask;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\DigitalOceanSpacesDownloadTask;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\GenericS3DownloadTask;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\GoogleDriveDownloadTask;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\SFTPDownloadTask;
use WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload\WasabiDownloadTask;
use WPStaging\Core\WPStaging;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class JobCloudDownload extends AbstractJob
{
    /** @var string[] list of tasks to execute for this job. Populated at init(). */
    private $tasks = [];

    /** @var JobCloudDownloadDataDto $jobDataDto */
    protected $jobDataDto;

    public static function getJobName(): string
    {
        return 'cloud_backup_download';
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
        $this->startBenchmark();

        try {
            $response = $this->getResponse($this->currentTask->execute());
        } catch (\Exception $e) {
            $this->currentTask->getLogger()->critical($e->getMessage());
            $response = $this->getResponse($this->currentTask->generateResponse(false));
        }

        $this->finishBenchmark(get_class($this->currentTask));

        return $response;
    }

    /**
     * @return void
     */
    protected function init()
    {
        $taskMap = [
            'amazons3'            => AmazonS3DownloadTask::class,
            'digitalocean-spaces' => DigitalOceanSpacesDownloadTask::class,
            'generic-s3'          => GenericS3DownloadTask::class,
            'googledrive'         => GoogleDriveDownloadTask::class,
            'sftp'                => SFTPDownloadTask::class,
            'wasabi-s3'           => WasabiDownloadTask::class,
        ];

        $providerName = $this->jobDataDto->getStorageProviderName();

        if (array_key_exists($providerName, $taskMap)) {
            $this->tasks[] = $taskMap[$providerName];
            return;
        }

        // Should never happen.
        WPStaging::make(LoggerInterface::class)->warning('No task found for provider: ' . $providerName);
    }
}
