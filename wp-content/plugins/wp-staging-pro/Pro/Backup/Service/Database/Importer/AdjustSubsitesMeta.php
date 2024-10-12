<?php

namespace WPStaging\Pro\Backup\Service\Database\Importer;

use UnexpectedValueException;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Pro\Multisite\Dto\AdjustedSubsiteDto;
use WPStaging\Pro\Multisite\Dto\SubsiteDto;

use function WPStaging\functions\debug_log;

/**
 * Class responsible for adjusting subsites meta data
 * Source site is the site from which backup was created or from which we are cloning site
 * Destination site is the site to which backup is being restored or our staging site
 *
 */
class AdjustSubsitesMeta
{
    /** @var string */
    const FILTER_MULTISITE_SUBSITES = 'wpstg.backup.restore.multisites.subsites';

    /** @var SubsiteDto[] */
    protected $sites;

    /** @var string */
    private $sourceSiteDomain;

    /** @var string */
    private $sourceSitePath;

    /** @var string */
    private $sourceSiteUrl;

    /** @var string */
    private $sourceHomeUrl;

    /** @var bool */
    protected $isSourceSubdomainInstall;

    /** @var Strings */
    protected $stringsUtil;

    public function __construct(Strings $stringsUtil)
    {
        $this->stringsUtil = $stringsUtil;
    }

    /** @return string */
    public function getSourceSiteDomain(): string
    {
        return $this->sourceSiteDomain;
    }

    /** @return string */
    public function getSourceSitePath(): string
    {
        return $this->sourceSitePath;
    }

    /** @return string */
    public function getSourceSiteUrl(): string
    {
        return $this->sourceSiteUrl;
    }

    /** @return string */
    public function getSourceHomeUrl(): string
    {
        return $this->sourceHomeUrl;
    }

    /** @return bool */
    public function getIsSourceSubdomainInstall(): bool
    {
        return $this->isSourceSubdomainInstall;
    }

    /**
     * @param string $sourceSiteDomain
     * @return void
     */
    public function setSourceSiteDomain(string $sourceSiteDomain)
    {
        $this->sourceSiteDomain = $sourceSiteDomain;
    }

    /**
     * @param string $sourceSitePath
     * @return void
     */
    public function setSourceSitePath(string $sourceSitePath)
    {
        $this->sourceSitePath = $sourceSitePath;
    }

    /**
     * @param string $sourceSiteUrl
     * @return void
     */
    public function setSourceSiteUrl(string $sourceSiteUrl)
    {
        $this->sourceSiteUrl = $sourceSiteUrl;
    }

    /**
     * @param string $sourceHomeUrl
     * @return void
     */
    public function setSourceHomeUrl(string $sourceHomeUrl)
    {
        $this->sourceHomeUrl = $sourceHomeUrl;
    }

    /**
     * @param bool $isSubdomainInstall
     * @return void
     */
    public function setSourceSubdomainInstall(bool $isSubdomainInstall)
    {
        $this->isSourceSubdomainInstall = $isSubdomainInstall;
    }

    /**
     * @param array $sites [
     * [
     *  'site_id' => int,
     *  'blog_id' => int,
     *  'domain' => string,
     *  'path' => string,
     *  'site_url' => string,
     *  'home_url' => string
     * ]]
     * @return void
     */
    public function setSourceSites(array $sites)
    {
        $this->sites = [];
        foreach ($sites as $site) {
            $this->sites[] = SubsiteDto::createFromSiteData($site);
        }
    }

    /**
     * Get Sites with adjusted new urls
     *
     * @param string $baseDomain
     * @param string $basePath
     * @param string $siteURL
     * @param string $homeURL
     * @param bool $isSubdomainInstall
     *
     * @return array [[
     *  'siteId' => int,
     *  'blogId' => int,
     *  'domain' => string,
     *  'path' => string,
     *  'siteUrl' => string,
     *  'homeUrl' => string
     *  'adjustedDomain' => string,
     *  'adjustedPath' => string,
     *  'adjustedSiteUrl' => string,
     *  'adjustedHomeUrl' => string,
     * ]] array of site info
     */
    public function getAdjustedSubsites(string $baseDomain, string $basePath, string $siteURL, string $homeURL, bool $isSubdomainInstall): array
    {
        $adjustedSites = [];
        foreach ($this->sites as $site) {
            $adjustedSite    = $this->adjustSubsite($site, $baseDomain, $basePath, $siteURL, $homeURL, $isSubdomainInstall);
            $adjustedSites[] = $adjustedSite->toArray();
        }

        $filteredAdjustedSites = Hooks::applyFilters(self::FILTER_MULTISITE_SUBSITES, $adjustedSites, $baseDomain, $basePath, $siteURL, $homeURL, $isSubdomainInstall);
        if (is_array($filteredAdjustedSites)) {
            return $filteredAdjustedSites;
        }

        debug_log('Filter: wpstg.backup.restore.multisites.subsites does not return an array. Using default subsites.');
        return $adjustedSites;
    }

