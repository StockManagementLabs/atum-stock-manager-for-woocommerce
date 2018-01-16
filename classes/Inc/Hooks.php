<?php
/**
 * @package         Atum
 * @subpackage      Inc
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.3.8.2
 *
 * Global ATUM hooks
 */

namespace Atum\Inc;


final class Hooks {

	/**
	 * Register the admin-side hooks
	 *
	 * @since 1.3.8.2
	 */
	public static function admin_hooks() {

		// Check if ATUM has the "Manage Stock" option enabled
		if ( Helpers::is_atum_managing_stock() ) {
			add_action( 'init', array( __CLASS__, 'atum_manage_stock_hooks' ) );
		}
		else {
			// Add the WC stock management option to grouped products
			add_action( 'init', array( __CLASS__, 'wc_manage_stock_hooks' ) );
		}

		// Add the purchase price to WC products
		add_action( 'woocommerce_product_options_pricing', array(__CLASS__, 'add_purchase_price_meta') );
		add_action( 'woocommerce_variation_options_pricing', array(__CLASS__, 'add_purchase_price_meta'), 10, 3 );

		// Save the product purchase price meta
		add_action( 'save_post_product', array(__CLASS__, 'save_purchase_price') );
		add_action( 'woocommerce_update_product_variation', array(__CLASS__, 'save_purchase_price') );

		// Show the right stock status on WC products list when ATUM is managing the stock
		add_filter( 'woocommerce_admin_stock_html', array(__CLASS__, 'set_wc_products_list_stock_status'), 10, 2 );

		// Add purchase price to WPML custom prices
		add_filter( 'wcml_custom_prices_fields', array(__CLASS__, 'wpml_add_purchase_price_to_custom_prices') );
		add_filter( 'wcml_custom_prices_fields_labels', array(__CLASS__, 'wpml_add_purchase_price_to_custom_price_labels') );
		add_filter( 'wcml_custom_prices_strings', array(__CLASS__, 'wpml_add_purchase_price_to_custom_price_labels') );
		add_filter( 'wcml_update_custom_prices_values', array(__CLASS__, 'wpml_sanitize_purchase_price_in_custom_prices'), 10, 3 );
		add_action( 'wcml_after_save_custom_prices', array(__CLASS__, 'wpml_save_purchase_price_in_custom_prices'), 10, 4 );

		// Add the location column to the items table in WC orders
		add_action( 'woocommerce_admin_order_item_headers', array(__CLASS__, 'wc_order_add_location_column_header') );
		add_action( 'woocommerce_admin_order_item_values', array(__CLASS__, 'wc_order_add_location_column_value'), 10, 3 );

	}

	/**
	 * Register the global hooks
	 *
	 * @since 1.3.8.2
	 */
	public static function global_hooks() {

		// Save the date when any product goes out of stock
		add_action( 'woocommerce_product_set_stock' , array(__CLASS__, 'record_out_of_stock_date'), 20 );

		// Delete the views' transients after changing the stock of any product
		add_action( 'woocommerce_product_set_stock' , array(__CLASS__, 'delete_transients') );
		add_action( 'woocommerce_variation_set_stock' , array(__CLASS__, 'delete_transients') );

	}

	/**
	 * Add Hooks when Atum "Manage Stock" option is enabled
	 *
	 * @since 0.1.0
	 */
	public static function atum_manage_stock_hooks() {

		// Disable WooCommerce manage stock option for individual products
		add_action( 'woocommerce_product_options_stock', array( __CLASS__, 'disable_manage_stock' ) );
		add_action( 'woocommerce_product_options_stock_fields', array( __CLASS__, 'add_manage_stock' ) );

		// Disable WooCommerce manage stock option for product variations
		add_action( 'woocommerce_ajax_admin_get_variations_args', array(__CLASS__, 'disable_variation_manage_stock'));

		// Set to yes the WooCommerce _manage_stock meta key for all the supported products
		add_action( 'update_post_metadata', array( __CLASS__, 'save_manage_stock' ), 10, 5 );

	}

	/**
	 * Add Hooks when WooCommerce is managing the individual products' stock
	 *
	 * @since 1.1.1
	 */
	public static function wc_manage_stock_hooks() {

		// Add the WooCommerce manage stock option to grouped products
		add_action( 'woocommerce_product_options_stock_fields', array( __CLASS__, 'add_manage_stock' ) );

		// Allow saving the WooCommerce _manage_stock meta key for grouped products
		add_action( 'update_post_metadata', array( __CLASS__, 'save_manage_stock' ), 10, 5 );

	}

