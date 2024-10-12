<?php
/**
 *
 * Plugin Name:           Woocommerce Order Tracker
 * Plugin URI:            https://wpswings.com
 * Description:           Woocommerce Order Tracker provides you the best way to track your orders.
 * Version:				  2.1.7
 * Author:                WP Swings<webmaster@wpswings.com>
 * Author URI:            https://wpswings.com/?utm_source=wpswings-order-tracker-official&utm_medium=order-tracker-cc-backend&utm_campaign=official
 * Requires at least:     4.4
 * Tested up to:          6.3.1
 * WC requires at least:  3.0
 * WC tested up to:       8.2.0
 * Text Domain:           woocommerce-order-tracker
 * Domain Path:           /languages
 * License:               GNU General Public License v3.0
 * License URI:           http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Woocommerce_Order_Tracker
 */

/**
 * Exit if accessed directly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Automattic\WooCommerce\Utilities\OrderUtil;
$activated = true;
if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		$activated = false;
		wot_dependency_checkup();
	}
} else {

	/**
	 * Add more setting.
	 *
	 * @since 1.0.0
	 */
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		$activated = false;
		wot_dependency_checkup();
	}
}

/**
 * Checking dependency for woocommerce plugin.
 *
 * @return void
 */
function wot_dependency_checkup() {
	if ( ! in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins' ), true ) ) {
		add_action( 'admin_init', 'wot_deactivate_child_plugin' );
		add_action( 'admin_notices', 'wot_show_admin_notices' );
	}
}
/**
 * Deactivating child plugin.
 *
 * @return void
 */
function wot_deactivate_child_plugin() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}
/**
 * Showing admin notices.
 *
 * @return void
 */
function wot_show_admin_notices() {
	$wot_child_plugin  = __( 'Woocommerce order tracker', 'woocommerce-order-tracker' );
	$wot_parent_plugin = __( 'Woocommerce', 'woocommerce-order-tracker' );
	echo '<div class="notice notice-error is-dismissible"><p>'
		/* translators: %s: dependency checks */
		. sprintf( esc_html__( '%1$s requires %2$s to function correctly. Please activate %2$s before activating %1$s. For now, the plugin has been deactivated.', 'woocommerce-order-tracker' ), '<strong>' . esc_html( $wot_child_plugin ) . '</strong>', '<strong>' . esc_html( $wot_parent_plugin ) . '</strong>' )
		. '</p></div>';
	if ( isset( $_GET['activate'] ) ) { // phpcs:ignore
		unset( $_GET['activate'] ); //phpcs:ignore
	}
}

/**
 * Check if WooCommerce is active
 */
