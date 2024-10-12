<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobRestore;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreDatabaseTask as BasicRestoreDatabaseTask;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Pro\Backup\Dto\Task\Restore\RestoreDatabaseTaskDto;
use WPStaging\Pro\Backup\Service\Database\Importer\DatabaseSearchReplacer;

class RestoreDatabaseTask extends BasicRestoreDatabaseTask
{
    /** @var RestoreDatabaseTaskDto */
    protected $currentTaskDto;

    /** @return string */
    protected function getCurrentTaskType(): string
    {
        return RestoreDatabaseTaskDto::class;
    }

    /**
     * @return void
     */
    protected function setupSearchReplace()
    {
        // Early bail if SearchReplacerInterface is of wrong type
        if (!$this->databaseSearchReplacer instanceof DatabaseSearchReplacer) {
            return;
        }

        $backupMetadata = $this->jobDataDto->getBackupMetadata();

        $this->databaseSearchReplacer->setSourceAbsPath($backupMetadata->getAbsPath());
        $this->databaseSearchReplacer->setSourcePlugins($backupMetadata->getPlugins());
        $this->databaseSearchReplacer->setSourceUrls($backupMetadata->getSiteUrl(), $backupMetadata->getHomeUrl(), $backupMetadata->getUploadsUrl());
        $this->databaseSearchReplacer->setIsWpBakeryActive($backupMetadata->getWpBakeryActive());

        if (is_multisite() && $backupMetadata->getBackupType() === BackupMetadata::BACKUP_TYPE_MULTISITE) {
            $currentSubsiteId = $this->currentTaskDto->currentSubsiteId ?? 1;
            $this->databaseSearchReplacer->setupSubsitesSearchReplacer($backupMetadata, $currentSubsiteId);
        }

        $this->databaseImporter->setSearchReplace($this->databaseSearchReplacer->getSearchAndReplace(get_site_url(), get_home_url()));
    }

    /**
     * @return void
     */
    protected function setupMultipartDatabaseRestore()
    {
        // Early bail if SearchReplacerInterface is of wrong type
        if (!$this->databaseSearchReplacer instanceof DatabaseSearchReplacer) {
            return;
        }

        $metadata = $this->jobDataDto->getBackupMetadata();
        $databasePartIndex = $this->jobDataDto->getDatabasePartIndex();
        $databasePart = $metadata->getMultipartMetadata()->getDatabaseParts()[$databasePartIndex];
        $databaseFile = $this->pathIdentifier->getBackupDirectory() . $databasePart;

        if (!file_exists($databaseFile) || filesize($databaseFile) === 0 || filesize($databaseFile) === false) {
            $this->jobDataDto->setDatabasePartIndex($databasePartIndex + 1);
            $this->jobDataDto->setIsMissingDatabaseFile(true);
            $this->logger->warning(sprintf('Skip restoring database. Missing Part Index: %d.', $databasePartIndex));

            throw new MissingFileException();
        }

        $this->databaseImporter->setFile($databaseFile);
        $this->databaseImporter->seekLine($this->stepsDto->getCurrent());

        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal($this->databaseImporter->getTotalLines());
            if ($databasePartIndex !== 0) {
                $this->logger->info(sprintf('Restoring Database File Part Index: %d', $databasePartIndex));
            }
        }

        $this->databaseSearchReplacer->setSourceAbsPath($metadata->getAbsPath());
        $this->databaseSearchReplacer->setSourcePlugins($metadata->getPlugins());
        $this->databaseSearchReplacer->setSourceUrls($metadata->getSiteUrl(), $metadata->getHomeUrl(), $metadata->getUploadsUrl());
        $this->databaseSearchReplacer->setIsWpBakeryActive($metadata->getWpBakeryActive());

        if (is_multisite() && $metadata->getBackupType() === BackupMetadata::BACKUP_TYPE_MULTISITE) {
            $currentTaskData  = $this->jobDataDto->getCurrentTaskData();
            $currentSubsiteId = isset($currentTaskData['currentSubsiteId']) ? $currentTaskData['currentSubsiteId'] : 1;
            $this->databaseSearchReplacer->setupSubsitesSearchReplacer($metadata, $currentSubsiteId);
        }

        $this->databaseImporter->setSearchReplace($this->databaseSearchReplacer->getSearchAndReplace(get_site_url(), get_home_url()));
    }
}
