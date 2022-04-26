<?php
/**
 * Stock Central page
 *
 * @package         Atum
 * @subpackage      StockCentral
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           0.0.1
 */

namespace Atum\StockCentral;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumListTables\AtumListPage;
use Atum\Components\AtumHelpPointers;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Settings\Settings;
use Atum\StockCentral\Lists\ListTable;


class StockCentral extends AtumListPage {
	
	/**
	 * The singleton instance holder
	 *
	 * @var StockCentral
	 */
	private static $instance;

	/**
	 * The ATUM Stock Central admin page slug
	 */
	const UI_SLUG = 'atum-stock-central';

	/**
	 * The menu order for this module
	 */
	const MENU_ORDER = 2;
	
	/**
	 * StockCentral singleton constructor
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		// Add the module menu.
		add_filter( 'atum/admin/menu_items', array( $this, 'add_menu' ), self::MENU_ORDER );

		if ( is_admin() ) {

			// Initialize on admin page load.
			add_action( 'load-' . Globals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG, array( $this, 'screen_options' ) );
			add_action( 'load-toplevel_page_' . self::UI_SLUG, array( $this, 'screen_options' ) );

			// Add the Stock Central settings.
			add_filter( 'atum/settings/tabs', array( $this, 'add_settings_tab' ) );
			add_filter( 'atum/settings/defaults', array( $this, 'add_settings_defaults' ) );

			// Register the help pointers.
			add_action( 'admin_enqueue_scripts', array( $this, 'setup_help_pointers' ) );

			$this->init_hooks();

		}
		
	}

	/**
	 * Add the Stock Central menu
	 *
	 * @since 1.3.6
	 *
	 * @param array $menus
	 *
	 * @return array
	 */
	public function add_menu( $menus ) {

		$menus['stock-central'] = array(
			'title'      => __( 'Stock Central', ATUM_TEXT_DOMAIN ),
			'callback'   => array( $this, 'display' ),
			'slug'       => self::UI_SLUG,
			'menu_order' => self::MENU_ORDER,
		);

		return $menus;

	}
	
	/**
	 * Display the Stock Central admin page
	 *
	 * @since 0.0.1
	 */
	public function display() {

		$this->set_per_page();
		parent::display();

		$sc_url = add_query_arg( 'page', self::UI_SLUG, admin_url( 'admin.php' ) );

		if ( ! $this->is_uncontrolled_list ) {
			$sc_url = add_query_arg( 'uncontrolled', 1, $sc_url );
		}

		Helpers::load_view( 'list-tables/stock-central', array(
			'list'                 => $this->list,
			'ajax'                 => Helpers::get_option( 'enable_ajax_filter', 'yes' ),
			'is_uncontrolled_list' => $this->is_uncontrolled_list,
			'sc_url'               => $sc_url,
		) );
		
	}
	
	/**
	 * Enable Screen options creating the list table before the Screen option panel is rendered and enable
	 * "per page" option. Also add help tabs and help sidebar
	 *
	 * @since 0.0.2
	 */
	public function screen_options() {

		$this->set_per_page();

		// Add "Products per page" to screen options tab.
		add_screen_option( 'per_page', array(
			'label'   => __( 'Products per page', ATUM_TEXT_DOMAIN ),
			'default' => $this->per_page,
			'option'  => str_replace( '-', '_', self::UI_SLUG . '_entries_per_page' ),
		) );
		
		$help_tabs = array(
			array(
				'name'  => 'general',
				'title' => __( 'General', ATUM_TEXT_DOMAIN ),
			),
			array(
				'name'  => 'views',
				'title' => __( 'Views', ATUM_TEXT_DOMAIN ),
			),
			array(
				'name'  => 'product-details',
				'title' => __( 'Product Details', ATUM_TEXT_DOMAIN ),
			),
			array(
				'name'  => 'stock-counters',
				'title' => __( 'Stock Counters', ATUM_TEXT_DOMAIN ),
			),
			array(
				'name'  => 'stock-negatives',
				'title' => __( 'Stock Negatives', ATUM_TEXT_DOMAIN ),
			),
			array(
				'name'  => 'stock-selling-manager',
				'title' => __( 'Stock Selling Manager', ATUM_TEXT_DOMAIN ),
			),
		);
		
		$screen = get_current_screen();
		
		foreach ( $help_tabs as $help_tab ) {

			$screen->add_help_tab( array_merge( array(
				'id'       => ATUM_PREFIX . __CLASS__ . '_help_tabs_' . $help_tab['name'],
				'callback' => array( $this, 'help_tabs_content' ),
			), $help_tab ) );

		}
		
		$screen->set_help_sidebar( Helpers::load_view_to_string( 'help-tabs/help-sidebar' ) );

		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( isset( $_GET['uncontrolled'] ) && 1 === absint( $_GET['uncontrolled'] ) ) {
			$this->is_uncontrolled_list = TRUE;
		}

		$namespace  = __NAMESPACE__ . '\Lists';
		$list_class = $this->is_uncontrolled_list ? "$namespace\UncontrolledListTable" : "$namespace\ListTable";
		$this->list = new $list_class( [
			'per_page'        => $this->per_page,
			'show_cb'         => TRUE,
			'show_controlled' => ! $this->is_uncontrolled_list,
		] );
		
	}

