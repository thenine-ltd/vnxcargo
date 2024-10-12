<?php

namespace WPStaging\Pro\Backup\Storage\Storages\SFTP\Clients;

use Exception;
use RuntimeException;
use WPStaging\Backup\Exceptions\StorageException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Pro\Backup\Storage\Storages\SFTP\Auth;

use function WPStaging\functions\debug_log;

class FtpClient implements ClientInterface
{
    use ResourceTrait;

    /** @var resource|false|null */
    protected $ftp;

    /** @var string */
    protected $host;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var int */
    protected $port;

    /** @var bool */
    protected $passive;

    /** @var bool */
    protected $ssl;

    /** @var string|false */
    protected $error;

    /** @var bool */
    protected $isLogin;

    /** @var string */
    protected $path;

    /** @var string */
    protected $tmpDirectory;

    /** @var int */
    protected $mode;

    /**
     * @var string $host
     * @var string $username
     * @var string $password
     * @var bool   $ssl
     * @var bool   $passive
     * @var int    $port
     *
     * @throws FtpException
     */
    public function __construct(string $host, string $username, string $password, bool $ssl, bool $passive, int $port)
    {
        if (!extension_loaded('ftp')) {
            throw new FtpException("PHP FTP extension not loaded");
        }

        $this->host     = $host;
        $this->port     = $port;
        $this->ssl      = $ssl;
        $this->username = $username;
        $this->password = $password;
        $this->passive  = $passive;
        $this->mode     = Auth::FTP_UPLOAD_MODE_PUT;

        /** @var Directory */
        $directory          = WPStaging::make(Directory::class);
        $this->tmpDirectory = $directory->getTmpDirectory();
    }

    /**
     * @param string $path
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * What command to use internally for appending? REST(using ftp_fput) or APPEND(using ftp_append)?
     * @param int $mode
     * @return void
     *
     * @throws RuntimeException
     */
    public function setMode(int $mode)
    {
        $supportedModes = [
            Auth::FTP_UPLOAD_MODE_PUT,
            Auth::FTP_UPLOAD_MODE_APPEND,
            Auth::FTP_UPLOAD_MODE_NON_BLOCKING,
        ];

        if (!in_array($mode, $supportedModes)) {
            throw new RuntimeException(sprintf('Given upload mode (%s) not supported using ftp extension.', $mode));
        }

        $this->mode = $mode;
    }

    /**
     * @param int $retry
     * @return bool
     */
    public function login(int $retry = 3): bool
    {
        if ($this->isLogin) {
            return true;
        }

        if (is_resource($this->ftp) && @ftp_systype($this->ftp) !== false) {
            return true;
        }

        try {
            if ($this->ssl) {
                $this->ftp = @ftp_ssl_connect($this->host, $this->port, 30);
            } else {
                $this->ftp = @ftp_connect($this->host, $this->port, 30);
            }
        } catch (Exception $ex) {
            debug_log(sprintf('Extension - Hostname: %s, Error: %s', $this->host, $ex->getMessage()));
            $this->ftp = false;
        }

        if ($this->ftp === false && $retry > 0) {
            return $this->login($retry - 1);
        }

        if ($this->ftp === false) {
            $this->isLogin = false;
            return false;
        }

        $result = @ftp_login($this->ftp, $this->username, $this->password);

        if ($result === false) {
            $this->isLogin = false;
            return false;
        }

        $this->isLogin = true;
        ftp_pasv($this->ftp, $this->passive);
        ftp_set_option($this->ftp, FTP_AUTOSEEK, false);

        return true;
    }

    /**
     * @param string $remotePath
     * @param string $file
     * @param string $chunk
     * @param int $offset
     * @return bool
     */
    public function upload(string $remotePath, string $file, string $chunk, int $offset = 0): bool
    {
        if (!$this->login()) {
            return false;
        }

        if ($remotePath !== '') {
            $remotePath = trailingslashit($remotePath);
        }

        $remoteFile = $remotePath . $file;

        if ($this->mode === Auth::FTP_UPLOAD_MODE_PUT || $offset === 0) {
            return $this->uploadUsingPut($remoteFile, $chunk, $offset);
        }

        return $this->uploadUsingAppend($remoteFile, $file, $chunk, $offset);
    }

    /**
     * @param string $remoteFile
     * @param string $localFile
     * @param int $offset
     * @return int
     */
    public function nonBlockingUpload(string $remoteFile, string $localFile, int $offset = 0): int
    {
        if (!$this->login()) {
            throw new StorageException('FTP login failed');
        }

        $localFileSize  = filesize($localFile);
        $remoteFileSize = ftp_size($this->ftp, $remoteFile);
        $resume         = true;

        if ($remoteFileSize <= 0) {
            $remoteFileSize = 0;
            $resume         = false;
        }

        if ($remoteFileSize >= $localFileSize) {
            throw new FinishedQueueException('Remote file size is greater than or equal to local file size');
        }

        $handle = fopen($localFile, 'rb');
        if (!$handle) {
            throw new StorageException('Failed to open local file');
        }

        if ($resume) {
            fseek($handle, $remoteFileSize);
        }

        $response = ftp_nb_fput($this->ftp, $remoteFile, $handle, FTP_BINARY, $remoteFileSize);

        while ($response === FTP_MOREDATA && !$this->isThreshold()) {
            $response = ftp_nb_continue($this->ftp);
        }

        $newUploadSize = ftp_size($this->ftp, $remoteFile);
        if ($newUploadSize <= 0) {
            $newUploadSize = ftell($handle);
        }

        fclose($handle);

        if ($response === FTP_FINISHED) {
            throw new FinishedQueueException('FTP upload finished');
        }

        return $newUploadSize - $remoteFileSize;
    }

