<?php
/**
 * @package         Atum
 * @subpackage      InboundStock
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.3.0
 */

namespace Atum\InboundStock;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumListTables\AtumListPage;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Settings\Settings;
use Atum\InboundStock\Inc\ListTable;


class InboundStock extends AtumListPage {
	
	/**
	 * The singleton instance holder
	 * @var InboundStock
	 */
	private static $instance;

	/*
	 * The admin page slug
	 */
	const UI_SLUG = 'atum-inbound-stock';
	
	/**
	 * InboundStock singleton constructor
	 *
	 * @since 1.3.0
	 */
	private function __construct() {
		
		$user_option = get_user_meta( get_current_user_id(), 'products_per_page', TRUE );
		$this->per_page = ( $user_option ) ? $user_option : Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE );

		// Initialize on admin page load
		add_action( 'load-' . Globals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG, array( $this, 'screen_options' ) );

		// Reduce the products shown in List Table to those included within Purchase Orders
		//add_action( 'pre_get_posts', array($this, 'filter_po_products') );

		parent::init_hooks();
		
	}
	
	/**
	 * Display the Inbound Stock admin page
	 *
	 * @since 1.3.0
	 */
	public function display() {
		
		parent::display();

		Helpers::load_view( 'inbound-stock', array(
			'list' => $this->list,
			'ajax' => Helpers::get_option( 'enable_ajax_filter', 'yes' ),
		) );
		
	}

	/**
	 * Filter the Inbound Stock products to only show those included within Purchase Orders
	 *
	 * @since 1.3.0
	 *
	 * @param \WP_Query $query
	 */
	public function filter_po_products($query) {

		global $plugin_page, $wpdb;

		if ($plugin_page == self::UI_SLUG) {

			$sql = $wpdb->prepare("
				SELECT meta_value, order_id 
				FROM `{$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` AS oi 
				LEFT JOIN `{$wpdb->atum_order_itemmeta}` AS oim ON oi.`order_item_id` = oim.`order_item_id`
				LEFT JOIN `{$wpdb->posts}` AS p ON oi.`order_id` = p.`ID`
				WHERE meta_key IN ('_product_id', '_variation_id') AND order_item_type = 'line_item' 
				AND p.`post_type` = %s AND meta_value > 0
				ORDER BY oi.`order_item_id` DESC;",
				PurchaseOrders::POST_TYPE
			);

			$po_products = $wpdb->get_results($sql);
			$post_in = array(-1); // No results when no products were found within POs

			if ( ! empty($po_products) ) {
				$post_in = wp_list_pluck($po_products, 'meta_value');
				$query->set( 'order_ids', wp_list_pluck($po_products, 'order_id') );
			}

			$query->set( 'post__in', $post_in );

		}

	}
	
	/**
	 * Enable Screen options creating the list table before the Screen option panel is rendered and enable
	 * "per page" option. Also add help tabs and help sidebar
	 *
	 * @since 1.3.0
	 *
	 * @TODO
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
	 * @since 1.3.0
	 *
	 * @param \WP_Screen $screen    The current screen
	 * @param array      $tab       The current help tab
	 *
	 * @TODO
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
	 * @return InboundStock instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}