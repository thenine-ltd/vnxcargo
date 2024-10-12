<?php

namespace WPStaging\Pro\Staging;

use WPStaging\Pro\Notices\EntireNetworkCloneServerConfigNotice;
use WPStaging\Core\Utils\Htaccess;
use WPStaging\Framework\Staging\CloneOptions;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\ServerVars;

class NetworkClone
{
    /**
     * The option to check whether the clone is newly created network clone
     * Added in clone options
     */
    const NEW_NETWORK_CLONE_KEY = 'isNetworkClone';

    /**
     * The option to store base directory.
     * Required for creating .htaccess file on staging network if server is APACHE
     * Added in clone options
     */
    const NETWORK_BASE_DIR_KEY = 'networkBaseDir';

    /**
     * First time initiation of new staging network
     */
    public function init()
    {
        $cloneOptions = WPStaging::make(CloneOptions::class);

        // Early bail
        if ($cloneOptions->get(self::NEW_NETWORK_CLONE_KEY) === null) {
            return;
        }

        // If Current Server is apache, then Htaccess file is supported.
        if (WPStaging::make(ServerVars::class)->isApache() && $this->createHtaccess($cloneOptions->get(self::NETWORK_BASE_DIR_KEY))) {
            $cloneOptions->delete(self::NEW_NETWORK_CLONE_KEY);
            return;
        }

        WPStaging::make(EntireNetworkCloneServerConfigNotice::class)->enable();
        $cloneOptions->delete(self::NEW_NETWORK_CLONE_KEY);
    }

    /**
     * Return true if htaccess file added.
     * Return null if htaccess file already exists
     * Else return false
     *
     * @return null|bool
     */
    protected function createHtaccess($baseDir)
    {
        $htaccessPath = trailingslashit(ABSPATH) . '.htaccess';
        // Early bail if file already exists
        if (file_exists($htaccessPath)) {
            return null;
        }

        return WPStaging::make(Htaccess::class)->createForStagingNetwork($htaccessPath, $baseDir);
    }
}
