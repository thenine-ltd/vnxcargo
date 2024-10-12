<?php

namespace WPStaging\Backend\Pro\Modules\Jobs\Copiers;

/**
 * Class ThemesCopier
 *
 * Copies themes.
 *
 * @package WPStaging\Backend\Pro\Modules\Jobs\Copiers
 */
class ThemesCopier extends Copier
{
    /**
     * @return string
     */
    protected function getCopierType()
    {
        return Copier::TYPE_THEME;
    }
}
