<?php

    /**
    * 
    *
    *
    * Function to add custom colour scheme
    */
    add_action('admin_init', 'custom_admin_interface_pro_color_scheme');
    function custom_admin_interface_pro_color_scheme() {

        $options = get_option('custom_admin_interface_pro_settings');    

        //do svg
        if(isset($options['admin_color_scheme_page']['svg_icon_color']) && strlen($options['admin_color_scheme_page']['svg_icon_color'])>0 ){
            $svg_icon_color = $options['admin_color_scheme_page']['svg_icon_color'];
        } else {
            $svg_icon_color = '#f1f2f3';  
        }

        //do colors
        if(
            isset($options['admin_color_scheme_page']['color_scheme_color_1']) && strlen($options['admin_color_scheme_page']['color_scheme_color_1'])>0 &&
            isset($options['admin_color_scheme_page']['color_scheme_color_2']) && strlen($options['admin_color_scheme_page']['color_scheme_color_2'])>0 &&
            isset($options['admin_color_scheme_page']['color_scheme_color_3']) && strlen($options['admin_color_scheme_page']['color_scheme_color_3'])>0 &&
            isset($options['admin_color_scheme_page']['color_scheme_color_4']) && strlen($options['admin_color_scheme_page']['color_scheme_color_4'])>0
        ){
            $color_scheme = array(
                $options['admin_color_scheme_page']['color_scheme_color_1'],
                $options['admin_color_scheme_page']['color_scheme_color_2'],
                $options['admin_color_scheme_page']['color_scheme_color_3'],
                $options['admin_color_scheme_page']['color_scheme_color_4']   
            );
        } else {
            $color_scheme = array( '#222', '#333', '#0073aa', '#00a0d2' );
        }    
        
        //add the color scheme
        wp_admin_css_color(
            'custom', 
            'Custom',
            plugins_url( '../../custom-admin-theme.css', __FILE__ ),
            $color_scheme,
            array( 'base' => $svg_icon_color, 'focus' => $svg_icon_color, 'current' => $svg_icon_color )
        );
            
    }
    /**
    * 
    *
    *
    * Function to implement custom color scheme to head
    */
    add_action( 'admin_enqueue_scripts', 'custom_admin_interface_pro_color_scheme_implementation' );
    function custom_admin_interface_pro_color_scheme_implementation(){
        
        wp_enqueue_style( 'caip-custom-admin-theme', plugins_url( '../../custom-admin-theme.css', __FILE__ ) ,array(),custom_admin_code_pro_version());
        
        if(get_option( 'custom_admin_interface_pro_settings' )){

            $options = get_option('custom_admin_interface_pro_settings');
        
            $current_color_scheme = get_user_option( 'admin_color' );
            
            if(
                $current_color_scheme == 'custom' && 
                isset($options['admin_color_scheme_page']['color_scheme_color_1']) && strlen($options['admin_color_scheme_page']['color_scheme_color_1'])>0 &&
                isset($options['admin_color_scheme_page']['color_scheme_color_2']) && strlen($options['admin_color_scheme_page']['color_scheme_color_2'])>0 &&
                isset($options['admin_color_scheme_page']['color_scheme_color_3']) && strlen($options['admin_color_scheme_page']['color_scheme_color_3'])>0 &&
                isset($options['admin_color_scheme_page']['color_scheme_color_4']) && strlen($options['admin_color_scheme_page']['color_scheme_color_4'])>0
                // && isset($options['admin_color_scheme_page']['svg_icon_color']) && strlen($options['admin_color_scheme_page']['svg_icon_color'])>0
            ){
                
                //desclare primary variables
                $custom_color_1 = $options['admin_color_scheme_page']['color_scheme_color_1']." !important"; 
                $custom_color_2 = $options['admin_color_scheme_page']['color_scheme_color_2']." !important";   
                $custom_color_3 = $options['admin_color_scheme_page']['color_scheme_color_3']." !important";
                $custom_color_4 = $options['admin_color_scheme_page']['color_scheme_color_4']." !important";
                $custom_icon_color = $options['admin_color_scheme_page']['svg_icon_color']." !important";   
                
                //output the code
                $css_code = "
                
                #adminmenu li a.wp-has-current-submenu .update-plugins,
                #adminmenu li.current a .awaiting-mod,
                #adminmenu li.menu-top:hover>a .update-plugins,
                #adminmenu li:hover a .awaiting-mod {
                    background: {$custom_color_4};
                }
                #adminmenu .wp-has-current-submenu .wp-submenu,
                #adminmenu .wp-has-current-submenu.opensub .wp-submenu,
                #adminmenu .wp-submenu,
                #adminmenu a.wp-has-current-submenu:focus+.wp-submenu,
                .folded #adminmenu .wp-has-current-submenu .wp-submenu {
                    background: {$custom_color_1};
                }

                #adminmenu li.wp-has-submenu.wp-not-current-submenu.opensub:hover:after {
                    border-right-color: {$custom_color_1};
                }

                #wpadminbar .ab-top-menu>li.menupop.hover>.ab-item,
                #wpadminbar.nojq .quicklinks .ab-top-menu>li>.ab-item:focus,
                #wpadminbar.nojs .ab-top-menu>li.menupop:hover>.ab-item,
                #wpadminbar:not(.mobile) .ab-top-menu>li:hover>.ab-item,
                #wpadminbar:not(.mobile) .ab-top-menu>li>.ab-item:focus {
                    background: {$custom_color_1};
                }

                #wpadminbar .menupop .ab-sub-wrapper {
                    background: {$custom_color_1};
                }

                .wp-responsive-open #wpadminbar #wp-admin-bar-menu-toggle a {
                    background: {$custom_color_1};
                }

                .wp-core-ui .wp-ui-primary {
                    background-color: {$custom_color_2};
                }

                .wp-core-ui .wp-ui-text-primary {
                    color: {$custom_color_2};
                }

                .tablenav .tablenav-pages a:focus,
                .tablenav .tablenav-pages a:hover,
                .wrap .add-new-h2:hover,
                .wrap .page-title-action:hover {
                    background-color: {$custom_color_2};
                }

                .view-switch a.current:before {
                    color: {$custom_color_2};
                }

                #adminmenu,
                #adminmenuback,
                #adminmenuwrap {
                    background: {$custom_color_2};
                }

                #wpadminbar {
                    background: {$custom_color_2};
                }

                .theme-filter.current,
                .theme-section.current {
                    border-bottom-color: {$custom_color_2};
                }

                body.more-filters-opened .more-filters {
                    background-color: {$custom_color_2};
                }

                .wp-core-ui .wp-ui-highlight {
                    background-color: {$custom_color_3};
                }

                .wp-core-ui .wp-ui-text-highlight {
                    color: {$custom_color_3};
                }

                #adminmenu a:hover,
                #adminmenu li.menu-top:hover,
                #adminmenu li.opensub>a.menu-top,
                #adminmenu li>a.menu-top:focus {
                    background-color: {$custom_color_3};
                }

                #adminmenu .wp-has-current-submenu .wp-submenu a:focus,
                #adminmenu .wp-has-current-submenu .wp-submenu a:hover,
                #adminmenu .wp-has-current-submenu.opensub .wp-submenu a:focus,
                #adminmenu .wp-has-current-submenu.opensub .wp-submenu a:hover,
                #adminmenu .wp-submenu a:focus,
                #adminmenu .wp-submenu a:hover,
                #adminmenu a.wp-has-current-submenu:focus+.wp-submenu a:focus,
                #adminmenu a.wp-has-current-submenu:focus+.wp-submenu a:hover,
                .folded #adminmenu .wp-has-current-submenu .wp-submenu a:focus,
                .folded #adminmenu .wp-has-current-submenu .wp-submenu a:hover {
                    color: white;
                }

                #adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a:focus,
                #adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a:hover,
                #adminmenu .wp-submenu li.current a:focus,
                #adminmenu .wp-submenu li.current a:hover,
                #adminmenu a.wp-has-current-submenu:focus+.wp-submenu li.current a:focus,
                #adminmenu a.wp-has-current-submenu:focus+.wp-submenu li.current a:hover {
                    color: white;
                }

                #adminmenu li.current a.menu-top,
                #adminmenu li.wp-has-current-submenu .wp-submenu .wp-submenu-head,
                #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
                .folded #adminmenu li.current.menu-top {
                    background: {$custom_color_3};
                }

                #collapse-button:focus,
                #collapse-button:hover {
                    color: {$custom_color_3};
                }

                #wpadminbar .ab-top-menu>li.menupop.hover>.ab-item,
                #wpadminbar.nojq .quicklinks .ab-top-menu>li>.ab-item:focus,
                #wpadminbar.nojs .ab-top-menu>li.menupop:hover>.ab-item,
                #wpadminbar:not(.mobile) .ab-top-menu>li:hover>.ab-item,
                #wpadminbar:not(.mobile) .ab-top-menu>li>.ab-item:focus {
                    color: {$custom_color_3};
                }

                #wpadminbar:not(.mobile)>#wp-toolbar a:focus span.ab-label,
                #wpadminbar:not(.mobile)>#wp-toolbar li.hover span.ab-label,
                #wpadminbar:not(.mobile)>#wp-toolbar li:hover span.ab-label {
                    color: {$custom_color_3};
                }

                #wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover>a,
                #wpadminbar .quicklinks .menupop ul li a:focus,
                #wpadminbar .quicklinks .menupop ul li a:focus strong,
                #wpadminbar .quicklinks .menupop ul li a:hover,
                #wpadminbar .quicklinks .menupop ul li a:hover strong,
                #wpadminbar .quicklinks .menupop.hover ul li a:focus,
                #wpadminbar .quicklinks .menupop.hover ul li a:hover,
                #wpadminbar li #adminbarsearch.adminbar-focused:before,
                #wpadminbar li .ab-item:focus .ab-icon:before,
                #wpadminbar li .ab-item:focus:before,
                #wpadminbar li a:focus .ab-icon:before,
                #wpadminbar li.hover .ab-icon:before,
                #wpadminbar li.hover .ab-item:before,
                #wpadminbar li:hover #adminbarsearch:before,
                #wpadminbar li:hover .ab-icon:before,
                #wpadminbar li:hover .ab-item:before,
                #wpadminbar.nojs .quicklinks .menupop:hover ul li a:focus,
                #wpadminbar.nojs .quicklinks .menupop:hover ul li a:hover {
                    color: {$custom_color_3};
                }

                #wpadminbar .menupop .menupop>.ab-item:hover:before,
                #wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover>a .blavatar,
                #wpadminbar .quicklinks li a:focus .blavatar,
                #wpadminbar .quicklinks li a:hover .blavatar,
                #wpadminbar.mobile .quicklinks .ab-icon:before,
                #wpadminbar.mobile .quicklinks .ab-item:before {
                    color: {$custom_color_3};
                }    

                #wpadminbar #wp-admin-bar-user-info a:hover .display-name {
                    color: {$custom_color_3};
                }

                .wp-pointer .wp-pointer-content h3 {
                    background-color: {$custom_color_3};
                }

                .wp-pointer .wp-pointer-content h3:before {
                    color: {$custom_color_3};
                }

                .wp-pointer.wp-pointer-top .wp-pointer-arrow,
                .wp-pointer.wp-pointer-top .wp-pointer-arrow-inner,
                .wp-pointer.wp-pointer-undefined .wp-pointer-arrow,
                .wp-pointer.wp-pointer-undefined .wp-pointer-arrow-inner {
                    border-bottom-color: {$custom_color_3};
                }

                .media-item .bar,
                .media-progress-bar div {
                    background-color: {$custom_color_3};
                }

                .details.attachment {
                    -webkit-box-shadow: inset 0 0 0 3px #fff, inset 0 0 0 7px {$custom_color_3};
                    box-shadow: inset 0 0 0 3px #fff, inset 0 0 0 7px {$custom_color_3};
                }

                .attachment.details .check {
                    background-color: {$custom_color_3};
                    -webkit-box-shadow: 0 0 0 1px #fff, 0 0 0 2px {$custom_color_3};
                    box-shadow: 0 0 0 1px #fff, 0 0 0 2px {$custom_color_3};
                }

                .media-selection .attachment.selection.details .thumbnail {
                    -webkit-box-shadow: 0 0 0 1px #fff, 0 0 0 3px {$custom_color_3};
                    box-shadow: 0 0 0 1px #fff, 0 0 0 3px {$custom_color_3};
                }

                .theme-browser .theme.active .theme-name,
                .theme-browser .theme.add-new-theme a:focus:after,
                .theme-browser .theme.add-new-theme a:hover:after {
                    background: {$custom_color_3};
                }

                .theme-browser .theme.add-new-theme a:focus span:after,
                .theme-browser .theme.add-new-theme a:hover span:after {
                    color: {$custom_color_3};
                }

                body.more-filters-opened .more-filters:focus,
                body.more-filters-opened .more-filters:hover {
                    background-color: {$custom_color_3};
                }

                .widgets-chooser li.widgets-chooser-selected {
                    background-color: {$custom_color_3};
                }

                .wp-responsive-open div#wp-responsive-toggle a {
                    background: {$custom_color_3};
                }

                .mce-container.mce-menu .mce-menu-item-normal.mce-active,
                .mce-container.mce-menu .mce-menu-item-preview.mce-active,
                .mce-container.mce-menu .mce-menu-item.mce-selected,
                .mce-container.mce-menu .mce-menu-item:focus,
                .mce-container.mce-menu .mce-menu-item:hover {
                    background: {$custom_color_3};
                }

                .wp-core-ui .wp-ui-notification {
                    background-color: {$custom_color_4};
                }

                .wp-core-ui .wp-ui-text-notification {
                    color: {$custom_color_4};
                }    

                .view-switch a:hover:before {
                    color: {$custom_color_4};
                }

                #adminmenu .awaiting-mod,
                #adminmenu .update-plugins {
                    background: {$custom_color_4};
                }

                #adminmenu a:hover, #adminmenu li.menu-top:hover, #adminmenu li.opensub>a.menu-top, #adminmenu li>a.menu-top:focus {
                    color: #fff;
                }

                .wp-menu-image, .wp-menu-image:before {
                    color: {$custom_icon_color};     
                }
                
                .wp-admin #wpadminbar #wp-admin-bar-site-name>.ab-item:before {
                color: {$custom_icon_color};
                }

                ";
            
                wp_add_inline_style( 'caip-custom-admin-theme', $css_code );     
                
            }
        
        }
    }
    /**
    * 
    *
    *
    * Function to force color scheme if desired
    */
    add_filter( 'get_user_option_admin_color', 'custom_admin_interface_pro_force_color_scheme', 5 );
    function custom_admin_interface_pro_force_color_scheme($color_scheme){

        if(get_option( 'custom_admin_interface_pro_settings' )){

            $options = get_option('custom_admin_interface_pro_settings');

            if(isset($options['admin_color_scheme_page']['force_on_all_users']) && $options['admin_color_scheme_page']['force_on_all_users'] == 'checked'){
                $color_scheme = 'custom';    
                return $color_scheme;
            } else {
                return $color_scheme;     
            }

        }
    }
    /**
    * 
    *
    *
    * Function to add button and text color
    */
    add_action('admin_head', 'custom_admin_interface_pro_button_color');
    function custom_admin_interface_pro_button_color() {
        

        if(get_option( 'custom_admin_interface_pro_settings' )){
            $options = get_option('custom_admin_interface_pro_settings');

            if(
                isset($options['admin_color_scheme_page']['admin_link_and_button_color']) && 
                strlen($options['admin_color_scheme_page']['admin_link_and_button_color'])>0 &&
                $options['admin_color_scheme_page']['admin_link_and_button_color'] !== "#0085ba"
            ){
                wp_enqueue_style( 'custom-admin-code-css', plugins_url( '../../../inc/custom-admin-style.css', __FILE__ ),array(),custom_admin_code_pro_version() );    
            
                $button_link_color = wp_strip_all_tags($options['admin_color_scheme_page']['admin_link_and_button_color']);
                
                $custom_code = "
                    a {
                    color: {$button_link_color};
                    }
                    
                    .wp-core-ui .button-primary {
                        background: {$button_link_color};
                        background-image: -webkit-gradient(linear,left top,left bottom,from({$button_link_color}),to({$button_link_color}));
                        background-image: -webkit-linear-gradient(top,{$button_link_color},{$button_link_color});
                        background-image: -moz-linear-gradient(top,{$button_link_color},{$button_link_color});
                        background-image: -ms-linear-gradient(top,{$button_link_color},{$button_link_color});
                        background-image: -o-linear-gradient(top,{$button_link_color},{$button_link_color});
                        background-image: linear-gradient(to bottom,{$button_link_color},{$button_link_color});
                        border-color: {$button_link_color};
                        text-shadow: none !important;
                        box-shadow: none !important;
                    }
                ";
                wp_add_inline_style( 'custom-admin-code-css', $custom_code );

            }


        }


     
    
        if(
            isset($options['admin_color_scheme_page']['admin_link_and_button_color_hover']) && 
            strlen($options['admin_color_scheme_page']['admin_link_and_button_color_hover'])>0 &&
            $options['admin_color_scheme_page']['admin_link_and_button_color_hover'] !== "#008ec2"
        ){
            
            wp_enqueue_style( 'custom-admin-code-css', plugins_url( '../../../inc/custom-admin-style.css', __FILE__ ),array(),custom_admin_code_pro_version() );  
            
            $button_link_hover_color = wp_strip_all_tags($options['admin_color_scheme_page']['admin_link_and_button_color_hover']);
            
            $custom_code = "
            a:hover, a:active {
            color: {$button_link_hover_color};
            }
            
            .wp-core-ui .button-primary.active, .wp-core-ui .button-primary:hover, .wp-core-ui .button-primary:active {
                background: {$button_link_hover_color};
                background-image: -webkit-gradient(linear,left top,left bottom,from({$button_link_hover_color}),to({$button_link_hover_color}));
                background-image: -webkit-linear-gradient(top,{$button_link_hover_color},{$button_link_hover_color});
                background-image: -moz-linear-gradient(top,{$button_link_hover_color},{$button_link_hover_color});
                background-image: -ms-linear-gradient(top,{$button_link_hover_color},{$button_link_hover_color});
                background-image: -o-linear-gradient(top,{$button_link_hover_color},{$button_link_hover_color});
                background-image: linear-gradient(to bottom,{$button_link_hover_color},{$button_link_hover_color});
                border-color: {$button_link_hover_color};
                text-shadow: none !important;
                box-shadow: none !important;
            }
            ";
            wp_add_inline_style( 'custom-admin-code-css', $custom_code );
            
        }
        
    }
   

?>