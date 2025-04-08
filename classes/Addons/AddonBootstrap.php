<?php
/**
 * AddonBootstrap class for being used as parent class for the addons bootstrapping
 *
 * @since       1.9.27
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     Atum\Addons
 */

namespace Atum\Addons;

defined( 'ABSPATH' ) || die;

abstract class AddonBootstrap {

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
	 * The add-on capabilities
	 *
	 * @var array
	 */
	protected static $capabilities = [];

	/**
	 * Addons generic constructor
	 *
	 * @param string $addon_key The add-on key to be registered.
	 */
	public function __construct( $addon_key ) {

		$this->addon_key = $addon_key;

		// Do not allow to load the add-on if it was not correctly bootstrapped.
		if ( $this->addon_key && Addons::is_addon_bootstrapped( $this->addon_key ) ) {

			self::$bootstrapped = TRUE;

			// Load after ATUM is fully loaded.
			add_action( 'atum/after_init', array( $this, 'init' ) );

			// Register the add-on capabilities.
			add_filter( 'atum/capabilities/caps', array( $this, 'register_addon_capabilities' ) );

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

	/**
	 * Register the add-on capabilities (if any).
	 *
	 * @since 1.9.46
	 *
	 * @param array $capabilities The capabilities to be registered.
	 *
	 * @return array
	 */
	public function register_addon_capabilities( $capabilities ) {
		if ( ! empty( static::$capabilities ) ) {
			$capabilities = array_merge( $capabilities, static::$capabilities );
		}

		return $capabilities;
	}

}
