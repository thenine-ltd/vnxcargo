<?php
if (!defined('ABSPATH')) {
    exit;
}

$total_widgets = $this->get_total_widgets();
$deleted_list  = get_option("chaty_deleted_settings");
if (empty($deleted_list) || !is_array($deleted_list)) {
    $deleted_list = [];
}

$chaty_widget  = [];
$chaty_widgets = get_option("chaty_total_settings");
if (!empty($chaty_widgets) && $chaty_widgets != null && is_numeric($chaty_widgets) && $chaty_widgets > 0) {
    for ($i = 1; $i <= $chaty_widgets; $i++) {
        if (!in_array($i, $deleted_list)) {
            $chaty_widget[] = $i;
        }
    }
}

$chaty_widgets = [];
$widget        = "";
$is_deleted    = get_option("cht_is_default_deleted");
if ($is_deleted === false) {
    $cht_widget_title = get_option("cht_widget_title");
    $cht_widget_title = empty($cht_widget_title) ? "Widget-1" : $cht_widget_title;
    $status           = get_option("cht_active");
    $date            = get_option("cht_created_on");
    $date_status     = ($date === false || empty($date)) ? 0 : 1;
    $widget          = [
        'title'      => $cht_widget_title,
        'index'      => 0,
        'nonce'      => wp_create_nonce("chaty_remove__0"),
        'status'     => $status,
        'created_on' => $date,
    ];
    $chaty_widgets[] = $widget;
}

