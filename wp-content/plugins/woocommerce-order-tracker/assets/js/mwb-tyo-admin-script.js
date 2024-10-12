/**
* Script for the ADMIN END
*  
* @link http://www.wpswings.com/
*/

var ajax_url = global_tyo_admin.ajaxurl;
// var nonce = global_tyo_admin.mwb_tyo_nonce;

jQuery(document).ready(function(){
	
   
    jQuery('body').on('click','.mwb_enhanced_tyo_remove',function(e){
    	e.preventDefault();
    	var mwb_enhanced_tyo_remove=jQuery(this).data("id");
    	
    	
    	jQuery("#mwb_enhanced_tyo_class"+mwb_enhanced_tyo_remove).remove();
		
    	jQuery.ajax({
			url:ajax_url,
			type:"POST",
			data: {
				action : 'mwb_provider_remove_company_data_from_plugin',
				mwb_company_name:mwb_enhanced_tyo_remove,
				nonce : global_tyo_admin.mwb_tyo_nonce,
			},success:function(response){
				// console.log( response );
			
			}	

		});
    });
	//jquery add buttton
     jQuery('#mwb_tyo_enhanced_woocommerce_shipment_tracking_add_providers').on('click',function(e){
      var mwb_company_name=jQuery('.mwb_toy_enhanced_provider').val();
      var mwb_company_url=jQuery('.mwb_toy_enhanced_provider_url').val();
      jQuery.ajax({
			url:ajax_url,
			type:"POST",
			data: {
				action : 'mwb_provider_subbmission_data_from_plugin',
				nonce : global_tyo_admin.mwb_tyo_nonce,
				mwb_company_name : mwb_company_name,
				mwb_company_url : mwb_company_url
			},success:function(response){
				jQuery('.mwb_toy_enhanced_provider').val("");
				jQuery('.mwb_toy_enhanced_provider_url').val("");
				var  mwb_append="<div class='mwb-tyo-courier-data' id='mwb_enhanced_tyo_class"+mwb_company_name+"'>";
				  mwb_append+="<input type='checkbox' id='mwb_enhanced_checkbox"+mwb_company_name+"' name='mwb_tyo_courier_url["+mwb_company_name+"]' value='"+mwb_company_url+"'>";
               mwb_append+="<label for='mwb_enhanced_checkbox"+mwb_company_name+"'>"+mwb_company_name+"</label>";
                mwb_append+='<a href="#" id="mwb_enhanced_cross'+mwb_company_name+'" class="mwb_enhanced_tyo_remove" data-id="'+mwb_company_name+'">X</a></div>';
				jQuery(mwb_append).appendTo(".mwb_tyo_courier_content");
				
			}	

		});
      
     });
		// license validation
	jQuery('#mwb_wot_license_save').on('click',function(){
		
		jQuery('.licennse_notification').html('');
		var mwb_license = jQuery('#mwb_wot_license_key').val();
		
		if( mwb_license == '' )
		{
			jQuery('#mwb_wot_license_key').css('border','1px solid red');
			return false;
		}
		else
		{
			jQuery('#mwb_wot_license_key').css('border','none');
		}
		jQuery('.loading_image').show();
		jQuery.post(
			global_tyo_admin.ajaxurl,
			{
				'action':'mwb_wot_register_license',
				'mwb_nonce':global_tyo_admin.mwb_tyo_nonce,
				'license_key':mwb_license
			},
			function(response)
			{
				if( response.msg == '' )
				{
					response.msg = 'Something Went Wrong! Please try again';
				}
				jQuery('.loading_image').hide();
				if(response.status == true )
				{
					jQuery('.licennse_notification').css('color','green');
					jQuery('.licennse_notification').html(response.msg);
					window.location.href = global_tyo_admin.site_url+'/wp-admin/admin.php?page=wc-settings&tab=mwb_tyo_settings';
					
				}
				else
				{
					jQuery('.licennse_notification').html(response.msg);
				}
			},'json'
		);
	});

});



