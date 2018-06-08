<?php
/**
 * @package        Atum
 * @subpackage     Components
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2018 Stock Management Labs™
 *
 * @since          1.3.1
 *
 * Add capabilities to WP user roles
 */

namespace Atum\Components;

defined( 'ABSPATH' ) or die;

use Atum\Inc\Helpers;

class AtumRender {

	/**
	 * The singleton instance holder
	 * @var AtumCapabilities
	 */
	private static $instance;

	/**
	 * Singleton constructor
	 *
	 * @since 1.3.1
	 */
	private function __construct() {

		// override the stock  / $availability if required.
		$is_out_stock_threshold_managed =  Helpers::get_option( 'out_stock_threshold', "no" ) ;

		if($is_out_stock_threshold_managed  === "yes"){
			add_filter( 'woocommerce_product_is_in_stock', array($this, 'get_product_is_in_stock_when_out_stock_threshold'), 10, 2 );

			// old way, and debug method to print and override values on single-product/stock.php
			//add_filter( 'woocommerce_get_stock_html', array($this, 'get_stock_html_when_out_stock_threshold'), 10, 2 );

			// TODO this... is usseful? if $product_stock_quantity < $out_stock_threshold we can't add to cart.
			//add_filter( 'woocommerce_quantity_input_max', array($this, 'get_product_quantity_input_max_when_out_stock_threshold'), 10, 2 );

		}
	}

	/**
	 * Override the get_stock_status to all products that have stock managed at product level,
	 * and atum's out_stock_threshold enabled and the _out_stock_threshold set.
	 * @return bool
	 */
	public function get_product_is_in_stock_when_out_stock_threshold( ) {
		global $product;

		// if not, it's not our problem: @see wc-apidocs/source-class-WC_Product.html#1931
		$out_stock_threshold = get_post_meta( $product->get_id(), '_out_stock_threshold', $single = true );

		if ( ! $product->get_manage_stock() || empty($out_stock_threshold) ) {
			return 'outofstock' !== $product->get_stock_status();
		}

		$product_stock_quantity = $product->get_stock_quantity();

		if ($product_stock_quantity > $out_stock_threshold) {
			return true;
		}else{
			return false;
		}
		return false; // 'in-stock': true 'out-of-stock': false
	}


	/**
	 *  set max on woocomerce before add to cart if required.
	 * TODO this... is usseful?
	do_action( 'woocommerce_before_add_to_cart_quantity' );
	woocommerce_quantity_input( array(
	'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
	'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
	'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
	) );
	do_action( 'woocommerce_after_add_to_cart_quantity' );
	?>
	 * @return string
	 */
	public function get_product_quantity_input_max_when_out_stock_threshold() {
		global $product;
		$out_stock_threshold    = get_post_meta( $product->get_id(), '_out_stock_threshold', $single = true );
		$product_stock_quantity = $product->get_stock_quantity();
		if ( $product_stock_quantity > $out_stock_threshold ) {
			return $product->get_max_purchase_quantity();
		} else {
			return $product->is_sold_individually() ? 1 : ( $product->backorders_allowed() || ! $product->managing_stock() ? - 1 : $product->get_stock_quantity() );
		}
	}


	public function get_stock_html_when_out_stock_threshold( ) {
		//$html         = '';
		//$availability = $product->get_availability();
		global $product;
		$availability = $product->get_availability();

		echo "product get_manage_stock";
		var_dump($product->get_manage_stock()); // true false
		echo "product get_stock_quantity";
		var_dump($product->get_stock_quantity()); // int  2 stock quantity
		echo "product get_stock_status";
		var_dump($product->get_stock_status()); // 'outofstock' (length=10)

		echo "wc_get_product_stock_status_options";
		var_dump(wc_get_product_stock_status_options());
		// 'instock' => string 'In stock' (length=8)
		// 'outofstock' => string 'Out of stock' (length=12)
		echo "availability";
		var_dump($availability);
		// 'availability' => string '10 in stock' (length=11)
		// 'class' => string 'in-stock' (length=8)

		// 'availability' => string 'Out of stock' (length=12)
		// 'class' => string 'out-of-stock' (length=12)
		echo "product out_stock_threshold";
		$out_stock_threshold = get_post_meta( $product->get_id(), '_out_stock_threshold', $single = true ); //int value
		var_dump($out_stock_threshold); //1

		// work as usual
		if ($product->get_stock_quantity() > $out_stock_threshold && ! empty( $availability['availability'] ) ){
			ob_start();

			wc_get_template( 'single-product/stock.php', array(
				'product'      => $product,
				'class'        => $availability['class'],
				'availability' => $availability['availability'],
			) );

			$html = ob_get_clean();

		}elseif ($product->get_stock_quantity() <= $out_stock_threshold && ! empty( $availability['availability'] )) {
			ob_start();

			wc_get_template( 'single-product/stock.php', array(
				'product'      => $product,
				'class'        => 'out-of-stock',
				'availability' => wc_get_product_stock_status_options()['outofstock'],
			) );

			$html = ob_get_clean();
		}
		return $html;
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
	 * @return AtumCapabilities instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}