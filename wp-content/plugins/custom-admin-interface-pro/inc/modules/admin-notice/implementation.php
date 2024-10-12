<?php
    /**
    * 
    *
    *
    * Function to display admin notice
    */
    add_action( 'admin_notices', 'admin_notice_implementation' );
    function admin_notice_implementation() {

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'posts';
        
        //just get all data
        $query = "SELECT * FROM $table_name WHERE post_type='admin_notice' AND post_status='publish'";
        
        $posts = $wpdb->get_results($query);

        if($posts){
            foreach($posts as $post){

                $post_id = $post->ID;
                
                //check if the code needs to be executed
                if(custom_admin_interface_pro_exception_check($post_id)){

                    //enqueue the dismiss script
                    wp_enqueue_script('admin-notice-dismiss-script');

                    $current_user_id = get_current_user_id();

                    $notice_content = get_post_meta($post_id, 'notice_content', true);
                    $notice_color = get_post_meta($post_id, 'notice_color', true);
                    $notice_expiry = get_post_meta($post_id, 'notice_expiry', true);
                    $notice_dismissable = get_post_meta($post_id, 'notice_dismissable', true);

                    if( !isset($notice_expiry) || strlen($notice_expiry) < 1 ){
                        $notice_expiry = '2050-01-01';
                    }

                    $todays_date = date('Y-m-d');   

                    //check if the notice is expired
                    if ($todays_date < $notice_expiry) {

                        // var_dump('I WAS RAN');

                        //check if the transient exists
                        $transient_name = 'admin_notice_'.$post_id.'_'.$current_user_id;

                        //get transient
                        $get_transient = get_transient($transient_name);   
                        
                        // var_dump($get_transient);
                
                        if($get_transient != true) {

                            if(isset($notice_dismissable) && strlen($notice_dismissable)>0){
                                $dismissable_class = 'is-dismissible'; 
                            } else {
                                $dismissable_class = '';     
                            }
    
                            echo '<div id="admin_notice_'.$post_id.'" data-post-id="'.$post_id.'" data-user-id="'.$current_user_id.'" class="custom-admin-interface-pro-notice notice '.$notice_color.' '.$dismissable_class.'">';
                            
                                echo custom_admin_interface_pro_shortcode_replacer($notice_content);
    
                            echo '</div>';

                        }

                    }
    
                } //end exception check
            } //end foreach post
        } //end post check 
    }
    /**
    * 
    *
    *
    * Function to add transient when someone dismisses the notice message
    */
    function custom_admin_interface_admin_notice_dismiss() {
        
        //get user input
        $user_id = $_POST['userId']; 
        $post_id = $_POST['postId']; 
        
        //create transient name - an stands for admin notice    
        $transient_name = 'admin_notice_'.$post_id.'_'.$user_id;
        
        //create transient with no expiration
        set_transient($transient_name,true,0);

        // echo 'done';
            
        wp_die();    
    }
    add_action( 'wp_ajax_custom_admin_interface_admin_notice_dismiss', 'custom_admin_interface_admin_notice_dismiss' );
    

    /**
    * 
    *
    *
    * Function to clear transients related to the admin notice
    */
    function custom_admin_interface_delete_notice_transients( $post_id ) {

        // If this is just a revision, don't send the email.
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        global $wpdb; 
        $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_admin_notice_{$post_id}_%'";
        $wpdb->query($sql);
      
    }
    add_action( 'save_post_admin_notice', 'custom_admin_interface_delete_notice_transients' );

?>