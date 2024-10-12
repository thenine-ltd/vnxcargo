jQuery(document).ready(function ($) {
    
    $('body').on('click', '.custom-admin-interface-pro-duplicate-item', function (event) {

        event.preventDefault(); 

        var postId = $(this).attr('data');

        var data = {
            'action': 'custom_admin_interface_duplicate_post',
            'postId': postId,
        };

        jQuery.post(ajaxurl, data, function (response) {
            console.log(response);
            location.reload();

        });

    });

    
});

