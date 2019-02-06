<?php
/**
 * Legacy trait for Widget Helpers
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

use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Settings\Settings;


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

		$show_unmanaged_counter      = 'yes' === Helpers::get_option( 'unmanaged_counters' );
		$products                    = Helpers::get_all_products();
		$stock_counters['count_all'] = count( $products );

		$variations = self::get_children_legacy( 'variable', 'product_variation' );

		// Add the Variations to the posts list.
		if ( $variations ) {
			// The Variable products are just containers and don't count for the list views.
			$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
			$products                     = array_unique( array_merge( array_diff( $products, self::$variable_products ), $variations ) );
		}

		$group_items = self::get_children_legacy( 'grouped' );

		// Add the Group Items to the posts list.
		if ( $group_items ) {
			// The Grouped products are just containers and don't count for the list views.
			$stock_counters['count_all'] += ( count( $group_items ) - count( self::$grouped_products ) );
			$products                     = array_unique( array_merge( array_diff( $products, self::$grouped_products ), $group_items ) );

		}
		
		// WC Subscriptions compatibility.
		$subscription_variations = [];
		if ( class_exists( '\WC_Subscriptions' ) ) {
			
			$subscription_variations = self::get_children_legacy( 'variable-subscription', 'product_variation' );
			
			// Add the Variations to the posts list.
			if ( $subscription_variations ) {
				// The Variable products are just containers and don't count for the list views.
				$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
				$products                     = array_unique( array_merge( array_diff( $products, self::$variable_products ), $subscription_variations ) );
			}
			
		}

		if ( $products ) {

			$post_types = $variations || $subscription_variations ? [ 'product', 'product_variation' ] : [ 'product' ];

			/*
			 * Unmanaged products
			 */
			if ( $show_unmanaged_counter ) {
				$products_unmanaged_status = Helpers::get_unmanaged_products( $post_types, TRUE );

				$stock_counters['count_in_stock'] += count( array_filter( $products_unmanaged_status, function ( $row ) {

					return ( 'instock' === $row[1] );
				} ) );

				$stock_counters['count_out_stock'] += count( array_filter( $products_unmanaged_status, function ( $row ) {

					return ( 'outofstock' === $row[1] );
				} ) );
			}
			else {
				$products_unmanaged_status = Helpers::get_unmanaged_products( $post_types, FALSE );
			}

			$products_unmanaged                = array_column( $products_unmanaged_status, 0 );
			$stock_counters['count_unmanaged'] = count( $products_unmanaged );

			// Remove the unmanaged from the products list.
			if ( ! empty( $products_unmanaged ) && ! empty( $products ) ) {
				$matching = array_intersect( $products, $products_unmanaged );

				if ( ! empty( $matching ) ) {
					$products = array_diff( $products, $matching );
				}
			}

			/*
			 * Products In Stock
			 */
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
					array(
						'key'     => '_stock',
						'value'   => 0,
						'type'    => 'numeric',
						'compare' => '>',
					),
				),
			);

			$products_in_stock                 = new \WP_Query( apply_filters( 'atum/dashboard_widgets/stock_counters/in_stock', $args ) );
			$products_in_stock                 = $products_in_stock->posts;
			$stock_counters['count_in_stock'] += count( $products_in_stock );

			/*
			 * Products Out of Stock
			 */
			$products_not_stock = array_diff( $products, $products_in_stock, $products_unmanaged );
			$args               = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => '_stock',
							'value'   => 0,
							'type'    => 'numeric',
							'compare' => '<=',
						),
						array(
							'key'     => '_stock',
							'compare' => 'NOT EXISTS',
						),
					),
					array(
						'relation' => 'OR',
						array(
							'key'   => '_backorders',
							'value' => 'no',
							'type'  => 'char',
						),
						array(
							'key'     => '_backorders',
							'compare' => 'NOT EXISTS',
						),
					),

				),
				'post__in'       => $products_not_stock,
			);

			$products_out_stock                 = new \WP_Query( apply_filters( 'atum/dashboard_widgets/stock_counters/out_stock', $args ) );
			$products_out_stock                 = $products_out_stock->posts;
			$stock_counters['count_out_stock'] += count( $products_out_stock );

			/*
			 * Products with low stock
			 */
			if ( ! empty( $products_in_stock ) ) {

				$days_to_reorder = absint( Helpers::get_option( 'sale_days', Settings::DEFAULT_SALE_DAYS ) );

				// Compare last seven days average sales per day * re-order days with current stock.
				$str_sales = "
					(SELECT	(
						SELECT MAX(CAST( meta_value AS SIGNED )) AS q 
						FROM {$wpdb->prefix}woocommerce_order_itemmeta 
						WHERE meta_key IN ('_product_id', '_variation_id') 
						AND order_item_id = item.order_item_id
					) AS IDs,
				    CEIL(SUM((
				        SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta 
				        WHERE meta_key = '_qty' AND order_item_id = item.order_item_id))/7*$days_to_reorder
			        ) AS qty
					FROM $wpdb->posts AS orders
				    INNER JOIN {$wpdb->prefix}woocommerce_order_items AS item ON (orders.ID = item.order_id)
					INNER JOIN $wpdb->postmeta AS order_meta ON (orders.ID = order_meta.post_id)
					WHERE (orders.post_type = 'shop_order'
				    AND orders.post_status IN ('wc-completed', 'wc-processing') AND item.order_item_type ='line_item'
				    AND order_meta.meta_key = '_paid_date'
				    AND order_meta.meta_value >= '" . Helpers::date_format( '-7 days' ) . "')
					GROUP BY IDs) AS sales";

				$str_statuses = "
					(SELECT p.ID, IF( 
						CAST( IFNULL(sales.qty, 0) AS DECIMAL(10,2) ) <= 
						CAST( IF( LENGTH({$wpdb->postmeta}.meta_value) = 0 , 0, {$wpdb->postmeta}.meta_value) AS DECIMAL(10,2) ), TRUE, FALSE
					) AS status
					FROM $wpdb->posts AS p
				    LEFT JOIN {$wpdb->postmeta} ON (p.ID = {$wpdb->postmeta}.post_id)
				    LEFT JOIN " . $str_sales . " ON (p.ID = sales.IDs)
					WHERE {$wpdb->postmeta}.meta_key = '_stock'
		            AND p.post_type IN ('" . implode( "','", $post_types ) . "')
		            AND p.ID IN (" . implode( ',', $products_in_stock ) . ' )
		            ) AS statuses';

				$str_sql = apply_filters( 'atum/dashboard_widgets/stock_counters/low_stock', "SELECT ID FROM $str_statuses WHERE status IS FALSE;" );

				$products_low_stock                = $wpdb->get_results( $str_sql ); // WPCS: unprepared SQL ok.
				$products_low_stock                = wp_list_pluck( $products_low_stock, 'ID' );
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

		// Get the published Variables first.
		$parent_args = (array) apply_filters( 'atum/dashboard_widgets/get_children/parent_args', array(
			'post_type'      => 'product',
			'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
			'posts_per_page' => - 1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $parent_type,
				),
			),
		) );

		$parents = new \WP_Query( $parent_args );

		if ( $parents->found_posts ) {

			// Save them to be used when preparing the list query.
			if ( 'variable' === $parent_type ) {
				self::$variable_products = array_merge( self::$variable_products, $parents->posts );
			}
			else {
				self::$grouped_products = array_merge( self::$grouped_products, $parents->posts );
			}

			$children_args = (array) apply_filters( 'atum/dashboard_widgets/get_children/children_args', array(
				'post_type'       => $post_type,
				'post_status'     => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
				'posts_per_page'  => - 1,
				'fields'          => 'ids',
				'post_parent__in' => $parents->posts,
			) );

			$children = new \WP_Query( $children_args );

			if ( $children->found_posts ) {
				return $children->posts;
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
			'items_stocks_counter'          => 0,
			'items_purcharse_price_total'   => 0,
			'items_without_purcharse_price' => 0,
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
		
		// WC Subscriptions compatibility.
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
		$products_in_stock = new \WP_Query( apply_filters( 'atum/dashboard_widgets/current_stock_counters/in_stock', $args ) );
		
		// Get current stock values.
		foreach ( $products_in_stock->posts as $product_id ) {
			$product                 = Helpers::get_atum_product( $product_id );
			$product_stock           = (float) $product->get_stock_quantity();
			$product_purcharse_price = (float) $product->get_purchase_price();
			
			if ( $product_stock && $product_stock > 0 ) {
				$counters['items_stocks_counter'] += $product_stock;
				if ( $product_purcharse_price && ! empty( $product_purcharse_price ) ) {
					$counters['items_purcharse_price_total'] += ( $product_purcharse_price * $product_stock );
				}
				else {
					$counters['items_without_purcharse_price'] += $product_stock;
				}
			}
		}
		
		return apply_filters( 'atum/dashboard_widgets/current_stock_counters/counters', $counters, $products_in_stock->posts );
		
	}

}
