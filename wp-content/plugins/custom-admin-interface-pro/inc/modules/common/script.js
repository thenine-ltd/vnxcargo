
jQuery(document).ready(function ($) {

    //make clone remember original
    (function (original) {
        jQuery.fn.clone = function () {
          var result           = original.apply(this, arguments),
              my_textareas     = this.find('textarea').add(this.filter('textarea')),
              result_textareas = result.find('textarea').add(result.filter('textarea')),
              my_selects       = this.find('select').add(this.filter('select')),
              result_selects   = result.find('select').add(result.filter('select'));
      
          for (var i = 0, l = my_textareas.length; i < l; ++i) $(result_textareas[i]).val($(my_textareas[i]).val());
          for (var i = 0, l = my_selects.length;   i < l; ++i) {
            for (var j = 0, m = my_selects[i].options.length; j < m; ++j) {
              if (my_selects[i].options[j].selected === true) {
                result_selects[i].options[j].selected = true;
              }
            }
          }
          return result;
        };
      }) (jQuery.fn.clone);


    //remove item
    $('body').on('click','.remove-exception-item', function(){
        
        //remove parent list item
        $(this).parent().remove();

        inject_conditions();
        
    });

    //add item
    $('body').on('click','.add-exception-item', function(){
        
        var parentItem = $(this).parent();

        var select2_active = true;

        //destroy select2
        $( '.exception-item' ).each(function( index ) {
            // if( $(this).attr('data-select2-id') ){
            if( $(this).hasClass('select2-hidden-accessible') ){
                $(this).select2('destroy');
            } else {
                select2_active = false;
            }
        }); 

        //clone item
        $( parentItem ).clone().insertAfter( parentItem );

        inject_conditions();

        //do select2
        $( '.exception-item' ).each(function( index ) {
            if(select2_active){
                $(this).select2({});
            }
        });        
        
    });

    //do injection routine
    function inject_conditions(){

        var injectedArray = [];

        $( '.exceptions-list select' ).each(function( index ) {

            var thisValue = $(this).val();

            //only add item to array if it has a length
            if(thisValue.length>0){
                injectedArray.push(thisValue); 
            }

               
        });

        //make sure values are unique
        var injectedArrayUnique = [];
        $.each(injectedArray, function(i, el){
            if($.inArray(el, injectedArrayUnique) === -1) injectedArrayUnique.push(el);
        });

        var commaList = injectedArrayUnique.join(',');

        //inject value
        $('#exceptions').val(commaList);

    }

    //on change
    $('body').on('change', '.exceptions-list select',function(){
        inject_conditions();
    });

    //make list items sortable
    if($( '.exceptions-list' ).length){
        $( '.exceptions-list' ).sortable({
            stop: function( event, ui ) {
                inject_conditions();   
            }
        });
    }
    
    

    //add shortcode content to editor
    $('body').on('click','.custom-admin-interface-pro-wrapper .shortcode-button, .custom-admin-interface-pro-settings .shortcode-button', function(){

        //declare values
        var shortcodeContent = $(this).text();

        //do target one
        var targetOne = $(this).parent().next().find('.wp-editor-area');
        var targetOneValue = targetOne.val();
        var targetOneNewValue = targetOneValue+shortcodeContent;
        targetOne.val(targetOneNewValue);

        //do target two
        var targetTwo = $(this).parent().next().find('iframe').contents().find('#tinymce');
        var targetTwoValue = targetTwo.html();
        var targetTwoNewValue = targetTwoValue + shortcodeContent;
        targetTwo.html(targetTwoNewValue);

    });

    function save_the_list_items(){
        //put data into settings
        //loop through items
        var data = [];

        $( ".custom-admin-interface-pro-hide-list-item" ).each(function( index ) {

            if($(this).hasClass('selected')){
                var id = $(this).attr('data');
                data.push(id);    
            }

        });

        //turn into comma string and inject
        data =  data.join(',');
        $('.custom-admin-interface-pro-hide-setting').val(data);
    }


    //select and deselect item in a hidden list
    $('body').on('click','.custom-admin-interface-pro-hide-list-item-inner-right', function(event){

        // if (event.target !== this){
        //     return;
        // } else {

            var thisParent = $(this).parent();

            //toggle the class
            thisParent.toggleClass('selected');
            
            //determine if selected or not
            if( thisParent.hasClass('selected')){
                thisParent.find('.custom-admin-interface-pro-hide-list-item-inner-right svg').removeClass('fa-eye-slash').addClass('fa-eye'); 
            } else {
                thisParent.find('.custom-admin-interface-pro-hide-list-item-inner-right svg').removeClass('fa-eye').addClass('fa-eye-slash');    
            }

            //save the settings
            save_the_list_items();
        // }

        

       
    });

    //make select and deselect all work
    //select and deselect item in a hidden list
    $('body').on('click','.select-all-link', function(){

        //loop through the items
        $( ".custom-admin-interface-pro-hide-list-item" ).each(function( index ) {

            //add class
            $(this).addClass('selected');
            $(this).find('.custom-admin-interface-pro-hide-list-item-inner-right svg').removeClass('fa-eye-slash').addClass('fa-eye'); 

        });

        //save the settings
        save_the_list_items();
        
    });

    //make select and deselect all work
    //select and deselect item in a hidden list
    $('body').on('click','.deselect-all-link', function(){

        //loop through the items
        $( ".custom-admin-interface-pro-hide-list-item" ).each(function( index ) {

            //add class
            $(this).removeClass('selected');
            $(this).find('.custom-admin-interface-pro-hide-list-item-inner-right svg').removeClass('fa-eye').addClass('fa-eye-slash');  

        });

        //save the settings
        save_the_list_items();
        
    });

    //toggle display of addition info
    $('body').on('click','.custom-admin-interface-pro-hide-list-item-inner-left .information-icon', function(event){

        var informationText = $(this).next();

        if(informationText.css('display') == 'none'){
            informationText.css('display','block');      
        } else {
            informationText.css('display','none'); 
        }
      
        
    });


    //save the settings
    $('body').on('click','.custom-admin-interface-pro-save-settings', function(event){

        event.preventDefault();

        var section = $(this).attr('data');

        var data = [];

        //loop through settings fields
        $( '.custom-admin-interface-pro-settings-field-input' ).each(function( index ) {
            
            var type = $(this).attr('data');

            if (type == undefined) {
                type = 'tinymce';  
                //save data for tinymce
                tinyMCE.triggerSave();
            }
            
            //do some custom logic for checkboxes
            if(type == 'checkbox'){
                if($(this).is(':checked')){
                    var value = 'checked';
                } else {
                    var value = '';   
                }
            } else if(type == 'code-css'){
                var value = css_code_editor.getValue();
            } else if(type == 'code-js'){
                var value = js_code_editor.getValue();
            } else {
                var value = $(this).val();
            }

            // console.log(value);

            var object = {
                type: type, 
                name: $(this).attr('name'), 
                value: value
            };

            //push to array
            data.push(object);

        });

        // console.log(data);
        // console.log(section);

        var data = {
            'action': 'custom_admin_interface_pro_save_settings',
            'section': section,
            'data': data,
        };

        jQuery.post(ajaxurl, data, function (response) {
            
            // console.log(response);

            location.reload();
                                 
        });
      
        
    });



    //makes image upload button work
    $('body').on('click','.custom-admin-interface-pro-image-upload', function(event){

        event.preventDefault();
        
        var previousInput = $(this).prev(); 
       
        var image = wp.media({ 
            title: 'Upload Image',
            // mutiple: true if you want to upload multiple files at once
            multiple: false
        }).open()
        .on('select', function(event){
            // This will return the selected image from the Media Uploader, the result is an object
            var uploaded_image = image.state().get('selection').first();
            // We convert uploaded_image to a JSON object to make accessing it easier
            var image_url = uploaded_image.toJSON().url;
            // Let's assign the url value to the input field
            
            previousInput.val(image_url);

        });
    });

    //hides and shows information text
    $('body').on('click','.help-icon', function(event){

        event.preventDefault();
        
        $(this).next().slideToggle(0);

    });

    //do colour picker
    $('.custom-admin-interface-pro-settings-field-color').wpColorPicker();

    

    if($(".custom-admin-interface-pro-settings-field-css").length){
        //activate codemirror on all classes
        var css_code = $(".custom-admin-interface-pro-settings-field-css")[0];

        var css_code_editor = CodeMirror.fromTextArea(css_code, {
            lineNumbers: true,
            lineWrapping: true,
            mode: "css",
            theme: "blackboard",    
            matchBrackets: true,
            autoCloseTags: true,
            autoCloseBrackets: true,
            viewportMargin: Infinity
        });
    }
        

    if($(".custom-admin-interface-pro-settings-field-js").length){
        //activate codemirror on all js
        var js_code = $(".custom-admin-interface-pro-settings-field-js")[0];

        var js_code_editor = CodeMirror.fromTextArea(js_code, {
            lineNumbers: true,
            lineWrapping: true,
            mode: "javascript",
            theme: "blackboard",    
            matchBrackets: true,
            autoCloseTags: true,
            autoCloseBrackets: true,
            viewportMargin: Infinity
        });
    }

    //if theres a post area field add the current time to it that way revisions will force on every save
    if($('.postarea').length){

        var now = new Date();

        var activeEditor = tinyMCE.get('content');
        var activeEditor = tinyMCE.get('content');
        if(activeEditor!==null){
            activeEditor.setContent(now);
        } else {
            $('#content').val(now);
        }
    }

    //activate select2 on user and role list
    if($('.exception-item').length){
        $('.exception-item').select2({});
    }
        


});