<?php

    /**
    * 
    *
    *
    * Function to delete settings
    */
    function custom_admin_interface_pro_delete_settings() {
        
        //check referrer
        check_ajax_referer( 'custom_admin_interface_pro_delete_settings', 'security' );

        //only proceed if we have permission
        if ( ! current_user_can( custom_admin_interface_pro_permission() ) ){
            echo "ERROR";
            wp_die(); 
        } 
        
        // get user input
        $data = $_POST['data']; 

        $modules = array(
            //settings
            'general_settings' => 'setting',   
            'admin_color_scheme_page' => 'setting',   
            'login_page' => 'setting',   
            'maintenance_page' => 'setting',   
            'manage_settings' => 'setting', 
            //post types
            'admin_menu' => 'post',   
            'admin_notice' => 'post',
            'admin_toolbar' => 'post',
            'custom_admin_code' => 'post',
            'dashboard_widget' => 'post',
            'custom_frontend_code' => 'post',
            'hide_metabox' => 'post',
            'hide_plugin' => 'post',
            'hide_sidebar' => 'post',
            'hide_user' => 'post',
        );

        //loop through settings
        foreach($data as $module){

            //check if item is in array
            if(array_key_exists($module,$modules)){

                //get main setting
                $option_name = 'custom_admin_interface_pro_settings';
                $options = get_option( $option_name );

                $module_type = $modules[$module];

                if($module_type == 'post'){
                    
                    //loop through all posts
                    $args = array(
                        'post_type'      => $module,
                        'post_status'    => 'any,trash', //we will delete all post statuses
                        'posts_per_page' => -1
                    );
            
                    $posts = get_posts( $args );
            
                    if($posts){
                        foreach($posts as $post){
                            $post_id = $post->ID;
                            wp_delete_post( $post_id , true );
                        }
                    }

                } else {
                    //its a setting
                    unset($options[$module]);
                }

                //update the option
                update_option($option_name, $options);

            }

        }
        
        echo 'SUCCESS';
        wp_die();    
    }
    add_action( 'wp_ajax_custom_admin_interface_pro_delete_settings', 'custom_admin_interface_pro_delete_settings' );
    /**
    * 
    *
    *
    * Function to import settings
    */
    function custom_admin_interface_pro_import_settings() {
        
        //check referrer
        check_ajax_referer( 'custom_admin_interface_pro_import_settings', 'security' );

        //only proceed if we have permission
        if ( ! current_user_can( custom_admin_interface_pro_permission() ) ){
            echo "ERROR";
            wp_die(); 
        } 
        
        // get user input
        $data = $_POST['data']; 

        //deserialize the data
        $data = unserialize(base64_decode($data));
        // $data = unserialize($data);

        // echo json_encode($data);
        // wp_die();

        $modules = array(
            //settings
            'general_settings' => 'setting',   
            'admin_color_scheme_page' => 'setting',   
            'login_page' => 'setting',   
            'maintenance_page' => 'setting',   
            'manage_settings' => 'setting', 
            //post types
            'admin_menu' => 'post',   
            'admin_notice' => 'post',
            'admin_toolbar' => 'post',
            'custom_admin_code' => 'post',
            'dashboard_widget' => 'post',
            'custom_frontend_code' => 'post',
            'hide_metabox' => 'post',
            'hide_plugin' => 'post',
            'hide_sidebar' => 'post',
            'hide_user' => 'post',
        );

        //loop through the data and take appropriate actions
        foreach($data as $module => $module_data){

            // echo $module;
            // wp_die();

            //check if item is in array
            if(array_key_exists($module,$modules)){

                //get main setting
                $option_name = 'custom_admin_interface_pro_settings';
                $options = get_option( $option_name );

                $module_type = $modules[$module];

                if($module_type == 'post'){
                    
                    //loop through all the posts
                    foreach($module_data as $post){

                        $postarr = array(
                            'post_type' => $module,
                            'post_title' => $post['post_title'],  
                            'post_content' => $post['post_content'],  
                            'post_status' => $post['post_status'], 
                            'meta_input' => $post['meta_input']
                        );

                        // echo json_encode($postarr);
                        // wp_die();

                        wp_insert_post( $postarr, false );

                    }
                    

                } else {
                    $options[$module] = $module_data;   
                }

                //update the option
                update_option($option_name, $options);
            }
        }


        echo 'SUCCESS';
        wp_die();    
    }
    add_action( 'wp_ajax_custom_admin_interface_pro_import_settings', 'custom_admin_interface_pro_import_settings' );
    /**
    * 
    *
    *
    * Function to export settings
    */
    function custom_admin_interface_pro_export_settings() {
        
        //check referrer
        check_ajax_referer( 'custom_admin_interface_pro_export_settings', 'security' );

        //only proceed if we have permission
        if ( ! current_user_can( custom_admin_interface_pro_permission() ) ){
            echo "ERROR";
            wp_die(); 
        } 
        
        // get user input
        $data = $_POST['data']; 

        $modules = array(
            //settings
            'general_settings' => 'setting',   
            'admin_color_scheme_page' => 'setting',   
            'login_page' => 'setting',   
            'maintenance_page' => 'setting',   
            'manage_settings' => 'setting', 
            //post types
            'admin_menu' => 'post',   
            'admin_notice' => 'post',
            'admin_toolbar' => 'post',
            'custom_admin_code' => 'post',
            'dashboard_widget' => 'post',
            'custom_frontend_code' => 'post',
            'hide_metabox' => 'post',
            'hide_plugin' => 'post',
            'hide_sidebar' => 'post',
            'hide_user' => 'post',
        );

        //data to send back
        $return_data = array();

        //loop through settings
        foreach($data as $module){

            //check if item is in array
            if(array_key_exists($module,$modules)){

                //get main setting
                $option_name = 'custom_admin_interface_pro_settings';
                $options = get_option( $option_name );

                $module_type = $modules[$module];

                if($module_type == 'post'){
                    
                    //create a temporary array to store all the post data
                    $temp_data = array();

                    //loop through all posts
                    $args = array(
                        'post_type'      => $module,
                        // 'post_status'    => 'publish', //we will delete all post statuses
                        'posts_per_page' => -1
                    );
            
                    $posts = get_posts( $args );
            
                    if($posts){
                        foreach($posts as $post){
                            $post_id = $post->ID;
                            $post_status = $post->post_status;
                            $post_title = $post->post_title;
                            $post_content = $post->post_content;

                            //will also need to get meta
                            $meta = array();
                            $all_post_meta = get_post_meta($post_id);
                            foreach($all_post_meta as $key => $value){
                                $meta[$key] = $value[0];
                            }

                            array_push($temp_data,array(
                                // 'post_id' => $post_id, 
                                'post_content' => $post_content, 
                                'post_status' => $post_status, 
                                'post_title' => $post_title, 
                                'meta_input' => wp_slash($meta)
                            ));
                        }
                    }

                    //now lets add the temp data to the main array
                    $return_data[$module] = $temp_data;

                } else {
                    //its a setting
                    // unset($options[$module]);
                    $return_data[$module] = $options[$module];
                }
            }
        }

        //for now lets turn the array into json
        echo base64_encode(serialize($return_data));

        // echo 'SUCCESS';
        wp_die();    
    }
    add_action( 'wp_ajax_custom_admin_interface_pro_export_settings', 'custom_admin_interface_pro_export_settings' );
?>