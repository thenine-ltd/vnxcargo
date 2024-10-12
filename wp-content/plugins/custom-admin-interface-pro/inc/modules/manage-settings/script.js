jQuery(document).ready(function ($) {

    function copyToClipboard(text) {
        var dummy = document.createElement("textarea");
        // to avoid breaking orgain page when copying more words
        // cant copy when adding below this code
        // dummy.style.display = 'none'
        document.body.appendChild(dummy);
        //Be careful if you use texarea. setAttribute('value', value), which works with "input" does not work with "textarea". – Eduard
        dummy.value = text;
        dummy.select();
        document.execCommand("copy");
        document.body.removeChild(dummy);
    }

    function download(filename, text) {
        var element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);
      
        element.style.display = 'none';
        document.body.appendChild(element);
      
        element.click();
      
        document.body.removeChild(element);
      }
    

    //select all
    $('body').on('click','.select-all-link', function(event){

        event.preventDefault();

        var target = $(this).parent().next();

        //loop through all the checkboxes and check them
        target.find('input').each(function( index ) {
            
            $( this ).prop( "checked", true );

        });

    });

    //deselect all
    $('body').on('click','.deselect-all-link', function(event){

        event.preventDefault();

        var target = $(this).parent().next();

        //loop through all the checkboxes and check them
        target.find('input').each(function( index ) {
            
            $( this ).prop( "checked", false );

        });
        
    });

    //copy/export settings
    $('body').on('click','#copy-settings,#export-settings', function(event){

        event.preventDefault();

        var idOfElementWhichClicked = event.target.id;

        if(idOfElementWhichClicked == 'copy-settings'){
            var target = $(this).prev();
        } else {
            var target = $(this).prev().prev();
        }


        var settingsToExport = [];

        //loop through checked inputs
        

        //loop through all the checkboxes and check them
        target.find('input').each(function( index ) {
            if ($(this).is(':checked')) {
                var thisInputName = $(this).attr('name');
                settingsToExport.push(thisInputName);   
            }
        });

        // console.log(settingsToExport);

        if(settingsToExport.length > 0){
            
            var data = {
                'action': 'custom_admin_interface_pro_export_settings',
                'security': $('.export-import-delete-wrapper').attr('data-noonce-export-settings'),
                'data': settingsToExport,
            };
    
            jQuery.post(ajaxurl, data, function (response) {

                response = response.trim();

                // if(response == 'SUCCESS'){
                    
                    //do appropriate action here
                    if(idOfElementWhichClicked == 'copy-settings'){

                        copyToClipboard(response);
            
                        Swal.fire({
                            type: 'success',
                            title: $('.export-import-delete-wrapper').attr('data-copied'),
                        });

                    } else {
                        //create download file
                        download($('.export-import-delete-wrapper').attr('data-filename')+'.txt',response);
                    }
                    
                // } else {
                //     Swal.fire({
                //         type: 'error',
                //         title: $('.export-import-delete-wrapper').attr('data-error'),
                //     });
                // }

            });

        } else {
            Swal.fire({
                type: 'info',
                title: $('.export-import-delete-wrapper').attr('data-no-module'),
            });
        }
        
    });


    


    //import settings
    $('body').on('click','#import-settings', function(event){

        event.preventDefault();

        var settingsToImport =  $('#import-settings-input').val();

        // console.log(settingsToImport);

        if(settingsToImport.length > 0){


            Swal.fire({
                title: $('.export-import-delete-wrapper').attr('data-confirm'),
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: $('.export-import-delete-wrapper').attr('data-confirm-button')
              }).then((result) => {
                if (result.value) {

                    //continue
                    var data = {
                        'action': 'custom_admin_interface_pro_import_settings',
                        'security': $('.export-import-delete-wrapper').attr('data-noonce-import-settings'),
                        'data': settingsToImport,
                    };
            
                    jQuery.post(ajaxurl, data, function (response) {
    
                        console.log(response.trim());
    
                        if(response.trim() == 'SUCCESS'){
    
                            Swal.fire({
                                type: 'success',
                                title: $('.export-import-delete-wrapper').attr('data-success'),
                            });
    
                            //clear existing value
                            $('#import-settings-input').val('');
    
                            
                        } else {
                            Swal.fire({
                                type: 'error',
                                title: $('.export-import-delete-wrapper').attr('data-error'),
                            });
                        }
    
                    });
                }
              });

            
                

        } else {
            Swal.fire({
                type: 'info',
                title: $('.export-import-delete-wrapper').attr('data-no-data'),
            });
        }


        
    });



    //load contents of txt file into input
    $('body').on('change','#import-settings-file-upload', function(event){

        event.preventDefault();

        var fileToLoad = document.getElementById("import-settings-file-upload").files[0];

        var fileReader = new FileReader();
        fileReader.onload = function(fileLoadedEvent){
            var textFromFileLoaded = fileLoadedEvent.target.result;
            document.getElementById("import-settings-input").value = textFromFileLoaded;
        };

        fileReader.readAsText(fileToLoad, "UTF-8");
        
    });




    //delete settings
    $('body').on('click','#delete-settings', function(event){

        event.preventDefault();

        var settingsToDelete = [];

        //loop through checked inputs
        var target = $(this).prev();

        //loop through all the checkboxes and check them
        target.find('input').each(function( index ) {
            if ($(this).is(':checked')) {
                var thisInputName = $(this).attr('name');
                settingsToDelete.push(thisInputName);   
            }
        });

        if(settingsToDelete.length > 0){

            Swal.fire({
                title: $('.export-import-delete-wrapper').attr('data-confirm'),
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: $('.export-import-delete-wrapper').attr('data-confirm-button')
              }).then((result) => {
                if (result.value) {

                    //continue
                    var data = {
                        'action': 'custom_admin_interface_pro_delete_settings',
                        'security': $('.export-import-delete-wrapper').attr('data-noonce-delete-settings'),
                        'data': settingsToDelete,
                    };
            
                    jQuery.post(ajaxurl, data, function (response) {
        
                        console.log(response.trim());
        
                        if(response.trim() == 'SUCCESS'){
                            Swal.fire({
                                type: 'success',
                                title: $('.export-import-delete-wrapper').attr('data-success'),
                            });
                        } else {
                            Swal.fire({
                                type: 'error',
                                title: $('.export-import-delete-wrapper').attr('data-error'),
                            });
                        }
        
                    });
                }
              });


        } else {
            Swal.fire({
                type: 'info',
                title: $('.export-import-delete-wrapper').attr('data-no-module'),
            });
        }

    });


});