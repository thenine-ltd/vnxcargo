<?php

namespace WPStaging\Pro\Staging\Multisite;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Framework\CloningProcess\Data\DataCloningDto;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Core\Utils\Logger;

class SubsitesDomainPathAndUrlsUpdater
{
    /** @var callable */
    protected $updateOptionTable;

    /** @var callable */
    protected $skipTable;

    /** @var DataCloningDto */
    protected $dto;

    /**
     * @param string $message
     * @param string $type
     * @return void
     */
    protected function log(string $message, string $type = Logger::TYPE_INFO)
    {
        $this->dto->getJob()->log("DB Data Step " . $this->dto->getStepNumber() . ": " . $message, $type);
    }

    /**
     * @param DataCloningDto $dto
     * @param callable $updateOptionTableCallable
     * @param callable $skipTableCallable
     * @return void
     */
    public function setup(DataCloningDto $dto, callable $updateOptionTableCallable, callable $skipTableCallable)
    {
        $this->dto               = $dto;
        $this->updateOptionTable = $updateOptionTableCallable;
        $this->skipTable         = $skipTableCallable;
    }

    /**
     * @return true
     */
    public function updateSubsitesDomainPathAndUrls(): bool
    {
        $this->log("Updating subsites domain and urls");
        $this->updateSiteTable();
        $this->updateSubsitesInfo();
        return true;
    }

    /**
     * Wrapper for DOMAIN_CURRENT_SITE for mocking, if DOMAIN_CURRENT_SITE is not defined this returns 'HTTP_HOST' data from $_SERVER.
     * @return string
     */
    protected function getCurrentSiteDomain(): string
    {
        if (!defined('DOMAIN_CURRENT_SITE')) {
            $domain = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field($_SERVER['HTTP_HOST']) : '';
            return $domain;
        }

        return DOMAIN_CURRENT_SITE;
    }

    /**
     * Wrapper for PATH_CURRENT_SITE for mocking
     * @return string
     */
    protected function getCurrentSitePath(): string
    {
        return PATH_CURRENT_SITE;
    }

    /**
     * Wrapper for get_sites for mocking
     *
     * @return array
     */
    protected function getSites(): array
    {
        return get_sites();
    }

    /**
     * Update Multisite Site Table
     * @return bool
     *
     * @throws FatalException
     */
    protected function updateSiteTable(): bool
    {
        $tableName = 'site';
        $domain    = $this->dto->getStagingSiteDomain();
        $path      = trailingslashit($this->dto->getStagingSitePath());
        $isSkipped = call_user_func($this->skipTable, $tableName);
        if ($isSkipped) {
            $this->log("{$this->dto->getPrefix()}{$tableName} Skipped");
            return true;
        }

        $this->log("Updating domain and path in {$this->dto->getPrefix()}{$tableName} to " . $domain . " and " . $path . " respectively");
        // Replace URLs
        $result = $this->dto->getStagingDb()->query(
            $this->dto->getStagingDb()->prepare(
                "UPDATE {$this->dto->getPrefix()}{$tableName} SET domain = %s, path = %s",
                $domain,
                $path
            )
        );

        if ($result === false) {
            throw new FatalException("Failed to update domain and path in {$this->dto->getPrefix()}{$tableName}. {$this->dto->getStagingDb()->last_error}");
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function updateSubsitesInfo(): bool
    {
        $stagingSiteURL = $this->dto->getStagingSiteUrl();
        $str            = new Strings();
        $basePath       = trailingslashit($this->getCurrentSitePath());
        $baseDomain     = $this->getCurrentSiteDomain();
        if (strpos($baseDomain, 'www.') === 0) {
            $baseDomain = $str->strReplaceFirst('www.', '', $baseDomain);
        }

        $stagingSitePath   = trailingslashit($this->dto->getStagingSitePath());
        $stagingSiteDomain = $this->dto->getStagingSiteDomain();
        if (strpos($stagingSiteDomain, 'www.') === 0) {
            $stagingSiteDomain = $str->strReplaceFirst('www.', '', $stagingSiteDomain);
        }

        foreach ($this->getSites() as $site) {
            $tableName = $this->getOptionTableWithoutBasePrefix($site->blog_id);

            // case for domain based subsites
            if (strpos($site->domain, $baseDomain) === false) {
                $subsiteDomain = $str->strReplaceFirst($baseDomain, $site->domain, $stagingSiteDomain);
            } else {
                // case for subdomain based subsites or subdirectory
                $subsiteDomain = $str->strReplaceFirst($baseDomain, $stagingSiteDomain, $site->domain);
            }

            $subsiteHasWWWPrefix = false;
            // remove www prefix from domain
            if (strpos($site->domain, 'www.') === 0) {
                $subsiteHasWWWPrefix = true;
            }

            if (strpos($subsiteDomain, 'www.') === 0) {
                $subsiteDomain = $str->strReplaceFirst('www.', '', $subsiteDomain);
            }

            $subsitePath  = $str->strReplaceFirst($basePath, $stagingSitePath, $site->path);
            $wwwPrefix    = '';
            if ($subsiteHasWWWPrefix) {
                $wwwPrefix = 'www.';
            }

            $subsiteUrl  = parse_url($stagingSiteURL)["scheme"] . "://" . $wwwPrefix . $subsiteDomain . $subsitePath;
            $subsiteInfo = [
                'url'    => $subsiteUrl,
                'domain' => $wwwPrefix . $subsiteDomain,
                'path'   => $subsitePath,
            ];

            $subsiteInfo   = apply_filters('wpstg.cloning.multisite.subsite_info', $subsiteInfo, $site->blog_id, $stagingSiteURL, $subsiteHasWWWPrefix);
            $subsiteUrl    = !empty($subsiteInfo['url']) ? $subsiteInfo['url'] : $subsiteUrl;
            $subsiteDomain = !empty($subsiteInfo['domain']) ? $subsiteInfo['domain'] : $subsiteDomain;
            $subsitePath   = !empty($subsiteInfo['path']) ? $subsiteInfo['path'] : $subsitePath;

            $this->updateBlogsTable($site->blog_id, $subsiteDomain, $subsitePath);
            call_user_func($this->updateOptionTable, $tableName, $subsiteUrl);
        }

        return true;
    }

    /**
     * @param int $blogID
     * @param string $domain
     * @param string $path
     * @return bool
     *
     * @throws FatalException
     */
    protected function updateBlogsTable(int $blogID, string $domain, string $path): bool
    {
        $tableName = 'blogs';
        $isSkipped = call_user_func($this->skipTable, $tableName);
        if ($isSkipped) {
            $this->log("{$this->dto->getPrefix()}{$tableName} Skipped");
            return true;
        }

        $this->log("Updating domain in {$this->dto->getPrefix()}{$tableName} to " . $domain  . " and " . $path . " respectively");
        // Replace URLs
        $result = $this->dto->getStagingDb()->query(
            $this->dto->getStagingDb()->prepare(
                "UPDATE {$this->dto->getPrefix()}{$tableName} SET domain = %s, path = %s WHERE blog_id = %s",
                $domain,
                $path,
                $blogID
            )
        );

        if ($result === false) {
            throw new FatalException("Failed to update domain and path in {$this->dto->getPrefix()}{$tableName}. {$this->dto->getStagingDb()->last_error}");
        }

        return true;
    }

    /**
     * Get Option Table Without Base Prefix
     *
     * @param string $blogID
     * @return string
     */
    protected function getOptionTableWithoutBasePrefix(string $blogID): string
    {
        if ($blogID === '0' || $blogID === '1') {
            return 'options';
        }

        return $blogID . '_options';
    }
}
