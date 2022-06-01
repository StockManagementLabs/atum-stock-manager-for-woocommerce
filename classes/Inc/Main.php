<?php
/**
 * Plugin Initialization
 *
 * @package         Atum
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           0.0.1
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;

/**
* For WC navigation system.
* use Automattic\WooCommerce\Admin\Features\Navigation\Menu;
*/
use Atum\Addons\Addons;
use Atum\Api\AtumApi;
use Atum\Cli\AtumCli;
use Atum\Components\AtumBarcodes;
use Atum\Components\AtumCalculatedProps;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumNotifications;
use Atum\Components\AtumColors;
use Atum\Components\AtumQueues;
use Atum\Dashboard\Dashboard;
use Atum\DataExport\DataExport;
use Atum\InboundStock\InboundStock;
use Atum\Integrations\Wpml;
use Atum\MetaBoxes\FileAttachment;
use Atum\MetaBoxes\ProductDataMetaBoxes;
use Atum\Modules\ModuleManager;
use Atum\Orders\CheckOrderPrices;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Settings\Settings;
use Atum\StockCentral\StockCentral;
use Atum\InventoryLogs\InventoryLogs;
use Atum\Suppliers\Suppliers;


class Main {

	/**
	 * The singleton instance holder
	 *
	 * @var Main
	 */
	private static $instance;

	/**
	 * The ATUM menu items
	 *
	 * @var array
	 */
	private $menu_items = array();

	/**
	 * The menu item that will be used as main (parent for all submenus)
	 *
	 * @var array
	 */
	private static $main_menu_item = array();
	
	/**
	 * The ATUM menu items order
	 *
	 * @var array
	 */
	private $menu_items_order = array();


	/**
	 * Singleton constructor
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		// Make the ATUM cache group, non persistent.
		wp_cache_add_non_persistent_groups( ATUM_TEXT_DOMAIN );
		
		if ( is_admin() ) {
			$this->main_admin_hooks();
		}

		$this->main_global_hooks();
		
	}

	/**
	 * Register the Main admin-side hooks
	 *
	 * @since 1.3.3
	 */
	protected function main_admin_hooks() {

		// Add the menus.
		add_action( 'admin_menu', array( $this, 'create_menu' ), 1 );

		// Load dependencies.
		add_action( 'init', array( $this, 'admin_load' ) );

	}

	/**
	 * Register the Main global hooks
	 *
	 * @since 1.3.3
	 */
	protected function main_global_hooks() {

		// Reorder the admin submenus.
		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', array( $this, 'set_menu_order' ) );

		// Add the ATUM menu to admin bar.
		add_action( 'wp_before_admin_bar_render', array( $this, 'add_admin_bar_menu' ) );

		// Load language files.
		load_plugin_textdomain( ATUM_TEXT_DOMAIN, FALSE, plugin_basename( ATUM_PATH ) . '/languages' ); // phpcs:ignore: WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found

		// Create menu (priority must be lower than 10).
		add_action( 'init', array( $this, 'pre_init' ), 1 );

		// Load front stuff (priority must be higher than 10).
		add_action( 'init', array( $this, 'init' ), 11 );

		// Load ATUM modules.
		add_action( 'setup_theme', array( $this, 'load_modules' ) );

		// This filter needs to be registered at the right time.
		add_action( 'setup_theme', function() {
			add_filter( 'woocommerce_rest_is_request_to_rest_api', array( $this, 'bypass_wp_endpoints_with_wc_keys' ) );
		}, 1 );

	}

	/**
	 * Do pre init tasks
	 *
	 * @since 1.3.6
	 */
	public function pre_init() {

		// Upgrade if needed.
		$db_version = get_option( 'atum_version' );

		if ( version_compare( $db_version, ATUM_VERSION, '!=' ) ) {
			// Do upgrade tasks.
			new Upgrade( $db_version ?: '0.0.1' );
		}

		// Add menu items.
		$this->menu_items = (array) apply_filters( 'atum/admin/menu_items', array() );

		foreach ( $this->menu_items as $menu_item ) {
			$this->menu_items_order[] = array(
				'slug'       => $menu_item['slug'],
				'menu_order' => ! isset( $menu_item['menu_order'] ) ? 99 : $menu_item['menu_order'],
			);
		}

		// The first submenu will be the main (parent) menu too.
		self::$main_menu_item = array_slice( $this->menu_items, 0, 1 );
		self::$main_menu_item = current( self::$main_menu_item );

		do_action( 'atum/after_pre_init' );

	}

