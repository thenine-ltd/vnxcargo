<?php

namespace WPStaging\Backend\Pro\Modules\Jobs\Copiers;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilesystemExceptions;
use WPStaging\Framework\SiteInfo;

/**
 * Class Copier
 *
 * Abstract class for copying Plugins and Themes.
 *
 * @package WPStaging\Backend\Pro\Modules\Jobs
 */
abstract class Copier
{
    /** @var string */
    const PREFIX_TEMP = 'wpstg-tmp-';

    /** @var string */
    const PREFIX_BACKUP = 'wpstg-bak-';

    /** @var string */
    const TYPE_PLUGIN = 'plugin';

    /** @var string */
    const TYPE_THEME = 'theme';

    /** @var string The path to the themes directory. */
    protected $themesDir;

    /** @var string The path to the plugins directory. */
    protected $pluginsDir;

    /** @var string */
    protected $tmpThemesDir;

    /** @var string */
    protected $tmpPluginsDir;

    /** @var array */
    protected $errors = [];

    /**
     * @var Filesystem The filesystem is private so that only the Copier can use it.
     *                 This allows the copier to run safety checks before passing the
     *                 commands to the Filesystem.
     */
    protected $filesystem;

    /** @var SiteInfo */
    protected $siteInfo;

    /** @var string */
    private $itemName;

    /** @var string */
    private $basePath;

    /** @var string */
    private $tmpPath;

    /** @var string */
    private $backupPath;

    /** @var string */
    private $logTitle;

    /** @var bool */
    private $isFile;

    public function __construct(Filesystem $fileSystem, Directory $directory, SiteInfo $siteInfo)
    {
        $this->filesystem    = $fileSystem;
        $this->siteInfo      = $siteInfo;
        $this->themesDir     = $directory->getActiveThemeParentDirectory();
        $this->pluginsDir    = $directory->getPluginsDirectory();
        $this->tmpPluginsDir = $directory->getPluginsTmpDirectory();
        $this->tmpThemesDir  = $directory->getThemesTmpDirectory();
    }

    /** @return string[] */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return void
     * @throws \RuntimeException
     */
    public function copy()
    {
        $destDir = '';
        $tempDir = '';

        $copierType = $this->getCopierType();
        if ($copierType === self::TYPE_PLUGIN) {
            $destDir = $this->pluginsDir;
            $tempDir = $this->tmpPluginsDir;

            $this->logTitle = 'Plugin';
        } elseif ($copierType === self::TYPE_THEME) {
            $destDir = $this->themesDir;
            $tempDir = $this->tmpThemesDir;

            $this->logTitle = 'Theme';
        } else {
            throw new \RuntimeException('Unknown copier type: ' . $copierType);
        }

        if (!is_dir($tempDir)) {
            return;
        }

        $iterator = $this->filesystem
                        ->setRecursive(false)
                        ->setDotSkip()
                        ->setDirectory($tempDir)
                        ->get();

        foreach ($iterator as $item) {
            $this->itemName = $item->getFilename();
            // If it is index.php let remove it
            if ($this->itemName === 'index.php') {
                @unlink($item->getPathname());
                continue;
            }

            // If it is a backup path, let skip
            if (strpos($this->itemName, Copier::PREFIX_BACKUP) === 0) {
                continue;
            }

            $this->basePath   = $destDir . $this->itemName;
            $this->tmpPath    = $item->getPathname();
            $this->backupPath = $tempDir . Copier::PREFIX_BACKUP . $this->itemName;
            $this->isFile     = $item->isFile();

            if (!$this->backupItem()) {
                $this->errors[] = sprintf('%s Handler: Skipping item %s. Please copy it manually from staging to live via FTP!', $this->logTitle, $this->itemName);
                $this->removeTmpItem();
                continue;
            }

            if (!$this->activateTmpItem()) {
                $this->errors[] = sprintf('%s Handler: Skipping item %s Can not activate it. Please copy it manually from staging to live via FTP.', $this->logTitle, $this->itemName);
                $this->restoreBackupItem();
                continue;
            }

            if (!$this->removeBackupItem()) {
                $this->errors[] = sprintf('%s Handler: Can not remove backup item: %s. Please remove it manually via wp-admin > plugins|themes or via FTP.', $this->logTitle, $this->itemName);
                continue;
            }
        }
    }

    /**
     * Clean the temp directory
     * @return void
     */
    public function cleanup()
    {
        $tempDir    = '';
        $copierType = $this->getCopierType();
        if ($copierType === self::TYPE_PLUGIN) {
            $tempDir  = $this->tmpPluginsDir;
        } elseif ($copierType === self::TYPE_THEME) {
            $tempDir  = $this->tmpThemesDir;
        } else {
            throw new \RuntimeException('Unknown copier type: ' . $copierType);
        }

        if ($this->siteInfo->isBitnami()) {
            $tempDir = wp_normalize_path(realpath($tempDir));
        }

        if (!is_dir($tempDir)) {
            return;
        }

        $iterator = null;

        try {
            $iterator = $this->filesystem
                ->setRecursive(false)
                ->setDirectory($tempDir)
                ->setDotSkip()
                ->get();
        } catch (FilesystemExceptions $ex) {
            $this->errors[] = $ex->getMessage();
            return;
        }

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                unlink($item->getPathname());
                continue;
            }

