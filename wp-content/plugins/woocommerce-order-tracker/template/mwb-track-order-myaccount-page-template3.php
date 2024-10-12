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
			$page_id = $mwb_tyo_pages['pages']['mwb_guest_track_order_page'];
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
				 * Add reason
				 *
				 * @since 1.0.0
				 */
				$reason = apply_filters( 'mwb_tyo_track_choose_order', $reason );

			}
		} else // Check order associated to customer account or not for guest user.
		{	
			if( 'yes' != get_option( 'mwb_tyo_enable_track_order_using_order_id', 'no' ) ) { 

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
$mwb_tyo_enable_track_order_popup = get_option( 'mwb_tyo_enable_track_order_popup', '' );
if ( 'yes' != $mwb_tyo_enable_track_order_popup ) {
	get_header( 'shop' );

	/**
	 * Add content.
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_before_main_content' );
} elseif ( 'yes' == $mwb_tyo_enable_track_order_popup && $current_user_id > 0 && 0 != $order_id && '' != $order_id && null != $order_id ) {?>
		<link rel="stylesheet" type="text/css" href="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . '/assets/css/mwb-tyo-style-front.css?v=6.1'; ?>" media="screen">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
		<script type="text/javascript" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/js/mwb-tyo-script.js'; ?>"></script>
		<?php

		/**
		 * Add action.
		 *
		 * @since 1.0.0
		 */
		do_action( 'mwb_tyo_before_popup' );
} else {
	get_header( 'shop' );

	/**
	 * Add action.
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_before_main_content' );
}

	/**
	 *  Woocommerce_before_main_content hook.
	 *
	 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
	 * @hooked woocommerce_breadcrumb - 20
	 */
	$mwb_main_wrapper_class = get_option( 'mwb_tyo_track_order_class' );
	$mwb_child_wrapper_class = get_option( 'mwb_tyo_track_order_child_class' );
	$mwb_track_order_css = get_option( 'mwb_tyo_tracking_order_custom_css' );
	$mwb_tyo_enhanced_customer_note = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_enhanced_cn', true );
	$mwb_order_number = wps_order_tracker_get_meta_data( $order_id, '_order_number', true );
	$mwb_prefix = '';
	$mwb_prefix = get_option( 'woocommerce_order_number_prefix', false );
	$mwb_order_number = $mwb_prefix . $mwb_order_number;
if ( ! empty( $mwb_tyo_enhanced_customer_note ) ) {
	$mwb_tyo_enhanced_customer_note = $mwb_tyo_enhanced_customer_note;
} else {
	$mwb_tyo_enhanced_customer_note = '';
}
?>
	<style>	<?php echo $mwb_track_order_css; ?>	</style>
	
	<div class="mwb-tyo-order-tracking-section <?php echo esc_attr( $mwb_main_wrapper_class ); ?>">
		<?php


		if ( true == $allowed ) {

			$tyo_order = new WC_Order( $order_id );
			$expected_delivery_date = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_date', true );
			$expected_delivery_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_time', true );
			$order_delivered_date = wps_order_tracker_get_meta_data( $order_id, '_completed_date', true );
			if ( WC()->version < '3.0.0' ) {
				$order_status = $tyo_order->post_status;
				$ordered_by = $tyo_order->post->post_author;
				$ordered_by = get_user_by( 'ID', $ordered_by );
				$mwb_date_on_order_change = $tyo_order->modified_date;
				$mwb_modified_date = date_i18n( 'd F, Y H:i', strtotime( $mwb_date_on_order_change ) );
				$mwb_status_change_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time', true );
				$mwb_status_change_time[ 'wc-' . $order_status ] = $mwb_modified_date;

				$ordered_by = $ordered_by->data->display_name;
			} else {
				$order_status = 'wc-' . $tyo_order->get_status();
				$ordered_by = $tyo_order->get_customer_id();
				$ordered_by = get_user_by( 'ID', $ordered_by );
				if ( ! empty( $ordered_by ) ) {
					$ordered_by = $ordered_by->data->display_name;
				}
				$mwb_date_on_order_change = $tyo_order->get_date_modified();
				$mwb_modified_date = date_i18n( 'd F, Y H:i', strtotime( $mwb_date_on_order_change ) );
				if( ! empty( $mwb_status_change_time ) ) {
					$mwb_status_change_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time', true );

				}
				$mwb_status_change_time[ $order_status ] = $mwb_modified_date;

			}


			$billing_first_name = $tyo_order->get_billing_first_name();
			$billing_last_name = $tyo_order->get_billing_last_name();
			$billing_address = $tyo_order->get_billing_address_1() . ' ' . $tyo_order->get_billing_address_2();
			$billing_city = $tyo_order->get_billing_city();
			$billing_state = $tyo_order->get_billing_state();
			$billing_country = $tyo_order->get_billing_country();
			$billing_postcode = $tyo_order->get_billing_postcode();
			$mwb_track_order_status = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_status', true );
			$order_status_key = str_replace( '-', '_', $order_status );
			$order_status_key = 'mwb_tyo_' . $order_status_key . '_text';

			?>
			
			<?php
			$get_status_approval = get_option( 'mwb_tyo_order_status_in_approval', array() );
			$get_status_processing = get_option( 'mwb_tyo_order_status_in_processing', array() );
			$get_status_shipping = get_option( 'mwb_tyo_order_status_in_shipping', array() );
			$mwb_track_order_status = array();
			$mwb_track_order_status = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_status', true );
			$woo_statuses = wc_get_order_statuses();
			$status_process = 0;
			$status_shipped = 0;
			if ( is_array( $get_status_processing ) && ! empty( $get_status_processing ) ) {
				foreach ( $get_status_processing as $key => $value ) {
					if ( ! empty( $mwb_track_order_status ) && in_array( $value, $mwb_track_order_status ) ) {
						$status_process = 1;
					}
				}
			}

			if ( is_array( $get_status_shipping ) && ! empty( $get_status_shipping ) ) {
				foreach ( $get_status_shipping as $key1 => $value ) {
					if ( ! empty( $mwb_track_order_status ) && in_array( $value, $mwb_track_order_status ) ) {
						$status_shipped = 1;
					}
				}
			}

			?>
			<section class="<?php echo esc_html( $mwb_child_wrapper_class ); ?> section mwb_tyo_product-details-section">
				<table class="mwb_tyo_shop_table order_details mwb-product-details-table mwb-tyo-track-order-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'APPROVAL', 'woocommerce-order-tracker' ); ?></th>
							<th><?php esc_html_e( 'PROCESSING', 'woocommerce-order-tracker' ); ?></th>
							<th><?php esc_html_e( 'SHIPPING', 'woocommerce-order-tracker' ); ?></th>
							<?php if ( '' != $expected_delivery_date || '' != $expected_delivery_time || '' != $order_delivered_date ) { ?>
								<th><?php esc_html_e( 'DELIVERY', 'woocommerce-order-tracker' ); ?></th>
							<?php } ?>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="3">
								<?php if ( 'yes' != $mwb_tyo_enable_track_order_popup ) { ?>
									<div class="mwb-design-division">
										<?php
								} else {
									?>
										<div class="mwb-design-division mwb-delivery-division-for-message">
										<?php } ?>
										<div class="mwb-controller">
											<span class="track-approval">
												<span class="mwb-circle mwb-tyo-hover 
												<?php
												if ( empty( $mwb_track_order_status ) ) {
													echo 'active'; }
												?>
												" data-status = "<?php esc_html_e( 'Your Order is Successfully Placed', 'woocommerce-order-tracker' ); ?>"></span> 

												<?php
												$class = '';
												$active = 0;
												$f = 0;
												$cancelled = 0;
												if ( is_array( $mwb_track_order_status ) && empty( $mwb_track_order_status ) && '' != $order_status && in_array( $order_status, $get_status_approval ) ) {
													?>
														<?php
														$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $order_status ] );

														?>
														<span class="mwb-circle active" data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

													<?php
												} else if ( is_array( $mwb_track_order_status ) && ! empty( $mwb_track_order_status ) ) {
													$f = 0;
													foreach ( $mwb_track_order_status as $key => $value ) {
														if ( in_array( $value, $get_status_approval ) ) {
															$f = 1;
															$value_key = str_replace( '-', '_', $value );
															$value_key = 'mwb_tyo_' . $value_key . '_text';
															$message = __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $value ];
															?>
															<?php
															$current_status = get_option( $value_key, '' );
															$get_status_approval_count = count( $get_status_approval );
															for ( $i = 0; $i < $get_status_approval_count; $i++ ) {
																if ( array_key_exists( $get_status_approval[ $i ], $mwb_status_change_time ) ) {
																	$current_status = $current_status . __( ' on ', 'woocommerce-order-tracker' ) . $mwb_status_change_time[ $get_status_approval[ $i ] ];
																}
															}
															?>
															<?php
															if ( '' == $current_status ) {
																$get_status_approval_count = count( $get_status_approval );
																for ( $i = 0; $i < $get_status_approval_count; $i++ ) {
																	if ( array_key_exists( $get_status_approval[ $i ], $mwb_status_change_time ) ) {
																		$current_status = $message . __( ' on ', 'woocommerce-order-tracker' ) . $mwb_status_change_time[ $get_status_approval[ $i ] ];
																	}
																}
															}
															?>
																<span class="mwb-circle mwb-tyo-hover 
																<?php
																if ( ! isset( $mwb_track_order_status[ $key + 1 ] ) ) {
																	$active = 1;
																	echo 'active'; }
																?>
																"  data-status = '<?php echo esc_attr( $message ); ?>'></span>	

																<?php
														}
														if ( isset( $mwb_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $mwb_track_order_status[ $key + 1 ] && in_array( $value, $get_status_approval ) && 'wc-cancelled' == $order_status ) {
															$cancelled = 1;
															$current_status = get_option( 'mwb_tyo_wc_cancelled_text', '' );
															if ( '' == $current_status ) {
																$current_status = __( 'Your Order is Cancelled', 'woocommerce-order-tracker' );
															}
															?>
																<span class="mwb-circle order-cancelled"  data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

																<?php
														}
													}
												}
												?>

												</span>
												<span class="track-processing">
													<?php if ( 1 != $cancelled ) { ?>

														<?php
														if ( 1 == $active ) {
															if ( is_array( $get_status_processing ) && ! empty( $get_status_processing ) && ! empty( $mwb_track_order_status ) && is_array( $mwb_track_order_status ) ) {
																foreach ( $get_status_processing as $key => $value ) {
																	if ( in_array( $value, $mwb_track_order_status ) ) {
																		$class = 'revert';
																	}
																}
															}
														}
														?>
														<?php $f = 0; ?>
														<?php
														if ( is_array( $mwb_track_order_status ) && empty( $mwb_track_order_status ) && '' != $order_status ) {
															?>
															<?php $current_status = get_option( $order_status_key, __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $order_status ] ); ?>
															<span class="mwb-circle active" data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

															<?php
														} else if ( ! empty( $get_status_processing ) && is_array( $mwb_track_order_status ) && ! empty( $mwb_track_order_status ) ) {
															$f = 0;
															foreach ( $mwb_track_order_status as $key => $value ) {
																if ( in_array( $value, $get_status_processing ) ) {
																	$f = 1;
																	$value_key = str_replace( '-', '_', $value );
																	$value_key = 'mwb_tyo_' . $value_key . '_text';
																	$message = __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $value ];
																	?>
																	<?php
																	$current_status = get_option( $value_key, '' );
																	$get_status_processing_count = count( $get_status_processing );
																	for ( $i = 0; $i < $get_status_processing_count; $i++ ) {

																		if ( array_key_exists( $get_status_processing[ $i ], $mwb_status_change_time ) ) {
																			$current_status = $current_status . __( ' on ', 'woocommerce-order-tracker' ) . $mwb_status_change_time[ $get_status_processing[ $i ] ];
																		}
																	}
																	?>
																	<?php
																	if ( '' == $current_status ) {
																		$get_status_processing_count = count( $get_status_processing );
																		for ( $i = 0; $i < $get_status_processing_count; $i++ ) {
																			if ( array_key_exists( $get_status_processing[ $i ], $mwb_status_change_time ) ) {
																				$current_status = $message . __( ' on ', 'woocommerce-order-tracker' ) . $mwb_status_change_time[ $get_status_processing[ $i ] ];
																			}
																		}
																	}
																	?>
																	<span class="mwb-circle mwb-tyo-hover <?php echo esc_attr( $class ); ?> <?php
																	if ( ! isset( $mwb_track_order_status[ $key + 1 ] ) ) {
																		$active = 1;
																		echo 'active'; }
																	?>
																	" data-status = '
																	<?php
																	if ( 'revert' == $class ) {
																		esc_attr_e( 'Your Order is Sent back', 'woocommerce-order-tracker' );
																	} else {
																		echo esc_attr( $message ); }
																	?>
'  ></span>	

																	<?php
																}
																if ( isset( $mwb_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $mwb_track_order_status[ $key + 1 ] && in_array( $value, $get_status_processing ) && 'wc-cancelled' == $order_status ) {
																	$cancelled = 1;
																	$current_status = get_option( 'mwb_tyo_wc_cancelled_text', '' );
																	if ( '' == $current_status ) {
																		$current_status = __( 'Your Order is Cancelled', 'woocommerce-order-tracker' );
																	}
																	?>
																	<span class="mwb-circle order-cancelled"  data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

																	<?php
																}
															}
															if ( 1 != $f && 0 == $status_process && 0 == $status_shipped ) {
																?>
																<span class="mwb-circle hollow" data-status=""></span> 
																<?php
															} else if ( 1 != $f && 0 == $status_process && 1 == $status_shipped ) {
																?>
																<span class="mwb-circle" data-status="<?php esc_attr_e( 'Your Order Is Processed', 'woocommerce-order-tracker' ); ?>"></span> 
																<?php
															}
														} else {
															?>
															<span class="mwb-circle hollow" data-status=""></span> 
															<?php
														}
														?>
														<?php
													} else {
														$current_status = get_option( 'mwb_tyo_wc_cancelled_text', __( 'Your Order is cancelled', 'woocommerce-order-tracker' ) );
														?>
														<span class="mwb-circle red" data-status="<?php echo esc_attr( $current_status ); ?>"></span> 
														<?php
													}
													?>
												</span>
												<span class="track-shipping">
													<?php if ( 1 != $cancelled ) { ?>
														<?php
														if ( 1 == $active ) {
															if ( ! empty( $mwb_track_order_status ) && is_array( $get_status_shipping ) && ! empty( $get_status_shipping ) ) {
																foreach ( $get_status_shipping as $key => $value ) {
																	if ( in_array( $value, $mwb_track_order_status ) ) {
																		$class = 'revert';
																	}
																}
															}
														}
														?>
														<?php
														$f = 0;

														if ( is_array( $mwb_track_order_status ) && empty( $mwb_track_order_status ) && '' != $order_status ) {
															?>
																<?php
																$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $order_status ] );

																?>
																<span class="mwb-circle active" data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

															<?php
														} else if ( ! empty( $get_status_shipping ) && is_array( $mwb_track_order_status ) && ! empty( $mwb_track_order_status ) ) {
															$f = 0;
															foreach ( $mwb_track_order_status as $key => $value ) {

																if ( in_array( $value, $get_status_shipping ) ) {

																	$f = 1;
																	$value_key = str_replace( '-', '_', $value );
																	$value_key = 'mwb_tyo_' . $value_key . '_text';


																	$message = __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $value ];

																	?>
																	<?php
																	$current_status = get_option( $value_key, '' );
																	$get_status_shipping_count = count( $get_status_shipping );
																	for ( $i = 0; $i < $get_status_shipping_count; $i++ ) {

																		if ( array_key_exists( $get_status_shipping[ $i ], $mwb_status_change_time ) ) {
																			$current_status = $current_status . __( ' on ', 'woocommerce-order-tracker' ) . $mwb_status_change_time[ $get_status_shipping[ $i ] ];
																		}
																	}
																	?>
																	<?php
																	if ( '' == $current_status ) {
																		$get_status_shipping_count = count( $get_status_shipping );
																		for ( $i = 0; $i < $get_status_shipping_count; $i++ ) {
																			if ( ! empty( $mwb_status_change_time ) && is_array( $mwb_status_change_time ) ) {
																				if ( in_array( $get_status_shipping[ $i ], $mwb_status_change_time ) ) {
																					$current_status = $message . __( ' on ', 'woocommerce-order-tracker' ) . $mwb_status_change_time[ $get_status_shipping[ $i ] ];
																				}
																			}
																		}
																	}
																	?>
																		<span class="mwb-circle mwb-tyo-hover <?php echo esc_attr( $class ); ?> <?php
																		if ( ! isset( $mwb_track_order_status[ $key + 1 ] ) ) {
																			$active = 1;
																			echo 'active'; }
																		?>
																		" data-status = '
																	<?php
																	if ( 'revert' == $class ) {
																			esc_attr_e( 'Your Order is Sent back', 'woocommerce-order-tracker' );
																	} else {
																		echo esc_attr( $message ); }
																	?>
'  ></span>	

																		<?php
																}
																if ( isset( $mwb_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $mwb_track_order_status[ $key + 1 ] && in_array( $value, $get_status_shipping ) && 'wc-cancelled' == $order_status ) {
																	$cancelled = 1;
																	$current_status = get_option( 'mwb_tyo_wc_cancelled_text', '' );
																	if ( '' == $current_status ) {
																		$current_status = __( 'Your Order is Cancelled', 'woocommerce-order-tracker' );
																	}
																	?>
																		<span class="mwb-circle order-cancelled"  data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

																		<?php
																}
															}
															if ( 1 != $f ) {
																?>
																	<span class="mwb-circle hollow" data-status=""></span> 
																<?php
															}
														} else {
															?>
																<span class="mwb-circle hollow" data-status=""></span> 
															<?php
														}
														?>
														<?php
													} else {
														$current_status = get_option( 'mwb_tyo_wc_cancelled_text', __( 'Your Order is cancelled', 'woocommerce-order-tracker' ) );
														?>
															<span class="mwb-circle red" data-status="<?php echo esc_attr( $current_status ); ?>"></span> 
														<?php
													}
													?>
													</span>
													<div class="mwb-deliver-msg mwb-tyo-mwb-delivery-msg"></div>
												</div>
											</div>
										</td>
										<?php if ( '' != $expected_delivery_date || '' != $expected_delivery_time || '' != $order_delivered_date ) { ?>
											<td>
												<?php
												if ( 'yes' != $mwb_tyo_enable_track_order_popup ) {
													?>
													<div class="mwb-delivery-div">
													<?php
												} else {
													?>
														<div class="mwb-delivery-div mwb-after-delivery-div">
													<?php } ?>
														<span>
														<?php
														if ( 'wc-cancelled' == $order_status ) {
															esc_html_e( 'Order Cancelled', 'woocommerce-order-tracker' );
														} else if ( '' == $order_delivered_date && 'wc-cancelled' != $order_status ) {
															esc_html_e( 'Not Delivered', 'woocommerce-order-tracker' ); } else {
															echo esc_html__( 'on ', 'woocommerce-order-tracker' ) . esc_html( date_i18n( 'd F, Y H:i', strtotime( $order_delivered_date ) ) );
															}
															?>
														</span>
														<?php
														if ( '' != $expected_delivery_date ) {
															?>
															<span>
															<?php
															if ( ( '' != $order_delivered_date ) || ( 'wc-cancelled' == $order_status ) ) {
																?>
	<del><?php echo esc_html__( 'by ', 'woocommerce-order-tracker' ) . esc_html( date_i18n( 'F d, Y', strtotime( $expected_delivery_date ) ) ) . esc_html( $expected_delivery_time ); ?>
														</del>
																<?php
															} else {
																echo esc_html__( 'by ', 'woocommerce-order-tracker' ) . esc_html( date_i18n( 'F d, Y', strtotime( $expected_delivery_date ) ) ) . esc_html( $expected_delivery_time );}
															?>
</span><?php } ?>
													</div>
												</td>
											<?php } ?>
										</tr>
									</tbody>
								</table>
							</section>
							
							<?php if( ! empty( $mwb_tyo_enhanced_customer_note ) ) { ?>
								<div class="mwb-tyo-order-tracking-section ">
									<section class="section mwb_tyo_product-details-section">
										<table class=" mwb_tyo_shop_table order_details mwb-product-details-table mwb-tyo-track-order-table ">
											<thead>
												<tr>
													<th><?php esc_html_e( 'Customer Note :-', 'woocommerce-order-tracker' );?></th>	
												</tr>
											</thead>
											<tbody>
												<tr>
													<td><?php echo esc_html( $mwb_tyo_enhanced_customer_note );?></td>
												</tr>
											</tbody>
														
											
										</table>
									</section>
								</div>
								<?php
							}
		} else {
			?>
							<div>
								<input type="text" name="mwb_tyo_track_no" id="mwb_tyo_track_no">
								<input type="button" name="track" id="track" class="button alt" value="Track">
								<div id="YQContainer"></div>
							</div>

			<?php
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
		?>
					</div>
					<?php
					/**
					 * Woocommerce_after_main_content hook.
					 *
					 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
					 */
					if ( 'yes' != $mwb_tyo_enable_track_order_popup ) {


						/**
						 * Add content.
						 *
						 * @since 1.0.0
						 */
						do_action( 'woocommerce_after_main_content' );
						get_footer( 'shop' );
					} elseif ( 'yes' == $mwb_tyo_enable_track_order_popup && $current_user_id > 0 && 0 != $order_id && '' != $order_id && null != $order_id ) {
						
						/**
						 * Add content.
						 *
						 * @since 1.0.0
						 */
						do_action( 'mwb_tyo_after_popup' );
					} else {

						/**
						 * Add content.
						 *
						 * @since 1.0.0
						 */
						do_action( 'woocommerce_after_main_content' );
						get_footer( 'shop' );
					}
