<?php
/**
 * Global options for Atum
 *
 * @package         Atum
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           0.1.4
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;


final class Globals {
	
	/**
	 * The product types allowed
	 * For now the "external" products are excluded as WC doesn't add stock control fields to them
	 *
	 * @var array
	 */
	private static $product_types = [ 'simple', 'variable', 'grouped' ];

	/**
	 * The product types that allow children
	 *
	 * @var array
	 */
	private static $inheritable_product_types = [ 'variable', 'grouped' ];

	/**
	 * The child product types
	 *
	 * @var array
	 */
	private static $child_product_types = [ 'variation' ];
	
	/**
	 * ATUM existent order types
	 *
	 * @var array
	 */
	private static $order_types = [ ATUM_PREFIX . 'purchase_order', ATUM_PREFIX . 'inventory_log' ];

	/**
	 * The number of decimals specified in settings to round the stock quantities
	 *
	 * @var int
	 */
	private static $stock_decimals;

	/**
	 * The ATUM fields within the WC's Product Data meta box (ATUM Inventory tab)
	 *
	 * @var array
	 */
	private static $product_tab_fields = array(
		self::ATUM_CONTROL_STOCK_KEY => 'checkbox',
	);

	/**
	 * The ATUM pages hook name
	 */
	const ATUM_UI_HOOK = 'atum-inventory';

	/**
	 * Directory name to allow override of ATUM templates
	 */
	const TEMPLATE_DIR = 'atum';

	/**
	 * User meta key to control the current user dismissed notices
	 */
	const DISMISSED_NOTICES = 'atum_dismissed_notices';

	/**
	 * The products' location taxonomy name
	 */
	const PRODUCT_LOCATION_TAXONOMY = ATUM_PREFIX . 'location';

	/**
	 * The meta key where is stored the ATUM stock management status
	 */
	const ATUM_CONTROL_STOCK_KEY = '_atum_manage_stock';

	/**
	 * The meta key where is stored the purchase price
	 */
	const PURCHASE_PRICE_KEY = '_purchase_price';

	/**
	 * The meta key where is stored the out of stock date
	 */
	const OUT_OF_STOCK_DATE_KEY = '_out_of_stock_date';

	/**
	 * The meta key where is stored the out of stock threshold
	 */
	const OUT_STOCK_THRESHOLD_KEY = '_out_stock_threshold';

	/**
	 * The meta key name used for inheritable products (Grouped, Variables...)
	 */
	const IS_INHERITABLE_KEY = '_inheritable';

	/**
	 * Searchable columns and their types
	 */
	const SEARCHABLE_COLUMNS = array(
		'string'  => array(
			'title',
			'_supplier',
			'_sku',
			'_supplier_sku',
			'IDs', // ID as string to allow the use of commas ex: s = '12, 13, 89'.
		),
		'numeric' => array(
			'ID',
			'_regular_price',
			'_sale_price',
			'_purchase_price',
			'_weight',
			'_stock',
		),
	);

	
	/**
	 * Getter for the product_types property
	 *
	 * @since 0.1.4
	 *
	 * @return array
	 */
	public static function get_product_types() {

		// Add WC Subscriptions compatibility.
		if (
			class_exists( '\WC_Subscriptions' ) &&
			! in_array( 'subscription', self::$product_types ) &&
			'yes' === Helpers::get_option( 'show_subscriptions', 'yes' )
		) {
			self::$product_types = array_merge( self::$product_types, [ 'subscription', 'variable-subscription' ] );
		}

		// Add WC Bookings compatibility.
		if (
			class_exists( '\WC_Bookings' ) &&
			! in_array( 'booking', self::$product_types ) &&
			'yes' === Helpers::get_option( 'show_bookable_products', 'yes' )
		) {
			array_push( self::$product_types, 'booking' );
		}

		return (array) apply_filters( 'atum/allowed_product_types', self::$product_types );

	}

	/**
	 * Getter for the inheritable_product_types property
	 *
	 * @since 1.3.2
	 *
	 * @return array
	 */
	public static function get_inheritable_product_types() {

		// Add WC Subscriptions compatibility.
		if (
			class_exists( '\WC_Subscriptions' ) &&
			! in_array( 'variable-subscription', self::$inheritable_product_types ) &&
			'yes' === Helpers::get_option( 'show_subscriptions', 'yes' )
		) {
			self::$inheritable_product_types[] = 'variable-subscription';
		}

		return (array) apply_filters( 'atum/allowed_inheritable_product_types', self::$inheritable_product_types );
	}

	/**
	 * Getter for the child_product_types property
	 *
	 * @since 1.1.4.2
	 *
	 * @return array
	 */
	public static function get_child_product_types() {

		// Add WC Subscriptions compatibility.
		if (
			class_exists( '\WC_Subscriptions' ) &&
			! in_array( 'subscription_variation', self::$child_product_types ) &&
			'yes' === Helpers::get_option( 'show_subscriptions', 'yes' )
		) {
			self::$child_product_types[] = 'subscription_variation';
		}

		return (array) apply_filters( 'atum/allowed_child_product_types', self::$child_product_types );
	}

	/**
	 * Get all the product types that allow stock management
	 *
	 * @since 1.4.11
	 */
	public static function get_product_types_with_stock() {

		// Get all the product types used for List Tables except grouped.
		$product_types = array_merge( self::get_product_types(), self::get_inheritable_product_types(), self::get_child_product_types() );

		return (array) apply_filters( 'atum/product_types_with_stock', array_diff( array_unique( $product_types ), [ 'grouped' ] ) );

	}
	
	/**
	 * Get all ATUM order types
	 *
	 * @since 1.4.16
	 *
	 * @return array
	 */
	public static function get_order_types() {
		
		return (array) apply_filters( 'atum/order_types', self::$order_types );
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
	 * Getter for the Product Data Tab Fields property
	 *
	 * @since 1.4.1
	 *
	 * @return array
	 */
	public static function get_product_tab_fields() {
		return (array) apply_filters( 'atum/product_tab_fields', self::$product_tab_fields );
	}
	
}
