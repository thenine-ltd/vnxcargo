<?php

namespace WPStaging\Pro\Push\Data;

use WPStaging\Core\Utils\Logger;

class UpdateActivePluginsOptionsTable extends OptionsTablePushService
{
    const FILTER_PUSHING_UPDATE_ACTIVE_PLUGINS = 'wpstg.pushing.update_active_plugins';

    /**
     * @inheritDoc
     */
    protected function processOptionsTable()
    {
        $this->log("Updating {$this->tmpOptionsTable} active_plugins");

        // Get active_plugins from tmp tables
        $activePlugins = $this->productionDb->get_var("SELECT option_value FROM {$this->tmpOptionsTable} WHERE option_name = 'active_plugins' ");
        if (empty($activePlugins)) {
            $activePlugins = [];
        } else {
            $activePlugins = unserialize($activePlugins);
        }

        // Get active_plugins from production site
        $activePluginsProd = [];
        if ($this->tableExists($this->prodOptionsTable)) {
            $activePluginsProd = $this->productionDb->get_var("SELECT option_value FROM {$this->prodOptionsTable} WHERE option_name = 'active_plugins' ");
            $activePluginsProd = unserialize($activePluginsProd);
        }

        if (!$activePlugins) {
            $this->log("Can not get list of active plugins from from {$this->tmpOptionsTable} - DB Error {$this->productionDb->last_error}", Logger::TYPE_WARNING);
            return true;
        }

        $activePlugins = apply_filters(self::FILTER_PUSHING_UPDATE_ACTIVE_PLUGINS, $activePlugins);

        $plugin = plugin_basename(trim(WPSTG_PLUGIN_FILE));

        $activePlugins = array_filter($activePlugins, function ($pluginSlug) {
            return strpos($pluginSlug, 'wp-staging') === false;
        });

        // Only activate that WP Staging plugin which is used during PUSH
        $activePlugins[] = $plugin;

        // Activate WP STAGING Hooks Plugin if it is activated on production site
        if (array_search('wp-staging-hooks/wp-staging-hooks.php', $activePluginsProd) !== false) {
            $activePlugins[] = 'wp-staging-hooks/wp-staging-hooks.php';
        }

        // Update active_plugins
        $resultActivePlugins = $this->productionDb->query(
            "UPDATE {$this->tmpOptionsTable} SET option_value = '" . serialize($activePlugins) . "' WHERE option_name = 'active_plugins' "
        );

        if ($resultActivePlugins === false) {
            $this->log("Can not update table active_plugins in {$this->tmpOptionsTable}");
            $this->returnException("Can not update table active_plugins in {$this->tmpOptionsTable} - db error: " . $this->productionDb->last_error);
            return false;
        }

        return true;
    }
}
