<?php
/**
 * Shared trait for Atum Data Legacy Stores (using custom tables)
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\DataStores;

use Atum\Inc\Globals;


defined( 'ABSPATH' ) || die;

trait AtumDataStoreLegacyCustomTableTrait {
	
	/**
	 * Read product data.
	 *
	 * @param \WC_Product $product Product object.
	 * @since 1.5.0
	 */
	protected function read_product_data( &$product ) {
		
		$id = $product->get_id();
		
		$atum_data = $this->get_product_row_from_db( $id );
		
		$product->set_props(
			$atum_data
		);
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
		
		$atum_data = wp_cache_get( ATUM_PREFIX . 'woocommerce_product_' . $product_id, 'product' );
		
		if ( FALSE === $atum_data ) {
			
			// Get the extra ATUM data for the product.
			$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
			$atum_data               = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $atum_product_data_table WHERE product_id = %d;", $product_id ), ARRAY_A ); // WPCS: Unprepared SQL ok.
			
			wp_cache_set( ATUM_PREFIX . 'woocommerce_product_' . $product_id, $atum_data, 'product' );
			
		}
		
		return (array) apply_filters( 'atum/model/product_data_store_legacy/product_data', $atum_data, $product_id );
		
	}
	
	/**
	 * Helper method that updates all the post meta for a product based on it's settings in the WC_Product class.
	 *
	 * @param \WC_Product $product Product object.
	 * @param bool        $force Force update. Used during create.
	 * @since 1.5.0
	 */
	protected function update_post_meta( &$product, $force = false ) {
		
		parent::update_post_meta( $product, $force );
		$this->update_atum_product_data( $product );
	}
}
