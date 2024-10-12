<?php
    /**
    * 
    *
    *
    * Output content of settings
    */
    function custom_admin_interface_pro_manage_settings(){
        
        //get styles and scripts
        wp_enqueue_script(array('sweetalert','manage-settings-script','common-script','fontawesome'));
        wp_enqueue_style(array('common-style','manage-settings-style'));

        //section name
        $section = 'manage_settings';

        //do header
        echo custom_admin_interface_pro_settings_page_header(__('Manage Settings','custom-admin-interface-pro'));

        //get site name
        $site_name = get_bloginfo( 'name' );
        $site_name = str_replace(' ','-',$site_name);
        $site_name = strtolower($site_name);


        //lets do our export/import/delete section
        
        //wrapper
        echo '<div class="export-import-delete-wrapper"
        
        data-no-module="'.__('Please select at least 1 module','custom-admin-interface-pro').'" 
        data-no-data="'.__('There was no data to import','custom-admin-interface-pro').'" 
        data-success="'.__('Action Completed','custom-admin-interface-pro').'" 
        data-error="'.__('An Error Occured','custom-admin-interface-pro').'" 
        data-noonce-delete-settings="'.wp_create_nonce( 'custom_admin_interface_pro_delete_settings' ).'" 
        data-noonce-export-settings="'.wp_create_nonce( 'custom_admin_interface_pro_export_settings' ).'" 
        data-noonce-import-settings="'.wp_create_nonce( 'custom_admin_interface_pro_import_settings' ).'" 
        data-copied="'.__('Settings Copied to Clipboard','custom-admin-interface-pro').'" 
        data-filename="'.__('custom-admin-interface-pro-settings-'.$site_name.'-'.date('Y-m-d',current_time( 'timestamp' )),'custom-admin-interface-pro').'" 
        data-confirm="'.__('Are you sure?','custom-admin-interface-pro').'" 
        data-confirm-button="'.__('Yes, proceed!','custom-admin-interface-pro').'" 
        
        >';

            //export
            echo '<div class="export-wrapper">';

                echo '<div class="export-import-delete-inner">';

                    echo '<h3>'.__('Export Settings','custom-admin-interface-pro').'</h3>';
                    //do select all
                    echo custom_admin_interface_pro_manage_settings_list();
                    //copy settings
                    echo '<button style="margin-right: 8px;" id="copy-settings" class="button-primary"><i class="fas fa-copy"></i> '.__('Copy Settings','custom-admin-interface-pro').'</button>'; 
                    //export settings
                    echo '<button id="export-settings" class="button-primary"><i class="fas fa-file-download"></i> '.__('Export Settings','custom-admin-interface-pro').'</button>';   
                echo '</div>';
            echo '</div>';

            //import
            echo '<div class="import-wrapper">';
                echo '<div class="export-import-delete-inner">';    
                    echo '<h3>'.__('Import Settings','custom-admin-interface-pro').'</h3>';

                    //text area
                    echo '<label style="font-size: inherit;font-weight: inherit; margin-bottom: 10px; display: block;">'.__('Please paste your copied settings here or upload a file below.','custom-admin-interface-pro').'</label>';
                    echo '<textarea rows="10" id="import-settings-input"></textarea>';

                    //import
                    echo '<input type="file" id="import-settings-file-upload" accept="text/plain">';

                    //import settings
                    echo '<button id="import-settings" class="button-primary"><i class="fas fa-file-upload"></i> '.__('Import Settings','custom-admin-interface-pro').'</button>'; 
                echo '</div>';
            echo '</div>';


            //delete
            echo '<div class="delete-wrapper">';
                echo '<div class="export-import-delete-inner">';
                    echo '<h3>'.__('Delete Settings','custom-admin-interface-pro').'</h3>';
                    //do select all
                    echo custom_admin_interface_pro_manage_settings_list();
                    //delete settings
                    echo '<button id="delete-settings" class="button-primary"><i class="fas fa-trash-alt"></i> '.__('Delete Settings','custom-admin-interface-pro').'</button>'; 
                echo '</div>';
            echo '</div>';

        echo '</div>';


        //do horizontal rule
        echo '<hr style="margin-top: 45px; margin-bottom: 45px;">';

        //get the roles
        global $wp_roles;
        $roles = $wp_roles->roles;
        $administrator_capabilities = $roles['administrator']['capabilities'];

        //just get the keys
        $administrator_capabilities = array_keys($administrator_capabilities);
        //sort the array
        sort($administrator_capabilities);

        $capability_options = array();

        foreach($administrator_capabilities as $capability){

            $replace_underscores = str_replace('_',' ',$capability);
            $make_title_case = ucwords($replace_underscores);

            $capability_options[$capability] = $make_title_case;

        }


        //do settings fields
        $settings = array(
            array(
                'name' => 'disable_post_revisions',
                'label' => __('Disable Post Revisions','custom-admin-interface-pro'),
                'help' => __('By disabling post revisions you will save space in your database but it does mean you won\'t be able to restore data for the modules that support revisions.','custom-admin-interface-pro'),
                'type' => 'checkbox',
                'default' => '',
                'options' => '',
            ),  
            array(
                'name' => 'minimum_permissions',
                'label' => __('Minimum Permission','custom-admin-interface-pro'),
                'help' => __('Please select the minimum capability for users to update options for Custom Admin Interface Pro - we recommend Manage Options.','custom-admin-interface-pro'),
                'type' => 'select',
                'default' => 'manage_options',
                'options' => $capability_options,
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
    /**
    * 
    *
    *
    * Output content of settings
    */
    function custom_admin_interface_pro_manage_settings_list(){
        
        //start output
        $html = '';

        //start select all/none
        $html .= custom_admin_interface_pro_select_all();

        //do listing
        $modules = array(
            //settings
            'general_settings' => __('General Settings','custom-admin-interface-pro'),
            'admin_color_scheme_page' => __('Admin Color Scheme','custom-admin-interface-pro'),
            'login_page' => __('Login Page','custom-admin-interface-pro'),
            'maintenance_page' => __('Maintenance Page','custom-admin-interface-pro'),
            //post types
            'admin_menu' => __('Admin Menus','custom-admin-interface-pro'),
            'admin_notice' => __('Admin Notices','custom-admin-interface-pro'),
            'admin_toolbar' => __('Admin Toolbars','custom-admin-interface-pro'),
            'custom_admin_code' => __('Custom Admin Codes','custom-admin-interface-pro'),
            'dashboard_widget' => __('Dashboard Widgets','custom-admin-interface-pro'),
            'custom_frontend_code' => __('Custom Frontend Codes','custom-admin-interface-pro'),
            'hide_metabox' => __('Hide Metaboxes','custom-admin-interface-pro'),
            'hide_plugin' => __('Hide Plugins','custom-admin-interface-pro'),
            'hide_sidebar' => __('Hide Sidebars','custom-admin-interface-pro'),
            'hide_user' => __('Hide Users','custom-admin-interface-pro'),
            //settings
            'manage_settings' => __('Manage Settings','custom-admin-interface-pro'),   
        );

        //create listing
        $html .= '<ul class="modules-listing">';

            foreach($modules as $module_key => $module_label){
                $html .= '<li>';
                    $html .= '<label class="switch">';
                        $html .= '<input class="module-item-input" type="checkbox" name="'.$module_key.'" id="'.$module_key.'">';
                        $html .= '<span class="slider"></span>';
                    $html .= '</label>';
                    $html .= '<span class="module-item-label">'.$module_label.'</span>';
                $html .= '</li>';
            }

        $html .= '</ul>';






        return $html;

    }


?>