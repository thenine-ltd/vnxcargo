<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div style="display: none">
    <?php
    $embedded_message = "";
    $settings         = [
        'media_buttons'    => false,
        'wpautop'          => false,
        'drag_drop_upload' => false,
        'textarea_name'    => 'chat_editor_channel',
        'textarea_rows'    => 4,
        'quicktags'        => false,
        'tinymce'          => [
            'toolbar1' => 'bold, italic, underline',
            'toolbar2' => '',
            'toolbar3' => '',
        ],
    ];
    wp_editor($embedded_message, "chat_editor_channel", $settings);
    ?>
</div>


<div class="preview-section-overlay"></div>

<?php $pro_id = $this->is_pro() ? "pro" : ""; ?>

<section class="one chaty-setting-form" id="<?php echo esc_attr($pro_id) ?>" style="padding-bottom: 0;" xmlns="http://www.w3.org/1999/html">
    
    <?php
    // if($this->widget_index != "" || $this->get_total_widgets() > 0) {
    $cht_widget_title = get_option("cht_widget_title".$this->widget_index);
    if (isset($_GET['widget_title']) && empty(!$_GET['widget_title'])) {
        $cht_widget_title = $_GET['widget_title'];
    }

    if( empty( $cht_widget_title ) ) {
        $current_id     = get_option("chaty_total_settings");
        if(empty($current_id)) {
            $status           = get_option("cht_active");
            if($status === false) {
                $current_id = 1;
            } else {
                $current_id = 2;
            }
        } else {
            $current_id = $current_id+2;
        }
        $cht_widget_title  = "Widget #". $current_id;
    }
    ?>
        <div class="chaty-input mb-5">
            <label class="font-primary text-cht-gray-150 text-base block mb-3" for="cht_widget_title"><?php esc_html_e('Name', 'chaty'); ?></label>
            <input id="cht_widget_title" class="w-full sm:w-96 px-[12px_!important] text-base inline-block" type="text" name="cht_widget_title" value="<?php echo esc_attr($cht_widget_title) ?>">
        </div>
    <?php
    // } ?>

    <?php
    $social_app = get_option('cht_numb_slug'.$this->widget_index);
    $social_app = ($social_app == false) ? "whatsapp" : $social_app;
    $social_app = trim($social_app, ",");
    $social_app = explode(",", $social_app);
    $social_app = array_unique($social_app);
    $imageUrl   = CHT_PLUGIN_URL."admin/assets/images/chaty-default.png";
    ?>
    <input type="hidden" id="default_image" value="<?php echo esc_url($imageUrl)  ?>" />
    <div class="channels-icons" id="channel-list">
        <?php if ($this->socials) :
            foreach ($this->socials as $key => $social) :
                $value        = get_option('cht_social'.$this->widget_index.'_'.$social['slug']);
                $active_class = '';
                foreach ($social_app as $key_soc) :
                    if ($key_soc == $social['slug']) {
                        $active_class = 'active';
                    }
                endforeach;
                $custom_class = in_array($social['slug'], ["Link", "Custom_Link", "Custom_Link_3", "Custom_Link_4", "Custom_Link_5", "Custom_Link_6"]) ? "custom-link" : "";
                ?>

                <div class="icon icon-sm chat-channel-<?php echo esc_attr($social['slug']); ?> <?php echo esc_attr($active_class) ?> <?php echo esc_attr($custom_class) ?>" data-social="<?php echo esc_attr($social['slug']); ?>" data-title="<?php echo esc_attr($social['title']); ?>">
                    <span class="icon-box">
                        <?php echo $social['svg']; ?>
                    </span>
                    <span class="channel-title"><?php echo esc_attr($social['title']); ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="custom-channel-button">
        <a href="#">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" svg-inline="" focusable="false" tabindex="-1"><path d="M15.833 1.75H4.167A2.417 2.417 0 001.75 4.167v11.666a2.417 2.417 0 002.417 2.417h11.666a2.417 2.417 0 002.417-2.417V4.167a2.417 2.417 0 00-2.417-2.417zM10 6.667v6.666" stroke="#83A1B7" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M6.667 10h6.666" stroke="#83A1B7" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            <?php esc_html_e('Custom Channel', 'chaty'); ?>
        </a>
    </div>
    <?php if (!$this->is_pro()) : ?>
