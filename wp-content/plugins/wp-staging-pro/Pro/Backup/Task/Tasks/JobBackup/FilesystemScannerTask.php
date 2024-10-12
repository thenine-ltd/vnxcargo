<?php

namespace WPStaging\Pro\Backup\Task\Tasks\JobBackup;

use DirectoryIterator;
use WPStaging\Backup\Task\Tasks\JobBackup\FilesystemScannerTask as BasicFilesystemScannerTask;

class FilesystemScannerTask extends BasicFilesystemScannerTask
{
    /**
     * @return array
     */
    protected function getExcludedDirectories(): array
    {
        $excludedDirs = parent::getExcludedDirectories();

        if (!$this->isBaseNetworkSite()) {
            return $excludedDirs;
        }

        $refresh = true;

        if ($this->jobDataDto->getIsNetworkSiteBackup()) {
            $excludedDirs[] = $this->directory->getUploadsDirectory($refresh) . 'sites';
            return $excludedDirs;
        }

        // Exclude all wp staging uploads directories from subsites
        $sitesDirectory = $this->directory->getUploadsDirectory($refresh) . 'sites';

        if (is_dir($sitesDirectory) === false) {
            return $excludedDirs;
        }

        $uploadsIt = new DirectoryIterator($sitesDirectory);

        foreach ($uploadsIt as $uploadItem) {
            // Early bail: We don't touch links
            if ($uploadItem->isLink() || $this->isDot($uploadItem)) {
                continue;
            }

            if ($uploadItem->isFile()) {
                continue;
            }

            if ($uploadItem->isDir()) {
                $excludedDirs[] = trailingslashit($uploadItem->getPathname()) . 'wp-staging';
            }
        }

        return $excludedDirs;
    }

    /**
     * @return string
     */
    protected function getUploadsDirectory(): string
    {
        if ($this->jobDataDto->getIsNetworkSiteBackup()) {
            switch_to_blog($this->jobDataDto->getSubsiteBlogId());
            $uploadsDir = $this->directory->getUploadsDirectory($refresh = true);
            restore_current_blog();

            return $uploadsDir;
        }

        return $this->directory->getMainSiteUploadsDirectory();
    }

    /**
     * @return bool
     */
    protected function isBaseNetworkSite(): bool
    {
        if (!is_multisite()) {
            return false;
        }

        $blogId = get_current_blog_id();
        return $blogId === 1 || $blogId === 0;
    }
}
