<?php
/**
 * AddonBootstrap class for being used as parent class for the addons bootstrapping
 *
 * @since       1.9.27
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2023 Stock Management Labs™
 *
 * @package     Atum\Addons
 */

namespace Atum\Addons;

defined( 'ABSPATH' ) || die;

abstract class AddonBootstrap {

	/**
	 * The singleton instance holder
	 *
	 * @var AddonBootstrap
	 */
	private static $instance;

	/**
	 * The addon key. It must match with the key used when registering the add-on.
	 *
	 * @var string
	 */
	protected $addon_key = '';

	/**
	 * Whether the add-on was correctly bootstrapped
	 *
	 * @var bool
	 */
	protected static $bootstrapped = FALSE;

	/**
	 * Addons generic constructor
	 */
	protected function __construct() {

		// Do not allow to load the add-on if it was not correctly bootstrapped.
		if ( $this->addon_key && Addons::is_addon_bootstrapped( $this->addon_key ) ) {

			self::$bootstrapped = TRUE;

			// Load after ATUM is fully loaded.
			add_action( 'atum/after_init', array( $this, 'init' ) );

			// Load dependencies.
			$this->load_dependencies();

		}

	}

	/**
	 * Load addon's stuff once ATUM is fully loaded.
	 */
	abstract public function init();

	/**
	 * Load the add-on dependencies
	 */
	abstract protected function load_dependencies();

	/**
	 * Check whether this addon was bootstrapped
	 *
	 * @since 1.9.27
	 *
	 * @return bool
	 */
	public static function is_bootstrapped() {
		return self::$bootstrapped;
	}


	/*******************
	 * Instance methods
	 *******************/

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

	/**
	 * Get Singleton instance
	 *
	 * @return AddonBootstrap instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

}
