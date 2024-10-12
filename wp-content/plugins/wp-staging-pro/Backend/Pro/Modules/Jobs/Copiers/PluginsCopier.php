<?php

namespace WPStaging\Backend\Pro\Modules\Jobs\Copiers;

/**
 * Class PluginsCopier
 *
 * Copies plugins.
 *
 * @package WPStaging\Backend\Pro\Modules\Jobs\Copiers
 */
class PluginsCopier extends Copier
{
    /**
     * @return string
     */
    protected function getCopierType()
    {
        return Copier::TYPE_PLUGIN;
    }
}
