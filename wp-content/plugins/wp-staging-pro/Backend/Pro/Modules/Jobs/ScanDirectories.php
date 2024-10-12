<?php

namespace WPStaging\Backend\Pro\Modules\Jobs;

use Exception;
use WPStaging\Backend\Modules\Jobs\JobExecutable;
use WPStaging\Backend\Pro\Modules\Jobs\Copiers\Copier;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Traits\FileScanToCacheTrait;
use WPStaging\Framework\Utils\WpDefaultDirectories;

/**
 * Class ScanDirectories
 * Scan the file system for all files and folders to copy
 * @package WPStaging\Backend\Modules\Directories
 *
 * @todo This class need more code DRY and use WPStaging\Framework\Adapter\Directory instead for getting paths
 */
class ScanDirectories extends JobExecutable
{
    use FileScanToCacheTrait;

    /**
     * @var array
     */
    private $files = [];

    /**
     * Total steps to do
     * @var int
     */
    private $total = 4;

    /**
     * File name of the caching file
     * @var string
     */
    private $filename;

    /**
     * @var WpDefaultDirectories
     */
    private $wpDirectories;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /** @var PathIdentifier */
    private $pathAdapter;

    /** @var Directory */
    private $dirAdapter;

    /**
     * @var SiteInfo
     */
    private $siteInfo;

    /**
     * @var string
     */
    private $stagingPath;

    /**
     * @var string
     */
    private $stagingWpContentPath;

    /**
     * Initialize
     */
    public function initialize()
    {
        $this->filename      = $this->getFilesIndexCacheFilePath();
        $this->wpDirectories = new WpDefaultDirectories();
        /** @var Filesystem */
        $this->filesystem  = WPStaging::make(Filesystem::class);
        $this->stagingPath = rtrim($this->filesystem->normalizePath($this->options->path));
        $this->pathAdapter = WPStaging::make(PathIdentifier::class);
        $this->siteInfo    = WPStaging::make(SiteInfo::class);
        $this->dirAdapter  = WPStaging::make(Directory::class);

        $this->stagingWpContentPath = trailingslashit($this->stagingPath) . 'wp-content/';

        if ($this->options->currentStep === 0) {
            // should run only one time.
            $this->options->extraDirectories = array_merge($this->options->extraDirectories, $this->getOtherWpContentFolders());
        }

        $this->filesIndexCache->initWithPhpHeader();
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = $this->total + count($this->options->extraDirectories);
    }

    /**
     * Start Module
     * @return object
     */
    public function start()
    {
        // Execute steps
        $this->run();

        // Save option, progress
        $this->saveProgress();

        return (object) $this->response;
    }

