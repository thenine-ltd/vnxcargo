<?php

namespace WPStaging\Backend\Pro\Modules\Jobs;

use WPStaging\Backend\Modules\Jobs\Data as CloningData;
use WPStaging\Pro\Staging\Data\Steps\MultisiteAddNetworkAdministrators;
use WPStaging\Pro\Staging\Data\Steps\MultisiteUpdateActivePlugins;
use WPStaging\Pro\Staging\Data\Steps\NewAdminAccount;

/**
 * Class CloningProData
 * Used in Pro version only
 * @package WPStaging\Backend\Modules\Jobs
 */
class CloningProData extends CloningData
{
    protected function initializeSteps()
    {
        parent::initializeSteps();

        if ($this->isMultisiteAndPro() && !$this->isNetworkClone()) {
            $this->steps[] = MultisiteUpdateActivePlugins::class; // Merge sitewide active plugins and subsite active plugins
            $this->steps[] = MultisiteAddNetworkAdministrators::class; // Add network administrators to _usermeta
        }

        if ($this->options->useNewAdminAccount) {
            $this->steps[] = NewAdminAccount::class; // Add new admin account
        }
    }

    /**
     * Get a list of tables to copy
     */
    protected function getTables()
    {
        parent::getTables();

        if ($this->isMultisiteAndPro() && !$this->isNetworkClone()) {
            // Add extra global tables from main multisite (wpstg[x]_users and wpstg[x]_usermeta)
            $this->tables[] = $this->options->prefix . 'users';
            $this->tables[] = $this->options->prefix . 'usermeta';
        }
    }

    /**
     * Calculate total steps in this job and assign it to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 8;

        if ($this->isMultisiteAndPro() && !$this->isNetworkClone()) {
            $this->options->totalSteps = 10;
        }

        if ($this->options->useNewAdminAccount) {
            $this->options->totalSteps++;
        }
    }
}
