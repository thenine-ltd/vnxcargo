<?php

namespace WPStaging\Backend\Pro\Modules\Jobs;

use WPStaging\Backend\Modules\Jobs\Job;
use WPStaging\Framework\CloningProcess\Data\Job as DataJob;
use WPStaging\Pro\Push\Data\PreserveBlogPublicSettings;
use WPStaging\Pro\Push\Data\PreserveHomeSiteURL;
use WPStaging\Pro\Push\Data\PreserveOptions;
use WPStaging\Pro\Push\Data\PreservePermalinkStructure;
use WPStaging\Pro\Push\Data\PreserveSessionTokenUserMetaTable;
use WPStaging\Pro\Push\Data\PreserveWPStagingInfo;
use WPStaging\Pro\Push\Data\PreserveWPStagingProVersion;
use WPStaging\Pro\Push\Data\RemoveStagingOptions;
use WPStaging\Pro\Push\Data\UpdateActivePluginsOptionsTable;
use WPStaging\Pro\Push\Data\UpdateDomainPathBlogsTable;
use WPStaging\Pro\Push\Data\UpdateDomainPathSiteTable;
use WPStaging\Pro\Push\Data\UpdatePrefixOptionsTable;
use WPStaging\Pro\Push\Data\UpdatePrefixUserMetaTable;
use WPStaging\Pro\Push\Data\RemoveLoginLinkData;

/**
 * Class Data
 * @package WPStaging\Backend\Pro\Modules\Jobs
 */
class Data extends DataJob
{
    /**
     * Initialize
     */
    public function initialize()
    {
        parent::initialize();

        $this->options->destinationDir = '';
        $this->options->mainJob = Job::PUSH;
        $this->tables = [];
    }

    protected function initializeSteps()
    {
        $this->steps = [
            PreserveWPStagingInfo::class, // Preserve entries in options table related to wp staging data
            UpdatePrefixOptionsTable::class, // Update prefix in options table
            UpdatePrefixUserMetaTable::class, // Update prefix in user_meta table
            UpdateActivePluginsOptionsTable::class, // Update active plugins in options table
            PreserveSessionTokenUserMetaTable::class, // Preserve session token in user_meta table
            PreservePermalinkStructure::class, // Preserve permalink structure in options table
            PreserveHomeSiteURL::class, // Preserve home and site url in options table
            PreserveWPStagingProVersion::class, // Preserve wp staging pro version in options table
            PreserveBlogPublicSettings::class, // Preserve blog public settings in options table
            PreserveOptions::class, // Preserve options in options table
            RemoveStagingOptions::class, // Remove staging options in options table
            RemoveLoginLinkData::class, // Remove login link data in options table
        ];

        if ($this->isNetworkClone()) {
            $this->steps[] = UpdateDomainPathSiteTable::class; // Update domain and path in site table
            $this->steps[] = UpdateDomainPathBlogsTable::class; // Update domain and path in blogs table
        }   

    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 12;

        if ($this->isNetworkClone()) {
            $this->options->totalSteps = 14;
        }
    }

    /**
     * Checks Whether There is Any Job to Execute or Not
     * @return bool
     */
    protected function isFinished()
    {
        return
            $this->options->currentStep > $this->options->totalSteps ||
            $this->options->currentStep >= count($this->steps);
    }

    protected function getTables()
    {
        $this->tables = [];
    }
}
