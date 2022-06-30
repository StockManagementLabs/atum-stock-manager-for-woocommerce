<?php
/**
 * Legacy trait for Widget Helpers
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
			'count_in_stock'       => 0,
			'count_out_stock'      => 0,
			'count_restock_status' => 0,
			'count_all'            => 0,
			'count_unmanaged'      => 0,
		);

		$products = Helpers::get_all_products();

		if ( ! empty( $products ) ) {

			$show_unmanaged_counter      = 'yes' === Helpers::get_option( 'unmanaged_counters' );
			$stock_counters['count_all'] = count( $products );

			$variations = self::get_children( 'variable', 'product_variation' );

			// Add the Variations to the posts list.
			if ( ! empty( $variations ) ) {
				// The Variable products are just containers and don't count for the list views.
				$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
			}

			// TODO: WE ARE EXCLUDING GROUPED PRODUCTS FOR NOW AS THEIR CHILDREN ARE NOT BEING CALCULATED CORRECTLY.
			/*$group_items = self::get_children_legacy( 'grouped' );

			// Add the Group Items to the posts list.
			if ( ! empty( $group_items ) ) {
				// The Grouped products are just containers and don't count for the list views.
				$stock_counters['count_all'] += ( count( $group_items ) - count( self::$grouped_products ) );

			}*/

			// WC Subscriptions compatibility.
			$subscription_variations = [];
			if ( class_exists( '\WC_Subscriptions' ) ) {

				$subscription_variations = self::get_children( 'variable-subscription', 'product_variation' );

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

			$product_statuses = Globals::get_queryable_product_statuses();

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
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => [ 'variable', 'variable-subscription', 'grouped' ],
						'operator' => 'NOT IN',
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
			 * Products in restock status
			 */
			if ( ! empty( $products_in_stock ) ) {

				$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
				$str_sql                 = apply_filters( 'atum/dashboard/get_stock_levels/restock_status_products', "
					SELECT product_id FROM $atum_product_data_table WHERE restock_status = 1
				" );

				$products_restock_status                = $wpdb->get_col( $str_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$stock_counters['count_restock_status'] = count( $products_restock_status );

			}

		}

		return $stock_counters;

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

		global $wpdb;
		$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		// Init values counter.
		$counters = [
			'items_stocks_counter'         => 0,
			'items_purchase_price_total'   => 0,
			'items_without_purchase_price' => 0,
		];

		self::$atum_query_data = array(); // Reset value.

		$args = array(
			'post_type'      => [ 'product', 'product_variation' ],
			'posts_per_page' => - 1,
			'post_status'    => Globals::get_queryable_product_statuses(),
			'fields'         => 'ids',
			'tax_query'      => array(
				'relation' => 'AND',
				// Exclude the grouped products.
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => [ 'grouped', 'bundle', 'composite' ],
					'operator' => 'NOT IN',
				),
			),
			'meta_query'     => array(
				// Exclude unmanaged products.
				// TODO: DO WE NEED TO EXCLUDE UNMANAGED PRODUCTS?
				/*array(
					'key'   => '_manage_stock',
					'value' => 'yes',
				),*/
			),
		);

		// As when we filter by any taxonomy, the variation products are lost,
		// we need to create another query to get the children.
		$children_query_needed = FALSE;

		// Check if category filter exists.
		if ( $category ) {

			array_push( $args['tax_query'], array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $category,
			) );

			$children_query_needed = TRUE;

		}

		// Check if product type filter exists.
		if ( $product_type ) {

			if ( 'downloadable' === $product_type ) {

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
					'terms'    => $product_type,
				) );

				$children_query_needed = TRUE;

			}

		}

		// We need to apply the stock_status criteria to query only if we aren't filtering. Otherwise may exclude unmanaged parents and get no results.
		if ( ! $children_query_needed ) {
			self::$atum_query_data['where'][] = apply_filters( 'atum/dashboard/get_items_in_stock/in_stock_products_atum_args', array(
				'key'   => 'atum_stock_status',
				'value' => [ 'instock', 'onbackorder' ],
				'type'  => 'CHAR',
			) );
		}
		
		// Get products in stock.
		add_filter( 'posts_clauses', array( __CLASS__, 'atum_product_data_query_clauses' ) );
		$products_in_stock_query = new \WP_Query( apply_filters( 'atum/dashboard/get_items_in_stock/in_stock_products_args', $args ) );
		remove_filter( 'posts_clauses', array( __CLASS__, 'atum_product_data_query_clauses' ) );

		$products_in_stock_sql = $products_in_stock_query->request;

		if ( $children_query_needed ) {

			$products_in_stock_sql = "SELECT DISTINCT pist.ID FROM ( ( $products_in_stock_sql ) UNION ( SELECT p.ID FROM $wpdb->posts p
				LEFT JOIN $atum_product_data_table apd ON (p.ID = apd.product_id)
				WHERE apd.atum_stock_status IN ('instock', 'onbackorder') AND p.post_type = 'product_variation'
				AND p.post_parent IN ( $products_in_stock_sql ) ) ) pist ";
		}

		$variation_children = "SELECT pch1.ID FROM $wpdb->posts pch1
            WHERE pch1.post_parent = p0.ID AND pch1.post_type = 'product_variation'";
		$unmanaged_children = "SELECT pch2.ID FROM $wpdb->posts pch2
            LEFT JOIN $wpdb->postmeta pmchild2 ON pch2.ID = pmchild2.post_id AND pmchild2.meta_key = '_manage_stock'
            WHERE pch2.post_parent = p0.ID AND pmchild2.meta_value = 'no'";
		$children_with_pp   = "SELECT pch3.ID FROM $wpdb->posts pch3
			LEFT JOIN $atum_product_data_table apd3 ON pch3.ID = apd3.product_id
            WHERE pch3.post_parent = p0.ID AND apd3.purchase_price IS NOT NULL AND apd3.purchase_price > 0";

		$base_stock_field                   = apply_filters( 'atum/dashboard/stock_field', "IF( ms.meta_value = 'yes', CAST( st.meta_value AS DECIMAL(10,6) ), 0 )" );
		$stock_field                        = "IF( NOT EXISTS($variation_children) OR EXISTS($unmanaged_children), IF( $base_stock_field > 0, $base_stock_field, 0 ), 0 )";
		$stock_wo_purchase_price_field      = 'IF( ' . apply_filters( 'atum/dashboard/purchase_price_field', 'apd0.purchase_price > 0' ) . ", 0, $base_stock_field )";
		$stock_without_purchase_price_field = "IF( NOT EXISTS($children_with_pp) AND (NOT EXISTS($variation_children) OR EXISTS($unmanaged_children)), $stock_wo_purchase_price_field, 0 )";

		$items_in_stock_sql = apply_filters( 'atum/dashboard/current_stock_query_parts', array(
			'fields' => array(
				'ID'                           => 'p0.ID',
				'items_stocks_counter'         => "IFNULL( SUM( $stock_field ), 0 ) items_stocks_counter",
				'items_purchase_price_total'   => 'IFNULL( SUM( ' . apply_filters( 'atum/dashboard/purchase_price_total', "apd0.purchase_price * $stock_field" ) . ' ), 0 ) items_purchase_price_total',
				'items_without_purchase_price' => "IFNULL( SUM( $stock_without_purchase_price_field ), 0 ) items_without_purchase_price",
			),
			'join'   => array(
				"LEFT JOIN $wpdb->postmeta st ON p0.ID = st.post_id AND st.meta_key = '_stock'",
				"LEFT JOIN $wpdb->postmeta ms ON p0.ID = ms.post_id AND ms.meta_key = '_manage_stock'",
				"LEFT JOIN $atum_product_data_table apd0 ON p0.ID = apd0.product_id",
			),
			'where'  => "p0.ID IN ( $products_in_stock_sql )",
		) );

		$sql_string = 'SELECT ' . implode( ",\n", $items_in_stock_sql['fields'] )
			. "\n\nFROM $wpdb->posts p0 " . implode( "\n\t", $items_in_stock_sql['join'] )
			. "\n\nWHERE " . $items_in_stock_sql['where']
			. "\n\nGROUP BY " . apply_filters( 'atum/dashboard/current_stock_query_group_by', 'p0.ID' );

		// phpcs:disable WordPress.DB.PreparedSQL
		$counters = (array) $wpdb->get_row( "SELECT SUM( t.items_stocks_counter ) items_stocks_counter,
            SUM( t.items_purchase_price_total ) items_purchase_price_total,
            SUM( t.items_without_purchase_price ) items_without_purchase_price
			FROM ( $sql_string ) t" );

		return self::format_counters_items_in_stock( $counters );

		/*
		$products_in_stock = $products_in_stock_query->posts;
		$managed_variables = $managed_variables_stock = [];

		if ( ! empty( $products_in_stock ) ) {

			if ( $children_query_needed ) {

				//global $wpdb;
				$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

				// phpcs:disable WordPress.DB.PreparedSQL
				$children_in_stock = $wpdb->get_col( "
					SELECT p.ID FROM $wpdb->posts p
					LEFT JOIN $atum_product_data_table apd ON (p.ID = apd.product_id)
					WHERE apd.atum_stock_status IN ('instock', 'onbackorder') AND p.post_type = 'product_variation'
					AND p.post_parent IN (" . $products_in_stock_query->request . ');
				' );
				// phpcs:enable

				$products_in_stock = array_unique( array_merge( $products_in_stock, $children_in_stock ) );

			}

			// Get current stock values.
			foreach ( $products_in_stock as $product_id ) {

				$product = Helpers::get_atum_product( $product_id );

				if ( ! apply_filters( 'atum/dashboard/get_items_in_stock/allowed_product', TRUE, $product ) ) {
					continue;
				}

				if ( in_array( $product->get_type(), [ 'grouped', 'bundle' ] ) ) {
					continue;
				}

				// Inheritable products stock is only 'real stock' if any of their children is unmanaged.
				if ( $product->get_stock_quantity() &&
					in_array( $product->get_type(), Globals::get_inheritable_product_types() ) ) {
					$product_stock = 0;
					$children      = $product->get_children();

					foreach ( $children as $child_id ) {
						$child = Helpers::get_atum_product( $child_id );

						if ( 'parent' === $child->managing_stock() ) {
							$product_stock = $product->get_stock_quantity();
							break;
						}
					}
				}
				else {
					$product_stock = $product->get_stock_quantity();
				}

				$product_stock          = (float) apply_filters( 'atum/dashboard/get_items_in_stock/product_stock', $product_stock, $product );
				$product_purchase_price = (float) apply_filters( 'atum/dashboard/get_items_in_stock/product_price', $product->get_purchase_price(), $product );

				if ( $product_stock > 0 ) {

					if ( TRUE === $product->managing_stock() ) {

						$counters['items_stocks_counter'] += $product_stock;

						if ( $product_purchase_price && ! empty( $product_purchase_price ) ) {
							$counters['items_purchase_price_total'] += ( $product_purchase_price * $product_stock );
						}
						else {
							$counters['items_without_purchase_price'] += $product_stock;
						}
					}
					// It's a variation and returns its parent stock.
					elseif ( 'variation' === $product->get_type() ) {

						$parent_id = $product->get_parent_id();

						if ( $parent_id ) {

							$product = Helpers::get_atum_product( $parent_id );

							if ( $product instanceof \WC_Product && $product->managing_stock() ) {

								if ( ! array_key_exists( $parent_id, $managed_variables ) ) {

									$managed_variables[ $parent_id ] = [];

								}
								$managed_variables[ $parent_id ][ $product_id ] = $product_purchase_price;

								if ( empty( $managed_variables_stock[ $parent_id ] ) ) {
									$managed_variables_stock[ $parent_id ] = $product_stock;
								}

							}
						}

					}

				}

			}

			if ( ! empty( $managed_variables ) ) {

				foreach ( $managed_variables as $parent_id => $variations ) {

					if ( ! empty( $managed_variables_stock[ $parent_id ] ) ) {

						$variations_pp = 0;
						foreach ( $variations as $purchase_price ) {
							$variations_pp += $purchase_price;
						}

						$counters['items_purchase_price_total'] += ( $variations_pp / count( $variations ) * $managed_variables_stock[ $parent_id ] );

					}
				}
			}

		}
		
		return self::format_counters_items_in_stock( apply_filters( 'atum/dashboard/get_items_in_stock/counters', $counters ) );
		*/
		
	}

}
