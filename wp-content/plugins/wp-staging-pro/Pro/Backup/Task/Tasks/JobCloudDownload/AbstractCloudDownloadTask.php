<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload;

use Exception;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Task\AbstractTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Pro\Backup\Dto\Job\JobCloudDownloadDataDto;
use WPStaging\Pro\Backup\Storage\RemoteUploaderInterface;

use function WPStaging\functions\debug_log;

abstract class AbstractCloudDownloadTask extends AbstractTask
{
    /** @var int */
    const MAX_RETRY = 3;

    /** @var int Chunk size in MB */
    const CHUNK_SIZE = 5;

    /** @var int */
    protected $retried = 0;

    /** @var JobCloudDownloadDataDto */
    protected $jobDataDto;

    /** @var RemoteUploaderInterface */
    private $remoteUploader;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param RemoteUploaderInterface $downloader
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, RemoteUploaderInterface $downloader)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->remoteUploader = $downloader;
    }

    public function execute(): TaskResponseDto
    {
        if ($this->jobDataDto->getInitialDownload() === true) {
            $this->logger->info('Starting download of backup file: ' . $this->jobDataDto->getCloudFileName());
            $response = $this->generateResponse(true);
            $response->setJobStatus(false);
            $response->setIsRunning(false);
            $this->jobDataDto->setInitialDownload(false);
            return $response;
        } else {
            return $this->cloudDownload($this->jobDataDto->getFile(), $this->jobDataDto->getSize());
        }
    }

    protected function getProviderName(): string
    {
        return $this->remoteUploader->getProviderName();
    }

    /**
     * Download backup from cloud providers as chunks
     *
     * @param string $fileName
     * @param int $backupFileSize
     *
     * @return bool|array|object
     */
    protected function cloudDownload(string $fileName, int $backupFileSize)
    {
        $fileSizeFormatted = size_format($backupFileSize, 2);
        // Delay in requests in milliseconds
        $delay = apply_filters('wpstg.remoteStorages.delayBetweenRequests', 0);
        $delay = filter_var($delay, FILTER_VALIDATE_INT);
        // make sure delay cannot be less than 0
        $delay = max(0, $delay);
        // make sure delay cannot be more than 1 second
        $delay = min(1000, $delay);

        $this->stepsDto->setTotal(ceil($backupFileSize / MB_IN_BYTES));

        $chunkSize = Hooks::applyFilters('wpstg.chunkDownloadCloudFileToFolder.chunkSize', self::CHUNK_SIZE * MB_IN_BYTES);
        $this->remoteUploader->setupDownload($this->logger, $this->jobDataDto, $chunkSize);
        $chunkSizeDownloaded = 0;

        $timeStart  = microtime(true);
        $chunkStart = $this->jobDataDto->getChunkStart();
        while (!$this->isThreshold()) {
            $chunkSizeDownloaded = 0;
            try {
                $chunkSizeDownloaded = $this->remoteUploader->chunkDownloadCloudFileToFolder($fileName, $backupFileSize, $this->jobDataDto->getChunkStart());
                if ($chunkSizeDownloaded > 0) {
                    $this->stepsDto->setCurrent(($this->stepsDto->getCurrent() < $this->stepsDto->getTotal()) ? ($this->stepsDto->getCurrent() + 1) : $this->stepsDto->getTotal());
                    $this->jobDataDto->setChunkStart($chunkSizeDownloaded);
                }
            } catch (FinishedQueueException $exception) {
                $this->logger->info('Successfully downloaded ' . $fileSizeFormatted . ' of backup file: ' . $this->jobDataDto->getCloudFileName());

                $urls        = new Urls();
                $fileName    = $exception->getMessage();
                $downloadUrl = $urls->getBackupUrl() . $fileName;

                $this->stepsDto->finish();
                $response = $this->generateResponse(true);
                $response->setJobStatus(true);
                $response->addMessage(["md5BaseName" => md5($fileName),"dataName" => $urls->getHomeUrlWithoutScheme(),"downloadUrl" => $downloadUrl]);
                return $response;
            } catch (Exception $exception) {
                // Last chunk maybe. No-op
                debug_log('Download Error: ' . $exception->getMessage());
            }

            if ($chunkSizeDownloaded === 0 || $chunkSizeDownloaded === false) {
                $this->retried++;
            }

            if ($this->retried > self::MAX_RETRY) {
                $this->logger->critical($this->getProviderName() . ' - Download Cancelled: Unable to download backup');
                return $this->cancelDownload();
            }

            if ($delay > 0) {
                // convert milliseconds to microseconds for usleep function
                usleep($delay * 1000);
            }
        }

        $timeEnd                = microtime(true);
        $currentChunkDownloaded = $chunkSizeDownloaded - $chunkStart;
        $executionTime          = $timeEnd - $timeStart;
        $downloaded             = size_format($chunkSizeDownloaded, 2);
        $this->logger->info('Downloaded ' . $downloaded . '/' . $fileSizeFormatted . ' - Speed  ' . round($currentChunkDownloaded / (MB_IN_BYTES * $executionTime), 2) . 'MB/s - Backup file: ' . $this->jobDataDto->getCloudFileName());

        $this->stepsDto->setManualPercentage(100 * $chunkSizeDownloaded / $backupFileSize);
        if ($chunkSizeDownloaded < $backupFileSize) {
            $response = $this->generateResponse(true);
            $response->setJobStatus(false);
        } else {
            $this->stepsDto->finish();
            $response = $this->generateResponse(true);
            $response->setJobStatus(true);
        }

        return $response;
    }

    /**
     * Cancel download and delete tmp files
     *
     * @return TaskResponseDto
     */
    private function cancelDownload(): TaskResponseDto
    {
        $this->jobDataDto->setEndTime(time());
        $response = $this->generateResponse(false);
        $response->setJobStatus(false);
        $tmpImportDir = WPStaging::make(Directory::class)->getDownloadsDirectory();
        $tmpImportDir = untrailingslashit($tmpImportDir);
        // Early bail: Path to Clean does not exist
        if (file_exists($tmpImportDir)) {
            WPStaging::make(FileSystem::class)->delete($tmpImportDir);
        }

        return $response;
    }
}
