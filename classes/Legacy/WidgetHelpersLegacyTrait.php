<?php
/**
 * Legacy trait for Widget Helpers
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


trait WidgetHelpersLegacyTrait {

	/**
	 * Get the current stock levels
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	public static function get_stock_levels_legacy() {

		global $wpdb;

		$stock_counters = array(
			'count_in_stock'  => 0,
			'count_out_stock' => 0,
			'count_low_stock' => 0,
			'count_all'       => 0,
			'count_unmanaged' => 0,
		);

		$products = Helpers::get_all_products();

		if ( ! empty( $products ) ) {

			$show_unmanaged_counter      = 'yes' === Helpers::get_option( 'unmanaged_counters' );
			$stock_counters['count_all'] = count( $products );

			$variations = self::get_children_legacy( 'variable', 'product_variation' );

			// Add the Variations to the posts list.
			if ( ! empty( $variations ) ) {
				// The Variable products are just containers and don't count for the list views.
				$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
			}

			$group_items = self::get_children_legacy( 'grouped' );

			// Add the Group Items to the posts list.
			if ( ! empty( $group_items ) ) {
				// The Grouped products are just containers and don't count for the list views.
				$stock_counters['count_all'] += ( count( $group_items ) - count( self::$grouped_products ) );

			}

			// WC Subscriptions compatibility.
			$subscription_variations = [];
			if ( class_exists( '\WC_Subscriptions' ) ) {

				$subscription_variations = self::get_children_legacy( 'variable-subscription', 'product_variation' );

				// Add the Variations to the posts list.
				if ( $subscription_variations ) {
					// The Variable products are just containers and don't count for the list views.
					$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
				}

			}

			$post_types = ( ! empty( $variations ) || ! empty( $subscription_variations ) ) ? [ 'product', 'product_variation' ] : [ 'product' ];

			/*
			 * Unmanaged products
			 */
			if ( $show_unmanaged_counter ) {

				$products_unmanaged_status = Helpers::get_unmanaged_products( $post_types, TRUE );

				$stock_counters['count_in_stock'] += count( array_filter( $products_unmanaged_status, function ( $row ) {
					return 'instock' === $row[1];
				} ) );

				$stock_counters['count_out_stock'] += count( array_filter( $products_unmanaged_status, function ( $row ) {
					return 'outofstock' === $row[1];
				} ) );

			}
			else {
				$products_unmanaged_status = Helpers::get_unmanaged_products( $post_types, FALSE );
			}

			$products_unmanaged                = array_column( $products_unmanaged_status, 0 );
			$stock_counters['count_unmanaged'] = count( $products_unmanaged );

			$product_statuses = current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ];

			/*
			 * Products In Stock
			 */
			// TODO: WHAT ABOUT PRODUCTS WITH MI OR PRODUCTS WITH CALCULATED STOCK?
			$args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'post_status'    => $product_statuses,
				'fields'         => 'ids',
				// Exclude variable and grouped products.
				'tax_query' => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => [ 'variable', 'variable-subscription', 'grouped' ],
						'operator' => 'NOT IN'
					),
				),
				// Exclude unmanaged products.
				'meta_query'     => array(
					array(
						'key'   => '_manage_stock',
						'value' => 'yes',
					),
				),
			);

			self::$atum_query_data['where'][] = apply_filters( 'atum/dashboard/get_stock_levels/in_stock_products_atum_args', array(
				'key'   => 'atum_stock_status',
				'value' => [ 'instock', 'onbackorder' ],
				'type'  => 'CHAR',
			) );

			add_filter( 'posts_clauses', array( __CLASS__, 'atum_product_data_query_clauses' ) );

			$products_in_stock                 = new \WP_Query( apply_filters( 'atum/dashboard/get_stock_levels/in_stock_products_args', $args ) );
			$products_in_stock                 = $products_in_stock->posts;
			$stock_counters['count_in_stock'] += count( $products_in_stock );
			self::$atum_query_data             = array(); // Empty the ATUM query data to not conflict with next queries.

			/*
			 * Products Out of Stock
			 */
			self::$atum_query_data['where'][] = apply_filters( 'atum/dashboard/get_stock_levels/out_stock_products_atum_args', array(
				'key'   => 'atum_stock_status',
				'value' => 'outofstock',
				'type'  => 'CHAR',
			) );

			$products_out_stock                 = new \WP_Query( apply_filters( 'atum/dashboard/get_stock_levels/out_stock_products_args', $args ) );
			$products_out_stock                 = $products_out_stock->posts;
			$stock_counters['count_out_stock'] += count( $products_out_stock );
			self::$atum_query_data              = array(); // Empty the ATUM query data to not conflict with next queries.

			// ATUM query clauses not needed anymore.
			remove_filter( 'posts_clauses', array( __CLASS__, 'atum_product_data_query_clauses' ) );

			/*
			 * Products with low stock
			 */
			if ( ! empty( $products_in_stock ) ) {

				$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
				$str_sql = apply_filters( 'atum/dashboard/get_stock_levels/low_stock_products', "
					SELECT product_id FROM $atum_product_data_table WHERE low_stock = 1
				" );

				$products_low_stock = $wpdb->get_col( $str_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$stock_counters['count_low_stock'] = count( $products_low_stock );

			}

		}

		return $stock_counters;

	}

	/**
	 * Get all the available children products of the published parent products (Variable and Grouped)
	 *
	 * @since 1.4.0
	 *
	 * @param string $parent_type   The parent product type.
	 * @param string $post_type     Optional. The children post type.
	 *
	 * @return array|bool
	 */
	private static function get_children_legacy( $parent_type, $post_type = 'product' ) {

		global $wpdb;

		// Get the published parents first.
		$products_visibility = current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ];
		$parent_product_type = get_term_by( 'slug', $parent_type, 'product_type' );

		if ( ! $parent_product_type ) {
			return FALSE;
		}

		// Let adding extra parent types externally.
		$parent_product_type_ids = apply_filters( 'atum/dashboard/get_children/parent_product_types', [ $parent_product_type->term_taxonomy_id ], $parent_type );

		$parents_sql = "
			SELECT DISTINCT p.ID FROM $wpdb->posts p
			LEFT JOIN $wpdb->term_relationships tr ON (p.ID = tr.object_id) 
			WHERE tr.term_taxonomy_id IN (" . implode( ',', $parent_product_type_ids ) . ") AND p.post_type = 'product' 
			AND p.post_status IN ('" . implode( "','", $products_visibility ) . "') 
			GROUP BY p.ID		 
		";

		$parents = $wpdb->get_col( $parents_sql );

		if ( ! empty( $parents ) ) {

			// Save them to be used when counting products.
			if ( $parent_type === 'variable' ) {
				self::$variable_products = array_merge( self::$variable_products, array_map( 'absint', $parents ) );
			}
			else {
				self::$grouped_products = array_merge( self::$grouped_products, array_map( 'absint', $parents ) );
			}

			$children_sql = $wpdb->prepare("
				SELECT p.ID FROM $wpdb->posts p
				WHERE p.post_parent IN (
					$parents_sql
				) AND p.post_type = %s AND p.post_status IN ('" . implode( "','", $products_visibility ) . "')
			", $post_type );

			$children = $wpdb->get_col( $children_sql );

			if ( ! empty( $children ) ) {
				return array_map( 'absint', $children );
			}

		}

		return FALSE;

	}
	
	/**
	 * Get all the available children products of the published parent products (Variable and Grouped)
	 *
	 * @since 1.5.1
	 *
	 * @param string $category
	 * @param string $product_type
	 *
	 * @return array
	 */
	private static function get_items_in_stock_legacy( $category, $product_type ) {
		
		// Init values counter.
		$counters = [
			'items_stocks_counter'         => 0,
			'items_purchase_price_total'   => 0,
			'items_without_purchase_price' => 0,
		];
		
		$products = Helpers::get_all_products();
		
		$variations = $filtered_variations = [];
		
		foreach ( Globals::get_inheritable_product_types() as $inheritable_product_type ) {
			
			if ( 'grouped' === $inheritable_product_type && 'grouped' === $product_type ) {
				$filtered_variations = self::get_children_legacy( 'grouped' );
			}
			elseif ( 'grouped' !== $inheritable_product_type ) {
				
				$current_variations = self::get_children_legacy( $inheritable_product_type, 'product_variation' );
				
				if ( $inheritable_product_type === $product_type ) {
					$filtered_variations = $current_variations;
				}
				
				if ( $current_variations ) {
					$variations = array_unique( array_merge( $variations, $current_variations ) );
				}
				
			}
			
		}
		
		// Add the Variations to the posts list. We skip grouped products.
		if ( $variations ) {
			$products = array_unique( array_merge( $products, $variations ) );
		}

		$args = array();

		if ( $products ) {
			
			$post_types = $variations ? [ 'product', 'product_variation' ] : [ 'product' ];
			
			$args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
				'fields'         => 'ids',
				'post__in'       => $products,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_manage_stock',
						'value' => 'yes',
					),
				),
				'tax_query'      => array(
					'relation' => 'AND',
				),
			);
			
			// Check if category filter data exist.
			if ( $category ) {
				array_push( $args['tax_query'], array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => array( $category ),
				) );
			}
			
			// Check if product type filter data exist.
			if ( $product_type ) {
				
				if ( in_array( $product_type, Globals::get_inheritable_product_types() ) && 'bundle' !== $product_type ) {

					if ( $filtered_variations ) {
						$args['post__in'] = $filtered_variations;
					}
					else {
						return $counters;
					}

				}
				elseif ( 'downloadable' === $product_type ) {

					array_push( $args['meta_query'], array(
						'key'     => '_downloadable',
						'value'   => 'yes',
						'compare' => '=',
					) );

				}
				elseif ( 'virtual' === $product_type ) {

					array_push( $args['meta_query'], array(
						'key'     => '_virtual',
						'value'   => 'yes',
						'compare' => '=',
					) );

				}
				else {

					array_push( $args['tax_query'], array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => array( $product_type ),
					) );

				}

			}
			
		}
		
		// Get products.
		$products_in_stock = new \WP_Query( apply_filters( 'atum/dashboard/current_stock_value_widget/in_stock_products_args', $args ) );
		
		// Get current stock values.
		foreach ( $products_in_stock->posts as $product_id ) {

			$product = Helpers::get_atum_product( $product_id );

			if ( ! apply_filters( 'atum/dashboard/current_stock_value_widget/allowed_product', TRUE, $product ) ) {
				continue;
			}

			$product_stock          = (float) $product->get_stock_quantity();
			$product_purchase_price = (float) $product->get_purchase_price();
			
			if ( $product_stock > 0 ) {

				$counters['items_stocks_counter'] += $product_stock;

				if ( $product_purchase_price && ! empty( $product_purchase_price ) ) {
					$counters['items_purchase_price_total'] += ( $product_purchase_price * $product_stock );
				}
				else {
					$counters['items_without_purchase_price'] += $product_stock;
				}

			}

		}
		
		return apply_filters( 'atum/dashboard/current_stock_value_widget/counters', $counters );
		
	}

}
