<?php
/**
 * WC Grouped Product data store: using new custom tables
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

class WCProductGroupedDataStoreCustomTable extends WCProductDataStoreCustomTable implements \WC_Object_Data_Store_Interface {

	/**
	 * Handle updated meta props after updating meta data.
	 *
	 * @since  3.0.0
	 * @param  WC_Product $product Product Object.
	 */
	protected function handle_updated_props( &$product ) {
		if ( in_array( 'children', $this->updated_props, true ) ) {
			$this->update_prices_from_children( $product );
		}
		parent::handle_updated_props( $product );
	}

	/**
	 * Sync grouped product prices with children.
	 *
	 * @since 3.0.0
	 * @param WC_Product $product Product Object.
	 */
	public function sync_price( &$product ) {
		$this->update_prices_from_children( $product );
	}

	/**
	 * Loop over child products and update the grouped product price to match the lowest child price.
	 *
	 * @param WC_Product $product Product object.
	 */
	protected function update_prices_from_children( &$product ) {
		global $wpdb;

		$min_price = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT price
				FROM {$wpdb->prefix}wc_products as products
				LEFT JOIN {$wpdb->posts} as posts ON products.product_id = posts.ID
				WHERE posts.post_parent = %d
				order by price ASC",
				$product->get_id()
			)
		); // WPCS: db call ok, cache ok.

		$wpdb->update(
			"{$wpdb->prefix}wc_products",
			array(
				'price' => wc_format_decimal( $min_price ),
			),
			array(
				'product_id' => $product->get_id(),
			)
		); // WPCS: db call ok, cache ok.
	}

	/**
	 * Empty method that overrides parent method and prevent the use of
	 * WC_Product_Grouped::extra_data. If we don't do this, the post meta
	 * '_children' will be used instead of product relationships from the
	 * table wp_wc_product_relationships to get grouped products children.
	 *
	 * @param WC_Product $product Product object.
	 */
	protected function read_extra_data( &$product ) {}
}
