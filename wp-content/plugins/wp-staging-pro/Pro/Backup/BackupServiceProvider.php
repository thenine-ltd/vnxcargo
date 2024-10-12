<?php

namespace WPStaging\Pro\Backup;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Backup\Job\AbstractJob;
use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Backup\Job\JobRestoreProvider;
use WPStaging\Backup\Service\BackupSigner;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Backup\Service\Compression\CompressionInterface;
use WPStaging\Backup\Service\Database\Exporter\AbstractExporter;
use WPStaging\Backup\Service\Database\Exporter\DDLExporterProvider;
use WPStaging\Backup\Service\Database\Exporter\RowsExporterProvider;
use WPStaging\Backup\Service\Database\Importer\DatabaseSearchReplacerInterface;
use WPStaging\Backup\Service\Database\Importer\SubsiteManagerInterface;
use WPStaging\Backup\Service\Multipart\MultipartInjection;
use WPStaging\Backup\Service\Multipart\MultipartSplitInterface;
use WPStaging\Backup\Service\ZlibCompressor;
use WPStaging\Backup\Task\Tasks\JobBackup\SignBackupTask;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Pro\Backup\Ajax\ManageSchedules;
use WPStaging\Pro\Backup\Ajax\RemoteUpload;
use WPStaging\Pro\Backup\Ajax\CloudFileList;
use WPStaging\Pro\Backup\Ajax\RemoteUpload\PrepareRemoteUpload;
use WPStaging\Pro\Backup\Dto\Job\JobRemoteUploadDataDto;
use WPStaging\Pro\Backup\Job\Jobs\JobBackup;
use WPStaging\Pro\Backup\Job\Jobs\JobRemoteUpload;
use WPStaging\Pro\Backup\Job\Jobs\JobRestore;
use WPStaging\Pro\Backup\Service\BackupSigner as ProBackupSigner;
use WPStaging\Pro\Backup\Service\Compression\ZlibService;
use WPStaging\Pro\Backup\Service\Database\Exporter\DDLExporter;
use WPStaging\Pro\Backup\Service\Database\Exporter\RowsExporter;
use WPStaging\Pro\Backup\Service\Database\Importer\DatabaseSearchReplacer;
use WPStaging\Pro\Backup\Service\Database\Importer\SubsiteManager;
use WPStaging\Pro\Backup\Service\Multipart\MultipartSplitter;
use WPStaging\Pro\Backup\Storage\StoragesServiceProvider;
use WPStaging\Pro\Backup\Task\Tasks\JobRestore\RestoreDatabaseTask;

/**
 * Class BackupServiceProvider
 * @package WPStaging\Pro\Backup
 *
 * This class is used to register all the services related to the Backup feature that are PRO only features like
 * Multisite Support, Multipart Backups, Remote Storages, Migration, Multiple Backup Schedules etc etc
 */
class BackupServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        $this->container->when(JobBackup::class)
                ->needs(JobDataDto::class)
                ->give(JobBackupDataDto::class);

        $this->container->when(JobRestore::class)
                ->needs(JobDataDto::class)
                ->give(JobRestoreDataDto::class);

        $this->container->when(ZlibCompressor::class)
                ->needs(CompressionInterface::class)
                ->give(ZlibService::class);

        $this->container->when(JobRemoteUpload::class)
                ->needs(JobDataDto::class)
                ->give(JobRemoteUploadDataDto::class);

        $this->container->register(StoragesServiceProvider::class);

        $container = $this->container;

        $this->container->when(JobBackupProvider::class)
                        ->needs(AbstractJob::class)
                        ->give(function () use (&$container) {
                            return $container->make(JobBackup::class);
                        });

        $this->container->when(JobRestoreProvider::class)
                        ->needs(AbstractJob::class)
                        ->give(function () use (&$container) {
                            return $container->make(JobRestore::class);
                        });

        $this->container->when(DDLExporterProvider::class)
                        ->needs(AbstractExporter::class)
                        ->give(function () use (&$container) {
                            return $container->make(DDLExporter::class);
                        });

        $this->container->when(RowsExporterProvider::class)
                        ->needs(AbstractExporter::class)
                        ->give(function () use (&$container) {
                            return $container->make(RowsExporter::class);
                        });

        foreach (MultipartInjection::MULTIPART_CLASSES as $classId) {
            $this->container->when($classId)
                            ->needs(MultipartSplitInterface::class)
                            ->give(MultipartSplitter::class);
        }

        $this->container->when(RestoreDatabaseTask::class)
                        ->needs(DatabaseSearchReplacerInterface::class)
                        ->give(DatabaseSearchReplacer::class);

        $this->container->when(DatabaseImporter::class)
                        ->needs(SubsiteManagerInterface::class)
                        ->give(SubsiteManager::class);

        $this->container->when(SignBackupTask::class)
                        ->needs(BackupSigner::class)
                        ->give(ProBackupSigner::class);
    }

    protected function addHooks()
    {
        add_action('wp_ajax_wpstg--backups-edit-schedule', $this->container->callback(ManageSchedules::class, 'editSchedule'), 10, 1); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups-edit-schedule-modal', $this->container->callback(ManageSchedules::class, 'editScheduleModal'), 10, 1); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        add_action('wp_ajax_wpstg--backups--prepare-remote-upload', $this->container->callback(PrepareRemoteUpload::class, 'ajaxPrepare')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--remote-upload', $this->container->callback(RemoteUpload::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--storage--list', $this->container->callback(CloudFileList::class, 'getStorageList')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }
}
