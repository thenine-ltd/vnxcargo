<?php

namespace WPStaging\Pro\Backup\Job\Jobs;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Job\Jobs\JobRestore as BasicJobRestore;
use WPStaging\Backup\Task\Tasks\CleanupTmpTablesTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RenameDatabaseTask;
use WPStaging\Backup\Task\Tasks\JobRestore\UpdateBackupsScheduleTask;
use WPStaging\Framework\SiteInfo;
use WPStaging\Pro\Backup\Task\Tasks\JobRestore\ExtractFilesTask;
use WPStaging\Pro\Backup\Task\Tasks\JobRestore\NetworkSite\AddMissingNetworkAdminsCapabilitiesTask;
use WPStaging\Pro\Backup\Task\Tasks\JobRestore\NetworkSite\ImportSubsiteUsersTask;
use WPStaging\Pro\Backup\Task\Tasks\JobRestore\RestoreDatabaseTask;
use WPStaging\Pro\Backup\Task\Tasks\JobRestore\RestoreRequirementsCheckTask;
use WPStaging\Pro\Backup\Task\Tasks\JobRestore\UpdateSubsitesDomainAndPathTask;
use WPStaging\Pro\Backup\Task\Tasks\JobRestore\UpdateSubsitesUrlsTask;
use WPStaging\Pro\Backup\Task\Tasks\JobRestore\WordPressCom\PreserveWordPressComDataTask;

class JobRestore extends BasicJobRestore
{
    /**
     * @override
     * @return void
     */
    protected function setRequirementTask()
    {
        $this->tasks[] = RestoreRequirementsCheckTask::class;
    }

    /**
     * @override
     * @return void
     */
    protected function addDatabaseTasks()
    {
        $this->addRestoreDatabaseTask();
        $this->addMultisiteTasks();
        $this->addNetworkSiteTasks();
        $this->addWordPressComTasks();
        $this->addImportSubsiteUsersTasks();

        $this->tasks[] = UpdateBackupsScheduleTask::class;
        $this->tasks[] = RenameDatabaseTask::class;
        $this->tasks[] = CleanupTmpTablesTask::class;
    }

    /**
     * @return void
     */
    protected function addRestoreDatabaseTask()
    {
        $metadata = $this->backupMetadata;
        if (!$metadata->getIsMultipartBackup()) {
            $this->tasks[] = RestoreDatabaseTask::class;

            return;
        }

        foreach ($metadata->getMultipartMetadata()->getDatabaseParts() as $databasePart) {
            $this->tasks[] = RestoreDatabaseTask::class;
        }
    }

    /**
     * @return void
     */
    protected function addMultisiteTasks()
    {
        if (!is_multisite()) {
            return;
        }

        $backupType = $this->backupMetadata->getBackupType();
        if ($backupType !== BackupMetadata::BACKUP_TYPE_MULTISITE) {
            return;
        }

        $this->tasks[] = UpdateSubsitesDomainAndPathTask::class;
        $this->tasks[] = UpdateSubsitesUrlsTask::class;
    }

    /**
     * @return void
     */
    protected function addWordPressComTasks()
    {
        if (!$this->backupMetadata->getHostingType() === SiteInfo::HOSTED_ON_WP) {
            return;
        }

        $this->tasks[] = PreserveWordPressComDataTask::class;
    }

    /**
     * @return void
     */
    protected function addNetworkSiteTasks()
    {
        $backupType = $this->backupMetadata->getBackupType();
        if (
            $backupType !== BackupMetadata::BACKUP_TYPE_NETWORK_SUBSITE &&
            $backupType !== BackupMetadata::BACKUP_TYPE_MAIN_SITE
        ) {
            return;
        }

        $this->tasks[] = AddMissingNetworkAdminsCapabilitiesTask::class;
    }

    /**
     * @return void
     */
    protected function addImportSubsiteUsersTasks()
    {
        if (!is_multisite()) {
            return;
        }

        $backupType = $this->backupMetadata->getBackupType();
        if ($backupType === BackupMetadata::BACKUP_TYPE_MULTISITE) {
            return;
        }

        $this->tasks[] = ImportSubsiteUsersTask::class;
    }

    /**
     * @return void
     */
    protected function addExtractFilesTasks()
    {
        $metadata = $this->jobDataDto->getBackupMetadata();
        if (!$metadata->getIsMultipartBackup()) {
            $this->tasks[] = ExtractFilesTask::class;
            return;
        }

        foreach ($metadata->getMultipartMetadata()->getFileParts() as $ignored) {
            $this->tasks[] = ExtractFilesTask::class;
        }
    }
}
