<?php
/**
 * This file is for license panel. Include this file if license is not validated.
 * If license is validated then show you setting page.
 * Otherwise show the same file.
 *
 * @package Woocommerce_Order_Tracker.
 */

global $wp_version;
global $current_user;

?>
<h3><?php esc_html_e( 'WooCommerce Order Tracker License Panel', 'woocommerce-order-tracker' ); ?></h3>
<hr/>
<div style="text-align: justify; float: left; width: 66%; font-size: 16px; line-height: 25px; padding-right: 4%;">
<?php
 esc_html_e( 'This is the License Activation Panel. After purchasing extension from Codecanyon you will get the purchase code of this extension. Please verify your purchase below so that you can use feature of this plugin.', 'woocommerce-order-tracker' );
?>
	
 </div>
<table class="form-table">
	<tbody>
		<tr valign="top">
			<th class="titledesc" scope="row">
				<label><?php esc_html_e( 'Enter Purchase Code', 'woocommerce-order-tracker' ); ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<input type="text" id="mwb_wot_license_key" class="input-text regular-input" placeholder="Enter your Purchase code here...">
					<input type="submit" value="Validate" class="button-primary" id="mwb_wot_license_save">
					<img class="loading_image" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ); ?>assets/images/loading.gif" style="height: 28px;vertical-align: middle;display:none;">
					<b class="licennse_notification"></b>
				</fieldset>
			</td>
		</tr>
	</tbody>
</table>
