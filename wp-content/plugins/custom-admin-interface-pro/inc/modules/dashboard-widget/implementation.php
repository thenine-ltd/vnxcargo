<?php

    /**
    * 
    *
    *
    * Dashboard content
    */
    function dashboard_widget_output( $var, $args ) {

        //replace shortcodes
        $output = custom_admin_interface_pro_shortcode_replacer($args['args']);

        $output = apply_filters('custom_admin_interface_pro_dashboard_widget_content', $output);

        echo $output; 
    }
    /**
    * 
    *
    *
    * Enable shortcodes to run propertly
    */
    add_filter( 'custom_admin_interface_pro_dashboard_widget_content', 'do_shortcode' );

    /**
    * 
    *
    *
    * Function to add new dashboard widget
    */
    add_action( 'wp_dashboard_setup', 'dashboard_widget_implementation' );
    function dashboard_widget_implementation() {

        //we need to get all published posts and loop through them
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'posts';
        
        //just get all data
        $query = "SELECT * FROM $table_name WHERE post_type='dashboard_widget' AND post_status='publish'";
        
        $posts = $wpdb->get_results($query);




        if($posts){
            foreach($posts as $post){

                $post_id = $post->ID;
                
                //check if the code needs to be executed
                if(custom_admin_interface_pro_exception_check($post_id)){

                    $widget_title = get_post_meta($post_id, 'widget_title', true);
                    $widget_content = get_post_meta($post_id, 'widget_content', true);
    
                    if(strlen($widget_title) < 1) {
                        $widget_title = "Custom Widget";      
                    }
                    
                    wp_add_dashboard_widget(
                        'dashboard_widget_'.$post_id, //slug
                        $widget_title, //title
                        'dashboard_widget_output', //function
                        null,
                        $widget_content
                    );

                    
            
                } //end exception check
            } //end foreach post
        } //end post check

    }
    
?>