if (!empty($chaty_widget)) {
    foreach ($chaty_widget as $i) {
        $cht_widget_title = get_option("cht_widget_title_".$i);
        if (empty($cht_widget_title) || $cht_widget_title == "" || $cht_widget_title == null) {
            $cht_widget_title = " Widget #".($i + 1);
        }

        $status          = get_option("cht_active_".$i);
        $date            = get_option("cht_created_on_".$i);
        $widget          = [
            'title'      => $cht_widget_title,
            'index'      => $i,
            'nonce'      => wp_create_nonce("chaty_remove__".$i),
            'status'     => $status,
            'created_on' => $date,
        ];
        $chaty_widgets[] = $widget;
        $date_status     = (($date === false || empty($date)) && !$date_status) ? 0 : 1;
    }
}
?>
<div class="wrap">
    <h2></h2>
    <div class="container" dir="ltr">
        <header class="header flex flex-col items-start sm:flex-row sm:justify-between">
            <a href="<?php echo esc_url( $this->getDashboardUrl() ) ?>">
                <img src="<?php echo esc_url(plugins_url('../../admin/assets/images/logo-color.svg', __FILE__)); ?>" alt="Chaty" class="logo">
            </a>

            <?php settings_errors(); ?>
            <div class="flex sm:self-end flex-col sm:flex-row items-baseline sm:items-center space-y-4 sm:space-y-0 sm:space-x-5 mt-5 sm:mt-0">
                
                <?php
                if (count($chaty_widgets)):
                    if (!$this->data_check() && !$this->is_pro()) { ?>
                        <a class="btn bg-transparent text-cht-gray-150 border-cht-gray-150/70 text-base hover:text-cht-gray-150 font-normal rounded-lg hover:bg-cht-gray-150/10" href="<?php echo esc_url(admin_url("admin.php?page=chaty-upgrade")) ?>">
                            <?php esc_html_e('Create New Widget', 'chaty'); ?>
                        </a>
                    <?php } else { ?>
                        <a class="btn bg-transparent text-cht-gray-150 border-cht-gray-150/70 text-base hover:text-cht-gray-150 font-normal rounded-lg hover:bg-cht-gray-150/10" href="<?php echo esc_url(admin_url("admin.php?page=chaty-widget-settings")) ?>">
                            <?php esc_html_e('Create New Widget', 'chaty'); ?>
                        </a>
                    <?php }
                else: ?>
                    <a class="btn bg-transparent text-cht-gray-150 border-cht-gray-150/70 text-base hover:text-cht-gray-150 font-normal rounded-lg hover:bg-cht-gray-150/10" href="<?php echo esc_url(admin_url("admin.php?page=chaty-app&widget=0")) ?>"><?php esc_html_e("Create Widget", "chaty") ?></a>
                <?php endif; ?>

                <?php if ($this->data_check() && $this->is_pro()) { ?>
                    <a class="btn bg-transparent text-cht-primary rounded-lg" target="_blank" href="<?php echo esc_url($this->getUpgradeMenuItemUrl()); ?>">
                        <?php esc_html_e('Renew now', 'chaty'); ?>
                    </a>
                    <?php if ($this->is_pro()) {
                        $active_license = $this->active_license();
                        ?>
                        <p class="text-base text-cht-primary-100 font-semibold font-primary italic"><?php esc_html_e('Your Pro plan expires on', 'chaty'); ?> <?php echo esc_attr(gmdate('F jS, Y', strtotime($active_license))); ?></p>
                    <?php } ?>
                <?php } else if ($this->is_expired()) {
                    $licenseKey = $this->get_token();
                    $expired_on = $this->is_expired()
                    ?>
                    <span class="text-base text-cht-primary-100 font-semibold font-primary italic">Your pro plan has expired on <?php echo esc_attr(gmdate('F jS, Y', strtotime($expired_on))) ?></span>
                    <a target="_blank" href="<?php echo esc_url(CHT_CHATY_PLUGIN_URL."/checkout/?edd_license_key=".$licenseKey."&download_id=".CHT_CHATY_PLUGIN_ID) ?>" class="renew-button"><?php esc_html_e("Renew Now", 'chaty') ?></a>
                <?php } else if (!$this->data_check() && !$this->is_pro()) { ?>
                    <a class="text-cht-primary border border-solid border-cht-primary px-5 py-2.5 text-base rounded-lg flex items-center space-x-2 transition duration-200 hover:text-cht-primary hover:bg-cht-primary/10" href="<?php echo esc_url($this->getUpgradeMenuItemUrl()); ?>">
                        <?php esc_html_e('Enter License Key', 'chaty'); ?>
                    </a>
                <?php } else {
                    $licenseKey      = get_option("cht_token");
                    $licenseData     = $this->getLicenseKeyInformation($licenseKey);
                    $isLicenseActive = 0;
                    if (!empty($licenseData)) {
                        if ($licenseData['license'] == "valid") {
                            $isLicenseActive = 1;
                        }

                        if ($licenseData['license'] == "expired") {
                            $isLicenseActive = 2;
                        }
                    }

                    $newVersion     = "";
                    $active_license = $this->active_license();
                    if ($isLicenseActive == 1 && $licenseData['expires'] == "lifetime") { ?>
                        <p class="text-base text-cht-primary-100 font-semibold font-primary italic"><?php esc_html_e("You have a lifetime license", "chaty") ?></p>
                    <?php } else { ?>
                        <p class="text-base text-cht-primary-100 font-semibold font-primary italic"><?php printf(esc_html__("Your pro plan is valid until %1\$s", "chaty"), esc_attr(gmdate('F jS, Y', strtotime($active_license)))) ?></p>
                    <?php } ?>
                <?php }//end if
                ?>
            </div>
        </header>
        <?php if (count($chaty_widgets)) { ?>
            <div class="chaty-table sm:mt-5">
                <div class="responsive-table dashboard">
                    <table class="border-separate w-full rounded-lg border border-cht-gray-50" cellspacing="0" cellpadding="0">
                        <thead>
                            <tr>
                                <th class="w-28 rounded-tl-lg text-cht-gray-150 text-sm font-semibold font-primary py-3 px-2 bg-cht-primary-50"><?php esc_html_e("Status", 'chaty'); ?></th>
                                <th class="text-left text-cht-gray-150 text-sm font-semibold font-primary py-3 px-5 bg-cht-primary-50"><?php esc_html_e("Widget name", 'chaty'); ?></th>
                                <?php if ($date_status) { ?>
                                <th class="text-left text-cht-gray-150 text-sm font-semibold font-primary py-3 px-5 bg-cht-primary-50"><?php esc_html_e("Created On", 'chaty'); ?></th>
                                <?php } ?>
                                <th class="w-36 rounded-tr-lg text-cht-gray-150 text-sm font-semibold font-primary py-3 px-2 bg-cht-primary-50"><?php esc_html_e("Actions", 'chaty'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chaty_widgets as $widget) { ?>
                                <tr id="widget_<?php echo esc_attr($widget['index']) ?>" data-widget="<?php echo esc_attr($widget['index']) ?>" data-nonce="<?php echo esc_attr($widget['nonce']) ?>">
                                    <td class="bg-white py-3.5 px-5 text-cht-gray-150 font-primary text-sm text-center">
                                        <label class="chaty-switch" for="trigger_on_time<?php echo esc_attr($widget['index']) ?>">
                                            <input type="checkbox" class="change-chaty-status" name="chaty_trigger_on_time" id="trigger_on_time<?php echo esc_attr($widget['index']) ?>" value="yes" <?php checked($widget['status'], 1) ?>>
                                            <div class="chaty-slider round"></div>
                                        </label>
                                    </td>
                                    <td class="border-t bg-white border-x py-3.5 text-cht-gray-150 font-primary text-sm text-left px-5 border-cht-gray-50 widget-title"><?php echo esc_attr($widget['title']) ?></td>
                                    <?php if ($date_status) { ?>
                                        <?php if (!empty($widget['created_on'])) {?>
                                            <td class="border-t bg-white py-3.5 text-cht-gray-150 font-primary text-sm text-left px-5 border-r border-cht-gray-50"><?php echo esc_attr(gmdate("F j, Y", strtotime($widget['created_on']))) ?></td>
                                        <?php } else { ?>
                                            <td>&nbsp;</td>
                                        <?php } ?>
                                    <?php } ?>
                                    <td class="bg-white py-3.5 px-5">
                                        <div class="font-primary text-cht-gray-150 relative">
                                            <div class="flex items-stretch justify-center">
                                                <a class="border-l text-center text-xs border-t border-b border-cht-gray-150/30 px-2.5 py-1 rounded-tl-md rounded-bl-md inline-block duration-200 ease-linear hover:bg-cht-gray-150/10 focus:text-cht-gray-150" href="<?php echo esc_url(admin_url("admin.php?page=chaty-app&widget=".esc_attr($widget['index']))) ?>"><?php esc_html_e("Edit", "chaty") ?></a>
                                                <span class="action-dropdown-btn border border-cht-gray-150/30 rounded-tr-md  rounded-br-md px-1 inline-block cursor-pointer duration-200 ease-linear hover:bg-cht-gray-150/10">
                                                    <svg class="pointer-events-none" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" svg-inline="" focusable="false" tabindex="-1"><path d="M8 8.667a.667.667 0 100-1.334.667.667 0 000 1.334zM8 4a.667.667 0 100-1.333A.667.667 0 008 4zM8 13.333A.667.667 0 108 12a.667.667 0 000 1.333z" stroke="currentColor" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                                </span>
                                            </div>

                                            <div class="action-dropdown z-10 hidden absolute top-full mt-1 right-4 bg-white p-3 rounded-lg simple-shadow border border-cht-gray-150/10">
                                                <a class="clone-widget flex items-center text-base rounded-lg py-2 px-3 w-36 hover:bg-cht-primary/10 hover:text-cht-gray-150 space-x-2" href="javascript:;">
                                                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" svg-inline="" focusable="false" tabindex="-1"><path d="M13.333 6h-6C6.597 6 6 6.597 6 7.333v6c0 .737.597 1.334 1.333 1.334h6c.737 0 1.334-.597 1.334-1.334v-6c0-.736-.597-1.333-1.334-1.333z" stroke="currentColor" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"></path><path d="M3.333 10h-.666a1.333 1.333 0 01-1.334-1.333v-6a1.333 1.333 0 011.334-1.334h6A1.333 1.333 0 0110 2.667v.666" stroke="currentColor" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                                    <span><?php esc_html_e("Duplicate", "chaty") ?></span>
                                                </a>
                                                <a class="rename-widget text-base rounded-lg py-2 px-3 flex items-center w-36 hover:bg-cht-primary/10 hover:text-cht-gray-150 space-x-2" href="javascript:;">
                                                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" svg-inline="" focusable="false" tabindex="-1"><path d="M14.166 2.5A2.357 2.357 0 0117.5 5.833L6.25 17.083l-4.583 1.25 1.25-4.583L14.166 2.5z" stroke="#83A1B7" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                                    <span><?php esc_html_e("Rename", "chaty") ?></span>
                                                </a>
                                                <hr class="border border-cht-gray-150/10 my-1">
                                                <a class="remove-widget text-base rounded-lg py-2 px-3 flex items-center w-36 hover:bg-cht-primary/10 hover:text-cht-gray-150 space-x-2" href="javascript:;">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" svg-inline="" focusable="false" tabindex="-1"><path d="M2 4h12M5.333 4V2.667a1.333 1.333 0 011.334-1.334h2.666a1.333 1.333 0 011.334 1.334V4m2 0v9.333a1.334 1.334 0 01-1.334 1.334H4.667a1.334 1.334 0 01-1.334-1.334V4h9.334z" stroke="#ff424d" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                                    <span class="text-cht-red "><?php esc_html_e("Delete", "chaty") ?></span>
                                                </a>
                                            </div>
                                        </div>
                                        
                                    </td>
                                </tr>
                            <?php }//end foreach
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } else { ?>
            <div class="chaty-table no-widgets py-10 bg-cover rounded-lg border border-cht-gray-50">
                <img class="mx-auto w-60" src="<?php echo esc_url(CHT_PLUGIN_URL) ?>/admin/assets/images/stars-image.png" alt="" />
                <p class="font-primary text-base text-cht-gray-150 -mt-2 max-w-screen-sm px-5 mx-auto"><?php esc_html_e("Create widgets for WhatsApp, Facebook Messenger, Telegram & 20+ more channels. Adding a new Chaty widget takes a minute - start now  🚀", "chaty") ?></p>
                <div class="flex items-center space-x-3 mt-5 justify-center">
                    <a class="btn rounded-lg drop-shadow-3xl" href="<?php echo esc_url(admin_url("admin.php?page=chaty-app&widget=0")) ?>"><?php esc_html_e("Create Widget", "chaty") ?></a>
                </div>
            </div>
        <?php }//end if
        ?>
    </div>
