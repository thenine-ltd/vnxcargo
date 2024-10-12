<?php

    /**
    * 
    *
    *
    * Output content of metabox
    */
    function hide_plugin_metabox_content($post){

        //set no once
        wp_nonce_field( basename( __FILE__ ), 'hide_plugin_metabox_nonce' );

        //get variables
        $post_id = $post->ID;

        //get existing values
        $hide_plugin = get_post_meta($post_id, 'hide_plugin', true);

        //enqueue styles and scripts
        wp_enqueue_script(array('common-script','hide-plugin-script'));
        wp_enqueue_style(array('common-style','hide-plugin-style'));

        //output code
        $html = '';

        $html .= '<div class="custom-admin-interface-pro-wrapper">';

            $html .= custom_admin_interface_pro_settings_inline_notice('blue',__('Hide these plugins from showing on the <a target="_blank" href="plugins.php">plugins</a> page.','custom-admin-interface-pro'));

            //do select all
            $html .= custom_admin_interface_pro_select_all();

            //do list
            $html .= custom_admin_interface_pro_hide_list('plugin',$hide_plugin);

            $html .= '<input id="hide_plugin" class="custom-admin-interface-pro-hide-setting" name="hide_plugin" value="'.esc_html($hide_plugin).'" />';

        $html .= '</div>';

        echo $html;


    }
    /**
    * 
    *
    *
    * Save the data in metabox
    */
    add_action( 'save_post', 'hide_plugin_save_metabox', 10, 2 );
    function hide_plugin_save_metabox( $post_id ){
	
        if ( !isset( $_POST['hide_plugin_metabox_nonce'] ) || !wp_verify_nonce( $_POST['hide_plugin_metabox_nonce'], basename( __FILE__ ) ) ){
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
           return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ){
            return;
        }

        //do settings
        
        if ( isset( $_REQUEST['hide_plugin'] ) ) {
            update_post_meta( $post_id, 'hide_plugin', sanitize_textarea_field($_POST['hide_plugin']) );
        }
    
    }
?>