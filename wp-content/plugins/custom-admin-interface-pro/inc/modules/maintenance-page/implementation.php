<?php


    /**
    * 
    *
    *
    * Function to enable maintenance mode
    */
    add_action( 'wp', 'custom_admin_interface_pro_maintenance_page' );
    function custom_admin_interface_pro_maintenance_page() {


        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option( 'custom_admin_interface_pro_settings' );


            if(isset($options['maintenance_page']['enable_maintenance_mode']) && strlen($options['maintenance_page']['enable_maintenance_mode'])>0 ){

                global $pagenow;
                global $post;

                $post_id = $post->ID; 

                if(isset($options['maintenance_page']['maintenance_mode_end_date']) && strlen($options['maintenance_page']['maintenance_mode_end_date'])>0 ){
                    $maintenance_expiry_date = $options['maintenance_page']['maintenance_mode_end_date'];    
                } else {
                    $maintenance_expiry_date = "2050-01-01";        
                }

                
                $todays_date = date('Y-m-d');
                
                $user = wp_get_current_user();
                $allowed_roles = apply_filters( 'custom_admin_interface_pro_modify_maintenance_page_roles', array('editor', 'administrator', 'author') );

                $page_ids_to_ignore = apply_filters( 'custom_admin_interface_pro_page_ids_to_ignore_maintenance_page', array() );

                    
                if (!in_array($post_id, $page_ids_to_ignore) && $pagenow !== 'wp-login.php' && 
                !is_admin() && 
                !current_user_can( 'manage_options' ) && 
                !array_intersect($allowed_roles, $user->roles ) && 
                $options['maintenance_page']['enable_maintenance_mode'] == 'checked' && 
                $todays_date < $maintenance_expiry_date) {

                    header('HTTP/1.1 503 Service Temporarily Unavailable', true, 503 );
                    header( 'Content-Type: text/html; charset=utf-8' );
                    ?>

                    <!DOCTYPE html>
                    <html>
                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1">
                            <link rel="profile" href="http://gmpg.org/xfn/11">
                            <style type="text/css">
                            * {
                                -webkit-box-sizing: border-box;
                                -moz-box-sizing: border-box;
                                box-sizing: border-box; 
                            }

                            html, body {
                                min-height: 100%; 
                            }

                            body {
                                background: <?php echo $options['maintenance_page']['custom_background_color']; ?>;
                                background-image: url(<?php echo $options['maintenance_page']['custom_background_image']; ?>);
                                background-position: <?php echo $options['maintenance_page']['custom_background_image_position']; ?>;
                                background-size: <?php echo $options['maintenance_page']['custom_background_image_size']; ?>;
                                background-repeat: <?php echo $options['maintenance_page']['custom_background_image_repeat']; ?>;
                                font-family: Helvetica, Arial, sans-serif;
                                font-size: 18px;
                                text-align: center; 
                            }

                            #container {
                                margin: 40px auto;
                                max-width: 600px;
                                background: white;
                                box-shadow: 0px 0px 5px 0px #e0dfdf;
                                padding-bottom: 30px;
                                padding-top:0px;
                                padding-left: 30px; 
                                padding-right: 30px;
                            }

                            main {
                                margin-top: 30px;
                            }

                            p {
                                margin: 0 0 20px; 
                            }
                                
                            .logo {
                                max-width:540px;   
                                margin-top: 30px;          
                            }
                                
                            @media screen and (max-width: 630px) {
                                .logo {
                                    max-width:100%;             
                                }   
                            }
                                
                            </style>
                            
                            <title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
                        </head>

                        <body>
                            <div id="container">
                                <header>
                                    <?php
                                        if(isset($options['maintenance_page']['custom_maintenance_logo']) && strlen($options['maintenance_page']['custom_maintenance_logo'])>0 ){
                                            if( $options['maintenance_page']['custom_maintenance_logo'] == '[site_logo]' ){
                                                $site_logo = get_theme_mod( 'custom_logo' );
                                                $site_logo = wp_get_attachment_image_src( $site_logo , 'full' );
                                                $site_logo = $site_logo[0];
                                            } else {
                                                $site_logo =  $options['maintenance_page']['custom_maintenance_logo'];   
                                            }

                                            echo '<img class="logo" src="'.$site_logo.'">';

                                        }
                                    ?>
                                    
                                </header>
                                <main>

                                
                                    <?php 
                                        //the below code was showing elementor content inadvertendly. If someone complains, we need to find out a better way of doing this 24/04/23
                                        // echo apply_filters('the_content', stripslashes($options['maintenance_page']['custom_maintenance_text']));

                                        echo stripslashes($options['maintenance_page']['custom_maintenance_text']);
                                    ?>
                                </main>
                            </div>
                        </body>
                    </html>

                    <?php
                    die();
                }
            }    

        }

    }


?>