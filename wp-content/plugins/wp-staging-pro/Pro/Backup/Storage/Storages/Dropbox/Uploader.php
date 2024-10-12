<?php

namespace WPStaging\Pro\Backup\Storage\Storages\Dropbox;

use Exception;
use UnexpectedValueException;
use WPStaging\Backup\Dto\Interfaces\RemoteUploadDtoInterface;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Exceptions\StorageException;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Pro\Backup\Dto\Job\JobCloudDownloadDataDto;
use WPStaging\Pro\Backup\Storage\RemoteUploaderInterface;
use WPStaging\Pro\Backup\Storage\Storages\Dropbox\Auth;
use WPStaging\Pro\Backup\Task\Tasks\JobBackup\AbstractStorageTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

use function WPStaging\functions\debug_log;

class Uploader implements RemoteUploaderInterface
{
    use WithBackupIdentifier;

    /** @var string */
    const DROPBOX_API_FILE_UPLOAD_SESSION_URL = 'https://content.dropboxapi.com/2/files/upload_session';

    /** @var RemoteUploadDtoInterface */
    private $jobDataDto;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $filePath;

    /** @var string */
    private $fileName;

    /** @var string */
    private $folderName;

    /** @var int */
    private $maxBackupsToKeep;

    /** @var FileObject */
    private $fileObject;

    /** @var int */
    private $chunkSize;

    /** @var Auth */
    private $auth;

    /** @var bool|string */
    private $error;

    /** @var array */
    protected $options;

    /** @var Strings */
    private $strings;

    /** @var Directory */
    private $dirAdapter;

    public function __construct(Auth $auth, Strings $strings, Directory $directory)
    {
        $this->auth = $auth;
        if (!$this->auth->testConnection() && !$this->auth->refreshToken()) {
            $this->error = __('Fail to refresh the access token, the process should resume automatically, if the error persists please reconnect to your dropbox account.', 'wp-staging');
            return;
        }

        if (!$this->auth->isAuthenticated()) {
            $this->error = __('Dropbox is not authenticated. Backup is still available locally.', 'wp-staging');
            return;
        }

        $this->error            = false;
        $this->strings          = $strings;
        $this->dirAdapter       = $directory;
        $this->options          = $this->auth->getOptions();
        $this->folderName       = isset($this->options['folderName']) ? $this->options['folderName'] : Auth::FOLDER_NAME;
        $this->maxBackupsToKeep = isset($this->options['maxBackupsToKeep']) && $this->options['maxBackupsToKeep'] > 0 ? intval($this->options['maxBackupsToKeep']) : 15;
    }

    public function getProviderName()
    {
        return 'Dropbox';
    }

    /**
     * @param LoggerInterface $logger
     * @param RemoteUploadDtoInterface $jobDataDto
     * @param int $chunkSize
     * @return void
     */
    public function setupUpload(LoggerInterface $logger, RemoteUploadDtoInterface $jobDataDto, $chunkSize = 1 * MB_IN_BYTES)
    {
        $this->logger     = $logger;
        $this->jobDataDto = $jobDataDto;
        $this->chunkSize  = $chunkSize;
    }

    public function setupDownload(LoggerInterface $logger, JobCloudDownloadDataDto $jobDataDto, int $chunkSize = MB_IN_BYTES)
    {
        // no-op
    }

    /**
     * @param int $backupSize
     */
    public function checkDiskSize($backupSize)
    {
        // no-op
    }

    /**
     * @param  string $backupFilePath
     * @param  string $fileName
     * @return bool
     */
    public function setBackupFilePath($backupFilePath, $fileName)
    {
        $this->fileName   = $fileName;
        $this->filePath   = $backupFilePath;
        $this->fileObject = new FileObject($this->filePath, FileObject::MODE_READ);

        $uploadMetadata = (array)$this->jobDataDto->getRemoteStorageMeta();
        if (!array_key_exists($this->fileName, $uploadMetadata)) {
            $this->logger->info('Dropbox: Starting upload of backup file:' . $this->fileName);
        }

        return true;
    }

