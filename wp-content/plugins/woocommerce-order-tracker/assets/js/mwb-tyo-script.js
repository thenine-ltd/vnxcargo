/**
* Script for the FRONTEND 
*  
* @link http://www.wpswings.com/
*/

jQuery(window).bind("load", function() {
	jQuery('#mwb_placed_order > img').show(900);
	jQuery('#mwb_approval_order > img').show(1500);
	jQuery('#mwb_processing_order > img').show(2100);
	jQuery('#mwb_shipping_order > img').show(2700);
	jQuery('#mwb_delivered_order > img').show(3300);
	jQuery('#mwb_cancelled_order > img').show(3900);
});
jQuery( document ).ready( function()
{
	
	jQuery("body").on('click','#mwb_live_tracking',function(e){
         e.preventDefault(); 
         var number=jQuery(this).text();
         var mwb_tyo_url = jQuery(this).attr('href'); 
         window.open(mwb_tyo_url+number, '_blank');
	});
	jQuery( document ).on( 'mouseover', '.mwb-circle', function(){

		var status = jQuery(this).attr( 'data-status' );
		if (status != '' ) 
		{
			var status_msg = '<h4>'+status+'</h4>';
			jQuery( '.mwb-tyo-mwb-delivery-msg' ).html( status_msg );
			jQuery( '.mwb-tyo-mwb-delivery-msg' ).show();
		}
	} );
	jQuery( document ).on( 'mouseout', '.mwb-circle', function(){

		jQuery( '.mwb-tyo-mwb-delivery-msg' ).hide();

	} );
	jQuery('a.thickbox').each( function( key, value ){
		var link = jQuery( this ).attr( 'href' );
		var order_id = link.substring( link.lastIndexOf( '/' )+1, link.indexOf( '?' ) );
		
	} );

	jQuery('.woocommerce-orders-table__cell').on('click','a.thickbox',function(){
		var mwb_tyo_iframe_obj = jQuery(document).find('#TB_window');
	});
	var completed_condition_data = jQuery('.mwb_completed_condition > img').attr('data-completed_data');
	var cancelled_condition_data = jQuery('.mwb_cancelled_condition > img').attr('data-cancelled_data');
	if(completed_condition_data == 1)
	{
		jQuery('.mwb_tyo_header-wrapper').addClass('mwb_tyo_completed_condition');
	}
	if(cancelled_condition_data == 1)
	{
		jQuery('.mwb_tyo_header-wrapper').addClass('mwb_tyo_completed_condition');
	}
	jQuery('#mwb_order_detail_section').addClass('mwb_active_tab');
	jQuery('#mwb_progress_bar').hide();
	jQuery('#mwb_approval').hide();
	jQuery('#mwb_process').hide();
	jQuery('#mwb_ship').hide();
	jQuery('#mwb_delivery').hide();
	jQuery('#mwb_tyo_enhanced_customer_notice_template2').hide();
	jQuery('#mwb_order_track_section').on('click',function(){
		jQuery('#mwb_order_detail_section').removeClass();
		jQuery('#mwb_order_track_customer_notice').removeClass();
		jQuery('#mwb_order_track_section').addClass('mwb_active_tab');
		jQuery('#mwb_progress_bar').show(1700);
		jQuery('#mwb_approval').show(2000);
		jQuery('#mwb_process').show(2000);
		jQuery('#mwb_ship').show(2000);
		jQuery('#mwb_delivery').show(2000);
		
		var i = 1;
		jQuery('.mwb_progress .circle_tyo_mwb').removeClass().addClass('circle_tyo_mwb');
		jQuery('.mwb_progress .bar_tyo_mwb').removeClass().addClass('bar_tyo_mwb');
		setInterval(function() {

			var z = jQuery('.hidden_value').val();
			if(i <= jQuery('.hidden_value').val())
			{
				jQuery('.mwb_progress .circle_tyo_mwb:nth-of-type(' + i + ')').addClass('active_tyo_mwb');

				jQuery('.mwb_progress .circle_tyo_mwb:nth-of-type(' + (i-1) + ')').removeClass('active_tyo_mwb').addClass('mwb_done');

				jQuery('.mwb_progress .circle_tyo_mwb:nth-of-type(' + (i-1) + ') .label').html('&#10003;');

				jQuery('.mwb_progress .bar_tyo_mwb:nth-of-type(' + (i-1) + ')').addClass('active_tyo_mwb');

				jQuery('.mwb_progress .bar_tyo_mwb:nth-of-type(' + (i-2) + ')').removeClass('active_tyo_mwb').addClass('mwb_done');

				i++;

				if (i==0) {
					jQuery('.mwb_progress .bar_tyo_mwb').removeClass().addClass('bar_tyo_mwb');
					jQuery('.mwb_progress div.circle_tyo_mwb').removeClass().addClass('circle_tyo_mwb');
					i = 1;
				}
			}
		}, 1200);
		jQuery('#mwb_product').hide();
		jQuery('#mwb_order').hide();
		jQuery('#mwb_tyo_enhanced_customer_notice_template2').hide();
	});
	jQuery('#mwb_order_detail_section').on('click',function(){
		jQuery('#mwb_order_detail_section').addClass('mwb_active_tab');
		jQuery('#mwb_order_track_customer_notice').removeClass();
		jQuery('#mwb_order_track_section').removeClass();
		jQuery('#mwb_product').show(1700);
		jQuery('#mwb_order').show(1700);
		jQuery('#mwb_progress_bar').hide();
		jQuery('#mwb_approval').hide();
		jQuery('#mwb_process').hide();
		jQuery('#mwb_ship').hide();
		jQuery('#mwb_delivery').hide();
		jQuery('#mwb_tyo_enhanced_customer_notice_template2').hide();
	});
	jQuery('#mwb_order_track_customer_notice').on('click',function(){
		jQuery('#mwb_order_track_customer_notice').addClass('mwb_active_tab');
		jQuery('#mwb_order_detail_section').removeClass();
		jQuery('#mwb_order_track_section').removeClass();
		jQuery('#mwb_tyo_enhanced_customer_notice_template2').show(1700);
		jQuery('#mwb_progress_bar').hide();
		jQuery('#mwb_approval').hide();
		jQuery('#mwb_process').hide();
		jQuery('#mwb_ship').hide();
		jQuery('#mwb_delivery').hide();
		jQuery('#mwb_product').hide();
		jQuery('#mwb_order').hide();
	});
	
	jQuery('.mwb_product-details-section ').hide();
	jQuery('.mwb_product-details-section mwb-table-responsive').hide();
	jQuery('#mwb_shop_location').hide();

	jQuery('#get_current_shop_location').click(function(){
		jQuery('#mwb_shop_location').show(1700);
		jQuery('.mwb_header-wrapper').hide();
		jQuery('.mwb_order_tracker_content').hide();
		jQuery('.mwb_product-details-section ').hide();
		jQuery('#mwb_tyo_enhanced_customer_notice_template4').hide();
		jQuery('.mwb_product-details-section mwb-table-responsive').hide();
		jQuery('#mwb_tyo_track_order_status').removeClass();
		jQuery('#mwb_tyo_track_order_details').removeClass();
		jQuery('#get_current_shop_customer_notice').removeClass();
		jQuery('#get_current_shop_location').addClass('mwb_tyo_active');
	});
	jQuery('#mwb_tyo_track_order_details').click(function(){
		jQuery('.mwb_product-details-section ').show(1700);
		jQuery('#mwb_shop_location').hide();
		jQuery('.mwb_header-wrapper').hide();
		jQuery('#mwb_tyo_enhanced_customer_notice_template4').hide();
		jQuery('.mwb_order_tracker_content').hide();
		jQuery('#mwb_tyo_track_order_status').removeClass();
		jQuery('#get_current_shop_location').removeClass();
		jQuery('#get_current_shop_customer_notice').removeClass();
		jQuery('#mwb_tyo_track_order_details').addClass('mwb_tyo_active');
	});
	jQuery('#mwb_tyo_track_order_status').click(function(){
		jQuery('.mwb_header-wrapper').show(1700);
		jQuery('.mwb_order_tracker_content').show(1700);
		jQuery('.mwb_product-details-section ').hide();
		jQuery('#mwb_tyo_enhanced_customer_notice_template4').hide();
		jQuery('#mwb_shop_location').hide();
		jQuery('.mwb_product-details-section').hide();
		jQuery('#mwb_tyo_track_order_details').removeClass();
		jQuery('#get_current_shop_location').removeClass();
		jQuery('#get_current_shop_customer_notice').removeClass();
		jQuery('#mwb_tyo_track_order_status').addClass('mwb_tyo_active');
	});
	jQuery('#get_current_shop_customer_notice').click(function() {
		jQuery('#mwb_tyo_enhanced_customer_notice_template4').show(1700);
		jQuery('.mwb_product-details-section').hide();
		jQuery('#mwb_shop_location').hide();
		jQuery('.mwb_header-wrapper').hide();
		jQuery('.mwb_order_tracker_content').hide();
		jQuery('#mwb_tyo_track_order_details').removeClass();
		jQuery('#get_current_shop_location').removeClass();
		jQuery('#mwb_tyo_track_order_status').removeClass();
		jQuery('#get_current_shop_customer_notice').addClass('mwb_tyo_active');
	});
});