            if ($item->isDir()) {
                $this->rmDir($item->getPathname());
            }
        }

        if (is_dir($tempDir)) {
            $this->rmDir($tempDir);
        }
    }

    /**
     * Make sure we are renaming or removing only sub directories of
     * wp-content/plugins or wp-content/themes.
     *
     * @param string $path The full path to be renamed or removed.
     * @param bool   $isInTemp
     *
     * @return bool Whether given path is allowed to be renamed or removed.
     */
    protected function isAllowedToRenameOrRemove(string $path, bool $isInTemp = false): bool
    {
        $realPath = wp_normalize_path(realpath($path));

        if ($realPath === false) {
            return false;
        }

        $pluginsDir = $isInTemp ? $this->tmpPluginsDir : $this->pluginsDir;
        $themesDir  = $isInTemp ? $this->tmpThemesDir : $this->themesDir;
        if ($this->siteInfo->isBitnami()) {
            $pluginsDir = wp_normalize_path(realpath($pluginsDir));
            $themesDir  = wp_normalize_path(realpath($themesDir));
        }

        $isInPluginsFolder = strpos($realPath, $pluginsDir) === 0;
        $isInThemesFolder  = strpos($realPath, $themesDir) === 0;

        return $isInPluginsFolder || $isInThemesFolder;
    }

    /**
     * @param string $fullPath
     *
     * @return bool Whether given path is writable.
     */
    protected function isWritable(string $fullPath): bool
    {
        if ($this->isFile && is_writable($fullPath)) {
            return true;
        }

        return is_dir($fullPath) && is_writable($fullPath);
    }

    /**
     * @param string $fullPath
     * @return void
     */
    protected function rm(string $fullPath)
    {
        if ($this->isFile && $this->isAllowedToRenameOrRemove($fullPath, $isInTemp = true)) {
            unlink($fullPath);
            return;
        }

        $this->rmDir($fullPath);
    }

    /**
     * @param string $fullPath
     * @return void
     */
    protected function rmDir(string $fullPath)
    {
        if (!$this->isAllowedToRenameOrRemove($fullPath, $isInTemp = true)) {
            $this->errors[] = 'Trying to remove a file/folder that is outside the expected path: ' . $fullPath;

            return;
        }

        try {
            $this->filesystem->setRecursive()->delete($fullPath);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();

            return;
        }
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return bool Whether the rename was successful or not.
     */
    protected function rename(string $from, string $to): bool
    {
        if (!$this->isAllowedToRenameOrRemove($from) && !$this->isAllowedToRenameOrRemove($from, $isInTemp = true)) {
            $this->errors[] = 'Trying to rename a file/folder that is outside the expected path: ' . $from;

            return false;
        }

        return @rename($from, $to);
    }

    /**
     * @return string
     */
    protected function getCopierType()
    {
        throw new \RuntimeException('Method getCopierType() must be implemented.');
    }

    /** @return bool */
    private function activateTmpItem(): bool
    {
        if (!$this->isWritable($this->tmpPath)) {
            $this->errors[] = sprintf('%s Handler: Temp directory does not exist or is not writable: %s', $this->logTitle, $this->tmpPath);

            return false;
        }

        if (!$this->isWritable($this->tmpPath) || !$this->rename($this->tmpPath, $this->basePath)) {
            $this->errors[] = sprintf('%s Handler: Can not activate item: %s', $this->logTitle, $this->itemName);

            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @todo Allow user to delete all wpstg-bak plugins after pushing
     */
    private function backupItem(): bool
    {
        // Nothing to backup on prod site
        if (!file_exists($this->basePath)) {
            return true;
        }

        if ($this->isWritable($this->backupPath)) {
            $this->rm($this->backupPath);
        }

        if (!$this->isWritable($this->basePath)) {
            $this->errors[] = sprintf('%s Handler: Can not backup item: %s. Path not writeable.', $this->logTitle, $this->basePath);

            return false;
        }

        if (!$this->rename($this->basePath, $this->backupPath)) {
            $this->errors[] = sprintf('%s Handler: Can not backup item: %s. Rename failed.', $this->logTitle, $this->itemName);

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function restoreBackupItem(): bool
    {
        if (!$this->isWritable($this->backupPath)) {
            $this->errors[] = sprintf('%s Handler: Can not restore backup item: %s. Path is not writeable.', $this->logTitle, $this->backupPath);

            return false;
        }

        if (!$this->rename($this->backupPath, $this->basePath)) {
            $this->errors[] = sprintf('%s Handler: Can not restore backup item: %s. Rename failed.', $this->logTitle, $this->itemName);

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function removeTmpItem(): bool
    {
        if ($this->isWritable($this->tmpPath)) {
            $this->rm($this->tmpPath);

            return true;
        }

        $this->errors[] = sprintf('%s Handler: Can not remove temp item: %s. Path is not writeable. Remove it manually via FTP.', $this->logTitle, $this->tmpPath);

        return false;
    }

    /**
     * @return bool
     */
    private function removeBackupItem(): bool
    {
        // No backup to delete on prod site
        if (!is_dir($this->backupPath)) {
            return true;
        }

        if ($this->isWritable($this->backupPath)) {
            $this->rm($this->backupPath);

            return true;
        }

        $this->errors[] = sprintf('%s Handler: Can not remove backup item: %s. Path is not writeable. Remove it manually via FTP.', $this->logTitle, $this->backupPath);

        return false;
    }
}
