<?php
    /**
    * 
    *
    *
    * Output content of settings
    */
    function custom_admin_interface_pro_feedback(){
        
        //get styles and scripts
        wp_enqueue_script(array('common-script','fontawesome'));
        wp_enqueue_style(array('common-style'));

        //section name
        $section = 'feedback';

        //do header
        echo custom_admin_interface_pro_settings_page_header(__('Feedback','custom-admin-interface-pro'));

        echo __('I am keen to hear your feedback on how this plugin can be improved to provide additional benefits to pro users. If there\'s something you can\'t do but want to do please <a href="mailto:info@northernbeacheswebsites.com.au">email me</a>. Whilst I can\'t promise the feature will be implemented, if it\'s a decent idea that others will like I will try my very best.','custom-admin-interface-pro');

        echo custom_admin_interface_pro_settings_page_footer(false,$section);


    }


?>