<?php

	add_action( 'wp_enqueue_scripts', 'ohio_child_local_enqueue_parent_styles' );

	function ohio_child_local_enqueue_parent_styles() {
		wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	}


	function custom_pos_url($url)
{
   $url = site_url( '/pos/', 'https' );
   return $url;
}
add_filter('op_pos_url','custom_pos_url',10,1);

/*
function op_custom_customer_field_google_address_data($session_response_data){
   
   
    $name_field =  array(
        'code' => 'name',
        'type' => 'text',
        'label' => __('Name','openpos'),
        'options'=> [],
        'placeholder' => __('Name','openpos'),
        'description' => '',
        'default' => '',
        'allow_shipping' => 'yes',
        'required' => 'no',
        'searchable' => 'no'
    );
    
      $phone_field = array(
        'code' => 'phone',
        'type' => 'text',
        'label' => __('Phone','openpos'),
        'options'=> array(),
        'placeholder' => __('Phone','openpos'),
        'description' => '',
        'default' => '',
        'allow_shipping' => 'yes',
        'required' => 'yes',
        'searchable' => 'yes'
      );
       
      $email_field = array(
        'code' => 'email',
        'type' => 'email',
        'label' => __('Email','openpos'),
        'options'=> array(),
        'placeholder' => __('Email','openpos'),
        'description' => '',
        'default' => '',
        'allow_shipping' => 'no',
        'required' => 'no',
        'searchable' => 'yes',
        'editable' => 'no'
      );
       $address_field = array(
         'code' => 'address',
         'type' => 'text',
         'label' => __('Address','openpos'),
         'options'=> array(),
         'placeholder' => __('Adress','openpos'),
         'description' => '',
         'default' => '',
         'allow_shipping'=> 'yes',
         'required' => 'no',
         'searchable' => 'no'
       );
        
      
    $addition_checkout_fields = array();
    
    $addition_checkout_fields[] = $address_field;
    
    $session_response_data['setting']['openpos_customer_fields'] = array($name_field,$phone_field);
    $session_response_data['setting']['openpos_customer_addition_fields'] = $addition_checkout_fields;

    return $session_response_data;
}

add_filter('op_get_login_cashdrawer_data','op_custom_customer_field_google_address_data',10,1);
*/
function custom_pos_allow_receipt($session_response_data){
    $session_response_data['allow_receipt'] =  'yes';
    return $session_response_data;
}
add_filter('op_get_login_cashdrawer_data','custom_pos_allow_receipt',11,1);

function custom_pos_cart_subtotal_incl_tax($session_response_data){
    
    $session_response_data['setting']['pos_cart_subtotal_incl_tax'] = 'yes';
   return $session_response_data;

}
add_filter('op_get_login_cashdrawer_data','custom_pos_cart_subtotal_incl_tax',10,1);


function rf_product_thumbnail_size( $size ) {
    global $product;

    $size = 'full';
    return $size;
}
add_filter( 'single_product_archive_thumbnail_size', 'rf_product_thumbnail_size' );
add_filter( 'subcategory_archive_thumbnail_size', 'rf_product_thumbnail_size' );
add_filter( 'woocommerce_gallery_thumbnail_size', 'rf_product_thumbnail_size' );
add_filter( 'woocommerce_gallery_image_size', 'rf_product_thumbnail_size' );

add_action( 'wp_footer', 'cxc_cart_refresh_update_qty' ); 
function cxc_cart_refresh_update_qty() { 
	if ( is_cart() || ( is_cart() && is_checkout() ) ) {
		?>
		<script>
			jQuery( function( $ ) {
				let timeout;
				jQuery('.woocommerce').on('change', 'input.qty', function(){
					if ( timeout !== undefined ) {
						clearTimeout( timeout );
					}
					timeout = setTimeout(function() {
						jQuery("[name='update_cart']").trigger("click"); // trigger cart update
					}, 1000 ); // 1 second delay, half a second (500) seems comfortable too
				});
			} );
		</script>
		<?php
	}
}
/*
add_filter('acf/settings/remove_wp_meta_box', '__return_true');
*/

add_action( 'woocommerce_checkout_create_order', 'add_order_total_weight_metadata' );
function add_order_total_weight_metadata( $order ) {
    $order->add_meta_data('_cart_weight', intval( WC()->cart->get_cart_contents_weight() ) );
}

add_filter( 'manage_edit-shop_order_columns', 'woo_order_weight_column' );
function woo_order_weight_column( $columns ) {
  $columns['total_weight'] = __( 'Weight', 'woocommerce' );
    return $columns;
}

add_action( 'manage_shop_order_posts_custom_column', 'woo_custom_order_weight_column', 2 );
function woo_custom_order_weight_column( $column ) {
    if ( $column == 'total_weight' ) {
        global $the_order;

        // Get total weight metadata value
        $total_weight = $the_order->get_meta('_cart_weight');

        if ( $total_weight > 0 ) {
            echo wc_format_weight( $total_weight );
        } else {
            _e('N/A', 'woocommerce');
        }
    }
}

add_action( 'woocommerce_after_order_itemmeta', 'zlp_show_weight_admin_order_item_meta', 10, 3 );
function zlp_show_weight_admin_order_item_meta( $item_id, $item, $product ) {
	
	// IF PRODUCT OR IT'S VARIATION HAS WEIGHT
	if( $product->weight ){ ?>
		<table cellspacing="0" class="display_meta">
			<tbody>
				<tr>
					<th><?php _e( 'Total Weight:' ); ?></th>
					<td><p><?php echo ($item->get_quantity())*($product->get_weight())." ".get_option('woocommerce_weight_unit'); ?></p></td>
				</tr>
			</tbody>
		</table>
	<?php }
} 

function devvn_oft_custom_get_price_html( $price, $product ) {
    if ( !is_admin() && !$product->is_in_stock()) {
       $price = '<a class="tn-ofs-btn" href="https://messenger.com/t/103357145792413">' . __( 'Liên hệ', 'woocommerce' ) . '</a>';
    }
    return $price;
}
add_filter( 'woocommerce_get_price_html', 'devvn_oft_custom_get_price_html', 99, 2 );

add_filter( 'woocommerce_loop_add_to_cart_link', 'my_out_of_stock_button' );

function my_out_of_stock_button( $args ){
  global $product;
  if( $product && !$product->is_in_stock() ){
    return '<a href="https://messenger.com/t/103357145792413">Liên Hệ</a>';
  }
  return $args;
}