	/**
	 * Disable the WooCommerce "Manage Stock" checkbox for simple products
	 *
	 * @since 0.1.0
	 */
	public static function disable_manage_stock() {

		// The external products don't have stock and the grouped depends on its own products' stock
		$product_type = wp_get_post_terms( get_the_ID(), 'product_type', array('fields' => 'names') );

		if ( ! is_wp_error($product_type) && ! in_array('external', $product_type) ) : ?>
			<script type="text/javascript">
				(function ($) {
					var $manageStockField = $('._manage_stock_field');
					$manageStockField.find('.checkbox').prop({'checked': true, 'readonly': true}).css('pointer-events', 'none')
						.siblings('.description').html('<strong><sup>**</sup><?php _e('The stock is currently managed by ATUM plugin', ATUM_TEXT_DOMAIN) ?><sup>**</sup></strong>');

					$manageStockField.children().click(function(e) {
						e.stopImmediatePropagation();
						e.preventDefault();
					});
				})(jQuery);
			</script>
		<?php endif;

	}

	/**
	 * Add the WooCommerce's stock management checkbox to Grouped and External products
	 *
	 * @since 1.1.1
	 */
	public static function add_manage_stock () {

		if ( get_post_type() != 'product' ) {
			return;
		}

		$product = wc_get_product();

		// Show the "Manage Stock" checkbox on Grouped products and hide the other stock fields
		if ( $product && is_a($product, '\\WC_Product') ) : ?>
			<script type="text/javascript">
				var $backOrders = jQuery('._backorders_field');
				jQuery('._manage_stock_field').addClass('show_if_grouped show_if_product-part show_if_raw-material');

				<?php // NOTE: The "wp-menu-arrow" is a WP built-in class that adds "display: none!important" so doesn't conflict with WC JS ?>
				jQuery('#product-type').change(function() {
					var productType = jQuery(this).val();
					if (productType === 'grouped' || productType === 'external') {
						$backOrders.addClass('wp-menu-arrow');
					}
					else {
						$backOrders.removeClass('wp-menu-arrow');
					}
				});

				<?php if ( in_array($product->get_type(), ['grouped', 'external'] ) ): ?>
				$backOrders.addClass('wp-menu-arrow');
				<?php endif; ?>
			</script>
		<?php endif;

	}

	/**
	 * Disable the WooCommerce "Manage Stock" checkbox for variation products
	 *
	 * @since 1.1.1
	 *
	 * @param array $args
	 * @return array
	 */
	public static function disable_variation_manage_stock ($args) {

		?>
		<script type="text/javascript">
			(function ($) {
				$('.variable_manage_stock').each(function() {
					$(this).prop({'checked': true, 'readonly': true})
						.siblings('.woocommerce-help-tip')
						.attr('data-tip', '<?php _e('The stock is currently managed by ATUM plugin', ATUM_TEXT_DOMAIN) ?>');

					$(this).click(function(e) {
						e.stopImmediatePropagation();
						e.preventDefault();
					});
				});
			})(jQuery);
		</script>
		<?php

		return $args;
	}

	/**
	 * Fires immediately after adding/updating the manage stock metadata
	 *
	 * @since 0.1.0
	 *
	 * @param mixed  $check         ID of updated metadata entry
	 * @param int    $product_id    The product ID
	 * @param string $meta_key      Meta key
	 * @param mixed  $meta_value    Meta value
	 * @param mixed  $prev_value    Previous valus for this meta field
	 *
	 * @return NULL|bool            NULL to continue saving the meta key ($check is always NULL) or any other value to not continue
	 */
	public static function save_manage_stock( $check, $product_id, $meta_key, $meta_value, $prev_value ) {

		if ( $meta_key == '_manage_stock' && $meta_value == 'no' ) {
			$product = wc_get_product( $product_id );

			if ( $product && in_array( $product->get_type(), Globals::get_product_types() ) ) {
				remove_action( 'update_post_metadata', array(__CLASS__, 'save_manage_stock') );

				if ( Helpers::is_atum_managing_stock() ) {
					$manage_stock = 'yes'; // Always enabled
					Helpers::delete_transients();
				}
				else {
					$manage_stock = ( isset($_POST['_manage_stock']) && $_POST['_manage_stock'] == 'yes' ) ? 'yes' : 'no';
				}

				update_post_meta( $product_id, '_manage_stock', $manage_stock );

				// Do not continue saving this meta key
				return TRUE;
			}
		}

		return $check;

	}

