<?php
    /**
    * 
    *
    *
    * Output content of settings
    */
    function custom_admin_interface_pro_support(){
        
        //get styles and scripts
        wp_enqueue_script(array('support-script','common-script','fontawesome'));
        wp_enqueue_style(array('common-style','support-style'));

        //section name
        $section = 'support';

        //do header
        echo custom_admin_interface_pro_settings_page_header(__('Support','custom-admin-interface-pro'));

        //do video
        echo '<h2>'.__('Check our walkthrough video','custom-admin-interface-pro').'</h2>'; 

        echo '<iframe style="margin-top: 15px;" width="560" height="315" src="https://www.youtube.com/embed/6sF4LmWM5Xc" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'; 

        echo '<ol>';
            echo '<li>General Settings <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=60s">1:00</a></li>';
            echo '<li>Admin Color Scheme <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=109s">1:49</a></li>';
            echo '<li>Login Page <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=139s">2:19</a></li>';
            echo '<li>Maintenance Page <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=176s">2:56</a></li>';
            echo '<li>Admin Menus <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=221s">3:41</a></li>';
            echo '<li>Admin Notices <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=341s">5:41</a></li>';
            echo '<li>Admin Toolbars <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=388s">6:28</a></li>';
            echo '<li>Custom Admin Codes <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=454s">7:34</a></li>';
            echo '<li>Dashboard Widgets <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=489s">8:09</a></li>';
            echo '<li>Custom Frontend Codes <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=527s">8:47</a></li>';
            echo '<li>Hide Metaboxes <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=565s">9:25</a></li>';
            echo '<li>Hide Plugins <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=615s">9:25</a></li>';
            echo '<li>Hide Sidebars <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=651s">10:51</a></li>';
            echo '<li>Hide Users <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=689s">11:29</a></li>';
            echo '<li>Manage Settings <a target="_blank" href="https://www.youtube.com/watch?v=6sF4LmWM5Xc&t=727s">12:07</a></li>';
        echo '</ol>';

        
        //do divider
        echo '<hr style="margin-top: 45px; margin-bottom: 30px;">';
            

        echo '<h2>'.__('Frequently Asked Questions','custom-admin-interface-pro').'</h2>'; 

        //do faq
        $frequently_asked_questions = array(
            __('Should I keep WP Custom Admin Interface Pro activated to use the pro version Custom Admin Interface Pro?','custom-admin-interface-pro') => 
            __('Whilst there\'s no harm having both activated it\'s recommended to only have 1 plugin activated at any given time that way you might get confused on what plugin is doing what.','custom-admin-interface-pro'),

            __('I can not save the plugin settings?','custom-admin-interface-pro') => 
            __('Go to your personal <a href="'.get_admin_url().'profile.php">profile page</a> and make sure the setting "Visual Editor - Disable the visual editor when writing" is unchecked, and then save the settings.','custom-admin-interface-pro'),

            __('I can not hide the welcome metabox on the dashboard?','custom-admin-interface-pro') => 
            __('This item is a lot more tricky to hide, instead use <a href="'.get_admin_url().'edit.php?post_type=custom_admin_code">custom admin code</a> to hide it via CSS, you can use this code: <code>#welcome-panel { display: none; }</code>.','custom-admin-interface-pro'),
            
            __('I can not hide meta boxes in Gutenberg','custom-admin-interface-pro') => 
            __('Unfortunately it is not currently possible to hide these without hiding them on the individual user account in question. This can be done by clicking on the three dots in the top right hand corner of the editor and then clicking options. We are monitoring this and if/when a solution should become available we will be implementing it.','custom-admin-interface-pro'),

            __('On the admin menu and toolbar editor the restore buttons in the free version have gone?','custom-admin-interface-pro') => 
            __('Custom Admin Interface Pro uses the WordPress revision system so there\'s no longer a need for restore buttons, just click the restore link next to the date/time you want to restore to. If you have mucked things up just delete the menu/toolbar and create a new one and it will revert back to the default menu/toolbar.','custom-admin-interface-pro'),

            __('Can I use my site logo or icon in your logo and favicon options in the plugin more easily?','custom-admin-interface-pro') => 
            __('Yes! If you set your site logo or icon using the WordPress Customiser, then you can insert these values into any of our favicon or logo settings using shortcodes: [site_logo] and [site_icon]. Simply put these values into our settings fields and whatever is set in the customiser will be used by our plugin.','custom-admin-interface-pro'),

            __('Some menu items are not displaying for certain user roles, why is this?','custom-admin-interface-pro') => 
            __('Although you can create custom menu\'s and with the plugin plugin, the plugin does not have the power to make certain menu items available to certain user roles. This is because menu items created by plugins sometimes code their plugin so that the menu item should only show for a certain role. So even though in our plugin it looks as though the menu items should display, because the plugin has restricted the plugins use to a particular roles(s) these menu items are not going to show up.

            Now, with that said, it is possible to change the permission of the menu items so that they can show for other roles. BUT, just because you can change the permission so that the menu item is now visible for other user roles, it doesn\'t mean the menu item will actually be usable, in many cases the page itself is restricted to particular users based on what has been setup in the plugin.
            
            In an ideal world, perhaps it would be great if plugins had the ability to change the security of other plugins, BUT you can understand also how this could have significant security implications, hence why it\'s not possible. For example, imagine if a plugin could make WooCommerce settings available to subscribers, that would be disastrous.','custom-admin-interface-pro'),

            __('What if some of the settings of the plugin cause issues with the backend of WordPress?','custom-admin-interface-pro') => 
            __('You can always delete the plugin settings from the <a href="admin.php?page=caip_manage_settings">Manage Settings</a> page. If you get yourself in a real pickle you should delete the plugin from your site via FTP and then install this plugin <a target="_blank" href="https://www.dropbox.com/s/xa2g0jevqe04eww/custom-admin-interface-pro-delete-all-settings.zip?dl=0">here</a> which will wipe all settings/posts created by Custom Admin Interface Pro, then you should be able to safely install the plugin again as the plugin data will now be removed.','custom-admin-interface-pro'),


            __('I have a huge amount of users for a particular role and I am having performance issues on some of the plugin settings pages?','custom-admin-interface-pro') => 
            __('We have a filter to exclude a particular role from the dropdowns as some people have thousands of customers and this can cause issues putting all those users into one dropdown. Please use the following filter (replace subscriber with the role you wish to exclude - this should be the roles slug name, not display name):<br><br>
            <code>
            add_filter(\'custom_admin_interface_pro_role_to_exclude\',\'my_function\',10,1);<br>
            <br>
            function my_function($value){<br>
                return \'subscriber\';<br>
            }
            </code>
            
            ','custom-admin-interface-pro'),
        );

        //do output of faq
        foreach($frequently_asked_questions as $question => $answer){
            //do container
            echo '<div class="question-container">';
                echo '<h3><i class="far fa-question-circle"></i> '.$question.'</h3>';
                echo '<div class="answer-container">';   
                    echo $answer;
                echo '</div>';
            echo '</div>';
        }

        //do divider
        echo '<hr style="margin-top: 45px; margin-bottom: 30px;">';

        echo '<h2 style="margin-bottom: 15px;">'.__('Contact Us','custom-admin-interface-pro').'</h2>';  

        //do support contact section
        echo __('To assist me with your support request please provide the following information with your email:','custom-admin-interface-pro');

        //user theme
        $user_theme = wp_get_theme();    
        $user_theme = $user_theme->get( 'Name' );

        //active themes
        $active_plugins = get_option('active_plugins');
        $plugins = get_plugins();
        $activated_plugins = array();

        foreach ($active_plugins as $plugin){           
            array_push($activated_plugins, $plugins[$plugin]);     
        } 

        $active_plugins_output = '<ul>';
        foreach ($activated_plugins as $key){ 
            $active_plugins_output .= '<li>'.$key['Name'].'</li>';
        }
        $active_plugins_output .= '</ul>';

            

        //start data
        $diagnostic_info = array(
            __('PHP Version','custom-admin-interface-pro') => phpversion(),
            __('WordPress Version','custom-admin-interface-pro') => get_bloginfo('version'),
            __('Plugin Version','custom-admin-interface-pro') => custom_admin_code_pro_version(),
            __('Current Theme','custom-admin-interface-pro') => $user_theme,
            __('Active Plugins','custom-admin-interface-pro') => $active_plugins_output,
        );

        //start table output
        echo '<table class="diagnostic-information">';

            //loop through diagnostic info
            foreach($diagnostic_info as $label => $data){
                echo '<tr>';
                    echo '<td><strong>'.$label.':</strong></td>';
                    echo '<td>'.$data.'</td>';
                echo '</tr>';
            }

        echo '</table>';

        echo __('Now please <a href="mailto:info@northernbeacheswebsites.com.au">email me</a> with this information.','custom-admin-interface-pro');



            
        // $html .= custom_admin_interface_pro_settings_inline_notice('blue','hello world');
        // $html .= custom_admin_interface_pro_settings_inline_notice('yellow','hello world');

        echo custom_admin_interface_pro_settings_page_footer(false,$section);


    }


?>