</div>
<div class="chaty-popup" id="clone-widget">
    <div class="chaty-popup-outer"></div>
    <div class="chaty-popup-inner popup-pos-bottom">
        <div class="chaty-popup-content">
            <div class="chaty-popup-close">
                <a href="javascript:void(0)" class="close-delete-pop close-chaty-popup-btn right-2 top-2 relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M15.6 15.5c-.53.53-1.38.53-1.91 0L8.05 9.87 2.31 15.6c-.53.53-1.38.53-1.91 0s-.53-1.38 0-1.9l5.65-5.64L.4 2.4C-.13 1.87-.13 1.02.4.49s1.38-.53 1.91 0l5.64 5.63L13.69.39c.53-.53 1.38-.53 1.91 0s.53 1.38 0 1.91L9.94 7.94l5.66 5.65c.52.53.52 1.38 0 1.91z"></path></svg>
                </a>
            </div>
            <form class="" action="<?php echo esc_url(admin_url("admin.php?page=chaty-widget-settings")) ?>" method="get">
                <div class="a-card a-card--normal">
                    <div class="chaty-popup-header text-left font-primary text-cht-gray-150 font-medium p-5 relative">
                        <?php esc_html_e("Duplicate Widget?", "chaty" ) ?>
                    </div>
                    <div class="chaty-popup-body px-8 py-5">
                        <p class=" text-base font-primary text-cht-gray-150">
                            <?php esc_html_e("Please select a name for your new duplicate widget", "chaty" ) ?>
                        </p>
                       
                        <div class="chaty-popup-input">
                            <input type="text" name="widget_title" id="widget_title">
                            <input type="hidden" name="copy-from" id="widget_clone_id">
                            <input type="hidden" name="page" value="chaty-widget-settings">
                        </div>
                    </div>
                    <input type="hidden" id="delete_widget_id" value="">
                    <div class="flex justify-end py-2 pb-5 px-8 space-x-5">
                        <button type="button" class="rounded-lg btn bg-transparent border-cht-gray-150 text-cht-gray-150 hover:text-cht-gray-150 hover:bg-cht-gray-150/10 btn-default close-chaty-popup-btn"><?php esc_html_e("Cancel", "chaty" ) ?></button>
                        <button type="submit" class="btn rounded-lg btn-primary drop-shadow-3xl"><?php esc_html_e("Create Widget", "chaty" ) ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="chaty-popup" id="rename-widget">
    <div class="chaty-popup-outer"></div>
    <div class="chaty-popup-inner popup-pos-bottom">
        <div class="chaty-popup-content">
            <div class="chaty-popup-close">
                <a href="javascript:void(0)" class="close-delete-pop close-chaty-popup-btn right-2 top-2 relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M15.6 15.5c-.53.53-1.38.53-1.91 0L8.05 9.87 2.31 15.6c-.53.53-1.38.53-1.91 0s-.53-1.38 0-1.9l5.65-5.64L.4 2.4C-.13 1.87-.13 1.02.4.49s1.38-.53 1.91 0l5.64 5.63L13.69.39c.53-.53 1.38-.53 1.91 0s.53 1.38 0 1.91L9.94 7.94l5.66 5.65c.52.53.52 1.38 0 1.91z"></path></svg>
                </a>
            </div>
            <form class="" action="<?php echo esc_url(admin_url("admin.php?page=chaty-widget-settings")) ?>" method="get" id="rename-widget-form">
                <div class="a-card a-card--normal">
                    <div class="chaty-popup-header text-left font-primary text-cht-gray-150 font-medium p-5 relative">
                        <?php esc_html_e("Rename Widget", "chaty") ?>
                    </div>
                    <div class="chaty-popup-body px-8 py-5">
                        <p class=" text-base font-primary text-cht-gray-150">
                            <?php esc_html_e("Enter new name for widget", "chaty") ?>
                        </p>
                        <div class="chaty-popup-input">
                            <input type="text" name="widget_title" id="widget_new_title">
                            <input type="hidden" name="widget_id" id="widget_rename_id">
                            <input type="hidden" name="page" value="chaty-widget-settings">
                        </div>
                    </div>
                    <input type="hidden" id="delete_widget_id" value="">
                    <div class="flex justify-end py-5 px-8 space-x-5">
                        <button type="button" class="rounded-lg btn bg-transparent border-cht-gray-150 text-cht-gray-150 hover:text-cht-gray-150 hover:bg-cht-gray-150/10 btn-default close-chaty-popup-btn">Cancel</button>
                        <button type="submit" class="btn rounded-lg btn-primary drop-shadow-3xl"><?php esc_html_e("Rename Widget", "chaty") ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="chaty-popup" id="delete-widget">
    <div class="chaty-popup-outer"></div>
    <div class="chaty-popup-inner popup-pos-bottom">
        <div class="chaty-popup-content">
            <div class="chaty-popup-close">
                <a href="javascript:void(0)" class="close-delete-pop close-chaty-popup-btn right-2 top-2 relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M15.6 15.5c-.53.53-1.38.53-1.91 0L8.05 9.87 2.31 15.6c-.53.53-1.38.53-1.91 0s-.53-1.38 0-1.9l5.65-5.64L.4 2.4C-.13 1.87-.13 1.02.4.49s1.38-.53 1.91 0l5.64 5.63L13.69.39c.53-.53 1.38-.53 1.91 0s.53 1.38 0 1.91L9.94 7.94l5.66 5.65c.52.53.52 1.38 0 1.91z"></path></svg>
                </a>
            </div>
            <div class="a-card a-card--normal">
                <div class="chaty-popup-header text-left font-primary text-cht-gray-150 font-medium p-5 relative">
                    <?php esc_html_e("Delete Widget?", "chaty") ?>
                </div>
                <div class="chaty-popup-body p-5 text-base font-primary text-cht-gray-150">
                    <?php esc_html_e("Are you sure you want to delete this widget?", "chaty") ?>
                </div>
                <input type="hidden" id="delete_widget_id" value="">
                <div class="flex justify-end p-5 space-x-5">
                    <button type="button" class="rounded-lg btn btn-default close-chaty-popup-btn bg-transparent border-cht-gray-150 text-cht-gray-150 hover:text-cht-gray-150 hover:bg-cht-gray-150/10"><?php esc_html_e("Cancel", "chaty") ?></button>
                    <button type="button" class="rounded-lg bg-transparent border-red-500 text-red-500 hover:bg-red-500/10 hover:text-red-500 btn btn-primary" id="delete-widget-btn" onclick="javascript:removeWidgetItem();"><?php esc_html_e("Delete Widget", "chaty") ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "review-popup.php" ?>

