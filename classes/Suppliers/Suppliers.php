<?php
/**
 * @package     Atum
 * @subpackage  Suppliers
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2017 Stock Management Labs™
 *
 * @since       1.2.9
 *
 * Handles the Suppliers post type
 */

namespace Atum\Suppliers;

use Atum\Components\AtumCapabilities;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Inc\Main;


defined( 'ABSPATH' ) or die;

class Suppliers {

	/**
	 * The singleton instance holder
	 * @var Suppliers
	 */
	private static $instance;
	
	/**
	 * The post type labels
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
	const MENU_ORDER = 35;
	
	
	/**
	 * Suppliers singleton constructor
	 */
	private function __construct() {

		// Register the Supplier post type
		add_action( 'init', array($this, 'register_post_type') );

		// Global hooks
		if ( AtumCapabilities::current_user_can( 'read_supplier' ) ) {

			// Add the "Suppliers" link to the ATUM's admin bar menu
			add_filter( 'atum/admin/top_bar/menu_items', array( $this, 'add_admin_bar_link' ), 12 );

			// Add item order
			add_filter( 'atum/admin/menu_items_order', array( $this, 'add_item_order' ) );

		}

		// Admin hooks
		if ( is_admin() ) {

			if ( AtumCapabilities::current_user_can( 'read_supplier' ) ) {

				// Add custom columns to Suppliers' post type list table
				add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_columns' ) );
				add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_columns' ), 2 );

				// Add the supplier's fields to products
				add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_product_supplier_fields' ), 11, 3 );
				add_action( 'woocommerce_product_options_inventory_product_data', array($this, 'add_product_supplier_fields') );

				// Save the product supplier meta box
				add_action( 'save_post_product', array( $this, 'save_product_supplier_fields' ) );
				add_action( 'woocommerce_update_product_variation', array( $this, 'save_product_supplier_fields' ) );

			}

			if ( AtumCapabilities::current_user_can( 'edit_supplier' ) ) {

				// Add meta boxes to Supplier post UI
				add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_meta_boxes' ), 30 );

				// Save the supplier's meta boxes
				add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ) );

				// Enqueue scripts
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

		// Minimum capability required
		$is_user_allowed = AtumCapabilities::current_user_can( 'read_supplier' );
		$main_menu_item  = Main::get_main_menu_item();

		$this->labels = array(
			'name'                  => __( 'Suppliers', ATUM_TEXT_DOMAIN ),
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
				'edit_published_posts'   => ATUM_PREFIX . 'edit_published_suppliers'
			)
		), $args ) );

		// Register the Suppliers post type
		register_post_type( self::POST_TYPE, $args );

	}

	/**
	 * Set the columns for the Suppliers' list table
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function add_columns($columns) {

		unset($columns['date']);

		$sup_columns = array(
			'company_code'  => __( 'Code', ATUM_TEXT_DOMAIN ),
			'company_phone' => __( 'Phone', ATUM_TEXT_DOMAIN ),
			'assigned_to'   => __( 'Assigned To', ATUM_TEXT_DOMAIN ),
			'location'      => __( 'Location', ATUM_TEXT_DOMAIN )
		);

		return array_merge($columns, $sup_columns);

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
	public function render_columns($column) {

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

				if ($user_id > 0) {
					$user = get_user_by('id', $user_id);

					if ($user) {
						echo '<a href="' . get_edit_user_link($user_id) . '" target="_blank">' . $user->display_name . '</a>';
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
	public function add_meta_boxes () {

		// Supplier Details meta box
		add_meta_box(
			'supplier_details',
			__('Supplier Details', ATUM_TEXT_DOMAIN),
			array($this, 'show_supplier_details_meta_box'),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Billing Information meta box
		add_meta_box(
			'billing_information',
			__('Billing Information', ATUM_TEXT_DOMAIN),
			array($this, 'show_billing_information_meta_box'),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Default Settings meta box
		add_meta_box(
			'default_settings',
			__('Default Settings', ATUM_TEXT_DOMAIN),
			array($this, 'show_default_settings_meta_box'),
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
		Helpers::load_view('meta-boxes/suppliers/details', array('supplier_id' => $post->ID));
	}

	/**
	 * Displays the billing information meta box at Supplier posts
	 *
	 * @since 1.2.9
	 *
	 * @param \WP_Post $post
	 */
	public function show_billing_information_meta_box( $post ) {
		Helpers::load_view('meta-boxes/suppliers/billing-information', array('supplier_id' => $post->ID));
	}

	/**
	 * Displays the default settings meta box at Supplier posts
	 *
	 * @since 1.2.9
	 *
	 * @param \WP_Post $post
	 */
	public function show_default_settings_meta_box( $post ) {
		Helpers::load_view('meta-boxes/suppliers/default-settings', array('supplier_id' => $post->ID));
	}

	/**
	 * Save the Supplier meta boxes
	 *
	 * @since 1.2.9
	 *
	 * @param int $supplier_id
	 */
	public function save_meta_boxes($supplier_id) {

		if (! isset($_POST['supplier_details'], $_POST['billing_information'], $_POST['default_settings']) ) {
			return;
		}

		foreach (['supplier_details', 'billing_information', 'default_settings'] as $metabox_key) {

			foreach ( array_map('esc_attr', $_POST[$metabox_key]) as $meta_key => $meta_value ) {

				// The meta key names will follow the format: _supplier_details_name
				if ($meta_value === '') {
					delete_post_meta($supplier_id, "_{$metabox_key}_{$meta_key}");
				}
				else {
					update_post_meta($supplier_id, "_{$metabox_key}_{$meta_key}", $meta_value);
				}

			}

		}

	}

	/**
	 * Adds the Supplier fields in WC's product data meta box
	 *
	 * @since 1.3.0
	 *
	 * @param int      $loop             Only for variations. The loop item number
	 * @param array    $variation_data   Only for variations. The variation item data
	 * @param \WP_Post $variation        Only for variations. The variation product
	 */
	public function add_product_supplier_fields ($loop = NULL, $variation_data = array(), $variation = NULL) {

		global $post;

		if ( empty($variation) ) {

			$product = wc_get_product( $post->ID );

			// Do not add the field to variable products (every variation will have its own)
			if ( in_array( $product->get_type(), ['variable', 'variable-subscription'] ) ) {
				return;
			}

		}

		$product_id   = empty( $variation ) ? $post->ID : $variation->ID;
		$supplier_id  = get_post_meta( $product_id, '_supplier', TRUE );
		$supplier_sku = get_post_meta( $product_id, '_supplier_sku', TRUE );

		if ($supplier_id) {
			$supplier = get_post($supplier_id);
		}

		// If the user is not allowed to edit Suppliers, add a hidden input
		if ( ! AtumCapabilities::current_user_can('edit_supplier') ):

			// Supplier ID
			woocommerce_wp_hidden_input( array(
				'id'    => '_supplier',
				'value' => ( ! empty($supplier) ) ? esc_attr( $supplier->ID ) : ''
			) );

			// Supplier SKU
			woocommerce_wp_hidden_input( array(
				'id'    => '_supplier_sku',
				'value' => $supplier_sku ?: ''
			) );

		else:

			// Supplier ID
			$suplier_field_name = empty($variation) ? '_supplier' : "variation_supplier[$loop]";

			if ( empty($variation) ): ?>
			<div class="options_group show_if_simple show_if_product-part show_if_raw-material">
			<?php endif; ?>

				<p class="form-field _supplier_field<?php if ( ! empty($variation) ) echo ' form-row form-row-first' ?>">
					<label for="_supplier"><?php _e('Supplier', ATUM_TEXT_DOMAIN) ?></label> <?php echo wc_help_tip( __( 'Choose a supplier for this product.', ATUM_TEXT_DOMAIN ) ); ?>

					<select class="wc-product-search" id="_supplier" name="<?php echo $suplier_field_name ?>" style="width: <?php echo ( empty($variation) ) ? 80 : 100 ?>%" data-allow_clear="true"
						data-action="atum_json_search_suppliers" data-placeholder="<?php esc_attr_e( 'Search Supplier by Name or ID&hellip;', ATUM_TEXT_DOMAIN ); ?>"
						data-multiple="false" data-selected="" data-minimum_input_length="1">
						<?php if ( ! empty($supplier) ): ?>
							<option value="<?php echo esc_attr( $supplier->ID ) ?>" selected="selected"><?php echo $supplier->post_title ?></option>
						<?php endif; ?>
					</select>
				</p>

				<?php
				// Supplier SKU
				$supplier_sku_field_name = empty($variation) ? '_supplier_sku' : "variation_supplier_sku[$loop]";

				woocommerce_wp_text_input( array(
					'id'            => '_supplier_sku',
					'name'          => $supplier_sku_field_name,
					'value'         => $supplier_sku ?: '',
					'label'         => '<abbr title="' . __( "Supplier's Stock Keeping Unit", ATUM_TEXT_DOMAIN ) . '">' . __( "Supplier's SKU", ATUM_TEXT_DOMAIN ) . '</abbr>',
					'desc_tip'      => TRUE,
					'description'   => __( "Supplier's SKU refers to a Stock-keeping unit coming from the product's supplier, a unique identifier for each distinct product and service that can be purchased.", ATUM_TEXT_DOMAIN ),
					'wrapper_class' =>  ( ! empty($variation) ) ? 'form-row form-row-last' : ''
				) );

			if ( empty($variation) ): ?>
				</div>
			<?php endif;

		endif;

	}

	/**
	 * Save the product supplier fields
	 *
	 * @since 1.3.0
	 *
	 * @param int $post_id    The post ID
	 */
	public function save_product_supplier_fields ($post_id) {

		$product  = wc_get_product( $post_id );

		if ( in_array( $product->get_type(), ['variable', 'variable-subscription'] ) ) {
			return;
		}

		if ( isset($_POST['variation_supplier']) && isset($_POST['variation_supplier_sku']) ) {
			$supplier = reset($_POST['variation_supplier']);
			$supplier = $supplier ? absint($supplier) : '';

			$supplier_sku = reset($_POST['variation_supplier_sku']);
		}
		elseif ( isset($_POST['_supplier']) && isset($_POST['_supplier_sku']) ) {
			$supplier     = $_POST['_supplier'] ? absint( $_POST['_supplier'] ) : '';
			$supplier_sku = esc_attr( $_POST['_supplier_sku'] );
		}
		else {
			// If we are not saving the product from its edit page, do not continue
			return;
		}

		// Always save the supplier metas (nevermind it has value or not) to be able to sort by it in List Tables
		update_post_meta( $post_id, '_supplier', $supplier );
		update_post_meta( $post_id, '_supplier_sku', $supplier_sku );

	}

	/**
	 * Enqueue the scripts
	 *
	 * @since 1.2.9
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts($hook) {

		global $post_type;
		if ( in_array($hook, ['post.php', 'post-new.php']) && $post_type == self::POST_TYPE) {
			wp_register_style( 'atum-suppliers', ATUM_URL . 'assets/css/atum-suppliers.css', array(), ATUM_VERSION );
			wp_enqueue_style( 'atum-suppliers' );
			wp_enqueue_script( 'wc-enhanced-select');
		}

	}

	/**
	 * Get all the products linked to the specified supplier
	 *
	 * @since 1.3.0
	 *
	 * @param int    $supplier_id  The supplier ID
	 * @param string $fields       Which fields to return (all or ids)
	 *
	 * @return array|bool
	 */
	public static function get_supplier_products($supplier_id, $fields = '') {

		$supplier = get_post($supplier_id);

		if ($supplier->post_type == self::POST_TYPE) {

			$args = array(
				'post_type'      => array('product', 'product_variation'),
				'posts_per_page' => - 1,
				'fields'         => $fields,
				'meta_query'     => array(
					array(
						'key'   => '_supplier',
						'value' => $supplier_id
					)
				)
			);

			return apply_filters( 'atum/suppliers/products', get_posts($args), $supplier );

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
	public function add_admin_bar_link($atum_menus) {

		$atum_menus['suppliers'] = array(
			'slug'       => ATUM_TEXT_DOMAIN . '-suppliers',
			'title'      => _x( 'Suppliers', 'Admin menu name', ATUM_TEXT_DOMAIN ),
			'href'       => 'edit.php?post_type=' . self::POST_TYPE,
			'menu_order' => self::MENU_ORDER
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
			'menu_order' => self::MENU_ORDER
		);
		
		return $items_order;
		
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
	 * @return Suppliers instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}