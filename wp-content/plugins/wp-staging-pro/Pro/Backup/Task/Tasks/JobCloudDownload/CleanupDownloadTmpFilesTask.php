<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobCloudDownload;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Task\AbstractTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Filesystem\Filesystem;

class CleanupDownloadTmpFilesTask extends AbstractTask
{
    /** @var Filesystem */
    private $filesystem;

    /** @var Directory */
    private $directory;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, Filesystem $filesystem, Directory $directory, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->filesystem = $filesystem;
        $this->directory  = $directory;
    }

    public static function getTaskName(): string
    {
        return 'backup_restore_cleanup_files';
    }

    public static function getTaskTitle(): string
    {
        return 'Cleaning Up Restore Files';
    }

    /**
     * @return \WPStaging\Backup\Dto\TaskResponseDto
     */
    public function execute()
    {

        $tmpImportDir = $this->directory->getDownloadsDirectory();

        $tmpImportDir = untrailingslashit($tmpImportDir);

        $fullPathForLogging = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $this->filesystem->normalizePath($tmpImportDir, true));

        // Early bail: Path to Clean does not exist
        if (!file_exists($tmpImportDir)) {
            return $this->generateResponse();
        }

        try {
            $deleted = $this->filesystem
                ->setRecursive(true)
                ->setShouldStop(function () {
                    return $this->isThreshold();
                })
                ->delete($tmpImportDir);
        } catch (\Exception $e) {
            $this->logger->warning(sprintf(
                __('%s: Could not cleanup path "%s". May be a permission issue?', 'wp-staging'),
                static::getTaskTitle(),
                $fullPathForLogging
            ));

            return $this->generateResponse();
        }

        if ($deleted) {
            // Successfully deleted
            $this->logger->info(sprintf(
                __('%s: Path "%s" successfully cleaned up.', 'wp-staging'),
                static::getTaskTitle(),
                $fullPathForLogging
            ));
            $response = $this->generateResponse();
            $response->setJobStatus(true);
            return $response;
        } else {
            /*
             * Not successfully deleted.
             * This can happen if the folder to delete is too large
             * to be deleted in a single request. We continue
             * deleting it in the next request...
             */
            $response = $this->generateResponse(false);
            $response->setJobStatus(false);

            $this->logger->info(sprintf(
                __('%s: Re-enqueing path %s for deletion, as it couldn\'t be deleted in a single request without
                    hitting execution limits. If you see this message in a loop, PHP might not be able to delete
                    this directory, so you might want to try to delete it manually.', 'wp-staging'),
                static::getTaskTitle(),
                $fullPathForLogging
            ));

            // Early bail: Response modified for repeating
            return $response;
        }
    }
}
