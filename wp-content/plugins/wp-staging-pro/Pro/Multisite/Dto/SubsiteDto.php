<?php

namespace WPStaging\Pro\Multisite\Dto;

use WPStaging\Framework\Interfaces\ArrayableInterface;
use WPStaging\Framework\Traits\ArrayableTrait;

class SubsiteDto implements ArrayableInterface
{
    use ArrayableTrait;

    /** @var int */
    protected $siteId;

    /** @var int */
    protected $blogId;

    /** @var string */
    protected $domain;

    /** @var string */
    protected $path;

    /** @var string */
    protected $siteUrl;

    /** @var string */
    protected $homeUrl;

    /**
     * @param array $siteData [
     *  'site_id' => int,
     *  'blog_id' => int,
     *  'domain' => string,
     *  'path' => string,
     *  'site_url' => string,
     *  'home_url' => string,
     * ]
     * @return SubsiteDto
     */
    public static function createFromSiteData(array $siteData): SubsiteDto
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
     * ]
     * @return void
     */
    public function hydrate(array $data)
    {
        $this->setSiteId($data['site_id'] ?? $data['siteId']);
        $this->setBlogId($data['blog_id'] ?? $data['blogId']);
        $this->setDomain($data['domain']);
        $this->setPath($data['path']);
        $this->setSiteUrl($data['site_url'] ?? $data['siteUrl']);
        $this->setHomeUrl($data['home_url'] ?? $data['homeUrl']);
    }

    /** @return int */
    public function getSiteId(): int
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return void
     */
    public function setSiteId(int $siteId)
    {
        $this->siteId = $siteId;
    }

    /** @return int */
    public function getBlogId(): int
    {
        return $this->blogId;
    }

    /**
     * @param int $blogId
     * @return void
     */
    public function setBlogId(int $blogId)
    {
        $this->blogId = $blogId;
    }

    /** @return string */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     * @return void
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
    }

    /** @return string */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /** @return string */
    public function getSiteUrl(): string
    {
        return $this->siteUrl;
    }

    /**
     * @param string $siteUrl
     * @return void
     */
    public function setSiteUrl(string $siteUrl)
    {
        $this->siteUrl = $siteUrl;
    }

    /** @return string */
    public function getHomeUrl(): string
    {
        return $this->homeUrl;
    }

    /**
     * @param string $homeUrl
     * @return void
     */
    public function setHomeUrl(string $homeUrl)
    {
        $this->homeUrl = $homeUrl;
    }
}
