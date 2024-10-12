<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobRestore\NetworkSite;

use RuntimeException;
use WPStaging\Backup\Ajax\Restore\PrepareRestore;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Core\DTO\Settings;
use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Pro\Backup\Dto\Task\Restore\ImportSubsiteUsersTaskDto;
use WPStaging\Pro\Backup\Service\Database\Importer\SubsiteUsersImporter;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * Class ImportSubsiteUsersTask
 *
 * This class will handle import of users from backup to the subsite.
 * It will update the user id of the users if needed in the required tables.
 *
 * @package WPStaging\Pro\Backup\Task\Tasks\JobRestore\NetworkSite
 */
class ImportSubsiteUsersTask extends RestoreTask
{
    const FILTER_TABLES_TO_ADJUST = 'wpstg.restore.subsite.import_users.tables_to_adjust';

    /**
     * Copy postfix for users tables for doing operations
     * @var string
     */
    const COPY_POSTFIX = '_copy';

    /** @var int */
    const STEP_INDEX_CLONE_USERS_TABLES = 0;

    /** @var int */
    const STEP_INDEX_COPY_USERS_TABLE_DATA = 1;

    /** @var int */
    const STEP_INDEX_COPY_USERMETA_TABLE_DATA = 2;

    /** @var int */
    const STEP_INDEX_IMPORT_USERS = 3;

    /** @var int */
    const STEP_INDEX_IMPORT_USERMETA = 4;

    /** @var int */
    const MINIMUM_STEPS = 6;

    /** @var SubsiteUsersImporter */
    protected $subsiteUsersImporter;

    /** @var \wpdb */
    protected $wpdb;

    /**
     * Tmp prefix used in restoring the backup
     * @var string
     */
    protected $tmpPrefix;

    /**
     * Base prefix of the current multisite
     * @var string
     */
    protected $basePrefix;

    /** @var JobRestoreDataDto $jobDataDto */
    protected $jobDataDto;

    /** @var ImportSubsiteUsersTaskDto */
    protected $currentTaskDto;

    /** @return string */
    protected function getCurrentTaskType(): string
    {
        return ImportSubsiteUsersTaskDto::class;
    }

    /** @var array<string,string> */
    protected $tablesToAdjust = [
        'posts'    => 'post_author',
        'comments' => 'user_id',
        'links'    => 'link_owner',
    ];

    public function __construct(
        SubsiteUsersImporter $subsiteUsersImporter,
        JobDataDto $jobDataDto,
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->subsiteUsersImporter = $subsiteUsersImporter;
        // @phpstan-ignore-next-line
        $this->jobDataDto = $jobDataDto;
    }

    public static function getTaskName(): string
    {
        return 'backup_import_users_in_subsite';
    }

