<?php

namespace WPStaging\Pro\Backup\Service\Database\Importer;

use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Exceptions\WPStagingException;

class SubsiteUsersImporter
{
    /** @var string[] */
    const USERS_TABLES = [
        'users',
        'usermeta'
    ];

    /** @var TableService */
    protected $tableService;

    /** @var string */
    protected $tmpPrefix;

    /** @var string */
    protected $basePrefix;

    /** @var string */
    protected $dropPrefix;

    /** @var string */
    protected $copyPostfix;

    /**
     * @param TableService $tableService
     */
    public function __construct(TableService $tableService)
    {
        $this->tableService = $tableService;
        $this->basePrefix   = $this->tableService->getDatabase()->getBasePrefix();
    }

    /**
     * @param string $tmpPrefix
     * @param string $dropPrefix
     * @param string $copyPostfix
     * @return void
     */
    public function setup(string $tmpPrefix, string $dropPrefix, string $copyPostfix)
    {
        $this->tmpPrefix   = $tmpPrefix;
        $this->dropPrefix  = $dropPrefix;
        $this->copyPostfix = $copyPostfix;
    }

    /**
     * @param string $basePrefix
     * @return void
     */
    public function setBasePrefix(string $basePrefix)
    {
        $this->basePrefix = $basePrefix;
    }

    /** @return TableService */
    public function getTableService(): TableService
    {
        return $this->tableService;
    }

    /**
     * Clone users table of the networks
     * wp_users -> wpstgtmp_users_copy
     * wp_usermeta -> wpstgtmp_usermeta_copy
     * @throws WPStagingException
     */
    public function cloneUsersTables()
    {
        foreach (self::USERS_TABLES as $table) {
            $baseTable    = $this->basePrefix . $table;
            $tmpCopyTable = $this->tmpPrefix . $table . $this->copyPostfix;
            $result = $this->tableService->cloneTableWithoutData($baseTable, $tmpCopyTable);
            if (!$result) {
                throw new WPStagingException("Failed to clone table: $baseTable to $tmpCopyTable");
            }
        }
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return bool
     */
    public function importUsers(int $offset, int $limit): bool
    {
        $tmpUserCopyTable = $this->tmpPrefix . 'users' . $this->copyPostfix;
        $tmpUserTable     = $this->tmpPrefix . 'users';

        $query = <<<SQL
INSERT INTO $tmpUserCopyTable (user_login, user_pass, user_nicename, user_email, user_url, user_registered, user_activation_key, user_status, display_name)
SELECT u2.user_login, u2.user_pass, u2.user_nicename, u2.user_email, u2.user_url, u2.user_registered, u2.user_activation_key, u2.user_status, u2.display_name
FROM $tmpUserTable AS u2
LEFT JOIN $tmpUserCopyTable AS u1 ON (CONVERT(u2.user_login USING utf8) = CONVERT(u1.user_login USING utf8))
WHERE u1.ID IS NULL
LIMIT $limit OFFSET $offset;
SQL;

        $result = $this->tableService->getDatabase()->exec($query);

        return $result !== false;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return bool
     */
    public function importUserMeta(int $offset, int $limit): bool
    {
        $tmpUserCopyTable = $this->tmpPrefix . 'users' . $this->copyPostfix;
        $tmpUserTable     = $this->tmpPrefix . 'users';
        $tmpMetaCopyTable = $this->tmpPrefix . 'usermeta' . $this->copyPostfix;
        $tmpMetaTable     = $this->tmpPrefix . 'usermeta';

        $query = <<<SQL
INSERT INTO $tmpMetaCopyTable (user_id, meta_key, meta_value)
SELECT u1.id, m2.meta_key, m2.meta_value
FROM $tmpMetaTable AS m2
INNER JOIN $tmpUserTable AS u2 ON (m2.user_id = u2.ID)
INNER JOIN $tmpUserCopyTable AS u1 ON (CONVERT(u2.user_login USING utf8) = CONVERT(u1.user_login USING utf8))
LEFT JOIN $tmpMetaCopyTable AS m1 ON (m1.user_id = u1.ID AND CONVERT(m2.meta_key USING utf8) = CONVERT(m1.meta_key USING utf8))
LIMIT $limit OFFSET $offset;
SQL;

        $result = $this->tableService->getDatabase()->exec($query);

        return $result !== false;
    }

    /**
     * Replace users table i.e. wpstgtmp_users_copy to wpstgtmp_users, wpstgtmp_usermeta_copy to wpstgtmp_usermeta
     * @throws WPStagingException
     */
    public function replaceUsersTable()
    {
        foreach (self::USERS_TABLES as $table) {
            $tableToDrop = $this->dropPrefix . $table . $this->copyPostfix;
            $result      = $this->tableService->dropTable($tableToDrop);
            if (!$result) {
                $error = $this->tableService->getLastWpdbError();
                throw new WPStagingException(sprintf('Import Users: Failed to drop: %s, Additional Error: %s', $tableToDrop, $error));
            }

            $tmpTable = $this->tmpPrefix . $table;
            $result   = $this->tableService->renameTable($tmpTable, $tableToDrop);
            if (!$result) {
                $error = $this->tableService->getLastWpdbError();
                throw new WPStagingException(sprintf('Import Users: Failed to rename table to drop: %s to %s, Additional Error: %s', $tmpTable, $tableToDrop, $error));
            }

            $copyTable = $this->tmpPrefix . $table . $this->copyPostfix;
            $result    = $this->tableService->renameTable($copyTable, $tmpTable);
            if (!$result) {
                $error = $this->tableService->getLastWpdbError();
                throw new WPStagingException(sprintf('Import Users: Failed to rename table: %s to %s, Additional Error: %s', $copyTable, $tmpTable, $error));
            }
        }
    }

    /**
     * @param string $tableName
     * @param string $tableUserIdField
     * @return bool
     */
    public function adjustUserIdInOtherTable(string $tableName, string $tableUserIdField): bool
    {
        $tmpUserCopyTable = $this->tmpPrefix . 'users' . $this->copyPostfix;
        $tmpUserTable     = $this->tmpPrefix . 'users';
        $tableToAdjust    = $this->tmpPrefix . $tableName;
        $fieldToUpdate    = "ta.$tableUserIdField";

        $query = <<<SQL
UPDATE $tableToAdjust AS ta
INNER JOIN $tmpUserTable AS u2 ON ($fieldToUpdate = u2.ID)
INNER JOIN $tmpUserCopyTable AS u1 ON (CONVERT(u1.user_login USING utf8) = CONVERT(u2.user_login USING utf8))
SET $fieldToUpdate = u1.ID;
SQL;

        $result = $this->tableService->getDatabase()->exec($query);

        return $result !== false;
    }
}
