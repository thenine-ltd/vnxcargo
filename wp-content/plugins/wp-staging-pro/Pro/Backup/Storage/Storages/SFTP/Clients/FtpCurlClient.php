<?php

namespace WPStaging\Pro\Backup\Storage\Storages\SFTP\Clients;

use Exception;
use WPStaging\Backup\Exceptions\StorageException;

use function WPStaging\functions\debug_log;

class FtpCurlClient implements ClientInterface
{
    /** @var resource|null */
    protected $handler = null;

    /** @var string */
    protected $hostname;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var bool */
    protected $passive;

    /** @var bool */
    protected $ssl;

    /** @var int */
    protected $port;

    /** @var string|false */
    protected $error;

    /** @var string */
    protected $path;

    /** @var int */
    protected $httpCode;

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
    public function __construct(string $hostname, string $username, string $password, bool $ssl, bool $passive, int $port)
    {
        if (!extension_loaded('curl')) {
            throw new FtpException("PHP cURL extension not loaded");
        }

        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->port     = $port;
        $this->passive  = $passive;
        $this->ssl      = $ssl;
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
    public function login(): bool
    {
        $this->error = '';
        try {
            $this->sendCurlRequest("", [
                CURLOPT_TIMEOUT => 120
            ]);
        } catch (Exception $ex) {
            debug_log('FTP CURL error, login');
            $this->error = $ex->getMessage();
            return false;
        }

        if (!empty($this->error)) {
            debug_log('FTP CURL login error');
            return false;
        }

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
        $handle = fopen('php://temp', 'wb+');
        if (!$handle) {
            return false;
        }

        if (($fileSize = fwrite($handle, $chunk))) {
            rewind($handle);
        }

        $curlOptions = [
            CURLOPT_UPLOAD     => true,
            CURLOPT_FTPAPPEND  => true,
            CURLOPT_INFILE     => $handle,
            CURLOPT_INFILESIZE => $fileSize,
        ];

        if ($remotePath !== '') {
            $remotePath = trailingslashit($remotePath);
        }

        $this->error = '';
        try {
            $this->sendCurlRequest($remotePath . $file, $curlOptions);
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
        }

        fclose($handle);

        // return true when no error
        return empty($this->error);
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
        // CurlHandle is only available from PHP 8
        // @phpstan-ignore-next-line
        if (class_exists('\CurlHandle') && ($this->handler instanceof \CurlHandle)) {
            curl_close($this->handler);
            $this->handler = null;
            return;
        }

        if (is_resource($this->handler)) {
            curl_close($this->handler);
            $this->handler = null;
        }
    }

    /**
     * @param string $path
     * @return array
     * @throws StorageException
     */
    public function getFiles(string $path): array
    {
        $this->error = '';
        try {
            $response = $this->sendCurlRequest(sprintf('/%s/', $path), [
                CURLOPT_CUSTOMREQUEST => 'LIST -tr'
            ]);
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            throw new StorageException($ex->getMessage());
        }

        $items = explode(PHP_EOL, $response);
        $files = [];
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

        return $files;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        $this->error = '';
        $filePath    = empty($this->path) ? $path : sprintf('%s/%s', $this->path, $path);
        $curlPath    = empty($this->path) ? '' : sprintf('/%s/', $this->path);

        try {
            $response = $this->sendCurlRequest($curlPath, [
                CURLOPT_QUOTE => [
                    sprintf('DELE /%s', $filePath)
                ]
            ]);
            if ($response) {
                return true;
            }
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            return false;
        }

        return empty($this->error);
    }

    public function getError(): string
    {
        return $this->error;
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
     * @param array $options
     * @return string|bool
     */
    protected function sendCurlRequest(string $path, array $options = [])
    {
        // @phpstan-ignore-next-line
        $this->handler = curl_init();

        // Set FTP URL
        curl_setopt($this->handler, CURLOPT_URL, sprintf('ftp://%s:%d/%s', $this->hostname, $this->port, $path));

        // Set username and password
        curl_setopt($this->handler, CURLOPT_USERPWD, sprintf('%s:%s', $this->username, $this->password));

        // Set default configuration
        curl_setopt($this->handler, CURLOPT_HEADER, false);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, true);
        //
        curl_setopt($this->handler, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($this->handler, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($this->handler, CURLOPT_TIMEOUT, 0);

        // Add additional options to connect to FTP with SSL if SSL was selected
        if ($this->ssl) {
            curl_setopt($this->handler, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->handler, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($this->handler, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
            curl_setopt($this->handler, CURLOPT_FTPSSLAUTH, CURLFTPAUTH_TLS);
        }

        // Is passive
        if ($this->passive) {
            curl_setopt($this->handler, CURLOPT_FTP_USE_EPSV, true);
        } else {
            curl_setopt($this->handler, CURLOPT_FTP_USE_EPRT, true);
            curl_setopt($this->handler, CURLOPT_FTPPORT, 0);
        }

        // Apply cURL options
        foreach ($options as $name => $value) {
            curl_setopt($this->handler, $name, $value);
        }

        // HTTP request
        $response = curl_exec($this->handler);
        if ($response !== false) {
            $this->handleHttpCodeError();

            $this->close();

            return $response;
        }

        $errno = curl_errno($this->handler);
        if (!$errno) {
            $this->handleHttpCodeError();

            $this->close();

            return $response;
        }

        switch ($errno) {
            case 6:
            case 7:
                $this->error = esc_html__("Unable to connect FTP server. Check your settings.", 'wp-staging');
                break;
            case 9:
                $this->error = esc_html__("Unable to connect FTP server. Check your permissions.", 'wp-staging');
                break;
            case 28:
                $this->error = esc_html__("Unable to connect FTP server. Server timeout. Check your settings.", 'wp-staging');
                break;
            case 67:
                $this->error = esc_html__("Unable to login to FTP server. Check your credentials.", 'wp-staging');
                break;
            default:
                $this->error = sprintf(esc_html__("Unable to connect FTP server. Error code: %s", 'wp-staging'), $errno);
        }

        $this->httpCode = curl_getinfo($this->handler, CURLINFO_HTTP_CODE);

        $this->close();

        return $response;
    }

    protected function handleHttpCodeError()
    {
        $this->httpCode = curl_getinfo($this->handler, CURLINFO_HTTP_CODE);
        if ($this->httpCode === 429) {
            $this->error = esc_html__("FTP Curl Client - Too many requests!", 'wp-staging');
        }

        if ($this->httpCode >= 500) {
            $this->error = esc_html__("FTP Curl Client - Internal Server Error", 'wp-staging');
        } elseif ($this->httpCode >= 400) {
            $this->error = sprintf(esc_html__("FTP Curl Client - Error code: %s", 'wp-staging'), $this->httpCode);
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
        $chunkEnd = $chunkStart + $chunkSize;
        $curlPath = $this->path . $fileName;
        $curlOptions = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_BINARYTRANSFER => 1,
            CURLOPT_RANGE => sprintf('%d-%d', $chunkStart, $chunkEnd)
        ];
        try {
            $response = $this->sendCurlRequest($curlPath, $curlOptions);
            if ($response === false) {
                $this->error = 'Unable to download file';
                return false;
            }

            $fileHandle = fopen($filePath, 'a+');
            if (fwrite($fileHandle, $response) === false) {
                $this->error = 'Unable to write to file';
                return false;
            }

            fclose($fileHandle);
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            return false;
        }
    }
}
