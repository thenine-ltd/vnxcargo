<?php

namespace WPStaging\Backend\Pro\Modules\Jobs;

use Countable;
use DirectoryIterator;
use Exception;
use wpdb;
use WPStaging\Backend\Modules\Jobs\Job;
use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils\Directories;
use WPStaging\Backend\Optimizer\Optimizer;
use WPStaging\Framework\Database\SelectedTables;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Utils\WpDefaultDirectories;
use WPStaging\Framework\Utils\Strings;

/**
 * Scans the staging site and its settings that is going to be pushed
 * @todo find out what's the difference between this class and \WPStaging\Backend\Modules\Jobs\Scan and delete the unused one
 * @package WPStaging\Backend\Modules\Jobs
 */
class Scan extends Job
{
    use BackupTrait;

    /**
     * @var array
     */
    private $directories = [];

    /**
     * @var Strings
     */
    private $strUtils;

    /**
     * @var Directories
     */
    private $objDirectories;

    /**
     * Staging site db
     * @var wpdb
     */
    private $db;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Sanitize
     */
    private $sanitize;

    /**
     * @var WpDefaultDirectories
     */
    private $dirUtils;

    /**
     * Upon class initialization
     * @throws Exception
     */
    public function initialize()
    {
        /** @var Filesystem */
        $this->filesystem = WPStaging::make(Filesystem::class);
        $this->sanitize   = WPStaging::make(Sanitize::class);
        $this->dirUtils   = WPStaging::make(WpDefaultDirectories::class);

        $this->strUtils = WPStaging::make(Strings::class);

        $this->start();

        $this->getDb();

        $this->objDirectories = new Directories();

        $this->installOptimizer();

        $this->getStagingTables();

        $this->setTablesToPush();

        $this->buildDirectories();

        $this->buildUploadsDirectories();

        $this->isVersionIdentical();
    }

    /**
     * Select database
     */
    protected function getDb()
    {
        $this->options->existingClones = get_option(Sites::STAGING_SITES_OPTION, []);

        $cloneID = isset($_POST["clone"]) ? $this->sanitize->sanitizeString($_POST['clone']) : '';

        if (
            array_key_exists(strtolower($cloneID), $this->options->existingClones) &&
            !empty($this->options->existingClones[$cloneID]['databaseUser']) &&
            !empty($this->options->existingClones[$cloneID]['databasePassword'])
        ) {
            $currentClone                    = $this->options->existingClones[$cloneID];
            $this->options->current          = $cloneID;
            $this->options->databaseUser     = $currentClone['databaseUser'];
            $this->options->databasePassword = $currentClone['databasePassword'];
            $this->options->databaseDatabase = $currentClone['databaseDatabase'];
            $this->options->databaseServer   = $currentClone['databaseServer'];
            $this->options->databasePrefix   = $currentClone['databasePrefix'];
            $this->options->databaseSsl      = $currentClone['databaseSsl'];

            if ($this->options->databaseSsl && !defined('MYSQL_CLIENT_FLAGS')) {
                // phpcs:disable PHPCompatibility.Constants.NewConstants.mysqli_client_ssl_dont_verify_server_certFound
                define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
            }

            $this->db         = new wpdb($this->options->databaseUser, str_replace("\\\\", "\\", $this->options->databasePassword), $this->options->databaseDatabase, $this->options->databaseServer);
            $this->db->prefix = $this->options->databasePrefix;
        } else {
            $this->db = WPStaging::getInstance()->get("wpdb");
        }

        $this->options->wpBakeryActive = $this->isWpBakeryActive();
        $this->saveOptions();
    }