jQuery('.mwb-tooltip-template-3').hide();
jQuery('.mwb-tooltip-template-fedex').hide();
jQuery('.mwb-tooltip-canadapost').hide();
jQuery('.mwb-tooltip-usps').hide();


jQuery(window).load(function(){

	var div_height = jQuery('.mwb-tooltip').height()*2;
	var div_height_ok = jQuery('.mwb-tooltip').height();
	var progress_width_template1 = jQuery('.mwb-tyo-outer').attr('data-progress');
	var progressBarHeight = jQuery('.mwb-tyo-inner').attr('data-progress-bar-height');
	var progress_bar_final = 0;
	if(progressBarHeight > 5)
	{
		progress_bar_final = (progressBarHeight*div_height)+15;
	}
	else
	{
		progress_bar_final = (progressBarHeight*div_height);
	}
	var highlighted_point = parseInt(progress_bar_final/progressBarHeight);
	var i = 1;
	var new_point = highlighted_point-120;
	jQuery('.mwb-tyo-outer').css('height',progress_bar_final+'px','important');

	template1_start(new_point,highlighted_point,progress_bar_final,i);

	var tmplate_no = jQuery('.mwb-tyo-outer-template-2').attr('data-template_no');
	var tmplate3_no = jQuery('.mwb-tyo-inner-template-3').attr('data-template_no');
	
	
	if(tmplate_no == 2)
	{
		var progress_width_template2 = jQuery('.mwb-tyo-outer-template-2').attr('data-progress');
		var mwb_content_height = 0;
		// get height of each div 
		jQuery('.mwb-tooltip-template-2').each(function(){
			mwb_content_height += jQuery(this).height();
		});
		// calculate total height of progress bar
		var div_height_template2 = mwb_content_height+(progress_width_template2*60);
		// calculate pixels to show div's
		var progress_height = parseInt(div_height_template2/progress_width_template2);
		var j=1;
		var tmp_new_height = progress_height-110;
		// apply height to progress bar
		jQuery('.mwb-tyo-outer-template-2').css('height',div_height_template2+'px','important');
		// function call 
		if(tmp_new_height<0)
		{
	      tmp_new_height=18;
		}
		template2_start(tmp_new_height,progress_height,div_height_template2,j);
	}

	if(tmplate3_no == 3)
	{
		// get height of each div 
		var div_height_template3 = jQuery('.mwb-tooltip-template-3').height();
		var progress_width = jQuery('.mwb-tyo-inner-template-3').attr('data-progress');	
		var progress_total_height = jQuery('.mwb-tyo-inner-template-3').attr('data-progress-bar-height');	
		var outer_height = jQuery('.mwb-tyo-outer-template-3').height();
		// calculate total division of progress bar
		var devided_part = outer_height/progress_total_height;
		// calculate total height of progress bar
		var total_progress_height = parseInt((devided_part*progress_width));
		
		if(progress_width > 1){
			var updated_margin = (54*progress_width);
		}
		// function call 
		template3_start(outer_height,devided_part,progress_width,div_height_template3,total_progress_height);
	}
});

