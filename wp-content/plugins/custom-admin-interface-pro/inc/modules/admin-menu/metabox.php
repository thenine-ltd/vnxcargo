<?php
    
    /**
    * 
    *
    *
    * Fix script issue with wordpress 5.9 and above
    */
    add_action( 'admin_enqueue_scripts', 'admin_menu_deregister_scripts_and_styles');
    function admin_menu_deregister_scripts_and_styles() {

        wp_deregister_script( 'jquery-ui-sortable'  );
        wp_register_script('jquery-ui-sortable', plugins_url('../../library/jquery-ui-sortable/sortable.min.js', __FILE__ ), array('jquery-ui-mouse' ),custom_admin_code_pro_version(),true);
    }

    /**
    * 
    *
    *
    * Output content of metabox
    */
    function admin_menu_metabox_content($post){

        //set no once
        wp_nonce_field( basename( __FILE__ ), 'admin_menu_metabox_nonce' );

        //get variables
        $post_id = $post->ID;

        //get existing values
        $top_menu = get_post_meta($post_id, 'top_menu', true);
        $sub_menu = get_post_meta($post_id, 'sub_menu', true);
        $hide_menu = get_post_meta($post_id, 'hide_menu', true);

        //we need to change the version of jquery ui sortable backwards
  

        //enqueue styles and scripts
        wp_enqueue_script(array('jquery-ui-sortable','sweetalert','nestedsortable','common-script','admin-menu-script'));
        wp_enqueue_style(array('common-style','admin-menu-style'));

        //output code
        $html = '<div class="custom-admin-interface-pro-wrapper">';

            $html .= custom_admin_interface_pro_settings_inline_notice('blue',__('Modify the main menu of WordPress.','custom-admin-interface-pro'));


            //lets get this party started!
            global $menu, $submenu; //we use this to build out our menu
            global $custom_admin_interface_pro_top_level_menu_original, $custom_admin_interface_pro_sub_level_menu_original; //i dont think we need this anymore

            // var_dump($menu);
            // var_dump($custom_admin_interface_pro_top_level_menu_original);
            // var_dump($custom_admin_interface_pro_sub_level_menu_original);

            //if the options don't have a length use the globals for the initial menu
            if(strlen($top_menu)>0 && strlen($sub_menu)>0){
                //we need to first decode the saved values
                $top_menu_decoded = json_decode($top_menu,true); 
                $sub_menu_decoded = json_decode($sub_menu,true); 

                // var_dump($top_menu_decoded);
                // var_dump($sub_menu_decoded);

                //lets build the menu
                $html .= build_admin_menu($top_menu_decoded,$sub_menu_decoded,$hide_menu);
            } else {
                //lets build the menu
                $html .= build_admin_menu($custom_admin_interface_pro_top_level_menu_original,$custom_admin_interface_pro_sub_level_menu_original,$hide_menu);
            }

            
            

            //hidden input which holds the toolbar data
            //eventually add style="display:none;"
            $html .= '<input style="display:none;" id="top_menu" type="text" name="top_menu" value="'.esc_html($top_menu).'">';
            $html .= '<input style="display:none;" id="sub_menu" type="text" name="sub_menu" value="'.esc_html($sub_menu).'">';
            $html .= '<input style="display:none;" id="hide_menu" type="text" name="hide_menu" value="'.esc_html($hide_menu).'">';


        $html .= '</div>';

        echo $html;

    }
    /**
    * 
    *
    *
    * Save the data in metabox
    */
    add_action( 'save_post', 'admin_menu_save_metabox', 10, 2 );
    function admin_menu_save_metabox( $post_id ){
	
        if ( !isset( $_POST['admin_menu_metabox_nonce'] ) || !wp_verify_nonce( $_POST['admin_menu_metabox_nonce'], basename( __FILE__ ) ) ){
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
           return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ){
            return;
        }

        //do settings
        if ( isset( $_REQUEST['top_menu'] ) ) {
            update_post_meta( $post_id, 'top_menu', sanitize_textarea_field($_POST['top_menu']) );
        }
        if ( isset( $_REQUEST['sub_menu'] ) ) {
            update_post_meta( $post_id, 'sub_menu', sanitize_textarea_field($_POST['sub_menu']) );
        }
        if ( isset( $_REQUEST['hide_menu'] ) ) {
            update_post_meta( $post_id, 'hide_menu', sanitize_textarea_field($_POST['hide_menu']) );
        }
        

    }
    /**
    * 
    *
    *
    * Save the revision data
    */
    add_action( 'save_post', 'admin_menu_save_revision');
    function admin_menu_save_revision( $post_id ){
    
        $parent_id = wp_is_post_revision( $post_id );

        if ( $parent_id ) {

            $parent  = get_post( $parent_id );

            $top_menu = get_post_meta( $parent->ID, 'top_menu', true );
            if ( false !== $top_menu ){
                add_metadata( 'post', $post_id, 'top_menu', $top_menu);
            }  
            
            $sub_menu = get_post_meta( $parent->ID, 'sub_menu', true );
            if ( false !== $sub_menu ){
                add_metadata( 'post', $post_id, 'sub_menu', $sub_menu);
            }  

            $hide_menu = get_post_meta( $parent->ID, 'hide_menu', true );
            if ( false !== $hide_menu ){
                add_metadata( 'post', $post_id, 'hide_menu', $hide_menu);
            }  

        }
     
    }
    /**
    * 
    *
    *
    * Restore the revision
    */
    add_action( 'wp_restore_post_revision', 'admin_menu_restore_revision', 10, 2 );
    function admin_menu_restore_revision( $post_id, $revision_id ) {

        $post     = get_post( $post_id );
        $revision = get_post( $revision_id );

        //field
        $top_menu  = get_metadata( 'post', $revision->ID, 'top_menu', true );
    
        if ( false !== $top_menu ){
            update_post_meta( $post_id, 'top_menu', $top_menu );
        } else {
            delete_post_meta( $post_id, 'top_menu' );
        }

        //field
        $sub_menu  = get_metadata( 'post', $revision->ID, 'sub_menu', true );
    
        if ( false !== $sub_menu ){
            update_post_meta( $post_id, 'sub_menu', $sub_menu );
        } else {
            delete_post_meta( $post_id, 'sub_menu' );
        }

        //field
        $hide_menu  = get_metadata( 'post', $revision->ID, 'hide_menu', true );
    
        if ( false !== $hide_menu ){
            update_post_meta( $post_id, 'hide_menu', $hide_menu );
        } else {
            delete_post_meta( $post_id, 'hide_menu' );
        }
    
    }
    /**
    * 
    *
    *
    * Builds admin menu
    */
    function build_admin_menu($menu,$submenu,$hide_menu){

        $hide_new_menu_items = apply_filters('custom_admin_interface_pro_hide_new_menu_items', true);

        // var_dump($menu);
        // var_dump($submenu);

        //turn the hide items into an array
        $hide_menu = explode(',',$hide_menu);

        global $custom_admin_interface_pro_top_level_menu_original, $custom_admin_interface_pro_sub_level_menu_original;

        //what we are going to do is automatically add any new top level menu items to our menu
        //so what we are going to do is loop through our new menu and add all the menu items to an array
        //then we are going to loop through our original menu and see if the key exists
        //if it doesn't we will add that part of the array onto our new menu
        $all_menu_links = array();

        foreach($menu as $menu_item){
            array_push($all_menu_links,$menu_item[2]);
        }

        //this is used for what menu items which can be deleted
        $all_original_menu_links = array();
        $top_level_menu_items_with_html = array();

        foreach($custom_admin_interface_pro_top_level_menu_original as $menu_item){

            array_push($all_original_menu_links,$menu_item[2]);

            //detect whether html exists
            if($menu_item[0] != strip_tags($menu_item[0])){
                array_push($top_level_menu_items_with_html,$menu_item[2]);
            }

            //also prevent separators from being added
            if(!in_array($menu_item[2],$all_menu_links) && strlen($menu_item[0])>0){
                array_unshift($menu,$menu_item);
                //lets also add the item to the hidden elements
                if($hide_new_menu_items){
                    array_push($hide_menu,$menu_item[2]);
                }
                

                //lets also add sub level items
                //lets not bother hiding these because the parent takes care of this
                // if(array_key_exists($menu_item[2],$custom_admin_interface_pro_sub_level_menu_original)){
                //     //add item to sublevel array
                //     $submenu[$menu_item[2]] = $custom_admin_interface_pro_sub_level_menu_original[$menu_item[2]];
                // }

            }
        }






        //lets also add sub level menu items and hide them
        //so we need to do a comparison of the saved menu and the original submenu
        foreach($custom_admin_interface_pro_sub_level_menu_original as $top_level_menu => $sub_level_menu){

            // var_dump($top_level_menu);

            foreach($sub_level_menu as $key => $sub_level_menu_item){

                $sub_level_menu_item_index = $sub_level_menu_item[2];


                if(is_array($submenu) && array_key_exists($top_level_menu,$submenu)){

                    //we need to check if this exists in our created sub menu
                    $sub_level_menu_items_in_associated_top_level_menu = $submenu[$top_level_menu];

                    $match_found = false;

                    foreach($sub_level_menu_items_in_associated_top_level_menu as $sub_level_menu_items_in_associated_top_level_menu_item){
                        $sub_level_menu_items_in_associated_top_level_menu_item_key = $sub_level_menu_items_in_associated_top_level_menu_item[2];
                        $sub_level_menu_items_in_associated_top_level_menu_item_key_special = htmlspecialchars($sub_level_menu_items_in_associated_top_level_menu_item_key);

                        if($sub_level_menu_items_in_associated_top_level_menu_item_key == $sub_level_menu_item_index || $sub_level_menu_items_in_associated_top_level_menu_item_key_special == $sub_level_menu_item_index){

                            $match_found = true;

                        }

                    }

                    if(!$match_found){
                        //we are having some issues with customize items so we are just going to exclude these for now - at a later date I can hopefully get to the bottom of this.
                        if (strpos($sub_level_menu_item_index, 'customize.php') === false) {
                            array_push($submenu[$top_level_menu],$sub_level_menu_item);
                            //we also need to make the item hidden
                            if($hide_new_menu_items){
                                array_push($hide_menu,$top_level_menu.'|'.$sub_level_menu_item_index);
                            }
                            
                        }
                    }
                }

            }
        }




        //lets also detech html in sub items
        $sub_level_menu_items_with_html = array();
        foreach($custom_admin_interface_pro_sub_level_menu_original as $top_level_menu => $sub_level_menu){
            foreach($sub_level_menu as $sub_level_menu_item){

                $sub_level_menu_item_title = $sub_level_menu_item[0];
                $sub_level_menu_item_index = $sub_level_menu_item[2];

                if($sub_level_menu_item_title != strip_tags($sub_level_menu_item_title)){
                    array_push($sub_level_menu_items_with_html,$sub_level_menu_item_index);
                }
            }
        }



        //another new feature we will do is compile a list of menu items that are currently in the menu which aren't in the original and these ones we will allow deletion of
        $menu_items_which_can_be_deleted = array();
        //we have got the original menu links from the previous loop
        //now lets loop through our current menu
        foreach($menu as $menu_item){
            //also check the item is not a separator
            if(!in_array($menu_item[2],$all_original_menu_links) && strlen($menu_item[0])>0){
                array_push($menu_items_which_can_be_deleted,$menu_item[2]);
            }
        }


        //sort the top level menu items
        ksort($menu, SORT_NUMERIC);
        

        //start output
        $html = ''; 

        //do toolbar
        $html .= '<ul class="menu-toolbar">';
            $html .= '<li><button id="add_menu_item" class="button-secondary"><i class="fas fa-plus-circle"></i> '.__('Add Menu Item','custom-admin-interface-pro').'</button></li>';
            $html .= '<li><button id="add_separator_item" class="button-secondary"><i class="fas fa-plus-circle"></i> '.__('Add Separator Item','custom-admin-interface-pro').'</button></li>';
            // $html .= '<li><button id="add_new_items" class="button-secondary"><i class="fas fa-plus-circle"></i> '.__('Add Newly Added Items','custom-admin-interface-pro').'</button></li>';
            $html .= '<li><button id="compress_all" class="button-secondary"><i class="fas fa-compress-alt"></i> <span class="compress-all-items">'.__('Compress All Items','custom-admin-interface-pro').'</span><span style="display: none;" class="expand-all-items">'.__('Expand All Items','custom-admin-interface-pro').'</span></button></li>';
        $html .= '</ul>';


        //add roles to data
        //get the roles
        global $wp_roles;
        $roles = $wp_roles->roles;
        $administrator_capabilities = $roles['administrator']['capabilities'];

        //just get the keys
        $administrator_capabilities = array_keys($administrator_capabilities);
        //sort the array
        sort($administrator_capabilities);



        //output menu icon edit
        //this is hidden from view and pulled in by javascript into the popup
        $html .= '<div id="icon_edit_html" style="display:none;">';

            //do dash icons
            $html .= '<div class="dash-icons">';

                //do heading
                $html .= '<h4>'.__('WordPress Dashicons','custom-admin-interface-pro').'</h4>';

                //declare an array that holds all dashicon classes
                $all_dash_icons = array('dashicons-menu', 'dashicons-admin-site', 'dashicons-dashboard', 'dashicons-admin-post', 'dashicons-admin-media', 'dashicons-admin-links', 'dashicons-admin-page', 'dashicons-admin-comments', 'dashicons-admin-appearance', 'dashicons-admin-plugins', 'dashicons-admin-users', 'dashicons-admin-tools', 'dashicons-admin-settings', 'dashicons-admin-network', 'dashicons-admin-home', 'dashicons-admin-generic', 'dashicons-admin-collapse', 'dashicons-filter', 'dashicons-admin-customizer', 'dashicons-admin-multisite', 'dashicons-welcome-write-blog', 'dashicons-welcome-add-page', 'dashicons-welcome-view-site', 'dashicons-welcome-widgets-menus', 'dashicons-welcome-comments', 'dashicons-welcome-learn-more', 'dashicons-format-aside', 'dashicons-format-image', 'dashicons-format-gallery', 'dashicons-format-video', 'dashicons-format-status', 'dashicons-format-quote', 'dashicons-format-chat', 'dashicons-format-audio', 'dashicons-camera', 'dashicons-images-alt', 'dashicons-images-alt2', 'dashicons-video-alt', 'dashicons-video-alt2', 'dashicons-video-alt3', 'dashicons-media-archive', 'dashicons-media-audio', 'dashicons-media-code', 'dashicons-media-default', 'dashicons-media-document', 'dashicons-media-interactive', 'dashicons-media-spreadsheet', 'dashicons-media-text', 'dashicons-media-video', 'dashicons-playlist-audio', 'dashicons-playlist-video', 'dashicons-controls-play', 'dashicons-controls-pause', 'dashicons-controls-forward', 'dashicons-controls-skipforward', 'dashicons-controls-back', 'dashicons-controls-skipback', 'dashicons-controls-repeat', 'dashicons-controls-volumeon', 'dashicons-controls-volumeoff', 'dashicons-image-crop', 'dashicons-image-rotate', 'dashicons-image-rotate-left', 'dashicons-image-rotate-right', 'dashicons-image-flip-vertical', 'dashicons-image-flip-horizontal', 'dashicons-image-filter', 'dashicons-undo', 'dashicons-redo', 'dashicons-editor-bold', 'dashicons-editor-italic', 'dashicons-editor-ul', 'dashicons-editor-ol', 'dashicons-editor-quote', 'dashicons-editor-alignleft', 'dashicons-editor-aligncenter', 'dashicons-editor-alignright', 'dashicons-editor-insertmore', 'dashicons-editor-spellcheck', 'dashicons-editor-expand', 'dashicons-editor-contract', 'dashicons-editor-kitchensink', 'dashicons-editor-underline', 'dashicons-editor-justify', 'dashicons-editor-textcolor', 'dashicons-editor-paste-word', 'dashicons-editor-paste-text', 'dashicons-editor-removeformatting', 'dashicons-editor-video', 'dashicons-editor-customchar', 'dashicons-editor-outdent', 'dashicons-editor-indent', 'dashicons-editor-help', 'dashicons-editor-strikethrough', 'dashicons-editor-unlink', 'dashicons-editor-rtl', 'dashicons-editor-break', 'dashicons-editor-code', 'dashicons-editor-paragraph', 'dashicons-editor-table', 'dashicons-align-left', 'dashicons-align-right', 'dashicons-align-center', 'dashicons-align-none', 'dashicons-lock', 'dashicons-unlock', 'dashicons-calendar', 'dashicons-calendar-alt', 'dashicons-visibility', 'dashicons-hidden', 'dashicons-post-status', 'dashicons-edit', 'dashicons-trash', 'dashicons-sticky', 'dashicons-external', 'dashicons-arrow-up', 'dashicons-arrow-down', 'dashicons-arrow-right', 'dashicons-arrow-left', 'dashicons-arrow-up-alt', 'dashicons-arrow-down-alt', 'dashicons-arrow-right-alt', 'dashicons-arrow-left-alt', 'dashicons-arrow-up-alt2', 'dashicons-arrow-down-alt2', 'dashicons-arrow-right-alt2', 'dashicons-arrow-left-alt2', 'dashicons-sort', 'dashicons-leftright', 'dashicons-randomize', 'dashicons-list-view', 'dashicons-exerpt-view', 'dashicons-grid-view', 'dashicons-move', 'dashicons-share', 'dashicons-share-alt', 'dashicons-share-alt2', 'dashicons-twitter', 'dashicons-rss', 'dashicons-email', 'dashicons-email-alt', 'dashicons-facebook', 'dashicons-facebook-alt', 'dashicons-googleplus', 'dashicons-networking', 'dashicons-hammer', 'dashicons-art', 'dashicons-migrate', 'dashicons-performance', 'dashicons-universal-access', 'dashicons-universal-access-alt', 'dashicons-tickets', 'dashicons-nametag', 'dashicons-clipboard', 'dashicons-heart', 'dashicons-megaphone', 'dashicons-schedule', 'dashicons-wordpress', 'dashicons-wordpress-alt', 'dashicons-pressthis', 'dashicons-update', 'dashicons-screenoptions', 'dashicons-info', 'dashicons-cart', 'dashicons-feedback', 'dashicons-cloud', 'dashicons-translation', 'dashicons-tag', 'dashicons-category', 'dashicons-archive', 'dashicons-tagcloud', 'dashicons-text', 'dashicons-yes', 'dashicons-no', 'dashicons-no-alt', 'dashicons-plus', 'dashicons-plus-alt', 'dashicons-minus', 'dashicons-dismiss', 'dashicons-marker', 'dashicons-star-filled', 'dashicons-star-half', 'dashicons-star-empty', 'dashicons-flag', 'dashicons-warning', 'dashicons-location', 'dashicons-location-alt', 'dashicons-vault', 'dashicons-shield', 'dashicons-shield-alt', 'dashicons-sos', 'dashicons-search', 'dashicons-slides', 'dashicons-analytics', 'dashicons-chart-pie', 'dashicons-chart-bar', 'dashicons-chart-line', 'dashicons-chart-area', 'dashicons-groups', 'dashicons-businessman', 'dashicons-id', 'dashicons-id-alt', 'dashicons-products', 'dashicons-awards', 'dashicons-forms', 'dashicons-testimonial', 'dashicons-portfolio', 'dashicons-book', 'dashicons-book-alt', 'dashicons-download', 'dashicons-upload', 'dashicons-backup', 'dashicons-clock', 'dashicons-lightbulb', 'dashicons-microphone', 'dashicons-desktop', 'dashicons-laptop', 'dashicons-tablet', 'dashicons-smartphone', 'dashicons-phone', 'dashicons-index-card', 'dashicons-carrot', 'dashicons-building', 'dashicons-store', 'dashicons-album', 'dashicons-palmtree', 'dashicons-tickets-alt', 'dashicons-money', 'dashicons-smiley', 'dashicons-thumbs-up', 'dashicons-thumbs-down', 'dashicons-layout', 'dashicons-paperclip');
                    
                //for each dash icon print it out
                foreach ($all_dash_icons as $icon){
                    $html .= '<span data="'.$icon.'" class="icon-for-selection dashicons '.$icon.'"></span>';       
                }


            $html .= '</div>';

            //do custom icons
            $html .= '<div class="custom-icons">';

                $html .= '<h4>'.__('Plugin/Theme Icons','custom-admin-interface-pro').'</h4>';
                // print out all custom svg icons

                // var_dump($custom_admin_interface_pro_top_level_menu_original);

                foreach($custom_admin_interface_pro_top_level_menu_original as $item => $value) {
                    //check if item isn't a separator
                    $separator_class_check = $value[4];
                    
                    if(strpos($separator_class_check,'wp-menu-separator') == false){
                        
                        if(array_key_exists(6,$value)){
                            $icon = $value[6];
                            
                            if(strpos($icon,'data:image/svg+xml') !== false || strpos($icon,'http') !== false ){
                                $html .= '<span class="icon-for-selection svg-menu-icon svg" data="'.$icon.'" style="background-image: url(&quot;'.$icon.'&quot;);"></span>';    
                            }
                        }
                            

                    }
                }

            $html .= '</div>';

            //upload button
            $html .= '<div class="upload-icon">';
                $html .= '<h4>'.__('Upload an Icon','custom-admin-interface-pro').'</h4>';
                $html .= '<input type="button" name="upload-icon-button" id="upload-icon-button" class="button-secondary" value="'.__('Upload an Icon','custom-admin-interface-pro').'">';
            $html .= '</div>';


        $html .= '</div>';




        //start menu listing
        $html .= '<ul id="custom_menu_list" class="custom-admin-interface-pro-builder"

        data-delete-title="'.__('Are you sure?','custom-admin-interface-pro').'" 
        data-delete-text="'.__('You won\'t be able to revert this.','custom-admin-interface-pro').'" 
        data-delete-confirm="'.__('Yes, delete it!','custom-admin-interface-pro').'" 
        data-new-menu-item="'.__('Your New Menu Item','custom-admin-interface-pro').'" 
        data-edit-title="'.__('Menu title','custom-admin-interface-pro').'" 
        data-edit-link="'.__('Menu link','custom-admin-interface-pro').'" 
        data-edit-class="'.__('Menu classes','custom-admin-interface-pro').'" 
        data-edit-permission="'.__('Menu permission','custom-admin-interface-pro').'" 
        data-permission-options="'.implode(',',$administrator_capabilities).'" 
        data-icon-popup-title="'.__('Choose a Custom Icon','custom-admin-interface-pro').'" 
        data-details-popup-title="'.__('Edit Menu Item Details','custom-admin-interface-pro').'" 
        data-no-title-link="'.__('Please ensure all menu items have a link and a title','custom-admin-interface-pro').'" 
        data-duplicate="'.__('Please ensure all top level menu items have a unique link','custom-admin-interface-pro').'" 
        data-cancel-button="'.__('Cancel','custom-admin-interface-pro').'" 
        
        >';


            //loop through the top level items
            foreach($menu as $menu_item){

                //get properties
                $title = make_menu_title_nice($menu_item[0]);
                $permission = $menu_item[1];
                $link = $menu_item[2];
                $id = $menu_item[2];
                $alternate = make_menu_title_nice($menu_item[3]);
                $classes = $menu_item[4];
                

                // var_dump($menu_item);
                // var_dump($icon);
                // var_dump($menu_item[0]);
                // var_dump($title);
                // var_dump($classes);

                //some separators have additional classes so we need to check if the string contains
                if (strpos($classes, 'wp-menu-separator') !== false) {

                    $slug = '';
                    $icon = '';
                    
                    //its a separator
                    $html .= '<li class="mjs-nestedSortable-no-nesting separator" data-type="separator" data-title="'.$title.'" data-permission="'.$permission.'" data-link="'.$link.'" data-alternate-name="'.$alternate.'" data-classes="'.$classes.'" data-slug="'.$slug.'" data-icon="'.$icon.'">'; 
                        //create a contianer div
                        $html .= '<div>';
                            //add divider line
                            $html .= '<hr align="left" class="separator-line">';
                            //do action items
                            $html .= '<div class="action-items">';
                                //delete
                                $html .= '<div class="delete-item"><i title="'.__('Delete Item','custom-admin-interface-pro').'" class="fas fa-trash-alt"></i></div>';
                            $html .= '</div>'; 
                        $html .= '</div>';
                    $html .= '</li>';  

                     
                } else {

                    //these variables are only available to normal menu items and not separators
                    $slug = $menu_item[5];
                    $icon = $menu_item[6];

                    //need to show appropriate hide icon
                    //need to update condition
                    if(in_array($id,$hide_menu)){
                        $hide = '<div class="hide-item"><i title="'.__('Hide/Show Item','custom-admin-interface-pro').'" class="fas fa-eye"></i></div>';
                        $hidden_class = 'selected';
                    } else {
                        $hide = '<div class="hide-item"><i title="'.__('Hide/Show Item','custom-admin-interface-pro').'" class="fas fa-eye-slash"></i></div>';
                        $hidden_class = '';
                    }

                    //only show delete if the menu item contains class added-custom-menu-item
                    // if(strpos($classes, 'added-custom-menu-item') !== false) {
                    if(in_array($link,$menu_items_which_can_be_deleted)){
                        $delete = '<div class="delete-item"><i title="'.__('Delete Item','custom-admin-interface-pro').'" class="fas fa-trash-alt"></i></div>';    
                    } else {
                        $delete = '';   
                    }


                    $html .= '<li class="'.$hidden_class.'" data-title="'.$title.'" data-permission="'.$permission.'" data-link="'.$link.'" data-alternate-name="'.$alternate.'" data-classes="'.$classes.'" data-slug="'.$slug.'" data-icon="'.$icon.'">'; 
                        //create a contianer div
                        $html .= '<div>';   

                            //do icon
                            //check if dashicon
                            if (strpos($icon, 'dashicons-') !== false) {
                                $html .= '<span class="item-icon dashicons-before '.$icon.'"></span>';
                            } else {
                                //its an image
                                $html .= '<span style="background-image: url('.$icon.')" class="item-icon svg-menu-icon"></span>';
                            }


                            //do title
                            $html .= '<span class="menu-item-title">';
                                $html .= $title;  
                            $html .= '</span>'; 

                            //do action items
                            $html .= '<div class="action-items">';

                                //expand
                                $html .= '<div class="expand-item"><i title="'.__('Expand/Compress Item','custom-admin-interface-pro').'" class="fas fa-compress-alt"></i></div>';

                                //delete
                                $html .= $delete;
                                //edit
                                //check whether title should be editable
                                if(in_array($link,$top_level_menu_items_with_html)){
                                    $title_editable = 'false';
                                } else {
                                    $title_editable = 'true';
                                }

                                $html .= '<div data-title-editable="'.$title_editable.'" class="edit-item"><i title="'.__('Edit Item','custom-admin-interface-pro').'" class="fas fa-edit"></i></div>';
                                //hide
                                $html .= $hide;
                            $html .= '</div>'; 

                        $html .= '</div>';
                        
                        //do sub menu items here
                        //check if item is in array
                        if(array_key_exists($link,$submenu)){

                            $submenu_items = $submenu[$link];

                            // var_dump($submenu_items);

                            //only continue if there's items
                            if(count($submenu_items) > 0){
                                //do list container
                                $html .= '<ul>';

                                    //loop through the list items
                                    foreach($submenu_items as $submenu_item){

                                        // var_dump($submenu_item);

                                        //do key variables
                                        $title = make_menu_title_nice($submenu_item[0]);
                                        $permission = $submenu_item[1];
                                        $sub_link = $submenu_item[2];


                                        //sometimes this alternate might not be set
                                        if(array_key_exists(3,$submenu_item)){
                                            $alternate = make_menu_title_nice($submenu_item[3]);
                                        } else {
                                            $alternate = '';  
                                        }
                                        
                                        $id = $link.'|'.$sub_link;

                                        if(in_array($id,$hide_menu)){
                                            $hide = '<div class="hide-item"><i title="'.__('Hide/Show Item','custom-admin-interface-pro').'" class="fas fa-eye"></i></div>';
                                            $hidden_class = 'selected';
                                        } else {
                                            $hide = '<div class="hide-item"><i title="'.__('Hide/Show Item','custom-admin-interface-pro').'" class="fas fa-eye-slash"></i></div>';
                                            $hidden_class = '';
                                        }

                                        // var_dump($title);

                                        //only show submenu item if it has a title
                                        //we need to do this because the latest WooCommerce update it has a submenu item with no title...
                                        //some users are reporting duplication of sub items, and these items had no links, so we can perhaps check for no links as well here...
                                        if(strlen($title)>0 && strlen($sub_link)>0){

                                            $html .= '<li class="'.$hidden_class.'" data-title="'.$title.'" data-permission="'.$permission.'" data-link="'.$sub_link.'" data-alternate-name="'.$alternate.'" data-classes="" data-slug="" data-icon="dashicons-admin-generic">'; 
                                                //create a contianer div
                                                $html .= '<div>';   
                                                    
                                                //do icon
                                                $html .= '<span class="item-icon dashicons-before dashicons-admin-generic"></span>';

                                                    //do title
                                                    $html .= '<span class="menu-item-title">';
                                                        $html .= $title;  
                                                    $html .= '</span>'; 

                                                    //do action items
                                                    $html .= '<div class="action-items">';
                                                        //delete
                                                        //i dont think we will be able to get a way to add the delete link to sub items because we have no class identifier
                                                        // $html .= '<div class="delete-item"><i class="fas fa-trash-alt"></i></div>';
                                                        //edit

                                                        if(in_array($sub_link,$sub_level_menu_items_with_html)){
                                                            $title_editable = 'false';
                                                        } else {
                                                            $title_editable = 'true';
                                                        }

                                                        $html .= '<div data-title-editable="'.$title_editable.'" class="edit-item"><i title="'.__('Edit Item','custom-admin-interface-pro').'" class="fas fa-edit"></i></div>';
                                                        //hide
                                                        $html .= $hide;
                                                    $html .= '</div>'; 

                                                $html .= '</div>';
                                            $html .= '</li>';  
                                        } //end title check
                                    }

                                $html .= '</ul>';
                            }
                        }

                        
                    
                    $html .= '</li>';



                }

                      
            }

        $html .= '</ul>';

        return $html;
    }
    /**
    * 
    *
    *
    * function to make titles nice
    */
    function make_menu_title_nice($title){

        if($title != strip_tags($title)){
            //contains html
            return strip_tags($title);
        } else {
            return $title; 
        }

    }

?>