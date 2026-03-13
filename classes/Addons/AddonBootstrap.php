<?php
/**
 * AddonBootstrap class for being used as parent class for the addons bootstrapping
 *
 * @since       1.9.27
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2026 Stock Management Labs™
 *
 * @package     Atum\Addons
 */

namespace Atum\Addons;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

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
	 * Whether the add-on is admin-side-only
	 *
	 * @var bool
	 */
	protected $is_admin_addon = FALSE;

	/**
	 * Addons generic constructor
	 *
	 * @param string $addon_key The add-on key to be registered.
	 */
	public function __construct( $addon_key ) {

		$this->addon_key = $addon_key;

		// Do not allow loading the add-on if it was not correctly bootstrapped.
		if ( $this->addon_key && Addons::is_addon_bootstrapped( $this->addon_key ) ) {

			self::$bootstrapped = TRUE;

			// If it is an admin-side-only addon, don't load it on the frontend.
			if ( ! $this->is_admin_addon || ( $this->is_admin_addon && Helpers::is_not_front_request() ) ) {

				// Register the add-on capabilities.
				add_filter( 'atum/capabilities/caps', array( $this, 'register_addon_capabilities' ) );

				// Load dependencies.
				$this->load_dependencies();

			}

			// Load admin-side-only addons dependencies.
			$this->load_admin_side_addon_global_dependencies();

			// Load after ATUM is fully loaded.
			add_action( 'atum/after_init', array( $this, 'init' ) );

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
	 * Load the admin-side-only addons dependencies that must be loaded globally (including the frontend).
	 */
	protected function load_admin_side_addon_global_dependencies() {
		// Override only if needed.
	}

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
