<?php

namespace WPStaging\Backend\Pro\Modules\Jobs;

use WPStaging\Backend\Modules\Jobs\JobExecutable;
use WPStaging\Backup\Dto\Task\Restore\RenameDatabaseTaskDto;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Database\TablesRenamer;

/**
 * @package WPStaging\Backend\Modules\Jobs
 */
class DatabaseTmpRename extends JobExecutable
{
    /** @var int */
    const TOTAL_STEPS = 5;

    /** @var int */
    const STEP_BEFORE_RENAMING = 0;

    /** @var int */
    const STEP_RENAME_NON_CONFLICTING_TABLES = 1;

    /** @var int */
    const STEP_RENAME_CONFLICTING_TABLES = 2;

    /** @var int */
    const STEP_RENAME_CUSTOM_TABLES = 3;

    /** @var int */
    const STEP_AFTER_RENAMING = 4;

    /**
     * @var TablesRenamer
     */
    private $tablesRenamer;

    /** @var string */
    private $productionTablePrefix;

    /**
     * Initialize
     * @return void
     */
    public function initialize()
    {
        $wpdb                        = WPStaging::make('wpdb');
        $this->productionTablePrefix = $wpdb->prefix;

        $this->checkFatalError();
        $this->tablesRenamer = WPStaging::make(TablesRenamer::class);
    }

