<?php

namespace WPStaging\Pro\Backup\Storage;

use WPStaging\Backup\Dto\Interfaces\RemoteUploadDtoInterface;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Pro\Backup\Dto\Job\JobCloudDownloadDataDto;

/**
 * @todo Either let refactor this to RemoteStorageInterface and also refactor Uploader term in classes that implement this interface
 * @todo Or Create a separate interface for Downloader and move download related code from Uploader classes to new code and let this interface and uploader classes only handle upload code.
 */
interface RemoteUploaderInterface
{
    /** @return string */
    public function getProviderName();

    public function setupUpload(LoggerInterface $logger, RemoteUploadDtoInterface $jobDataDto, $chunkSize = 1024 * 1024);

    /**
     * @var string $backupFilePath
     * @var string $fileName
     * @return bool
     */
    public function setBackupFilePath($backupFilePath, $fileName);

    /** @return int */
    public function chunkUpload();

    /**
     * @param array $uploadsToVerify
     * @return bool
     */
    public function verifyUploads(array $uploadsToVerify): bool;

    /**
     * @param int $backupSize
     * @throws DiskNotWritableException
     */
    public function checkDiskSize($backupSize);

    /**
     * Mainly added to improve unit testing of remote storage
     * Though can be later added to remote storages settings page so user can test upload himself
     * @param string $filePath
     * @param string $remoteFileName
     * @return bool
     */
    public function uploadFile($filePath, $remoteFileName = '');

    public function stopUpload();

    /** @return string */
    public function getError();

    /** @return bool */
    public function deleteOldestBackups();

    /** @return array */
    public function getBackups();

    /**
     * @param string $fileId
     * @return bool|void
     */
    public function deleteFile($fileId);

    /**
     * @param  LoggerInterface $logger
     * @param  JobCloudDownloadDataDto $jobDataDto
     * @param  int $chunkSize
     * @return void
     */
    public function setupDownload(LoggerInterface $logger, JobCloudDownloadDataDto $jobDataDto, int $chunkSize = MB_IN_BYTES);

    /**
     * Save backup file from remote storage to server
     * @param string $fileId Usually this is same with the file name.
     * @param int $fileSize
     * @param int $chunkStart
     * @return int|bool
     */
    public function chunkDownloadCloudFileToFolder(string $fileId, int $fileSize, int $chunkStart);
}
