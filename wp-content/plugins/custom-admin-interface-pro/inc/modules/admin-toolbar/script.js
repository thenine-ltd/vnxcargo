
jQuery(document).ready(function ($) {
    //add sortable functionality
    $('#custom_toolbar_list').nestedSortable({
        handle: 'div',
        items: 'li',
        toleranceElement: '> div',
        listType: 'ul',
        isTree: true
    });




    //add menu item
    $('body').on('click', '#add_toolbar_item', function (event) {
        event.preventDefault(); 

        //get current time
        var date = new Date();
        var seconds = date.getTime();
        var newId = 'new-toolbar-item-'+seconds

        var data = '<li data-deletable="false" data-item-hidden="false" data-title-editable="true" data-id="'+newId+'" data-title="Your New Toolbar Item" data-parent="false" data-href="" data-group="" class="mjs-nestedSortable-branch mjs-nestedSortable-expanded">';
            data += '<div class="ui-sortable-handle">';

              data += '<span class="toolbar-item-title">';
                data += $('#custom_toolbar_list').attr('data-new-item-title');
              data += '</span>';

              data += '<div class="action-items"><div class="delete-item"><i class="fas fa-trash-alt"></i></div><div class="edit-item"><i class="fas fa-edit"></i></div><div class="hide-item"><i class="fas fa-eye-slash"></i></div></div>';
            data += '</div>';
        data += '</li>';
            
        
        //inject data
        $('#custom_toolbar_list').prepend(data);

        // saveRoutine();

    });

    //on click of hide item
    $('body').on('click', '.hide-item', function (event) {
        event.preventDefault(); 
        //toggle list class
        $(this).parent().parent().toggleClass('selected');

        //chagge icon and data
        var childIcon = $(this).find('svg');
        var parentList = $(this).parent().parent().parent();

        if(childIcon.hasClass('fa-eye-slash')){
            childIcon.removeClass('fa-eye-slash');
            childIcon.addClass('fa-eye');
            parentList.attr('data-item-hidden','true');
        } else {
            childIcon.addClass('fa-eye-slash');
            childIcon.removeClass('fa-eye');
            parentList.attr('data-item-hidden','false');
        }

        // saveRoutine();

    });


    //on click delete item
    $('body').on('click', '.delete-item', function (event) {

        event.preventDefault(); 

        //delete parent item
        var parentList = $(this).parent().parent().parent();
        
        Swal.fire({
            title: $('#custom_toolbar_list').attr('data-delete-title'),
            text: $('#custom_toolbar_list').attr('data-delete-text'),
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: $('#custom_toolbar_list').attr('data-delete-confirm')
          }).then((result) => {
            if (result.value) {
                parentList.remove();
            }
          })


        // saveRoutine();

    });

    //on edit click
    $('body').on('click', '.edit-item', function (event) {
      event.preventDefault(); 

        //get existing values of clicked item
        var parentListItem = $(this).parent().parent().parent();
        var titleDisplay = $(this).parent().prev();
        var existingTitle = parentListItem.attr('data-title');
        var existingTitleEditable = parentListItem.attr('data-title-editable');
        var existingLink = parentListItem.attr('data-href');

        //start output
        var html = '';

        if(existingTitleEditable == 'true'){
          html += '<label>'+$('#custom_toolbar_list').attr('data-edit-title')+'</label><input id="toolbar-item-title" class="swal2-input" value="'+existingTitle+'">';
        }

        html += '<label>'+$('#custom_toolbar_list').attr('data-edit-link')+'</label><input id="toolbar-item-link" class="swal2-input" value="'+existingLink+'">';

        Swal.fire({
          title: $('#custom_toolbar_list').attr('data-edit-popup-title'),
          html: html,
          showCancelButton: true,
          confirmButtonText: 'Confirm',
          preConfirm: function () {
            return new Promise(function (resolve) {
              resolve([
                $('#toolbar-item-title').val(),
                $('#toolbar-item-link').val()
              ])
            })
          },
          onOpen: function () {
            $('#toolbar-item-title').focus()
          }
        }).then(function (result) {

          console.log(result);

          if(existingTitleEditable == 'true'){
            var newTitle = result['value'][0]; 
            //update value
            parentListItem.attr('data-title',newTitle);
            //also update the label
            titleDisplay.text(newTitle);
          }
          var newLink = result['value'][1];
          parentListItem.attr('data-href',newLink);

          // saveRoutine();

        }).catch(Swal.noop)


  });


   
    function saveRoutine(){

        //do hide items
        var hiddenItems = [];
        $( '#custom_toolbar_list li' ).each(function( index ) {   
            var isHidden = $(this).attr('data-item-hidden');
            var id = $(this).attr('data-id');
            if(isHidden == 'true'){
                //add to array
                hiddenItems.push(id);
            }
        });

        //turn array into comma list
        var hiddenItems = hiddenItems.join(',');
        //inject into setting
        $('#toolbar_items_to_remove').val(hiddenItems);


        var mainToolbarData = {};

        //loop through items
        $( '#custom_toolbar_list li' ).each(function( index ) {
          var deletable = $(this).attr('data-deletable');
          var titleEditable = $(this).attr('data-title-editable');
          var id = $(this).attr('data-id');
          var title = $(this).attr('data-title');
          var href = $(this).attr('data-href');
          var group = $(this).attr('data-group');

          // console.log(title);

          //if group is blank set it to sale
          if(group == ''){
            group = false;  
          }

          //get parent
          var parentId = $(this).parent().parent().attr('data-id');
          if($(this).parent().parent().attr('data-id') == null){
            parentId = false;
          }

          var tempObject = {
            deletable:deletable,
            titleEditable:titleEditable,
            id:id,
            title:title,
            href:href,
            parent:parentId,
            group:group,
          };

          mainToolbarData[id] = tempObject;

        });

        // console.log(mainToolbarData);

        //inject into settin
        $('#custom_toolbar').val(JSON.stringify(mainToolbarData));

      
    }

    //run save routine before publish
    $("#publish").click(function(e) {
      saveRoutine();
      return true;
    });



});