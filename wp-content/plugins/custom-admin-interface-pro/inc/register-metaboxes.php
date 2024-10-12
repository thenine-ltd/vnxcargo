<?php

    /**
    * 
    *
    *
    * Registers metaboxes - metaboxes are used for the custom post type settings pages
    */
    add_action('add_meta_boxes', 'custom_admin_interface_pro_register_metaboxes');
    function custom_admin_interface_pro_register_metaboxes(){

        // create metaboxes for the main settings area of each custom post type
        global $custom_admin_interface_pro_post_types;

        foreach($custom_admin_interface_pro_post_types as $post_type){

            //get key variables
            $post_type_single_name = $post_type['single'];
            $post_type_plural_plural = $post_type['plural'];
            $post_type_name_slug = custom_admin_interface_pro_functionify_name($post_type_single_name);

            //add show condition
            add_meta_box(
                $post_type_name_slug.'_conditions',           
                __('Conditions','custom-admin-interface-pro'),  
                'conditions_metabox_content',  
                $post_type_name_slug,            
                'advanced',
                'high'
            );

            //add main setting
            add_meta_box(
                $post_type_name_slug,        
                $post_type_single_name.' '.__('Settings','custom-admin-interface-pro'), 
                $post_type_name_slug.'_metabox_content', 
                $post_type_name_slug,                   
                'advanced',
                'high'
            );

            

        } //end for each post type

    }


?>