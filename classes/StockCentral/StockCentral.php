<?php
/**
 * @package         Atum
 * @subpackage      StockCentral
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           0.0.1
 *
 */

namespace Atum\StockCentral;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumListTables\AtumListPage;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Settings\Settings;
use Atum\StockCentral\Inc\ListTable;


class StockCentral extends AtumListPage {
	
	/**
	 * The singleton instance holder
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
	const MENU_ORDER = 1;
	
	/**
	 * StockCentral singleton constructor
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		// Add the module menu
		add_filter( 'atum/admin/menu_items', array($this, 'add_menu'), self::MENU_ORDER );

		if ( is_admin() ) {

			$user_option    = get_user_meta( get_current_user_id(), ATUM_PREFIX . 'stock_central_products_per_page', TRUE );
			$this->per_page = $user_option ?: Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE );

			add_action( 'load-toplevel_page_' . self::UI_SLUG, array( $this, 'screen_options' ) );

			// Add the Stock Central settings
			add_filter( 'atum/settings/tabs', array( $this, 'add_settings_tab' ) );
			add_filter( 'atum/settings/defaults', array( $this, 'add_settings_defaults' ) );

			parent::init_hooks();

		}
		
	}

	/**
	 * Add the Stock Central menu. Must be the first element in the array
	 *
	 * @since 1.3.6
	 *
	 * @param array $menus
	 *
	 * @return array
	 */
	public function add_menu ($menus) {

		$menus['stock-central'] = array(
			'title'      => __( 'Stock Central', ATUM_TEXT_DOMAIN ),
			'callback'   => array( $this, 'display' ),
			'slug'       => self::UI_SLUG,
			'menu_order' => self::MENU_ORDER
		);

		return $menus;

	}
	
	/**
	 * Display the Stock Central admin page
	 *
	 * @since 0.0.1
	 */
	public function display() {
		
		parent::display();

		Helpers::load_view( 'stock-central', array(
			'list' => $this->list,
			'ajax' => Helpers::get_option( 'enable_ajax_filter', 'yes' ),
		) );
		
	}
	
	/**
	 * Enable Screen options creating the list table before the Screen option panel is rendered and enable
	 * "per page" option. Also add help tabs and help sidebar
	 *
	 * @since 0.0.2
	 */
	public function screen_options() {

		// Add "Products per page" to screen options tab
		$args   = array(
			'label'   => __('Products per page', ATUM_TEXT_DOMAIN),
			'default' => $this->per_page,
			'option'  => ATUM_PREFIX . 'stock_central_products_per_page'
		);
		
		add_screen_option( 'per_page', $args );
		
		$help_tabs = array(
			array(
				'name'  => 'general',
				'title' => __( 'General', ATUM_TEXT_DOMAIN ),
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
		
		$this->list = new ListTable( ['per_page' => $this->per_page] );
		
	}
	
	/**
	 * Display the help tabs' content
	 *
	 * @since 0.0.2
	 *
	 * @param \WP_Screen $screen    The current screen
	 * @param array      $tab       The current help tab
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
	public function add_settings_tab ($tabs) {

		$tabs['stock_central'] = array(
			'tab_name' => __( 'Stock Central', ATUM_TEXT_DOMAIN ),
			'sections' => array(
				'stock_central' => __( 'Stock Central Options', ATUM_TEXT_DOMAIN )
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

		$defaults['posts_per_page'] = array(
			'section' => 'stock_central',
			'name'    => __( 'Products per Page', ATUM_TEXT_DOMAIN ),
			'desc'    => __( "Controls the number of products displayed per page within the Stock Central screen. Please note, you can set this value within the 'Screen Options' tab as well. Enter '-1' to remove the pagination and display all available products on one page (not recommended if your store contains a large number of products as it may affect the performance).", ATUM_TEXT_DOMAIN ),
			'type'    => 'number',
			'default' => Settings::DEFAULT_POSTS_PER_PAGE
		);

		$defaults['sale_days'] = array(
			'section' => 'stock_central',
			'name'    => __( 'Days to Re-Order', ATUM_TEXT_DOMAIN ),
			'desc'    => __( "This value sets the number of days a user needs to replenish the stock levels. It controls the 'Low Stock' indicator within the 'Stock Central' page.", ATUM_TEXT_DOMAIN ),
			'type'    => 'number',
			'default' => Settings::DEFAULT_SALE_DAYS
		);

		return $defaults;

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
	 * @return StockCentral instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}