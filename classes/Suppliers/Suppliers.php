<?php
/**
 * Handles the Suppliers post type
 *
 * @package     Atum
 * @subpackage  Suppliers
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @since       1.2.9
 */

namespace Atum\Suppliers;

defined( 'ABSPATH' ) || die;

/**
// For WC navigation system.
use Automattic\WooCommerce\Admin\Features\Navigation\Menu;
use Automattic\WooCommerce\Admin\Features\Navigation\Screen;
 */

use Atum\Components\AtumCache;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumMarketingPopup;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Inc\Main;
use Atum\Legacy\SuppliersLegacyTrait;


class Suppliers {

	/**
	 * The singleton instance holder
	 *
	 * @var Suppliers
	 */
	private static $instance;
	
	/**
	 * The post type labels
	 *
	 * @var array
	 */
	protected $labels = array();

	/**
	 * Store the supplier being loaded
	 *
	 * @var Supplier
	 */
	private $supplier = NULL;
	
	/**
	 * The Supplier post type name
	 */
	const POST_TYPE = ATUM_PREFIX . 'supplier';
	
	/**
	 * The menu order
	 */
	const MENU_ORDER = 4;

	/**
	 * The Supplier field key
	 */
	const SUPPLIER_FIELD_KEY = '_supplier';

