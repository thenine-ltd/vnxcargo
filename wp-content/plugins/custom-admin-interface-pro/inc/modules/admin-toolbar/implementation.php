<?php
    /**
    * 
    *
    *
    * Implement custom toolbar
    */
    add_action( 'admin_bar_menu', 'admin_toolbar_implementation', PHP_INT_MAX );
    function admin_toolbar_implementation( $wp_admin_bar ) {
    
        //get original toolbar items
        $original_toolbar_nodes = $wp_admin_bar->get_nodes();

        //create a global variable
        global $default_wordpress_toolbar;
        //set the variable to all nodes
        $default_wordpress_toolbar = $original_toolbar_nodes;

        //lets implement the menu
        //we are going to follow a similar procedure to how our other posts are implemented
        //we need to get all published posts and loop through them

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'posts';
        
        //just get all data
        $query = "SELECT * FROM $table_name WHERE post_type='admin_toolbar' AND post_status='publish'";
        
        $posts = $wpdb->get_results($query);

        if($posts){
            foreach($posts as $post){

                $post_id = $post->ID;
                
                $disable_on_frontend = get_post_meta($post_id, 'disable_frontend', true);

                if( (is_admin() && $disable_on_frontend == 'checked') || $disable_on_frontend != 'checked' ){

                    //check if the code needs to be executed
                    if(custom_admin_interface_pro_exception_check($post_id)){

                        //get users to hide
                        $custom_toolbar = get_post_meta($post_id, 'custom_toolbar', true);

                        // var_dump($custom_toolbar);

                        //lets do a check to make sure there's json
                        if(strpos($custom_toolbar, '{') !== false){
                            $custom_toolbar_array = json_decode($custom_toolbar);    

                            //create an array which will store all our notifications
                            $items_with_spans = array();
                            $items_meta = array();

                            //cycle through each node in the toolbar
                            foreach($original_toolbar_nodes as $item) {
                                //item id
                                $item_id = $item->id;
                                //item meta
                                $item_meta = $item->meta;
                                //lets add the id and meta to our array
                                $items_meta += array($item_id => $item_meta);
                                //item title
                                $item_title = $item->title;
                                //if item title contains a span lets add it to the array
                                if(strpos($item_title,'<span') !== false) {
                                    $items_with_spans += array($item_id => $item_title);    
                                }
                            }

                            //now we need to add this information to our settings
                            foreach($custom_toolbar_array as $item) {
                                //declare the id as a variable    
                                $item_id = $item->id;
                                //lets replace the meta
                                if(array_key_exists($item_id,$items_meta)){
                                    $meta_to_utilise = $items_meta[$item_id];
                                    $item->meta = $meta_to_utilise; 
                                } 
                                
                                //lets replace the titles
                                if(array_key_exists($item_id,$items_with_spans)){
                                    $title_to_utilise = $items_with_spans[$item_id];
                                    $item->title = $title_to_utilise;
                                }
                            }

                            // because wordpress wont just let us replace the global variable like it does on the main menu we need to remove all nodes from the original and then add our new nodes
                            // so lets first remove all the nodes
                            foreach($original_toolbar_nodes as $key => $val) {
                                $current_node = $original_toolbar_nodes[$key];
                                $wp_admin_bar->remove_node($key);
                            }

                            //now lets add our nodes...thanks wordpress!
                            foreach($custom_toolbar_array as $item) {
                            
                                $args = array(
                                    'id'    => $item->id,
                                    'title' => $item->title,
                                    'parent' => $item->parent,
                                    'href'  => $item->href,
                                    'group' => $item->group,
                                    // 'meta'  => $item->meta
                                );

                                if(property_exists($item,'meta')){
                                    $args['meta'] = $item->meta;
                                }

                                //if the item is a logout link, let's change the URL
                                if($item->id == 'logout'){
                                    $args['href'] = wp_login_url().'?action=logout&_wpnonce='. wp_create_nonce( 'log-out' );
                                }

                                //if Buddypress is activated replace url
                                if ( is_plugin_active( 'buddypress/bp-loader.php' ) &&  is_user_logged_in() ){
                                    if (strpos($item->href, '/members/') !== false) {
                                        $url_exploded = explode('/',$item->href);

                                        $username = bp_core_get_username( bp_loggedin_user_id() );

                                        $url_exploded[4] = $username;

                                        $new_url = implode('/',$url_exploded);

                                        $args['href'] = $new_url;

                                    }

                                    //if url is a profile url, and user not admin, change to frontend link
                                    // if( strpos($item->href, 'profile.php') !== false && ! is_admin() ){
                                    if( strpos($item->href, 'profile.php') !== false ){
                                        $username = bp_core_get_username( bp_loggedin_user_id() );
                                        $buddypress_profile_link =  apply_filters('custom_admin_interface_pro_buddypress_profile_edit_page','/members/'.$username.'/profile/edit/');
                                        $args['href'] = $buddypress_profile_link;    
                                    }

                                }

                                //if wp rocket, work with noonce
                                if ( is_plugin_active( 'wp-rocket/wp-rocket.php' ) ){

                                    if ($item->title == 'Clear cache') {
    
                                        $referer = filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_URL );

                                        $existing_url = $item->href;

                                        $find = '_wp_http_referer=';

                                        $position_in_string = strpos($existing_url, $find, 0);

                                        $new_nonce = wp_create_nonce( 'purge_cache_all' );

                                        $new_url = substr($existing_url, 0, $position_in_string + strlen($find)).$referer.'&_wpnonce='.$new_nonce;

                                        $args['href'] = $new_url;

                                    }

                                }

                                
                                $wp_admin_bar->add_node( $args );     
                            }


                            //Do divi fix - ensures exit visual builder toolbar item is visible on frontend builder
                            $theme = wp_get_theme();
                            $queried_object = get_queried_object();

                            if ( ( is_plugin_active( 'divi-builder/divi-builder.php' ) || 'Divi' == $theme->name || 'Divi' == $theme->parent_theme ) &&  is_user_logged_in() && $queried_object != NULL ){
                                
                                if( property_exists($queried_object, 'ID') ){
                                    $queried_post_id = $queried_object->ID;
                                    // $queried_post_guid = $queried_object->guid; 
                                    $post_link = get_the_permalink($queried_post_id);

                                    //do divi
                                    $args = array(
                                        'id' => 'et-disable-visual-builder',
                                        'title' => 'Exit Visual Builder',
                                        'parent' => false,
                                        'href' => $post_link,
                                        'group' => false,
                                        'meta' => array()
            
                                    );

                                    $wp_admin_bar->add_node( $args );   
                                }
                            }


                            //now lets hide items from the toolbar
                            $hide_items = get_post_meta($post_id, 'toolbar_items_to_remove', true);
                            $hide_items_array = explode(',', $hide_items);

                            foreach ($hide_items_array as $value) {
                                $wp_admin_bar->remove_node($value);  
                            }
                            


                        }
                    }

                } //end exception check
            } //end foreach post
        } //end post check



    }
    

?>