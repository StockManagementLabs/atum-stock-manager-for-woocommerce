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

trait AtumListTableLegacyTrait {

	/**
	 * Prepare the table data (legacy method)
	 *
	 * @since 0.0.1
	 *
	 * TODO: 1.5.0.
	 */
	public function prepare_items_legacy() {

		/**
		 * Define our column headers
		 */
		$columns             = $this->get_columns();
		$products            = array();
		$sortable            = $this->get_sortable_columns();
		$hidden              = get_hidden_columns( $this->screen );
		$this->group_columns = $this->calc_groups( $this->group_members, $hidden );

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
		if ( $this->show_controlled ) {

			$args['meta_query'] = array(
				array(
					'key'   => Globals::ATUM_CONTROL_STOCK_KEY,
					'value' => 'yes',
				),
			);

		}
		else {

			$args['meta_query'] = array(
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
					array(
						'key'   => Globals::IS_INHERITABLE_KEY,
						'value' => 'yes',
					),
				),
			);

		}

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
		 * Supplier filter
		 */
		if ( ! empty( $_REQUEST['supplier'] ) && AtumCapabilities::current_user_can( 'read_supplier' ) ) {

			$supplier = absint( $_REQUEST['supplier'] );

			if ( ! empty( $args['meta_query'] ) ) {
				$args['meta_query']['relation'] = 'AND';
			}

			$args['meta_query'][] = array(
				'key'   => Suppliers::SUPPLIER_META_KEY,
				'value' => $supplier,
				'type'  => 'numeric',
			);

			// This query does not get product variations and as each variation may have a distinct supplier,
			// we have to get them separately and to add their variables to the results.
			$this->supplier_variation_products = Suppliers::get_supplier_products( $supplier, 'product_variation' );

			if ( ! empty( $this->supplier_variation_products ) ) {
				add_filter( 'atum/list_table/views_data_products', array( $this, 'add_supplier_variables_to_query' ) );
				add_filter( 'atum/list_table/items', array( $this, 'add_supplier_variables_to_query' ) );
				add_filter( 'atum/list_table/views_data_variations', array( $this, 'add_supplier_variations_to_query' ), 10, 2 );
			}

		}

		/**
		 * Extra meta args
		 */
		if ( ! empty( $this->extra_meta ) ) {
			$args['meta_query'][] = $this->extra_meta;
		}

		/**
		 * Sorting
		 */
		if ( ! empty( $_REQUEST['orderby'] ) ) {

			$args['order'] = ( isset( $_REQUEST['order'] ) && 'asc' === $_REQUEST['order'] ) ? 'ASC' : 'DESC';

			// Columns starting by underscore are based in meta keys, so can be sorted.
			if ( '_' === substr( $_REQUEST['orderby'], 0, 1 ) ) {

				// All the meta key based columns are numeric except the SKU.
				if ( '_sku' === $_REQUEST['orderby'] ) {
					$args['orderby'] = 'meta_value';
				}
				else {
					$args['orderby'] = 'meta_value_num';
				}

				$args['meta_key'] = $_REQUEST['orderby'];

			}
			// Standard Fields.
			else {
				$args['orderby'] = $_REQUEST['orderby'];
			}

		}
		else {
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
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

		// Let others play.
		$args = apply_filters( 'atum/list_table/prepare_items/args', $args );

		// Build "Views Filters" and calculate totals.
		$this->set_views_data( $args );

		$allow_query = TRUE;

		/**
		 * REQUIRED. Register our pagination options & calculations
		 */
		$found_posts = isset( $this->count_views['count_all'] ) ? $this->count_views['count_all'] : 0;

		if ( ! empty( $_REQUEST['view'] ) ) {

			$view        = esc_attr( $_REQUEST['view'] );
			$allow_query = FALSE;

			foreach ( $this->id_views as $key => $post_ids ) {

				if ( $view === $key && ! empty( $post_ids ) ) {

					$get_parents = FALSE;
					foreach ( Globals::get_inheritable_product_types() as $inheritable_product_type ) {

						if ( ! empty( $this->container_products[ $inheritable_product_type ] ) ) {
							$get_parents = TRUE;
							break;
						}

					}

					// Add the parent products again to the query.
					$args['post__in'] = $get_parents ? $this->get_parents( $post_ids ) : $post_ids;
					$allow_query      = TRUE;
					$found_posts      = $this->count_views[ "count_$key" ];

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
			$wp_query = new \WP_Query( $args );

			$products    = $wp_query->posts;
			$product_ids = wp_list_pluck( $products, 'ID' );

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
		$this->items = apply_filters( 'atum/list_table/items', $products );

		$this->set_pagination_args( array(
			'total_items' => $found_posts,
			'per_page'    => $this->per_page,
			'total_pages' => $total_pages,
			'orderby'     => ! empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'date',
			'order'       => ! empty( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'desc',
		) );

	}

}