    /**
     * @param BackupMetadata $backupMetadata
     * @return void
     * @throws UnexpectedValueException
     */
    public function readBackupMetadata(BackupMetadata $backupMetadata)
    {
        $this->isSourceSubdomainInstall = $backupMetadata->getSubdomainInstall();
        $this->sourceSiteUrl            = $backupMetadata->getSiteUrl();
        $this->sourceHomeUrl            = $backupMetadata->getHomeUrl();

        $sourceSiteURLWithoutWWW = str_ireplace('//www.', '//', $this->sourceSiteUrl);
        $parsedURL = parse_url($sourceSiteURLWithoutWWW);

        if (!is_array($parsedURL) || !array_key_exists('host', $parsedURL)) {
            throw new UnexpectedValueException("Bad URL format, cannot proceed.");
        }

        $this->sourceSiteDomain = $parsedURL['host'];
        $this->sourceSitePath = '/';
        if (array_key_exists('path', $parsedURL)) {
            $this->sourceSitePath = $parsedURL['path'];
        }

        $this->sites = [];
        foreach ($backupMetadata->getSites() as $site) {
            $this->sites[] = SubsiteDto::createFromSiteData($site);
        }
    }

    /**
     * @param SubsiteDto $site
     * @param string $baseDomain
     * @param string $basePath
     * @param string $siteURL
     * @param string $homeURL
     * @param bool $isSubdomainInstall
     * @return AdjustedSubsiteDto
     */
    private function adjustSubsite(SubsiteDto $site, string $baseDomain, string $basePath, string $siteURL, string $homeURL, bool $isSubdomainInstall): AdjustedSubsiteDto
    {
        // When base site
        $sourceSiteDomain = strpos($this->sourceSiteDomain, 'www.') === 0 ? substr($this->sourceSiteDomain, 4) : $this->sourceSiteDomain;
        $subsiteDomain    = strpos($site->getDomain(), 'www.') === 0 ? substr($site->getDomain(), 4) : $site->getDomain();
        if ($sourceSiteDomain === $subsiteDomain && $this->sourceSitePath === $site->getPath()) {
            $adjustedSite = AdjustedSubsiteDto::createFromSiteData($site->toArray());
            $adjustedSite->setAdjustedDomain($baseDomain);
            $adjustedSite->setAdjustedPath($basePath);
            $adjustedSite->setAdjustedSiteUrl(rtrim($siteURL, '/'));
            $adjustedSite->setAdjustedHomeUrl(rtrim($homeURL, '/'));

            return $adjustedSite;
        }

        $sourceSiteUrlWithoutScheme = $this->stringsUtil->getUrlWithoutScheme($this->sourceSiteUrl);
        $sourceHomeUrlWithoutScheme = $this->stringsUtil->getUrlWithoutScheme($this->sourceHomeUrl);
        $destinationSiteUrlWithoutScheme = $this->stringsUtil->getUrlWithoutScheme($siteURL);
        $destinationHomeUrlWithoutScheme = $this->stringsUtil->getUrlWithoutScheme($homeURL);

        $subsiteSiteUrlWwwPrefix = '';
        if (strpos($destinationSiteUrlWithoutScheme, 'www.') === 0) {
            $subsiteSiteUrlWwwPrefix = 'www.';
        }

        $subsiteHomeUrlWwwPrefix = '';
        if (strpos($destinationHomeUrlWithoutScheme, 'www.') === 0) {
            $subsiteHomeUrlWwwPrefix = 'www.';
        }

        $sourceSiteUrlWithoutScheme = strpos($sourceSiteUrlWithoutScheme, 'www.') === 0 ? substr($sourceSiteUrlWithoutScheme, 4) : $sourceSiteUrlWithoutScheme;
        $sourceHomeUrlWithoutScheme = strpos($sourceHomeUrlWithoutScheme, 'www.') === 0 ? substr($sourceHomeUrlWithoutScheme, 4) : $sourceHomeUrlWithoutScheme;
        $destinationSiteUrlWithoutScheme = strpos($destinationSiteUrlWithoutScheme, 'www.') === 0 ? substr($destinationSiteUrlWithoutScheme, 4) : $destinationSiteUrlWithoutScheme;
        $destinationHomeUrlWithoutScheme = strpos($destinationHomeUrlWithoutScheme, 'www.') === 0 ? substr($destinationHomeUrlWithoutScheme, 4) : $destinationHomeUrlWithoutScheme;

        $subsiteDomain = str_replace($this->sourceSiteDomain, $baseDomain, $site->getDomain());
        $subsitePath   = str_replace(trailingslashit($this->sourceSitePath), $basePath, $site->getPath());
        $subsiteSiteUrlWithoutScheme = str_replace($sourceSiteUrlWithoutScheme, $destinationSiteUrlWithoutScheme, $site->getSiteUrl());
        $subsiteSiteUrlWithoutScheme = $this->stringsUtil->getUrlWithoutScheme($subsiteSiteUrlWithoutScheme);
        $subsiteSiteUrlWithoutScheme = strpos($subsiteSiteUrlWithoutScheme, 'www.') === 0 ? substr($subsiteSiteUrlWithoutScheme, 4) : $subsiteSiteUrlWithoutScheme;
        $subsiteHomeUrlWithoutScheme = str_replace($sourceHomeUrlWithoutScheme, $destinationHomeUrlWithoutScheme, $site->getHomeUrl());
        $subsiteHomeUrlWithoutScheme = $this->stringsUtil->getUrlWithoutScheme($subsiteHomeUrlWithoutScheme);
        $subsiteHomeUrlWithoutScheme = strpos($subsiteHomeUrlWithoutScheme, 'www.') === 0 ? substr($subsiteHomeUrlWithoutScheme, 4) : $subsiteHomeUrlWithoutScheme;

        $subsiteSiteUrlSchemePrefix = parse_url($siteURL, PHP_URL_SCHEME) . '://';
        $subsiteHomeUrlSchemePrefix = parse_url($homeURL, PHP_URL_SCHEME) . '://';

        $baseSiteUrlWithoutScheme = untrailingslashit($baseDomain . $basePath);

        $addWwwPrefix  = strpos($baseDomain, 'www.') === 0 ? true : false;
        $subsiteDomain = rtrim($subsiteDomain, '/');
        $subsiteDomain = strpos($subsiteDomain, 'www.') === 0 ? substr($subsiteDomain, 4) : $subsiteDomain;
        $subsiteDomain = $addWwwPrefix ? 'www.' . $subsiteDomain : $subsiteDomain;

        if ($this->isSourceSubdomainInstall === $isSubdomainInstall && $subsiteSiteUrlWithoutScheme === $baseSiteUrlWithoutScheme && $this->areBothHomeUrlSiteUrlInSameDomain($subsiteHomeUrlWithoutScheme, $subsiteSiteUrlWithoutScheme)) {
            $adjustedSite = AdjustedSubsiteDto::createFromSiteData($site->toArray());
            $adjustedSite->setAdjustedDomain($subsiteDomain);
            $adjustedSite->setAdjustedPath($subsitePath);
            $adjustedSite->setAdjustedSiteUrl($subsiteSiteUrlSchemePrefix . $subsiteSiteUrlWwwPrefix . $subsiteSiteUrlWithoutScheme);
            $adjustedSite->setAdjustedHomeUrl($subsiteHomeUrlSchemePrefix . $subsiteHomeUrlWwwPrefix . $subsiteHomeUrlWithoutScheme);

            return $adjustedSite;
        }

        // Check whether different domain based subsite
        $baseSiteUrlWithoutScheme = strpos($baseSiteUrlWithoutScheme, 'www.') === 0 ? substr($baseSiteUrlWithoutScheme, 4) : $baseSiteUrlWithoutScheme;
        if (strpos($subsiteSiteUrlWithoutScheme, $baseSiteUrlWithoutScheme) === false) {
            return $this->adjustDomainBasedSubsite($site, $baseDomain, $basePath, $subsiteSiteUrlSchemePrefix . $subsiteSiteUrlWwwPrefix, $subsiteHomeUrlSchemePrefix . $subsiteHomeUrlWwwPrefix, $isSubdomainInstall);
        }

        $adjustSiteUrl = $this->getAdjustedSubsiteInfo($baseDomain, $basePath, $baseSiteUrlWithoutScheme, $subsiteSiteUrlWithoutScheme, $subsiteSiteUrlWwwPrefix, $isSubdomainInstall);
        $subsiteDomain = $adjustSiteUrl['domain'];
        $subsitePath   = $adjustSiteUrl['path'];
        $subsiteSiteUrlWithoutScheme = $adjustSiteUrl['url'];

        $adjustHomeUrl = $this->getAdjustedSubsiteInfo($baseDomain, $basePath, $baseSiteUrlWithoutScheme, $subsiteHomeUrlWithoutScheme, $subsiteHomeUrlWwwPrefix, $isSubdomainInstall);
        $subsiteHomeUrlWithoutScheme = $adjustHomeUrl['url'];

        $adjustedSite = AdjustedSubsiteDto::createFromSiteData($site->toArray());
        $adjustedSite->setAdjustedDomain(rtrim($subsiteDomain, '/'));
        $adjustedSite->setAdjustedPath($subsitePath);

        $adjustedSite->setAdjustedSiteUrl($subsiteSiteUrlSchemePrefix . $subsiteSiteUrlWithoutScheme);
        $adjustedSite->setAdjustedHomeUrl($subsiteHomeUrlSchemePrefix . $subsiteHomeUrlWithoutScheme);

        return $adjustedSite;
    }

