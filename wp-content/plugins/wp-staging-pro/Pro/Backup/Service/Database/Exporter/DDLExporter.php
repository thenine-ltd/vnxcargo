<?php

namespace WPStaging\Pro\Backup\Service\Database\Exporter;

use WPStaging\Backup\Service\Database\Exporter\DDLExporter as BaseDDLExporter;

class DDLExporter extends BaseDDLExporter
{
    /**
     * @var array
     */
    protected $subsites = [];

    /**
     * @var string
     */
    protected $basePrefix;

    /**
     * @return void
     */
    protected function addUsersTablesForSubsite()
    {
        // Early bail if not network site backup
        if (!$this->isNetworkSiteBackup) {
            return;
        }

        $this->basePrefix = $this->database->getBasePrefix();

        $usersTables = [
            $this->basePrefix . 'users',
            $this->basePrefix . 'usermeta',
        ];

        foreach ($usersTables as $usersTable) {
            if (in_array($usersTable, $this->excludedTables)) {
                continue;
            }

            if (in_array($usersTable, $this->tables)) {
                continue;
            }

            $this->writeQueryCreateTable($usersTable, $isWpTable = true, $isBaseWpTable = true);

            $this->tables[] = $usersTable;
        }
    }

    /**
     * @return void
     */
    protected function filterOtherSubsitesTables()
    {
        // Early bail if not network site backup
        if (!$this->isNetworkSiteBackup) {
            return;
        }

        $blogId = get_current_blog_id();
        if ($blogId !== 1 && $blogId !== 0) {
            return;
        }

        $this->basePrefix = $this->database->getBasePrefix();
        $this->subsites   = get_sites();

        $this->tables = array_filter($this->tables, function ($table) {
            if ($this->isOtherNetworkSiteTable($table)) {
                return false;
            }

            return true;
        });
    }

    /**
     * @param string $tableName
     * @return bool
     */
    protected function isOtherNetworkSiteTable(string $tableName): bool
    {
        foreach ($this->subsites as $subsite) {
            if (strpos($tableName, $this->basePrefix . $subsite->blog_id . '_') === 0) {
                return true;
            }
        }

        // Subsite could be deleted but database may have residual tables
        // Check if table name starts with the pattern base_prefix_#_ where # is a number
        if (preg_match('/^' . $this->basePrefix . '\d+_/', $tableName)) {
            return true;
        }

        return false;
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
     * Replace view identifiers in SQL query i.e.
     * Replace prefix of tables/views used to create this view with tmp prefix
     * Also replace the base prefix with the current subsite prefix for users and usermeta table for subsite clone
     *
     * @param string $sql view create query
     *
     * @return string
     */
    protected function replaceViewIdentifiers($sql)
    {
        if (!$this->isNetworkSiteBackup) {
            return parent::replaceViewIdentifiers($sql);
        }

        $this->basePrefix = $this->database->getBasePrefix();
        $baseTables = [
            $this->basePrefix . 'users',
            $this->basePrefix . 'usermeta',
        ];

        foreach (array_merge($this->tables, $this->views) as $tableName) {
            // Replace base prefix with current subsite prefix for users and usermeta table
            if (in_array($tableName, $baseTables)) {
                $newTableName = $this->replaceBasePrefix($tableName, '{WPSTG_TMP_PREFIX}');
                $sql          = str_ireplace("`$tableName`", "`$newTableName`", $sql);
                continue;
            }

            $newTableName = $this->replacePrefix($tableName, '{WPSTG_TMP_PREFIX}');
            $sql          = str_ireplace("`$tableName`", "`$newTableName`", $sql);
        }

        return $sql;
    }
}