	/**
	 * The Supplier SKU field key
	 */
	const SUPPLIER_SKU_FIELD_KEY = '_supplier_sku';
	
	
	/**
	 * Suppliers singleton constructor
	 *
	 * @since 1.2.9
	 */
	private function __construct() {

		// Register the Supplier post type.
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Global hooks.
		if ( AtumCapabilities::current_user_can( 'read_supplier' ) ) {

			// Add the "Suppliers" link to the ATUM's admin bar menu.
			add_filter( 'atum/admin/top_bar/menu_items', array( $this, 'add_admin_bar_link' ), 12 );

			// Add item order.
			add_filter( 'atum/admin/menu_items_order', array( $this, 'add_item_order' ) );

		}

		// Admin hooks.
		if ( is_admin() ) {

			if ( AtumCapabilities::current_user_can( 'read_supplier' ) ) {

				// Add custom columns to Suppliers' post type list table.
				add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_columns' ) );
				add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_columns' ), 2 );

			}

			if ( AtumCapabilities::current_user_can( 'edit_supplier' ) ) {

				// Add meta boxes to Supplier post UI.
				add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_meta_boxes' ), 30 );

				// Save the supplier's meta boxes.
				add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ) );

				// Enqueue scripts.
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			}

		}

	}

	/**
	 * Register the Suppliers post type
	 *
	 * @param array $args
	 *
	 * @since 1.2.9
	 */
	public function register_post_type( $args = array() ) {

		// Minimum capability required.
		$is_user_allowed = AtumCapabilities::current_user_can( 'read_supplier' );
		$main_menu_item  = Main::get_main_menu_item();

		$this->labels = array(
			'name'                  => __( 'Suppliers', ATUM_TEXT_DOMAIN ),
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralContext
			'singular_name'         => _x( 'Supplier', self::POST_TYPE . ' post type singular name', ATUM_TEXT_DOMAIN ),
			'add_new'               => __( 'Add New Supplier', ATUM_TEXT_DOMAIN ),
			'add_new_item'          => __( 'Add New Supplier', ATUM_TEXT_DOMAIN ),
			'edit'                  => __( 'Edit', ATUM_TEXT_DOMAIN ),
			'edit_item'             => __( 'Edit Supplier', ATUM_TEXT_DOMAIN ),
			'new_item'              => __( 'New Supplier', ATUM_TEXT_DOMAIN ),
			'view'                  => __( 'View Supplier', ATUM_TEXT_DOMAIN ),
			'view_item'             => __( 'View Supplier', ATUM_TEXT_DOMAIN ),
			'search_items'          => __( 'Search Suppliers', ATUM_TEXT_DOMAIN ),
			'not_found'             => __( 'No suppliers found', ATUM_TEXT_DOMAIN ),
			'not_found_in_trash'    => __( 'No suppliers found in trash', ATUM_TEXT_DOMAIN ),
			'parent'                => __( 'Parent supplier', ATUM_TEXT_DOMAIN ),
			'menu_name'             => _x( 'Suppliers', 'Admin menu name', ATUM_TEXT_DOMAIN ),
			'filter_items_list'     => __( 'Filter suppliers', ATUM_TEXT_DOMAIN ),
			'items_list_navigation' => __( 'Suppliers navigation', ATUM_TEXT_DOMAIN ),
			'items_list'            => __( 'Suppliers list', ATUM_TEXT_DOMAIN ),
		);

		$args = apply_filters( 'atum/suppliers/post_type_args', wp_parse_args( array(
			'labels'              => $this->labels,
			'description'         => __( 'This is where Suppliers are stored.', ATUM_TEXT_DOMAIN ),
			'public'              => FALSE,
			'show_ui'             => $is_user_allowed,
			'publicly_queryable'  => FALSE,
			'exclude_from_search' => TRUE,
			'hierarchical'        => FALSE,
			'show_in_menu'        => $is_user_allowed ? $main_menu_item['slug'] : FALSE,
			'show_in_nav_menus'   => FALSE,
			'rewrite'             => FALSE,
			'query_var'           => is_admin(),
			'supports'            => array( 'title', 'thumbnail' ),
			'has_archive'         => FALSE,
			'capabilities'        => array(
				'edit_post'              => ATUM_PREFIX . 'edit_supplier',
				'read_post'              => ATUM_PREFIX . 'read_supplier',
				'delete_post'            => ATUM_PREFIX . 'delete_supplier',
				'edit_posts'             => ATUM_PREFIX . 'edit_suppliers',
				'edit_others_posts'      => ATUM_PREFIX . 'edit_others_suppliers',
				'publish_posts'          => ATUM_PREFIX . 'publish_suppliers',
				'read_private_posts'     => ATUM_PREFIX . 'read_private_suppliers',
				'create_posts'           => ATUM_PREFIX . 'create_suppliers',
				'delete_posts'           => ATUM_PREFIX . 'delete_suppliers',
				'delete_private_posts'   => ATUM_PREFIX . 'delete_private_suppliers',
				'delete_published_posts' => ATUM_PREFIX . 'delete_published_suppliers',
				'delete_other_posts'     => ATUM_PREFIX . 'delete_other_suppliers',
				'edit_private_posts'     => ATUM_PREFIX . 'edit_private_suppliers',
				'edit_published_posts'   => ATUM_PREFIX . 'edit_published_suppliers',
			),
		), $args ) );

		// Register the Suppliers post type.
		register_post_type( self::POST_TYPE, $args );

		/**
		// Add suppliers post type on wc navigation system.
		// Check if the WC method are availables.
		if ( class_exists( 'Automattic\WooCommerce\Admin\Features\Navigation\Screen' ) && method_exists( Screen::class, 'register_post_type' ) ) {
			Screen::register_post_type( self::POST_TYPE );
			add_action( 'atum/after_adding_menu', array( $this, 'add_supplier_post_type_wcmenu' ), 10, 0 );
		}
		*/

	}

	/**
	 * Set the columns for the Suppliers' list table
	 *
	 * @since 1.2.9
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function add_columns( $columns ) {

		unset( $columns['date'] );

		$sup_columns = array(
			'company_code'  => __( 'Code', ATUM_TEXT_DOMAIN ),
			'company_phone' => __( 'Phone', ATUM_TEXT_DOMAIN ),
			'assigned_to'   => __( 'Assigned To', ATUM_TEXT_DOMAIN ),
			'location'      => __( 'Location', ATUM_TEXT_DOMAIN ),
		);

		return array_merge( $columns, $sup_columns );

	}

	/**
	 * Output custom columns for Suppliers' list table
	 *
	 * @since 1.3.0
	 *
	 * @param string $column
	 *
	 * @return bool True if the column is rendered or False if not
	 */
	public function render_columns( $column ) {

		global $post;
		$rendered = FALSE;
		$supplier = new Supplier( $post->ID );

		switch ( $column ) {

			case 'company_code':
				echo esc_html( $supplier->code );
				break;

			case 'company_phone':
				echo esc_html( $supplier->phone );
				break;

			case 'assigned_to':
				$user_id = esc_html( $supplier->assigned_to );

				if ( $user_id > 0 ) {
					$user = get_user_by( 'id', $user_id );

					if ( $user ) {
						echo '<a href="' . esc_url( get_edit_user_link( $user_id ) ) . '" target="_blank">' . esc_html( $user->display_name ) . '</a>';
					}

				}

				break;

			case 'location':
				echo esc_html( $supplier->location );
				break;

		}

		return $rendered;

	}

	/**
	 * Add the Suppliers meta boxes
	 *
	 * @since 1.2.9
	 */
	public function add_meta_boxes() {

		global $post;
		$this->supplier = new Supplier( $post->ID );

		// Supplier Details meta box.
		add_meta_box(
			'supplier_details',
			__( 'Supplier Details', ATUM_TEXT_DOMAIN ),
			array( $this, 'show_supplier_details_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Billing Information meta box.
		add_meta_box(
			'billing_information',
			__( 'Billing Information', ATUM_TEXT_DOMAIN ),
			array( $this, 'show_billing_information_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Default Settings meta box.
		add_meta_box(
			'default_settings',
			__( 'Default Settings', ATUM_TEXT_DOMAIN ),
			array( $this, 'show_default_settings_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

	}

	/**
	 * Displays the supplier details meta box at Supplier posts
	 *
	 * @since 1.2.9
	 *
	 * @param \WP_Post $post
	 */
	public function show_supplier_details_meta_box( $post ) {
		Helpers::load_view( 'meta-boxes/suppliers/details', array( 'supplier' => $this->supplier ) );
	}

	/**
	 * Displays the billing information meta box at Supplier posts
	 *
	 * @since 1.2.9
	 *
	 * @param \WP_Post $post
	 */
	public function show_billing_information_meta_box( $post ) {

		$country_obj = new \WC_Countries();

		Helpers::load_view( 'meta-boxes/suppliers/billing-information', array(
			'supplier'  => $this->supplier,
			'countries' => $country_obj->get_countries(),
		) );
	}

	/**
	 * Displays the default settings meta box at Supplier posts
	 *
	 * @since 1.2.9
	 *
	 * @param \WP_Post $post
	 */
	public function show_default_settings_meta_box( $post ) {
		Helpers::load_view( 'meta-boxes/suppliers/default-settings', array( 'supplier' => $this->supplier ) );
	}

	/**
	 * Save the Supplier meta boxes
	 *
	 * @since 1.2.9
	 *
	 * @param int $supplier_id
	 */
	public function save_meta_boxes( $supplier_id ) {

		if ( ! isset( $_POST['supplier_details'], $_POST['billing_information'], $_POST['default_settings'] ) ) {
			return;
		}

		$supplier = new Supplier( $supplier_id );

		foreach ( [ 'supplier_details', 'billing_information', 'default_settings' ] as $metabox_key ) {

			// Add unchecked checkboxes values.
			if ( 'supplier_details' === $metabox_key && ! isset( $_POST[ $metabox_key ]['use_default_description'] ) ) {
				$_POST[ $metabox_key ]['use_default_description'] = 'no';
			}
			elseif ( 'default_settings' === $metabox_key && ! isset( $_POST[ $metabox_key ]['use_default_terms'] ) ) {
				$_POST[ $metabox_key ]['use_default_terms'] = 'no';
			}

			$supplier->set_data( $_POST[ $metabox_key ] );

		}

		$supplier->save();

	}

	/**
	 * Enqueue the scripts
	 *
	 * @since 1.2.9
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		global $post_type;

		if ( self::POST_TYPE === $post_type ) {

			if ( in_array( $hook, [ 'post.php', 'post-new.php', 'edit.php' ] ) ) {

				// Sweet Alert 2.
				if ( 'edit.php' === $hook ) {

					wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', [], ATUM_VERSION );
					wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', [], ATUM_VERSION, TRUE );

					wp_register_style( 'atum-suppliers-list', ATUM_URL . 'assets/css/atum-suppliers-list.css', [ 'sweetalert2' ], ATUM_VERSION );
					wp_enqueue_style( 'atum-suppliers-list' );

					if ( is_rtl() ) {
						wp_register_style( 'atum-suppliers-list-rtl', ATUM_URL . 'assets/css/atum-suppliers-list-rtl.css', [ 'atum-suppliers-list' ], ATUM_VERSION );
						wp_enqueue_style( 'atum-suppliers-list-rtl' );
					}

					// Load the ATUM colors.
					Helpers::enqueue_atum_colors( 'atum-suppliers-list' );

					wp_register_script( 'atum-suppliers-list', ATUM_URL . 'assets/js/build/atum-post-type-list.js', [ 'jquery', 'wp-hooks' ], ATUM_VERSION, TRUE );

					wp_localize_script( 'atum-suppliers-list', 'atumPostTypeListVars', array(
						'placeholderSearch' => __( 'Search...', ATUM_TEXT_DOMAIN ),
					) );

					wp_enqueue_script( 'atum-suppliers-list' );

				}
				else {

					wp_register_style( 'atum-suppliers', ATUM_URL . 'assets/css/atum-suppliers.css', [], ATUM_VERSION );
					wp_enqueue_style( 'atum-suppliers' );

					wp_enqueue_script( 'atum-suppliers', ATUM_URL . 'assets/js/build/atum-suppliers.js', [ 'jquery' ], ATUM_VERSION, TRUE );

				}

				// ATUM marketing popup.
				AtumMarketingPopup::maybe_enqueue_scripts();

			}

		}

	}

	/**
	 * If the site is not using the new tables, use the legacy method
	 *
	 * @since 1.5.0
	 * @deprecated Only for backwards compatibility and will be removed in a future version.
	 */
	use SuppliersLegacyTrait;

	/**
	 * Get all the products linked to the specified supplier
	 *
	 * @since 1.3.0
	 *
	 * @param int      $supplier_id   The supplier ID.
	 * @param string[] $post_type     Optional. The product post types to get.
	 * @param bool     $type_filter   Optional. Whether to filter the retrieved suppliers by product type or not.
	 * @param array    $extra_filters Optional. Any other extra filter needed to reduce the returned results.
	 *
	 * @return array|bool
	 */
	public static function get_supplier_products( $supplier_id, $post_type = [ 'product', 'product_variation' ], $type_filter = TRUE, $extra_filters = array() ) {

		/**
		 * If the site is not using the new tables, use the legacy method
		 *
		 * @since 1.5.0
		 * @deprecated Only for backwards compatibility and will be removed in a future version.
		 */
		if ( ! Helpers::is_using_new_wc_tables() ) {
			return self::get_supplier_products_legacy( $supplier_id, $post_type, $type_filter, $extra_filters );
		}

		global $wpdb;

		$supplier = get_post( $supplier_id );

		if ( $supplier && self::POST_TYPE === $supplier->post_type ) {

			$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders
			$where = $wpdb->prepare( "WHERE apd.supplier_id = %d AND p.post_type IN ('" . implode( "','", $post_type ) . "')", $supplier_id );
			$join  = '';

			// Check the product type if needed.
			$is_filtering_product_type = FALSE;

			if ( ! empty( $extra_filters['tax_query'] ) ) {
				$is_filtering_product_type = ! empty( wp_list_filter( $extra_filters['tax_query'], [ 'taxonomy' => 'product_type' ] ) );
			}

			if ( $type_filter && ! $is_filtering_product_type ) {

				$product_types = Globals::get_product_types();

				if ( in_array( 'product_variation', $post_type ) ) {
					$product_types = array_merge( $product_types, Globals::get_child_product_types() );
				}

				$product_types = (array) apply_filters( 'atum/suppliers/supplier_product_types', $product_types );

				$where .= " AND wcp.type IN ('" . implode( "','", $product_types ) . "')";

			}

			// Add any extra filter (product category for example).
			if ( ! empty( $extra_filters['tax_query'] ) && is_array( $extra_filters['tax_query'] ) ) {

				foreach ( $extra_filters['tax_query'] as $index => $tax_query ) {
					$term_ids = Helpers::get_term_ids_by_slug( (array) $tax_query['terms'], $tax_query['taxonomy'] );
					$join     = " LEFT JOIN $wpdb->term_relationships tr$index ON (p.ID = tr$index.object_id) ";
					$where   .= " AND tr$index.term_taxonomy_id IN (" . implode( ',', $term_ids ) . ')';
				}

			}

			// phpcs:disable WordPress.DB.PreparedSQL
			$products = $wpdb->get_results( "
				SELECT p.ID, p.post_parent FROM $wpdb->posts p
				LEFT JOIN {$wpdb->prefix}wc_products wcp ON p.ID = wcp.product_id
				LEFT JOIN $atum_data_table apd ON p.ID = apd.product_id 
				$join
				$where
			", ARRAY_A );
			// phpcs:enable

			if ( $products ) {
				// Merge the child and parent IDs.
				$products = array_unique( array_filter( array_merge( wp_list_pluck( $products, 'ID' ), wp_list_pluck( $products, 'post_parent' ) ) ) );
			}

			return apply_filters( 'atum/suppliers/products', $products, $supplier, $post_type, $type_filter );

		}

		return FALSE;

	}

	/**
	 * Get all the product IDs with no supplier assigned
	 *
	 * @since 1.8.8
	 *
	 * @return array
	 */
	public static function get_no_supplier_products() {

		global $wpdb;

		$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$product_statuses        = Globals::get_queryable_product_statuses();

		$sql = "
			SELECT product_id FROM $atum_product_data_table apd
			LEFT JOIN $wpdb->posts p ON (apd.product_id = p.ID)                 
		 	WHERE p.post_status IN('" . implode( "','", $product_statuses ) . "') 
		 	AND (supplier_id = 0 OR supplier_id IS NULL)
		";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return apply_filters( 'atum/suppliers/no_supplier_products', $wpdb->get_col( $sql ) );
		
	}
	
	/**
	 * Add the Suppliuers link to the ATUM's admin bar menu
	 *
	 * @since 1.3.0
	 *
	 * @param array $atum_menus
	 *
	 * @return array
	 */
	public function add_admin_bar_link( $atum_menus ) {

		$atum_menus['suppliers'] = array(
			'slug'       => ATUM_TEXT_DOMAIN . '-suppliers',
			'title'      => _x( 'Suppliers', 'Admin menu name', ATUM_TEXT_DOMAIN ),
			'href'       => 'edit.php?post_type=' . self::POST_TYPE,
			'menu_order' => self::MENU_ORDER,
		);

		return $atum_menus;
	}
	
	/**
	 * Add the current item menu order
	 *
	 * @param array $items_order
	 *
	 * @return array
	 */
	public function add_item_order( $items_order ) {

		$items_order[] = array(
			'slug'       => 'edit.php?post_type=' . self::POST_TYPE,
			'menu_order' => self::MENU_ORDER,
		);
		
		return $items_order;
		
	}

	/**
	 * Check if product supplier's SKU is found for any other product IDs.
	 *
	 * @since 1.5.0
	 *
	 * @param int    $product_id   Product ID to exclude from the query.
	 * @param string $supplier_sku Will be slashed to work around https://core.trac.wordpress.org/ticket/27421.
	 *
	 * @return int
	 */
	public static function get_product_id_by_supplier_sku( $product_id, $supplier_sku ) {

		$cache_key        = AtumCache::get_cache_key( 'product_id_by_supplier_sku', [ $product_id, $supplier_sku ] );
		$found_product_id = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			global $wpdb;

			$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

			// phpcs:disable WordPress.DB.PreparedSQL
			$found_product_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT p.ID
				FROM $wpdb->posts p
				LEFT JOIN $atum_data_table apd ON ( p.ID = apd.product_id )
				WHERE p.post_status != 'trash' AND apd.supplier_sku = %s AND p.ID <> %d
				LIMIT 1",
				wp_slash( $supplier_sku ),
				$product_id
			) );
			// phpcs:enable

			AtumCache::set_cache( $cache_key, $found_product_id );

		}

		return $found_product_id;

	}

	/**
	 * Add supplier post type to the new wc navigation system
	 *
	 * @since 1.8.9
	 */
	public function add_supplier_post_type_wcmenu() {
		$post_type_items = Menu::get_post_type_items(
			'atum_supplier',
			array(
				'title'  => __( 'Suppliers', ATUM_TEXT_DOMAIN ),
				'parent' => 'ATUM',
			)
		);

		Menu::add_plugin_item( $post_type_items['all'] );
	}


	/*******************
	 * Instance methods
	 *******************/

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
	 * @return Suppliers instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
