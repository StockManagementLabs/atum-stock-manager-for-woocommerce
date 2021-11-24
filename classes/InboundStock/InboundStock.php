<?php
/**
 * Inbound Stock page
 *
 * @package         Atum
 * @subpackage      InboundStock
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.3.0
 */

namespace Atum\InboundStock;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumListTables\AtumListPage;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Settings\Settings;
use Atum\InboundStock\Lists\ListTable;


class InboundStock extends AtumListPage {
	
	/**
	 * The singleton instance holder
	 *
	 * @var InboundStock
	 */
	private static $instance;

	/**
	 * The admin page slug
	 */
	const UI_SLUG = 'atum-inbound-stock';

	/**
	 * The menu order for this module
	 */
	const MENU_ORDER = 4;
	
	/**
	 * InboundStock singleton constructor
	 *
	 * @since 1.3.0
	 */
	private function __construct() {

		// Add the module menu.
		add_filter( 'atum/admin/menu_items', array( $this, 'add_menu' ), self::MENU_ORDER );

		if ( is_admin() ) {

			// Initialize on admin page load.
			add_action( 'load-' . Globals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG, array( $this, 'screen_options' ) );

			$this->init_hooks();

		}
		
	}

	/**
	 * Add the Inbound Stock menu
	 *
	 * @since 1.3.6
	 *
	 * @param array $menus
	 *
	 * @return array
	 */
	public function add_menu( $menus ) {

		$menus['inbound-stock'] = array(
			'title'      => __( 'Inbound Stock', ATUM_TEXT_DOMAIN ),
			'callback'   => array( $this, 'display' ),
			'slug'       => self::UI_SLUG,
			'menu_order' => self::MENU_ORDER,
			'capability' => ATUM_PREFIX . 'read_inbound_stock',
		);

		return $menus;

	}
	
	/**
	 * Display the Inbound Stock admin page
	 *
	 * @since 1.3.0
	 */
	public function display() {

		$this->set_per_page();
		parent::display();

		Helpers::load_view( 'list-tables/inbound-stock', array(
			'list' => $this->list,
			'ajax' => Helpers::get_option( 'enable_ajax_filter', 'yes' ),
		) );
		
	}
	
	/**
	 * Enable Screen options creating the list table before the Screen option panel is rendered and enable "per page" option
	 *
	 * @since 1.3.0
	 */
	public function screen_options() {

		$this->set_per_page();

		// Add "Products per page" screen option.
		$args = array(
			'label'   => __( 'Products per page', ATUM_TEXT_DOMAIN ),
			'default' => $this->per_page,
			'option'  => self::UI_SLUG . '_entries_per_page',
		);
		
		add_screen_option( 'per_page', $args );

		// Add the help tab.
		$help_tabs = array(
			array(
				'name'  => 'columns',
				'title' => __( 'Columns', ATUM_TEXT_DOMAIN ),
			),
		);

		Helpers::add_help_tab( $help_tabs, $this );
		
		$this->list = new ListTable( [ 'per_page' => $this->per_page ] );
		
	}

	/* @noinspection PhpUnusedParameterInspection */
	/**
	 * Display the help tabs' content
	 *
	 * @since 0.0.2
	 *
	 * @param \WP_Screen $screen    The current screen.
	 * @param array      $tab       The current help tab.
	 */
	public function help_tabs_content( $screen, $tab ) {

		Helpers::load_view( 'help-tabs/inbound-stock/' . $tab['name'] );
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
	 * @return InboundStock instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}
