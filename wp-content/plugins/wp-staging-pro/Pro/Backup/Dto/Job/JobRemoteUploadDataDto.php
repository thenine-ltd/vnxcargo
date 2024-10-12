<?php

namespace WPStaging\Pro\Backup\Dto\Job;

use WPStaging\Backup\Dto\Interfaces\RemoteUploadDtoInterface;
use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Backup\Dto\Traits\RemoteUploadTrait;
use WPStaging\Backup\Entity\BackupMetadata;

class JobRemoteUploadDataDto extends JobDataDto implements RemoteUploadDtoInterface
{
    use RemoteUploadTrait;

    /** @var string */
    private $file;

    /** @var BackupMetadata|null */
    private $backupMetadata;

    /** @var bool */
    private $isDeleteLocalBackup = false;

    /**
     * @return string The .wpstg backup file being restored.
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @param string $file
     * @return void
     */
    public function setFile(string $file)
    {
        $this->file = untrailingslashit(wp_normalize_path($file));
    }

    /**
     * @return BackupMetadata|null
     */
    public function getBackupMetadata()
    {
        return $this->backupMetadata;
    }

    /**
     * @param BackupMetadata|mixed $backupMetadata
     * @return void
     */
    public function setBackupMetadata($backupMetadata)
    {
        if ($backupMetadata instanceof BackupMetadata) {
            $this->backupMetadata = $backupMetadata;

            return;
        }

        $this->backupMetadata = null;
    }

    /**
     * @return bool
     */
    public function getIsDeleteLocalBackup(): bool
    {
        return (bool)$this->isDeleteLocalBackup;
    }

    /**
     * @param bool $deleteLocalBackup
     * @return void
     */
    public function setIsDeleteLocalBackup(bool $deleteLocalBackup)
    {
        $this->isDeleteLocalBackup = $deleteLocalBackup === true || $deleteLocalBackup === 'true';
    }
}