// template-1 js function definition
function template1_start(new_point,highlighted_point,progress_bar_final,i)
{
	var height = jQuery('.mwb-tyo-inner').height();
	var newheight = height+1;
	var originalheight = newheight+'px';
	jQuery('.mwb-tyo-inner').height(originalheight);
	if(jQuery('.mwb-tyo-inner').height() == new_point )
	{
		jQuery('#mwb-tooltip_'+i).addClass('mwb_tyo_highlighted_point');
		new_point += highlighted_point;
		i++;
	}
	
	if(jQuery('.mwb-tyo-inner').height() <= progress_bar_final)
	{
		// recursive function call
		setTimeout(function() {
			template1_start(new_point,highlighted_point,progress_bar_final,i);
		},7);
	}

}

// template-2 js function definition
function template2_start(tmp_new_height,progress_height,progress_width_template2_final,j)
{  
	var template2_height = jQuery('.mwb-tyo-inner-template-2').height();
	var template2_newheight = template2_height+1;
	var template2_original_height = template2_newheight+'px';
	jQuery('.mwb-tyo-inner-template-2').height(template2_original_height);
	if(jQuery('.mwb-tyo-inner-template-2').height() == tmp_new_height )
	{
		jQuery('#mwb-temp-tooltip_'+j).addClass('mwb_tyo_temp_highlighted_point');
		tmp_new_height += progress_height;
		j++;
	}
	if(jQuery('.mwb-tyo-inner-template-2').height() <= progress_width_template2_final)
	{
		setTimeout(function() {
			template2_start(tmp_new_height,progress_height,progress_width_template2_final,j);
		},7);
	}
}