    /**
     * Start Module
     * @return $this
     * @throws Exception
     */
    public function start()
    {
        // Delete old job files initially
        $this->cloneOptionCache->delete();
        $this->filesIndexCache->delete();

        // Basic Options
        $this->options->root           = wp_normalize_path(WPStaging::getWPpath());
        $this->options->existingClones = array_change_key_case(get_option(Sites::STAGING_SITES_OPTION, []), CASE_LOWER);
        $this->options->current        = null;

        $cloneID = isset($_POST["clone"]) ? $this->sanitize->sanitizeString($_POST['clone']) : '';

        // Get clone data
        if (array_key_exists(strtolower($cloneID), $this->options->existingClones)) {
            $currentClone                      = $this->options->existingClones[$cloneID];
            $this->options->current            = $cloneID;
            $this->options->clone              = strtolower($cloneID);
            $this->options->cloneDirectoryName = preg_replace("#\W+#", '-', strtolower($this->options->clone));
            $this->options->cloneNumber        = $currentClone['number'];
            $this->options->directoryName      = $currentClone['directoryName'];
            $this->options->cloneName          = $currentClone['cloneName'];
            $this->options->url                = $currentClone['url'];
            $this->options->prefix             = $currentClone['prefix'];
            $this->options->path               = $this->filesystem->normalizePath($currentClone['path'], true);
            $this->options->databaseUser       = $currentClone['databaseUser'];
            $this->options->databasePassword   = $currentClone['databasePassword'];
            $this->options->databaseDatabase   = $currentClone['databaseDatabase'];
            $this->options->databaseServer     = $currentClone['databaseServer'];
            $this->options->databasePrefix     = $currentClone['databasePrefix'];
            $this->options->databaseSsl        = isset($currentClone['databaseSsl']) ? (bool)$currentClone['databaseSsl'] : false;
            $this->options->uploadsSymlinked   = isset($currentClone['uploadsSymlinked']) ? (bool)$currentClone['uploadsSymlinked'] : false;
            $this->options->networkClone       = isset($currentClone['networkClone']) ? (bool)$currentClone['networkClone'] : false;
        } else {
            wp_die('Fatal error - The clone does not exist in database. This should not happen.');
        }

        // Tables
        $this->options->clonedTables   = [];

        // Files
        $this->options->totalFiles    = 0;
        $this->options->copiedFiles   = 0;
        $this->options->totalFileSize = 0;

        // Directories
        $this->options->includedDirectories = [];
        $this->options->excludedDirectories = [];
        $this->options->extraDirectories    = [];
        $this->options->directoriesToCopy   = [];
        $this->options->scannedDirectories  = [];

        // Never copy these directories
        $this->options->excludedDirectories = $this->getExcludedDirectories();

        // Job
        $this->options->currentJob  = "";
        $this->options->currentStep = 0;
        $this->options->totalSteps  = 0;

        // Quick and dirty
        $this->assignBackupId($this->options);

        // Save options
        $this->saveOptions();

        return $this;
    }

    protected function setTablesToPush()
    {
        $this->options->tablePushSelection     = $this->getTablePushSelection();
        $this->options->tablesExcludedByFilter = $this->getExcludedCustomTables();
        $this->saveOptions();
    }

    /**
     * Get last saved selection of excluded tables
     * @return array
     */
    protected function getExcludedDirectories()
    {
        if (!empty($this->options->existingClones[$this->options->clone]['excludedDirs'])) {
            return $this->options->existingClones[$this->options->clone]['excludedDirs'];
        }

        return [];
    }

    /**
     * Get last saved table selection for push
     * @return array
     */
    protected function getTablePushSelection()
    {
        $excludedTables = $this->getExcludedCustomTables();
        $pushSelections = [];
        foreach ($this->getTablesSelection() as $table) {
            if (in_array($table, $excludedTables)) {
                continue;
            }

            $pushSelections[] = $table;
        }

        return $pushSelections;
    }

    protected function getTablesSelection()
    {
        if (!empty($this->options->existingClones[$this->options->clone]['tablePushSelection'])) {
            return $this->options->existingClones[$this->options->clone]['tablePushSelection'];
        }

        $tableObjects = $this->options->tables;
        $tables       = [];
        foreach ($tableObjects as $table) {
            if ($this->isTableSelected($table->name)) {
                $tables[] = $table->name;
            }
        }

        return $tables;
    }