	/**
	 * Initialize the front stuff
	 * This will run with priority 11 within the "init" hook
	 *
	 * @since 1.2.0
	 */
	public function init() {

		//
		// Register the Locations taxonomy and link it to products
		// --------------------------------------------------------!
		$labels = array(
			'name'              => _x( 'Product Locations', 'taxonomy general name', ATUM_TEXT_DOMAIN ),
			'singular_name'     => _x( 'Location', 'taxonomy singular name', ATUM_TEXT_DOMAIN ),
			'search_items'      => __( 'Search locations', ATUM_TEXT_DOMAIN ),
			'all_items'         => __( 'All locations', ATUM_TEXT_DOMAIN ),
			'parent_item'       => __( 'Parent location', ATUM_TEXT_DOMAIN ),
			'parent_item_colon' => __( 'Parent location:', ATUM_TEXT_DOMAIN ),
			'edit_item'         => __( 'Edit location', ATUM_TEXT_DOMAIN ),
			'update_item'       => __( 'Update location', ATUM_TEXT_DOMAIN ),
			'add_new_item'      => __( 'Add new location', ATUM_TEXT_DOMAIN ),
			'new_item_name'     => __( 'New location name', ATUM_TEXT_DOMAIN ),
			'menu_name'         => __( 'ATUM Locations', ATUM_TEXT_DOMAIN ),
			'not_found'         => __( 'No locations found', ATUM_TEXT_DOMAIN ),
		);

		$args = apply_filters( 'atum/location_taxonomy_args', array(
			'hierarchical' => TRUE,
			'labels'       => $labels,
			'show_ui'      => TRUE,
			'query_var'    => is_admin(),
			'rewrite'      => FALSE,
			'public'       => FALSE,
			'capabilities' => array(
				'manage_terms' => ATUM_PREFIX . 'manage_location_terms',
				'edit_terms'   => ATUM_PREFIX . 'edit_location_terms',
				'delete_terms' => ATUM_PREFIX . 'delete_location_terms',
				'assign_terms' => ATUM_PREFIX . 'assign_location_terms',
			),
		) );

		register_taxonomy( Globals::PRODUCT_LOCATION_TAXONOMY, 'product', $args );

		do_action( 'atum/after_init' );

	}
	
	/**
	 * Load admin plugin dependencies and performs initializations
	 *
	 * @since 0.0.3
	 */
	public function admin_load() {

		// Add the footer text to ATUM pages.
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

	}

	/**
	 * Load the ATUM modules
	 *
	 * @since 1.1.2
	 */
	public function load_modules() {

		//
		// Load core modules
		// ------------------!
		ModuleManager::get_instance();
		AtumCapabilities::get_instance();
		Hooks::get_instance();
		Addons::get_instance();
		Ajax::get_instance();
		Settings::get_instance();
		ProductDataMetaBoxes::get_instance();
		FileAttachment::get_instance();
		AtumQueues::get_instance();
		AtumCalculatedProps::get_instance();
		CheckOrderPrices::get_instance();
		AtumNotifications::get_instance();

		if ( class_exists( '\WP_CLI', FALSE ) ) {
			AtumCli::get_instance();
		}

		//
		// Enable WPML module if needed
		// -----------------------------!
		if ( class_exists( '\SitePress' ) && class_exists( '\woocommerce_wpml' ) ) {
			Wpml::get_instance();
		}
		
		//
		// Load extra modules
		// -------------------!
		if ( ModuleManager::is_module_active( 'api' ) ) {
			AtumApi::get_instance();
		}

		if ( ModuleManager::is_module_active( 'dashboard' ) ) {
			Dashboard::get_instance();
		}

		if ( ModuleManager::is_module_active( 'stock_central' ) ) {
			StockCentral::get_instance();
		}

		if ( AtumCapabilities::current_user_can( 'export_data' ) && ModuleManager::is_module_active( 'data_export' ) ) {
			new DataExport();
		}

		if ( AtumCapabilities::current_user_can( 'read_inventory_log' ) && ModuleManager::is_module_active( 'inventory_logs' ) ) {
			InventoryLogs::get_instance();
		}

		if ( ModuleManager::is_module_active( 'purchase_orders' ) ) {

			if ( AtumCapabilities::current_user_can( 'read_supplier' ) ) {
				Suppliers::get_instance();

				// The Suppliers is a dependency for Purchase Orders.
				if ( AtumCapabilities::current_user_can( 'read_purchase_order' ) ) {

					PurchaseOrders::get_instance();

					// The Purchase Orders is a dependency for Inbound Stock.
					if ( AtumCapabilities::current_user_can( 'read_inbound_stock' ) ) {
						InboundStock::get_instance();
					}
				}
			}

		}

		if ( ModuleManager::is_module_active( 'visual_settings' ) && AtumCapabilities::current_user_can( 'edit_visual_settings' ) ) {
			AtumColors::get_instance();
		}

		if ( ModuleManager::is_module_active( 'barcodes' ) ) {
			AtumBarcodes::get_instance();
		}

	}

