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

	$myaccount_page_url = '';
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

	// Check order id is valid.

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
		} else // Check order associated to customer account or not for guest user.
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
	<link rel="stylesheet" type="text/css" href="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . '/assets/css/mwb-tyo-style-front.css'; ?>">
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

$reason = __( 'Please choose an Order.', 'woocommerce-order-tracker' ) . '<a href="' . $myaccount_page_url . '">' . __( 'Click Here', 'woocommerce-order-tracker' ) . '</a>';

/**
 * Add reason.
 *
 * @since 1.0.0
 */
$reason = apply_filters( 'mwb_tyo_track_choose_order', $reason );
$mwb_main_wrapper_class = get_option( 'mwb_tyo_track_order_class' );
$mwb_child_wrapper_class = get_option( 'mwb_tyo_track_order_child_class' );
$mwb_track_order_css = get_option( 'mwb_tyo_tracking_order_custom_css' );
?>
<style> <?php echo $mwb_track_order_css; ?>  </style>
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
			$mwb_current_status = $woo_statuses[ $order_status ];
			$ordered_by = get_user_by( 'ID', $ordered_by );
			if ( isset( $ordered_by ) && ! empty( $ordered_by ) ) {
				$ordered_by = $ordered_by->data->display_name;
			}
		} else {
			$mwb_get_status = $tyo_order->get_data( $order_id );
			$mwb_current_status = $mwb_get_status['status'];
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
		$mwb_tyo_grand_total = 0;
		$mwb_tyo_total_qty = 0;
		if ( WC()->version < '3.0.0' ) {
			foreach ( $tyo_order->get_items() as $item_id => $item ) {
				if ( $item['qty'] > 0 ) {
					
					/**
					 * Add poduct.
					 *
					 * @since 1.0.0
					 */
					$product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );
					
					/**
					 * Add poduct.
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
				}
			}
		} else {
			$total = 0;
			$mwb_tyo_grand_total = 0;
			$mwb_tyo_total_qty = 0;
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
				}
			}
		}
		$completed = 0;
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
			if ( in_array( $previous_status, $get_status_approval ) && ! empty( $get_status_approval ) && is_array( $get_status_approval ) && ! empty( $previous_status ) ) {
				$no_processing = 1;
				$no_shipping = 1;
			} elseif ( in_array( $previous_status, $get_status_processing ) && ! empty( $get_status_processing ) && is_array( $get_status_processing ) && ! empty( $previous_status ) ) {
				$no_processing = 0;
				$no_shipping = 1;
			} else {
				$no_processing = 0;
				$no_shipping = 0;
			}
		}
		if ( WC()->version < '3.0.0' ) {
			$mwb_date_on_order_change = $tyo_order->modified_date;

		} else {
			$change_order_status = $tyo_order->get_data()['status'];
			$date_on_order_change = $tyo_order->get_data();
			$mwb_date_on_order_change = $date_on_order_change['date_modified'];
		}
		$shipping = 0;
		$processing = 0;
		$shipping_blk = 0;
		$processing_blk = 0;
		if ( is_array( $get_status_processing ) && ! empty( $get_status_processing ) ) {
			foreach ( $get_status_processing as $key => $value ) {

				if ( ! empty( $value ) && is_array( $value ) && ! empty( $mwb_track_order_status ) && in_array( $value, $mwb_track_order_status ) ) {
					$processing = 1;
					break;
				}
			}
		}
		?>
		<?php
		if ( is_array( $get_status_shipping ) && ! empty( $get_status_shipping ) ) {
			foreach ( $get_status_shipping as $key => $value ) {
				if ( ! empty( $mwb_track_order_status ) && ! empty( $value ) ) {
					if ( in_array( $value, $mwb_track_order_status ) ) {
						$shipping = 1;
						break;
					}
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
		if ( ! empty( $get_status_approval ) && is_array( $get_status_approval ) && ! empty( $current_last_status ) && in_array( $current_last_status, $get_status_approval ) ) {
			$shipping_blk = 1;
			$processing_blk = 1;
			$processing = 0;
			$shipping = 0;
		} else if ( ! empty( $get_status_processing ) && is_array( $get_status_processing ) && ! empty( $current_last_status ) && in_array( $current_last_status, $get_status_processing ) ) {
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
		if ( isset( $mwb_current_status ) && ! empty( $mwb_current_status ) ) {
			if ( 'Completed' == $mwb_current_status && 'wc-cancelled' != $order_status ) {
				?>
		  <div>
			<input type="hidden" name="mwb_image_hidden_value" data-id='1' id="mwb_image_hidden_value">
		  </div>
				<?php
			} else {
				?>
			<div>
			  <input type="hidden" name="mwb_image_hidden_value" data-id='0' id="mwb_image_hidden_value">
			</div>
				<?php
			}
		}
		?>
		<?php
		$mwb_tyo_enhanced_customer_note = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_enhanced_cn', true );
		if ( ! empty( $mwb_tyo_enhanced_customer_note ) ) {
			$mwb_tyo_enhanced_customer_note = $mwb_tyo_enhanced_customer_note;
		} else {
			$mwb_tyo_enhanced_customer_note = '';
		}
		$mwb_order_number = wps_order_tracker_get_meta_data( $order_id, '_order_number', true );
		$mwb_prefix = '';
		$mwb_prefix = get_option( 'woocommerce_order_number_prefix', false );
		$mwb_order_number = $mwb_prefix . $mwb_order_number;
		?>

		<div class="mwb_order_tab">
		  <ul class="mwb_order_track_link">
			<li id="mwb_tyo_track_order_details"><a href="javascript:;"><?php esc_html_e( 'Order Details', 'woocommerce-order-tracker' ); ?></a></li>
			<li id="mwb_tyo_track_order_status" class="mwb_tyo_active"><a href="javascript:;"><?php esc_html_e( 'Order Tracking', 'woocommerce-order-tracker' ); ?></a></li>
			<li id="get_current_shop_location"><a href="javascript:;" ><?php esc_html_e( 'Get Shop Location', 'woocommerce-order-tracker' ); ?></a></li>
			
		  </ul>
		</div>
		
		<div class="mwb_header">
		  <div class="mwb_header-wrapper">
		   <div class="mwb_order_tracking_path"> 
			<ul>
			  <li id="mwb_list_1"><span ><img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/placed.png'; ?>" alt=""></span></li>
			  <li id="mwb_list_2" class="
			  <?php
				if ( 1 != $shipping && 1 != $processing ) {
					echo 'mwb_tyo_tooltip_class2';
				}
				?>
			  ">
				<?php
				if ( 1 == $shipping || 1 == $processing ) {
					?>
				<span><img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/approved.png'; ?>" alt=""></span>
					<?php
				} else {
					$shipping_blk = 1;
					$processing_blk = 1;
					?>
				<div class="mwb_tooltip">
				  <span class="mwb_tooltiptext"><?php echo esc_html( $mwb_current_status ); ?></span>
				</div><span><img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/approved-ani.gif'; ?>" alt=""></span>
				<?php } ?>
			</li>
			<li id="mwb_list_3" class="
			<?php
			if ( 0 == $shipping && 1 == $processing ) {
				echo 'mwb_tyo_tooltip_class3'; }
			?>
			">
				<?php
				if ( 1 == $processing_blk ) {
					?>
				<span><img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/processing-b&w.png'; ?>" alt=""></span>
					<?php
				} else if ( 0 == $shipping && 1 == $processing && 1 != $order_cancel ) {
					$shipping_blk = 1;
					?>
				<div class="mwb_tooltip">
				  <span class="mwb_tooltiptext"><?php echo esc_html( $mwb_current_status ); ?></span>
				  </div><span><img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/processing-ani.gif'; ?>" alt=""></span>
												   <?php
				} else {
					?>
				  <span><img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/processing.png'; ?>" alt=""></span>
					<?php
				}
				?>
			  </li>
			  
			  <li id="mwb_list_4" class="
			  <?php
				if ( 1 == $shipping && 'wc-completed' != $order_status ) {
					echo 'mwb_tyo_tooltip_class4'; }
				?>
				">
		<?php
		if ( 1 == $shipping_blk ) {
			?>
				<span><img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/shipping-b&w.png'; ?>" alt=""></span>
				<?php
		} else if ( 1 == $shipping && 'wc-completed' != $order_status && 1 != $order_cancel ) {
			?>
				<div class="mwb_tooltip">
				  <span class="mwb_tooltiptext"><?php echo esc_html( $mwb_current_status ); ?></span>
				  </div><span><img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/shipping-ani.gif'; ?>" alt=""></span>
			<?php
		} else {
			?>
				  <span><img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/shipping.png'; ?>" alt=""></span>
						  <?php
		}
		?>
				</li>
				
				  <?php
					if ( 'wc-completed' == $order_status ) {
						$completed = 1;
						?>

				  <li id="mwb_list_5" class="<?php esc_attr_e( 'mwb_tyo_tooltip_class5', 'woocommerce-order-tracker' ); ?>"><div class="mwb_tooltip">
					<span class="mwb_tooltiptext"><?php echo esc_attr( $mwb_current_status ); ?></span>
				  </div><span><img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/deliver-ani.gif'; ?>" alt=""></span></li> 
						<?php
					} else if ( 1 == $order_cancel ) {
						?>
				   <li id="mwb_list_5" class="<?php esc_attr_e( 'mwb_tyo_tooltip_class5', 'woocommerce-order-tracker' ); ?>"><div class="mwb_tooltip">
					<span class="mwb_tooltiptext"><?php echo esc_attr( $mwb_current_status ); ?></span>
				  </div><span><img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/cancel-ani.gif'; ?>" alt=""></span></li>
						<?php
					} else {
						?>

				  <li id="mwb_list_5">
					<span>
					  <img src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/deliver-b&w.png'; ?>" alt="">
					</span>
				  </li> 
						<?php
					}
					?>
			  </ul>
			</div>
		  </div>
		  <div class="mwb_order_tracker_content approval">
			<div class="mwb-deliver-msg mwb_tyo_mwb_delivery_current_msg">
			  <h3><?php esc_attr_e( 'Your Current Order Status', 'woocommerce-order-tracker' ); ?></h3>
			  <ul class="mwb_order-info" id="mwb_tyo_default_order_details">
				<li><?php echo esc_html( $mwb_current_status ); ?></li>
				  <?php
				  
					if ( isset( $mwb_date_on_order_change ) && ! empty( $mwb_date_on_order_change ) ) {
						?>
				  <li><?php echo esc_html( $mwb_date_on_order_change->date( 'd F, Y H:i' )  ); ?></li>
						<?php
					} else {
						?>
				  <li>
						<?php
						if ( WC()->version < '3.0.0' ) {
							echo esc_html( date_i18n( 'd F, Y H:i', strtotime( $tyo_order->post->post_date ) ) );
						} else {
										$mwb_date = $tyo_order->get_date->date( 'd F, Y H:i' );
										echo esc_html( $mwb_date  );}
						?>
					</li>

						<?php
					}
					?>
				<li><?php echo esc_html__( 'Your Current Order status is ', 'woocommerce-order-tracker' ) . esc_html( $mwb_current_status ); ?></li></ul>
			  </div>
			</div>
			<div class="section mwb_product-details-section ">
			  <div class="mwb_order-details-wrap">
				<div class="mwb-orders-detail">
				  <h3><?php esc_html_e( 'Order Details', 'woocommerce-order-tracker' ); ?></h3>
				  <p><span><?php esc_html_e( 'order id', 'woocommerce-order-tracker' ); ?></span> <span>
									 <?php
										if ( empty( $mwb_order_number ) ) {
											echo esc_html( $order_id );
										} else {
											echo esc_html( $mwb_order_number );}
										?>
					<?php echo esc_html__( '(', 'woocommerce-order-tracker' ) . esc_html( count( $tyo_order->get_items() ) ) . esc_html__( ' items)', 'woocommerce-order-tracker' ); ?></span></p>
				  <p><span><?php esc_html_e( 'order date', 'woocommerce-order-tracker' ); ?></span> <span>
									 <?php
										if ( WC()->version < '3.0.0' ) {
											echo esc_html( date_i18n( 'd F, Y H:i', strtotime( $tyo_order->post->post_date ) ) );
										} else {
											$mwb_date = $tyo_order->get_date_created();
											echo esc_html( $mwb_date->date( 'd F, Y H:i' ) ) ;}
										?>
					</span></p>
				  <p><span><?php esc_html_e( 'amount paid', 'woocommerce-order-tracker' ); ?></span> <span><?php echo wp_kses_post( mwb_tyo_format_price( $tyo_order->get_total() ) ); ?></span></p>
				</div>
				<div class="mwb_user_address">
				  <h3> <?php echo esc_html( $billing_first_name ) . ' ' . esc_html( $billing_last_name ) . ' ' . esc_html( $tyo_order->get_billing_phone() ); ?></h3> 
				  <p><?php echo esc_html( $billing_address ); ?></p>
				  <p><?php echo esc_html( $billing_city ) . ', ' . esc_html( $billing_state ) . ' -' . esc_html( $billing_postcode ); ?></p>
				  <p><?php echo esc_html( WC()->countries->countries[ $billing_country ] ); ?></p>
				</div>
			  </div>
			</div>
			<div class="section mwb_product-details-section mwb-table-responsive">
			  <table class="mwb_tyo_shop_table order_details mwb-tyo-product-details-table mwb-tyo-product-detail-table mwb_table"> 
				<thead>
				  <tr>
					<th><?php esc_html_e( 'Product Details', 'woocommerce-order-tracker' ); ?></th>
					<th><?php esc_html_e( 'Quantity', 'woocommerce-order-tracker' ); ?></th>
					<th><?php esc_html_e( 'Amount Paid', 'woocommerce-order-tracker' ); ?></th>
				  </tr>
				</thead>
				<tbody>
					<?php
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
								$mwb_tyo_grand_total += $total;
								$mwb_tyo_total_qty += $item['qty'];
								?>
						<tr>
						  <td>
							<div class="mwb-tyo-product-wrapper mwb-tyo-product-img">
							 <div class="mwb-tyo-product-wrapper mwb-tyo-product-img">
								<?php
								if ( isset( $thumbnail ) && ! empty( $thumbnail ) ) {
									echo wp_kses_post( $thumbnail );
								} else {
									?>
								<img alt="<?php esc_attr_e( 'Placeholder', 'woocommerce-order-tracker' ); ?>" class="mwb_tyo_attachment-thumbnail size-thumbnail wp-post-image mwb-img-responsive" src="<?php echo esc_attr( home_url() ); ?>/wp-content/plugins/woocommerce/assets/images/placeholder.png">
									<?php
								}
								?>
							  </div>
							  <div class="mwb-product-wrapper mwb-tyo-product-desc">
								<h4><a href=""><?php echo esc_html( $productdata->post->post_title ); ?></a></h4>
							  </div>
							</td>
							<td class="mwbtext-center">
								<?php echo esc_html( $item['qty'] ); ?>
							</td>
							<td>
							 <span class="mwbtext-center"><span class="mwb_tyo_formatted_price"><?php echo wp_kses_post( mwb_tyo_format_price( $total ) ); ?></span></span>
						   </td>
						 </tr>
								<?php
							}
						}
					} else {
						$total = 0;
						$mwb_tyo_grand_total = 0;
						$mwb_tyo_total_qty = 0;
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
								$mwb_tyo_grand_total += $total;
								$mwb_tyo_total_qty += $item['qty'];
								?>
						<tr>
						  <td>
							<div class="mwb-tyo-product-wrapper mwb-tyo-product-img">
								 <?php
									if ( isset( $thumbnail ) && ! empty( $thumbnail ) ) {
										 echo wp_kses_post( $thumbnail );
									} else {
										?>
							  <img alt="<?php esc_attr_e( 'Placeholder', 'woocommerce-order-tracker' ); ?>" class="mwb_tyo_attachment-thumbnail size-thumbnail wp-post-image mwb-img-responsive" src="<?php echo esc_attr( home_url() ); ?>/wp-content/plugins/woocommerce/assets/images/placeholder.png">
										<?php
									}
									?>
						  </div>
						  <div class="mwb-product-wrapper mwb-tyo-product-desc">
							<h4><a href=""><?php echo esc_html( $productdata->get_title() ); ?></a></h4>
						  </div>
						</td>
						<td class="mwbtext-center">
								<?php echo wp_kses_post( $item->get_quantity() ); ?>
						</td>
						<td>
						  <span class="mwbtext-center"><span class="mwb_tyo_formatted_price"><?php echo wp_kses_post( mwb_tyo_format_price( $total ) ); ?></span></span>
						</td>
					  </tr>
								<?php
							}
						}
					}
					?>
				<tr>
				  <td>
					<div>
					  <span><?php esc_html_e( 'Total', 'woocommerce-order-tracker' ); ?></span>
					</div>
				  </td>
				  <td id="mwb_tyo_total_item_qty">
					<?php echo esc_html( $mwb_tyo_total_qty ); ?>
				  </td>
				  <td>
					<div>
					  <span class="mwb_tyo_formatted_price" id="mwb_tyo_formatted_price"><?php echo wp_kses_post( mwb_tyo_format_price( $mwb_tyo_grand_total ) ); ?></span>
					</div>
				  </td>
				</tr>
			  </tbody>
			</table>
		  </div>
		</div>
		<?php if( ! empty( $mwb_tyo_enhanced_customer_note ) ) { ?>
			<div class="mwb-tyo-order-tracking-section ">
				<section class="mwb_order_tracker_content">
					<div class="mwb-deliver-msg mwb_tyo_mwb_delivery_current_msg ">
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
		 * @since 1.0.0
		 */
		$return_request_not_send = apply_filters( 'mwb_tyo_tracking_request_not_send', $return_request_not_send );
		echo wp_kses_post( $return_request_not_send );
		echo wp_kses_post( $reason );
	}
	?>
	</div>
	<?php
	$shop_address = get_option( 'mwb_tyo_shop_address', false );
	?>
	<div class="mwb_get_shop_loaction" id="mwb_shop_location">
	 <iframe width="640" height="480" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.it/maps?q=<?php echo esc_attr( $shop_address ); ?>&output=embed"></iframe>
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