if ( $activated ) {
	define( 'MWB_TRACK_YOUR_ORDER_PATH', plugin_dir_path( __FILE__ ) );
	define( 'MWB_TRACK_YOUR_ORDER_URL', plugin_dir_url( __FILE__ ) );
	define( 'MWB_TRACK_YOUR_ORDER_TEXT_DOMAIN', 'woocommerce-order-tracker' );
	define( 'MWB_TRACK_YOUR_ORDER_VERSION', '2.1.7' );

	include_once MWB_TRACK_YOUR_ORDER_PATH . 'includes/class-mwb-track-your-order.php';
	include_once MWB_TRACK_YOUR_ORDER_PATH . 'admin/class-mwb-tyo-admin-settings.php';
	include_once MWB_TRACK_YOUR_ORDER_PATH . 'includes/class-mwb-track-your-order-with-fedex.php';

	/**
	 * This function is used for formatting the price
	 *
	 * @link http://www.wpswings.com/
	 * @param unknown $price is price.
	 * @return string
	 */
	function mwb_tyo_format_price( $price ) {
		$price = number_format( (float) $price, 2, '.', '' );
		$currency_symbol = get_woocommerce_currency_symbol();
		$currency_pos = get_option( 'woocommerce_currency_pos' );
		switch ( $currency_pos ) {
			case 'left':
				$uprice = $currency_symbol . '<span class="mwb_rnx_formatted_price">' . $price . '</span>';
				break;
			case 'right':
				$uprice = '<span class="mwb_rnx_formatted_price">' . $price . '</span>' . $currency_symbol;
				break;
			case 'left_space':
				$uprice = $currency_symbol . '&nbsp;<span class="mwb_rnx_formatted_price">' . $price . '</span>';
				break;
			case 'right_space':
				$uprice = '<span class="mwb_rnx_formatted_price">' . $price . '</span>&nbsp;' . $currency_symbol;
				break;
		}

		return $uprice;
	}

	/**
	 * This function is to add track order page
	 *
	 * @link http://www.wpswings.com/
	 */
	function mwb_tyo_add_pages() {
		$mwb_tyo_existing_time = get_option( 'mwb_wtvv_activation_date_time', '' );
		if ( isset( $mwb_tyo_existing_time ) && ( '' == $mwb_tyo_existing_time || null == $mwb_tyo_existing_time ) ) {
			$mwb_tyo_current_datetime = current_time( 'timestamp' );
			update_option( 'mwb_tyo_activation_date_time', $mwb_tyo_current_datetime );
		}
		$email = get_option( 'admin_email', false );
		$admin = get_user_by( 'email', $email );
		$admin_id = $admin->ID;

		$mwb_tyo_tracking = array(
			'post_author'    => $admin_id,
			'post_name'      => 'track-your-order',
			'post_title'     => __( 'Track Order', 'woocommerce-order-tracker' ),
			'post_type'      => 'page',
			'post_status'    => 'publish',

		);

		$page_id = wp_insert_post( $mwb_tyo_tracking );

		if ( $page_id ) {
			$mwb_tyo_pages['pages']['mwb_track_order_page'] = $page_id;
		}

		$mwb_tyo_guest_request_form = array(
			'post_author'    => $admin_id,
			'post_name'      => 'guest-track-order-form',
			'post_title'     => __( 'Track Your Order', 'woocommerce-order-tracker' ),
			'post_type'      => 'page',
			'post_status'    => 'publish',

		);

		$page_id = wp_insert_post( $mwb_tyo_guest_request_form );

		if ( $page_id ) {
			$mwb_tyo_pages['pages']['mwb_guest_track_order_page'] = $page_id;
		}

		$mwb_tyo_fed_ex_tracking = array(
			'post-author'   => $admin_id,
			'post_name'     => 'track-fedEx-order',
			'post_title'    => __( 'Shipment Tracking', 'woocommerce-order-tracker' ),
			'post_type'     => 'page',
			'post_status'   => 'publish',

		);

		$page_id = wp_insert_post( $mwb_tyo_fed_ex_tracking );
		if ( $page_id ) {
			$mwb_tyo_pages['pages']['mwb_fedex_track_order'] = $page_id;
		}

		update_option( 'mwb_tyo_tracking_page', $mwb_tyo_pages );

	}

	register_activation_hook( __FILE__, 'mwb_tyo_add_pages' );

	/**
	 * This function is to remove track order page
	 *
	 * @link http://www.wpswings.com/
	 */
	function mwb_tyo_remove_pages() {
		wp_clear_scheduled_hook( 'mwb_tyo_daily_notification' );
		delete_option( 'mwb_tyo_warning_notification_message' );
		delete_option( 'mwb_tyo_warning_notification' );

		$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );

		if ( isset( $mwb_tyo_pages['pages'] ) && ! empty( $mwb_tyo_pages['pages'] ) ) {
			$pages = $mwb_tyo_pages['pages'];
			foreach ( $pages as $page_id ) {
				wp_delete_post( $page_id, true );
			}
		}
		delete_option( 'mwb_tyo_tracking_page' );
	}

	register_deactivation_hook( __FILE__, 'mwb_tyo_remove_pages' );

		// replace get_post_meta with wps_order_tracker_get_meta_data
	function wps_order_tracker_get_meta_data( $id, $key, $v ) {
		if ( 'shop_order' === OrderUtil::get_order_type( $id ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS usage is enabled.
			$order    = wc_get_order( $id );
			if ( '_customer_user' == $key ) {
				$meta_val = $order->get_customer_id();
				return $meta_val;
			}
			$meta_val = $order->get_meta( $key );
			return $meta_val;
		} else {
			// Traditional CPT-based orders are in use.
			$meta_val = get_post_meta( $id, $key, $v );
			return $meta_val; 
		}
	}

	// replace update_post_meta with wps_order_tracker_update_meta_data
	function wps_order_tracker_update_meta_data( $id, $key, $value ) {
		if ( 'shop_order' === OrderUtil::get_order_type( $id ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS usage is enabled.
			$order = wc_get_order( $id );
			$order->update_meta_data( $key, $value );
			$order->save();
		} else {
			// Traditional CPT-based orders are in use.
			update_post_meta( $id, $key, $value );
		}
	}


	/**
	 * This function checks session is set or not
	 *
	 * @link http://www.wpswings.com/
	 */
	function mwb_tyo_set_session() {
		if ( !session_id() ) {
			
			session_start();
		}
		$value_check = isset( $_POST['track_order_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['track_order_nonce_name'] ) ) : '';
		wp_verify_nonce( $value_check, 'track_order_nonce' );
		if ( isset( $_POST['mwb_tyo_order_id_submit'] ) ? sanitize_text_field( wp_unslash( $_POST['mwb_tyo_order_id_submit'] ) ) : '' ) {
			$order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
			$order = wc_get_order( $order_id );
			$billing_email = $order->get_billing_email();
			$mwb_tyo_pages = get_option( 'mwb_tyo_tracking_page' );
			$page_id = $mwb_tyo_pages['pages']['mwb_track_order_page'];
			$track_order_url = get_permalink( $page_id );
			$order = wc_get_order( $order_id );
			if( ! empty( $order ) ) {

				if( 'yes' != get_option( 'mwb_tyo_enable_track_order_using_order_id', 'no' ) ) {
					$req_email = isset( $_POST['order_email'] ) ? sanitize_text_field( wp_unslash( $_POST['order_email'] ) ) : '';
					
					if ( ! empty($req_email ) && ! empty( $billing_email ) && $req_email == $billing_email ) {
						$_SESSION['mwb_tyo_email'] = $billing_email;
						$order = wc_get_order( $order_id );
						$url = $track_order_url . '?' . $order_id;
						wp_redirect( $url );
						exit();
					} else {
						$_SESSION['mwb_tyo_notification'] = __( 'OrderId or Email is Invalid', 'woocommerce-order-tracker' );
					}
				} else {
					$order = wc_get_order( $order_id );
					$url = $track_order_url . '?' . $order_id;
					wp_redirect( $url );
					exit();
				}
			} else{
				$_SESSION['mwb_tyo_notification'] = __( 'OrderId is Invalid', 'woocommerce-order-tracker' );
			}
		}
	}
	add_action( 'init', 'mwb_tyo_set_session' );

	/**
	 * This function is used to load language'.
	 *
	 * @link http://www.wpswings.com/
	 */
	function mwb_tyo_load_plugin_textdomain() {
		$domain = 'woocommerce-order-tracker';

		/**
		 * Add more setting.
		 *
		 * @since 1.0.0
		 */
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, MWB_TRACK_YOUR_ORDER_PATH . 'languages/' . $domain . '-' . $locale . '.mo' );
		$var = load_plugin_textdomain( $domain, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}
	add_action( 'plugins_loaded', 'mwb_tyo_load_plugin_textdomain' );

	/**
	 * Add setting on link page.
	 *
	 * @param array  $actions is for actions.
	 * @param string $plugin_file is a file path.
	 * @return array
	 */
	function mwb_tyo_admin_settings( $actions, $plugin_file ) {
		static $plugin;
		if ( ! isset( $plugin ) ) {

			$plugin = plugin_basename( __FILE__ );
		}
		if ( $plugin == $plugin_file ) {
			$settings = array(

				'settings' => '<a href="' . home_url( '/wp-admin/admin.php?page=wc-settings&tab=mwb_tyo_settings' ) . '">' . __( 'Settings', 'woocommerce-order-tracker' ) . '</a>',
			);
			$actions = array_merge( $settings, $actions );
		}
		return $actions;
	}

	// add link for settings.
	add_filter( 'plugin_action_links', 'mwb_tyo_admin_settings', 10, 5 );





} else {

	/**
	 * Show warning message if woocommerce is not install
	 *
	 * @name mwb_tyo_plugin_deactivate()
	 *  
	 * @link http://www.wpswings.com/
	 */
	function mwb_tyo_plugin_deactivation() {
		?>
	<div class="error notice is-dismissible">
		<p><?php esc_html_e( 'Woocommerce is not activated, Please activate Woocommerce first to install Woocommerce Track Order.', 'woocommerce-order-tracker' ); ?></p>
	</div>

		<?php
	}
	add_action( 'admin_init', 'mwb_tyo_plugin_deactivation' );


	/**
	 * Call Admin notices
	 *
	 * @name mwb_tyo_plugin_deactivate()
	 *  
	 * @link http://www.wpswings.com/
	 */
	function mwb_tyo_plugin_deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );

		/**
		 * Add more setting.
		 *
		 * @since 1.0.0
		*/
		do_action( 'woocommerce_product_options_stock_fields' );
		add_action( 'admin_notices', 'mwb_tyo_plugin_deactivate' );
	}
}

 $mwb_tyo_license_key = get_option( 'mwb_tyo_license_key', '' );

 define( 'MWB_TYO_LICENSE_KEY', $mwb_tyo_license_key );
 define( 'MWB_TYO_FILE', __FILE__ );
 $mwb_tyo_url_updtae = 'https://wpswings.com/pluginupdates/codecanyon/woocommerce-order-tracker/update.php';
 require_once( 'class-mwb-tyo-update.php' );


 add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );