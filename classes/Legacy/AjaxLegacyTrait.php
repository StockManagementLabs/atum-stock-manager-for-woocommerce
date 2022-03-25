<?php
/**
 * Legacy trait for Ajax callbacks
 *
 * @package         Atum\Legacy
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @deprecated      This legacy class is only here for backwards compatibility and will be removed in a future version.
 *
 * @since           1.5.0
 */

namespace Atum\Legacy;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\Models\Log;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\Suppliers\Suppliers;


trait AjaxLegacyTrait {

	/**
	 * Seach for products from enhanced selects
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.3.7
	 */
	public function search_products_legacy() {

		check_ajax_referer( 'search-products', 'security' );

		if ( empty( $_GET['term'] ) ) {
			wp_die( [] );
		}

		$term = (string) wc_clean( wp_unslash( $_GET['term'] ) );

		if ( empty( $term ) ) {
			wp_die( [] );
		}

		$limit       = ! empty( $_GET['limit'] ) ? absint( $_GET['limit'] ) : absint( apply_filters( 'atum/ajax/search_products/json_search_limit', 30 ) );
		$include_ids = ! empty( $_GET['include'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['include'] ) ) : [];
		$exclude_ids = ! empty( $_GET['exclude'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) ) : [];

		$ids = Helpers::search_products( $term, '', TRUE, FALSE, $limit, $include_ids, $exclude_ids );

		$post_id = ! empty( $_GET['id'] ) ? absint( $_GET['id'] ) : NULL;

		if ( ! $post_id ) {

			$url = wp_parse_url( wp_get_referer() );
			parse_str( $url['query'], $url_query );

			if ( ! empty( $url_query['post'] ) ) {
				$post_id = absint( $url_query['post'] );
			}

		}

		$included = [];

		if ( $post_id ) {

			$atum_order = Helpers::get_atum_order_model( $post_id, FALSE );

			// The Purchase Orders only should allow products from the current PO's supplier (if such PO only allows 1 supplier).
			if ( $atum_order instanceof PurchaseOrder && ! $atum_order->has_multiple_suppliers() ) {

				$supplier_products    = Suppliers::get_supplier_products( $atum_order->get_supplier( 'id' ), [ 'product', 'product_variation' ], FALSE );
				$no_supplier_products = Suppliers::get_no_supplier_products();
				$supplier_products    = is_array( $supplier_products ) ? array_map( 'absint', $supplier_products ) : [];

				if ( is_array( $no_supplier_products ) ) {
					$no_supplier_products = array_map( 'absint', $no_supplier_products );
					$supplier_products    = array_merge( $supplier_products, $no_supplier_products );
				}

				// If the PO supplier has no linked products, it must return an empty array.
				$included = $supplier_products;

			}

		}

		if ( ! empty( $included ) ) {
			$ids = array_intersect( $ids, $included );
		}

		wp_send_json( apply_filters( 'atum/ajax/search_products/json_search_found_products', $this->prepare_json_search_products( $ids, $atum_order ?? NULL ) ) );

	}

	/**
	 * Prepare the list of products to be returned to the ajax search
	 *
	 * @since 1.9.14
	 *
	 * @param int[]             $ids
	 * @param PurchaseOrder|Log $atum_order
	 *
	 * @return array
	 */
	private function prepare_json_search_products( $ids, $atum_order = NULL ) {

		// Exclude variable products from results.
		$exclude_types = (array) apply_filters( 'atum/ajax/search_products/excluded_product_types', array_diff( Globals::get_inheritable_product_types(), [ 'grouped', 'bundle' ] ) );
		$products      = [];

		foreach ( $ids as $id ) {

			$product = Helpers::get_atum_product( $id );

			if ( ! wc_products_array_filter_readable( $product ) ) {
				continue;
			}

			if ( in_array( $product->get_type(), $exclude_types, TRUE ) ) {
				continue;
			}

			$products[ $product->get_id() ] = rawurldecode( wp_strip_all_tags( $product->get_formatted_name() ) );

		}

		return array_filter( $products );

	}

}
