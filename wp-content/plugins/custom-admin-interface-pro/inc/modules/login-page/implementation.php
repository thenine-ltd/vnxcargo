<?php

    /**
    * 
    *
    *
    * Function to change login styling and scripts
    */
    add_action( 'login_enqueue_scripts', 'custom_admin_interface_pro_login_styling' );
    function custom_admin_interface_pro_login_styling() {
        
        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );

            //enqueue style and script
            wp_enqueue_style('caip-custom-login-style', plugins_url('../../custom-login-style.css', __FILE__ ), array(),custom_admin_code_pro_version());
            wp_enqueue_script( 'caip-custom-login-script', plugins_url( '../../custom-login-script.js', __FILE__ ), array( 'jquery') ,custom_admin_code_pro_version());


            //do style
            $custom_style = '';

            //do background
            if(isset($options['login_page']['login_background']) && strlen($options['login_page']['login_background'])>0 ){
                $custom_style .= "
                .login {
                background-color: {$options['login_page']['login_background']};
                }"; 
            }

            //do background image if set instead
            if(
                isset($options['login_page']['login_background_image']) && strlen($options['login_page']['login_background_image'])>0 &&
                isset($options['login_page']['login_background_image_position']) && strlen($options['login_page']['login_background_image_position'])>0 &&
                isset($options['login_page']['login_background_image_size']) && strlen($options['login_page']['login_background_image_size'])>0 &&
                isset($options['login_page']['login_background_image_repeat']) && strlen($options['login_page']['login_background_image_repeat'])>0 
            ){
                $custom_style .= "
                .login {
                background-image: url({$options['login_page']['login_background_image']});
                background-position: {$options['login_page']['login_background_image_position']};
                background-size: {$options['login_page']['login_background_image_size']};
                background-repeat: {$options['login_page']['login_background_image_repeat']};
                }";    
            }


            if(isset($options['login_page']['login_text']) && strlen($options['login_page']['login_text'])>0 ){
                $custom_style .= ".login #backtoblog a, .login #nav a {
                    color: {$options['login_page']['login_text']} !important;
                }";
            }


            if(isset($options['login_page']['login_logo']) && strlen($options['login_page']['login_logo'])>0 ){

                if( $options['login_page']['login_logo'] == '[site_logo]' ){
                    $site_logo = get_theme_mod( 'custom_logo' );
                    $site_logo = wp_get_attachment_image_src( $site_logo , 'full' );
                    $site_logo = $site_logo[0];
                } else {
                    $site_logo =  $options['login_page']['login_logo'];   
                }

                $custom_style .= "#login h1 a, .login h1 a {
                    background-image: url({$site_logo});
                    width:320px !important;
                    background-size: contain !important;
                    background-repeat: no-repeat;

                }";

                //this script is only doing the logo
                wp_enqueue_script( 'caip-custom-login-script');
            }

            if(isset($options['login_page']['login_css']) && strlen($options['login_page']['login_css'])>0 ){
                $custom_style .= stripslashes( wp_strip_all_tags( $options['login_page']['login_css'] ) );
            }

            wp_add_inline_style( 'caip-custom-login-style', $custom_style );  


            //do scripts
            $custom_script = '';

            if(isset($options['login_page']['login_js']) && strlen($options['login_page']['login_js'])>0 ){
                $custom_script .=  wp_strip_all_tags(stripslashes($options['login_page']['login_js']));
            }
            wp_add_inline_script( 'caip-custom-login-script', $custom_script );

        } 
        
    }

    /**
    * 
    *
    *
    * Changes the login URL
    */
    function custom_admin_interface_pro_login_url($url) {
        
        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );

            if(isset($options['login_page']['login_url']) && strlen($options['login_page']['login_url'])>0 ){
                $url = $options['login_page']['login_url']; 
            } else {
                $url = '#';  
            }
        }

        return $url;  
    }
    add_filter( 'login_headerurl', 'custom_admin_interface_pro_login_url' );
  

?>