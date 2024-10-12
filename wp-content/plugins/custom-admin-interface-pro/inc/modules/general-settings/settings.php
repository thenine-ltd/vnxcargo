<?php
    /**
    * 
    *
    *
    * Output content of settings
    */
    function custom_admin_interface_pro_general_settings(){
        
        

        //get styles and scripts
        wp_enqueue_script(array('common-script','fontawesome'));
        wp_enqueue_style(array('common-style'));

        //section name
        $section = 'general_settings';

        //do header
        echo custom_admin_interface_pro_settings_page_header(__('General Settings','custom-admin-interface-pro'));

            //show message for people to enter in their purchase email
            $show_registration = true;

            //get existing option
            $option_name = 'custom_admin_interface_pro_settings';

            if(get_option($option_name)){
                $option = get_option($option_name);
                //check if array key exists
                if(array_key_exists($section,$option)){
                    if(strlen($option[$section]['purchase_email'])>0 && strlen($option[$section]['order_id'])>0){
                        $show_registration = false;
                    }
                }
            } 

            if($show_registration){
                echo custom_admin_interface_pro_settings_inline_notice('red',__('Please enter your purchase email and order ID for updates and support!','custom-admin-interface-pro'));
            }
            


            echo custom_admin_interface_pro_settings_inline_notice('blue',__('Welcome to Custom Admin Interface Pro! On this page you will find general admin settings. Please explore the other sub menus to do a range of other edits.','custom-admin-interface-pro'));

            //do settings fields
            $settings = array(
                array(
                    'name' => 'purchase_email',
                    'label' => __('Purchase Email','custom-admin-interface-pro'),
                    'help' => __('This is the email you entered in the billing details section when purchasing the product.','custom-admin-interface-pro'),
                    'type' => 'text',
                    'default' => '',
                    'options' => '',
                ),   
                array(
                    'name' => 'order_id',
                    'label' => __('Order ID','custom-admin-interface-pro'),
                    'help' => __('This should be a number like 12345. Don\'t enter #12345.','custom-admin-interface-pro'),
                    'type' => 'text',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'custom_favicon',
                    'label' => __('Custom Favicon','custom-admin-interface-pro'),
                    'help' => __('For best results please upload an image with a square aspect ratio','custom-admin-interface-pro'),
                    'type' => 'image',
                    'default' => '',
                    'options' => '',
                ),   
                array(
                    'name' => 'favicon_frontend',
                    'label' => __('Also add the above favicon to the frontend','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'checkbox',
                    'default' => '',
                    'options' => '',
                ),   
                array(
                    'name' => 'custom_footer_text',
                    'label' => __('Custom Footer Text','custom-admin-interface-pro'),
                    'help' => __('Please feel free to use shortcodes to add dynamic content.','custom-admin-interface-pro'),
                    'type' => 'tinymce',
                    'default' => '',
                    'options' => '',
                ), 
                array(
                    'name' => 'custom_header_text',
                    'label' => __('Custom Header Text','custom-admin-interface-pro'),
                    'help' => __('Please feel free to use shortcodes to add dynamic content.','custom-admin-interface-pro'),
                    'type' => 'tinymce',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'remove_version_number',
                    'label' => __('Remove WordPress Version Number from Footer','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'checkbox',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'remove_frontend_toolbar',
                    'label' => __('Remove admin toolbar from the front end for all users','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'checkbox',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'disable_automatic_wordpress_updates',
                    'label' => __('Disable Automatic WordPress Updates','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'checkbox',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'disable_plugin_updates',
                    'label' => __('Disable Plugin Updates','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'checkbox',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'disable_gutenberg_editor',
                    'label' => __('Disable Gutenberg Editor','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'checkbox',
                    'default' => '',
                    'options' => '',
                ), 
                array(
                    'name' => 'enable_duplicate_post',
                    'label' => __('Enable Duplicate Post','custom-admin-interface-pro'),
                    'help' => __('When enabled a duplicate link will be added under posts, pages and custom posts to easily duplicate the item as a draft. This also means you can duplicate items in Custom Admin Interface Pro, e.g. duplicate menus and toolbars etc.','custom-admin-interface-pro'),
                    'type' => 'checkbox',
                    'default' => '',
                    'options' => '',
                ),    
            );

            //loop through each setting
            foreach($settings as $setting){
                echo custom_admin_interface_pro_settings_field(
                    $setting['name'],
                    $setting['label'],
                    $setting['help'],
                    $setting['type'],
                    $setting['default'],
                    $setting['options'],
                    $section
                );    
            }

        echo custom_admin_interface_pro_settings_page_footer(true,$section);

        //force check for updates
        custom_admin_interface_pro_force_check_for_updates();


    }


?>