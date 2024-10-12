jQuery(document).ready(function ($) {

    //copy/export settings
    $('body').on('click','.question-container h3', function(event){

        //hide all open questions to make it accordion like
        $('.answer-container').hide('fast');

        $(this).next().show('fast');

    });



});