    protected function isTableSelected($table)
    {
        $tableWithoutPrefix = wpstg_replace_first_match($this->options->prefix, '', $table);
        preg_match('/^\D*/', $tableWithoutPrefix, $match);
        if (is_multisite() && is_main_site() && !$this->isNetworkClone() && array_filter($match)) {
            return true;
        }

        if (is_multisite() && is_main_site() && !$this->isNetworkClone()) {
            return false;
        }

        if (strpos($table, $this->options->prefix) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Get custom tables to exclude
     * @return array
     */
    protected function getExcludedCustomTables()
    {
        $excludedTables = [];
        $excludedTables = apply_filters('wpstg_push_excluded_tables', $excludedTables);

        $tables = [];
        foreach ($excludedTables as $key => $value) {
            $tables[] = $this->options->prefix . $value;
        }

        return $tables;
    }

    /**
     * Make sure the Optimizer mu plugin is installed before cloning or pushing
     */
    private function installOptimizer()
    {
        $optimizer = new Optimizer();
        $optimizer->installOptimizer();
    }

    /**
     * @param null|string $directories
     * @param bool $forceDisabled
     * @return string
     */
    public function directoryListing($directories = null, $forceDisabled = false)
    {
        if ($directories === null && !in_array('uploads', $this->directories)) {
            $stagingUploadsFolder = $this->options->path . $this->dirUtils->getRelativeUploadPath();
            $currentArray = &$this->directories;
            $currentArray['uploads']["metaData"] = [
                "size" => 0,
                "path" => rtrim($stagingUploadsFolder, '/\\'),
            ];
        }

        if ($directories == null) {
            $directories = $this->directories;
        }

        $output = '';
        foreach ($directories as $name => $directory) {
            // Not a directory, possibly a symlink, therefore we will skip it
            if (!is_array($directory)) {
                continue;
            }

            // Need to preserve keys so no array_shift()
            $data = reset($directory);
            unset($directory[key($directory)]);

            $cloneDir = trailingslashit($this->options->path);
            // These directories checkbox should be checked by default
            $directoriesToCheck = [
                $cloneDir . 'wp-content/plugins',
                $cloneDir . 'wp-content/themes',
                $cloneDir . 'wp-content/uploads',
            ];

            $isChecked = false;
            if (!empty($this->options->excludedDirectories) || $this->strUtils->startsWithAnyFromList($directoriesToCheck, $data["path"])) {
                $isChecked = true;
            }

            // Check if folder is unchecked
            if (in_array($data["path"], $this->options->excludedDirectories)) {
                $isDisabled = true;
            } else {
                $isDisabled = false;
            }

            $idName = uniqid('wpstg');
            $output .= "<div class='wpstg-dir'>";

            $isCheckboxChecked = false;

            if ($isChecked && !$isDisabled && !$forceDisabled) {
                $isCheckboxChecked = true;
            }

            $attributes = [
                'classes' => 'wpstg-check-dir',
            ];

            $disabledClass = '';
            
            if (is_link($data['path'])) {
                $attributes['isDisabled'] = true;
                $disabledClass .= ' wpstg-storage-settings-disabled';
                $output .= Checkbox::render($idName, '', $data["path"], false, $attributes, [], true);
            } else {
                $output .= Checkbox::render($idName, 'selectedDirectories[]', $data["path"], $isCheckboxChecked, $attributes, [], true);
            }

            $output .= "<label class='wpstg-push-expand-dirs{$disabledClass}' for='{$idName}'>{$name}</label>";

            if (isset($data["size"])) {
                $humanSize = $this->utilsMath->formatSize($data["size"]);
                $output .= "<span class='wpstg-size-info'>{$humanSize}</span>";
            }

            if (is_link($data['path'])) {
                $assetsUrl = trailingslashit(WPSTG_PLUGIN_URL);
                $output .= "<span class='wpstg--tooltip wpstg--tooltip-normal'>
                    <img class='wpstg--dashicons wpstg--grey wpstg-symlink-dir-tooltip' src='{$assetsUrl}assets/svg/vendor/dashicons/info-outline.svg' alt='info'/>
                    <span class='wpstg--tooltiptext'>
                        " . __('This option is disabled as uploads directory was symlinked', 'wp-staging') . "
                    </span>
                </span>";
            }

            if (!empty($directory)) {
                $output .= "<div class='wpstg-dir wpstg-subdir wpstg-push'>";
                $output .= $this->directoryListing($directory, $isDisabled);
                $output .= "</div>";
            }

            $output .= "</div>";
        }

        return $output;
    }

    /**
     * Checks if there is enough free disk space to create staging site
     * Returns null when can't run disk_free_space function one way or another
     * @return void
     */
    public function hasFreeDiskSpace()
    {
        $freeSpace = false;
        // Calculate disk space only if disk_free_space function is available
        if (function_exists("disk_free_space")) {
            $freeSpace = @disk_free_space($this->options->path);
        }

        $data = [
            'freespace' => $freeSpace === false ? false : $this->utilsMath->formatSize($freeSpace),
            'usedspace' => $this->utilsMath->formatSize($this->getDirectorySizeInclSubdirs($this->options->path))
        ];

        echo json_encode($data);
        die();
    }

    /**
     * Build directories and main meta data recursively
     */
    protected function buildDirectories()
    {
        $wpcontentDir = $this->options->path . 'wp-content';
        // check if dir exists
        if (!file_exists($wpcontentDir)) {
            wp_die(sprintf(
                Escape::escapeHtml(__('<strong>Fatal error:</strong> Path <code>%s</code> does not exist!  <br><br> Did you move from one server to another recently or changed the location of your site? <br><br>
                ​If you moved your site please reconnect the staging site to the new location and change the path in WP STAGING settings. 
                <br><br>​
                <a href="%s" target="_blank">Learn how to fix this.</a><br>
                <br>
                If you still need help, <a href="%s" target="_blank">please contact us!</a>', 'wp-staging')),
                esc_html($wpcontentDir),
                'https://wp-staging.com/docs/moved-website-to-new-server-can-not-push-staging-site/',
                'https://wp-staging.com/support/'
            ));
        }

        $directories = new DirectoryIterator($wpcontentDir);

        foreach ($directories as $directory) {
            //Not a valid directory. Continue iteration but do not loop through the current further and look for subdirectores
            if (($path = $this->getPath($directory)) === false) {
                continue;
            }

            $this->handleDirectory($path);

            // Get Sub-directories
            $this->getSubDirectories($this->filesystem->normalizePath($directory->getRealPath()));
        }
    }

    /**
     * Populate $this->directory and add the uploads folder if it has been customized
     * @return boolean
     */
    protected function buildUploadsDirectories()
    {
        $relPath = rtrim($this->dirUtils->getRelativeUploadPath(), '/\\');
        $absPath = wpstg_get_abs_upload_dir();
        $path    = $this->options->path . $relPath;

        // Do not do anything if the default uploads folder is used
        if (strpos($absPath, 'wp-content/uploads') !== false) {
            return false;
        }

        $currentArray = &$this->directories;

        $currentArray[$relPath]["metaData"] = [
            "size" => 0,
            "path" => $path,
        ];

        return true;
    }

    /**
     * Get relative Path from $directory and check if some dirs are excluded
     * Example: src/var/www/wordpress/root/staging/wp-content/ returns /staging/wp-content
     *
     * @param DirectoryIterator $directory
     * @return string|false
     */
    protected function getPath(DirectoryIterator $directory)
    {
        if (!$directory->isDir() || $directory->isDot()) {
            return false;
        }

        /*
         * Do not follow root path like src/web/..
         * This must be done before \SplFileInfo->isDir() is used!
         * Prevents open base dir restriction fatal errors
         */
        $realPath = $this->filesystem->normalizePath($directory->getRealPath());
        if (strpos($realPath, $this->options->path) === false) {
            return false;
        }

        $path = str_replace($this->options->path . 'wp-content/', '', $realPath);
        if (empty($path) || strpos($realPath, $this->options->path . 'wp-content/mu-plugins') === 0) {
            return false;
        }

        return $path;
    }

    /**
     * @param string $path
     */
    protected function getSubDirectories($path)
    {
        if (!is_dir($path)) {
            return;
        }

        $directories = new DirectoryIterator($path);

        foreach ($directories as $directory) {
            // Not a valid directory
            if (($path = $this->getPath($directory)) === false) {
                continue;
            }

            $this->handleDirectory($path);
        }
    }

    /**
     * Organizes $this->directories
     * @param string $path
     */
    protected function handleDirectory($path)
    {
        $directoryArray = explode('/', $path);
        $total          = (is_array($directoryArray) || $directoryArray instanceof Countable) ? count($directoryArray) : 0;

        if ($total < 1) {
            return;
        }

        $total        = $total - 1;
        $currentArray = &$this->directories;

        for ($i = 0; $i <= $total; $i++) {
            if (!isset($currentArray[$directoryArray[$i]])) {
                $currentArray[$directoryArray[$i]] = [];
            }

            $currentArray = &$currentArray[$directoryArray[$i]];

            // Attach meta data to the end
            if ($i < $total) {
                continue;
            }

            $fullPath = $this->options->path . 'wp-content' . '/' . $path;
            $size     = $this->getDirectorySize($fullPath);

            $currentArray["metaData"] = [
                "size" => $size,
                "path" => $this->options->path . 'wp-content' . '/' . $path,
            ];
        }
    }


    /**
     * Gets size of given directory
     * @param string $path
     * @return int|null
     */
    protected function getDirectorySize($path)
    {
        if (!isset($this->settings->checkDirectorySize) || $this->settings->checkDirectorySize !== '1') {
            return null;
        }

        return $this->objDirectories->size($path);
    }

    /**
     * Get total size of a directory including all its subdirectories
     * @param string $dir
     * @return int
     */
    protected function getDirectorySizeInclSubdirs($dir)
    {
        $size = 0;
        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->getDirectorySizeInclSubdirs($each);
        }

        return $size;
    }

    public function loadStagingDBTables($onlyStagingPrefix = true)
    {
        $selectedTables = new SelectedTables();
        $selectedTables->setWpdb($this->db, $this->options->prefix);
        if (!$onlyStagingPrefix) {
            $selectedTables->shouldIncludeAllTables(true);
        }

        $currentTables = $selectedTables->getPrefixedTables($this->isNetworkClone(), true);

        $this->options->tables = json_decode(json_encode($currentTables));
    }

    /**
     * Get Database Tables of the current staging site
     * deprecated
     */
    protected function getStagingTables()
    {
        // Load only staging tables not all tables from staging db
        $this->loadStagingDBTables();
    }

    /**
     * Check if WordPress version number of staging and production site is identical before pushing
     * @return boolean
     */
    private function isVersionIdentical()
    {
        // Get version number of wp production
        $versionProduction = get_bloginfo('version');

        // Get version number of wp staging
        $file           = $this->options->path . 'wp-includes/version.php';
        $versionStaging = file_get_contents($file);

        preg_match("/\\\$wp_version.*=.*'(.*)';/", $versionStaging, $matches);

        $error = '';
        if (empty($matches[1])) {
            $error .= __('<strong>Fatal Error: Cannot detect WordPress version of staging site. Open support ticket at support@wp-staging.com </strong><br>', 'wp-staging');
        }

        if (empty($versionProduction)) {
            $error .= '<strong>' . __('Fatal Error: Cannot detect WordPress version of production site. Open support ticket at support@wp-staging.com', 'wp-staging') . '</strong>';
        }

        if (!empty($error)) {
            wp_die(Escape::escapeHtml($error));
        }

        if (version_compare((string)$versionProduction, (string)$matches[1], '!=')) {
            $error = sprintf(
                __('Can not proceed!<br>WordPress version on production and staging site must be identical!<br>Please update WordPress and try again pushing the staging site to live.<p></p>WordPress on Production Site: Version %s <br>WordPress on Staging Site: Version %s<p></p>Read: <a href="https://wp-st​aging.com/docs/wordpress-core-update-not-showing-updating-wordpress-manually/" target="_blank">How to update WordPress automatically on staging site</a>', 'wp-staging')
                ,$versionProduction, $matches[1]
            );
            wp_die(Escape::escapeHtml($error));
        }

        return true;
    }

    /**
     * Check if wp bakery active on the staging site
     *
     * @return bool
     */
    protected function isWpBakeryActive()
    {
        // The index file for WP Bakery Plugin
        $pluginIndexFile = 'js_composer.php';
        return $this->isPluginActive($pluginIndexFile);
    }

    /**
     * Check whether the plugin is active against its index file on the staging site
     *
     * @param string $pluginIndexFile
     * @return bool
     */
    protected function isPluginActive($pluginIndexFile)
    {
        $optionTable = $this->options->prefix . 'options';
        if (!$this->tableExists($optionTable)) {
            return false;
        }

        $stagingActivePlugins = $this->db->get_var("SELECT option_value FROM `$optionTable` WHERE option_name = 'active_plugins';");

        if (empty($stagingActivePlugins)) {
            return false;
        }

        return strpos($stagingActivePlugins, $pluginIndexFile) !== false;
    }

    /**
     * Check if table exists
     * @param string $table
     * @return bool
     */
    private function tableExists($table)
    {
        return !($table != $this->db->get_var("SHOW TABLES LIKE '{$table}'"));
    }
}
