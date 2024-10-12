<?php

namespace WPStaging\Pro\Backup\Service\Database\Importer;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Database\Importer\DatabaseSearchReplacerInterface;
use WPStaging\Framework\Database\SearchReplace;

class DatabaseSearchReplacer implements DatabaseSearchReplacerInterface
{
    /**
     * @var array
     */
    protected $search  = [];

    /**
     * @var array
     */
    protected $replace = [];

    /** @var string */
    protected $sourceSiteUrl;

    /** @var string */
    protected $sourceHomeUrl;

    /** @var string */
    protected $sourceSiteHostname;

    /** @var string */
    protected $sourceHomeHostname;

    /** @var string */
    protected $sourceSiteUploadURL;

    /** @var string */
    protected $destinationSiteUrl;

    /** @var string */
    protected $destinationHomeUrl;

    /** @var string */
    protected $destinationSiteHostname;

    /** @var string */
    protected $destinationHomeHostname;

    /** @var string */
    protected $destinationSiteUploadURL;

    /** @var bool */
    protected $matchingScheme;

    /** @var string */
    protected $sourceAbsPath;

    /**
     * @var array
     */
    protected $plugins = [];

    /** @var bool|null */
    protected $requireCslashEscaping = null;

    /** @var bool */
    protected $isWpBakeryActive = false;

    /** @var bool */
    protected $isMultisite = false;

    /** @var bool */
    protected $isSubsiteSearchReplace = false;

    /** @var SubsitesSearchReplacer */
    protected $subsitesSearchReplacer;

    /**
     * @param SubsitesSearchReplacer $subsitesSearchReplacer
     */
    public function __construct(SubsitesSearchReplacer $subsitesSearchReplacer)
    {
        $this->subsitesSearchReplacer = $subsitesSearchReplacer;
    }

    /**
     * @param bool $isWpBakeryActive
     * @return void
     */
    public function setIsWpBakeryActive(bool $isWpBakeryActive)
    {
        $this->isWpBakeryActive = $isWpBakeryActive;
    }

    /**
     * @param string $sourceAbsPath
     * @return void
     */
    public function setSourceAbsPath(string $sourceAbsPath)
    {
        $this->sourceAbsPath = $sourceAbsPath;
    }

    /**
     * @param array $plugins
     * @return void
     */
    public function setSourcePlugins(array $plugins)
    {
        $this->plugins = $plugins;
    }

    /**
     * @param string $sourceSiteUrl
     * @param string $sourceHomeUrl
     * @param string $sourceSiteUploadURL
     * @return void
     */
    public function setSourceUrls(string $sourceSiteUrl, string $sourceHomeUrl, string $sourceSiteUploadURL)
    {
        $this->sourceSiteUrl = untrailingslashit($sourceSiteUrl);
        $this->sourceHomeUrl = untrailingslashit($sourceHomeUrl);
        $this->sourceSiteUploadURL = untrailingslashit($sourceSiteUploadURL);
    }

    /**
     * @param BackupMetadata $backupMetadata
     * @param int $currentSubsiteId
     * @return void
     */
    public function setupSubsitesSearchReplacer(BackupMetadata $backupMetadata, int $currentSubsiteId)
    {
        $this->subsitesSearchReplacer->setupSubsitesAdjuster($backupMetadata, $currentSubsiteId);
        $this->isMultisite = true;
    }

