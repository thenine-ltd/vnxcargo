<?php

namespace WPStaging\Pro\Staging\Service;

use WPStaging\Framework\Database\DbInfo;
use WPStaging\Framework\Database\WpDbInfo;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database\DatabaseException;

class CompareExternalDatabase
{
    /**
     * @var DbInfo
     */
    protected $stagingDbInfo;

    /**
     * @var WpDbInfo
     */
    protected $productionDbInfo;

    /**
     * @var bool
     */
    protected $isProductionDbConnected;

    /**
     * @param string $hostServer
     * @param string $user
     * @param string $password
     * @param string $database
     * @param bool $useSsl
     *
     * @throws DatabaseException
     */
    public function __construct(string $hostServer, string $user, string $password, string $database, bool $useSsl = false)
    {
        $this->stagingDbInfo    = new DbInfo($hostServer, $user, $password, $database, $useSsl);
        $this->productionDbInfo = new WpDbInfo(WPStaging::getInstance()->get("wpdb"));
    }

    /**
     * @return array
     */
    public function maybeGetComparison(): array
    {
        $productionDbInfo = $this->productionDbInfo->toArray();
        $stagingDbInfo    = $this->stagingDbInfo->toArray();
        // DB properties are equal. Do nothing
        if ($productionDbInfo === $stagingDbInfo) {
            return [
                "success" => true
            ];
        }

        // DB Properties are different. Get comparison table
        return [
            "success"    => false,
            'error_type' => 'comparison',
            "checks"     => [
                [
                    "name"       => __('DB Collation', 'wp-staging'),
                    "production" => $this->productionDbInfo->getDbCollation(),
                    "staging"    => $this->stagingDbInfo->getDbCollation(),
                ],
                [
                    "name"       => __('DB Storage Engine', 'wp-staging'),
                    "production" => $this->productionDbInfo->getDbEngine(),
                    "staging"    => $this->stagingDbInfo->getDbEngine(),
                ],
                [
                    "name"       => __('MySQL Server Version', 'wp-staging'),
                    "production" => $this->productionDbInfo->getMySqlServerVersion(),
                    "staging"    => $this->stagingDbInfo->getMySqlServerVersion(),
                ]
            ]
        ];
    }
}
