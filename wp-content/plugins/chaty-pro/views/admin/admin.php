<?php
if (!defined('ABSPATH')) {
    exit;
}

// echo $widget_index = $this->widget_index; die;
if (isset($_GET['copy-from']) && is_numeric($_GET['copy-from']) && $_GET['copy-from'] >= 0) {
    if ($_GET['copy-from'] == 0) {
        $this->widget_index = "";
    } else {
        $this->widget_index = "_".$_GET['copy-from'];
    }
}

// echo $this->widget_index; die;
if (!$this->is_pro()) {
    // initialize with default values if key is not activated
    if (get_option('cht_position'.$this->widget_index) == 'custom') {
        update_option('cht_position'.$this->widget_index, 'right');
    }

    $social = get_option('cht_numb_slug'.$this->widget_index);
    $social = explode(",", $social);
    $social = array_splice($social, 0, 3);
    $social = implode(',', $social);
    update_option('cht_numb_slug'.$this->widget_index, $social);
    if (get_option('cht_custom_color'.$this->widget_index) != '') {
        update_option('cht_custom_color'.$this->widget_index, '');
        update_option('cht_color'.$this->widget_index, '#A886CD');
    }
}

$is_pro    = $this->is_pro();
$cht_token = get_option("cht_token");
$pro_class = (!$is_pro && $cht_token != '') ? 'none_pro' : '';
?>
<div class="container <?php echo esc_attr($pro_class) ?>" dir="ltr" id="chaty-container">
    <main class="main">
        <svg class="sr-only" aria-hidden="true" width="39" height="39" viewBox="0 0 39 39" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="linear-gradient" x1="0.892" y1="0.192" x2="0.128" y2="0.85" gradientUnits="objectBoundingBox">
                    <stop offset="0" stop-color="#4a64d5"></stop>
                    <stop offset="0.322" stop-color="#9737bd"></stop>
                    <stop offset="0.636" stop-color="#f15540"></stop>
                    <stop offset="1" stop-color="#fecc69"></stop>
                </linearGradient>
            </defs>
            <circle class="color-element" cx="19.5" cy="19.5" r="19.5" fill="url(#linear-gradient)"></circle>
            <path id="Path_1923" data-name="Path 1923" d="M13.177,0H5.022A5.028,5.028,0,0,0,0,5.022v8.155A5.028,5.028,0,0,0,5.022,18.2h8.155A5.028,5.028,0,0,0,18.2,13.177V5.022A5.028,5.028,0,0,0,13.177,0Zm3.408,13.177a3.412,3.412,0,0,1-3.408,3.408H5.022a3.411,3.411,0,0,1-3.408-3.408V5.022A3.412,3.412,0,0,1,5.022,1.615h8.155a3.412,3.412,0,0,1,3.408,3.408v8.155Z" transform="translate(10 10.4)" fill="#fff"></path>
            <path id="Path_1924" data-name="Path 1924" d="M45.658,40.97a4.689,4.689,0,1,0,4.69,4.69A4.695,4.695,0,0,0,45.658,40.97Zm0,7.764a3.075,3.075,0,1,1,3.075-3.075A3.078,3.078,0,0,1,45.658,48.734Z" transform="translate(-26.558 -26.159)" fill="#fff"></path>
            <path id="Path_1925" data-name="Path 1925" d="M120.105,28.251a1.183,1.183,0,1,0,.838.347A1.189,1.189,0,0,0,120.105,28.251Z" transform="translate(-96.119 -14.809)" fill="#fff"></path>
        </svg>
        <form id="cht-form" action="options.php" method="POST" enctype="multipart/form-data">
            <!-- ---------------------
            Header Step (choose your chat channel | customize social widget launcher | triggers and targeting)
            ---------------------- -->
            <div class="chaty-header z-50 flex sm:flex-wrap gap-y-3 items-center justify-between sm:justify-center lg:justify-center bg-white p-1.5 fixed top-0 left-0 w-full" id="chaty-header-tab-label">
                
                <a href="<?php echo esc_url( $this->getDashboardUrl() ) ?>">
                    <img class="max-w-[100px]" src="<?php echo esc_url(plugins_url('../../admin/assets/images/logo-color.svg', __FILE__)); ?>" alt="Chaty" class="logo">
                </a>

                <ul class="chaty-app-tabs md:flex-1 sm:flex items-start justify-between sm:min-w-[665px]">
                    <li class="m-0">
                        <a href="javascript:;" class="chaty-tab <?php echo ($step == 1) ? "active" : "completed" ?>" data-tab-id="chaty-tab-social-channel" id="chaty-social-channel" data-tab="first" data-tab-index="">
                            <span class="chaty-tabs-heading"></span>
                            <span class="sm:inline chaty-tabs-subheading"><?php esc_html_e("1. Choose your channels", "chaty") ?></span>
                            <span class="inline sm:hidden chaty-tabs-subheading"><?php esc_html_e("Channels", "chaty") ?></span>
                        </a>
                    </li>
                    <li class="my-0">
                        <a href="javascript:;" class="chaty-tab <?php echo ($step == 2) ? "active" : (($step == 3) ? "completed" : "") ?>" data-tab-id="chaty-tab-customize-widget" id="chaty-app-customize-widget" data-tab-index="" data-tab="middle" data-forced-save="yes">
                            <span class="chaty-tabs-heading"></span>
                            <span class="sm:inline chaty-tabs-subheading"><?php esc_html_e("2. Customize your widget", "chaty") ?></span>
                            <span class="inline sm:hidden chaty-tabs-subheading"><?php esc_html_e("Customization", "chaty") ?></span>
                        </a>
                    </li>
                    <li class="m-0">
                        <a href="javascript:;" class="chaty-tab <?php echo ($step == 3) ? "active" : "" ?>" data-tab-id="chaty-tab-triger-targeting" id="chaty-triger-targeting" data-tab="last" data-tab-index="" data-forced-save="yes">
                            <span class="chaty-tabs-heading"></span>
                            <span class="sm:inline chaty-tabs-subheading"><?php esc_html_e("3. Triggers and targeting", "chaty") ?></span>
                            <span class="inline sm:hidden chaty-tabs-subheading"><?php esc_html_e("Triggers", "chaty") ?></span>
                        </a>
                    </li>
                </ul>

                <!-- footer start -->
                <footer class="footer-buttons relative space-x-2 step-<?php echo esc_attr($step) ?>">
                
                    <div class="flex items-center sm:justify-center gap-5">
                        
                        <div class="flex items-center space-x-3">
                            <button type="button" class="sm:flex back-button" id="back-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M15.8333 10H4.16668" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10 15.8333L4.16668 9.99996L10 4.16663" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>Back</span>
                            </button>
                            <button type="button" class="sm:flex next-button" id="next-button">
                                <span>Next</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M4.16677 10H15.8334" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10.0001 4.16663L15.8334 9.99996L10.0001 15.8333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>

                        <span class="save-button-container">
                            <button type="submit" class="save-button" id="save-button" name="save_button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M15.8333 17.5H4.16667C3.72464 17.5 3.30072 17.3244 2.98816 17.0118C2.67559 16.6993 2.5 16.2754 2.5 15.8333V4.16667C2.5 3.72464 2.67559 3.30072 2.98816 2.98816C3.30072 2.67559 3.72464 2.5 4.16667 2.5H13.3333L17.5 6.66667V15.8333C17.5 16.2754 17.3244 16.6993 17.0118 17.0118C16.6993 17.3244 16.2754 17.5 15.8333 17.5Z" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14.1666 17.5V10.8334H5.83331V17.5" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M5.83331 2.5V6.66667H12.5" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>Save Widget</span>
                            </button>
                            <button class="arrow-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        <span>
                    </div>

                    <input type="submit" class="save-dashboard-button hidden" id="save-dashboard-button" name="save_and_view_dashboard" value="<?php esc_html_e('Save & View Dashboard', 'chaty'); ?>" />
                    <input type="hidden" name="current_step" value="<?php echo esc_attr($step) ?>" id="current_step">
                </footer>
            <!-- footer ends -->

            </div>
            <!-- end of header step -->

            <!-- responsive next prev button -->
            <div class="responsive-nav-btns footer-buttons step-<?php echo esc_attr($step) ?>">
                <button type="button" class="flex back-button" id="back-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M15.8333 10H4.16668" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 15.8333L4.16668 9.99996L10 4.16663" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Back</span>
                </button>
                <button type="button" class="flex next-button" id="next-button">
                    <span>Next</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M4.16677 10H15.8334" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10.0001 4.16663L15.8334 9.99996L10.0001 15.8333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <?php settings_fields($this->plugin_slug); ?>
            <!-- settings and preview section -->
            <section class="grid grid-cols-1 lg:grid-cols-3 overflow-x-hidden sm:overflow-visible rounded-lg border border-gray-150/40 bg-white" id="chaty-widget-body-tab">
                <!-- tabs and settings -->
                <div class="settings-column col-span-2 border-r border-gray-150/40">
                    <!--/* Social channel list section */-->
                    <div id="chaty-tab-social-channel" class="social-channel-tabs <?php echo ($step == 1) ? "active" : "" ?>">
                        <h1 class="section-title font-primary text-cht-gray-150 text-2xl border-b border-gray-150/40 px-8 py-5">
                            <strong><?php esc_html_e('Step', 'chaty'); ?> <?php esc_html_e('1', 'chaty'); ?>:</strong> <?php esc_html_e('Choose your channels', 'chaty'); ?>
                        </h1>
                        <div class="p-3 sm:p-5 md:p-8">
                            <?php require_once 'channels-section.php'; ?>
                        </div>
                    </div>

                    <!--/* Customize widget section */-->
                    <div id="chaty-tab-customize-widget" class="social-channel-tabs <?php echo ($step == 2) ? "active" : "" ?>">
                        <h1 class="section-title font-primary text-cht-gray-150 text-2xl border-b border-gray-150/40 px-8 py-5">
                            <strong><?php esc_html_e('Step', 'chaty'); ?> <?php esc_html_e('2', 'chaty'); ?>:</strong> <?php esc_html_e('Customize your widget', 'chaty'); ?>
                        </h1>
                        <div class="p-3 sm:p-5 md:p-8">
                            <?php require_once 'customize-widget-section.php'; ?>
                        </div>
                    </div>

                    <!--/* Customize widget section */-->
                    <div id="chaty-tab-triger-targeting" class="social-channel-tabs <?php echo ($step == 3) ? "active" : "" ?>">
                        <h1 class="section-title font-primary text-cht-gray-150 text-2xl border-b border-gray-150/40 px-8 py-5">
                            <strong><?php esc_html_e('Step', 'chaty');?> <?php esc_html_e('3', 'chaty'); ?>:</strong> <?php esc_html_e('Triggers and targeting', 'chaty');?>
                        </h1>
                        <div class="p-3 sm:p-5 md:p-8">
                            <?php require_once 'trigger-and-target.php'; ?>
                             <!--/* form submit button */-->
                            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce("chaty_plugin_nonce")) ?>">
                            <input type="hidden" name="cht_token" value="<?php echo esc_attr(get_option("cht_token")); ?>">
                            <?php
                            $created_on = get_option('cht_created_on'.$this->widget_index);
                            if ($created_on === false) {
                                $created_on = gmdate("Y-m-d");
                            }
                            ?>
                            <input type="hidden" name="cht_created_on" value="<?php echo esc_attr($created_on) ?>" />
                            <input type="hidden" name="button_type" value="" id="button_type" />
                            <div class="hidden">
                                <?php //submit_button(null, null, null, false); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- preview -->
                <aside class="preview-section-chaty pb-20 z-0">
                    <div class="preview sticky top-28 ease-linear duration-300" id="admin-preview">
                        <h2 class="text-white">&nbsp;</h2>
                        <div class="page border rounded-md">
                            <div class="page-header bg-cht-gray-150/10">
                                <svg width="40" height="8" viewBox="0 0 40 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="4" cy="4" r="4" fill="#C6D7E3"></circle>
                                    <circle cx="20" cy="4" r="4" fill="#C6D7E3"></circle>
                                    <circle cx="36" cy="4" r="4" fill="#C6D7E3"></circle>
                                </svg>
                            </div>
                            <div class="page-body">
                                <div class="chaty-preview border-t rounded-bl-md rounded-br-md">
                                    <div class="chaty-preview-channels">
                                    </div>
                                    <div class="chaty-preview-cta">
                                        <div class="chaty-main-cta"></div>
                                        <div class="chaty-close-cta">
                                            <div class="chaty-channel">
                                                <div class="chaty-cta">
                                                    <svg viewBox="0 0 54 54" fill="none" xmlns="http://www.w3.org/2000/svg"><ellipse cx="26" cy="26" rx="26" ry="26" fill="#A886CD" style="fill: rgb(168, 134, 205);"></ellipse><rect width="27.1433" height="3.89857" rx="1.94928" transform="translate(18.35 15.6599) scale(0.998038 1.00196) rotate(45)" fill="white"></rect><rect width="27.1433" height="3.89857" rx="1.94928" transform="translate(37.5056 18.422) scale(0.998038 1.00196) rotate(135)" fill="white"></rect></svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="switch-preview">
                            <input data-gramm_editor="false" type="radio" id="previewDesktop" name="switchPreview" class="js-switch-preview switch-preview__input" checked="checked">
                            <label for="previewDesktop" class="switch-preview__label switch-preview__desktop">
                                <svg class="pointer-events-none" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" svg-inline="" focusable="false" tabindex="-1"><path d="M16.667 15c.916 0 1.666-.75 1.666-1.667V5c0-.917-.75-1.667-1.666-1.667H3.333c-.916 0-1.666.75-1.666 1.667v8.333c0 .917.75 1.667 1.666 1.667h-2.5a.836.836 0 00-.833.833c0 .459.375.834.833.834h18.334a.836.836 0 00.833-.834.836.836 0 00-.833-.833h-2.5zM4.167 5h11.666c.459 0 .834.375.834.833V12.5a.836.836 0 01-.834.833H4.167a.836.836 0 01-.834-.833V5.833c0-.458.375-.833.834-.833z" fill="#83A1B7"></path></svg>
                            </label>
                            <input data-gramm_editor="false" type="radio" id="previewMobile" name="switchPreview" class="js-switch-preview switch-preview__input">
                            <label for="previewMobile" class="switch-preview__label switch-preview__mobile">
                                <svg class="pointer-events-none" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" svg-inline="" focusable="false" tabindex="-1"><path d="M12.916.833H6.25c-1.15 0-2.083.934-2.083 2.084v14.166c0 1.15.933 2.084 2.083 2.084h6.666c1.15 0 2.084-.934 2.084-2.084V2.917c0-1.15-.934-2.084-2.084-2.084zm-3.333 17.5c-.691 0-1.25-.558-1.25-1.25 0-.691.559-1.25 1.25-1.25.692 0 1.25.559 1.25 1.25 0 .692-.558 1.25-1.25 1.25zM13.333 15h-7.5V3.333h7.5V15z" fill="#83A1B7"></path></svg>
                            </label>
                        </div>
                        <div id="custom-css"></div>
                    </div>
                </aside>
                <div class="chaty-sticky-buttons">

                    <a href="#" class="preview-help-btn"><?php esc_html_e('Preview', 'chaty'); ?></a>

                    <?php if (!empty($this->widget_index) && $this->widget_index != '_new_widget' && false) { ?>
                        <a href="javascript:;" data-nonce="<?php echo esc_attr(wp_create_nonce("chaty_remove_".$this->widget_index)) ?>" class="remove-chaty-widget-sticky remove-chaty-options"><?php esc_html_e("Remove", "chaty") ?></a>
                    <?php } ?>
                </div>
            </section>
        </form>

        <input type="hidden" id="widget_index" value="<?php echo esc_attr($this->widget_index) ?>" />
    </main>