    /**
     * @param string $destinationSiteUrl
     * @param string $destinationHomeUrl
     * @param string $absPath
     * @param string|null $destinationSiteUploadURL
     * @return SearchReplace
     */
    public function getSearchAndReplace(string $destinationSiteUrl, string $destinationHomeUrl, string $absPath = ABSPATH, $destinationSiteUploadURL = null): SearchReplace
    {
        $this->setupSearchReplaceUrls($destinationSiteUrl, $destinationHomeUrl, $destinationSiteUploadURL);
        if ($this->isMultisite) {
            $this->replaceSubsitesUrls($destinationSiteUrl, $destinationHomeUrl);
        }

        $this->replaceAbsPath($absPath);

        // Remove unnecessary S/R
        foreach ($this->search as $k => $searchItem) {
            if ($this->replace[$k] === $searchItem) {
                unset($this->search[$k]);
                unset($this->replace[$k]);
            }
        }

        // Recalculate indexes
        $this->search  = array_values($this->search);
        $this->replace = array_values($this->replace);

        // This help sort according to search also keeping replace associated with search
        $searchReplaceToSort = array_combine($this->search, $this->replace);

        /** @var array */
        $searchReplaceToSort = apply_filters('wpstg.backup.restore.searchreplace', $searchReplaceToSort, $absPath, $this->sourceSiteUrl, $this->sourceHomeUrl, $this->destinationSiteUrl, $this->destinationHomeUrl);

        /*
         * Order items, so that the biggest values are replaced first.
         * This prevents smaller items from being replaced on
         * bigger items that starts with the same string but
         * ends differently.
         */
        uksort($searchReplaceToSort, function ($item1, $item2) {
            if (strlen($item1) == strlen($item2)) {
                return 0;
            }

            return (strlen($item1) > strlen($item2)) ? -1 : 1;
        });

        $orderedSearch  = array_keys($searchReplaceToSort);
        $orderedReplace = array_values($searchReplaceToSort);

        return (new SearchReplace())
            ->setSearch($orderedSearch)
            ->setReplace($orderedReplace)
            ->setWpBakeryActive($this->isWpBakeryActive);
    }

    /**
     * @dev Public for testing purposes only.
     * @return string
     */
    public function buildHostname(string $url): string
    {
        $parsedUrl = parse_url($url);

        if (!is_array($parsedUrl) || !array_key_exists('host', $parsedUrl)) {
            throw new \UnexpectedValueException("Bad URL format, cannot proceed.");
        }

        // This is a requirement
        $hostname = $parsedUrl['host'];

        // This will be populated if wordpress site has a different port then 80 or 443
        if (array_key_exists('port', $parsedUrl)) {
            $hostname = $hostname . ':' . $parsedUrl['port'];
        }

        // This will be populated if WordPress was/is going to be in a subfolder
        if (array_key_exists('path', $parsedUrl)) {
            $hostname = trailingslashit($hostname) . trim($parsedUrl['path'], '/');
        }

        return $hostname;
    }

    /**
     * @param string $destinationSiteUrl
     * @param string $destinationHomeUrl
     * @param string|null $destinationSiteUploadURL
     * @return void
     */
    protected function setupSearchReplaceUrls(string $destinationSiteUrl, string $destinationHomeUrl, $destinationSiteUploadURL = null)
    {
        $this->sourceSiteHostname = untrailingslashit($this->buildHostname($this->sourceSiteUrl));
        $this->sourceHomeHostname = untrailingslashit($this->buildHostname($this->sourceHomeUrl));

        $this->destinationSiteUrl = untrailingslashit($destinationSiteUrl);
        $this->destinationHomeUrl = untrailingslashit($destinationHomeUrl);

        $this->destinationSiteHostname = untrailingslashit($this->buildHostname($this->destinationSiteUrl));
        $this->destinationHomeHostname = untrailingslashit($this->buildHostname($this->destinationHomeUrl));

        // No need to search replace uploads url for subsites
        if (!$this->isSubsiteSearchReplace) {
            $this->destinationSiteUploadURL = $destinationSiteUploadURL;

            $this->prepareUploadURLs();
        }

        // Check if scheme is identical https or http on both sites
        $this->matchingScheme = parse_url($this->sourceSiteUrl, PHP_URL_SCHEME) === parse_url($this->destinationSiteUrl, PHP_URL_SCHEME);

        if (!$this->matchingScheme) {
            $this->replaceMultipleSchemes();
        }

        $this->replaceGenericScheme();
    }

