<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobBackup;

use RuntimeException;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupRequirementsCheckTask as BasicBackupRequirementsCheckTask;

class BackupRequirementsCheckTask extends BasicBackupRequirementsCheckTask
{
    /**
     * @return void
     */
    protected function cannotBackupMultisite()
    {
        if (!is_multisite()) {
            return;
        }

        if ($this->jobDataDto->getBackupType() !== BackupMetadata::BACKUP_TYPE_MULTISITE) {
            return;
        }

        // Early bail if super admin or wp cli request
        if (is_super_admin() || $this->jobDataDto->getIsWpCliRequest()) {
            return;
        }

        throw new RuntimeException(esc_html__('Only super admins/network admins can create entire multisite networks.', 'wp-staging'));
    }
}
