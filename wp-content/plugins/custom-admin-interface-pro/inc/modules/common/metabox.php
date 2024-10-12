<?php
    /**
    * 
    *
    *
    * Output content of metabox
    */
    function conditions_metabox_content($post){


        //do select2
        wp_enqueue_script(array('select2'));
        wp_enqueue_style(array('select2'));

        //get noonce
        wp_nonce_field( basename( __FILE__ ), 'conditions_metabox_nonce' );

        //get post id
        $post_id = $post->ID;

        //get saved values
        $exception_type_value = get_post_meta($post_id, 'exception_type', true);
        $exceptions_value = get_post_meta($post_id, 'exceptions', true);

        //enqueue items
        wp_enqueue_script(array('jquery-ui-sortable','fontawesome'));

        //start output
        $html = '';

        //implement text
        $html .= __('Implement this for','custom-admin-interface-pro').' ';

        //select

        $options = array(
            'no-one' => __('No-one','custom-admin-interface-pro'),
            'everyone' => __('Everyone','custom-admin-interface-pro')
            
        );

        $html .= '<select class="exception_type" name="exception_type">';

            foreach($options as $option_value => $option_label){
                if($option_value == $exception_type_value){
                    $html .= '<option selected="selected" value="'.esc_html($option_value).'">'.esc_html($option_label).'</option>';
                } else {
                    $html .= '<option value="'.esc_html($option_value).'">'.esc_html($option_label).'</option>';
                }
            }
            

        $html .= '</select>';

        $html .= ' '.__('except','custom-admin-interface-pro').':';



        //do exceptions
        //explode existing option
        $exceptions_value_exploded = explode(',',$exceptions_value);

        //output list item
        $html .= '<ul class="exceptions-list">';

        if(!empty($exceptions_value_exploded)){

            $select_options = array(''=>'');


            //get all roles
            global $wp_roles;
            $all_roles = $wp_roles->roles;

            foreach($all_roles as $role_slug => $role_data){
                $role_name = $role_data['name'];
                $label = __('Role','custom-admin-interface-pro').': '.$role_name;
                $select_options[$role_slug] = $label;
            }

            global $wpdb;

            $sql = "SELECT ID 
                    FROM $wpdb->users WHERE 1=1 AND {$wpdb->users}.ID IN (
                        SELECT {$wpdb->usermeta}.user_id FROM $wpdb->usermeta 
                        WHERE {$wpdb->usermeta}.meta_key = '{$wpdb->prefix}capabilities'
                        AND {$wpdb->usermeta}.meta_value NOT LIKE '%".apply_filters("custom_admin_interface_pro_role_to_exclude","ZZZZZZZZZ")."%') ORDER BY display_name ASC";

            $all_users = $wpdb->get_results($sql);
            
            foreach($all_users as $user){

                $id = $user->ID; 
                $user = get_userdata($id);
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $nick_name = $user->nickname;

                $all_user_roles = $user->roles; 
                
                $all_user_roles_name = array();

                foreach($all_user_roles as $user_role){
                    array_push($all_user_roles_name, $all_roles[$user_role]['name']);
                }

                if(count($all_user_roles_name)){
                    $user_role_display = ' ('.implode(', ',$all_user_roles_name).')';    
                } else {
                    $user_role_display = '';     
                }

                //work out the persons name
                //if they dont have a first or last name do a nickname
                if(strlen($first_name) > 0 || strlen($last_name) > 0){
                    $name = $first_name.' '.$last_name;
                } else {
                    $name = $nick_name;  
                }

                $label = __('User','custom-admin-interface-pro').': '.$name.$user_role_display;
                $user_id = $user->ID;

                $select_options[$user_id] = $label;

            }

            

            //loop through the exceptions
            foreach($exceptions_value_exploded as $exceptions_value_item){

                $html .= '<li>';


                    //do icon
                    $html .= '<i class="move-exception-item fas fa-arrows-alt-v"></i>';
                    
                    //do select
                    $html .= '<select class="exception-item">';

                        //loop through options
                        foreach($select_options as $option_value => $option_label){

                            if($option_value == $exceptions_value_item){
                                $html .= '<option selected="selected" value="'.$option_value.'">'.$option_label.'</option>'; 
                            } else {
                                $html .= '<option value="'.$option_value.'">'.$option_label.'</option>';  
                            }

                              
                        }

                    $html .= '</select>';

                    //do icons
                    $html .= '<i class="add-exception-item fas fa-plus"></i>';
                    $html .= '<i class="remove-exception-item fas fa-minus"></i>';
                    


                $html .= '</li>';

            }
        }

        $html .= '</ul>';


        $html .= '<input id="exceptions" type="text" name="exceptions" value="'.esc_html( $exceptions_value ).'">';

        echo $html;


    }
    /**
    * 
    *
    *
    * Save the data in metabox
    */
    add_action( 'save_post', 'custom_admin_interface_pro_save_conditions_metabox', 10, 2 );
    function custom_admin_interface_pro_save_conditions_metabox( $post_id ){
	
        if ( !isset( $_POST['conditions_metabox_nonce'] ) || !wp_verify_nonce( $_POST['conditions_metabox_nonce'], basename( __FILE__ ) ) ){
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
           return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ){
            return;
        }
        
        if ( isset( $_REQUEST['exception_type'] ) ) {
            update_post_meta( $post_id, 'exception_type', sanitize_text_field( $_POST['exception_type'] ) );
        }

        if ( isset( $_REQUEST['exceptions'] ) ) {
            update_post_meta( $post_id, 'exceptions', sanitize_text_field( $_POST['exceptions'] ) );
        }
    
    }
    
?>