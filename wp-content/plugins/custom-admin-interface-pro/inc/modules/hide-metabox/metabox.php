<?php

    /**
    * 
    *
    *
    * Output content of metabox
    */
    function hide_metabox_metabox_content($post){

        //set no once
        wp_nonce_field( basename( __FILE__ ), 'hide_metabox_metabox_nonce' );

        //get variables
        $post_id = $post->ID;

        //get existing values
        $hide_metabox = get_post_meta($post_id, 'hide_metabox', true);

        //enqueue styles and scripts
        wp_enqueue_script(array('common-script','hide-metabox-script'));
        wp_enqueue_style(array('common-style','hide-metabox-style'));

        //output code
        $html = '';

        $html .= '<div class="custom-admin-interface-pro-wrapper">';
            $html .= custom_admin_interface_pro_settings_inline_notice('blue',__('This will hide meta boxes which are shown on different parts of WordPress.','custom-admin-interface-pro'));
            // $html .= custom_admin_interface_pro_settings_inline_notice('red',__('Be careful not to hide the metabox on ','custom-admin-interface-pro'));


            //do select all
            $html .= custom_admin_interface_pro_select_all();

            //do list
            $html .= custom_admin_interface_pro_hide_list('metabox',$hide_metabox);

            $html .= '<input id="hide_metabox" class="custom-admin-interface-pro-hide-setting" name="hide_metabox" value="'.esc_html($hide_metabox).'" />';

        $html .= '</div>';

        echo $html;


    }
    /**
    * 
    *
    *
    * Save the data in metabox
    */
    add_action( 'save_post', 'hide_metabox_save_metabox', 10, 2 );
    function hide_metabox_save_metabox( $post_id ){
	
        if ( !isset( $_POST['hide_metabox_metabox_nonce'] ) || !wp_verify_nonce( $_POST['hide_metabox_metabox_nonce'], basename( __FILE__ ) ) ){
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
           return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ){
            return;
        }

        //do settings
        
        if ( isset( $_REQUEST['hide_metabox'] ) ) {
            update_post_meta( $post_id, 'hide_metabox', sanitize_textarea_field($_POST['hide_metabox']) );
        }
    
    }
?>