jQuery( document ).ready( function(){
	jQuery('.mwb_tyo_get_approval').click(function(){
		
		jQuery('#mwb-tyo-modal').show();
	});

	jQuery('.mwb-tyo-modal-close').click(function(){
		
		jQuery('#mwb-tyo-modal').hide();
	});

	jQuery('.mwb-tyo-underlay').click(function(){
		
		jQuery('#mwb-tyo-modal').hide();
	});
	jQuery(document).on('click','#mwb_buyer_submit_no',function(){
		
		jQuery('#mwb-tyo-modal').hide();
	});
	

	jQuery(document).on('click','#mwb_buyer_submit_yes',function(){
		var notification_value = jQuery(this).attr('data-accept_value');
		jQuery.ajax({
			url:ajaxurl,
			type:"POST",
			data: {
				action : 'mwb_form_subbmission_data_from_plugin',
				notification : notification_value,
				nonce : global_tyo_admin.mwb_tyo_nonce,
			},success:function(response){
				window.location.reload();
			}	

		});
	});
	
	var current_url = window.location.href;
	if( current_url.indexOf( 'tab=mwb_tyo_settings&section=custom_status' ) > 0 )
	{
		jQuery( document ).find( '.woocommerce-save-button' ).hide();
	}
	if(current_url.indexOf('tab=mwb_tyo_settings&section=templates') > 0)
	{
		jQuery( document ).find( '.woocommerce-save-button' ).hide();
	}
	jQuery("div#mwb_mwb_mail_success").hide();
	jQuery("div#mwb_mwb_mail_failure").hide();
	jQuery("div#mwb_mwb_mail_empty").hide();
	jQuery("div#mwb_mwb_invalid_input").hide();
	jQuery("div#mwb_mwb_select_for_delete").hide();

	/*SETTING A TIMEPICKER TO THE METABOX ON ORDER EDIT PAGE*/
	if( jQuery( '.mwb_tyo_est_delivery_time' ).length > 0 ){

		jQuery( '.mwb_tyo_est_delivery_time' ).timepicker();
	}

	/*SETTING A DATEPICKER TO THE METABOX ON ORDER EDIT PAGE*/
	if( jQuery( '.mwb_tyo_est_delivery_date' ).length > 0 ){

		jQuery( '.mwb_tyo_est_delivery_date' ).datepicker({ minDate: new Date()});
		
	}

	/*SHOW / HIDE FOR SELECTING THE USE OF CUSTOM ORDER STATUS */
	jQuery( '#mwb_tyo_enable_custom_order_feature' ).on( 'change', function(){
		if ( jQuery( '#mwb_tyo_enable_custom_order_feature' ).is( ':checked' ) ) {
			jQuery('#mwb_tyo_new_custom_statuses_for_order_tracking').closest('tr').show();
		}else{
			jQuery('#mwb_tyo_new_custom_statuses_for_order_tracking').closest('tr').hide();
		}
	} );

	if ( jQuery( '#mwb_tyo_enable_custom_order_feature' ).is( ':checked' ) ) {
		jQuery('#mwb_tyo_new_custom_statuses_for_order_tracking').closest('tr').show();
	}else{
		jQuery('#mwb_tyo_new_custom_statuses_for_order_tracking').closest('tr').hide();
	}

	jQuery( '#mwb_tyo_order_status_in_hidden' ).closest('tr').hide();


	var selected_order_status_approval = jQuery( '#mwb_tyo_order_status_in_approval' ).val();
	var selected_order_status_processing = jQuery( '#mwb_tyo_order_status_in_processing' ).val();
	var selected_order_status_shipping = jQuery( '#mwb_tyo_order_status_in_shipping' ).val();

	jQuery.each( selected_order_status_processing , function( key , value ){
		jQuery("#mwb_tyo_order_status_in_approval option[value="+value+"]").remove();
		jQuery("#mwb_tyo_order_status_in_shipping option[value="+value+"]").remove();
	} );

	jQuery.each( selected_order_status_approval , function( key , value ){
		jQuery("#mwb_tyo_order_status_in_processing option[value="+value+"]").remove();
		jQuery("#mwb_tyo_order_status_in_shipping option[value="+value+"]").remove();
	} );

	jQuery.each( selected_order_status_shipping , function( key , value ){
		jQuery("#mwb_tyo_order_status_in_processing option[value="+value+"]").remove();
		jQuery("#mwb_tyo_order_status_in_approval option[value="+value+"]").remove();
	} );

	jQuery( document ).on( 'change' , '#mwb_tyo_new_custom_statuses_for_order_tracking', function(){
		var selected_order_status_approval = jQuery( '#mwb_tyo_order_status_in_approval' ).val();
		
		var selected_order_status_processing = jQuery( '#mwb_tyo_order_status_in_processing' ).val();
		var selected_order_status_shipping = jQuery( '#mwb_tyo_order_status_in_shipping' ).val();
		var notSelected = jQuery("#mwb_tyo_new_custom_statuses_for_order_tracking").find('option').not(':selected');
		var array1 = notSelected.map(function () {
			return this.value;
		}).get();
		
		jQuery.each( array1 , function( key, val ){
			jQuery("#mwb_tyo_order_status_in_processing option[value='"+val+"']").remove();
			jQuery("#mwb_tyo_order_status_in_approval option[value='"+val+"']").remove();
			jQuery("#mwb_tyo_order_status_in_shipping option[value='"+val+"']").remove();
			var value = val.replace( 'wc-' , '' );
			if( jQuery(document).find( '.select2-selection__choice' ).attr( 'title' ) == value )
			{
				jQuery(document).find( '.select2-selection__choice' ).attr( 'title', value ).remove();
			}
		} );
		var order_statuses = global_tyo_admin.order_statuses;
		var val = jQuery(this).val();


		jQuery.each( val , function(key , value){
			var status_name = order_statuses[value];

			if( status_name == '' || status_name == null ){
				status_name = value.replace( 'wc-' , '' );
			}
			var  l = '<option value='+value+'>'+status_name+'</option>';
			if((jQuery.inArray(value, selected_order_status_approval)==-1) && (jQuery.inArray(value,selected_order_status_processing)==-1) && (jQuery.inArray(value,selected_order_status_shipping)==-1) )
			{
				
				if( jQuery("#mwb_tyo_order_status_in_processing option[value="+value+"]").length <= 0 ){
					jQuery('#mwb_tyo_order_status_in_processing').append( l );
				}
				if( jQuery("#mwb_tyo_order_status_in_approval option[value="+value+"]").length <= 0 ){
					jQuery('#mwb_tyo_order_status_in_approval').append( l );
				}
				if( jQuery("#mwb_tyo_order_status_in_shipping option[value="+value+"]").length <= 0 ){
					jQuery('#mwb_tyo_order_status_in_shipping').append( l );
				}
			}
			
		} );

	} );


	jQuery( document ).on( 'change', '#mwb_tyo_order_status_in_approval', function()
	{
		var order_statuses = global_tyo_admin.order_statuses;
		var existing_value =jQuery('#mwb_tyo_new_custom_statuses_for_order_tracking').val();
		var status = [];
		var selected_order_status_approval = jQuery('#mwb_tyo_order_status_in_approval' ).val();
		var selected_order_status_processing = jQuery('#mwb_tyo_order_status_in_processing' ).val();
		var selected_order_status_shipping = jQuery( '#mwb_tyo_order_status_in_shipping' ).val();
		var hidden_value = []; 
		var previously_selected_value = []; 
		var previously_selected_value = jQuery( '#mwb_tyo_order_status_in_hidden' ).val();
		jQuery.each( selected_order_status_approval, function( key, value ){
			hidden_value.push( value );
		} );
	jQuery.each( selected_order_status_processing, function( key, value ){
		hidden_value.push( value );
	} );
	jQuery.each( selected_order_status_shipping, function( key, value ){
		hidden_value.push( value );
	} );
	
	
	jQuery( '#mwb_tyo_order_status_in_hidden' ).val( hidden_value );
	var pre_length = 0 ;
	if(previously_selected_value != null && previously_selected_value.length != null && previously_selected_value.length != 0)
	{
		var pre_length = previously_selected_value.length;
	}
	var hidden_length = hidden_value.length;
	if( pre_length >= hidden_length )
	{
		var i = 0;
		jQuery.grep(previously_selected_value, function(el) {

			if (jQuery.inArray(el, hidden_value) == -1) 
			{
				var status_name = order_statuses[el];
				var  l = '<option value='+el+'>'+status_name+'</option>';
				
				jQuery('#mwb_tyo_order_status_in_processing').append( l );
				jQuery('#mwb_tyo_order_status_in_shipping').append( l );
			}


			i++;

		});
	}
	else if( pre_length <= hidden_length )
	{
		var i = 0;
		jQuery.grep(hidden_value, function(el) {

			if (jQuery.inArray(el, previously_selected_value) == -1) 
			{
				jQuery("#mwb_tyo_order_status_in_processing option[value="+el+"]").remove();
				jQuery("#mwb_tyo_order_status_in_shipping option[value="+el+"]").remove();

			}


			i++;

		});
	}

} );

	jQuery( document ).on( 'change', '#mwb_tyo_order_status_in_processing', function()
	{
		var order_statuses = global_tyo_admin.order_statuses;
		var existing_value =jQuery('#mwb_tyo_new_custom_statuses_for_order_tracking').val();
		var status = [];
		var selected_order_status_approval = jQuery( '#mwb_tyo_order_status_in_approval' ).val();
		var selected_order_status_processing = jQuery( '#mwb_tyo_order_status_in_processing' ).val();
		var selected_order_status_shipping = jQuery( '#mwb_tyo_order_status_in_shipping' ).val();
		var hidden_value = [] ; 
		var previously_selected_value = []; 
		var previously_selected_value = jQuery( '#mwb_tyo_order_status_in_hidden' ).val();
		jQuery.each( selected_order_status_approval, function( key, value ){
			hidden_value.push( value );
		} );
		jQuery.each( selected_order_status_processing, function( key, value ){
			hidden_value.push( value );
		} );
		jQuery.each( selected_order_status_shipping, function( key, value ){
			hidden_value.push( value );
		} );
		jQuery( '#mwb_tyo_order_status_in_hidden' ).val( hidden_value );

		var pre_length = 0 ;
		if(previously_selected_value != null && previously_selected_value.length != null && previously_selected_value.length != 0)
		{
			var pre_length = previously_selected_value.length;
		}
		var hidden_length = hidden_value.length;

		if( pre_length >= hidden_length )
		{

			var i = 0;
			jQuery.grep(previously_selected_value, function(el) {

				if (jQuery.inArray(el, hidden_value) == -1) 
				{
					var status_name = order_statuses[el];
					var  l = '<option value='+el+'>'+status_name+'</option>';
					jQuery('#mwb_tyo_order_status_in_approval').append( l );
					jQuery('#mwb_tyo_order_status_in_shipping').append( l );
				}


				i++;

			});
		}
		else if( pre_length <= hidden_length )
		{
			var i = 0;
			jQuery.grep(hidden_value, function(el) {

				if (jQuery.inArray(el, previously_selected_value) == -1) 
				{

					jQuery("#mwb_tyo_order_status_in_approval option[value="+el+"]").remove();
					jQuery("#mwb_tyo_order_status_in_shipping option[value="+el+"]").remove();

				}


				i++;

			});
		}
	} );

	
	jQuery( document ).on( 'change', '#mwb_tyo_order_status_in_shipping', function()
	{
		var order_statuses = global_tyo_admin.order_statuses;
	
	var status = [];
	var selected_order_status_approval = jQuery( '#mwb_tyo_order_status_in_approval' ).val();
	var selected_order_status_processing = jQuery( '#mwb_tyo_order_status_in_processing' ).val();
	var selected_order_status_shipping = jQuery( '#mwb_tyo_order_status_in_shipping' ).val();
	
	var hidden_value = []; 
	var previously_selected_value = []; 
	var previously_selected_value = jQuery( '#mwb_tyo_order_status_in_hidden' ).val();
	jQuery.each( selected_order_status_approval, function( key, value ){
		hidden_value.push( value );
	} );
	jQuery.each( selected_order_status_processing, function( key, value ){
		hidden_value.push( value );
	} );
	jQuery.each( selected_order_status_shipping, function( key, value ){
		hidden_value.push( value );
	} );

	jQuery( '#mwb_tyo_order_status_in_hidden' ).val( hidden_value );

	var pre_length = 0 ;
	if(previously_selected_value != null && previously_selected_value.length != null && previously_selected_value.length != 0)
	{
		var pre_length = previously_selected_value.length;
	}
	var hidden_length = hidden_value.length;

	if( pre_length >= hidden_length )
	{
		var i = 0;
		jQuery.grep(previously_selected_value, function(el) {

			if (jQuery.inArray(el, hidden_value) == -1) 
			{
				var status_name = order_statuses[el];
				var  l = '<option value='+el+'>'+status_name+'</option>';
				jQuery('#mwb_tyo_order_status_in_processing').append( l );
				jQuery('#mwb_tyo_order_status_in_approval').append( l );
			}
			i++;

		});
	}
	else if( pre_length <= hidden_length )
	{
		var i = 0;
		jQuery.grep(hidden_value, function(el) {

			if (jQuery.inArray(el, previously_selected_value) == -1) 
			{

				jQuery("#mwb_tyo_order_status_in_processing option[value="+el+"]").remove();
				jQuery("#mwb_tyo_order_status_in_approval option[value="+el+"]").remove();

			}

			i++;
		});
	}
} );

// for custom order status image icons 
	jQuery('.mwb_tyo_other_setting_upload_logo').click(function(){
    var imageurl = jQuery("#mwb_tyo_other_setting_upload_logo").val();

        tb_show('', 'media-upload.php?TB_iframe=true');

        window.send_to_editor = function(html)
        {
           var imageurl = jQuery(html).attr('href');
          
           if(typeof imageurl == 'undefined')
           {
             imageurl = jQuery(html).attr('src');
           }
           var last_index = imageurl.lastIndexOf('/');
            var url_last_part = imageurl.substr(last_index+1);
            if( url_last_part == '' ){
              
              imageurl = jQuery(html).children("img").attr("src");  
            }   
           jQuery("#mwb_tyo_other_setting_upload_logo").val(imageurl);
           jQuery("#mwb_tyo_other_setting_upload_image").attr("src",imageurl);
           jQuery("#mwb_tyo_other_setting_remove_logo").show();
           tb_remove();
        };
        return false;
  });


	jQuery(document.body).on('click','.mwb_delete_costom_order',function(){
		var mwb_action=jQuery(this).data('action');
		var mwb_key=jQuery(this).data('key');
		jQuery.ajax({
			url: global_tyo_admin.ajaxurl,
			type : 'post',
			data:{
				action : 'mwb_mwb_delete_custom_order_status',
				mwb_custom_action : mwb_action,
				mwb_custom_key	: mwb_key,
				nonce : global_tyo_admin.mwb_tyo_nonce,
			},
			success: function(response){
				if(response=='success')
				{
					location.reload();
				}

			}

		});
	});
	jQuery(document).on('click','.activate_button',function(e){
		
		var selectd_template_name=jQuery(this).attr('data-id');
		var activated_value='yes';
		jQuery.ajax({
			url : global_tyo_admin.ajaxurl,
			type : 'post',
			data : {
				action : 'mwb_selected_template',
				selected_button_value : activated_value,
				template_name:selectd_template_name,
				nonce : global_tyo_admin.mwb_tyo_nonce,
			},
			success : function( response ) {
				
				if(response == "success") {
					jQuery("div.mwb_notices_templates_order_tracker").html('<div id="message" class="notice notice-success"><p><strong>'+global_tyo_admin.message_template_activated+'</strong></p></div>').delay(50000).fadeOut(function(){});
					
					location.reload();
				}
			}
		});
	});

	jQuery(document).on('click','#mwb_tyo_preview_first',function(){
		jQuery('#mwb_template_2').show();
		jQuery('#mwb_template_3').hide();
		jQuery('#mwb_template_1').hide();
		jQuery('#mwb_template_4').hide();
		jQuery('#mwb_new_template_1').hide();
		jQuery('#mwb_new_template_2').hide();
		jQuery('#mwb_new_template_3').hide();
	});
	jQuery(document).on('click','#mwb_tyo_preview_second',function(){
		jQuery('#mwb_template_3').show();
		jQuery('#mwb_template_2').hide();
		jQuery('#mwb_template_1').hide();
		jQuery('#mwb_template_4').hide();
		jQuery('#mwb_new_template_1').hide();
		jQuery('#mwb_new_template_2').hide();
		jQuery('#mwb_new_template_3').hide();

	});
	jQuery(document).on('click','#mwb_tyo_preview_third',function(){
		jQuery('#mwb_template_1').show();
		jQuery('#mwb_template_2').hide();
		jQuery('#mwb_template_3').hide();
		jQuery('#mwb_template_4').hide();
		jQuery('#mwb_new_template_1').hide();
		jQuery('#mwb_new_template_2').hide();
		jQuery('#mwb_new_template_3').hide();
	});
	jQuery(document).on('click','#mwb_tyo_preview_fourth',function(){
		jQuery('#mwb_template_4').show();
		jQuery('#mwb_template_1').hide();
		jQuery('#mwb_template_2').hide();
		jQuery('#mwb_template_3').hide();
		jQuery('#mwb_new_template_1').hide();
		jQuery('#mwb_new_template_2').hide();
		jQuery('#mwb_new_template_3').hide();
	});
	jQuery(document).on('click','#mwb_tyo_preview_new_template_1',function(){
		
		jQuery('#mwb_new_template_1').show();
		jQuery('#mwb_template_1').hide();
		jQuery('#mwb_template_2').hide();
		jQuery('#mwb_template_4').hide();
		jQuery('#mwb_template_3').hide();
		jQuery('#mwb_new_template_2').hide();
		jQuery('#mwb_new_template_3').hide();
	});
	jQuery(document).on('click','#mwb_tyo_preview_new_template_2',function(){
		jQuery('#mwb_new_template_2').show();
		jQuery('#mwb_template_1').hide();
		jQuery('#mwb_template_2').hide();
		jQuery('#mwb_template_4').hide();
		jQuery('#mwb_template_3').hide();
		jQuery('#mwb_new_template_1').hide();
		jQuery('#mwb_new_template_3').hide();
		
	});
	jQuery(document).on('click','#mwb_tyo_preview_new_template_3',function(){
		jQuery('#mwb_new_template_3').show();
		jQuery('#mwb_template_1').hide();
		jQuery('#mwb_template_2').hide();
		jQuery('#mwb_template_4').hide();
		jQuery('#mwb_template_3').hide();
		jQuery('#mwb_new_template_1').hide();
		jQuery('#mwb_new_template_2').hide();
	});
	

	jQuery('.hidden_wrapper').hide();
	jQuery(document).on('click','#mwb_tyo_preview_first',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#mwb_tyo_preview_second',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#mwb_tyo_preview_third',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery('.hidden_wrapper').hide();
	jQuery(document).on('click','#mwb_tyo_preview_fourth',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#mwb_tyo_preview_new_template_1',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#mwb_tyo_preview_new_template_3',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#mwb_tyo_preview_new_template_2',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#mwb_template_1',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#mwb_template_2',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#mwb_template_3',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#mwb_template_4',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#mwb_new_template_1',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#mwb_new_template_2',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#mwb_new_template_3',function(){
		jQuery(".hidden_wrapper").hide();
	});


} );