</div>
<input type="hidden" id="select_channel_icon_slug" />
<?php require_once 'popup.php'; ?>
<?php if (0 && isset($_GET['page']) && $_GET['page'] == "chaty-widget-settings" && !isset($_GET['copy-from'])) { ?>
    <div class="chaty-popup-form">
        <div class="chaty-popup-overlay"></div>
        <div class="chaty-popup-content">
            <div class="popup-title"><?php esc_html_e("Create a new widget", "chaty") ?></div>
            <div class="popup-description"><?php esc_html_e("Would you like to load settings from an existing widget?", "chaty") ?></div>
            <form action="<?php echo esc_url(admin_url("admin.php")) ?>" method="get">
                <div class="select-field">
                    <input type="hidden" name="page" value="chaty-widget-settings" />
                    <select name="copy-from" >
                        <option value=""><?php esc_html_e("No thanks", "chaty") ?></option>
                        <?php
                        $total_widgets = $this->get_total_widgets();
                        $menu_text     = esc_attr__('Settings', 'chaty');
                        if ($total_widgets > 0) {
                            $menu_text     = esc_html__("Settings Widget #1", "chaty");
                            $total_widgets = 1;

                            $text = get_option("cht_widget_title");
                            if (!empty($text) && $text != "" && $text != null) {
                                $menu_text = esc_html__("Settings ", "chaty").$text;
                            }
                        } else {
                            $total_widgets = 0;
                        }

                        $deleted_list = get_option("chaty_deleted_settings");
                        if (empty($deleted_list) || !is_array($deleted_list)) {
                            $deleted_list = [];
                        }

                        echo "<option value='0'>".esc_attr($menu_text)>"</option>";
                        $chaty_widgets = get_option("chaty_total_settings");
                        if (!empty($chaty_widgets) && $chaty_widgets != null && is_numeric($chaty_widgets) && $chaty_widgets > 0) {
                            for ($i = 1; $i <= $chaty_widgets; $i++) {
                                if (!in_array($i, $deleted_list)) {
                                    $cht_widget_title = get_option("cht_widget_title_".$i);
                                    if (empty($cht_widget_title) || $cht_widget_title == "" || $cht_widget_title == null) {
                                        $cht_widget_title = esc_html__("Settings Widget #", "chaty").($i + $total_widgets);
                                    } else {
                                        $cht_widget_title = esc_html__("Settings ", "chaty").$cht_widget_title;
                                    }

                                    echo "<option value='".esc_attr($i)."'".esc_attr($cht_widget_title)."</option>";
                                }
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="select-field-btn">
                    <button type="submit" class="popup-form-btn"><?php esc_html_e("Start Editing", "chaty") ?></button>
                </div>
            </form>
        </div>
    </div>
<?php }//end if

