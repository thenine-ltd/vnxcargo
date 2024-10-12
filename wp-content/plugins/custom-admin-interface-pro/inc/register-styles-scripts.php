<?php

    /**
    * 
    *
    *
    * Register admin styles and scripts
    */
    add_action( 'admin_enqueue_scripts', 'custom_admin_code_pro_register_styles_and_scripts',PHP_INT_MAX );
    function custom_admin_code_pro_register_styles_and_scripts($hook){
        
        //do font awesome
        wp_register_script('fontawesome', plugins_url('/library/fontawesome/all.min.js', __FILE__ ), array(),custom_admin_code_pro_version());

        //do codemirror
        wp_register_script('codemirror', plugins_url('/library/codemirror/codemirror.js', __FILE__ ), array(),custom_admin_code_pro_version());
        wp_register_script('css-lint', plugins_url('/library/codemirror/css-lint.js', __FILE__ ), array(),custom_admin_code_pro_version());
        wp_register_script('css', plugins_url('/library/codemirror/css.js', __FILE__ ), array(),custom_admin_code_pro_version());
        wp_register_script('javascript-lint', plugins_url('/library/codemirror/javascript-lint.js', __FILE__ ), array(),custom_admin_code_pro_version());
        wp_register_script('javascript', plugins_url('/library/codemirror/javascript.js', __FILE__ ), array(),custom_admin_code_pro_version());
        wp_register_script('closebrackets', plugins_url('/library/codemirror/closebrackets.js', __FILE__ ), array(),custom_admin_code_pro_version());
        wp_register_script('matchbrackets', plugins_url('/library/codemirror/matchbrackets.js', __FILE__ ), array(),custom_admin_code_pro_version());
        wp_register_script('closetag', plugins_url('/library/codemirror/closetag.js', __FILE__ ), array(),custom_admin_code_pro_version());
        wp_register_style('codemirror', plugins_url('/library/codemirror/codemirror.css', __FILE__ ), array(),custom_admin_code_pro_version());
        wp_register_style('blackboard', plugins_url('/library/codemirror/blackboard.css', __FILE__ ), array(),custom_admin_code_pro_version());

        //do select2
        wp_register_script('select2', plugins_url('/library/select2/select2.min.js', __FILE__ ), array(),custom_admin_code_pro_version());
        wp_register_style('select2', plugins_url('/library/select2/select2.min.css', __FILE__ ), array(),custom_admin_code_pro_version());

        //do nested sortable
        wp_register_script('nestedsortable', plugins_url('/library/nestedsortable/jquery.mjs.nestedSortable.js', __FILE__ ), array('jquery-ui-sortable'),custom_admin_code_pro_version());

        //do sweet alert
        wp_register_script('sweetalert', plugins_url('/library/sweetalert/sweetalert2.all.min.js', __FILE__ ), array(),custom_admin_code_pro_version());

        //do woocommerce
        //this is done to prevent woocommerce forcing the icon
        wp_dequeue_style('woocommerce_admin_menu_styles');
        wp_register_style('woocommerce_admin_menu_styles_new', plugins_url('/library/woocommerce/menu.css', __FILE__ ), array(),custom_admin_code_pro_version());

        //do clipboard
        // wp_register_script('clipboard', plugins_url('/library/clipboard/clipboard.min.js', __FILE__ ), array(),custom_admin_code_pro_version());

        //do admin script
        wp_register_script('custom-admin-code-js', plugins_url( '/custom-admin-script.js', __FILE__ ), array('jquery'),custom_admin_code_pro_version() );

        //loop through custom post types
        global $custom_admin_interface_pro_post_types;

        foreach($custom_admin_interface_pro_post_types as $post_type){

            //get key variables
            $post_type_single_name = $post_type['single'];
            $post_type_name_slug = custom_admin_interface_pro_slugify_name($post_type_single_name);
            //register styles and scripts
            wp_register_script($post_type_name_slug.'-script', plugins_url('/modules/'.$post_type_name_slug.'/script.js', __FILE__ ), array(),custom_admin_code_pro_version());
            wp_register_style($post_type_name_slug.'-style', plugins_url('/modules/'.$post_type_name_slug.'/style.css', __FILE__ ), array(),custom_admin_code_pro_version());


        }

        //register styles and scripts for other modules
        wp_register_style('support-style', plugins_url('/modules/support/style.css', __FILE__ ), array(),custom_admin_code_pro_version());
        wp_register_style('manage-settings-style', plugins_url('/modules/manage-settings/style.css', __FILE__ ), array(),custom_admin_code_pro_version());

        //register common styles and scripts for modules
        wp_register_script('common-script', plugins_url('/modules/common/script.js', __FILE__ ), array('wp-color-picker'),custom_admin_code_pro_version());
        wp_register_style('common-style', plugins_url('/modules/common/style.css', __FILE__ ), array(),custom_admin_code_pro_version());

        //do notice dismiss script
        wp_register_script('admin-notice-dismiss-script', plugins_url( '/modules/admin-notice/admin-notice-dismiss.js', __FILE__ ), array('jquery'),custom_admin_code_pro_version() );
        wp_register_script('manage-settings-script', plugins_url( '/modules/manage-settings/script.js', __FILE__ ), array('jquery'),custom_admin_code_pro_version() );
        wp_register_script('support-script', plugins_url( '/modules/support/script.js', __FILE__ ), array('jquery'),custom_admin_code_pro_version() );

        //we are going to enqueue the duplicate functionality on every page if enabled
        global $post;

        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );
            if(isset($options['general_settings']['enable_duplicate_post']) && $options['general_settings']['enable_duplicate_post'] == 'checked' ){
                if ( $hook == 'edit.php') {
                    wp_enqueue_script(  'duplicate-script', plugins_url( '/duplicate.js', __FILE__ ),array('jquery'),custom_admin_code_pro_version() );
                }
            }
        }

        


    }
    




?>