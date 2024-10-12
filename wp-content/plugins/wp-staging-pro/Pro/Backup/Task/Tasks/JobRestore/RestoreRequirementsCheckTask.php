<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobRestore;

use RuntimeException;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreRequirementsCheckTask as BasicRestoreRequirementsCheckTask;

class RestoreRequirementsCheckTask extends BasicRestoreRequirementsCheckTask
{
    /**
     * @return void
     */
    protected function cannotRestoreOnMultisite()
    {
        // Early bail if not multisite
        if (!is_multisite()) {
            return;
        }

        /** @var BackupMetadata */
        $metadata = $this->jobDataDto->getBackupMetadata();

        if ($metadata->getBackupType() === BackupMetadata::BACKUP_TYPE_MULTISITE && !current_user_can('setup_network')) {
            throw new RuntimeException(esc_html__('Only a network administrator can restore an entire multisite network backup. Please ask your network administrator to restore this backup.', 'wp-staging'));
        }
    }

    /**
     * @return void
     */
    protected function cannotMigrate()
    {
        // no-op
    }

    /**
     * @return void
     */
    protected function cannotRestoreMultipartBackup()
    {
        // no-op
    }

    /**
     * @return void
     */
    protected function cannotRestoreIfBackupGeneratedOnProVersion()
    {
        // no-op
    }
}
