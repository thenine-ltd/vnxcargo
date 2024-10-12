<?php

    /**
    * 
    *
    *
    * Function to add favicon to backend
    */
    add_action('login_head', 'custom_admin_interface_pro_custom_favicon_backend');
    add_action('admin_head', 'custom_admin_interface_pro_custom_favicon_backend');
    function custom_admin_interface_pro_custom_favicon_backend() {
        
        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );

            if(isset($options['general_settings']['custom_favicon']) && strlen($options['general_settings']['custom_favicon'])>0){

                if( $options['general_settings']['custom_favicon'] == '[site_icon]' ){
                    $site_icon = get_site_icon_url();
                } else {
                    $site_icon = $options['general_settings']['custom_favicon'];
                }

                echo '<link rel="shortcut icon" href="' . $site_icon . '" />';   
            } else {
                return;
            }

        } 
    }
    /**
    * 
    *
    *
    * Function to add favicon to frontend
    */
    add_action('wp_head','custom_admin_interface_pro_custom_favicon_frontend'); 
    function custom_admin_interface_pro_custom_favicon_frontend() {
        
        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );

            if(isset($options['general_settings']['custom_favicon']) && strlen($options['general_settings']['custom_favicon'])>0 && $options['general_settings']['favicon_frontend'] == 'checked'){

                if( $options['general_settings']['custom_favicon'] == '[site_icon]' ){
                    $site_icon = get_site_icon_url();
                } else {
                    $site_icon = $options['general_settings']['custom_favicon'];
                }

                echo '<link rel="shortcut icon" href="' . $site_icon . '" />';   
            } else {
                return;
            }
        } 
    }
    /**
    * 
    *
    *
    * Function to add custom footer text
    */
    add_filter('admin_footer_text', 'custom_admin_interface_pro_custom_footer_text');
    function custom_admin_interface_pro_custom_footer_text () {

        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );

            if(isset($options['general_settings']['custom_footer_text']) && strlen($options['general_settings']['custom_footer_text'])>0 ){
                echo custom_admin_interface_pro_shortcode_replacer(stripslashes($options['general_settings']['custom_footer_text']));  
            }
        } 
    }
    /**
    * 
    *
    *
    * Function to add custom header text
    */
    add_filter('in_admin_header', 'custom_admin_interface_pro_custom_header_text');
    function custom_admin_interface_pro_custom_header_text () {

        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );

            if(isset($options['general_settings']['custom_header_text']) && strlen($options['general_settings']['custom_header_text'])>0 ){
                echo custom_admin_interface_pro_shortcode_replacer(stripslashes($options['general_settings']['custom_header_text']));  
            }
        } 
    }
    /**
    * 
    *
    *
    * Remove WordPress version number from footer
    */
    add_action( 'admin_menu', 'custom_admin_interface_pro_remove_version_number' );
    function custom_admin_interface_pro_remove_version_number() {

        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );

            if(isset($options['general_settings']['remove_version_number']) && $options['general_settings']['remove_version_number'] == 'checked' ){
                remove_filter( 'update_footer', 'core_update_footer' );    
            }
        } 
             
    }
    /**
    * 
    *
    *
    * Function to remove toolbar from frontend for all users
    */
    add_action('init','custom_admin_interface_pro_remove_admin_bar');
    function custom_admin_interface_pro_remove_admin_bar () {

        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );

            if(isset($options['general_settings']['remove_frontend_toolbar']) && $options['general_settings']['remove_frontend_toolbar'] == 'checked' ){
                add_filter('show_admin_bar', '__return_false');   
            }
        } 
    }
    /**
    * 
    *
    *
    * Function to disable automatic updates
    */
    add_action('admin_init','custom_admin_interface_pro_disable_automatic_updates');
    function custom_admin_interface_pro_disable_automatic_updates(){
        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );

            if(isset($options['general_settings']['disable_automatic_wordpress_updates']) && $options['general_settings']['disable_automatic_wordpress_updates'] == 'checked' ){
                add_filter( 'automatic_updater_disabled', '__return_true' );     
            }
        }   
    }
    /**
    * 
    *
    *
    * Function to disable plugin updates
    */
    add_action('admin_init','custom_admin_interface_pro_disable_plugin_updates');
    function custom_admin_interface_pro_disable_plugin_updates(){

        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );

            if(isset($options['general_settings']['disable_plugin_updates']) && $options['general_settings']['disable_plugin_updates'] == 'checked' ){
                add_filter('site_transient_update_plugins', '__return_false');   
            }
        }  
    }
    /**
    * 
    *
    *
    * Function to remove gutenbery
    */
    add_action( 'admin_init', 'custom_admin_interface_pro_remove_gutenberg' );
    function custom_admin_interface_pro_remove_gutenberg() {
        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );

            if(isset($options['general_settings']['disable_gutenberg_editor']) && $options['general_settings']['disable_gutenberg_editor'] == 'checked' ){
                // disable for posts
                add_filter('use_block_editor_for_post', '__return_false', 10);
                // disable for post types
                add_filter('use_block_editor_for_post_type', '__return_false', 10); 
            }
        }  
    }
    /**
    * 
    *
    *
    * Add duplicate link to posts
    */
    add_filter('post_row_actions', 'custom_admin_interface_pro_enable_duplicate_functionality',10,2);
    add_filter('page_row_actions', 'custom_admin_interface_pro_enable_duplicate_functionality',10,2);
    function custom_admin_interface_pro_enable_duplicate_functionality($actions, $post) {

        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );
            if(isset($options['general_settings']['enable_duplicate_post']) && $options['general_settings']['enable_duplicate_post'] == 'checked' ){
                $actions['clone'] = '<a data="'.$post->ID.'" class="custom-admin-interface-pro-duplicate-item" href="#">' . __('Duplicate', 'custom-admin-interface-pro') . '</a>';
            }
        }

        return $actions;
    }
    /**
    * 
    *
    *
    * Do implementation of duplicate functionality
    */
    add_action( 'wp_ajax_custom_admin_interface_duplicate_post', 'custom_admin_interface_duplicate_post' );
    function custom_admin_interface_duplicate_post() {
        
        //check if permission is granted
        if ( ! current_user_can( custom_admin_interface_pro_permission() ) ){
            echo "You don't have permission to perform this action.";
            wp_die(); 
        }

        //get user input
        $post_id = intval($_POST['postId']); 
        
        //lets get the existing data of the post
        $post = get_post( $post_id );

        $post_data = array(
            'menu_order' => $post->menu_order+1,
            'comment_status' => $post->comment_status,
            'ping_status' => $post->ping_status,
            'post_author' => $post->post_author,
            'post_content' => $post->post_content,
            'post_content_filtered' => $post->post_content_filtered,			
            'post_excerpt' => $post->post_excerpt,
            'post_mime_type' => $post->post_mime_type,
            'post_parent' => $post->post_parent,
            'post_password' => $post->post_password,
            'post_status' => 'draft', //for now we are going to force this to draft
            'post_title' => $post->post_title.' '.__('Duplicate','custom-admin-interface-pro'),
            'post_type' => $post->post_type,
            'post_name' => $post->post_name,
            'post_date' => $post->post_date,
        );

        //add our meta
        $meta = array();

        $all_post_meta = get_post_meta($post_id);
        foreach($all_post_meta as $key => $value){
            $meta[$key] = $value[0];
        }

        if(count($meta)){
            $post_data['meta_input'] = $meta;
        }


        //then create our new post
        $new_post_id = wp_insert_post(wp_slash($post_data));

        //then add taxonomies to the new post
        $post_taxonomies = get_object_taxonomies($post->post_type);
        foreach ($post_taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($post_id, $taxonomy, array( 'orderby' => 'term_order' ));
			$terms = array();
			for ($i=0; $i<count($post_terms); $i++) {
				$terms[] = $post_terms[$i]->slug;
			}
			wp_set_object_terms($new_post_id, $terms, $taxonomy);
		}

        echo 'SUCCESS';
            
        wp_die();    
    }
    

?>