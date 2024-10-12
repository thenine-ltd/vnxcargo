<?php
/**
 * Main Plugin class for managing admin interfaces.
 *
 * @class    MWB_TYO_Admin_Settings
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/admin
 *  
 *  
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'MWB_TYO_Admin_Settings' ) ) {


	/**
	 * Class for admin setting.
	 */
	class MWB_TYO_Admin_Settings {

		/**
		 * This is construct of class.
		 *
		 * @link http://www.wpswings.com/
		 */
		public function __construct() {
			$this->id = 'mwb_tyo_settings';
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'mwb_tyo_add_settings_tab' ), 50 );
			add_action( 'woocommerce_settings_tabs_' . $this->id, array( $this, 'mwb_tyo_settings_tab' ) );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'woocommerce_sections_' . $this->id, array( $this, 'mwb_tyo_output_sections' ) );

			// license validation.
			$mwb_wot_license_hash = get_option( 'mwb_tyo_license_hash' );
			$mwb_wot_license_key = get_option( 'mwb_tyo_license_key' );
			$mwb_wot_license_plugin = get_option( 'mwb_tyo_plugin_name' );
			$mwb_wot_hash = md5( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . $mwb_wot_license_plugin . $mwb_wot_license_key : '' );
			$mwb_wot_license_hash = get_option( 'mwb_tyo_license_hash' );

			$mwb_tyo_activation_time = get_option( 'mwb_tyo_activation_date_time', false );
			if ( ! $mwb_tyo_activation_time ) {
				$current_timestamp = current_time( 'timestamp' );
				update_option( 'mwb_tyo_activation_date_time', $current_timestamp );
				$mwb_tyo_activation_time = $current_timestamp;
			}
			$mwb_tyo_after_time = strtotime( '+14 days', $mwb_tyo_activation_time );
			$mwb_tyo_currenttime = current_time( 'timestamp' );

			if ( $mwb_wot_hash != $mwb_wot_license_hash ) {
				add_action( 'admin_menu', array( $this, 'mwb_tyo_admin_menu_verification' ) );
			}

			if ( $mwb_tyo_after_time < $mwb_tyo_currenttime && $mwb_wot_hash != $mwb_wot_license_hash ) {
				add_action( 'init', array( $this, 'mwb_tyo_redirect_to_verification' ) );
			}
		}

		/**
		 * This function will redirect to MWB Order Tracker Verification page
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_redirect_to_verification() {
			if ( isset( $_GET['page'] ) && isset( $_GET['tab'] ) ) {
				if ( 'wc-settings' == $_GET['page'] && 'mwb_tyo_settings' == $_GET['tab'] ) {
					wp_redirect( admin_url( 'admin.php?page=mwb_verification' ) );
				}
			}
		}


		/**
		 * Add sub menu page to woocommerce setting
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_admin_menu_verification() {
			add_submenu_page( 'woocommerce', __( 'WPS License', 'woocommerce-order-tracker' ), __( 'WPS ORDER TRACKER LICENSE', 'woocommerce-order-tracker' ), 'manage_woocommerce', 'mwb_verification', array( $this, 'mwb_tyo_ordertracker_verification' ) );
		}

		/**
		 * This function will include the license page
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_ordertracker_verification() {
			include_once MWB_TRACK_YOUR_ORDER_PATH . 'admin/mwb-wot-license.php';
		}

		/**
		 * Add new tab for woocommerce setting.
		 *
		 * @param array $settings_tabs is the array of tab of woocommerce setting.
		 * @return array
		 */
		public static function mwb_tyo_add_settings_tab( $settings_tabs ) {
			$settings_tabs['mwb_tyo_settings'] = __( 'Track Your Order', 'woocommerce-order-tracker' );
			return $settings_tabs;
		}

		/**
		 * Save section setting
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_settings_tab() {
			global $current_section;
			woocommerce_admin_fields( self::mwb_tyo_get_settings( $current_section ) );
		}

		/**
		 * Output of section setting
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_output_sections() {

			global $current_section;
			$sections = $this->mwb_tyo_get_sections();
			if ( empty( $sections ) || 1 === count( $sections ) ) {
				return;
			}

			echo '<ul class="subsubsub">';

			$array_keys = array_keys( $sections );

			foreach ( $sections as $id => $label ) {
				echo '<li><a href="' . esc_attr( admin_url( 'admin.php?page=wc-settings&tab=' ) . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . esc_html( $label ) . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
			}
			echo '</ul><br class="clear" />';

		}

		/**
		 * Create section setting
		 *
		 * @link http://www.wpswings.com/
		 */
		public function mwb_tyo_get_sections() {

			$sections = array(
				''              => __( 'Track Order', 'woocommerce-order-tracker' ),
				'custom_status' => __( 'Build Custom Order Status', 'woocommerce-order-tracker' ),
				'other'         => __( 'Common Setting', 'woocommerce-order-tracker' ),
				'templates'     => __( 'Templates', 'woocommerce-order-tracker' ),
				'enable_api'    => __( 'Shipping Services Integration', 'woocommerce-order-tracker' ),
				'new_settings'  => __( 'New Settings', 'woocommerce-order-tracker' ),
				'tracking_with_google_map' => __( 'Track Your Order With Google Map', 'woocommerce-order-tracker' ),
				'track_with_17track' => __( '17 Track Integration', 'woocommerce-order-tracker' ),
				'enhanced_tracking'     => __( 'Enhanced Tracking', 'woocommerce-order-tracker' ),
			);

			/**
			 * Add more section in setting.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'mwb_tyo_get_sections' . $this->id, $sections );
		}

		/**
		 * Section setting.
		 *
		 * @param string $current_section is the contains current section.
		 * @return array
		 */
		public function mwb_tyo_get_settings( $current_section ) {

			$custom_order_status = get_option( 'mwb_tyo_new_custom_order_status', array() );
			$order_status = array(
				'wc-dispatched' => __( 'Order Dispatched', 'woocommerce-order-tracker' ),
				'wc-packed' => __( 'Order Packed', 'woocommerce-order-tracker' ),
				'wc-shipped' => __( 'Order Shipped', 'woocommerce-order-tracker' ),
			);
			if ( is_array( $custom_order_status ) && ! empty( $custom_order_status ) ) {
				foreach ( $custom_order_status as $key => $value ) {
					foreach ( $value as $status_key => $status_value ) {
						$order_status[ 'wc-' . $status_key ] = $status_value;
					}
				}
			}

			$statuses = wc_get_order_statuses();
			$mwb_tyo_statuses = wc_get_order_statuses();

			if ( isset( $statuses['wc-cancelled'] ) ) {
				unset( $statuses['wc-cancelled'] );
			}
			if ( 'other' == $current_section ) {
				$settings = array(
					array(
						'title' => __( 'Basic Settings', 'woocommerce-order-tracker' ),
						'type'  => 'title',
					),
					array(
						'title'         => __( 'Main Wrapper Class of Theme', 'woocommerce-order-tracker' ),
						'desc'          => __( 'Write the main wrapper class of your theme if some design issue arises.', 'woocommerce-order-tracker' ),
						'type'          => 'text',
						'id'        => 'mwb_tyo_track_order_class',
					),
					array(
						'title'         => __( 'Child Wrapper Class of Theme', 'woocommerce-order-tracker' ),
						'desc'          => __( 'Write the child wrapper class of your theme if some design issue arises.', 'woocommerce-order-tracker' ),
						'type'          => 'text',
						'id'        => 'mwb_tyo_track_order_child_class',
					),
					array(
						'title'         => __( 'Tracking Order Page Custom CSS', 'woocommerce-order-tracker' ),
						'desc'          => __( 'Write the custom css for Tracking Order page.', 'woocommerce-order-tracker' ),
						'type'          => 'textarea',
						'id'        => 'mwb_tyo_tracking_order_custom_css',
					),
				);
				foreach ( $statuses as $key => $value ) {
					$key = str_replace( '-', '_', $key );
					$text_arr = array(
						'title'         => __( 'Text for ', 'woocommerce-order-tracker' ) . $value . __( ' status on tracking page', 'woocommerce-order-tracker' ),
						'desc'          => __( 'Write the text for ', 'woocommerce-order-tracker' ) . $value . __( ' to be shown on frontend during order tracking.', 'woocommerce-order-tracker' ),
						'type'          => 'text',
						'id'        => 'mwb_tyo_' . $key . '_text',
					);
					$settings[] = $text_arr;
				}
				$settings[] = array(
					'type'  => 'sectionend',
				);

				/**
				 * Add more common setting.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'mwb_tyo_get_common_settings' . $this->id, $settings );
			} else if ( 'custom_status' == $current_section ) {
				include_once MWB_TRACK_YOUR_ORDER_PATH . 'admin/class-mwb-custom-order-status.php';
				$settings = array();

				/**
				 * Add more common setting.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'mwb_tyo_get_common_settings' . $this->id, $settings );
			} else if ( 'templates' == $current_section ) {
				include_once MWB_TRACK_YOUR_ORDER_PATH . 'admin/track-order-templates.php';
				$settings = array();

				/**
				 * Add more common setting.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'mwb_tyo_get_track_order_templates' . $this->id, $settings );
			} else if ( 'enable_api' == $current_section ) {
				$settings = array(
					array(
						'title' => __( 'Third Party Shipment Tracking API To Track Order In Real Time', 'woocommerce-order-tracker' ),
						'type'  => 'title',
					),
					array(
						'title'   => __( 'Enable Third Party Tracking API', 'woocommerce-order-tracker' ),
						'desc'    => __( 'Enable Third Party Tracking API', 'woocommerce-order-tracker' ),
						'default' => 'no',
						'type'    => 'checkbox',
						'id'      => 'mwb_tyo_enable_third_party_tracking_api',
					),
					array(
						'title' => __( 'Enter The Shop Address', 'woocommerce-order-tracker' ),
						'desc'  => __( 'Enter the shop address so that customer can find your store easily', 'woocommerce-order-tracker' ),
						'type'  => 'text',
						'id'    => 'mwb_tyo_shop_address',
						'desc_tip' => true,
					),
					array(
						'title'   => __( 'Enable FedEx Shipment Tracking', 'woocommerce-order-tracker' ),
						'desc'    => __( 'Enable FedEx Shipment Tracking API', 'woocommerce-order-tracker' ),
						'default' => 'no',
						'type'    => 'checkbox',
						'id'      => 'mwb_tyo_enable_track_order_using_api',
					),
					array(
						'title' => __( 'Enter Your FedEx User Key', 'woocommerce-order-tracker' ),
						'desc'  => __( 'Enter your FedEx user key', 'woocommerce-order-tracker' ),
						'type'  => 'text',
						'id'    => 'mwb_fedex_userkey',
						'desc_tip' => true,
					),
					array(
						'title' => __( 'Enter Your FedEx User Password', 'woocommerce-order-tracker' ),
						'desc'  => __( 'Enter your FedEx user password', 'woocommerce-order-tracker' ),
						'type'  => 'text',
						'id'    => 'mwb_fedex_userpassword',
						'desc_tip' => true,
					),
					array(
						'title' => __( 'Enter Your FedEx Account Number', 'woocommerce-order-tracker' ),
						'desc'  => __( 'Enter your FedEx account number', 'woocommerce-order-tracker' ),
						'type'  => 'text',
						'id'    => 'mwb_fedex_account_number',
						'desc_tip' => true,
					),
					array(
						'title' => __( 'Enter Your FedEx Meter Number', 'woocommerce-order-tracker' ),
						'desc'  => __( 'Enter your FedEx meter number', 'woocommerce-order-tracker' ),
						'type'  => 'text',
						'id'    => 'mwb_fedex_meter_number',
						'desc_tip' => true,
					),
					array(
						'title'   => __( 'Enable Canada Post Shipment Tracking', 'woocommerce-order-tracker' ),
						'desc'    => __( 'Enable Canada Post Shipment Tracking API', 'woocommerce-order-tracker' ),
						'default' => 'no',
						'type'    => 'checkbox',
						'id'      => 'mwb_tyo_enable_canadapost_tracking',
					),
					array(
						'title' => __( 'Enter your Canada Post UserName', 'woocommerce-order-tracker' ),
						'desc'  => __( 'Enter your Canada Post UserName Here', 'woocommerce-order-tracker' ),
						'type'  => 'text',
						'id'    => 'mwb_tyo_canadapost_tracking_user_key',
						'desc_tip' => true,
					),
					array(
						'title' => __( 'Enter your Canada Post User Password', 'woocommerce-order-tracker' ),
						'desc'  => __( 'Enter your Canada Post User Password Here', 'woocommerce-order-tracker' ),
						'type'  => 'text',
						'id'    => 'mwb_tyo_canadapost_tracking_user_password',
						'desc_tip' => true,
					),

					array(
						'title'   => __( 'Enable USPS Shipment Tracking', 'woocommerce-order-tracker' ),
						'desc'    => __( 'Enable USPS Shipment Tracking API', 'woocommerce-order-tracker' ),
						'default' => 'no',
						'type'    => 'checkbox',
						'id'      => 'mwb_tyo_enable_usps_tracking',
					),
					array(
						'title' => __( 'Enter your USPS UserName', 'woocommerce-order-tracker' ),
						'desc'  => __( 'Enter your USPS UserName Here', 'woocommerce-order-tracker' ),
						'type'  => 'text',
						'id'    => 'mwb_tyo_usps_tracking_user_key',
						'desc_tip' => true,
					),
					array(
						'title' => __( 'Enter your USPS User Password', 'woocommerce-order-tracker' ),
						'desc'  => __( 'Enter your USPS User Password Here', 'woocommerce-order-tracker' ),
						'type'  => 'text',
						'id'    => 'mwb_tyo_usps_tracking_user_password',
						'desc_tip' => true,
					),

					array(
						'type'  => 'sectionend',
					),
				);

				/**
				 * Add more setting.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'mwb_tyo_get_track_order_settings' . $this->id, $settings );
			} else if ( 'new_settings' == $current_section ) {

				$statuses = wc_get_order_statuses();
				$defaultstatuses = array_keys( $statuses );
				array_push( $defaultstatuses, 'wc-dispatched', 'wc-packed', 'wc-shipped' );
				if ( is_array( $custom_order_status ) && ! empty( $custom_order_status ) ) {
					foreach ( $custom_order_status as $key => $value ) {
						foreach ( $value as $status_key => $status_value ) {
							array_push( $defaultstatuses, 'wc-' . $status_key );
						}
					}
				}

				$new_order_status = array();
				$mwb_tyo_old_selected_statuses = get_option( 'mwb_tyo_new_settings_custom_statuses_for_order_tracking', false );

				if ( empty( $mwb_tyo_old_selected_statuses ) ) {
					update_option( 'mwb_tyo_new_settings_custom_statuses_for_order_tracking', $defaultstatuses );
				}

				$custom_order_status = get_option( 'mwb_tyo_new_custom_order_status', array() );

				$order_status = array(
					'wc-packed' => __( 'Order Packed', 'woocommerce-order-tracker' ),
					'wc-dispatched' => __( 'Order Dispatched', 'woocommerce-order-tracker' ),
					'wc-shipped' => __( 'Order Shipped', 'woocommerce-order-tracker' ),
				);
				if ( is_array( $custom_order_status ) && ! empty( $custom_order_status ) ) {
					foreach ( $custom_order_status as $key => $value ) {
						foreach ( $value as $status_key => $status_value ) {
							$order_status[ 'wc-' . $status_key ] = $status_value;
						}
					}
				}
				$statuses = wc_get_order_statuses();
				foreach ( $statuses as $keys => $values ) {
					$order_status[ $keys ] = $values;
				}
				if ( is_array( $mwb_tyo_old_selected_statuses ) && ! empty( $mwb_tyo_old_selected_statuses ) ) {
					
					foreach ( $mwb_tyo_old_selected_statuses as $new_key => $new_value ) {
						if( array_key_exists( $new_value, $new_order_status ) ) {

							$new_order_status[ $new_value ] = $order_status[ $new_value ];
						}

					}
				}

				$flag = false;
				foreach ( $order_status as $key => $value ) {
					if ( ! isset( $new_order_status[ $key ] ) ) {
						$new_order_status[ $key ] = $value;
						$flag = true;
					}
				}

				if ( $flag ) {
					$order_status = $new_order_status;
				} else {
					if ( is_array( $mwb_tyo_old_selected_statuses ) && ! empty( $mwb_tyo_old_selected_statuses ) ) {
						foreach ( $mwb_tyo_old_selected_statuses as $new_keys => $new_values ) {
							$mwb_tyo_final_position[ $new_values ] = $order_status[ $new_values ];

						}
						$order_status = $mwb_tyo_final_position;
					}
				}

				$mwb_tyo_different_date_format = array(
					'd F,Y H:i' => __( 'dd Month yyyy T', 'woocommerce-order-tracker' ),
					'd/m/y' => __( 'dd/mm/yy', 'woocommerce-order-tracker' ),
					'd F,Y g:i a' => __( 'dd Month,yyyy T AM/PM', 'woocommerce-order-tracker' ),
					'Y/m/d' => __( 'yyyy/mm/dd', 'woocommerce-order-tracker' ),
					'm/d/Y' => __( 'mm/dd/yyyy', 'woocommerce-order-tracker' ),
					'd M, y' => __( 'd M, yy', 'woocommerce-order-tracker' ),
					'D, d M, y' => __( 'DD, d MM, yy', 'woocommerce-order-tracker' ),
					'y-m-d' => __( 'yy-mm-dd', 'woocommerce-order-tracker' ),
				);

				$settings = array(
					array(
						'title' => __( 'This Settings Will Work For New Added Templates', 'woocommerce-order-tracker' ),
						'type'  => 'title',
					),
					array(
						'title'    => __( 'Select Your Order Statuses To Show On Front-End For Tracking', 'woocommerce-order-tracker' ),
						'desc'     => __( 'Select New Order Statuses And Default Statuses Which You Want For Order Tracking, You can change the position of the statuses as you want this section is sortable', 'woocommerce-order-tracker' ),
						'class'    => 'wc-enhanced-select',
						'css'      => 'min-width:300px;',
						'default'  => '',
						'type'     => 'multiselect',
						'options'  => $order_status,
						'desc_tip' => true,
						'id'        => 'mwb_tyo_new_settings_custom_statuses_for_order_tracking',
						'desc_tip' => true,
					),
					array(
						'title'     => __( 'Select Date Format', 'woocommerce-order-tracker' ),
						'desc'      => __( 'Select date format to show on tracking templates', 'woocommerce-order-tracker' ),
						'class'    => 'wc-enhanced-select',
						'css'      => 'min-width:300px;',
						'default'   => '',
						'type'      => 'select',
						'options'   => $mwb_tyo_different_date_format,
						'id'        => 'mwb_tyo_selected_date_format',
						'desc_tip'  => true,
					),
					array(
						'type'  => 'sectionend',
					),
				);

				/**
				 * Add more common setting.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'mwb_tyo_all_new_settings' . $this->id, $settings );
			} elseif ( 'tracking_with_google_map' == $current_section ) {
				include_once MWB_TRACK_YOUR_ORDER_PATH . 'admin/mwb-tyo-track-with-google-map.php';
				$settings = array();

				/**
				 * Add more common setting.
				 *
				 * @since 1.0.0
				 */	
				return apply_filters( 'mwb_tyo_track_with_google_map' . $this->id, $settings );
			} elseif ( 'track_with_17track' == $current_section ) {
				$settings = array(
					array(
						'title' => __( 'Third Party Shipment Tracking API To Track Order In Real Time With 17Track.net', 'woocommerce-order-tracker' ),
						'type'  => 'title',
					),
					array(
						'title'   => __( 'Enable 17Track.net Tracking Feature', 'woocommerce-order-tracker' ),
						'desc'    => __( 'Enable 17Track.net tracking Feature if you ship your orders with different shipping companies', 'woocommerce-order-tracker' ),
						'default' => 'no',
						'type'    => 'checkbox',
						'id'      => 'mwb_tyo_enable_17track_integration',
					),

					array(
						'type'  => 'sectionend',
					),
				);

				/**
				 * Add more setting.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'mwb_tyo_17track_order_settings' . $this->id, $settings );
			} elseif ( 'enhanced_tracking' == $current_section ) {
				include_once MWB_TRACK_YOUR_ORDER_PATH . 'admin/enhanced-tracking-settings.php';
				$settings = array();

				/**
				 * Add more setting.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'mwb_tyo_enhanced_tracking' . $this->id, $settings );
			} else {

				$total_hidden_status = $mwb_tyo_statuses;
				foreach ( $order_status as $key => $val ) {
					$total_hidden_status[ $key ] = $val;
				}

				$settings = array(
					array(
						'title' => __( 'Track Your Order', 'woocommerce-order-tracker' ),
						'type'  => 'title',
					),
					array(
						'title'         => __( 'Enable Order tracking Feature', 'woocommerce-order-tracker' ),
						'desc'          => __( 'Enable Track Your Order Feature', 'woocommerce-order-tracker' ),
						'default'       => 'no',
						'type'          => 'checkbox',
						'id'        => 'mwb_tyo_enable_track_order_feature',
					),
					array(
						'title'         => __( 'Enable use of icon for order status', 'woocommerce-order-tracker' ),
						'desc'          => __( 'Enable this to show icon instead of text for order status in order table.', 'woocommerce-order-tracker' ),
						'default'       => 'no',
						'type'          => 'checkbox',
						'id'        => 'mwb_tyo_enable_order_status_icon',
					),
					array(
						'title'         => __( 'Shortcode to create Order Tracking page', 'woocommerce-order-tracker' ),
						'desc'          => __( '[wps_create_tracking_page] -> it will show my-account-page for logged in user and it will show tracking form for guest user.', 'woocommerce-order-tracker' ),
						'default'       => 'no',
						'type'          => 'checkbox',
						'id'        	=> 'wps_shortcode_desc',
						'class'     	=> 'wps_shortcode_hidden',
					),
					array(
						'title'         => __( 'Shortcode to show track order form', 'woocommerce-order-tracker' ),
						'desc'          => __( '[wps_track_order_form] -> it will show tracking form for logged in user as well as guest user.', 'woocommerce-order-tracker' ),
						'default'       => 'no',
						'type'          => 'checkbox',
						'id'        	=> 'wps_shortcode_desc',
						'class'     	=> 'wps_shortcode_hidden',
					),
					array(
						'title'         => __( 'Enable Order tracking using order id only', 'woocommerce-order-tracker' ),
						'desc'          => __( 'In Default case, guest user can track order using email and order id. Enable this to track order using order id only.', 'woocommerce-order-tracker' ),
						'default'       => 'no',
						'type'          => 'checkbox',
						'id'        => 'mwb_tyo_enable_track_order_using_order_id',
					),
					array(
						'title'         => __( 'Enable logged-in user to EXPORT ORDER', 'woocommerce-order-tracker' ),
						'desc'          => __( 'Enabling this setting , Logged-in user can export order from my-account/order sections', 'woocommerce-order-tracker' ),
						'default'       => 'no',
						'type'          => 'checkbox',
						'id'        => 'mwb_tyo_enable_export_order_logged_in_user',
					),
					array(
						'title'         => __( 'Enable Guest user to EXPORT ORDER', 'woocommerce-order-tracker' ),
						'desc'          => __( 'Enabling this setting , Guest user can export order from guest tracking page', 'woocommerce-order-tracker' ),
						'default'       => 'no',
						'type'          => 'checkbox',
						'id'        => 'mwb_tyo_enable_export_order_guest_user',
					),
					array(
						'title'     => __( 'Enable Pop-up View For Order Tracking', 'woocommerce-order-tracker' ),
						'desc'      => __( 'Enable Track Your Order Feature in pop-up box', 'woocommerce-order-tracker' ),
						'default'   => 'no',
						'type'      => 'checkbox',
						'id'        => 'mwb_tyo_enable_track_order_popup',
					),
					array(
						'title'         => __( 'Enable Use Of Custom Order Status', 'woocommerce-order-tracker' ),
						'desc'          => __( 'Enable use of Custom Order Status', 'woocommerce-order-tracker' ),
						'default'       => 'no',
						'type'          => 'checkbox',
						'id'        => 'mwb_tyo_enable_custom_order_feature',
					),
					array(
						'title'    => __( 'Custom Order Statuses', 'woocommerce-order-tracker' ),
						'desc'     => __( 'Select new Order Status to be created for enhanced order tracking', 'woocommerce-order-tracker' ),
						'class'    => 'wc-enhanced-select',
						'css'      => 'min-width:300px;',
						'default'  => '',
						'type'     => 'multiselect',
						'options'  => $order_status,
						'desc_tip' => true,
						'id'        => 'mwb_tyo_new_custom_statuses_for_order_tracking',
					),
					array(
						'title'    => __( 'hidden_status', 'woocommerce-order-tracker' ),
						'desc'     => __( 'Select Order Status to be shown in the Approval section while order tracking', 'woocommerce-order-tracker' ),
						'class'    => 'wc-enhanced-select',
						'css'      => 'min-width:300px;',
						'default'  => '',
						'type'     => 'multiselect',
						'options'  => $total_hidden_status,
						'desc_tip' => true,
						'id'        => 'mwb_tyo_order_status_in_hidden',
					),
					array(
						'title'    => __( 'Approval', 'woocommerce-order-tracker' ),
						'desc'     => __( 'Select Order Status to be shown in the Approval section while order tracking', 'woocommerce-order-tracker' ),
						'class'    => 'wc-enhanced-select',
						'css'      => 'min-width:300px;',
						'default'  => '',
						'type'     => 'multiselect',
						'options'  => $mwb_tyo_statuses,
						'desc_tip' => true,
						'id'        => 'mwb_tyo_order_status_in_approval',
					),
					array(
						'title'    => __( 'Processing', 'woocommerce-order-tracker' ),
						'desc'     => __( 'Select Order Status to be shown in the Processing section while order tracking', 'woocommerce-order-tracker' ),
						'class'    => 'wc-enhanced-select',
						'css'      => 'min-width:300px;',
						'default'  => '',
						'type'     => 'multiselect',
						'options'  => $mwb_tyo_statuses,
						'desc_tip' => true,
						'id'        => 'mwb_tyo_order_status_in_processing',
					),
					array(
						'title'    => __( 'Shipping', 'woocommerce-order-tracker' ),
						'desc'     => __( 'Select Order Status to be shown in the Shipping section while order tracking', 'woocommerce-order-tracker' ),
						'class'    => 'wc-enhanced-select',
						'css'      => 'min-width:300px;',
						'default'  => '',
						'type'     => 'multiselect',
						'options'  => $mwb_tyo_statuses,
						'desc_tip' => true,
						'id'        => 'mwb_tyo_order_status_in_shipping',
					),
					array(
						'title'     => __( 'Enable E-mail Notification Feature', 'woocommerce-order-tracker' ),
						'desc'      => __( 'Enable to send the e-mail notification to the customer on changing order status', 'woocommerce-order-tracker' ),
						'default'   => 'no',
						'type'      => 'checkbox',
						'id'        => 'mwb_tyo_email_notifier',
					),
					array(
						'type'  => 'sectionend',
					),
				);

				/**
				 * Add more setting.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'mwb_tyo_get_track_order_settings' . $this->id, $settings );
			}
		}

		 /**
		  * Save setting
		  *
		  * @link http://www.wpswings.com/
		  */
		public function save() {
			global $current_section;

			if ( 'custom_status' == $current_section || 'templates' == $current_section || 'tracking_with_google_map' == $current_section || 'enhanced_tracking' == $current_section ) {

				$settings = array();
			} else {
				$settings = $this->mwb_tyo_get_settings( $current_section );
			}
			WC_Admin_Settings::save_fields( $settings );
		}
	}
		new MWB_TYO_Admin_Settings();
}
