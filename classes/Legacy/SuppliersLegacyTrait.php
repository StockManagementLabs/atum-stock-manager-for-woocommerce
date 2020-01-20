<?php
/**
 * Legacy trait for Suppliers
 *
 * @package         Atum\Legacy
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2020 Stock Management Labs™
 *
 * @deprecated      This legacy class is only here for backwards compatibility and will be removed in a future version.
 *
 * @since           1.5.0
 */

namespace Atum\Legacy;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;

trait SuppliersLegacyTrait {
	
	/**
	 * Store current supplier id to allow getting it from the where clause
	 *
	 * @var int
	 */
	private static $current_supplier_id;

	/**
	 * Get all the products linked to the specified supplier
	 *
	 * @since 1.5.0
	 *
	 * @param int          $supplier_id  The supplier ID.
	 * @param array|string $post_type    Optional. The product post types to get.
	 * @param bool         $type_filter  Optional. Whether to filter the retrieved suppliers by product type or not.
	 *
	 * @return array|bool
	 *
	 * TODO: 1.5.
	 */
	public static function get_supplier_products_legacy( $supplier_id, $post_type = [ 'product', 'product_variation' ], $type_filter = TRUE ) {

		global $wpdb;

		$supplier = get_post( $supplier_id );

		if ( $supplier && self::POST_TYPE === $supplier->post_type ) {
			
			$atum_data_table           = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
			self::$current_supplier_id = $supplier_id;
			
			$args = array(
				'post_type'      => $post_type,
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => - 1,
				'fields'         => 'ids',
			);
			
			$term_join = $term_where = '';
			
			if ( $type_filter ) {

				// SC parents default taxonomies and ready to override to MC (or others) requirements.
				$product_taxonomies = apply_filters( 'atum/suppliers/supplier_product_types', Globals::get_product_types() );
				$term_ids           = Helpers::get_term_ids_by_slug( $product_taxonomies, $taxonomy = 'product_type' );

				$args['tax_query'] = array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'product_type',
						'field'    => 'id',
						'terms'    => $term_ids,
					),
				);

				$term_join  = " LEFT JOIN $wpdb->term_relationships tr ON (p.ID = tr.object_id) ";
				$term_where = ' AND tr.term_taxonomy_id IN (' . implode( ',', $term_ids ) . ') ';

			}
			
			add_filter( 'posts_join', array( __CLASS__, 'supplier_join' ), 10 );
			add_filter( 'posts_where', array( __CLASS__, 'supplier_where' ), 10, 2 );
			// Father IDs.
			$query    = new \WP_Query( apply_filters( 'atum/suppliers/supplier_products_args', $args ) );
			$products = $query->posts;
			remove_filter( 'posts_join', array( __CLASS__, 'supplier_join' ), 10 );
			remove_filter( 'posts_where', array( __CLASS__, 'supplier_where' ), 10 );

			if ( $type_filter ) {

				$child_ids = array();

				// Get rebel parents (rebel childs doesn't have term_relationships.term_taxonomy_id).
				// phpcs:disable WordPress.DB.PreparedSQL
				$query_parents = $wpdb->prepare( "
					SELECT DISTINCT p.ID FROM $wpdb->posts p
	                $term_join
	                WHERE p.post_type = 'product'
	                $term_where
	                AND p.post_status IN ('publish', 'private')              
	                AND p.ID IN (
	                
	                    SELECT DISTINCT sp.post_parent FROM $wpdb->posts sp
	                    INNER JOIN $atum_data_table AS apd ON (sp.ID = apd.product_id)
	                    WHERE sp.post_type = 'product_variation'
	                    AND apd.supplier_id = %d
	                    AND sp.post_status IN ('publish', 'private')
	                      
	                )", $supplier_id );
				// phpcs:enable

				$parent_ids = $wpdb->get_col( $query_parents ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( ! empty( $parent_ids ) ) {

					// Get rebel childs.
					// phpcs:disable WordPress.DB.PreparedSQL
					$query_childs = $wpdb->prepare( "
		                SELECT DISTINCT p.ID FROM $wpdb->posts p
		                INNER JOIN $atum_data_table AS apd ON (p.ID = apd.product_id)
		                WHERE p.post_type = 'product_variation'
		                AND apd.supplier_id = %d
		                AND p.post_parent IN ( " . implode( ',', $parent_ids ) . " )
		                AND p.post_status IN ('publish', 'private')
	                ", $supplier_id );
					// phpcs:enable

					$child_ids = $wpdb->get_col( $query_childs ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				}

				$products = array_unique( array_merge( $products, $parent_ids, $child_ids ) );

			}

			return apply_filters( 'atum/suppliers/products', $products, $supplier, $post_type, $type_filter );

		}

		return FALSE;

	}
	
	/**
	 * Add Atum Data Table to the wp_query join clause
	 *
	 * @since 1.5.0
	 *
	 * @param string $join
	 *
	 * @return string
	 */
	public static function supplier_join( $join ) {
		
		global $wpdb;
		
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		
		$join .= " INNER JOIN $atum_data_table apd ON ($wpdb->posts.ID = apd.product_id) ";
		
		return $join;
	}
	
	/**
	 * Add Atum Data Table to the wp_query join clause
	 *
	 * @since 1.5.0
	 *
	 * @param string    $where
	 * @param \WP_Query $wp_query
	 *
	 * @return string
	 */
	public static function supplier_where( $where, $wp_query ) {
		
		$where .= sprintf( ' AND (apd.supplier_id = %d) ', self::$current_supplier_id );
		
		return $where;
	}
}
