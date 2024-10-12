<?php
    /**
    * 
    *
    *
    * Output content of settings
    */
    function custom_admin_interface_pro_admin_color_scheme_settings(){
        
        

        //get styles and scripts
        wp_enqueue_script(array('common-script','fontawesome'));
        wp_enqueue_style(array('common-style'));

        //section name
        $section = 'admin_color_scheme_page';

        //do header
        echo custom_admin_interface_pro_settings_page_header(__('Admin Color Scheme','custom-admin-interface-pro'));

            echo custom_admin_interface_pro_settings_inline_notice('blue',__('Create a custom color scheme for your admin area. These 5 below colors will make up a new color scheme called "Custom" that will be available to select on your <a target="_blank" href="profile.php">User Profile</a> page.','custom-admin-interface-pro'));

            echo custom_admin_interface_pro_settings_inline_notice('yellow',__('Please ensure you select all 4 colours and an SVG icon color for the color scheme to be created.','custom-admin-interface-pro'));


            //do settings fields
            $settings = array(
                array(
                    'name' => 'color_scheme_color_1',
                    'label' => __('Color Scheme Color 1','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'color',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'color_scheme_color_2',
                    'label' => __('Color Scheme Color 2','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'color',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'color_scheme_color_3',
                    'label' => __('Color Scheme Color 3','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'color',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'color_scheme_color_4',
                    'label' => __('Color Scheme Color 4','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'color',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'svg_icon_color',
                    'label' => __('SVG Icon Color','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'color',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'admin_link_and_button_color',
                    'label' => __('Admin Link and Button Color','custom-admin-interface-pro'),
                    'help' => __('Please click the "Default" button when changing the colour to load the default WordPress styling','custom-admin-interface-pro'),
                    'type' => 'color',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'admin_link_and_button_color_hover',
                    'label' => __('Admin Link and Button Hover Color','custom-admin-interface-pro'),
                    'help' => __('Please click the "Default" button when changing the colour to load the default WordPress styling','custom-admin-interface-pro'),
                    'type' => 'color',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'force_on_all_users',
                    'label' => __('Force the custom color scheme on all users','custom-admin-interface-pro'),
                    'help' => __('This will force the "Custom" color scheme on all users regardless of what color scheme they have chosen in their user profile.','custom-admin-interface-pro'),
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

    }


?>