    /**
     * @return void
     */
    protected function checkFatalError()
    {
        if (DatabaseTmp::TMP_PREFIX === $this->productionTablePrefix) {
            $this->returnException('Fatal Error: Prefix ' . $this->productionTablePrefix . ' is used for the live site and used for the temporary database tables hence we can not replace the production database. Please ask support@wp-staging.com how to resolve this.');
        }
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = self::TOTAL_STEPS;
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     * @throws \Exception
     */
    protected function execute(): bool
    {
        // Over limits threshold
        if ($this->isOverThreshold()) {
            $this->log('DB Rename: Is over threshold. Continuing ...');
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

        if ($this->options->currentStep === self::STEP_BEFORE_RENAMING) {
            $this->stepBeforeTablesRenaming();
            $this->prepareResponse(false, true);
            return false;
        }

        if ($this->options->currentStep === self::STEP_RENAME_NON_CONFLICTING_TABLES) {
            $incrementStep = $this->stepRenameNonConflictingTables();
            $this->prepareResponse(false, $incrementStep);
            return false;
        }

        if ($this->options->currentStep === self::STEP_RENAME_CONFLICTING_TABLES) {
            $incrementStep = $this->stepRenameConflictingTables();
            $this->prepareResponse(false, $incrementStep);
            return false;
        }

        if ($this->options->currentStep === self::STEP_RENAME_CUSTOM_TABLES) {
            $incrementStep = $this->stepRenameCustomTables();
            $this->prepareResponse(false, $incrementStep);
            return false;
        }

        if ($this->options->currentStep === self::STEP_AFTER_RENAMING) {
            $this->stepAfterTablesRenaming();
            $this->prepareResponse(false, true);
            return false;
        }

        $this->isFinished();
        $this->prepareResponse(true, false);
        return false;
    }

    /**
     * @return void
     */
    protected function setupTableRenamer()
    {
        $this->tablesRenamer->setTmpPrefix(DatabaseTmp::TMP_PREFIX);
        $this->tablesRenamer->setCustomTableTmpPrefix(DatabaseTmp::TMP_PREFIX_CUSTOM_TABLE);
        $this->tablesRenamer->setProductionTablePrefix($this->productionTablePrefix);
        $this->tablesRenamer->setDropPrefix(DatabaseTmp::TMP_PREFIX_TO_DROP);
        $this->tablesRenamer->setShortNamedTablesToRename([]);
        $this->tablesRenamer->setShortNamedTablesToDrop([]);
        $this->tablesRenamer->setRenameViews(false);
        // Only rename custom tables when external database
        $this->tablesRenamer->setRenameCustomTables($this->isExternalDatabase());
        $this->tablesRenamer->setExcludedTables([]);
        $this->tablesRenamer->setLogEachRename(true);
        $this->tablesRenamer->setLogger($this->logger);
        $this->tablesRenamer->setThresholdCallable([$this, 'isOverThreshold']);
    }

    /**
     * @return void
     */
    protected function stepBeforeTablesRenaming()
    {
        $this->setupTableRenamer();
        $this->options->renameJobData           = $this->tablesRenamer->setupRenamer()->toArray();
        $this->options->totalTablesToRename     = $this->tablesRenamer->getTotalTables();
        $this->options->totalTablesRenamed      = 0;
        $this->options->activePluginsToPreserve = $this->tablesRenamer->getActivePluginsToPreserve();
        $this->options->isNetworkActivePlugin   = is_plugin_active_for_network(WPSTG_PLUGIN_FILE);

        if ($this->options->isNetworkActivePlugin && $this->options->isNetworkClone) {
            $this->options->activeSitewidePluginsToPreserve = $this->tablesRenamer->getActiveSitewidePluginsToPreserve();
        }

        if (!empty($this->options->activePluginsToPreserve)) {
            $this->log('DB Rename: Preserved Active Plugins');
        }
    }

    /** @return bool */
    protected function stepRenameNonConflictingTables(): bool
    {
        $taskDto = $this->prepareTablesRenaming();
        $result  = $this->tablesRenamer->cleanTemporaryBackupTables();
        if ($result === false) {
            $this->log(sprintf('DB Rename: Cleaning Temporary Tables failed. Remaining: %s', $this->tablesRenamer->getTablesRemainingToBeDropped()));
            return false;
        }

        $result = $this->tablesRenamer->renameNonConflictingTables();
        $taskDto->nonConflictingTablesRenamed = $this->tablesRenamer->getNonConflictingTablesRenamed();
        $this->log(sprintf('DB Rename: Renamed %d/%d tables', $taskDto->nonConflictingTablesRenamed, $this->options->totalTablesToRename));
        $this->options->renameJobData = $taskDto->toArray();

        return $result;
    }

    /** @return bool */
    protected function stepRenameConflictingTables(): bool
    {
        $taskDto = $this->prepareTablesRenaming();
        $result  = $this->tablesRenamer->renameConflictingTables();
        $taskDto->conflictingTablesRenamed = $this->tablesRenamer->getConflictingTablesRenamed();
        $totalTablesRenamed = $taskDto->nonConflictingTablesRenamed + $taskDto->conflictingTablesRenamed;
        $this->log(sprintf('DB Rename: Renamed %d/%d tables', $totalTablesRenamed, $this->options->totalTablesToRename));
        $this->options->renameJobData = $taskDto->toArray();

        return $result;
    }

    /** @return bool */
    protected function stepRenameCustomTables(): bool
    {
        /**
         * Early bail if not external database.
         * Custom Table Renaming has no effect when its applied on same database.
         * It will be some_table -> wpstgtmx_some_table -> some_table on same database.
         * So it makes no sense to rename custom tables when its the same database.
         */
        if (!$this->isExternalDatabase()) {
            return true;
        }

        $taskDto = $this->prepareTablesRenaming();
        $result  = $this->tablesRenamer->renameCustomTables();
        $taskDto->customTablesRenamed = $this->tablesRenamer->getCustomTablesRenamed();
        $totalTablesRenamed = $taskDto->nonConflictingTablesRenamed + $taskDto->conflictingTablesRenamed + $taskDto->customTablesRenamed;
        $this->log(sprintf('DB Rename: Renamed %d/%d tables', $totalTablesRenamed, $this->options->totalTablesToRename));
        $this->options->renameJobData = $taskDto->toArray();

        return $result;
    }

    /**
     * @return void
     */
    protected function stepAfterTablesRenaming()
    {
        $activeWpstgPlugin = plugin_basename(trim(WPSTG_PLUGIN_FILE));
        if (!empty($this->options->activePluginsToPreserve)) {
            $this->tablesRenamer->setProductionTablePrefix($this->productionTablePrefix);
            $this->tablesRenamer->restorePreservedActivePlugins($this->options->activePluginsToPreserve, $activeWpstgPlugin, false);
            $this->log('DB Rename: Restored Preserved Active Plugins');
        }

        if ($this->options->isNetworkActivePlugin && $this->options->isNetworkClone) {
            $this->tablesRenamer->restorePreservedActiveSitewidePlugins($this->options->isNetworkActivePlugin, $activeWpstgPlugin);
            $this->log('DB Rename: Preserved Network Active Plugin Status');
        }

        $this->flush();
    }

    /**
     * @return RenameDatabaseTaskDto
     */
    protected function prepareTablesRenaming(): RenameDatabaseTaskDto
    {
        $this->setupTableRenamer();
        $taskData = json_decode(json_encode($this->options->renameJobData), true);
        $taskDto  = new RenameDatabaseTaskDto();
        $taskDto->hydrateProperties($taskData);
        $this->tablesRenamer->setTaskDto($taskDto);

        return $taskDto;
    }

    /**
     * Flush wpdb cache and permalinks rewrite rules
     * to prevent 404s and other oddities
     * @global object $wp_rewrite
     * @return void
     */
    protected function flush()
    {
        wp_cache_flush();
        global $wp_rewrite;
        $wp_rewrite->init();
        flush_rewrite_rules(true); // true = hard refresh, recreates the .htaccess file
    }

    /**
     * Push is finished
     * @return bool
     */
    protected function isFinished(): bool
    {
        // This job is finished
        if ($this->options->currentStep >= $this->options->totalSteps) {
            $this->log('DB Rename: Has been finished successfully. Cleaning up...');
            $this->prepareResponse(true, false);
            return true;
        }

        return false;
    }
}
