<?php

    /**
    * 
    *
    *
    * Output content of metabox
    */
    function hide_sidebar_metabox_content($post){

        //set no once
        wp_nonce_field( basename( __FILE__ ), 'hide_sidebar_metabox_nonce' );

        //get variables
        $post_id = $post->ID;

        //get existing values
        $hide_sidebar = get_post_meta($post_id, 'hide_sidebar', true);

        //enqueue styles and scripts
        wp_enqueue_script(array('common-script','hide-sidebar-script'));
        wp_enqueue_style(array('common-style','hide-sidebar-style'));

        //output code
        $html = '';

        $html .= '<div class="custom-admin-interface-pro-wrapper">';
            $html .= custom_admin_interface_pro_settings_inline_notice('blue',__('Hide these sidebars from showing on the <a target="_blank" href="widgets.php">widgets page</a>.','custom-admin-interface-pro'));
        

            //do select all
            $html .= custom_admin_interface_pro_select_all();

            //do list
            $html .= custom_admin_interface_pro_hide_list('sidebar',$hide_sidebar);

            $html .= '<input id="hide_sidebar" class="custom-admin-interface-pro-hide-setting" name="hide_sidebar" value="'.esc_html($hide_sidebar).'" />';

        $html .= '</div>';

        echo $html;


    }
    /**
    * 
    *
    *
    * Save the data in metabox
    */
    add_action( 'save_post', 'hide_sidebar_save_metabox', 10, 2 );
    function hide_sidebar_save_metabox( $post_id ){
	
        if ( !isset( $_POST['hide_sidebar_metabox_nonce'] ) || !wp_verify_nonce( $_POST['hide_sidebar_metabox_nonce'], basename( __FILE__ ) ) ){
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
           return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ){
            return;
        }

        //do settings
        
        if ( isset( $_REQUEST['hide_sidebar'] ) ) {
            update_post_meta( $post_id, 'hide_sidebar', sanitize_textarea_field($_POST['hide_sidebar']) );
        }
    
    }
?>