jQuery(document).on('click','input#mwb_mwb_create_role_box',function(){
	jQuery(this).toggleClass('role_box_open');
	jQuery("div#mwb_mwb_create_box").slideToggle();
	if(jQuery(this).hasClass('role_box_open')) {
		jQuery(this).val(global_tyo_admin.mwb_tyo_close_button);
	}
	else {
		jQuery(this).val('Create Custom Order Status');
	}
});


jQuery(document).on('click','input#mwb_mwb_create_custom_order_status',function(){
	jQuery('#mwb_mwb_send_loading').show();
	var mwb_mwb_create_order_status = jQuery('#mwb_mwb_create_order_name').val().trim();
	var mwb_order_image_url = jQuery(document).find('#mwb_tyo_other_setting_upload_logo').val();
	if(mwb_mwb_create_order_status != "" && mwb_mwb_create_order_status != null) 
	{
		if( /^[a-zA-Z0-9- ]*$/.test(mwb_mwb_create_order_status) )
		{
			mwb_mwb_create_order_status = mwb_mwb_create_order_status

			jQuery.ajax({
				url : global_tyo_admin.ajaxurl,
				type : 'post',
				data : {
					action : 'mwb_mwb_create_custom_order_status',
					mwb_mwb_new_role_name : mwb_mwb_create_order_status,
					mwb_custom_order_image_url : mwb_order_image_url,
					nonce : global_tyo_admin.mwb_tyo_nonce,
				},
				success : function( response ) {
					jQuery('#mwb_mwb_send_loading').hide();

					if(response == "success") {
						jQuery('#mwb_tyo_other_setting_upload_logo').val('');
						jQuery('input#mwb_mwb_create_role_box').trigger('click');
						jQuery("div.mwb_notices_order_tracker").html('<div id="message" class="notice notice-success"><p><strong>'+global_tyo_admin.message_success+'</strong></p></div>');
						jQuery('#mwb_mwb_create_order_name').val('');
						location.reload();
					}
					else {
						jQuery("div.mwb_notices_order_tracker").html('<div id="message" class="notice notice-error"><p><strong>'+global_tyo_admin.message_error_save+'</strong></p></div>').delay(2000).fadeOut(function(){});
					}	
				}
			});
		}
		else{
			jQuery('#mwb_mwb_send_loading').hide();
			jQuery("div.mwb_notices_order_tracker").html( '<div id="message" class="notice notice-error"><p><strong>'+global_tyo_admin.message_invalid_input+'</strong></p></div>' ).delay(4000).fadeOut(function(){});
			return;
		}	
	}else{
		jQuery('#mwb_mwb_send_loading').hide();
		jQuery("div.mwb_notices_order_tracker").html( '<div id="message" class="notice notice-error"><p><strong>'+global_tyo_admin.message_empty_data+'</strong></p></div>' ).delay(4000).fadeOut(function(){});
		return;
	}
	jQuery('#mwb_mwb_send_loading').hide();

});

