<?php
    /**
    * 
    *
    *
    * Output content of settings
    */
    function custom_admin_interface_pro_login_page_settings(){
        
        

        //get styles and scripts
        wp_enqueue_script(array('codemirror','css-lint','css','javascript-lint','javascript','closebrackets','matchbrackets','closetag','matchtags','common-script','fontawesome'));
        wp_enqueue_style(array('codemirror','blackboard','common-style'));

        //section name
        $section = 'login_page';

        //do header
        echo custom_admin_interface_pro_settings_page_header(__('Login Page Settings','custom-admin-interface-pro'));

            echo custom_admin_interface_pro_settings_inline_notice('blue',__('Create a custom <a target="_blank" href="'.home_url().'/wp-login.php">login</a> page.','custom-admin-interface-pro')); 
            //do settings fields
            $settings = array(
                array(
                    'name' => 'login_background',
                    'label' => __('Login Screen Background Color','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'color',
                    'default' => '',
                    'options' => '',
                ),   
                array(
                    'name' => 'login_background_image',
                    'label' => __('Login Screen Background Image','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'image',
                    'default' => '',
                    'options' => '',
                ),
                array(
                    'name' => 'login_background_image_position',
                    'label' => __('Login Screen Background Position','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'select',
                    'default' => '',
                    'options' => array(
                        'center top' => 'Center Top',
                        'center center' => 'Center Center',
                        'center bottom' => 'Center Bottom',
                        'left top' => 'Left Top',
                        'left center' => 'Left Center',
                        'left bottom' => 'Left Bottom',
                        'right top' => 'Right Top',
                        'right center' => 'Right Center',
                        'right bottom' => 'Right Bottom',
                    ),
                ), 
                array(
                    'name' => 'login_background_image_size',
                    'label' => __('Login Screen Background Size','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'select',
                    'default' => '',
                    'options' => array(
                        'auto' => 'Auto',
                        'contain' => 'Contain',
                        'cover' => 'Cover',
                    ),
                ), 
                array(
                    'name' => 'login_background_image_repeat',
                    'label' => __('Login Screen Background Repeat','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'select',
                    'default' => '',
                    'options' => array(
                        'no-repeat' => 'No Repeat',
                        'repeat-x' => 'Repeat X',
                        'repeat-y' => 'Repeat Y',
                        'repeat' => 'Repeat',
                        'space' => 'Space',
                        'round' => 'Round',
                    ),
                ),   
                array(
                    'name' => 'login_text',
                    'label' => __('Login Screen Text Color','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'color',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'login_logo',
                    'label' => __('Custom Login Logo','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'image',
                    'default' => '',
                    'options' => '',
                ),   
                array(
                    'name' => 'login_url',
                    'label' => __('Login Logo URL','custom-admin-interface-pro'),
                    'help' => __('This is the link of the login logo. Please input the full URL including <code>http://</code>. To have no link simply input <code>#</code>.','custom-admin-interface-pro'),
                    'type' => 'text',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'login_css',
                    'label' => __('Add custom CSS to the WordPress login area','custom-admin-interface-pro'),
                    'help' => __(''),
                    'type' => 'code-css',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'login_js',
                    'label' => __('Add custom Javascript/jQuery to the WordPress login area','custom-admin-interface-pro'),
                    'help' => __(''),
                    'type' => 'code-js',
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