<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobRestore;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Task\Tasks\JobRestore\ExtractFilesTask as BasicExtractFilesTask;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\MissingFileException;

class ExtractFilesTask extends BasicExtractFilesTask
{
    /** @var int */
    private $filesExtractionIndex = 0;

    /** @var int */
    private $totalFilesPart = 0;

    /**
     * @return void
     */
    protected function setNextBackupToExtract()
    {
        if (!$this->metadata->getIsMultipartBackup()) {
            return;
        }

        $this->filesExtractionIndex++;
        $this->jobDataDto->setFilePartIndex($this->filesExtractionIndex);
        $this->currentTaskDto->currentIndexOffset  = 0;
        $this->currentTaskDto->totalFilesExtracted = 0;
        if ($this->filesExtractionIndex === $this->totalFilesPart && $this->jobDataDto->getBackupMetadata()->getIsExportingUploads()) {
            $this->logger->info(esc_html__('Restored Media Library', 'wp-staging'));
        }

        $this->setCurrentTaskDto($this->currentTaskDto);
    }

    /**
     * @return void
     */
    protected function setupExtractor()
    {
        if (!$this->metadata->getIsMultipartBackup()) {
            $this->stepsDto->setTotal($this->metadata->getTotalFiles());
            $this->extractorService->setup($this->currentTaskDto->toExtractorDto(), $this->jobDataDto->getFile(), $this->jobDataDto->getTmpDirectory());
            return;
        }

        $this->prepareMultipartExtraction();
    }

    /**
     * @return void
     */
    protected function prepareMultipartExtraction()
    {
        $this->filesExtractionIndex = $this->jobDataDto->getFilePartIndex();

        $filesPart  = $this->metadata->getMultipartMetadata()->getFileParts();
        $backupPart = $filesPart[$this->filesExtractionIndex];

        $this->totalFilesPart = count($filesPart);

        $backupsDirectory = WPStaging::make(BackupsFinder::class)->getBackupsDirectory();
        $partMetadata     = new BackupMetadata();
        $fileToExtract    = $backupsDirectory . $backupPart;

        if (!file_exists($fileToExtract)) {
            $this->logger->warning(sprintf(esc_html__('Backup part %s doesn\'t exist. Skipping from extraction', 'wp-staging'), basename($fileToExtract)));
            throw new MissingFileException();
        }

        $partMetadata = $partMetadata->hydrateByFilePath($fileToExtract);
        $this->stepsDto->setTotal($partMetadata->getMultipartMetadata()->getTotalFiles());
        $this->extractorService->setup($this->currentTaskDto->toExtractorDto(), $fileToExtract, $this->jobDataDto->getTmpDirectory());
    }
}
