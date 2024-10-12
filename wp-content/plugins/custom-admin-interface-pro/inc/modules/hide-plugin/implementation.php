<?php
    /**
    * 
    *
    *
    * Remove plugins from plugin page
    */
    add_action('pre_current_active_plugins', 'hide_plugin_implementation');
    function hide_plugin_implementation() {

        //we need to get all published posts and loop through them
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'posts';
        
        //just get all data
        $query = "SELECT * FROM $table_name WHERE post_type='hide_plugin' AND post_status='publish'";
        
        $posts = $wpdb->get_results($query);

        if($posts){

            foreach($posts as $post){

                $post_id = $post->ID;
                
                //check if the code needs to be executed
                if(custom_admin_interface_pro_exception_check($post_id)){

                    global $wp_list_table;

                    //get users to hide
                    $hide_plugin = get_post_meta($post_id, 'hide_plugin', true);

                    $plugins_to_remove_array = explode(",",$hide_plugin);   

                    $my_plugins = $wp_list_table->items;

                    foreach ($my_plugins as $key => $val) {
                        if (in_array($key,$plugins_to_remove_array)) {
                            unset($wp_list_table->items[$key]);
                        }
                    }  
                    
                } //end exception check
            } //end foreach post
        } //end post check

    }
    
?>