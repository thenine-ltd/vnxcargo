<?php

namespace WPStaging\Pro\Backup\Ajax;

use WPStaging\Backup\BackupProcessLock;
use WPStaging\Backup\Exceptions\ProcessLockedException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Pro\Backup\Dto\Job\JobCloudDownloadDataDto;
use WPStaging\Pro\Backup\Job\Jobs\Cloud\JobCloudDownload;

class Download extends AbstractTemplateComponent
{
    /** @var BackupProcessLock */
    protected $processLock;

    /**
     * @param TemplateEngine $templateEngine
     * @param BackupProcessLock $processLock
     */
    public function __construct(TemplateEngine $templateEngine, BackupProcessLock $processLock)
    {
        $this->processLock = $processLock;

        parent::__construct($templateEngine);
    }

    /**
     * @return void
     */
    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $file = !empty($_POST['file']) ? Sanitize::sanitizeString($_POST['file']) : "";
        $size = !empty($_POST['size']) ? Sanitize::sanitizeInt($_POST['size']) : "";
        $cloudFileName = !empty($_POST['cloudFileName']) ? Sanitize::sanitizeString($_POST['cloudFileName']) : "";
        $storageProviderName = !empty($_POST['storageProviderName']) ? Sanitize::sanitizeString($_POST['storageProviderName']) : "";
        $isInit = !empty($_POST['isInit']) ? Sanitize::sanitizeString($_POST['isInit']) : "";

        try {
            $this->processLock->checkProcessLocked();
        } catch (ProcessLockedException $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());
        }

        /** @var JobCloudDownload $job */
        $job = WPStaging::make(JobCloudDownload::class);

        $jobDataDto = WPStaging::make(JobCloudDownloadDataDto::class);
        $jobDataDto->setInit(true);
        $jobDataDto->setInitialDownload(($isInit === 'yes') ? true : false);
        $jobDataDto->setFile($file);
        $jobDataDto->setCloudFileName($cloudFileName);
        $jobDataDto->setSize($size);
        $jobDataDto->setStorageProviderName($storageProviderName);
        $jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));
        $job->setJobDataDto($jobDataDto);
        $jobId = isset($_POST['jobIdBeingCancelled']) ? html_entity_decode(sanitize_text_field($_POST['jobIdBeingCancelled'])) : '';

        AnalyticsEventDto::enqueueCancelEvent($jobId);

        wp_send_json($job->prepareAndExecute());
    }
}
