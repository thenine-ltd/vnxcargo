<?php

namespace WPStaging\Pro\Backup\Service\Database\Importer;

use wpdb;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Pro\Traits\NetworkConstantTrait;

/**
 * This class will generate urls for the subsites to be used in SearchReplacer.
 */
class SubsitesSearchReplacer
{
    use NetworkConstantTrait;

    /** @var string */
    const FILTER_FULL_NETWORK_SEARCH_REPLACE = 'wpstg.multisite.full_search_replace';

    /** @var AdjustSubsitesMeta */
    private $adjustSubsitesMeta;

    /** @var int */
    private $currentSubsiteId;

    /** @var array */
    private $subsites;

    /** @var wpdb */
    private $wpdb;

    /**
     * @param AdjustSubsitesMeta $adjustSubsitesMeta
     */
    public function __construct(AdjustSubsitesMeta $adjustSubsitesMeta)
    {
        $this->adjustSubsitesMeta = $adjustSubsitesMeta;
        $this->wpdb               = WPStaging::getInstance()->get("wpdb");
    }

    /**
     * @param BackupMetadata $backupMetadata
     * @param int $currentSubsiteId
     * @return void
     */
    public function setupSubsitesAdjuster(BackupMetadata $backupMetadata, int $currentSubsiteId)
    {
        $this->adjustSubsitesMeta->readBackupMetadata($backupMetadata);
        $this->currentSubsiteId = $currentSubsiteId;
        $this->subsites         = $backupMetadata->getSites();
    }

    /**
     * @param string $siteUrl
     * @param string $homeUrl
     * @return array
     */
    public function getSubsitesToReplace(string $siteUrl, string $homeUrl): array
    {
        $isFullNetworkSearchReplace = Hooks::applyFilters(self::FILTER_FULL_NETWORK_SEARCH_REPLACE, false) === true;

        if (($this->currentSubsiteId === 0 || $this->currentSubsiteId === 1) && !$isFullNetworkSearchReplace) {
            return [];
        }

        if (!$isFullNetworkSearchReplace) {
            return $this->getCurrentSubsiteAdjustedMeta($siteUrl, $homeUrl);
        }

        $subsites = [];
        foreach ($this->subsites as $subsite) {
            $blogId = (int)$subsite['blog_id'];
            if ($blogId === 0 || $blogId === 1) {
                continue;
            }

            $subsites[] = $subsite;
        }

        $this->adjustSubsitesMeta->setSourceSites($subsites);
        return $this->adjustSubsitesMeta->getAdjustedSubsites($this->getCurrentNetworkDomain(), $this->getCurrentNetworkPath(), $siteUrl, $homeUrl, $this->getIsSubdomainInstall());
    }

    /**
     * @param string $siteUrl
     * @param string $homeUrl
     * @return array
     */
    protected function getCurrentSubsiteAdjustedMeta(string $siteUrl, string $homeUrl): array
    {
        foreach ($this->subsites as $subsite) {
            $blogId = (int)$subsite['blog_id'];
            if ($blogId !== $this->currentSubsiteId) {
                continue;
            }

            $this->adjustSubsitesMeta->setSourceSites([$subsite]);
            return $this->adjustSubsitesMeta->getAdjustedSubsites($this->getCurrentNetworkDomain(), $this->getCurrentNetworkPath(), $siteUrl, $homeUrl, $this->getIsSubdomainInstall());
        }

        return [];
    }

    /**
     * @return bool
     */
    protected function getIsSubdomainInstall(): bool
    {
        return is_subdomain_install();
    }
}
