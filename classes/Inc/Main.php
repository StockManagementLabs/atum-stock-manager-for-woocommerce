<?php
/**
 * @package         Atum
 * @subpackage      Inc
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           0.0.1
 *
 * Initializate the plugin
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Addons\Addons;
use Atum\Components\AtumCapabilities;
use Atum\Components\HelpPointers;
use Atum\Dashboard\Statistics;
use Atum\DataExport\DataExport;
use Atum\InboundStock\InboundStock;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Settings\Settings;
use Atum\StockCentral\StockCentral;
use Atum\InventoryLogs\InventoryLogs;
use Atum\Suppliers\Suppliers;


class Main {
	
	/**
	 * The singleton instance holder
	 * @var Main
	 */
	private static $instance;

	/**
	 * The ATUM menu items
	 * @var array
	 */
	private $menu_items = array();

	/**
	 * The menu item that will be used as main (parent for all submenus)
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
			$this->register_admin_hooks();
		}

		$this->register_global_hooks();
		
	}

	/**
	 * Register the admin-side hooks
	 *
	 * @since 1.3.3
	 */
	protected function register_admin_hooks() {

		// Add the menus
		add_action( 'admin_menu', array( $this, 'create_menu' ), 1 );

		// Load dependencies
		add_action( 'init', array( $this, 'admin_load' ) );

		// Check if ATUM has the "Manage Stock" option enabled
		if ( Helpers::is_atum_managing_stock() ) {
			add_action( 'init', array( $this, 'atum_manage_stock_hooks' ) );
		}
		else {
			// Add the WC stock management option to grouped products
			add_action( 'init', array( $this, 'wc_manage_stock_hooks' ) );
		}

		// Add the purchase price to WC products
		add_action( 'woocommerce_product_options_pricing', array($this, 'add_purchase_price_meta') );
		add_action( 'woocommerce_variation_options_pricing', array($this, 'add_purchase_price_meta'), 10, 3 );

		// Save the product purchase price meta
		add_action( 'save_post_product', array($this, 'save_purchase_price') );
		add_action( 'woocommerce_update_product_variation', array($this, 'save_purchase_price') );

		// Show the right stock status on WC products list when ATUM is managing the stock
		add_filter( 'woocommerce_admin_stock_html', array($this, 'set_wc_products_list_stock_status'), 10, 2 );

		// Add purchase price to WPML custom prices
		add_filter( 'wcml_custom_prices_fields', array($this, 'wpml_add_purchase_price_to_custom_prices') );
		add_filter( 'wcml_custom_prices_fields_labels', array($this, 'wpml_add_purchase_price_to_custom_price_labels') );
		add_filter( 'wcml_custom_prices_strings', array($this, 'wpml_add_purchase_price_to_custom_price_labels') );
		add_filter( 'wcml_update_custom_prices_values', array($this, 'wpml_sanitize_purchase_price_in_custom_prices'), 10, 3 );
		add_action( 'wcml_after_save_custom_prices', array($this, 'wpml_save_purchase_price_in_custom_prices'), 10, 4 );

		// Add the location column to the items table in WC orders
		add_action( 'woocommerce_admin_order_item_headers', array($this, 'wc_order_add_location_column_header') );
		add_action( 'woocommerce_admin_order_item_values', array($this, 'wc_order_add_location_column_value'), 10, 3 );

	}

	/**
	 * Register the global hooks
	 *
	 * @since 1.3.3
	 */
	protected function register_global_hooks() {

		// Reorder the admin submenus
		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', array($this, 'set_menu_order') );

		// Add the ATUM menu to admin bar
		add_action( 'wp_before_admin_bar_render', array( $this, 'add_admin_bar_menu' ) );

		// Load language files
		load_plugin_textdomain( ATUM_TEXT_DOMAIN, FALSE, plugin_basename( ATUM_PATH ) . '/languages' );

		// Create menu (priority must be lower than 10)
		add_action( 'init', array($this, 'add_menu_items'), 1 );

		// Load front stuff (priority must be higher than 10)
		add_action( 'init', array($this, 'load'), 11 );

		// Load ATUM modules
		add_action( 'setup_theme', array( $this, 'load_modules' ) );

		// Save the date when any product goes out of stock
		add_action( 'woocommerce_product_set_stock' , array($this, 'record_out_of_stock_date'), 20 );

		// Delete the views' transients after changing the stock of any product
		add_action( 'woocommerce_product_set_stock' , array($this, 'delete_transients') );
		add_action( 'woocommerce_variation_set_stock' , array($this, 'delete_transients') );

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
		//---------------------------------------------------------
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
			'menu_name'         => __( 'Locations', ATUM_TEXT_DOMAIN ),
		);

		$args = apply_filters( 'atum/location_taxonomy_args', array(
			'hierarchical' => TRUE,
			'labels'       => $labels,
			'show_ui'      => TRUE,
			'query_var'    => is_admin(),
			'rewrite'      => FALSE,
			'public'       => FALSE
		) );

		register_taxonomy( Globals::PRODUCT_LOCATION_TAXONOMY, 'product', $args );


		// Set the stock decimals setting globally
		Globals::set_stock_decimals( Helpers::get_option('stock_quantity_decimals', 0) );

		// Maybe allow decimals for WC products' stock quantity
		if (Globals::get_stock_decimals() > 0) {

			// Add min value to the quantity field (WC default = 1)
			add_filter('woocommerce_quantity_input_min', array($this, 'stock_quantity_input_atts'), 10, 2);

			// Add step value to the quantity field (WC default = 1)
			add_filter('woocommerce_quantity_input_step', array($this, 'stock_quantity_input_atts'), 10, 2);

			// Removes the WooCommerce filter, that is validating the quantity to be an int
			remove_filter('woocommerce_stock_amount', 'intval');

			// Replace the above filter with a custom one that validates the quantity to be a int or float and applies rounding
			add_filter('woocommerce_stock_amount', array($this, 'round_stock_quantity'));

			// Customise the "Add to Cart" message to allow decimals in quantities
			add_filter('wc_add_to_cart_message_html', array($this, 'add_to_cart_message'), 10, 2);

		}

	}
	
	/**
	 * Load admin plugin dependencies and performs initializations
	 *
	 * @since 0.0.3
	 */
	public function admin_load() {

		$db_version = get_option( ATUM_PREFIX . 'version' );
		
		if ( version_compare($db_version, ATUM_VERSION, '!=') ) {
			// Do upgrade tasks
			new Upgrade( $db_version ?: '0.0.1' );
		}

		// TODO: CREATE A FIRST-ACCESS TUTORIAL WITH HELP POINTERS (LIKE WC)
		// Register the help pointers
		//add_action( 'admin_enqueue_scripts', array( $this, 'setup_help_pointers' ) );

		// Admin styles
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

		// Add the footer text to ATUM pages
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
		
	}

	/**
	 * Load the ATUM modules
	 *
	 * @since 1.1.2
	 */
	public function load_modules () {

		//
		// Load core modules
		//--------------------

		ModuleManager::get_instance();
		AtumCapabilities::get_instance();
		Addons::get_instance();
		Ajax::get_instance();
		Settings::get_instance();

		//
		// Load extra modules
		//--------------------

		if ( ModuleManager::is_module_active('stock_central') ) {
			StockCentral::get_instance();
		}

		if ( AtumCapabilities::current_user_can('view_statistics') && ModuleManager::is_module_active('dashboard_statistics') ) {
			new Statistics( __( 'ATUM Statistics', ATUM_TEXT_DOMAIN ) );
		}

		if ( AtumCapabilities::current_user_can('export_data') && ModuleManager::is_module_active('data_export') ) {
			new DataExport();
		}

		if ( AtumCapabilities::current_user_can('read_inventory_log') && ModuleManager::is_module_active('inventory_logs') ) {
			new InventoryLogs();
		}

		if ( ModuleManager::is_module_active('purchase_orders') ) {

			if ( AtumCapabilities::current_user_can('read_supplier') ) {
				Suppliers::get_instance();

				// The Suppliers is a dependency for Purchase Orders
				if ( AtumCapabilities::current_user_can('read_purchase_order') ) {
					new PurchaseOrders();

					// The Purchase Orders is a dependency for Inbound Stock
					if ( AtumCapabilities::current_user_can('read_inbound_stock') ) {
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
	public function add_menu_items () {

		$this->menu_items = (array) apply_filters( 'atum/admin/menu_items', array() );

		foreach ( $this->menu_items as $menu_item ) {
			$this->menu_items_order[] = array(
				'slug'       => $menu_item['slug'],
				'menu_order' => ( empty( $menu_item['menu_order'] ) ) ? 99 : $menu_item['menu_order'],
			);
		}

		// The first submenu will be the main (parent) menu too
		self::$main_menu_item = array_slice( $this->menu_items, 0, 1 );
		self::$main_menu_item = reset( self::$main_menu_item );

	}
	
	/**
	 * Generate the ATUM menu
	 *
	 * @since 0.0.1
	 */
	public function create_menu() {
		
		// Add the main menu item
		add_menu_page(
			self::$main_menu_item['title'],
			__( 'ATUM Inventory', ATUM_TEXT_DOMAIN ),
			ATUM_PREFIX . 'view_admin_menu',
			self::$main_menu_item['slug'],
			'',
			ATUM_URL . 'assets/images/atum-icon.svg',
			58 // Add the menu just after the WC Products
		);

		// Overwrite the main menu item hook name set by add_menu_page to avoid conflicts with translations
		global $admin_page_hooks;
		$admin_page_hooks[ self::$main_menu_item['slug'] ] = Globals::ATUM_UI_HOOK;
		
		// Build the submenu items
		if ( ! empty($this->menu_items) ) {

			foreach ( $this->menu_items as $key => $menu_item ) {

				$slug = $menu_item['slug'];

				if ( strpos( $slug, ATUM_TEXT_DOMAIN ) === FALSE ) {
					$slug = ATUM_TEXT_DOMAIN . "-$slug";
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

		do_action('atum/after_adding_menu');
		
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
	public function set_menu_order ($menu_order) {

		global $submenu;

		if ( ! empty($submenu) && ! empty( $submenu[ self::$main_menu_item['slug'] ] ) ) {

			$menu_items = $submenu[ self::$main_menu_item['slug'] ];
			$this->menu_items_order = (array) apply_filters( 'atum/admin/menu_items_order', $this->menu_items_order );
			
			usort($menu_items, function ($a, $b) {

				$a_slug     = $a[2];
				$b_slug     = $b[2];
				$a_position = $b_position = 99;
				
				foreach ( $this->menu_items_order as $menu_item ) {
					
					if ($menu_item['slug'] == $a_slug) {
						$a_position = $menu_item['menu_order'];
					}

					if ($menu_item['slug'] == $b_slug) {
						$b_position = $menu_item['menu_order'];
					}

				}
				
				return floatval($a_position) - floatval($b_position);

			});

			$submenu[ self::$main_menu_item['slug'] ] = apply_filters( 'atum/menu_order', $menu_items );

		}

		return $menu_order;

	}

	/**
	 * Add theme options menu item to Admin Bar
	 *
	 * @since 1.2.0
	 */
	public function add_admin_bar_menu() {

		if ( ! AtumCapabilities::current_user_can('view_admin_bar_menu') ) {
			return;
		}

		if ( Helpers::get_option( 'enable_admin_bar_menu', 'yes' ) != 'yes' ) {
			return;
		}

		global $wp_admin_bar;

		// Add the main menu item
		$wp_admin_bar->add_node( array(
			'id'    => self::$main_menu_item['slug'],
			'title' => '<span class="ab-icon"><img src="' . ATUM_URL . 'assets/images/atum-icon.svg" style="padding-top: 2px"></span><span class="ab-label">ATUM</span>',
			'href'  => admin_url( 'admin.php?page=' . self::$main_menu_item['slug'] )
		) );

		$submenu_items = (array) apply_filters('atum/admin/top_bar/menu_items', $this->menu_items);

		// Build the submenu items
		if ( ! empty($submenu_items) ) {
			
			usort($submenu_items, function ($a, $b) {
				return (int) $a['menu_order'] - (int) $b['menu_order'];
			});

			foreach ( $submenu_items as $key => $menu_item ) {

				$slug = $menu_item['slug'];

				if ( strpos( $slug, ATUM_TEXT_DOMAIN ) === FALSE ) {
					$slug = ATUM_TEXT_DOMAIN . "-$slug";
				}

				$href = ( isset( $menu_item['href'] ) ) ? $menu_item['href'] : "admin.php?page=$slug";

				$wp_admin_bar->add_node( array(
					'id'     => "$slug-item",
					'parent' => self::$main_menu_item['slug'],
					'title'  => $menu_item['title'],
					'href'   => admin_url( $href )
				) );

			}

		}

	}
	
	/**
	 * Add Hooks when Atum "Manage Stock" option is enabled
	 *
	 * @since 0.1.0
	 */
	public function atum_manage_stock_hooks() {
		
		// Disable WooCommerce manage stock option for individual products
		add_action( 'woocommerce_product_options_stock', array( $this, 'disable_manage_stock' ) );
		add_action( 'woocommerce_product_options_stock_fields', array( $this, 'add_manage_stock' ) );
		
		// Disable WooCommerce manage stock option for product variations
		add_action( 'woocommerce_ajax_admin_get_variations_args', array($this, 'disable_variation_manage_stock'));
		
		// Set to yes the WooCommerce _manage_stock meta key for all the supported products
		add_action( 'update_post_metadata', array( $this, 'save_manage_stock' ), 10, 5 );
		
	}

	/**
	 * Add Hooks when WooCommerce is managing the individual products' stock
	 *
	 * @since 1.1.1
	 */
	public function wc_manage_stock_hooks() {

		// Add the WooCommerce manage stock option to grouped products
		add_action( 'woocommerce_product_options_stock_fields', array( $this, 'add_manage_stock' ) );

		// Allow saving the WooCommerce _manage_stock meta key for grouped products
		add_action( 'update_post_metadata', array( $this, 'save_manage_stock' ), 10, 5 );

	}
	
	/**
	 * Disable the WooCommerce "Manage Stock" checkbox for simple products
	 *
	 * @since 0.1.0
	 */
	public function disable_manage_stock() {
		
		// The external products don't have stock and the grouped depends on its own products' stock
		$product_type = wp_get_post_terms( get_the_ID(), 'product_type', array('fields' => 'names') );
		
		if ( ! is_wp_error($product_type) && ! in_array('external', $product_type) ) : ?>
			<script type="text/javascript">
				(function ($) {
					var $manageStockField = $('._manage_stock_field');
					$manageStockField.find('.checkbox').prop({'checked': true, 'readonly': true}).css('pointer-events', 'none')
						.siblings('.description').html('<strong><sup>**</sup><?php _e('The stock is currently managed by ATUM plugin', ATUM_TEXT_DOMAIN) ?><sup>**</sup></strong>');

					$manageStockField.children().click(function(e) {
						e.stopImmediatePropagation();
						e.preventDefault();
					});
				})(jQuery);
			</script>
		<?php endif;
		
	}
	
	/**
	 * Disable the WooCommerce "Manage Stock" checkbox for variation products
	 *
	 * @since 1.1.1
	 *
	 * @param array $args
	 * @return array
	 */
	public function disable_variation_manage_stock ($args) {
		
		?>
		<script type="text/javascript">
			(function ($) {
				$('.variable_manage_stock').each(function() {
					$(this).prop({'checked': true, 'readonly': true})
						.siblings('.woocommerce-help-tip')
						.attr('data-tip', '<?php _e('The stock is currently managed by ATUM plugin', ATUM_TEXT_DOMAIN) ?>');

					$(this).click(function(e) {
						e.stopImmediatePropagation();
						e.preventDefault();
					});
				});
			})(jQuery);
		</script>
		<?php
		
		return $args;
	}

	/**
	 * Add the WooCommerce's stock management checkbox to Grouped and External products
	 *
	 * @since 1.1.1
	 */
	public function add_manage_stock () {

		if ( get_post_type() != 'product' ) {
			return;
		}

		$product = wc_get_product();

		// Show the "Manage Stock" checkbox on Grouped products and hide the other stock fields
		if ( $product && is_a($product, '\\WC_Product') ) : ?>
			<script type="text/javascript">
				var $backOrders = jQuery('._backorders_field');
				jQuery('._manage_stock_field').addClass('show_if_grouped show_if_product-part show_if_raw-material');

				<?php // NOTE: The "wp-menu-arrow" is a WP built-in class that adds "display: none!important" so doesn't conflict with WC JS ?>
				jQuery('#product-type').change(function() {
					var productType = jQuery(this).val();
					if (productType === 'grouped' || productType === 'external') {
						$backOrders.addClass('wp-menu-arrow');
					}
					else {
						$backOrders.removeClass('wp-menu-arrow');
					}
				});

				<?php if ( in_array($product->get_type(), ['grouped', 'external'] ) ): ?>
				$backOrders.addClass('wp-menu-arrow');
				<?php endif; ?>
			</script>
		<?php endif;

	}
	
	/**
	 * Fires immediately after adding/updating the manage stock metadata
	 *
	 * @since 0.1.0
	 *
	 * @param mixed  $check         ID of updated metadata entry
	 * @param int    $product_id    The product ID
	 * @param string $meta_key      Meta key
	 * @param mixed  $meta_value    Meta value
	 * @param mixed  $prev_value    Previous valus for this meta field
	 *
	 * @return NULL|bool            NULL to continue saving the meta key ($check is always NULL) or any other value to not continue
	 */
	public function save_manage_stock( $check, $product_id, $meta_key, $meta_value, $prev_value ) {
		
		if ( $meta_key == '_manage_stock' && $meta_value == 'no' ) {
			$product = wc_get_product( $product_id );
			
			if ( $product && in_array( $product->get_type(), Globals::get_product_types() ) ) {
				remove_action( 'update_post_metadata', array($this, 'save_manage_stock') );

				if ( Helpers::is_atum_managing_stock() ) {
					$manage_stock = 'yes'; // Always enabled
					Helpers::delete_transients();
				}
				else {
					$manage_stock = ( isset($_POST['_manage_stock']) && $_POST['_manage_stock'] == 'yes' ) ? 'yes' : 'no';
				}

				update_post_meta( $product_id, '_manage_stock', $manage_stock );

				// Do not continue saving this meta key
				return TRUE;
			}
		}

		return $check;
		
	}
	
	/**
	 * Add/Remove the "Out of stock" date when WooCommerce updates the stock of a product
	 *
	 * @since 0.1.3
	 *
	 * @param \WC_Product $product    The product being changed
	 */
	public function record_out_of_stock_date ($product) {
		
		if ( in_array($product->get_type(), Globals::get_product_types()) ) {
			
			$current_stock = $product->get_stock_quantity();
			$out_of_stock_date_key = Globals::get_out_of_stock_date_key();
			$product_id = $product->get_id();
			
			if (!$current_stock) {
				update_post_meta( $product_id, $out_of_stock_date_key, Helpers::date_format( current_time('timestamp'), TRUE ) );
				Helpers::delete_transients();
			}
			elseif ( get_post_meta( $product_id, $out_of_stock_date_key, TRUE ) ) {
				// Meta key not needed anymore for this product
				delete_post_meta( $product_id, $out_of_stock_date_key );
				Helpers::delete_transients();
			}
			
		}
		
	}

	/**
	 * Delete the ATUM transients after the product stock changes
	 *
	 * @since 0.1.5
	 *
	 * @param \WC_Product $product   The product
	 */
	public function delete_transients($product) {
		Helpers::delete_transients();
	}

	/**
	 * Add the purchase price field to WC's product data meta box
	 *
	 * @since 1.2.0
	 *
	 * @param int      $loop             Only for variations. The loop item number
	 * @param array    $variation_data   Only for variations. The variation item data
	 * @param \WP_Post $variation        Only for variations. The variation product
	 */
	public function add_purchase_price_meta ($loop = NULL, $variation_data = array(), $variation = NULL) {

		if ( ! current_user_can( ATUM_PREFIX . 'edit_purchase_price') ) {
			return;
		}

		$field_title = __( 'Purchase price', ATUM_TEXT_DOMAIN ) . ' (' . get_woocommerce_currency_symbol() . ')';

		if ( empty($variation) ) {

			woocommerce_wp_text_input( array(
				'id'        => '_purchase_price',
			    'label'     => $field_title,
			    'data_type' => 'price'
			) );

		}
		else {

			woocommerce_wp_text_input( array(
				'id'            => "variation_purchase_price_{$loop}",
				'name'          => "variation_purchase_price[$loop]",
				'value'         => get_post_meta($variation->ID, '_purchase_price', TRUE),
				'label'         => $field_title,
				'wrapper_class' => 'form-row form-row-first',
				'data_type'     => 'price'
			) );

		}

	}

	/**
	 * Save the purchase price meta on product post savings
	 *
	 * @since 1.2.0
	 *
	 * @param int $post_id
	 */
	public function save_purchase_price ($post_id) {

		$purchase_price = '';

		// Product variations
		if ( isset($_POST['variation_purchase_price']) ) {
			$purchase_price = (string) isset( $_POST['variation_purchase_price'] ) ? wc_clean( reset($_POST['variation_purchase_price']) ) : '';
			$purchase_price = ('' === $purchase_price) ? '' : wc_format_decimal( $purchase_price );
			update_post_meta( $post_id, '_purchase_price', $purchase_price );
		}
		else {

			$product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );

			if ( in_array( $product_type, Globals::get_inheritable_product_types() ) ) {
				// Inheritable products have no prices
				update_post_meta( $post_id, '_purchase_price', $purchase_price );
			}
			else {
				$purchase_price = (string) isset( $_POST['_purchase_price'] ) ? wc_clean( $_POST['_purchase_price'] ) : '';
				$purchase_price = ('' === $purchase_price) ? '' : wc_format_decimal( $purchase_price );
				update_post_meta( $post_id, '_purchase_price', $purchase_price);
			}

		}
		
		// Add WPML compatibility
		if (class_exists('\woocommerce_wpml')) {
			
			global $sitepress;
			$wpml = \woocommerce_wpml::instance();
			
			$post_type = get_post_type( $post_id );
			
			$product_translations = $sitepress->get_element_translations( $sitepress->get_element_trid($post_id, "post_{$post_type}"), "post_{$post_type}" );
			foreach($product_translations as $translation){

				if( $translation->element_id !==  $post_id){
					update_post_meta( $translation->element_id, '_purchase_price', $purchase_price);
				}

			}

		}

	}
	
	/**
	 * Add purchase price to WPML's custom price fields
	 *
	 * @since 1.3.0
	 *
	 * @param array   $prices      Custom prices fields
	 * @param integer $product_id  The product ID
	 *
	 * @return array
	 */
	public function wpml_add_purchase_price_to_custom_prices( $prices, $product_id ) {
		
		$prices[] = '_purchase_price';
		return $prices;
	}
	
	/**
	 * Add purchase price to WPML's custom price fields labels
	 *
	 * @since 1.3.0
	 *
	 * @param array   $labels       Custom prices fields labels
	 * @param integer $product_id   The product ID
	 *
	 * @return array
	 */
	public function wpml_add_purchase_price_to_custom_price_labels( $labels, $product_id ) {
		
		$labels['_purchase_price'] = __( 'Purchase Price', ATUM_TEXT_DOMAIN );
		return $labels;
	}
	
	/**
	 * Sanitize WPML's purchase prices
	 *
	 * @since 1.3.0
	 *
	 * @param array  $prices
	 * @param string $code
	 * @param bool   $variation_id
	 *
	 * @return array
	 */
	public function wpml_sanitize_purchase_price_in_custom_prices( $prices, $code, $variation_id = false ) {
	
		if ($variation_id) {
			$prices['_purchase_price'] = ( ! empty( $_POST['_custom_variation_purchase_price'][$code][$variation_id]) ) ? wc_format_decimal( $_POST['_custom_variation_purchase_price'][$code][$variation_id] ) : '';
		}
		else {
			$prices['_purchase_price'] = ( ! empty( $_POST['_custom_purchase_price'][$code]) )? wc_format_decimal( $_POST['_custom_purchase_price'][$code] ) : '';
		}
	
		return $prices;
	}
	
	
	/**
	 * Save WPML's purchase price when custom prices are enabled
	 *
	 * @since 1.3.0
	 *
	 * @param int    $post_id
	 * @param float  $product_price
	 * @param array  $custom_prices
	 * @param string $code
	 */
	public function wpml_save_purchase_price_in_custom_prices( $post_id, $product_price, $custom_prices, $code ) {
	
		if ( isset( $custom_prices[ '_purchase_price'] ) ) {
			update_post_meta( $post_id, "_purchase_price_{$code}", $custom_prices['_purchase_price'] );
		}
	}

	/**
	 * Add the location to the items table in WC orders
	 *
	 * @since 1.3.3
	 *
	 * @param \WC_Order $wc_order
	 */
	public function wc_order_add_location_column_header($wc_order) {
		?><th class="item_location sortable" data-sort="string-ins"><?php _e( 'Location', ATUM_TEXT_DOMAIN ); ?></th><?php
	}

	/**
	 * Add the location to the items table in WC orders
	 *
	 * @since 1.3.3
	 *
	 * @param \WC_Product    $product
	 * @param \WC_Order_Item $item
	 * @param int            $item_id
 	 */
	public function wc_order_add_location_column_value($product, $item, $item_id) {

		if ($product) {
			$product_id = ( $product->get_type() == 'variation' ) ? $product->get_parent_id() : $product->get_id();
			$locations  = wc_get_product_terms( $product_id, Globals::PRODUCT_LOCATION_TAXONOMY, array( 'fields' => 'names' ) );
			$locations_list = ( ! empty( $locations ) ) ? implode( ', ', $locations ) : '&ndash;';
		}

		?>
		<td class="item_location"<?php if ($product) echo ' data-sort-value="' . $locations_list . '"' ?>>
			<?php if ($product): ?>
			<div class="view"><?php echo $locations_list ?></div>
			<?php else: ?>
			&nbsp;
			<?php endif; ?>
		</td>
		<?php
	}

	/**
	 * Sets the stock status in WooCommerce products' list when ATUM is managing the stock
	 *
	 * @since 1.2.6
	 *
	 * @param string      $stock_html   The HTML markup for the stock status
	 * @param \WC_Product $the_product  The product that is currently checked
	 *
	 * @return string
	 */
	public function set_wc_products_list_stock_status($stock_html, $the_product) {

		if (
			Helpers::is_atum_managing_stock() &&
			Helpers::get_option('show_variations_stock', 'yes') == 'yes' &&
			in_array( $the_product->get_type(), ['variable', 'variable-subscription'] )
		) {

			// WC Subscriptions compatibility
			if ( class_exists('\WC_Subscriptions') && $the_product->get_type() == 'variable-subscription') {
				$variable_product = new \WC_Product_Variable_Subscription( $the_product->get_id() );
			}
			else {
				$variable_product = new \WC_Product_Variable( $the_product->get_id() );
			}

			// Get the variations within the variable
			$variations = $variable_product->get_children();
			$stock_status = __('Out of stock', ATUM_TEXT_DOMAIN);
			$stocks_list = array();

			if ( ! empty($variations) ) {

				foreach ($variations as $variation_id) {
					$variation_product = wc_get_product($variation_id);
					$variation_stock = $variation_product->get_stock_quantity();
					$stocks_list[] = $variation_stock;

					if ($variation_stock > 0) {
						$stock_status = __('In stock', ATUM_TEXT_DOMAIN);
					}
				}

			}

			if ( empty($stocks_list) ) {
				$stock_html = '<mark class="outofstock">' . $stock_status . '</mark> (0)';
			}
			else {
				$class = ( $stock_status == __('Out of stock', ATUM_TEXT_DOMAIN)  ) ? 'outofstock' : 'instock';
				$stock_html = '<mark class="' . $class . '">' . $stock_status . '</mark> (' . implode( ', ', array_map('intval', $stocks_list) ) . ')';
			}

		}

		return $stock_html;

	}
	
	/**
	 * Setup help pointers for some Atum screens
	 *
	 * @since 0.1.6
	 */
	public function setup_help_pointers() {
		
		$pointers = array(
			array(
				'id'       => self::$main_menu_item['slug'] . '-help-tab',      // Unique id for this pointer
				'next'     => 'screen-tab',
				'screen'   => 'toplevel_page_' . self::$main_menu_item['slug'], // This is the page hook we want our pointer to show on
				'target'   => '#contextual-help-link-wrap',                     // The css selector for the pointer to be tied to, best to use ID's
				'title'    => __('ATUM Quick Help', ATUM_TEXT_DOMAIN),
				'content'  => __("Click the 'Help' tab to learn more about the ATUM's Stock Central.", ATUM_TEXT_DOMAIN),
				'position' => array(
					'edge'  => 'top',                                           // Top, bottom, left, right
					'align' => 'right'                                           // Top, bottom, left, right, middle
				),
				'arrow_position' => array(
					'left' => 'auto',
					'right' => '32px'
				)
			),
			array(
				'id'       => self::$main_menu_item['slug'] . '-screen-tab',
				'screen'   => 'toplevel_page_' . self::$main_menu_item['slug'],
				'target'   => '#screen-options-link-wrap',
				'title'    => __('ATUM Screen Setup', ATUM_TEXT_DOMAIN),
				'content'  => __("Click the 'Screen Options' tab to setup your table view preferences.", ATUM_TEXT_DOMAIN),
				'position' => array(
					'edge'  => 'top',
					'align' => 'left'
				),
				'arrow_position' => array(
					'left' => 'auto',
					'right' => '166px'
				)
			)
		);
		
		// Instantiate the class and pass our pointers array to the constructor
		new HelpPointers( $pointers );
		
	}

	/**
	 * Enqueue styles on WP admin
	 *
	 * @since 1.2.3
	 */
	public function admin_styles() {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Enqueue ATUM widgets styles for WP dashboard
		if ( in_array( $screen_id, array( 'dashboard' ) ) ) {
			wp_register_style( 'atum_admin_dashboard_styles', ATUM_URL . '/assets/css/atum-dashboard-widgets.css', array(), ATUM_VERSION );
			wp_enqueue_style( 'atum_admin_dashboard_styles' );

			wp_register_script( 'circle-progress', ATUM_URL . 'assets/js/vendor/circle-progress.min.js', array('jquery'), ATUM_VERSION, TRUE );
			wp_enqueue_script( 'circle-progress' );
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

		if ( isset( $current_screen->id ) && $current_screen->parent_base == self::$main_menu_item['slug'] ) {

			// Change the footer text
			if ( ! get_option( 'atum_admin_footer_text_rated' ) ) {

				$footer_text = sprintf( __( 'If you like <strong>ATUM</strong> please leave us a %s&#9733;&#9733;&#9733;&#9733;&#9733;%s rating. A huge thanks in advance!', ATUM_TEXT_DOMAIN ), '<a href="https://wordpress.org/support/plugin/atum-stock-manager-for-woocommerce/reviews/?filter=5#new-post" target="_blank" class="wc-rating-link" data-rated="' . esc_attr__( 'Thanks :)', ATUM_TEXT_DOMAIN ) . '">', '</a>' );
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
	 * Set min and step value for the stock quantity input number field (WC default = 1)
	 *
	 * @since 1.3.4
	 *
	 * @param int         $value
	 * @param \WC_Product $product
	 *
	 * @return float|int
	 */
	public function stock_quantity_input_atts($value, $product) {
		return 10 / pow(10, Globals::get_stock_decimals() + 1);
	}

	/**
	 * Round the stock quantity according to the number of decimals specified in settings
	 *
	 * @since 1.3.4
	 *
	 * @param float|int $qty
	 *
	 * @return float|int
	 */
	public function round_stock_quantity($qty) {

		if ( ! Globals::get_stock_decimals() ) {
			return intval($qty);
		}
		else {
			return round( floatval($qty), Globals::get_stock_decimals() );
		}

	}

	/**
	 * Customise the "Add to cart" messages to allow decimal places
	 *
	 * @since 1.3.4.1
	 *
	 * @param string $message
	 * @param int|array $products
	 *
	 * @return string
	 */
	public function add_to_cart_message( $message, $products ) {

		$titles = array();
		$count  = 0;

		foreach ( $products as $product_id => $qty ) {
			$titles[] = ( $qty != 1 ? round( floatval( $qty ), Globals::get_stock_decimals() ) . ' &times; ' : '' ) . sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', ATUM_TEXT_DOMAIN ), strip_tags( get_the_title( $product_id ) ) );
			$count   += $qty;
		}

		$titles     = array_filter( $titles );
		$added_text = sprintf( _n( '%s has been added to your cart.', '%s have been added to your cart.', $count, ATUM_TEXT_DOMAIN ), wc_format_list_of_items( $titles ) );

		// Output success messages
		if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			$return_to = apply_filters( 'woocommerce_continue_shopping_redirect', wc_get_raw_referer() ? wp_validate_redirect( wc_get_raw_referer(), FALSE ) : wc_get_page_permalink( 'shop' ) );
			$message   = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( $return_to ), esc_html__( 'Continue shopping', ATUM_TEXT_DOMAIN ), esc_html( $added_text ) );
		}
		else {
			$message = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( wc_get_page_permalink( 'cart' ) ), esc_html__( 'View cart', ATUM_TEXT_DOMAIN ), esc_html( $added_text ) );
		}

		return $message;

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
	 * @return Main instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}