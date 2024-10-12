<?php
/**
 * Exit if accessed directly
 *
 * @package Woocommerce_Order_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;
if ( ! class_exists( 'MWB_Track_Your_Order' ) ) {
	/**
	 * This is class for tracking order and other functionalities .
	 *
	 * @name    Mwb_Track_Your_Order
	 * @package Woocommerce_Order_Tracker
	 */
	class MWB_Track_Your_Order {

		/**
		 * This is construct of class
		 *
		 * @link http://www.wpswings.com/
		 */
		public function __construct() {
			$mwb_tyo_activation_time = get_option( 'mwb_tyo_activation_date_time', false );
			$mwb_wot_license_hash = get_option( 'mwb_tyo_license_hash' );
			$mwb_wot_license_key = get_option( 'mwb_tyo_license_key' );
			$mwb_wot_license_plugin = get_option( 'mwb_tyo_plugin_name' );
			$mwb_wot_hash = md5( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' . $mwb_wot_license_plugin . $mwb_wot_license_key );

			if ( '' == $mwb_tyo_activation_time ) {
				$current_timestamp = current_time( 'timestamp' );
				update_option( 'mwb_tyo_activation_date_time', $current_timestamp );
				$mwb_tyo_activation_time = $current_timestamp;
			}

			$mwb_tyo_after_time = strtotime( '+14 days', $mwb_tyo_activation_time );
			$mwb_tyo_currenttime = current_time( 'timestamp' );

			add_action( 'wp_enqueue_scripts', array( $this, 'mwb_tyo_public_enqueue_styles' ) );
			add_filter( 'admin_enqueue_scripts', array( $this, 'mwb_tyo_admin_scripts' ) );

			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'mwb_tyo_add_track_order_button_on_orderpage' ), 10, 2 );
			
			add_action( 'woocommerce_before_account_orders', array( $this, 'wps_wot_add_export_button_before_order_table'  ), 10, 1 );
			
			add_filter( 'template_include', array( $this, 'mwb_tyo_include_track_order_page' ), 10 );
			add_filter( 'template_include', array( $this, 'mwb_tyo_include_guest_track_order_page' ), 10 );
			add_filter( 'template_include', array( $this, 'mwb_ordertracking_page' ), 10 );
			add_action( 'init', array( $this, 'mwb_tyo_register_custom_order_status' ), 5 );

			add_filter( 'wc_order_statuses', array( $this, 'mwb_tyo_add_custom_order_status' ) );
			add_action( 'admin_menu', array( $this, 'mwb_tyo_tracking_order_meta_box' ) );
			add_action( 'admin_notices', array( $this, 'mwb_tyo_notifiaction_msg' ) );
			add_action( 'save_post', array( $this, 'mwb_tyo_save_delivery_date_meta' ) );
			add_action( 'woocommerce_process_shop_order_meta', array( $this, 'mwb_tyo_save_delivery_date_meta_hpos' ) );
			add_action( 'save_post', array( $this, 'mwb_tyo_save_shipping_services_meta' ) );
			add_action( 'woocommerce_process_shop_order_meta', array( $this, 'mwb_tyo_save_shipping_services_meta_hpos' ) );
			add_action( 'save_post', array( $this, 'mwb_tyo_save_custom_shipping_cities_meta' ) );
			add_action( 'woocommerce_process_shop_order_meta', array( $this, 'mwb_tyo_save_custom_shipping_cities_meta_hpos' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'mwb_tyo_scripts' ) );
			add_action( 'woocommerce_order_status_changed', array( $this, 'mwb_tyo_track_order_status' ), 10, 3 );
			add_action( 'woocommerce_order_details_after_order_table', array( $this, 'mwb_tyo_track_order_button' ) );
			add_action( 'woocommerce_order_details_before_order_table_items', array( $this, 'mwb_tyo_track_order_info' ) );
			add_action( 'wp_ajax_mwb_mwb_create_custom_order_status', array( $this, 'mwb_mwb_create_custom_order_status' ) );
			add_action( 'wp_ajax_mwb_mwb_delete_custom_order_status', array( $this, 'mwb_mwb_delete_custom_order_status' ) );

			add_action( 'wp_ajax_mwb_form_subbmission_data_from_plugin', array( $this, 'mwb_form_subbmission_data_from_plugin' ) );

			add_action( 'wp_ajax_mwb_selected_template', array( $this, 'mwb_tyo_selected_template' ) );
			add_action( 'init', array( $this, 'mwb_thickbox_view' ), 10 );

			add_filter( 'woocommerce_account_menu_items', array( $this, 'mwb_tyo_add_shipment_tracking_menu_on_myaccount_page' ), 10 );
			add_action( 'woocommerce_account_mwb-tyo-shipment-tracking_endpoint', array( $this, 'mwb_tyo_shipment_tracking_frontend_page' ), 10 );
			add_action( 'init', array( $this, 'mwb_tyo_shipment_tracking_endpoints' ) );
			add_filter( 'woocommerce_account_orders_columns', array( $this, 'mwb_tyo_tracking_number_on_order_page' ), 10 );
			add_action( 'woocommerce_my_account_my_orders_column_mwb-tyo-shipment-tracking-number', array( $this, 'mwb_tyo_show_tacking_number' ), 10 );
			add_action( 'woocommerce_my_account_my_orders_column_mwb_enhanced_add-column', array( $this, 'mwb_tyo_show_live_tacking_number' ), 10 );
			add_action( 'wp_print_scripts', array( $this, 'mwb_tyo_add_custom_order_status_icon' ) );
			add_action( 'wp_ajax_mwb_tyo_first_loading_order_status', array( $this, 'mwb_tyo_first_loading_order_status' ) );
			add_action( 'wp_ajax_mwb_tyo_reorder_order_status', array( $this, 'mwb_tyo_reorder_order_status' ) );
			add_action( 'mwb_woocommerce_before_main_content', array( $this, 'mwb_wot_before_main_content' ), 10, 1 );
			add_filter( 'mwb_tyo_add_diffrent_shipping_services', array( $this, 'mwb_tyo_other_shipping_services' ), 10, 1 );
			add_action( 'wp_ajax_mwb_wot_register_license', array( $this, 'mwb_wot_check_license' ) );
			add_filter( 'sidebars_widgets', array( $this, 'mwb_tyo_disable_all_widgets' ), 10 );
			add_action( 'wp_ajax_mwb_tyo_insert_address_for_tracking', array( $this, 'mwb_tyo_insert_address_for_tracking' ) );
			add_action( 'wp_ajax_mwb_provider_subbmission_data_from_plugin', array( $this, 'mwb_tyo_enhanced_insert_provider_url_for_tracking' ) );
			add_action( 'wp_ajax_mwb_provider_remove_company_data_from_plugin', array( $this, 'mwb_tyo_enhanced_remove_company_provider_url_for_tracking' ) );
			add_action( 'admin_menu', array( $this, 'mwb_tyo_woo_ehanced_shipment_tracking_customer_metaboxes' ), 20 );
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'adding_custom_bulk_actions_edit_product' ), 10, 1 );
			add_action( 'handle_bulk_actions-edit-shop_order', array( $this, 'wot_bulk_process_custom_status' ), 20, 3 );
			add_action( 'restrict_manage_posts', array( $this, 'wot_add_html_to_export_order_listing_page' ) );
			add_action( 'wp_ajax_wps_wot_export_order_using_order_status', array( $this, 'wps_wot_export_order_using_order_status_callback' ) );
			add_action( 'wp_ajax_wps_wot_export_my_orders', array( $this, 'wps_wot_export_my_orders_callback' ) );
			add_action( 'wp_ajax_nopriv_wps_wot_export_my_orders_guest_user', array( $this, 'wps_wot_export_my_orders_guest_user_callback' ) );
			add_action( 'woocommerce_order_status_changed', array( $this, 'wps_wot_send_mail_on_pending_status' ), 10, 3 );
			$mwb_enhanced_gen_set = get_option( 'mwb_tyo_general_settings_saved', false );
			if ( ! empty( $mwb_enhanced_gen_set ) ) {

				if ( 'on' == $mwb_enhanced_gen_set['enable_plugin'] && ! empty( $mwb_enhanced_gen_set['enable_plugin'] ) ) {
					add_action( 'admin_menu', array( $this, 'mwb_tyo_woo_ehanced_shipment_courier_company_metaboxes' ), 10 );
					add_filter( 'mwb_tyo_add_more_columns_on_order_table', array( $this, 'mwb_tyo_woo_add_column' ), 10, 1 );
				}
			}
			add_action( 'save_post', array( $this, 'mwb_tyo_woo_ehnanced_once_update_customer_notes' ) );
			add_action( 'woocommerce_process_shop_order_meta', array( $this, 'mwb_tyo_woo_ehnanced_once_update_customer_notes_hpos' ) );
			add_shortcode( 'wps_create_tracking_page', array( $this, 'wps_create_tracking_page_shortcode_callback' ) );
			add_shortcode( 'wps_track_order_form', array( $this, 'wps_track_order_form_shortcode_callback' ) );

		}

		/**
		 * Adding field to export orders.
		 *
		 * @return void
		 */
		public function wot_add_html_to_export_order_listing_page(){
			global $pagenow, $post_type;
			if ( 'shop_order' !== $post_type && 'edit.php' !== $pagenow ) {
				return;
			}
			// $current = isset( $_GET['filter_booking'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_booking'] ) ) : ''; // phpcs:ignore WordPress.Security
			?>
			<select id="wot_select_order_status">
				<option value=""><?php esc_html_e( 'Choose Status', 'woocommerce-order-tracker' ); ?></option>
				<?php $statuses = wc_get_order_statuses();
				foreach( $statuses as $key => $value ) {
					if( 'wc-checkout-draft' != $key ) { ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html($value); ?></option>

						<?php
					}
				} ?>
			
			</select>
			<input type="button" id="wot_export_order" class="button" value="<?php esc_html_e( 'Export', 'woocommerce-order-tracker' ); ?>">
			<?php
		}

		/**
		 * Callback function of export order.
		 *
		 * @return void
		 */
		public function wps_wot_export_order_using_order_status_callback(){
			$order_status = isset( $_POST['order_status'] ) ? $_POST['order_status'] : '';
			if( ! empty( $order_status ) ) {
				$_orders = get_posts(
					array(

						'post_status' => $order_status,
						'post_type'   => 'shop_order',
						'numberposts' => -1,
						'fields' => 'ids',
					)
				);

				
				$order_details = $this->wps_wot_get_csv_order_details( $_orders );
				$main_arr = array(
					'status' => 'success',
					'file_name' => 'wps_order_details',
					'order_data' => $order_details,
				);
			} else {
				$main_arr = array(
					'status' => 'failed',
				);
			}

			echo wp_json_encode( $main_arr );
			wp_die();
		}

		/**
		 * Function for ajax callback.
		 *
		 * @return array
		 */
		public function wps_wot_export_my_orders_callback() {
			$_orders = get_posts(
				array(

					'post_status' => array_keys(wc_get_order_statuses()),
					'post_type'   => 'shop_order',
					'numberposts' => -1,
					'meta_key' => '_customer_user',
					'meta_value' => get_current_user_id(),
					'fields' => 'ids',
				)
			);

			if( ! empty( $_orders ) ) {
				$order_details = $this->wps_wot_get_csv_order_details( $_orders );
				$main_arr = array(
					'status' => 'success',
					'file_name' => 'wps_order_details',
					'order_data' => $order_details,
				);
			} else {

				$main_arr = array(
					'status' => 'failed',
				);
			}
			echo wp_json_encode( $main_arr );
			wp_die();
		}

		/**
		 * Function for ajax callback for guest user export
		 *
		 * @return array
		 */
		public function wps_wot_export_my_orders_guest_user_callback() {

			$email = isset( $_POST['email'] ) ? $_POST['email'] : '';
			$_orders = array();
			if( ! empty( $email ) ) {
				$_orders_temp = get_posts(
					array(

						'post_status' => array_keys(wc_get_order_statuses()),
						'post_type'   => 'shop_order',
						'numberposts' => -1,
						'fields' => 'ids',
					)
				);
				
				if( ! empty( $_orders_temp ) && is_array( $_orders_temp ) ) { 
					foreach($_orders_temp as $key => $id ) {
						$_order = wc_get_order( $id );
						if( $_order->get_billing_email() == $email ) {
							$_orders[] = $id;
						}
					}
					$order_details = $this->wps_wot_get_csv_order_details( $_orders );
					$main_arr = array(
						'status' => 'success',
						'file_name' => 'wps_order_details',
						'order_data' => $order_details,
					);
				}
				
			} else {
				$main_arr = array(
					'status' => 'failed',
				);
			}

			echo wp_json_encode( $main_arr );
			wp_die();

		}

		/**
		 * Function for sending mail.
		 *
		 * @param int $order_id is the id of order.
		 * @param string $old_status is the old status.
		 * @param string $new_status is the new changed.
		 * @return void
		 */
		public function wps_wot_send_mail_on_pending_status( $order_id, $old_status, $new_status ) {
			
			if( 'pending' == $new_status ) {
				$_order = wc_get_order( $order_id );
				
				$customer_email = $_order->get_billing_email();
				$mailer = WC()->mailer();
				$customer_name = $_order->get_billing_first_name() . ' ' . $_order->get_billing_last_name();
				//format the email
				$recipient = $customer_email;
				$subject = __("Hi! Your Order status on Pending!", 'woocommerce-order-status');
				$content = '<div><p>Hi! ' . $customer_name . ' Your order is on Pending! </p>' ;
				$content .= '<p> Please <a href="' . esc_url( $_order->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay for this order', 'woocommerce-order-tracker' ) . '</a> and Enjoy your day!</p></div>';
				$headers = "Content-Type: text/html\r\n";

				//send the email through wordpress
				$mailer->send( $recipient, $subject, $content, $headers );
			}
		}


		/**
		 * Function to return order details.
		 *
		 * @param array $_orders contains array of order ids.
		 * @return array
		 */
		public function wps_wot_get_csv_order_details( $_orders ){
			$order_details = array();
			$order_details[] = array(
				__( 'Order Id', 'woocommerce-order-tracker' ),
				__( 'Order Status', 'woocommerce-order-tracker' ),
				__( 'Order Total', 'woocommerce-order-tracker' ),
				__( 'Order Items', 'woocommerce-order-tracker' ),
				__( 'Payment Method', 'woocommerce-order-tracker' ),
				__( 'Billing Name', 'woocommerce-order-tracker' ),
				__( 'Billing Email', 'woocommerce-order-tracker' ),
				__( 'Billing Address', 'woocommerce-order-tracker' ),
				__( 'Billing Contact', 'woocommerce-order-tracker' ),
				__( 'Order date', 'woocommerce-order-tracker' ),
				
			);

			foreach( $_orders as $index => $_order_id ) {
				$order = wc_get_order( $_order_id );
				$order_total = $order->get_total();
				$payment_method = $order->get_payment_method_title();
				$billing_name = $order->get_billing_first_name(); 
				$billing_name .= ' ';
				$billing_name .= $order->get_billing_last_name();
				$billing_email  = $order->get_billing_email();
				$billing_address = $order->get_billing_company();
				$billing_address .=' ';
				$billing_address .= $order->get_billing_address_1();
				$billing_address .=' ';
				$billing_address .= $order->get_billing_address_2();
				$billing_address .=' ';
				$billing_address .= $order->get_billing_city();
				$billing_address .=' ';
				$billing_address .= $order->get_billing_state();
				$billing_address .=' ';
				$billing_address .= $order->get_billing_country();
				$billing_address .=' ';
				$billing_address .= $order->get_billing_postcode();
				
				$billing_contact = $order->get_billing_phone();
				$order_date = $order->get_date_created()->date('F d Y H:i ');
				$order_items = '';
				$_order_status = $order->get_status();
				foreach ( $order->get_items() as $item_id => $item ) { 
					$order_items .= $item->get_name() . ' ';
				}
				$order_details[] = array(
					$_order_id,
					$_order_status,
					$order_total,
					$order_items,
					$payment_method,
					$billing_name,
					$billing_email,
					$billing_address,
					$billing_contact,
					$order_date,
				);
			}
			return $order_details;
		}

		/**
		 * Enqueue style for public.
		 *
		 * @return void
		 */
		public function mwb_tyo_public_enqueue_styles() {
			wp_enqueue_style( 'mwb-tyo-public-style', MWB_TRACK_YOUR_ORDER_URL . 'assets/css/mwb-tyo-public-style.css', MWB_TRACK_YOUR_ORDER_VERSION, true );

		}
		/**
		 * Function to add coluimn.
		 *
		 * @param array $column is array of  column.
		 * @return array
		 */
		public function mwb_tyo_woo_add_column( $column ) {
			$column['mwb_enhanced_add-column'] = __( 'Live Tracking', 'woocommerce-order-tracker' );
			return $column;
		}

		/**
		 * Function to deal with courier.
		 *
		 * @return void
		 */
		public function mwb_tyo_woo_ehanced_shipment_courier_company_metaboxes() {
			$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ): 'shop_order';
			add_meta_box( 'mwb_tyo_enhanced_cuorier_company', __( 'Enter Courier Company', 'woocommerce-order-tracker' ), array( $this, 'mwb_tyo_enhanced_create_courier_company_callback' ), $screen, 'side', 'high' );
		}

		/**
		 * Courier callback function.
		 *
		 * @return void
		 */
		public function mwb_tyo_enhanced_create_courier_company_callback($post_or_order_object) {
			
			$mwb_enhanced_gen_set = get_option( 'mwb_tyo_general_settings_saved', false );
			$mwb_enhanced_courier_company = $mwb_enhanced_gen_set['providers_data'];
			if( $post_or_order_object instanceof WC_Order ){
				$mwb_post_id = $post_or_order_object->get_id();
			} else {
				$mwb_post_id = $post_or_order_object->ID;
			}
			// $mwb_post_id = $post_or_order_object->ID;
			$mwb_tyo_enhanced_cuorier_company = wps_order_tracker_get_meta_data( $mwb_post_id, 'mwb_tyo_enhanced_order_company', true );
			$mwb_tyo_enhanced_track_no = wps_order_tracker_get_meta_data( $mwb_post_id, 'mwb_tyo_enhanced_tracking_no', true );

			?>

			<label><?php esc_html_e( 'Select the Company', 'woocommerce-order-tracker' ); ?></label>
			<select name="mwb_enhnaced_tyo_courier">
				<?php
				if ( ! empty( $mwb_enhanced_courier_company ) ) {
					foreach ( $mwb_enhanced_courier_company as $key => $value ) {
						?>
							
						<option 
						<?php
						if ( ! empty( $mwb_tyo_enhanced_cuorier_company ) ) {
							selected( $value, $mwb_tyo_enhanced_cuorier_company );}
						?>
							 
						value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $key ); ?></option>	  
						?>
						<?php
					}
				}
				?>
			</select>
			<div><?php esc_html_e( 'Tracking Number', 'woocommerce-order-tracker' ); ?></div>
			<input type="hidden" name="mwb_enhanced_tyo_tracking_nonce_verification" value="<?php wp_create_nonce( 'mwb_enhanced_tyo_tracking_nonce' ); ?>">
			<input type="text" name="mwb_enhanced_tyo_tracking" value="
			<?php
			if ( ! empty( $mwb_tyo_enhanced_track_no ) ) {
				echo esc_html( $mwb_tyo_enhanced_track_no );}
			?>
				"></input>
			<?php
		}

		/**
		 * Update customer note.
		 *
		 * @return void
		 */
		public function mwb_tyo_woo_ehnanced_once_update_customer_notes() {
			
			 global $post;
			 $value_check = isset( $_POST['mwb_enhanced_tyo_tracking_nonce_verification'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_enhanced_tyo_tracking_nonce_verification'] ) ) : '';
			 wp_verify_nonce( $value_check, 'mwb_enhanced_tyo_tracking_nonce' );
			if ( isset( $post ) ) {
				$mwb_post_id = $post->ID;
				$mwb_tyo_enhanced_customer_note = isset( $_POST['mwb_tyo_enhanced_customer_note'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_enhanced_customer_note'] ) ) : '';
				$mwb_tyo_enhanced_company_courier = isset( $_POST['mwb_enhnaced_tyo_courier'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_enhnaced_tyo_courier'] ) ) : '';
				$mwb_tyo_enhanced_tracking_no = isset( $_POST['mwb_enhanced_tyo_tracking'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_enhanced_tyo_tracking'] ) ) : '';
				wps_order_tracker_update_meta_data( $mwb_post_id, 'mwb_tyo_enhanced_cn', $mwb_tyo_enhanced_customer_note );
				wps_order_tracker_update_meta_data( $mwb_post_id, 'mwb_tyo_enhanced_order_company', $mwb_tyo_enhanced_company_courier );
				wps_order_tracker_update_meta_data( $mwb_post_id, 'mwb_tyo_enhanced_tracking_no', $mwb_tyo_enhanced_tracking_no );
			}
		}
		/**
		 * Update customer note.
		 *
		 * @return void
		 */
		public function mwb_tyo_woo_ehnanced_once_update_customer_notes_hpos($order_id) {
			
		
			   $mwb_post_id = $order_id;
			   $mwb_tyo_enhanced_customer_note = isset( $_POST['mwb_tyo_enhanced_customer_note'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_enhanced_customer_note'] ) ) : '';
			   $mwb_tyo_enhanced_company_courier = isset( $_POST['mwb_enhnaced_tyo_courier'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_enhnaced_tyo_courier'] ) ) : '';
			   $mwb_tyo_enhanced_tracking_no = isset( $_POST['mwb_enhanced_tyo_tracking'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_enhanced_tyo_tracking'] ) ) : '';
			   wps_order_tracker_update_meta_data( $mwb_post_id, 'mwb_tyo_enhanced_cn', $mwb_tyo_enhanced_customer_note );
			   wps_order_tracker_update_meta_data( $mwb_post_id, 'mwb_tyo_enhanced_order_company', $mwb_tyo_enhanced_company_courier );
			   wps_order_tracker_update_meta_data( $mwb_post_id, 'mwb_tyo_enhanced_tracking_no', $mwb_tyo_enhanced_tracking_no );

	   }
		/**
		 * Shipment tracking metabox
		 *
		 * @return void
		 */
		public function mwb_tyo_woo_ehanced_shipment_tracking_customer_metaboxes() {
			$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ): 'shop_order';
			add_meta_box( 'mwb_tyo_enhanced_customer_note', __( 'Enter Customer Notes', 'woocommerce-order-tracker' ), array( $this, 'mwb_tyo_enhanced_create_customer_note_metabox' ), $screen, 'side', 'high' );

		}

		/**
		 * Create customer notes.
		 *
		 * @return void
		 */
		public function mwb_tyo_enhanced_create_customer_note_metabox($post_or_order_object) {
			global $post;
			
			if( $post_or_order_object instanceof WC_Order ){
				$mwb_post_id = $post_or_order_object->get_id();
			} else {
				$mwb_post_id = $post_or_order_object->ID;
			}
			$mwb_tyo_enhanced_customer_note = wps_order_tracker_get_meta_data( $mwb_post_id, 'mwb_tyo_enhanced_cn', true );
			if ( ! empty( $mwb_tyo_enhanced_customer_note ) ) {
				$mwb_tyo_customer_val = $mwb_tyo_enhanced_customer_note;
			} else {
				$mwb_tyo_customer_val = '';
			}
			?>
			<label for="mwb_enhanced_customer_note"><?php esc_html_e( 'Customer Note', 'woocommerce-order-tracker' ); ?></label>
			<?php
			$tip_descriptions = __( 'Enter The customer note that will display on the order tracking page.', 'woocommerce-order-tracker' );
			echo wp_kses_post( wc_help_tip( $tip_descriptions ) );
			?>
			<textarea id="mwb_enhanced_customer_note" name="mwb_tyo_enhanced_customer_note"><?php echo esc_html( htmlspecialchars( $mwb_tyo_customer_val ) ); ?></textarea>
			<?php
		}

		/**
		 * Remove company provider url.
		 *
		 * @return void
		 */
		public function mwb_tyo_enhanced_remove_company_provider_url_for_tracking() {
			check_ajax_referer( 'mwb-tyo-ajax-seurity-string', 'nonce' );
			$mwb_enhanced_toy_default_company = ( ! empty( $_POST['mwb_company_name'] ) ) ? sanitize_text_field( wp_unslash( $_POST['mwb_company_name'] ) ) : '';
			$mwb_enhanced_toy_provider_array = get_option( 'mwb_tyo_courier_companies', false );

			if ( ! empty( $mwb_enhanced_toy_provider_array ) && ! empty( $mwb_enhanced_toy_default_company ) ) {
				unset( $mwb_enhanced_toy_provider_array[ $mwb_enhanced_toy_default_company ] );
				update_option( 'mwb_tyo_courier_companies', $mwb_enhanced_toy_provider_array );
			}
			wp_die();
		}

		/**
		 * Insert provider url.
		 *
		 * @return void
		 */
		public function mwb_tyo_enhanced_insert_provider_url_for_tracking() {
			check_ajax_referer( 'mwb-tyo-ajax-seurity-string', 'nonce' );
			$mwb_enhanced_toy_provider_name = ( ! empty( $_POST['mwb_company_name'] ) ) ? sanitize_text_field( wp_unslash( $_POST['mwb_company_name'] ) ) : '';
			$mwb_enhanced_toy_provider_url = ( ! empty( $_POST['mwb_company_url'] ) ) ? sanitize_text_field( wp_unslash( $_POST['mwb_company_url'] ) ) : '';
			$mwb_carrier_company = get_option( 'mwb_tyo_courier_companies', false );
			$mwb_enhanced_toy_provider_array = array( $mwb_enhanced_toy_provider_name => $mwb_enhanced_toy_provider_url );
			$mwb_enhanced_final_array = array_merge( $mwb_carrier_company, $mwb_enhanced_toy_provider_array );

			update_option( 'mwb_tyo_courier_companies', $mwb_enhanced_final_array );
			echo json_encode( $mwb_enhanced_toy_provider_array );
			wp_die();
		}

		/**
		 * Add tracking  link.
		 *
		 * @param object $order is the object of order.
		 * @return void
		 */
		public function mwb_tyo_add_17track_link( $order ) {
			if ( isset( $order ) ) {
				$order_id = $order->get_id();
				$tracking_number = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_package_tracking_number', true );
				?>
				<a href="#" id="YQElem1"><?php esc_html_e( 'Track', 'woocommerce-order-tracker' ); ?></a>
				<?php
			}
		}

		/**
		 * Insert address for tracking.
		 *
		 * @return void
		 */
		public function mwb_tyo_insert_address_for_tracking() {
			check_ajax_referer( 'mwb_tyo_new_tracking_nonce', 'nonce' );
			$mwb_tyo_address_collections = isset( $_POST['mwb_tyo_addresses'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_addresses'] ) ) : '';
			$mwb_tyo_previous_address = get_option( 'mwb_tyo_old_addresses', array() );
			if ( is_array( $mwb_tyo_previous_address ) ) {

				$mwb_tyo_previous_address[ 'mwb_address_' . $mwb_tyo_address_collections ] = $mwb_tyo_address_collections;
				update_option( 'mwb_tyo_old_addresses', $mwb_tyo_previous_address );
			}

			$mwb_tyo_address_array_value = get_option( 'mwb_tyo_old_addresses', false );
			echo json_encode( $mwb_tyo_address_array_value );
			wp_die();
		}


		/**
		 * Disable all widgets.
		 *
		 * @param array $sidebars_widgets is array.
		 * @return array
		 */
		public function mwb_tyo_disable_all_widgets( $sidebars_widgets ) {

			$track_page_id = get_option( 'mwb_tyo_tracking_page', array() );
			if ( 0 < strpos( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', '/track-your-order' ) || 0 < strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/guest-track-order-form' ) || 0 < strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/track-fedex-order' ) ) {

				$sidebars_widgets = array( false );
			}
			return $sidebars_widgets;
		}


		/**
		 * This function is for accepting license for order tracker plugin
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_wot_check_license() {
			check_ajax_referer( 'mwb-tyo-ajax-seurity-string', 'mwb_nonce' );
			$mwb_license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

			$mwb_admin_name = '';
			$mwb_admin_email = get_option( 'admin_email', null );
			$mwb_admin_details = get_user_by( 'email', $mwb_admin_email );
			if ( isset( $mwb_admin_details->data ) ) {
				if ( isset( $mwb_admin_details->data->display_name ) ) {
					$mwb_admin_name = $mwb_admin_details->data->display_name;
				}
			}

			$mwb_license_arr = array(
				'license_key' => $mwb_license_key,
				'domain_name' => isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '',
				'admin_name' => $mwb_admin_name,
				'admin_email' => $mwb_admin_email,
				'plugin_name' => 'woocommerce order tracker',
			);

			$curl = curl_init();

			curl_setopt_array(
				$curl,
				array(
					CURLOPT_URL => 'https://wpswings.com/codecanyon/validate_license.php',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => '',
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_SSL_VERIFYHOST => false,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => 'POST',
					CURLOPT_POSTFIELDS => http_build_query( $mwb_license_arr ),
					CURLOPT_HTTPHEADER => array(
						'cache-control: no-cache',
						'content-type: application/x-www-form-urlencoded',
						'postman-token: d9d9a28b-94cc-7a0e-47b2-5bd10aa7aa89',
					),
				)
			);

			$mwb_res = curl_exec( $curl );

			$err = curl_error( $curl );

			curl_close( $curl );

			if ( $err ) {
				echo 'cURL Error #:' . esc_html( $err );
			}
			$mwb_res = json_decode( $mwb_res, true );

			if ( true == $mwb_res['status'] ) {
				update_option( 'mwb_tyo_license_hash', $mwb_res['hash'] );
				update_option( 'mwb_tyo_plugin_name', 'woocommerce order tracker' );
				update_option( 'mwb_tyo_license_key', $mwb_res['mwb_key'] );
				echo json_encode(
					array(
						'status' => true,
						'msg' => __(
							'Successfully Verified',
							'woocommerce-order-tracker'
						),
					)
				);
			} else if ( false == $mwb_res['status'] ) {
				echo json_encode(
					array(
						'status' => false,
						'msg' => $mwb_res['msg'],
					)
				);
			}
			wp_die();
		}

		/**
		 * Other shipping services
		 *
		 * @param array $shipping_company list of shipping company.
		 * @return array
		 */
		public function mwb_tyo_other_shipping_services( $shipping_company ) {
			$mwb_tyo_enable_canadapost_api = get_option( 'mwb_tyo_enable_canadapost_tracking', false );
			$mwb_tyo_enable_usps_api = get_option( 'mwb_tyo_enable_usps_tracking', false );
			if ( isset( $mwb_tyo_enable_canadapost_api ) && 'yes' == $mwb_tyo_enable_canadapost_api ) {

				$shipping_company['canada_post'] = __( 'Canada Post', 'woocommerce-order-tracker' );
			}
			if ( isset( $mwb_tyo_enable_usps_api ) && 'yes' == $mwb_tyo_enable_usps_api ) {
				$shipping_company['usps'] = __( 'USPS', 'woocommerce-order-tracker' );
			}
			return $shipping_company;
		}

		/**
		 * Display before main content.
		 *
		 * @param integer $order_id is order id.
		 * @return void
		 */
		public function mwb_wot_before_main_content( $order_id ) {

			$mwb_status_change_time_template = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time_template', true );

			if ( empty( $mwb_status_change_time_template ) ) {

				$mwb_tyo_email_notifier = get_option( 'mwb_tyo_email_notifier', 'no' );
				$mwb_tyo_selected_date_format = get_option( 'mwb_tyo_selected_date_format', false );
				$order = new WC_Order( $order_id );

				if ( '3.0.0' > WC()->version ) {
					$mwb_date_on_order_change = $order->modified_date;

				} else {
					$mwb_tyo_order_data_status = $order->get_data()['status'];
					$mwb_tyo_order_data_modified_date = $order->get_data()['date_modified'];
					$date_on_order_change = $order->get_data();

					if ( isset( $mwb_tyo_order_data_status ) && ! empty( $mwb_tyo_order_data_status ) ) {

						$change_order_status = $order->get_data()['status'];
						get_the_modified_date( $order );
						$mwb_date_on_order_change = $date_on_order_change['date_modified']->date( 'd F, Y H:i' );
						
					} else {
						$change_order_status = '';
						$mwb_date_on_order_change = '';
					}
				}

				if ( isset( $mwb_tyo_selected_date_format ) && ! empty( $mwb_tyo_selected_date_format ) ) {
					$mwb_tyo_converted_date  = strtotime( $mwb_date_on_order_change );
					$mwb_modified_date = date_i18n( $mwb_tyo_selected_date_format, $mwb_tyo_converted_date );

				} else {
					$mwb_modified_date = date_i18n( 'F d, g:i a', strtotime( $mwb_date_on_order_change ) );
				}

				$mwb_status_change_time = array();
				$mwb_status_change_time_temp = array();
				$mwb_status_change_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time', true );
				$mwb_status_change_time_temp = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time_temp', true );
				$mwb_status_change_time_template2 = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time_template', true );
				$mwb_status_change_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time', true );
				if( ! empty( $mwb_status_change_time ) && ! empty( $mwb_status_change_time_temp ) ) {

					$mwb_status_change_time[ 'wc-' . $change_order_status ] = $mwb_modified_date;
					$mwb_status_change_time_temp[][ 'wc-' . $change_order_status ] = $mwb_modified_date;
				}

				wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_onchange_time_template', $mwb_status_change_time_temp );

			}
		}

		/**
		 * This function is for selecting default woocommerce order statuses in database
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_first_loading_order_status() {
			check_ajax_referer( 'mwb_tyo_status_nonce', 'nonce' );
			$mwb_tyo_all_first_time_status = isset( $_POST['mwb_first_selected_all_status'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_first_selected_all_status'] ) ) : '';
			update_option( 'mwb_tyo_statuses_for_order_tracking_on_activation', $mwb_tyo_all_first_time_status );
			echo 'success';
			wp_die();
		}


		/**
		 * This function is for reordering of selected order statuses
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_reorder_order_status() {
			check_ajax_referer( 'mwb_tyo_status_nonce', 'nonce' );
			$mwb_tyo_old_selected_statuses = get_option( 'mwb_tyo_new_settings_custom_statuses_for_order_tracking', false );

			$mwb_tyo_reorder_status_position = isset( $_POST['mwb_wot_selected_method'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_wot_selected_method'] ) ) : '';

			if ( is_array( $mwb_tyo_reorder_status_position ) && ! empty( $mwb_tyo_reorder_status_position ) ) {
				foreach ( $mwb_tyo_reorder_status_position as $reorder_key => $reorder_value ) {

					if ( in_array( $reorder_value, $mwb_tyo_old_selected_statuses ) ) {

						$mwb_tyo_reorder_status_position_final[] = $reorder_value;
					}
				}
			}

			update_option( 'mwb_tyo_new_settings_custom_statuses_for_order_tracking', $mwb_tyo_reorder_status_position_final );
			esc_html_e( 'success', 'woocommerce-order-tracker' );
			wp_die();
		}

		/**
		 * Notification msg
		 *
		 * @return void
		 */
		public function mwb_tyo_notifiaction_msg() {
			$mwb_tyo_msg = get_option( 'mwb_tyo_warning_notification_message', false );
			if ( isset( $mwb_tyo_msg ) && '' != $mwb_tyo_msg ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $mwb_tyo_msg ); ?></p>
				</div>
				<?php
			}
		}

		/**
		 * This function is for accepting update request from clients.
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_form_subbmission_data_from_plugin() {
			check_ajax_referer( 'mwb-tyo-ajax-seurity-string', 'nonce' );
			$mwb_tyo_notification = isset( $_POST['notification'] ) ? sanitize_text_field( wp_unslash( $_POST['notification'] ) ) : 'no';
			update_option( 'mwb_tyo_warning_notification', $mwb_tyo_notification );
			mwb_tyo_daily_notification_cron();
		}

		/**
		 * This function is to include the image icons on order edit page for custom order status
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_add_custom_order_status_icon() {
			if ( ! is_admin() ) {
				return;
			}

			$custom_order_status = get_option( 'mwb_tyo_new_custom_order_status', array() );
			$custom_order_image = get_option( 'mwb_tyo_new_custom_order_image', false );
			$mwb_home_url = get_home_url();
			foreach ( $custom_order_status as $key => $value ) {
				foreach ( $value as $img_key => $img_value ) {
					$url = str_replace( $mwb_home_url, '..', $custom_order_image[ $img_key ] );
					?>
					 <style>
					/* Add custom status order icons */
					.widefat .column-order_status mark<?php echo '.' . esc_html( $img_key ); ?> { 
						background-image: url(<?php echo esc_html( $url ); ?>);
						width:17px!important;
						height:17px!important;
						background-size: 100% 100%;
					}

					/* Repeat for each different icon; tie to the correct status */

				</style> 
					<?php
				}
			}
		}

		/**
		 * Function to track.
		 *
		 * @param array $columns is list of columns.
		 * @return array
		 */
		public function mwb_tyo_tracking_number_on_order_page( $columns ) {
			unset( $columns['order-actions'] );
			$track_17 = get_option( 'mwb_tyo_enable_17track_integration', false );
			if ( isset( $track_17 ) && ( 'yes' == $track_17 ) ) {

				$columns['mwb-tyo-shipment-tracking-number'] = __( '17Tracking Id', 'woocommerce-order-tracker' );
			}

			/**
			 * Add more column in order table.
			 *
			 * @since 1.0.0
			 */
			$columns = apply_filters( 'mwb_tyo_add_more_columns_on_order_table', $columns );
			$columns['order-actions'] = '&nbsp;';
			return $columns;

		}


		/**
		 * This function is to display tracking number on myaccount order section
		 *
		 *  @param object $order is an object.
		 *  
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_show_tacking_number( $order ) {
			if ( '3.0.0' > WC()->version ) {
				$mwb_order = $order->post;
				$order_id = $mwb_order->ID;
			} else {
				$mwb_order = $order->get_data();
				$order_id = $mwb_order['id'];
			}
			$mwb_tracking_number = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_package_tracking_number', true );
			if ( isset( $mwb_tracking_number ) && '' != $mwb_tracking_number ) {

				echo '<a href="#" class="button mwb_tyo_17track" id="YQElem1">' . esc_html( $mwb_tracking_number ) . '</a>';
			} else {
				$value = __( 'N/A', 'woocommerce-order-tracker' );
				echo esc_html( $value );
			}
		}


		/**
		 * This function is to display tracking number on myaccount order section
		 *
		 * @param object $order is an object.
		 *  
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_show_live_tacking_number( $order ) {
			if ( '3.0.0' > WC()->version ) {
				$mwb_order = $order->post;
				$order_id = $mwb_order->ID;
			} else {
				$mwb_order = $order->get_data();
				$order_id = $mwb_order['id'];
			}
			$mwb_tracking_url = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_enhanced_order_company', true );
			$mwb_tracking_number = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_enhanced_tracking_no', true );
			if ( isset( $mwb_tracking_number ) && '' != $mwb_tracking_number ) {

				echo '<a id="mwb_live_tracking" href="' . esc_html( $mwb_tracking_url ) . '" class="button">' . esc_html( $mwb_tracking_number ) . '</a>';
			} else {
				$value = __( 'N/A', 'woocommerce-order-tracker' );
				echo esc_html( $value );
			}
		}


		/**
		 * Shipment tracking menu on my account page.
		 *
		 * @param array $items contains items.
		 * @return array
		 */
		public function mwb_tyo_add_shipment_tracking_menu_on_myaccount_page( $items ) {
			unset( $items['customer-logout'] );

			$items['customer-logout'] = __( 'Logout', 'woocommerce-order-tracker' );
			
			/**
			 * Add more menu in my account page.
			 *
			 * @since 1.0.0
			 */
			$items = apply_filters( 'mwb_tyo_add_more_menu_on_myaccount_page', $items );
			return $items;
		}

		/**
		 * This function is to include the tracking page on myaccount page under shipment tracking menu option
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_shipment_tracking_frontend_page() {
			 include MWB_TRACK_YOUR_ORDER_PATH . 'template/mwb-order-tracking-page.php';
		}


		/**
		 * This function is to define the endpoint of shipment tracking option in myaccount menu option
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_shipment_tracking_endpoints() {
			add_rewrite_endpoint( 'mwb-tyo-shipment-tracking', EP_ROOT | EP_PAGES );
		}

		/**
		 * Tracking view.
		 *
		 * @return void
		 */
		public function mwb_thickbox_view() {
			add_thickbox();
		}

		/**
		 * This function is to include CSS and js
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_scripts() {

			$url = plugins_url();
			$mwb_tyo_enable_track_order_feature = get_option( 'mwb_tyo_enable_track_order_feature', 'no' );
			$mwb_tyo_enable_track_order_popup = get_option( 'mwb_tyo_enable_track_order_popup', 'no' );
			$mwb_tyo_enable_templates = get_option( 'mwb_tyo_enable_templates', 'no' );
			$ajax_nonce = wp_create_nonce( 'mwb-tyo-ajax-seurity-string' );
			$upload_url = home_url();
			$mwb_tyo_order_production_add  = get_option( 'mwb_tyo_order_production_address', false );

			$user_id = get_current_user_id();

			$redirect_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

			$track_page_id = get_option( 'mwb_tyo_tracking_page', array() );
			if ( is_array( $track_page_id ) && ! empty( $track_page_id ) ) {
				$logedin_track_page_id = $track_page_id['pages']['mwb_track_order_page'];
				$guest_track_page_id = $track_page_id['pages']['mwb_guest_track_order_page'];

				if ( ( get_the_ID() == $logedin_track_page_id ) || ( get_the_ID() == $guest_track_page_id ) ) {
					wp_enqueue_style( 'mwb-tyo-style-front', MWB_TRACK_YOUR_ORDER_URL . 'assets/css/mwb-tyo-style-front.css', MWB_TRACK_YOUR_ORDER_VERSION, true );
				}
			}
			$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
			if ( is_array( $mwb_tyo_pages ) && ! empty( $mwb_tyo_pages ) ) {
				$fedex_page_id = $mwb_tyo_pages['pages']['mwb_fedex_track_order'];
				if ( get_the_ID() == $fedex_page_id ) {
					wp_enqueue_style( 'mwb-tyo-style-front', MWB_TRACK_YOUR_ORDER_URL . '/assets/css/mwb-tyo-style-front.css', MWB_TRACK_YOUR_ORDER_VERSION, true );

				}
			}

			if ( ( get_the_ID() == $logedin_track_page_id ) || ( get_the_ID() == $guest_track_page_id ) ) {
				wp_enqueue_script( '17track-js', '//www.17track.net/externalcall.js', '', MWB_TRACK_YOUR_ORDER_VERSION, true );

				wp_register_script( 'mwb-tyo-script', MWB_TRACK_YOUR_ORDER_URL . 'assets/js/mwb-tyo-script.js', array( 'jquery' ), '5.3', true );
				$ajax_nonce = wp_create_nonce( 'mwb-tyo-ajax-seurity-string' );
				$statuses = wc_get_order_statuses();
				$translation_array = array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'mwb_tyo_nonce' => $ajax_nonce,
					'order_statuses'    => $statuses,
					'order_feature_enable' => $mwb_tyo_enable_track_order_feature,
					'order_feature_popup' => $mwb_tyo_enable_track_order_popup,
					'track_order_template' => $mwb_tyo_enable_templates,
				);
				wp_localize_script( 'mwb-tyo-script', 'global_tyo', $translation_array );
				wp_enqueue_script( 'mwb-tyo-script' );
			}

			if ( get_the_ID() == $fedex_page_id ) {
				wp_enqueue_script( 'mwb-tyo-script', MWB_TRACK_YOUR_ORDER_URL . '/assets/js/mwb-tyo-script.js', array( 'jquery' ), MWB_TRACK_YOUR_ORDER_VERSION, true );
			}

			wp_enqueue_script( 'mwb-tyo-script-velocity', MWB_TRACK_YOUR_ORDER_URL . 'assets/js/velocity.min.js', array( 'jquery' ), MWB_TRACK_YOUR_ORDER_VERSION, true );
			wp_enqueue_script( 'mwb-tyo-script-jquery', MWB_TRACK_YOUR_ORDER_URL . 'assets/js/jquery-ui.js', '', MWB_TRACK_YOUR_ORDER_VERSION, true );
			wp_enqueue_script( 'mwb-tyo-script-jquery-min', MWB_TRACK_YOUR_ORDER_URL . 'assets/js/jquery-ui.min.js', '', MWB_TRACK_YOUR_ORDER_VERSION, true );

			if ( 0 <= strpos( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', '/track-your-order' ) ) {
				$mwb_enable_google_map = get_option( 'mwb_tyo_google_api_settings', '' );
				$mwb_tyo_google_api_key = get_option( 'mwb_tyo_google_api_key', '' );

				
					wp_enqueue_script( 'mwb_new_road_map_script', 'https://maps.googleapis.com/maps/api/js?key= ' . $mwb_tyo_google_api_key, '', MWB_TRACK_YOUR_ORDER_VERSION, true );

					wp_register_script( 'mwb-tyo-new-script', MWB_TRACK_YOUR_ORDER_URL . 'assets/js/mwb-tyo-new-frontend.js', array( 'jquery' ), '5.6', true );
					$ajax_nonce = wp_create_nonce( 'mwb-tyo-ajax-seurity-string' );
					$statuses = wc_get_order_statuses();
					$translation_array = array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'mwb_tyo_nonce' => $ajax_nonce,
						'order_statuses'    => $statuses,
						'order_feature_enable' => $mwb_tyo_enable_track_order_feature,
						'order_feature_popup' => $mwb_tyo_enable_track_order_popup,
						'track_order_template' => $mwb_tyo_enable_templates,
						'track_order_order_origin' => $mwb_tyo_order_production_add,
					);
					wp_localize_script( 'mwb-tyo-new-script', 'global_new_tyo', $translation_array );
					wp_enqueue_script( 'mwb-tyo-new-script' );
			
			}

		}
		/**
		 * This function is add cs and js to order meta
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_admin_scripts() {
			$flag_counter = 0;
			$flag_counter_after = 0;
			$screen = get_current_screen();
			$mwb_tyo_selected_statuses = get_option( 'mwb_tyo_statuses_for_order_tracking_on_activation', false );
			$mwb_tyo_selected_statuses_after_save = get_option( 'mwb_tyo_new_settings_custom_statuses_for_order_tracking', false );
			if ( is_array( $mwb_tyo_selected_statuses ) && ! empty( $mwb_tyo_selected_statuses ) ) {
				$flag_counter = 1;
			}

			if ( is_array( $mwb_tyo_selected_statuses_after_save ) && ! empty( $mwb_tyo_selected_statuses_after_save ) ) {
				$flag_counter_after = 1;
			}

			$screen = get_current_screen();

			if ( isset( $screen->id ) ) {
				if ( 'shop_order' == $screen->id ) {
					wp_enqueue_style( 'mwb-tyo-style-jqueru-ui', MWB_TRACK_YOUR_ORDER_URL . 'assets/css/jquery-ui.css', MWB_TRACK_YOUR_ORDER_VERSION, true );
					wp_enqueue_style( 'mwb-tyo-style-timepicker', MWB_TRACK_YOUR_ORDER_URL . 'assets/css/jquery.ui.timepicker.css', MWB_TRACK_YOUR_ORDER_VERSION, true );
					wp_enqueue_script( 'mwb-tyo-script-timepicker', MWB_TRACK_YOUR_ORDER_URL . 'assets/js/jquery.ui.timepicker.js', array( 'jquery' ), MWB_TRACK_YOUR_ORDER_VERSION, true );
					wp_enqueue_script( 'jquery-ui-datepicker' );

				}
			}
			wp_register_script( 'mwb-tyo-script-admin', MWB_TRACK_YOUR_ORDER_URL . 'assets/js/mwb-tyo-admin-script.js', array( 'jquery' ), MWB_TRACK_YOUR_ORDER_VERSION );
			if ( strpos( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', '&tab=mwb_tyo_settings' ) > 0 ) {
				wp_enqueue_script( 'select2' );
				wp_enqueue_style( 'select2' );
			}

			$ajax_nonce = wp_create_nonce( 'mwb-tyo-ajax-seurity-string' );
			$statuses = wc_get_order_statuses();
			$order_status = array(
				'wc-dispatched' => __( 'Order Dispatched', 'woocommerce-order-tracker' ),
				'wc-packed' => __( 'Order Packed', 'woocommerce-order-tracker' ),
				'wc-shipped' => __( 'Order Shipped', 'woocommerce-order-tracker' ),
			);
			$custom_order_status = get_option( 'mwb_tyo_new_custom_order_status', array() );
			if ( is_array( $custom_order_status ) && ! empty( $custom_order_status ) ) {
				foreach ( $custom_order_status as $key => $value ) {
					foreach ( $value as $status_key => $status_value ) {
						$order_status[ 'wc-' . $status_key ] = $status_value;
					}
				}
			}
			foreach ( $order_status as $key => $val ) {
				$statuses[ $key ] = $val;
			}

			$custom_status_url = get_option( 'mwb_tyo_new_custom_order_image' );
			if( OrderUtil::custom_orders_table_usage_is_enabled() ){
				$wps_tyo_wc = '-';
			} else {
				$wps_tyo_wc = '-wc-';
			}
			$translation_array = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'mwb_tyo_nonce' => $ajax_nonce,
				'site_url' => site_url(),
				'order_statuses'    => $statuses,
				'custom_order_status_url'  => $custom_status_url,
				'is_enable_status_icon' => get_option( 'mwb_tyo_enable_order_status_icon', 'no' ),
				'default_enable_statuses' => $flag_counter,
				'after_save_statuses' => $flag_counter_after,
				'mwb_tyo_close_button' => __( 'Close', 'woocommerce-order-tracker' ),
				'message_success'  => __( 'Order Status successfully saved.', 'woocommerce-order-tracker' ),
				'message_invalid_input'  => __( 'Please enter a Valid Status Name.', 'woocommerce-order-tracker' ),
				'message_error_save'  => __( 'Unable to save Order Status.', 'woocommerce-order-tracker' ),
				'message_empty_data'  => __( 'Please enter the status name .', 'woocommerce-order-tracker' ),
				'message_template_activated' => __( 'Template Activated sucessfully.', 'woocommerce-order-tracker' ),
				'wps_tyo_wc' => $wps_tyo_wc,
			);
			wp_enqueue_style( 'mwb-tyo-style', MWB_TRACK_YOUR_ORDER_URL . '/assets/css/mwb-tyo-style.css', array(), MWB_TRACK_YOUR_ORDER_VERSION );
			wp_localize_script( 'mwb-tyo-script-admin', 'global_tyo_admin', $translation_array );
			wp_enqueue_script( 'mwb-tyo-script-admin' );

			if ( isset( $_GET['page'] ) && isset( $_GET['tab'] ) && isset( $_GET['section'] ) ) {
				if ( 'wc-settings' == $_GET['page'] && 'mwb_tyo_settings' == $_GET['tab'] && 'new_settings' == $_GET['section'] ) {

					wp_enqueue_script( 'mwb-tyo-script-admin-jquery', MWB_TRACK_YOUR_ORDER_URL . 'assets/js/jquery-ui.js', array( 'jquery-ui-sortable' ), MWB_TRACK_YOUR_ORDER_VERSION, true );
					wp_register_script( 'mwb-tyo-admin-status-reorder', MWB_TRACK_YOUR_ORDER_URL . 'assets/js/mwb-tyo-admin-status-sortable.js', array( 'jquery', 'jquery-ui-sortable' ), MWB_TRACK_YOUR_ORDER_VERSION, true );
					$mwb_tyo_sortable_array = array(
						'adminajaxurl' => admin_url( 'admin-ajax.php' ),
						'site_url' => site_url(),
						'order_statuses'    => $statuses,
						'default_enable_statuses' => $flag_counter,
						'after_save_statuses' => $flag_counter_after,
						'admin_nonce' => wp_create_nonce( 'mwb_tyo_status_nonce' ),
					);
					wp_localize_script( 'mwb-tyo-admin-status-reorder', 'mwb_tyo_reorder_param', $mwb_tyo_sortable_array );
					wp_enqueue_script( 'mwb-tyo-admin-status-reorder' );
				}
			}
			if ( isset( $_GET['page'] ) && isset( $_GET['tab'] ) ) {
				if ( 'wc-settings' == $_GET['page'] && 'mwb_tyo_settings' == $_GET['tab'] ) {
					wp_register_script( 'mwb-tyo-new-tracking-js', MWB_TRACK_YOUR_ORDER_URL . 'assets/js/mwb-tyo-new-tracking.js', array( 'jquery' ), MWB_TRACK_YOUR_ORDER_VERSION, true );
					$mwb_tyo_new_settings_array = array(
						'adminajaxurl' => admin_url( 'admin-ajax.php' ),
						'site_url' => site_url(),
						'address_validation' => __( 'Please Enter Address First', 'woocommerce-order-tracker' ),
						'address_validation_success' => __( 'Address Successfully Added', 'woocommerce-order-tracker' ),
						'selec_address_placeholder' => __( 'Select Your Hubpoint Addresses', 'woocommerce-order-tracker' ),
						'ajax_nonce' => wp_create_nonce( 'mwb_tyo_new_tracking_nonce' ),
					);
					wp_localize_script( 'mwb-tyo-new-tracking-js', 'mwb_tyo_new_param', $mwb_tyo_new_settings_array );
					wp_enqueue_script( 'mwb-tyo-new-tracking-js' );
				}
			}
		}

		/**
		 * This function adds a Custom order status on the backend
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_mwb_create_custom_order_status() {
			check_ajax_referer( 'mwb-tyo-ajax-seurity-string', 'nonce' );
			$create_custom_order_status = array();
			$value = array();
			$custom_order_image_url = array();
			$mwb_image_url = array();
			$value = get_option( 'mwb_tyo_new_custom_order_status', false );
			$custom_order_image_url = get_option( 'mwb_tyo_new_custom_order_image', false );
			if ( is_array( $value ) && ! empty( $value ) ) {
				$create_custom_order_status = isset( $_POST['mwb_mwb_new_role_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_mwb_new_role_name'] ) ) : '';
				$create_custom_order_image_url = isset( $_POST['mwb_custom_order_image_url'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_custom_order_image_url'] ) ) : '';
				$key_custom_order_status = str_replace( ' ', '', $create_custom_order_status );
				$key_custom_order_status = strtolower( $key_custom_order_status );
				$value[] = array( $key_custom_order_status => $create_custom_order_status );
				$custom_order_image_url[ $key_custom_order_status ] = $create_custom_order_image_url;

				update_option( 'mwb_tyo_new_custom_order_status', $value );
				update_option( 'mwb_tyo_new_custom_order_image', $custom_order_image_url );

			} else {

				$create_custom_order_status = isset( $_POST['mwb_mwb_new_role_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_mwb_new_role_name'] ) ) : '';
				$create_custom_order_image_url = isset( $_POST['mwb_custom_order_image_url'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_custom_order_image_url'] ) ) : '';
				$key_custom_order_status = str_replace( ' ', '', $create_custom_order_status );
				$key_custom_order_status = strtolower( $key_custom_order_status );
				$value[] = array( $key_custom_order_status => $create_custom_order_status );
				$custom_order_image_url[ $key_custom_order_status ] = $create_custom_order_image_url;

				update_option( 'mwb_tyo_new_custom_order_status', $value );
				update_option( 'mwb_tyo_new_custom_order_image', $custom_order_image_url );
			}

			esc_html_e( 'success', 'woocommerce-order-tracker' );
			wp_die();
		}

		/**
		 * This function delete the Custom order status on the backend
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_selected_template() {
			
			check_ajax_referer( 'mwb-tyo-ajax-seurity-string', 'nonce' );
			$selected_template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '';
			update_option( 'mwb_tyo_activated_template', $selected_template_name );
			esc_html_e( 'success', 'woocommerce-order-tracker' );
			wp_die();
		}

		/**
		 * This function delete the Custom order status on the backend
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_mwb_delete_custom_order_status() {
			check_ajax_referer( 'mwb-tyo-ajax-seurity-string', 'nonce' );
			$mwb_tyo_old_selected_statuses = get_option( 'mwb_tyo_new_settings_custom_statuses_for_order_tracking', false );
			$mwb_custom_action = isset( $_POST['mwb_custom_action'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_custom_action'] ) ) : '';
			$mwb_custom_key = isset( $_POST['mwb_custom_key'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_custom_key'] ) ) : '';
			if ( isset( $mwb_custom_key ) && ! empty( $mwb_custom_key ) ) {
				$custom_order_status_exist = get_option( 'mwb_tyo_new_custom_order_status', array() );
				if ( is_array( $custom_order_status_exist ) && ! empty( $custom_order_status_exist ) ) {
					foreach ( $custom_order_status_exist as $key => $value ) {
						foreach ( $value as $mwb_order_key => $mwb_order_status ) {
						
							if ( $mwb_order_key == $mwb_custom_key ) {
								unset( $custom_order_status_exist[ $key ] );

							}
						}
					}
					update_option( 'mwb_tyo_new_custom_order_status', $custom_order_status_exist );
					
					if ( is_array( $mwb_tyo_old_selected_statuses ) && ! empty( $mwb_tyo_old_selected_statuses ) ) {
						foreach ( $mwb_tyo_old_selected_statuses as $old_key => $old_value ) {
							if ( substr( $old_value, 3 ) == $mwb_custom_key ) {
								unset( $mwb_tyo_old_selected_statuses[ $old_key ] );
								update_option( 'mwb_tyo_new_settings_custom_statuses_for_order_tracking', $mwb_tyo_old_selected_statuses );
							}
						}
					}
					esc_html_e( 'success', 'woocommerce-order-tracker' );
				} else {
					esc_html_e( 'failed', 'woocommerce-order-tracker' );
				}

				wp_die();
			}
		}




		/**
		 * Track order button on order page.
		 *
		 * @param array  $actions list of actions.
		 * @param object $order is an object.
		 * @return array
		 */
		public function mwb_tyo_add_track_order_button_on_orderpage( $actions, $order ) {
			$mwb_tyo_enable_track_order_feature = get_option( 'mwb_tyo_enable_track_order_feature', 'no' );
			if ( 'yes' != $mwb_tyo_enable_track_order_feature ) {
				return $actions;
			}
			$mwb_tyo_enable_track_order_popup = get_option( 'mwb_tyo_enable_track_order_popup', 'no' );
			$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
			$page_id = $mwb_tyo_pages['pages']['mwb_track_order_page'];
			if ( '3.0.0' > WC()->version ) {
				$order_id = $order->id;
				$track_order_url = get_permalink( $page_id );
				if ( 'yes' == $mwb_tyo_enable_track_order_popup ) {
					$actions['thickbox']['url']     = $track_order_url . '?' . $order_id . '?TB_iframe=true';
					$actions['thickbox']['name']    = __( 'Track Order', 'woocommerce-order-tracker' );
				} else {
					$actions['mwb_track_order']['url']  = $track_order_url . '?' . $order_id;
					$actions['mwb_track_order']['name']     = __( 'Track Order', 'woocommerce-order-tracker' );
				}
			} else {
				$order_id = $order->get_id();
				$track_order_url = get_permalink( $page_id );
				if ( 'yes' == $mwb_tyo_enable_track_order_popup ) {

					$actions['thickbox']['url']     = $track_order_url . '?' . $order_id . '?TB_iframe=true';
					$actions['thickbox']['name']    = __( 'Track Order', 'woocommerce-order-tracker' );
				} else {
					$actions['mwb_track_order']['url']  = $track_order_url . '?' . $order_id;
					$actions['mwb_track_order']['name']     = __( 'Track Order', 'woocommerce-order-tracker' );
				}
			}

			

			return $actions;
		}

		/**
		 * Function for add columns.
		 *
		 * @param bool $has_orders to check .
		 * @return void
		 */
		public function wps_wot_add_export_button_before_order_table( $has_orders ){
			if( 'yes' == get_option( 'mwb_tyo_enable_export_order_logged_in_user' ) ) {

				?>
					<button class="wps_export woocommerce-button"><?php esc_html_e( 'Export Orders', 'woocommerce-order-tracker' );?></button>
				<?php
				
			}
		}

		/**
		 * This function is to create template for track order
		 *
		 * @link http://www.wpswings.com/
		 * @param string $template is the contains path.
		 * @return string
		 */
		public function mwb_tyo_include_track_order_page( $template ) {
			$selected_template = get_option( 'mwb_tyo_activated_template' );
			$mwb_tyo_google_map_setting = get_option( 'mwb_tyo_google_api_settings', false );
			$mwb_tyo_enable_track_order_feature = get_option( 'mwb_tyo_enable_track_order_feature', 'no' );
			if ( 'yes' != $mwb_tyo_enable_track_order_feature ) {
				return $template;
			}
			if ( 'yes' == $mwb_tyo_enable_track_order_feature && 1 == $mwb_tyo_google_map_setting ) {
				$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
				$page_id = $mwb_tyo_pages['pages']['mwb_track_order_page'];
				if ( is_page( $page_id ) ) {
					$new_template = MWB_TRACK_YOUR_ORDER_PATH . 'template/mwb-map-new-template.php';
					$template = $new_template;
				}
			} else {

				$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page', false );
				$page_id = $mwb_tyo_pages['pages']['mwb_track_order_page'];
				if ( is_page( $page_id ) ) {
					if ( ' ' != $selected_template && null != $selected_template ) {
						$new_template = MWB_TRACK_YOUR_ORDER_PATH . 'template/mwb-track-order-myaccount-page-' . $selected_template . '.php';
						$template = $new_template;
					} else {
						$new_template = MWB_TRACK_YOUR_ORDER_PATH . 'template/mwb-track-order-myaccount-page-template1.php';
						$template = $new_template;
					}
				}
			}

			return $template;
		}

		/**
		 * This function is to create template for track order
		 *
		 * @link http://www.wpswings.com/
		 * @param string $template is the contains path.
		 * @return string
		 */
		public function mwb_tyo_include_guest_track_order_page( $template ) {
			$mwb_tyo_enable_track_order_feature = get_option( 'mwb_tyo_enable_track_order_feature', 'no' );
			if ( 'yes' != $mwb_tyo_enable_track_order_feature ) {
				return $template;
			}
			$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
			$page_id = $mwb_tyo_pages['pages']['mwb_guest_track_order_page'];
			if ( is_page( $page_id ) ) {
				$new_template = MWB_TRACK_YOUR_ORDER_PATH . 'template/mwb-guest-track-order-page.php';
				$template = $new_template;
			}

			return $template;
		}

		/**
		 * This function is to create template for FedEX tracking of Order
		 *
		 * @link http://www.wpswings.com/
		 * @param string $template is the contains path.
		 * @return string
		 */
		public function mwb_ordertracking_page( $template ) {
			$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
			$page_id = $mwb_tyo_pages['pages']['mwb_fedex_track_order'];
			if ( is_page( $page_id ) ) {
				$new_template = MWB_TRACK_YOUR_ORDER_PATH . 'template/mwb-order-tracking-page.php';
				$template = $new_template;
			}

			return $template;
		}

		/**
		 * This function is to add custom order status for return and exchange
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_register_custom_order_status() {

			$mwb_tyo_enable_track_order_feature = get_option( 'mwb_tyo_enable_track_order_feature', 'no' );
			$mwb_tyo_enable_custom_order_feature = get_option( 'mwb_tyo_enable_custom_order_feature', 'no' );
			if ( 'yes' !== $mwb_tyo_enable_track_order_feature || 'yes' !== $mwb_tyo_enable_custom_order_feature ) {
				return;
			}

			register_post_status(
				'wc-packed',
				array(
					'label'                     => __( 'Order Packed', 'woocommerce-order-tracker' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count */
					'label_count'               => _n_noop( __( 'Order Packed', 'woocommerce-order-tracker' ) . '<span class="count">(%s)</span>', __( 'Order Packed', 'woocommerce-order-tracker' ) . '<span class="count">(%s)</span>' ),
				)
			);

			register_post_status(
				'wc-dispatched',
				array(
					'label'                     => __( 'Order Dispatched', 'woocommerce-order-tracker' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count */
					'label_count'               => _n_noop( __( 'Order Dispatched', 'woocommerce-order-tracker' ) . ' <span class="count">(%s)</span>', __( 'Order Dispatched', 'woocommerce-order-tracker' ) . ' <span class="count">(%s)</span>' ),
				)
			);

			register_post_status(
				'wc-shipped',
				array(
					'label'                     => __( 'Order Shipped', 'woocommerce-order-tracker' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count */
					'label_count'               => _n_noop( __( 'Order Shipped', 'woocommerce-order-tracker' ) . ' <span class="count">(%s)</span>', __( 'Order Shipped', 'woocommerce-order-tracker' ) . ' <span class="count">(%s)</span>' ),
				)
			);

			$custom_statuses = get_option( 'mwb_tyo_new_custom_order_status', array() );

			if ( is_array( $custom_statuses ) && ! empty( $custom_statuses ) ) {
				foreach ( $custom_statuses as $key => $value ) {
					foreach ( $value as $custom_status_key => $custom_status_value ) {
						register_post_status(
							'wc-' . $custom_status_key,
							array(
								'label'                     => $custom_status_value,
								'public'                    => true,
								'exclude_from_search'       => false,
								'show_in_admin_all_list'    => true,
								'show_in_admin_status_list' => true,
								/* translators: %s: count */
								'label_count'               => _n_noop( $custom_status_value . ' <span class="count">(%s)</span>', $custom_status_value . ' <span class="count">(%s)</span>' ),
							)
						);
					}
				}
			}
		}

		/**
		 * This function is to register custom order status
		 *
		 * @link http://www.wpswings.com/
		 * @param array $order_statuses is list of stautuses.
		 * @return multitype:string unknown
		 */
		public function mwb_tyo_add_custom_order_status( $order_statuses ) {
			$mwb_tyo_enable_track_order_feature = get_option( 'mwb_tyo_enable_track_order_feature', 'no' );
			$mwb_tyo_enable_custom_order_feature = get_option( 'mwb_tyo_enable_custom_order_feature', 'no' );
			if ( 'yes' != $mwb_tyo_enable_track_order_feature || 'yes' != $mwb_tyo_enable_custom_order_feature ) {
				return $order_statuses;
			}
			$custom_order = get_option( 'mwb_tyo_new_custom_order_status', array() );
			$statuses = get_option( 'mwb_tyo_new_custom_statuses_for_order_tracking', array() );
			$mwb_tyo_statuses = get_option( 'mwb_tyo_new_settings_custom_statuses_for_order_tracking', array() );

			$custom_order[] = array( 'dispatched' => __( 'Order Dispatched', 'woocommerce-order-tracker' ) );
			$custom_order[] = array( 'shipped' => __( 'Order Shipped', 'woocommerce-order-tracker' ) );
			$custom_order[] = array( 'packed' => __( 'Order Packed', 'woocommerce-order-tracker' ) );

			if ( is_array( $custom_order ) && ! empty( $custom_order ) ) {
				foreach ( $custom_order as $key1 => $value1 ) {
					foreach ( $value1 as $custom_key => $custom_value ) {
						if ( in_array( 'wc-' . $custom_key, $statuses ) ) {
							$order_statuses[ 'wc-' . $custom_key ] = $custom_value;
						}
					}
				}
			}

			return $order_statuses;
		}
		/**
		 * This function is add Meta box for adding estimated date of delivery
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_tracking_order_meta_box() {
			 $mwb_tyo_enable_track_order_feature = get_option( 'mwb_tyo_enable_track_order_feature', 'no' );
			$mwb_tyo_enable_track_17track_feature = get_option( 'mwb_tyo_enable_17track_integration', 'no' );

			if ( 'yes' != $mwb_tyo_enable_track_order_feature ) {
				return;
			}
			$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ): 'shop_order';
			add_meta_box( 'mwb_tyo_track_order', __( 'Enter Estimated Delivery Date', 'woocommerce-order-tracker' ), array( $this, 'mwb_tyo_track_order_metabox' ), $screen, 'side' );
			$mwb_tyo_enable_track_order_api = get_option( 'mwb_tyo_enable_third_party_tracking_api', 'no' );
			if ( 'yes' == $mwb_tyo_enable_track_order_api || 'yes' == $mwb_tyo_enable_track_17track_feature ) {
				add_meta_box( 'mwb_tyo_tracking_services', __( 'Select Service For Tracking Your Package', 'woocommerce-order-tracker' ), array( $this, 'mwb_tyo_track_order_services_metabox' ), $screen, 'side' );
			}
			$mwb_tyo_google_map_setting = get_option( 'mwb_tyo_google_api_settings', false );

			if ( 1 == $mwb_tyo_google_map_setting ) {
				add_meta_box( 'mwb_tyo_custom_tracking_services', __( 'Select City When Your Package Reaches The Desire City', 'woocommerce-order-tracker' ), array( $this, 'mwb_tyo_track_order_custom_services_metabox' ), $screen, 'side' );
			}

		}

		/**
		 * Custom service.
		 *
		 * @return void
		 */
		public function mwb_tyo_track_order_custom_services_metabox($post_or_order_object) {
			global $post, $thepostid, $theorder;
			if ( '3.0.0' > WC()->version ) {
				$order_id = $theorder->id;
			} else {
				$order_id = $theorder->get_id();
			}
			if( $post_or_order_object instanceof WC_Order ){
				$mwb_post_id = $post_or_order_object->get_id();
			} else {
				$mwb_post_id = $post_or_order_object->ID;
			}
			$mwb_tyo_saved_selected_cities = wps_order_tracker_get_meta_data( $mwb_post_id , 'mwb_tyo_save_selected_city', true );
			$mwb_tyo_all_selected_cities = get_option( 'mwb_tyo_all_tracking_address', false );
			?>
			<div class="mwb_tyo_shipping_service_wrapper">
				<select name="mwb_tyo_custom_shipping_cities" id="mwb_tyo_custom_shipping_cities">
					<option value="<?php esc_attr_e( 'none', 'woocommerce-order-tracker' ); ?>"><?php esc_attr_e( 'Select shipping Cities', 'woocommerce-order-tracker' ); ?></option>
					<?php
					if ( is_array( $mwb_tyo_all_selected_cities ) && ! empty( $mwb_tyo_all_selected_cities ) ) {
						foreach ( $mwb_tyo_all_selected_cities as $custom_key => $custom_value ) {
							?>
							<option value="<?php echo esc_attr( $custom_value ); ?>" 
													  <?php
														if ( isset( $mwb_tyo_saved_selected_cities ) && '' != $mwb_tyo_saved_selected_cities && $custom_value == $mwb_tyo_saved_selected_cities ) {
															echo 'selected';}
														?>
								><?php echo esc_html( str_replace( 'mwb_address_', '', $custom_value ) ); ?></option>
							<?php
						}
					}
					?>
				</select>
				<input type="hidden" name="mwb_tyo_custom_shipping_cities_nonce_name" value="<?php wp_create_nonce( 'mwb_tyo_custom_shipping_cities_nonce' ); ?>">
			</div>
			<?php
		}

		/**
		 * This function is for estimated delivery date html
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_track_order_metabox() {
			 global $post, $thepostid, $theorder;
			$mwb_tyo_enable_track_order_feature = get_option( 'mwb_tyo_enable_track_order_feature', 'no' );
			if ( 'yes' != $mwb_tyo_enable_track_order_feature ) {
				return;
			}
			if ( '3.0.0' > WC()->version ) {
				$order_id = $theorder->id;
			} else {
				$order_id = $theorder->get_id();
			}
			$expected_delivery_date = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_date', true );
			$expected_delivery_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_time', true );

			?>
			<div class="mwb_tyo_estimated_delivery_datails_wrapper">
				<input type="hidden" name="wps_tyo_delivery_nonce_name" value="<?php wp_create_nonce( 'wps_tyo_delivery_nonce' ); ?>">
				<label for="mwb_tyo_est_delivery_date"><?php esc_html_e( 'Delivery Date', 'woocommerce-order-tracker' ); ?></label>
				<input type="text" class="mwb_tyo_est_delivery_date" id="mwb_tyo_est_delivery_date" name="mwb_tyo_est_delivery_date" value="<?php echo esc_attr( $expected_delivery_date ); ?>" placeholder="<?php esc_attr_e( 'Enter Delivery Date', 'woocommerce-order-tracker' ); ?>"></input>
				<label for="mwb_tyo_est_delivery_time"><?php esc_html_e( 'Delivery Time', 'woocommerce-order-tracker' ); ?></label>				
				<input type="text" class="mwb_tyo_est_delivery_time" name="mwb_tyo_est_delivery_time" id="mwb_tyo_est_delivery_time" value="<?php echo esc_attr( $expected_delivery_time ); ?>" placeholder="<?php esc_attr_e( 'Enter Delivery time', 'woocommerce-order-tracker' ); ?>"></input>
			</div>
			<?php
		}


		/**
		 * This function is for different shipping services html
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_track_order_services_metabox() {
			global $post, $thepostid, $theorder;
			$mwb_tyo_enable_track_order_api = get_option( 'mwb_tyo_enable_third_party_tracking_api', 'no' );
			$mwb_tyo_enable_track_17track_feature = get_option( 'mwb_tyo_enable_17track_integration', 'no' );

			if ( '3.0.0' > WC()->version ) {
				$order_id = $theorder->id;
			} else {
				$order_id = $theorder->get_id();
			}
			$mwb_tyo_fedex_tracking_enable = get_option( 'mwb_tyo_enable_track_order_using_api', 'no' );
			if ( 'yes' === $mwb_tyo_fedex_tracking_enable ) {

				$mwb_diffrent_shipping_services = array( 'fedex' => 'FedEx' );
			} else {
				$mwb_diffrent_shipping_services = array();
			}

			/**
			 * Add different shipping services.
			 *
			 * @since 1.0.0
			 */
			$mwb_diffrent_shipping_services = apply_filters( 'mwb_tyo_add_diffrent_shipping_services', $mwb_diffrent_shipping_services );
			$mwb_tyo_track_id = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_package_tracking_number', true );
			$selected_method = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_selected_shipping_service', true );
			if ( 'yes' == $mwb_tyo_enable_track_order_api ) {
				?>
				<div class="mwb_tyo_shipping_service_wrapper">
					<select name="mwb_tyo_selected_shipping_services">
						<option><?php esc_html_e( '---Select shipping Services---', 'woocommerce-order-tracker' ); ?></option>
						<?php
						if ( isset( $mwb_diffrent_shipping_services ) && ! empty( $mwb_diffrent_shipping_services ) ) {
							foreach ( $mwb_diffrent_shipping_services as $key => $value ) {
								?>
							<option value="<?php echo esc_attr( $key ); ?>"
													  <?php
														if ( $key == $selected_method ) {
															echo 'selected'; }
														?>
								><?php echo esc_html( $value ); ?></option>
								<?php
							}
						}
						?>
				</select>
				<input type="hidden" name="mwb_tyo_selected_shipping_services_nonce_name" value="<?php wp_create_nonce( 'mwb_tyo_selected_shipping_services_nonce' ); ?>">
			</div>
			<div class="mwb_tyo_ship_tracking_wrapper">
				<label for="mwb_tyo_user_tracking_number"><?php esc_html_e( 'Tracking Number', 'woocommerce-order-tracker' ); ?></label>
				<input type="text" name="mwb_tyo_tracking_number" id="mwb_tyo_tracking_number" value="<?php echo esc_attr( $mwb_tyo_track_id ); ?>" placeholder="<?php esc_attr_e( 'Enter Tracking Number', 'woocommerce-order-tracker' ); ?>"></input>
			</div>
				<?php
			} elseif ( 'yes' == $mwb_tyo_enable_track_17track_feature ) {
				?>
			<div class="mwb_tyo_ship_tracking_wrapper">
				<label for="mwb_tyo_user_tracking_number"><?php esc_html_e( '17Track Number', 'woocommerce-order-tracker' ); ?></label>
				<input type="text" name="mwb_tyo_tracking_number" id="mwb_tyo_tracking_number" value="<?php echo esc_attr( $mwb_tyo_track_id ); ?>" placeholder="<?php esc_attr_e( 'Enter 17 Tracking Number', 'woocommerce-order-tracker' ); ?>"></input>
			</div>
				<?php
			}
		}

		/**
		 * This function is for saving delivery date
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_save_delivery_date_meta() {
			global $post;
			if ( isset( $post->ID ) ) {
				$mwb_track_order_status = array();
				$post_id = $post->ID;
				$value_check = isset( $_POST['wps_tyo_delivery_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tyo_delivery_nonce_name'] ) ) : '';
				wp_verify_nonce( $value_check, 'wps_tyo_delivery_nonce' );
				if ( isset( $_POST['mwb_tyo_est_delivery_date'] ) && sanitize_text_field( wp_unslash( $_POST['mwb_tyo_est_delivery_date'] ) ) != '' ) {
					wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_estimated_delivery_date', sanitize_text_field( wp_unslash( $_POST['mwb_tyo_est_delivery_date'] ) ) );
				} else {
					wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_estimated_delivery_date', false );
				}

				if ( isset( $_POST['mwb_tyo_est_delivery_time'] ) && sanitize_text_field( wp_unslash( $_POST['mwb_tyo_est_delivery_time'] ) ) != '' ) {
					wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_estimated_delivery_time', sanitize_text_field( wp_unslash( $_POST['mwb_tyo_est_delivery_time'] ) ) );
				} else {
					wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_estimated_delivery_time', false );
				}
			}
		}

		/**
		 * This function is for saving delivery date
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_save_delivery_date_meta_hpos($order_id){ 
			$post_id = $order_id;
			if ( isset( $_POST['mwb_tyo_est_delivery_date'] ) && sanitize_text_field( wp_unslash( $_POST['mwb_tyo_est_delivery_date'] ) ) != '' ) {
				wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_estimated_delivery_date', sanitize_text_field( wp_unslash( $_POST['mwb_tyo_est_delivery_date'] ) ) );
			} else {
				wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_estimated_delivery_date', false );
			}

			if ( isset( $_POST['mwb_tyo_est_delivery_time'] ) && sanitize_text_field( wp_unslash( $_POST['mwb_tyo_est_delivery_time'] ) ) != '' ) {
				wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_estimated_delivery_time', sanitize_text_field( wp_unslash( $_POST['mwb_tyo_est_delivery_time'] ) ) );
			} else {
				wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_estimated_delivery_time', false );
			}
		}

		/**
		 * This function is for saving delivery date
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_save_custom_shipping_cities_meta() {
			global $post;
			if ( isset( $post->ID ) ) {
				$this->mwb_tyo_save_custom_shipping_cities_meta_callback( $post->ID);
			}
		}

		/**
		 * This function is for saving shipping services meta data
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_save_custom_shipping_cities_meta_hpos($order_id){
			$this->mwb_tyo_save_custom_shipping_cities_meta_callback($order_id);
		}

		public function mwb_tyo_save_custom_shipping_cities_meta_callback($post_id){
			$order = wc_get_order( $post_id);
			if ( isset( $order ) && ! empty( $order ) ) {

				$orderdata = $order->get_data();
				$order_modified_date = $orderdata['date_modified'];
				$converted_order_modified_date = date_i18n( 'F d, Y g:i a', strtotime( $order_modified_date ) );
				$current_order_status = $order->get_status();

				$mwb_tyo_all_selected_cities = get_option( 'mwb_tyo_old_addresses', false );

				if ( is_array( wps_order_tracker_get_meta_data( $post_id, 'mwb_tyo_track_custom_cities', true ) ) ) {
					$mwb_tyo_previous_saved_cities = wps_order_tracker_get_meta_data( $post_id, 'mwb_tyo_track_custom_cities', true );
				} else {
					$mwb_tyo_previous_saved_cities = array();
				}

				if ( is_array( wps_order_tracker_get_meta_data( $post_id, 'mwb_tyo_custom_change_time', true ) ) ) {
					$mwb_tyo_previous_saved_changed_time = wps_order_tracker_get_meta_data( $post_id, 'mwb_tyo_custom_change_time', true );
				} else {
					$mwb_tyo_previous_saved_changed_time = array();
				}

				$value_check = isset( $_POST['mwb_tyo_custom_shipping_cities_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_custom_shipping_cities_nonce_name'] ) ) : '';
				wp_verify_nonce( $value_check, 'mwb_tyo_custom_shipping_cities_nonce' );
				if ( isset( $_POST['mwb_tyo_custom_shipping_cities'] ) && sanitize_text_field( wp_unslash( $_POST['mwb_tyo_custom_shipping_cities'] ) ) != '' ) {
					if ( isset( $mwb_tyo_previous_saved_cities ) && '' == $mwb_tyo_previous_saved_cities ) {
						if ( array_key_exists( isset( $_POST['mwb_tyo_custom_shipping_cities'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_custom_shipping_cities'] ) ) : '', $mwb_tyo_all_selected_cities ) ) {

							$mwb_tyo_previous_saved_cities[ $current_order_status ][] = $mwb_tyo_all_selected_cities[ sanitize_text_field( wp_unslash( $_POST['mwb_tyo_custom_shipping_cities'] ) ) ];
							$mwb_tyo_previous_saved_changed_time[ $current_order_status ][] = $converted_order_modified_date;
							wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_track_custom_cities', $mwb_tyo_previous_saved_cities );
							wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_custom_change_time', $mwb_tyo_previous_saved_changed_time );
						}
					} else {
						if ( array_key_exists( isset( $_POST['mwb_tyo_custom_shipping_cities'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_custom_shipping_cities'] ) ) : '', $mwb_tyo_all_selected_cities ) ) {

							$mwb_tyo_previous_saved_cities[ $current_order_status ][] = $mwb_tyo_all_selected_cities[ sanitize_text_field( wp_unslash( $_POST['mwb_tyo_custom_shipping_cities'] ) ) ];
							$mwb_tyo_previous_saved_changed_time[ $current_order_status ][] = $converted_order_modified_date;
							wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_track_custom_cities', $mwb_tyo_previous_saved_cities );
							wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_custom_change_time', $mwb_tyo_previous_saved_changed_time );
						}
					}

					wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_save_selected_city', isset( $_POST['mwb_tyo_custom_shipping_cities'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_custom_shipping_cities'] ) ) : '' );
				}
			}
		}


		/**
		 * This function is for saving shipping services meta data
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_save_shipping_services_meta() {
			 global $post;
			if ( isset( $post->ID ) ) {
				$this->mwb_tyo_save_shipping_services_meta__callback($post->ID);
			}
		}

		/**
		 * This function is for saving shipping services meta data
		 *
		 * @link http://www.wpswings.com/
		 * 
		 */
		public function mwb_tyo_save_shipping_services_meta_hpos($order_id){
			$post_id = $order_id;
			$this->mwb_tyo_save_shipping_services_meta__callback($post_id);
		}

		/**
		 * This function is for saving shipping services meta data
		 *
		 * @link http://www.wpswings.com/
		 * 
		 */
		public function mwb_tyo_save_shipping_services_meta__callback($post_id){
			$post_id = $post_id;
			$value_check = isset( $_POST['mwb_tyo_selected_shipping_services_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_selected_shipping_services_nonce_name'] ) ) : '';
			wp_verify_nonce( $value_check, 'mwb_tyo_selected_shipping_services_nonce' );
			if ( isset( $_POST['mwb_tyo_selected_shipping_services'] ) && isset( $_POST['mwb_tyo_tracking_number'] ) && sanitize_text_field( wp_unslash( $_POST['mwb_tyo_tracking_number'] ) ) != '' && ! empty( sanitize_text_field( wp_unslash( $_POST['mwb_tyo_selected_shipping_services'] ) ) ) ) {
				wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_selected_shipping_service', sanitize_text_field( wp_unslash( $_POST['mwb_tyo_selected_shipping_services'] ) ) );
				wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_package_tracking_number', sanitize_text_field( wp_unslash( $_POST['mwb_tyo_tracking_number'] ) ) );

				$headers = array();
				$order = wc_get_order( $post_id );
				$headers[] = 'Content-Type: text/html; charset=UTF-8';
				$mwb_tracking_url = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_enhanced_order_company', true );
				$mwb_tracking_number = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_enhanced_tracking_no', true );
				if ( '3.0.0' > WC()->version ) {
					$fname = $order->get_billing_first_name();
					$lname = $order->get_billing_last_name();
					$to = $order->get_billing_email();
				} else {
					$mwb_all_data = $order->get_data();
					$billing_address = $mwb_all_data['billing'];
					$shipping_address = $mwb_all_data['shipping'];
					$to = $billing_address['email'];
				}
				$subject = __( 'Your Order Status for Order #', 'woocommerce-order-tracker' ) . $post_id;
				$message = __( 'Your Order Status is ', 'woocommerce-order-tracker' ) . $statuses[ $new_status ];
				$mail_header = __( 'Current Order Status is ', 'woocommerce-order-tracker' ) . $statuses[ $new_status ];
				$mail_footer = '';
				$message = '<html>
				<body>
					<style>
						body {
							box-shadow: 2px 2px 10px #ccc;
							color: #767676;
							font-family: Arial,sans-serif;
							margin: 80px auto;
							max-width: 700px;
							padding-bottom: 30px;
							width: 100%;
						}

						h2 {
							font-size: 30px;
							margin-top: 0;
							color: #fff;
							padding: 40px;
							background-color: #557da1;
						}

						h4 {
							color: #557da1;
							font-size: 20px;
							margin-bottom: 10px;
						}

						.content {
							padding: 0 40px;
						}

						.Customer-detail ul li p {
							margin: 0;
						}

						.details .Shipping-detail {
							width: 40%;
							float: right;
						}

						.details .Billing-detail {
							width: 60%;
							float: left;
						}

						.details .Shipping-detail ul li,.details .Billing-detail ul li {
							list-style-type: none;
							margin: 0;
						}

						.details .Billing-detail ul,.details .Shipping-detail ul {
							margin: 0;
							padding: 0;
						}

						.clear {
							clear: both;
						}

						table,td,th {
							border: 2px solid #ccc;
							padding: 15px;
							text-align: left;
						}

						table {
							border-collapse: collapse;
							width: 100%;
						}

						.info {
							display: inline-block;
						}

						.bold {
							font-weight: bold;
						}

						.footer {
							margin-top: 30px;
							text-align: center;
							color: #99B1D8;
							font-size: 12px;
						}
						dl.variation dd {
							font-size: 12px;
							margin: 0;
						}
					</style>

					<div style="padding: 36px 48px; background-color:#557DA1;color: #fff; font-size: 30px; font-weight: 300; font-family:helvetica;" class="header">
						' . $mail_header . '
					</div>		

					<div class="content">

						<div class="Order">
							<h4>Order #' . $post_id . '</h4>
							<table>
								<tbody>
									<tr>
										<th>' . __( 'Order Id', 'woocommerce-order-tracker' ) . '</th>
										<th>' . __( 'Tracking Number ', 'woocommerce-order-tracker' ) . '</th>
									</tr>';
									$mwb_user_tracking_number = wps_order_tracker_get_meta_data( $post_id, 'mwb_tyo_package_tracking_number', true );
									$message .= '<tr>
									<td>' . $mwb_user_tracking_number . '</td>
								</tr>';
								$message .= '</tbody>
							</table>
							<div>
								<a href=' . $mwb_tracking_url . '>' . $mwb_tracking_number . '</a>
							</div>
						</div>
					</div>
					<div style="text-align: center; padding: 10px;" class="footer">
						' . $mail_footer . '
					</div>
				</body>
				</html>';
				$mwb_mail_already_send = wps_order_tracker_get_meta_data( $post_id, 'mwb_tyo_tracking_id_sent', true );
				if ( 1 != $mwb_mail_already_send ) {
					wc_mail( $to, $subject, $message, $headers );
					wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_tracking_id_sent', 1 );
				}
			} elseif ( isset( $_POST['mwb_tyo_tracking_number'] ) && '' != $_POST['mwb_tyo_tracking_number'] ) {
				wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_package_tracking_number', sanitize_text_field( wp_unslash( $_POST['mwb_tyo_tracking_number'] ) ) );
			} else {
				wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_selected_shipping_service', '' );
				wps_order_tracker_update_meta_data( $post_id, 'mwb_tyo_package_tracking_number', '' );
			}
		}

		/**
		 * Track order status.
		 *
		 * @param integer $order_id is order id.
		 * @param string  $old_status is previous status.
		 * @param string  $new_status is the new status.
		 * @return void
		 */
		public function mwb_tyo_track_order_status( $order_id, $old_status, $new_status ) {
			
			$old_status = 'wc-' . $old_status;
			$new_status = 'wc-' . $new_status;
			$mwb_tyo_email_notifier = get_option( 'mwb_tyo_email_notifier', 'no' );
			$order = new WC_Order( $order_id );
			if ( '3.0.0' > WC()->version ) {
				$mwb_date_on_order_change = $order->modified_date;
			
			} else {
				$change_order_status = $order->get_data()['status'];

				$date_on_order_change = $order->get_data();
				
				$mwb_date_on_order_change = $date_on_order_change['date_modified']->format( 'd F, Y H:i' );
				
				

			}
			$mwb_modified_date = $mwb_date_on_order_change ;

			$mwb_status_change_time = array();
			$mwb_status_change_time_temp = array();
			$mwb_status_change_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time', true );
			$mwb_status_change_time_temp = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time_temp', true );
			$mwb_status_change_time_template2 = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_onchange_time_template', true );
			$order_index = 'wc-' . $change_order_status;
			if ( is_array( $mwb_status_change_time_temp ) && ! empty( $mwb_status_change_time_temp ) ) {
				if ( is_array( $mwb_status_change_time_temp )) {

					$mwb_status_change_time[ $order_index ] = $mwb_modified_date;
				 }
			} else {
				$mwb_status_change_time = array();
				if ( is_array( $mwb_status_change_time )) {

					$mwb_status_change_time[ $order_index ] = $mwb_modified_date;
				 }
			}
			if ( is_array( $mwb_status_change_time_temp ) && ! empty( $mwb_status_change_time_temp ) ) {

				$mwb_status_change_time_temp[ $order_index ] = $mwb_modified_date;
			} else {
				$mwb_status_change_time_temp = array();
				if ( is_array( $mwb_status_change_time_temp )) { 

					$mwb_status_change_time_temp[ $order_index ] = $mwb_modified_date;
				}
			}
			if ( is_array( $mwb_status_change_time_template2 ) && ! empty( $mwb_status_change_time_template2 ) ) {

				$mwb_status_change_time_template2[][ $order_index ] = $mwb_modified_date;
			} else {
				$mwb_status_change_time_template2 = array();
				$mwb_status_change_time_template2[][ $order_index ] = $mwb_modified_date;
			}
			$statuses = wc_get_order_statuses();

			$mwb_track_order_status = wps_order_tracker_get_meta_data( $order_id, 'mwb_track_order_status', true );
			if ( is_array( $mwb_track_order_status ) && ! empty( $mwb_track_order_status ) ) {
				$c = count( $mwb_track_order_status );
				if ( $mwb_track_order_status[ $c - 1 ] === $old_status ) {

					if ( in_array( $new_status, $mwb_track_order_status ) ) {

						$key = array_search( $new_status, $mwb_track_order_status );
						unset( $mwb_track_order_status[ $key ] );
						$mwb_track_order_status = array_values( $mwb_track_order_status );
					}

					$mwb_track_order_status[] = $new_status;
					wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_status', $mwb_track_order_status );
					wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_onchange_time', $mwb_status_change_time );
					wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_onchange_time_temp', $mwb_status_change_time_temp );
					wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_onchange_time_template', $mwb_status_change_time_template2 );
				} else {

					$mwb_track_order_status[] = $old_status;
					$mwb_track_order_status[] = $new_status;
					wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_status', $mwb_track_order_status );
					wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_onchange_time', $mwb_status_change_time );
					wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_onchange_time_temp', $mwb_status_change_time_temp );
					wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_onchange_time_template', $mwb_status_change_time_template2 );
				}
			} else {

				$mwb_status_change_time = array();
				$mwb_status_change_time_temp = array();
				$mwb_status_change_time_template2 = array();
				$mwb_track_order_status = array();
				$mwb_track_order_status[] = $old_status;
				$mwb_track_order_status[] = $new_status;

				wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_status', $mwb_track_order_status );
				wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_onchange_time', $mwb_status_change_time );
				wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_onchange_time_temp', $mwb_status_change_time_temp );
				wps_order_tracker_update_meta_data( $order_id, 'mwb_track_order_onchange_time_template', $mwb_status_change_time_template2 );
			}

			if ( 'yes' == $mwb_tyo_email_notifier && 'wc-completed' != $new_status ) {
				if ( '3.0.0' > WC()->version ) {
					$order_id = $order->id;
					$headers = array();
					$headers[] = 'Content-Type: text/html; charset=UTF-8';
					
					$to = $order->get_billing_email();
					$subject = __( 'Your Order Status for Order #', 'woocommerce-order-tracker' ) . $order_id;
					$message = __( 'Your Order Status is ', 'woocommerce-order-tracker' ) . $statuses[ $new_status ];
					$mail_header = __( 'Current Order Status is ', 'woocommerce-order-tracker' ) . $statuses[ $new_status ];
					$mail_footer = '';

				} else {
					$headers = array();
					$headers[] = 'Content-Type: text/html; charset=UTF-8';
					$mwb_all_data = $order->get_data();
					$billing_address = $mwb_all_data['billing'];
					$shipping_address = $mwb_all_data['shipping'];
					$to = $billing_address['email'];
					$subject = __( 'Your Order Status for Order #', 'woocommerce-order-tracker' ) . $order_id;
					$message = __( 'Your Order Status is ', 'woocommerce-order-tracker' ) . $statuses[ $new_status ];
					$mail_header = __( 'Current Order Status is ', 'woocommerce-order-tracker' ) . $statuses[ $new_status ];
					$mail_footer = '';

				}

				$message = '<html>
				<body>
					<style>
						body {
							box-shadow: 2px 2px 10px #ccc;
							color: #767676;
							font-family: Arial,sans-serif;
							margin: 80px auto;
							max-width: 700px;
							padding-bottom: 30px;
							width: 100%;
						}

						h2 {
							font-size: 30px;
							margin-top: 0;
							color: #fff;
							padding: 40px;
							background-color: #557da1;
						}

						h4 {
							color: #557da1;
							font-size: 20px;
							margin-bottom: 10px;
						}

						.content {
							padding: 0 40px;
						}

						.Customer-detail ul li p {
							margin: 0;
						}

						.details .Shipping-detail {
							width: 40%;
							float: right;
						}

						.details .Billing-detail {
							width: 60%;
							float: left;
						}

						.details .Shipping-detail ul li,.details .Billing-detail ul li {
							list-style-type: none;
							margin: 0;
						}

						.details .Billing-detail ul,.details .Shipping-detail ul {
							margin: 0;
							padding: 0;
						}

						.clear {
							clear: both;
						}

						table,td,th {
							border: 2px solid #ccc;
							padding: 15px;
							text-align: left;
						}

						table {
							border-collapse: collapse;
							width: 100%;
						}

						.info {
							display: inline-block;
						}

						.bold {
							font-weight: bold;
						}

						.footer {
							margin-top: 30px;
							text-align: center;
							color: #99B1D8;
							font-size: 12px;
						}
						dl.variation dd {
							font-size: 12px;
							margin: 0;
						}
					</style>

					<div style="padding: 36px 48px; background-color:#557DA1;color: #fff; font-size: 30px; font-weight: 300; font-family:helvetica;" class="header">
						' . $mail_header . '
					</div>		

					<div class="content">

						<div class="Order">
							<h4>Order #' . $order_id . '</h4>
							<table>
								<tbody>
									<tr>
										<th>' . __( 'Product', 'woocommerce-order-tracker' ) . '</th>
										<th>' . __( 'Quantity', 'woocommerce-order-tracker' ) . '</th>
										<th>' . __( 'Price', 'woocommerce-order-tracker' ) . '</th>
									</tr>';

									$order = new WC_Order( $order_id );
									$total = 0;
				foreach ( $order->get_items() as $item_id => $item ) {
					/**
					 * Woocommerce order items.
					 *
					 * @since 1.0.0
					 */ 
					$product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );
					$item_meta      = new WC_Order_Item_Meta( $item, $product );
					$item_meta_html = $item_meta->display( true, true );

					$message .= '<tr>
										<td>' . $item['name'] . '<br>';
						$message .= '<small>' . $item_meta_html . '</small>
											<td>' . $item['qty'] . '</td>
											<td>' . wc_price( $product->get_price() ) . '</td>
										</tr>';
					$total = $total + ( $product->get_price() * $item['qty'] );
				}
									$message .= '<tr>
									<th colspan = "2">' . __( 'Total', 'woocommerce-order-tracker' ) . '</th>
									<td>' . wc_price( $total ) . '</td>';
									$message .= '</tbody>
								</table>
							</div>
							<div class="Customer-detail">
								<h4>' . __( 'Customer details', 'woocommerce-order-tracker' ) . '</h4>
								<ul>
									<li>
										<p class="info">
											<span class="bold">' . __( 'Email', 'woocommerce-order-tracker' ) . ': </span>' . $order->billing_email() . '
										</p>
									</li>
									<li>
										<p class="info">
											<span class="bold">' . __( 'Tel', 'woocommerce-order-tracker' ) . ': </span>' . $order->billing_phone() . '
										</p>
									</li>
								</ul>
							</div>
							<div class="details">
								<div class="Shipping-detail">
									<h4>' . __( 'Shipping Address', 'woocommerce-order-tracker' ) . '</h4>
									' . $order->get_formatted_shipping_address() . '
								</div>
								<div class="Billing-detail">
									<h4>' . __( 'Billing Address', 'woocommerce-order-tracker' ) . '</h4>
									' . $order->get_formatted_billing_address() . '
								</div>
								<div class="clear"></div>
							</div>
						</div>
						<div style="text-align: center; padding: 10px;" class="footer">
							' . $mail_footer . '
						</div>
					</body>
					</html>';
					wc_mail( $to, $subject, $message, $headers );

			}
		}





		/**
		 * This function is for rendering track order button
		 *
		 * @link http://www.wpswings.com/
		 * @param object $order is a object.
		 */
		public function mwb_tyo_track_order_button( $order ) {
			if ( '3.0.0' > WC()->version ) {
				$order_id = $order->id;
			} else {
				$order_id = $order->get_id();
			}
			$mwb_tyo_enable_track_order_feature = get_option( 'mwb_tyo_enable_track_order_feature', 'no' );
			$mwb_tyo_enable_track_order_popup = get_option( 'mwb_tyo_enable_track_order_popup', 'no' );
			if ( 'yes' != $mwb_tyo_enable_track_order_feature ) {
				return;
			}
			if ( 'yes' != $mwb_tyo_enable_track_order_popup ) {
				$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
				$page_id = $mwb_tyo_pages['pages']['mwb_track_order_page'];
				$track_order_url = get_permalink( $page_id );
				?>
				<p><label class="mwb_enhanced_order_note"><?php esc_html_e( 'Note:', 'woocommerce-order-tracker' ); ?></label><span class="mwb_order_note_text"><?php esc_html_e( 'Click The Below To Track Your Order', 'woocommerce-order-tracker' ); ?></span></p>
					<a href="<?php echo esc_attr( $track_order_url ) . '?' . esc_attr( $order_id ); ?>" class="button button-primary"><?php esc_html_e( 'TRACK ORDER', 'woocommerce-order-tracker' ); ?></a>

				<?php
			} else {
				$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
				$page_id = $mwb_tyo_pages['pages']['mwb_track_order_page'];
				$track_order_url = get_permalink( $page_id );
				add_thickbox();
				$order_number = wps_order_tracker_get_meta_data( $order_id, '_order_number', true );
				$prefix = '';
				$prefix = get_option( 'woocommerce_order_number_prefix', false );
				if ( ! empty( $order_number ) ) {
					$order_number = $prefix . $order_number;
				} else {
					$order_number = $order_id;
				}
				if ( isset( $_SERVER['REMOTE_ADDR'] ) && '103.97.184.162' == $_SERVER['REMOTE_ADDR'] ) {
					?>

						<p><label class="mwb_enhanced_order_note"><?php esc_html_e( 'Note:', 'woocommerce-order-tracker' ); ?></label><span class="mwb_order_note_text"><?php esc_html_e( 'Click The Below To Track Your Order', 'woocommerce-order-tracker' ); ?></span><p>
					<?php } ?>
							<a href="<?php echo esc_attr( $track_order_url ) . '?' . esc_attr( $order_id ) . '?TB_iframe=true&width=850'; ?>" class="button button-primary thickbox"  title = "<?php esc_attr_e( 'Order status for - ', 'woocommerce-order-tracker' ) . esc_attr( $order_number ); ?>"><?php esc_html_e( 'TRACK ORDER', 'woocommerce-order-tracker' ); ?></a>
					<?php
			}
		}

		/**
		 * This function is for rendering track order button
		 *
		 * @link http://www.wpswings.com/
		 * @param object $order is a object.
		 */
		public function mwb_tyo_track_order_info( $order ) {
			if ( '3.0.0' > WC()->version ) {
				$order_id = $order->id;
			} else {
				$order_id = $order->get_id();
			}
			$mwb_tyo_enable_track_order_feature = get_option( 'mwb_tyo_enable_track_order_feature', 'no' );
			$mwb_tyo_enable_track_order_popup = get_option( 'mwb_tyo_enable_track_order_popup', 'no' );
			if ( 'yes' != $mwb_tyo_enable_track_order_feature ) {
				return;
			}
			if ( 'yes' != $mwb_tyo_enable_track_order_popup ) {
				$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
				$page_id = $mwb_tyo_pages['pages']['mwb_track_order_page'];
				$track_order_url = get_permalink( $page_id );
				$mwb_tyo_enable_track_order_api = get_option( 'mwb_tyo_enable_third_party_tracking_api', 'no' );
				if ( 'yes' == $mwb_tyo_enable_track_order_api ) {

					$wps_shipping_service = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_selected_shipping_service', true );
					if ( 'canada_post' === $wps_shipping_service ) {
						$wps_shipping_service = 'Canada Post';
					} else if ( 'fedex' === $wps_shipping_service ) {
						$wps_shipping_service = 'FedEx';
					} else if ( 'usps' === $wps_shipping_service ) {
						$wps_shipping_service = 'USPS';
					}
					$wps_est_delivery_date = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_date', true );
					$wps_est_delivery_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_time', true );
					$wps_tyo_tracking_number = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_package_tracking_number', true );
					?>
				<div style="background-color: rgba(246,246,246,255);padding: 20px;">
				<h3 style="font-weight:500"><?php esc_html_e( 'Tracking Info', 'woocommerce-order-tracker' ); ?></h3>
				<p>
					<?php esc_html_e( 'Order picked up by ', 'woocommerce-order-tracker' ); ?><b><?php echo esc_html( $wps_shipping_service ); ?></b><br>
					<?php esc_html_e( 'Estimated Delivery Date : ', 'woocommerce-order-tracker' ); ?><b>
									  <?php
										echo esc_html( $wps_est_delivery_date );
										echo ' ';
										echo esc_html( $wps_est_delivery_time );
										?>
					</b><br>
					<?php esc_html_e( 'Tracking Code : ', 'woocommerce-order-tracker' ); ?><b><?php echo esc_html( $wps_tyo_tracking_number ); ?></b>
				</p>
					<a href="<?php echo esc_attr( $track_order_url ) . '?' . esc_attr( $order_id ); ?>" ><?php esc_html_e( 'track your order', 'woocommerce-order-tracker' ); ?></a>
				</div>
					<?php
				}
			} else {
				$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
				$page_id = $mwb_tyo_pages['pages']['mwb_track_order_page'];
				$track_order_url = get_permalink( $page_id );
				add_thickbox();
				$order_number = wps_order_tracker_get_meta_data( $order_id, '_order_number', true );
				$prefix = '';
				$prefix = get_option( 'woocommerce_order_number_prefix', false );
				if ( ! empty( $order_number ) ) {
					$order_number = $prefix . $order_number;
				} else {
					$order_number = $order_id;
				}
				$mwb_tyo_enable_track_order_api = get_option( 'mwb_tyo_enable_third_party_tracking_api', 'no' );
				if ( 'yes' == $mwb_tyo_enable_track_order_api ) {

					$wps_shipping_service = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_selected_shipping_service', true );
					if ( 'canada_post' === $wps_shipping_service ) {
						$wps_shipping_service = 'Canada Post';
					} else if ( 'fedex' === $wps_shipping_service ) {
						$wps_shipping_service = 'FedEx';
					} else if ( 'usps' === $wps_shipping_service ) {
						$wps_shipping_service = 'USPS';
					}
					$wps_est_delivery_date = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_date', true );
					$wps_est_delivery_time = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_estimated_delivery_time', true );
					$wps_tyo_tracking_number = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_package_tracking_number', true );
					?>
				<div style="background-color: rgba(246,246,246,255);padding: 20px;">
				<h3 style="font-weight:500"><?php esc_html_e( 'Tracking Info', 'woocommerce-order-tracker' ); ?></h3>
				<p>
					<?php esc_html_e( 'Order picked up by ', 'woocommerce-order-tracker' ); ?><b><?php echo esc_html( $wps_shipping_service ); ?></b><br>
					<?php esc_html_e( 'Estimated Delivery Date : ', 'woocommerce-order-tracker' ); ?><b>
									  <?php
										echo esc_html( $wps_est_delivery_date );
										echo ' ';
										echo esc_html( $wps_est_delivery_time );
										?>
					</b><br>
					<?php esc_html_e( 'Tracking Code : ', 'woocommerce-order-tracker' ); ?><b><?php echo esc_html( $wps_tyo_tracking_number ); ?></b>
				</p>
					<a href="<?php echo esc_attr( $track_order_url ) . '?' . esc_attr( $order_id ); ?>" ><?php esc_html_e( 'track your order', 'woocommerce-order-tracker' ); ?></a>
				</div>
					<?php
				}
			}
		}

		/**
		 * Function to add custom status.
		 *
		 * @param array $actions is array of action.
		 * @return array
		 */
		public function adding_custom_bulk_actions_edit_product( $actions ) {

			$order_status = wc_get_order_statuses();
			foreach ( $order_status as $key => $value ) {
				$key = substr( $key, 3 );
				if ( 'processing' !== $key && 'on-hold' !== $key && 'completed' !== $key && 'cancelled' !== $key && 'pending' !== $key && 'refunded' !== $key && 'failed' !== $key ) {
					$key1 = 'mark_' . $key;
					$actions[ $key1 ] = __( 'Change status to ' . $key, 'woocommerce' );

				}
			}
			return $actions;

		}

		/**
		 * Function to change status.
		 *
		 * @param string $redirect is an url.
		 * @param array  $doaction is array.
		 * @param array  $object_ids array of ids.
		 * @return string
		 */
		public function wot_bulk_process_custom_status( $redirect, $doaction, $object_ids ) {

			if ( 'mark_processing' == $doaction && 'mark_on-hold' == $doaction && 'mark_completed' == $doaction && 'mark_cancelled' == $doaction && 'mark_pending' == $doaction && 'mark_refunded' == $doaction && 'trash' == $doaction && 'mark_failed' == $doaction && '-1' == $doaction ) {
				return $redirect;
			}
			$tracking_statuses = get_option( 'mwb_tyo_new_custom_statuses_for_order_tracking' );
			if ( ! empty( $tracking_statuses ) ) {

				$do_action_revised = 'wc-' . substr( $doaction, 5 );

				foreach ( $tracking_statuses as $key => $value ) {
					if ( $value == $do_action_revised ) {
						foreach ( $object_ids as $order_id ) {

							$order = wc_get_order( $order_id );
							$order->update_status( $do_action_revised );

						}
					}
				}
			}

			return $redirect;

		}

		/**
		 * Shortcode callback.
		 *
		 * @param array $atts is array atts.
		 * @param string $content is the string.
		 * @return void
		 */
		public function wps_create_tracking_page_shortcode_callback( $atts, $content ){
			
			
			if( is_user_logged_in( ) ) {
				$wps_page_id = get_option('woocommerce_myaccount_page_id');
				if( ! empty( $wps_page_id ) ) {

					// $wps_link = get_permalink( $wps_page_id ) . '/orders/';
					do_action( 'woocommerce_account_navigation' ); ?>

					<div class="woocommerce-MyAccount-content">
						<?php
							/**
							 * My Account content.
							 *
							 * @since 2.6.0
							 */
							do_action( 'woocommerce_account_content' );
						?>
					</div> <?php
					
				}
			} else{
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

					

				
			}


		}

		public function wps_track_order_form_shortcode_callback($atts, $content) {

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


		}

	}
				new MWB_Track_Your_Order();
}
