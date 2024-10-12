<?php

/**
 * @see WPStaging\Backend\Administrator::getLicensePage
 *
 * @var object $license
 */

$message = '';
?>
<div class="wpstg_admin" id="wpstg-clonepage-wrapper">
    <?php

    require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/header.php');

    $isActiveLicensePage = true;
    require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/main-navigation.php');

    $customerName        = !empty($license->customer_name) ? $license->customer_name : '[unknown name]';
    $customerEmail       = !empty($license->customer_email) ? $license->customer_email : '[unknown email address]';
    $buttonText3Sites    = __('Upgrade to 3 sites', 'wp-staging');
    $buttonText25Sites   = __('Upgrade to 25 sites', 'wp-staging');
    $buttonText99Sites   = __('Upgrade to 99 sites', 'wp-staging');
    $licensePriceId      = empty($license->price_id) ? '' : $license->price_id;
    $licenseId           = empty($license->license_id) ? '' : $license->license_id;
    $upgradeUrl          = "https://wp-staging.com/checkout/?nocache=true&edd_action=sl_license_upgrade&license_id=$licenseId&upgrade_id=";
    $displayStyle        = ($licensePriceId === '13') ? ' wpstg-license-upgrade-developer' : '';
    $licenseExpiry       = !empty($license->expires) ? date_i18n(get_option('date_format'), strtotime($license->expires, current_time('timestamp'))) : '';
    $upgradeLicenseStyle = ($licensePriceId === '3') ? ' wpstg-license-upgraded' : '';
    $numberOfLoadingBars = (isset($license->license) && $license->license === 'valid') ? 8 : 3;
    $numberOfLoadingBars = ($licensePriceId === '3') ? 6 : $numberOfLoadingBars;    ?>
    <div class="wpstg-loading-bar-container">
        <div class="wpstg-loading-bar"></div>
    </div>
    <div class="wpstg-metabox-holder wpstg-license-wrapper">
        <?php include(WPSTG_PLUGIN_DIR . 'Backend/views/_main/loading-placeholder.php');?>
        <div class="wpstg-license-message-wrapper">
            <form method="post" action="#">
                <?php if (isset($license->license) && $license->license === 'valid') : ?>
                    <h3 class="wpstg-license-heading"><?php esc_html_e('WP Staging Pro is activated.', 'wp-staging'); ?></h3>
                    <input type="hidden" name="wpstg_deactivate_license" value="1">
                    <input type="submit" class="wpstg-border-thin-button wpstg-button wpstg-license-deactivate-button" value="<?php esc_attr_e('Deactivate License', 'wp-staging'); ?>">
                <?php else : ?>
                    <div> <?php esc_html_e('Enter your license key to activate WP STAGING | PRO.', 'wp-staging'); ?> </div>
                    <div>
                        <?php esc_html_e('You can buy a license key on', 'wp-staging'); ?>
                        <a href="https://wp-staging.com?utm_source=wpstg-license-ui&utm_medium=website&utm_campaign=enter-license-key&utm_id=purchase-key&utm_content=wpstaging" target="_blank"><?php echo esc_html('wp-staging.com') ?></a>
                    </div>
                    <div class="wpstg-license-activate-wrapper">
                        <label for="wpstg_input_field_license_key"></label><input type="text" name="wpstg_license_key" id="wpstg_input_field_license_key" placeholder="<?php esc_attr_e('Please enter your license key', 'wp-staging');?>" value='<?php echo esc_attr(get_option('wpstg_license_key', '')); ?>'>
                        <input type="hidden" name="wpstg_activate_license" value="1">
                        <input type="submit" class="wpstg-button wpstg-blue-primary wpstg-license-activate-button" value="<?php esc_attr_e('Activate License', 'wp-staging'); ?>">
                    </div>
                <?php endif;
                wp_nonce_field('wpstg_license_nonce', 'wpstg_license_nonce');
                ?>
            </form>

            <?php if (isset($license->license) && $license->license === 'valid') : ?>
                <div class="wpstg-license-active-message">
                    <div> <?php echo esc_html__('This license is active until ', 'wp-staging') .  esc_html($licenseExpiry);?></div>
                    <div> <?php echo esc_html__(' Registered to ', 'wp-staging') . esc_html($customerName) . ' (' . esc_html($customerEmail) . ')'; ?> </div>
                    <div class="wpstg-license-refresh-wrapper">
                        <a href="javascript:void(0)" id="wpstg-refresh-license-link" title="<?php esc_html__("Refresh License Status", "wp-staging") ?>" > <?php echo esc_html__("Refresh License Status", "wp-staging") ?></a>
                        <span id="wpstg-refresh-license-loader" class="wpstg-loader"></span>
                    </div>
                    <div class="wpstg-license-actions-container">
                        <div class="wpstg-license-upgrade <?php echo esc_attr($upgradeLicenseStyle) ?>">
                            <?php if ($licensePriceId === '1') :?>
                                <a href="<?php echo esc_url($upgradeUrl . '4') ?>" target="_blank" class="wpstg-license-upgrade-button wpstg-button" title="<?php echo esc_html($buttonText3Sites);?>"> <?php echo esc_html($buttonText3Sites) ?> </a>
                            <?php endif; ?>

                            <?php if ($licensePriceId === '1' || $licensePriceId === '7') :?>
                                <a href="<?php echo esc_url($upgradeUrl . '6') ?>" target="_blank" class="wpstg-license-upgrade-button wpstg-button" title="<?php echo esc_html($buttonText25Sites);?>"> <?php echo esc_html($buttonText25Sites) ?> </a>
                            <?php endif; ?>

                            <?php if ($licensePriceId === '1' || $licensePriceId === '7' || $licensePriceId === '13') :?>
                                <a href="<?php echo esc_url($upgradeUrl . '5') ?>" target="_blank" class="wpstg-license-upgrade-button wpstg-button <?php echo esc_attr($displayStyle) ?>" title="<?php echo esc_html($buttonText99Sites);?>"> <?php echo esc_html($buttonText99Sites) ?> </a>
                            <?php endif; ?>
                        </div>
                        <div class="wpstg-license-manage">
                            <a href="https://wp-staging.com/your-account" id="wpstg--button--manage--license" class="wpstg-button--blue" target="_blank"> <?php echo esc_html__("Manage License in Your Account", "wp-staging") ?> </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($license->error) && $license->error === 'expired') : ?>
                <span class="wpstg--red"> <?php echo esc_html__('Your license expired on ', 'wp-staging') . esc_html($licenseExpiry);?> </span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/footer.php');
?>
