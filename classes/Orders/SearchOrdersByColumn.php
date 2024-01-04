<?php
/**
 * Adds the "Search Orders By Column" field to the orders list
 *
 * @since       1.9.30
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 Stock Management Labs™
 *
 * @package     Atum\Orders
 */

namespace Atum\Orders;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;

class SearchOrdersByColumn {

	/**
	 * The singleton instance holder
	 *
	 * @var SearchOrdersByColumn
	 */
	private static $instance;

	/**
	 * Searchable columns
	 *
	 * @var array
	 */
	private $search_columns = [];

	/**
	 * The available order types IDs
	 *
	 * @var array
	 */
	private $order_types_ids = array();

	/**
	 * SearchOrdersByColumn singleton constructor
	 *
	 * @since 1.9.30
	 */
	private function __construct() {

		if ( is_admin() ) {

			// Delay the execution until all the ATUM add-ons have been loaded.
			add_action( 'atum/after_init', array( $this, 'init' ), 100 );

			// Add the search-by-column field to the WC Orders list.
			add_action( 'restrict_manage_posts', array( $this, 'add_search_by_column_field' ) );
			add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'add_search_by_column_field' ) ); // HPOS support.

			// Enqueue scripts and styles.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		}

	}

	/**
	 * Initialize class props
	 *
	 * @since 1.9.30
	 */
	public function init() {

		// Allow adding the columns externally. For now, we don't have columns in ATUM free.
		$this->search_columns  = apply_filters( 'atum/orders/search_by_column/columns', $this->search_columns );
		$this->order_types_ids = apply_filters( 'atum/orders/search_by_column/order_type_ids', Globals::get_order_type_id( '' ) );

	}

	/**
	 * Add the search-by-column field to Orders' List Tables when applicable
	 *
	 * @since 1.9.30
	 *
	 * @param string $post_type
	 */
	public function add_search_by_column_field( $post_type ) {

		if ( ! empty( $this->search_columns ) && in_array( $post_type, array_keys( $this->order_types_ids ), TRUE ) ) {

			$args = array(
				'ajax'            => FALSE,
				'show_atum_icon'  => 'shop_order' === $post_type,
				'menu_items'      => $this->search_columns,
				'no_option'       => __( 'Search By', ATUM_TEXT_DOMAIN ),
				'no_option_title' => __( 'Search By', ATUM_TEXT_DOMAIN ),
			);

			Helpers::load_view( 'list-tables/search-by-column-field', $args );

		}

	}

	/**
	 * Enqueue scripts for the search-by-column field
	 *
	 * @since 1.9.30
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		global $post_type;

		if (
			! empty( $this->search_columns ) && (
				( Helpers::is_using_cot_list() && function_exists( 'wc_get_page_screen_id' ) && wc_get_page_screen_id( 'shop-order' ) === $hook && ! isset( $_GET['id'] ) ) ||
				( 'edit.php' === $hook && in_array( $post_type, array_keys( $this->order_types_ids ) ) ) || 'woocommerce_page_wc-orders' === $hook
			)
		) {

			wp_register_style( 'atum-search-orders', ATUM_URL . 'assets/css/atum-search-orders.css', [], ATUM_VERSION );
			wp_register_script( 'atum-search-orders', ATUM_URL . 'assets/js/build/atum-search-orders.js', [ 'jquery' ], ATUM_VERSION, TRUE );

			wp_enqueue_style( 'atum-search-orders' );
			wp_enqueue_script( 'atum-search-orders' );

		}

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
	 * @return SearchOrdersByColumn instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


}
