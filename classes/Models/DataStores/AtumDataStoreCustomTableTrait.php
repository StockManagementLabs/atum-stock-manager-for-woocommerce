<?php
/**
 * Shared trait for Atum Data Stores (using custom tables)
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

trait AtumDataStoreCustomTableTrait {

	/**
	 * Store data into WC's and ATUM's custom product data tables
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product $product The product object.
	 */
	protected function update_product_data( &$product ) {

		/* @noinspection PhpUndefinedClassInspection */
		parent::update_product_data( $product );
		$this->update_atum_product_data( $product );

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

		$cache_key = AtumCache::get_cache_key( 'product_data', $product_id );
		$data      = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			// Get the default data from parent class.
			/* @noinspection PhpUndefinedClassInspection */
			$data = parent::get_product_row_from_db( $product_id );

			// Get the extra ATUM data for the product.
			$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
			$atum_data               = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $atum_product_data_table WHERE product_id = %d;", $product_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

			if ( ! empty( $atum_data ) ) {
				$data = array_merge( $data, $atum_data );
				AtumCache::set_cache( $cache_key, $data );
			}

		}

		return (array) apply_filters( 'atum/model/product_data_store_custom_table/product_data', $data, $product_id );

	}

}