    /**
     * Step 0
     * Get Plugin Files
     */
    public function getStagingPlugins()
    {
        $path          = $this->stagingWpContentPath . 'plugins/';
        $normalizePath = $this->filesystem->normalizePath($path);

        if ($this->isDirectoryExcluded($path)) {
            $this->log('Scanning: Skip ' . $normalizePath);
            return true;
        }

        $files = $this->open($this->filename, 'a');

        $excludeFolders = [
            '**/nbproject',
            '**/wp-staging*', // remove all wp-staging plugins
            '**/wps-hide-login',
            '**/' . Copier::PREFIX_BACKUP . '*',
            '**/' . Copier::PREFIX_TEMP . '*',
        ];
        $excludeFolders = array_merge($this->options->excludedDirectories, $excludeFolders);

        $this->log(sprintf('Scanning %s, its sub-directories and files', $normalizePath));
        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_PLUGINS);
            $this->options->totalFiles += $this->scanToCacheFile($files, $path, true, $excludeFolders, [], $normalizePath);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        $this->close($files);
        return true;
    }

    /**
     * Step 1
     * Get Themes Files
     */
    public function getStagingThemes()
    {
        $path          = $this->stagingWpContentPath . 'themes/';
        $normalizePath = $this->filesystem->normalizePath($path);

        if ($this->isDirectoryExcluded($path)) {
            $this->log('Scanning: Skip ' . $normalizePath);
            return true;
        }

        $files = $this->open($this->filename, 'a');

        $excludeFolders = [
            '**/nbproject',
            '**/' . Copier::PREFIX_BACKUP . '*',
            '**/' . Copier::PREFIX_TEMP . '*',
        ];
        $excludeFolders = array_merge($this->options->excludedDirectories, $excludeFolders);

        $this->log(sprintf('Scanning %s, its sub-directories and files', $normalizePath));
        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_THEMES);
            $this->options->totalFiles += $this->scanToCacheFile($files, $path, true, $excludeFolders, [], $normalizePath);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        $this->close($files);
        return true;
    }

    /**
     * Step 2
     * Get Media Files
     */
    public function getStagingUploads()
    {
        if ($this->isMultisiteAndPro()) {
            // Detect the method, old or new one. Older WP Staging sites used a fixed upload location wp-content/uploads/2019 where the new approach keep the multisites
            $folder = $this->stagingWpContentPath . 'uploads/sites';
            if (is_dir($folder)) {
                $this->getStagingUploadsNew();
            } else {
                $this->getStagingUploadsOld();
            }

            return true;
        }

        $path = trailingslashit($this->stagingPath) . $this->getUploadFolder() . "/";
        if ($this->siteInfo->isWpContentOutsideAbspath()) {
            $path = trailingslashit($this->stagingPath) . "wp-content/uploads/";
        }

        $normalizePath = $this->filesystem->normalizePath($path);

        // Skip it
        if ($this->isDirectoryExcluded($normalizePath)) {
            $this->log('Scanning: Skip ' . $normalizePath);
            return true;
        }

        if (!is_dir($normalizePath)) {
            $this->log('Scanning: Not a valid path ' . $normalizePath);
            return true;
        }

        $files = $this->open($this->filename, 'a');

        $relpath = str_replace($this->stagingPath, '', $normalizePath);

        $excludeFolders = [
            '**/node_modules',
            '**/nbproject',
            $relpath . 'wp-staging',
            $path . 'wp-staging', // Extra rule if WP Content is outside ABSPATH
        ];
        $excludeFolders = array_merge($this->options->excludedDirectories, $excludeFolders);

        $this->log(sprintf('Scanning %s, its sub-directories and files', $normalizePath));

        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_UPLOADS);
            $this->options->totalFiles += $this->scanToCacheFile($files, $path, true, $excludeFolders, [], $normalizePath);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        $this->close($files);
        return true;
    }

     /**
     * Get all files from the upload folder. Old approach 2018
     * This is used only for compatibility reasons and will be removed in the future
     * @deprecated since version 2.7.6
     * @return bool
     */
    public function getStagingUploadsOld()
    {
        $path          = $this->stagingWpContentPath . 'uploads/';
        $normalizePath = $this->filesystem->normalizePath($path);

        if ($this->isDirectoryExcluded($path)) {
            return true;
        }

        if (!is_dir($path)) {
            return true;
        }

        $files = $this->open($this->filename, 'a');

        $excludeFolders = [
            '**/wp-staging',
            '**/node_modules',
            '**/nbproject',
        ];

        $this->log(sprintf('Scanning %s, its sub-directories and files', $normalizePath));

        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_UPLOADS);
            $this->options->totalFiles += $this->scanToCacheFile($files, $path, true, $excludeFolders, [], $normalizePath);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        $this->close($files);
        return true;
    }

    /**
     * Get all files from the upload folder. New approach 2019
     * @return bool
     */
    private function getStagingUploadsNew()
    {
        $path          = trailingslashit($this->stagingPath) . $this->getRelUploadDir();
        $normalizePath = $this->filesystem->normalizePath($path);

        if ($this->isDirectoryExcluded($path)) {
            return true;
        }

        if (!is_dir($path)) {
            return true;
        }

        $files = $this->open($this->filename, 'a');

        $excludeFolders = [
            '**/wp-staging',
            '**/node_modules',
            '**/nbproject',
        ];
        $excludeFolders = array_merge($this->options->excludedDirectories, $excludeFolders);

        $this->log(sprintf('Scanning %s, its sub-directories and files', $normalizePath));

        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_UPLOADS);
            $this->options->totalFiles += $this->scanToCacheFile($files, $path, true, $excludeFolders, [], $normalizePath);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        $this->close($files);
        return true;
    }

    /**
     * Get relative path to upload dir of staging site e.g. /wp-content/uploads/sites/2 for child sites or wp-content/uploads for main network site
     */
    private function getRelUploadDir()
    {
        $uploads     = wp_upload_dir();
        $basedir     = $uploads['basedir'];
        $relativeDir = str_replace(ABSPATH, '', $this->filesystem->normalizePath($basedir, true));
        return trailingslashit($relativeDir);
    }

    /**
     * Get the relative path to uploads folder of the multisite live site e.g. wp-content/blogs.dir/ID/files or wp-content/upload/sites/ID or wp-content/uploads
     * @param string $pathUploadsFolder Absolute path to the uploads folder
     * @param string $subPathName
     * @return string
     */
    private function getRelUploadPath()
    {
        // Check first which structure is used
        $uploads = wp_upload_dir();
        $basedir = $uploads['basedir'];
        $blogId  = get_current_blog_id();
        if (strpos($basedir, 'blogs.dir') === false) {
            // Since WP 3.5
            $getRelUploadPath = $blogId > 1 ?
                    'wp-content/uploads/sites/' . get_current_blog_id() . "/" :
                    'wp-content/uploads/';
        } else {
            // old blog structure before WP 3.5
            $getRelUploadPath = $blogId > 1 ?
                    'wp-content/blogs.dir/' . get_current_blog_id() . '/files/' :
                    'wp-content/';
        }
        return $getRelUploadPath;
    }

    /**
     * Gets WP Content other files than plugins, mu-plugin, themes and uploads from $this->options->includedDirectories.
     *
     * @return array
     */
    private function getOtherWpContentFolders(): array
    {
        $pluginsPath   = $this->stagingWpContentPath . 'plugins';
        $themesPath    = $this->stagingWpContentPath . 'themes';
        $uploadsPath   = $this->stagingWpContentPath . 'uploads';
        $muPluginsPath = $this->stagingWpContentPath . 'mu-plugins';

        $includedDirectories = $this->options->includedDirectories;

        $filteredPaths = array_filter($includedDirectories, function ($path) use ($pluginsPath, $themesPath, $uploadsPath, $muPluginsPath) {
            return (
                strpos($path, $pluginsPath)   === false &&
                strpos($path, $themesPath)    === false &&
                strpos($path, $uploadsPath)   === false &&
                strpos($path, $muPluginsPath) === false
            );
        });

        if (!is_array($filteredPaths)) {
            return [];
        }

        return $filteredPaths;
    }

    /**
     * Step 4 - x
     * Get extra folders of the wp root level
     * Does not collect wp-includes, wp-admin and wp-content folder
     */
    private function getExtraFiles($folder)
    {
        $folder        = rtrim($folder, DIRECTORY_SEPARATOR);
        $normalizePath = $this->filesystem->normalizePath($folder);

        if (!is_dir($normalizePath)) {
            return true;
        }

        $files = $this->open($this->filename, 'a');

        $this->log(sprintf('Scanning %s, its sub-directories and files', $normalizePath));

        if ($this->siteInfo->isWpContentOutsideAbspath()) {
            $dirPrefix = PathIdentifier::IDENTIFIER_WP_CONTENT;
            $rootPath = trailingslashit($this->stagingWpContentPath);
        } else {
            $dirPrefix = PathIdentifier::IDENTIFIER_ABSPATH;
            $rootPath = trailingslashit($this->stagingPath);
        }

        $pluginWpContentDir = rtrim($this->dirAdapter->getPluginWpContentDirectory(), '/\\');

        // Exclude wp-content/wp-staging folder
        $excludedPaths = [
            $this->pathAdapter->transformPathToIdentifiable($pluginWpContentDir),
            PathIdentifier::IDENTIFIER_WP_CONTENT . WPSTG_PLUGIN_DOMAIN, // Extra caution if pluginWpContentDir changed later
        ];

        try {
            $this->setPathIdentifier($dirPrefix);
            $this->options->totalFiles += $this->scanToCacheFile($files, $normalizePath, true, $excludedPaths, [], $rootPath);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        $this->close($files);
        return true;
    }

    /**
     * Closes a file handle
     *
     * @param  resource $handle File handle to close
     * @return bool
     */
    public function close($handle)
    {
        return @fclose($handle);
    }

    /**
     * Opens a file in specified mode
     *
     * @param  string   $file Path to the file to open
     * @param  string   $mode Mode in which to open the file
     * @return resource
     * @throws Exception
     */
    public function open($file, $mode)
    {
        $file_handle = @fopen($file, $mode);
        if ($file_handle === false) {
            $this->returnException(sprintf(__('Unable to open %s with mode %s', 'wp-staging'), $file, $mode));
        }

        return $file_handle;
    }

    /**
     * Write contents to a file
     *
     * @param  resource $handle  File handle to write to
     * @param  string   $content Contents to write to the file
     * @return integer
     * @throws Exception
     * @throws Exception
     */
    public function write($handle, $content)
    {
        $write_result = @fwrite($handle, $content);
        if ($write_result === false) {
            if (( $meta = stream_get_meta_data($handle) )) {
                //$this->returnException(sprintf(__('Unable to write to: %s', 'wp-staging'), $meta['uri']));
                throw new Exception(sprintf(__('Unable to write to: %s', 'wp-staging'), $meta['uri']));
            }
        } elseif ($write_result !== strlen($content)) {
            //$this->returnException(__('Out of disk space.', 'wp-staging'));
            throw new Exception(__('Out of disk space.', 'wp-staging'));
        }

        return $write_result;
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    protected function execute()
    {
        // No job left to execute
        if ($this->isFinished()) {
            $this->prepareResponse(true, false);
            return false;
        }

        if ($this->options->currentStep == 0) {
            $this->getStagingPlugins();
            $this->prepareResponse(false, true);
            return false;
        }

        if ($this->options->currentStep == 1) {
            $this->getStagingThemes();
            $this->prepareResponse(false, true);
            return false;
        }

        if ($this->options->currentStep == 2) {
            $this->getStagingUploads();
            $this->prepareResponse(false, true);
            return false;
        }

        if (isset($this->options->extraDirectories[$this->options->currentStep - $this->total])) {
            $this->getExtraFiles($this->options->extraDirectories[$this->options->currentStep - $this->total]);
            $this->prepareResponse(false, true);
            return false;
        }

        // Not finished - Prepare response
        $this->prepareResponse(false, true);
        return true;
    }

    /**
     * Checks Whether There is Any Job to Execute or Not
     * @return bool
     */
    protected function isFinished()
    {
        return ($this->options->currentStep > $this->options->totalSteps);
    }

    /**
     * Save files
     * @return bool
     */
    protected function saveProgress()
    {
        return $this->saveOptions();
    }

    /**
     * Get files
     * @return void
     */
    protected function getFiles()
    {
        $fileName = $this->getFilesIndexCacheFilePath();

        $fileContent = file_get_contents($fileName);
        if ($fileContent === false) {
            $this->files = [];
            return;
        }

        $this->files = explode(PHP_EOL, $fileContent);
    }

    /**
     * Replace forward slash with current directory separator
     * Windows Compatibility Fix
     * @param string $path Path
     *
     * @return string
     */
    private function sanitizeDirectorySeparator($path)
    {
        $string = str_replace("/", "\\", $path);
        return str_replace('\\\\', '\\', $string);
    }

    /**
     * Check if directory is excluded from scanning
     * @param string $directory
     * @return bool
     */
    protected function isDirectoryExcluded($directory)
    {
        $directory = $this->sanitizeDirectorySeparator($directory);
        foreach ($this->options->excludedDirectories as $excludedDirectory) {
            $excludedDirectory = $this->sanitizeDirectorySeparator($excludedDirectory);
            if (strpos(trailingslashit($directory), trailingslashit($excludedDirectory)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get WP media folder
     *
     * @return string
     */
    protected function getUploadFolder()
    {
        $uploads = wp_upload_dir();
        $folder  = str_replace(ABSPATH, '', $uploads['basedir']);
        return $folder;
    }
}
