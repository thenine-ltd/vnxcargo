jQuery(document).ready(function ($) {

    //add shortcode content to editor
    $('body').on('click','.custom-admin-interface-pro-notice button', function(){

        var postId = $(this).parent().attr('data-post-id');
        var userId = $(this).parent().attr('data-user-id');

        // console.log(postId+' '+userId);

        //do request    
        var data = {
            'action': 'custom_admin_interface_admin_notice_dismiss',
            'userId': userId,
            'postId': postId,
        };

        jQuery.post(ajaxurl, data, function (response) {
            // console.log(response);
        });


    });

});

