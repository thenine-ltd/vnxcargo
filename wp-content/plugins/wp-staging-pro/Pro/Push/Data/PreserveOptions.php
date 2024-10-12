<?php

namespace WPStaging\Pro\Push\Data;

use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Staging\CloneOptions;
use WPStaging\Framework\Database\OptionPreservationHandler;
use WPStaging\Framework\ThirdParty\FreemiusScript;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Backup\BackupRetentionHandler;

class PreserveOptions extends OptionsTablePushService
{
    private $optionPreservationHandler;
    private $freemiusHelper;
    private $siteInfo;

    /**
     * @param FreemiusScript $freemiusScript
     * @param SiteInfo $siteInfo
     * @param OptionPreservationHandler $optionPreservationHandler
     */
    public function __construct(FreemiusScript $freemiusScript, SiteInfo $siteInfo, OptionPreservationHandler $optionPreservationHandler)
    {
        $this->freemiusHelper            = $freemiusScript;
        $this->siteInfo                  = $siteInfo;
        $this->optionPreservationHandler = $optionPreservationHandler;
    }

    /**
     * @inheritDoc
     */
    protected function processOptionsTable(): bool
    {
        $this->log("Preserve Data in " . $this->prodOptionsTable);

        if (!$this->tableExists($this->prodOptionsTable)) {
            return true;
        }

        $optionsToPreserve = [
            'wpstg_optimizer_excluded',
            'wpstg_version_upgraded_from',
            'wpstg_version',
            'wpstg_installDate',
            'wpstg_free_install_date',
            'wpstgpro_install_date',
            'wpstgpro_upgrade_date',
            'wpstgpro_version',
            'wpstgpro_version_upgraded_from',
            'wpstg_version_latest',
            'wpstg_queue_table_version',
            'upload_path',
            'wpstg_free_upgrade_date',
            'wpstg_googledrive',
            'wpstg_amazons3',
            'wpstg_sftp',
            'wpstg_digitalocean',
            'wpstg_wasabi',
            'wpstg_dropbox',
            FinishBackupTask::OPTION_LAST_BACKUP,
            BackupScheduler::OPTION_BACKUP_SCHEDULES,
            AccessToken::OPTION_NAME,
            BackupRetentionHandler::OPTION_BACKUPS_RETENTION
        ];

        // Preserve CloneOptions if current site is staging site
        if ($this->siteInfo->isStagingSite()) {
            $optionsToPreserve[] = CloneOptions::WPSTG_CLONE_SETTINGS_KEY;
        }

        // Preserve freemius options on the production site if present.
        if ($this->freemiusHelper->hasFreemiusOptions()) {
            $optionsToPreserve = array_merge($optionsToPreserve, $this->freemiusHelper->getFreemiusOptions());
        }

        $optionsToPreserve = apply_filters('wpstg_preserved_options', $optionsToPreserve);
        $this->optionPreservationHandler->setProductionDb($this->productionDb);
        $likeStatement     = $this->optionPreservationHandler->getLikeStatement($optionsToPreserve);
        $productionOptions = $this->optionPreservationHandler->getOptionsDataToPreserve($likeStatement, $this->prodOptionsTable);

        if (empty($productionOptions)) {
            return true;
        }

        // Delete any preserve data from pushed wpstgtmp_options table that already exist to insert the actually "preserved data" in the next step and not get any conflicts
        $this->optionPreservationHandler->deleteFromTable($likeStatement, $this->tmpOptionsTable);

        // Create insert preserved data queries for wpstgtmp_options tables
        $sql = $this->optionPreservationHandler->createInsertQuery($productionOptions, $this->tmpOptionsTable);

        $this->debugLog("Preserve values " . json_encode($productionOptions));

        $this->executeSql($sql);

        return true;
    }
}