    /**
     * @throws FinishedQueueException
     *
     * @return int Number of bytes uploaded
     */
    public function chunkUpload()
    {
        if (isset($this->jobDataDto->getRemoteStorageMeta()[$this->fileName]) && isset($this->jobDataDto->getRemoteStorageMeta()[$this->fileName]['Offset'])) {
            $fileMetadata = $this->jobDataDto->getRemoteStorageMeta()[$this->fileName];
            $offset       = $fileMetadata['Offset'];
        } else {
            $offset = 0;
            unset($this->options['sessionId']);
            $this->auth->saveOptions($this->options);
        }

        $this->fileObject->fseek($offset);
        $chunk = $this->fileObject->fread($this->chunkSize);

        $chunkSize = strlen($chunk);
        $offsetNew = $offset + $chunkSize;
        $fileSize  = $this->fileObject->getSize();
        if ($offset === 0 && !isset($this->options['sessionId'])) {
            $chunkSize = $this->startUploadSession($chunk);
        } elseif (isset($this->options['sessionId']) && $offsetNew < $fileSize) {
            $chunkSize = $this->appendUploadSession((int)$offset, $chunk);
        } elseif (isset($this->options['sessionId']) && $offsetNew >= $fileSize) {
            $chunkSize = $this->finishUploadSession((int)$offset, $chunk);
        } else {
            debug_log('WP STAGING should stop upload: ' . $this->fileName);
            throw new FinishedQueueException();
        }

        // finish upload task if the file is fully uploaded and upload session is closed
        if (!isset($this->options['sessionId']) && ($chunkSize + $offset) >= $fileSize) {
            debug_log('WP STAGING stopping upload: ' . $this->fileName);
            throw new FinishedQueueException();
        }

        // close the upload session if the file is fully uploaded by the first chunk
        if (isset($this->options['sessionId']) && ($chunkSize + $offset) >= $fileSize) {
            $this->finishUploadSession($chunkSize, null);
            throw new FinishedQueueException();
        }

        return $chunkSize;
    }

    /**
     * @param string $filePath
     * @param string $remoteFileName
     *
     * @throws Exception
     * @return bool
     */
    public function uploadFile($filePath, $remoteFileName = '')
    {
        $this->chunkSize  = AbstractStorageTask::CHUNK_SIZE * MB_IN_BYTES;
        $this->fileName   = $remoteFileName;
        $this->fileObject = new FileObject($filePath, FileObject::MODE_READ);
        $this->jobDataDto = WPStaging::make(JobBackupDataDto::class);
        try {
            $this->chunkUpload();
        } catch (FinishedQueueException $exception) {
            return true;
        } catch (Exception $ex) {
            throw new Exception("Dropbox error in uploadFile: " . $ex->getMessage());
        }
        return true;
    }

    /**
     * @return void
     */
    public function stopUpload()
    {
        unset($this->options['sessionId']);
    }

    /** @return string */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function getBackups()
    {
        $files = $this->auth->getFiles();

        $backups = array_filter($files, function ($file) {
            $fileName = $file['name'];
            if ($this->strings->endsWith($fileName, '.wpstg') || $this->strings->endsWith($fileName, '.sql')) {
                return $file;
            }

            return false;
        });

        return $backups;
    }

    /**
     * @return bool
     */
    public function deleteOldestBackups()
    {
        $retainedBackups = $this->auth->getRetainedBackups();
        if (count($retainedBackups) < $this->maxBackupsToKeep) {
            return true;
        }

        $remoteBackupsFiles = $this->getBackups();
        $toDelete           = [];

        foreach ($retainedBackups as $retainedBackupId => $retainedBackup) {
            if (count($retainedBackups) < $this->maxBackupsToKeep) {
                break;
            }

            foreach ($remoteBackupsFiles as $file) {
                $fileName = $file['name'];
                if (strpos($fileName, $retainedBackupId) !== false) {
                    $toDelete[] = [
                        'path' => $file['path_lower'],
                    ];
                }
            }

            $this->auth->unsetStorageFromRetainedBackups($retainedBackupId);
            unset($retainedBackups[$retainedBackupId]);
        }

        $args = [
            'headers' => [
                'Content-Type'    => 'application/json',
                'Authorization'   => 'Bearer ' . $this->options['accessToken'],
            ],
            'body'    => json_encode([
                'entries' => $toDelete,
            ]),
        ];
        $response = $this->runRemoteRequest(Auth::DROPBOX_API_V2_URL . '/files/delete_batch', $args);
        if (isset($response['async_job_id'])) {
            return $this->checkDeleteBatchStatus($response['async_job_id']);
        }

        return true;
    }

