<?php
    /**
    * 
    *
    *
    * Function to implement the custom menu
    */
    add_action( 'admin_menu', 'admin_menu_implementation',PHP_INT_MAX);
    function admin_menu_implementation(){

        //what we are going to do here is create globals and share this with my metabox area
        global $menu, $submenu;
        global $custom_admin_interface_pro_top_level_menu_original, $custom_admin_interface_pro_sub_level_menu_original;

        $custom_admin_interface_pro_top_level_menu_original = $menu;
        $custom_admin_interface_pro_sub_level_menu_original = $submenu;

        // var_dump($custom_admin_interface_pro_sub_level_menu_original);

        //sort the array
        ksort($custom_admin_interface_pro_top_level_menu_original, SORT_NUMERIC);

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'posts';
        
        //just get all data
        $query = "SELECT * FROM $table_name WHERE post_type='admin_menu' AND post_status='publish'";
        
        $posts = $wpdb->get_results($query);

        if($posts){
            foreach($posts as $post){

                $post_id = $post->ID;
        
                //check if the code needs to be executed
                if(custom_admin_interface_pro_exception_check($post_id)){

                    //the menu is activated so lets disable woocommerce menu styling
                    wp_enqueue_style('woocommerce_admin_menu_styles_new');

                    //get variables of our menus
                    $top_menu = get_post_meta($post_id, 'top_menu', true);
                    $sub_menu = get_post_meta($post_id, 'sub_menu', true);

                    //decode the variables
                    $top_menu_decoded = json_decode($top_menu,true); 
                    $sub_menu_decoded = json_decode($sub_menu,true); 

                    if($top_menu_decoded != null || $sub_menu_decoded != null){


                        //lets add the original notification icons back in to the top level
                        //lets loop through the original menu items if there's html add it to the array and then append that html onto the updated label
                        $top_menu_items_with_html = array();
                        foreach($custom_admin_interface_pro_top_level_menu_original as $original_top_level_menu_item){

                            $title = $original_top_level_menu_item[0];
                            $link = $original_top_level_menu_item[2];

                            if($title != strip_tags($title)){
                                //contain html
                                $top_menu_items_with_html[$link] = $title;
                            }
                        }

                        //now lets add these to the new menu
                        foreach($top_menu_decoded as $key => $new_top_level_menu_item){
                            $title = $new_top_level_menu_item[0];
                            $link = $new_top_level_menu_item[2];   

                            //check if link is in array
                            if(array_key_exists($link,$top_menu_items_with_html)){
                                //set the new title
                                $new_title = $top_menu_items_with_html[$link];
                                //now set the value
                                $top_menu_decoded[$key][0] = $new_title;
                            }

                        }

                        // var_dump($custom_admin_interface_pro_sub_level_menu_original);

                        //lets add notifications to the sub level item as well
                        $sub_menu_items_with_html = array();
                        foreach($custom_admin_interface_pro_sub_level_menu_original as $key => $sub_level_items){

                            foreach($sub_level_items as $sub_level_item){
                                $title = $sub_level_item[0];
                                $link = $sub_level_item[2];

                                if($title != strip_tags($title)){
                                    $sub_menu_items_with_html[$key.'|'.$link] = $title;
                                }

                            }
                        }

                        //lets update the title
                        foreach($sub_menu_decoded as $key => $sub_level_items){
                            foreach($sub_level_items as $key_sub => $sub_level_item){

                                $title = $sub_level_item[0];
                                $target = $sub_level_item[1];
                                $link = $sub_level_item[2];

                                // var_dump($link);

                                //lets replace the link with forward slashes as this can cause issues
                                //but don't do this for fluentcrm
                                if (strpos($link, 'fluentcrm-admin') !== false) {
                                    $new_link = $link;
                                } else {
                                    $new_link = str_replace('/','%2F',$link);
                                }

                                //try a general replacement
                                $new_link = str_replace('%2F','/',$link);
  
                                $sub_menu_decoded[$key][$key_sub][2] = $new_link;

                                //check if array key exists
                                if(array_key_exists($key.'|'.$link,$sub_menu_items_with_html)){
                                    $new_title = $sub_menu_items_with_html[$key.'|'.$link];
                                    //now update the new array
                                    $sub_menu_decoded[$key][$key_sub][0] = $new_title;
                                }

                                //fix up issues with woocommerce analytics
                                if($target == 'view_woocommerce_reports'){
                                    $new_link = str_replace('wc-admin&path=','admin.php?page=wc-admin&path=',$link);
                                    $sub_menu_decoded[$key][$key_sub][2] = $new_link;
                                }

                            }
                        }


                        //sometimes plugins add submenus that are not utilised like learndash and this can cause issues, so to get around this lets add sub menus from the original submenu into our new submenu if they don't exist
                        //loop through original submenu
                        foreach($submenu as $key => $sub_level_items){

                            // var_dump($sub_level_items);

                            //if it doesn't exist, add it
                            if(!array_key_exists($key,$sub_menu_decoded)){
                                $sub_menu_decoded[$key] = $sub_level_items;
                            }
                        }

                        //do fix for wp snippets plugin
                        if ( is_plugin_active( 'code-snippets/code-snippets.php' ) ) {

                            $existing_menu_item = $sub_menu_decoded['snippets'];

                            //add array item
                            array_push($existing_menu_item,array(
                                0 => 'Edit Snippet',
                                1 => 'manage_options',
                                2 => 'edit-snippet',
                                3 => 'Edit Snippet',
                            ));
                            
                            $sub_menu_decoded['snippets'] = $existing_menu_item;

                        }


                        // var_dump($sub_menu_decoded);



                        //lets actually change the menu by setting the globals to our options
                        $menu = $top_menu_decoded;
                        $submenu = $sub_menu_decoded;  

                        // var_dump($top_menu_decoded);


                        //lets hide menus
                        $hide_menu = get_post_meta($post_id, 'hide_menu', true);

                        // var_dump($hide_menu);

                        //only continue if set
                        if(isset($hide_menu) && strlen($hide_menu)>0){

                            //lets loop over the items
                            $hide_menu_exploded = explode(',',$hide_menu);

                            foreach($hide_menu_exploded as $menu_item_to_hide){

                                if (strpos($menu_item_to_hide, '|') !== false) {
                                    //its a sub level item
                                    $components_of_option = explode('|',$menu_item_to_hide);

                                    //we need to do some additional work for WooCommerce analytics
                                    //this code doesn't actually work, but there's no harm keeping it how it is...
                                    $parent_slug = str_replace('wc-admin&path=','admin.php?page=wc-admin&path=',$components_of_option[0]);

                                    $child_slug = str_replace('wc-admin&path=','admin.php?page=wc-admin&path=',$components_of_option[1]);

                                    // var_dump($parent_slug);
                                    // var_dump($child_slug);

                                    remove_submenu_page($parent_slug,$child_slug);  

                                } else {
                                    //its a parent item
                                    remove_menu_page($menu_item_to_hide); 
                                }

                            }
                        }





                    }




                } //end exception check
            } //end foreach post
        } //end post check














        // //sort values and remove the links
        // ksort($wp_custom_admin_interface_original_top_level_menu, SORT_NUMERIC);
        // foreach ($wp_custom_admin_interface_original_top_level_menu as $key => $value){
        //     unset($wp_custom_admin_interface_original_top_level_menu[15]);

        // }

        // //rename all the keys because javascript doesn't like strings as key
        // $wp_custom_admin_interface_original_top_level_menu = array_values($wp_custom_admin_interface_original_top_level_menu);

        
        // if(isset($options['wp_custom_admin_interface_remove_menu_item'])) {

                
        //     //lets check if the changes are going to apply to this user
            
        //     if(wp_custom_admin_interface_admin_menu_exception_check('menu',$options['wp_custom_admin_interface_exception_type'],$options['wp_custom_admin_interface_exception_cases'])) {
                
        //         //lets replace the standard menu with our custom menu
                
        //         $topLevelMenu = $options['wp_custom_admin_interface_top_level_menu'];

        //         $subLevelMenu = $options['wp_custom_admin_interface_sub_level_menu'];

        //         // $topLevelMenuDecoded = json_decode($topLevelMenu,true);

        //         // $subLevelMenuDecoded = json_decode($subLevelMenu, true); 

        //         if(strpos($topLevelMenu, '[') !== false){
        //             $topLevelMenuDecoded = json_decode($topLevelMenu,true);  
        //         } else {
        //             $topLevelMenuDecoded = json_decode(base64_decode($topLevelMenu),true);
        //         }

        //         if(strpos($subLevelMenu, '[') !== false){
        //             $subLevelMenuDecoded = json_decode($subLevelMenu,true);  
        //         } else {
        //             $subLevelMenuDecoded = json_decode(base64_decode($subLevelMenu),true);
        //         }
                
                
                

                
                
        //         //check if notifications are necessary on the custom menu
        //         if(isset($options['wp_custom_admin_interface_show_notifications'])){
                
        //             //create an array which will store all our notifications
        //             $itemsWithNotifications = array();

        //             //cycle through the top level menu
        //             foreach($menu as $item => $value) {
        //                 //check if the label has a notification and if it does carry on
        //                 if(strpos($value[0], '<span') !== false) {
        //                     $positionOfSpan = strpos($value[0],'<span');
        //                     $lengthOfNotification = strlen($value[0])-$positionOfSpan;
        //                     $justTheNotification = substr($value[0],$positionOfSpan,$lengthOfNotification);
        //                     //add the position and notification to the array
        //                     $itemsWithNotifications += array($value[2] => $justTheNotification);     
        //                 }
        //             }

        //             //cycle through the sub level menu
        //             foreach($submenu as $item => $value){
        //                 //let's go a little deeper
        //                 foreach($value as $item => $value){
        //                     //check if the label has a notification and if it does carry on
        //                     if(strpos($value[0], '<span') !== false) {
        //                         $positionOfSpan = strpos($value[0],'<span');
        //                         $lengthOfNotification = strlen($value[0])-$positionOfSpan;
        //                         $justTheNotification = substr($value[0],$positionOfSpan,$lengthOfNotification);
        //                         //add the position and notification to the array
        //                         $itemsWithNotifications += array($value[2] => $justTheNotification);     
        //                     }
        //                 }
        //             }    
                    
                    
                    
        //             //now lets loop through our new menu and add these notifications to it
        //             //let's start with the top level menu
        //             foreach($topLevelMenuDecoded as $item => $value) {
                        
        //                 $menuLink = $value[2];
                        
        //                 if(array_key_exists($menuLink,$itemsWithNotifications)){
                            
        //                     $notificationToUtilise = $itemsWithNotifications[$menuLink];
                            
        //                     $topLevelMenuDecoded[$item][0] = $value[0].' '.$notificationToUtilise;
                            
        //                 } 
        //             }
                    

        //             //and now the sub menu
        //             foreach($subLevelMenuDecoded as $item => $value) {
        //                 //let's go a little deeper
        //                 foreach($value as $itemDeeper => $valueDeeper){   
                        
        //                     $menuLink = $valueDeeper[2];

        //                     if(array_key_exists($menuLink,$itemsWithNotifications)){

        //                         $notificationToUtilise = $itemsWithNotifications[$menuLink];

        //                         $subLevelMenuDecoded[$item][$itemDeeper][0] = $valueDeeper[0].' '.$notificationToUtilise;
                                
        //                     } 
        //                 }
        //             }
        //         }
    
                
                
        //         $menu = $topLevelMenuDecoded;
        //         $submenu = $subLevelMenuDecoded;    
                
                
        //         //now lets remove menu pages

        //         $removeLastCharacterFromString = substr($options['wp_custom_admin_interface_remove_menu_item'],0,-1);

        //         $turnCommaStringIntoArray = explode(",", $removeLastCharacterFromString);

        //         //cycle through each item in array
        //         foreach ($turnCommaStringIntoArray as $value) {

        //             //now we need to check whether the value has square brackets - this will determine whether the item is a top level or sub level item
        //             if(strpos($value, '[') !== false) {

        //             //lets get the top level component part    
        //             $positionOfFirstSquareBracket = strpos($value,'[');
        //             $topLevelMenuComponent = substr($value,0,$positionOfFirstSquareBracket);  

        //             //lets ge the sub level component part
        //             preg_match("/\[(.*)\]/", $value , $matches); 
        //             $subLevelMenuComponent = $matches[1];   

        //             remove_submenu_page($topLevelMenuComponent,$subLevelMenuComponent);    


        //             } else {
        //                 //its a top level menu item so lets remove it
        //                 remove_menu_page($value);   
        //             }  
        //         }
        //     }
        // } 
    }
    
?>