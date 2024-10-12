var ajaxurl = mwb_tyo_new_param.adminajaxurl;
var nonce = mwb_tyo_new_param.ajax_nonce;
jQuery(document).ready(function(){
	
	jQuery('#mwb_tyo_selected_address').select2({
		placeholder: mwb_tyo_new_param.selec_address_placeholder,
		});
	jQuery('#mwb_tyo_custom_shipping_cities').select2();
	jQuery('#mwb_tyo_add_address').on('click',function(e){
		e.preventDefault();
		var mwb_tyo_address_collection = jQuery('#mwb_tyo_track_order_addresses').val();
		if(mwb_tyo_address_collection != ''){
			jQuery.ajax({
				url : ajaxurl,
				type : 'POST',
				cache : false,
				dataType: 'json',
				data :{
					action : 'mwb_tyo_insert_address_for_tracking',
					nonce : nonce,
					mwb_tyo_addresses : mwb_tyo_address_collection 
				},success : function(response){
					window.location.reload();
					jQuery('.mwb_tyo_empty_adrress_validation').html('<span>'+mwb_tyo_new_param.address_validation_success+'</span>');
				},complete : function(){
					jQuery('#mwb_tyo_track_order_addresses').val('');
				}
			});
		}
		else{
			jQuery('.mwb_tyo_empty_adrress_validation').html('<span>'+mwb_tyo_new_param.address_validation+'</span>');
		}
	});
});