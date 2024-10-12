<?php
/**
 * Custom order satus.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/admin
 *  
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

}

/**
 * Extending wp list table.
 */
class MWB_Custom_Order_Status extends WP_List_Table {

	/** Class constructor. */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Custom Order Creation', 'woocommerce-order-tracker' ), // singular name of the listed records.
				'plural'   => __( 'Custom Order Creation', 'woocommerce-order-tracker' ), // plural name of the listed records.
				'ajax'     => false, // does this table support ajax.
			)
		);
	}

	/**
	 * Retrieve feeds.
	 *
	 * @return string
	 */
	public function get_feeds() {

		$custom_order_status = array();
		$custom_order_image = array();

		$previous_status = get_option( 'mwb_tyo_new_custom_order_status', false );
		if ( is_array( $previous_status ) && ! empty( $previous_status ) ) {
			$custom_order_status = $previous_status;
		}

		return $custom_order_status;
	}

	/**
	 * Function for get count.
	 *
	 * @return integer
	 */
	public function get_count() {

		$custom_order_status = array();
		$previous_status = get_option( 'mwb_tyo_new_custom_order_status', false );
		if ( is_array( $previous_status ) && ! empty( $previous_status ) ) {
			$custom_order_status = $previous_status;
		}
		return count( $custom_order_status );
	}



	/** Text displayed when no customer data is available */
	public function no_items() {
		esc_html_e( 'No any Custom Order Created.', 'woocommerce-order-tracker' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item is the.
	 * @param string $column_name contains column.
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				return $item;
		}
	}


	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item is the item.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		foreach ( $item as $key => $value ) {

				return sprintf(
					'<input type="checkbox" name="mwb_mwb_custom_order[]" value="%s" />',
					$item[ $key ]
				);

		}
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data.
	 *
	 * @return string
	 */
	public function column_name( $item ) {
		foreach ( $item as $custom_order_key => $custom_order_status ) {
			$title = '<strong>' . $custom_order_status . '</strong>';
			$actions = array(
				'delete' => sprintf( '<a href="javascript:void(0);" data-action="%s" data-key="%s" class="mwb_delete_costom_order">' . __( 'Delete', 'woocommerce-order-tracker' ) . '</a>', 'delete', $custom_order_key ),
			);
			return $title . $this->row_actions( $actions );
		}
	}

	/**
	 * Method for order status image column
	 *
	 * @param array $item an array of DB data.
	 *
	 * @return string
	 */
	public function column_image( $item ) {
		$mwbimageurl = get_option( 'mwb_tyo_new_custom_order_image', false );
		foreach ( $item as $key => $value ) {
			$mwb_image = $mwbimageurl[ $key ];
			return sprintf(
				'<img src="%s" height="100px" width="100px"/>',
				$mwb_image
			);
		}
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />',
			'name'    => __( 'Custom Order Status Name', 'woocommerce-order-tracker' ),
			'image' => __( 'Custom Order Status Image', 'woocommerce-order-tracker' ),
		);
		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'woocommerce-order-tracker' ),
		);
		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$per_page = apply_filters( 'mwb_mwb_alter_custom_order_status_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers.
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		if ( ! $this->current_action() ) {
			if ( is_array( $_POST ) && ! empty( $_POST ) ) {
				$redirect_url = get_admin_url() . 'admin.php?page=wc-settings&tab=mwb_tyo_settings&section=custom_status';
				wp_redirect( $redirect_url );
			}
			$this->items = self::get_feeds();
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}

	}

	/**
	 * Function for render html.
	 *
	 * @return void
	 */
	public function renderHTML() {
		?>
		<div class="mwb_mwb_rows_wrap">
			<h3><?php esc_html_e( 'Create Custom Order Status', 'woocommerce-order-tracker' ); ?></h3>
			<input id="mwb_mwb_create_role_box" value="<?php esc_attr_e( 'Create Custom Order Status', 'woocommerce-order-tracker' ); ?>" class="button-primary" type="button">
		</div>
		<!-- messages :: start -->
		<div class="mwb_notices_order_tracker">
			
		</div>
		<!-- messages :: end -->
		<div id="mwb_mwb_create_box">
			<h3 align="center"><?php esc_html_e( 'Create New Custom Order Status', 'woocommerce-order-tracker' ); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<tr>
					<th>
						<label for="mwb__new_role_name"><?php esc_html_e( 'Custom Order Status Name', 'woocommerce-order-tracker' ); ?><label>
						</th>
						<td>
							<input type="text" name="mwb_mwb_create_order_name" pattern = '[A-Za-z0-9]' id="mwb_mwb_create_order_name" placeholder="<?php esc_attr_e( 'Type Custom Order Status Name Here', 'woocommerce-order-tracker' ); ?>">	
						</td>
					</tr>
					 <tr valign="top">
						<th scope="row" class="titledesc">
							<label for="mwb_tyo_other_setting_upload_logo"><?php esc_html_e( 'Upload Default Logo', 'woocommerce-order-tracker' ); ?></label>
						</th>
						<td class="forminp forminp-text">
							<?php
							$attribute_description = __( 'Upload the image which is used as logo for your custom order statuses.', 'woocommerce-order-tracker' );
							echo wp_kses_post( wc_help_tip( $attribute_description ) );

							?>
							<input type="text" readonly class="mwb_tyo_other_setting_upload_logo_value" id="mwb_tyo_other_setting_upload_logo" name="mwb_tyo_other_setting_upload_logo" value=""/>
							<input class="mwb_tyo_other_setting_upload_logo button"  type="button" value=<?php esc_attr_e( 'Upload Logo', 'woocommerce-order-tracker' ); ?> />
							
							<p id="mwb_tyo_other_setting_remove_logo">
								<span class="mwb_tyo_other_setting_remove_logo">
									<img src="" width="50px" height="50px" id="mwb_tyo_other_setting_upload_image">
								</span>
							</p>
						</td>
					</tr>
				</table>
				<p class="save_section">
					<input type="button" id="mwb_mwb_create_custom_order_status" value="<?php esc_attr_e( 'Create Order Status', 'woocommerce-order-tracker' ); ?>" class="button-primary">	
					<img id="mwb_mwb_send_loading" src="<?php echo esc_attr( MWB_TRACK_YOUR_ORDER_URL ) . 'assets/images/clock-loading.gif'; ?>">
				</p>
			</div>
			<?php
			$this->display();
	}

	/**
	 * Function for bulk action.
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		
		if ( 'bulk-delete' === $this->current_action() ) {
			
			$mwb_tyo_old_selected_statuses = get_option( 'mwb_tyo_new_settings_custom_statuses_for_order_tracking', false );

			$mwb_data = isset( $_POST['mwb_mwb_custom_order'] ) ? map_deep( wp_unslash( $_POST['mwb_mwb_custom_order'] ) , 'sanitize_text_field' ) : array();
			$mwb_data_exist_db = get_option( 'mwb_tyo_new_custom_order_status', array() );
			if ( is_array( $mwb_data ) && ! empty( $mwb_data ) ) {

				if ( is_array( $mwb_data_exist_db ) && ! empty( $mwb_data_exist_db ) ) {
					foreach ( $mwb_data_exist_db as $key1 => $value1 ) {
						foreach ( $value1 as $mwb_order_key => $mwb_order_value ) {
							foreach ( $mwb_data as $key2 => $value2 ) {
								if ( $mwb_order_value == $value2 ) {
									unset( $mwb_data_exist_db[ $key1 ] );
								}
								if ( is_array( $mwb_tyo_old_selected_statuses ) && ! empty( $mwb_tyo_old_selected_statuses ) ) {
									foreach ( $mwb_tyo_old_selected_statuses as $remove_key => $remove_value ) {
										if ( substr( $remove_value, 3 ) == $value2 ) {
											unset( $mwb_tyo_old_selected_statuses[ $remove_key ] );
										}
									}
								}
							}
						}
					}
				}
				update_option( 'mwb_tyo_new_settings_custom_statuses_for_order_tracking', $mwb_tyo_old_selected_statuses );

				update_option( 'mwb_tyo_new_custom_order_status', $mwb_data_exist_db );

				$redirect_url = get_admin_url() . 'admin.php?page=wc-settings&tab=mwb_tyo_settings&section=custom_status';
				wp_redirect( $redirect_url );
			} else {
				$redirect_url = get_admin_url() . 'admin.php?page=wc-settings&tab=mwb_tyo_settings&section=custom_status';
				wp_redirect( $redirect_url );
			}
		} else {
			$redirect_url = get_admin_url() . 'admin.php?page=wc-settings&tab=mwb_tyo_settings&section=custom_status';
			wp_redirect( $redirect_url );
		}
	}
}
	$mwb_mwb_user_role_table_list = new MWB_Custom_Order_Status();
	$mwb_mwb_user_role_table_list->prepare_items();


