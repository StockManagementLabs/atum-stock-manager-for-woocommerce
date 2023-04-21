<?php
/**
 * ATUM modules layer abstraction manager
 *
 * @package         Atum
 * @subpackage      Modules
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2023 Stock Management Labs™
 *
 * @since           1.3.6
 */

namespace Atum\Modules;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;


class ModuleManager {

	/**
	 * The singleton instance holder.
	 *
	 * @var ModuleManager
	 */
	private static $instance;

	/**
	 * The available modules
	 *
	 * @var array
	 */
	private $modules = [ 'dashboard', 'stock_central', 'inventory_logs', 'purchase_orders', 'data_export', 'visual_settings', 'api', 'barcodes' ];

	/**
	 * The current status of each module
	 *
	 * @var array
	 */
	private static $module_status = array();

	/**
	 * Suffix added to all the module options
	 */
	const OPTION_SUFFIX = '_module';

	/**
	 * Singleton constructor
	 *
	 * @since 1.3.6
	 */
	private function __construct() {

		// Add the Module Manager settings.
		add_filter( 'atum/settings/tabs', array( $this, 'add_settings_tab' ), 1 );
		add_filter( 'atum/settings/defaults', array( $this, 'add_settings_defaults' ), 1 );

		$this->modules = apply_filters( 'atum/module_manager/modules', $this->modules );

		foreach ( $this->modules as $module ) {
			self::$module_status[ $module ] = Helpers::get_option( $module . self::OPTION_SUFFIX, 'yes' );
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
	public function add_settings_tab( $tabs ) {

		$tabs['module_manager'] = array(
			'label'    => __( 'Modules', ATUM_TEXT_DOMAIN ),
			'icon'     => 'atmi-database',
			'sections' => array(
				'module_manager' => __( 'Module Manager', ATUM_TEXT_DOMAIN ),
			),
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
	public function add_settings_defaults( $defaults ) {

		/**
		 * IMPORTANT!!
		 * All the module option names MUST have the "_module" suffix or won't work.
		 */

		$defaults[ 'dashboard' . self::OPTION_SUFFIX ] = array(
			'group'   => 'module_manager',
			'section' => 'module_manager',
			'name'    => __( 'Dashboard', ATUM_TEXT_DOMAIN ),
			'desc'    => __( 'Enables/Disables the ATUM Dashboard module.', ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes',
		);

		$defaults[ 'stock_central' . self::OPTION_SUFFIX ] = array(
			'group'   => 'module_manager',
			'section' => 'module_manager',
			'name'    => __( 'Stock Central', ATUM_TEXT_DOMAIN ),
			'desc'    => __( 'Enables/Disables the Stock Central module.', ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes',
		);

		$defaults[ 'inventory_logs' . self::OPTION_SUFFIX ] = array(
			'group'   => 'module_manager',
			'section' => 'module_manager',
			'name'    => __( 'Inventory Logs', ATUM_TEXT_DOMAIN ),
			'desc'    => __( 'Enables/Disables the Inventory Logs module.', ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes',
		);

		$defaults[ 'purchase_orders' . self::OPTION_SUFFIX ] = array(
			'group'   => 'module_manager',
			'section' => 'module_manager',
			'name'    => __( 'Purchase Orders', ATUM_TEXT_DOMAIN ),
			'desc'    => __( "Enables/Disables the Purchase Orders module. It'll disable the dependant modules too (Inbound Stock, Suppliers, Product Locations and Purchase Price).", ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes',
		);

		$defaults[ 'data_export' . self::OPTION_SUFFIX ] = array(
			'group'   => 'module_manager',
			'section' => 'module_manager',
			'name'    => __( 'Data Export', ATUM_TEXT_DOMAIN ),
			'desc'    => __( 'Enables/Disables the Data Export module.', ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes',
		);

		$defaults[ 'visual_settings' . self::OPTION_SUFFIX ] = array(
			'group'   => 'module_manager',
			'section' => 'module_manager',
			'name'    => __( 'Visual Settings', ATUM_TEXT_DOMAIN ),
			'desc'    => __( 'Enables/Disables the Visual Settings module.', ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes',
		);

		$defaults[ 'api' . self::OPTION_SUFFIX ] = array(
			'group'   => 'module_manager',
			'section' => 'module_manager',
			'name'    => __( 'ATUM API', ATUM_TEXT_DOMAIN ),
			'desc'    => __( "Enables/Disables the ATUM's REST API. Please note that the API is required if you intend to use the ATUM's mobile APP.", ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes',
		);

		$defaults[ 'barcodes' . self::OPTION_SUFFIX ] = array(
			'group'   => 'module_manager',
			'section' => 'module_manager',
			'name'    => __( 'Barcodes', ATUM_TEXT_DOMAIN ),
			'desc'    => __( "Enables/Disables the ATUM's Barcodes fields in WordPress. Please note that if this module is disabled you won't be able to sync them through the ATUM's mobile APP.", ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes',
		);

		return apply_filters( 'atum/module_manager/modules_settings', $defaults );

	}

	/**
	 * Getter for the module_status prop (or one of its inner values)
	 *
	 * @since 1.3.6
	 *
	 * @param string $module
	 *
	 * @return array|string|bool
	 */
	public static function get_module_status( $module = '' ) {

		if ( $module ) {

			if ( isset( self::$module_status[ $module ] ) ) {
				return self::$module_status[ $module ];
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
	 * @param string $module The module to check.
	 *
	 * @return bool
	 */
	public static function is_module_active( $module ) {
		return 'yes' === self::get_module_status( $module );
	}


	/********************
	 * Instance methods
	 ********************/

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
	 * @return ModuleManager instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
