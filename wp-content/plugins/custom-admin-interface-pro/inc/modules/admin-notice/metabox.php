<?php
    
    /**
    * 
    *
    *
    * Output content of metabox
    */
    function admin_notice_metabox_content($post){

        //set no once
        wp_nonce_field( basename( __FILE__ ), 'admin_notice_metabox_nonce' );

        //get variables
        $post_id = $post->ID;

        //get existing values
        // $widget_title = get_post_meta($post_id, 'widget_title', true);
        $notice_content = html_entity_decode(stripslashes(get_post_meta($post_id, 'notice_content', true)));
        $notice_color = get_post_meta($post_id, 'notice_color', true);
        $notice_expiry = get_post_meta($post_id, 'notice_expiry', true);
        $notice_dismissable = get_post_meta($post_id, 'notice_dismissable', true);

        // //enqueue styles and scripts
        wp_enqueue_script(array('common-script','admin-notice-script'));
        wp_enqueue_style(array('common-style','admin-notice-style'));


        //output code

        echo '<div class="custom-admin-interface-pro-wrapper">';

            echo custom_admin_interface_pro_settings_inline_notice('blue',__('The admin notice creates a custom message that displays at the top of all admin pages.','custom-admin-interface-pro'));

            echo '<label>'.__('Notice Content','custom-admin-interface-pro').'</label>';

            //do shortcodes
            echo custom_admin_interface_pro_shortcode_output();

            wp_editor( $notice_content, 'notice_content', $settings = array(
                'wpautop' => false,
                'textarea_name' => 'notice_content',
                'drag_drop_upload' => true,
                'textarea_rows' => 30,
            ));   

            echo '<label>'.__('Admin Notice Color','custom-admin-interface-pro').'</label>';

            //options
            $options = array(
                'notice-success' => __('Green','custom-admin-interface-pro'),
                'notice-info' => __('Blue','custom-admin-interface-pro'),
                'notice-warning' => __('Yellow','custom-admin-interface-pro'),
                'notice-error' => __('Red','custom-admin-interface-pro'),
            );
            echo '<select name="notice_color">';
                foreach($options as $option_value => $option_label){
                    if($option_value == $notice_color){
                        echo '<option selected="selected" value="'.$option_value.'">'.$option_label.'</option>';
                    } else {
                        echo '<option value="'.$option_value.'">'.$option_label.'</option>';
                    }
                }

            echo '</select>';




            echo '<label>'.__('Admin Notice End Date','custom-admin-interface-pro').'</label>';

            echo '<input type="date" name="notice_expiry" value="'.$notice_expiry.'">';




            echo '<label>'.__('Make Notice Dismissable','custom-admin-interface-pro').'</label>';
            
            echo '<label class="switch">';
                if(strlen($notice_dismissable)>0){
                    echo '<input type="checkbox" name="notice_dismissable" value="checked" checked>';
                } else {
                    echo '<input type="checkbox" name="notice_dismissable" value="checked">';
                }
                echo '<span class="slider"></span>';
            echo '</label>';
            

                
            
        echo '</div>';

    }
    /**
    * 
    *
    *
    * Save the data in metabox
    */
    add_action( 'save_post', 'admin_notice_save_metabox', 10, 2 );
    function admin_notice_save_metabox( $post_id ){
	
        if ( !isset( $_POST['admin_notice_metabox_nonce'] ) || !wp_verify_nonce( $_POST['admin_notice_metabox_nonce'], basename( __FILE__ ) ) ){
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
           return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ){
            return;
        }

        //do settings
        
        if ( isset( $_REQUEST['notice_content'] ) ) {
            update_post_meta( $post_id, 'notice_content', $_POST['notice_content'] );
        }

        if ( isset( $_REQUEST['notice_color'] ) ) {
            update_post_meta( $post_id, 'notice_color', sanitize_text_field($_POST['notice_color']) );
        }

        if ( isset( $_REQUEST['notice_expiry'] ) ) {
            update_post_meta( $post_id, 'notice_expiry', sanitize_text_field($_POST['notice_expiry']) );
        }

        if ( isset( $_REQUEST['notice_dismissable'] ) && strlen($_REQUEST['notice_dismissable'])>0) {
            update_post_meta( $post_id, 'notice_dismissable', sanitize_text_field($_POST['notice_dismissable']) );
        } else {
            delete_post_meta( $post_id, 'notice_dismissable');    
        }
        
    }
    /**
    * 
    *
    *
    * Save the revision data
    */
    add_action( 'save_post', 'admin_notice_save_revision');
    function admin_notice_save_revision( $post_id ){
        
        $parent_id = wp_is_post_revision( $post_id );

        if ( $parent_id ) {

            $parent  = get_post( $parent_id );

            //field
            $notice_content = get_post_meta( $parent->ID, 'notice_content', true );

            if ( false !== $notice_content ){
                add_metadata( 'post', $post_id, 'notice_content', $notice_content );
            }

            //field
            $notice_color = get_post_meta( $parent->ID, 'notice_color', true );

            if ( false !== $notice_color ){
                add_metadata( 'post', $post_id, 'notice_color', $notice_color );
            }

            //field
            $notice_expiry = get_post_meta( $parent->ID, 'notice_expiry', true );

            if ( false !== $notice_expiry ){
                add_metadata( 'post', $post_id, 'notice_expiry', $notice_expiry );
            }

            //field
            $notice_dismissable = get_post_meta( $parent->ID, 'notice_dismissable', true );

            if ( false !== $notice_dismissable ){
                add_metadata( 'post', $post_id, 'notice_dismissable', $notice_dismissable );
            }
                

        }

        
    }
    /**
    * 
    *
    *
    * Restore the revision
    */
    add_action( 'wp_restore_post_revision', 'admin_notice_restore_revision', 10, 2 );
    function admin_notice_restore_revision( $post_id, $revision_id ) {

        $post     = get_post( $post_id );
        $revision = get_post( $revision_id );

        //field
        $notice_content  = get_metadata( 'post', $revision->ID, 'notice_content', true );
    
        if ( false !== $notice_content ){
            update_post_meta( $post_id, 'notice_content', $notice_content );
        } else {
            delete_post_meta( $post_id, 'notice_content' );
        }

        //field
        $notice_color  = get_metadata( 'post', $revision->ID, 'notice_color', true );
    
        if ( false !== $notice_color ){
            update_post_meta( $post_id, 'notice_color', $notice_color );
        } else {
            delete_post_meta( $post_id, 'notice_color' );
        }

        //field
        $notice_expiry  = get_metadata( 'post', $revision->ID, 'notice_expiry', true );
    
        if ( false !== $notice_expiry ){
            update_post_meta( $post_id, 'notice_expiry', $notice_expiry );
        } else {
            delete_post_meta( $post_id, 'notice_expiry' );
        }

        //field
        $notice_dismissable  = get_metadata( 'post', $revision->ID, 'notice_dismissable', true );
    
        if ( false !== $notice_dismissable ){
            update_post_meta( $post_id, 'notice_dismissable', $notice_dismissable );
        } else {
            delete_post_meta( $post_id, 'notice_dismissable' );
        }
    
    }
?>