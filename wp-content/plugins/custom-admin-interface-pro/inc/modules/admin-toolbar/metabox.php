<?php
    
    /**
    * 
    *
    *
    * Fix script issue with wordpress 5.9 and above
    */
    add_action( 'admin_enqueue_scripts', 'admin_toolbar_deregister_scripts_and_styles');
    function admin_toolbar_deregister_scripts_and_styles() {

        wp_deregister_script( 'jquery-ui-sortable'  );
        wp_register_script('jquery-ui-sortable', plugins_url('../../library/jquery-ui-sortable/sortable.min.js', __FILE__ ), array('jquery-ui-mouse' ),custom_admin_code_pro_version(),true);
    }

    /**
    * 
    *
    *
    * Output content of metabox
    */
    function admin_toolbar_metabox_content($post){

        //set no once
        wp_nonce_field( basename( __FILE__ ), 'admin_toolbar_metabox_nonce' );

        //get variables
        $post_id = $post->ID;

        //get existing values
        $custom_toolbar_saved = get_post_meta($post_id, 'custom_toolbar', true);
        $toolbar_items_to_remove = get_post_meta($post_id, 'toolbar_items_to_remove', true);
        $toolbar_items_to_remove_array = explode(',',$toolbar_items_to_remove);
        $disable_frontend = get_post_meta($post_id, 'disable_frontend', true);

        // //enqueue styles and scripts
        wp_enqueue_script(array('sweetalert','nestedsortable','common-script','admin-toolbar-script'));
        wp_enqueue_style(array('common-style','admin-toolbar-style'));


        //output code
        $html = '<div class="custom-admin-interface-pro-wrapper">';

            $html .= custom_admin_interface_pro_settings_inline_notice('blue',__('Modify the main toolbar of WordPress. This is the bar that displays at the top of the admin screen.','custom-admin-interface-pro'));

            $html .= custom_admin_interface_pro_settings_inline_notice('blue',__('We recommend keeping the existing toolbar structure intact as much as possible and rather just hide items or add new items in around the existing menu structure. If you need to start again just create a <a href="'.get_admin_url().'post-new.php?post_type=admin_toolbar">new item</a>.','custom-admin-interface-pro'));


            global $default_wordpress_toolbar;

            //the first step is to render a visual representation of thhe current toolbar
            //if no setting exists get the standard toolbar
            if(strlen($custom_toolbar_saved)>0){

                $custom_toolbar = json_decode($custom_toolbar_saved,true);
                // var_dump($custom_toolbar);
            } else {
                $custom_toolbar = $default_wordpress_toolbar;

                //lets turn this into the same data format as above
                $custom_toolbar_new = array();
                foreach($custom_toolbar as $toolbar_item){
                    $custom_toolbar_new[$toolbar_item->id] = array(
                        'id' => $toolbar_item->id,
                        'title' => $toolbar_item->title,
                        'parent' => $toolbar_item->parent,
                        'href' => $toolbar_item->href,
                        'group' => $toolbar_item->group,
                        'meta' => $toolbar_item->meta,
                    );
                }

                $custom_toolbar = $custom_toolbar_new;

            }
            // var_dump($custom_toolbar);
            $html .= build_admin_toolbar($custom_toolbar,$toolbar_items_to_remove_array);
            

            //hidden input which holds the toolbar data
            //eventually add style="display:none;"
            $html .= '<input style="display:none;" id="custom_toolbar" type="text" name="custom_toolbar" value="'.esc_html(json_encode($custom_toolbar)).'">';
            $html .= '<input style="display:none;" id="toolbar_items_to_remove" type="text" name="toolbar_items_to_remove" value="'.esc_html($toolbar_items_to_remove).'">';

            //checkbox on whether to output on frontend
            $html .= '<label>'.__('Disable the custom toolbar on the frontend','custom-admin-interface-pro').'</label>';

            $html .= '<label class="switch">';
                if(strlen($disable_frontend)>0){
                    $html .= '<input type="checkbox" name="disable_frontend" value="checked" checked>';
                } else {
                    $html .= '<input type="checkbox" name="disable_frontend" value="checked">';
                }
                $html .= '<span class="slider"></span>';
            $html .= '</label>';

                
            
        $html .= '</div>';

        echo $html;

    }
    /**
    * 
    *
    *
    * Save the data in metabox
    */
    add_action( 'save_post', 'admin_toolbar_save_metabox', 10, 2 );
    function admin_toolbar_save_metabox( $post_id ){
	
        if ( !isset( $_POST['admin_toolbar_metabox_nonce'] ) || !wp_verify_nonce( $_POST['admin_toolbar_metabox_nonce'], basename( __FILE__ ) ) ){
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
           return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ){
            return;
        }

        //do settings
        if ( isset( $_REQUEST['custom_toolbar'] ) ) {
            update_post_meta( $post_id, 'custom_toolbar', $_POST['custom_toolbar'] );
        }

        if ( isset( $_REQUEST['toolbar_items_to_remove'] ) ) {
            update_post_meta( $post_id, 'toolbar_items_to_remove', sanitize_textarea_field($_POST['toolbar_items_to_remove']) );
        }


        

        if ( isset( $_REQUEST['disable_frontend'] ) && strlen($_REQUEST['disable_frontend'])>0) {
            update_post_meta( $post_id, 'disable_frontend', sanitize_text_field($_POST['disable_frontend']) );
        } else {
            delete_post_meta( $post_id, 'disable_frontend');    
        }
        
    }
    /**
    * 
    *
    *
    * Save the revision data
    */
    add_action( 'save_post', 'admin_toolbar_save_revision');
    function admin_toolbar_save_revision( $post_id ){

        $parent_id = wp_is_post_revision( $post_id );

        if ( $parent_id ) {

            $parent  = get_post( $parent_id );
            
            //field
            $custom_toolbar = get_post_meta( $parent->ID, 'custom_toolbar', true );

            if ( false !== $custom_toolbar ){
                add_metadata( 'post', $post_id, 'custom_toolbar', wp_slash($custom_toolbar) );
            }

            //field
            $toolbar_items_to_remove = get_post_meta( $parent->ID, 'toolbar_items_to_remove', true );

            if ( false !== $toolbar_items_to_remove ){
                add_metadata( 'post', $post_id, 'toolbar_items_to_remove', $toolbar_items_to_remove );
            }

            //field
            $disable_frontend = get_post_meta( $parent->ID, 'disable_frontend', true );

            if ( false !== $disable_frontend ){
                add_metadata( 'post', $post_id, 'disable_frontend', $disable_frontend );
            }
                

        }

        
    }
    /**
    * 
    *
    *
    * Restore the revision
    */
    add_action( 'wp_restore_post_revision', 'admin_toolbar_restore_revision', 10, 2 );
    function admin_toolbar_restore_revision( $post_id, $revision_id ) {

        $post     = get_post( $post_id );
        $revision = get_post( $revision_id ); 
        
        //field
        $custom_toolbar  = get_metadata( 'post', $revision->ID, 'custom_toolbar', true );
    
        if ( false !== $custom_toolbar ){
            update_post_meta( $post_id, 'custom_toolbar', wp_slash($custom_toolbar) );
        } else {
            delete_post_meta( $post_id, 'custom_toolbar' );
        }

        //field
        $toolbar_items_to_remove  = get_metadata( 'post', $revision->ID, 'toolbar_items_to_remove', true );
    
        if ( false !== $toolbar_items_to_remove ){
            update_post_meta( $post_id, 'toolbar_items_to_remove', $toolbar_items_to_remove );
        } else {
            delete_post_meta( $post_id, 'toolbar_items_to_remove' );
        }

        //field
        $disable_frontend  = get_metadata( 'post', $revision->ID, 'disable_frontend', true );
    
        if ( false !== $disable_frontend ){
            update_post_meta( $post_id, 'disable_frontend', $disable_frontend );
        } else {
            delete_post_meta( $post_id, 'disable_frontend' );
        }
    
    }
    /**
    * 
    *
    *
    * function to convert standard array to a multidimensional array
    */
    function admin_toolbar_convert_to_multidimensional_array( $ar, $pid = null ) {
        $op = array();
        foreach( $ar as $item ) {
            if( $item['parent'] == $pid ) {
                $op[$item['id']] = array(
                    // 'parent' => $item['parent']
                );
                // using recursion
                $children =  admin_toolbar_convert_to_multidimensional_array( $ar, $item['id'] );
                if( $children ) {
                    $op[$item['id']] = $children;
                }
            }
        }
        return $op;
    }
    /**
    * 
    *
    *
    * function to convert multidimensional array to an unordered list
    */
    function convert_multidimensional_array_to_unordered_list($array,$custom_toolbar,$toolbar_items_to_remove_array) {

		//Recursive Step: make a list with child lists
        $output = '<ul id="custom_toolbar_list" class="custom-admin-interface-pro-builder"
        
        data-delete-title="'.__('Are you sure?','custom-admin-interface-pro').'" 
        data-delete-text="'.__('You won\'t be able to revert this.','custom-admin-interface-pro').'" 
        data-delete-confirm="'.__('Yes, delete it!','custom-admin-interface-pro').'" 
        data-edit-popup-title="'.__('Edit toolbar item','custom-admin-interface-pro').'" 
        data-edit-title="'.__('Toolbar title','custom-admin-interface-pro').'" 
        data-edit-link="'.__('Toolbar link','custom-admin-interface-pro').'" 
        data-new-item-title="'.__('Your New Toolbar Item','custom-admin-interface-pro').'" 
        
        >';
		foreach ($array as $key => $subArray) {

            //get standard variables
            $id = $custom_toolbar[$key]['id'];
            $title = $custom_toolbar[$key]['title'];
            $parent = $custom_toolbar[$key]['parent'];
            $href = $custom_toolbar[$key]['href'];
            $group = $custom_toolbar[$key]['group'];

            //if the title contains html lets not make the title editable
            if (strpos($title, '<') !== false) {
                $title_editable = 'false';
            } else {
                $title_editable = 'true';   
            }

            //if there's no title give it a default value
            if($title == ''){
                $title = 'No Title';
                $title_editable = 'false';
            }

            //do appropriate icon
            if(in_array($id,$toolbar_items_to_remove_array)){
                $icon = '<div class="hide-item"><i class="fas fa-eye"></i></div>';
                $item_hidden = 'true';
                $hidden_class = 'selected';
            } else {
                $icon = '<div class="hide-item"><i class="fas fa-eye-slash"></i></div>';
                $item_hidden = 'false';
                $hidden_class = '';
            }

            //if parent is blank set it to false
            if($parent == ''){
                $parent = 'false';
            }

            //check whether deletable
            if (strpos($id, 'new-toolbar-item') !== false) {
                $deletable = 'true';
                $delete_icon = '<div class="delete-item"><i class="fas fa-trash-alt"></i></div>';
            } else {
                $deletable = 'false';  
                $delete_icon = ''; 
            }

            $output .= '<li data-deletable="'.$deletable.'" data-item-hidden="'.$item_hidden.'" data-title-editable="'.$title_editable.'" data-id="'.$id.'" data-title="'.htmlspecialchars($title).'" data-parent="'.$parent.'" data-href="'.$href.'" data-group="'.$group.'"><div class="'.$hidden_class.'"><span class="toolbar-item-title">'.strip_tags($title).'</span><div class="action-items">'.$delete_icon.'<div class="edit-item"><i class="fas fa-edit"></i></div>'.$icon.'</div></div>'.convert_multidimensional_array_to_unordered_list($subArray,$custom_toolbar,$toolbar_items_to_remove_array).'</li>';
            
            

		}
		$output .= '</ul>';
		
		return $output;
	}

    /**
    * 
    *
    *
    * function to render toolbar - takes a setting as an input
    */
    function build_admin_toolbar( $custom_toolbar , $toolbar_items_to_remove_array ) {

        //first step is to turn our object into a more standard array
        $custom_toolbar_as_array = array();

        foreach($custom_toolbar as $toolbar_item){
            $id = $toolbar_item['id'];
            $parent = $toolbar_item['parent'];

            array_push($custom_toolbar_as_array,array(
                'id' => $id,
                'parent' => $parent
            ));

        }

        // var_dump($custom_toolbar_as_array);

        //the next step is to convert the single level array into a multidimensional array
        $nice_array = admin_toolbar_convert_to_multidimensional_array( $custom_toolbar_as_array );


        //now convert the multidimensional array into an unordered list
        $nice_list = convert_multidimensional_array_to_unordered_list($nice_array,$custom_toolbar,$toolbar_items_to_remove_array);


        //start output
        $html = '';

        //do action items
        $html .= '<ul class="admin-toolbar-toolbar"><li><button id="add_toolbar_item" class="button-secondary"><i class="fas fa-plus-circle"></i> '.__('Add Toolbar Item','custom-admin-interface-pro').'</button></li></ul>';

        //do list
        $html .= $nice_list;

        return $html;
        
    }
    
?>