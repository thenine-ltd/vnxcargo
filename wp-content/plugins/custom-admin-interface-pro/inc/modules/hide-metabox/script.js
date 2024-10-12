
jQuery(document).ready(function ($) {

    //code to add and remove items from input when clicking the hide icon

    function createListingOfMetaBoxes(){

        var existingSetting = $('.custom-admin-interface-pro-hide-setting').val();

        existingSetting = existingSetting.split(',');

        var postTypesAndIds = $('#post-type-data').attr('data');
        
        var postTypesAndIdsSplit = postTypesAndIds.split(',');
        
        postTypesAndIdsSplit.unshift('dashboard|dashboard');
        
        // console.log(postTypesAndIdsSplit);
                
        $.each(postTypesAndIdsSplit,function(index,typeAndId) {
            
            var typeAndId = typeAndId.split('|');
            var postType = typeAndId[0];
            var href = typeAndId[1];

            // console.log(typeAndId);

            // var positionOfPipe = typeAndId.indexOf("|");
            
            // var postType = typeAndId.substr(0,positionOfPipe);
            
            function ucwords(str,force){
              str=force ? str.toLowerCase() : str;  
              return str.replace(/(\b)([a-zA-Z])/g,
                   function(firstLetter){
                      return firstLetter.toUpperCase();
                   });
            }
            
            var removeUnderScoresFromPostType = postType.replace(/_/g, " ");
            
            // var fullLength = typeAndId.length;
            
            // var postID = typeAndId.substr(positionOfPipe+1,fullLength);
            
            var currentPageUrl = window.location.href;
            var positionOfAdmin = currentPageUrl.indexOf('post.php?');
            var firstPartOfUrl = currentPageUrl.substr(0,positionOfAdmin);

            if(postType == 'dashboard'){
                var href = firstPartOfUrl+'index.php';    
            } 

            // console.log(postType);
            // console.log(href);
            
            $.ajax({
                url: href,
                type:'GET',
                success: function(data){
                    
                    var content = $(data).find('#adv-settings .metabox-prefs-container').html();

                    var contentParsed = $.parseHTML(content);
                    
                    //here we are cycling through the ajax items
                    $.each(contentParsed,function(key,value) {


                        var itemType = value.toString();
                        // console.log(itemType);

                        if(itemType == '[object HTMLLabelElement]'){

                            var metaId = $(value).find('input').val(); 
                            var metaName = $(value).text();

                            // console.log(metaId+' '+metaName);
                            // if($selected == true){
                            //     $html .= '<i class="fas fa-eye"></i>';
                            // } else {
                            //     $html .= '<i class="fas fa-eye-slash"></i>'; 
                            
                            if(existingSetting.includes(metaId+'|'+postType)){
                                var icon = '<i class="fas fa-eye"></i>';
                                var additionalClass = ' selected';
                            } else {
                                var icon = '<i class="fas fa-eye-slash"></i>'; 
                                var additionalClass = '';
                            }

                            //add to list
                            $( ".custom-admin-interface-pro-hide-list" ).append( '<li data="'+metaId+'|'+postType+'" class="custom-admin-interface-pro-hide-list-item'+additionalClass+'"><div class="custom-admin-interface-pro-hide-list-item-inner-left"><strong>'+metaName+'</strong> <em>('+ucwords(removeUnderScoresFromPostType)+')</em></div><div class="custom-admin-interface-pro-hide-list-item-inner-right">'+icon+'</div></li>' );


                        }

                     });
                }
            
            });
            
        });
        
        

    }
    //run the function initially    
    createListingOfMetaBoxes();   

});