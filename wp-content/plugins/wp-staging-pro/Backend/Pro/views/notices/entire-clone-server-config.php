<?php

/**
 * @var \WPStaging\Pro\Notices\Notices $this
 * @var bool                           $isApache
 * @var string                         $server
 * @see \WPStaging\Pro\Notices\Notices::renderNotices
 */

use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\Facades\Escape;

?>
<div class="notice notice-warning wpstg-entire-clone-server-config-notice">
    <p>
        <strong><?php esc_html_e('WP STAGING - Clone Multisite Network', 'wp-staging'); ?></strong> <br/>
        <?php
        if ($isApache) {
            esc_html_e("We are unable to add an .htaccess file for this staging multisite network. It is required to make sure network URLs work properly. You will need to add this file manually.", "wp-staging");
        } else {
            $server = strtoupper($server);
            echo sprintf(esc_html__("Your site runs on %s webserver. Please configure your server to make sure your staging network site URLs work properly.", "wp-staging"), esc_html($server));
        }

        echo sprintf(
            Escape::escapeHtml(__(' Read <a href="%s" target="_blank">this article</a> on how to do it.', 'wp-staging')),
            'https://wp-staging.com/docs/activate-permalinks-staging-site/#NGINX_Multisite_in_Subfolder/'
        );
        ?>
    </p>
    <p>
      <?php Notices::renderNoticeDismissAction(
          $this->getNoticesViewPath(),
          'entire_clone_server_config',
          '.wpstg_dismiss_entire_clone_server_config_notice',
          '.wpstg-entire-clone-server-config-notice'
      ) ?>
    </p>
</div>
