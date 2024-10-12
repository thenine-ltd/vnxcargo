<?php
/**
 * This is file for enhance tracking.
 *
 * @package Woocommerce_Order_Tracker.
 */


if ( isset( $_POST['save'] ) ) {

	$mwb_tyo_general_settings = array();
	$mwb_tyo_enable_plugin = isset( $_POST['mwb_tyo_tracking_enable'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_tracking_enable'] ) ) : '';
	$mwb_tyo_shipment_tracking_providers_url = isset( $_POST['mwb_tyo_courier_url'] ) ? map_deep( wp_unslash( $_POST['mwb_tyo_courier_url'] ), 'sanitize_text_field' ) : array();
	$mwb_tyo_general_settings = array(
		'enable_plugin' => $mwb_tyo_enable_plugin,
		'providers_data' => $mwb_tyo_shipment_tracking_providers_url,
	);

	update_option( 'mwb_tyo_general_settings_saved', $mwb_tyo_general_settings );
	?>

	<?php
}


// Creation of shipment tracking courier list array.
$mwb_tyo_courier_companies = array();
$mwb_tyo_courier_companies = array(
	'DHL'                   => 'http://www.dhl.com/en/express/tracking.shtml?AWB=',
	'UPS'                   => 'https://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=',
	'USPS'                  => 'https://tools.usps.com/go/TrackConfirmAction?tRef=fullpage&tLc=5&text28777=&tLabels=',
	'FedEx'                 => 'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=',
	'RoyalMail'             => 'https://www.royalmail.com/track-your-item#/',
	'AustraliaPost'         => 'https://auspost.com.au/mypost/track/#/search',
	'IMEX '                 => 'http://dm.mytracking.net/IMEX/track/TrackDetails.aspx?t=',
	'OnTrac'                => 'https://www.ontrac.com/tracking.asp?trackingres=submit&tracking_number=',
	'parcelForce'           => 'https://www.parcelforce.com/track',
	'Dpd'                   => 'https://www.dpd.co.uk/apps/tracking/?reference=',
	'CollectPlus'           => 'https://www.collectplus.co.uk/track/',
	'TforceLogistics'       => 'http://www.tforcelogistics.com/track-a-shipment/',
	'ApcPostalLogostics'    => 'https://us.mytracking.net/APC/track/TrackDetails.aspx?t=',
	'EStes'                 => 'http://www.estes-express.com/WebApp/ShipmentTracking/',
);

if ( empty( get_option( 'mwb_tyo_courier_companies', false ) ) ) {
	update_option( 'mwb_tyo_courier_companies', $mwb_tyo_courier_companies );

}
	update_option( 'mwb_tyo_courier_default_company', $mwb_tyo_courier_companies );
	$mwb_tyo_courier_companies = get_option( 'mwb_tyo_courier_companies', false );
   $mwb_tyo_general_settings_data = get_option( 'mwb_tyo_general_settings_saved', false );
   $mwb_tyo_courier_default_company = get_option( 'mwb_tyo_courier_default_company', false );


?>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="mwb_tyo_wrapper">

		<div class="mwb_tyo_section">
			<div class="mwb_tyo_enable_plugin">
				<label><?php esc_html_e( 'Enable Plugin', 'woocommerce-order-tracker' ); ?>
				</label>
				<?php
				$tip_message = __( 'Enable the checkbox if you want to activate the functionality', 'woocommerce-order-tracker' );
				echo wp_kses_post( wc_help_tip( $tip_message ) );
				?>
				<label for="mwb_tyo_tracking_enable">
					<input type="checkbox" name="mwb_tyo_tracking_enable" id="mwb_tyo_tracking_enable" <?php isset( $mwb_tyo_general_settings_data['enable_plugin'] ) ? checked( 'on', $mwb_tyo_general_settings_data['enable_plugin'] ) : ''; ?> >
					<span><?php esc_html_e( 'Enable the checkbox to enable Tracking', 'woocommerce-order-tracker' ); ?><span>
				</label>
			</div>
		</div>

		<!-- Panel For Adding The Shipment Tracking Couries-->
		<div class="mwb_tyo_tracking_section">
			<div class="mwb_tyo_shipping_courier">
				<h4><?php esc_html_e( 'Select Shipping Companies You Want', 'woocommerce-order-tracker' ); ?></h4>
				<div class="mwb_tyo_courier_content">
					<?php
					   $mwb_tyo_courier_companies = get_option( 'mwb_tyo_courier_companies', false );


					if ( is_array( $mwb_tyo_courier_companies ) && ! empty( $mwb_tyo_courier_companies ) ) {
						foreach ( $mwb_tyo_courier_companies as $mwb_courier_key => $mwb_courier_value ) {
							if ( '' != $mwb_courier_key && '' != $mwb_courier_value ) {

								?>
								<div class="mwb-tyo-courier-data" id="mwb_enhanced_tyo_class<?php echo esc_attr( $mwb_courier_key ); ?>">
									<input type="checkbox" id="mwb_enhanced_checkbox<?php echo esc_attr( $mwb_courier_key ); ?>" name="mwb_tyo_courier_url[<?php echo esc_attr( $mwb_courier_key ); ?>]" value="<?php echo esc_attr( $mwb_courier_value ); ?>" 
									<?php
									if ( isset( $mwb_tyo_general_settings_data['providers_data'] ) && is_array( $mwb_tyo_general_settings_data['providers_data'] ) && ! empty( $mwb_tyo_general_settings_data['providers_data'] ) ) {
										if ( array_key_exists( $mwb_courier_key, $mwb_tyo_general_settings_data['providers_data'] ) ) {
											echo "checked='checked'";
										}
									}
									?>
									><label for="mwb_enhanced_checkbox<?php echo esc_attr( $mwb_courier_key ); ?>"><?php echo esc_html( $mwb_courier_key ); ?></label>
									<?php
									if ( ! empty( $mwb_tyo_courier_default_company ) ) {
										if ( ! array_key_exists( $mwb_courier_key, $mwb_tyo_courier_default_company ) ) {
											?>
										<a href="#" id="mwb_enhanced_cross<?php echo esc_attr( $mwb_courier_key ); ?>" class="mwb_enhanced_tyo_remove" data-id='<?php echo esc_attr( $mwb_courier_key ); ?>'>X</a>
											<?php
										}
									}
									?>
								</div>
								<?php
							}
						}
					}
					?>
				</div>
			</div>
		</div>
		<div class="mwb_tyo_tracking_section">
			
		</div>

<div class="mwb_enhanced_tyo_table_wrapper">
<table class="form-table">
  <tr>
	   <th><h4><?php esc_html_e( 'Add Shipment Tracking Provide Name', 'woocommerce-order-tracker' ); ?></h4></th>
		<td>
			
			
			<div class="mwb_enhanced_tyo_provider">

				<div class="mwb_enhanced_tyo_add-wrap">
					<?php
					$tip_description = __( 'Enter Providers name you are going to use like PostNl Shipping, Express Shipping etc.', 'woocommerce-order-tracker' );
					echo wp_kses_post( wc_help_tip( $tip_description ) );
					?>
					<label><?php esc_html_e( 'Provider Name', 'woocommerce-order-tracker' ); ?></label>

					<input type="text" name="mwb_enhanced_tyo_add_prodvider" class="mwb_toy_enhanced_provider" value="">
				</div>

				<div class="mwb_enhanced_tyo_add-wrap">
					<?php
					$tip_descriptions = __( 'Enter Providers Tracking Page Url from where your customer can track thier packages.', 'woocommerce-order-tracker' );
					echo wp_kses_post( wc_help_tip( $tip_descriptions ) );
					?>
					<label><?php esc_html_e( 'Provider tracking Page Url', 'woocommerce-order-tracker' ); ?></label>

					<div class="mwb_enhanced_tyo_add-inner-wrap">
						<input type="text" name="mwb_enhanced_tyo_add_prodvider" class='mwb_toy_enhanced_provider_url' value="">
						<input type="button" id='mwb_tyo_enhanced_woocommerce_shipment_tracking_add_providers' value="<?php esc_attr_e( 'Add', 'woocommerce-order-tracker' ); ?>" class="button">
					</div>
				</div>
			</div>
	   </td>
   </tr>
	   <tr>
			<td>
			 <div>
				
				</div>
			
			</td>
			</tr>
</table>
</div> 
</div> 
