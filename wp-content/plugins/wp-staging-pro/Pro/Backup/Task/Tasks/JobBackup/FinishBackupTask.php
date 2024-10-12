<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobBackup;

use WPStaging\Core\WPStaging;
use WPStaging\Backup\BackupRetentionHandler;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask as BasicFinishBackupTask;

class FinishBackupTask extends BasicFinishBackupTask
{
    /** @var BackupRetentionHandler */
    protected $backupRetention;

    /**
     * Retains backups that if at least one remote storage is set.
     *
     * @return void
     */
    protected function saveBackupsInDB()
    {
        $storages = $this->jobDataDto->getStorages();

        // Don't hold backup with only localStorage.
        if (count($storages) === 1 && reset($storages) === 'localStorage') {
            return;
        }

        /** @var BackupRetentionHandler $backupRetention */
        $backupRetention = WPStaging::make(BackupRetentionHandler::class);

        $oldBackups = $backupRetention->getBackupsRetention();
        $oldBackups[$this->jobDataDto->getId()] = [
            'createdDate' => current_time('Ymd-His'),
            'storages'    => $storages,
            'backupSize'  => $this->jobDataDto->getTotalBackupSize(),
            'isMultipart' => $this->jobDataDto->getIsMultipartBackup(),
        ];

        $backupRetention->updateBackupsRetentionOptions($oldBackups);
    }
}
