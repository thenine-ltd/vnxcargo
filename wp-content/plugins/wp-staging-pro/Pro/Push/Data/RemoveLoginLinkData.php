<?php

namespace WPStaging\Pro\Push\Data;

class RemoveLoginLinkData extends DBPushService
{
    /**
     * @inheritDoc
     */
    protected function internalExecute()
    {
        if ($this->stagingPrefix === $this->productionDb->prefix) {
            $this->log("Skipping. Prefix of production and live site is the same: {$this->productionDb->prefix}");
            return true;
        }

        // users table has been excluded from pushing process so exit here
        if ($this->isTableExcluded($this->stagingPrefix . 'users')) {
            $this->log("{$this->stagingPrefix}users excluded. Skipping this step");
            return true;
        }

        $tmpUserTable = $this->getTmpPrefix() . 'users';

        if ($this->isTable($tmpUserTable) === false) {
            $this->log('Fatal Error ' . $tmpUserTable . ' does not exist');
            $this->returnException('Fatal Error ' . $tmpUserTable . ' does not exist');
            return false;
        }

        $this->log("Delete temporary magic login link users from {$tmpUserTable}");

        $prepare = $this->productionDb->prepare(
            "DELETE FROM {$tmpUserTable} WHERE user_login LIKE 'wpstg_%%';"
        );
        $resultMetaKeys = $this->productionDb->query($prepare);

        if ($resultMetaKeys === false) {
            $this->log("SQL - " . $prepare);
            $this->log("Failed to delete magic login link users {$this->productionDb->last_error}");
            $this->returnException("Failed to delete magic login link users {$this->productionDb->last_error}");
            return false;
        }

        return true;
    }
}
