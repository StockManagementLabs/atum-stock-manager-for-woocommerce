<?php
/**
 * @package         Atum
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.3.8.2
 *
 * Global ATUM hooks
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) or die;


class Hooks {

	/**
	 * The singleton instance holder
	 * @var Hooks
	 */
	private static $instance;

	/**
	 * Hooks singleton constructor
	 */
	private function __construct() {

		if ( is_admin() ) {
			$this->register_admin_hooks();
		}

		$this->register_global_hooks();

	}

	/**
	 * Register the admin-side hooks
	 *
	 * @since 1.3.8.2
	 */
	public function register_admin_hooks() {

		// Add extra links to the plugin desc row
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		// Handle the ATUM customizations to the WC's Product Data meta box
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_product_data_tab_panel' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_product_variation_data_panel' ), 9, 3 );
		add_action( 'save_post_product', array( $this, 'save_product_data_panel' ), 11 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_product_variation_data_panel' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts') );

		// Show the right stock status on WC products list when ATUM is managing the stock
		add_filter( 'woocommerce_admin_stock_html', array( $this, 'set_wc_products_list_stock_status' ), 10, 2 );

		// Add the location column to the items table in WC orders
		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'wc_order_add_location_column_header' ) );
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'wc_order_add_location_column_value' ), 10, 3 );

		// Firefox fix to not preserve the dropdown
		add_filter( 'wp_dropdown_cats', array( $this, 'set_dropdown_autocomplete' ), 10, 2 );

	}

	/**
	 * Register the global hooks
	 *
	 * @since 1.3.8.2
	 */
	public function register_global_hooks() {

		// Save the date when any product goes out of stock
		add_action( 'woocommerce_product_set_stock' , array( $this, 'record_out_of_stock_date'), 20 );

		// Set the stock decimals setting globally
		add_action( 'init', array($this, 'stock_decimals'), 11 );

		// Delete the views' transients after changing the stock of any product
		add_action( 'woocommerce_product_set_stock', array( $this, 'delete_transients' ) );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'delete_transients' ) );

	}

	/**
	 * Show row meta on the plugin screen
	 *
	 * @since 1.4.0
	 *
	 * @param array  $links   Plugin Row Meta
	 * @param string $file    Plugin Base file
	 *
	 * @return	array
	 */
	public function plugin_row_meta( $links, $file ) {

		if ( ATUM_BASENAME == $file ) {
			$row_meta = array(
				'video_tutorials' => '<a href="https://www.youtube.com/channel/UCcTNwTCU4X_UrIj_5TUkweA" aria-label="' . esc_attr__( 'View ATUM Video Tutorials', ATUM_TEXT_DOMAIN ) . '" target="_blank">' . esc_html__( 'Videos', ATUM_TEXT_DOMAIN ) . '</a>',
				'addons'          => '<a href="https://www.stockmanagementlabs.com/addons/" aria-label="' . esc_attr__( 'View ATUM add-ons', ATUM_TEXT_DOMAIN ) . '" target="_blank">' . esc_html__( 'Add-ons', ATUM_TEXT_DOMAIN ) . '</a>',
				'support'         => '<a href="https://stockmanagementlabs.ticksy.com/" aria-label="' . esc_attr__( 'Visit premium customer support', ATUM_TEXT_DOMAIN ) . '" target="_blank">' . esc_html__( 'Support', ATUM_TEXT_DOMAIN ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return $links;
	}

	/**
	 * Enqueue the ATUM admin scripts
	 *
	 * @since 1.4.1
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts($hook) {

		$post_type = get_post_type();

		if ($post_type == 'product' && $hook == 'post.php') {
			wp_enqueue_style( 'switchery', ATUM_URL . 'assets/css/vendor/switchery.min.css', array(), ATUM_VERSION );
			wp_enqueue_style( 'atum-product-data', ATUM_URL . 'assets/css/atum-product-data.css', array('switchery'), ATUM_VERSION );

			wp_enqueue_script( 'switchery', ATUM_URL . 'assets/js/vendor/switchery.min.js', array('jquery'), ATUM_VERSION, TRUE );
		}

	}

	/**
	 * Filters the Product data tabs settings to add ATUM settings
	 *
	 * @since 1.4.1
	 *
	 * @param array $data_tabs
	 *
	 * @return array
	 */
	public function add_product_data_tab($data_tabs) {

		// Add the ATUM tab to Simple and BOM products
		$bom_tab = array(
			'atum' => array(
				'label'    => __( 'ATUM Inventory', ATUM_TEXT_DOMAIN ),
				'target'   => 'atum_product_data',
				'class'    => array( 'show_if_simple', 'show_if_variable', 'show_if_product-part', 'show_if_raw-material' ),
				'priority' => 21
			)
		);

		// Insert the ATUM tab under Inventory tab
		$data_tabs = array_merge( array_slice($data_tabs, 0, 2), $bom_tab, array_slice($data_tabs, 2) );

		return $data_tabs;

	}

	/**
	 * Add the fields to ATUM Inventory tab within WC's Product Data meta box
	 *
	 * @since 1.4.1
	 */
	public function add_product_data_tab_panel() {

		?><div id="atum_product_data" class="atum-data-panel panel woocommerce_options_panel hidden">
			<div class="options_group"><?php

				$product_id = get_the_ID();
				woocommerce_wp_checkbox( array(
					'id'            => Globals::ATUM_CONTROL_STOCK_KEY,
					'name'          => 'atum_product_tab[' . Globals::ATUM_CONTROL_STOCK_KEY . ']',
					'value'         => get_post_meta( $product_id, Globals::ATUM_CONTROL_STOCK_KEY, TRUE ),
					'class'         => 'js-switch',
					'wrapper_class' => 'show_if_simple show_if_raw-material show_if_product-part',
					'label'         => __( 'ATUM Control Switch', ATUM_TEXT_DOMAIN ),
					'description'   => __( 'Turn the switch ON or OFF to allow the ATUM plugin to include this product in its lists, counters and statistics.', ATUM_TEXT_DOMAIN ),
					'desc_tip'      => TRUE
				) );
				?>

				<p class="form-field show_if_variable">
					<label for="change_stock_control"><?php _e("Variations' ATUM Control", ATUM_TEXT_DOMAIN ) ?></label>
					<select name="change_stock_control" id="change_stock_control">
						<option value="controlled"><?php _e('Controlled', ATUM_TEXT_DOMAIN) ?></option>
						<option value="uncontrolled"><?php _e('Uncontrolled', ATUM_TEXT_DOMAIN) ?></option>
					</select>
					&nbsp;
					<button type="button" class="button button-primary"><?php _e('Change Now!', ATUM_TEXT_DOMAIN) ?></button>

					<?php echo wc_help_tip( __('Changes the ATUM Control switch for all the variations to the status set at once.', ATUM_TEXT_DOMAIN) ); ?>
				</p>

			</div>

			<?php

			// Allow other fields to be added to the ATUM panel
			do_action('atum/after_product_data_panel');

			?>
			<script type="text/javascript">
				jQuery(function($){
					atumDoSwitchers();

					$('#woocommerce-product-data').on('woocommerce_variations_loaded', function() {
						atumDoSwitchers();
					});
				});

				function atumDoSwitchers() {
					jQuery('.js-switch').each(function () {
						new Switchery(this, { size: 'small' });
						jQuery(this).removeClass('js-switch');
					});
				}
			</script>
		</div><?php

	}

	/**
	 * Add the Product Levels meta boxes to the Product variations
	 *
	 * @since 0.0.3
	 *
	 * @param int      $loop             The current item in the loop of variations
	 * @param array    $variation_data   The current variation data
	 * @param \WP_Post $variation        The variation post
	 */
	public function add_product_variation_data_panel ($loop, $variation_data, $variation) {

		?>
		<div class="atum-data-panel">
			<h3 class="atum-section-title"><?php _e('ATUM Inventory', ATUM_LEVELS_TEXT_DOMAIN) ?></h3>

			<?php
			woocommerce_wp_checkbox( array(
				'id'          => Globals::ATUM_CONTROL_STOCK_KEY . '_' . $loop,
				'name'        => "variation_atum_tab[" . Globals::ATUM_CONTROL_STOCK_KEY . "][$loop]",
				'value'       => get_post_meta( $variation->ID, Globals::ATUM_CONTROL_STOCK_KEY, TRUE ),
				'class'       => 'js-switch',
				'label'       => __( 'ATUM Control Switch', ATUM_TEXT_DOMAIN ),
				'description' => __( "Turn the switch ON or OFF to allow the ATUM plugin to include this product in its lists, counters and statistics.", ATUM_TEXT_DOMAIN ),
				'desc_tip'    => TRUE
			) );

			// Allow other fields to be added to the ATUM panel
			do_action('atum/after_variation_product_data_panel', $loop, $variation_data, $variation); ?>
		</div>
		<?php

		}

	/**
	 * Save all the fields within the Product Data's ATUM Inventory tab
	 *
	 * @since 1.4.1
	 *
	 * @param int $product_id               The saved product's ID
	 * @param array $product_tab_values     Allow passing the values to save externally instead of getting them from $_POST
	 */
	public function save_product_data_panel( $product_id, $product_tab_values = array() ) {

		if ( empty( $product_tab_values ) && isset( $_POST['atum_product_tab'] ) ) {
			$product_tab_values = $_POST['atum_product_tab'];
		}

		$product_tab_fields     = Globals::get_product_tab_fields();
		$is_inheritable_product = Helpers::is_inheritable_type( $_POST['product-type'] );

		// Update the "_inehritable" meta key
		if ( $is_inheritable_product ) {
			update_post_meta( $product_id, Globals::IS_INHERITABLE_KEY, 'yes' );
		}
		else {
			delete_post_meta( $product_id, Globals::IS_INHERITABLE_KEY );
		}

		foreach ( $product_tab_fields as $field_name => $field_type ) {

			// The ATUM's stock control must be always 'yes' for inheritable products
			if ( $field_name == Globals::ATUM_CONTROL_STOCK_KEY && $is_inheritable_product ) {
				update_post_meta( $product_id, $field_name, 'yes' );
				continue;
			}

			// Sanitize the fields
			$field_value = '';
			switch ( $field_type ) {
				case 'checkbox':

					$field_value = isset( $product_tab_values[ $field_name ] ) ? 'yes' : '';
					break;

				case 'number_int':

					if ( isset( $product_tab_values[ $field_name ] ) ) {
						$field_value = absint( $product_tab_values[ $field_name ] );
					}
					break;

				case 'number_float':

					if ( isset( $product_tab_values[ $field_name ] ) ) {
						$field_value = floatval( $product_tab_values[ $field_name ] );
					}
					break;

				case 'text':
				default:

					if ( isset( $product_tab_values[ $field_name ] ) ) {
						$field_value = wc_clean( $product_tab_values[ $field_name ] );
					}
					break;
			}

			if ( $field_value ) {
				update_post_meta( $product_id, $field_name, $field_value );
			}
			else {
				delete_post_meta( $product_id, $field_name );
			}

		}

	}

	/**
	 * Save all the fields within the Variation Product's ATUM Section
	 *
	 * @since 1.4.1
	 *
	 * @param int $variation_id
	 * @param int $i
	 */
	public function save_product_variation_data_panel($variation_id, $i) {

		if ( isset( $_POST['variation_atum_tab'][ Globals::ATUM_CONTROL_STOCK_KEY ][ $i ] ) ) {
			update_post_meta( $variation_id, Globals::ATUM_CONTROL_STOCK_KEY, 'yes' );
		}
		else {
			delete_post_meta( $variation_id, Globals::ATUM_CONTROL_STOCK_KEY );
		}

	}

	/**
	 * Add hooks to show and save the Purchase Price field on products
	 *
	 * @since 1.3.8.3
	 */
	public function purchase_price_hooks() {

		// Add the purchase price to WC products
		add_action( 'woocommerce_product_options_pricing', array( $this, 'add_purchase_price_meta' ) );
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_purchase_price_meta' ), 10, 3 );

		// Save the product purchase price meta
		add_action( 'save_post_product', array( $this, 'save_purchase_price' ) );
		add_action( 'woocommerce_update_product_variation', array( $this, 'save_purchase_price' ) );
		
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
	public function add_purchase_price_meta ($loop = NULL, $variation_data = array(), $variation = NULL) {

		if ( ! current_user_can( ATUM_PREFIX . 'edit_purchase_price') ) {
			return;
		}

		$field_title = __( 'Purchase price', ATUM_TEXT_DOMAIN ) . ' (' . get_woocommerce_currency_symbol() . ')';

		if ( empty($variation) ) {
			$post_id       = get_the_ID();
			$wrapper_class = '_purchase_price_field';
			$field_id      = $field_name = '_purchase_price';
		}
		else {
			$post_id       = $variation->ID;
			$field_name    = "variation_purchase_price[$loop]";
			$field_id      = "variation_purchase_price_{$loop}";
			$wrapper_class = "$field_name form-row form-row-first";
		}

		$field_value = wc_format_localized_price( get_post_meta( $post_id, '_purchase_price', TRUE ) );

		Helpers::load_view( 'meta-boxes/product-data/purchase-price-field', compact( 'wrapper_class', 'field_title', 'field_name', 'field_id', 'field_value' ) );

	}

	/**
	 * Save the purchase price meta on product post savings
	 *
	 * @since 1.2.0
	 *
	 * @param int $post_id
	 */
	public function save_purchase_price($post_id) {

		$product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );

		// Variables, grouped and variations
		if ( Helpers::is_inheritable_type($product_type) ) {

			// Inheritable products have no prices
			if ( isset($_POST['_purchase_price']) ) {
				update_post_meta( $post_id, '_purchase_price', '' );
			}
			elseif ( isset($_POST['variation_purchase_price']) ) {

				$product_key    = array_search( $post_id, $_POST['variable_post_id'] );
				$purchase_price = (string) isset( $_POST['variation_purchase_price'] ) ? wc_clean( $_POST['variation_purchase_price'][ $product_key ] ) : '';
				$purchase_price = ( '' === $purchase_price ) ? '' : wc_format_decimal( $purchase_price );
				update_post_meta( $post_id, '_purchase_price', $purchase_price );

			}

		}
		// Rest of product types (Bypass if "_puchase_price" meta is not coming)
		elseif ( isset($_POST['_purchase_price']) ) {

			$purchase_price = (string) isset( $_POST['_purchase_price'] ) ? wc_clean( $_POST['_purchase_price'] ) : '';
			$purchase_price = ('' === $purchase_price) ? '' : wc_format_decimal( $purchase_price );
			update_post_meta( $post_id, '_purchase_price', $purchase_price);

		}
		
		if (isset($purchase_price)) {
			do_action( 'atum/hooks/after_save_purchase_price', $post_id, $purchase_price );
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
	public function set_wc_products_list_stock_status($stock_html, $the_product) {

		if (
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

					if ( ! Helpers::is_atum_controlling_stock($variation_id) ) {
						continue;
					}

					$variation_product = wc_get_product( $variation_id );
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
	 * Add the location to the items table in WC orders
	 *
	 * @since 1.3.3
	 *
	 * @param \WC_Order $wc_order
	 */
	public function wc_order_add_location_column_header($wc_order) {
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
	public function wc_order_add_location_column_value($product, $item, $item_id) {

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
	public function record_out_of_stock_date ($product) {

		if ( in_array($product->get_type(), Globals::get_product_types()) ) {

			$current_stock = $product->get_stock_quantity();
			$out_of_stock_date_key = Globals::OUT_OF_STOCK_DATE_KEY;
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
	public function delete_transients($product) {
		Helpers::delete_transients();
	}

	/**
	 * Set the stock decimals
	 *
	 * @since 1.3.8.2
	 */
	public function stock_decimals () {

		Globals::set_stock_decimals( Helpers::get_option('stock_quantity_decimals', 0) );

		// Maybe allow decimals for WC products' stock quantity
		if ( Globals::get_stock_decimals() > 0 ) {

			// Add min value to the quantity field (WC default = 1)
			add_filter( 'woocommerce_quantity_input_min', array( $this, 'stock_quantity_input_atts' ), 10, 2 );

			// Add step value to the quantity field (WC default = 1)
			add_filter( 'woocommerce_quantity_input_step', array( $this, 'stock_quantity_input_atts' ), 10, 2 );

			// Removes the WooCommerce filter, that is validating the quantity to be an int
			remove_filter( 'woocommerce_stock_amount', 'intval' );

			// Replace the above filter with a custom one that validates the quantity to be a int or float and applies rounding
			add_filter( 'woocommerce_stock_amount', array( $this, 'round_stock_quantity' ) );

			// Customise the "Add to Cart" message to allow decimals in quantities
			add_filter( 'wc_add_to_cart_message_html', array( $this, 'add_to_cart_message' ), 10, 2 );

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
	public function stock_quantity_input_atts($value, $product) {
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
	public function round_stock_quantity($qty) {

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
	public function add_to_cart_message( $message, $products ) {

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

	/**
	 * Firefox fix to not preserve the dropdown
	 *
	 * @since 1.4.1
	 *
	 * @param string $dropdown
	 * @param array  $args
	 *
	 * @return string
	 */
	public function set_dropdown_autocomplete($dropdown, $args) {

		if ($args['name'] == 'product_cat') {
			$dropdown = str_replace('<select ', '<select autocomplete="off" ', $dropdown);
		}

		return $dropdown;

	}


	/****************************
	 * Instance methods
	 ****************************/
	public function __clone() {

		// cannot be cloned
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	public function __sleep() {

		// cannot be serialized
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Hooks instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}