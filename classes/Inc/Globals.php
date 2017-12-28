<?php
/**
 * @package         Atum
 * @subpackage      Inc
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           0.1.4
 *
 * Global options for Atum
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) or die;


final class Globals {
	
	/**
	 * The product types allowed
	 * For now the "external" products are excluded as WC doesn't add stock control fields to them
	 * @var array
	 */
	private static $product_types = ['simple', 'variable', 'grouped'];

	/**
	 * The product types that allow children
	 * @var array
	 */
	private static $inheritable_product_types = ['variable', 'grouped'];

	/**
	 * The child product types
	 * @var array
	 */
	private static $child_product_types = ['variation'];
	
	/**
	 * The meta key where is stored the out of stock date
	 * @var string
	 */
	private static $out_of_stock_date_key = '_out_of_stock_date';

	/**
	 * The number of decimals specified in settings to round the stock quantities
	 * @var int
	 */
	private static $stock_decimals;

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
	const PRODUCT_LOCATION_TAXONOMY =  ATUM_PREFIX . 'location';
	
	/**
	 * Getter for the $out_of_stock_date_key property
	 *
	 * @since 0.1.4
	 *
	 * @return string
	 */
	public static function get_out_of_stock_date_key() {
		return self::$out_of_stock_date_key;
	}
	
	/**
	 * Getter for the product_types property
	 *
	 * @since 0.1.4
	 *
	 * @return array
	 */
	public static function get_product_types() {

		// Add WC Subscriptions compatibility
		if ( class_exists('WC_Subscriptions') && Helpers::get_option('show_subscriptions', 'yes') == 'yes' ) {
			self::$product_types = array_merge( self::$product_types, ['subscription', 'variable-subscription'] );
		}

		return (array) apply_filters('atum/allowed_product_types', self::$product_types);
	}

	/**
	 * Getter for the inheritable_product_types property
	 *
	 * @since 1.3.2
	 *
	 * @return array
	 */
	public static function get_inheritable_product_types() {

		// Add WC Subscriptions compatibility
		if ( class_exists('WC_Subscriptions') && Helpers::get_option('show_subscriptions', 'yes') == 'yes' ) {
			self::$inheritable_product_types = array_merge( self::$inheritable_product_types, ['variable-subscription'] );
		}

		return (array) apply_filters('atum/allowed_inheritable_product_types', self::$inheritable_product_types);
	}

	/**
	 * Getter for the child_product_types property
	 *
	 * @since 1.1.4.2
	 *
	 * @return array
	 */
	public static function get_child_product_types() {

		// Add WC Subscriptions compatibility
		if ( class_exists('WC_Subscriptions') && Helpers::get_option('show_subscriptions', 'yes') == 'yes' ) {
			self::$child_product_types = array_merge( self::$child_product_types, ['subscription_variation'] );
		}

		return (array) apply_filters('atum/allowed_child_product_types', self::$child_product_types);
	}

	/**
	 * Getter for the Stock Decimals property
	 *
	 * @since 1.3.4
	 *
	 * @return int
	 */
	public static function get_stock_decimals() {
		return (int) apply_filters( 'atum/stock_decimals', self::$stock_decimals);
	}

	/**
	 * Setter for the Stock Decimals property
	 *
	 * @since 1.3.4
	 *
	 * @param int $stock_decimals
	 *
	 * @return int
	 */
	public static function set_stock_decimals($stock_decimals) {
		self::$stock_decimals = absint($stock_decimals);
	}
	
}