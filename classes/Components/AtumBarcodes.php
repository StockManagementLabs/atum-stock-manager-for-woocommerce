<?php
/**
 * Handle Barcodes in ATUM
 *
 * @package     Atum
 * @subpackage  Components
 * @since       1.9.18
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumListTables\AtumListTable;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Models\Products\AtumProductTrait;
use AtumLevels\Levels\Products\BOMProductTrait;


class AtumBarcodes {

	/**
	 * The barcode field name
	 */
	const BARCODE_FIELD_KEY = '_barcode';

	/**
	 * The singleton instance holder
	 *
	 * @var AtumBarcodes
	 */
	private static $instance;

	/**
	 * AtumBarcodes singleton constructor.
	 *
	 * @since 1.9.18
	 */
	private function __construct() {

		if ( is_admin() ) {

			// Add the barcode field to WC products.
			add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_barcode_field_to_products' ), 10, 3 );
			add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_barcode_field_to_products' ) );

			// Save the barcode field when saving the ATUM's meta boxes.
			add_action( 'atum/product_data/data_to_save', array( $this, 'save_barcode_field' ), 10, 3 );

			// Add the barcode columnn to SC and MC.
			add_filter( 'atum/stock_central_list/column_group_members', array( $this, 'add_barcode_column_to_group' ) );
			add_filter( 'atum/product_levels/manufacturing_list_table/column_group_members', array( $this, 'add_barcode_column_to_group' ) );
			add_filter( 'atum/stock_central_list/table_columns', array( $this, 'add_barcode_column' ) );
			add_filter( 'atum/product_levels/manufacturing_list_table/table_columns', array( $this, 'add_barcode_column' ) );
			add_filter( 'atum/stock_central_list/searchable_columns', array( $this, 'add_searchable_barcode_column' ) );
			add_filter( 'atum/product_levels/manufacturing_list_table/searchable_columns', array( $this, 'add_searchable_barcode_column' ) );
			add_filter( 'atum/list_table/atum_sortable_columns', array( $this, 'add_sortable_atum_column' ) );
			add_filter( 'atum/list_table/column_default__barcode', array( $this, 'column__barcode' ), 10, 4 );

			// Add the barcode field to some product terms.
			foreach ( [ Globals::PRODUCT_LOCATION_TAXONOMY, 'product_cat' ] as $taxonomy ) {
				add_action( "{$taxonomy}_edit_form_fields", array( $this, 'add_barcode_term_meta' ), 11, 2 );
				add_action( "edited_$taxonomy", array( $this, 'save_barcode_term_meta' ), 10, 2 );
			}

		}

	}

	/**
	 * Add the barcode field to the product data meta box
	 *
	 * @since 1.9.18
	 *
	 * @param int      $loop             Only for variations. The loop item number.
	 * @param array    $variation_data   Only for variations. The variation item data.
	 * @param \WP_Post $variation        Only for variations. The variation product.
	 */
	public function add_barcode_field_to_products( $loop = NULL, $variation_data = array(), $variation = NULL ) {

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
		$barcode_key        = self::BARCODE_FIELD_KEY;
		$barcode            = $product->get_barcode();
		$barcode_field_name = empty( $variation ) ? $barcode_key : "variation{$barcode_key}[$loop]";
		$barcode_field_id   = empty( $variation ) ? $barcode_key : $barcode_key . $loop;

		// If the user is not allowed to edit barcodes, add a hidden input.
		if ( ! AtumCapabilities::current_user_can( 'edit_barcode' ) ) : ?>

			<input type="hidden" name="<?php echo esc_attr( $barcode_field_name ) ?>" id="<?php echo esc_attr( $barcode_field_id ) ?>" value="<?php echo esc_attr( $barcode ) ?>">

		<?php else :

			$barcode_field_classes = (array) apply_filters( 'atum/barcodes/product_data_field/classes', array_merge( [ 'show_if_simple' ], Helpers::get_option_group_hidden_classes() ) );

			Helpers::load_view( 'meta-boxes/product-data/barcode-field', compact( 'barcode_field_name', 'barcode_field_id', 'variation', 'loop', 'barcode', 'barcode_field_classes' ) );

		endif;

	}

	/**
	 * Save the barcode field when saving the ATUM's product meta boxes
	 *
	 * @since 1.9.18
	 *
	 * @param array       $product_data
	 * @param \WC_Product $product
	 * @param int         $loop
	 *
	 * @return array
	 */
	public function save_barcode_field( $product_data, $product, $loop ) {

		if ( $product->is_type( 'variation' ) ) {
			$product_data['barcode'] = isset( $_POST[ 'variation' . self::BARCODE_FIELD_KEY ][ $loop ] ) ? $_POST[ 'variation' . self::BARCODE_FIELD_KEY ][ $loop ] : NULL;
		}
		else {
			$product_data['barcode'] = isset( $_POST[ self::BARCODE_FIELD_KEY ] ) ? $_POST[ self::BARCODE_FIELD_KEY ] : NULL;
		}

		return $product_data;

	}

	/**
	 * Add the barcode column to List Table group
	 *
	 * @since 1.9.18
	 *
	 * @param array $groups
	 *
	 * @return array
	 */
	public function add_barcode_column_to_group( $groups ) {

		$new_table_groups = array();

		foreach ( $groups as $group_key => $group ) {

			$new_table_groups[ $group_key ] = $group;

			if ( 'product-details' === $group_key ) {
				// Add the barcode column to Product Details group.
				$new_table_groups[ $group_key ]['members'][] = self::BARCODE_FIELD_KEY;
			}

		}

		return $new_table_groups;

	}

	/**
	 * Filter the List Table columns array, to add the barcode column
	 *
	 * @since 1.9.18
	 *
	 * @param array $table_columns
	 *
	 * @return array
	 */
	public function add_barcode_column( $table_columns ) {

		$new_table_colums = array();

		// Add the columns after the Product Details group.
		foreach ( $table_columns as $column_key => $column_value ) {

			$new_table_colums[ $column_key ] = $column_value;

			// Add the barcode col.
			if ( '_supplier_sku' === $column_key ) {
				$new_table_colums[ self::BARCODE_FIELD_KEY ] = __( 'Barcode', ATUM_TEXT_DOMAIN );
			}

		}

		return $new_table_colums;

	}

	/**
	 * Add the barcode column to the list table's searchable columns
	 *
	 * @since 1.9.18
	 *
	 * @param array $searchable_columns
	 *
	 * @return array
	 */
	public function add_searchable_barcode_column( $searchable_columns ) {

		return array_merge_recursive( $searchable_columns, array(
			'string' => array(
				self::BARCODE_FIELD_KEY,
			),
		) );
	}

	/**
	 * Add the barcode column to the list of ATUM's sortable columns
	 *
	 * @since 1.9.18
	 *
	 * @param string[] $sortable_atum_columns
	 *
	 * @return string[]
	 */
	public function add_sortable_atum_column( $sortable_atum_columns ) {

		$sortable_atum_columns[ self::BARCODE_FIELD_KEY ] = array(
			'type'  => 'STRING',
			'field' => 'barcode',
		);

		return $sortable_atum_columns;
	}

	/**
	 * Column for the barcode at List Tables
	 *
	 * @since 1.9.18
	 *
	 * @param string                                                             $column_item
	 * @param \WP_Post                                                           $item
	 * @param \WC_Product|\WC_Product_Variation|AtumProductTrait|BOMProductTrait $product
	 * @param AtumListTable                                                      $list_table
	 *
	 * @return string
	 */
	public function column__barcode( $column_item, $item, $product, $list_table ) {

		$barcode = AtumListTable::EMPTY_COL;

		if ( apply_filters( 'atum/barcodes/list_table/support_barcode', ! $list_table->allow_calcs || ! AtumCapabilities::current_user_can( 'view_barcode' ) ) ) {
			return $barcode;
		}

		$barcode = $product->get_barcode();

		if ( 0 === strlen( $barcode ) ) {
			$barcode = AtumListTable::EMPTY_COL;
		}

		if ( AtumCapabilities::current_user_can( 'edit_barcode' ) ) {

			$args = apply_filters( 'atum/barcodes/list_table/editable_args', array(
				'meta_key'   => 'barcode',
				'value'      => $barcode,
				'input_type' => 'text',
				'tooltip'    => esc_attr__( 'Click to edit the barcode', ATUM_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Barcode', ATUM_TEXT_DOMAIN ),
			) );

			$barcode = AtumListTable::get_editable_column( $args );

		}

		return apply_filters( 'atum/barcodes/list_table/column_barcode', $barcode, $item, $product, $list_table );

	}

	/**
	 * Add the barcode field to some terms
	 *
	 * @since 1.9.18
	 *
	 * @param \WP_Term $term
	 */
	public function add_barcode_term_meta( $term ) {

		$barcode = get_term_meta( $term->term_id, 'barcode', TRUE );

		?>
		<tr class="form-field">
			<th scope="row">
				<label for="barcode_term_meta">
					<?php Helpers::atum_field_label(); ?>
					<?php esc_html_e( 'Barcode', ATUM_TEXT_DOMAIN ); ?>
				</label>
			</th>
			<td>
				<input type="text" name="barcode_term_meta" id="barcode_term_meta" value="<?php echo esc_attr( $barcode ) ?>">
				<p class="description">
					<?php esc_html_e( 'The barcode for all the products linked to this term.', ATUM_TEXT_DOMAIN ) ?>
				</p>
			</td>
		</tr>
		<?php

	}

	/**
	 * Save the barcode field for terms.
	 *
	 * @since 1.9.18
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Term taxonomy ID.
	 */
	public function save_barcode_term_meta( $term_id, $tt_id ) {

		if ( isset( $_POST['barcode_term_meta'] ) ) {
			$field_value = esc_attr( stripslashes( $_POST['barcode_term_meta'] ) );
			update_term_meta( $term_id, 'barcode', $field_value );
		}

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
	 * @return AtumBarcodes instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