    /**
     * @param SubsiteDto $site
     * @param string $baseDomain
     * @param string $basePath
     * @param string $siteUrlSchemaPrefix
     * @param string $homeUrlSchemaPrefix
     * @param bool $isSubdomainInstall
     * @return AdjustedSubsiteDto
     */
    protected function adjustDomainBasedSubsite(SubsiteDto $site, string $baseDomain, string $basePath, string $siteUrlSchemaPrefix, string $homeUrlSchemaPrefix, bool $isSubdomainInstall): AdjustedSubsiteDto
    {
        $adjustedSite = AdjustedSubsiteDto::createFromSiteData($site->toArray());
        $baseDomain = rtrim($baseDomain, '/');
        if (!$isSubdomainInstall) {
            $adjustedSite->setAdjustedDomain($baseDomain);
            $adjustedSite->setAdjustedPath($basePath . trailingslashit($site->getDomain()));
        } else {
            $baseDomain = strpos($baseDomain, 'www.') === 0 ? substr($baseDomain, 4) : $baseDomain;
            $adjustedSite->setAdjustedDomain($site->getDomain() . '.' . $baseDomain);
            $adjustedSite->setAdjustedPath($basePath);
        }

        $adjustedSite->setAdjustedSiteUrl($siteUrlSchemaPrefix . $adjustedSite->getAdjustedDomain() . $adjustedSite->getAdjustedPath());
        $adjustedSite->setAdjustedHomeUrl($homeUrlSchemaPrefix . $adjustedSite->getAdjustedDomain() . $adjustedSite->getAdjustedPath());

        return $adjustedSite;
    }

