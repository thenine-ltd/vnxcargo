<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobRestore;

use WPStaging\Backup\Ajax\Restore\PrepareRestore;
use WPStaging\Pro\Backup\Task\MultisiteRestoreTask;

class UpdateSubsitesDomainAndPathTask extends MultisiteRestoreTask
{
    public static function getTaskName()
    {
        return 'backup_restore_update_domain_and_path';
    }

    public static function getTaskTitle()
    {
        return 'Updating domain and path for subsites';
    }

    public function execute()
    {
        $this->stepsDto->setTotal(1);

        if ($this->jobDataDto->getIsMissingDatabaseFile()) {
            $this->logger->warning(esc_html__('Skipped updating site URL domain and path due to missing database file', 'wp-staging'));
            return $this->generateResponse();
        }

        if ($this->jobDataDto->getIsSameSiteBackupRestore()) {
            $this->logger->info(esc_html__('Skipped updating site URL domain and path as already same', 'wp-staging'));
            return $this->generateResponse();
        }

        $this->adjustDomainPath();
        // Skip if source and current domain and path already same
        if ($this->areDomainAndPathSame() && $this->isSubdomainInstall === is_subdomain_install()) {
            $this->logger->info(esc_html__('Skipped updating site URL domain and path as already same', 'wp-staging'));
            return $this->generateResponse();
        }

        if (!$this->areDomainAndPathSame()) {
            $this->updateSiteTableDomainPath();
        }

        $this->updateBlogsTableDomainPath();

        $this->logger->info(esc_html__('Updating site URL domain and URL path in database finished', 'wp-staging'));

        return $this->generateResponse();
    }

    protected function updateSiteTableDomainPath()
    {
        $tmpSiteTable = PrepareRestore::TMP_DATABASE_PREFIX . 'site';
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$tmpSiteTable} SET domain = %s, path = %s",
                $this->getCurrentNetworkDomain(),
                $this->getCurrentNetworkPath()
            )
        );

        if (!$result) {
            $this->logger->warning(esc_html__("Failed to update Domain and Path in site table", "wp-staging"));
        }
    }

    protected function updateBlogsTableDomainPath()
    {
        $tmpBlogsTable = PrepareRestore::TMP_DATABASE_PREFIX . 'blogs';

        foreach ($this->sites as $blog) {
            $result = $this->wpdb->query(
                $this->wpdb->prepare(
                    "UPDATE {$tmpBlogsTable} SET domain = %s, path = %s WHERE blog_id = %s AND site_id = %s",
                    $blog['adjustedDomain'],
                    $blog['adjustedPath'],
                    $blog['blogId'],
                    $blog['siteId']
                )
            );

            if (!$result) {
                $this->logger->warning(sprintf(esc_html__("Failed to update Domain and Path in blogs table for blog_id: %s and site_id: %s", "wp-staging"), $blog['blogId'], $blog['siteId']));
            }
        }
    }
}
