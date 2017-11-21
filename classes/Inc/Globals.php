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
	 * The meta key where is stored the out of stock date
	 * @var string
	 */
	private static $out_of_stock_date_key = '_out_of_stock_date';
	
	/**
	 * The ATUM admin page slug
	 */
	const ATUM_UI_SLUG = 'atum-stock-central';

	/**
	 * The ATUM pages hook name
	 */
	const ATUM_UI_HOOK = 'atum-inventory';

	
	/**
	 * Getter for the $out_of_stock_date_key property
	 *
	 * @since 0.1.4
	 * @return string
	 */
	public static function get_out_of_stock_date_key() {
		return self::$out_of_stock_date_key;
	}
	
	/**
	 * Getter for the product_types property
	 *
	 * @since 0.1.4
	 * @return array
	 */
	public static function get_product_types() {

		// Add WC Subscriptions compatibility
		if ( class_exists('WC_Subscriptions') ) {
			self::$product_types = array_merge( self::$product_types, ['subscription', 'variable-subscription'] );
		}

		return (array) apply_filters('atum/allowed_product_types', self::$product_types);
	}

	/**
	 * Getter for the inheritable_product_types property
	 *
	 * @since 1.3.2
	 * @return array
	 */
	public static function get_inheritable_product_types() {

		// Add WC Subscriptions compatibility
		if ( class_exists('WC_Subscriptions') ) {
			self::$inheritable_product_types = array_merge( self::$inheritable_product_types, ['variable-subscription'] );
		}

		return (array) apply_filters('atum/allowed_inheritable_product_types', self::$inheritable_product_types);
	}
	
}