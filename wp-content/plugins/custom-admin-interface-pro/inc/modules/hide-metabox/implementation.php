<?php
    /**
    * 
    *
    *
    * Remove metaboxes
    */
    add_action('admin_head','hide_metabox_implementation');
    function hide_metabox_implementation() {

        //we need to get all published posts and loop through them
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'posts';
        
        //just get all data
        $query = "SELECT * FROM $table_name WHERE post_type='hide_metabox' AND post_status='publish'";
        
        $posts = $wpdb->get_results($query);

        if($posts){
            foreach($posts as $post){

                $post_id = $post->ID;
                
                //check if the code needs to be executed
                if(custom_admin_interface_pro_exception_check($post_id)){

                    //get users to hide
                    $hide_metabox = get_post_meta($post_id, 'hide_metabox', true);

                    $metaboxes_to_remove_array = explode(",",$hide_metabox);   
                    
                    foreach($metaboxes_to_remove_array as $metabox){

                        //explode again
                        $metabox_properties = explode('|',$metabox);
                        $metabox_key = $metabox_properties[0];

                        if( array_key_exists(1,$metabox_properties) ){
                            $metabox_post_type = $metabox_properties[1];

                            remove_meta_box($metabox_key,$metabox_post_type,'normal');
                            remove_meta_box($metabox_key,$metabox_post_type,'advanced');
                            remove_meta_box($metabox_key,$metabox_post_type,'side');
                        }
                    }
                } //end exception check
            } //end foreach post
        } //end post check
        
    }







    
?>