<?php

namespace WPStaging\Pro\Notices;

use WPStaging\Framework\Notices\BooleanNotice;

/**
 * Class EntireNetworkCloneServerConfigNotice
 *
 * Show dismissible notice on entire network clone about setting up configuration to allow subdirectory staging network subsites
 *
 * @see Notices;
 */
class EntireNetworkCloneServerConfigNotice extends BooleanNotice
{
    /**
     * The option name to store the visibility of this notice
     */
    const OPTION_NAME = 'wpstg_entire_network_clone_notice';

    public function getOptionName(): string
    {
        return self::OPTION_NAME;
    }
}
