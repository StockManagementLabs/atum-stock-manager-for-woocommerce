<?php
/**
 * Legacy trait for Atum List Table component
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

use Atum\Components\AtumCache;
use Atum\Components\AtumCapabilities;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Suppliers\Suppliers;


trait ListTableLegacyTrait {

	/**
	 * Prepare the table data
	 *
	 * @since 0.0.1
	 */
	public function prepare_items_legacy() {

		/**
		 * Define our column headers
		 */
		$columns             = $this->get_columns();
		$posts               = array();
		$sortable            = $this->get_sortable_columns();
		$hidden              = get_hidden_columns( $this->screen );
		$this->group_columns = $this->calc_groups( $hidden );

		/**
		 * REQUIRED. Build an array to be used by the class for column headers
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
			'posts_per_page' => $this->per_page,
			'paged'          => $this->get_pagenum(),
		);

		/**
		 * Get Controlled or Uncontrolled items
		 */
		$this->set_controlled_query_data();

		/**
		 * Tax filter
		 */

		// Add product category to the tax query.
		if ( ! empty( $_REQUEST['product_cat'] ) ) {

			$this->taxonomies[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => esc_attr( $_REQUEST['product_cat'] ),
			);

		}

		// Change the product type tax query (initialized in constructor) to the current queried type.
		if ( ! empty( $_REQUEST['product_type'] ) ) {

			$type = esc_attr( $_REQUEST['product_type'] );

			foreach ( $this->taxonomies as $index => $taxonomy ) {

				if ( 'product_type' === $taxonomy['taxonomy'] ) {

					if ( in_array( $type, [ 'downloadable', 'virtual' ] ) ) {

						$this->taxonomies[ $index ]['terms'] = 'simple';

						$this->extra_meta = array(
							'key'   => "_$type",
							'value' => 'yes',
						);

					}
					else {
						$this->taxonomies[ $index ]['terms'] = $type;
					}

					break;
				}

			}

		}

		if ( $this->taxonomies ) {
			$args['tax_query'] = (array) apply_filters( 'atum/list_table/taxonomies', $this->taxonomies );
		}

		/**
		 * Extra meta args
		 */
		if ( ! empty( $this->extra_meta ) ) {
			$args['meta_query'][] = $this->extra_meta;
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {

			// Add the orderby args.
			$args = $this->parse_orderby_args( $args );

		}

		/**
		 * Searching
		 */
		if ( ! empty( $_REQUEST['search_column'] ) ) {
			$args['search_column'] = esc_attr( $_REQUEST['search_column'] );
		}

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = esc_attr( $_REQUEST['s'] );
		}

		/**
		 * Supplier filter
		 * NOTE: it's important to run this filter after processing all the rest because we need to pass the $args through.
		 */
		if ( ! empty( $_REQUEST['supplier'] ) && AtumCapabilities::current_user_can( 'read_supplier' ) ) {

			$supplier = absint( $_REQUEST['supplier'] );

			if ( ! empty( $this->atum_query_data['where'] ) ) {
				$this->atum_query_data['where']['relation'] = 'AND';
			}

			$this->atum_query_data['where'][] = apply_filters( 'atum/list_table/supplier_filter_query_data', array(
				'key'   => 'supplier_id',
				'value' => $supplier,
				'type'  => 'NUMERIC',
			));

			// This query does not get product variations and as each variation may have a distinct supplier,
			// we have to get them separately and to add their variables to the results.
			$this->supplier_variation_products = Suppliers::get_supplier_products( $supplier, [ 'product_variation' ], TRUE, $args );

			if ( ! empty( $this->supplier_variation_products ) ) {
				add_filter( 'atum/list_table/views_data_products', array( $this, 'add_supplier_variables_to_query' ), 10, 2 );
				add_filter( 'atum/list_table/items', array( $this, 'add_supplier_variables_to_query' ), 10, 2 );
				add_filter( 'atum/list_table/views_data_variations', array( $this, 'add_supplier_variations_to_query' ), 10, 2 );
			}

		}

		// Let others play.
		$args = apply_filters( 'atum/list_table/prepare_items/args', $args );

		// Build "Views Filters" and calculate totals.
		$this->set_views_data_legacy( $args );

		$allow_query = TRUE;

		/**
		 * REQUIRED. Register our pagination options & calculations
		 */
		$found_posts = isset( $this->count_views['count_all'] ) ? $this->count_views['count_all'] : 0;

		if ( ! empty( $_REQUEST['view'] ) ) {

			$view        = esc_attr( $_REQUEST['view'] );
			$allow_query = FALSE;

			foreach ( $this->id_views as $key => $post_ids ) {

				if ( $view === $key ) {

					$this->supplier_variation_products = array_intersect( $this->supplier_variation_products, $post_ids );

					if ( ! empty( $post_ids ) ) {

						$get_parents = FALSE;
						foreach ( Globals::get_inheritable_product_types() as $inheritable_product_type ) {

							if ( ! empty( $this->container_products[ str_replace( '-', '_', $inheritable_product_type ) ] ) ) {
								$get_parents = TRUE;
								break;
							}

						}

						// Add the parent products again to the query.
						if ( $get_parents ) {

							$parents = $this->get_variation_parents( $post_ids );

							// Exclude the parents with no children.
							// For example: the current list may have the "Out of stock" filter applied and a variable product
							// may have all of its variations in stock, but its own stock could be 0. It shouldn't appear empty.
							foreach ( Globals::get_inheritable_product_types() as $inheritable_product_type ) {

								$inheritable_product_type = str_replace( '-', '_', $inheritable_product_type );

								if ( strpos( $inheritable_product_type, 'variable' ) !== 0 ) {
									continue;
								}

								if ( ! isset( $this->container_products[ $inheritable_product_type ] ) ) {
									continue;
								}

								$empty_variables = array_diff( $this->container_products[ $inheritable_product_type ], $parents );

								foreach ( $empty_variables as $empty_variable ) {
									if ( in_array( $empty_variable, $post_ids ) ) {
										unset( $post_ids[ array_search( $empty_variable, $post_ids ) ] );
									}
								}

							}

							// Get the Grouped parents.
							if ( ! empty( $this->container_products['grouped'] ) ) {

								$grouped_parents = $this->get_grouped_parents( $post_ids );

								$empty_grouped_parents = array_diff( $this->container_products['grouped'], $parents );

								foreach ( $empty_grouped_parents as $empty_grouped ) {
									if ( in_array( $empty_grouped, $post_ids ) ) {
										unset( $post_ids[ array_search( $empty_grouped, $post_ids ) ] );
									}
								}

								$parents = array_merge( $parents, $grouped_parents );

							}

							$args['post__in'] = array_merge( $parents, $post_ids );

						}
						else {
							$args['post__in'] = $post_ids;
						}

						$allow_query = TRUE;
						$found_posts = $this->count_views[ "count_$key" ];
					}

				}

			}
		}

		if ( $allow_query ) {

			if ( ! empty( $this->excluded ) ) {

				if ( isset( $args['post__not_in'] ) ) {
					$args['post__not_in'] = array_merge( $args['post__not_in'], $this->excluded );
				}
				else {
					$args['post__not_in'] = $this->excluded;
				}

			}

			// Setup the WP query.
			global $wp_query;

			// Pass through the ATUM query data filter.
			do_action( 'atum/list_table/before_query_data' );
			add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
			$wp_query = new \WP_Query( $args );
			remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
			do_action( 'atum/list_table/after_query_data' );

			$posts = $wp_query->posts;

			if ( $found_posts > 0 && empty( $posts ) ) {

				$args['paged']     = 1;
				$_REQUEST['paged'] = $args['paged'];

				// Pass through the ATUM query data filter.
				do_action( 'atum/list_table/before_query_data' );
				add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
				$wp_query = new \WP_Query( $args );
				remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
				do_action( 'atum/list_table/after_query_data' );

				$posts = $wp_query->posts;

			}

			$product_ids = wp_list_pluck( $posts, 'ID' );

			$this->current_products = $product_ids;
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$total_pages = ( - 1 == $this->per_page || ! $wp_query->have_posts() ) ? 0 : ceil( $wp_query->found_posts / $this->per_page );

		}
		else {
			$found_posts = $total_pages = 0;
		}

		/**
		 * REQUIRED!!!
		 * Save the sorted data to the items property, where can be used by the rest of the class.
		 */
		$this->items = apply_filters( 'atum/list_table/items', $posts, 'posts' );

		$this->set_pagination_args( array(
			'total_items' => $found_posts,
			'per_page'    => $this->per_page,
			'total_pages' => $total_pages,
			'orderby'     => ! empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'date',
			'order'       => ! empty( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'desc',
		) );

	}

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

		// TODO: PERHAPS THE TRANSIENT CAN BE USED MORE GENERALLY TO AVOID REPETITIVE WORK.
		$all_transient = AtumCache::get_transient_key( 'list_table_all', [ $args, $this->wc_query_data, $this->atum_query_data ] );
		$products      = AtumCache::get_transient( $all_transient );

		if ( ! $products ) {

			global $wp_query;

			// Pass through the ATUM query data filter.
			do_action( 'atum/list_table/set_views_data/before_query_data' );
			add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
			$wp_query = new \WP_Query( apply_filters( 'atum/list_table/set_views_data/all_args', $args ) );
			remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
			do_action( 'atum/list_table/set_views_data/after_query_data' );

			$products = $wp_query->posts;

			// Save it as a transient to improve the performance.
			AtumCache::set_transient( $all_transient, $products );

		}

		// Let others play here.
		$products = (array) apply_filters( 'atum/list_table/views_data_products', $products );

		$this->count_views['count_all'] = count( $products );

		if ( $this->is_filtering && empty( $products ) ) {
			return;
		}

		// If it's a search or a product filtering, include only the filtered items to search for children.
		$post_in = $this->is_filtering ? $products : array();

		foreach ( $this->taxonomies as $index => $taxonomy ) {

			if ( 'product_type' === $taxonomy['taxonomy'] ) {

				if ( in_array( 'variable', (array) $taxonomy['terms'] ) ) {

					$variations = apply_filters( 'atum/list_table/views_data_variations', $this->get_children( 'variable', $post_in, 'product_variation' ), $post_in );

					// Remove the variable containers from the array and add the variations.
					$products = array_unique( array_merge( array_diff( $products, $this->container_products['all_variable'] ), $variations ) );

				}

				if ( in_array( 'grouped', (array) $taxonomy['terms'] ) ) {

					$group_items = apply_filters( 'atum/list_table/views_data_grouped', $this->get_children( 'grouped', $post_in ), $post_in );

					// Remove the grouped containers from the array and add the group items.
					$products = array_unique( array_merge( array_diff( $products, $this->container_products['all_grouped'] ), $group_items ) );

				}

				// WC Subscriptions compatibility.
				if ( class_exists( '\WC_Subscriptions' ) && in_array( 'variable-subscription', (array) $taxonomy['terms'] ) ) {

					$sc_variations = apply_filters( 'atum/list_table/views_data_sc_variations', $this->get_children( 'variable-subscription', $post_in, 'product_variation' ), $post_in );

					// Remove the variable subscription containers from the array and add the subscription variations.
					$products = array_unique( array_merge( array_diff( $products, $this->container_products['all_variable_subscription'] ), $sc_variations ) );

				}

				// WC Product Bundle compatibility.
				if ( class_exists( '\WC_Product_Bundle' ) && in_array( 'bundle', (array) $taxonomy['terms'] ) ) {

					$sc_bundles = apply_filters( 'atum/list_table/views_data_bundle', $this->get_children( 'bundle', $post_in ), $post_in );

					// Remove the bundle containers from the array and add the subscription variations.
					$products = array_unique( array_merge( array_diff( $products, $this->container_products['all_bundle'] ), $sc_bundles ) );

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

					$this->id_views['managed'] = array_diff( $products, $products_unmanaged );
					// Need to substract count unmanaged because group items are not included twice in managed id_views.
					$this->count_views['count_managed'] = $this->count_views['count_all'] - count( $products_unmanaged );

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
			 * Products args.
			 */
			$products_args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'post__in'       => $products,
			);

			$temp_atum_query_data = $this->atum_query_data;

			/*
			 * Products in stock.
			 */
			if ( ! empty( $this->atum_query_data['where'] ) ) {
				$this->atum_query_data['where']['relation'] = 'AND';
			}

			$this->atum_query_data['where'][] = array(
				'key'   => 'atum_stock_status',
				'value' => 'instock',
				'type'  => 'CHAR',
			);

			$in_stock_transient = AtumCache::get_transient_key( 'list_table_in_stock', [ $products_args, $this->wc_query_data, $this->atum_query_data ] );
			$products_in_stock  = AtumCache::get_transient( $in_stock_transient );

			if ( empty( $products_in_stock ) ) {
				add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
				$products_in_stock = new \WP_Query( apply_filters( 'atum/list_table/set_views_data/in_stock_products_args', $products_args ) );
				remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
				AtumCache::set_transient( $in_stock_transient, $products_in_stock );
			}

			$this->atum_query_data = $temp_atum_query_data;

			$products_in_stock = $products_in_stock->posts;

			$this->id_views['in_stock']          = (array) $products_in_stock;
			$this->count_views['count_in_stock'] = is_array( $products_in_stock ) ? count( $products_in_stock ) : 0;

			$products_not_stock = array_diff( (array) $products, (array) $products_in_stock, (array) $products_unmanaged );

			/**
			 * Products on Backorder.
			 */
			$products_args['post__in'] = $products_not_stock;

			if ( ! empty( $this->atum_query_data['where'] ) ) {
				$this->atum_query_data['where']['relation'] = 'AND';
			}

			$this->atum_query_data['where'][] = array(
				'key'   => 'atum_stock_status',
				'value' => 'onbackorder',
				'type'  => 'CHAR',
			);

			$backorders_transient = AtumCache::get_transient_key( 'list_table_backorders', [ $products_args, $this->wc_query_data, $this->atum_query_data ] );
			$products_backorders  = AtumCache::get_transient( $backorders_transient );

			if ( empty( $products_backorders ) && ! empty( $products_not_stock ) ) {
				add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
				$products_backorders = new \WP_Query( apply_filters( 'atum/list_table/set_views_data/back_order_products_args', $products_args ) );
				remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
				$products_backorders = $products_backorders->posts;
				AtumCache::set_transient( $backorders_transient, $products_backorders );
			}

			$this->atum_query_data = $temp_atum_query_data;

			$this->id_views['back_order']          = (array) $products_backorders;
			$this->count_views['count_back_order'] = is_array( $products_backorders ) ? count( $products_backorders ) : 0;

			// As the Group items might be displayed multiple times, we should count them multiple times too.
			if ( ! empty( $group_items ) && ( empty( $_REQUEST['product_type'] ) || 'grouped' !== $_REQUEST['product_type'] ) ) {
				$this->count_views['count_in_stock']   += count( array_intersect( $group_items, (array) $products_in_stock ) );
				$this->count_views['count_back_order'] += count( array_intersect( $group_items, (array) $products_backorders ) );
			}

			/**
			 * Products with low stock
			 */
			if ( ! empty( $products_in_stock ) ) {

				$low_stock_transient = AtumCache::get_transient_key( 'list_table_low_stock', [ $args, $this->wc_query_data, $this->atum_query_data ] );
				$products_low_stock  = AtumCache::get_transient( $low_stock_transient );

				if ( empty( $products_low_stock ) ) {

					$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
					$str_sql                 = apply_filters( 'atum/list_table/set_views_data/low_stock_products', "
						SELECT product_id FROM $atum_product_data_table WHERE low_stock = 1
					" );

					$products_low_stock = $wpdb->get_col( $str_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					AtumCache::set_transient( $low_stock_transient, $products_low_stock );

				}

				$this->id_views['low_stock']          = (array) $products_low_stock;
				$this->count_views['count_low_stock'] = is_array( $products_low_stock ) ? count( $products_low_stock ) : 0;

			}

			/**
			 * Products out of stock
			 */
			$products_out_stock = array_diff( (array) $products_not_stock, (array) $products_backorders );

			$this->id_views['out_stock']          = $products_out_stock;
			$this->count_views['count_out_stock'] = $this->count_views['count_all'] - $this->count_views['count_in_stock'] - $this->count_views['count_back_order'] - $this->count_views['count_unmanaged'];

			/**
			 * Calculate totals
			 */
			if ( $this->show_unmanaged_counters ) {

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

		$post_statuses = current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ];

		// Get the published products of the same type first.
		$parent_args = array(
			'post_type'      => 'product',
			'post_status'    => $post_statuses,
			'posts_per_page' => - 1,
			'fields'         => 'ids',
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => apply_filters( 'atum/list_table/parent_post_type', $parent_type ),
				),
			),
		);

		if ( ! empty( $post_in ) ) {
			$parent_args['post__in'] = $post_in;
		}

		// As this query does not contain ATUM params, doesn't need the filters.
		$parents = new \WP_Query( apply_filters( 'atum/list_table/get_children/parent_args', $parent_args ) );

		$parents_with_child = $grouped_products = $bundle_children = array();

		if ( $parents->found_posts ) {

			switch ( $parent_type ) {
				case 'variable':
					$this->container_products['all_variable'] = array_unique( array_merge( $this->container_products['all_variable'], $parents->posts ) );
					break;

				case 'grouped':
					$this->container_products['all_grouped'] = array_unique( array_merge( $this->container_products['all_grouped'], $parents->posts ) );

					// Get all the children from their corresponding meta key.
					foreach ( $parents->posts as $parent_id ) {
						$children = get_post_meta( $parent_id, '_children', TRUE );

						if ( ! empty( $children ) && is_array( $children ) ) {
							$grouped_products     = array_merge( $grouped_products, $children );
							$parents_with_child[] = $parent_id;
						}
					}

					break;

				// WC Subscriptions compatibility.
				case 'variable-subscription':
					$this->container_products['all_variable_subscription'] = array_unique( array_merge( $this->container_products['all_variable_subscription'], $parents->posts ) );
					break;

				// WC Bundle Producs compatibility.
				case 'bundle':
					$this->container_products['all_bundle'] = array_unique( array_merge( $this->container_products['all_bundle'], $parents->posts ) );

					$bundle_children = Helpers::get_bundle_items( array(
						'return'    => 'id=>product_id',
						'bundle_id' => $parents->posts,
					) );

					foreach ( $parents->posts as $parent_id ) {

						if ( ! empty( $bundle_children ) && is_array( $bundle_children ) ) {
							$parents_with_child[] = $parent_id;
						}
					}
					break;
			}

			$children_args = array(
				'post_type'      => $post_type,
				'post_status'    => $post_statuses,
				'posts_per_page' => - 1,
				'fields'         => 'id=>parent',
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			);

			if ( 'grouped' === $parent_type ) {
				$children_args['post__in'] = $grouped_products;
			}
			else {
				$children_args['post_parent__in'] = $parents->posts;
			}

			// Apply the same order and orderby args than their parent.
			$children_args = $this->parse_orderby_args( $children_args );

			// Sometimes with the general cache for this function is not enough to avoid duplicated queries.
			$cache_key    = AtumCache::get_cache_key( 'get_children_query', $children_args );
			$children_ids = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

			if ( $has_cache ) {
				return $children_ids;
			}

			/*
			 * NOTE: we should apply here all the query filters related to individual child products
			 * like the ATUM control switch or the supplier
			 */
			$this->set_controlled_query_data();

			if ( ! empty( $this->supplier_variation_products ) && ! empty( $this->atum_query_data['where'] ) && ! empty( wp_list_filter( $this->atum_query_data['where'], [ 'key' => 'supplier_id' ] ) ) ) {

				$this->atum_query_data['where'][] = array(
					'key'   => 'supplier_id',
					'value' => absint( $_REQUEST['supplier'] ),
					'type'  => 'NUMERIC',
				);

				$this->atum_query_data['where']['relation'] = 'AND';

			}

			add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
			$children = new \WP_Query( apply_filters( 'atum/list_table/get_children/children_args', $children_args ) );
			remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );

			if ( $children->found_posts ) {

				if ( 'grouped' !== $parent_type ) {
					$parents_with_child = wp_list_pluck( $children->posts, 'post_parent' );
				}


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
				$this->children_products = array_unique( array_merge( $this->children_products, $children_ids ) );
				AtumCache::set_cache( $cache_key, $children_ids );

				return $children_ids;

			}
			elseif ( class_exists( '\WC_Product_Bundle' ) && 'bundle' === $parent_type ) {

				foreach ( $bundle_children as $key => $bundle_child ) {

					$product_child = Helpers::get_atum_product( $bundle_child );

					if ( $product_child ) {
						if ( 'yes' === Helpers::get_atum_control_status( $product_child ) ) {

							if ( ! $this->show_controlled ) {
								unset( $bundle_children[ $key ] );
							}

						}
						elseif ( $this->show_controlled ) {
							unset( $bundle_children[ $key ] );
						}
					}

				}

				if ( empty( $bundle_children ) ) {
					$parents_with_child = [];
				}
				else {

					$bundle_parents = [];
					foreach ( $bundle_children as $bundle_child ) {
						$bundle_parents = array_merge( $bundle_parents, wc_pb_get_bundled_product_map( $bundle_child ) );
					}

					$parents_with_child = $bundle_parents;

				}

				$this->container_products['bundle'] = array_unique( array_merge( $this->container_products['bundle'], $parents_with_child ) );

				// Exclude all those subscription variations with no children from the list.
				$this->excluded = array_unique( array_merge( $this->excluded, array_diff( $this->container_products['all_bundle'], $this->container_products['bundle'] ) ) );

				$this->children_products = array_unique( array_merge( $this->children_products, array_map( 'intval', $bundle_children ) ) );
				AtumCache::set_cache( $cache_key, $bundle_children );

				return $bundle_children;

			}
			else {
				$this->excluded = array_unique( array_merge( $this->excluded, $parents->posts ) );
			}

		}

		return array();

	}

}
