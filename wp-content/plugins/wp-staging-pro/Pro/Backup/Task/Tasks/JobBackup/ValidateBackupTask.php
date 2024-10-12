<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobBackup;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Task\Tasks\JobBackup\ValidateBackupTask as BasicValidateBackupTask;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\MissingFileException;

class ValidateBackupTask extends BasicValidateBackupTask
{
    /** @var int */
    private $filesExtractionIndex = 0;

    /**
     * @return void
     */
    protected function prepareCurrentBackupFileValidation()
    {
        if (!$this->metadata->getIsMultipartBackup()) {
            $this->currentBackupFile = $this->jobDataDto->getBackupFilePath();
            $this->stepsDto->setTotal($this->metadata->getTotalFiles());
            $this->backupExtractor->setup($this->currentTaskDto->toExtractorDto(), $this->currentBackupFile, '');
            return;
        }

        $this->prepareMultipartValidation();
    }

    /**
     * @return void
     */
    protected function setNextBackupToValidate()
    {
        if (!$this->metadata->getIsMultipartBackup()) {
            return;
        }

        $this->filesExtractionIndex++;
        $this->jobDataDto->setFilePartIndex($this->filesExtractionIndex);
        $this->currentTaskDto->currentIndexOffset  = $this->filesExtractionIndex;
        $this->currentTaskDto->totalFilesExtracted = 0;
    }

    /**
     * @return void
     */
    protected function prepareMultipartValidation()
    {
        $this->filesExtractionIndex = $this->jobDataDto->getFilePartIndex();

        $filesPart = $this->metadata->getMultipartMetadata()->getFileParts();
        $backupPart = $filesPart[$this->filesExtractionIndex];

        $backupsDirectory = WPStaging::make(BackupsFinder::class)->getBackupsDirectory();
        $partMetadata = new BackupMetadata();

        $this->currentBackupFile = $backupsDirectory . $backupPart;

        if (!file_exists($this->currentBackupFile)) {
            $this->logger->warning(sprintf(esc_html__('Backup part %s doesn\'t exist. Skipping from extraction', 'wp-staging'), basename($this->currentBackupFile)));
            throw new MissingFileException();
        }

        $partMetadata = $partMetadata->hydrateByFilePath($this->currentBackupFile);
        $this->stepsDto->setTotal($partMetadata->getMultipartMetadata()->getTotalFiles());
        $this->backupExtractor->setup($this->currentTaskDto->toExtractorDto(), $this->currentBackupFile, '');
    }
}
