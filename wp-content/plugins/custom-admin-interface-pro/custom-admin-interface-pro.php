<?php

/*
*		Plugin Name: Custom Admin Interface Pro
*		Plugin URI: https://www.northernbeacheswebsites.com.au
*		Description: The ultimate toolkit for modifying the admin interface of WordPress  
*		Version: 1.57
*		Author: Martin Gibson
*		Developer: Northern Beaches Websites
*		Developer URI:  https://www.northernbeacheswebsites.com.au
*		Text Domain: custom-admin-interface-pro
*       Copyright: ©2019 Northern Beaches Websites.
*		Support: https://www.northernbeacheswebsites.com.au/contact
*/
	//raz0r
	$cai_settings = get_option('custom_admin_interface_pro_settings');
	$cai_settings['general_settings']['purchase_email'] = 'mail@mail.com';
	$cai_settings['general_settings']['order_id'] = '989';
	update_option('custom_admin_interface_pro_settings', $cai_settings);
	set_transient('custom-admin-interface-pro-update', false);
    /**
    * 
    *
    *
    * Gets version number of plugin
    */
    function custom_admin_code_pro_version() {
        if ( ! function_exists( 'get_plugins' ) )
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
        $plugin_file = basename( ( __FILE__ ) );
        return $plugin_folder[$plugin_file]['Version'];
    }
    /**
    * 
    *
    *
    * Create globals
    */
    global $custom_admin_interface_pro_post_types;
    $custom_admin_interface_pro_post_types = array(
        array(
            'single' => __( 'Admin Menu' , 'custom-admin-interface-pro' ),
            'plural' => __( 'Admin Menus' , 'custom-admin-interface-pro' ),
        ),
        array(
            'single' => __( 'Admin Notice' , 'custom-admin-interface-pro' ),
            'plural' => __( 'Admin Notices' , 'custom-admin-interface-pro' ),
        ),
        array(
            'single' => __( 'Admin Toolbar' , 'custom-admin-interface-pro' ),
            'plural' => __( 'Admin Toolbars' , 'custom-admin-interface-pro' ),
        ),
        array(
            'single' => __( 'Custom Admin Code' , 'custom-admin-interface-pro' ),
            'plural' => __( 'Custom Admin Codes' , 'custom-admin-interface-pro' ),
        ),
        array(
            'single' => __( 'Custom Frontend Code' , 'custom-admin-interface-pro' ),
            'plural' => __( 'Custom Frontend Codes' , 'custom-admin-interface-pro' ),
        ),
        array(
            'single' => __( 'Dashboard Widget' , 'custom-admin-interface-pro' ),
            'plural' => __( 'Dashboard Widgets' , 'custom-admin-interface-pro' ),
        ),
        array(
            'single' => __( 'Hide Metabox' , 'custom-admin-interface-pro' ),
            'plural' => __( 'Hide Metaboxes' , 'custom-admin-interface-pro' ),
        ),
        array(
            'single' => __( 'Hide Plugin' , 'custom-admin-interface-pro' ),
            'plural' => __( 'Hide Plugins' , 'custom-admin-interface-pro' ),
        ),
        array(
            'single' => __( 'Hide Sidebar' , 'custom-admin-interface-pro' ),
            'plural' => __( 'Hide Sidebars' , 'custom-admin-interface-pro' ),
        ),
        array(
            'single' => __( 'Hide User' , 'custom-admin-interface-pro' ),
            'plural' => __( 'Hide Users' , 'custom-admin-interface-pro' ),
        ),   
    );

    /**
    * 
    *
    *
    * Include general files
    */
    require('inc/general-functions.php');
    /**
    * 
    *
    *
    * Registers custom post types
    */
    function custom_admin_interface_pro_register_custom_post_types() {

        global $custom_admin_interface_pro_post_types;

        //loop through the post types array
        foreach($custom_admin_interface_pro_post_types as $post_type){

            //get key variables
            $single_name = $post_type['single'];
            $plural_name = $post_type['plural'];
            $slug = custom_admin_interface_pro_functionify_name($single_name);

            //create argument array for custom post type
            $labels = array(
                'name'               => _x( $plural_name, 'post type general name', 'custom-admin-interface-pro' ),
                'singular_name'      => _x( $single_name, 'post type singular name', 'custom-admin-interface-pro' ),
                'menu_name'          => _x( $plural_name, 'admin menu', 'custom-admin-interface-pro' ),
                'name_admin_bar'     => _x( $single_name, 'add new on admin bar', 'custom-admin-interface-pro' ),
                'add_new'            => _x( 'Add New', $single_name, 'custom-admin-interface-pro' ),
                'add_new_item'       => __( 'Add New '.$single_name, 'custom-admin-interface-pro' ),
                'new_item'           => __( 'New '.$single_name, 'custom-admin-interface-pro' ),
                'edit_item'          => __( 'Edit '.$single_name, 'custom-admin-interface-pro' ),
                'view_item'          => __( 'View '.$single_name, 'custom-admin-interface-pro' ),
                'all_items'          => __( 'All '.$plural_name, 'custom-admin-interface-pro' ),
                'search_items'       => __( 'Search '.$plural_name, 'custom-admin-interface-pro' ),
                'parent_item_colon'  => __( 'Parent '.$plural_name.':', 'custom-admin-interface-pro' ),
                'not_found'          => __( 'No '.$plural_name.' found.', 'custom-admin-interface-pro' ),
                'not_found_in_trash' => __( 'No '.$plural_name.' found in Trash.', 'custom-admin-interface-pro' )
            );
    
            $suppprts = array('title','author');
            $post_types_with_additional_supports = array('admin_toolbar','admin_menu','admin_notice','custom_admin_code','dashboard_widget','custom_frontend_code');
    
    
            //dont save revision data if disabled in the plugin settings
            $enable_post_revision = true;
            if(get_option( 'custom_admin_interface_pro_settings' )){
                $options = get_option( 'custom_admin_interface_pro_settings' );
                if(isset($options['manage_settings']['disable_post_revisions']) && $options['manage_settings']['disable_post_revisions'] == 'checked' ){
                    $enable_post_revision = false;
                }
            }
    
            //if the item is the admin toolbar or admin menu add revisions so people can restore the data
            if( in_array($slug,$post_types_with_additional_supports) && $enable_post_revision ){
                array_push($suppprts,'revisions','editor'); //we are going to add the editor to force revisions
            }
            
            $args = array(
                'labels'             => $labels,
                'public'             => true,
                'exclude_from_search'=> true,
                'publicly_queryable' => false,
                'show_ui'            => true,
                'show_in_menu'       => false, 
                'query_var'          => false,
                'rewrite'            => array( 'slug' => $slug ),
                'capability_type'    => 'post',
                'has_archive'        => true,
                'hierarchical'       => false,
                'menu_position'      => null,
                'supports'           => $suppprts
            );

            //register the post type
            register_post_type( $slug, $args );

        }
        
    }
    add_action( 'init', 'custom_admin_interface_pro_register_custom_post_types' );
    /**
    * 
    *
    *
    * Register custom post types on plugin activation
    */
    register_activation_hook( __FILE__, 'custom_admin_interface_pro_activation' );
    function custom_admin_interface_pro_activation() {
        // trigger our function that registers the custom post types
        custom_admin_interface_pro_register_custom_post_types();
     
        // clear the permalinks after the post type has been registered
        flush_rewrite_rules();
    }
    /**
    * 
    *
    *
    * De-register custom post types on plugin deactivation
    */
    function custom_admin_interface_pro_deactivation() {

        // unregister the post types, so the rules are no longer in memory
        global $custom_admin_interface_pro_post_types;

        //loop through the post types array
        foreach($custom_admin_interface_pro_post_types as $post_type){

            //get key variables
            $single_name = $post_type['single'];
            $slug = custom_admin_interface_pro_functionify_name($single_name);

            unregister_post_type( $slug );

        }

        // clear the permalinks to remove our post type's rules from the database
        flush_rewrite_rules();
    }
    register_deactivation_hook( __FILE__, 'custom_admin_interface_pro_deactivation' );
    /**
     * 
     *
     *
     * Add settings link to plugin on plugins page
     */
    function custom_admin_code_pro_settings_link( $links ) {
        $settings_link = '<a href="admin.php?page=caip_general">' . __( 'Settings','custom-admin-interface-pro' ) . '</a>';
        
        array_unshift( $links, $settings_link );
        return $links;
    }
    $plugin = plugin_basename( __FILE__ );
    add_filter( "plugin_action_links_$plugin", 'custom_admin_code_pro_settings_link' );
    /**
    * 
    *
    *
    * Creates the menu
    */
    add_action( 'admin_menu', 'custom_admin_interface_pro_add_admin_menu' );

    function custom_admin_interface_pro_add_admin_menu(){

        $custom_admin_interface_pro_menu_icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAyMS4xLjAsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiDQoJIHZpZXdCb3g9IjAgMCAyMCAyMCIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgMjAgMjA7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+DQoJLnN0MHtmaWxsOiNGRkZGRkY7fQ0KCS5zdDF7ZmlsbDojQTBBNUFBO30NCjwvc3R5bGU+DQo8cGF0aCBjbGFzcz0ic3QwIiBkPSJNLTM2OC4xLTE4LjljMC04MS43LDAtMTYzLjMsMC0yNDVjODcuNSwwLDE3NSwwLDI2Mi41LDBjMCw4MS43LDAsMTYzLjMsMCwyNDUNCglDLTE5My4xLTE4LjktMjgwLjYtMTguOS0zNjguMS0xOC45eiBNLTI5MC43LTE5MS45YzYuMiwwLDEyLjUsMCwxOC43LDBjNy4yLDAsMTEuMy00LjEsMTEuMy0xMS4yYzAtMTIuNSwwLTI1LDAtMzcuNQ0KCWMwLTcuMS00LjEtMTEuMi0xMS4xLTExLjJjLTEyLjYsMC0yNS4yLDAtMzcuNywwYy02LjksMC0xMS4xLDQuMS0xMS4xLDExYzAsMTIuNywwLDI1LjMsMCwzOGMwLDYuNyw0LjIsMTAuOSwxMC45LDExDQoJQy0zMDMuNC0xOTEuOS0yOTctMTkxLjktMjkwLjctMTkxLjl6IE0tMTkwLjYtMTcxLjljLTYuMywwLTEyLjcsMC0xOSwwYy02LjksMC0xMSw0LjItMTEsMTEuMWMwLDEyLjYsMCwyNS4yLDAsMzcuNw0KCWMwLDYuOSw0LjEsMTEuMSwxMSwxMS4xYzEyLjcsMCwyNS4zLDAsMzgsMGM2LjgsMCwxMS00LjIsMTEtMTEuMWMwLTEyLjYsMC0yNS4yLDAtMzcuN2MwLTctNC4xLTExLjEtMTEuMi0xMS4xDQoJQy0xNzguMS0xNzEuOS0xODQuMy0xNzEuOS0xOTAuNi0xNzEuOXogTS0yNzAuNi05MS44Yy02LjIsMC0xMi41LDAtMTguNywwYy03LjEsMC0xMS4yLDQuMS0xMS4yLDExLjFjMCwxMi42LDAsMjUuMiwwLDM3LjcNCgljMCw2LjksNC4xLDExLjEsMTEsMTEuMWMxMi43LDAsMjUuMywwLDM4LDBjNi44LDAsMTAuOS00LjIsMTEtMTAuOWMwLTEyLjcsMC0yNS41LDAtMzguMmMwLTYuNi00LjItMTAuOC0xMC44LTEwLjgNCglDLTI1Ny44LTkxLjktMjY0LjItOTEuOC0yNzAuNi05MS44eiBNLTI1NS41LTIxMi4xYzQ1LjEsMCw4OS45LDAsMTM0LjcsMGMwLTYuNiwwLTEzLjEsMC0xOS42Yy00NSwwLTg5LjgsMC0xMzQuNywwDQoJQy0yNTUuNS0yMjUuMS0yNTUuNS0yMTguNy0yNTUuNS0yMTIuMXogTS0zNjAuNC0xNTEuOGMwLDYuOCwwLDEzLjMsMCwxOS43YzQ1LDAsODkuOCwwLDEzNC42LDBjMC02LjcsMC0xMy4yLDAtMTkuNw0KCUMtMjcwLjctMTUxLjgtMzE1LjUtMTUxLjgtMzYwLjQtMTUxLjh6IE0tMjM1LjUtNTIuMWMzOC40LDAsNzYuNiwwLDExNC43LDBjMC02LjcsMC0xMy4xLDAtMTkuNmMtMzguMywwLTc2LjUsMC0xMTQuNywwDQoJQy0yMzUuNS02NS4xLTIzNS41LTU4LjctMjM1LjUtNTIuMXogTS0zMDUuNy03MS43Yy0xOC40LDAtMzYuNiwwLTU0LjcsMGMwLDYuNywwLDEzLjEsMCwxOS42YzE4LjMsMCwzNi41LDAsNTQuNywwDQoJQy0zMDUuNy01OC43LTMwNS43LTY1LjEtMzA1LjctNzEuN3ogTS0zMjUuOC0yMTJjMC02LjcsMC0xMy4zLDAtMTkuN2MtMTEuNywwLTIzLjEsMC0zNC42LDBjMCw2LjcsMCwxMy4yLDAsMTkuNw0KCUMtMzQ4LjgtMjEyLTMzNy40LTIxMi0zMjUuOC0yMTJ6IE0tMTU1LjQtMTUxLjdjMCw2LjcsMCwxMy4xLDAsMTkuN2MxMS42LDAsMjMuMiwwLDM0LjcsMGMwLTYuNiwwLTEzLjEsMC0xOS43DQoJQy0xMzIuMy0xNTEuNy0xNDMuOC0xNTEuNy0xNTUuNC0xNTEuN3oiLz4NCjxnPg0KCTxwYXRoIGNsYXNzPSJzdDEiIGQ9Ik02LDYuMWMtMC41LDAtMSwwLTEuNSwwQzQsNi4xLDMuNiw1LjgsMy42LDUuMmMwLTEsMC0yLDAtM2MwLTAuNSwwLjMtMC45LDAuOS0wLjljMSwwLDIsMCwzLDANCgkJYzAuNiwwLDAuOSwwLjMsMC45LDAuOWMwLDEsMCwyLDAsM2MwLDAuNi0wLjMsMC45LTAuOSwwLjlDNyw2LjEsNi41LDYuMSw2LDYuMXoiLz4NCgk8cGF0aCBjbGFzcz0ic3QxIiBkPSJNMTQsNy43YzAuNSwwLDEsMCwxLjUsMGMwLjYsMCwwLjksMC4zLDAuOSwwLjljMCwxLDAsMiwwLDNjMCwwLjYtMC4zLDAuOS0wLjksMC45Yy0xLDAtMiwwLTMsMA0KCQljLTAuNSwwLTAuOS0wLjMtMC45LTAuOWMwLTEsMC0yLDAtM2MwLTAuNiwwLjMtMC45LDAuOS0wLjlDMTMsNy43LDEzLjUsNy43LDE0LDcuN3oiLz4NCgk8cGF0aCBjbGFzcz0ic3QxIiBkPSJNNy42LDE0LjFjMC41LDAsMSwwLDEuNSwwYzAuNSwwLDAuOSwwLjMsMC45LDAuOWMwLDEsMCwyLDAsMy4xYzAsMC41LTAuMywwLjktMC45LDAuOWMtMSwwLTIsMC0zLDANCgkJYy0wLjUsMC0wLjktMC4zLTAuOS0wLjljMC0xLDAtMiwwLTNjMC0wLjYsMC4zLTAuOSwwLjktMC45QzYuNiwxNC4xLDcuMSwxNC4xLDcuNiwxNC4xeiIvPg0KCTxwYXRoIGNsYXNzPSJzdDEiIGQ9Ik04LjgsNC41YzAtMC41LDAtMSwwLTEuNmMzLjYsMCw3LjIsMCwxMC44LDBjMCwwLjUsMCwxLDAsMS42QzE2LDQuNSwxMi40LDQuNSw4LjgsNC41eiIvPg0KCTxwYXRoIGNsYXNzPSJzdDEiIGQ9Ik0wLjQsOS4zYzMuNiwwLDcuMiwwLDEwLjcsMGMwLDAuNSwwLDEsMCwxLjZjLTMuNiwwLTcuMSwwLTEwLjcsMEMwLjQsMTAuNCwwLjQsOS44LDAuNCw5LjN6Ii8+DQoJPHBhdGggY2xhc3M9InN0MSIgZD0iTTEwLjQsMTcuM2MwLTAuNSwwLTEsMC0xLjZjMy4xLDAsNi4xLDAsOS4yLDBjMCwwLjUsMCwxLDAsMS42QzE2LjUsMTcuMywxMy41LDE3LjMsMTAuNCwxNy4zeiIvPg0KCTxwYXRoIGNsYXNzPSJzdDEiIGQ9Ik00LjgsMTUuN2MwLDAuNSwwLDEsMCwxLjZjLTEuNSwwLTIuOSwwLTQuNCwwYzAtMC41LDAtMSwwLTEuNkMxLjksMTUuNywzLjMsMTUuNyw0LjgsMTUuN3oiLz4NCgk8cGF0aCBjbGFzcz0ic3QxIiBkPSJNMy4yLDQuNWMtMC45LDAtMS44LDAtMi44LDBjMC0wLjUsMC0xLDAtMS42YzAuOSwwLDEuOCwwLDIuOCwwQzMuMiwzLjQsMy4yLDQsMy4yLDQuNXoiLz4NCgk8cGF0aCBjbGFzcz0ic3QxIiBkPSJNMTYuOCw5LjNjMC45LDAsMS44LDAsMi44LDBjMCwwLjUsMCwxLDAsMS42Yy0wLjksMC0xLjgsMC0yLjgsMEMxNi44LDEwLjQsMTYuOCw5LjgsMTYuOCw5LjN6Ii8+DQo8L2c+DQo8L3N2Zz4NCg==';

        //register main menu
        $custom_admin_interface_pro_settings_page = add_menu_page( 'Custom Admin Interface Pro', 'Custom Admin Interface Pro', custom_admin_interface_pro_permission(), 'custom_admin_interface_pro','',$custom_admin_interface_pro_menu_icon);

        //register sub menus
        add_submenu_page( 'custom_admin_interface_pro', 'General', 'General', custom_admin_interface_pro_permission(), 'caip_general', 'custom_admin_interface_pro_general_settings' );
        add_submenu_page( 'custom_admin_interface_pro', 'Admin Color Scheme', 'Admin Color Scheme', custom_admin_interface_pro_permission(), 'caip_admin_color_scheme', 'custom_admin_interface_pro_admin_color_scheme_settings' );
        add_submenu_page( 'custom_admin_interface_pro', 'Login Page', 'Login Page', custom_admin_interface_pro_permission(), 'caip_login', 'custom_admin_interface_pro_login_page_settings' );
        add_submenu_page( 'custom_admin_interface_pro', 'Maintenance Page', 'Maintenance Page', custom_admin_interface_pro_permission(), 'caip_maintenance_page', 'custom_admin_interface_pro_maintenance_page_settings' );

        global $custom_admin_interface_pro_post_types;

        //loop through the post types array
        foreach($custom_admin_interface_pro_post_types as $post_type){

            //get key variables
            $post_type_single_name = $post_type['single'];
            $post_type_plural_plural = $post_type['plural'];
            $post_type_name_slug = custom_admin_interface_pro_functionify_name($post_type_single_name);

            add_submenu_page( 'custom_admin_interface_pro', $post_type_plural_plural, $post_type_plural_plural, custom_admin_interface_pro_permission(), 'edit.php?post_type='.$post_type_name_slug, NULL );

        }
        remove_submenu_page('custom_admin_interface_pro','custom_admin_interface_pro');

        //do manage settings and help
        add_submenu_page( 'custom_admin_interface_pro', 'Manage Settings', 'Manage Settings', custom_admin_interface_pro_permission(), 'caip_manage_settings', 'custom_admin_interface_pro_manage_settings' );
        add_submenu_page( 'custom_admin_interface_pro', 'Support', 'Support', custom_admin_interface_pro_permission(), 'caip_support', 'custom_admin_interface_pro_support' );
        add_submenu_page( 'custom_admin_interface_pro', 'Feedback', 'Feedback', custom_admin_interface_pro_permission(), 'caip_feedback', 'custom_admin_interface_pro_feedback' );


    }
    require('inc/register-styles-scripts.php');
    require('inc/register-metaboxes.php');
    // require('inc/register-columns.php');

    /**
    * 
    *
    *
    * Loop through custom post types and add the column to each type and the content
    */
    add_action('admin_init','custom_admin_interface_pro_add_columns_to_post_types');

    function custom_admin_interface_pro_add_columns_to_post_types(){

        global $custom_admin_interface_pro_post_types;

        foreach($custom_admin_interface_pro_post_types as $post_type){

            //get key variables
            $post_type_single_name = $post_type['single'];
            $post_type_name_slug = custom_admin_interface_pro_functionify_name($post_type_single_name);

            add_filter( 'manage_edit-'.$post_type_name_slug.'_columns', 'custom_admin_interface_pro_add_columns' ) ;
            add_action( 'manage_'.$post_type_name_slug.'_posts_custom_column' , 'custom_admin_interface_pro_add_column_content', 10, 2 );

        } 
    }

    /**
    * 
    *
    *
    * Add conditions column to custom post types in plugin
    */
    function custom_admin_interface_pro_add_columns( $columns ) {

        $new_columns = array();
        
        $columns['conditions'] = __( 'Conditions','custom-admin-interface-pro'); 
    
        $custom_order = array('cb','title', 'author', 'conditions','date');

        foreach ($custom_order as $column_name){
            $new_columns[$column_name] = $columns[$column_name];  
        }
          
        return $new_columns;

    }


    /**
    * 
    *
    *
    * Output the column content
    */
    function custom_admin_interface_pro_add_column_content( $column, $post_id ) {

        if($column == 'conditions'){

            //get variables
            $exception_type = get_post_meta($post_id, 'exception_type', true);
            $exceptions = get_post_meta($post_id, 'exceptions', true);

            if($exception_type == 'everyone'){
                $exception_type_nice = __('Everyone','custom-admin-interface-pro');
            } else {
                $exception_type_nice = __('No-one','custom-admin-interface-pro');
            }



            //start output
            $html = '';

            $html .= __('Implemented for','custom-admin-interface-pro').' <strong>'.$exception_type_nice.'</strong>';

            if(strlen($exceptions) > 0){
                $html .= ' '.__('except:','custom-admin-interface-pro').'<br>';   

                $exceptions_nice = array();

                $exceptions_exploded = explode(',',$exceptions);

                global $wp_roles;
                $all_roles = $wp_roles->roles;

                // var_dump($all_roles);

                foreach($exceptions_exploded as $exception){
                    if(is_numeric($exception)) {
                        //it's a user
                        $user = get_user_by('ID', $exception);
                        $user_first_name = $user->first_name;
                        $user_last_name = $user->last_name;
                        $user_roles = $user->roles; 

                        if(is_array($user_roles)){
                            $user_roles = array_values($user_roles);
                        } else {
                            $user_roles = array();
                        }

                        

                        // var_dump($user_roles);

                        $user_roles_nice = array();

                        if(count($user_roles)){
                            foreach($user_roles as $user_role){
                                array_push($user_roles_nice, $all_roles[$user_role]['name']);
                            }
                        }

                        $label = '<strong>'.$user_first_name.' '.$user_last_name.'</strong>';

                        if(count($user_roles_nice)){
                            $label .= ' <em>('.implode(', ',$user_roles_nice).')</em>';   
                        }

                        array_push($exceptions_nice,$label);

                    } else {
                        //its a role
                        $label = '<strong>'.$all_roles[$exception]['name'].'</strong> <em>('.__('Role','custom-admin-interface-pro').')</em>';

                        array_push($exceptions_nice,$label);
                    }
                }

                $html .= '<span style="opacity: 0.5;">'.implode(', ',$exceptions_nice).'</span>';


            }

            echo $html;
        }

    }

    //include custom post type files
    foreach($custom_admin_interface_pro_post_types as $post_type){
        
        $post_type_single_name = $post_type['single'];
        $post_type_name_slug = custom_admin_interface_pro_slugify_name($post_type_single_name);
        //require files
        require('inc/modules/'.$post_type_name_slug.'/metabox.php');
        require('inc/modules/'.$post_type_name_slug.'/implementation.php');

    }

    require('inc/modules/common/metabox.php');

    //include settings
    require('inc/modules/general-settings/settings.php');
    require('inc/modules/admin-color/settings.php');
    require('inc/modules/login-page/settings.php');
    require('inc/modules/maintenance-page/settings.php');
    require('inc/modules/manage-settings/settings.php');
    require('inc/modules/support/settings.php');
    require('inc/modules/feedback/settings.php');

    //include implementation
    require('inc/modules/general-settings/implementation.php');
    require('inc/modules/admin-color/implementation.php');
    require('inc/modules/login-page/implementation.php');
    require('inc/modules/maintenance-page/implementation.php');
    require('inc/modules/manage-settings/implementation.php');
    /**
    * 
    *
    *
    * Initialise the update check
    */
    require 'inc/library/plugin-update-checker/plugin-update-checker.php';

    global $plugin_update_checker_custom_admin_interface_pro;
    $plugin_update_checker_custom_admin_interface_pro = Puc_v4_Factory::buildUpdateChecker(
        'https://northernbeacheswebsites.com.au/?update_action=get_metadata&update_slug=custom-admin-interface-pro', //Metadata URL.
        __FILE__, //Full path to the main plugin file.
        'custom-admin-interface-pro' //Plugin slug. Usually it's the same as the name of the directory.
    );
    /**
    * 
    *
    *
    * Add queries to the update call
    */
    $plugin_update_checker_custom_admin_interface_pro->addQueryArgFilter('filter_update_checks_custom_admin_interface_pro');
    function filter_update_checks_custom_admin_interface_pro($queryArgs) {
        
        //get existing option
        $option_name = 'custom_admin_interface_pro_settings';
        $section = 'general_settings';

        if(get_option($option_name)){
            $option = get_option($option_name);

            $siteUrl = get_site_url();
            $siteUrl = parse_url($siteUrl);
            $siteUrl = $siteUrl['host'];

            //check if array key exists
            if(array_key_exists($section,$option)){
                if(strlen($option[$section]['purchase_email'])>0 && strlen($option[$section]['order_id'])>0){
                    $queryArgs['purchaseEmailAddress'] = $option[$section]['purchase_email'];
                    $queryArgs['orderId'] = $option[$section]['order_id'];
                    $queryArgs['siteUrl'] = $siteUrl;
                    $queryArgs['productId'] = '12244';   
                }
            }
        } 

        return $queryArgs;
    }
    /**
    * 
    *
    *
    * Set the transient to check for updates
    */
    $plugin_update_checker_custom_admin_interface_pro->addFilter(
        'request_info_result', 'filter_puc_request_info_result_slug_custom_admin_interface_pro', 10, 2
    );
    function filter_puc_request_info_result_slug_custom_admin_interface_pro( $plugininfo, $result ) { 
        //get the message from the server and set as transient
        set_transient('custom-admin-interface-pro-update',$plugininfo->{'message'},YEAR_IN_SECONDS * 1);

        return $plugininfo; 
    }; 
    /**
    * 
    *
    *
    * Show message underneath plugin
    */
    $path = plugin_basename( __FILE__ );
    add_action("after_plugin_row_{$path}", function( $plugin_file, $plugin_data, $status ) {
        
        $option_name = 'custom_admin_interface_pro_settings';
        $section = 'general_settings';

        if(get_option($option_name)){
            $option = get_option($option_name);
            //check if array key exists
            if(array_key_exists($section,$option)){
                if(strlen($option[$section]['purchase_email'])>0 && strlen($option[$section]['order_id'])>0){

                    $order_id = $option[$section]['order_id'];

                    //get transient
                    $message = get_transient('custom-admin-interface-pro-update');
                
                    if($message !== 'Yes' && $message !== false){
                        
                        $purchaseLink = 'https://northernbeacheswebsites.com.au/custom-admin-interface-pro/';

                        if($message == 'Incorrect Details'){
                            $displayMessage = 'The Order ID and Purchase ID you entered is not correct. Please double check the details you entered to receive product updates.';    
                        } elseif ($message == 'Licence Expired'){
                            $displayMessage = 'Your licence has expired. Please <a href="'.$purchaseLink.'" target="_blank">purchase a new licence</a> to receive further updates for this plugin.';    
                        } elseif ($message == 'Website Mismatch') {
                            $displayMessage = 'This plugin has already been registered on another website using your details. Under the licence terms this plugin can only be used on one website. Please <a href="'.$purchaseLink.'" target="_blank">click here</a> to purchase an additional licence. To change the website assigned to your licence, please click <a href="https://northernbeacheswebsites.com.au/my-account/view-order/'.$order_id.'/" target="_blank">here</a>.';    
                        } else {
                            $displayMessage = '';    
                        }
                        
                        echo '<tr class="plugin-update-tr active"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt"><p class="installer-q-icon">'.$displayMessage.'</p></div></td></tr>';

                    } 
                    
                } else {
                    echo '<tr class="plugin-update-tr active"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt"><p class="installer-q-icon">Please enter your Order ID and Purchase ID in the plugin settings to receive automatics updates.</p></div></td></tr>';    
                }
            }
        } 

    }, 10, 3 );

    /**
    * 
    *
    *
    * Force check for updates
    */
    function custom_admin_interface_pro_force_check_for_updates(){
        global $plugin_update_checker_custom_admin_interface_pro;
        
        $plugin_update_checker_custom_admin_interface_pro->checkForUpdates();
    }
    
    


    
?>