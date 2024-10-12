<?php

namespace WPStaging\Pro\Push\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Backend\Pro\Modules\Jobs\DatabaseTmp;
use WPStaging\Backend\Pro\Modules\Jobs\Copiers\PluginsCopier;
use WPStaging\Backend\Pro\Modules\Jobs\Copiers\ThemesCopier;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Database\TableService;

/**
 * Class Cancel Pushing Process
 * @package WPStaging\Pro\Push\Ajax
 */
class CancelPush
{
    /**
     * @var Directory
     */
    private $directory;

    /**
     * @var PluginsCopier
     */
    private $pluginsCopier;

    /**
     * @var ThemesCopier
     */
    private $themesCopier;

    /**
     * @var Auth
     */
    private $auth;

    public function __construct(Auth $auth, Directory $directory, PluginsCopier $pluginsCopier, ThemesCopier $themesCopier)
    {
        $this->auth = $auth;
        $this->directory = $directory;
        $this->pluginsCopier = $pluginsCopier;
        $this->themesCopier  = $themesCopier;
    }

    /**
     * Cancel push
     */
    public function ajaxCancelPush()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        $this->cleanUpTables();
        $this->cleanUpFiles();

        wp_send_json_success();
    }

    /**
     * Clean up db temp tables
     *
     * @return void
     */
    protected function cleanUpTables()
    {
        /** @var TableService */
        $tableService = WPStaging::make(TableService::class);
        $tableService->dropTablesLike(DatabaseTmp::TMP_PREFIX);
    }

    /**
     * Clean up temp files(plugins, themes, cache)
     *
     * @return void
     */
    protected function cleanUpFiles()
    {
        $this->deleteFiles($this->glob(trailingslashit($this->directory->getPluginUploadsDirectory()), "*.cache.php"));
        $this->deleteFiles($this->glob(trailingslashit($this->directory->getCacheDirectory()), "*.cache.php"));

        // Old cache file extension
        $this->deleteFiles($this->glob(trailingslashit($this->directory->getPluginUploadsDirectory()), "*.cache"));
        $this->deleteFiles($this->glob(trailingslashit($this->directory->getCacheDirectory()), "*.cache"));

        $this->deleteFiles($this->glob(trailingslashit($this->directory->getCacheDirectory()), "*.sql"));
        $this->pluginsCopier->cleanup();
        $this->themesCopier->cleanup();
    }

    /**
     * Delete files
     *
     * @param  array $files list of files to delete
     * @return void
     */
    protected function deleteFiles($files)
    {
        array_map(function ($fileName) {
            return $this->directory->getFileSystem()->delete($fileName);
        }, $files);
    }

    /**
     * Glob that is safe with streams (vfs for example)
     *
     * @param string $directory
     * @param string $filePattern
     * @return array
     */
    private function glob($directory, $filePattern)
    {
        $files = scandir($directory);
        $found = [];
        foreach ($files as $filename) {
            if (fnmatch($filePattern, $filename)) {
                $found[] = $directory . '/' . $filename;
            }
        }

        return $found;
    }
}
