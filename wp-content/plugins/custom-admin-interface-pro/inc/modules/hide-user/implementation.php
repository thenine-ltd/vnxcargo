<?php
    /**
    * 
    *
    *
    * Remove users from user page
    */
    add_action('pre_user_query', 'hide_user_implementation');
    function hide_user_implementation($user_search) {



        //we need to get all published posts and loop through them
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'posts';
        
        //just get all data
        $query = "SELECT * FROM $table_name WHERE post_type='hide_user' AND post_status='publish'";
        
        $posts = $wpdb->get_results($query);

        if($posts){
            foreach($posts as $post){

                $post_id = $post->ID;
                
                //check if the code needs to be executed
                if(custom_admin_interface_pro_exception_check($post_id)){

                    //get users to hide
                    $hide_user = get_post_meta($post_id, 'hide_user', true);
                    

                    //gel global variable    
                    global $current_user;
                    //get current user login    
                    $user_id = $current_user->ID;
                    //create an array from exception cases
                    $hide_user_array = explode(",",$hide_user);     

                    //get the database    
                    global $wpdb;

                    foreach ($hide_user_array as &$user_to_remove) {

                        //if the current user is the user lets not remove them, so this way they can still see themselves
                        if ($user_id == $user_to_remove) { 

                        } else {
                            //do query
                            $user_search->query_where = str_replace('WHERE 1=1',"WHERE 1=1 AND {$wpdb->users}.ID != '$user_to_remove'",$user_search->query_where);    
                        }       

                    } 

                } //end exception check
            } //end foreach post
        } //end post check


    }
    
?>