	/**
	 * Add the purchase price field to WC's product data meta box
	 *
	 * @since 1.2.0
	 *
	 * @param int      $loop             Only for variations. The loop item number
	 * @param array    $variation_data   Only for variations. The variation item data
	 * @param \WP_Post $variation        Only for variations. The variation product
	 */
	public static function add_purchase_price_meta ($loop = NULL, $variation_data = array(), $variation = NULL) {

		if ( ! current_user_can( ATUM_PREFIX . 'edit_purchase_price') ) {
			return;
		}

		$field_title = __( 'Purchase price', ATUM_TEXT_DOMAIN ) . ' (' . get_woocommerce_currency_symbol() . ')';

		if ( empty($variation) ) {

			woocommerce_wp_text_input( array(
				'id'        => '_purchase_price',
				'label'     => $field_title,
				'data_type' => 'price'
			) );

		}
		else {

			woocommerce_wp_text_input( array(
				'id'            => "variation_purchase_price_{$loop}",
				'name'          => "variation_purchase_price[$loop]",
				'value'         => get_post_meta($variation->ID, '_purchase_price', TRUE),
				'label'         => $field_title,
				'wrapper_class' => 'form-row form-row-first',
				'data_type'     => 'price'
			) );

		}

	}

	/**
	 * Save the purchase price meta on product post savings
	 *
	 * @since 1.2.0
	 *
	 * @param int $post_id
	 */
	public static function save_purchase_price ($post_id) {

		$purchase_price = '';

		// Product variations
		if ( isset($_POST['variation_purchase_price']) ) {
			$purchase_price = (string) isset( $_POST['variation_purchase_price'] ) ? wc_clean( reset($_POST['variation_purchase_price']) ) : '';
			$purchase_price = ('' === $purchase_price) ? '' : wc_format_decimal( $purchase_price );
			update_post_meta( $post_id, '_purchase_price', $purchase_price );
		}
		else {

			$product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );

			if ( in_array( $product_type, Globals::get_inheritable_product_types() ) ) {
				// Inheritable products have no prices
				update_post_meta( $post_id, '_purchase_price', $purchase_price );
			}
			else {
				$purchase_price = (string) isset( $_POST['_purchase_price'] ) ? wc_clean( $_POST['_purchase_price'] ) : '';
				$purchase_price = ('' === $purchase_price) ? '' : wc_format_decimal( $purchase_price );
				update_post_meta( $post_id, '_purchase_price', $purchase_price);
			}

		}