	/**
	 * Generate the ATUM menu
	 *
	 * @since 0.0.1
	 */
	public function create_menu() {

		// Add the main menu item.
		add_menu_page(
			self::$main_menu_item['title'],
			__( 'ATUM Inventory', ATUM_TEXT_DOMAIN ),
			ATUM_PREFIX . 'view_admin_menu',
			self::$main_menu_item['slug'],
			'',
			ATUM_URL . 'assets/images/atum-icon.svg',
			58 // Add the menu just after the WC Products.
		);

		// ATUM category on WC navigation system.
		// Check if the WC method are availables.
		/** Next
		if ( class_exists( 'Automattic\WooCommerce\Admin\Features\Navigation\Menu' ) && method_exists( Menu::class, 'add_plugin_category' ) ) {

			Menu::add_plugin_category(
				array(
					'id'     => 'ATUM',
					'title'  => __( 'ATUM Inventory', ATUM_TEXT_DOMAIN ),
					'url'    => self::$main_menu_item['slug'],
					'parent' => 'woocommerce',
				)
			);

		}
		 */

		// Overwrite the main menu item hook name set by add_menu_page to avoid conflicts with translations.
		global $admin_page_hooks;
		$admin_page_hooks[ self::$main_menu_item['slug'] ] = Globals::ATUM_UI_HOOK; // phpcs:ignore WordPress.Variables.GlobalVariables.OverrideProhibited

		// Build the submenu items.
		if ( ! empty( $this->menu_items ) ) {

			foreach ( $this->menu_items as $key => $menu_item ) {

				$slug = $menu_item['slug'];

				if ( FALSE === strpos( $slug, ATUM_SHORT_NAME ) ) {
					$slug = ATUM_SHORT_NAME . "-$slug";
				}

				add_submenu_page(
					self::$main_menu_item['slug'],
					$menu_item['title'],
					$menu_item['title'],
					isset( $menu_item['capability'] ) ? $menu_item['capability'] : ATUM_PREFIX . 'view_admin_menu',
					$slug,
					$menu_item['callback']
				);

				// Addonds & atum submenú items on wc navigation system.
				// Check if the WC method are availables.
				/**  Next
				if ( class_exists( 'Automattic\WooCommerce\Admin\Features\Navigation\Menu' ) && method_exists( Menu::class, 'add_plugin_item' ) ) {

					Menu::add_plugin_item(
						array(
							'id'         => $menu_item['title'],
							'title'      => $menu_item['title'],
							'capability' => 'manage_woocommerce',
							'url'        => $slug,
							'order'      => $menu_item['menu_order'],
							'parent'     => 'ATUM',
						)
					);

				}
				*/

			}

		}

		do_action( 'atum/after_adding_menu' );

	}

	/**
	 * Set the ATUM admin menu's order
	 *
	 * @since 1.2.4
	 *
	 * @param array $menu_order
	 *
	 * @return array
	 */
	public function set_menu_order( $menu_order ) {

		global $submenu;

		if ( ! empty( $submenu ) && ! empty( $submenu[ self::$main_menu_item['slug'] ] ) ) {

			$menu_items             = $submenu[ self::$main_menu_item['slug'] ];
			$this->menu_items_order = (array) apply_filters( 'atum/admin/menu_items_order', $this->menu_items_order );

			usort( $menu_items, function ( $a, $b ) {

				$a_slug     = $a[2];
				$b_slug     = $b[2];
				$a_position = $b_position = 99;
				
				foreach ( $this->menu_items_order as $menu_item ) {

					if ( $menu_item['slug'] === $a_slug ) {
						$a_position = $menu_item['menu_order'];
					}

					if ( $menu_item['slug'] === $b_slug ) {
						$b_position = $menu_item['menu_order'];
					}

				}

				return floatval( $a_position ) - floatval( $b_position );

			});

			$submenu[ self::$main_menu_item['slug'] ] = apply_filters( 'atum/menu_order', $menu_items ); // phpcs:ignore WordPress.Variables.GlobalVariables.OverrideProhibited

		}

		return $menu_order;

	}

