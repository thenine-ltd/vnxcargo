<?php

namespace WPStaging\Pro\Backup\Ajax\RemoteUpload;

use Exception;
use UnexpectedValueException;
use WP_Error;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Security\Auth;
use WPStaging\Backup\Ajax\PrepareJob;
use WPStaging\Backup\BackupProcessLock;
use WPStaging\Backup\Exceptions\ProcessLockedException;
use WPStaging\Pro\Backup\Dto\Job\JobRemoteUploadDataDto;
use WPStaging\Pro\Backup\Job\Jobs\JobRemoteUpload;

class PrepareRemoteUpload extends PrepareJob
{
    /** @var JobRemoteUploadDataDto */
    private $jobDataDto;

    /** @var JobRemoteUpload */
    private $jobRemoteUpload;

    /**
     * @param Filesystem $filesystem
     * @param Directory $directory
     * @param Auth $auth
     * @param BackupProcessLock $processLock
     */
    public function __construct(Filesystem $filesystem, Directory $directory, Auth $auth, BackupProcessLock $processLock)
    {
        parent::__construct($filesystem, $directory, $auth, $processLock);
    }

    /**
     * @param array|null $data
     * @return void
     */
    public function ajaxPrepare($data)
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error(null, 401);
        }

        try {
            $this->processLock->checkProcessLocked();
        } catch (ProcessLockedException $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());
        }

        try {
            $data = $this->prepare($data);
        } catch (Exception $ex) {
            wp_send_json_error($ex->getMessage(), $ex->getCode());
        }

        wp_send_json_success();
    }

    /**
     * @param @param array|null $data
     * @return array
     * @throws UnexpectedValueException
     */
    public function prepare($data = null): array
    {
        if (empty($data) && array_key_exists('wpstgRemoteUploadData', $_POST)) {
            $data = Sanitize::sanitizeArray($_POST['wpstgRemoteUploadData'], []);
        }

        $sanitizedData = $this->setupInitialData($data);

        return $sanitizedData;
    }

    /**
     * @param array|null $sanitizedData
     * @return array
     * @throws UnexpectedValueException
     */
    private function setupInitialData($sanitizedData): array
    {
        $sanitizedData = $this->validateAndSanitizeData($sanitizedData);
        $this->clearCacheFolder();

        // Lazy-instantiation to avoid process-lock checks conflicting with running processes.
        $container             = WPStaging::getInstance()->getContainer();
        $this->jobDataDto      = $container->get(JobRemoteUploadDataDto::class);
        $this->jobRemoteUpload = $container->get(JobRemoteUpload::class);

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setIsOnlyUpload(true);

        $this->jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));

        $this->jobRemoteUpload->setJobDataDto($this->jobDataDto);

        return $sanitizedData;
    }

    /**
     * @return array
     * @throws UnexpectedValueException
     */
    private function validateAndSanitizeData($data): array
    {
        $expectedKeys = [
            'file',
            'storages'
        ];

        // Make sure data has no keys other than the expected ones.
        $data = array_intersect_key($data, array_flip($expectedKeys));

        // Make sure data has all expected keys.
        foreach ($expectedKeys as $expectedKey) {
            if (!array_key_exists($expectedKey, $data) && $expectedKey === 'storages') {
                $data['storages'] = [];
            }

            if (!array_key_exists($expectedKey, $data) && $expectedKey === 'file') {
                throw new UnexpectedValueException("Invalid request. No Backup Selected.");
            }
        }

        return $data;
    }
}
