<?php
/**
 * Guest track order page.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/template
 *  
 */

$current_user_id = get_current_user_id();
if ( $current_user_id > 0 ) {
	$myaccount_page = get_option( 'woocommerce_myaccount_page_id' );
	$myaccount_page_url = get_permalink( $myaccount_page );
	wp_redirect( $myaccount_page_url );
	exit;
}

get_header( 'shop' );

/**
 * Woocommerce_before_main_content hook.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @since 1.0.0
*/
do_action( 'woocommerce_before_main_content' );

/**
 * Woocommerce_after_main_content hook.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */

$mwb_main_wrapper_class = get_option( 'mwb_tyo_track_order_class' );
$mwb_child_wrapper_class = get_option( 'mwb_tyo_track_order_child_class' );
$mwb_track_order_css = get_option( 'mwb_tyo_tracking_order_custom_css' );
?>
<style>	<?php echo esc_html( $mwb_track_order_css ); ?>	</style>
<div class="woocommerce woocommerce-account <?php echo esc_attr( $mwb_main_wrapper_class ); ?>">
	<div class="<?php echo esc_attr( $mwb_child_wrapper_class ); ?>">
		<div id="mwb_tyo_guest_request_form_wrapper">
			<h2>
			<?php
			$return_product_form = __( 'Track Your Order', 'woocommerce-order-tracker' );

			/**
			 * Add more setting.
			 *
			 * @since 1.0.0
			 */
			$return_product_form = apply_filters( 'mwb_tyo_return_product_form', $return_product_form );
			wp_kses_post( $return_product_form );
			?>
			</h2>
			<?php
			if ( isset( $_SESSION['mwb_tyo_notification'] ) && ! empty( $_SESSION['mwb_tyo_notification'] ) ) {
				?>
				<ul class="woocommerce-error">
						<li><strong><?php esc_html_e( 'ERROR', 'woocommerce-order-tracker' ); ?></strong>: <?php echo esc_html( $_SESSION['mwb_tyo_notification'] ); ?></li>
				</ul>
				<?php
				unset( $_SESSION['mwb_tyo_notification'] );
			}
			?>
			<?php
			$mwb_tyo_enable_track_17track_feature = get_option( 'mwb_tyo_enable_17track_integration', 'no' );
			if ( ! empty( $mwb_tyo_enable_track_17track_feature ) && 'yes' == $mwb_tyo_enable_track_17track_feature ) {
				?>
			<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
			<label><?php esc_html_e( 'Enter Your 17TrackingNo', 'woocommerce-order-tracker' ); ?></label>
				<input type="text"id="mwb_tyo_enhanced_trackingid"name="mwb_tracking_no" class="mwb_tyo_enhanced_trackingid">
			</p>
			<p class="form-row">
				
				<input type="button" class="button mwb_tyo_enhanced_17track" id="YQElem2" value="17Tracking">
			</p>
			<?php } ?>
			<form class="login" method="post">
				<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
					<label for="username"><?php esc_html_e( 'Enter Order Id', 'woocommerce-order-tracker' ); ?><span class="required"> *</span></label>
					<input type="text" id="order_id" name="order_id" class="woocommerce-Input woocommerce-Input--text input-text">
				</p>
				<?php if( 'yes' != get_option( 'mwb_tyo_enable_track_order_using_order_id', 'no' ) )  { ?>


					<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
						<label for="username"><?php esc_html_e( 'Enter Order Email', 'woocommerce-order-tracker' ); ?><span class="required"> *</span></label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="order_email" id="order_email" value="">
					</p> <?php
				} ?>
				<input type="hidden" name="track_order_nonce_name" value="<?php wp_create_nonce( 'track_order_nonce' ); ?>">
				<p class="form-row">
					<input type="submit" value="<?php esc_attr_e( 'TRACK ORDER', 'woocommerce-order-tracker' ); ?>" name="mwb_tyo_order_id_submit" class="woocommerce-Button button">
				</p>
			</form>
		</div>
	</div>
</div>

<?php 
$check = get_option( 'mwb_tyo_enable_export_order_guest_user' );
if( 'yes' == $check ) { ?>

	<div>
		<form method="POST">
			<h3><?php esc_html_e( '!------ Export Your All Orders Using Email ------!', 'woocommerce-order-tracker' ) ?></h3>
			<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
				<label for="wps_wot_export_email"><?php esc_html_e( 'Enter Email', 'woocommerce-order-tracker' ); ?><span class="required"> *</span></label>
				<input type="email" required  class="woocommerce-Input wps_wot_export_email woocommerce-Input--text input-text">
				<input type="submit"  value="<?php esc_attr_e( 'Export Orders', 'woocommerce-order-tracker' ); ?>"  class="woocommerce-Button wps_wot_guest_user_export_button button">
			</p>
		</form>
	</div>

<?php
}


/**
 *  Add more conent.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_after_main_content' );

/**
 * Woocommerce_sidebar hook.
 *
 * @hooked woocommerce_get_sidebar - 10
*/

get_footer( 'shop' );
