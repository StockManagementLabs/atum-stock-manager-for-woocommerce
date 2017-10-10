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

use Atum\Inc\Globals;
use Atum\Inc\Helpers;


defined( 'ABSPATH' ) or die;

class Suppliers {

	/**
	 * The singleton instance holder
	 * @var Suppliers
	 */
	private static $instance;

	/**
	 * The Supplier post type name
	 */
	const POST_TYPE = ATUM_PREFIX . 'supplier';

	/**
	 * Suppliers singleton constructor
	 */
	private function __construct() {

		$this->register_post_type();

		// Add meta boxes to Supplier post UI
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_meta_boxes' ), 30 );

		// Save the meta boxes
		add_action( 'save_post_' . self::POST_TYPE , array( $this, 'save_meta_boxes' ) );

		// Add the supplier selection to products
		add_action( 'woocommerce_product_options_general_product_data', array($this, 'show_product_supplier_meta_box') );

		// Save the product supplier meta box
		add_action( 'save_post_product' , array( $this, 'save_product_supplier_meta_box' ) );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register the Suppliers post type
	 *
	 * @param array $args
	 *
	 * @since 1.2.9
	 */
	private function register_post_type( $args = array() ) {

		// Minimum capability required
		$is_user_allowed = current_user_can( 'manage_woocommerce' );

		$labels = array(
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
			'labels'              => $labels,
			'description'         => __( 'This is where Suppliers are stored.', ATUM_TEXT_DOMAIN ),
			'public'              => FALSE,
			'show_ui'             => $is_user_allowed,
			'publicly_queryable'  => FALSE,
			'exclude_from_search' => TRUE,
			'hierarchical'        => FALSE,
			'show_in_menu'        => $is_user_allowed ? Globals::ATUM_UI_SLUG : FALSE,
			'show_in_nav_menus'   => FALSE,
			'rewrite'             => FALSE,
			'query_var'           => is_admin(),
			'supports'            => array( 'title', 'thumbnail' ),
			'has_archive'         => FALSE,
		), $args ));

		// Register the Suppliers post type
		register_post_type( self::POST_TYPE, $args );

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
	 * Displays the Supplier selector in WC products
	 *
	 * @since 1.3.0
	 */
	public function show_product_supplier_meta_box() {

		$supplier_id = get_post_meta(get_the_ID(), '_supplier', TRUE);

		if ($supplier_id) {
			$supplier = get_post($supplier_id);
		}
		?>
		<div class="options_group show_if_simple show_if_variable">
			<p class="form-field _supplier_field">
				<label for="_supplier"><?php _e('Supplier') ?></label> <?php echo wc_help_tip( __( 'Choose a supplier for this product.', ATUM_TEXT_DOMAIN ) ); ?>

				<select class="wc-product-search" id="_supplier" name="_supplier" style="width: 80%" data-allow_clear="true" data-action="atum_json_search_suppliers"
					data-placeholder="<?php esc_attr_e( 'Search Supplier by Name or ID&hellip;', ATUM_TEXT_DOMAIN ); ?>" data-multiple="false"
					data-selected="" data-minimum_input_length="1">
					<?php if ( ! empty($supplier) ): ?>
						<option value="<?php echo esc_attr( $supplier->ID ) ?>" selected="selected"><?php echo $supplier->post_title ?></option>
					<?php endif; ?>
				</select>
			</p>
		</div>
		<?php

	}

	/**
	 * Save the product supplier meta box
	 *
	 * @since 1.3.0
	 *
	 * @param int $post_id    The post ID
	 */
	public function save_product_supplier_meta_box($post_id) {

		if ( isset($_POST['_supplier']) ) {

			$supplier = absint( $_POST['_supplier'] );

			if ( ! $supplier ) {
				delete_post_meta( $post_id, '_supplier' );
			}
			else {
				update_post_meta( $post_id, '_supplier', $supplier );
			}

		}

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
	 * @return Settings instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}