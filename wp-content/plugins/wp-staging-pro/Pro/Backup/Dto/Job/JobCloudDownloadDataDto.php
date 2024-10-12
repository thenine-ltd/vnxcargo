<?php

namespace WPStaging\Pro\Backup\Dto\Job;

use WPStaging\Backup\Dto\JobDataDto;

class JobCloudDownloadDataDto extends JobDataDto
{
    /** @var string */
    private $file;

    /** @var int */
    protected $size;

    /** @var string */
    private $storageProviderName;

    /** @var int */
    protected $chunkStart = 0;

    /** @var bool */
    protected $initialDownload = false;

    /** @var string */
    private $cloudFileName;

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @param string $file
     */
    public function setFile(string $file)
    {
        $this->file = untrailingslashit(wp_normalize_path($file));
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return (int)$this->size;
    }

    /**
     * @param  int $size
     * @return void
     */
    public function setSize(int $size)
    {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getStorageProviderName(): string
    {
        return $this->storageProviderName;
    }

    /**
     * @param string $storageProviderName
     */
    public function setStorageProviderName(string $storageProviderName)
    {
        $this->storageProviderName = $storageProviderName;
    }

    /**
     * @return int
     */
    public function getChunkStart(): int
    {
        return (int)$this->chunkStart;
    }

    /**
     * @param  int $chunkStart
     * @return void
     */
    public function setChunkStart(int $chunkStart)
    {
        $this->chunkStart = $chunkStart;
    }

    /**
     * @return bool
     */
    public function getInitialDownload(): bool
    {
        return (bool)$this->initialDownload;
    }

    /**
     * @param  bool $initialDownload
     * @return void
     */
    public function setInitialDownload(bool $initialDownload)
    {
        $this->initialDownload = $initialDownload;
    }

    /**
     * @return string
     */
    public function getCloudFileName(): string
    {
        return $this->cloudFileName;
    }

    /**
     * @param string $cloudFileName
     */
    public function setCloudFileName(string $cloudFileName)
    {
        $this->cloudFileName = $cloudFileName;
    }
}
