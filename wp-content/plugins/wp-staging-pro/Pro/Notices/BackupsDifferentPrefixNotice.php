<?php

namespace WPStaging\Pro\Notices;

use WPStaging\Framework\Notices\BooleanNotice;

/**
 * Class BackupsDifferentPrefixNotice
 *
 * Show notice if backup is created on version 4.0.2 for single
 * Show notice if backup is created on version 4.3.0 or lower for multisite
 *
 * @see Notices;
 */
class BackupsDifferentPrefixNotice extends BooleanNotice
{
    /**
     * The option name to store the visibility of this notice
     */
    const OPTION_NAME = 'wpstg_different_prefix_backup_notice';

    public function getOptionName(): string
    {
        return self::OPTION_NAME;
    }
}
