<?php
/**
 * Class AtumStockDecimals
 *
 * @package     Atum\Components
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2023 Stock Management Labs™
 *
 * @since       1.9.37
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

final class AtumStockDecimals {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumStockDecimals
	 */
	private static $instance;

	/**
	 * The number of decimals specified in settings to round the stock quantities
	 *
	 * @var int
	 */
	private static $stock_decimals = 0;

	/**
	 * AtumStockDecimals singleton constructor
	 *
	 * @since 1.9.37
	 */
	private function __construct() {

		// Set the stock decimals setting globally.
		add_action( 'init', array( $this, 'stock_decimals' ), 11 );

	}

	/**
	 * Set the stock decimals
	 *
	 * @since 1.3.8.2
	 */
	public function stock_decimals() {

		self::set_stock_decimals( Helpers::get_option( 'stock_quantity_decimals', 0 ) );

		// Maybe allow decimals for WC products' stock quantity.
		if ( self::get_stock_decimals() > 0 ) {

			// Add step value to the quantity field (WC default = 1).
			add_filter( 'woocommerce_quantity_input_step', array( $this, 'stock_quantity_input_step' ), 10, 2 );
			add_filter( 'woocommerce_quantity_input_min', array( $this, 'stock_quantity_input_min' ), 10, 2 );

			// Removes the WooCommerce filter, that is validating the quantity to be an int.
			remove_filter( 'woocommerce_stock_amount', 'intval' );

			// Replace the above filter with a custom one that validates the quantity to be a int or float and applies rounding.
			add_filter( 'woocommerce_stock_amount', array( $this, 'round_stock_quantity' ) );

			// Customise the "Add to Cart" message to allow decimals in quantities.
			add_filter( 'wc_add_to_cart_message_html', array( $this, 'add_to_cart_message' ), 10, 2 );

			// Add custom decimal quantities to order add products.
			add_action( 'woocommerce_order_item_add_line_buttons', array( $this, 'wc_orders_min_qty' ) );

			// Change min_qty on quantity field on variable products if its necessary.
			add_filter( 'woocommerce_available_variation', array( $this, 'maybe_change_variable_min_qty' ) );

			// Stock status for decimal numbers under 1.
			foreach ( [ 'product', 'product_variation' ] as $post_type ) {
				add_filter( "woocommerce_{$post_type}_get_stock_status", array( $this, 'get_stock_status' ), 10, 2 );
			}

			// Prevent WooPayments reduce stock.
			add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'can_reduce_order_stock' ), PHP_INT_MAX, 2 );

			/**
			 * Cart and checkout blocks modifications.
			 * TODO...https://github.com/woocommerce/woocommerce/issues/44421
			 */
			/*add_filter('woocommerce_store_api_product_quantity_minimum',function ($qty, $cartItemData){
				$product_id = $cartItemData->get_id();
				return 20;
			}, 10, 2);
			add_filter('woocommerce_store_api_product_quantity_multiple_of',function ($qty, $cartItemData){
				$product_id = $cartItemData->get_id();
				return 0.1;
			}, 10, 2);*/

		}

	}

	/**
	 * Set step value for the stock quantity input number field (WC default = 1)
	 *
	 * @since 1.3.4
	 *
	 * @param int         $value
	 * @param \WC_Product $product
	 *
	 * @return float|int
	 */
	public function stock_quantity_input_step( $value, $product ) {
		return self::get_input_step();
	}

	/**
	 * Set min and step value for the stock quantity input number field (WC default = 1)
	 *
	 * @since 1.9.34.1
	 *
	 * @param int         $value
	 * @param \WC_Product $product
	 *
	 * @return float|int
	 */
	public function stock_quantity_input_min( $value, $product ) {

		// Always > 0
		$stock_decimals = self::get_stock_decimals();

		$step = Helpers::get_option( 'stock_quantity_step', 0 );

		$step_decimals = strlen( substr( strrchr( $step - floor( $step ), '.' ), 1 ) );

		if ( $step_decimals && $step_decimals < $stock_decimals ) {
			$step .= str_repeat( '0', $stock_decimals - $step_decimals );

			return $step;
		}

		return ( 10 / pow( 10, $stock_decimals + 1 ) );

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

		if ( ! self::get_stock_decimals() ) {
			return intval( $qty );
		}
		else {
			return round( floatval( $qty ), self::get_stock_decimals() );
		}

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
			$titles[] = ( 1 != $qty ? round( floatval( $qty ), self::get_stock_decimals() ) . ' &times; ' : '' ) . sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', ATUM_TEXT_DOMAIN ), wp_strip_all_tags( get_the_title( $product_id ) ) ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
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
	 * Add set min quantities script to WC orders
	 *
	 * @since 1.4.18
	 *
	 * @param \WC_Order $order
	 */
	public function wc_orders_min_qty( $order ) {

		$step = self::get_input_step();

		?>
		<script type="text/javascript">
			jQuery(function($) {
				var $script = $('#tmpl-wc-modal-add-products');

				$script.html($script.html().replace('step="1"', 'step="<?php echo esc_attr( $step ) ?>"')
					.replace('<?php echo esc_attr( 'step="1"' ) ?>', '<?php echo esc_attr( 'step="' . $step . '"' ) ?>'));

			});
		</script>

		<?php
	}

	/**
	 * Check if its necessary change the variable min quantity value
	 *
	 * @since 1.9.2
	 *
	 * @param array $variation_atts
	 *
	 * @return array
	 */
	public function maybe_change_variable_min_qty( $variation_atts ) {

		$input_step = self::get_input_step();

		if ( $input_step ) {
			$variation_atts['min_qty'] = $input_step;
		}

		return $variation_atts;

	}

	/**
	 * Check stock quantity when stock is value has decimals under 1.
	 *
	 * @since 1.9.34
	 *
	 * @param string      $stock_status
	 * @param \WC_Product $product
	 */
	public function get_stock_status( $stock_status, $product ) {

		$no_stock = floatval( get_option( 'woocommerce_notify_no_stock_amount' ) ) ?: 0;
		$product  = Helpers::get_atum_product( $product );

		$stock = $product->get_stock_quantity();

		if ( 'no' !== Helpers::get_option( 'out_stock_threshold', 'no' ) && $product->get_out_stock_threshold() !== '' ) {
			$no_stock = $product->get_out_stock_threshold();
		}

		if ( $stock < 1 && 'instock' !== $stock_status && $stock > $no_stock ) {
			$stock_status = 'instock';
		}

		return $stock_status;

	}

	/**
	 * Prevent WooPayments reduce order stock and override ATUM methods.
	 *
	 * @since 1.9.34
	 *
	 * @param boolean   $can_reduce_stock
	 * @param \WC_Order $order
	 *
	 * @return boolean
	 */
	public function can_reduce_order_stock( $can_reduce_stock, $order ) {

		if (
			class_exists( '\WC_Payment_Gateway_WCPay' ) &&
		     wc_string_to_bool( Helpers::get_option( 'chg_stock_order_complete', 'no' ) ) &&
		     'processing' === $order->get_status() &&
		     'woocommerce_payments' === $order->get_payment_method()
		) {
			$can_reduce_stock = FALSE;
		}

		return $can_reduce_stock;

	}

	/**
	 * Getter for the Stock Decimals property
	 *
	 * @since 1.3.4
	 *
	 * @return int
	 */
	public static function get_stock_decimals() {
		return (int) apply_filters( 'atum/stock_decimals', self::$stock_decimals );
	}

	/**
	 * Setter for the Stock Decimals property
	 *
	 * @since 1.3.4
	 *
	 * @param int $stock_decimals
	 */
	public static function set_stock_decimals( $stock_decimals ) {
		self::$stock_decimals = absint( $stock_decimals );
	}

	/**
	 * Return the step to input stock quantities attending ATUM custom decimals set.
	 *
	 * @since 1.4.18
	 *
	 * @return float|int
	 */
	public static function get_input_step() {

		$stock_decimals = self::get_stock_decimals();

		if ( ! $stock_decimals ) {
			return 1;
		}

		$step = Helpers::get_option( 'stock_quantity_step', 0 );

		if ( ! is_numeric( $step ) || 0.0 === (float) $step ) {
			return 'any';
		}

		$step_decimals = strlen( substr( strrchr( $step - floor( $step ), '.' ), 1 ) );

		// Allow values like 0.125 when 3 decimals are set and the step is like 0.1.
		if ( $step_decimals && $step_decimals < $stock_decimals ) {
			$step .= str_repeat( '0', $stock_decimals - $step_decimals );

			return $step;
		}

		// Avoid returning 1 when we should allow stock decimals to avoid HTML5 validation errors.
		return floor( $step ) == $step ? 'any' : $step; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison

	}


	/********************
	 * Instance methods
	 ********************/

	/**
	 * Get Singleton instance
	 *
	 * @return AtumStockDecimals instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

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

}
