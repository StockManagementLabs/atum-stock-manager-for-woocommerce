<?php
/**
 * Global options for Atum
 *
 * @package         Atum
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           0.1.4
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;

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
	 * The product types that allow children
	 *
	 * @var array
	 */
	private static $simple_product_types = [ 'simple' ];

	/**
	 * The child product types
	 *
	 * @var array
	 */
	private static $child_product_types = [ 'variation' ];
	
	/**
	 * ATUM order types
	 *
	 * @var array
	 */
	private static $order_types = [
		ATUM_PREFIX . 'purchase_order',
		ATUM_PREFIX . 'inventory_log',
	];

	/**
	 * WC_Order statuses that allow changing the product stock
	 *
	 * @since 1.8.7
	 *
	 * @var array
	 */
	private static $order_statuses_change_stock = [
		'wc-completed',
		'wc-processing',
		'wc-on-hold',
	];

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
	private static $product_tab_fields = [ self::ATUM_CONTROL_STOCK_KEY => 'checkbox' ];
	
	/**
	 * Existent Order types identification in tables
	 *
	 * @var array
	 */
	private static $order_type_tables_id = array(
		'shop_order'                   => 1,
		ATUM_PREFIX . 'purchase_order' => 2,
		ATUM_PREFIX . 'inventory_log'  => 3,
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
	 * The products' location taxonomy name
	 */
	const PRODUCT_LOCATION_TAXONOMY = 'atum_location';

	/**
	 * The table name where is stored the ATUM data for products
	 */
	const ATUM_PRODUCT_DATA_TABLE = 'atum_product_data';

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
	 * The user meta key used to store settings per user
	 */
	const ATUM_USER_META_KEY = ATUM_PREFIX . 'user_meta';
	
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

		// Add WC Product Bundles compatibility.
		if (
			class_exists( '\WC_Bundles' ) &&
			! in_array( 'bundle', self::$product_types ) &&
			'yes' === Helpers::get_option( 'show_bundles', 'yes' )
		) {
			self::$product_types = array_merge( self::$product_types, [ 'bundle' ] );
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

		$cache_key                 = AtumCache::get_cache_key( 'inheritable_product_types' );
		$inheritable_product_types = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $inheritable_product_types;
		}

		// Add WC Subscriptions compatibility.
		if (
			class_exists( '\WC_Subscriptions' ) &&
			! in_array( 'variable-subscription', self::$inheritable_product_types ) &&
			'yes' === Helpers::get_option( 'show_subscriptions', 'yes' )
		) {
			self::$inheritable_product_types[] = 'variable-subscription';
		}

		// Add WC Product Bundles compatibility.
		if (
			class_exists( '\WC_Bundles' ) &&
			! in_array( 'bundle', self::$inheritable_product_types ) &&
			'yes' === Helpers::get_option( 'show_bundles', 'yes' )
		) {
			self::$inheritable_product_types[] = 'bundle';
		}

		$inheritable_product_types = (array) apply_filters( 'atum/allowed_inheritable_product_types', self::$inheritable_product_types );
		AtumCache::set_cache( $cache_key, $inheritable_product_types );

		return $inheritable_product_types;

	}

	/**
	 * Getter for the simple_product_types property
	 *
	 * @since 1.8.5
	 *
	 * @return array
	 */
	public static function get_simple_product_types() {

		$cache_key            = AtumCache::get_cache_key( 'simple_product_types' );
		$simple_product_types = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $simple_product_types;
		}

		$simple_product_types = (array) apply_filters( 'atum/simple_product_types', self::$simple_product_types );
		AtumCache::set_cache( $cache_key, $simple_product_types );

		return $simple_product_types;

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
	 * Get all ATUM compatible product types
	 *
	 * @since 1.5.8.3
	 */
	public static function get_all_compatible_products() {

		return (array) apply_filters( 'atum/compatible_product_types', array_unique( array_merge( self::get_product_types(), self::get_inheritable_product_types(), self::get_child_product_types() ) ) );

	}

	/**
	 * Get all the product types that allow stock management
	 *
	 * @since 1.4.11
	 */
	public static function get_product_types_with_stock() {

		return (array) apply_filters( 'atum/product_types_with_stock', array_diff( self::get_all_compatible_products(), [ 'grouped' ] ) );

	}

	/**
	 * Get all product types installed but not compatible with ATUM
	 *
	 * @since 1.5.8.3
	 */
	public static function get_incompatible_products() {

		return (array) apply_filters( 'atum/incompatible_product_types', array_diff( array_keys( wc_get_product_types() ), self::get_all_compatible_products() ) );

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
	 * Get the WC_Order statuses that allow changing the stock
	 *
	 * @since 1.8.7
	 *
	 * @return array
	 */
	public static function get_order_statuses_change_stock() {

		return (array) apply_filters( 'atum/order_statuses_allow_change_stock', self::$order_statuses_change_stock );
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

	/**
	 * Add the hook to enable the ATUM Product data models
	 *
	 * @since 1.5.0
	 */
	public static function enable_atum_product_data_models() {
		add_filter( 'woocommerce_product_class', array( __CLASS__, 'get_atum_product_data_model_class' ), PHP_INT_MAX, 4 );
		add_filter( 'woocommerce_data_stores', array( __CLASS__, 'replace_wc_data_stores' ), PHP_INT_MAX );
	}

	/**
	 * Add the hook to enable the ATUM Product data models
	 *
	 * @since 1.5.0
	 */
	public static function disable_atum_product_data_models() {
		remove_filter( 'woocommerce_product_class', array( __CLASS__, 'get_atum_product_data_model_class' ), PHP_INT_MAX );
		remove_filter( 'woocommerce_data_stores', array( __CLASS__, 'replace_wc_data_stores' ), PHP_INT_MAX );
	}

	/**
	 * Get the ATUM's product data model class name matching the passed product type
	 *
	 * @since 1.5.0
	 *
	 * @param string $wc_product_class
	 * @param string $product_type
	 * @param string $post_type
	 * @param int    $product_id
	 *
	 * @return string
	 */
	public static function get_atum_product_data_model_class( $wc_product_class, $product_type, $post_type, $product_id ) {

		$atum_product_class = apply_filters( 'atum/models/product_data_class', Helpers::get_atum_product_class( $product_type ), $product_type, $post_type, $product_id );

		if ( $atum_product_class ) {
			return $atum_product_class;
		}

		return $wc_product_class;

	}

	/**
	 * Replace the WooCommerce data stores with our customer ones
	 *
	 * @since 1.5.0
	 *
	 * @param array $data_stores
	 *
	 * @return array
	 */
	public static function replace_wc_data_stores( $data_stores ) {

		$data_stores_namespace = '\Atum\Models\DataStores';

		// Check if we have to use the new custom tables or the old ones.
		// TODO: WHEN WC MOVE THE NEW TABLES FROM THE FEATURE PLUGIN TO THE CORE, WE SHOULD CHANGE THE CLASS NAMES.
		if ( Helpers::is_using_new_wc_tables() ) {

			$data_stores['product']           = "{$data_stores_namespace}\AtumProductDataStoreCustomTable";
			$data_stores['product-grouped']   = "{$data_stores_namespace}\AtumProductGroupedDataStoreCustomTable";
			$data_stores['product-variable']  = "{$data_stores_namespace}\AtumProductVariableDataStoreCustomTable";
			$data_stores['product-variation'] = "{$data_stores_namespace}\AtumProductVariationDataStoreCustomTable";

			// WC Bookings compatibility.
			if ( array_key_exists( 'product-booking', $data_stores ) ) {
				$data_stores['product-booking'] = "{$data_stores_namespace}\AtumProductBookingDataStoreCPT"; // For now WC Bookings does not support the new tables.
			}

			// WC Subscriptions compatibility.
			if ( array_key_exists( 'subscription', $data_stores ) ) {
				$data_stores['subscription']                   = "{$data_stores_namespace}\AtumProductSubscriptionDataStoreCPT"; // For now WC Subscriptions does not support the new tables.
				$data_stores['product-variable-subscription']  = "{$data_stores_namespace}\AtumProductVariableDataStoreCustomTable";
				$data_stores['product_subscription_variation'] = "{$data_stores_namespace}\AtumProductVariationDataStoreCustomTable";
			}

		}
		else {

			$data_stores['product']           = "{$data_stores_namespace}\AtumProductDataStoreCPT";
			$data_stores['product-grouped']   = "{$data_stores_namespace}\AtumProductGroupedDataStoreCPT";
			$data_stores['product-variable']  = "{$data_stores_namespace}\AtumProductVariableDataStoreCPT";
			$data_stores['product-variation'] = "{$data_stores_namespace}\AtumProductVariationDataStoreCPT";

			// WC Bookings compatibility.
			if ( array_key_exists( 'product-booking', $data_stores ) ) {
				$data_stores['product-booking'] = "{$data_stores_namespace}\AtumProductBookingDataStoreCPT";
			}

			// WC Subscriptions compatibility.
			if ( array_key_exists( 'subscription', $data_stores ) ) {
				$data_stores['subscription']                   = "{$data_stores_namespace}\AtumProductSubscriptionDataStoreCPT";
				$data_stores['product-variable-subscription']  = "{$data_stores_namespace}\AtumProductVariableDataStoreCPT";
				$data_stores['product_subscription_variation'] = "{$data_stores_namespace}\AtumProductVariationDataStoreCPT";
			}
			
			// WC product bundles compatibility.
			if ( class_exists( '\WC_Product_Bundle' ) ) {

				$data_stores['product-bundle'] = "{$data_stores_namespace}\AtumProductBundleDataStoreCPT";

			}

		}

		return $data_stores;

	}
	
	/**
	 * Get the current Order $table identifier. Defaults to 1 (WC Order)
	 *
	 * @since 1.5.0.2
	 *
	 * @param string $type
	 *
	 * @return int|array|FALSE
	 */
	public static function get_order_type_table_id( $type = 'shop_order' ) {

		if ( $type ) {
			return isset( self::$order_type_tables_id[ $type ] ) ? self::$order_type_tables_id[ $type ] : FALSE;
		}
		
		return self::$order_type_tables_id;
		
	}


	/**
	 * Get the JS localization vars for the DateTimePicker
	 *
	 * @since 1.5.7
	 *
	 * @param array $replace Optional. Only needed if want to modify any default value.
	 *
	 * @return array
	 */
	public static function get_date_time_picker_js_vars( $replace = array() ) {

		$defaults = array(
			'goToToday'       => __( 'Go to today', ATUM_TEXT_DOMAIN ),
			'clearSelection'  => __( 'Clear selection', ATUM_TEXT_DOMAIN ),
			'closePicker'     => __( 'Close the picker', ATUM_TEXT_DOMAIN ),
			'selectMonth'     => __( 'Select Month', ATUM_TEXT_DOMAIN ),
			'prevMonth'       => __( 'Previous Month', ATUM_TEXT_DOMAIN ),
			'nextMonth'       => __( 'Next Month', ATUM_TEXT_DOMAIN ),
			'selectYear'      => __( 'Select Year', ATUM_TEXT_DOMAIN ),
			'prevYear'        => __( 'Previous Year', ATUM_TEXT_DOMAIN ),
			'nextYear'        => __( 'Next Year', ATUM_TEXT_DOMAIN ),
			'selectDecade'    => __( 'Select Decade', ATUM_TEXT_DOMAIN ),
			'prevDecade'      => __( 'Previous Decade', ATUM_TEXT_DOMAIN ),
			'nextDecade'      => __( 'Next Decade', ATUM_TEXT_DOMAIN ),
			'prevCentury'     => __( 'Previous Century', ATUM_TEXT_DOMAIN ),
			'nextCentury'     => __( 'Next Century', ATUM_TEXT_DOMAIN ),
			'incrementHour'   => __( 'Increment Hour', ATUM_TEXT_DOMAIN ),
			'pickHour'        => __( 'Pick Hour', ATUM_TEXT_DOMAIN ),
			'decrementHour'   => __( 'Decrement Hour', ATUM_TEXT_DOMAIN ),
			'incrementMinute' => __( 'Increment Minute', ATUM_TEXT_DOMAIN ),
			'pickMinute'      => __( 'Pick Minute', ATUM_TEXT_DOMAIN ),
			'decrementMinute' => __( 'Decrement Minute', ATUM_TEXT_DOMAIN ),
			'incrementSecond' => __( 'Increment Second', ATUM_TEXT_DOMAIN ),
			'pickSecond'      => __( 'Pick Second', ATUM_TEXT_DOMAIN ),
			'decrementSecond' => __( 'Decrement Second', ATUM_TEXT_DOMAIN ),
			'dateFormat'      => 'YYYY-MM-DD',
			'dateTimeFormat'  => 'YYYY-MM-DD HH:mm',
			'calendarLocale'  => substr( get_user_locale(), 0, 2 ),
		);

		return array_merge( $defaults, $replace );

	}

	/**
	 * Return max number of decimals for backend ATUM prices (in frontend WC setting will be applied)
	 *
	 * @since 1.8.4
	 *
	 * @return integer|boolean
	 */
	public static function get_prices_decimals() {

		return apply_filters( 'atum/price_decimals', FALSE );
	}
}
