<?php

namespace WPStaging\Pro\Backup\Task;

use UnexpectedValueException;
use wpdb;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Core\WPStaging;
use WPStaging\Pro\Backup\Service\Database\Importer\AdjustSubsitesMeta;
use WPStaging\Pro\Traits\NetworkConstantTrait;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * Class MultisiteRestoreTask
 *
 * This is an abstract class for the multisite specific restore actions of restoring a site.
 *
 * @package WPStaging\Pro\Backup\Task
 */
abstract class MultisiteRestoreTask extends RestoreTask
{
    use NetworkConstantTrait;

    /** @var array */
    protected $sites;

    /** @var wpdb */
    protected $wpdb;

    /** @var string */
    protected $sourceSiteDomain;

    /** @var string */
    protected $sourceSitePath;

    /** @var bool */
    protected $isSubdomainInstall;

    /** @var AdjustSubsitesMeta */
    protected $adjustSubsitesMeta;

    public function __construct(AdjustSubsitesMeta $adjustSubsitesMeta, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->wpdb               = WPStaging::getInstance()->get("wpdb");
        $this->adjustSubsitesMeta = $adjustSubsitesMeta;
    }

    /**
     * @throws UnexpectedValueException
     */
    protected function adjustDomainPath()
    {
        $this->adjustSubsitesMeta->readBackupMetadata($this->jobDataDto->getBackupMetadata());
        $this->sourceSiteDomain   = $this->adjustSubsitesMeta->getSourceSiteDomain();
        $this->sourceSitePath     = $this->adjustSubsitesMeta->getSourceSitePath();
        $this->isSubdomainInstall = $this->adjustSubsitesMeta->getIsSourceSubdomainInstall();
        $this->sites              = $this->adjustSubsitesMeta->getAdjustedSubsites($this->getCurrentNetworkDomain(), $this->getCurrentNetworkPath(), get_site_url(), get_home_url(), is_subdomain_install());
    }

    /**
     * Are source and destination network domain and path same?
     * @return bool
     */
    protected function areDomainAndPathSame(): bool
    {
        if ($this->sourceSitePath !== $this->getCurrentNetworkPath()) {
            return false;
        }

        if ($this->sourceSiteDomain === $this->getCurrentNetworkDomain()) {
            return true;
        }

        // Check once again as the domain might be different due to www prefix
        return ('www.' . $this->sourceSiteDomain) === $this->getCurrentNetworkDomain();
    }
}