	/**
	 * Add ATUM menu to the Admin Bar
	 *
	 * @since 1.2.0
	 */
	public function add_admin_bar_menu() {

		if ( ! AtumCapabilities::current_user_can( 'view_admin_bar_menu' ) ) {
			return;
		}

		if ( 'yes' !== Helpers::get_option( 'enable_admin_bar_menu', 'yes' ) ) {
			return;
		}

		/**
		 * Variable definition
		 *
		 * @var \WP_Admin_Bar $wp_admin_bar
		 */
		global $wp_admin_bar;

		// Add the main menu item.
		$wp_admin_bar->add_node( array(
			'id'    => self::$main_menu_item['slug'],
			'title' => '<span class="ab-icon"><img src="' . ATUM_URL . 'assets/images/atum-icon.svg" style="padding-top: 2px" alt="ATUM"></span><span class="ab-label">ATUM</span>',
			'href'  => admin_url( 'admin.php?page=' . self::$main_menu_item['slug'] ),
		) );

		$submenu_items = (array) apply_filters( 'atum/admin/top_bar/menu_items', $this->menu_items );

		// Build the submenu items.
		if ( ! empty( $submenu_items ) ) {

			usort( $submenu_items, function ( $a, $b ) {
				return (int) $a['menu_order'] - (int) $b['menu_order'];
			} );

			foreach ( $submenu_items as $key => $menu_item ) {

				$slug = $menu_item['slug'];

				if ( strpos( $slug, ATUM_SHORT_NAME ) === FALSE ) {
					$slug = ATUM_SHORT_NAME . "-$slug";
				}

				$href = ( isset( $menu_item['href'] ) ) ? $menu_item['href'] : "admin.php?page=$slug";

				$wp_admin_bar->add_node( array(
					'id'     => "$slug-item",
					'parent' => self::$main_menu_item['slug'],
					'title'  => $menu_item['title'],
					'href'   => admin_url( $href ),
				) );

			}

		}

	}

	/**
	 * Change the admin footer text on ATUM admin pages
	 *
	 * @since  1.2.0
	 *
	 * @param  string $footer_text
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {

		$current_screen = get_current_screen();

		if ( isset( $current_screen->id ) && $current_screen->parent_base === self::$main_menu_item['slug'] ) {

			// Load the footer text in all pages except the ATUM Dashboard && allow add-ons pages to be included.
			$excluded = (array) apply_filters( 'atum/admin/footer/pages_excluded', array( Dashboard::UI_SLUG ) );

			foreach ( $excluded as $item ) {
				if ( strpos( $current_screen->id, $item ) !== FALSE ) {
					return '';
				}
			}

			// Change the footer text.
			$footer_text = Helpers::get_rating_text();

			$footer_class        = FALSE;
			$screen_base         = get_current_screen()->base;
			$allow_styled_footer = apply_filters( 'atum/admin/allow_styled_footer', [
				'edit',
				'atum-inventory_page_atum-stock-central',
				'atum-inventory_page_atum-inbound-stock',
			] );

			if ( in_array( $screen_base, $allow_styled_footer, TRUE ) ) {
				$footer_class = TRUE;
			}

			return Helpers::load_view_to_string( 'atum-footer', compact( 'footer_class', 'footer_text' ) );

		}
		
		return $footer_text;

	}

	/**
	 * Allow authenticating some WP API's endpoints using the WC API keys, so we can upload images to products, list comments, etc.
	 *
	 * @since 1.7.5
	 *
	 * @param bool $is_request_to_rest_api
	 *
	 * @return bool
	 */
	public function bypass_wp_endpoints_with_wc_keys( $is_request_to_rest_api ) {

		if ( ! $is_request_to_rest_api ) {

			if ( empty( $_SERVER['REQUEST_URI'] ) ) {
				return FALSE;
			}

			$rest_prefix = trailingslashit( rest_get_url_prefix() );
			$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );

			$is_request_to_rest_api = apply_filters( 'atum/api/bypass_wp_endpoints_with_wc_keys',
				( FALSE !== strpos( $request_uri, $rest_prefix . 'wp/v2/media' ) ) ||
				( FALSE !== strpos( $request_uri, $rest_prefix . 'wp/v2/comments' ) )
			);

		}

		return $is_request_to_rest_api;

	}

	/**
	 * Getter for the main_menu_item prop
	 *
	 * @since 1.3.6
	 *
	 * @return array
	 */
	public static function get_main_menu_item() {

		return self::$main_menu_item;
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
	 * @return Main instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}
