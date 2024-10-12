<?php
    /**
    * 
    *
    *
    * Remove sidebars from widget page
    */
    add_action('admin_init', 'hide_sidebar_implementation');
    function hide_sidebar_implementation() {

        global $wp_registered_sidebars;
        global $custom_admin_interface_pro_original_sidebar_listing; 
        
        $custom_admin_interface_pro_original_sidebar_listing = $wp_registered_sidebars;

        //we need to get all published posts and loop through them
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'posts';
        
        //just get all data
        $query = "SELECT * FROM $table_name WHERE post_type='hide_sidebar' AND post_status='publish'";
        
        $posts = $wpdb->get_results($query);

        if($posts){
            foreach($posts as $post){

                $post_id = $post->ID;
                
                //check if the code needs to be executed
                if(custom_admin_interface_pro_exception_check($post_id)){

                    //get users to hide
                    $hide_sidebar = get_post_meta($post_id, 'hide_sidebar', true);

                    $sidebars_to_remove_array = explode(",",$hide_sidebar);   
                    
                    foreach ($sidebars_to_remove_array as &$sidebar) {
                        unregister_sidebar($sidebar);
                    }

                } //end exception check
            } //end foreach post
        } //end post check
        
    }
    
?>