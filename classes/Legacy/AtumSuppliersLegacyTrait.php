<?php
/**
 * Legacy trait for Suppliers
 *
 * @package         Atum\Legacy
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @deprecated      This legacy class is only here for backwards compatibility and will be removed in a future version.
 *
 * @since           1.5.0
 */

namespace Atum\Legacy;

defined( 'ABSPATH' ) || die;


trait AtumSuppliersLegacyTrait {

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

		if ( self::POST_TYPE === $supplier->post_type ) {

			$args = array(
				'post_type'      => $post_type,
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => self::SUPPLIER_META_KEY,
						'value' => $supplier_id,
					),
				),
			);

			$term_join = $term_where = '';

			if ( $type_filter ) {

				// SC fathers default taxonomies and ready to override to MC (or others) requirements.
				$product_taxonomies = apply_filters( 'atum/suppliers/supplier_products_taxonomies', Globals::get_product_types() );
				$term_ids           = Helpers::get_term_ids_by_slug( $product_taxonomies, $taxonomy = 'product_type' );

				$args['tax_query'] = array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'product_type',
						'field'    => 'id',
						'terms'    => $term_ids,
					),
				);

				$term_join  = "LEFT JOIN $wpdb->term_relationships tr ON (p.ID = tr.object_id)";
				$term_where = 'AND tr.term_taxonomy_id IN (' . implode( ',', $term_ids ) . ')';

			}

			// Father IDs.
			$products = get_posts( apply_filters( 'atum/suppliers/supplier_products_args', $args ) );

			if ( $type_filter ) {

				$child_ids = array();

				// Get rebel parents (rebel childs doesn't have term_relationships.term_taxonomy_id).
				$query_parents = $wpdb->prepare( "
					SELECT DISTINCT p.ID FROM $wpdb->posts p
	                $term_join
	                WHERE p.post_type = 'product'
	                $term_where
	                AND p.post_status IN ('publish', 'private')              
	                AND p.ID IN (
	                
	                    SELECT DISTINCT sp.post_parent FROM $wpdb->posts sp
	                    INNER JOIN $wpdb->postmeta AS mt1 ON (sp.ID = mt1.post_id)
	                    WHERE sp.post_type = 'product_variation'
	                    AND (mt1.meta_key = '" . self::SUPPLIER_META_KEY . "' AND CAST(mt1.meta_value AS SIGNED) = %d)
	                    AND sp.post_status IN ('publish', 'private')
	                      
	                )", $supplier_id ); // WPCS: unprepared SQL ok.

				$parent_ids = $wpdb->get_col( $query_parents ); // WPCS: unprepared SQL ok.

				if ( ! empty( $parent_ids ) ) {
					// Get rebel childs.
					$query_childs = $wpdb->prepare( "
		                SELECT DISTINCT p.ID FROM $wpdb->posts p
		                INNER JOIN $wpdb->postmeta AS mt1 ON (p.ID = mt1.post_id)
		                WHERE p.post_type = 'product_variation'
		                AND (mt1.meta_key = '" . self::SUPPLIER_META_KEY . "' AND CAST(mt1.meta_value AS SIGNED) = %d)
		                AND p.post_parent IN ( " . implode( ',', $parent_ids ) . " )
		                AND p.post_status IN ('publish', 'private')
	                ", $supplier_id ); // WPCS: unprepared SQL ok.

					$child_ids = $wpdb->get_col( $query_childs ); // WPCS: unprepared SQL ok.
				}

				$products = array_unique( array_merge( $products, $parent_ids, $child_ids ) );

			}

			return apply_filters( 'atum/suppliers/products', $products, $supplier, $post_type, $type_filter );

		}

		return FALSE;

	}

}
