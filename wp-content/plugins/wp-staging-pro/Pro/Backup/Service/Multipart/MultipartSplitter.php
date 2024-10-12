<?php

namespace WPStaging\Pro\Backup\Service\Multipart;

use wpdb;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Entity\MultipartMetadata;
use WPStaging\Backup\Service\Compressor;
use WPStaging\Backup\Service\Multipart\MultipartSplitInterface;
use WPStaging\Backup\Task\Tasks\JobBackup\DatabaseBackupTask;

class MultipartSplitter implements MultipartSplitInterface
{
    public function setBackupPartInfo(JobBackupDataDto $jobDataDto, Compressor $compressor)
    {
        $backupPartInfo = $compressor->getFinalizeBackupInfo();
        $jobDataDto->addMultipartFileInfo($backupPartInfo);
    }

    public function setupCompressor(JobBackupDataDto $jobDataDto, Compressor $compressor, $identifier, $stepsSet)
    {
        $compressor->setCategory($identifier);
        if ($stepsSet) {
            $indices = $jobDataDto->getFileBackupIndices();
            if (array_key_exists($identifier, $indices)) {
                $compressor->setCategoryIndex($indices[$identifier]);
                return;
            }
        }

        $compressor->setCategoryIndex(0);
    }

    public function maybeIncrementBackupFileIndex(JobBackupDataDto $jobDataDto, Compressor $compressor, $identifier, $path)
    {
        if (!$jobDataDto->getIsMultipartBackup()) {
            return;
        }

        $fileSize = filesize($path);
        $maxPartSize = $jobDataDto->getMaxMultipartBackupSize();
        if (!$compressor->doExceedMaxPartSize($fileSize, $maxPartSize)) {
            return;
        }

        $backupPartInfo = $compressor->getFinalizeBackupInfo();
        $jobDataDto->addMultipartFileInfo($backupPartInfo);

        $index = 0;
        $fileBackupIndices = $jobDataDto->getFileBackupIndices();
        if (array_key_exists($identifier, $fileBackupIndices)) {
            $index = $fileBackupIndices[$identifier];
        }

        $fileBackupIndices[$identifier] = $index + 1;
        $jobDataDto->setFileBackupIndices($fileBackupIndices);
        $compressor->setCategoryIndex($fileBackupIndices[$identifier]);
    }

    public function updateMultipartMetadata(JobBackupDataDto $jobDataDto, BackupMetadata $backupMetadata, $category, $categoryIndex)
    {
        $splitMetadata = $backupMetadata->getMultipartMetadata();
        $splitMetadata = empty($splitMetadata) ? new MultipartMetadata() : $splitMetadata;
        $splitMetadata->setTotalFiles($jobDataDto->getFilesInPart($category, $categoryIndex));
        $backupMetadata->setMultipartMetadata($splitMetadata);
    }

    public function incrementFileCountInPart(JobBackupDataDto $jobDataDto, $category, $categoryIndex)
    {
        if ($jobDataDto->getIsMultipartBackup()) {
            $filesCount = $jobDataDto->getFilesInPart($category, $categoryIndex);
            $jobDataDto->setFilesInPart($category, $categoryIndex, $filesCount + 1);
        }
    }

    public function setupDatabaseFilename(JobBackupDataDto $jobDataDto, $wpdb, $cacheDirectory, $partFilename)
    {
        $currentPartIndex = $jobDataDto->getMaxDbPartIndex();

        $databaseFileLocation = $cacheDirectory . $partFilename;

        // create database file with comments for parts
        if (!file_exists($databaseFileLocation) && $currentPartIndex !== 0) {
            $this->createDatabasePart($wpdb, $databaseFileLocation, $currentPartIndex);
        }

        $multipartFilesInfo = $jobDataDto->getMultipartFilesInfo();
        $destinationFiles = array_map(function ($backupFile) {
            return $backupFile['destination'];
        }, $multipartFilesInfo);

        if (in_array($partFilename, $destinationFiles)) {
            return;
        }

        $jobDataDto->setDatabaseFile($databaseFileLocation);

        $jobDataDto->addMultipartFileInfo([
            'category' => DatabaseBackupTask::PART_IDENTIFIER,
            'index' => $currentPartIndex,
            'filePath' => $databaseFileLocation,
            'destination' => $partFilename,
            'status' => 'Pending',
            'sizeBeforeAddingIndex' => 0
        ]);
    }

    /**
     * @param wpdb   $wpdb
     * @param string $databaseFileLocation
     * @param int    $partNo
     */
    private function createDatabasePart($wpdb, $databaseFileLocation, $partNo)
    {
        $content = <<<SQL
-- WP Staging SQL Backup Dump
-- https://wp-staging.com/
--
-- Host: {$wpdb->dbhost}
-- Database: {$wpdb->dbname}
-- Part No: {$partNo}
-- Class: WPStaging\Backup\Service\Database\Exporter\RowsExporter
--
SQL;
        file_put_contents($databaseFileLocation, $content);
    }
}
