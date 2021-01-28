<?php
/**
 * Shared trait for common method to all Atum Data Stores
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Inc\Globals;
use Atum\Inc\Hooks;


trait AtumDataStoreCommonTrait {
	
	/**
	 * Store data into ATUM's custom product data table
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product $product The product object.
	 */
	public function update_atum_product_data( &$product ) {
		
		global $wpdb;
		
		$changes = $product->get_changes();
		$data    = [];
		$insert  = FALSE;
		$row     = $this->get_product_row_from_db( $product->get_id() );

		$columns = (array) apply_filters( 'atum/data_store/columns', array(
			'purchase_price',
			'supplier_id',
			'supplier_sku',
			'atum_controlled',
			'out_stock_date',
			'out_stock_threshold',
			'inheritable',
			'inbound_stock',
			'stock_on_hold',
			'reserved_stock',
			'sold_today',
			'sales_last_days',
			'customer_returns',
			'warehouse_damage',
			'lost_in_post',
			'other_logs',
			'out_stock_days',
			'lost_sales',
			'has_location',
			'update_date',
			'atum_stock_status',
			'low_stock',
		) );

		// Columns data need to be converted to datetime.
		$date_columns = (array) apply_filters( 'atum/data_store/date_columns', array(
			'out_stock_date',
			'update_date',
		) );

		// Switches, checkboxes and flags.
		$yes_no_columns = (array) apply_filters( 'atum/data_store/yes_no_columns', array(
			'atum_controlled',
			'inheritable',
			'has_location',
			'low_stock',
		) );

		// Values which can be null in the database.
		$allow_null = (array) apply_filters( 'atum/data_store/allow_null_columns', array(
			'supplier_id',
			'purchase_price',
			'out_stock_date',
			'out_stock_threshold',
			'inbound_stock',
			'stock_on_hold',
			'reserved_stock',
			'sold_today',
			'sales_last_days',
			'customer_returns',
			'warehouse_damage',
			'lost_in_post',
			'other_logs',
			'out_stock_days',
			'lost_sales',
			'has_location',
			'update_date',
		) );

		// We should make an insert if the returning row is empty or has none of the ATUM columns.
		if ( ! $row || empty( array_intersect( array_keys( $row ), $columns ) ) ) {
			$insert = TRUE;
		}
		elseif ( empty( $changes ) ) {
			return;
		}
		
		foreach ( $columns as $column ) {
			
			if ( ( $insert || array_key_exists( $column, $changes ) ) && is_callable( array( $product, "get_$column" ) ) ) {
				
				$value = call_user_func( array( $product, "get_$column" ), 'edit' );
				
				if ( in_array( $column, $date_columns, TRUE ) ) {
					$data[ $column ] = empty( $value ) ? NULL : gmdate( 'Y-m-d H:i:s', $product->{"get_$column"}( 'edit' )->getOffsetTimestamp() );
				}
				elseif ( in_array( $column, $yes_no_columns, TRUE ) ) {

					// Some yes/no columns could allow NULL values too.
					if ( in_array( $column, $allow_null, TRUE ) && is_null( $value ) ) {
						$data[ $column ] = NULL;
					}
					else {
						$data[ $column ] = 'yes' === $value ? 1 : 0; // These columns are saved as integers in db.
					}

				}
				else {
					$data[ $column ] = '' === $value && in_array( $column, $allow_null, TRUE ) ? NULL : $value;
				}
				
				$this->updated_props[] = $column;
				
			}
			
		}
		
		if ( empty( $data ) ) {
			return;
		}

		// If not passing the updated date, add the current date.
		if ( ! array_key_exists( 'update_date', $changes ) ) {
			$data['update_date']   = gmdate( 'Y-m-d H:i:s' );
			$this->updated_props[] = 'update_date';
		}

		do_action( 'atum/data_store/before_saving_product_data', $data );
		
		if ( $insert ) {
			$data['product_id'] = $product->get_id();
			$wpdb->insert( $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE, $data );
		}
		elseif ( ! empty( $data ) ) {
			
			$wpdb->update(
				$wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE,
				$data,
				array(
					'product_id' => $product->get_id(),
				)
			);
			
		}

		$this->clear_caches( $product );

		do_action( 'atum/data_store/after_save_product_data', $data, $product->get_id() );
		
	}
	
	/**
	 * Method to delete a product from the database.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product $product The product object.
	 * @param array       $args    Array of args to pass to the delete method.
	 */
	public function delete( &$product, $args = array() ) {
		
		global $wpdb;

		$id = $product->get_id();

		$args = wp_parse_args(
			$args,
			array(
				'force_delete'   => FALSE, // If the product is being trashed or removed completely.
				'delete_product' => TRUE,  // If the product itself must be removed too.
			)
		);

		if ( ! $id ) {
			return;
		}
		
		// Delete the ATUM data for this product.
		if ( TRUE === $args['force_delete'] ) {

			$wpdb->delete(
				$wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE,
				[ 'product_id' => $id ],
				[ '%d' ]
			);

			do_action( 'atum/data_store/after_delete_product_data', $product, $args );

		}

		if ( TRUE === $args['delete_product'] ) {

			$post_type = $product->is_type( 'variation' ) ? 'product_variation' : 'product';

			if ( $args['force_delete'] ) {

				// Avoid our custom hook to run this method again.
				remove_action( 'delete_post', array( Hooks::get_instance(), 'before_delete_product' ) );

				do_action( "woocommerce_before_delete_$post_type", $id ); // Default WC action for compatibility.
				wp_delete_post( $id );
				$this->clear_caches( $product );
				$product->set_id( 0 );
				do_action( "woocommerce_delete_$post_type", $id ); // Default WC action for compatibility.

			}
			else {
				wp_trash_post( $id );
				$product->set_status( 'trash' );
				do_action( "woocommerce_trash_$post_type", $id ); // Default WC action for compatibility.
			}

		}

	}
	
	/**
	 * Clear any ATUM's data store caches.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product $product The product object.
	 */
	protected function clear_caches( &$product ) {

		/* @noinspection PhpUndefinedClassInspection */
		parent::clear_caches( $product );
		foreach ( [ 'product_data', 'atum_product' ] as $cache_name ) {

			$cache_key = AtumCache::get_cache_key( $cache_name, $product->get_id() );
			AtumCache::delete_cache( $cache_key );
		}

	}
	
}
