<?php

namespace WPStaging\Pro\Backup\Service\Database\Exporter;

use WPStaging\Backup\Service\Database\Exporter\RowsExporter as BaseRowsExporter;

class RowsExporter extends BaseRowsExporter
{
    /**
     * @param string $tableName
     * @return string
     */
    protected function getPrefixedTableName(string $tableName): string
    {
        if (!is_multisite()) {
            return parent::getPrefixedTableName($tableName);
        }

        if (!$this->isNetworkSiteBackup) {
            return parent::getPrefixedTableName($tableName);
        }

        $basePrefix = $this->database->getBasePrefix();
        if ($tableName === $basePrefix . 'users' || $tableName === $basePrefix . 'usermeta') {
            return $this->getPrefixedBaseTableName($tableName);
        }

        return parent::getPrefixedTableName($tableName);
    }

    /**
     * @return string
     */
    protected function getPrefix(): string
    {
        if (!is_multisite()) {
            return $this->database->getPrefix();
        }

        if (!$this->isNetworkSiteBackup) {
            return $this->database->getBasePrefix();
        }

        return $this->database->getPrefixBySubsiteId($this->subsiteBlogId);
    }

    /**
     * @param string $value
     * @return bool
     */
    protected function isOtherSitePrefixValue(string $value): bool
    {
        if (!is_multisite()) {
            return false;
        }

        if (!$this->isNetworkSiteBackup) {
            return false;
        }

        if ($this->database->getPrefix() === $this->database->getBasePrefix()) {
            return false;
        }

        return strpos($value, $this->database->getBasePrefix()) === 0 && strpos($value, $this->database->getPrefix()) !== 0;
    }
}
