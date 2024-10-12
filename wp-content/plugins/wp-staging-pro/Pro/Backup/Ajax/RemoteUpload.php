<?php

namespace WPStaging\Pro\Backup\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\ErrorHandler;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Pro\Backup\Job\Jobs\JobRemoteUpload;

class RemoteUpload extends AbstractTemplateComponent
{
    /**
     * @var string
     */
    const WPSTG_REQUEST = 'wpstg_remote_upload';

    /**
     * @return void
     */
    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->setupTmpErrorFile();

        /** @var JobRemoteUpload */
        $jobUploader = WPStaging::make(JobRemoteUpload::class);
        $jobUploader->setMemoryExhaustErrorTmpFile($tmpFileToDelete);

        wp_send_json($jobUploader->prepareAndExecute());
    }

    /**
     * @return string|false
     */
    protected function setupTmpErrorFile()
    {
        if (!defined('WPSTG_UPLOADS_DIR')) {
            return false;
        }

        if (!defined('WPSTG_REQUEST')) {
            define('WPSTG_REQUEST', self::WPSTG_REQUEST);
        }

        return WPSTG_UPLOADS_DIR . self::WPSTG_REQUEST . ErrorHandler::ERROR_FILE_EXTENSION;
    }
}
