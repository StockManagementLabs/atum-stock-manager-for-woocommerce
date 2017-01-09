<?php
/**
 * @package         Atum
 * @subpackage      Inc
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       (c)2017 Stock Management Labs
 *
 * @since           0.0.1
 *
 * Initializate the plugin
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Settings\Settings;
use Atum\StockCentral\Inc\HelpPointers;
use Atum\StockCentral\StockCentral;


class Init {
	
	/**
	 * The singleton instance holder
	 * @var Init
	 */
	private static $instance;
	
	/**
	 * The Settings page object
	 * @var Settings
	 */
	private $sp;
	
	/**
	 * The Stock central object
	 * @var StockCentral
	 */
	private $sc;
	
	
	private function __construct() {
		
		if ( is_admin() ) {
			
			// Add the menus
			add_action( 'admin_menu', array( $this, 'add_plugin_menu' ), 1 );
			
			// Initialization checks
			add_action( 'init', array( $this, 'load' ) );
			
			// WooCommerce Hooks related to the ATUM's "Manage Stock" option
			if ( Helpers::get_option( 'manage_stock', 'no' ) == 'yes' ) {
				add_action( 'init', array( $this, 'manage_stock_hooks' ) );
			}
			else {
				add_action( 'save_post_product', array($this, 'delete_transients') );
			}
			
		}
		
		// Save the date when any product goes out of stock
		add_action( 'woocommerce_product_set_stock' , array($this, 'record_out_of_stock_date'), 20 );
		
	}
	
	/**
	 * Load plugin dependencies and performs initial checkings
	 *
	 * @since 0.0.3
	 */
	public function load() {
		
		// Delete transients if this is first execution after upgrade
		$db_version = get_option( ATUM_PREFIX . 'version' );
		
		if ( $db_version != ATUM_VERSION ) {
			Helpers::delete_transients();
			update_option( ATUM_PREFIX . 'version', ATUM_VERSION );
		}
		
		// Load language files
		load_plugin_textdomain( ATUM_TEXT_DOMAIN, FALSE, plugin_basename( ATUM_PATH ) . '/languages' );
		
		// Add notice if Atum manege stock isn't active
		$this->sp = Settings::get_instance();
		$this->sc = StockCentral::get_instance();
		
		// Add the help pointers
		add_action( 'admin_enqueue_scripts', array($this, 'setup_help_pointers') );
		
	}
	
	/**
	 * Generate the plugin pages' menus
	 *
	 * @since 0.0.1
	 */
	public function add_plugin_menu() {
		
		// Add the main menu item
		add_menu_page(
			__( 'Stock Central', ATUM_TEXT_DOMAIN ),
			__( 'Stock Central', ATUM_TEXT_DOMAIN ),
			'manage_options',
			Globals::ATUM_UI_SLUG,
			'',
			'dashicons-chart-area',
			58
		);
		
		$menu_items = apply_filters( 'atum/admin/menu_items', array(
			'stock-central'   => array(
				'title'    => __( 'Stock Central', ATUM_TEXT_DOMAIN ),
				'callback' => array( $this, 'load_stock_central' ),
				'slug'     => 'stock-central'
			),
			'product-levels'  => array(
				'title' => __( 'Product Levels', ATUM_TEXT_DOMAIN ),
			),
			'stock-redist'    => array(
				'title' => __( 'Stock Redistribution', ATUM_TEXT_DOMAIN ),
			),
			'stock-takes'     => array(
				'title' => __( 'Stock Takes', ATUM_TEXT_DOMAIN ),
			),
			'reports'         => array(
				'title' => __( 'Reports', ATUM_TEXT_DOMAIN ),
			),
			'graphs'          => array(
				'title' => __( 'Graphs', ATUM_TEXT_DOMAIN ),
			),
			'stock-forecast'  => array(
				'title' => __( 'Stock Forecast', ATUM_TEXT_DOMAIN ),
			),
			'stock-log'       => array(
				'title' => __( 'Stock Logs', ATUM_TEXT_DOMAIN ),
			),
			'financials'      => array(
				'title' => __( 'Financials', ATUM_TEXT_DOMAIN ),
			),
			'profit-loss'     => array(
				'title' => __( 'Profit & Loss', ATUM_TEXT_DOMAIN ),
			),
			'purchase-orders' => array(
				'title' => __( 'Purchase Orders', ATUM_TEXT_DOMAIN ),
			),
			'purchase-orders' => array(
				'title' => __( 'Purchase Orders', ATUM_TEXT_DOMAIN ),
			),
			/*'the-mobile-app' => array(
				'title' => __('The Mobile App', ATUM_TEXT_DOMAIN),
			),*/
			'import-export'   => array(
				'title' => __( 'Import/Export', ATUM_TEXT_DOMAIN ),
			),
			'settings'        => array(
				'title'    => __( 'Settings', ATUM_TEXT_DOMAIN ),
				'callback' => array( $this, 'load_settings' ),
				'slug'     => 'settings'
			)
		) );
		
		// Build the submenu items
		foreach ( $menu_items as $key => $menu_item ) {
			
			$slug = ( ! isset( $menu_item['slug'] ) ) ? 'go-premium' : $menu_item['slug'];
			
			if ( strpos( $slug, ATUM_TEXT_DOMAIN ) === FALSE ) {
				$slug = ATUM_TEXT_DOMAIN . "-$slug";
			}
			
			$callback = ( ! empty( $menu_item['callback'] ) && is_callable( $menu_item['callback'] ) ) ? $menu_item['callback'] : array(
				$this,
				'load_go_premium_page'
			);
			
			add_submenu_page(
				Globals::ATUM_UI_SLUG,
				$menu_item['title'],
				$menu_item['title'],
				'manage_options',
				$slug,
				$callback
			);
		}
		
	}
	
	/**
	 * Load the Stock Central page
	 *
	 * @since 0.0.1
	 */
	public function load_stock_central() {
		
		$this->sc->display();
	}
	
	/**
	 * Load the Settings page
	 *
	 * @since 0.0.1
	 */
	public function load_settings() {
		
		$this->sp->display();
	}
	
	/**
	 * Load the Go Premium pricing tables in the free version
	 *
	 * @since 0.0.2
	 */
	public function load_go_premium_page() {
		
		wp_enqueue_style( 'go-premium', ATUM_URL . 'assets/css/go-premium.css', FALSE, ATUM_VERSION );
		Helpers::load_view( 'go-premium' );
	}
	
	/**
	 * Add WooCommerce Hooks when Atum Manage Stock is enabled
	 *
	 * @since 0.1.0
	 */
	public function manage_stock_hooks() {
		
		// Hide WooCommerce manage stock option for individual products
		add_action( 'woocommerce_product_options_stock', array( $this, 'hide_manage_stock' ) );
		
		// Set to yes the WooCommerce _manage_stock meta key for individual products
		add_action( 'added_post_meta', array( $this, 'save_manage_stock' ), 10, 4 );
		add_action( 'updated_postmeta', array( $this, 'save_manage_stock' ), 10, 4 );
		
	}
	
	/**
	 * Hide the WooCommerce "Manage Stock" checkbox for simple products
	 *
	 * @since 0.1.0
	 */
	public function hide_manage_stock() {
		
		// TODO: only for free version??
		?>
		<script type="text/javascript">
			(function ($) {
				'use strict';
				$('._manage_stock_field').removeClass('show_if_simple').find('.checkbox').prop('checked', true);
			})(jQuery);
		</script>
		<?php
		
	}
	
	/**
	 * Fires immediately after adding/updating the manage stock metadata
	 *
	 * @since 0.1.0
	 *
	 * @param int    $meta_id    ID of updated metadata entry.
	 * @param int    $object_id  Object ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 */
	public function save_manage_stock( $meta_id, $object_id, $meta_key, $meta_value ) {
		
		if ( $meta_key == '_manage_stock' && $meta_value == 'no' ) {
			$product = wc_get_product( $object_id );
			
			if ( $product && in_array( $product->product_type, Globals::get_product_types() ) ) {
				remove_action( 'updated_postmeta', array($this, 'save_manage_stock') );
				update_post_meta( $object_id, '_manage_stock', 'yes' );
				Helpers::delete_transients();
			}
		}
		
	}
	
	/**
	 * Add/Remove the "Out of stock" date when WooCommerce updates the stock of a product
	 *
	 * @since 0.1.3
	 *
	 * @param \WC_Product $product    The product that is being changed
	 */
	public function record_out_of_stock_date ($product) {
		
		if ( in_array($product->product_type, Globals::get_product_types()) ) {
			
			$current_stock = $product->get_stock_quantity();
			$out_of_stock_date_key = Globals::get_out_of_stock_date_key();
			
			if (!$current_stock) {
				update_post_meta( $product->id, $out_of_stock_date_key, Helpers::date_format( time(), TRUE ) );
				Helpers::delete_transients();
			}
			elseif ( get_post_meta( $product->id, $out_of_stock_date_key, TRUE ) ) {
				// Meta key not needed anymore for this product
				delete_post_meta( $product->id, $out_of_stock_date_key );
				Helpers::delete_transients();
			}
			
		}
		
	}
	
	/**
	 * Delete the ATUM transients after saving a product
	 *
	 * @since 0.1.5
	 *
	 * @param int $product_id   The product ID
	 */
	public function delete_transients($product_id) {
		Helpers::delete_transients();
	}
	
	/**
	 * Setup help pointers for some Atum screens
	 *
	 * @since 0.1.6
	 */
	public function setup_help_pointers() {
		
		$pointers = array(
			array(
				'id'       => Globals::ATUM_UI_SLUG . '-help-tab',      // Unique id for this pointer
				'next'     => 'screen-tab',
				'screen'   => 'toplevel_page_' . Globals::ATUM_UI_SLUG, // This is the page hook we want our pointer to show on
				'target'   => '#contextual-help-link-wrap',             // The css selector for the pointer to be tied to, best to use ID's
				'title'    => __('ATUM Quick Help', ATUM_TEXT_DOMAIN),
				'content'  => __("Click the 'Help' tab to learn more about the ATUM's Stock Central.", ATUM_TEXT_DOMAIN),
				'position' => array(
					'edge'  => 'top',                                   // Top, bottom, left, right
					'align' => 'left'                                   // Top, bottom, left, right, middle
				)
			),
			array(
				'id'       => Globals::ATUM_UI_SLUG . '-screen-tab',
				'screen'   => 'toplevel_page_' . Globals::ATUM_UI_SLUG,
				'target'   => '#screen-options-link-wrap',
				'title'    => __('ATUM Screen Setup', ATUM_TEXT_DOMAIN),
				'content'  => __("Click the 'Screen Options' tab to setup your table view preferences.", ATUM_TEXT_DOMAIN),
				'position' => array(
					'edge'  => 'top',
					'align' => 'left'
				)
			)
		);
		
		// Instantiate the class and pass our pointers array to the constructor
		new HelpPointers( $pointers );
		
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
	 * @static
	 * @return Init instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}