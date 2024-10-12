<?php

namespace WPStaging\Backend\Pro\Modules\Jobs;

use Exception;
use WPStaging\Backend\Modules\Jobs\Job;
use WPStaging\Backend\Pro\Modules\Jobs\Backups\BackupUploadsDir;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingPush;
use WPStaging\Framework\Database\TableService;

/**
 * Class Finish
 * @package WPStaging\Backend\Modules\Jobs
 */
class Finish extends Job
{
    /** @var array */
    private $tables;

    /** @var TableService */
    private $tableService;

    /**
     * Start Module
     * @return array
     * @throws Exception
     */
    public function start()
    {
        $this->tableService = WPStaging::make(TableService::class);

        $this->getTableRecords();

        // Clean up
        $this->deleteTables();

        $this->scheduleCronToDeleteUploadsBackup();

        // Delete Cache Files
        $this->deleteCacheFiles();

        WPStaging::make(AnalyticsStagingPush::class)->enqueueFinishEvent($this->options->jobIdentifier, $this->options);

        do_action('wpstg_pushing_complete');

        $this->logger->info("################## FINISH ##################");

        return [
            "status"       => 'finished',
            "percentage"   => 100,
            "total"        => $this->options->totalSteps,
            "step"         => $this->options->currentStep,
            "last_msg"     => $this->logger->getLastLogMsg(),
            "job_done"     => true
        ];
    }

    /**
     * Add cron for deleting the uploads backups if that option was selected
     */
    protected function scheduleCronToDeleteUploadsBackup()
    {
        $backup = new BackupUploadsDir($this);
        $backup->scheduleDeleteOfTheBackup();

        foreach ($backup->getLogs() as $log) {
            if ($log['type'] === Logger::TYPE_INFO) {
                $this->log($log['msg']);
            }
        }
    }

    /**
     * Delete Cache Files
     * @throws \Exception
     */
    protected function deleteCacheFiles()
    {
        $this->log("Finish: Deleting clone job's cache files...");

        // Clean cache files
        $this->cloneOptionCache->delete();
        $this->filesIndexCache->delete();

        $this->log("Finish: Clone job's cache files have been deleted!");
    }

    /**
     * Delete tmp Tables
     */
    public function deleteTables()
    {
        foreach ($this->tables as $table) {
            $this->tableService->deleteTables([$table]);
        }
    }

    /**
     * Get tmp Tables
     */
    private function getTableRecords()
    {
        $tmpPrefix    = DatabaseTmp::TMP_PREFIX;
        $this->tables = [];
        if ($tmpPrefix !== $this->tableService->getDatabase()->getPrefix()) {
            $this->tables = $this->tableService->findTableNamesStartWith($tmpPrefix);
        }

        $this->tables = array_merge($this->tables, $this->tableService->findTableNamesStartWith(DatabaseTmp::TMP_PREFIX_TO_DROP));
    }
}
