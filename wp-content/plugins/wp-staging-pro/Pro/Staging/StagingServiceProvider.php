<?php

namespace WPStaging\Pro\Staging;

use WPStaging\Backend\Modules\Jobs\Cloning;
use WPStaging\Backend\Pro\Modules\Jobs\CloningPro;
use WPStaging\Core\CloningJobProvider;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Pro\Staging\Ajax\ExternalDatabase;
use WPStaging\Pro\Staging\Ajax\UserAccountSynchronizer;

/**
 * Used to register classes and hooks for the staging/cloning related services.
 */
class StagingServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        $this->container->make(UserAccountSynchronizer::class);
        $this->container->make(ExternalDatabase::class);

        $this->container->when(CloningJobProvider::class)
                        ->needs(Cloning::class)
                        ->give(CloningPro::class);
    }

    protected function addHooks()
    {
        add_action("wp_ajax_wpstg_sync_account", $this->container->callback(UserAccountSynchronizer::class, "ajaxSyncAccount")); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_database_connect", $this->container->callback(ExternalDatabase::class, "ajaxDatabaseConnect")); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_database_verification", $this->container->callback(ExternalDatabase::class, "ajaxDatabaseVerification")); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }
}
