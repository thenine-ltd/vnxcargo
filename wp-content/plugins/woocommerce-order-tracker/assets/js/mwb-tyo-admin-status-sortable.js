
var ajaxurl = mwb_tyo_reorder_param.adminajaxurl;
var nonce = mwb_tyo_reorder_param.admin_nonce;
jQuery(window).load(function(){
	
	jQuery('ul.select2-selection__rendered').sortable({

		containment: 'parent',
		update: function() {
			var reorder_val = orderSortedValues();
						var mwb_tyo_reorder_status = [];
			for(var j in reorder_val){
				jQuery('#mwb_tyo_new_settings_custom_statuses_for_order_tracking option').each(function(e){
					
					if(reorder_val[j] == jQuery(this).text()){
						mwb_tyo_reorder_status.push(jQuery(this).attr('value'));
					}
				});

				
			}
			jQuery.ajax({
				url: ajax_url,
				cache: false,
				type: "POST",
				data: {
					action: 'mwb_tyo_reorder_order_status',				
					mwb_wot_selected_method: mwb_tyo_reorder_status,
					nonce : nonce,

				},success:function(response) {
					window.location.reload();
				}
			});
		}
	});	
});
function orderSortedValues(){
		
	var mwb_tyo_new_order_status_position = [];

	jQuery('li.select2-selection__choice').each(function(e){
		mwb_tyo_new_order_status_position.push(jQuery(this).attr('title'));

	});
	return mwb_tyo_new_order_status_position;
}


jQuery(document).ready(function(){
	
	var mwb_tyo_first_select_status = [];
	if(mwb_tyo_reorder_param.default_enable_statuses == 0)
	{
		jQuery('#mwb_tyo_new_settings_custom_statuses_for_order_tracking option').each(function(e){
			if(jQuery('#mwb_tyo_new_settings_custom_statuses_for_order_tracking option:selected'))
			{
				jQuery('#mwb_tyo_new_settings_custom_statuses_for_order_tracking option[value="'+jQuery(this).val()+'"]').attr("selected","selected");	
				mwb_tyo_first_select_status.push(jQuery(this).val());
				jQuery.ajax({
					url: ajax_url,
					cache: false,
					type: "POST",
					data: {
						action: 'mwb_tyo_first_loading_order_status',				
						mwb_first_selected_all_status: mwb_tyo_first_select_status,
						nonce : nonce,

					},success:function(response) {
					}
				});
			}
		});
	}
	if(mwb_tyo_reorder_param.after_save_statuses == 0)
	{
		jQuery('#mwb_tyo_new_custom_statuses_for_order_tracking option').each(function(e){
			if(jQuery('#mwb_tyo_new_custom_statuses_for_order_tracking option:selected'))
			{
				jQuery('#mwb_tyo_new_custom_statuses_for_order_tracking option[value="'+jQuery(this).val()+'"]').attr("selected","selected");
			}

		});
	}
});