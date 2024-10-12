<?php
/**
 * Template page to track order.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/template
 *  
 */

/**
 * Exit if accessed directly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$allowed = true;

$current_user_id = get_current_user_id();

if ( true == $allowed ) {
	$check_value = isset( $_POST['woocommerce-process-checkout-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ) : '';
	wp_verify_nonce( $check_value, 'woocommerce-process_checkout' );
	if ( isset( $_POST['order_id'] ) ) {
		$order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
	} else {
		$link_array = explode( '?', isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
		if ( empty( $link_array[ count( $link_array ) - 1 ] ) ) {
			$order_id = $link_array[ count( $link_array ) - 2 ];
		} else {
			$order_id = $link_array[ count( $link_array ) - 1 ];
		}
	}

	// check order id is valid.

	if ( ! is_numeric( $order_id ) ) {

		if ( get_current_user_id() > 0 ) {
			$myaccount_page = get_option( 'woocommerce_myaccount_page_id' );
			$myaccount_page_url = get_permalink( $myaccount_page );
		} else {
			$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
			$page_id = $mwb_tyo_pages['pages']['mwb_track_order_page'];
			$myaccount_page_url = get_permalink( $page_id );
		}
		$allowed = false;
		$reason = __( 'Please choose an Order.', 'woocommerce-order-tracker' ) . '<a href="' . $myaccount_page_url . '">' . __( 'Click Here', 'woocommerce-order-tracker' ) . '</a>';

		/**
		 * Add reason.
		 *
		 * @since 1.0.0
		 */
		$reason = apply_filters( 'mwb_tyo_track_choose_order', $reason );
	} else {

		$order_customer_id = wps_order_tracker_get_meta_data( $order_id, '_customer_user', true );

		if ( $current_user_id > 0 ) {
			if ( $order_customer_id != $current_user_id ) {
				$myaccount_page = get_option( 'woocommerce_myaccount_page_id' );
				$myaccount_page_url = get_permalink( $myaccount_page );
				$allowed = false;
				$reason = __( 'This order #', 'woocommerce-order-tracker' ) . $order_id . __( 'is not associated to your account.', 'woocommerce-order-tracker' ) . "<a href='$myaccount_page_url'>" . __( 'Click Here ', 'woocommerce-order-tracker' ) . '</a>';
				
				/**
				 * Add reason.
				 *
				 * @since 1.0.0
				 */
				$reason = apply_filters( 'mwb_tyo_track_choose_order', $reason );
			}
		} else // check order associated to customer account or not for guest user.
		{
			if( 'yes' != get_option( 'mwb_tyo_enable_track_order_using_order_id', 'no' ) )  { 

				if ( isset( $_SESSION['mwb_tyo_email'] ) ) {
					$tyo_user_email = $_SESSION['mwb_tyo_email'];
					$order = wc_get_order( $order_id );
					$order_email = $order->get_billing_email();
					if ( $tyo_user_email != $order_email ) {
						$allowed = false;
						$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
						$page_id = $mwb_tyo_pages['pages']['mwb_track_order_page'];
						$myaccount_page_url = get_permalink( $page_id );
						$reason = __( 'This order #', 'woocommerce-order-tracker' ) . $order_id . __( 'is not associated to your account.', 'woocommerce-order-tracker' ) . "<a href='$myaccount_page_url'>" . __( 'Click Here ', 'woocommerce-order-tracker' ) . '</a>';
						
						/**
						 * Add reason.
						 *
						 * @since 1.0.0
						 */
						$reason = apply_filters( 'mwb_tyo_track_choose_order', $reason );
					}
				} else {
					$allowed = false;
					$myaccount_page = get_option( 'woocommerce_myaccount_page_id' );
					$myaccount_page_url = get_permalink( $myaccount_page );
					$allowed = false;
					$reason = __( 'This order #', 'woocommerce-order-tracker' ) . $order_id . __( ' is not associated to your account.', 'woocommerce-order-tracker' ) . "<a href='$myaccount_page_url'>" . __( 'Click Here ', 'woocommerce-order-tracker' ) . '</a>';
					
					/**
					 * Add reason.
					 *
					 * @since 1.0.0
					 */
					$reason = apply_filters( 'mwb_tyo_track_choose_order', $reason );
				}
			}
		}
	}
} else {
	$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
	$page_id = $mwb_tyo_pages['pages']['mwb_guest_track_order_page'];
	$track_order_url = get_permalink( $page_id );
	header( 'Location: ' . $track_order_url );
}

get_header( 'shop' );

/**
 * Add content.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_before_main_content' );



	/**
	 * Woocommerce_before_main_content hook.
	 *
	 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
	 * @hooked woocommerce_breadcrumb - 20
	 */

