<?php

namespace WPStaging\Pro\Backup\Storage\Storages\SFTP\Clients;

use Exception;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\ServerVars;
use WPStaging\Backup\Exceptions\StorageException;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Vendor\phpseclib3\Crypt\PublicKeyLoader;
use WPStaging\Vendor\phpseclib3\Net\SFTP;
use WPStaging\Vendor\phpseclib3\Net\SSH2;
use WPStaging\Vendor\phpseclib3\Math\BigInteger;

use function WPStaging\functions\debug_log;

class SftpClient implements ClientInterface
{
    use WithBackupIdentifier;

    /** @var SFTP */
    protected $sftp;

    /** @var string */
    protected $host;

    /** @var int */
    protected $port;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var string */
    protected $key;

    /** @var string */
    protected $passphrase;

    /** @var string|false */
    protected $error;

    /** @var bool */
    private $isBadKey;

    /** @var bool */
    private $isLogin;

    /** @var string */
    protected $path;

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $key
     * @param string $passphrase
     * @param int $port
     */
    public function __construct(string $host, string $username, string $password, string $key, string $passphrase, int $port)
    {
        $this->username = $username;
        $this->password = $password;
        $this->key = $key;
        $this->passphrase = $passphrase;

        $this->host = $host;
        $this->port = $port;
        $this->isLogin = false;

        if (defined('NET_SFTP_LOGGING')) {
            define('NET_SFTP_LOGGING', SSH2::LOG_COMPLEX);
        }

        if (defined('NET_SSH2_LOGGING')) {
            define('NET_SSH2_LOGGING', SSH2::LOG_COMPLEX);
        }
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
     * @param int $mode
     * @return void
     */
    public function setMode(int $mode)
    {
        // No-op
    }

    /**
     * @return bool
     */
    public function connect(): bool
    {
        $this->setBigIntegerEngine();
        $this->sftp = new SFTP($this->host, $this->port, 30);

        if (!empty($this->password)) {
            try {
                return $this->sftp->login($this->username, $this->password);
            } catch (Exception $e) {
                debug_log("Error: " . $e->getMessage());
                return false;
            }
        }

        $key = '';
        try {
            $key = PublicKeyLoader::load(trim($this->key), empty($this->passphrase) ? false : $this->passphrase);
        } catch (Exception $e) {
            $this->isBadKey = true;
            debug_log("Error: " . $e->getMessage());
        }

        try {
            return $this->sftp->login($this->username, $key);
        } catch (Exception $e) {
            debug_log("Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @return bool
     */
    public function login(): bool
    {
        if ($this->isLogin) {
            return true;
        }

        $result = $this->connect();

        if ($result === true) {
            $this->isLogin = true;
            return true;
        }

        $this->isLogin = false;
        if (!$this->sftp->isConnected()) {
            $this->error = "Unable to connect to SFTP server ";
            debug_log("Error: " . $this->error);
            return false;
        }

        if (!$this->sftp->isAuthenticated()) {
            $this->error = "Unable to login to SFTP server ";
            debug_log("Error: " . $this->error);
            if ($this->isBadKey) {
                $this->error .= ' - Either the passphrase or key provided is not correct. ';
                debug_log("Error: " . $this->error);
            }

            return false;
        }

        debug_log("Error: Unable to login via sFTP. Unknown error. ");
        return false;
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
        if (!$this->sftp->isConnected()) {
            $this->connect();
        }

        $handle = fopen('php://temp', 'wb+');
        if ((!$handle)) {
            return false;
        }

        if (fwrite($handle, $chunk)) {
            rewind($handle);
        }

        if (!$this->isDirectoryChanged($remotePath)) {
            $file = '/' . trailingslashit($remotePath) . $file;
        }

        $result = false;
        try {
            $result = $this->sftp->put($file, $handle, SFTP::SOURCE_LOCAL_FILE | SFTP::RESUME_START, $offset);
        } catch (Exception $e) {
            debug_log("Error: " . $e->getMessage());
        }

        fclose($handle);

        return $result;
    }

    /**
     * @param string $remoteFile
     * @param string $localFile
     * @param int $offset
     * @return int
     */
    public function nonBlockingUpload(string $remoteFile, string $localFile, int $offset = 0): int
    {
        return 0;
    }

    /**
     * @return void
     */
    public function close()
    {
        $this->isLogin = false;
        if ($this->sftp !== null) {
            $this->sftp->disconnect();
        }
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param string $path
     *
     * @return array
     * @throws StorageException
     */
    public function getFiles($path): array
    {
        if (!$this->sftp->isConnected()) {
            $this->connect();
        }

        if ($this->isDirectoryChanged($path)) {
            $path = '.';
        }

        try {
            $items = @$this->sftp->rawlist($path);
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            throw new StorageException($this->error);
        }

        if (empty($items)) {
            $this->error .= "Could not upload backup via SFTP to " . $path . " Does the folder exist on the remote server? ";
            $this->error .= "The backup is still available on the local file system.";
            throw new StorageException($this->error);
        }

        $files = [];
        foreach ($items as $file) {
            if ($file['type'] !== 1) {
                continue;
            }

            $files[] = [
                'name' => $file['filename'],
                'time' => $file['mtime'],
                'size' => $file['size'],
            ];
        }

        uasort($files, function ($file1, $file2) {
            return $file1['time'] < $file2['time'] ? -1 : 1;
        });

        return array_values($files);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function deleteFile($path): bool
    {
        try {
            return @$this->sftp->delete($path);
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function getIsSupportNonBlockingUpload(): bool
    {
        return false;
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isDirectoryChanged(string $path): bool
    {
        $path = rtrim($path);
        $path = untrailingslashit($path);
        $currentPath = $this->sftp->pwd();
        if (empty($path)) {
            return false;
        }

        if ('/' . $path === $currentPath) {
            return true;
        }

        return $this->sftp->chdir($path);
    }

    /**
     * @param string $backupPath
     * @param string $filePath
     * @param string $fileName
     * @param int $chunkStart
     * @param int $chunkSize
     * @return bool
     */
    public function downloadAsChunks($backupPath, $filePath, $fileName, $chunkStart, $chunkSize)
    {
        try {
            $output =  $this->sftp->get($backupPath . $fileName, false, $chunkStart, $chunkSize);
            $fileHandle = fopen($filePath, 'a+');
            fwrite($fileHandle, $output);
            fclose($fileHandle);
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * By default, phpseclib uses the following order for engines for BigInteger calculations.
     * 1. GMP
     * 2. PHP64
     * 3. BCMath
     * 4. PHP32
     *
     * But during our testing BCMath performs better than PHP64 and PHP32.
     */
    protected function setBigIntegerEngine()
    {
        // if gmp extension is installed then use default preference
        if (extension_loaded('gmp')) {
            return;
        }

        // if bcmath extension is not installed then use default preference
        if (!extension_loaded('bcmath')) {
            return;
        }

        try {
            BigInteger::setEngine('BCMath', ["OpenSSL"]);
        } catch (Exception $e) {
            BigInteger::setEngine('BCMath', ["DefaultEngine"]);
        }
    }
}