    /**
     * @param string $destinationSiteUrl
     * @param string $destinationHomeUrl
     * @return void
     */
    protected function replaceSubsitesUrls(string $destinationSiteUrl, string $destinationHomeUrl)
    {
        $subsites = $this->subsitesSearchReplacer->getSubsitesToReplace($destinationSiteUrl, $destinationHomeUrl);
        $this->isSubsiteSearchReplace = true;
        foreach ($subsites as $subsite) {
            $this->sourceHomeUrl = $subsite['homeUrl'];
            $this->sourceSiteUrl = $subsite['siteUrl'];
            $this->setupSearchReplaceUrls($subsite['adjustedSiteUrl'], $subsite['adjustedHomeUrl']);
        }
    }

    /**
     * @param string $absPath
     * @return void
     */
    protected function replaceAbsPath(string $absPath)
    {
        // Early bail if ABSPATH already same
        if ($this->sourceAbsPath === $absPath) {
            return;
        }

        $this->search[] = $this->sourceAbsPath;
        $this->search[] = addcslashes($this->sourceAbsPath, '/');
        $this->search[] = urlencode($this->sourceAbsPath);

        $this->replace[] = $absPath;
        $this->replace[] = addcslashes($absPath, '/');
        $this->replace[] = urlencode($absPath);

        if (urlencode($this->sourceAbsPath) !== rawurlencode($this->sourceAbsPath)) {
            $this->search[] = rawurlencode($this->sourceAbsPath);
            $this->replace[] = rawurlencode($absPath);
        }

        // Normalized $absPath
        if (wp_normalize_path($this->sourceAbsPath) !== $this->sourceAbsPath) {
            $this->search[] = wp_normalize_path($this->sourceAbsPath);
            $this->search[] = wp_normalize_path(addcslashes($this->sourceAbsPath, '/'));
            $this->search[] = wp_normalize_path(urlencode($this->sourceAbsPath));

            $this->replace[] = wp_normalize_path($absPath);
            $this->replace[] = wp_normalize_path(addcslashes($absPath, '/'));
            $this->replace[] = wp_normalize_path(urlencode($absPath));

            if (wp_normalize_path(urlencode($this->sourceAbsPath)) !== wp_normalize_path(rawurlencode($this->sourceAbsPath))) {
                $this->search[] = wp_normalize_path(rawurlencode($this->sourceAbsPath));
                $this->replace[] = wp_normalize_path(rawurlencode($absPath));
            }
        }
    }

    /**
     * @return void
     */
    protected function replaceGenericScheme()
    {
        if ($this->isIdenticalSiteHostname()) {
            $this->replaceGenericHomeScheme();
            return;
        }

        $this->replaceURLs($this->sourceSiteHostname, $this->destinationSiteHostname);

        $this->replaceUploadURLs();
        $this->replaceGenericHomeScheme();
    }

    /**
     * @return void
     */
    protected function replaceGenericHomeScheme()
    {
        // A cross-domain scenario between FE and Admin
        if (!$this->isCrossDomain()) {
            return;
        }

        if ($this->isIdenticalHomeHostname()) {
            return;
        }

        $this->replaceURLs($this->sourceHomeHostname, $this->destinationHomeHostname);
    }

    /**
     * @return void
     */
    protected function replaceUploadURLs()
    {
        if ($this->isIdenticalUploadURL()) {
            return;
        }

        $sourceUploadURLWithoutScheme = trailingslashit($this->sourceSiteHostname) . $this->sourceSiteUploadURL;
        $destinationUploadURLWithoutScheme = trailingslashit($this->destinationSiteHostname) . $this->destinationSiteUploadURL;
        $this->replaceURLs($sourceUploadURLWithoutScheme, $destinationUploadURLWithoutScheme);
    }

