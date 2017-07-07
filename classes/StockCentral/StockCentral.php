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

use Atum\Components\AtumListPage;
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
	 * StockCentral singleton constructor
	 *
	 * @since 0.0.1
	 */
	private function __construct() {
		
		$user_option = get_user_meta( get_current_user_id(), 'products_per_page', TRUE );
		$this->per_page = ( $user_option ) ? $user_option : Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE );
		
		add_action( 'load-toplevel_page_' . Globals::ATUM_UI_SLUG, array( $this, 'screen_options' ) );

		parent::init_hooks();
		
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

		// Add "Products per page" screen option
		$args   = array(
			'label'   => __('Products per page', ATUM_TEXT_DOMAIN),
			'default' => $this->per_page,
			'option'  => 'products_per_page'
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
		
		$screen->set_help_sidebar( Helpers::load_view_to_string( 'help-tabs/stock-central-help-sidebar' ) );
		
		$this->list = new ListTable( array( 'per_page' => $this->per_page) );
		
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
		
		Helpers::load_view( 'help-tabs/stock-central-' . $tab['name'] );
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