    /**
     * @param  string $asyncJobId
     *
     * @return bool
     */
    public function checkDeleteBatchStatus($asyncJobId)
    {
        $args = [
            'headers' => [
                'Content-Type'    => 'application/json',
                'Authorization'   => 'Bearer ' . $this->options['accessToken'],
            ],
            'body'    => json_encode([
                'async_job_id' => $asyncJobId,
            ]),
        ];

        $tagStatus = 'in_progress';
        $i         = 0;
        do {
            $response = $this->runRemoteRequest(Auth::DROPBOX_API_V2_URL . '/files/delete_batch/check', $args);
            if (isset($response['.tag'])) {
                if ($response['.tag'] === 'complete') {
                    return true;
                }

                $tagStatus = $response['.tag'];
                usleep(1000);
            } elseif (!isset($response['.tag']) || $i >= 20) {
                $tagStatus = null;
            }
            $i++;
        } while ($tagStatus === 'in_progress');

        debug_log('Dropbox warning: fail to check delete batch status. response: ' . print_r($response, true));
        return false;
    }

    /**
     * @param  array $uploadsToVerify
     * @return bool
     */
    public function verifyUploads(array $uploadsToVerify): bool
    {
        $backups = $this->getBackups();
        $uploadsConfirmed = [];
        foreach ($backups as $file) {
            $fileName = $file['name'];

            if (!array_key_exists($fileName, $uploadsToVerify)) {
                continue;
            }

            $toVerify = $uploadsToVerify[$fileName];
            $fileSize = (int)$file['size'];

            if ($fileSize !== $toVerify['size']) {
                continue;
            }

            if ($file['content_hash'] !== $toVerify['hash']) {
                continue;
            }

            $uploadsConfirmed[] = $fileName;
        }

        return count($uploadsConfirmed) === count($uploadsToVerify);
    }

    /**
     * @param string $file
     * @return string|false — a string on success, false otherwise.
     */
    public function computeDropboxContentHash($file)
    {
        try {
            return $this->auth->computeFileHash($file);
        } catch (UnexpectedValueException $ex) {
            return false;
        }
    }

    /**
     * Save backup file from google drive to server
     *
     * @param string $fileId
     * @param int $fileSize
     * @param int $chunkStart
     * @return int|void
     */
    public function chunkDownloadCloudFileToFolder(string $fileId, int $fileSize, int $chunkStart)
    {
        //no-op.
    }

    /**
     * @param string $fileId
     * @return bool|void
     */
    public function deleteFile($fileId)
    {
        //no-op.
    }

    /**
     * @param  string $chunk Chunk of data to upload
     * @return int Number of bytes uploaded
     */
    protected function startUploadSession($chunk)
    {
        $args = [
            'headers' => [
                'Content-Type'    => 'application/octet-stream',
                'Authorization'   => 'Bearer ' . $this->options['accessToken'],
                'Dropbox-API-Arg' => '{"session_type":{".tag":"sequential"}}',
            ],
            'body'    => $chunk,
        ];
        $response = $this->runRemoteRequest(self::DROPBOX_API_FILE_UPLOAD_SESSION_URL . '/start', $args);
        if (!isset($response['session_id'])) {
            debug_log('Dropbox session id is missing. Should Retry...');
            return 0;
        }

        $this->setMetadata(strlen($chunk));
        $this->options['sessionId'] = $response['session_id'];
        $this->auth->saveOptions($this->options);
        return strlen($chunk);
    }

