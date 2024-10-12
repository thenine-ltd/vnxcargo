<?php

namespace WPStaging\Pro\Backup\Service;

use Directory;
use RuntimeException;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Service\BackupSigner as BaseBackupSigner;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;

class BackupSigner extends BaseBackupSigner
{
    /** @var bool */
    protected $isMultipartBackup = false;

    /** @var array */
    protected $backupParts = [];

    /**
     * @param JobBackupDataDto $jobDataDto
     * @return void
     */
    public function setup(JobBackupDataDto $jobDataDto)
    {
        parent::setup($jobDataDto);
        $this->isMultipartBackup = $jobDataDto->getIsMultipartBackup();
    }

    /**
     * @param string $backupFilePath
     * @return void
     */
    public function signBackup(string $backupFilePath)
    {
        if ($this->isMultipartBackup) {
            $this->signMultipartBackup();
            return;
        }

        $this->signBackupFile($backupFilePath);
    }

    /**
     * @param string $backupFilePath
     * @return void
     */
    public function validateSignedBackup(string $backupFilePath)
    {
        if ($this->isMultipartBackup) {
            $this->validateMultipartBackup();
            return;
        }

        $this->validateBackupFile($backupFilePath);
    }

    /**
     * @return void
     */
    protected function signMultipartBackup()
    {
        $backupsDirectory = '';
        if ($this->jobDataDto->isLocalBackup()) {
            $backupsDirectory = WPStaging::make(BackupsFinder::class)->getBackupsDirectory();
        } else {
            $backupsDirectory = WPStaging::make(Directory::class)->getCacheDirectory();
        }

        $backupsDirectory  = Hooks::applyFilters('wpstg.tests.backupsDirectory', $backupsDirectory);
        $backupSize        = 0;
        $this->backupParts = [];

        foreach ($this->jobDataDto->getMultipartFilesInfo() as $multipartFileInfo) {
            $backupPart = $backupsDirectory . $multipartFileInfo['destination'];
            if (!file_exists($backupPart)) {
                throw new RuntimeException('Backup part not found: ' . $backupPart);
            }

            $partSize    = filesize($backupPart);
            $partSize    = $partSize - 2 + strlen($partSize);
            $backupSize += $partSize;

            $this->backupParts[] = [
                'path' => $backupPart,
                'size' => $partSize
            ];
        }

        $incrementSizePerPart = strlen($backupSize) - 2;
        $backupSize           = $backupSize + (count($this->backupParts) * $incrementSizePerPart);

        foreach ($this->backupParts as $part) {
            $this->signBackupFile($part['path'], $backupSize, $part['size'] + $incrementSizePerPart);
        }
    }

    /**
     * @return void
     * @throws \WPStaging\Backup\Exceptions\DiskNotWritableException
     * @throws RuntimeException
     */
    protected function validateMultipartBackup()
    {
        $backupSize = 0;
        clearstatcache();
        foreach ($this->backupParts as $index => $part) {
            $partSize                          = filesize($part['path']);
            $backupSize                        += $partSize;
            $this->backupParts[$index]['size'] = $partSize;
        }

        foreach ($this->backupParts as $part) {
            $this->validateBackupFile($part['path'], $backupSize, $part['size']);
        }
    }

    /**
     * @param BackupMetadata $backupMetadata
     * @param integer $partSize
     * @return void
     */
    protected function signMultipartMetadata(BackupMetadata $backupMetadata, int $partSize)
    {
        if (!$this->isMultipartBackup) {
            return;
        }

        $multipartMetadata = $backupMetadata->getMultipartMetadata();
        $multipartMetadata->setPartSize($partSize);
        $backupMetadata->setMultipartMetadata($multipartMetadata);
    }

    /**
     * @param BackupMetadata $backupMetadata
     * @param integer $partSize
     * @return void
     */
    protected function validateMultipartMetadata(BackupMetadata $backupMetadata, int $partSize)
    {
        if (!$this->isMultipartBackup) {
            return;
        }

        $multipartMetadata = $backupMetadata->getMultipartMetadata();

        if ($multipartMetadata->getPartSize() !== $partSize) {
            throw new RuntimeException('Unexpected multipart size in metadata: Multipart Size: ' . $multipartMetadata->getPartSize() . ' Expected Size: ' . $partSize);
        }
    }
}
