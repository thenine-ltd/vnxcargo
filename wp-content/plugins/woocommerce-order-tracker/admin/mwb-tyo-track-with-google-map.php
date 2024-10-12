<?php
/**
 * Tracking with google map.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/admin
 *  
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( isset( $_POST['save'] ) ) {
	$mwb_tyo_enable_google_api_settings = isset( $_POST['mwb_tyo_trackorder_with_google_map'] ) ? 1 : 0;
	$mwb_tyo_enable_google_api_key = isset( $_POST['mwb_tyo_track_order_google_map_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_track_order_google_map_api_key'] ) ) : '';
	$mwb_tyo_order_origin_address = isset( $_POST['mwb_tyo_track_order_production_address'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_track_order_production_address'] ) ) : '';
	$mwb_tyo_all_selected_address = isset( $_POST['mwb_tyo_selected_address'] ) ? map_deep( wp_unslash( $_POST['mwb_tyo_selected_address'] ), 'sanitize_text_field' ) : array();
	$mwb_typ_order_gone_through_address = isset( $_POST['mwb_tyo_track_order_addresses'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_track_order_addresses'] ) ) : '';

	update_option( 'mwb_tyo_google_api_settings', $mwb_tyo_enable_google_api_settings );
	update_option( 'mwb_tyo_google_api_key', $mwb_tyo_enable_google_api_key );
	update_option( 'mwb_tyo_order_production_address', $mwb_tyo_order_origin_address );
	update_option( 'mwb_tyo_all_tracking_address', $mwb_tyo_all_selected_address );
	update_option( 'mwb_tyo_track_order_addresses', $mwb_typ_order_gone_through_address );
}
?>
<div class="mwb_tyo_track_order_with_google_map">
	<?php
	$mwb_tyo_enable_google_settings  = get_option( 'mwb_tyo_google_api_settings', false );
	$mwb_tyo_google_api_key  = get_option( 'mwb_tyo_google_api_key', false );
	$mwb_tyo_order_production_add  = get_option( 'mwb_tyo_order_production_address', false );
	?>
	<div class="mwb_tyo_track_order_enable_google_map">
		<div class="mwb_tyo_google_details">
			<label><?php esc_html_e( 'Enable Google Map For Tracking', 'woocommerce-order-tracker' ); ?></label>
		</div>
		<div class="mwb_tyo_field_details">
			<input type="checkbox" name="mwb_tyo_trackorder_with_google_map" id="mwb_tyo_trackorder_with_google_map" <?php checked( $mwb_tyo_enable_google_settings, 1 ); ?>><?php esc_html_e( 'Enable Tracking Your Order With Google Map API', 'woocommerce-order-tracker' ); ?>
		</div>
	</div>

	<div class="mwb_tyo_track_order_enable_google_map">
		<div class="mwb_tyo_google_details">
			<label><?php esc_html_e( 'Enter Google Map API Key', 'woocommerce-order-tracker' ); ?></label>
		</div>
		<div class="mwb_tyo_field_details">
			<input type="text" name="mwb_tyo_track_order_google_map_api_key" id="mwb_tyo_track_order_google_map_api_key" value="<?php echo esc_attr( $mwb_tyo_google_api_key ); ?>"><?php esc_html_e( 'Enter your google map API key', 'woocommerce-order-tracker' ); ?>
		</div>
	</div>

	<div class="mwb_tyo_track_order_enable_google_map">
		<div class="mwb_tyo_google_details">
			<label><?php esc_html_e( 'Enter Order Production House Address', 'woocommerce-order-tracker' ); ?></label>
		</div>
		<div class="mwb_tyo_field_details">
			<input type="text" name="mwb_tyo_track_order_production_address" id="mwb_tyo_track_order_production_address" value="<?php echo esc_attr( $mwb_tyo_order_production_add ); ?>" placeholder="<?php esc_attr_e( 'Enter Order Origin Address', 'woocommerce-order-tracker' ); ?>"><?php esc_html_e( 'Enter your order production house address', 'woocommerce-order-tracker' ); ?></div>
		</div>

		<div class="mwb_tyo_track_order_enable_google_map">
			<div class="mwb_tyo_google_details">
				<label><?php esc_html_e( 'Enter Addresses From Where Your Order Has Gone Through', 'woocommerce-order-tracker' ); ?></label></div>
				<div class="mwb_tyo_field_details">
					<input type="text" name="mwb_tyo_track_order_addresses" id="mwb_tyo_track_order_addresses" value="<?php
					if ( ! empty( $mwb_typ_order_gone_through_address ) ) {
						echo esc_attr( $mwb_typ_order_gone_through_address );
					}
					?>" placeholder="<?php esc_attr_e( 'Enter Address', 'woocommerce-order-tracker' ); ?>"><?php esc_html_e( 'Enter the addresses one by one from where your order has gone through', 'woocommerce-order-tracker' ); ?>
					<input type="button" name="mwb_tyo_add_address" id="mwb_tyo_add_address" value="<?php esc_attr_e( 'Add Address', 'woocommerce-order-tracker' ); ?>" class="button-primary">
					<span class="mwb_tyo_empty_adrress_validation" ></span>
				</div>
				
			</div>

			<div class="mwb_tyo_track_order_enable_google_map">
				<div class="mwb_tyo_google_details">
					<label><?php esc_html_e( 'Selected Addresses', 'woocommerce-order-tracker' ); ?></label>
				</div>
				<div class="mwb_tyo_field_details">
					<select multiple="multiple" name="mwb_tyo_selected_address[]" id="mwb_tyo_selected_address">
						<?php
						$mwb_tyo_total_addresses  = get_option( 'mwb_tyo_all_tracking_address', false );
						$mwb_tyo_address_array_value = get_option( 'mwb_tyo_old_addresses', false );
						if ( is_array( $mwb_tyo_address_array_value ) && ! empty( $mwb_tyo_address_array_value ) ) {
							foreach ( $mwb_tyo_address_array_value as $add_key => $add_value ) {
								?>
								<option value="<?php echo esc_attr( $add_key ); ?>"
														  <?php
															if ( is_array( $mwb_tyo_total_addresses ) && in_array( $add_key, $mwb_tyo_total_addresses ) ) {
																echo 'selected=selected'; }
															?>
								><?php echo esc_html( $add_value ); ?></option>
								<?php
							}
						}
						?>
					</select>
				</div>
			</div>
		</div>