    /**
     * @param string $sourceURL
     * @param string $destinationURL
     * @param bool $doubleSlashPrefix
     * @return void
     */
    protected function replaceURLs(string $sourceURL, string $destinationURL, bool $doubleSlashPrefix = true)
    {
        $prefix = $doubleSlashPrefix ? '//' : '';
        $sourceGenericProtocol = $prefix . $sourceURL;
        $destinationGenericProtocol = $prefix . $destinationURL;

        $sourceGenericProtocolJsonEscaped = addcslashes($sourceGenericProtocol, '/');
        $destinationGenericProtocolJsonEscaped = addcslashes($destinationGenericProtocol, '/');

        $this->search[] = $sourceGenericProtocol;
        $this->search[] = $sourceGenericProtocolJsonEscaped;
        $this->search[] = urlencode($sourceGenericProtocol);

        $this->replace[] = $destinationGenericProtocol;
        $this->replace[] = $destinationGenericProtocolJsonEscaped;
        $this->replace[] = urlencode($destinationGenericProtocol);

        if ($this->isExtraCslashEscapingRequired()) {
            $this->search[] = addcslashes($sourceGenericProtocolJsonEscaped, '/');
            $this->replace[] = addcslashes($destinationGenericProtocolJsonEscaped, '/');
        }
    }

    /**
     * @return void
     */
    protected function replaceMultipleSchemes()
    {
        if ($this->isIdenticalSiteHostname()) {
            $this->replaceMultipleHomeSchemes();
            $this->replaceMultipleSchemesUploadURL();
            return;
        }

        $sourceSiteHostnameJsonEscapedHttps = addcslashes('https://' . $this->sourceSiteHostname, '/');
        $sourceSiteHostnameJsonEscapedHttp = addcslashes('http://' . $this->sourceSiteHostname, '/');

        $this->search[] = 'https://' . $this->sourceSiteHostname;
        $this->search[] = 'http://' . $this->sourceSiteHostname;
        $this->search[] = $sourceSiteHostnameJsonEscapedHttps;
        $this->search[] = $sourceSiteHostnameJsonEscapedHttp;
        $this->search[] = urlencode('https://' . $this->sourceSiteHostname);
        $this->search[] = urlencode('http://' . $this->sourceSiteHostname);

        $this->replace[] = $this->destinationSiteUrl;
        $this->replace[] = $this->destinationSiteUrl;
        $this->replace[] = addcslashes($this->destinationSiteUrl, '/');
        $this->replace[] = addcslashes($this->destinationSiteUrl, '/');
        $this->replace[] = urlencode($this->destinationSiteUrl);
        $this->replace[] = urlencode($this->destinationSiteUrl);

        // One more time json escaping for some edge cases i.e. RevSlider
        if ($this->isExtraCslashEscapingRequired()) {
            $this->search[] = addcslashes($sourceSiteHostnameJsonEscapedHttps, '/');
            $this->search[] = addcslashes($sourceSiteHostnameJsonEscapedHttp, '/');
            $this->replace[] = addcslashes($this->destinationSiteUrl, '/');
            $this->replace[] = addcslashes($this->destinationSiteUrl, '/');
        }

        $this->replaceMultipleHomeSchemes();
    }

    /**
     * @return void
     */
    protected function replaceMultipleHomeSchemes()
    {
        // A cross-domain scenario between FE and Admin
        if (!$this->isCrossDomain()) {
            return;
        }

        if ($this->isIdenticalHomeHostname()) {
            return;
        }

        $sourceHomeHostnameJsonEscapedHttps = addcslashes('https://' . $this->sourceHomeHostname, '/');
        $sourceHomeHostnameJsonEscapedHttp = addcslashes('http://' . $this->sourceHomeHostname, '/');

        $this->search[] = 'https://' . $this->sourceHomeHostname;
        $this->search[] = 'http://' . $this->sourceHomeHostname;
        $this->search[] = $sourceHomeHostnameJsonEscapedHttps;
        $this->search[] = $sourceHomeHostnameJsonEscapedHttp;
        $this->search[] = urlencode('https://' . $this->sourceHomeHostname);
        $this->search[] = urlencode('http://' . $this->sourceHomeHostname);

        $this->replace[] = $this->destinationHomeUrl;
        $this->replace[] = $this->destinationHomeUrl;
        $this->replace[] = addcslashes($this->destinationHomeUrl, '/');
        $this->replace[] = addcslashes($this->destinationHomeUrl, '/');
        $this->replace[] = urlencode($this->destinationHomeUrl);
        $this->replace[] = urlencode($this->destinationHomeUrl);

        // One more time json escaping for some edge cases i.e. RevSlider
        if ($this->isExtraCslashEscapingRequired()) {
            $this->search[] = addcslashes($sourceHomeHostnameJsonEscapedHttps, '/');
            $this->search[] = addcslashes($sourceHomeHostnameJsonEscapedHttp, '/');
            $this->replace[] = addcslashes($this->destinationHomeUrl, '/');
            $this->replace[] = addcslashes($this->destinationHomeUrl, '/');
        }
    }

