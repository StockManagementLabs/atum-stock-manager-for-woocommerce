<?php
/**
 * @package     Atum
 * @subpackage  Dashboard
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2018 Stock Management Labs™
 *
 * @since       1.3.9
 *
 * The ATUM Dashboard main class
 */

namespace Atum\Dashboard;


class Dashboard {

	/**
	 * The singleton instance holder
	 * @var Dashboard
	 */
	private static $instance;

	/**
	 * Dashboard constructor
	 *
	 * @since 1.3.9
	 */
	private function __construct() {

	}


	/****************************
	 * Instance methods
	 ****************************/
	public function __clone() {
		// cannot be cloned
		_doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN), '1.0.0' );
	}

	public function __sleep() {
		// cannot be serialized
		_doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Dashboard instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}