    /**
     * @param  int $offset
     * @param  string $chunk Chunk of data to upload
     * @return int Number of bytes uploaded
     */
    protected function appendUploadSession($offset, $chunk)
    {
        $dropboxArgs = [
            'close'  => false,
            'cursor' => [
                'session_id' => $this->options['sessionId'],
                'offset'     => $offset,
            ],
        ];

        $args = [
            'headers' => [
                'Content-Type'    => 'application/octet-stream',
                'Authorization'   => 'Bearer ' . $this->options['accessToken'],
                'Dropbox-API-Arg' => json_encode($dropboxArgs),
            ],
            'body'    => $chunk,
        ];
        $response = $this->runRemoteRequest(self::DROPBOX_API_FILE_UPLOAD_SESSION_URL . '/append_v2', $args);
        if (!$response) {
            return 0;
        }

        $this->setMetadata($offset + strlen($chunk));
        return strlen($chunk);
    }

    /**
     * @param int $offset
     * @param ?int $chunk
     *
     * @throws FinishedQueueException
     *
     * @return int Returns 0 if upload failed so it can be retried.
     */
    protected function finishUploadSession($offset, $chunk)
    {
        $path = '/' . trim($this->folderName, '/') . '/' . $this->fileName;
        $dropboxArgs = [
            'cursor' => [
                'session_id' => $this->options['sessionId'],
                'offset'     => $offset,
            ],
            'commit' => [
                'autorename'      => true,
                'mode'            => 'add',
                'mute'            => false,
                'path'            => $path,
                'strict_conflict' => false,
            ],
        ];

        $args = [
            'headers'     => [
                'Content-Type'  => 'application/octet-stream',
                'Authorization' => 'Bearer ' . $this->options['accessToken'],
                'Dropbox-API-Arg' => json_encode($dropboxArgs),
            ],
            'body'        => $chunk,
        ];
        $response = $this->runRemoteRequest(self::DROPBOX_API_FILE_UPLOAD_SESSION_URL . '/finish', $args);
        if (!$response) {
            return 0;
        }

        unset($this->options['sessionId']);
        $this->auth->saveOptions($this->options);

        $this->setMetadata($offset + strlen($chunk ?? 0));
        $this->auth->saveStorageAccountInfo();
        throw new FinishedQueueException();
    }

    /**
     * @param  mixed $url
     * @param  mixed $args
     * @throws Exception
     * @return array|bool
     */
    protected function runRemoteRequest($url, $args = [])
    {
        $defaults = [
            'timeout'     => 120,
            'httpversion' => '1.0',
            'sslverify'   => true,
        ];
        $args = wp_parse_args($args, $defaults);

        $response = wp_remote_post($url, $args);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $errorMessage = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
            $this->error = $errorMessage;

            $responseBody = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($responseBody['error']) && isset($responseBody['error']['correct_offset'])) {
                $this->setMetadata($responseBody['error']['correct_offset']);
                return false;
            } elseif (isset($responseBody['error']) && isset($responseBody['error']['lookup_failed']) && isset($responseBody['error']['lookup_failed']['correct_offset'])) {
                $this->setMetadata($responseBody['error']['lookup_failed']['correct_offset']);
                return false;
            } elseif (strpos($errorMessage, 'invalid_access_token') !== false) {
                $this->options['isAuthenticated'] = false;
                $this->auth->saveOptions($this->options);
                throw new StorageException(__('Access token expired: Please reconnect to your Dropbox account!', 'wp-staging'));
            } elseif (strpos($errorMessage, 'too_many_requests') !== false) {
                sleep(10);
            } elseif (strpos($errorMessage, 'cURL error 28: Resolving timed out after') !== false || strpos($errorMessage, 'cURL error 7') !== false || strpos($errorMessage, 'cURL error 6: Could not resolve host') !== false) {
                debug_log("WP STAGING Dropbox curl error in uploader. Error Message: $errorMessage. Retrying...");
                return false;
            } else {
                debug_log("WP STAGING Dropbox error in uploader. url: $url; Error Message: $errorMessage");
                unset($this->options['sessionId']);
                $this->auth->saveOptions($this->options);
                $this->setMetadata(null);
                throw new StorageException('Dropbox error in runRemoteRequest: ' . $errorMessage);
            }
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * @param  int $offset
     * @return void
     */
    protected function setMetadata($offset = 0)
    {
        $this->jobDataDto->setRemoteStorageMeta([
            $this->fileName => [
                'Offset' => $offset,
            ]
        ]);
    }
}