<!--        <div class="popover-upgrade-pro hidden bg-white border border-solid gap-4 border-cht-gray-150/20 shadow-lg shadow-cht-gray-50 mt-4 flex-wrap items-center overflow-hidden relative justify-between text-cht-gray-150 rounded-lg p-5 sm:p-2">-->
<!--            <span class="flex items-center">-->
<!--                <img class="hidden sm:inline-block -ml-3 -mb-3 self-end" src="--><?php //echo esc_url(CHT_PLUGIN_URL."admin/assets/images/license-left.png") ?><!--"/>-->
<!--                <p class="text-base leading-6">Free plan is <strong>limited to 2 channels.</strong> <br/> Get unlimited channels in the pro plan</p>-->
<!--            </span>-->
<!--            <a class="text-cht-primary text-sm border self-start sm:self-center border-solid border-cht-primary px-5 py-2 rounded-lg hover:text-white hover:bg-cht-primary" target="_blank" href="--><?php //echo esc_url($this->getUpgradeMenuItemUrl()) ?><!--">--><?php //esc_html_e('Activate Your License', 'chaty'); ?><!--</a>-->
<!--        </div>-->
    <?php endif; ?>
    <input type="hidden" class="add_slug" name="cht_numb_slug" value="<?php echo esc_attr(get_option('cht_numb_slug'.$this->widget_index)); ?>" id="cht_numb_slug" >


    <div class="social-channels mt-4">
        <div class="channel-empty-state relative <?php echo esc_attr(count($this->socials) == 0?"active":"") ?>">
            <img class="-left-3 sm:-left-5 md:-left-8 relative" src="<?php echo esc_url(CHT_PLUGIN_URL."admin/assets/images/empty-state-star.png") ?>"/>
            <p class="absolute top-4 left-0 text-base text-cht-gray-150 w-52 text-center opacity-60"><?php esc_html_e('So many channels to choose from...', 'chaty'); ?></p>
        </div>

        <ul id="channels-selected-list" class="channels-selected-list channels-selected">
            <?php 
                     
            if ($this->socials) {
                $social = get_option('cht_numb_slug'.$this->widget_index);

                $social = explode(",", $social);
                $social = array_unique($social);
                foreach ($social as $key_soc) {
                    foreach ($this->socials as $key => $social) {
                        if ($social['slug'] != $key_soc) {
                            // compare social media slug
                            continue;
                        }

                        $value = get_option('cht_social'.$this->widget_index.'_'.$social['slug']);
                        // get setting for media if already saved
                        include plugin_dir_path(__FILE__)."channel-setting.php";
                    }
                }
            } ?>
            <?php
            $is_pro    = $this->is_pro();
            $pro_class = ($is_pro) ? "pro" : "free";
            $text      = get_option("cht_close_button_text".$this->widget_index);
            $text      = ($text === false) ? "Hide" : $text;
            $text      = wp_unslash(wp_strip_all_tags($text));
            $allowedHTML = [
                'b'      => []
            ];
            $text = wp_strip_all_tags(wp_kses($text, $allowedHTML));
            $text = preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
            ?>
            <!-- close setting start -->
            <li class="chaty-cls-setting" data-id="" id="chaty-social-close">
                <div class="channels-selected__item pro 1 available ml-1">
                    <div class="chaty-default-settings flex">
                        <div class="move-icon">
                            <img alt="chaty" src="<?php echo esc_url(CHT_PLUGIN_URL."admin/assets/images/move-icon.png") ?>" style="opacity:0"; />
                        </div>
                        <div class="icon icon-md active" data-title="close">
                            <span id="image_data_close">
                                <svg viewBox="0 0 54 54" fill="none" xmlns="http://www.w3.org/2000/svg"><ellipse cx="26" cy="26" rx="26" ry="26" fill="#A886CD"></ellipse><rect width="27.1433" height="3.89857" rx="1.94928" transform="translate(18.35 15.6599) scale(0.998038 1.00196) rotate(45)" fill="white"></rect><rect width="27.1433" height="3.89857" rx="1.94928" transform="translate(37.5056 18.422) scale(0.998038 1.00196) rotate(135)" fill="white"></rect></svg>
                            </span>
                            <span class="default_image_close" style="display: none;">
                                 <svg viewBox="0 0 54 54" fill="none" xmlns="http://www.w3.org/2000/svg"><ellipse cx="26" cy="26" rx="26" ry="26" fill="#A886CD"></ellipse><rect width="27.1433" height="3.89857" rx="1.94928" transform="translate(18.35 15.6599) scale(0.998038 1.00196) rotate(45)" fill="white"></rect><rect width="27.1433" height="3.89857" rx="1.94928" transform="translate(37.5056 18.422) scale(0.998038 1.00196) rotate(135)" fill="white"></rect></svg>
                            </span>
                        </div>
                       <div class="sm:flex sm:items-center ml-4">
                            <div class="channels__input-box cls-btn-settings active">
                                <input type="text" class="channels__input" name="cht_close_button_text" value="<?php echo esc_attr(wp_unslash($text)) ?>" data-gramm_editor="false" >
                                <p class="input-example cls-btn-settings mt-1 active text-sm text-cht-gray-150">
                                    <?php esc_html_e('On hover Close button text', 'chaty'); ?>
                                </p>
                            </div>
                       </div>
                    </div>
                </div>
            </li>
            <!-- close setting end -->
        </ul>
        <div class="clear clearfix"></div>
        <div class="channels-selected__item disabled" style="opacity: 0; display: none;">

        </div>

        <input type="hidden" id="is_pro_plugin" value="<?php echo esc_attr($this->is_pro() ? "1" : "0"); ?>" />
        <?php if ($this->is_pro() && $this->is_expired()) : ?>
            <div class="popover">
                <a target="_blank" href="<?php echo esc_url($this->getUpgradeMenuItemUrl()); ?>">
                    <?php esc_html_e('Your Pro Plan has expired. ', 'chaty'); ?>
                    <strong><?php esc_html_e('Upgrade Now', 'chaty'); ?></strong>
                </a>
            </div>
        <?php endif; ?>
    </div>

</section>