<script>
var dataWidget = -1;
jQuery(document).ready(function () {

    jQuery(document).on("click", ".clone-widget", function(){
        <?php if (!$this->data_check() && !$this->is_pro()) { ?>
            window.location = "<?php echo esc_url($this->getUpgradeMenuItemUrl()); ?>";
        <?php } else { ?>
            var WidgetId = jQuery(this).closest("tr").data("widget");
            jQuery("#widget_clone_id").val(WidgetId);
            var WidgetName = jQuery(this).closest("tr").find(".widget-title").text();
            jQuery("#widget_title").val(WidgetName);
            jQuery("#clone-widget").show();
        <?php } ?>
    });

    jQuery(document).on("click", ".change-chaty-status", function(e){
        dataWidget = jQuery(this).closest("tr").data("widget");
        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'change_chaty_widget_status',
                widget_nonce: jQuery("#widget_"+dataWidget).data("nonce"),
                widget_index: "_"+jQuery("#widget_"+dataWidget).data("widget")
            },
            beforeSend: function (xhr) {

            },
            success: function (res) {

            },
            error: function (xhr, status, error) {

            }
        });
    });

    jQuery(document).on("click", ".remove-widget", function(){
        dataWidget = jQuery(this).closest("tr").data("widget");
        jQuery("#delete-widget").show();
    });

    /* IIFE: action column button show hide scripts */ 
    (()=>{

        let $openedScope = null;
        function showHideDropdown( $scope, toggle = false ) {
            if( $scope && toggle ) {
                $scope.find('.action-dropdown-btn').addClass('bg-cht-gray-150/10');
                $scope.find('.action-dropdown').slideDown(250, 'linear');
            } 
            if( $scope && !toggle ) {
                $scope.find('.action-dropdown-btn').removeClass('bg-cht-gray-150/10');
                $scope.find('.action-dropdown').slideUp(250, 'linear');
            }
        }

        // show element when click on 3 dots
        jQuery('.action-dropdown-btn').on('click', function(){
            const $scope = jQuery(this).parent().parent();
            const isSame = $scope.is( $openedScope );          
            if( !isSame ) {
                showHideDropdown( $openedScope )    
                showHideDropdown( $scope, true );
                $openedScope = $scope;
            } 
            else {
                showHideDropdown( $scope, false );
                $openedScope = null;
            }
        })

        //hide elemnt when click out of element
        jQuery(document).on('click', function(ev){
            if( !ev.target.closest('.action-dropdown-btn')) {
                showHideDropdown($openedScope)
                $openedScope = null
            }
        })

        // show rename modal
        jQuery(".rename-widget").on('click', function(){
            jQuery(".chaty-popup-content").removeClass("form-loading");
            jQuery('#rename-widget').show();
            var WidgetId = jQuery(this).closest("tr").data("widget");
            jQuery("#widget_rename_id").val(WidgetId);
            var WidgetName = jQuery(this).closest("tr").find(".widget-title").text();
            jQuery("#widget_new_title").val(WidgetName);
            dataWidget = WidgetId;
        });

        // rename form submit
        jQuery(document).on("submit", "#rename-widget-form", function(e){
            jQuery("#rename-widget .chaty-popup-content").removeClass("form-loading");
            jQuery("#widget_new_title").removeClass("input-error");
            jQuery(this).find(".form-error-message").remove();
            if(jQuery("#widget_new_title").val() == "") {
                jQuery("#widget_new_title").addClass("input-error");
                jQuery("#widget_new_title").after("<span class='form-error-message'>Widget name is required</span>");
            } else {
                jQuery("#rename-widget .chaty-popup-content").addClass("form-loading");
                jQuery.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'rename_chaty_widget',
                        widget_nonce: jQuery("#widget_"+dataWidget).data("nonce"),
                        widget_index: "_"+jQuery("#widget_"+dataWidget).data("widget"),
                        widget_title: jQuery("#widget_new_title").val()
                    },
                    beforeSend: function (xhr) {
                        jQuery("#rename-widget-form .btn-primary").prop("disabled", true);

                    },
                    success: function (res) {
                        window.location = res;
                    },
                    error: function (xhr, status, error) {

                    }
                });
            }
            return false;
        });

    })()

});

function removeWidgetItem() {
    if(dataWidget == -1) {
        return;
    }
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'remove_chaty_widget',
            widget_nonce: jQuery("#widget_"+dataWidget).data("nonce"),
            widget_index: "_"+jQuery("#widget_"+dataWidget).data("widget")
        },
        beforeSend: function (xhr) {
            jQuery("#delete-widget .chaty-popup-content").addClass("form-loading");
            jQuery("#delete-widget-btn").prop("disabled", true);

        },
        success: function (res) {
            window.location = res;
        },
        error: function (xhr, status, error) {

        }
    });
}
</script>
