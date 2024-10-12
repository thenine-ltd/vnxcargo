<?php
/**
 * Guest track order page.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/template
 *  
 */

$allowed = true;
$current_user_id = get_current_user_id();

if ( $allowed ) {
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
		 * Add more setting.
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
				 * Add more setting.
				 *
				 * @since 1.0.0
				 */
				$reason = apply_filters( 'mwb_tyo_track_choose_order', $reason );
			}
		} else // check order associated to customer account or not for guest user.
		{
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
				 *  Add reason.
				 *
				 * @since 1.0.0
				 */
				$reason = apply_filters( 'mwb_tyo_track_choose_order', $reason );
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

if ( $allowed ) {

	if ( isset( $order_id ) && ! empty( $order_id ) ) {
		$tyo_order = wc_get_order( $order_id );
		$order_data = $tyo_order->get_data();

		$mwb_tyo_all_saved_cities = get_option( 'mwb_tyo_all_tracking_address', false );
		if ( is_array( $mwb_tyo_all_saved_cities ) && ! empty( $mwb_tyo_all_saved_cities ) ) {
			foreach ( $mwb_tyo_all_saved_cities as $saved_key => $saved_value ) {

				$mwb_tyo_modified_all_saved_cities[] = str_replace( 'mwb_address_', '', $saved_value );
			}
		}

		$mwb_tyo_previous_saved_cities = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_track_custom_cities', true );
		$mwb_tyo_previous_saved_changed_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_custom_change_time', true );


		$billing_addresses = $tyo_order->get_formatted_billing_address();
		$created_time = $order_data['date_created'];
		$converted_created_date = date_i18n( 'F d, Y g:i a', strtotime( $created_time ) );

	}

	$mwb_tyo_google_api_key = get_option( 'mwb_tyo_google_api_key', false );
	$mwb_admin_shop_location = get_option( 'mwb_tyo_order_production_address', false );

	$mwb_order_delivery_date = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_date', true );
	$mwb_order_delivery_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_time', true );
	?>
	<div class="mwb-track-order-main-wrapper">
		<div class="mwb-track-order-content">
			<div class="mwb-track-order-tracking-section">
				<div class="mwb-track-order-tooltip">
					<div class="mwb-track-order-tooltip-wrap">
						<span><?php esc_html_e( 'Order Placed Successfully ', 'woocommerce-order-tracker' ); ?></span>
						<span><?php esc_html_e( ' On ', 'woocommerce-order-tracker' ); ?><?php echo esc_html( $converted_created_date ); ?></span>
						<span>
						<?php
						if ( isset( $mwb_admin_shop_location ) && '' != $mwb_admin_shop_location ) {
							echo esc_html( $mwb_admin_shop_location );
						} else {
							esc_html_e( 'United States', 'woocommerce-order-tracker' ); }
						?>
						</span>
					</div>
					<span class="mwb-track-order-inner-circle"></span>
				</div>
				<?php

				if ( is_array( $mwb_tyo_previous_saved_cities ) && ! empty( $mwb_tyo_previous_saved_cities ) ) {
					foreach ( $mwb_tyo_previous_saved_cities as $city_key => $city_value ) {
						$mwb_tyo_same_city_occurence = array_count_values( $city_value );

						?>
						<div class="mwb-track-order-tooltip">
							<div class="mwb-track-order-tooltip-wrap">
								<span><?php echo esc_html( $city_key ); ?></span>
								<div class="mwb-tyo-new-order-time">
									<span>
										<?php

										if ( is_array( $mwb_tyo_previous_saved_changed_time ) && array_key_exists( $city_key, $mwb_tyo_previous_saved_changed_time ) ) {
											foreach ( $city_value as $key => $value ) {
												if ( $mwb_tyo_same_city_occurence[ $value ] <= 1 ) {
													?>
													<span><?php echo esc_html( $value ); ?><?php esc_html_e( ' On ', 'woocommerce-order-tracker' ); ?><?php echo esc_html( $mwb_tyo_previous_saved_changed_time[ $city_key ][ $key ] ); ?></span>
													<?php
												} else {
													$mwb_tyo_occurence_key = end( $mwb_tyo_previous_saved_changed_time[ $city_key ] );
													?>
													<span><?php echo esc_html( $value ); ?><?php esc_html_e( ' On ', 'woocommerce-order-tracker' ); ?><?php echo esc_html( $mwb_tyo_occurence_key ); ?></span>
													<?php
													break;
												}
											}
										}
										?>
									</span>
								</div>
							</div>
							<span class="mwb-track-order-inner-circle"></span>
						</div>
						<?php
					}
				}
				if ( isset( $mwb_order_delivery_time ) && '' != $mwb_order_delivery_time ) {
					$mwb_order_delivery_time = date_i18n( 'g:i a', strtotime( $mwb_order_delivery_time ) );
				}

				?>
				<div class="mwb-track-order-last-tooltip">
					<div class="mwb-track-order-last-tooltip-wrap">
						<span><?php esc_html_e( 'Product Delivered', 'woocommerce-order-tracker' ); ?></span>
						<?php
						if ( '' != $mwb_order_delivery_date ) {
							if ( '' != $mwb_order_delivery_time ) {
								?>
								<span><?php esc_html_e( ' On ', 'woocommerce-order-tracker' ); ?><?php echo esc_html( $mwb_order_delivery_date ) . ' ' . esc_html( $mwb_order_delivery_time ); ?></span>
								<?php
							} else {
								?>
								<span><?php esc_html_e( ' On ', 'woocommerce-order-tracker' ); ?><?php echo esc_html( $mwb_order_delivery_date ); ?></span>
								<?php
							}
						} else {
							?>
							<span><?php esc_html_e( ' -- ', 'woocommerce-order-tracker' ); ?></span>
							<?php
						}
						?>
						<span><?php echo esc_html( $order_data['billing']['address_1'] ) . ', ' . esc_html( $order_data['billing']['address_2'] ) . '</br>' . esc_html( $order_data['billing']['city'] ) . ', ' . esc_html( $order_data['billing']['state'] ) . '</br>' . esc_html( $order_data['billing']['email'] ); ?></span>
					</div>
					<span class="mwb-track-order-last-inner-circle"></span>
					<span class="mwb-track-order-outer-inner-circle"></span>
				</div>
			</div>
		</div>
		<div class="mwb-tyo-new-order-details">
			<?php
			if ( WC()->version < '3.0.0' ) {
				foreach ( $tyo_order->get_items() as $orderkey => $ordervalue ) {
					?>
					<div class="mwb-tyo-new-order-details-inner">
						<?php
						if ( $ordervalue['qty'] > 0 ) {

							/**
							 * Add products.
							 *
							 * @since 1.0.0
							 */
							$product = apply_filters( 'woocommerce_order_item_product', $ordervalue->get_product(), $ordervalue );
							
							/**
							 * Get permalink.
							 *
							 * @since 1.0.0
							 */
							$thumbnail     = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $orderkey, $ordervalue ) : '';
							$productdata = new WC_Product( $product->id );
							$is_visible        = $product && $product->is_visible();

							/**
							 * Get permalink.
							 *
							 * @since 1.0.0
							 */
							$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $ordervalue ) : '', $ordervalue, $tyo_order );
							$total += $product->get_price() * $ordervalue['qty'];
							?>
							<div class="order-id"><p><?php esc_html_e( 'order id : ', 'woocommerce-order-tracker' ); ?><strong><?php echo esc_html( $order_id ); ?></strong></p></div>
							<div class="mwb-tyo-order-img-detail">
								<div class="mwb-tyo-order">
									<p class="mwb-tyo-order-name-bold"><?php echo esc_html( $productdata->post->post_title ); ?></p>
									<p class="mwb-tyo-seller-name"><?php esc_html_e( 'seller : ', 'woocommerce-order-tracker' ); ?><?php echo esc_html( get_bloginfo() ); ?></p>
								</div>
								<div class="mwb-tyo-product-name">
									<?php
									if ( isset( $thumbnail ) && ! empty( $thumbnail ) ) {
										echo wp_kses_post( $thumbnail );
									} else {
										?>
										<p><img alt="<?php esc_html_e( 'Placeholder', 'woocommerce-order-tracker' ); ?>"  class="mwb_tyo_attachment-thumbnail size-thumbnail wp-post-image mwb-img-responsive" src="<?php echo esc_attr( home_url() ); ?>/wp-content/plugins/woocommerce/assets/images/placeholder.png"></p>
										<?php
									}
									?>
								</div>
							</div>
							<div class="price-product">
								<p class="price-bold"><?php echo wp_kses_post( mwb_tyo_format_price( $product->get_price() ) ); ?></p>
								<div class="mwb-tyo-quantity">
									<label><?php esc_html_e( 'Quantity : ', 'woocommerce-order-tracker' ); ?><?php echo esc_html( $ordervalue['qty'] ); ?></label> 
								</div>
							</div>       
							<?php
						}
						?>
					</div>
					<?php
				}
			} else {

				$total = 0;
				$mwb_tyo_grand_total = 0;
				$mwb_tyo_total_qty = 0;
				foreach ( $tyo_order->get_items() as $orderkey => $ordervalue ) {
					?>
					<div class="mwb-tyo-new-order-details-inner">
						<?php
						if ( $ordervalue->get_quantity() > 0 ) {

							/**
							 * Add Products.
							 *
							 * @since 1.0.0
							 */
							$product = apply_filters( 'woocommerce_order_item_product', $ordervalue->get_product(), $ordervalue );
							
							/**
							 * Add Products.
							 *
							 * @since 1.0.0
							 */
							$thumbnail     = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $orderkey, $ordervalue ) : '';
							$productdata = wc_get_product( $product->get_id() );
							$is_visible        = $product && $product->is_visible();

							/**
							 * Product permalink.
							 *
							 * @since 1.0.0
							 */
							$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $ordervalue ) : '', $ordervalue, $tyo_order );
							$total += $product->get_price() * $ordervalue['qty'];
							$mwb_tyo_grand_total += $total;
							$mwb_tyo_total_qty += $ordervalue['qty'];
							?>
							<div class="order-id"><p><?php esc_html_e( 'order id : ', 'woocommerce-order-tracker' ); ?><strong><?php echo esc_html( $order_id ); ?></strong></p></div>
							<div class="mwb-tyo-order-img-detail">
								<div class="mwb-tyo-order">
									<p class="mwb-tyo-order-name-bold"><?php echo esc_html( $productdata->get_title() ); ?></p>
									<p class="mwb-tyo-seller-name"><?php esc_html_e( 'seller : ', 'woocommerce-order-tracker' ); ?><?php echo esc_html( get_bloginfo() ); ?></p>
								</div>
								<div class="mwb-tyo-product-name">
									<?php
									if ( isset( $thumbnail ) && ! empty( $thumbnail ) ) {
										echo wp_kses_post( $thumbnail );
									} else {
										?>
										<p><img alt="<?php esc_attr_e( 'Placeholder', 'woocommerce-order-tracker' ); ?>"  class="mwb_tyo_attachment-thumbnail size-thumbnail wp-post-image mwb-img-responsive" src="<?php echo esc_attr( home_url() ); ?>/wp-content/plugins/woocommerce/assets/images/placeholder.png"></p>
										<?php
									}
									?>
								</div>
							</div>
							<div class="price-product">
								<p class="price-bold"><?php echo wp_kses_post( mwb_tyo_format_price( $product->get_price() ) ); ?></p>
								<div class="mwb-tyo-quantity">
									<label><?php esc_html_e( 'Quantity : ', 'woocommerce-order-tracker' ); ?><?php echo esc_html( $ordervalue['qty'] ); ?></label> 
								</div>
							</div>        
							<?php
						}
						?>
					</div>
					<?php
				}
			}
			?>
		</div>
	</div>	
	<?php

	if ( is_array( $mwb_tyo_previous_saved_cities ) && ! empty( $mwb_tyo_previous_saved_cities ) ) {
		foreach ( $mwb_tyo_previous_saved_cities as $mapkey => $mapvalue ) {
			if ( is_array( $mapvalue ) && ! empty( $mapvalue ) ) {
				foreach ( $mapvalue as $keymap => $valuemap ) {
					$mwb_tyo_order_send_to_cities[] = $valuemap;
					$mwb_tyo_order_send_to_cities = array_unique( $mwb_tyo_order_send_to_cities );
					$mwb_tyo_order_send_to_cities = array_values( $mwb_tyo_order_send_to_cities );
					$mwb_tyo_order_sent_cities = json_encode( $mwb_tyo_order_send_to_cities );
				}
			}
		}
	}

	if ( isset( $mwb_tyo_order_sent_cities ) && ( '' != $mwb_tyo_order_sent_cities || null != $mwb_tyo_order_sent_cities ) ) {

		echo '<input type="hidden" name="mwb_tyo_google_distance_map" id="mwb_tyo_google_distance_map" value="' . esc_attr( htmlspecialchars( $mwb_tyo_order_sent_cities ) ) . '">';
	} else {
		$mwb_tyo_order_production_add = get_option( 'mwb_tyo_order_production_address', false );
		$address[] = $mwb_tyo_order_production_add;
		$mwb_tyo_order_sent_cities = $address;
		echo '<input type="hidden" name="mwb_tyo_google_distance_map" id="mwb_tyo_google_distance_map" value="' . esc_attr( htmlspecialchars( json_encode( $mwb_tyo_order_sent_cities ) ) ) . '">';
	}

	$mwb_tyo_order_production_add = get_option( 'mwb_tyo_order_production_address', false );
	$address = $mwb_tyo_order_production_add;
	$mwb_tyo_billing_add = $order_data['billing']['city'] . '+' . $order_data['billing']['state'];

	$mwb_tyo_origin_location = get_option( 'mwb_tyo_address_get_correct', false );

	if ( isset( $mwb_tyo_origin_location ) && ( '' != $mwb_tyo_origin_location || null != $mwb_tyo_origin_location ) ) {
		$lat = get_option( 'mwb_tyo_address_latitude', false );
		$long = get_option( 'mwb_tyo_address_longitude', false );
		?>
		<input type="hidden" id="start_hidden" value="<?php echo esc_attr( $lat ); ?>">
		<input type="hidden" id="end_hidden" value="<?php echo esc_attr( $long ); ?>">
		<input type="hidden" id="billing_hidden" value="<?php echo esc_attr( $mwb_tyo_billing_add ); ?>">
		<?php
	} else {
		if( ! empty( $address ) ) {

			$geocode = file_get_contents( 'https://maps.google.com/maps/api/geocode/json?address=' . urlencode( $address ) . '&key=' . $mwb_tyo_google_api_key );
			$output = json_decode( $geocode );
	
	
			if ( isset( $output->results[0] ) && ! empty( $output->results[0] ) ) {
				$lat = $output->results[0]->geometry->location->lat;
				$long = $output->results[0]->geometry->location->lng;
	
				?>
				<input type="hidden" id="start_hidden" value="<?php echo esc_attr( $lat ); ?>">
				<input type="hidden" id="end_hidden" value="<?php echo esc_attr( $long ); ?>">
				<input type="hidden" id="billing_hidden" value="<?php echo esc_attr( $mwb_tyo_billing_add ); ?>">
				<?php
				update_option( 'mwb_tyo_address_get_correct', 'yes' );
				update_option( 'mwb_tyo_address_latitude', $lat );
				update_option( 'mwb_tyo_address_longitude', $long );
	
			}
		}
	}
	?>
	<h3><?php esc_html_e( 'Places Where Your Package Travel', 'woocommerce-order-tracker' ); ?></h3>
	<div id="map"></div>
	<div id="directions-panel"></div>
	<?php
} else {
	$return_request_not_send = __( 'Tracking Request can\'t be send. ', 'woocommerce-order-tracker' );

	/**
	 * Request not sent.
	 *
	 * @since 1.0.0
	 */
	$return_request_not_send = apply_filters( 'mwb_tyo_tracking_request_not_send', $return_request_not_send );
	echo wp_kses_post( $return_request_not_send );
	echo wp_kses_post( $reason );
}
get_footer( 'shop' );
?>
