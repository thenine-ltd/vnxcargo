<?php

namespace WPStaging\Pro\Multisite\Dto;

class AdjustedSubsiteDto extends SubsiteDto
{
    /** @var string */
    private $adjustedDomain;

    /** @var string */
    private $adjustedPath;

    /** @var string */
    private $adjustedSiteUrl;

    /** @var string */
    private $adjustedHomeUrl;

    /**
     * @param array $siteData [
     *  'site_id' => int,
     *  'blog_id' => int,
     *  'domain' => string,
     *  'path' => string,
     *  'site_url' => string,
     *  'home_url' => string,
     * ]
     *
     * @return AdjustedSubsiteDto
     */
    #[\ReturnTypeWillChange]
    public static function createFromSiteData(array $siteData): AdjustedSubsiteDto
    {
        $subsiteDto = new self();
        $subsiteDto->hydrate($siteData);

        return $subsiteDto;
    }

    /**
     * @param array $data [
     *  'site_id' => int,
     *  'blog_id' => int,
     *  'domain' => string,
     *  'path' => string,
     *  'site_url' => string,
     *  'home_url' => string,
     *  'adjustedDomain' => string?,
     *  'adjustedPath' => string?,
     *  'adjustedSiteUrl' => string?,
     *  'adjustedHomeUrl' => string?,
     * ]
     * @return void
     */
    public function hydrate(array $data)
    {
        parent::hydrate($data);

        $this->adjustedDomain  = $data['adjustedDomain'] ?? '';
        $this->adjustedPath    = $data['adjustedPath'] ?? '';
        $this->adjustedSiteUrl = $data['adjustedSiteUrl'] ?? '';
        $this->adjustedHomeUrl = $data['adjustedHomeUrl'] ?? '';
    }

    /** @return string */
    public function getAdjustedDomain(): string
    {
        return $this->adjustedDomain;
    }

    /** @return string */
    public function getAdjustedPath(): string
    {
        return $this->adjustedPath;
    }

    /** @return string */
    public function getAdjustedSiteUrl(): string
    {
        return $this->adjustedSiteUrl;
    }

    /** @return string */
    public function getAdjustedHomeUrl(): string
    {
        return $this->adjustedHomeUrl;
    }

    /**
     * @param string $adjustedDomain
     * @return void
     */
    public function setAdjustedDomain(string $adjustedDomain)
    {
        $this->adjustedDomain = $adjustedDomain;
    }

    /**
     * @param string $adjustedPath
     * @return void
     */
    public function setAdjustedPath(string $adjustedPath)
    {
        $this->adjustedPath = $adjustedPath;
    }

    /**
     * @param string $adjustedSiteUrl
     * @return void
     */
    public function setAdjustedSiteUrl(string $adjustedSiteUrl)
    {
        $this->adjustedSiteUrl = $adjustedSiteUrl;
    }

    /**
     * @param string $adjustedHomeUrl
     * @return void
     */
    public function setAdjustedHomeUrl(string $adjustedHomeUrl)
    {
        $this->adjustedHomeUrl = $adjustedHomeUrl;
    }
}