    public static function getTaskTitle(): string
    {
        return 'Importing Users in Subsite';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute(): TaskResponseDto
    {
        $this->setupFilter();
        if (!$this->stepsDto->getTotal()) {
            /**
             * Total Steps: 6 + count($this->tablesToAdjust)
             * 1. Clone users tables i.e. wp_users and wp_usermeta to wpstgtmp_users_copy and wpstgtmp_usermeta_copy
             * 2. Copy users table data
             * 3. Copy users meta table data
             * 4. Import users
             * 5. Import users meta
             * (6 to x-1). 6 to step before Last step. Adjust user ids in other required tables
             * x. Last Step. Replace users table i.e. wpstgtmp_users_copy to wpstgtmp_users, wpstgtmp_usermeta_copy to wpstgtmp_usermeta
             */
            $this->stepsDto->setTotal(6 + count($this->tablesToAdjust));
        }

        $this->tmpPrefix = $this->jobDataDto->getTmpDatabasePrefix();

        $this->subsiteUsersImporter->setup(
            $this->tmpPrefix,
            PrepareRestore::TMP_DATABASE_PREFIX_TO_DROP,
            self::COPY_POSTFIX
        );

        $this->basePrefix = $this->subsiteUsersImporter->getTableService()->getDatabase()->getBasePrefix();

        if ($this->stepsDto->getCurrent() === self::STEP_INDEX_CLONE_USERS_TABLES) {
            return $this->cloneUsersTables();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_INDEX_COPY_USERS_TABLE_DATA) {
            return $this->copyUsersTableData();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_INDEX_COPY_USERMETA_TABLE_DATA) {
            return $this->copyUserMetaTableData();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_INDEX_IMPORT_USERS) {
            return $this->importUsers();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_INDEX_IMPORT_USERMETA) {
            return $this->importUsersMeta();
        }

        // Should be done last when everything else goes right
        if ($this->stepsDto->getCurrent() === $this->stepsDto->getTotal() - 1) {
            return $this->replaceUsersTable();
        }

        return $this->adjustUserIdsInOtherTable($this->stepsDto->getCurrent() - (self::MINIMUM_STEPS - 1));
    }

    /**
     * Clone users table of the networks
     * wp_users -> wpstgtmp_users_copy
     * wp_usermeta -> wpstgtmp_usermeta_copy
     * @return TaskResponseDto
     */
    protected function cloneUsersTables(): TaskResponseDto
    {
        try {
            $this->subsiteUsersImporter->cloneUsersTables();
        } catch (WPStagingException $e) {
            $this->logger->warning($e->getMessage());
            $this->stepsDto->finish();
            return $this->generateResponse(false);
        }

        return $this->generateResponse();
    }

    /**
     * @return TaskResponseDto
     */
    protected function copyUsersTableData(): TaskResponseDto
    {
        $baseTable = $this->basePrefix . 'users';
        $tmpTable  = $this->tmpPrefix . 'users' . self::COPY_POSTFIX;
        if (!$this->copyTablesData($baseTable, $tmpTable)) {
            return $this->generateResponse(false);
        }

        return $this->generateResponse();
    }

    /**
     * @return TaskResponseDto
     */
    protected function copyUserMetaTableData(): TaskResponseDto
    {
        $baseTable = $this->basePrefix . 'usermeta';
        $tmpTable  = $this->tmpPrefix . 'usermeta' . self::COPY_POSTFIX;
        if (!$this->copyTablesData($baseTable, $tmpTable)) {
            return $this->generateResponse(false);
        }

        return $this->generateResponse();
    }

    /**
     * @return TaskResponseDto
     */
    protected function importUsers(): TaskResponseDto
    {
        $tmpTable = $this->tmpPrefix . 'users';

        $limit  = $this->prepareDataCopy($tmpTable);
        $offset = $this->currentTaskDto->rowsCopied;

        $result = $this->subsiteUsersImporter->importUsers($offset, $limit);
        if (!$result) {
            $this->logger->warning('Failed to import users from ' . $tmpTable . ' to copy tmp user table');
            $this->stepsDto->finish();
            return $this->generateResponse(false);
        }

        $this->currentTaskDto->rowsCopied = $this->currentTaskDto->rowsCopied + $limit;
        if ($this->currentTaskDto->rowsCopied >= $this->currentTaskDto->totalRows) {
            $this->currentTaskDto->started = false;
            return $this->generateResponse();
        }

        return $this->generateResponse(false);
    }

    /**
     * @return TaskResponseDto
     */
    protected function importUsersMeta(): TaskResponseDto
    {
        $tmpTable  = $this->tmpPrefix . 'usermeta';

        $limit  = $this->prepareDataCopy($tmpTable);
        $offset = $this->currentTaskDto->rowsCopied;

        $result = $this->subsiteUsersImporter->importUserMeta($offset, $limit);
        if (!$result) {
            $this->logger->warning('Failed to import meta from ' . $tmpTable . ' to copy tmp usermeta table');
            $this->stepsDto->finish();
            return $this->generateResponse(false);
        }

        $this->currentTaskDto->rowsCopied = $this->currentTaskDto->rowsCopied + $limit;
        if ($this->currentTaskDto->rowsCopied >= $this->currentTaskDto->totalRows) {
            $this->currentTaskDto->started = false;
            return $this->generateResponse();
        }

        return $this->generateResponse(false);
    }

    /**
     * Replace users table i.e. wpstgtmp_users_copy to wpstgtmp_users, wpstgtmp_usermeta_copy to wpstgtmp_usermeta
     * @return TaskResponseDto
     */
    protected function replaceUsersTable(): TaskResponseDto
    {
        try {
            $this->subsiteUsersImporter->replaceUsersTable();
        } catch (WPStagingException $e) {
            $this->logger->warning($e->getMessage());
            $this->stepsDto->finish();
            return $this->generateResponse(false);
        }

        return $this->generateResponse();
    }

    /**
     * @param int $tableIndex
     *
     * @return TaskResponseDto
     */
    protected function adjustUserIdsInOtherTable(int $tableIndex): TaskResponseDto
    {
        $tablesToAdjust = array_keys($this->tablesToAdjust);
        if (!isset($tablesToAdjust[$tableIndex])) {
            throw new RuntimeException('Invalid table index');
        }

        $otherTable = $tablesToAdjust[$tableIndex];
        $otherField = $this->tablesToAdjust[$otherTable];

        $result = $this->subsiteUsersImporter->adjustUserIdInOtherTable($otherTable, $otherField);
        if (!$result) {
            // Could fails as no adjusted was needed, so let keep on...
            $this->logger->warning('User ids were not adjusted in subsite ' . $otherTable . ' table.');
        }

        return $this->generateResponse();
    }

    protected function copyTablesData(string $srcTable, string $destTable): bool
    {
        $limit = $this->prepareDataCopy($srcTable);

        $result = $this->subsiteUsersImporter->getTableService()->copyTableData($srcTable, $destTable, $this->currentTaskDto->rowsCopied, $limit);
        if (!$result) {
            $this->logger->warning('Failed to copy data from ' . $srcTable . ' to ' . $destTable);
            $this->stepsDto->finish();
            return false;
        }

        $this->currentTaskDto->rowsCopied = $this->currentTaskDto->rowsCopied + $limit;
        if ($this->currentTaskDto->rowsCopied >= $this->currentTaskDto->totalRows) {
            $this->currentTaskDto->started = false;
            return true;
        }

        return false;
    }

    /**
     * @param string $tableName
     * @return int
     */
    protected function prepareDataCopy(string $tableName): int
    {
        if (empty($this->currentTaskDto->started)) {
            $this->currentTaskDto->rowsCopied = 0;
            $this->currentTaskDto->totalRows  = $this->subsiteUsersImporter->getTableService()->getRowsCount($tableName);
            $this->currentTaskDto->started    = true;
        }

        $settings = (object)((new Settings())->setDefault());
        return $settings->queryLimit;
    }

    /** @return void */
    protected function setupFilter()
    {
        $this->tablesToAdjust = Hooks::applyFilters(self::FILTER_TABLES_TO_ADJUST, $this->tablesToAdjust);
    }
}
