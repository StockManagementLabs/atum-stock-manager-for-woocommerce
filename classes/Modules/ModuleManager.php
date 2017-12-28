<?php
/**
 * @package         Atum
 * @subpackage      Modules
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.3.6
 *
 * ATUM modules layer abstraction manager
 */

namespace Atum\Modules;


use Atum\Inc\Helpers;


class ModuleManager {

	/**
	 * The singleton instance holder
	 * @var ModuleManager
	 */
	private static $instance;

	private $modules = ['stock_central', 'inventory_logs', 'purchase_orders', 'data_export', 'dashboard_statistics'];

	/**
	 * The current status of each module
	 * @var array
	 */
	private static $module_status = array();

	/**
	 * Singleton constructor
	 *
	 * @since 1.3.6
	 */
	private function __construct() {

		// Add the Module Manager settings
		add_filter( 'atum/settings/tabs', array($this, 'add_settings_tab'), 1 );
		add_filter( 'atum/settings/defaults', array($this, 'add_settings_defaults'), 1 );

		foreach ($this->modules as $module) {
			self::$module_status[ $module ] = Helpers::get_option("{$module}_module", 'yes');
		}

	}

	/**
	 * Add a new tab to the ATUM settings page
	 *
	 * @since 1.3.6
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_settings_tab ($tabs) {

		$tabs['module_manager'] = array(
			'tab_name' => __( 'Modules', ATUM_TEXT_DOMAIN ),
			'sections' => array(
				'module_manager' => __( 'Module Manager', ATUM_TEXT_DOMAIN )
			)
		);

		return $tabs;
	}

	/**
	 * Add fields to the ATUM settings page
	 *
	 * @since 1.3.6
	 *
	 * @param array $defaults
	 *
	 * @return array
	 */
	public function add_settings_defaults ($defaults) {

		$defaults['stock_central_module'] = array(
			'section' => 'module_manager',
			'name'    => __( 'Stock Central', ATUM_TEXT_DOMAIN ),
			'desc'    => __( 'Enables/Disables the Stock Central module.', ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes'
		);

		$defaults['inventory_logs_module'] = array(
			'section' => 'module_manager',
			'name'    => __( 'Inventory Logs', ATUM_TEXT_DOMAIN ),
			'desc'    => __( 'Enables/Disables the Inventory Logs module.', ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes'
		);

		$defaults['purchase_orders_module'] = array(
			'section' => 'module_manager',
			'name'    => __( 'Purchase Orders', ATUM_TEXT_DOMAIN ),
			'desc'    => __( "Enables/Disables the Purchase Orders module. It'll disable the dependant modules too (Inbound Stock, Suppliers and Product Locations).", ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes'
		);

		$defaults['data_export_module'] = array(
			'section' => 'module_manager',
			'name'    => __( 'Data Export', ATUM_TEXT_DOMAIN ),
			'desc'    => __( 'Enables/Disables the Data Export module.', ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes'
		);

		$defaults['dashboard_statistics_module'] = array(
			'section' => 'module_manager',
			'name'    => __( 'Dashboard Statistics Widget', ATUM_TEXT_DOMAIN ),
			'desc'    => __( 'Enables/Disables the Dashboard Statistics Widget module.', ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes'
		);

		return $defaults;

	}

	/**
	 * Getter for the module_status prop (or one of it's inner values)
	 *
	 * @since 1.3.6
	 *
	 * @param string $module
	 *
	 * @return array|string|bool
	 */
	public static function get_module_status ($module = '') {

		if ($module) {

			if ( isset( self::$module_status[$module] ) ) {
				return self::$module_status[$module];
			}

			return FALSE;

		}

		return self::$module_status;

	}

	/**
	 * Check whether an ATUM module is currently active
	 *
	 * @since 1.3.6
	 *
	 * @param string $module    The module to check
	 *
	 * @return bool
	 */
	public static function is_module_active($module) {
		return self::get_module_status($module) == 'yes';
	}


	/****************************
	 * Instance methods
	 ****************************/
	public function __clone() {

		// cannot be cloned
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	public function __sleep() {

		// cannot be serialized
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return ModuleManager instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}