jQuery(document).ready(function(){

	jQuery('#mwb_fedex_userkey').closest('tr').hide();
	jQuery('#mwb_fedex_userpassword').closest('tr').hide();
	jQuery('#mwb_fedex_account_number').closest('tr').hide();
	jQuery('#mwb_fedex_meter_number').closest('tr').hide();
	jQuery('#mwb_tyo_enable_track_order_using_api').closest('tr').hide();
	jQuery('#mwb_tyo_enable_canadapost_tracking').closest('tr').hide();
	jQuery('#mwb_tyo_canadapost_tracking_user_key').closest('tr').hide();
	jQuery('#mwb_tyo_canadapost_tracking_user_password').closest('tr').hide();
	
	jQuery( '#mwb_tyo_enable_third_party_tracking_api' ).on( 'change', function(){
		if ( jQuery( '#mwb_tyo_enable_third_party_tracking_api' ).is( ':checked' ) ) 
		{	
			jQuery('#mwb_fedex_userkey').closest('tr').show();
			jQuery('#mwb_fedex_userpassword').closest('tr').show();
			jQuery('#mwb_fedex_account_number').closest('tr').show();
			jQuery('#mwb_fedex_meter_number').closest('tr').show();
			jQuery('#mwb_tyo_enable_track_order_using_api').closest('tr').show();
			jQuery('#mwb_tyo_enable_canadapost_tracking').closest('tr').show();
			jQuery('#mwb_tyo_canadapost_tracking_user_key').closest('tr').show();
			jQuery('#mwb_tyo_canadapost_tracking_user_password').closest('tr').show();
			jQuery('#mwb_tyo_enable_usps_tracking').closest('tr').show();
			jQuery('#mwb_tyo_usps_tracking_user_key').closest('tr').show();
			jQuery('#mwb_tyo_usps_tracking_user_password').closest('tr').show();
		}
		else
		{
			jQuery('#mwb_fedex_userkey').closest('tr').hide();
			jQuery('#mwb_fedex_userpassword').closest('tr').hide();
			jQuery('#mwb_fedex_account_number').closest('tr').hide();
			jQuery('#mwb_fedex_meter_number').closest('tr').hide();
			jQuery('#mwb_tyo_enable_track_order_using_api').closest('tr').hide();
			jQuery('#mwb_tyo_enable_canadapost_tracking').closest('tr').hide();
			jQuery('#mwb_tyo_canadapost_tracking_user_key').closest('tr').hide();
			jQuery('#mwb_tyo_canadapost_tracking_user_password').closest('tr').hide();
			jQuery('#mwb_tyo_enable_usps_tracking').closest('tr').hide();
			jQuery('#mwb_tyo_usps_tracking_user_key').closest('tr').hide();
			jQuery('#mwb_tyo_usps_tracking_user_password').closest('tr').hide();
		}
	} );

	if ( jQuery( '#mwb_tyo_enable_third_party_tracking_api' ).is( ':checked' ) ) 
		{	
			jQuery('#mwb_fedex_userkey').closest('tr').show();
			jQuery('#mwb_fedex_userpassword').closest('tr').show();
			jQuery('#mwb_fedex_account_number').closest('tr').show();
			jQuery('#mwb_fedex_meter_number').closest('tr').show();
			jQuery('#mwb_tyo_enable_track_order_using_api').closest('tr').show();
			jQuery('#mwb_tyo_enable_canadapost_tracking').closest('tr').show();
			jQuery('#mwb_tyo_canadapost_tracking_user_key').closest('tr').show();
			jQuery('#mwb_tyo_canadapost_tracking_user_password').closest('tr').show();
			jQuery('#mwb_tyo_enable_usps_tracking').closest('tr').show();
			jQuery('#mwb_tyo_usps_tracking_user_key').closest('tr').show();
			jQuery('#mwb_tyo_usps_tracking_user_password').closest('tr').show();
		}
		else
		{
			jQuery('#mwb_fedex_userkey').closest('tr').hide();
			jQuery('#mwb_fedex_userpassword').closest('tr').hide();
			jQuery('#mwb_fedex_account_number').closest('tr').hide();
			jQuery('#mwb_fedex_meter_number').closest('tr').hide();
			jQuery('#mwb_tyo_enable_track_order_using_api').closest('tr').hide();
			jQuery('#mwb_tyo_enable_canadapost_tracking').closest('tr').hide();
			jQuery('#mwb_tyo_canadapost_tracking_user_key').closest('tr').hide();
			jQuery('#mwb_tyo_canadapost_tracking_user_password').closest('tr').hide();
			jQuery('#mwb_tyo_enable_usps_tracking').closest('tr').hide();
			jQuery('#mwb_tyo_usps_tracking_user_key').closest('tr').hide();
			jQuery('#mwb_tyo_usps_tracking_user_password').closest('tr').hide();
		}
	
});

