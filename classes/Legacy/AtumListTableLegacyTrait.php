<?php
/**
 * Legacy trait for Atum List Table component
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

use Atum\Inc\Helpers;


trait AtumListTableLegacyTrait {

	/**
	 * Set views for table filtering and calculate total value counters for pagination
	 *
	 * @since 0.0.2
	 *
	 * @param array $args WP_Query arguments.
	 */
	protected function set_views_data_legacy( $args ) {

		global $wpdb;

		if ( $this->show_unmanaged_counters ) {

			$this->id_views = array_merge( $this->id_views, array(
				'managed'        => [],
				'unm_in_stock'   => [],
				'unm_out_stock'  => [],
				'unm_back_order' => [],
				'all_in_stock'   => [],
				'all_out_stock'  => [],
				'all_back_order' => [],
			) );

			$this->count_views = array_merge( $this->count_views, array(
				'count_managed'        => 0,
				'count_unm_in_stock'   => 0,
				'count_unm_out_stock'  => 0,
				'count_unm_back_order' => 0,
				'count_all_in_stock'   => 0,
				'count_all_out_stock'  => 0,
				'count_all_back_order' => 0,
			) );

		}

		// Get all the IDs in the two queries with no pagination.
		$args['fields']         = 'ids';
		$args['posts_per_page'] = - 1;
		unset( $args['paged'] );

		// TODO: PERHAPS THE TRANSIENT CAN BE USED MORE GENERACALLY TO AVOID REPETITIVE WORK.
		$all_transient = Helpers::get_transient_identifier( $args, 'list_table_all' );
		$products      = Helpers::get_transient( $all_transient );

		if ( ! $products ) {

			global $wp_query;

			// Pass through the ATUM query data filter.
			add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
			$wp_query = new \WP_Query( apply_filters( 'atum/list_table/set_views_data/all_args', $args ) );
			remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );

			$products = $wp_query->posts;

			// Save it as a transient to improve the performance.
			Helpers::set_transient( $all_transient, $products );

		}

		// Let others play here.
		$products = apply_filters( 'atum/list_table/views_data_products', $products );

		$this->count_views['count_all'] = count( $products );

		if ( $this->is_filtering && empty( $products ) ) {
			return;
		}

		// If it's a search or a product filtering, include only the filtered items to search for children.
		$post_in = $this->is_filtering ? $products : array();

		foreach ( $this->taxonomies as $index => $taxonomy ) {

			if ( 'product_type' === $taxonomy['taxonomy'] ) {

				if ( in_array( 'variable', (array) $taxonomy['terms'] ) ) {

					$variations = apply_filters( 'atum/list_table/views_data_variations', $this->get_children_legacy( 'variable', $post_in, 'product_variation' ), $post_in );

					// Remove the variable containers from the array and add the variations.
					$products = array_unique( array_merge( array_diff( $products, $this->container_products['all_variable'] ), $variations ) );

				}

				if ( in_array( 'grouped', (array) $taxonomy['terms'] ) ) {

					$group_items = apply_filters( 'atum/list_table/views_data_grouped', $this->get_children_legacy( 'grouped', $post_in ), $post_in );

					// Remove the grouped containers from the array and add the group items.
					$products = array_unique( array_merge( array_diff( $products, $this->container_products['all_grouped'] ), $group_items ) );

				}

				// WC Subscriptions compatibility.
				if ( class_exists( '\WC_Subscriptions' ) && in_array( 'variable-subscription', (array) $taxonomy['terms'] ) ) {

					$sc_variations = apply_filters( 'atum/list_table/views_data_sc_variations', $this->get_children_legacy( 'variable-subscription', $post_in, 'product_variation' ), $post_in );

					// Remove the variable subscription containers from the array and add the subscription variations.
					$products = array_unique( array_merge( array_diff( $products, $this->container_products['all_variable_subscription'] ), $sc_variations ) );

				}

				// Re-count the resulting products.
				$this->count_views['count_all'] = count( $products );

				// The grouped items must count once per group they belongs to and once individually.
				if ( ! empty( $group_items ) ) {
					$this->count_views['count_all'] += count( $group_items );
				}

				do_action( 'atum/list_table/after_children_count', $taxonomy['terms'], $this );

				break;
			}

		}

		// For the Uncontrolled items, we don't need to calculate stock totals.
		if ( ! $this->show_controlled ) {
			return;
		}

		if ( $products ) {

			$post_types = ( ! empty( $variations ) || ! empty( $sc_variations ) ) ? [ $this->post_type, 'product_variation' ] : [ $this->post_type ];

			/*
			 * Unmanaged products
			 */
			if ( $this->show_unmanaged_counters ) {

				$products_unmanaged        = array();
				$products_unmanaged_status = Helpers::get_unmanaged_products( $post_types, TRUE );

				if ( ! empty( $products_unmanaged_status ) ) {

					// Filter the unmanaged (also removes uncontrolled).
					$products_unmanaged_status = array_filter( $products_unmanaged_status, function ( $row ) use ( $products ) {
						return in_array( $row[0], $products );
					} );

					$this->id_views['unm_in_stock'] = array_column( array_filter( $products_unmanaged_status, function ( $row ) {
						return 'instock' === $row[1];
					} ), 0 );

					$this->count_views['count_unm_in_stock'] = count( $this->id_views['unm_in_stock'] );

					$this->id_views['unm_out_stock'] = array_column( array_filter( $products_unmanaged_status, function ( $row ) {
						return 'outofstock' === $row[1];
					} ), 0 );

					$this->count_views['count_unm_out_stock'] = count( $this->id_views['unm_out_stock'] );

					$this->id_views['unm_back_order'] = array_column( array_filter( $products_unmanaged_status, function ( $row ) {
						return 'onbackorder' === $row[1];
					} ), 0 );

					$this->count_views['count_unm_back_order'] = count( $this->id_views['unm_back_order'] );

					$products_unmanaged = array_column( $products_unmanaged_status, 0 );

					$this->id_views['managed']          = array_diff( $products, $products_unmanaged );
					$this->count_views['count_managed'] = count( $this->id_views['managed'] );

				}

			}
			else {
				$products_unmanaged = array_column( Helpers::get_unmanaged_products( $post_types ), 0 );
			}

			// Remove the unmanaged from the products list.
			if ( ! empty( $products_unmanaged ) ) {

				// Filter the unmanaged (also removes uncontrolled).
				$products_unmanaged = array_intersect( $products, $products_unmanaged );

				$this->id_views['unmanaged']          = $products_unmanaged;
				$this->count_views['count_unmanaged'] = count( $products_unmanaged );

				if ( ! empty( $products_unmanaged ) ) {
					$products = ! empty( $this->count_views['count_managed'] ) ? $this->id_views['managed'] : array_diff( $products, $products_unmanaged );
				}

			}

			/*
			 * Products in stock
			 */
			$in_stock_args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_stock',
						'value'   => 0,
						'type'    => 'numeric',
						'compare' => '>',
					),
				),
				'post__in'       => $products,
			);

			$in_stock_transient = Helpers::get_transient_identifier( $in_stock_args, 'list_table_in_stock' );
			$products_in_stock  = Helpers::get_transient( $in_stock_transient );

			if ( empty( $products_in_stock ) ) {
				// As this query does not contain ATUM params, doesn't need the filters.
				$products_in_stock = new \WP_Query( apply_filters( 'atum/list_table/set_views_data/in_stock_args', $in_stock_args ) );
				Helpers::set_transient( $in_stock_transient, $products_in_stock );
			}

			$products_in_stock = $products_in_stock->posts;

			$this->id_views['in_stock']          = $products_in_stock;
			$this->count_views['count_in_stock'] = count( $products_in_stock );

			$products_not_stock = array_diff( $products, $products_in_stock, $products_unmanaged );

			/**
			 * Products on Back Order
			 */
			$back_order_args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_stock',
						'value'   => 0,
						'type'    => 'numeric',
						'compare' => '<=',
					),
					array(
						'key'     => '_backorders',
						'value'   => array( 'yes', 'notify' ),
						'type'    => 'char',
						'compare' => 'IN',
					),

				),
				'post__in'       => $products_not_stock,
			);

			$back_order_transient = Helpers::get_transient_identifier( $back_order_args, 'list_table_back_order' );
			$products_back_order  = Helpers::get_transient( $back_order_transient );

			if ( empty( $products_back_order ) ) {
				// As this query does not contain ATUM params, doesn't need the filters.
				$products_back_order = new \WP_Query( apply_filters( 'atum/list_table/set_views_data/back_order_args', $back_order_args ) );
				Helpers::set_transient( $back_order_transient, $products_back_order );
			}

			$products_back_order = $products_back_order->posts;

			$this->id_views['back_order']          = $products_back_order;
			$this->count_views['count_back_order'] = count( $products_back_order );

			// As the Group items might be displayed multiple times, we should count them multiple times too.
			if ( ! empty( $group_items ) && ( empty( $_REQUEST['product_type'] ) || 'grouped' !== $_REQUEST['product_type'] ) ) {
				$this->count_views['count_in_stock']   += count( array_intersect( $group_items, $products_in_stock ) );
				$this->count_views['count_back_order'] += count( array_intersect( $group_items, $products_back_order ) );

			}

			/**
			 * Products with low stock
			 */
			if ( $this->count_views['count_in_stock'] ) {

				$low_stock_transient = Helpers::get_transient_identifier( $args, 'list_table_low_stock' );
				$products_low_stock  = Helpers::get_transient( $low_stock_transient );

				if ( empty( $products_low_stock ) ) {

					// Compare last seven days average sales per day * re-order days with current stock.
					// TODO: 1.5.0.
					$str_sales = "(SELECT			   
					    (SELECT MAX(CAST( meta_value AS SIGNED )) AS q FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key IN('_product_id', '_variation_id') AND order_item_id = `item`.`order_item_id`) AS IDs,
					    CEIL(SUM((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_qty' AND order_item_id = `item`.`order_item_id`))/7*$this->last_days) AS qty
						FROM `{$wpdb->posts}` AS `order`
						    INNER JOIN `{$wpdb->prefix}woocommerce_order_items` AS `item` ON (`order`.`ID` = `item`.`order_id`)
							INNER JOIN `{$wpdb->postmeta}` AS `order_meta` ON (`order`.ID = `order_meta`.`post_id`)
						WHERE (`order`.`post_type` = 'shop_order'
						    AND `order`.`post_status` IN ('wc-completed', 'wc-processing') AND `item`.`order_item_type` ='line_item'
						    AND `order_meta`.`meta_key` = '_paid_date'
						    AND `order_meta`.`meta_value` >= '" . Helpers::date_format( '-7 days' ) . "')
						GROUP BY IDs) AS sales";

					$str_states = "(SELECT `{$wpdb->posts}`.`ID`,
						IF( CAST( IFNULL(`sales`.`qty`, 0) AS DECIMAL(10,2) ) <= 
							CAST( IF( LENGTH(`{$wpdb->postmeta}`.`meta_value`) = 0 , 0, `{$wpdb->postmeta}`.`meta_value`) AS DECIMAL(10,2) ), TRUE, FALSE) AS state
						FROM `{$wpdb->posts}`
						    LEFT JOIN `{$wpdb->postmeta}` ON (`{$wpdb->posts}`.`ID` = `{$wpdb->postmeta}`.`post_id`)
						    LEFT JOIN " . $str_sales . " ON (`{$wpdb->posts}`.`ID` = `sales`.`IDs`)
						WHERE (`{$wpdb->postmeta}`.`meta_key` = '_stock'
				            AND `{$wpdb->posts}`.`post_type` IN ('" . implode( "', '", $post_types ) . "')
				            AND (`{$wpdb->posts}`.`ID` IN (" . implode( ', ', $products_in_stock ) . ')) )) AS states';

					$str_sql = apply_filters( 'atum/list_table/set_views_data/low_stock', "SELECT `ID` FROM $str_states WHERE state IS FALSE;" );

					$products_low_stock = $wpdb->get_results( $str_sql ); // WPCS: unprepared SQL ok.
					$products_low_stock = wp_list_pluck( $products_low_stock, 'ID' );
					Helpers::set_transient( $low_stock_transient, $products_low_stock );

				}

				$this->id_views['low_stock']          = $products_low_stock;
				$this->count_views['count_low_stock'] = count( $products_low_stock );

			}

			/**
			 * Products out of stock
			 */
			$products_out_stock = array_diff( $products_not_stock, $products_back_order );

			$this->id_views['out_stock']          = $products_out_stock;
			$this->count_views['count_out_stock'] = $this->count_views['count_all'] - $this->count_views['count_in_stock'] - $this->count_views['count_back_order'] - $this->count_views['count_unmanaged'];

			if ( $this->show_unmanaged_counters ) {
				/**
				 * Calculate totals
				 */
				$this->id_views['all_in_stock']          = array_merge( $this->id_views['in_stock'], $this->id_views['unm_in_stock'] );
				$this->count_views['count_all_in_stock'] = $this->count_views['count_in_stock'] + $this->count_views['count_unm_in_stock'];

				$this->id_views['all_out_stock']          = array_merge( $this->id_views['out_stock'], $this->id_views['unm_out_stock'] );
				$this->count_views['count_all_out_stock'] = $this->count_views['count_out_stock'] + $this->count_views['count_unm_out_stock'];

				$this->id_views['all_back_order']          = array_merge( $this->id_views['back_order'], $this->id_views['unm_back_order'] );
				$this->count_views['count_all_back_order'] = $this->count_views['count_back_order'] + $this->count_views['count_unm_back_order'];

			}

		}

	}

	/**
	 * Get all the available children products in the system
	 *
	 * @since 1.1.1
	 *
	 * @param string $parent_type   The parent product type.
	 * @param array  $post_in       Optional. If is a search query, get only the children from the filtered products.
	 * @param string $post_type     Optional. The children post type.
	 *
	 * @return array|bool
	 */
	protected function get_children_legacy( $parent_type, $post_in = array(), $post_type = 'product' ) {

		// Get the published Variables first.
		$parent_args = array(
			'post_type'      => 'product',
			'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
			'posts_per_page' => - 1,
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $parent_type,
				),
			),
		);

		if ( ! empty( $post_in ) ) {
			$parent_args['post__in'] = $post_in;
		}

		// As this query does not contain ATUM params, doesn't need the filters.
		$parents = new \WP_Query( apply_filters( 'atum/list_table/get_children/parent_args', $parent_args ) );

		if ( $parents->found_posts ) {

			switch ( $parent_type ) {
				case 'variable':
					$this->container_products['all_variable'] = array_unique( array_merge( $this->container_products['all_variable'], $parents->posts ) );
					break;

				case 'grouped':
					$this->container_products['all_grouped'] = array_unique( array_merge( $this->container_products['all_grouped'], $parents->posts ) );
					break;

				case 'variable-subscription':
					$this->container_products['all_variable_subscription'] = array_unique( array_merge( $this->container_products['all_variable_subscription'], $parents->posts ) );
					break;
			}

			$children_args = array(
				'post_type'       => $post_type,
				'post_status'     => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
				'posts_per_page'  => - 1,
				'post_parent__in' => $parents->posts,
				'orderby'         => 'menu_order',
				'order'           => 'ASC',
			);

			/*
			 * NOTE: we should apply here all the query filters related to individual child products
			 * like the ATUM control switch or the supplier
			 */

			if ( $this->show_controlled ) {

				$children_args['meta_query'] = array(
					array(
						'key'   => Globals::ATUM_CONTROL_STOCK_KEY,
						'value' => 'yes',
					),
				);

			}
			else {

				$children_args['meta_query'] = array(
					array(
						'relation' => 'OR',
						array(
							'key'     => Globals::ATUM_CONTROL_STOCK_KEY,
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'   => Globals::ATUM_CONTROL_STOCK_KEY,
							'value' => 'no',
						),
					),
				);

			}

			if ( ! empty( $this->supplier_variation_products ) ) {

				$children_args['meta_query'][] = array(
					'key'   => Suppliers::SUPPLIER_META_KEY,
					'value' => esc_attr( $_REQUEST['supplier'] ),
					'type'  => 'NUMERIC',
				);

				$children_args['meta_query']['relation'] = 'AND';

			}

			$children = new \WP_Query( apply_filters( 'atum/list_table/get_children/children_args', $children_args ) );

			if ( $children->found_posts ) {

				$parents_with_child = wp_list_pluck( $children->posts, 'post_parent' );

				switch ( $parent_type ) {
					case 'variable':
						$this->container_products['variable'] = array_unique( array_merge( $this->container_products['variable'], $parents_with_child ) );

						// Exclude all those variations with no children from the list.
						$this->excluded = array_unique( array_merge( $this->excluded, array_diff( $this->container_products['all_variable'], $this->container_products['variable'] ) ) );
						break;

					case 'grouped':
						$this->container_products['grouped'] = array_unique( array_merge( $this->container_products['grouped'], $parents_with_child ) );

						// Exclude all those grouped with no children from the list.
						$this->excluded = array_unique( array_merge( $this->excluded, array_diff( $this->container_products['all_grouped'], $this->container_products['grouped'] ) ) );
						break;

					case 'variable-subscription':
						$this->container_products['variable_subscription'] = array_unique( array_merge( $this->container_products['variable_subscription'], $parents_with_child ) );

						// Exclude all those subscription variations with no children from the list.
						$this->excluded = array_unique( array_merge( $this->excluded, array_diff( $this->container_products['all_variable_subscription'], $this->container_products['variable_subscription'] ) ) );
						break;
				}

				$children_ids            = wp_list_pluck( $children->posts, 'ID' );
				$this->children_products = array_merge( $this->children_products, $children_ids );

				return $children_ids;

			}
			else {
				$this->excluded = array_unique( array_merge( $this->excluded, $parents->posts ) );
			}

		}

		return array();

	}

}
