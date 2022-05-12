<?php
/**
 * Legacy trait for Helpers
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

trait HelpersLegacyTrait {

	/**
	 * Get an array of products that are not managed by WC
	 *
	 * @since 1.4.1
	 *
	 * @param array $post_types
	 * @param bool  $get_stock_status   Whether to get also the WC stock_status of the unmanaged products.
	 *
	 * @return array
	 */
	public static function get_unmanaged_products_legacy( $post_types, $get_stock_status = FALSE ) {

		global $wpdb;

		$unmng_fields    = [ 'posts.ID' ];
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$unmng_join      = [ "LEFT JOIN $atum_data_table AS apd ON (posts.ID = apd.product_id)" ];

		// Use the lookup tables when possible to improve the performance.
		if ( ! empty( $wpdb->wc_product_meta_lookup ) ) {
			$unmng_join[] = "LEFT JOIN $wpdb->wc_product_meta_lookup AS pml ON (posts.ID = pml.product_id)";
		}
		else {
			$unmng_join[] = "LEFT JOIN $wpdb->postmeta AS mt1 ON (posts.ID = mt1.post_id AND mt1.meta_key = '_manage_stock')";
		}

		$post_statuses = Globals::get_queryable_product_statuses();

		// TODO: Change the query to remove the subquery and get the values with joins.
		if ( $get_stock_status ) {

			if ( ! empty( $wpdb->wc_product_meta_lookup ) ) {
				$unmng_fields[] = 'pml.stock_status';
			}
			else {
				$unmng_fields[] = "(SELECT meta_value FROM $wpdb->postmeta WHERE post_id = posts.ID AND meta_key = '_stock_status' ) AS stock_status";
			}

		}

		$unmng_join = (array) apply_filters( 'atum/get_unmanaged_products_legacy/join_query', $unmng_join );

		$unmng_where = array(
			"WHERE posts.post_type IN ('" . implode( "','", $post_types ) . "')",
			"AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "')",
			'AND apd.inheritable != 1', // Exclude the inheritable products from query (as are just containers in ATUM List Tables).
		);

		if ( ! empty( $wpdb->wc_product_meta_lookup ) ) {
			$unmng_where[] = 'AND pml.stock_quantity IS NULL';
		}
		else {
			$unmng_where[] = "AND (mt1.post_id IS NULL OR mt1.meta_value = 'no')";
		}

		$unmng_where = apply_filters( 'atum/get_unmanaged_products_legacy/where_query', $unmng_where );

		$sql = 'SELECT DISTINCT ' . implode( ',', $unmng_fields ) . "\n FROM $wpdb->posts posts \n" . implode( "\n", $unmng_join ) . "\n" . implode( "\n", $unmng_where );

		return $wpdb->get_results( $sql, ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	}

}
