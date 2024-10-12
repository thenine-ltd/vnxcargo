<?php

    /**
    * 
    *
    *
    * Output content of metabox
    */
    function custom_frontend_code_metabox_content($post){

        //set no once
        wp_nonce_field( basename( __FILE__ ), 'custom_frontend_code_metabox_nonce' );

        //get variables
        $post_id = $post->ID;

        //get existing values
        $custom_css_frontend_value = get_post_meta($post_id, 'custom_css_frontend', true);
        $custom_js_frontend_value = get_post_meta($post_id, 'custom_js_frontend', true);

        //enqueue styles and scripts
        wp_enqueue_script(array('codemirror','css-lint','css','javascript-lint','javascript','closebrackets','matchbrackets','closetag','matchtags','common-script','custom-frontend-code-script'));
        wp_enqueue_style(array('codemirror','blackboard','common-style','custom-frontend-code-style'));

        //output code
        $html = '';
        
        $html .= '<div class="custom-admin-interface-pro-wrapper">';

            $html .= custom_admin_interface_pro_settings_inline_notice('blue',__('The custom code section enables you to implement custom code on the frontend of Wordpress.','custom-admin-interface-pro'));

            $html .= '<label>'.__('Custom CSS','custom-admin-interface-pro').'</label>';
            $html .= '<textarea id="custom_css_frontend" name="custom_css_frontend">'.esc_html($custom_css_frontend_value).'</textarea>';

            $html .= '<label>'.__('Custom Javascript/jQuery','custom-admin-interface-pro').'</label>';
            $html .= '<textarea id="custom_js_frontend" name="custom_js_frontend">'.esc_html( $custom_js_frontend_value ).'</textarea>';
        $html .= '</div>';

        echo $html;


    }
    /**
    * 
    *
    *
    * Save the data in metabox
    */
    add_action( 'save_post', 'custom_frontend_code_save_metabox', 10, 2 );
    function custom_frontend_code_save_metabox( $post_id ){
	
        if ( !isset( $_POST['custom_frontend_code_metabox_nonce'] ) || !wp_verify_nonce( $_POST['custom_frontend_code_metabox_nonce'], basename( __FILE__ ) ) ){
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
           return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ){
            return;
        }

        //do settings
        
        if ( isset( $_REQUEST['custom_css_frontend'] ) ) {
            update_post_meta( $post_id, 'custom_css_frontend', sanitize_textarea_field($_POST['custom_css_frontend']) );
        }

        if ( isset( $_REQUEST['custom_js_frontend'] ) ) {
            update_post_meta( $post_id, 'custom_js_frontend', sanitize_textarea_field( $_POST['custom_js_frontend'] ) );
        }
    
    }
    /**
    * 
    *
    *
    * Save the revision data
    */
    add_action( 'save_post', 'custom_frontend_code_save_revision');
    function custom_frontend_code_save_revision( $post_id ){



        $parent_id = wp_is_post_revision( $post_id );

        if ( $parent_id ) {

            $parent  = get_post( $parent_id );
            
            //field
            $custom_css_frontend = get_post_meta( $parent->ID, 'custom_css_frontend', true );

            if ( false !== $custom_css_frontend ){
                add_metadata( 'post', $post_id, 'custom_css_frontend', $custom_css_frontend );
            }

            //field
            $custom_js_frontend = get_post_meta( $parent->ID, 'custom_js_frontend', true );

            if ( false !== $custom_js_frontend ){
                add_metadata( 'post', $post_id, 'custom_js_frontend', $custom_js_frontend );
            }
                
        }
       
        
    }
    /**
    * 
    *
    *
    * Restore the revision
    */
    add_action( 'wp_restore_post_revision', 'custom_frontend_code_restore_revision', 10, 2 );
    function custom_frontend_code_restore_revision( $post_id, $revision_id ) {

        $post     = get_post( $post_id );
        $revision = get_post( $revision_id );
        
        //field
        $custom_css_frontend  = get_metadata( 'post', $revision->ID, 'custom_css_frontend', true );
    
        if ( false !== $custom_css_frontend ){
            update_post_meta( $post_id, 'custom_css_frontend', $custom_css_frontend );
        } else {
            delete_post_meta( $post_id, 'custom_css_frontend' );
        }

        //field
        $custom_js_frontend  = get_metadata( 'post', $revision->ID, 'custom_js_frontend', true );
    
        if ( false !== $custom_js_frontend ){
            update_post_meta( $post_id, 'custom_js_frontend', $custom_js_frontend );
        } else {
            delete_post_meta( $post_id, 'custom_js_frontend' );
        }
    
    }
?>