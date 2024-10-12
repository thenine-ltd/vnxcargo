<?php

/**
 * @see WPStaging\Pro\Auth\LoginLinkGenerator::ajaxLoginLinkUserInterface
 *
 * @var object $clone
 * @var string $canUseMagicLogin
 */

if (!defined("WPINC")) {
    die();
}

use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Auth\LoginByLink;

$cloneId   = isset($_POST["clone"]) ? Sanitize::sanitizeString($_POST["clone"]) : '';
$cloneName = isset($_POST["cloneName"]) ? Sanitize::sanitizeString($_POST["cloneName"]) : '';

?>
<input type="hidden" id="wpstg-generate-login-link-clone-id" name="wpstg-generate-login-link-clone-id" value="<?php echo esc_attr($cloneId); ?>">
<div class="wpstg-form-horizontal">
    <div class="wpstg-form-row">
        <h3><?php esc_html_e('Generate Login Link', 'wp-staging'); ?></h3>
    </div>
    <div>
        <p>
            <?php
            echo sprintf(esc_html__("This will generate a login link for the staging site \"%s\". You can use this link to login to the staging site without having to enter your username and password. This can be useful to share quick login links with your clients or team members.", "wp-staging"), esc_html($cloneName));
            ?>
        </p>
    </div>
    <?php if (!$canUseMagicLogin) : ?>
        <div>
            <p>
                <strong class="wpstg-magic-logic-warning-title"><?php esc_html_e("Warning!", "wp-staging"); ?> </strong>
                <?php
                echo sprintf(esc_html__("It seems like magic login cannot be used on the staging site. Please update WP STAGING on the staging site before creating a magic login link!", "wp-staging"), esc_html($cloneName));
                ?>
            </p>
        </div>
    <?php endif; ?>
    <div class="wpstg-form-row">
        <label id="wpstg-generate-login-link-user-role-label">
            <?php esc_html_e("Login as", "wp-staging"); ?>

            <select name="wpstg-generate-login-link-role" id="wpstg-generate-login-link-role">
                <?php
                $roleList                                  = wp_roles()->get_names();
                $roleList[LoginByLink::WPSTG_VISITOR_ROLE] = esc_html__('Visitor', 'wp-staging');
                $roleList                                  = array_reverse($roleList);
                ?>

                <?php foreach ($roleList as $roleKey => $roleName) : ?>
                    <option value="<?php echo esc_attr($roleKey) ?>" <?php echo ($roleKey === LoginByLink::WPSTG_VISITOR_ROLE) ? 'selected="selected"' : '' ?>>
                        <?php echo esc_html(translate_user_role($roleName)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <br />
    <div>
        <label id="wpstg-generate-login-link-minutes-label" for="wpstg-generate-login-link-minutes">
            <?php esc_html_e("Login will expire in", "wp-staging"); ?>
        </label>
        <?php
        $days = [];
        for ($i = 0; $i <= 10; $i++) {
            $days[] = "{$i}";
        }
        ?>
        <select name="wpstg-generate-login-link-days" id="wpstg-generate-login-link-days">
            <?php foreach ($days as $day) : ?>
                <option value="<?php echo esc_attr($day) ?>" <?php echo ($day === '1') ? 'selected="selected"' : '' ?>>
                    <?php echo esc_html($day); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php esc_html_e("days", "wp-staging"); ?>
        <?php
        $hours = [];
        for ($i = 0; $i <= 23; $i++) {
            $hours[] = "{$i}";
        }
        ?>
        <select name="wpstg-generate-login-link-hours" id="wpstg-generate-login-link-hours">
            <?php foreach ($hours as $hour) : ?>
                <option value="<?php echo esc_attr($hour) ?>">
                    <?php echo esc_html($hour); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php esc_html_e("hours", "wp-staging"); ?>
        <?php
        $minutes = [];
        for ($i = 0; $i <= 55; $i += 5) {
            $minutes[] = "{$i}";
        }
        ?>
        <select name="wpstg-generate-login-link-minutes" id="wpstg-generate-login-link-minutes">
            <?php foreach ($minutes as $minute) : ?>
                <option value="<?php echo esc_attr($minute) ?>">
                    <?php echo esc_html($minute); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php esc_html_e("mins", "wp-staging"); ?>
    </div>
    <br />
    <hr>
    <br />
    <div id="wpstg-generate-login-link-container">
        <h3 id="wpstg-generate-login-link-head"><?php echo esc_html__("Click on the link to copy it! (Will be displayed only once)", "wp-staging") ?></h3>
        <div id="wpstg-generate-login-link-generated-container">
            <span id="wpstg-generate-login-link-generated" data-url="<?php echo esc_url($clone['url'] . '/wp-login.php?wpstg_login='); ?>"></span>
            <span id="wpstg-generate-login-link-copy-text" data-copy="<?php echo esc_attr__('Copy!', 'wp-staging'); ?>" data-copied="<?php echo esc_attr__('Copied!', 'wp-staging'); ?>"></span>
        </div>
    </div>
</div>

<p>
<button type="button" class="wpstg-prev-step-link wpstg-button--primary wpstg-button-back-arrow">
    <i class="wpstg-back-arrow"></i>
    <?php esc_html_e("Back", "wp-staging") ?>
</button>
<button
    type="button"
    id="wpstg-generate-login-link"
    data-alert-title="<?php esc_attr_e('Do you want to create a new login link?', 'wp-staging') ?>"
    data-alert-body="<?php echo esc_html__("This action will remove and invalidate all prior login links and create a new one. Do you want to proceed?", "wp-staging") ?>"
    data-confirm-btn-text="<?php esc_attr_e('Proceed', 'wp-staging') ?>"
    class="wpstg-button--blue"
    >
    <?php esc_html_e('Create Login Link', 'wp-staging'); ?>
</button>
</p>
