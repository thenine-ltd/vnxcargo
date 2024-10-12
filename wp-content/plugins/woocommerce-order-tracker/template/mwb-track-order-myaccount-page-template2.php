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
		<link rel="stylesheet" type="text/css" href="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . '/assets/css/mwb-tyo-style-front.css'; ?>" media="screen">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
		<script type="text/javascript" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/js/mwb-tyo-script.js'; ?>"></script>
		<?php

		/**
		 * Add content.
		 *
		 * @since 1.0.0
		 */
		do_action( 'mwb_tyo_before_popup' );
} else {
	get_header( 'shop' );

	/**
	 * Add content.
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_before_main_content' );
}

/**
	 * Woocommerce_before_main_content hook.
	 *
	 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
	 * @hooked woocommerce_breadcrumb - 20
	 */
$mwb_main_wrapper_class = get_option( 'mwb_tyo_track_order_class' );
$mwb_child_wrapper_class = get_option( 'mwb_tyo_track_order_child_class' );
$mwb_track_order_css = get_option( 'mwb_tyo_tracking_order_custom_css' );
$mwb_tyo_enhanced_customer_note = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_enhanced_cn', true );
if ( ! empty( $mwb_tyo_enhanced_customer_note ) ) {
	$mwb_tyo_enhanced_customer_note = $mwb_tyo_enhanced_customer_note;
} else {
	$mwb_tyo_enhanced_customer_note = '';
}
?>

<style>	<?php echo $mwb_track_order_css; ?>	</style>

<div class="mwb-tyo-order-tracking-section <?php echo esc_attr( $mwb_main_wrapper_class ); ?>">
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
		foreach ( $get_status_shipping as $key1 => $value1 ) {
			if ( ! empty( $mwb_track_order_status ) && in_array( $value, $mwb_track_order_status ) ) {
				$status_shipped = 1;
			}
		}
	}
	$tyo_order = new WC_Order( $order_id );
	$expected_delivery_date = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_date', true );
	$expected_delivery_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_time', true );
	$order_delivered_date = wps_order_tracker_get_meta_data( $order_id, '_completed_date', true );

	if ( $allowed ) {
		$tyo_order = new WC_Order( $order_id );
		$expected_delivery_date = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_date', true );
		$expected_delivery_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_time', true );
		$order_delivered_date = wps_order_tracker_get_meta_data( $order_id, '_completed_date', true );
		if ( WC()->version < '3.0.0' ) {
			$order_status = $tyo_order->post_status;
			$ordered_by = $tyo_order->post->post_author;
			$ordered_by = get_user_by( 'ID', $ordered_by );
			if ( isset( $ordered_by ) && ! empty( $ordered_by ) ) {
				$ordered_by = $ordered_by->data->display_name;
			}
		} else {
			$order_status = 'wc-' . $tyo_order->get_status();
			$order_data = $tyo_order->get_data();
			$ordered_by = $order_data['customer_id'];
			$ordered_by = get_user_by( 'ID', $ordered_by );
			if ( isset( $ordered_by ) && ! empty( $ordered_by ) ) {
				$ordered_by = $ordered_by->data->display_name;
			}
		}
		$billing_first_name = $tyo_order->get_billing_first_name();
		$billing_last_name = $tyo_order->get_billing_last_name();
		$billing_address = $tyo_order->get_billing_address_1() . ' ' . $tyo_order->get_billing_address_2();
		$billing_city = $tyo_order->get_billing_city();
		$billing_state = $tyo_order->get_billing_state();
		$billing_country = $tyo_order->get_billing_country();
		$billing_postcode = $tyo_order->get_billing_postcode();
		$mwb_track_order_status = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_status', true );
		$order_onchange_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time', true );
		$order_status_key = str_replace( '-', '_', $order_status );
		$order_status_key = 'mwb_tyo_' . $order_status_key . '_text';
		$total = 0;
		if ( WC()->version < '3.0.0' ) {
			foreach ( $tyo_order->get_items() as $item_id => $item ) {
				if ( $item['qty'] > 0 ) {

					/**
					 * Add product.
					 *
					 * @since 1.0.0
					 */
					$product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );
					
					/**
					 * Add product.
					 *
					 * @since 1.0.0
					 */
					$thumbnail     = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';
					$productdata = new WC_Product( $product->id );
					$is_visible        = $product && $product->is_visible();
					
					/**
					 * Add product.
					 *
					 * @since 1.0.0
					 */
					$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $tyo_order );

					$total = $product->get_price() * $item['qty'];

				}
			}
		} else {
			$total = 0;
			foreach ( $tyo_order->get_items() as $item_id => $item ) {
				if ( $item->get_quantity() > 0 ) {

					/**
					 * Add product.
					 *
					 * @since 1.0.0
					 */
					$product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );

					/**
					 * Add product.
					 *
					 * @since 1.0.0
					 */
					$thumbnail     = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';
					$productdata = wc_get_product( $product->get_id() );
					$is_visible        = $product && $product->is_visible();

					/**
					 * Add product.
					 *
					 * @since 1.0.0
					 */
					$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $tyo_order );
					$total = $product->get_price() * $item['qty'];
				}
			}
		}
		$completed = 0;
		if ( 'wc-completed' == $order_status ) {
			$completed = 1;
		}
		$onchange_approval_date = '';
		$onchange_processing_date = '';
		$onchange_shipping_date = '';
		$onchange_cancle_date = '';
		$no_shipping = 0;
		$no_processing = 0;
		$order_cancel = 0;
		?>
		<?php
		if ( 'wc-cancelled' == $order_status ) {
			$order_cancel = 1;
			$previous_status = $mwb_track_order_status[ count( $mwb_track_order_status ) - 2 ];
			if ( ! empty( $get_status_approval ) && is_array( $get_status_approval ) && in_array( $previous_status, $get_status_approval ) ) {
				$no_processing = 1;
				$no_shipping = 1;
			} elseif ( ! empty( $get_status_approval ) && is_array( $get_status_approval ) & in_array( $previous_status, $get_status_processing ) ) {
				$no_processing = 0;
				$no_shipping = 1;
			} else {
				$no_processing = 0;
				$no_shipping = 0;
			}
		}
		?>
		<section class="mwb_tyo_header mwb_tyo_header--template2">
			<div class="mwb_tyo_header-wrapper">
				<div class="mwb_tyo_oder-tracker_gifimg">
					
				</div>
				<?php
				$shipping = 0;
				$processing = 0;
				$shipping_blk = 0;
				$processing_blk = 0;
				if ( is_array( $get_status_processing ) && ! empty( $get_status_processing ) && ! empty( $mwb_track_order_status ) ) {
					foreach ( $get_status_processing as $key => $value ) {
						if ( ! empty( $mwb_track_order_status ) && is_array( $mwb_track_order_status ) && in_array( $value, $mwb_track_order_status ) ) {
							$processing = 1;
							break;
						}
					}
				}
				?>
				<?php
				if ( is_array( $get_status_shipping ) && ! empty( $get_status_shipping ) && ! empty( $mwb_track_order_status ) ) {
					foreach ( $get_status_shipping as $key => $value ) {
						if ( ! empty( $mwb_track_order_status ) && is_array( $mwb_track_order_status ) && in_array( $value, $mwb_track_order_status ) ) {
							$shipping = 1;
							break;
						}
					}
				}
				?>
				<?php
				if( ! empty( $mwb_track_order_status ) && is_array( $mwb_track_order_status ) ){

					$length = count( $mwb_track_order_status );
					$current_last_status = $mwb_track_order_status[ $length - 1 ];
					$previous_last_status = $mwb_track_order_status[ $length - 2 ];
				}
				if ( ! empty( $get_status_approval ) && is_array( $get_status_approval ) && in_array( $current_last_status, $get_status_approval ) ) {
					$shipping_blk = 1;
					$processing_blk = 1;
					$processing = 0;
					$shipping = 0;
				} else if ( ! empty( $get_status_processing ) && is_array( $get_status_processing ) && in_array( $current_last_status, $get_status_processing ) ) {
					$shipping_blk = 1;
					$processing_blk = 0;
					$processing = 1;
					$shipping = 0;
				}
				if ( 1 == $completed ) {
					$shipping = 1;
					$processing = 1;
					$shipping_blk = 0;
					$processing_blk = 0;
				}
				if ( 'wc-cancelled' == $order_status && 1 == $no_processing ) {
					$shipping_blk = 1;
					$processing_blk = 1;
				} else if ( 'wc-cancelled' == $order_status && 1 == $no_shipping ) {
					$shipping_blk = 1;
					$processing_blk = 0;
				}

				?>
				<ul class="mwb_tyo_process_steps_wrap">
					<li id="mwb_placed_order">
						<img class="mwb-tyo-animate" width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/placed.png'; ?>" alt="">
						<span><?php esc_html_e( 'Placed', 'woocommerce-order-tracker' ); ?></span>
					</li>
					<li id="mwb_approval_order">
						<?php
						if ( 1 == $shipping || 1 == $processing ) {
							?>
							<img width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/approved.png'; ?>" alt="">
							<span><?php esc_html_e( 'Approval', 'woocommerce-order-tracker' ); ?></span>
							<?php
						} else {
							$shipping_blk = 1;
							$processing_blk = 1;
							?>
							<img width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/approved-ani.gif'; ?>" alt="">
							<span><?php esc_html_e( 'Approval', 'woocommerce-order-tracker' ); ?></span>
						<?php } ?>
					</li>
					<li id="mwb_processing_order">
						<?php
						if ( 1 == $processing_blk ) {
							?>
							<img width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/processing-b&w.png'; ?>" alt="">
							<span><?php esc_html_e( 'Processing', 'woocommerce-order-tracker' ); ?></span>
																					  <?php
						} else if ( 0 == $shipping && 1 == $processing ) {
							$shipping_blk = 1;
							?>
							<img width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/processing-ani.gif'; ?>" alt="">
							<span><?php esc_html_e( 'Processing', 'woocommerce-order-tracker' ); ?></span>
																					  <?php
						} else {
							?>
							<img width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/processing.png'; ?>" alt="">
							<span><?php esc_html_e( 'Processing', 'woocommerce-order-tracker' ); ?></span>
																					  <?php
						}
						?>
					</li>
					<li id="mwb_shipping_order">
						<?php
						if ( 1 == $shipping_blk ) {
							?>
							<img width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/shipping-b&w.png'; ?>" alt="">
							<span><?php esc_html_e( 'Shipping', 'woocommerce-order-tracker' ); ?></span>
							<?php
						} else if ( 1 == $shipping && 'wc-completed' != $order_status ) {
							?>
							<img width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/shipping-ani.gif'; ?>" alt="">
							<span><?php esc_html_e( 'Shipping', 'woocommerce-order-tracker' ); ?></span>
							<?php
						} else {
							?>
							<img width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/shipping.png'; ?>" alt="">
							<span><?php esc_html_e( 'Shipping', 'woocommerce-order-tracker' ); ?></span>
							<?php
						}
						?>
					</li>
					<?php
					if ( 'wc-completed' == $order_status ) {
						?>
						<li class="mwb_completed_condition" id="mwb_delivered_order">
							<img  width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/deliver-ani.gif'; ?>" alt="" data-completed_data='<?php echo esc_attr( $completed ); ?>' >
							<span><?php esc_html_e( 'Delivered', 'woocommerce-order-tracker' ); ?></span>
						</li> 
						<?php
					} else if ( 1 == $order_cancel ) {
						?>
						<li class="mwb_cancelled_condition" id="mwb_cancelled_order">
							<img  width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/cancel-ani.gif'; ?>" alt="" data-cancelled_data='<?php echo esc_attr( $order_cancel ); ?>' >
							<span><?php esc_html_e( 'Cancelled', 'woocommerce-order-tracker' ); ?></span>
						</li> 
						<?php
					} else {
						?>
						<li class="mwb_completed_condition" id="mwb_delivered_order">
							<img  width="100px" height="100px" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/deliver-b&w.png'; ?>" alt="" data-completed_data='<?php echo esc_attr( $completed ); ?>' >
							<span><?php esc_html_e( 'Delivery', 'woocommerce-order-tracker' ); ?></span>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
		</section>
		<section class="mwb_tyo_order_tracker_content approval">
			<div class="mwb-deliver-msg mwb-tyo-mwb-msg">
				<h3><?php esc_html_e( 'Approval', 'woocommerce-order-tracker' ); ?></h3>
				<?php
				if ( ! empty( $mwb_track_order_status ) ) {
					?>
						<ul class="mwb-tyo-order-info">
							<li><?php esc_html_e( 'placed', 'woocommerce-order-tracker' ); ?></li>
							<li>
							<?php
							if ( WC()->version < '3.0.0' ) {
								echo esc_html( date_i18n( 'd F, Y H:i', strtotime( $tyo_order->post->post_date ) ) );
							} else {
								$mwb_date = $tyo_order->get_date_created()->date('d F, Y H:i');
								echo esc_html(  $mwb_date ) ;}
							?>
							</li>
							<li><?php esc_html_e( 'Your Order is Successfully Placed', 'woocommerce-order-tracker' ); ?></li></ul>
							<?php
				}
				?>
						<?php
						$class = '';
						$active = 0;
						$f = 0;
						$cancelled = 0;
						if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) && is_array( $mwb_track_order_status ) && empty( $mwb_track_order_status ) && '' != $order_status && in_array( $order_status, $get_status_approval ) ) {

							$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $order_status ] );
							foreach ( $order_onchange_time as $changekey => $changevalue ) {
								if ( $order_status == $changekey ) {
									$onchange_approval_date = $changevalue ;
								}
							}
							?>
							<ul class="mwb-tyo-order-info">
								<li><?php echo esc_html( $order_status ); ?></li>
								<li><?php echo esc_html( $onchange_approval_date ); ?></li>
								<li><?php echo esc_html( $current_status ); ?></li></ul>
								<?php
						} else if ( is_array( $mwb_track_order_status ) && ! empty( $mwb_track_order_status ) ) {
							$f = 0;
							foreach ( $mwb_track_order_status as $key => $value ) {
								if ( ! empty( $get_status_approval ) && is_array( $get_status_approval ) && in_array( $value, $get_status_approval ) ) {
									$f = 1;
									$value_key = str_replace( '-', '_', $value );
									$value_key = 'mwb_tyo_' . $value_key . '_text';
									$message = __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $value ];
									$current_status = get_option( $value_key, '' );
									if ( '' == $current_status ) {
										$current_status = $message;
									}
									if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
										
										foreach ( $order_onchange_time as $key1 => $value1 ) {

											if ( $value == $key1 ) {
												
												$onchange_approval_date = $value1 ;
											}
										}
									}
									?>
										<ul class="mwb-tyo-order-info">
											<li><?php echo esc_html( $woo_statuses[ $value ] ); ?></li>
											<li>
											<?php
											if ( isset( $onchange_approval_date ) && '' != $onchange_approval_date ) {
												echo esc_html( $onchange_approval_date );
											} else {
												echo esc_html( $tyo_order->get_date_created()->date('d F, Y H:i') ) ;
												; }
											?>
											</li>
											<li><?php echo esc_html( $current_status ); ?></li></ul>
											<?php
								}
								if ( isset( $mwb_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $mwb_track_order_status[ $key + 1 ] && in_array( $value, $get_status_approval ) && 'wc-cancelled' == $order_status ) {
									$cancelled = 1;
									$current_status = get_option( 'mwb_tyo_wc_cancelled_text', '' );
									if ( '' == $current_status ) {
										$current_status = __( 'Your Order is Cancelled', 'woocommerce-order-tracker' );
									}
									if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
										foreach ( $order_onchange_time as $change_key1 => $change_value1 ) {
											if ( $mwb_track_order_status[ $key + 1 ] == $change_key1 ) {
												$onchange_approval_date = $change_value1 ;
											}
										}
									}
									?>
											<ul class="mwb-tyo-order-info">
												<li><?php esc_html_e( 'Cancelled', 'woocommerce-order-tracker' ); ?></li>
												<li><?php echo esc_html( $change_value1 ); ?> </li>
												<li><?php echo esc_html( $current_status ); ?></li></ul>
										<?php
								}
							}
						}
						?>
								</div>
							</section>
							<?php

							$status_counter = 0;
							if ( ! empty( $get_status_processing ) && is_array( $get_status_processing ) && ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
								foreach ( $get_status_processing as $preocessing_key => $processing_value ) {

									foreach ( $order_onchange_time as $order_key => $order_value ) {
										if ( $processing_value == $order_key ) {
											$status_counter = 1;
										}
									}
								}
							}
							if ( 1 == $status_counter ) {
								?>
								<section class="mwb_tyo_order_tracker_content processing">

									<div class="mwb-deliver-msg mwb-tyo-mwb-msg">
										<h3><?php esc_html_e( 'processing', 'woocommerce-order-tracker' ); ?></h3>
										<?php
										if ( 1 != $cancelled ) {
											if ( is_array( $mwb_track_order_status ) && empty( $mwb_track_order_status ) && '' != $order_status ) {
												$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $order_status ] );
												foreach ( $order_onchange_time as $changekey1 => $changevalue1 ) {
													if ( $order_status == $changevalue1 ) {
														$onchange_processing_date = $changevalue1 ;
													}
												}
												?>
												<ul class="mwb-tyo-order-info">
													<li><?php echo esc_html( $woo_statuses[ $order_status ] ); ?></li>
													<li><?php echo esc_html( $onchange_processing_date ); ?></li>
													<li><?php echo esc_html( $current_status ); ?>"</li></ul>
													<?php
											} else if ( is_array( $mwb_track_order_status ) && ! empty( $mwb_track_order_status ) ) {
												$f = 0;
												foreach ( $mwb_track_order_status as $key => $value ) {
													if ( in_array( $value, $get_status_processing ) ) {
														$f = 1;
														$value_key = str_replace( '-', '_', $value );
														$value_key = 'mwb_tyo_' . $value_key . '_text';
														$message = __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $value ];
														$current_status = get_option( $value_key, '' );
														if ( '' == $current_status ) {
															$current_status = $message;
														}
														if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
															foreach ( $order_onchange_time as $key1 => $value1 ) {

																if ( $value == $key1 ) {
																	$onchange_processing_date = $value1 ;
																}
															}
														}
														?>
															<ul class="mwb-tyo-order-info">
																<li><?php echo esc_html( $woo_statuses[ $value ] ); ?></li>
																<li><?php echo esc_html( $onchange_processing_date ); ?></li>
																<li><?php echo esc_html( $current_status ); ?></li></ul>
																<?php
													}
													if ( isset( $mwb_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $mwb_track_order_status[ $key + 1 ] && in_array( $value, $get_status_processing ) && 'wc-cancelled' == $order_status ) {
														$cancelled = 1;
														$current_status = get_option( 'mwb_tyo_wc_cancelled_text', '' );
														if ( '' == $current_status ) {
															$current_status = __( 'Your Order is Cancelled', 'woocommerce-order-tracker' );
														}
														if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
															foreach ( $order_onchange_time as $key2 => $value2 ) {
																if ( $mwb_track_order_status[ $key + 1 ] == $key2 ) {
																	$onchange_processing_date = $value2 ;
																}
															}
														}
														?>
																<ul class="mwb-tyo-order-info">
																	<li><?php esc_html_e( 'Cancelled', 'woocommerce-order-tracker' ); ?></li>
																	<li><?php echo esc_html( $onchange_processing_date ); ?></li>
																	<li><?php echo esc_html( $current_status ); ?></li></ul>
															<?php
													}
												}
											}
											?>
														<?php
										} else {
											$current_status = get_option( 'mwb_tyo_wc_cancelled_text', __( 'Your Order is cancelled', 'woocommerce-order-tracker' ) );
											if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
												foreach ( $order_onchange_time as $key_change1 => $value_change1 ) {
													if ( $order_status == $key_change1 ) {
														$onchange_processing_date = $value_change1 ;
													}
												}
											}
											?>
														<ul class="mwb-tyo-order-info">
															<li><?php esc_html_e( 'Cancelled', 'woocommerce-order-tracker' ); ?></li>
															<li><?php echo esc_html( $onchange_processing_date ); ?></li>
															<li><?php echo esc_html( $current_status ); ?></li></ul>
												<?php
										}
										?>
													</div>
												</section>
												<?php
							} else {
								if ( ! empty( $get_status_shipping ) && is_array( $get_status_shipping ) && ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
									foreach ( $get_status_shipping as $ship_key => $ship_value ) {
										foreach ( $order_onchange_time as $order_key => $order_value ) {
											if ( $order_value == $ship_value ) {
												$status_counter = 1;
											}
										}
									}
								}
								if ( 1 == $status_counter ) {
									?>
													<section class="mwb_tyo_order_tracker_content processing">

														<div class="mwb-deliver-msg mwb-tyo-mwb-msg">
															<ul class="mwb-tyo-order-info">
																<li><?php esc_html_e( 'Your Order is processed', 'woocommerce-order-tracker' ); ?></li>

															</ul>
														</div></section>
										<?php
								}
							}
							?>
												<?php
												$status_counter = 0;
												if ( ! empty( $get_status_shipping ) && is_array( $get_status_shipping ) && ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
													foreach ( $get_status_shipping as $shiping_key => $shiping_value ) {

														foreach ( $order_onchange_time as $order_key => $order_value ) {
															if ( $shiping_value == $order_key ) {
																$status_counter = 1;
															}
														}
													}
												}
												if ( 1 == $status_counter ) {
													?>
													<section class="mwb_tyo_order_tracker_content shipping">
														<div class="mwb-deliver-msg mwb-tyo-mwb-msg">
															<h3><?php esc_html_e( 'Shipping', 'woocommerce-order-tracker' ); ?></h3>
															<?php
															if ( 1 != $cancelled ) {
																if ( is_array( $mwb_track_order_status ) && empty( $mwb_track_order_status ) && '' != $order_status ) {
																	$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $order_status ] );
																	if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
																		foreach ( $order_onchange_time as $keychange => $valuechange ) {
																			if ( $order_status == $keychange ) {
																				$onchange_shipping_date = $valuechange;
																			}
																		}
																	}
																	?>
																	<ul class="mwb-tyo-order-info">
																		<li><?php echo esc_html( $woo_statuses[ $order_status ] ); ?></li>
																		<li><?php echo esc_html( $onchange_shipping_date ); ?></li>
																		<li><?php echo esc_html( $current_status ); ?>"</li></ul>
																		<?php
																} else if ( ! empty( $get_status_shipping ) && is_array( $mwb_track_order_status ) && ! empty( $mwb_track_order_status ) ) {
																	$f = 0;
																	foreach ( $mwb_track_order_status as $key => $value ) {
																		if ( in_array( $value, $get_status_shipping ) ) {
																			$f = 1;
																			$value_key = str_replace( '-', '_', $value );
																			$value_key = 'mwb_tyo_' . $value_key . '_text';
																			$message = __( 'Your Order status is ', 'woocommerce-order-tracker' ) . $woo_statuses[ $value ];
																			$current_status = get_option( $value_key, '' );
																			if ( '' == $current_status ) {
																				$current_status = $message;
																			}
																			if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
																				foreach ( $order_onchange_time as $key1 => $value1 ) {

																					if ( $value == $key1 ) {
																						$onchange_shipping_date = $value1 ;
																					}
																				}
																			}
																			?>
																				<ul class="mwb-tyo-order-info">
																					<li><?php echo esc_html( $woo_statuses[ $value ] ); ?></li>
																					<li><?php echo esc_html( $onchange_shipping_date ); ?></li>
																					<li><?php echo esc_html( $current_status ); ?></li></ul>
																					<?php
																		}
																		if ( ! empty( $get_status_shipping ) && is_array( $get_status_shipping ) && isset( $mwb_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $mwb_track_order_status[ $key + 1 ] && in_array( $value, $get_status_shipping ) && 'wc-cancelled' == $order_status ) {
																			$cancelled = 1;
																			$current_status = get_option( 'mwb_tyo_wc_cancelled_text', '' );
																			if ( '' == $current_status ) {
																				$current_status = __( 'Your Order is Cancelled', 'woocommerce-order-tracker' );
																			}
																			if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
																				foreach ( $order_onchange_time as $change_key => $change_value ) {
																					if ( $mwb_track_order_status[ $key + 1 ] == $change_key ) {
																						$onchange_shipping_date =  $change_value ;
																					}
																				}
																			}
																			?>
																					<ul class="mwb-tyo-order-info">
																						<li><?php esc_html_e( 'Cancelled', 'woocommerce-order-tracker' ); ?></li>
																						<li><?php echo esc_html( $onchange_shipping_date ); ?></li>
																						<li><?php echo esc_html( $current_status ); ?></li></ul>
																					<?php
																		}
																	}
																}
																?>
																			<?php
															} else {
																$current_status = get_option( 'mwb_tyo_wc_cancelled_text', __( 'Your Order is cancelled', 'woocommerce-order-tracker' ) );
																if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
																	foreach ( $order_onchange_time as $key_change => $value_change ) {
																		if ( $order_status == $key_change ) {
																			$onchange_shipping_date =  $value_change ;
																		}
																	}
																}
																?>
																			<ul class="mwb-tyo-order-info">
																				<li><?php esc_html_e( 'Cancelled', 'woocommerce-order-tracker' ); ?></li>
																				<li><?php echo esc_html( $onchange_shipping_date ); ?></li>
																				<li><?php echo esc_html( $current_status ); ?></li></ul>
																	<?php
															}
															?>
																		</div>
																	</section>
																	<?php
												} elseif ( ( '' != $expected_delivery_date ) && ( 'wc-completed' == $order_status ) ) {
													?>
																	<section class="mwb_tyo_order_tracker_content processing">

																		<div class="mwb-deliver-msg mwb-tyo-mwb-msg">
																			<ul class="mwb-tyo-order-info">
																				<li><?php esc_html_e( 'Your Order is Shipped', 'woocommerce-order-tracker' ); ?></li>

																			</ul>
																		</div></section>
														<?php
												}
												?>
																	<section class="mwb_tyo_order_tracker_content delivery">
																		<div class="mwb-deliver-msg mwb-tyo-mwb-msg">
																			<?php
																			if ( 1 == $completed ) {
																				?>
																				<h3><?php esc_html_e( 'Delivered', 'woocommerce-order-tracker' ); ?></h3>
																				<?php
																				if ( '' == $expected_delivery_date || '' == $expected_delivery_time || '' != $order_delivered_date ) {
																					if ( 'wc-cancelled' == $order_status && '' == $order_delivered_date ) {
																						$message = __( 'Order Cancelled', 'woocommerce-order-tracker' );
																					} else if ( '' != $order_delivered_date && 'wc-cancelled' == $order_status ) {
																						$message = __( 'Not Delivered', 'woocommerce-order-tracker' );
																					} else {
																						$message = __( 'Your order is completed and delivered on ', 'woocommerce-order-tracker' ) . date_i18n( 'F d,Y H:i', strtotime( $order_delivered_date ) );
																					}
																					?>
																					<span class="order-delivered-info">
																						<?php echo esc_html( $message ); ?>
																					</span>
																					<?php
																				}
																			} else if ( 1 == $order_cancel ) {
																				$message_cancelled = __( 'Your order is Cancelled', 'woocommerce-order-tracker' );
																				?>
																				<span class="order-delivered-info">
																					<?php echo esc_html( $message_cancelled ); ?>
																				</span>
																				<?php
																			} else if ( '' != $expected_delivery_date ) {
																				if ( ( '' != $order_delivered_date ) && ( 'wc-cancelled' == $order_status ) ) {
																					$message_expected = __( 'Your order is expected to delivered by ', 'woocommerce-order-tracker' ) . date_i18n( 'F d, Y ', strtotime( $expected_delivery_date ) ) . $expected_delivery_time;

																				} elseif ( 'wc-completed' == $order_status ) {
																					$message_expected = __( 'Your Order is Completed on ', 'woocommerce-order-tracker' ) . date_i18n( 'd F, Y H:i', strtotime( $order_delivered_date ) );
																				} else {
																					$message_expected = __( 'Your order is expected to delivered by ', 'woocommerce-order-tracker' ) . date_i18n( 'F d, Y ', strtotime( $expected_delivery_date ) ) . $expected_delivery_time;
																				}
																				?>
																				<p class="oreder-info">
																					<?php echo esc_html( $message_expected ); ?>
																				</p>
																				<?php
																			}
																			?>

																		</div>
																	</section>
																	<?php if( ! empty( $mwb_tyo_enhanced_customer_note ) ) { ?>
																		<div class="mwb-tyo-order-tracking-section ">
																			<section class="mwb_tyo_order_tracker_content">
																				<div class=" mwb-deliver-msg mwb-tyo-mwb-msg ">
																					<h3><?php esc_html_e( 'Customer Note :-', 'woocommerce-order-tracker' );?></h3>				
																					<span><?php echo esc_html( $mwb_tyo_enhanced_customer_note );?></span>
																				</div>
																			</section>
																		</div>
																		<?php
																	}
																	
																	
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
															?>
