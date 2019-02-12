<?php
/**
 * Shared trait for common method to all Atum Data Stores
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Inc\Globals;

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
		) );

		// Columns data need to be converted to datetime.
		$date_columns = (array) apply_filters( 'atum/data_store/date_columns', array(
			'out_stock_date',
		) );

		// Switches and/or checkboxes.
		$yes_no_columns = (array) apply_filters( 'atum/data_store/yes_no_columns', array(
			'atum_controlled',
			'inheritable',
		) );

		// Values which can be null in the database.
		$allow_null = (array) apply_filters( 'atum/data_store/allow_null_columns', array(
			'purchase_price',
			'out_stock_date',
			'out_stock_threshold',
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

		/* @noinspection PhpUndefinedClassInspection */
		parent::delete( $product, $args );
		
		// Delete the ATUM data for this product.
		if ( $args['force_delete'] ) {
			$wpdb->delete( $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE, array( 'product_id' => $id ), array( '%d' ) );
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
		$cache_key = AtumCache::get_cache_key( 'product_data', $product->get_id() );
		AtumCache::delete_cache( $cache_key );
	}
	
}
