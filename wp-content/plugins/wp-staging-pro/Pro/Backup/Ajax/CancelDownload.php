<?php

namespace WPStaging\Pro\Backup\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Pro\Backup\Job\Jobs\Cloud\JobDownloadCancel;

class CancelDownload extends AbstractTemplateComponent
{
    /**
     * @return void
     */
    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        /** @var JobDownloadCancel $job */
        $job = WPStaging::make(JobDownloadCancel::class);
        if (isset($_POST['isInit']) && sanitize_text_field($_POST['isInit']) === 'yes') {
            $jobDataDto = WPStaging::make(JobDataDto::class);
            $jobDataDto->setInit(true);
            $jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));
            $job->setJobDataDto($jobDataDto);

            $jobId = isset($_POST['jobIdBeingCancelled']) ? html_entity_decode(sanitize_text_field($_POST['jobIdBeingCancelled'])) : '';

            AnalyticsEventDto::enqueueCancelEvent($jobId);
        }

        wp_send_json($job->prepareAndExecute());
    }
}