	/**
	 * Display the help tabs' content
	 *
	 * @since 0.0.2
	 *
	 * @param \WP_Screen $screen    The current screen.
	 * @param array      $tab       The current help tab.
	 */
	public function help_tabs_content( $screen, $tab ) {
		
		Helpers::load_view( 'help-tabs/stock-central/' . $tab['name'] );
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

		$tabs['stock_central'] = array(
			'label'    => __( 'Stock Central', ATUM_TEXT_DOMAIN ),
			'icon'     => 'atmi-layers',
			'sections' => array(
				'stock_central' => __( 'Stock Central Options', ATUM_TEXT_DOMAIN ),
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
		
		$sc_columns = array();

		foreach ( ListTable::get_table_columns( TRUE ) as $column => $label ) {
			$sc_columns[ $column ] = array(
				'value' => 'yes', // All enabled by default.
				'name'  => wp_strip_all_tags( $label ),
			);
		}
		
		$defaults['sc_columns'] = array(
			'group'           => 'stock_central',
			'section'         => 'stock_central',
			'name'            => __( 'Available columns', ATUM_TEXT_DOMAIN ),
			'desc'            => __( "If there is any column in Stock Central that you don't need, you can remove it from here. Please, note that if you hide the column from Screen Options it will just hide it but disabling it from here, will unload it completely.", ATUM_TEXT_DOMAIN ),
			'type'            => 'multi_checkbox',
			'default'         => 'yes',
			'default_options' => $sc_columns,
		);

		$defaults['posts_per_page'] = array(
			'group'   => 'stock_central',
			'section' => 'stock_central',
			'name'    => __( 'Products per page', ATUM_TEXT_DOMAIN ),
			'desc'    => __( "Controls the number of products displayed per page on the Stock Central list. Please note, you can set this value within the 'Screen Options' tab as well and this last value will have preference over this one as will be saved per user. Enter '-1' to remove the pagination and display all available products on one page (not recommended if your store contains a large number of products as it may affect the performance).", ATUM_TEXT_DOMAIN ),
			'type'    => 'number',
			'default' => Settings::DEFAULT_POSTS_PER_PAGE,
			'options' => array(
				'min' => -1,
				'max' => 500,
			),
		);

		$defaults['sale_days'] = array(
			'group'   => 'stock_central',
			'section' => 'stock_central',
			'name'    => __( 'Days to re-order', ATUM_TEXT_DOMAIN ),
			'desc'    => __( "This value sets the number of days a user needs to replenish the stock levels. It controls the 'Restock Status' indicator within the 'Stock Central' page.", ATUM_TEXT_DOMAIN ),
			'type'    => 'number',
			'default' => Settings::DEFAULT_SALE_DAYS,
			'options' => array(
				'min' => 1,
				'max' => 365,
			),
		);

		$defaults['expandable_rows'] = array(
			'group'   => 'stock_central',
			'section' => 'stock_central',
			'name'    => __( 'Expandable rows', ATUM_TEXT_DOMAIN ),
			'desc'    => __( 'Show variable and grouped products expanded (ON) or collapsed (OFF) by default.', ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'no',
		);

		// WC Subscriptions compatibility.
		if ( class_exists( '\WC_Subscriptions' ) ) {

			$defaults['show_subscriptions'] = array(
				'group'   => 'stock_central',
				'section' => 'stock_central',
				'name'    => __( 'Show subscription products', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'When enabled, ATUM will show the subscription products from WooCommerce Subscriptions plugin in Stock Central.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			);

		}

		// WC Bookings compatibility.
		if ( class_exists( '\WC_Bookings' ) ) {

			$defaults['show_bookable_products'] = array(
				'group'   => 'stock_central',
				'section' => 'stock_central',
				'name'    => __( 'Show bookable products', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'When enabled, ATUM will show the Bookable products from WooCommerce Bookings plugin in Stock Central.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			);

		}

		// WC Bundles compatibility.
		if ( class_exists( '\WC_Bundles' ) ) {

			$defaults['show_bundles'] = array(
				'group'   => 'stock_central',
				'section' => 'stock_central',
				'name'    => __( 'Show bundle products', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'When enabled, ATUM will show the bundle products from WooCommerce Product Bundles in Stock Central.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			);

		}

		return $defaults;

	}

	/**
	 * Setup help pointers for some Atum screens
	 *
	 * @since 1.4.10
	 */
	public function setup_help_pointers() {

		$screen_id = Globals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG;

		$pointers = array(
			array(
				'id'             => self::UI_SLUG . '-screen-tab',  // Unique id for this pointer.
				'screen'         => $screen_id,                     // This is the page hook we want our pointer to show on.
				'target'         => '#screen-options-link-wrap',    // The css selector for the pointer to be tied to, best to use ID's.
				'next'           => '#contextual-help-link-wrap',   // The help tip that will be displayed next.
				'title'          => __( 'ATUM Stock Central Screen Options', ATUM_TEXT_DOMAIN ),
				'content'        => __( "Click the 'Screen Options' tab to add/hide/show columns within the Stock Central view.", ATUM_TEXT_DOMAIN ),
				'position'       => array(
					'edge'  => 'top',                               // Top, bottom, left, right.
					'align' => 'left',                              // Top, bottom, left, right, middle.
				),
				'arrow_position' => array(
					'right' => '32px',
				),
			),
			array(
				'id'             => self::UI_SLUG . '-help-tab',
				'screen'         => $screen_id,
				'target'         => '#contextual-help-link-wrap',
				'title'          => __( 'ATUM Quick Help', ATUM_TEXT_DOMAIN ),
				'content'        => __( "Click the 'Help' tab to learn more about the ATUM's Stock Central.", ATUM_TEXT_DOMAIN ),
				'position'       => array(
					'edge'  => 'top',
					'align' => 'right',
				),
				'arrow_position' => array(
					'left' => '84%',
				),
			),
		);

		// Instantiate the class and pass our pointers array to the constructor.
		/* @deprecated Use the AtumHelpGuide instead */
		new AtumHelpPointers( $pointers );

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
	 * @return StockCentral instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}
