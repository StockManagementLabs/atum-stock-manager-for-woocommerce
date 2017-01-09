<?php
/**
 * @package         Atum
 * @subpackage      StockCentral
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       (c)2017 Stock Management Labs
 *
 * @since           0.0.1
 *
 */

namespace Atum\StockCentral;

defined( 'ABSPATH' ) or die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Settings\Settings;
use Atum\StockCentral\Inc\StockCentralList;


class StockCentral {
	
	/**
	 * The singleton instance holder
	 * @var StockCentral
	 */
	private static $instance;
	
	/**
	 * Table rows per page
	 * @var int
	 */
	protected $per_page;
	
	/**
	 * The list
	 * @var StockCentralList
	 */
	protected $list;
	
	/**
	 * StockCentral singleton constructor
	 *
	 * @since 0.0.1
	 */
	private function __construct() {
		
		$user_option = get_user_meta( wp_get_current_user()->ID, 'products_per_page', TRUE );
		
		$this->per_page = ( $user_option ) ? $user_option : Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE );
		
		add_action( 'load-toplevel_page_' . Globals::ATUM_UI_SLUG, array( $this, 'screen_options' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
		
	}
	
	/**
	 * Display the Stock Central admin page
	 *
	 * @since 0.0.1
	 */
	public function display() {
		
		$this->list->prepare_items();
		Helpers::load_view( 'stock-central', array(
			'list' => $this->list,
			'ajax' => Helpers::get_option( 'enable_ajax_filter', 'yes' ),
		) );
		
	}
	
	/**
	 * Save products per page option.
	 *
	 * @since 0.0.2
	 *
	 * @param bool|int $status Screen option value. Default false to skip.
	 * @param string   $option The option name.
	 * @param int      $value  The number of rows to use.
	 *
	 * @return mixed
	 */
	public static function set_screen_option( $status, $option, $value ) {
		
		return $value;
	}
	
	
	/**
	 * Enable Screen options creating the list table before the Screen option panel is rendered table and enable
	 * Per page option to Screen options. Also adds help tabs and help sidebar
	 *
	 * @since 0.0.2
	 */
	public function screen_options() {
		
		$option = 'per_page';
		$args   = array(
			'label'   => 'Products per page',
			'default' => $this->per_page,
			'option'  => 'products_per_page'
		);
		add_screen_option( $option, $args );
		
		$help_tabs = array(
			array(
				'name'  => 'general',
				'title' => __( 'General', ATUM_TEXT_DOMAIN ),
			),
			'product_details'       => array(
				'name'  => 'product-details',
				'title' => __( 'Product Details', ATUM_TEXT_DOMAIN ),
			),
			'stock_count'           => array(
				'name'  => 'stock-counters',
				'title' => __( 'Stock Counters', ATUM_TEXT_DOMAIN ),
			),
			'stock_negatives'       => array(
				'name'  => 'stock-negatives',
				'title' => __( 'Stock Negatives', ATUM_TEXT_DOMAIN ),
			),
			'stock_selling_manager' => array(
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
		
		$this->list = new StockCentralList( array('per_page' => $this->per_page) );
		
	}
	
	/**
	 * Display help tabs content getting it form a view
	 *
	 * @since 0.0.2
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
	 * @static
	 * @return StockCentral instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}