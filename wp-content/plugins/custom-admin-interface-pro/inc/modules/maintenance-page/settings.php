<?php
    /**
    * 
    *
    *
    * Output content of settings
    */
    function custom_admin_interface_pro_maintenance_page_settings(){
        
        

        //get styles and scripts
        wp_enqueue_script(array('common-script','fontawesome'));
        wp_enqueue_style(array('common-style'));

        //section name
        $section = 'maintenance_page';

        //do header
        echo custom_admin_interface_pro_settings_page_header(__('Maintenance Page Settings','custom-admin-interface-pro'));

            echo custom_admin_interface_pro_settings_inline_notice('blue',__('This section enables you to create a custom under construction/maintenance page which is shown to all non-logged in users.','custom-admin-interface-pro')); 

            //do settings fields
            $settings = array(
                array(
                    'name' => 'enable_maintenance_mode',
                    'label' => __('Enable Maintenance Mode','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'checkbox',
                    'default' => '',
                    'options' => '',
                ),   
                array(
                    'name' => 'custom_maintenance_logo',
                    'label' => __('Custom Logo','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'image',
                    'default' => '',
                    'options' => '',
                ),   
                array(
                    'name' => 'custom_background_color',
                    'label' => __('Custom Background Color','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'color',
                    'default' => '',
                    'options' => '',
                ),   
                array(
                    'name' => 'custom_background_image',
                    'label' => __('Custom Screen Background Image','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'image',
                    'default' => '',
                    'options' => '',
                ),
                array(
                    'name' => 'custom_background_image_position',
                    'label' => __('Custom Screen Background Position','custom-admin-interface-pro'),
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
                    'name' => 'custom_background_image_size',
                    'label' => __('Custom Screen Background Size','custom-admin-interface-pro'),
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
                    'name' => 'custom_background_image_repeat',
                    'label' => __('Custom Screen Background Repeat','custom-admin-interface-pro'),
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
                    'name' => 'custom_maintenance_text',
                    'label' => __('Custom Maintenance Page/Coming Soon Text','custom-admin-interface-pro'),
                    'help' => __('','custom-admin-interface-pro'),
                    'type' => 'tinymce-no-shortcode',
                    'default' => '',
                    'options' => '',
                ),  
                array(
                    'name' => 'maintenance_mode_end_date',
                    'label' => __('Maintenance Mode End Date','custom-admin-interface-pro'),
                    'help' => __('Please leave blank to have no expiry.','custom-admin-interface-pro'),
                    'type' => 'date',
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