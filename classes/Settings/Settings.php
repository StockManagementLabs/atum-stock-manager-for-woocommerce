<?php
/**
 * Build and display the ATUM settings page
 *
 * @package     Atum
 * @subpackage  Settings
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @since       0.0.2
 */

namespace Atum\Settings;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumColors;
use Atum\Components\AtumMarketingPopup;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;


class Settings {

	/**
	 * The singleton instance holder
	 *
	 * @var Settings
	 */
	private static $instance;
	/**
	 * Tabs (groups) and sections structure
	 *
	 * @var array
	 */
	private $tabs;
	/**
	 * Default active tab
	 *
	 * @var string
	 */
	private $active_tab = 'general';
	/**
	 * Store field structure and default values for the settings page
	 *
	 * @var array
	 */
	private $defaults;
	/**
	 * Store the fields that should be stored as user meta
	 *
	 * @var array
	 */
	private $user_meta_options = [];
	/**
	 * Holds the values to be used in the fields callbacks
	 *
	 * @var array
	 */
	private $options;
	/**
	 * The admin page slug
	 */
	const UI_SLUG = 'atum-settings';
	/**
	 * The option key name for the plugin settings
	 */
	const OPTION_NAME = ATUM_PREFIX . 'settings';
	/**
	 * The menu order for this module
	 */
	const MENU_ORDER = 80;
	/**
	 * The sale days used when no value provided
	 */
	const DEFAULT_SALE_DAYS = 14;
	/**
	 * The default number of diaplayed posts per page
	 */
	const DEFAULT_POSTS_PER_PAGE = 20;

	/**
	 * Settings singleton constructor
	 */
	private function __construct() {

		add_action( 'admin_init', array( $this, 'register_settings' ) );

		if ( AtumCapabilities::current_user_can( 'manage_settings' ) ) {

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 11 );

			// Add the module menu.
			add_filter( 'atum/admin/menu_items', array( $this, 'add_menu' ), self::MENU_ORDER );

		}

