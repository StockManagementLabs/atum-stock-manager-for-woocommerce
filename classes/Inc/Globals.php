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
	private static $product_types = array('simple', 'variable', 'grouped');
	
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
		return (array) apply_filters('atum/allowed_product_types', self::$product_types);
	}
	
}