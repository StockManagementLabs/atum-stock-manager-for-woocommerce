<?php
/**
 * Global ATUM hooks
 *
 * @package         Atum
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.3.8.2
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Settings\Settings;

class Hooks {
	
	/**
	 * Store current stock threshold
	 *
	 * @since 1.4.15
	 *
	 * @var int
	 */
	private $stock_threshold = NULL;

	/**
	 * The singleton instance holder
	 *
	 * @var Hooks
	 */
	private static $instance;

	/**
	 * Hooks singleton constructor
	 *
	 * @since 1.3.8.2
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

		// Add extra links to the plugin desc row.
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Show the right stock status on WC products list when ATUM is managing the stock.
		add_filter( 'woocommerce_admin_stock_html', array( $this, 'set_wc_products_list_stock_status' ), 10, 2 );

		// Add the location column to the items table in WC orders.
		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'wc_order_add_location_column_header' ) );
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'wc_order_add_location_column_value' ), 10, 3 );

		// Firefox fix to not preserve the dropdown.
		add_filter( 'wp_dropdown_cats', array( $this, 'set_dropdown_autocomplete' ), 10, 2 );

		// Rebuild stock status in all products with _out_stock_threshold when we disable this setting.
		add_action( 'updated_option', array( $this, 'rebuild_wc_stock_status_on_disable' ), 10, 3 );
		
		add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_save_paid_date' ), 10, 2 );

	}

	/**
	 * Register the global hooks
	 *
	 * @since 1.3.8.2
	 */
	public function register_global_hooks() {

		// Save the date when any product goes out of stock.
		add_action( 'woocommerce_product_set_stock', array( $this, 'record_out_of_stock_date' ), 20 );

		// Set the stock decimals setting globally.
		add_action( 'init', array( $this, 'stock_decimals' ), 11 );

		// Delete the views' transients after changing the stock of any product.
		add_action( 'woocommerce_product_set_stock', array( $this, 'delete_transients' ) );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'delete_transients' ) );

		// Add out_stock_threshold hooks if required.
		if ( 'yes' === Helpers::get_option( 'out_stock_threshold', 'no' ) ) {
			
			add_action( 'woocommerce_product_set_stock', array( $this, 'maybe_change_stock_threshold' ) );
			add_action( 'woocommerce_variation_set_stock', array( $this, 'maybe_change_stock_threshold' ) );
			
			// woocommerce_variation_set_stock doesn't fires properly when updating from backend, so we need to change status for variations after save.
			add_action( 'woocommerce_save_product_variation', array( $this, 'maybe_change_variation_stock_status' ), 10, 2 );
			
			add_action( 'woocommerce_process_product_meta', array( $this, 'add_stock_status_threshold' ), 19 );
			add_action( 'woocommerce_process_product_meta', array( $this, 'remove_stock_status_threshold' ), 21 );
			
			add_action( 'atum/product_data/before_save_product_meta_boxes', array( $this, 'add_stock_status_threshold' ) );
			add_action( 'atum/product_data/after_save_product_meta_boxes', array( $this, 'remove_stock_status_threshold' ) );
			add_action( 'atum/product_data/before_save_product_variation_meta_boxes', array( $this, 'add_stock_status_threshold' ) );
			add_action( 'atum/product_data/after_save_product_variation_meta_boxes', array( $this, 'remove_stock_status_threshold' ) );
		
		}

	}

	/**
	 * Enqueue the ATUM admin scripts
	 *
	 * @since 1.4.1
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		$post_type = get_post_type();

		if ( 'product' === $post_type && in_array( $hook, [ 'post.php', 'post-new.php' ], TRUE ) ) {

			// Enqueue styles.
			wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', array(), ATUM_VERSION );
			wp_register_style( 'switchery', ATUM_URL . 'assets/css/vendor/switchery.min.css', array(), ATUM_VERSION );
			wp_register_style( 'atum-product-data', ATUM_URL . 'assets/css/atum-product-data.css', array( 'switchery', 'sweetalert2' ), ATUM_VERSION );
			wp_enqueue_style( 'atum-product-data' );

			// Enqueue scripts.
			wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', array(), ATUM_VERSION, TRUE );
			Helpers::maybe_es6_promise();

			wp_register_script( 'atum-product-data', ATUM_URL . 'assets/js/build/atum-product-data.js', array( 'jquery', 'sweetalert2' ), ATUM_VERSION, TRUE );

			wp_localize_script( 'atum-product-data', 'atumProductData', array(
				'areYouSure'                    => __( 'Are you sure?', ATUM_TEXT_DOMAIN ),
				'continue'                      => __( 'Yes, Continue', ATUM_TEXT_DOMAIN ),
				'cancel'                        => __( 'Cancel', ATUM_TEXT_DOMAIN ),
				'success'                       => __( 'Success!', ATUM_TEXT_DOMAIN ),
				'error'                         => __( 'Error!', ATUM_TEXT_DOMAIN ),
				'nonce'                         => wp_create_nonce( 'atum-product-data-nonce' ),
				'isOutStockThresholdEnabled'    => Helpers::get_option( 'out_stock_threshold', 'no' ),
				'outStockThresholdProductTypes' => Globals::get_product_types_with_stock(),
			) );

			wp_enqueue_script( 'atum-product-data' );

		}

	}
	
	/**
	 * Add set min quantities script to WC orders
	 *
	 * @since 1.4.18
	 *
	 * @param \WC_Order $order
	 */
	public function wc_orders_min_qty( $order ) {
		
		$step = Helpers::get_input_step();
		
		?>
		<script type="text/javascript">
			jQuery(function ($) {
				var $script = $('#tmpl-wc-modal-add-products');
				
				$script.html($script.html().replace('step="1"', 'step="<?php echo esc_attr( $step ) ?>"')
					.replace('<?php echo esc_attr( 'step="1"' ) ?>', '<?php echo esc_attr( 'step="' . $step . '"' ) ?>'));
				
			});
		</script>
		
		<?php
	}

	/**
	 * Sets the stock status in WooCommerce products' list for inheritable products
	 *
	 * @since 1.2.6
	 *
	 * @param string      $stock_html   The HTML markup for the stock status.
	 * @param \WC_Product $the_product  The product that is currently checked.
	 *
	 * @return string
	 */
	public function set_wc_products_list_stock_status( $stock_html, $the_product ) {

		if (
			'yes' === Helpers::get_option( 'show_variations_stock', 'yes' ) &&
			in_array( $the_product->get_type(), array_diff( Globals::get_inheritable_product_types(), [ 'grouped' ] ), TRUE )
		) {

			// Get the variations within the variable.
			$variations   = $the_product->get_children();
			$stock_status = 'outofstock';
			$stock_text   = esc_attr__( 'Out of stock', ATUM_TEXT_DOMAIN );
			$stock_html   = '';
			
			if ( ! empty( $variations ) ) {
				
				$stock_html = ' (';
				foreach ( $variations as $variation_id ) {
					
					$variation_product = wc_get_product( $variation_id ); // We don't need to use ATUM models here.
					$variation_stock   = is_null( $variation_product->get_stock_quantity() ) ? 'X' : $variation_product->get_stock_quantity();
					$variation_status  = $variation_product->get_stock_status();
					$style             = 'color:#a44';
					
					switch ( $variation_status ) {
						case 'instock':
							$stock_status = 'instock';
							$stock_text   = esc_attr__( 'In stock', ATUM_TEXT_DOMAIN );
							$style        = 'color:#7ad03a';
							break;
						case 'onbackorder':
							if ( 'instock' !== $stock_status ) {
								$stock_status = 'onbackorder';
								$stock_text   = esc_attr__( 'On backorder', ATUM_TEXT_DOMAIN );
							}
							$style = 'color:#eaa600';
							break;
					}
					
					$stock_html .= sprintf( '<span style="%s">%s</span>, ', $style, $variation_stock );
					
				}
				
				$stock_html = substr( $stock_html, 0, -2 ) . ')';
			}
			
			$stock_html = "<mark class='$stock_status'>$stock_text</mark>" . $stock_html;

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
	public function wc_order_add_location_column_header( $wc_order ) {
		?>
		<th class="item_location sortable" data-sort="string-ins"><?php esc_attr_e( 'Location', ATUM_TEXT_DOMAIN ); ?></th>
		<?php
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
	public function wc_order_add_location_column_value( $product, $item, $item_id ) {

		$locations_list = '';

		if ( $product ) {
			$product_id     = 'variation' === $product->get_type() ? $product->get_parent_id() : $product->get_id();
			$locations      = wc_get_product_terms( $product_id, Globals::PRODUCT_LOCATION_TAXONOMY, array( 'fields' => 'names' ) );
			$locations_list = ! empty( $locations ) ? implode( ', ', $locations ) : '&ndash;';
		}

		?>
		<td class="item_location"<?php if ($product) echo ' data-sort-value="' . esc_attr( $locations_list ) . '"' ?>>
			<?php if ( $product ) : ?>
				<div class="view"><?php echo esc_attr( $locations_list ) ?></div>
			<?php else : ?>
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
	 * @param \WC_Product $product    The product being changed.
	 */
	public function record_out_of_stock_date( $product ) {

		// Handle the products managed by WC and from any of the allowed product types.
		if ( $product->managing_stock() && in_array( $product->get_type(), Globals::get_product_types() ) ) {

			// Reload the product using the ATUM data models.
			$product = Helpers::get_atum_product( $product );

			// Do not record the date to products not controlled by ATUM.
			if ( Helpers::is_atum_controlling_stock( $product ) ) {

				$current_stock  = $product->get_stock_quantity();
				$out_stock_date = NULL;

				if ( ! $current_stock ) {
					$out_stock_date = Helpers::date_format( current_time( 'timestamp' ), TRUE );
				}

				/* @noinspection PhpUndefinedMethodInspection */
				$product->set_out_stock_date( $out_stock_date );
				/* @noinspection PhpUndefinedMethodInspection */
				$product->save_atum_data();

			}

			AtumCache::delete_transients();

		}

	}

	/**
	 * Delete the ATUM transients after the product stock changes
	 *
	 * @since 0.1.5
	 *
	 * @param \WC_Product $product   The product.
	 */
	public function delete_transients( $product ) {
		AtumCache::delete_transients();
		
	}

	/**
	 * Set the stock decimals
	 *
	 * @since 1.3.8.2
	 */
	public function stock_decimals() {

		Globals::set_stock_decimals( Helpers::get_option( 'stock_quantity_decimals', 0 ) );

		// Maybe allow decimals for WC products' stock quantity.
		if ( Globals::get_stock_decimals() > 0 ) {
			
			// Add step value to the quantity field (WC default = 1).
			add_filter( 'woocommerce_quantity_input_step', array( $this, 'stock_quantity_input_atts' ), 10, 2 );

			// Removes the WooCommerce filter, that is validating the quantity to be an int.
			remove_filter( 'woocommerce_stock_amount', 'intval' );

			// Replace the above filter with a custom one that validates the quantity to be a int or float and applies rounding.
			add_filter( 'woocommerce_stock_amount', array( $this, 'round_stock_quantity' ) );

			// Customise the "Add to Cart" message to allow decimals in quantities.
			add_filter( 'wc_add_to_cart_message_html', array( $this, 'add_to_cart_message' ), 10, 2 );
			
			// Add custom decimal quantities to order add products.
			add_action( 'woocommerce_order_item_add_line_buttons', array( $this, 'wc_orders_min_qty' ) );

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
	public function stock_quantity_input_atts( $value, $product ) {
		
		if ( doing_filter( 'woocommerce_quantity_input_min' ) && 0 === $value ) {
			return $value;
		}
		
		return Helpers::get_input_step();
	}

	/**
	 * Customise the "Add to cart" messages to allow decimal places
	 *
	 * @since 1.3.4.1
	 *
	 * @param string    $message
	 * @param int|array $products
	 *
	 * @return string
	 */
	public function add_to_cart_message( $message, $products ) {

		$titles = array();
		$count  = 0;

		foreach ( $products as $product_id => $qty ) {
			/* translators: the product title */
			$titles[] = ( 1 != $qty ? round( floatval( $qty ), Globals::get_stock_decimals() ) . ' &times; ' : '' ) . sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', ATUM_TEXT_DOMAIN ), wp_strip_all_tags( get_the_title( $product_id ) ) ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$count   += $qty;
		}

		$titles = array_filter( $titles );
		/* translators: the titles of products added to the cart */
		$added_text = sprintf( _n( '%s has been added to your cart.', '%s have been added to your cart.', $count, ATUM_TEXT_DOMAIN ), wc_format_list_of_items( $titles ) );

		// Output success messages.
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
	 * Hook update_options. If we update atum_settings, we check if out_stock_threshold == no.
	 * Then, if we have any out_stock_threshold meta, rebuild that product to update the stock_status if required
	 *
	 * @since 1.4.10
	 *
	 * @param string $option_name
	 * @param array  $old_value
	 * @param array  $option_value
	 */
	public function rebuild_wc_stock_status_on_disable( $option_name, $old_value, $option_value ) {

		if (
			Settings::OPTION_NAME === $option_name && isset( $option_value['out_stock_threshold'] ) &&
			'no' === $option_value['out_stock_threshold'] && Helpers::is_any_out_stock_threshold_set()
		) {
			Helpers::force_rebuild_stock_status( NULL, FALSE, TRUE );
		}

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
	public function set_dropdown_autocomplete( $dropdown, $args ) {

		if ( 'product_cat' === $args['name'] ) {
			$dropdown = str_replace( '<select ', '<select autocomplete="off" ', $dropdown );
		}

		return $dropdown;

	}
	
	/**
	 * Round the stock quantity according to the number of decimals specified in settings
	 *
	 * @since 1.4.13
	 *
	 * @param float|int $qty
	 *
	 * @return float|int
	 */
	public function round_stock_quantity( $qty ) {
		
		if ( ! Globals::get_stock_decimals() ) {
			return intval( $qty );
		}
		else {
			return round( floatval( $qty ), Globals::get_stock_decimals() );
		}
		
	}
	
	/**
	 * Change the stock threshold if this->stock_threshold has value
	 *
	 * @since 1.4.15
	 *
	 * @param bool|mixed $pre
	 * @param string     $option
	 * @param mixed      $default
	 *
	 * @return mixed
	 */
	public function get_custom_stock_threshold( $pre, $option, $default ) {
		return is_null( $this->stock_threshold ) ? $pre : $this->stock_threshold;
	}
	
	/**
	 * Change the stock threshold if current product has one set.
	 *
	 * @since 1.4.15
	 *
	 * @param \WC_Product $product   The product.
	 */
	public function maybe_change_stock_threshold( $product ) {
		
		if ( in_array( $product->get_type(), Globals::get_product_types_with_stock() ) ) {

			// Ensure that is the product uses the ATUM models.
			$product = Helpers::get_atum_product( $product );
			
			$this->stock_threshold = NULL;

			// When the product is being created, no change is needed.
			if ( ! is_a( $product, '\WC_Product' ) ) {
				return;
			}

			$product_id = $product->get_id();
			/* @noinspection PhpUndefinedMethodInspection */
			$out_of_stock_threshold = $product->get_out_stock_threshold();

			// Allow to be hooked externally.
			$out_of_stock_threshold = apply_filters( 'atum/out_of_stock_threshold_for_product', $out_of_stock_threshold, $product_id );
			
			if ( FALSE !== $out_of_stock_threshold && '' !== $out_of_stock_threshold ) {
				
				$this->stock_threshold = (int) $out_of_stock_threshold;
				$this->add_stock_status_threshold();
				
				$product->save();
				
				$this->remove_stock_status_threshold();
				
			}
			
		}
	}
	
	/**
	 * Change the stock status if current variation has one set.
	 *
	 * @since 1.4.15
	 *
	 * @param int $variation_id
	 * @param int $i
	 */
	public function maybe_change_variation_stock_status( $variation_id, $i ) {

		$this->stock_threshold = NULL;

		$product = Helpers::get_atum_product( $variation_id );
		/* @noinspection PhpUndefinedMethodInspection */
		$out_of_stock_threshold = $product->get_out_stock_threshold();

		// Allow to be hooked externally.
		$out_of_stock_threshold = apply_filters( 'atum/out_of_stock_threshold_for_product', $out_of_stock_threshold, $variation_id );

		if ( FALSE !== $out_of_stock_threshold && '' !== $out_of_stock_threshold ) {

			// TODO: TEST THIS WITH STOCK DECIMALS.
			$this->stock_threshold = (int) $out_of_stock_threshold;
			
			$this->add_stock_status_threshold();

			$product->save();
			
			$this->remove_stock_status_threshold();
			
		}
		
	}
	
	/**
	 * Add pre_option_woocommerce_notify_no_stock_amount filter after all order products stock is reduced.
	 *
	 * We don't need the parameter, so function can be called from various places.
	 *
	 * @since 1.5.0
	 *
	 * @param integer $product_id
	 */
	public function add_stock_status_threshold( $product_id = 0 ) {
		
		add_filter( 'pre_option_woocommerce_notify_no_stock_amount', array( $this, 'get_custom_stock_threshold' ), 10, 3 );
		
	}
	
	/**
	 * Remove pre_option_woocommerce_notify_no_stock_amount filter after all order products stock is reduced
	 *
	 * We don't need the parameter, so function can be called from various places.
	 *
	 * @since 1.5.0
	 *
	 * @param integer $product_id
	 */
	public function remove_stock_status_threshold( $product_id = 0 ) {
		
		remove_filter( 'pre_option_woocommerce_notify_no_stock_amount', array( $this, 'get_custom_stock_threshold' ) );
		
	}
	
	/**
	 * Show row meta on the plugin screen
	 *
	 * @since 1.4.0
	 *
	 * @param array  $links   Plugin row meta.
	 * @param string $file    Plugin base file.
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		
		if ( ATUM_BASENAME === $file ) {
			$row_meta = array(
				'video_tutorials' => '<a href="https://www.youtube.com/channel/UCcTNwTCU4X_UrIj_5TUkweA" aria-label="' . esc_attr__( 'View ATUM Video Tutorials', ATUM_TEXT_DOMAIN ) . '" target="_blank">' . esc_html__( 'Videos', ATUM_TEXT_DOMAIN ) . '</a>',
				'addons'          => '<a href="https://www.stockmanagementlabs.com/addons/" aria-label="' . esc_attr__( 'View ATUM add-ons', ATUM_TEXT_DOMAIN ) . '" target="_blank">' . esc_html__( 'Add-ons', ATUM_TEXT_DOMAIN ) . '</a>',
				'support'         => '<a href="https://forum.stockmanagementlabs.com/t/atum-wp-plugin-issues-bugs-discussions" aria-label="' . esc_attr__( 'Visit ATUM support forums', ATUM_TEXT_DOMAIN ) . '" target="_blank">' . esc_html__( 'Support', ATUM_TEXT_DOMAIN ) . '</a>',
			);
			
			return array_merge( $links, $row_meta );
		}
		
		return $links;
	}
	
	/**
	 * Save WC Order's paid date meta if not set when changing the order status to Completed
	 *
	 * @since 1.5.3
	 *
	 * @param int       $order_id
	 * @param \WC_Order $order
	 *
	 * @throws \WC_Data_Exception
	 */
	public function maybe_save_paid_date( $order_id, $order ) {
		
		$paid_date = $order->get_date_paid();
		
		if ( ! $paid_date ) {
			
			$order_mod = wc_get_order( $order_id );
			$order_mod->set_date_paid( current_time( 'timestamp', TRUE ) );
			$order_mod->save();
		}
		
	}
	
	/********************
	 * Instance methods
	 ********************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
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
