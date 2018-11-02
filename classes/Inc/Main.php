<?php
/**
 * Plugin Initialization
 *
 * @package         Atum
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           0.0.1
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCapabilities;
use Atum\Dashboard\Dashboard;
use Atum\DataExport\DataExport;
use Atum\InboundStock\InboundStock;
use Atum\Integrations\Wpml;
use Atum\Modules\ModuleManager;
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
		add_action( 'init', array( $this, 'add_menu_items' ), 1 );

		// Load front stuff (priority must be higher than 10).
		add_action( 'init', array( $this, 'load' ), 11 );

		// Load ATUM modules.
		add_action( 'setup_theme', array( $this, 'load_modules' ) );

	}

	/**
	 * Initialize the front stuff
	 * This will execute as a priority 11 within the "init" hook
	 *
	 * @since 1.2.0
	 */
	public function load() {

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
		) );

		register_taxonomy( Globals::PRODUCT_LOCATION_TAXONOMY, 'product', $args );

	}
	
	/**
	 * Load admin plugin dependencies and performs initializations
	 *
	 * @since 0.0.3
	 */
	public function admin_load() {

		$db_version = get_option( ATUM_PREFIX . 'version' );
		
		if ( version_compare( $db_version, ATUM_VERSION, '!=' ) ) {
			// Do upgrade tasks.
			new Upgrade( $db_version ?: '0.0.1' );
		}

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
		
		//
		// Enable WPML module if needed
		// -----------------------------!
		if ( class_exists( '\SitePress' ) && class_exists( '\woocommerce_wpml' ) ) {
			new Wpml();
		}
		
		//
		// Load extra modules
		// -------------------!
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
			new InventoryLogs();
		}

		if ( ModuleManager::is_module_active( 'purchase_orders' ) ) {

			if ( AtumCapabilities::current_user_can( 'read_supplier' ) ) {
				Suppliers::get_instance();

				// The Suppliers is a dependency for Purchase Orders.
				if ( AtumCapabilities::current_user_can( 'read_purchase_order' ) ) {
					new PurchaseOrders();

					// The Purchase Orders is a dependency for Inbound Stock.
					if ( AtumCapabilities::current_user_can( 'read_inbound_stock' ) ) {
						InboundStock::get_instance();
					}
				}
			}

		}

	}

	/**
	 * Add items to the ATUM menu
	 *
	 * @since 1.3.6
	 */
	public function add_menu_items() {

		$this->menu_items = (array) apply_filters( 'atum/admin/menu_items', array() );

		foreach ( $this->menu_items as $menu_item ) {
			$this->menu_items_order[] = array(
				'slug'       => $menu_item['slug'],
				'menu_order' => ( ! isset( $menu_item['menu_order'] ) ) ? 99 : $menu_item['menu_order'],
			);
		}

		// The first submenu will be the main (parent) menu too.
		self::$main_menu_item = array_slice( $this->menu_items, 0, 1 );
		self::$main_menu_item = reset( self::$main_menu_item );

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
	 * Add theme options menu item to Admin Bar
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
			'title' => '<span class="ab-icon"><img src="' . ATUM_URL . 'assets/images/atum-icon.svg" style="padding-top: 2px"></span><span class="ab-label">ATUM</span>',
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
			if ( ! get_option( 'atum_admin_footer_text_rated' ) ) {

				/* translators: the first one is the WordPress plugins directory link and the second is the link closing tag */
				$footer_text = sprintf( __( 'If you like <strong>ATUM</strong> please leave us a %1$s&#9733;&#9733;&#9733;&#9733;&#9733;%2$s rating. Huge thanks in advance!', ATUM_TEXT_DOMAIN ), '<a href="https://wordpress.org/support/plugin/atum-stock-manager-for-woocommerce/reviews/?filter=5#new-post" target="_blank" class="wc-rating-link" data-rated="' . esc_attr__( 'Thanks :)', ATUM_TEXT_DOMAIN ) . '">', '</a>' );
				wc_enqueue_js( "
					jQuery( 'a.wc-rating-link' ).click( function() {
						jQuery.post( '" . WC()->ajax_url() . "', { action: 'atum_rated' } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});
				" );

			}
			else {
				$footer_text = __( 'Thank you for trusting in <strong>ATUM</strong> for managing your stock.', ATUM_TEXT_DOMAIN );
			}

		}

		return $footer_text;

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
