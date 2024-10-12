<?php
/**
 * Track order template.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/admin
 *  
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$activated_template = get_option( 'mwb_tyo_activated_template', '' );
$template1 = __( 'Activate', 'woocommerce-order-tracker' );
$template2 = __( 'Activate', 'woocommerce-order-tracker' );
$template3 = __( 'Activate', 'woocommerce-order-tracker' );
$template4 = __( 'Activate', 'woocommerce-order-tracker' );
$new_template1 = __( 'Activate', 'woocommerce-order-tracker' );
$new_template2 = __( 'Activate', 'woocommerce-order-tracker' );
$new_template3 = __( 'Activate', 'woocommerce-order-tracker' );

if ( 'template1' == $activated_template ) {
	$template1 = __( 'Activated', 'woocommerce-order-tracker' );
} else if ( 'template2' == $activated_template ) {
	$template2 = __( 'Activated', 'woocommerce-order-tracker' );
} else if ( 'template3' == $activated_template ) {
	$template3 = __( 'Activated', 'woocommerce-order-tracker' );
} else if ( 'template4' == $activated_template ) {
	$template4 = __( 'Activated', 'woocommerce-order-tracker' );
} else if ( 'newtemplate1' == $activated_template ) {
	$new_template1 = __( 'Activated', 'woocommerce-order-tracker' );
} else if ( 'newtemplate2' == $activated_template ) {
	$new_template2 = __( 'Activated', 'woocommerce-order-tracker' );
} else if ( 'newtemplate3' == $activated_template ) {
	$new_template3 = __( 'Activated', 'woocommerce-order-tracker' );
}
?>
<h2><?php esc_html_e( 'Different templates for your order tracking', 'woocommerce-order-tracker' ); ?></h2>
<div class="mwb_notices_templates_order_tracker">
</div>
<div class="mwb_tyo_template">
	<div id="mwb_tyo_default_template">
		<div class="mwb_tyo_template_img_wrap">
			<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/ot02.png'; ?>" class="mwb_tyo_template_view">
		</div>
		<div class="mwb_tyo_temlate_main_wrapper">
			<div class="mwb_tyo_temlate_name">
				<h4><?php esc_html_e( 'Template-1', 'woocommerce-order-tracker' ); ?></h4>
			</div>
			<div class="mwb_tyo_temlate_wrapper">
				<input type="button" class="activate_button" id="mwb_tyo_activate_third" value=<?php echo esc_attr( $template1 ); ?> data-id='template1'>
				<input type="button" class="preview_button" id="mwb_tyo_preview_third" value=<?php esc_attr_e( 'Preview', 'woocommerce-order-tracker' ); ?> >
			</div>
		</div>
	</div>
	<div id="mwb_tyo_first_template">
		<div class="mwb_tyo_template_img_wrap">
			
			<img  src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/ot03.png'; ?>" class="mwb_tyo_template_view">
		</div>
		<div class="mwb_tyo_temlate_main_wrapper">
			<div class="mwb_tyo_temlate_name">
				<h4><?php esc_html_e( 'Template-2', 'woocommerce-order-tracker' ); ?></h4>
			</div>
			<div class="mwb_tyo_temlate_wrapper">
				<input type="button" class="activate_button" id="mwb_tyo_activate_first" value=<?php echo esc_attr( $template2 ); ?> data-id='template2'>
				<input type="button" class="preview_button" id="mwb_tyo_preview_first" value=<?php esc_attr_e( 'Preview', 'woocommerce-order-tracker' ); ?> >
			</div>
		</div>
	</div>
	<div id="mwb_tyo_second_template">
		<div class="mwb_tyo_template_img_wrap">
			<img  src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/ot01.jpg'; ?>" class="mwb_tyo_template_view">
		</div>
		<div class="mwb_tyo_temlate_main_wrapper">
			<div class="mwb_tyo_temlate_name">
				<h4><?php esc_html_e( 'Template-3', 'woocommerce-order-tracker' ); ?></h4>
			</div>
			<div class="mwb_tyo_temlate_wrapper">
				<input type="button" class="activate_button" id="mwb_tyo_activate_second" value=<?php echo esc_attr( $template3 ); ?> data-id='template3'>
				<input type="button" class="preview_button" id="mwb_tyo_preview_second" value=<?php esc_attr_e( 'Preview', 'woocommerce-order-tracker' ); ?> >
			</div>
		</div>
	</div>
	<div id="mwb_tyo_fourth_template">
		<div class="mwb_tyo_template_img_wrap">
			<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/ot04.jpg'; ?>" class="mwb_tyo_template_view">
		</div>
		<div class="mwb_tyo_temlate_main_wrapper">
			<div class="mwb_tyo_temlate_name">
				<h4><?php esc_html_e( 'Template-4', 'woocommerce-order-tracker' ); ?></h4>
			</div>
			<div class="mwb_tyo_temlate_wrapper">
				<input type="button" class="activate_button" id="mwb_tyo_activate_fourth" value=<?php echo esc_attr( $template4 ); ?> data-id='template4'>
				<input type="button" class="preview_button" id="mwb_tyo_preview_fourth" value=<?php esc_attr_e( 'Preview', 'woocommerce-order-tracker' ); ?> >
			</div>
		</div>
	</div>
	<div id="mwb_tyo_new_template_1">
		<div class="mwb_tyo_template_img_wrap">
			<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/NOt01.jpg'; ?>" class="mwb_tyo_template_view">
		</div>
		<div class="mwb_tyo_temlate_main_wrapper">
			<div class="mwb_tyo_temlate_name">
				<h4><?php esc_html_e( 'New-Template-1', 'woocommerce-order-tracker' ); ?></h4>
			</div>
			<div class="mwb_tyo_temlate_wrapper">
				<input type="button" class="activate_button" id="mwb_tyo_activate_new_template_1" value=<?php echo esc_html( $new_template1 ); ?> data-id='newtemplate1'>
				<input type="button" class="preview_button" id="mwb_tyo_preview_new_template_1" value=<?php esc_attr_e( 'Preview', 'woocommerce-order-tracker' ); ?> >
			</div>
		</div>
	</div>
	<div id="mwb_tyo_new_template_2">
		<div class="mwb_tyo_template_img_wrap">
			<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/Ot02.jpg'; ?>" class="mwb_tyo_template_view">
		</div>
		<div class="mwb_tyo_temlate_main_wrapper">
			<div class="mwb_tyo_temlate_name">
				<h4><?php esc_html_e( 'New-Template-2', 'woocommerce-order-tracker' ); ?></h4>
			</div>
			<div class="mwb_tyo_temlate_wrapper">
				<input type="button" class="activate_button" id="mwb_tyo_activate_new_template_2" value=<?php echo esc_attr( $new_template2 ); ?> data-id='newtemplate2'>
				<input type="button" class="preview_button" id="mwb_tyo_preview_new_template_2" value=<?php esc_attr_e( 'Preview', 'woocommerce-order-tracker' ); ?> >
			</div>
		</div>
	</div>
	<div id="mwb_tyo_new_template_3">
		<div class="mwb_tyo_template_img_wrap">
			<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/Ot03.jpg'; ?>" class="mwb_tyo_template_view">
		</div>
		<div class="mwb_tyo_temlate_main_wrapper">
			<div class="mwb_tyo_temlate_name">
				<h4><?php esc_html_e( 'New-Template-3', 'woocommerce-order-tracker' ); ?></h4>
			</div>
			<div class="mwb_tyo_temlate_wrapper">
				<input type="button" class="activate_button" id="mwb_tyo_activate_new_template_3" value=<?php echo esc_html( $new_template3 ); ?> data-id='newtemplate3'>
				<input type="button" class="preview_button" id="mwb_tyo_preview_new_template_3" value=<?php esc_attr_e( 'Preview', 'woocommerce-order-tracker' ); ?> >
			</div>
		</div>
	</div>
</div>
<div class="hidden_wrapper">
	<div id="mwb_template_2" class="mwb_hide_template" >
		<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/ot02.png'; ?>">
	</div>
	<div id="mwb_template_3" class="mwb_hide_template">
		<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/ot03.png'; ?>">
	</div>
	<div id="mwb_template_1" class="mwb_hide_template">
		<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/ot01.jpg'; ?>">
	</div>
	<div id="mwb_template_4" class="mwb_hide_template">
		<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/ot04.jpg'; ?>">
	</div>
	<div id="mwb_new_template_1" class="mwb_hide_template">
		<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/NOt01.jpg'; ?>">
	</div>
	<div id="mwb_new_template_2" class="mwb_hide_template">
		<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/Ot02.jpg'; ?>">
	</div>
	<div id="mwb_new_template_3" class="mwb_hide_template">
		<img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/Ot03.jpg'; ?>">
	</div>
	
</div>