if ( $allowed ) {
	$mwb_track_order_custom_orders = get_option( 'mwb_tyo_new_settings_custom_statuses_for_order_tracking', false );

	$mwb_track_status_activation = get_option( 'mwb_tyo_statuses_for_order_tracking_on_activation', false );

	if ( ! is_array( $mwb_track_order_custom_orders ) && empty( $mwb_track_order_custom_orders ) ) {

		$mwb_track_order_custom_orders = $mwb_track_status_activation;
	}
	$mwb_tyo_new_custom_order_statuses = array();
	if ( is_array( $mwb_track_order_custom_orders ) && ! empty( $mwb_track_order_custom_orders ) ) {
		foreach ( $mwb_track_order_custom_orders as $key => $value ) {

			$mwb_tyo_new_custom_order_statuses[ $value ] = substr( $value, 3 );
		}
	}

	if ( is_array( $mwb_tyo_new_custom_order_statuses ) && ! empty( $mwb_tyo_new_custom_order_statuses ) ) {

		$mwb_tyo_total_order_statuses = count( $mwb_tyo_new_custom_order_statuses );
	}

	$tyo_order = new WC_Order( $order_id );

	$expected_delivery_date = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_date', true );
	$expected_delivery_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_time', true );
	$order_delivered_date = wps_order_tracker_get_meta_data( $order_id, '_completed_date', true );
	$mwb_tyo_selected_date_format = get_option( 'mwb_tyo_selected_date_format', false );
	

	$mwb_tyo_first_counter = 1;
	if ( WC()->version < '3.0.0' ) {
		$order_status = $tyo_order->post_status;
		$ordered_by = $tyo_order->post->post_author;
		$ordered_by = get_user_by( 'ID', $ordered_by );
		$mwb_date_on_order_created = $tyo_order->created_date;
		if ( isset( $mwb_tyo_selected_date_format ) && ! empty( $mwb_tyo_selected_date_format ) ) {

			$mwb_tyo_converted_date = strtotime( $mwb_date_on_order_created );
			$mwb_created_date = date_i18n( $mwb_tyo_selected_date_format, $mwb_tyo_converted_date );

		} else {

			$mwb_created_date = date_i18n( 'F d, g:i a', strtotime( $mwb_date_on_order_created ) );
		}

		$mwb_date_on_order_change = $tyo_order->modified_date;
		if ( isset( $mwb_tyo_selected_date_format ) && ! empty( $mwb_tyo_selected_date_format ) ) {
			$mwb_tyo_converted_date_change = strtotime( $mwb_date_on_order_change );
			$mwb_modified_date = date_i18n( $mwb_tyo_selected_date_format, $mwb_tyo_converted_date_change );

		} else {

			$mwb_modified_date = date_i18n( 'F d, g:i a', strtotime( $mwb_date_on_order_change ) );
		}

		$mwb_status_change_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time', true );
		$mwb_status_change_time[ 'wc-' . $order_status ] = $mwb_modified_date;

		$ordered_by = $ordered_by->data->display_name;
	} else {
		$order_status = 'wc-' . $tyo_order->get_status();
	
		$timezone_string = get_option( 'timezone_string', false );
		if ( ! empty( $timezone_string ) ) {
			date_default_timezone_set( $timezone_string );
		} 
		// $mwb_created_date = strtotime( $mwb_created_date );
					


		$mwb_date_on_order_created = $tyo_order->get_date_created();

		if ( isset( $mwb_tyo_selected_date_format ) && ! empty( $mwb_tyo_selected_date_format ) ) {
			
			// $mwb_tyo_converted_date = strtotime( $mwb_date_on_order_created );
			$mwb_created_date = $mwb_date_on_order_created->date($mwb_tyo_selected_date_format);
		


		} else {
			$mwb_created_date = date_i18n( wc_date_format(), strtotime( $mwb_date_on_order_created ) );

		}

		$mwb_date_on_order_change = $tyo_order->get_date_modified();
		if ( isset( $mwb_tyo_selected_date_format ) && ! empty( $mwb_tyo_selected_date_format ) ) {
			// $mwb_tyo_converted_date_change = strtotime( $mwb_date_on_order_change );
			
		
			$mwb_modified_date = $mwb_date_on_order_change->date($mwb_tyo_selected_date_format);


		} else {
			$mwb_modified_date = date_i18n( 'F d, g:i a', strtotime( $mwb_date_on_order_change ) );
		}
		$mwb_status_change_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time', true );
		if( ! empty( $mwb_status_change_time ) ) {

			$mwb_status_change_time[ $order_status ] = $mwb_modified_date;
		}

	}
	$mwb_tyo_enhanced_customer_note = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_enhanced_cn', true );
	if ( ! empty( $mwb_tyo_enhanced_customer_note ) ) {
		$mwb_tyo_enhanced_customer_note = $mwb_tyo_enhanced_customer_note;
	} else {
		$mwb_tyo_enhanced_customer_note = '';
	}
	$mwb_tyo_all_order_stauses = wc_get_order_statuses();
	// print_r( $mwb_track_order_custom_orders );
	// echo"<br>";
	// print_r( $mwb_status_change_time ) ;die;
	$flag_count = false;
	?>
		
		<div class="mwb-tyo-main-data-wrapper">
			
			<div class="mwb-tyo-order-progress-data-field">
				<div class="mwb-tyo-small-circle1 mwb-circle-new">
					<div class="sub-circle mwb-tyo-sub-circle1">
					</div>
				</div>
				<div class="mwb-tyo-skill">
					<div class="mwb-tyo-outer">
						<div id="ineer_status" class="mwb-tyo-inner" data-progress="<?php echo ( count( $mwb_status_change_time ) + 1 ); ?>" data-progress-bar-height="<?php echo ( count( $mwb_status_change_time ) + 1 ); ?>"> 
						</div>  
					</div>
				</div>
				<div class="mwb-tyo-small-circle2 mwb-circle-new">
					<div class="sub-circle mwb-tyo-sub-circle2">
					</div>
				</div>
			</div>
			<div class="mwb-tyo-order-tracking-section-new">

				<div class="mwb-tooltip" id="mwb-tooltip_1">
					<h4><?php esc_html_e( 'Order Placed', 'woocommerce-order-tracker' ); ?></h4>
					<p><?php esc_html_e( 'your order is successfully placed on', 'woocommerce-order-tracker' ); ?> </p>
					<span>
					<?php
					
					echo esc_html( $mwb_created_date ) ;
					?>
				</span>
			</div>
			<?php
			 $counter = 1;
			if ( is_array( $mwb_status_change_time ) && ! empty( $mwb_status_change_time ) ) {

				
				foreach ( $mwb_status_change_time as $order_key => $order_value ) {
					if ( array_key_exists( $order_key, $mwb_status_change_time )  ) {
						?>
						<div class="mwb-tooltip" id="mwb-tooltip_<?php echo esc_attr( $counter + 1 ); ?>">
							<?php
							if ( array_key_exists( $order_key, $mwb_tyo_all_order_stauses ) ) {
								?>
								<h4><?php echo esc_html( $mwb_tyo_all_order_stauses[ $order_key ] ); ?></h4>
							<?php } else { ?> 
								<h4><?php echo esc_html( substr( $order_key, 3 ) ); ?></h4>
							<?php } ?>
							<p><?php esc_html_e( 'your order status is ', 'woocommerce-order-tracker' ); ?></p>
							<?php
							if ( array_key_exists( $order_key, $mwb_tyo_all_order_stauses ) ) {
								?>
								<p><?php echo esc_html( $mwb_tyo_all_order_stauses[ $order_key ] ); ?></p>
							<?php } else { ?> 
								<p><?php echo esc_html( substr( $order_key, 3 ) ); ?></p>
								<?php } ?><p><?php esc_html_e( ' on', 'woocommerce-order-tracker' ); ?> </p>
								<span>
									<?php
									
									echo esc_html( $mwb_status_change_time[ $order_key ] );
									?>
									
								</span> 
							</div>
							<?php
					} else {
						if ( array_key_exists( $order_key, $mwb_status_change_time ) ) {
							$flag_count = true;
							$mwb_tyo_new_array = array_slice( $mwb_track_order_custom_orders, 0, $order_key );

							?>
								<div class="mwb-tooltip" id="mwb-tooltip_<?php echo esc_attr( $counter + 1 ); ?>">
									<h4><?php echo esc_html( substr( $order_key, 3 ) ); ?></h4>
									<p><?php esc_html_e( 'your order status is ', 'woocommerce-order-tracker' ); ?></p>
									<p><?php echo esc_html( substr( $order_key, 3 ) ); ?><?php esc_html_e( ' on', 'woocommerce-order-tracker' ); ?> </p>
									<span>
									<?php
									
									echo esc_html( $mwb_status_change_time[ $order_key ] );
									?>
										
									</span> 
								</div>
								<?php
						}
					}
						
						$counter++;
				}

				if ( $flag_count ) {
					if ( ! empty( $mwb_tyo_new_array ) && is_array( $mwb_tyo_new_array ) ) {
						$mwb_tyo_new_array = array_reverse( $mwb_tyo_new_array );
						foreach ( $mwb_tyo_new_array as $new_key => $new_value ) {
							if ( ! array_key_exists( $new_value, $mwb_status_change_time ) ) {
								$mwb_status_change_time[ $new_value ] = $mwb_modified_date;
								wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_onchange_time', $mwb_status_change_time );
							}
						}
					}
				}
			}
			?>
			</div>
		</div>
		<?php
} else {
	$return_request_not_send = __( 'Tracking Request can\'t be send. ', 'woocommerce-order-tracker' );

	/**
	 * Tracking request.
	 *
	 * @since 1.0.0
	 */
	$return_request_not_send = apply_filters( 'mwb_tyo_tracking_request_not_send', $return_request_not_send );
	echo wp_kses_post( $return_request_not_send );
	echo wp_kses_post( $reason );
}
/**
 * Woocommerce_after_main_content hook.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 * @since 1.0.0
 */
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
$reset = date_default_timezone_get();
date_default_timezone_set( $reset );