		// Add tools to AtumCli commands.
		if ( class_exists( '\WP_CLI', FALSE ) ) {
			$tools = Tools::get_instance();
			\WP_CLI::do_hook( 'before_add_command:atum', $tools->add_settings_defaults( [] ), __NAMESPACE__ );
		}

	}

	/**
	 * Add the Settings menu
	 *
	 * @since 1.3.6
	 *
	 * @param array $menus
	 *
	 * @return array
	 */
	public function add_menu( $menus ) {

		$menus['settings'] = array(
			'title'      => __( 'Settings', ATUM_TEXT_DOMAIN ),
			'callback'   => array( $this, 'display' ),
			'slug'       => self::UI_SLUG,
			'menu_order' => self::MENU_ORDER,
		);

		return $menus;

	}

	/**
	 * Display the settings page view
	 *
	 * @since 0.0.2
	 */
	public function display() {

		$this->options = $this->get_settings( Helpers::get_options(), $this->defaults );

		if ( isset( $_GET['tab'] ) ) {
			$this->active_tab = $_GET['tab'];
		}

		Helpers::load_view( 'settings-page', array(
			'tabs'   => $this->tabs,
			'active' => $this->active_tab,
		) );
	}

	/**
	 * Get the option settings and merge them with defaults. With parameters in case we need this function in Helpers
	 *
	 * @since   0.0.2
	 *
	 * @param array $settings The settings.
	 * @param array $defaults The default options.
	 *
	 * @return  array       The options array mixed
	 */
	public function get_settings( $settings, $defaults ) {

		$options = array();

		if ( ! $settings || ! is_array( $settings ) ) {
			$settings = array();
		}

		foreach ( $defaults as $field => $default ) {

			if ( array_key_exists( $field, $settings ) ) {
				$options[ $field ] = $settings[ $field ];
			}
			elseif ( isset( $default['default'] ) ) {
				$options[ $field ] = $default['default'];
			}

		}

		return apply_filters( 'atum/settings/get_settings', $options );

	}

	/**
	 * Enqueues scripts and styles needed for the Settings Page
	 *
	 * @since 0.0.2
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		if ( in_array( $hook, [ Globals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG, 'toplevel_page_' . self::UI_SLUG ] ) ) {

			wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', [], ATUM_VERSION );

			wp_register_style( self::UI_SLUG, ATUM_URL . 'assets/css/atum-settings.css', [ 'sweetalert2' ], ATUM_VERSION );

			wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', [], ATUM_VERSION, TRUE );
			wp_register_script( 'color-picker-alpha', ATUM_URL . 'assets/js/vendor/wp-color-picker-alpha.js', [ 'wp-color-picker' ], ATUM_VERSION, TRUE );
			Helpers::maybe_es6_promise();

			// ATUM marketing popup.
			AtumMarketingPopup::maybe_enqueue_scripts();

			wp_register_script( self::UI_SLUG, ATUM_URL . 'assets/js/build/atum-settings.js', [ 'jquery', 'sweetalert2', 'wp-color-picker', 'wp-hooks' ], ATUM_VERSION, TRUE );

			wp_localize_script( self::UI_SLUG, 'atumSettingsVars', array(
				'areYouSure'         => __( 'Are you sure?', ATUM_TEXT_DOMAIN ),
				'atumPrefix'         => ATUM_PREFIX,
				'branded'            => __( 'Branded', ATUM_TEXT_DOMAIN ),
				'cancel'             => __( 'Cancel', ATUM_TEXT_DOMAIN ),
				'colorSchemeNonce'   => wp_create_nonce( 'atum-color-scheme-nonce' ),
				'continue'           => __( "I don't want to save, Continue", ATUM_TEXT_DOMAIN ),
				'dark'               => __( 'Dark', ATUM_TEXT_DOMAIN ),
				'done'               => __( 'Done!', ATUM_TEXT_DOMAIN ),
				'error'              => __( 'Error!', ATUM_TEXT_DOMAIN ),
				'getColorScheme'     => 'atum_get_color_scheme',
				'highContrast'       => __( 'High Contrast', ATUM_TEXT_DOMAIN ),
				'isAnyOostSet'       => Helpers::is_any_out_stock_threshold_set(),
				'ok'                 => __( 'OK', ATUM_TEXT_DOMAIN ),
				'selectAll'          => __( 'Select All', ATUM_TEXT_DOMAIN ),
				'oostDisableAction'  => 'atum_disable_out_stock_threshold',
				'oostDisableNonce'   => wp_create_nonce( 'atum-out-stock-threshold-disable-nonce' ),
				'oostDisableText'    => __( "We are going to leave your saved values in your database in case you decide to re-enable the ATUM's Out of Stock threshold per product again. From now on, your system will start using the WooCommerce's global Out of Stock threshold value (if set).", ATUM_TEXT_DOMAIN ),
				'oostSetClearScript' => 'atum_tool_clear_out_stock_threshold',
				'oostSetClearText'   => __( "We did save all your previous 'Out of stock' values the last time you used this option. Would you like to clear all the saved data and to start fresh? If you've added new products since then, these will just use the global WooCommerce value (if set).", ATUM_TEXT_DOMAIN ),
				'removeAll'          => __( 'Remove All!', ATUM_TEXT_DOMAIN ),
				'removeRange'        => __( 'Remove Range!', ATUM_TEXT_DOMAIN ),
				'run'                => __( 'Run', ATUM_TEXT_DOMAIN ),
				'runnerNonce'        => wp_create_nonce( 'atum-script-runner-nonce' ),
				'selectColor'        => __( 'Select Color', ATUM_TEXT_DOMAIN ),
				'startFresh'         => __( 'Start Fresh', ATUM_TEXT_DOMAIN ),
				'useSavedValues'     => __( 'Use Saved Values', ATUM_TEXT_DOMAIN ),
				'unsavedData'        => __( "If you move to another section without saving, you'll lose the changes you made to this Settings section", ATUM_TEXT_DOMAIN ),
				'unselectAll'        => __( 'Unselect All', ATUM_TEXT_DOMAIN ),
			) );

			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( self::UI_SLUG );

			if ( is_rtl() ) {
				wp_register_style( self::UI_SLUG . '-rtl', ATUM_URL . 'assets/css/atum-settings-rtl.css', array( self::UI_SLUG ), ATUM_VERSION );
				wp_enqueue_style( self::UI_SLUG . '-rtl' );
			}

			// Load the ATUM colors.
			Helpers::enqueue_atum_colors( self::UI_SLUG );

			if ( wp_script_is( 'es6-promise', 'registered' ) ) {
				wp_enqueue_script( 'es6-promise' );
			}

			wp_enqueue_editor();
			wp_enqueue_media();
			wp_enqueue_script( 'color-picker-alpha' );
			wp_enqueue_script( self::UI_SLUG );

		}

	}

	/**
	 * Register the settings using WP's Settings API
	 *
	 * @since 0.0.2
	 */
	public function register_settings() {

		$countries         = WC()->countries;
		$default_country   = $countries->get_base_country();
		$default_city      = $countries->get_base_city();
		$default_adress    = $countries->get_base_address();
		$default_address_2 = $countries->get_base_address_2();
		$default_postcode  = $countries->get_base_postcode();

		$this->tabs = array(
			'general'       => array(
				'label'    => __( 'General', ATUM_TEXT_DOMAIN ),
				'icon'     => 'atmi-cog',
				'sections' => array(
					'general'     => __( 'General Options', ATUM_TEXT_DOMAIN ),
					'list_tables' => __( 'List Tables', ATUM_TEXT_DOMAIN ),
				),
			),
			'store_details' => array(
				'label'    => __( 'Store Details', ATUM_TEXT_DOMAIN ),
				'icon'     => 'atmi-store',
				'sections' => array(
					'company'  => __( 'Company info', ATUM_TEXT_DOMAIN ),
					'shipping' => __( 'Shipping info', ATUM_TEXT_DOMAIN ),
				),
			),
			'advanced'      => array(
				'label'    => __( 'Advanced', ATUM_TEXT_DOMAIN ),
				'icon'     => 'atmi-construction',
				'sections' => array(
					'advanced' => __( 'Advanced Options', ATUM_TEXT_DOMAIN ),
				),
			),
		);

		$this->defaults = array(
			'enable_admin_bar_menu'          => array(
				'group'   => 'general',
				'section' => 'general',
				'name'    => __( 'Enable admin bar menu', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'When enabled, the ATUM menu will be accessible through the WP admin bar.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'out_stock_threshold'            => array(
				'group'       => 'general',
				'section'     => 'general',
				'name'        => __( 'Out of stock threshold per product', ATUM_TEXT_DOMAIN ),
				'desc'        => __( "Activate the switch to disable WooCommerce's global out of stock threshold setting and enable ATUM's out of stock threshold per product. All products will inherit the WooCommerce's global value by default (if set).<br><br>
			                          Deactivate the switch to disable ATUM's out of stock threshold per product and re-enable the WooCommerce's global out of stock threshold. All your saved individual values will remain untouched in your database and ready for a future use, in case you decide to return to the individual control.<br><br>
				                      We have a specific tool to clear all the individual out of stock threshold values in the 'Tools' section.", ATUM_TEXT_DOMAIN ),
				'type'        => 'switcher',
				'default'     => 'no',
				'confirm_msg' => esc_attr( __( 'This will clear all the Out Stock Threshold values that have been set in all products', ATUM_TEXT_DOMAIN ) ),
			),
			'stock_quantity_decimals'        => array(
				'group'   => 'general',
				'section' => 'general',
				'name'    => __( 'Decimals in stock quantity', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Enter the number of decimal places your shop needs in stock quantity fields. Set 0 to keep or 1 and higher to override the default WooCommerce's NO decimal setting.", ATUM_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => 0,
				'options' => array(
					'min' => 0,
					'max' => 8,
				),
			),
			'stock_quantity_step'            => array(
				'group'   => 'general',
				'section' => 'general',
				'name'    => __( 'Stock quantity steps', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Tell WooCommerce how much to increase/decrease the stock quantity value in frontend with each arrow click. Example: If set to '0.5'; the stock will change from value '5' to value '5.5' when pressing the UP arrow. Pressing the DOWN arrow will reduce the stock to '4.5'.", ATUM_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => 0,
				'options' => array(
					'min'  => 0,
					'max'  => 1,
					'step' => 0.01,
				),
			),
			'chg_stock_order_complete'       => array(
				'group'   => 'general',
				'section' => 'general',
				'name'    => __( "Change stock on 'Completed' status", ATUM_TEXT_DOMAIN ),
				'desc'    => __( "When enabling this option, the products' stock will be discounted only when any WooCommerce order's status is changed to 'Completed'. Any other status won't alter the stocks.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'orders_search_by_sku'           => array(
				'group'   => 'general',
				'section' => 'general',
				'name'    => __( 'Orders search by SKU', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'When enabled, you can search by product SKU or supplier SKU and will return any order containing a product matching the specified term. Please, note that due to the complexity of this query, it could cause a delay in returning the searched results on dbs with many orders.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'enable_ajax_filter'             => array(
				'group'   => 'general',
				'section' => 'list_tables',
				'name'    => __( 'Enable filter autosearch', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "When enabled, the manual search button disappears. Disable this function if you don't use or find the automatic search feature helpful.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'enhanced_suppliers_filter'      => array(
				'group'   => 'general',
				'section' => 'list_tables',
				'name'    => __( "Enhanced suppliers' filter", ATUM_TEXT_DOMAIN ),
				'desc'    => __( "When enabled, the List Tables Suppliers' filter will be replaced by an advanced search box. Recommended for sites with many suppliers.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'show_totals'                    => array(
				'group'   => 'general',
				'section' => 'list_tables',
				'name'    => __( 'Show totals row', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'When enabled, ATUM will display new row at the bottom of all the List Tables. You will be able to preview page column totals of essential stock counters.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'gross_profit'                   => array(
				'group'   => 'general',
				'section' => 'list_tables',
				'name'    => __( 'Gross profit', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'Choose how to show up the gross profit column values in List Tables by default (the other value will show up on a tooltip when hovering each value).', ATUM_TEXT_DOMAIN ),
				'type'    => 'button_group',
				'default' => 'percentage',
				'options' => array(
					'values' => array(
						'percentage' => __( 'Percentage', ATUM_TEXT_DOMAIN ),
						'monetary'   => __( 'Monetary Value', ATUM_TEXT_DOMAIN ),
					),
				),
			),
			'profit_margin'                  => array(
				'group'   => 'general',
				'section' => 'list_tables',
				'name'    => __( 'Profit margin', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Your profit margin in percentage. We'll use this value to mark in red all the gross profit values that fall below this margin and in green all the values equal or greater than this margin.", ATUM_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => '50',
				'options' => array(
					'min'  => 0,
					'step' => 1,
				),
			),
			'show_variations_stock'          => array(
				'group'   => 'general',
				'section' => 'list_tables',
				'name'    => __( 'Display stock info for variations', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "When enabled, the variations' stock status and quantities will be displayed in the WooCommerce products' list (admin side) for variable products.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'unmanaged_counters'             => array(
				'group'   => 'general',
				'section' => 'list_tables',
				'name'    => __( 'Unmanaged product counters', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Add 'In Stock', 'Out of Stock' and 'Backorder' counters and views for Unmanaged by WooCommerce Products in all ATUM list tables. This option will also add these products to the Dashboard Stock Control Widget. Please note that enabling this option can affect the performance in stores with a large number of products.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'sales_last_ndays'               => array(
				'group'   => 'general',
				'section' => 'list_tables',
				'name'    => __( 'Show sales in the last selected days', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'Enter the number of days to calculate the number of sales in that period in ATUM List Tables.', ATUM_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => self::DEFAULT_SALE_DAYS,
				'options' => array(
					'min' => 1,
					'max' => 31,
				),
			),
			'company_name'                   => array(
				'group'   => 'store_details',
				'section' => 'company',
				'name'    => __( 'Company name', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Fill your company's name", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => '',
			),
			'tax_number'                     => array(
				'group'   => 'store_details',
				'section' => 'company',
				'name'    => __( 'Tax/VAT number', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Fill your company's Tax/VAT Number", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => '',
			),
			'address_1'                      => array(
				'group'   => 'store_details',
				'section' => 'company',
				'name'    => __( 'Address line 1', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "The company's street address", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => $default_adress,
			),
			'address_2'                      => array(
				'group'   => 'store_details',
				'section' => 'company',
				'name'    => __( 'Address line 2', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'Optional additional info for the address', ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => $default_address_2,
			),
			'city'                           => array(
				'group'   => 'store_details',
				'section' => 'company',
				'name'    => __( 'City', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'The city where your business is located', ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => $default_city,
			),
			'country'                        => array(
				'group'   => 'store_details',
				'section' => 'company',
				'name'    => __( 'Country/State', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'The country and state or province if any', ATUM_TEXT_DOMAIN ),
				'type'    => 'wc_country',
				'default' => $default_country,
			),
			'zip'                            => array(
				'group'   => 'store_details',
				'section' => 'company',
				'name'    => __( 'Postcode/ZIP', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'The postal code of your business', ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => $default_postcode,
			),
			'same_ship_address'              => array(
				'group'      => 'store_details',
				'section'    => 'company',
				'name'       => __( 'Use as shipping address', ATUM_TEXT_DOMAIN ),
				'desc'       => __( "When enabled, the shipping address will be the same that the company's address.", ATUM_TEXT_DOMAIN ),
				'type'       => 'switcher',
				'default'    => 'yes',
				'dependency' => array(
					'section' => 'shipping',
					'value'   => 'no',
				),
			),
			'ship_to'                        => array(
				'group'   => 'store_details',
				'section' => 'shipping',
				'name'    => __( 'Ship to name', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'The ship to name that will appear in the Shipping address', ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => '',
			),
			'ship_address_1'                 => array(
				'group'   => 'store_details',
				'section' => 'shipping',
				'name'    => __( 'Address line 1', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'The shipping street address', ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => $default_adress,
			),
			'ship_address_2'                 => array(
				'group'   => 'store_details',
				'section' => 'shipping',
				'name'    => __( 'Address line 2', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'Optional additional info for the Shipping Address', ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => $default_address_2,
			),
			'ship_city'                      => array(
				'group'   => 'store_details',
				'section' => 'shipping',
				'name'    => __( 'City', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'The city where is your Shipping address', ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => $default_city,
			),
			'ship_country'                   => array(
				'group'   => 'store_details',
				'section' => 'shipping',
				'name'    => __( 'Country/State', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'The country and state/province (if any)', ATUM_TEXT_DOMAIN ),
				'type'    => 'wc_country',
				'default' => $default_country,
			),
			'ship_zip'                       => array(
				'group'   => 'store_details',
				'section' => 'shipping',
				'name'    => __( 'Postcode/ZIP', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'The postal code of your Shipping address', ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => $default_postcode,
			),
			'calc_prop_cron'                 => array(
				'group'      => 'advanced',
				'section'    => 'advanced',
				'name'       => __( 'Calculated properties CRON', ATUM_TEXT_DOMAIN ),
				'desc'       => __( "When enabled, the products' calculated sales properties used on some ATUM List Tables columns (like Sales Last Days, Sold Today, etc) will be calculated in a scheduled way instead of calculating them after every order gets processed. Make sure your CRON jobs system is working before enabling this option or your calculated properties will show wrong values in ATUM List Tables.", ATUM_TEXT_DOMAIN ),
				'type'       => 'switcher',
				'default'    => 'no',
				'dependency' => array(
					array(
						'field'    => 'calc_prop_cron_interval',
						'value'    => 'yes',
						'animated' => FALSE,
					),
					array(
						'field'    => 'calc_prop_cron_type',
						'value'    => 'yes',
						'animated' => FALSE,
					),
					array(
						'field'    => 'calc_prop_cron_start',
						'value'    => 'yes',
						'animated' => FALSE,
					),
				),
			),
			'calc_prop_cron_interval'        => array(
				'group'   => 'advanced',
				'section' => 'advanced',
				'name'    => __( 'CRON interval', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'Specify the interval between cron executions. A maximum of 24 hours/60 minutes is allowed.', ATUM_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => 1,
				'options' => array(
					'min'  => 1,
					'max'  => 60,
					'step' => 0.1,
				),
			),
			'calc_prop_cron_type'            => array(
				'group'   => 'advanced',
				'section' => 'advanced',
				'name'    => __( 'CRON interval type', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'Choose the interval type between minutes and hours.', ATUM_TEXT_DOMAIN ),
				'type'    => 'button_group',
				'default' => 'hours',
				'options' => array(
					'values' => array(
						'hours'   => __( 'Hours', ATUM_TEXT_DOMAIN ),
						'minutes' => __( 'Minutes', ATUM_TEXT_DOMAIN ),
					),
				),
			),
			'calc_prop_cron_start'           => array(
				'group'   => 'advanced',
				'section' => 'advanced',
				'name'    => __( 'CRON start time', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'Choose the time when the CRON will be executed for the first time.', ATUM_TEXT_DOMAIN ),
				'type'    => 'time_picker',
				'default' => '0:00',
			),
			'delete_data'                    => array(
				'group'   => 'advanced',
				'section' => 'advanced',
				'name'    => __( 'Delete data when uninstalling', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'Enable before uninstalling to remove all the data stored by ATUM in your database. Not recommended if you plan to reinstall ATUM in the future.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'use_order_product_lookup_table' => array(
				'group'   => 'advanced',
				'section' => 'advanced',
				'name'    => __( "Use WooCommerce's order product lookup table", ATUM_TEXT_DOMAIN ),
				'desc'    => __( "We use the WooCommerce's order product lookup table in some queries to improve the performance. If you see any issues of calculated sales props not showing right values, perhaps your lookup tables aren't being updated correctly and you can disable this feature. But, please note that it could affect the ATUM List Tables' loading time.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
		);

		// Load the tools tab.
		Tools::get_instance();

		// Add the tabs (groups).
		$this->tabs = (array) apply_filters( 'atum/settings/tabs', $this->tabs );

		if ( ! Helpers::is_rest_request() && AtumCapabilities::current_user_can( 'manage_settings' ) ) {

			foreach ( $this->tabs as $tab => $tab_data ) {

				foreach ( $tab_data['sections'] as $section_key => $section_name ) {

					/* @noinspection PhpParamsInspection */
					add_settings_section(
						ATUM_PREFIX . "setting_{$section_key}",  // ID.
						$section_name,                           // Title.
						FALSE,                                   // Callback.
						ATUM_PREFIX . "setting_{$section_key}"   // Page.
					);

					// Register the settings.
					register_setting(
						ATUM_PREFIX . "setting_{$section_key}",  // Option group.
						self::OPTION_NAME,                       // Option name.
						array( $this, 'sanitize' )               // Sanitization callback.
					);

				}

			}

		}

		// Add the fields.
		$this->defaults = (array) apply_filters( 'atum/settings/defaults', $this->defaults );
		foreach ( $this->defaults as $field => $options ) {

			$options['id'] = $field;

			if ( ! Helpers::is_rest_request() && AtumCapabilities::current_user_can( 'manage_settings' ) ) {
				add_settings_field(
					$field,                                             // ID.
					$options['name'],                                   // Title.
					array( $this, "display_{$options['type']}" ),      // Callback.
					ATUM_PREFIX . "setting_{$options['section']}",     // Page.
					ATUM_PREFIX . "setting_{$options['section']}",     // Section.
					$options
				);
			}

			// Register the fields that must be saved as user meta.
			if ( ! empty( $options['to_user_meta'] ) ) {

				if ( ! isset( $this->user_meta_options[ $options['to_user_meta'] ] ) ) {
					$this->user_meta_options[ $options['to_user_meta'] ] = array();
				}

				$this->user_meta_options[ $options['to_user_meta'] ][] = $field;
			}

		}

	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @since 0.0.2
	 *
	 * @param array $input Contains all settings fields as array keys.
	 *
	 * @return array
	 */
	public function sanitize( $input ) {

		$this->options = Helpers::get_options();

		// If it's the first time the user saves the settings, perhaps he doesn't have any, so save the defaults.
		if ( empty( $this->options ) || ! is_array( $this->options ) ) {

			// Remove the settings without defaults.
			$defaults = array_filter( $this->options, function ( $option ) {

				return isset( $option['default'] );
			} );

			$this->options = wp_list_pluck( $defaults, 'default' );

			// Avoid infinite loop calling this method.
			remove_filter( 'sanitize_option_' . self::OPTION_NAME, array( $this, 'sanitize' ) );
			update_option( self::OPTION_NAME, $this->options );

		}

		// Remove deprecated/removed/unneeded keys.
		$valid_keys = array_keys( $this->defaults );

		foreach ( $this->options as $option_key => $option_value ) {
			if ( ! in_array( $option_key, $valid_keys ) ) {
				unset( $this->options[ $option_key ] );
			}
		}

		if ( isset( $input['settings_section'] ) ) {

			// Save the the user meta options and exclude them from global settings.
			if ( ! empty( $this->user_meta_options[ $input['settings_section'] ] ) ) {

				$user_meta_options = $this->user_meta_options[ $input['settings_section'] ];
				$user_options      = array();

				foreach ( $user_meta_options as $user_meta_option ) {
					$user_options[ $user_meta_option ] = $this->sanitize_option( $user_meta_option, $input, $this->defaults[ $user_meta_option ] );
					unset( $this->options[ $user_meta_option ] ); // Don't save the user meta on global options.
				}

				Helpers::set_atum_user_meta( $input['settings_section'], $user_options );
			}

			// Only accept settings defined.
			foreach ( $this->defaults as $key => $atts ) {

				// Save only current section.
				if (
					! empty( $this->tabs[ $input['settings_section'] ] ) &&
					in_array( $atts['section'], array_keys( $this->tabs[ $input['settings_section'] ]['sections'] ) )
				) {

					// Don't save.
					if ( 'html' === $this->defaults[ $key ]['type'] && array_key_exists( $key, $this->options ) ) {
						unset( $this->options[ $key ] );
					}

					// Remove transients if this config changes.
					if ( ( 'sale_days' === $key || 'sales_last_ndays' === $key ) && $input[ $key ] !== $this->options[ $key ] ) {
						AtumCache::delete_transients();
					}

					$this->options[ $key ] = $this->sanitize_option( $key, $input, $atts );

				}
			}

			if ( ! empty( $this->options['calc_prop_cron'] ) && 'yes' === $this->options['calc_prop_cron'] ) {

				if ( 'hours' === $this->options['calc_prop_cron_type'] ) {

					if ( 24 < $this->options['calc_prop_cron_interval'] ) {
						$this->options['calc_prop_cron_interval'] = 24;
					}
				}
				elseif ( 60 < $this->options['calc_prop_cron_interval'] ) {
					$this->options['calc_prop_cron_interval'] = 60;
				}
			}

		}

		return apply_filters( 'atum/settings/sanitize', $this->options );

	}

	/**
	 * Sanitize an option before saving
	 *
	 * @since 1.5.9
	 *
	 * @param string $key
	 * @param array  $input
	 * @param array  $atts
	 *
	 * @return mixed|\WP_Error
	 */
	public function sanitize_option( $key, $input, $atts ) {

		// Calling to this method from the ATUM API, needs to return an error instead of setting the default value.
		$is_api_request = Helpers::is_rest_request();
		$field_type     = $this->defaults[ $key ]['type'];

		switch ( $field_type ) {

			case 'multi_checkbox':
				if ( $is_api_request && ! is_array( $input[ $key ] ) ) {
					return new \WP_Error( 'atum_rest_setting_value_invalid', __( 'An invalid setting value was passed.', ATUM_TEXT_DOMAIN ), [ 'status' => 400 ] );
				}
				$option = $input[ $key ];

				$option['value'] = ( isset( $option['value'] ) && 'yes' === $option['value'] ) ? 'yes' : 'no';

				if ( isset( $atts['default_options'] ) ) {
					foreach ( $atts['default_options'] as $index => $value ) {
						$option['options'][ $index ] = ( isset( $option['options'][ $index ] ) && 'yes' === $option['options'][ $index ] ) ? 'yes' : 'no';
					}
				}

				$sanitized_option = $option;

				break;
			case 'switcher':
				if ( $is_api_request && ! in_array( $input[ $key ], [ 'yes', 'no' ], TRUE ) ) {
					return new \WP_Error( 'atum_rest_setting_value_invalid', __( 'An invalid setting value was passed.', ATUM_TEXT_DOMAIN ), [ 'status' => 400 ] );
				}

				$sanitized_option = ( isset( $input[ $key ] ) && 'yes' === $input[ $key ] ) ? 'yes' : 'no';
				break;

			case 'number':
				if ( isset( $input[ $key ] ) && ! empty( $atts['options'] ) ) {

					$value = floatval( $input[ $key ] );

					// Check min and max allowed values.
					if (
						( isset( $atts['options']['min'] ) && $value < $atts['options']['min'] ) ||
						( isset( $atts['options']['max'] ) && $value > $atts['options']['max'] )
					) {

						if ( $is_api_request ) {
							return new \WP_Error( 'atum_rest_setting_value_invalid', __( 'An invalid setting value was passed.', ATUM_TEXT_DOMAIN ), [ 'status' => 400 ] );
						}
						else {
							return $atts['default'];
						}

					}

				}

				$sanitized_option = isset( $input[ $key ] ) ? floatval( $input[ $key ] ) : $atts['default'];
				break;

			case 'select':
			case 'wc_country':
				if ( $is_api_request && ! in_array( $input[ $key ], array_keys( $atts['options']['values'] ) ) ) {
					return new \WP_Error( 'atum_rest_setting_value_invalid', __( 'An invalid setting value was passed.', ATUM_TEXT_DOMAIN ), [ 'status' => 400 ] );
				}

				$sanitized_option = $atts['default'];

				if ( isset( $input[ $key ] ) ) {

					// wc_country field.
					if ( 'wc_country' === $field_type ) {

						if ( strpos( $input[ $key ], ':' ) !== FALSE ) {

							$country_states          = WC()->countries->get_allowed_country_states();
							list( $country, $state ) = explode( ':', $input[ $key ] );

							if ( isset( $country_states[ $country ], $country_states[ $country ][ $state ] ) ) {
								$sanitized_option = $input[ $key ];
							}

						}
						elseif ( in_array( $input[ $key ], array_keys( WC()->countries->get_countries() ) ) ) {
							$sanitized_option = $input[ $key ];
						}

					}
					// select field.
					elseif ( in_array( $input[ $key ], array_keys( $atts['options']['values'] ) ) ) {
						$sanitized_option = $input[ $key ];
					}

				}

				break;

			case 'button_group':
				$default_values = array_keys( $atts['options']['values'] );

				// The button groups could allow multiple values (multi-checkboxes).
				if ( ! empty( $atts['options']['multiple'] ) && TRUE === $atts['options']['multiple'] ) {

					$values = array();

					foreach ( $default_values as $default_value ) {

						// Save always the required value as checked.
						if ( isset( $atts['options']['required_value'] ) && $atts['options']['required_value'] === $default_value ) {
							$values[ $default_value ] = 'yes';
						}
						else {

							if ( $is_api_request && ! in_array( $default_value, array_keys( $input[ $key ] ) ) ) {
								return new \WP_Error( 'atum_rest_setting_value_invalid', __( 'An invalid setting value was passed.', ATUM_TEXT_DOMAIN ), [ 'status' => 400 ] );
							}

							$values[ $default_value ] = (
								isset( $input[ $key ] ) && is_array( $input[ $key ] ) &&
								in_array( $default_value, $input[ $key ] )
							) ? 'yes' : 'no';

						}

					}

					$sanitized_option = maybe_serialize( $values );

				}
				else {

					if ( $is_api_request && ! in_array( $input[ $key ], $default_values ) ) {
						return new \WP_Error( 'atum_rest_setting_value_invalid', __( 'An invalid setting value was passed.', ATUM_TEXT_DOMAIN ), [ 'status' => 400 ] );
					}

					$sanitized_option = ( isset( $input[ $key ] ) && in_array( $input[ $key ], $default_values ) ) ? $input[ $key ] : $atts['default'];

				}

				break;

			case 'textarea':
				$sanitized_option = isset( $input[ $key ] ) ? wp_kses(
					trim( stripslashes( $input[ $key ] ) ),
					array_merge(
						array(
							'iframe' => array(
								'src'   => TRUE,
								'style' => TRUE,
								'id'    => TRUE,
								'class' => TRUE,
							),
						),
						wp_kses_allowed_html( 'post' )
					)
				) : $atts['default'];
				break;

			case 'color':
				if ( $is_api_request && ! Helpers::validate_color( $input[ $key ] ) ) {
					return new \WP_Error( 'atum_rest_setting_value_invalid', __( 'An invalid setting value was passed.', ATUM_TEXT_DOMAIN ), [ 'status' => 400 ] );
				}

				$sanitized_option = ( ! empty( $input[ $key ] ) && Helpers::validate_color( $input[ $key ] ) ) ? $input[ $key ] : $atts['default'];
				break;

			case 'theme_selector':
				if ( $is_api_request && ! in_array( $input[ $key ], wp_list_pluck( $atts['options']['values'], 'key' ) ) ) {
					return new \WP_Error( 'atum_rest_setting_value_invalid', __( 'An invalid setting value was passed.', ATUM_TEXT_DOMAIN ), [ 'status' => 400 ] );
				}

				$sanitized_option = ( isset( $input[ $key ] ) && in_array( $input[ $key ], wp_list_pluck( $atts['options']['values'], 'key' ) ) ) ? $input[ $key ] : $atts['default'];
				break;

			case 'editor':
				$sanitized_option = isset( $input[ $key ] ) ? wp_kses_post( $input[ $key ] ) : $atts['default'];
				break;

			case 'text':
			default:
				$sanitized_option = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $atts['default'];
				break;

		}

		// Check if there is any special validation for this field.
		if ( ! empty( $atts['validation'] ) && is_callable( $atts['validation'] ) ) {
			$sanitized_option = call_user_func( $atts['validation'], $sanitized_option );
		}

		return $sanitized_option;

	}

	/**
	 * Get the settings option array and print a text field
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Field arguments.
	 */
	public function display_text( $args ) {

		$placeholder = isset( $args['options']['placeholder'] ) ? $args['options']['placeholder'] : '';
		$default     = isset( $args['default'] ) ? " data-default='" . $args['default'] . "'" : '';

		$output = sprintf(
			'<input class="atum-settings-input regular-text" type="text" id="%1$s" name="%2$s" placeholder="%3$s" value="%4$s" %5$s>',
			ATUM_PREFIX . $args['id'],
			self::OPTION_NAME . "[{$args['id']}]",
			$placeholder,
			$this->find_option_value( $args['id'] ),
			$this->get_dependency( $args ) . $default
		) . $this->get_description( $args );

		echo apply_filters( 'atum/settings/display_text', $output, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings option array and print a textarea
	 *
	 * @since 1.4.11
	 *
	 * @param array $args Field arguments.
	 */
	public function display_textarea( $args ) {

		$default = isset( $args['default'] ) ? " data-default='" . $args['default'] . "'" : '';
		$rows    = isset( $args['rows'] ) ? absint( $args['rows'] ) : 4;
		$cols    = isset( $args['cols'] ) ? ' cols="' . absint( $args['cols'] ) . '"' : '';

		$output = sprintf(
			'<textarea class="atum-settings-input regular-text" type="text" id="%1$s" rows="%2$d"%3$d name="%4$s" %5$s>%6$s</textarea>',
			ATUM_PREFIX . $args['id'],
			$rows,
			$cols,
			self::OPTION_NAME . "[{$args['id']}]",
			$this->get_dependency( $args ) . $default,
			$this->find_option_value( $args['id'] )
		) . $this->get_description( $args );

		echo apply_filters( 'atum/settings/display_textarea', $output, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings option array and print a TinyMCE editor
	 *
	 * @since 1.9.5
	 *
	 * @param array $args Field arguments.
	 */
	public function display_editor( $args ) {

		// TODO: ALLOW SPECIFYING THE EDITOR OPTIONS FROM THE SETTING CONFIG.
		$editor_settings = array(
			'media_buttons' => FALSE,
			'editor_height' => 225,
			'textarea_name' => self::OPTION_NAME . "[{$args['id']}]",
			'tinymce'       => array( 'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,forecolor,undo,redo' ),
		);

		ob_start();

		echo '<div class="atum-settings-editor" data-tiny-mce=\'' . wp_json_encode( $editor_settings['tinymce'] ) . '\'' . $this->get_dependency( $args ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_editor( $this->find_option_value( $args['id'] ), ATUM_PREFIX . $args['id'], $editor_settings );
		echo $this->get_description( $args ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo apply_filters( 'atum/settings/display_editor', ob_get_clean(), $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings option array and print a number field
	 *
	 * @since 0.0.2
	 *
	 * @param array $args Field arguments.
	 */
	public function display_number( $args ) {

		$step    = isset( $args['options']['step'] ) ? $args['options']['step'] : 1;
		$min     = isset( $args['options']['min'] ) ? $args['options']['min'] : 1;
		$max     = isset( $args['options']['max'] ) ? $args['options']['max'] : '';
		$default = isset( $args['default'] ) ? " data-default='" . $args['default'] . "'" : '';

		$output = sprintf(
			'<input class="atum-settings-input" type="number" min="%1$s" max="%2$s" step="%3$s" id="%4$s" name="%5$s" value="%6$s" %7$s>',
			$min,
			$max,
			$step,
			ATUM_PREFIX . $args['id'],
			self::OPTION_NAME . "[{$args['id']}]",
			$this->find_option_value( $args['id'] ),
			$this->get_dependency( $args ) . $default
		) . $this->get_description( $args );

		echo apply_filters( 'atum/settings/display_number', $output, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get a dropdow of countries registered in WC
	 *
	 * @since 1.3.1
	 *
	 * @param array $args Field arguments.
	 */
	public function display_wc_country( $args ) {

		$country_setting = (string) $this->options[ $args['id'] ];
		$default         = isset( $args['default'] ) ? " data-default='" . $args['default'] . "'" : '';

		if ( strstr( $country_setting, ':' ) ) {
			$country_setting = explode( ':', $country_setting );
			$country         = current( $country_setting );
			$state           = end( $country_setting );
		}
		else {
			$country = $country_setting;
			$state   = '*';
		}

		ob_start();
		?>
		<select id="<?php echo esc_attr( ATUM_PREFIX . $args['id'] ) ?>"
			name="<?php echo esc_attr( self::OPTION_NAME . "[{$args['id']}]" ) ?>"
			style="width: 25em"<?php echo wp_kses_post( $this->get_dependency( $args ) . $default ) ?>
		>
			<?php WC()->countries->country_dropdown_options( $country, $state ); ?>
		</select>
		<?php

		$output = ob_get_clean() . wp_kses_post( $this->get_description( $args ) );

		echo apply_filters( 'atum/settings/display_wc_country', $output, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings option array and prints a switcher
	 *
	 * @since 0.0.2
	 *
	 * @param array $args Field arguments.
	 */
	public function display_switcher( $args ) {

		$default = isset( $args['default'] ) ? " data-default='" . $args['default'] . "'" : '';

		$output = sprintf(
			'<span class="form-switch"><input type="checkbox" id="%1$s" name="%2$s" value="yes" %3$s class="form-check-input atum-settings-input" %4$s /></span>',
			ATUM_PREFIX . $args['id'],
			self::OPTION_NAME . "[{$args['id']}]",
			checked( 'yes', $this->find_option_value( $args['id'] ), FALSE ),
			$this->get_dependency( $args ) . $default
		) . $this->get_description( $args );

		echo apply_filters( 'atum/settings/display_switcher', $output, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings option array and prints a switcher with a multi-checkbox
	 *
	 * @since 1.7.1
	 *
	 * @param array $args Field arguments.
	 */
	public function display_multi_checkbox( $args ) {

		$data_default  = isset( $args['default'] ) ? " data-default='" . $args['default'] . "'" : '';
		$stored_values = $this->find_option_value( $args['id'] );

		$default_checked = 'yes' === $args['default'] ? 'checked' : '';

		if ( isset( $args['main_switcher'] ) && $args['main_switcher'] ) {

			$enabled = ! empty( $stored_values['value'] ) ? checked( 'yes', $stored_values['value'], FALSE ) : $default_checked;
			$output  = sprintf(
				'<span class="form-switch"><input type="checkbox" id="%1$s" name="%2$s" value="yes" %3$s class="form-check-input atum-settings-input atum-multi-checkbox-main" %4$s></span>',
				ATUM_PREFIX . $args['id'],
				self::OPTION_NAME . "[{$args['id']}][value]",
				$enabled,
				$this->get_dependency( $args ) . $data_default
			) . $this->get_description( $args );

		}
		else {
			$enabled = TRUE;
			$output  = $this->get_description( $args, 'no-padding' );
		}

		$checkboxes = isset( $stored_values['options'] ) ? $stored_values['options'] : [];
		$check_defs = isset( $args['default_options'] ) ? $args['default_options'] : [];

		if ( ! empty( $check_defs ) ) {

			$output .= '<div class="atum-settings-multi-checkbox" style="display: ' . ( $enabled ? 'block' : 'none' ) . '">';

			foreach ( $check_defs as $id => $checkbox ) {

				$default_attr    = isset( $checkbox['value'] ) ? " data-default='" . $checkbox['value'] . "'" : '';
				$default_checked = ( isset( $checkbox['value'] ) && 'yes' === $checkbox['value'] ) ? 'checked' : '';
				$checked         = ( ! empty( $checkboxes ) && isset( $checkboxes[ $id ] ) ) ? checked( 'yes', $checkboxes[ $id ], FALSE ) : $default_checked;

				$output .= '<div class="atum-multi-checkbox-option' . ( $checked ? ' setting-checked' : '' ) . '"><label>';
				$output .= sprintf(
					'<input type="checkbox" id="%1$s" name="%2$s" value="yes" %3$s class="atum-settings-input" %4$s>',
					ATUM_PREFIX . $id,
					self::OPTION_NAME . "[{$args['id']}][options][{$id}]",
					$checked,
					$default_attr
				);
				$output .= ' ' . esc_html( $checkbox['name'] ) . '</label>';

				if ( ! empty( $checkbox['desc'] ) ) {
					$output .= ' <span class="atum-help-tip tips" data-bs-placement="top" data-tip="' . esc_attr( $checkbox['desc'] ) . '"></span>';
				}

				$output .= '</div>';
			}

			$output .= '</div>';

		}

		echo apply_filters( 'atum/settings/display_multi_checkbox', $output, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings option array and prints a button group.
	 * To use it with checkbox set options['type'] to checkbox & options['multiple'] to [].
	 *
	 * @since 1.4.11
	 *
	 * @param array $args Field arguments.
	 */
	public function display_button_group( $args ) {

		$id             = ATUM_PREFIX . $args['id'];
		$name           = self::OPTION_NAME . "[{$args['id']}]";
		$multiple       = isset( $args['options']['multiple'] ) ? $args['options']['multiple'] : ''; // allow to send array.
		$value          = $multiple ? maybe_unserialize( $this->find_option_value( $args['id'] ) ) : $this->find_option_value( $args['id'] );
		$style          = isset( $args['options']['style'] ) ? $args['options']['style'] : 'secondary';
		$size           = isset( $args['options']['size'] ) ? $args['options']['size'] : 'sm';
		$input_type     = isset( $args['options']['input_type'] ) ? $args['options']['input_type'] : 'radio';
		$required_value = isset( $args['options']['required_value'] ) ? $args['options']['required_value'] : '';

		$default = '';
		if ( isset( $args['default'] ) ) {
			$default = is_array( $args['default'] ) ? wp_json_encode( $args['default'] ) : $args['default'];
			$default = " data-default='" . $default . "'";
		}

		ob_start();
		?>
		<div class="btn-group btn-group-<?php echo esc_attr( $size ) ?> btn-group-toggle" id="<?php echo esc_attr( $id ) ?>">
			<?php foreach ( $args['options']['values'] as $option_value => $option_label ) : ?>

				<?php
				if ( $multiple && is_array( $value ) ) :
					$is_active = in_array( $option_value, array_keys( $value ) ) && 'yes' === $value[ $option_value ];
				else :
					$is_active = $value === $option_value;
				endif;

				$disabled_str = $checked_str = '';

				// Force checked disabled and active on required value.
				// TODO required_value to required_values array.
				if ( $option_value === $required_value ) :
					$checked_str  = checked( TRUE, TRUE, FALSE );
					$disabled_str = ' disabled="disabled"';
					$is_active    = TRUE;
				else :
					$checked_str = checked( $is_active, TRUE, FALSE );
				endif;
				?>
				<label class="btn btn-<?php echo esc_attr( $style ) ?><?php if ( $is_active )echo ' active' ?>">
					<input class="multi-<?php echo esc_attr( $input_type ) ?>" type="<?php echo esc_attr( $input_type ) ?>" name="<?php echo esc_attr( $name ) ?><?php if ( $multiple )echo '[]' ?>"
						autocomplete="off"<?php echo wp_kses_post( $checked_str . $disabled_str ) ?> value="<?php echo esc_attr( $option_value ) ?>"
						<?php echo wp_kses_post( $this->get_dependency( $args ) . $default ) ?>> <?php echo esc_attr( $option_label ) ?>
				</label>

			<?php endforeach; ?>
		</div>
		<?php

		echo wp_kses_post( $this->get_description( $args ) );

		echo apply_filters( 'atum/settings/display_button_group', ob_get_clean(), $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings option array and prints select dropdown
	 *
	 * @since 1.4.9
	 *
	 * @param array $args Field arguments.
	 */
	public function display_select( $args ) {

		$id      = ATUM_PREFIX . $args['id'];
		$name    = self::OPTION_NAME . "[{$args['id']}]";
		$value   = $this->find_option_value( $args['id'] );
		$style   = isset( $args['options']['style'] ) ? ' style="' . $args['options']['style'] . '"' : '';
		$default = isset( $args['default'] ) ? " data-default='" . $args['default'] . "'" : '';

		ob_start();
		?>
		<select name="<?php echo esc_attr( $name ) ?>" id="<?php echo esc_attr( $id ) ?>"
			<?php echo wp_kses_post( $this->get_dependency( $args ) . $default . $style ) ?>>

			<?php foreach ( $args['options']['values'] as $option_value => $option_label ) : ?>
				<option value="<?php echo esc_attr( $option_value ) ?>"<?php selected( $option_value, $value ) ?>><?php echo esc_attr( $option_label ) ?></option>
			<?php endforeach; ?>
		</select>
		<?php

		echo wp_kses_post( $this->get_description( $args ) );
		echo apply_filters( 'atum/settings/display_select', ob_get_clean(), $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings option array and prints an script runner field
	 *
	 * @since 1.4.5
	 *
	 * @param array $args Field arguments.
	 */
	public function display_script_runner( $args ) {

		ob_start();
		?>
		<div class="script-runner<?php if ( ! empty( $args['options']['wrapper_class'] ) ) echo esc_attr( " {$args['options']['wrapper_class']}" ) ?><?php if ( ! empty( $args['options']['is_recurrent'] ) ) echo ' recurrent'; ?>"
			data-action="<?php echo esc_attr( $args['options']['script_action'] ) ?>" data-input="<?php echo esc_attr( $args['id'] ) ?>"
			<?php if ( ! empty( $args['options']['confirm_msg'] ) ) :
				echo 'data-confirm="' . esc_attr( $args['options']['confirm_msg'] ) . '"';
			endif;
			if ( ! empty( $args['options']['processing_msg'] ) ) :
				echo 'data-processing="' . esc_attr( $args['options']['processing_msg'] ) . '"';
			endif;
			if ( ! empty( $args['options']['processed_msg'] ) ) :
				echo 'data-processed="' . esc_attr( $args['options']['processed_msg'] ) . '"';
			endif; ?>>

			<?php do_action( 'atum/settings/before_script_runner_field', $args ) ?>

			<?php if ( isset( $args['options']['select'] ) ) : ?>
				<div class="atum-select2-container">
					<select style="width: 12em" id="<?php echo esc_attr( $args['id'] ) ?>">
						<?php foreach ( $args['options']['select'] as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ) ?>"><?php echo esc_attr( $label ) ?></option>
						<?php endforeach ?>
					</select>
					&nbsp;
				</div>
			<?php endif; ?>

			<?php if ( isset( $args['options']['number'] ) ) :
				$value = isset( $args['options']['number']['default'] ) ? $args['options']['number']['default'] : 1;
				?>
				<input class="atum-settings-input" type="number" min="1" max="100000" step="1" id="<?php echo esc_attr( $args['id'] ) ?>" value="<?php echo (int) $value ?>">
			<?php endif; ?>

			<button type="button" class="btn btn-<?php echo esc_attr( isset( $args['options']['button_style'] ) ? $args['options']['button_style'] : 'primary' ) ?> tool-runner"
				<?php if ( isset( $args['options']['button_status'] ) && 'disabled' === $args['options']['button_status'] )
					echo ' disabled="disabled"' ?>>
				<?php echo esc_attr( $args['options']['button_text'] ) ?>
			</button>

			<?php do_action( 'atum/settings/after_script_runner_field', $args ) ?>

		</div>
		<?php

		$output = ob_get_clean() . wp_kses_post( $this->get_description( $args ) );
		echo apply_filters( 'atum/settings/display_script_runner', $output, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings option array and prints color picker
	 *
	 * @since 1.4.13
	 *
	 * @param array $args Field arguments.
	 */
	public function display_color( $args ) {

		$id      = ATUM_PREFIX . $args['id'];
		$name    = self::OPTION_NAME . "[{$args['id']}]";
		$value   = $this->find_option_value( $args['id'] );
		$style   = isset( $args['options']['style'] ) ? ' style="' . esc_attr( $args['options']['style'] ) . '"' : '';
		$default = isset( $args['default'] ) ? " data-default='" . esc_attr( $args['default'] ) . "'" : '';
		$display = isset( $args['display'] ) ? str_replace( '_', '-', $args['display'] ) : '';

		ob_start();
		?>
		<input class="atum-settings-input atum-color" data-display="<?php echo esc_attr( $display ) ?>"
			data-alpha="true" name="<?php echo esc_attr( $name ) ?>" id="<?php echo esc_attr( $id ) ?>"
			type="text" value="<?php echo esc_attr( $value ) ?>" <?php echo $default . $style // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
		<?php

		echo wp_kses_post( $this->get_description( $args ) );
		echo apply_filters( 'atum/settings/display_color', ob_get_clean(), $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings HTML field
	 *
	 * @since 1.4.15
	 *
	 * @param array $args Field arguments.
	 */
	public function display_html( $args ) {

		$id    = ATUM_PREFIX . $args['id'];
		$value = $this->options[ $args['id'] ];
		$style = isset( $args['options']['style'] ) ? ' style="' . $args['options']['style'] . '"' : '';

		ob_start();
		?>
		<div id="<?php echo esc_attr( $id ) ?>" class="atum-settings-html"<?php echo esc_attr( $style ) ?>>
			<?php echo $value // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php

		echo apply_filters( 'atum/settings/display_html', ob_get_clean(), $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings theme selector.
	 *
	 * @since 1.5.9
	 *
	 * @param array $args Field arguments.
	 */
	public function display_theme_selector( $args ) {

		$id    = ATUM_PREFIX . $args['id'];
		$name  = self::OPTION_NAME . "[{$args['id']}]";
		$theme = AtumColors::get_user_theme();

		ob_start();
		?>
		<div class="theme-selector-wrapper" id="<?php echo esc_attr( $id ) ?>">

			<?php foreach ( $args['options']['values'] as $option ) : ?>

				<input type="radio" id="<?php echo esc_attr( $option['key'] ); ?>" name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $option['key'] ); ?>"
					<?php echo ! $theme && 'branded_mode' === $option['key'] || $theme === $option['key'] ? 'checked' : ''; ?>>

				<div class="selector-container">
					<div class="selector-box" data-value="<?php echo esc_attr( $option['key'] ); ?>" data-reset="0">
						<img src="<?php echo esc_attr( ATUM_URL . 'assets/images/settings/' . $option['thumb'] ); ?>" alt=""
							class="<?php echo ! $theme && 'branded_mode' === $option['key'] || $theme === $option['key'] ? ' active' : ''; ?>">
					</div>

					<div class="selector-description">
						<div><?php echo esc_attr( $option['name'] ); ?></div>
						<p class="atum-setting-info"><?php echo esc_attr( $option['desc'] ); ?></p>
					</div>
				</div>

			<?php endforeach; ?>

		</div>
		<?php
		echo wp_kses_post( $this->get_description( $args ) );

		echo apply_filters( 'atum/settings/display_theme_selector', ob_get_clean(), $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Gets an image uploader field
	 *
	 * @since 1.8.2
	 *
	 * @param array $args Field arguments.
	 */
	public function display_image_uploader( $args ) {

		$id            = ATUM_PREFIX . $args['id'];
		$name          = self::OPTION_NAME . "[{$args['id']}]";
		$attachment_id = absint( $this->find_option_value( $args['id'] ) );
		$data          = '';

		if ( ! empty( $args['options'] ) ) {

			foreach ( $args['options'] as $key => $value ) {
				$data .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}

		}

		ob_start();
		?>
		<div class="atum-file-uploader__wrapper" id="<?php echo esc_attr( $id ) ?>">
			<button type="button" class="atum-file-uploader btn btn-primary"<?php echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php esc_html_e( 'Upload', ATUM_TEXT_DOMAIN ); ?>
			</button>

			<?php if ( $attachment_id ) :
				$image_url = wp_get_attachment_image_url( $attachment_id );

				if ( $image_url ) : ?>
					<img class="atum-file-uploader__preview" src="<?php echo esc_url( $image_url ) ?>">
				<?php endif; ?>
			<?php endif; ?>

			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $attachment_id ); ?>">
		</div>
		<?php

		echo wp_kses_post( $this->get_description( $args ) );

		echo apply_filters( 'atum/settings/display_image_uploader', ob_get_clean(), $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the settings option array and prints an image radio selector.
	 *
	 * @since 1.9.1
	 *
	 * @param array $args Field arguments.
	 */
	public function display_image_selector( $args ) {

		$name  = self::OPTION_NAME . "[{$args['id']}]";
		$value = $this->find_option_value( $args['id'] );

		$default = '';
		if ( isset( $args['default'] ) ) {
			$default = is_array( $args['default'] ) ? wp_json_encode( $args['default'] ) : $args['default'];
			$default = " data-default='" . $default . "'";
		}

		ob_start();
		?>
		<div class="atum-image-selector" id="<?php echo ATUM_PREFIX . $args['id']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
			<?php foreach ( $args['options']['values'] as $option_value => $option_data ) : ?>

				<?php
				$is_active   = $value === $option_value;
				$checked_str = checked( $is_active, TRUE, FALSE );
				?>
				<label class="atum-image-radio<?php if ( $is_active )echo ' active' ?>">
					<img src="<?php echo esc_url( $option_data['img_url'] ) ?>" alt="">
					<input type="radio" name="<?php echo esc_attr( $name ) ?>"
						autocomplete="off"<?php echo wp_kses_post( $checked_str ) ?> value="<?php echo esc_attr( $option_value ) ?>"
						<?php echo wp_kses_post( $this->get_dependency( $args ) . $default ) ?>
					>

					<div class="atum-image-radio__label">
						<?php echo esc_attr( $option_data['label'] ) ?>
					</div>
				</label>

			<?php endforeach; ?>
		</div>
		<?php

		echo wp_kses_post( $this->get_description( $args ) );

		echo apply_filters( 'atum/settings/display_image_selector', ob_get_clean(), $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Print field description if it exists
	 *
	 * @since 0.0.2
	 *
	 * @param array  $args
	 * @param string $extra_class
	 *
	 * @return string
	 */
	public function get_description( $args, $extra_class = '' ) {

		$label = '';

		if ( array_key_exists( 'desc', $args ) ) {
			$label = '<div class="atum-setting-info ' . $extra_class . '">' . apply_filters( 'atum/settings/print_label', $args['desc'], $args ) . '</div>';
		}

		return $label;
	}

	/**
	 * Get the dependency data (if any)
	 *
	 * @since 1.4.8
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_dependency( $args ) {

		if ( isset( $args['dependency'] ) ) {
			return " data-dependency='" . wp_json_encode( $args['dependency'] ) . "'";
		}

		return '';

	}


	/**
	 * Get the settings option array and prints a time picker.
	 *
	 * @since 1.9.7
	 *
	 * @param array $args Field arguments.
	 */
	public function display_time_picker( $args ) {

		$id    = ATUM_PREFIX . $args['id'];
		$name  = self::OPTION_NAME . "[{$args['id']}]";
		$value = $this->find_option_value( $args['id'] );

		$default = '';
		if ( isset( $args['default'] ) ) {
			$default = $args['default'];
			$default = " data-default='" . $default . "'";
		}

		ob_start();
		?>
		<div class="date-wrapper" style="position: relative">
			<input type="text" id="<?php echo esc_attr( $id ) ?>" name="<?php echo esc_attr( $name ) ?>" class="atum-datepicker" placeholder="<?php esc_attr_e( 'Select time', ATUM_TEXT_DOMAIN ) ?>"
				value="<?php echo esc_attr( $this->find_option_value( $args['id'] ) ); ?>" data-format="HH:mm" data-min-date="false">
		</div>
		<?php
		echo apply_filters( 'atum/settings/display_time_picker', ob_get_clean(), $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Getter for the tabs (groups) prop
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_groups() {

		return $this->tabs;
	}

	/**
	 * Getter for the defaults prop
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_default_settings() {

		return $this->defaults;
	}

	/**
	 * Getter for the user_meta_options prop
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_user_meta_options() {

		return $this->user_meta_options;
	}

	/**
	 * Setter for the user_meta_options prop
	 *
	 * @since 1.6.2
	 *
	 * @param array $user_meta_options
	 */
	public function set_user_meta_options( $user_meta_options ) {

		$this->user_meta_options = $user_meta_options;
	}

	/**
	 * Find a value for a given option.
	 * First searches on user meta and if not found, it tries to get it from the options prop
	 *
	 * @since 1.5.9
	 *
	 * @param string $option_key
	 *
	 * @return string|bool  The meta key if it exists of FALSE if not.
	 */
	public function find_option_value( $option_key ) {

		if ( ! empty( $this->user_meta_options ) ) {

			foreach ( $this->user_meta_options as $user_meta_key => $user_meta_options ) {

				if ( in_array( $option_key, $user_meta_options, TRUE ) ) {
					$user_saved_meta = Helpers::get_atum_user_meta( $user_meta_key );

					return isset( $user_saved_meta[ $option_key ] ) ? $user_saved_meta[ $option_key ] : $this->defaults[ $option_key ]['default'];
				}

			}

		}

		if ( isset( $this->options[ $option_key ] ) ) {
			return $this->options[ $option_key ];
		}
		elseif ( isset( $this->defaults[ $option_key ]['default'] ) ) {
			return $this->defaults[ $option_key ]['default'];
		}

		return FALSE;

	}

	/****************************
	 * Instance methods
	 ****************************/

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
	 * @return Settings instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
