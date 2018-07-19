<?php
/**
 * Atum Legacy Class.
 * Integrates all Legacy functionality for WC versions less than 1.5Beta.
 *
 * @package         Atum
 * @subpackage      Legacy
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Legacy;

defined( 'ABSPATH' ) || die;

class AtumLegacy {
	
	/**
	 * AtumLegacy constructor.
	 */
	public function __construct() {
		
		if ( class_exists( 'Atum\StockCentral\StockCentral', FALSE ) ) {
			$this->load_legacy_stock_central();
		}
	}
	
	/**
	 * Load common modules legacy code
	 *
	 * @since 1.5.0
	 */
	public function load_legacy_common() {
	
	
	}
	
	/**
	 * Load Stock Central legacy code
	 *
	 * @since 1.5.0
	 */
	public function load_legacy_stock_central() {
	}
	
}
