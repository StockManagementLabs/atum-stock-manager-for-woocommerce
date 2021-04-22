<?php
/**
 * Legacy trait for Ajax callbacks
 *
 * @package         Atum\Legacy
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @deprecated      This legacy class is only here for backwards compatibility and will be removed in a future version.
 *
 * @since           1.5.0
 */

namespace Atum\Legacy;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;
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

		ob_start();

		$term = stripslashes( $_GET['term'] );

		$post_id = isset( $_GET['limit'] ) ? intval( $_GET['limit'] ) : 0;

		if ( empty( $term ) ) {
			wp_die();
		}

		global $wpdb;

		$like_term     = '%%' . $wpdb->esc_like( $term ) . '%%';
		$post_types    = apply_filters( 'atum/ajax/search_products/searched_post_types', [ 'product', 'product_variation' ] );
		$post_statuses = current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ];
		$meta_join     = $meta_where = array();
		$type_where    = '';
		$join_counter  = 1;

		// Search by meta keys.
		if ( ! empty( $wpdb->wc_product_meta_lookup ) ) {

			$meta_join[]  = "LEFT JOIN $wpdb->wc_product_meta_lookup plu ON posts.ID = plu.product_id";
			$meta_where[] = $wpdb->prepare( 'OR plu.sku LIKE %s', $like_term );

		}
		/* @deprecated Searching by meta keys is too slow, so we should us the lookup tables where possible */
		else {

			$searched_metas = array_map( 'wc_clean', apply_filters( 'atum/ajax/search_products/searched_meta_keys', [ '_sku' ] ) );

			foreach ( $searched_metas as $searched_meta ) {
				$meta_join[]  = "LEFT JOIN {$wpdb->postmeta} pm{$join_counter} ON posts.ID = pm{$join_counter}.post_id";
				$meta_where[] = $wpdb->prepare( "OR ( pm{$join_counter}.meta_key = %s AND pm{$join_counter}.meta_value LIKE %s )", $searched_meta, $like_term ); // phpcs:ignore WordPress.DB.PreparedSQL
				$join_counter ++;
			}

		}

		// Search by Supplier SKU.
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$meta_join[]     = "LEFT JOIN $atum_data_table apd ON posts.ID = apd.product_id";
		$meta_where[]    = $wpdb->prepare( 'OR apd.supplier_sku LIKE %s', $like_term );

		// Exclude variable products from results.
		$excluded_types = (array) apply_filters( 'atum/ajax/search_products/excluded_product_types', array_diff( Globals::get_inheritable_product_types(), [ 'grouped', 'bundle' ] ) );

		if ( ! empty( $excluded_types ) ) {

			$excluded_type_terms = array();

			foreach ( $excluded_types as $excluded_type ) {
				$excluded_type_terms[] = get_term_by( 'slug', $excluded_type, 'product_type' );
			}

			$excluded_type_terms = wp_list_pluck( array_filter( $excluded_type_terms ), 'term_taxonomy_id' );

			$type_where = "AND posts.ID NOT IN (
				SELECT p.ID FROM $wpdb->posts p
				LEFT JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id
				WHERE p.post_type IN ('" . implode( "','", $post_types ) . "')
				AND p.post_status IN ('" . implode( "','", $post_statuses ) . "')
				AND tr.term_taxonomy_id IN (" . implode( ',', $excluded_type_terms ) . ')
			)';

		}

		$query_select = " SELECT DISTINCT posts.ID FROM $wpdb->posts posts " . implode( "\n", $meta_join ) . ' ';

		// phpcs:disable WordPress.DB.PreparedSQL
		$where_clause = $wpdb->prepare( 'WHERE (
				posts.post_title LIKE %s
				OR posts.post_content LIKE %s
				' . implode( "\n", $meta_where ) . "
			)
			AND posts.post_type IN ('" . implode( "','", $post_types ) . "')
			AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "')
			" . $type_where . ' ',
			$like_term,
			$like_term
		);
		// phpcs:enable

		$query_select = apply_filters( 'atum/ajax/search_products/query_select', $query_select );
		$where_clause = apply_filters( 'atum/ajax/search_products/query_where', $where_clause );

		$query = "
			$query_select $where_clause
			ORDER BY posts.post_parent ASC, posts.post_title ASC
		";

		$product_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( is_numeric( $term ) ) {

			$post_id   = absint( $term );
			$post_type = get_post_type( $post_id );

			if ( 'product_variation' === $post_type ) {
				$product_ids[] = $post_id;
			}
			elseif ( 'product' === $post_type ) {
				$product_ids[] = $post_id;
			}

			$product_ids[] = wp_get_post_parent_id( $post_id );

		}

		$ids = wp_parse_id_list( $product_ids );

		if ( ! empty( $_GET['exclude'] ) ) {
			$ids = array_diff( $ids, (array) $_GET['exclude'] );
		}

		$included = ! empty( $_GET['include'] ) ? array_map( 'absint', (array) $_GET['include'] ) : array();
		$url      = wp_parse_url( wp_get_referer() );
		parse_str( $url['query'], $url_query );

		if ( ! empty( $url_query['post'] ) ) {

			$post_id = absint( $url_query['post'] );

		}

		if ( $post_id ) {
			/**
			 * Variable definition
			 *
			 * @var PurchaseOrder $po
			 */
			$po = Helpers::get_atum_order_model( $post_id, FALSE );

			// The Purchase Orders only should allow products from the current PO's supplier (if such PO only allows 1 supplier).
			if ( $po instanceof PurchaseOrder && ! $po->has_multiple_suppliers() ) {

				$supplier_products = apply_filters( 'atum/ajax/search_products/included_search_products', Suppliers::get_supplier_products( $po->get_supplier( 'id' ), [ 'product', 'product_variation' ], FALSE ) );

				// If the PO supplier has no linked products, it must return an empty array.
				if ( empty( $supplier_products ) ) {
					$ids = $included = array();
				}
				else {
					$included = array_merge( $included, $supplier_products );
				}

			}

		}

		if ( ! empty( $included ) ) {
			$ids = array_intersect( $ids, $included );
		}

		/*if ( ! empty( $_GET['limit'] ) ) {
			$ids = array_slice( $ids, 0, absint( $_GET['limit'] ) );
		}*/

		$product_objects = array_filter( array_map( 'wc_get_product', $ids ), 'wc_products_array_filter_editable' );
		$products        = array();

		foreach ( $product_objects as $product_object ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Product $product_object
			 */
			$products[ $product_object->get_id() ] = rawurldecode( wp_strip_all_tags( $product_object->get_formatted_name() ) );
		}

		wp_send_json( apply_filters( 'atum/ajax/search_products/json_search_found_products', $products ) );

	}

}
