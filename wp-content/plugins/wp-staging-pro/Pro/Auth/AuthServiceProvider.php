<?php

namespace WPStaging\Pro\Auth;

use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Pro\Auth\LoginLinkGenerator;

class AuthServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        // no-op
    }

    protected function addHooks()
    {
        add_action("wp_ajax_wpstg_render_login_link_user_interface", $this->container->callback(LoginLinkGenerator::class, 'ajaxLoginLinkUserInterface')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_save_generated_link_data", $this->container->callback(LoginLinkGenerator::class, 'ajaxSaveGeneratedLinkData')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }
}
