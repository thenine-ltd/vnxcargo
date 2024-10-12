<?php
    
    /**
    * 
    *
    *
    * Output content of metabox
    */
    function dashboard_widget_metabox_content($post){

        //set no once
        wp_nonce_field( basename( __FILE__ ), 'dashboard_widget_metabox_nonce' );

        //get variables
        $post_id = $post->ID;

        //get existing values
        $widget_title = get_post_meta($post_id, 'widget_title', true);
        $widget_content = html_entity_decode(stripslashes(get_post_meta($post_id, 'widget_content', true)));

        // //enqueue styles and scripts
        wp_enqueue_script(array('common-script','dashboard-widget-script'));
        wp_enqueue_style(array('common-style','dashboard-widget-style'));


        //output code

        echo '<div class="custom-admin-interface-pro-wrapper">';

            echo custom_admin_interface_pro_settings_inline_notice('blue',__('This section enables you to create a custom widget which will display on your dashboard.','custom-admin-interface-pro'));

            echo '<label>'.__('Widget Title','custom-admin-interface-pro').'</label>';
            echo '<input id="widget_title" type="text" name="widget_title" value="'.esc_html($widget_title).'">';

            echo '<label>'.__('Widget Content','custom-admin-interface-pro').'</label>';

            //do shortcodes
            echo custom_admin_interface_pro_shortcode_output();


                
            wp_editor( $widget_content, 'widget_content', $settings = array(
                'wpautop' => false,
                'textarea_name' => 'widget_content',
                'drag_drop_upload' => true,
                'textarea_rows' => 30,
            ));         
            
        echo '</div>';

    }
    /**
    * 
    *
    *
    * Save the data in metabox
    */
    add_action( 'save_post', 'dashboard_widget_save_metabox', 10, 2 );
    function dashboard_widget_save_metabox( $post_id ){
	
        if ( !isset( $_POST['dashboard_widget_metabox_nonce'] ) || !wp_verify_nonce( $_POST['dashboard_widget_metabox_nonce'], basename( __FILE__ ) ) ){
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
           return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ){
            return;
        }

        //do settings
        
        if ( isset( $_REQUEST['widget_title'] ) ) {
            update_post_meta( $post_id, 'widget_title', sanitize_textarea_field($_POST['widget_title']) );
        }

        if ( isset( $_REQUEST['widget_content'] ) ) {
            update_post_meta( $post_id, 'widget_content', $_POST['widget_content'] );
        }
    
    }
    /**
    * 
    *
    *
    * Save the revision data
    */
    add_action( 'save_post', 'dashboard_widget_save_revision');
    function dashboard_widget_save_revision( $post_id ){

        $parent_id = wp_is_post_revision( $post_id );

        if ( $parent_id ) {

            $parent  = get_post( $parent_id );

            //field
            $widget_title = get_post_meta( $parent->ID, 'widget_title', true );

            if ( false !== $widget_title ){
                add_metadata( 'post', $post_id, 'widget_title', $widget_title );
            }

            //field
            $widget_content = get_post_meta( $parent->ID, 'widget_content', true );

            if ( false !== $widget_content ){
                add_metadata( 'post', $post_id, 'widget_content', $widget_content );
            }
                

        }
        
    }
    /**
    * 
    *
    *
    * Restore the revision
    */
    add_action( 'wp_restore_post_revision', 'dashboard_widget_restore_revision', 10, 2 );
    function dashboard_widget_restore_revision( $post_id, $revision_id ) {

        $post     = get_post( $post_id );
        $revision = get_post( $revision_id );
        
        //field
        $widget_title  = get_metadata( 'post', $revision->ID, 'widget_title', true );
    
        if ( false !== $widget_title ){
            update_post_meta( $post_id, 'widget_title', $widget_title );
        } else {
            delete_post_meta( $post_id, 'widget_title' );
        }

        //field
        $widget_content  = get_metadata( 'post', $revision->ID, 'widget_content', true );
    
        if ( false !== $widget_content ){
            update_post_meta( $post_id, 'widget_content', $widget_content );
        } else {
            delete_post_meta( $post_id, 'widget_content' );
        }
    
    }
?>