    /**
     * @param string $homeUrlWithoutScheme
     * @param string $siteUrlWithoutScheme
     * @return bool
     */
    protected function areBothHomeUrlSiteUrlInSameDomain(string $homeUrlWithoutScheme, string $siteUrlWithoutScheme): bool
    {
        if ($homeUrlWithoutScheme === $siteUrlWithoutScheme) {
            return true;
        }

        if (strpos($homeUrlWithoutScheme, $siteUrlWithoutScheme) === 0) {
            return true;
        }

        if (strpos($siteUrlWithoutScheme, $homeUrlWithoutScheme) === 0) {
            return true;
        }

        return false;
    }

    protected function getAdjustedSubsiteInfo(string $subsiteDomain, string $subsitePath, string $baseSiteUrlWithoutScheme, string $subsiteUrlWithoutScheme, string $subsiteUrlWwwPrefix, bool $isSubdomainInstall)
    {
        $subsiteName = str_replace($baseSiteUrlWithoutScheme, '', $subsiteUrlWithoutScheme);
        $subsiteName = rtrim($subsiteName, '.');
        $subsiteName = trim($subsiteName, '/');
        if ($subsiteUrlWwwPrefix === '' && (strpos($subsiteDomain, 'www.') === 0)) {
            $subsiteDomain = substr($subsiteDomain, 4);
        }

        if ($isSubdomainInstall && ($subsiteName !== '') && ($subsiteName !== 'www')) {
            $subsiteName   = strpos($subsiteName, 'www.') === 0 ? substr($subsiteName, 4) : $subsiteName;
            $subsiteDomain = $subsiteName . '.' . $subsiteDomain;
        }

        if (!$isSubdomainInstall && ($subsiteName !== '')) {
            $subsiteName = strpos($subsiteUrlWithoutScheme, 'www.') === 0 ? substr($subsiteName, 4) : $subsiteName;
            $subsiteName = empty($subsiteName) ? '' : trailingslashit($subsiteName);
            $subsiteName = ltrim($subsiteName, '/');
            $subsitePath = $subsitePath . $subsiteName;
        }

        $subsiteUrlWithoutScheme = untrailingslashit(rtrim($subsiteDomain, '/') . $subsitePath);
        // Remove www. prefix from subsite URL, it will be added later
        if (strpos($subsiteUrlWithoutScheme, 'www.') === 0) {
            $subsiteUrlWithoutScheme = substr($subsiteUrlWithoutScheme, 4);
            // Force add it later as it was removed, this will make sure no double www prefix are added.
            $subsiteUrlWwwPrefix = 'www.';
        }

        return [
            'domain' => $subsiteDomain,
            'path'   => $subsitePath,
            'url'    => $subsiteUrlWwwPrefix . $subsiteUrlWithoutScheme,
        ];
    }
}
