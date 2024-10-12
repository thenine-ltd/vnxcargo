<?php

    /**
    * 
    *
    *
    * Turns a regular name into a slug
    */
    function custom_admin_interface_pro_slugify_name($name){

        $name = strtolower($name);
        $name = str_replace(' ', '-', $name);
        return $name;

    }

    /**
    * 
    *
    *
    * Turns a regular name into a function-like name
    */
    function custom_admin_interface_pro_functionify_name($name){

        $name = strtolower($name);
        $name = str_replace(' ', '_', $name);
        return $name;

    }

    /**
    * 
    *
    *
    * Checks whether exception criteria is met
    */
    function custom_admin_interface_pro_exception_check($post_id){

        if(!function_exists('wp_get_current_user')) {
            include(ABSPATH . "wp-includes/pluggable.php"); 
        }

        $exception_type = get_post_meta($post_id,'exception_type',true);
        $exceptions = get_post_meta($post_id,'exceptions',true);

        //get key variables
        $current_user = wp_get_current_user();
        $current_user_id = $current_user->ID;   
        $current_user_roles = $current_user->roles; 
        $current_user_roles = array_values($current_user_roles);
        
        //create an array from exception cases
        $exceptions_array = explode(',',$exceptions);
        
        //start running through the exception rules    
        if($exception_type == "everyone"){
            //here we are taking them away
            $score = 1; 

            foreach($exceptions_array as $value){

                //now we need to check whether the value is an integer
                if(is_numeric($value)) {
                    //so the value is an integer which means we need to check against the user id    
                    if($value == $current_user_id){
                        $score--;      
                    }
                } else {
                    //so the value isn't an integer which means we need to check against the user role
                    if(in_array($value,$current_user_roles)){
                        $score--;      
                    } 
                }
            }  
        } else {
            //here we are adding values
            $score = 0;
            foreach($exceptions_array as $value){

                //now we need to check whether the value is an integer
                if(is_numeric($value)) {
                    //so the value is an integer which means we need to check against the user id    
                    if($value == $current_user_id){
                        $score++;      
                    }
                } else {
                    //so the value isn't an integer which means we need to check against the user role
                    if(in_array($value,$current_user_roles)){
                        $score++;      
                    } 
                }
            }

        } 

        if($score < 1){
            return false;    
        } else {
            return true;     
        }   
            
    }

    /**
    * 
    *
    *
    * Function to output shortcodes
    */
    function custom_admin_interface_pro_shortcode_output() {

        //create array with shortcodes
        $shortcodes = array(
            '[CURRENT_YEAR]',
            '[WEBSITE_TITLE]',
            '[WEBSITE_TAGLINE]',
            '[WEBSITE_URL]',
            '[ADMIN_EMAIL_ADDRESS]',
            '[USER_FIRST_NAME]',
            '[USER_LAST_NAME]',
            '[USER_NICKNAME]',
            '[USER_EMAIL]',
        );

        //start output
        $html = '';

        $html .= '<div class="shortcode-container">';

        //loop through shortcodes
        foreach($shortcodes as $shortcode){
            $html .= '<a class="shortcode-button button-secondary">'.$shortcode.'</a>';
        }

        $html .= '</div>';

        return $html;
        
    }

    /**
    * 
    *
    *
    * Function to replace string with shortcodes to variables
    */
    function custom_admin_interface_pro_shortcode_replacer($original_text) {
        
        $current_user = wp_get_current_user();
        
        $html = $original_text;
        
        //create an associative array to be used for shortcode replacement    
        $variables = array(
                        "website_title"=>get_bloginfo('name'),
                        "website_tagline"=>get_bloginfo('description'),
                        "website_url"=>get_bloginfo('url'),
                        "admin_email_address"=>get_bloginfo('admin_email'),
                        "user_first_name"=>$current_user->user_firstname,
                        "user_last_name"=>$current_user->user_lastname,
                        "user_nickname"=>$current_user->nickname,
                        "user_email"=>$current_user->user_email,
                        "current_year"=>date("Y"),
                    ); 
        

        foreach($variables as $key => $value) { 
            $html = str_replace('['.strtoupper($key).']', $value, $html);    
        }
        
        return $html;
    }

    /**
    * 
    *
    *
    * Function which returns select all/deselect all line
    */
    function custom_admin_interface_pro_select_all() {
        
        $html = '';

        $html .= '<div class="custom-admin-interface-pro-select-all">';

            $html .= '<a href="#" class="select-all-link button-secondary"><i class="far fa-check-square"></i> '.__('Select all','custom-admin-interface-pro').'</a>';

            // $html .= ' / ';

            $html .= '<a href="#" class="deselect-all-link button-secondary"><i class="far fa-square"></i> '.__('Deselect all','custom-admin-interface-pro').'</a>';

        $html .= '</div>';

        return $html;
    }

    /**
    * 
    *
    *
    * Function which returns the list item
    */
    function custom_admin_interface_pro_hide_list($module,$selected_items) {

        //turn selected items into a usable array
        $selected_items_array = explode(',',$selected_items);

        $html = '';

        $html .= '<ul class="custom-admin-interface-pro-hide-list">';

            //get the items here depending on the module
            switch ($module) {
                case 'sidebar':
                    //do something
                    global $custom_admin_interface_pro_original_sidebar_listing; 
        
                    foreach($custom_admin_interface_pro_original_sidebar_listing as $sidebar){

                        $id = $sidebar['id']; 
                        $name = $sidebar['name'];
                        $italic_text = $sidebar['description'];
                        $information_text = '';

                        if(in_array($id,$selected_items_array)){
                            $selected = true;
                        } else {
                            $selected = false;   
                        }
        
                        //call another function which does the list item
                        $html .= custom_admin_interface_pro_hide_list_item($id,$name,$italic_text,$information_text,$selected);
   
                    }

                    break;

                case 'user':


                    global $wpdb;

                    $sql = "SELECT ID 
                    FROM $wpdb->users WHERE 1=1 AND {$wpdb->users}.ID IN (
                        SELECT {$wpdb->usermeta}.user_id FROM $wpdb->usermeta 
                        WHERE {$wpdb->usermeta}.meta_key = '{$wpdb->prefix}capabilities'
                        AND {$wpdb->usermeta}.meta_value NOT LIKE '%".apply_filters("custom_admin_interface_pro_role_to_exclude","ZZZZZZZZZ")."%') ORDER BY display_name ASC";

        
                    $all_users = $wpdb->get_results($sql);

        
                    foreach($all_users as $user){

                        $id = $user->ID; 
                        $user = get_userdata($id);
                        $name = $user->first_name.' '.$user->last_name;
                        $information_text = '';

                        global $wp_roles;

                        $all_roles = $wp_roles->roles;

                        $all_user_roles = $user->roles; 
                
                        $all_user_roles_name = array();

                        foreach($all_user_roles as $user_role){
                            array_push($all_user_roles_name, $all_roles[$user_role]['name']);
                        }

                        if(count($all_user_roles_name)){
                            $italic_text = implode(', ',$all_user_roles_name);    
                        } else {
                            $italic_text = '';     
                        } 

                        if(in_array($id,$selected_items_array)){
                            $selected = true;
                        } else {
                            $selected = false;   
                        }
        
                        //call another function which does the list item
                        $html .= custom_admin_interface_pro_hide_list_item($id,$name,$italic_text,$information_text,$selected);

 
                    }

                    break;

                case 'plugin':
                    //do something
                    $all_plugins = get_plugins();

                    foreach($all_plugins as $plugin_key => $plugin_data){

                        $id = $plugin_key; 
                        $name = $plugin_data['Name'];
                        $italic_text = '';
                        $information_text = $plugin_data['Description'];

                        if(in_array($id,$selected_items_array)){
                            $selected = true;
                        } else {
                            $selected = false;   
                        }
        
                        //call another function which does the list item
                        $html .= custom_admin_interface_pro_hide_list_item($id,$name,$italic_text,$information_text,$selected);


                    }
                    
                    break;

                case 'metabox':

                    //do something
                    global $wpdb;

                    $get_post_types = get_post_types(array(), 'names');  

                    $post_type_data = array();

                    foreach ($get_post_types as $post_type) {
                
                        $ignore = array('admin_menu','admin_notice','admin_toolbar','dashboard_widget','custom_admin_code','custom_frontend_code','hide_sidebar','hide_user','hide_plugin','hide_metabox','attachment','nav_menu_item','revision','custom_css','customize_changeset','shop_order_refund','shop_webhook','product_variation');
                        
                        if(!in_array($post_type,$ignore)){

                            $results = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 1", $post_type ), ARRAY_A );
        
                            foreach($results as $index => $post) {
                                $recent_post_id = $post['ID'];
                            }
                            
                            //only continue if there's a post
                            if(isset($recent_post_id)){

                                $recent_post_id = get_edit_post_link( $recent_post_id );

                                if($recent_post_id != null){
                                    array_push($post_type_data,$post_type.'|'.$recent_post_id);
                                }
                                

                            }
                        }
                    }

                    $html .= '<div id="post-type-data" data="'.implode(',',$post_type_data).'"></div>';


                    break;


                    

            }


        $html .= '</ul>';

        return $html;
    }

    /**
    * 
    *
    *
    * Function which returns the individual list item
    */
    function custom_admin_interface_pro_hide_list_item($id,$name,$italic_text,$information_text,$selected) {

        $html = '';

        //do eye or no eye
        if($selected == true){
            $class = 'selected';
        } else {
            $class = '';   
        }

        $html .= '<li data="'.$id.'" class="custom-admin-interface-pro-hide-list-item '.$class.'">';

            //do left side
            $html .= '<div class="custom-admin-interface-pro-hide-list-item-inner-left">';

                //do name
                $html .= $name;

                //do italic text if exists
                if(strlen($italic_text)>0){
                    $html .= ' <em>('.$italic_text.')</em>';
                }

                //do information text if exists
                if(strlen($information_text)>0){
                    $html .= ' <i class="fas fa-info-circle information-icon"></i>';
                    $html .= '<span>'.$information_text.'</span>';
                }
            
            $html .= '</div>';

            $html .= '<div class="custom-admin-interface-pro-hide-list-item-inner-right">';

                //do eye or no eye
                if($selected == true){
                    $html .= '<i class="fas fa-eye"></i>';
                } else {
                    $html .= '<i class="fas fa-eye-slash"></i>';   
                }

            $html .= '</div>';

        $html .= '</li>';

        return $html;
    }
    /**
    * 
    *
    *
    * Function to display header on settings pages
    */
    function custom_admin_interface_pro_settings_page_header($heading) {

        //we are going to enqueue the media here because we are going to use it across a few files
        wp_enqueue_media();  
        wp_enqueue_style( 'wp-color-picker' );

        $html = '';

        $html .= '<div class="wrap custom-admin-interface-pro-settings">';
            $html .= '<div class="postbox">';
                $html .= '<div class="inside">';

                    //do heading
                    $html .= '<div class="settings-header">';
                        $html .= '<i class="custom-admin-interface-pro-logo fas fa-sliders-h"></i> <h1>Custom Admin Interface Pro <span style="font-weight: 300;">'.$heading.'</span></h1>';
                        
                        // if($save_button){
                        //     $html .= '<button class="button-primary custom-admin-interface-pro-save-settings"><i class="fas fa-save"></i> '.__('Save settings','custom-admin-interface-pro').'</button>';    
                        // }

                    $html .= '</div>';

        

        return $html;
    }
    /**
    * 
    *
    *
    * Function to display footer on settings pages
    */
    function custom_admin_interface_pro_settings_page_footer($save_button,$section) {

        $html = '';

        if($save_button){

            $html .= '<div class="settings-footer">';
                $html .= '<button data="'.$section.'" class="button-primary custom-admin-interface-pro-save-settings"><i class="fas fa-save"></i> '.__('Save settings','custom-admin-interface-pro').'</button>';   
            $html .= '</div>';
        }

        $html .= '</div>'; //end inside
        $html .= '</div>'; //end postbox
        $html .= '</div>'; //end wrap

        return $html;
    }
    /**
    * 
    *
    *
    * Function to display inline notices
    */
    function custom_admin_interface_pro_settings_inline_notice($color,$content) {

        if($color == 'blue'){
            $class = 'notice-info';
            $icon = 'fa-info-circle';
        }

        if($color == 'yellow'){
            $class = 'notice-warning';
            $icon = 'fa-exclamation-triangle';
        }

        if($color == 'red'){
            $class = 'notice-error';
            $icon = 'fa-bomb';
        }

        if($color == 'green'){
            $class = 'notice-success';
            $icon = 'fa-check-circle';
        }

        $html = '';

        $html .= '<div class="custom-admin-interface-pro-notice notice '.$class.' inline">';
            $html .= '<p>';
                $html .= '<i class="fas '.$icon.'"></i> '.__($content,'custom-admin-interface-pro');
            $html .= '</p>';

        $html .= '</div>';

        return $html;
    }
    /**
    * 
    *
    *
    * Function to display inline notices
    */
    function custom_admin_interface_pro_settings_field($name,$label,$help,$type,$default,$options,$section) {


        //get existing option
        $option_name = 'custom_admin_interface_pro_settings';

        if(!get_option($option_name)){
            update_option($option_name, array() );
        }

        $option = get_option($option_name);

        //only get array key if it exists
        if(array_key_exists($section,$option)){
            $option_section = $option[$section];

            if(isset($option_section) && array_key_exists($name,$option_section)){
                $value = $option[$section][$name];
            } else {
                $value = '';
            }
        } else {
            $value = '';    
        }

        
        

    

        //start output
        // $html = '';

        echo '<div class="custom-admin-interface-pro-settings-field">';


            //do container div for label
            echo '<div class="custom-admin-interface-pro-settings-label-container">';
                echo '<label>'.$label.'</label>';
                if(strlen($help) > 0){
                    echo '<i class="help-icon fas fa-info-circle"></i>';
                    echo '<span class="field-help">'.$help.'</span>';
                }
            echo '</div>';
            
            //do text field
            if($type == 'text'){
                echo '<input class="custom-admin-interface-pro-settings-field-input" data="'.$type.'" type="text" name="'.$name.'" id="'.$name.'" value="'.stripslashes($value).'" />';
            }

            //do image field
            if($type == 'image'){
                echo '<input class="custom-admin-interface-pro-settings-field-input" data="'.$type.'" type="text" name="'.$name.'" id="'.$name.'" value="'.stripslashes($value).'" />';
                echo '<button class="button-secondary custom-admin-interface-pro-image-upload"><i class="far fa-image"></i> '.__('Upload Image','custom-admin-interface-pro').'</button>';
            }

            //do checkbox field
            if($type == 'checkbox'){

                if($value == 'checked'){
                    $checked = 'checked';
                } else {
                    $checked = '';    
                }
                echo '<label class="switch">';
                    echo '<input class="custom-admin-interface-pro-settings-field-input" data="'.$type.'" type="checkbox" name="'.$name.'" id="'.$name.'" '.$checked.'>';
                    echo '<span class="slider"></span>';
                echo '</label>';
            }

            //do tiny mce
            if($type == 'tinymce'){

                echo custom_admin_interface_pro_shortcode_output();
  
                wp_editor( stripslashes($value), $name, $settings = array(
                    'wpautop' => false,
                    'textarea_name' => $name,
                    'drag_drop_upload' => true,
                    'textarea_rows' => 30,
                    'editor_class' => 'custom-admin-interface-pro-settings-field-input',
                    'drag_drop_upload' => true,
                ));  

            }

            //do tiny mce
            if($type == 'tinymce-no-shortcode'){
  
                wp_editor( stripslashes($value), $name, $settings = array(
                    'wpautop' => false,
                    'textarea_name' => $name,
                    'drag_drop_upload' => true,
                    'textarea_rows' => 30,
                    'editor_class' => 'custom-admin-interface-pro-settings-field-input',
                    'drag_drop_upload' => true,
                ));  

            }

            //do color
            if($type == 'color'){
                echo '<input class="custom-admin-interface-pro-settings-field-input custom-admin-interface-pro-settings-field-color" data="'.$type.'" type="text" name="'.$name.'" id="'.$name.'" value="'.stripslashes($value).'" />';
            }

            //do css
            if($type == 'code-css'){
                echo '<textarea class="custom-admin-interface-pro-settings-field-input custom-admin-interface-pro-settings-field-css" data="'.$type.'" type="text" name="'.$name.'" id="'.$name.'">'.stripslashes($value).'</textarea>';
            }

            //do js
            if($type == 'code-js'){
                echo '<textarea class="custom-admin-interface-pro-settings-field-input custom-admin-interface-pro-settings-field-js" data="'.$type.'" type="text" name="'.$name.'" id="'.$name.'">'.stripslashes($value).'</textarea>';
            }

            //do date field
            if($type == 'date'){
                echo '<input class="custom-admin-interface-pro-settings-field-input" data="'.$type.'" type="date" name="'.$name.'" id="'.$name.'" value="'.stripslashes($value).'" />';
            }

            //do select field
            if($type == 'select'){
                echo '<select class="custom-admin-interface-pro-settings-field-input" data="'.$type.'" type="date" name="'.$name.'" id="'.$name.'">';
                    foreach($options as $select_value => $select_label){
                        if($select_value == $value){
                            echo '<option selected="selected" value="'.$select_value.'">'.$select_label.'</option>';
                        } elseif($value == '' && $select_value == $default){
                            echo '<option selected="selected" value="'.$select_value.'">'.$select_label.'</option>';
                        } else {
                            echo '<option value="'.$select_value.'">'.$select_label.'</option>';
                        }
                    }
                echo '</select>';
            }
            


        echo '</div>'; 

        // return $html;
    }
    /**
    * 
    *
    *
    * Function to save settings
    */
    add_action( 'wp_ajax_custom_admin_interface_pro_save_settings', 'custom_admin_interface_pro_save_settings' );
    function custom_admin_interface_pro_save_settings() {


        // check if user can manage options    
        if ( ! current_user_can( custom_admin_interface_pro_permission() ) ){
            echo "You don't have permission to perform this action.";
            wp_die(); 
        }
        
        //get user input
        $section = $_POST['section'];
        $data = $_POST['data']; 

        //get option, if no option exists create it
        $option_name = 'custom_admin_interface_pro_settings';

        if(!get_option($option_name)){
            update_option($option_name, array() );
        }

        $option = get_option($option_name);

        $array_to_add = array();

        foreach($data as $settings_field){
            $array_to_add[$settings_field['name']] = $settings_field['value']; 
        }

        //add array to add to main option
        $option[$section] = $array_to_add;

        //update the option
        update_option($option_name, $option);

        //we are also going to delete the update transient to update if they are updating the general settings as this is where our order id and password is
        if($section == 'general_settings'){
            delete_option('_site_transient_update_plugins');
        }
        
        // echo json_encode($data);

        
        wp_die();    
    }
    /**
    * 
    *
    *
    * Function to get minimum capability
    */
    function custom_admin_interface_pro_permission() {

        if(get_option( 'custom_admin_interface_pro_settings' )){

            $options = get_option( 'custom_admin_interface_pro_settings' );

            if(isset($options['manage_settings']['minimum_permissions'])){
                return $options['manage_settings']['minimum_permissions'];   
            } else {
                return 'manage_options';    
            }
        } else {
            return 'manage_options';
        }

     
    }
    /**
    * 
    *
    *
    * Add role and user id to body class to provide alternate css and javascript options
    */
    add_filter( 'admin_body_class','custom_admin_interface_pro_additional_body_classes' );
    function custom_admin_interface_pro_additional_body_classes( $classes ) {

        $current_user = wp_get_current_user();

        $current_user_id = $current_user->ID;

        $classes .= 'user-id-'.$current_user_id;

        $user_roles = $current_user->roles;
        
        if($user_roles){
            foreach($user_roles as $role){
                $classes .= ' user-role-'.$role;   
            }
        }

        return $classes;
        
    }




?>