    /**
     * @return void
     */
    public function close()
    {
        if ($this->ftp === null || $this->ftp === false) {
            $this->isLogin = false;
            $this->ftp     = null;
            return;
        }

        $this->isLogin = false;
        @ftp_close($this->ftp);
        $this->ftp = null;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        if (empty($this->error)) {
            return '';
        }

        return $this->error;
    }

    /**
     * @param string $path
     * @return array
     * @throws StorageException
     */
    public function getFiles(string $path): array
    {
        $this->login();

        if ($this->ftp === false) {
            return [];
        }

        // Let check if path is already set, then no need to change directory
        // @todo Improve this condition in a separate PR if required
        $currentPath = ftp_pwd($this->ftp);

        if ($path !== '' && $path !== $currentPath) {
            ftp_chdir($this->ftp, $path);
        }

        $items = [];
        try {
            $items = ftp_rawlist($this->ftp, '-tr');
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            debug_log($this->error);
            $this->close();
            throw new StorageException($this->error);
        }

        // If somehow ftp_rawlist fails, then try ftp_mlsd, mlsd is only available in PHP 7.2+
        if ($items === false && function_exists('ftp_mlsd')) {
            return $this->getFilesUsingMlsd();
        }

        $files = [];
        if (!is_array($items)) {
            $this->close();
            throw new StorageException('Wrong Output');
        }

        foreach ($items as $item) {
            if (empty($item)) {
                continue;
            }

            $metas = preg_split('/\s+/', trim($item));

            if ($metas[1] === '3' || $metas[1] === 'd') {
                continue;
            }

            $files[] = [
                'time' => null,
                'name' => $metas[count($metas) - 1],
                'size' => isset($metas[4]) ? (int)$metas[4] : null,
            ];
        }

        $this->close();
        return $files;
    }

    /**
     * @param string $path
     *
     * @return void|bool
     */
    public function deleteFile(string $path): bool
    {
        $this->login();
        if ($this->ftp === false) {
            return false;
        }

        $filepath = empty($this->path) ? $path : sprintf('%s/%s', $this->path, $path);

        try {
            $result = ftp_delete($this->ftp, $filepath);
            $this->close();
            return $result;
        } catch (Exception $ex) {
            debug_log($ex->getMessage());
            return false;
        }
    }

    /**
     * @param string $backupPath
     * @param string $filePath
     * @param string $fileName
     * @param int $chunkStart
     * @param int $chunkSize
     * @return void|bool
     */
    public function downloadAsChunks($backupPath, $filePath, $fileName, $chunkStart, $chunkSize)
    {
        try {
            $this->login();
            if ($this->ftp === false) {
                return false;
            }

            ftp_set_option($this->ftp, FTP_BINARY, true);
            if ($this->ftp === false) {
                return false;
            }

            $fileHandle = fopen($filePath, 'a+');
            $remotePath = $backupPath . $fileName;
            $response = ftp_nb_fget($this->ftp, $fileHandle, $remotePath, FTP_BINARY, $chunkStart);
            while ($response === FTP_MOREDATA) {
                $response = ftp_nb_continue($this->ftp);
            }

            fclose($fileHandle);
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            return false;
        }
    }

    /**
     * @return bool
     */
    public function getIsSupportNonBlockingUpload(): bool
    {
        return true;
    }

    /**
     * @param string $remoteFile
     * @param string $chunk
     * @param int $offset
     * @return bool
     */
    protected function uploadUsingPut(string $remoteFile, string $chunk, int $offset): bool
    {
        $handle = fopen('php://temp', 'wb+');
        if (!$handle) {
            return false;
        }

        if ($fileSize = fwrite($handle, $chunk)) {
            rewind($handle);
        }

        $result = false;
        try {
            $result = @ftp_fput($this->ftp, $remoteFile, $handle, FTP_BINARY, $offset);
        } catch (Exception $e) {
            debug_log(sprintf("Ftp Extension: Offset - %s, Error:  %s", $offset, $e->getMessage()));
        }

        fclose($handle);

        return $result;
    }

    /**
     * @param string $remoteFile
     * @param string $fileName
     * @param string $chunk
     * @param int $offset
     * @return bool
     */
    protected function uploadUsingAppend(string $remoteFile, string $fileName, string $chunk, int $offset): bool
    {
        // Early bail when ftp_append is not available
        if (!function_exists('ftp_append')) {
            return false;
        }

        $localFile = $this->tmpDirectory . $fileName;
        $handle    = fopen($localFile, 'wb+');
        if (!$handle) {
            return false;
        }

        if ($fileSize = fwrite($handle, $chunk)) {
            rewind($handle);
        }

        fclose($handle);

        try {
            return @ftp_append($this->ftp, $remoteFile, $localFile, FTP_BINARY); // phpcs:ignore
        } catch (Exception $e) {
            debug_log(sprintf("Ftp Extension: Offset - %s, Error:  %s", $offset, $e->getMessage()));
        }

        return false;
    }

    /**
     * @return array
     */
    protected function getFilesUsingMlsd(): array
    {
        $items = [];
        try {
            $items = ftp_mlsd($this->ftp, '.'); // phpcs:ignore
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            debug_log($this->error);
            throw new StorageException($this->error);
        }

        if (!is_array($items)) {
            $this->close();
            throw new StorageException('MLSD: Wrong Output');
        }

        $files = [];
        foreach ($items as $item) {
            if (empty($item['type'])) {
                continue;
            }

            if ($item['type'] !== 'file') {
                continue;
            }

            $files[] = [
                'time' => isset($item['modify']) ? strtotime($item['modify']) : null,
                'name' => $item['name'],
                'size' => isset($item['size']) ? (int)$item['size'] : null,
            ];
        }

        $this->close();
        return $files;
    }
}
