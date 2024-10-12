<?php

namespace WPStaging\Pro\Traits;

trait NetworkConstantTrait
{
    /**
     * @return string
     */
    protected function getCurrentNetworkPath(): string
    {
        if (defined('PATH_CURRENT_SITE')) {
            return constant('PATH_CURRENT_SITE');
        }

        return $this->wpdb->get_var("SELECT path FROM {$this->wpdb->site}");
    }

    /**
     * @return string
     */
    protected function getCurrentNetworkDomain(): string
    {
        if (defined('DOMAIN_CURRENT_SITE')) {
            return constant('DOMAIN_CURRENT_SITE');
        }

        return $this->wpdb->get_var("SELECT domain FROM {$this->wpdb->site}");
    }
}
