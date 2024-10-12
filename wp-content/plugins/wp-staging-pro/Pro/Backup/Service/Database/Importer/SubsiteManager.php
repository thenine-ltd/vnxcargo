<?php

namespace WPStaging\Pro\Backup\Service\Database\Importer;

use OutOfBoundsException;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Database\Importer\SubsiteManagerInterface;

use function WPStaging\functions\debug_log;

class SubsiteManager implements SubsiteManagerInterface
{
    /** @var JobRestoreDataDto */
    private $jobRestoreDataDto;

    /** @var int|null */
    private $lastSubsiteId = null;

    /** @var string */
    private $tmpBasePrefix;

    /** @var bool */
    private $isEntireNetworkBackup = false;

    /**
     * @param JobRestoreDataDto $jobRestoreDataDto
     * @return void
     */
    public function initialize(JobRestoreDataDto $jobRestoreDataDto)
    {
        $this->jobRestoreDataDto     = $jobRestoreDataDto;
        $this->tmpBasePrefix         = $this->jobRestoreDataDto->getTmpDatabasePrefix();
        $this->isEntireNetworkBackup = $this->jobRestoreDataDto->getBackupMetadata()->getBackupType() === BackupMetadata::BACKUP_TYPE_MULTISITE;
        $currentTaskData             = $this->jobRestoreDataDto->getCurrentTaskData();
        $this->lastSubsiteId         = $currentTaskData['currentSubsiteId'] ?? null;
    }

    /** @return void */
    public function updateSubsiteId()
    {
        $currentTaskData = $this->jobRestoreDataDto->getCurrentTaskData();
        $currentTaskData['currentSubsiteId'] = $this->lastSubsiteId;
        $this->jobRestoreDataDto->setCurrentTaskData($currentTaskData);
    }

    /** @return bool */
    public function isTableFromDifferentSubsite(string $query): bool
    {
        if (!is_multisite()) {
            return false;
        }

        if (!$this->isEntireNetworkBackup) {
            return false;
        }

        $currentSubsiteId = null;
        try {
            $currentSubsiteId = $this->extractSubsiteIdFromQuery($query);
        } catch (OutOfBoundsException $e) {
            return false;
        }

        if ($this->lastSubsiteId === null) {
            $this->lastSubsiteId = $currentSubsiteId;
            return false;
        }

        if ($currentSubsiteId === $this->lastSubsiteId) {
            return false;
        }

        $this->lastSubsiteId = $currentSubsiteId;

        return true;
    }

    /**
     * @param string $query
     * @return int
     * @throws OutOfBoundsException
     */
    protected function extractSubsiteIdFromQuery(string $query): int
    {
        preg_match('#^INSERT INTO `(.+?(?=`))` VALUES (\(.+\));$#', $query, $insertIntoExploded);

        if (count($insertIntoExploded) !== 3) {
            debug_log('Unable to extract ID. Maybe not an insert query? Query: ' . $query, 'info', false);
            throw new OutOfBoundsException('Unable to extract ID. The query was logged....');
        }

        $tableName = $insertIntoExploded[1];
        if (strpos($tableName, $this->tmpBasePrefix) !== 0) {
            debug_log('Unable to extract ID. Wrong Prefix. Maybe custom table? Query: ' . $query, 'info', false);
            throw new OutOfBoundsException('Unable to extract ID. The query was logged....');
        }

        $tableName = substr($tableName, strlen($this->tmpBasePrefix));

        if (strpos($tableName, '_') === false) {
            return 1;
        }

        $subsiteId = explode('_', $tableName)[0];

        if (!is_numeric($subsiteId)) {
            return 1;
        }

        return (int)$subsiteId;
    }
}
