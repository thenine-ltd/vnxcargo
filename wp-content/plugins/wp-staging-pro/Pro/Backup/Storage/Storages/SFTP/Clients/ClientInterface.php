<?php

namespace WPStaging\Pro\Backup\Storage\Storages\SFTP\Clients;

use WPStaging\Backup\Exceptions\StorageException;

interface ClientInterface
{
    /** @return bool */
    public function login();

    /**
     * @param string $remotePath
     * @param string $file
     * @param string $chunk
     * @param int $offset
     *
     * @return bool
     */
    public function upload(string $remotePath, string $file, string $chunk, int $offset = 0): bool;

    /**
     * @return void
     */
    public function close();

    /** @return string */
    public function getError(): string;

    /**
     * @param string $path
     * @return array
     * @throws StorageException
     */
    public function getFiles(string $path): array;

    /**
     * @param string $backupPath
     * @param string $filePath
     * @param string $fileName
     * @param int $chunkStart
     * @param int $chunkSize
     * @return bool
     */
    public function downloadAsChunks($backupPath, $filePath, $fileName, $chunkStart, $chunkSize);

    /**
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path);

    /**
     * @param string $path
     * @return void
     */
    public function setPath(string $path);

    /**
     * @param int $mode
     * @return void
     */
    public function setMode(int $mode);

    /**
     * @return bool
     */
    public function getIsSupportNonBlockingUpload(): bool;

    /**
     * @param string $remoteFile
     * @param string $localFile
     * @param int $offset
     *
     * @return int
     */
    public function nonBlockingUpload(string $remoteFile, string $localFile, int $offset = 0): int;
}