// template-3 js function definition
function template3_start(outer_height,devided_part,progress_width,div_height_template3,total_progress_height)
{
	var div_top_margin = 0;
	var template3_height = jQuery('.mwb-tyo-inner-template-3').height();
	var template3_newheight = template3_height+1;
	var template3_original_height = template3_newheight+'px';
	jQuery('.mwb-tyo-inner-template-3').height(template3_original_height);
	
	if(jQuery('.mwb-tyo-inner-template-3').height() <= total_progress_height)
	{
		div_top_margin = jQuery('.mwb-tyo-inner-template-3').height();
		jQuery('.mwb-tooltip-template-3').css('margin-top',(div_top_margin-30)+'px','important');
		jQuery('#mwb-temp3-tooltip').delay(1300*progress_width).fadeIn(1000);
	}
	if(jQuery('.mwb-tyo-inner-template-3').height() <= total_progress_height)
	{
		setTimeout(function() {
			template3_start(outer_height,devided_part,progress_width,div_height_template3,total_progress_height);
		},17);
	}
}

jQuery(document).ready(function(){
	
	var div_height_template2 = jQuery('.mwb-tooltip-template-fedex').height()+60;
	var progress_width_template2 = jQuery('.mwb-tyo-outer-template-fedex').attr('data-progress');
	var progress_width_template2_final = (progress_width_template2*div_height_template2)+(140);
	jQuery('.mwb-tyo-outer-template-fedex').css('height',progress_width_template2_final+'px','important');
	jQuery('.mwb-tyo-inner-template-fedex').animate(
	{
		height : progress_width_template2_final+'px'
	},12000);	

	jQuery('.mwb-tooltip-template-fedex').each(function(i,v){
		jQuery(this).delay(1500*i).fadeIn(800);
	});

	var div_height_canadapost = jQuery('.mwb-tooltip-canadapost').height()+60;
	var div_height_ok_canadapost = jQuery('.mwb-tooltip-canadapost').height();
	var progress_width_template1_canadapost = jQuery('.mwb-tyo-outer-template-canadapost').attr('data-progress');
	var progressBarHeight_canadapost = jQuery('.mwb-tyo-outer-template-canadapost').attr('data-progress-bar-height');
	var progress_bar_final_canadapost = (progressBarHeight_canadapost*div_height_canadapost)+(div_height_canadapost+170);

	jQuery('.mwb-tyo-outer-template-canadapost').css('height',progress_bar_final_canadapost+'px','important');

	jQuery('.mwb-tyo-inner-template-canadapost').animate(
	{
		height : progress_bar_final_canadapost+'px'
	},14000);	

	jQuery('.mwb-tooltip-canadapost').each(function(ind,val){
		jQuery(this).delay(1100*ind).fadeIn(800);
	});


	var div_height_usps = jQuery('.mwb-tooltip-usps').height()+60;
	var div_height_ok_usps = jQuery('.mwb-tooltip-usps').height();
	var progress_width_template1_usps = jQuery('.mwb-tyo-outer-template-usps').attr('data-progress');
	var progressBarHeight_usps = jQuery('.mwb-tyo-outer-template-usps').attr('data-progress-bar-height');
	var progress_bar_final_usps = (progressBarHeight_usps*div_height_usps)+(div_height_usps+200);

	jQuery('.mwb-tyo-outer-template-usps').css('height',progress_bar_final_usps+'px','important');

	jQuery('.mwb-tyo-inner-template-usps').animate(
	{
		height : progress_bar_final_usps+'px'
	},14000);	

	jQuery('.mwb-tooltip-usps').each(function(inte,va){
		jQuery(this).delay(1100*inte).fadeIn(800);
	});
	
});


jQuery(window).load(function(){

	jQuery(document).on('click','.mwb_tyo_17track',function(e){
		e.preventDefault();
		var num = jQuery(this).text();
		YQV5.trackSingleF2({
			YQ_ElementId:"YQElem1",
			YQ_Width:470,
			YQ_Height:560,
			YQ_Fc:"0",
			YQ_Lang:"en",
			YQ_Num:""+num+""
		});
	});
	jQuery(document).on('click','.mwb_tyo_enhanced_17track',function(){
		
		var num = jQuery(document).find('#mwb_tyo_enhanced_trackingid').val();
			YQV5.trackSingleF2({
				YQ_ElementId:"YQElem2",
				YQ_Width:470,
				YQ_Height:560,
				YQ_Fc:"0",
				YQ_Lang:"en",
				YQ_Num:""+num+""
			});
	 
	});
});




