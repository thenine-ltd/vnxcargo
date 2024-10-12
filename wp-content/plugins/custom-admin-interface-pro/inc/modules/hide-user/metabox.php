<?php

    /**
    * 
    *
    *
    * Output content of metabox
    */
    function hide_user_metabox_content($post){

        //set no once
        wp_nonce_field( basename( __FILE__ ), 'hide_user_metabox_nonce' );

        //get variables
        $post_id = $post->ID;

        //get existing values
        $hide_user = get_post_meta($post_id, 'hide_user', true);

        //enqueue styles and scripts
        wp_enqueue_script(array('common-script','hide-user-script'));
        wp_enqueue_style(array('common-style','hide-user-style'));

        //output code
        $html = '';

        $html .= '<div class="custom-admin-interface-pro-wrapper">';

            $html .= custom_admin_interface_pro_settings_inline_notice('blue',__('Hide these users from showing on the <a target="_blank" href="users.php">users page</a>. Please note if you hide yourself you will still see yourself in the user listing, however other people won\'t be able to see you.','custom-admin-interface-pro'));

            //do select all
            $html .= custom_admin_interface_pro_select_all();

            //do list
            $html .= custom_admin_interface_pro_hide_list('user',$hide_user);

            $html .= '<input id="hide_user" class="custom-admin-interface-pro-hide-setting" name="hide_user" value="'.esc_html($hide_user).'" />';

        $html .= '</div>';

        echo $html;


    }
    /**
    * 
    *
    *
    * Save the data in metabox
    */
    add_action( 'save_post', 'hide_user_save_metabox', 10, 2 );
    function hide_user_save_metabox( $post_id ){
	
        if ( !isset( $_POST['hide_user_metabox_nonce'] ) || !wp_verify_nonce( $_POST['hide_user_metabox_nonce'], basename( __FILE__ ) ) ){
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
           return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ){
            return;
        }

        //do settings
        
        if ( isset( $_REQUEST['hide_user'] ) ) {
            update_post_meta( $post_id, 'hide_user', sanitize_textarea_field($_POST['hide_user']) );
        }
    
    }
?>