jQuery(document).ready(function(){
	jQuery(document).on('click', '#wot_export_order', function(e){
		e.preventDefault();
		var order_status = jQuery('#wot_select_order_status').val();
		
		jQuery.ajax({
			url:ajax_url,
			type:"POST",
			datatType: 'JSON',
			data: {
				action : 'wps_wot_export_order_using_order_status',
				order_status : order_status,
				nonce : global_tyo_admin.mwb_tyo_nonce,
			},success:function(response){
				var result = JSON.parse(response);
				if( 'success' == result.status ) {
					var filename = result.file_name;
                    var order_data = result.order_data;
					var filename = filename + '.csv';
                    let csvContent = "data:text/csv;charset=utf-8,";
                    order_data.forEach(function(rowArray) {
                       let row = rowArray;
                       csvContent += row + "\r\n";
                             });
                   
                   var encodedUri = encodeURI(csvContent);
                        download(filename, encodedUri);
				}
			
			}	

		});
	});

	function download(filename, text) {
		var element = document.createElement('a');
		element.setAttribute('href', text);
		element.setAttribute('download', filename);
		element.style.display = 'none';
		document.body.appendChild(element);
		// automatically run the click event for anchor tag
		element.click();
   
		document.body.removeChild(element);
		

   }
});


jQuery(document).ready(function($){
	var is_enable_status_icon = global_tyo_admin.is_enable_status_icon;
	let wc = global_tyo_admin.wps_tyo_wc;
	if( 'yes' == is_enable_status_icon ) {
		console.log(jQuery('.wp-list-table tr.status-processing .order_status'));
		// /home/cedcoss/Local Sites/order-tracker/app/public/order_complted_icon.png
		var processing = global_tyo_admin.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/processing1.png';
		var completed = global_tyo_admin.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/deliver1.png';
		var on_hold = global_tyo_admin.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/approved1.png';
		var pending = global_tyo_admin.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/order-pending.jpeg';
		var cancelled = global_tyo_admin.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/cancel1.png';
		var failed = global_tyo_admin.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/order-cancelled.png';
		var refunded = global_tyo_admin.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/revert.png';
		var dispatched = global_tyo_admin.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/dispatch.png';
		var shipped = global_tyo_admin.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/shipped.png';
		var packed = global_tyo_admin.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/order-packed.png';
		 jQuery('.wp-list-table .status' + wc + 'processing .order_status ').html('<mark class="order-status status-processing" ><img src="' + processing + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status' + wc + 'completed .order_status ').html('<mark class="order-status status-completed" ><img src="' + completed + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status' + wc + 'on-hold .order_status ').html('<mark class="order-status status-on-hold" ><img src="' + on_hold + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status' + wc + 'pending .order_status ').html('<mark class="order-status status-pending" ><img src="' + pending + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status' + wc + 'cancelled .order_status ').html('<mark class="order-status status-cancelled" ><img src="' + cancelled + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status' + wc + 'failed .order_status ').html('<mark class="order-status status-failed" ><img src="' + failed + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status' + wc + 'refunded .order_status ').html('<mark class="order-status status-refunded" ><img src="' + refunded + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status' + wc + 'dispatched .order_status ').html('<mark class="order-status status-dispatched" ><img src="' + dispatched + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status' + wc + 'shipped .order_status ').html('<mark class="order-status status-shipped" ><img src="' + shipped + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status' + wc + 'packed .order_status ').html('<mark class="order-status status-packed" ><img src="' + packed + '" height="50" width="50"></mark> ');
	
		 var custom_url = global_tyo_admin.custom_order_status_url;
		 if( custom_url != '' ){
			
			 $.each( custom_url, function( key, value ) {
				jQuery('.wp-list-table .status-wc-' + key + ' .order_status ').html('<mark class="order-status status-'+ key +'" ><img src="' + value + '" height="50" width="50"></mark> ');
			  });
			
		 }
	}
} );
