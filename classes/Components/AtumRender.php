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

			// TODO this... is usseful? if $product_stock_quantity < $out_stock_threshold we can't add to cart.
			//add_filter( 'woocommerce_quantity_input_max', array($this, 'get_product_quantity_input_max_when_out_stock_threshold'), 10, 2 );
		}
	}

	/**
	 * Override the get_stock_status to all products that have stock managed at product level,
	 * and atum's out_stock_threshold enabled and the _out_stock_threshold set.
	 * @return bool
	 */
	public function get_product_is_in_stock_when_out_stock_threshold( $is_in_stock, $item) {

		global $wpdb;

	    $item_id = $item->get_ID();

		$query = $wpdb->prepare("SELECT meta_key, meta_value 
                  FROM wp_postmeta where post_id = %d
                  AND meta_key IN ( '_out_stock_threshold', '_manage_stock','_stock','_stock_status')",$item_id);

		/**
		 * $item_metas['_out_stock_threshold'][0]['meta_value']
		 * $item_metas['_manage_stock'][0]['meta_value']
		 * $item_metas['_stock'][0]['meta_value']
		 * $item_metas['_stock_status'][0]['meta_value']
		 */
		$item_metas = array_group_by( $wpdb->get_results( $query, ARRAY_A ), "meta_key" )  ;

		if ( count( $item_metas ) < 4 ) {
		    //not my problem
            return 'outofstock' !== $item->get_stock_status();
		}
		elseif($item_metas['_manage_stock'][0]['meta_value'] === "no" || empty($item_metas['_out_stock_threshold'][0]['meta_value']))  {
			//not my problem
			return 'outofstock' !== $item->get_stock_status();

        }else{
            if ($item_metas['_stock'][0]['meta_value'] > $item_metas['_out_stock_threshold'][0]['meta_value']) {
	            //avaiable
                return true;
            }else{
	            //_out_stock_threshold!
                return false;
            }
        }
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