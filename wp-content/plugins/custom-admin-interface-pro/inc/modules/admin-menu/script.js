jQuery(document).ready(function ($) {

    //lets make the items drag and droppable
    $('#custom_menu_list').nestedSortable({
        handle: 'div',
        items: 'li',
        toleranceElement: '> div',
        maxLevels: 2,
        listType: 'ul',
        isTree: true,
    }); 

    //make sure any separators can't be nested under other items
    no_nesting_of_separators(); //run initially
    function no_nesting_of_separators(){
        $('.separator').each(function( index ) {
            $(this).removeClass('mjs-nestedSortable-leaf').addClass('mjs-nestedSortable-no-nesting');
        });
    }


    //remove item when clicked
    //on click delete item
    $('body').on('click', '.delete-item', function (event) {

        event.preventDefault(); 

        //delete parent item
        var parentList = $(this).parent().parent().parent();
        
        Swal.fire({
            title: $('#custom_menu_list').attr('data-delete-title'),
            text: $('#custom_menu_list').attr('data-delete-text'),
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: $('#custom_menu_list').attr('data-delete-confirm')
        }).then((result) => {
            if (result.value) {
                parentList.remove();
            }
        })

    });


    //add separator item
    $('body').on('click', '#add_separator_item', function (event) {

        event.preventDefault(); 

        var html = '<li class="mjs-nestedSortable-no-nesting separator" data-type="separator" data-title="" data-permission="read" data-link="separator" data-alternate-name="" data-classes="wp-menu-separator" data-slug="" data-icon=""><div class="ui-sortable-handle"><hr align="left" class="separator-line"><div class="action-items ui-sortable-handle"><div class="delete-item ui-sortable-handle"><i class="fas fa-trash-alt"></i></div></div></div></li>';

        //prepend to list
        $('#custom_menu_list').prepend(html);

    });

    //add menu item
    $('body').on('click', '#add_menu_item', function (event) {

        event.preventDefault(); 

        var date = new Date();
        var seconds = date.getTime();

        var html = '<li class="topmenu-item mjs-nestedSortable-branch mjs-nestedSortable-expanded" data-type="topmenu-item" data-title="'+$('#custom_menu_list').attr('data-new-menu-item')+'" data-permission="read" data-link="change-me-'+seconds+'.php" data-alternate-name="" data-classes="menu-top added-custom-menu-item" data-slug="" data-icon="dashicons-admin-generic"><div class="ui-sortable-handle"><span class="item-icon dashicons-before dashicons-admin-generic"></span><span class="menu-item-title">'+$('#custom_menu_list').attr('data-new-menu-item')+'</span><div class="action-items ui-sortable-handle"><div class="delete-item ui-sortable-handle"><i class="fas fa-trash-alt"></i></div><div data-title-editable="true" class="edit-item ui-sortable-handle"><i class="fas fa-edit"></i></div><div class="hide-item ui-sortable-handle"><i class="fas fa-eye-slash"></i></div></div></div></li>';

        //prepend to list
        $('#custom_menu_list').prepend(html);

    });

    //hide an item
    $('body').on('click', '.hide-item', function (event) {

        event.preventDefault(); 
        var thisIcon = $(this).find('svg');
        var parentItem = $(this).parent().parent().parent();

        if(thisIcon.hasClass('fa-eye')){
            //item is hidden
            thisIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            parentItem.removeClass('selected');
        } else {
            //item is shown
            thisIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            parentItem.addClass('selected');
        }
        

    });







    //edit an item
    $('body').on('click', '.edit-item', function (event) {
        event.preventDefault(); 
  
        //get existing values of clicked item
        var parentListItem = $(this).parent().parent().parent();
        var titleDisplay = $(this).parent().prev();

        var existingTitle = parentListItem.attr('data-title');
        var existingLink = parentListItem.attr('data-link');
        var existingClasses= parentListItem.attr('data-classes');
        var existingPermission = parentListItem.attr('data-permission');

        var titleEditable = $(this).attr('data-title-editable');

        //we need to determine whether the item is a top level or sub level item
        if(parentListItem.hasClass('mjs-nestedSortable-leaf')){
            var showClassField = false;
        } else {
            var showClassField = true;    
        }
          
  
        //start output
        var html = '';

        //do title
        //only make the title editable if allowed
        if(titleEditable == 'true'){
            html += '<label>'+$('#custom_menu_list').attr('data-edit-title')+'</label><input id="edit-title-field" class="swal2-input" value="'+existingTitle+'">';
        }

        

        //do link
        html += '<label>'+$('#custom_menu_list').attr('data-edit-link')+'</label><input id="edit-link-field" class="swal2-input" value="'+existingLink+'">';

        //only do class if top level
        if(showClassField == true){
            //do class
            html += '<label>'+$('#custom_menu_list').attr('data-edit-class')+'</label><input id="edit-class-field" class="swal2-input" value="'+existingClasses+'">';
        }
        

        //do permission
        html += '<label>'+$('#custom_menu_list').attr('data-edit-permission')+'</label>';

        var permissionOptions = $('#custom_menu_list').attr('data-permission-options');

        permissionOptions = permissionOptions.split(',');


        html += '<select id="edit-permission-field" class="swal2-input">';

            //loop through array
            $.each(permissionOptions, function( index, value ) {
                if(value == existingPermission){
                    html += '<option selected="selected">'+value+'</option>';
                } else {
                    html += '<option>'+value+'</option>';
                }
                
            });

        html += '</select>';
        




        Swal.fire({
        title: $('#custom_menu_list').attr('data-details-popup-title'),
        html: html,
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        preConfirm: function () {
            return new Promise(function (resolve) {
            resolve([
                $('#edit-title-field').val(),
                $('#edit-link-field').val(),
                $('#edit-class-field').val(),
                $('#edit-permission-field').val()

            ])
            })
        },
        onOpen: function () {
            $('#edit-title-field').focus()
        }
        }).then(function (result) {

            // console.log(result);

            //do title
            var newTitle = result['value'][0]; 
            parentListItem.attr('data-title',newTitle);
            titleDisplay.text(newTitle);

            //do link
            var newLink = result['value'][1]; 
            parentListItem.attr('data-link',newLink);
            
            //do class
            if(showClassField == true){
                
                var newClass = result['value'][2]; 
                parentListItem.attr('data-classes',newClass);
            }
            //do permission
            var newPermission = result['value'][3]; 
            parentListItem.attr('data-permission',newPermission);

        }).catch(Swal.noop)
  
  
    });






    //change icon
    $('body').on('click', '.item-icon', function (event) {
        //start output
        var html = $('#icon_edit_html').html();

        var thisIcon = $(this);

        Swal.fire({
        title: $('#custom_menu_list').attr('data-icon-popup-title'),
        html: html,
        customClass: 'swal-wide',
        showCancelButton: true,
        cancelButtonText: $('#custom_menu_list').attr('data-cancel-button'),
        showConfirmButton: false,
        onOpen: function () {

            //remove focus on the upload field
            $('input').blur();

            //on click of an icon
            //when selecting a new icon replace the existing icon and close the dialog
            $('.swal-wide').on('click', ".icon-for-selection", function () { 
                    
                var newIcon = $(this).attr('data');

                //determine whether icon is dashicon
                if($(this).hasClass('dashicons')){
                    //its a dash icon

                    //we need to do update the icon displayed
                    thisIcon.removeClass().addClass('item-icon dashicons-before '+newIcon);

                    //we need to update the data attribute
                    thisIcon.parent().parent().attr('data-icon',newIcon);

                } else {

                    //remove existing
                    thisIcon.removeClass().css('background-image','');

                    thisIcon.addClass('item-icon svg-menu-icon');

                    //add svg
                    thisIcon.css('background-image','url('+newIcon+')');

                    thisIcon.parent().parent().attr('data-icon',newIcon);
   
                }

                //close the popup
                Swal.close();

            });

            //when uploading a custom icon
            $('.swal-wide').on("click","#upload-icon-button", function(e){

                e.preventDefault();

                var image = wp.media({ 
                    title: 'Upload Image',
                    // mutiple: true if you want to upload multiple files at once
                    multiple: false
                }).open()
                .on('select', function(e){
                    // This will return the selected image from the Media Uploader, the result is an object
                    var uploaded_image = image.state().get('selection').first();
                    // We convert uploaded_image to a JSON object to make accessing it easier
                    var newIcon = uploaded_image.toJSON().url;
                    // Let's assign the url value to the input field

                    //remove existing
                    thisIcon.removeClass().css('background-image','');

                    thisIcon.addClass('item-icon svg-menu-icon');

                    //add svg
                    thisIcon.css('background-image','url('+newIcon+')');

                    thisIcon.parent().parent().attr('data-icon',newIcon);

                    Swal.close();
                        

                });
            });

        }
       
        }).then(function (result) {


        }).catch(Swal.noop)
    });


    






    //do validation
    function validationCheck(){
        var errors = 0; 

        //STEP - remove all existing errors
        $( '#custom_menu_list li' ).each(function( index ) {
            var childDiv = $(this).children('div');

            //clear any previous errors
            childDiv.removeClass('error-item');  
        });

        //STEP - remove all existing errors
        $( '#custom_menu_list li' ).each(function( index ) {
            var childDiv = $(this).children('div');

            //clear any previous errors
            childDiv.removeClass('error-item');  
        });

        //STEP - make sure every item has a title and link
        $( '#custom_menu_list li' ).each(function( index ) {

            var childDiv = $(this).children('div');

            // //clear any previous errors
            // childDiv.removeClass('error-item');

            if(!$(this).hasClass('separator')){
                var title = $(this).attr('data-title');
                var link = $(this).attr('data-link');

                if(title.length < 1 || link.length < 1){
                    //add error
                    childDiv.addClass('error-item');

                    Swal.fire({
                        type: 'error',
                        title: $('#custom_menu_list').attr('data-no-title-link'),
                    });

                    errors++;
                }
            }

        });

        //STEP - make sure links are unique
        var links = [];
        var duplicates = [];

        //do initial run to get all the duplicates
        $( '#custom_menu_list > li:not(.separator)' ).each(function( index ) {

            var link = $(this).attr('data-link');
            
            if(links.includes(link)){
                duplicates.push(link);
                errors++;
            } else {
                //add item to array
                links.push(link);
            }
        });


        //now show and report the errors
        $( '#custom_menu_list > li' ).each(function( index ) {

            var link = $(this).attr('data-link');
            var childDiv = $(this).children('div');

            // //clear any previous errors
            // childDiv.removeClass('error-item');
            
            if(duplicates.includes(link)){

                childDiv.addClass('error-item');

                Swal.fire({
                    type: 'error',
                    title: $('#custom_menu_list').attr('data-duplicate'),
                });
            } 
        });

        //STEP - return appropriate value
        if(errors>0){
            return false;
        } else {
            return true;
        }
        
    }






    //do save routine
    function saveRoutine(){


        //STEP - do hide items
        var hiddenItems = [];
        $( '#custom_menu_list li' ).each(function( index ) {   
            
            //let rework the id so it's right
            var parentItem = $(this).parent().parent();

            if(parentItem.hasClass('mjs-nestedSortable-branch')){
                //its a child item

                //make sure it's a parent
                if(parentItem.attr('data-link') == null){
                    var id = $(this).attr('data-link');
                } else {
                    var id = parentItem.attr('data-link')+'|'+$(this).attr('data-link');
                }

            } else {
                //its a parent item
                var id = $(this).attr('data-link');
            }

            if($(this).hasClass('selected')){
                //add to array
                hiddenItems.push(id);
            }
        });

        //turn array into comma list
        var hiddenItems = hiddenItems.join(',');
        //inject into setting
        $('#hide_menu').val(hiddenItems);



        //STEP - remove hidden items stuck in sub menus
        $('#custom_menu_list > li > ul > .mjs-nestedSortable-no-nesting').remove();  


        //STEP - lets do the top level menu
        var topLevelMenuArray = [];

        $('#custom_menu_list > li').each(function( index ) {
            
            //lets check if separator or not
            if($(this).hasClass('mjs-nestedSortable-no-nesting')){
                //its a separator
                //item 0
                var item0 = '';
                //item 1
                var item1 = 'read';
                //item 2
                var item2 = 'separator-'+index;
                //item 3
                var item3 = '';
                //item 4
                var item4 = 'wp-menu-separator';

                var menuItem = [item0,item1,item2,item3,item4];

            } else {
                //its a normal menu item
                var item0 = $(this).attr('data-title');
                var item1 = $(this).attr('data-permission');
                var item2 = $(this).attr('data-link');
                var item3 = $(this).attr('data-alternate-name'); //may need to do some magic here
                var item4 = $(this).attr('data-classes');
                var item5 = $(this).attr('data-slug'); //may need to do some magic here
                var item6 = $(this).attr('data-icon');

                var menuItem = [item0,item1,item2,item3,item4,item5,item6];
                // console.log(menuItem);

            }

            //add the menu item to the array
            topLevelMenuArray.push(menuItem);
 
        });

        // console.log(topLevelMenuArray);
        //inject the value into the setting
        var stringifiedTopLevelMenuArray = JSON.stringify(topLevelMenuArray);    
        $('#top_menu').val(stringifiedTopLevelMenuArray);  



        //STEP - do sub level items
        var subLevelMenuArray = {};    
        
        $('#custom_menu_list > li > ul').each(function( index ) {
            
            var parent = $(this).parent();
            var parentUrl = parent.attr('data-link');
            
            var subLevelMenuitems = {};
            
            //loop through the sub menu items
            $($(this).find('li')).each(function( index ) {
                
                var item0 = $(this).attr('data-title');
                var item1 = $(this).attr('data-permission');
                var item2 = $(this).attr('data-link');
                var item3 = $(this).attr('data-alternate-name'); //may need to do some magic here


                var menuItem = [item0,item1,item2,item3];    
                // console.log(menuItem);

                subLevelMenuitems[index] = menuItem; 
                
            }); 
            
            subLevelMenuArray[parentUrl] = subLevelMenuitems;
            
        });    
        
            
        var stringifiedSubLevelMenuArray = JSON.stringify(subLevelMenuArray);    
        $('#sub_menu').val(stringifiedSubLevelMenuArray);  




    }




    //run save routine before publish
    $("#publish").click(function(e) {

        if(validationCheck()){
            saveRoutine();
            return true;
        } else {
            return false;   
        }

    });


    //enable to expand and compress tree items
    $('body').on('click', '.expand-item', function (event) {

        event.preventDefault(); 

        var thisIcon = $(this).find('svg');

        var treeItems = $(this).parent().parent().next();

        if(thisIcon.hasClass('fa-compress-alt')){
            //item is hidden
            thisIcon.removeClass('fa-compress-alt').addClass('fa-expand-alt');

            //we need to hide the tree
            treeItems.hide();

        } else {
            //item is shown
            thisIcon.removeClass('fa-expand-alt').addClass('fa-compress-alt');
            treeItems.show();
        }
        

    });

    //compress all items button
    
    $('body').on('click', '#compress_all', function (event) {

        event.preventDefault(); 

        var thisIcon = $(this).find('svg');
        // var thisItem = $(this);
        var subMenus = $('.mjs-nestedSortable-branch ul');
        // var treeItems = $(this).parent().parent().next();



        if(thisIcon.hasClass('fa-compress-alt')){
            //item is hidden
            thisIcon.removeClass('fa-compress-alt').addClass('fa-expand-alt');
            //change the text
            $('.compress-all-items').hide();
            $('.expand-all-items').show();
            subMenus.hide();

            //change all icons in existing list
            $('.mjs-nestedSortable-branch svg').each(function(  ) {
                
                if($(this).hasClass('fa-compress-alt')){
                    $(this).removeClass('fa-compress-alt').addClass('fa-expand-alt');
                }

            });
            

        } else {
            //item is shown
            thisIcon.removeClass('fa-expand-alt').addClass('fa-compress-alt');
            // treeItems.show();
            $('.compress-all-items').show();
            $('.expand-all-items').hide();
            subMenus.show();


            //change all icons in existing list
            $('.mjs-nestedSortable-branch svg').each(function(  ) {
                
                if($(this).hasClass('fa-expand-alt')){
                    $(this).removeClass('fa-expand-alt').addClass('fa-compress-alt');
                }

            });
        }
        

    });



});