    /**
     * @return void
     */
    protected function replaceMultipleSchemesUploadURL()
    {
        if ($this->isIdenticalUploadURL()) {
            return;
        }

        $sourceUploadURLWithHttpsScheme = 'https://' . trailingslashit($this->sourceSiteHostname) . $this->sourceSiteUploadURL;
        $destinationUploadURLWithScheme = trailingslashit($this->destinationSiteUrl) . $this->destinationSiteUploadURL;
        $this->replaceURLs($sourceUploadURLWithHttpsScheme, $destinationUploadURLWithScheme, $doubleSlashPrefix = false);
        $sourceUploadURLWithHttpScheme = 'http://' . trailingslashit($this->sourceSiteHostname) . $this->sourceSiteUploadURL;
        $this->replaceURLs($sourceUploadURLWithHttpScheme, $destinationUploadURLWithScheme, $doubleSlashPrefix = false);
    }

    /**
     * Plugins like rev-slider plugin require extra cslash escaping.
     * Return true to add this extra rules for S/R
     * Run this only as a dependency to lower the execution time.
     *
     * @return bool
     */
    protected function isExtraCslashEscapingRequired(): bool
    {
        if ($this->requireCslashEscaping !== null) {
            return $this->requireCslashEscaping;
        }

        $requireCslashEscaping = false;
        foreach ($this->plugins as $plugin) {
            if (in_array($plugin, $this->getPluginsWhichRequireCslashEscaping())) {
                $requireCslashEscaping = true;
                break;
            }
        }

        $this->requireCslashEscaping = apply_filters('wpstg.backup.restore.extended-cslash-search-replace', $requireCslashEscaping) === true;

        return $this->requireCslashEscaping;
    }

    /**
     * @return array
     */
    protected function getPluginsWhichRequireCslashEscaping(): array
    {
        return [
            'revslider/revslider.php',
            'elementor/elementor.php'
        ];
    }

    /**
     * Sometimes the site url is not the same as the site hostname. E.g. https://example.com vs. http://example.com
     * @return bool
     */
    protected function isCrossDomain(): bool
    {
        return $this->sourceSiteHostname !== $this->sourceHomeHostname;
    }

    /**
     * Is site hostname same between source and destination
     * @return bool
     */
    protected function isIdenticalSiteHostname(): bool
    {
        return $this->sourceSiteHostname === $this->destinationSiteHostname;
    }

    /**
     * Is home hostname same between source and destination
     * @return bool
     */
    protected function isIdenticalHomeHostname(): bool
    {
        return $this->sourceHomeHostname === $this->destinationHomeHostname;
    }

    /**
     * @return bool
     */
    protected function isIdenticalUploadURL(): bool
    {
        return $this->sourceSiteUploadURL === $this->destinationSiteUploadURL;
    }

    /**
     * @return void
     */
    protected function prepareUploadURLs()
    {
        if (empty($this->destinationSiteUploadURL)) {
            $uploadDir = wp_upload_dir(null, false, true);
            if (is_array($uploadDir)) {
                $this->destinationSiteUploadURL = array_key_exists('baseurl', $uploadDir) ? $uploadDir['baseurl'] : '';
            }
        }

        $this->destinationSiteUploadURL = untrailingslashit($this->destinationSiteUploadURL);

        $this->sourceSiteUploadURL = str_replace(trailingslashit($this->sourceSiteUrl), '', $this->sourceSiteUploadURL);
        $this->destinationSiteUploadURL = str_replace(trailingslashit($this->destinationSiteUrl), '', $this->destinationSiteUploadURL);
    }
}