		// Add WPML compatibility
		if (class_exists('\woocommerce_wpml')) {

			global $sitepress;
			$wpml = \woocommerce_wpml::instance();

			$post_type = get_post_type( $post_id );

			$product_translations = $sitepress->get_element_translations( $sitepress->get_element_trid($post_id, "post_{$post_type}"), "post_{$post_type}" );
			foreach($product_translations as $translation){

				if( $translation->element_id !==  $post_id){
					update_post_meta( $translation->element_id, '_purchase_price', $purchase_price);
				}

			}

		}

	}

	/**
	 * Sets the stock status in WooCommerce products' list when ATUM is managing the stock
	 *
	 * @since 1.2.6
	 *
	 * @param string      $stock_html   The HTML markup for the stock status
	 * @param \WC_Product $the_product  The product that is currently checked
	 *
	 * @return string
	 */
	public static function set_wc_products_list_stock_status($stock_html, $the_product) {

		if (
			Helpers::is_atum_managing_stock() &&
			Helpers::get_option('show_variations_stock', 'yes') == 'yes' &&
			in_array( $the_product->get_type(), ['variable', 'variable-subscription'] )
		) {

			// WC Subscriptions compatibility
			if ( class_exists('\WC_Subscriptions') && $the_product->get_type() == 'variable-subscription') {
				$variable_product = new \WC_Product_Variable_Subscription( $the_product->get_id() );
			}
			else {
				$variable_product = new \WC_Product_Variable( $the_product->get_id() );
			}

			// Get the variations within the variable
			$variations = $variable_product->get_children();
			$stock_status = __('Out of stock', ATUM_TEXT_DOMAIN);
			$stocks_list = array();

			if ( ! empty($variations) ) {

				foreach ($variations as $variation_id) {
					$variation_product = wc_get_product($variation_id);
					$variation_stock = $variation_product->get_stock_quantity();
					$stocks_list[] = $variation_stock;

					if ($variation_stock > 0) {
						$stock_status = __('In stock', ATUM_TEXT_DOMAIN);
					}
				}

			}

			if ( empty($stocks_list) ) {
				$stock_html = '<mark class="outofstock">' . $stock_status . '</mark> (0)';
			}
			else {
				$class = ( $stock_status == __('Out of stock', ATUM_TEXT_DOMAIN)  ) ? 'outofstock' : 'instock';
				$stock_html = '<mark class="' . $class . '">' . $stock_status . '</mark> (' . implode( ', ', array_map('intval', $stocks_list) ) . ')';
			}

		}

		return $stock_html;

	}

	/**
	 * Add purchase price to WPML's custom price fields
	 *
	 * @since 1.3.0
	 *
	 * @param array   $prices      Custom prices fields
	 * @param integer $product_id  The product ID
	 *
	 * @return array
	 */
	public static function wpml_add_purchase_price_to_custom_prices( $prices, $product_id ) {

		$prices[] = '_purchase_price';
		return $prices;
	}

	/**
	 * Add purchase price to WPML's custom price fields labels
	 *
	 * @since 1.3.0
	 *
	 * @param array   $labels       Custom prices fields labels
	 * @param integer $product_id   The product ID
	 *
	 * @return array
	 */
	public static function wpml_add_purchase_price_to_custom_price_labels( $labels, $product_id ) {

		$labels['_purchase_price'] = __( 'Purchase Price', ATUM_TEXT_DOMAIN );
		return $labels;
	}

	/**
	 * Sanitize WPML's purchase prices
	 *
	 * @since 1.3.0
	 *
	 * @param array  $prices
	 * @param string $code
	 * @param bool   $variation_id
	 *
	 * @return array
	 */
	public static function wpml_sanitize_purchase_price_in_custom_prices( $prices, $code, $variation_id = false ) {

		if ($variation_id) {
			$prices['_purchase_price'] = ( ! empty( $_POST['_custom_variation_purchase_price'][$code][$variation_id]) ) ? wc_format_decimal( $_POST['_custom_variation_purchase_price'][$code][$variation_id] ) : '';
		}
		else {
			$prices['_purchase_price'] = ( ! empty( $_POST['_custom_purchase_price'][$code]) )? wc_format_decimal( $_POST['_custom_purchase_price'][$code] ) : '';
		}

		return $prices;
	}


	/**
	 * Save WPML's purchase price when custom prices are enabled
	 *
	 * @since 1.3.0
	 *
	 * @param int    $post_id
	 * @param float  $product_price
	 * @param array  $custom_prices
	 * @param string $code
	 */
	public static function wpml_save_purchase_price_in_custom_prices( $post_id, $product_price, $custom_prices, $code ) {

		if ( isset( $custom_prices[ '_purchase_price'] ) ) {
			update_post_meta( $post_id, "_purchase_price_{$code}", $custom_prices['_purchase_price'] );
		}
	}

	/**
	 * Add the location to the items table in WC orders
	 *
	 * @since 1.3.3
	 *
	 * @param \WC_Order $wc_order
	 */
	public static function wc_order_add_location_column_header($wc_order) {
		?><th class="item_location sortable" data-sort="string-ins"><?php _e( 'Location', ATUM_TEXT_DOMAIN ); ?></th><?php
	}

	/**
	 * Add the location to the items table in WC orders
	 *
	 * @since 1.3.3
	 *
	 * @param \WC_Product    $product
	 * @param \WC_Order_Item $item
	 * @param int            $item_id
	 */
	public static function wc_order_add_location_column_value($product, $item, $item_id) {

		if ($product) {
			$product_id = ( $product->get_type() == 'variation' ) ? $product->get_parent_id() : $product->get_id();
			$locations  = wc_get_product_terms( $product_id, Globals::PRODUCT_LOCATION_TAXONOMY, array( 'fields' => 'names' ) );
			$locations_list = ( ! empty( $locations ) ) ? implode( ', ', $locations ) : '&ndash;';
		}

		?>
		<td class="item_location"<?php if ($product) echo ' data-sort-value="' . $locations_list . '"' ?>>
			<?php if ($product): ?>
				<div class="view"><?php echo $locations_list ?></div>
			<?php else: ?>
				&nbsp;
			<?php endif; ?>
		</td>
		<?php
	}


	/**
	 * Add/Remove the "Out of stock" date when WooCommerce updates the stock of a product
	 *
	 * @since 0.1.3
	 *
	 * @param \WC_Product $product    The product being changed
	 */
	public static function record_out_of_stock_date ($product) {

		if ( in_array($product->get_type(), Globals::get_product_types()) ) {

			$current_stock = $product->get_stock_quantity();
			$out_of_stock_date_key = Globals::get_out_of_stock_date_key();
			$product_id = $product->get_id();

			if (!$current_stock) {
				update_post_meta( $product_id, $out_of_stock_date_key, Helpers::date_format( current_time('timestamp'), TRUE ) );
				Helpers::delete_transients();
			}
			elseif ( get_post_meta( $product_id, $out_of_stock_date_key, TRUE ) ) {
				// Meta key not needed anymore for this product
				delete_post_meta( $product_id, $out_of_stock_date_key );
				Helpers::delete_transients();
			}

		}

	}

	/**
	 * Delete the ATUM transients after the product stock changes
	 *
	 * @since 0.1.5
	 *
	 * @param \WC_Product $product   The product
	 */
	public static function delete_transients($product) {
		Helpers::delete_transients();
	}

	/**
	 * Set the stock decimals
	 *
	 * @since 1.3.8.2
	 */
	public static function stock_decimals () {

		Globals::set_stock_decimals( Helpers::get_option('stock_quantity_decimals', 0) );

		// Maybe allow decimals for WC products' stock quantity
		if (Globals::get_stock_decimals() > 0) {

			// Add min value to the quantity field (WC default = 1)
			add_filter( 'woocommerce_quantity_input_min', array( __CLASS__, 'stock_quantity_input_atts' ), 10, 2 );

			// Add step value to the quantity field (WC default = 1)
			add_filter( 'woocommerce_quantity_input_step', array( __CLASS__, 'stock_quantity_input_atts' ), 10, 2 );

			// Removes the WooCommerce filter, that is validating the quantity to be an int
			remove_filter( 'woocommerce_stock_amount', 'intval' );

			// Replace the above filter with a custom one that validates the quantity to be a int or float and applies rounding
			add_filter( 'woocommerce_stock_amount', array( __CLASS__, 'round_stock_quantity' ) );

			// Customise the "Add to Cart" message to allow decimals in quantities
			add_filter( 'wc_add_to_cart_message_html', array( __CLASS__, 'add_to_cart_message' ), 10, 2 );

		}

	}

	/**
	 * Set min and step value for the stock quantity input number field (WC default = 1)
	 *
	 * @since 1.3.4
	 *
	 * @param int         $value
	 * @param \WC_Product $product
	 *
	 * @return float|int
	 */
	public static function stock_quantity_input_atts($value, $product) {
		return 10 / pow(10, Globals::get_stock_decimals() + 1);
	}

	/**
	 * Round the stock quantity according to the number of decimals specified in settings
	 *
	 * @since 1.3.4
	 *
	 * @param float|int $qty
	 *
	 * @return float|int
	 */
	public static function round_stock_quantity($qty) {

		if ( ! Globals::get_stock_decimals() ) {
			return intval($qty);
		}
		else {
			return round( floatval($qty), Globals::get_stock_decimals() );
		}

	}

	/**
	 * Customise the "Add to cart" messages to allow decimal places
	 *
	 * @since 1.3.4.1
	 *
	 * @param string $message
	 * @param int|array $products
	 *
	 * @return string
	 */
	public static function add_to_cart_message( $message, $products ) {

		$titles = array();
		$count  = 0;

		foreach ( $products as $product_id => $qty ) {
			$titles[] = ( $qty != 1 ? round( floatval( $qty ), Globals::get_stock_decimals() ) . ' &times; ' : '' ) . sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', ATUM_TEXT_DOMAIN ), strip_tags( get_the_title( $product_id ) ) );
			$count   += $qty;
		}

		$titles     = array_filter( $titles );
		$added_text = sprintf( _n( '%s has been added to your cart.', '%s have been added to your cart.', $count, ATUM_TEXT_DOMAIN ), wc_format_list_of_items( $titles ) );

		// Output success messages
		if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			$return_to = apply_filters( 'woocommerce_continue_shopping_redirect', wc_get_raw_referer() ? wp_validate_redirect( wc_get_raw_referer(), FALSE ) : wc_get_page_permalink( 'shop' ) );
			$message   = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( $return_to ), esc_html__( 'Continue shopping', ATUM_TEXT_DOMAIN ), esc_html( $added_text ) );
		}
		else {
			$message = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( wc_get_page_permalink( 'cart' ) ), esc_html__( 'View cart', ATUM_TEXT_DOMAIN ), esc_html( $added_text ) );
		}

		return $message;

	}

}