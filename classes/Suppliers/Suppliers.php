<?php
/**
 * Handles the Suppliers post type
 *
 * @package     Atum
 * @subpackage  Suppliers
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2018 Stock Management Labs™
 *
 * @since       1.2.9
 */

namespace Atum\Suppliers;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Inc\Main;


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
	 * The Supplier post type name
	 */
	const POST_TYPE = ATUM_PREFIX . 'supplier';
	
	/**
	 * The menu order
	 */
	const MENU_ORDER = 4;

	/**
	 * The Supplier meta key
	 */
	const SUPPLIER_META_KEY = '_supplier';

	/**
	 * The Supplier SKU meta key
	 */
	const SUPPLIER_SKU_META_KEY = '_supplier_sku';
	
	
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

				// Add the supplier's fields to products.
				add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_product_supplier_fields' ), 11, 3 );
				add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_product_supplier_fields' ) );

				// Save the product supplier meta box.
				add_action( 'save_post_product', array( $this, 'save_product_supplier_fields' ) );
				add_action( 'woocommerce_save_product_variation', array( $this, 'save_product_supplier_fields' ) );

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

		switch ( $column ) {

			case 'company_code':
				echo get_post_meta( $post->ID, '_supplier_details_code', TRUE );
				break;

			case 'company_phone':
				echo get_post_meta( $post->ID, '_supplier_details_phone', TRUE );
				break;

			case 'assigned_to':
				$user_id = get_post_meta( $post->ID, '_default_settings_assigned_to', TRUE );

				if ( $user_id > 0 ) {
					$user = get_user_by( 'id', $user_id );

					if ( $user ) {
						echo '<a href="' . get_edit_user_link( $user_id ) . '" target="_blank">' . $user->display_name . '</a>';
					}

				}

				break;

			case 'location':
				echo get_post_meta( $post->ID, '_default_settings_location', TRUE );
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
		Helpers::load_view( 'meta-boxes/suppliers/details', array( 'supplier_id' => $post->ID ) );
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
		$countries   = $country_obj->get_countries();

		Helpers::load_view( 'meta-boxes/suppliers/billing-information', array(
			'supplier_id' => $post->ID,
			'countries'   => $countries,
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
		Helpers::load_view( 'meta-boxes/suppliers/default-settings', array( 'supplier_id' => $post->ID ) );
	}

	/**
	 * Save the Supplier meta boxes
	 *
	 * @since 1.2.9
	 *
	 * @param int $supplier_id
	 */
	public function save_meta_boxes( $supplier_id ) {

		if ( ! isset( $_POST['supplier_details'], $_POST['billing_information'], $_POST['default_settings'] ) ) { // WPCS: CSRF ok.
			return;
		}

		foreach ( [ 'supplier_details', 'billing_information', 'default_settings' ] as $metabox_key ) {

			foreach ( array_map( 'esc_attr', $_POST[ $metabox_key ] ) as $meta_key => $meta_value ) { // WPCS: CSRF ok.

				// The meta key names will follow the format: _supplier_details_name.
				if ( '' === $meta_value ) {
					delete_post_meta( $supplier_id, "_{$metabox_key}_{$meta_key}" );
				}
				else {
					update_post_meta( $supplier_id, "_{$metabox_key}_{$meta_key}", $meta_value );
				}

			}

		}

	}

	/**
	 * Adds the Supplier fields in WC's product data meta box
	 *
	 * @since 1.3.0
	 *
	 * @param int      $loop             Only for variations. The loop item number.
	 * @param array    $variation_data   Only for variations. The variation item data.
	 * @param \WP_Post $variation        Only for variations. The variation product.
	 */
	public function add_product_supplier_fields( $loop = NULL, $variation_data = array(), $variation = NULL ) {

		global $post;

		if ( empty( $variation ) ) {

			$product = wc_get_product( $post->ID );

			// Do not add the field to variable products (every variation will have its own).
			if ( in_array( $product->get_type(), array_diff( Globals::get_inheritable_product_types(), [ 'grouped' ] ) ) ) {
				return;
			}

		}

		// Save the meta keys on a variable (some sites were experiencing weird issues when accessing to these constants directly).
		$supplier_meta     = self::SUPPLIER_META_KEY;
		$supplier_sku_meta = self::SUPPLIER_SKU_META_KEY;

		$product_id   = empty( $variation ) ? $post->ID : $variation->ID;
		$supplier_id  = get_post_meta( $product_id, $supplier_meta, TRUE );
		$supplier_sku = get_post_meta( $product_id, $supplier_sku_meta, TRUE );

		if ( $supplier_id ) {
			$supplier = get_post( $supplier_id );
		}

		$supplier_field_name     = empty( $variation ) ? $supplier_meta : "variation{$supplier_meta}[$loop]";
		$supplier_field_id       = empty( $variation ) ? $supplier_meta : $supplier_meta . $loop;
		$supplier_sku_field_name = empty( $variation ) ? $supplier_sku_meta : "variation{$supplier_sku_meta}[$loop]";
		$supplier_sku_field_id   = empty( $variation ) ? $supplier_sku_meta : $supplier_sku_meta . $loop;

		// If the user is not allowed to edit Suppliers, add a hidden input.
		if ( ! AtumCapabilities::current_user_can( 'edit_supplier' ) ) : ?>

			<input type="hidden" name="<?php echo $supplier_field_name ?>" id="<?php echo $supplier_field_id ?>" value="<?php echo ( ! empty( $supplier ) ? esc_attr( $supplier->ID ) : '' ) ?>">
			<input type="hidden" name="<?php echo $supplier_sku_field_name ?>" id="<?php echo $supplier_sku_field_id ?>" value="<?php echo ( $supplier_sku ?: '' ) ?>">

		<?php else :

			$supplier_fields_classes = (array) apply_filters( 'atum/product_data/supplier/classes', [ 'show_if_simple' ] );

			Helpers::load_view( 'meta-boxes/product-data/supplier-fields', compact( 'supplier_field_name', 'supplier_field_id', 'variation', 'loop', 'supplier', 'supplier_sku', 'supplier_sku_field_name', 'supplier_sku_field_id', 'supplier_fields_classes' ) );

		endif;

	}

	/**
	 * Save the product supplier fields
	 *
	 * @since 1.3.0
	 *
	 * @param int $product_id The saved product's ID.
	 */
	public function save_product_supplier_fields( $product_id ) {

		$product = wc_get_product( $product_id );

		if ( is_a( $product, '\WC_Product' ) && in_array( $product->get_type(), array_diff( Globals::get_inheritable_product_types(), [ 'grouped' ] ) ) ) {
			return;
		}

		if ( isset( $_POST['variation_supplier'], $_POST['variation_supplier_sku'] ) ) { // WPCS: CSRF ok.

			$product_key  = array_search( $product_id, $_POST['variable_post_id'] ); // WPCS: CSRF ok.
			$supplier     = isset( $_POST['variation_supplier'][ $product_key ] ) ? absint( $_POST['variation_supplier'][ $product_key ] ) : ''; // WPCS: CSRF ok.
			$supplier_sku = isset( $_POST['variation_supplier_sku'][ $product_key ] ) ? esc_attr( $_POST['variation_supplier_sku'][ $product_key ] ) : ''; // WPCS: CSRF ok.

		}
		elseif ( isset( $_POST[ self::SUPPLIER_META_KEY ], $_POST[ self::SUPPLIER_SKU_META_KEY ] ) ) { // WPCS: CSRF ok.
			$supplier     = isset( $_POST[ self::SUPPLIER_META_KEY ] ) ? absint( $_POST[ self::SUPPLIER_META_KEY ] ) : ''; // WPCS: CSRF ok.
			$supplier_sku = isset( $_POST[ self::SUPPLIER_SKU_META_KEY ] ) ? esc_attr( $_POST[ self::SUPPLIER_SKU_META_KEY ] ) : ''; // WPCS: CSRF ok.
		}
		else {
			// If we are not saving the product from its edit page, do not continue.
			return;
		}

		// Always save the supplier metas (nevermind it has value or not) to be able to sort by it in List Tables.
		update_post_meta( $product_id, self::SUPPLIER_META_KEY, $supplier );
		update_post_meta( $product_id, self::SUPPLIER_SKU_META_KEY, $supplier_sku );

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
		if ( in_array( $hook, [ 'post.php', 'post-new.php' ] ) && self::POST_TYPE === $post_type ) {
			wp_register_style( 'atum-suppliers', ATUM_URL . 'assets/css/atum-suppliers.css', array(), ATUM_VERSION );
			wp_enqueue_style( 'atum-suppliers' );
			wp_enqueue_script( 'wc-enhanced-select' );
		}

	}

	/**
	 * Get all the products linked to the specified supplier
	 *
	 * @since 1.3.0
	 *
	 * @param int          $supplier_id  The supplier ID.
	 * @param array|string $post_type    Optional. The product post types to get.
	 * @param bool         $type_filter  Optional. Whether to filter the retrieved suppliers by product type or not.
	 *
	 * @return array|bool
	 */
	public static function get_supplier_products( $supplier_id, $post_type = [ 'product', 'product_variation' ], $type_filter = TRUE ) {

		global $wpdb;

		$supplier = get_post( $supplier_id );

		if ( self::POST_TYPE === $supplier->post_type ) {

			$args = array(
				'post_type'      => $post_type,
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => self::SUPPLIER_META_KEY,
						'value' => $supplier_id,
					),
				),
			);

			$term_join = $term_where = '';

			if ( $type_filter ) {

				// SC fathers default taxonomies and ready to override to MC (or others) requirements.
				$product_taxonomies = apply_filters( 'atum/suppliers/supplier_products_taxonomies', Globals::get_product_types() );
				$term_ids           = Helpers::get_term_ids_by_slug( $product_taxonomies, $taxonomy = 'product_type' );

				$args['tax_query'] = array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'product_type',
						'field'    => 'id',
						'terms'    => $term_ids,
					),
				);

				$term_join  = "LEFT JOIN $wpdb->term_relationships tr ON (p.ID = tr.object_id)";
				$term_where = 'AND tr.term_taxonomy_id IN (' . implode( ',', $term_ids ) . ')';

			}

			// Father IDs.
			$products = get_posts( apply_filters( 'atum/suppliers/supplier_products_args', $args ) );

			if ( $type_filter ) {

				$child_ids = array();

				// Get rebel parents (rebel childs doesn't have term_relationships.term_taxonomy_id).
				$query_parents = $wpdb->prepare( "
					SELECT DISTINCT p.ID FROM $wpdb->posts p
	                $term_join
	                WHERE p.post_type = 'product'
	                $term_where
	                AND p.post_status IN ('publish', 'private')              
	                AND p.ID IN (
	                
	                    SELECT DISTINCT sp.post_parent FROM $wpdb->posts sp
	                    INNER JOIN $wpdb->postmeta AS mt1 ON (sp.ID = mt1.post_id)
	                    WHERE sp.post_type = 'product_variation'
	                    AND (mt1.meta_key = '" . self::SUPPLIER_META_KEY . "' AND CAST(mt1.meta_value AS SIGNED) = %d)
	                    AND sp.post_status IN ('publish', 'private')
	                      
	                )", $supplier_id ); // WPCS: unprepared SQL ok.

				$parent_ids = $wpdb->get_col( $query_parents ); // WPCS: unprepared SQL ok.

				if ( ! empty( $parent_ids ) ) {
					// Get rebel childs.
					$query_childs = $wpdb->prepare( "
		                SELECT DISTINCT p.ID FROM $wpdb->posts p
		                INNER JOIN $wpdb->postmeta AS mt1 ON (p.ID = mt1.post_id)
		                WHERE p.post_type = 'product_variation'
		                AND (mt1.meta_key = '" . self::SUPPLIER_META_KEY . "' AND CAST(mt1.meta_value AS SIGNED) = %d)
		                AND p.post_parent IN ( " . implode( ',', $parent_ids ) . " )
		                AND p.post_status IN ('publish', 'private')
	                ", $supplier_id ); // WPCS: unprepared SQL ok.

					$child_ids = $wpdb->get_col( $query_childs ); // WPCS: unprepared SQL ok.
				}

				$products = array_unique( array_merge( $products, $parent_ids, $child_ids ) );

			}

			return apply_filters( 'atum/suppliers/products', $products, $supplier, $post_type, $type_filter );

		}

		return FALSE;

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


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
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
