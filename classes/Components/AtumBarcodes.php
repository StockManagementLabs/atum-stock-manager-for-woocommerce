<?php
/**
 * Handle Barcodes in ATUM
 *
 * @package     Atum
 * @subpackage  Components
 * @since       1.9.18
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumListTables\AtumListTable;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Models\Interfaces\AtumProductInterface;
use Atum\Modules\ModuleManager;
use Atum\Suppliers\Suppliers;
use AtumLevels\Levels\Interfaces\BOMProductInterface;


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

			// Allow removing barcode support externally.
			if ( apply_filters( 'atum/barcodes/barcode_support/product', TRUE ) ) {

				// Add the barcode field to WC products.
				add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_barcode_field_to_products' ), 10, 3 );
				add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_barcode_field_to_products' ) );

				// Save the barcode field when saving the ATUM's meta boxes.
				add_action( 'atum/product_data/data_to_save', array( $this, 'save_barcode_field' ), 10, 3 );

				// Add the barcode column to SC and MC.
				add_filter( 'atum/stock_central_list/column_group_members', array( $this, 'add_barcode_column_to_group' ) );
				add_filter( 'atum/product_levels/manufacturing_list_table/column_group_members', array( $this, 'add_barcode_column_to_group' ) );
				add_filter( 'atum/stock_central_list/table_columns', array( $this, 'add_barcode_column' ) );
				add_filter( 'atum/product_levels/manufacturing_list_table/table_columns', array( $this, 'add_barcode_column' ) );
				add_filter( 'atum/stock_central_list/searchable_columns', array( $this, 'add_searchable_barcode_column' ) );
				add_filter( 'atum/product_levels/manufacturing_list_table/searchable_columns', array( $this, 'add_searchable_barcode_column' ) );
				add_filter( 'atum/list_table/atum_sortable_columns', array( $this, 'add_sortable_atum_column' ) );
				add_filter( 'atum/list_table/column_default__barcode', array( $this, 'column__barcode' ), 10, 4 );

			}

			// Add the barcode field to some product terms.
			$taxonomies = apply_filters( 'atum/barcodes/allowed_taxonomies', [ Globals::PRODUCT_LOCATION_TAXONOMY, 'product_cat', 'product_tag' ] );
			foreach ( $taxonomies as $taxonomy ) {
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
	 * @param int      $loop           Only for variations. The loop item number.
	 * @param array    $variation_data Only for variations. The variation item data.
	 * @param \WP_Post $variation      Only for variations. The variation product.
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

			$visibility_classes = array_map( function ( $val ) {
				return "show_if_{$val}";
			}, Globals::get_all_compatible_product_types() );

			$barcode_field_classes = implode( ' ', $visibility_classes );

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

			// Add the barcode col after the supplier SKU (if the PO module is enabled).
			if (
				( ModuleManager::is_module_active( 'purchase_orders' ) && '_supplier_sku' === $column_key ) ||
				'_sku' === $column_key
			) {
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
	 * @param string                                                                     $column_item
	 * @param \WP_Post                                                                   $item
	 * @param \WC_Product|\WC_Product_Variation|AtumProductInterface|BOMProductInterface $product
	 * @param AtumListTable                                                              $list_table
	 *
	 * @return string
	 */
	public function column__barcode( $column_item, $item, $product, $list_table ) {

		$barcode = AtumListTable::EMPTY_COL;

		if ( apply_filters( 'atum/barcodes/list_table/support_barcode', ! $list_table->allow_calcs || ! AtumCapabilities::current_user_can( 'view_barcode' ) ) ) {
			return $barcode;
		}

		$barcode = $product->get_barcode();

		if ( ! $barcode ) {
			$barcode = AtumListTable::EMPTY_COL;
		}

		if ( $list_table->allow_edit && AtumCapabilities::current_user_can( 'edit_barcode' ) ) {

			$args = apply_filters( 'atum/barcodes/list_table/editable_args', array(
				'meta_key'   => 'barcode',
				'value'      => $barcode,
				'input_type' => 'text',
				'tooltip'    => esc_attr__( 'Click to edit the barcode', ATUM_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Barcode', ATUM_TEXT_DOMAIN ),
			), $product );

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

				<?php do_action( 'atum/barcodes/after_barcode_term_meta_input', $term, $barcode ) ?>

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

            $field_value        = esc_attr( stripslashes( $_POST['barcode_term_meta'] ) );
            $term_barcode_found = self::get_term_id_by_barcode( $term_id, $field_value, $_POST['taxonomy'] );

            if ( $term_barcode_found ) {

                AtumAdminNotices::add_notice(
                    __( 'Error saving the term: Invalid or duplicated barcode.', ATUM_TEXT_DOMAIN ),
                    'invalid_barcode',
                    'error',
                    FALSE,
                    TRUE
                );

            }
            else {
			    update_term_meta( $term_id, 'barcode', $field_value );
            }

		}

	}

    /**
     * Check if the passed barcode is being used by another product.
     *
     * @since 1.9.41
     *
     * @param int    $product_id Product ID to exclude from the query.
     * @param string $barcode    Will be slashed to work around https://core.trac.wordpress.org/ticket/27421.
     *
     * @return int|NULL
     */
    public static function get_product_id_by_barcode( $product_id, $barcode ) {

        if ( ! $product_id || ! $barcode ) {
            return NULL;
        }

        $cache_key        = AtumCache::get_cache_key( 'product_id_by_barcode', [ $product_id, $barcode ] );
        $found_product_id = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

        if ( ! $has_cache ) {

            global $wpdb;

            $atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

            // phpcs:disable WordPress.DB.PreparedSQL
            $found_product_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT p.ID
				FROM $wpdb->posts p
				LEFT JOIN $atum_data_table apd ON ( p.ID = apd.product_id )
				WHERE p.post_status != 'trash' AND apd.barcode = %s AND p.ID <> %d
				LIMIT 1",
                wp_slash( $barcode ),
                $product_id
            ) );
            // phpcs:enable

            AtumCache::set_cache( $cache_key, $found_product_id );

        }

        return $found_product_id;

    }

    /**
     * Check if the passed barcode is being used by another term.
     *
     * @since 1.9.41
     *
     * @param int    $term_id  Term ID to exclude from the query.
     * @param string $barcode  Will be slashed to work around https://core.trac.wordpress.org/ticket/27421.
     * @param string $taxonomy The taxonomy to search for the barcode.
     *
     * @return int|NULL
     */
    public static function get_term_id_by_barcode( $term_id, $barcode, $taxonomy ) {

        if ( ! $term_id || ! $barcode || ! $taxonomy ) {
            return NULL;
        }

        $cache_key     = AtumCache::get_cache_key( 'term_id_by_barcode', [ $term_id, $barcode, $taxonomy ] );
        $found_term_id = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

        if ( ! $has_cache ) {

            global $wpdb;

            // phpcs:disable WordPress.DB.PreparedSQL
            $found_term_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT t.term_id
                FROM $wpdb->termmeta tm
                LEFT JOIN $wpdb->terms t ON ( t.term_id = tm.term_id AND tm.meta_key = 'barcode' )
                LEFT JOIN $wpdb->term_taxonomy tt ON ( t.term_id = tt.term_id )
                WHERE tt.taxonomy = %s AND tm.meta_value = %s AND t.term_id <> %d
                LIMIT 1",
                esc_attr( $taxonomy ),
                wp_slash( $barcode ),
                $term_id
            ) );
            // phpcs:enable

            AtumCache::set_cache( $cache_key, $found_term_id );

        }

        return $found_term_id;

    }

    /**
     * Check if the passed barcode is being used by another supplier.
     *
     * @since 1.9.41
     *
     * @param int    $supplier_id Supplier ID to exclude from the query.
     * @param string $barcode     Will be slashed to work around https://core.trac.wordpress.org/ticket/27421.
     *
     * @return int|NULL
     */
    public static function get_supplier_id_by_barcode( $supplier_id, $barcode ) {

        if ( ! $supplier_id || ! $barcode ) {
            return NULL;
        }

        $cache_key         = AtumCache::get_cache_key( 'supplier_id_by_barcode', [ $supplier_id, $barcode ] );
        $found_supplier_id = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

        if ( ! $has_cache ) {

            global $wpdb;

            // phpcs:disable WordPress.DB.PreparedSQL
            $found_supplier_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT p.ID
				FROM $wpdb->posts p
				LEFT JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id AND pm.meta_key = '_atum_barcode' )
				WHERE p.post_status != 'trash' AND p.post_type = %s AND pm.meta_value = %s AND p.ID <> %d
				LIMIT 1",
                Suppliers::POST_TYPE,
                wp_slash( $barcode ),
                $supplier_id
            ) );
            // phpcs:enable

            AtumCache::set_cache( $cache_key, $found_supplier_id );

        }

        return $found_supplier_id;

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
