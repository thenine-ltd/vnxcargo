<?php
/**
 * The update of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 * namespace woocommerce_event_tickets_manager_public.
 *
 * @package    Woocommerce_Order_Tracker
 * @subpackage Woocommerce_Order_Tracker/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'MWB_TYO_Update' ) ) {

	/**
	 * Class for update.
	 */
	class MWB_TYO_Update {

		/**
		 * Initialize the class and set its properties.
		 */
		public function __construct() {

			register_activation_hook( MWB_TYO_FILE, array( $this, 'mwb_tyo_check_activation' ) );
			add_action( 'mwb_tyo_check_event', array( $this, 'mwb_tyo_check_update' ) );
			add_filter( 'http_request_args', array( $this, 'mwb_tyo_updates_exclude' ), 5, 2 );
			register_deactivation_hook( MWB_TYO_FILE, array( $this, 'mwb_tyo_check_deactivation' ) );
			add_action( 'install_plugins_pre_plugin-information', array( $this, 'mwb_plugin_details' ) );
		}

		/**
		 * This is for plugin details.
		 *
		 * @return void
		 */
		public function mwb_plugin_details() {
			global $tab;

			if ( isset( $_REQUEST['plugin'] ) ) {

				if ( 'plugin-information' == $tab && 'woocommerce-order-tracker' == $_REQUEST['plugin'] ) {

					$url = 'https://wpswings.com/pluginupdates/codecanyon/woocommerce-order-tracker/update.php';

					$postdata = array(
						'action' => 'check_update',
						'license_code' => MWB_TYO_LICENSE_KEY,
					);

					$args = array(
						'method' => 'POST',
						'body' => $postdata,
					);

					$data = wp_remote_post( $url, $args );
					if ( is_wp_error( $data ) ) {
						return;
					}

					if ( isset( $data['body'] ) ) {
						$all_data = json_decode( $data['body'], true );

						if ( is_array( $all_data ) && ! empty( $all_data ) ) {
							$this->create_html_data( $all_data );
							wp_die();
						}
					}
				}
			}
		}

		/**
		 * Function for check deactivations.
		 *
		 * @return void
		 */
		public function mwb_tyo_check_deactivation() {
			wp_clear_scheduled_hook( 'mwb_tyo_check_event' );
		}

		/**
		 * Function for checking activations.
		 *
		 * @return void
		 */
		public function mwb_tyo_check_activation() {
			wp_schedule_event( time(), 'daily', 'mwb_tyo_check_event' );
		}

		/**
		 * Function for check update.
		 *
		 * @return bool
		 */
		public function mwb_tyo_check_update() {
			global $wp_version;
			global $mwb_tyo_url_updtae;
			$plugin_folder = plugin_basename( dirname( MWB_TYO_FILE ) );
			$plugin_file = basename( ( MWB_TYO_FILE ) );
			if ( defined( 'WP_INSTALLING' ) ) {
				return false;
			}
			$postdata = array(
				'action' => 'check_update',
				'purchase_code' => MWB_TYO_LICENSE_KEY,
			);
			$args = array(
				'method' => 'POST',
				'body' => $postdata,
			);
			
			$response = wp_remote_post( $mwb_tyo_url_updtae, $args );
			
			list($version, $url) = explode( '~', $response['body'] );

			if ( $this->mwb_plugin_get( 'Version' ) >= $version ) {
				return false;
			}

			$plugin_transient = get_site_transient( 'update_plugins' );
			$a = array(
				'slug' => $plugin_folder,
				'new_version' => $version,
				'url' => $this->mwb_plugin_get( 'AuthorURI' ),
				'package' => $url,
			);
			$o = (object) $a;
			$plugin_transient->response[ $plugin_folder . '/' . $plugin_file ] = $o;
			set_site_transient( 'update_plugins', $plugin_transient );
		}

		/**
		 * Function for update exclude.
		 *
		 * @param array  $r is a array of string.
		 * @param string $url is for check update.
		 * @return array
		 */
		public function mwb_tyo_updates_exclude( $r, $url ) {
			if ( 0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check' ) ) {
				return $r;
			}
			if ( ! empty( $r['body']['plugins'] ) ) {
				$plugins = $r['body']['plugins'];
				if ( ! empty( $plugins->plugins ) ) {
					unset( $plugins->plugins[ plugin_basename( __FILE__ ) ] );
				}
				if ( ! empty( $plugins->active ) ) {
					unset( $plugins->active[ array_search( plugin_basename( __FILE__ ), $plugins->active ) ] );
				}
				$r['body']['plugins'] = serialize( $plugins );
			}
			return $r;
		}

		/**
		 * Function to plugin get.
		 *
		 * @param string $i is the string used.
		 * @return array
		 */
		public function mwb_plugin_get( $i ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$plugin_folder = get_plugins( '/' . plugin_basename( dirname( MWB_TYO_FILE ) ) );
			$plugin_file = basename( ( MWB_TYO_FILE ) );
			return $plugin_folder[ $plugin_file ][ $i ];
		}

		/**
		 * Function for creating html.
		 *
		 * @param array $all_data is a array of data.
		 * @return void
		 */
		public function create_html_data( $all_data ) {
			?>
			<style>
				#TB_window{
					top : 4% !important;
				}
				.mwb_plugin_banner > img {
					height: 167px;
					width: 100%;
					border: 1px solid;
					border-radius: 7px;
				}
				.mwb_plugin_description > h4 {
					background-color: #3779B5;
					padding: 5px;
					color: #ffffff;
					border-radius: 5px;
				}
				.mwb_plugin_requirement > h4 {
					background-color: #3779B5;
					padding: 5px;
					color: #ffffff;
					border-radius: 5px;
				}
			</style>
			<div class="mwb_plugin_details_wrapper">
				<div class="mwb_plugin_banner">
					<img src="<?php echo esc_attr( $all_data['banners']['low'] ); ?>">	
				</div>
				<div class="mwb_plugin_description">
					<h4><?php esc_html_e( 'Plugin Description', 'woocommerce-order-tracker' ); ?></h4>
					<span><?php echo esc_html( $all_data['sections']['description'] ); ?></span>
				</div>
				<div class="mwb_plugin_requirement">
					<h4><?php esc_html_e( 'Plugin Change Log', 'woocommerce-order-tracker' ); ?></h4>
					<span><?php echo esc_html( $all_data['sections']['changelog'] ); ?></span>
				</div> 
			</div>
			<?php
		}

	}
	new MWB_TYO_Update();
}
?>
