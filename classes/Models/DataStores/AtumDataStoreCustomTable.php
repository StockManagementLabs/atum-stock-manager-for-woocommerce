<?php
/**
 * Shared trait for Atum Data Stores (using custom tables)
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

use Atum\Inc\Globals;

trait AtumDataStoreCustomTable {

	/**
	 * Store data into WC's and ATUM's custom product data tables
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product $product The product object.
	 */
	protected function update_product_data( &$product ) {

		parent::update_product_data( $product );
		$this->update_atum_product_data( $product );

	}

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

		if ( ! $row ) {
			$insert = TRUE;
		}
		elseif ( empty( $changes ) ) {
			return;
		}

		$columns = array(
			'purchase_price',
			'supplier_id',
			'supplier_sku',
			'atum_controlled',
			'out_stock_date',
			'out_stock_threshold',
			'inheritable',
		);

		// Columns data need to be converted to datetime.
		$date_columns = array(
			'out_stock_date',
		);

		// Switches and/or checkboxes.
		$yes_no_columns = array(
			'atum_controlled',
			'inheritable',
		);

		// Values which can be null in the database.
		$allow_null = array(
			'purchase_price',
			'out_stock_date',
			'out_stock_threshold',
		);

		foreach ( $columns as $column ) {

			if ( $insert || array_key_exists( $column, $changes ) ) {

				$value = $product->{"get_$column"}( 'edit' );

				if ( in_array( $column, $date_columns, TRUE ) ) {
					$data[ $column ] = empty( $value ) ? NULL : gmdate( 'Y-m-d H:i:s', $product->{"get_$column"}( 'edit' )->getOffsetTimestamp() );
				}
				elseif ( in_array( $column, $yes_no_columns, TRUE ) ) {
					$data[ $column ] = 'yes' === $value ? 1 : 0; // These columns are saved as integers in db.
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
			$wpdb->insert( $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE, $data ); // WPCS: db call ok, cache ok.
		}
		elseif ( ! empty( $data ) ) {

			$wpdb->update(
				$wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE,
				$data,
				array(
					'product_id' => $product->get_id(),
				)
			); // WPCS: db call ok, cache ok.

		}

	}

	/**
	 * Get product data row from the DB whilst utilising cache.
	 *
	 * @since 1.5.0
	 *
	 * @param int $product_id Product ID to grab from the database.
	 *
	 * @return array
	 */
	protected function get_product_row_from_db( $product_id ) {

		global $wpdb;

		$data = wp_cache_get( ATUM_PREFIX . 'woocommerce_product_' . $product_id, 'product' );

		if ( FALSE === $data ) {

			// Get the default data from parent class.
			$data = parent::get_product_row_from_db( $product_id );

			// Get the extra ATUM data for the product.
			$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
			$atum_data               = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $atum_product_data_table WHERE product_id = %d;", $product_id ), ARRAY_A ); // WPCS: Unprepared SQL ok.

			$data = array_merge( $data, $atum_data );

			wp_cache_set( ATUM_PREFIX . 'woocommerce_product_' . $product_id, $data, 'product' );

		}

		return (array) apply_filters( 'atum/model/product_data_store/product_data', $data, $product_id );

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

		parent::clear_caches( $product );
		wp_cache_delete( ATUM_PREFIX . 'woocommerce_product_' . $product->get_id(), 'product' );
	}

}
