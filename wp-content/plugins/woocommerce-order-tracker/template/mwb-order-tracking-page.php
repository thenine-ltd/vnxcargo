<?php
/**
 * Guest track order page.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/template
 *  
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );


/**
 * Add content.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_before_main_content' );

$flag = false;
$value_check = isset( $_POST['mwb_track_package_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_track_package_nonce_name'] ) ) : '';
wp_verify_nonce( $value_check, 'mwb_track_package_nonce' );
if ( isset( $_POST['mwb_track_package'] ) ) {
	$flag = true;
	$mwb_user_order_id = isset( $_POST['mwb_user_order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_user_order_id'] ) ) : '';
	update_option( 'mwb_tyo_user_order_id', $mwb_user_order_id );
}

?>
<div>
	<legend><h3><?php esc_html_e( 'Shipment Tracking', 'woocommerce-order-tracker' ); ?></h3></legend>

	<form action="" method="POST" role="form">

		<div class="form-group">
			<label for=""><?php esc_html_e( ' Enter Order Id ', 'woocommerce-order-tracker' ); ?></label>
			<input type="text" class="form-control" id="mwb_tyo_order_id" name="mwb_user_order_id"  placeholder=<?php esc_attr_e('Enter Order id', 'woocommerce-order-tracker');?> >
		</div>
		<input type="hidden" name="mwb_track_package_nonce_name" value="<?php wp_create_nonce( 'mwb_track_package_nonce' ); ?>">
		<button type="submit" class="btn btn-primary mwb_tyo_button_track" name="mwb_track_package"><?php esc_html_e( 'Submit', 'woocommerce-order-tracker' ); ?></button>
	</form>
</div>



<?php
if ( $flag ) {
	$mwb_tyo_selected_shipping_method = wps_order_tracker_get_meta_data( $mwb_user_order_id, 'mwb_tyo_selected_shipping_service', true );

	if ( isset( $mwb_tyo_selected_shipping_method ) && ( 'canada_post' == $mwb_tyo_selected_shipping_method ) ) {

		include_once MWB_TRACK_YOUR_ORDER_PATH . 'includes/class-mwb-track-your-order-with-fedex.php';
		$request = new MWB_Track_Your_Order_With_FedEx();
		$request->canadapost_request();
	} elseif ( isset( $mwb_tyo_selected_shipping_method ) && ( 'fedex' == $mwb_tyo_selected_shipping_method ) ) {
		include_once MWB_TRACK_YOUR_ORDER_PATH . 'includes/class-mwb-track-your-order-with-fedex.php';
		$request = new MWB_Track_Your_Order_With_FedEx();
		$request->fedex_request( $mwb_user_order_id );
	} elseif ( isset( $mwb_tyo_selected_shipping_method ) && ( 'usps' == $mwb_tyo_selected_shipping_method ) ) {
		include_once MWB_TRACK_YOUR_ORDER_PATH . 'includes/class-mwb-track-your-order-with-fedex.php';
		$uspsrequest = new MWB_Track_Your_Order_With_FedEx();
		$uspsrequest->mwb_tyo_usps_tracking_request( $mwb_user_order_id );
	} else {
		?>
			<div class="mwb_tyo_shipment_tracking_warning_msg">
				<h4><?php esc_html_e( 'Service Not Available', 'woocommerce-order-tracker' ); ?></h4>	
			</div>
		<?php
	}
}


/**
 * Add content.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
?>
