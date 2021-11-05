<?php
/**
 * Handle the ATUM meta boxes for WC's product data.
 *
 * @package     Atum\MetaBoxes
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @since       1.5.0
 */

namespace Atum\MetaBoxes;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumCapabilities;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Models\Products\AtumProductTrait;
use Atum\Suppliers\Suppliers;


class ProductDataMetaBoxes {

	/**
	 * The singleton instance holder
	 *
	 * @var ProductDataMetaBoxes
	 */
	private static $instance;

	/**
	 * Store all the product data submitted from the product's edit page
	 *
	 * @var array
	 */
	private $product_data = array();

	/**
	 * The product object currently saved.
	 *
	 * @var \WC_Product|AtumProductTrait
	 */
	private $product;

	/**
	 * Whether the above product is a variation
	 *
	 * @var bool
	 */
	private $is_variation = FALSE;

	/**
	 * When saving a variation product, this represent its index in the array of values
	 *
	 * @var int
	 */
	private $loop = NULL;

	/**
	 * Whether the purchase price is allowed
	 *
	 * @var bool
	 */
	private $purchase_price_allowed = FALSE;

	/**
	 * ProductDataMetaBoxes constructor
	 *
	 * @since 1.5.0
	 */
	private function __construct() {

		// Add the ATUM Inventory panel and fields.
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_product_data_tab_panel' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_product_variation_data_panel' ), 9, 3 );

		// Add out_stock_threshold field if required.
		if ( 'yes' === Helpers::get_option( 'out_stock_threshold', 'no' ) ) {
			add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_out_stock_threshold_field' ), 9, 3 );
			add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_out_stock_threshold_field' ), 11, 3 );
		}

		// Add the supplier's fields to products if allowed.
		if ( AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_product_supplier_fields' ), 11, 3 );
			add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_product_supplier_fields' ) );
		}

		// Save the ATUM's product data meta boxes (once WC has saved all its own meta boxes).
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_meta_boxes' ), PHP_INT_MAX, 2 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_product_variation_meta_boxes' ), PHP_INT_MAX, 2 );

	}

	/**
	 * Add hooks to show and save the Purchase Price field on products
	 *
	 * @since 1.3.8.3
	 */
	public function purchase_price_hooks() {

		$this->purchase_price_allowed = TRUE;

		// Add the purchase price to WC products.
		add_action( 'woocommerce_product_options_pricing', array( $this, 'add_purchase_price_field' ) );
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_purchase_price_field' ), 10, 3 );

	}

	/**
	 * Filters the Product data tabs settings to add ATUM settings
	 *
	 * @since 1.4.1
	 *
	 * @param array $data_tabs
	 *
	 * @return array
	 */
	public function add_product_data_tab( $data_tabs ) {

		// Add the ATUM tab to Simple and BOM products.
		$bom_tab = (array) apply_filters( 'atum/product_data/tab', array(
			'atum' => array(
				'label'    => __( 'ATUM Inventory', ATUM_TEXT_DOMAIN ),
				'target'   => 'atum_product_data',
				'class'    => array_unique( array_merge( array( 'show_if_simple', 'show_if_variable', 'hide_if_bundle' ), Helpers::get_option_group_hidden_classes() ) ),
				'priority' => 21,
			),
		) );

		// Insert the ATUM tab under Inventory tab.
		$data_tabs = array_merge( array_slice( $data_tabs, 0, 2 ), $bom_tab, array_slice( $data_tabs, 2 ) );

		return $data_tabs;

	}

	/**
	 * Add the fields to ATUM Inventory tab within WC's Product Data meta box
	 *
	 * @since 1.4.1
	 */
	public function add_product_data_tab_panel() {

		$product_id = get_the_ID();
		$product    = Helpers::get_atum_product( $product_id );

		if ( apply_filters( 'atum/product_data/can_add_atum_panel', TRUE, $product ) ) {

			$product_status           = get_post_status( $product_id );
			$checkbox_wrapper_classes = (array) apply_filters( 'atum/product_data/atum_switch/classes', [ 'show_if_simple', 'hide_if_bundle' ] );
			$control_button_classes   = (array) apply_filters( 'atum/product_data/control_button/classes', [ 'show_if_variable' ] );

			Helpers::load_view( 'meta-boxes/product-data/atum-tab-panel', compact( 'product', 'product_status', 'checkbox_wrapper_classes', 'control_button_classes' ) );
		}

	}

	/**
	 * Add the Product Levels meta boxes to the Product variations
	 *
	 * @since 0.0.3
	 *
	 * @param int      $loop           The current item in the loop of variations.
	 * @param array    $variation_data The current variation data.
	 * @param \WP_Post $variation      The variation post.
	 */
	public function add_product_variation_data_panel( $loop, $variation_data, $variation ) {

		// Get the variation product.
		$variation = Helpers::get_atum_product( $variation->ID );

		if ( apply_filters( 'atum/product_data/can_add_atum_panel', TRUE, $variation ) ) {
			Helpers::load_view( 'meta-boxes/product-data/atum-variation-panel', compact( 'loop', 'variation_data', 'variation' ) );
		}

	}

	/**
	 * Save all the fields within the Product Data's ATUM Inventory tab
	 *
	 * @since 1.4.1
	 */
	private function save_product_data_panel() {

		$tab_data_key           = $this->is_variation ? 'variation_atum_tab' : 'atum_product_tab';
		$atum_tab_values        = isset( $_POST[ $tab_data_key ] ) ? $_POST[ $tab_data_key ] : array();
		$product_tab_fields     = Globals::get_product_tab_fields();
		$is_inheritable_product = ! $this->is_variation && Helpers::is_inheritable_type( esc_attr( $_POST['product-type'] ) );

		$this->product_data['inheritable'] = $is_inheritable_product ? 'yes' : 'no';

		foreach ( $product_tab_fields as $field_name => $field_type ) {

			// The ATUM's stock control must be always 'yes' for inheritable products.
			if ( Globals::ATUM_CONTROL_STOCK_KEY === $field_name && $is_inheritable_product ) {
				$this->product_data['atum_controlled'] = 'yes';
				continue;
			}

			if ( $this->is_variation ) {
				$field_value = isset( $atum_tab_values[ $field_name ][ $this->loop ] ) ? $atum_tab_values[ $field_name ][ $this->loop ] : NULL;
			}
			else {
				$field_value = isset( $atum_tab_values[ $field_name ] ) ? $atum_tab_values[ $field_name ] : NULL;
			}

			// Sanitize the fields.
			switch ( $field_type ) {
				case 'checkbox':
					$field_value = 'yes' === $field_value ? 'yes' : 'no';
					break;

				case 'number_int':
						$field_value = absint( $field_value );
					break;

				case 'number_float':
					$field_value = floatval( $field_value );
					break;

				case 'text':
				default:
					$field_value = wc_clean( $field_value );
					break;
			}

			// The ATUM control key doesn't match to the new table column name.
			if ( Globals::ATUM_CONTROL_STOCK_KEY === $field_name ) {
				$field_name = 'atum_controlled';
			}

			$this->product_data[ $field_name ] = $field_value;

		}

	}

	/**
	 * Add the individual out stock threshold field to WC's product data meta box
	 *
	 * @since 1.4.10
	 *
	 * @param int      $loop            Only for variations. The loop item number.
	 * @param array    $variation_data  Only for variations. The variation item data.
	 * @param \WP_Post $variation       Only for variations. The variation product.
	 */
	public function add_out_stock_threshold_field( $loop = NULL, $variation_data = array(), $variation = NULL ) {

		global $post;

		$meta_key = Globals::OUT_STOCK_THRESHOLD_KEY;

		$woocommerce_notify_no_stock_amount = get_option( 'woocommerce_notify_no_stock_amount' );

		$product_id          = empty( $variation ) ? $post->ID : $variation->ID;
		$product             = Helpers::get_atum_product( $product_id );
		$out_stock_threshold = $product->get_out_stock_threshold();
		$product_type        = empty( $variation ) ? $product->get_type() : '';

		$out_stock_threshold_field_name = empty( $variation ) ? $meta_key : "variation{$meta_key}[$loop]";
		$out_stock_threshold_field_id   = empty( $variation ) ? $meta_key : $meta_key . $loop;

		// If the user is not allowed to edit "Out of stock threshold", add a hidden input.
		if ( ! AtumCapabilities::current_user_can( 'edit_out_stock_threshold' ) ) : ?>

			<input type="hidden" value="<?php echo esc_attr( $out_stock_threshold ?: '' ) ?>" name="<?php echo esc_attr( $out_stock_threshold_field_name ) ?>" id="<?php echo esc_attr( $out_stock_threshold_field_id ) ?>">

		<?php else :

			$visibility_classes = array_map( function ( $val ) {
				return "show_if_{$val}";
			}, Globals::get_product_types_with_stock() );

			$visibility_classes = (array) apply_filters( 'atum/product_data/out_stock_threshold/classes', array_merge( $visibility_classes, Helpers::get_option_group_hidden_classes() ) );

			Helpers::load_view( 'meta-boxes/product-data/out-stock-threshold-field', compact( 'variation', 'loop', 'product_type', 'out_stock_threshold', 'out_stock_threshold_field_name', 'out_stock_threshold_field_id', 'visibility_classes', 'woocommerce_notify_no_stock_amount' ) );

		endif;

	}

	/**
	 * Save the out of stock threshold field
	 * Rebuild: force_rebuild_stock_status if _out_stock_threshold is empty.
	 *
	 * @since 1.4.10
	 */
	private function save_out_stock_threshold_field() {

		$out_stock_threshold = NULL;

		if ( ! in_array( $this->product->get_type(), Globals::get_product_types_with_stock(), TRUE ) ) {
			return;
		}

		$out_stock_threshold = $this->is_variation ? $_POST[ 'variation' . Globals::OUT_STOCK_THRESHOLD_KEY ][ $this->loop ] : $_POST[ Globals::OUT_STOCK_THRESHOLD_KEY ];

		// Force product validate and save to rebuild stock_status (probably _out_stock_threshold has been disabled for this product).
		if ( (float) $this->product->get_out_stock_threshold() !== (float) $out_stock_threshold ) {
			Helpers::force_rebuild_stock_status( $this->product );
		}

		$this->product_data['out_stock_threshold'] = $out_stock_threshold;

	}

	/**
	 * Add the purchase price field to WC's product data meta box
	 *
	 * @since 1.2.0
	 *
	 * @param int      $loop             Only for variations. The loop item number.
	 * @param array    $variation_data   Only for variations. The variation item data.
	 * @param \WP_Post $variation        Only for variations. The variation product.
	 */
	public function add_purchase_price_field( $loop = NULL, $variation_data = array(), $variation = NULL ) {

		if ( ! current_user_can( ATUM_PREFIX . 'edit_purchase_price' ) ) {
			return;
		}

		$field_title = __( 'Purchase price', ATUM_TEXT_DOMAIN ) . ' (' . get_woocommerce_currency_symbol() . ')';

		if ( empty( $variation ) ) {
			$product_id    = get_the_ID();
			$wrapper_class = '_purchase_price_field ' . Helpers::get_option_group_hidden_classes( FALSE );
			$field_id      = $field_name = Globals::PURCHASE_PRICE_KEY;
		}
		else {
			$product_id    = $variation->ID;
			$field_name    = "variation_purchase_price[$loop]";
			$field_id      = "variation_purchase_price{$loop}";
			$wrapper_class = "$field_name form-row form-row-first";
		}

		$product        = Helpers::get_atum_product( $product_id );
		$purchase_price = $product->get_purchase_price();
		$field_value    = '' === $purchase_price ? '' : (float) $purchase_price;
		$price          = (float) $product->get_price();

		Helpers::load_view( 'meta-boxes/product-data/purchase-price-field', compact( 'wrapper_class', 'field_title', 'field_name', 'field_id', 'field_value', 'price', 'variation', 'loop', 'product_id' ) );

	}

	/**
	 * Save the purchase price meta on product post savings
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	private function save_purchase_price() {

		$product_type       = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );
		$old_purchase_price = $this->product->get_purchase_price();
		$new_purchase_price = $old_purchase_price ?: '';

		// Variables, grouped and variations.
		if ( Helpers::is_inheritable_type( $product_type ) && 'bundle' !== $product_type ) {

			if ( $this->is_variation && isset( $_POST[ 'variation' . Globals::PURCHASE_PRICE_KEY ][ $this->loop ] ) ) {
				$purchase_price     = wc_clean( $_POST[ 'variation' . Globals::PURCHASE_PRICE_KEY ][ $this->loop ] );
				$new_purchase_price = '' === $purchase_price ? '' : $purchase_price;
			}

		}
		// Rest of product types (Bypass if "_puchase_price" meta is not coming).
		elseif ( ! $this->is_variation && isset( $_POST[ Globals::PURCHASE_PRICE_KEY ] ) ) {
			$purchase_price     = wc_clean( $_POST[ Globals::PURCHASE_PRICE_KEY ] );
			$new_purchase_price = '' === $purchase_price ? '' : $purchase_price;
		}

		$this->product_data['purchase_price'] = $new_purchase_price;

		return array(
			'new_purchase_price' => $new_purchase_price,
			'old_purchase_price' => $old_purchase_price,
		);

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

		$product_id = empty( $variation ) ? $post->ID : $variation->ID;
		$product    = Helpers::get_atum_product( $product_id );

		if ( empty( $variation ) ) {

			// Do not add the field to variable products (every variation will have its own).
			if ( in_array( $product->get_type(), array_diff( Globals::get_inheritable_product_types(), [ 'grouped', 'bundle' ] ) ) ) {
				return;
			}

		}

		// Save the meta keys on a variable (some sites were experiencing weird issues when accessing to these constants directly).
		$supplier_meta     = Suppliers::SUPPLIER_META_KEY;
		$supplier_sku_meta = Suppliers::SUPPLIER_SKU_META_KEY;
		$supplier_id       = $product->get_supplier_id();
		$supplier_sku      = $product->get_supplier_sku();
		$supplier          = $supplier_id ? get_post( $supplier_id ) : NULL;

		$supplier_field_name     = empty( $variation ) ? $supplier_meta : "variation{$supplier_meta}[$loop]";
		$supplier_field_id       = empty( $variation ) ? $supplier_meta : $supplier_meta . $loop;
		$supplier_sku_field_name = empty( $variation ) ? $supplier_sku_meta : "variation{$supplier_sku_meta}[$loop]";
		$supplier_sku_field_id   = empty( $variation ) ? $supplier_sku_meta : $supplier_sku_meta . $loop;

		// If the user is not allowed to edit Suppliers, add a hidden input.
		if ( ! AtumCapabilities::current_user_can( 'edit_supplier' ) ) : ?>

			<input type="hidden" name="<?php echo esc_attr( $supplier_field_name ) ?>" id="<?php echo esc_attr( $supplier_field_id ) ?>" value="<?php echo esc_attr( ! empty( $supplier ) ? esc_attr( $supplier->ID ) : '' ) ?>">
			<input type="hidden" name="<?php echo esc_attr( $supplier_sku_field_name ) ?>" id="<?php echo esc_attr( $supplier_sku_field_id ) ?>" value="<?php echo esc_attr( $supplier_sku ?: '' ) ?>">

		<?php else :

			$supplier_fields_classes = (array) apply_filters( 'atum/product_data/supplier/classes', array_merge( [ 'show_if_simple' ], Helpers::get_option_group_hidden_classes() ) );

			Helpers::load_view( 'meta-boxes/product-data/supplier-fields', compact( 'supplier_field_name', 'supplier_field_id', 'variation', 'loop', 'supplier', 'supplier_sku', 'supplier_sku_field_name', 'supplier_sku_field_id', 'supplier_fields_classes' ) );

		endif;

	}

	/**
	 * Save the product supplier fields
	 *
	 * @since 1.3.0
	 */
	private function save_product_supplier_fields() {

		if ( in_array( $this->product->get_type(), array_diff( Globals::get_inheritable_product_types(), [ 'grouped', 'bundle' ] ) ) ) {
			return;
		}

		if ( $this->is_variation ) {
			$this->product_data['supplier_id']  = isset( $_POST[ 'variation' . Suppliers::SUPPLIER_META_KEY ][ $this->loop ] ) ? $_POST[ 'variation' . Suppliers::SUPPLIER_META_KEY ][ $this->loop ] : NULL;
			$this->product_data['supplier_sku'] = isset( $_POST[ 'variation' . Suppliers::SUPPLIER_SKU_META_KEY ][ $this->loop ] ) ? $_POST[ 'variation' . Suppliers::SUPPLIER_SKU_META_KEY ][ $this->loop ] : NULL;
		}
		else {
			$this->product_data['supplier_id']  = isset( $_POST[ Suppliers::SUPPLIER_META_KEY ] ) ? $_POST[ Suppliers::SUPPLIER_META_KEY ] : NULL;
			$this->product_data['supplier_sku'] = isset( $_POST[ Suppliers::SUPPLIER_SKU_META_KEY ] ) ? $_POST[ Suppliers::SUPPLIER_SKU_META_KEY ] : NULL;
		}

	}

	/**
	 * Save extra product data.
	 *
	 * @since 1.5.8
	 */
	private function save_extra_data() {

		// If it's out of stock, how many days?
		$this->product_data['out_stock_days'] = Helpers::get_product_out_stock_days( $this->product );

		// Has location terms assigned?
		$location_terms                     = wp_get_post_terms( $this->product->get_id(), Globals::PRODUCT_LOCATION_TAXONOMY );
		$this->product_data['has_location'] = ! empty( $location_terms );

	}

	/**
	 * Hook callback after saving a product
	 *
	 * @since 1.5.0
	 *
	 * @param int      $product_id The saved product's ID.
	 * @param \WP_Post $post       The saved post.
	 */
	public function save_product_meta_boxes( $product_id, $post ) {

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! isset( $_POST['product-type'] ) ) {
			return;
		}

		// Disable cache to avoid saving the wrong data.
		$was_cache_disabled = AtumCache::is_cache_disabled();
		if ( ! $was_cache_disabled ) {
			AtumCache::disable_cache();
		}
		
		do_action( 'atum/product_data/before_save_product_meta_boxes', $product_id, $post );

		$this->product      = Helpers::get_atum_product( $product_id );
		$this->is_variation = FALSE;
		$this->loop         = NULL;

		$this->save_atum_meta_boxes();

		do_action( 'atum/product_data/after_save_product_meta_boxes', $product_id, $post );

		if ( ! $was_cache_disabled ) {
			AtumCache::enable_cache();
		}

	}

	/**
	 * Hook callback after saving a variation product
	 *
	 * @since 1.5.0
	 *
	 * @param int $variation_id
	 * @param int $loop
	 */
	public function save_product_variation_meta_boxes( $variation_id, $loop ) {

		// Disable cache to avoid saving the wrong data.
		$was_cache_disabled = AtumCache::is_cache_disabled();
		if ( ! $was_cache_disabled ) {
			AtumCache::disable_cache();
		}
		
		do_action( 'atum/product_data/before_save_product_variation_meta_boxes', $variation_id, $loop );
		
		$this->product = Helpers::get_atum_product( $variation_id );

		if ( $this->product instanceof \WC_Product ) {

			$this->product->set_object_read( TRUE );

			$this->is_variation = TRUE;
			$this->loop         = $loop;

			$this->save_atum_meta_boxes();

			do_action( 'atum/product_data/after_save_product_variation_meta_boxes', $variation_id, $loop );

		}

		if ( ! $was_cache_disabled ) {
			AtumCache::enable_cache();
		}

	}

	/**
	 * Save all the collected data for the ATUM meta boxes at once
	 *
	 * @since 1.5.0
	 */
	public function save_atum_meta_boxes() {

		$this->save_product_data_panel();

		if ( 'yes' === Helpers::get_option( 'out_stock_threshold', 'no' ) ) {
			$this->save_out_stock_threshold_field();
		}

		if ( AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			$this->save_product_supplier_fields();
		}

		if ( $this->purchase_price_allowed ) {
			$purchase_price = $this->save_purchase_price();
		}

		// Save extra data (out of stock date, has_location, etc).
		$this->save_extra_data();

		$this->product_data = (array) apply_filters( 'atum/product_data/data_to_save', $this->product_data );

		if ( ! empty( $this->product_data ) ) {

			$errors = $this->product->set_props( $this->product_data );

			if ( is_wp_error( $errors ) ) {
				\WC_Admin_Meta_Boxes::add_error( $errors->get_error_message() );
			}

			if ( $this->product->is_type( 'variation' ) && $this->product->get_stock_status() !== $this->product->get_atum_stock_status() ) {
				$this->product->set_atum_stock_status( $this->product->get_stock_status() );
			}

			$this->product->save_atum_data();

			if ( isset( $purchase_price ) && $purchase_price['new_purchase_price'] !== $purchase_price['old_purchase_price'] ) {
				do_action( 'atum/product_data/after_save_purchase_price', $this->product->get_id(), $purchase_price['new_purchase_price'], $purchase_price['old_purchase_price'] );
			}

			do_action( 'atum/product_data/after_save_data', $this->product_data, $this->product );

		}

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
	 * @return ProductDataMetaBoxes instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
