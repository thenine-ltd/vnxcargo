<?php

namespace WPStaging\Pro\Push;

use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Pro\Push\Ajax\CancelPush;

class PushServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        // no-op
    }

    protected function addHooks()
    {
        add_action("wp_ajax_wpstg_cancel_push_processing", $this->container->callback(CancelPush::class, "ajaxCancelPush")); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }
}
