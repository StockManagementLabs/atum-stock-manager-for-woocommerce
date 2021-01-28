<?php
/**
 * Shared trait for Atum Data Legacy Stores (using legacy tables)
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


trait AtumDataStoreCPTTrait {
	
	/**
	 * Read product data.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product $product Product object.
	 */
	public function read_product_data( &$product ) {

		$id        = $product->get_id();
		$atum_data = $this->get_product_row_from_db( $id );
		
		$product->set_props(
			$atum_data
		);

		/* @noinspection PhpUnhandledExceptionInspection PhpUndefinedClassInspection */
		parent::read_product_data( $product );

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
		$atum_data = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );
		
		if ( ! $has_cache ) {
			
			// Get the extra ATUM data for the product.
			$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
			$atum_data               = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $atum_product_data_table WHERE product_id = %d;", $product_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

			if ( ! empty( $atum_data ) ) {
				AtumCache::set_cache( $cache_key, $atum_data );
			}
			
		}
		
		return (array) apply_filters( 'atum/model/product_data_store_cpt/product_data', $atum_data, $product_id );
		
	}
	
	/**
	 * Helper method that updates all the post meta for a product based on it's settings in the WC_Product class.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product $product Product object.
	 * @param bool        $force Force update. Used during create.
	 */
	public function update_post_meta( &$product, $force = FALSE ) {

		/* @noinspection PhpUndefinedClassInspection */
		parent::update_post_meta( $product, $force );
		$this->update_atum_product_data( $product );

	}

}
