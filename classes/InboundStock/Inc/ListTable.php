<?php
/**
 * @package         Atum\InboundStock
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.3.0
 */

namespace Atum\InboundStock\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumListTables\AtumListTable;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Models\AtumOrderItemModel;
use Atum\Inc\Globals;
use Atum\PurchaseOrders\PurchaseOrders;


class ListTable extends AtumListTable {

	/**
	 * @inheritdoc
	 */
	public function __construct( $args = array() ) {

		$this->taxonomies[] = array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => Globals::get_product_types()
		);

		// NAMING CONVENTION: The column names starting by underscore (_) are based on meta keys (the name must match the meta key name),
		// the column names starting with "calc_" are calculated fields and the rest are WP's standard fields
		// *** Following this convention is necessary for column sorting functionality ***
		$args['table_columns'] = array(
			'thumb'                => '<span class="wc-image tips" data-toggle="tooltip" data-placement="bottom" title="' . __( 'Image', ATUM_TEXT_DOMAIN ) . '">' . __( 'Thumb', ATUM_TEXT_DOMAIN ) . '</span>',
			'title'                => __( 'Product Name', ATUM_TEXT_DOMAIN ),
			'_sku'                 => __( 'SKU', ATUM_TEXT_DOMAIN ),
			'ID'                   => __( 'ID', ATUM_TEXT_DOMAIN ),
			'calc_type'            => '<span class="wc-type tips" data-toggle="tooltip" data-placement="bottom" title="' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '">' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '</span>',
			'calc_inbound'         => __( 'Inbound Stock', ATUM_TEXT_DOMAIN ),
			'calc_date_ordered'    => __( 'Date Ordered', ATUM_TEXT_DOMAIN ),
			'calc_date_expected'   => __( 'Date Expected', ATUM_TEXT_DOMAIN ),
			'calc_purchase_order'  => __( 'PO', ATUM_TEXT_DOMAIN)
		);

		// Initialize totalizers
		$this->totalizers = apply_filters( 'atum/inbound_stock_list/totalizers', array(
			'calc_inbound' => 0
		));

		parent::__construct( $args );
		
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.4.2
	 */
	protected function get_views() {

		$views = parent::get_views();
		unset($views['in_stock'], $views['low_stock'], $views['out_stock'], $views['unmanaged']);

		return $views;
	}

	/**
	 * @inheritdoc
	 */
	protected function table_nav_filters() {
		// Disable filters
	}

	/**
	 * @inheritdoc
	 *
	 * @since  1.1.3.1
	 */
	protected function get_table_classes() {

		$table_classes = parent::get_table_classes();
		$table_classes[] = 'inbound-stock-list';

		return $table_classes;
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.4.2
	 */
	protected function set_views_data( $args = array() ) {

		$this->count_views = array(
			'count_in_stock'  => 0,
			'count_out_stock' => 0,
			'count_low_stock' => 0,
			'count_unmanaged' => 0
		);

	}

	/**
	 * @inheritdoc
	 */
	protected function get_sortable_columns() {

		$sortable_columns = parent::get_sortable_columns();

		// Disable SKU sortable
		if ( isset( $sortable_columns['_sku'] ) ) {
			unset( $sortable_columns['_sku'] );
		}

		$sortable_columns['calc_purchase_order'] = array('PO', FALSE);
		return apply_filters( 'atum/inbound_stock_list/sortable_columns', $sortable_columns );

	}

	/**
	 * @inheritdoc
	 */
	protected function column_title( $item ) {

		$product_id = $this->get_current_product_id();

		if ( $this->product->get_type() == 'variation' ) {

			$parent_data = $this->product->get_parent_data();
			$title = $parent_data['title'];

			$attributes = wc_get_product_variation_attributes($product_id);
			if ( ! empty($attributes) ) {
				$title .= ' - ' . ucfirst( implode(' - ', $attributes) );
			}

			// Get the variable product ID to get the right link
			$product_id = $this->product->get_parent_id();

		}
		else {
			$title = $this->product->get_title();
		}

		$title_length = absint( apply_filters( 'atum/inbound_stock_list/column_title_length', 20 ) );

		if ( mb_strlen( $title ) > $title_length ) {
			$title = '<span class="tips" data-toggle="tooltip" title="' . $title . '">' . trim( mb_substr( $title, 0, $title_length ) ) .
			         '...</span><span class="atum-title-small">' . $title . '</span>';
		}

		$title = '<a href="' . get_edit_post_link($product_id) . '" target="_blank">' . $title . '</a>';

		return apply_filters( 'atum/inbound_stock_list/column_title', $title, $item, $this->product );
	}

	/**
	 * @inheritdoc
	 */
	protected function column__sku( $item, $editable = FALSE ) {
		return parent::column__sku( $item, $editable );
	}

	/**
	 * @inheritdoc
	 */
	protected function column_calc_type( $item ) {

		$type = $this->product->get_type();
		$product_types = wc_get_product_types();

		switch ( $type ) {
			case 'variation':

				$type = 'variable';
				$product_tip = __( 'Variation Product', ATUM_TEXT_DOMAIN );
				break;

			case 'variable':
			case 'grouped':

				$product_tip = $product_types[$type];
				break;

			default:
				return parent::column_calc_type($item);
		}

		return apply_filters( 'atum/inbound_stock_list/column_type', '<span class="product-type tips ' . $type . '" data-toggle="tooltip" title="' . $product_tip . '"></span>', $item, $this->product );

	}

	/**
	 * Column for inbound stock
	 *
	 * @since 1.3.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return string
	 */
	protected function column_calc_inbound( $item ) {

		// Get the quantity for the ATUM Order Item
		$qty = AtumOrderItemModel::get_item_meta($item->po_item_id, '_qty');
		$this->increase_total('calc_inbound', $qty);

		return apply_filters( 'atum/inbound_stock_list/column_inbound_stock', $qty, $item, $this->product );

	}

	/**
	 * Column for date ordered
	 *
	 * @since  1.3.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return string
	 */
	protected function column_calc_date_ordered( $item ) {

		$date_ordered = get_post_meta($item->po_id, '_date_created', TRUE);
		return apply_filters( 'atum/inbound_stock_list/column_date_ordered', $date_ordered, $item, $this->product );
	}

	/**
	 * Column for date expected
	 *
	 * @since  1.3.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return string
	 */
	protected function column_calc_date_expected( $item ) {

		$date_expected = get_post_meta($item->po_id, '_expected_at_location_date', TRUE);
		return apply_filters( 'atum/inbound_stock_list/column_date_expected', $date_expected, $item, $this->product );
	}

	/**
	 * Column for purchase order
	 *
	 * @since 1.3.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return string
	 */
	protected function column_calc_purchase_order( $item ) {

		$po_link = '<a href="' . get_edit_post_link( $item->po_id ) . '" target="_blank">' . $item->po_id . '</a>';
		return apply_filters( 'atum/inbound_stock_list/column_purchase_order', $po_link, $item, $this->product );

	}

	/**
	 * @inheritdoc
	 */
	public function prepare_items() {

		global $wpdb;

		$search_query = '';
		if ( ! empty( $_REQUEST['s'] ) ) {

			$search = esc_attr( $_REQUEST['s'] );

			if ( is_numeric($search) ) {
				$search_query .= 'AND `meta_value` = ' . absint( $_REQUEST['s'] );
			}
			else {
				$search_query .= "AND `order_item_name` LIKE '%{$_REQUEST['s']}%'";
			}

		}

		$order_by = 'ORDER BY `order_id`';
		if ( ! empty(  $_REQUEST['orderby'] ) ) {

			switch ( $_REQUEST['orderby'] ) {
				case 'title':

					$order_by = 'ORDER BY `order_item_name`';
			        break;

				case 'ID':

					$order_by = 'ORDER BY oi.`order_item_id`';
					break;


			}

		}

		$order = ( ! empty( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], ['asc', 'desc']) ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';

		$sql = $wpdb->prepare("
			SELECT MAX(CAST( `meta_value` AS SIGNED )) AS product_id, oi.`order_item_id`, `order_id`, `order_item_name` 			
			FROM `{$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` AS oi 
			LEFT JOIN `{$wpdb->atum_order_itemmeta}` AS oim ON oi.`order_item_id` = oim.`order_item_id`
			LEFT JOIN `{$wpdb->posts}` AS p ON oi.`order_id` = p.`ID`
			WHERE `meta_key` IN ('_product_id', '_variation_id') AND `order_item_type` = 'line_item' 
			AND p.`post_type` = %s AND `meta_value` > 0 AND `post_status` = 'atum_pending'
			$search_query
			GROUP BY oi.`order_item_id`
			$order_by $order;",
			PurchaseOrders::POST_TYPE
		);

		$po_products = $wpdb->get_results($sql);

		if ( ! empty($po_products) ) {

			$found_posts = count($po_products);

			// Paginate the results (if needed)
			if ($this->per_page != -1 && $found_posts > $this->per_page) {
				$page   = $this->get_pagenum();
				$offset = ( $page - 1 ) * $this->per_page;

				$po_products = array_slice($po_products, $offset, $this->per_page);
			}

			foreach ($po_products as $po_product) {
				$post = get_post($po_product->product_id);

				if ( $post ) {
					$post->po_id = $po_product->order_id;
					$post->po_item_id = $po_product->order_item_id;
				}

				$this->items[] = $post;

			}

			$this->set_views_data();
			$this->count_views['count_all'] = $found_posts;

			$this->set_pagination_args( array(
				'total_items' => $found_posts,
				'per_page'    => $this->per_page,
				'total_pages' => $this->per_page == - 1 ? 0 : ceil( $found_posts / $this->per_page )
			) );

		}

	}

	/**
	 * @inheritdoc
	 */
	public function single_row( $item ) {

		$this->product = wc_get_product( $item );
		$this->allow_calcs = TRUE;

		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';

		// Reset the child value
		$this->is_child = FALSE;

	}

	/**
	 * @inheritdoc
	 */
	protected function get_bulk_actions() {

		// No bulk actions needed for Inbound Stock
		return apply_filters( 'atum/inbound_stock_list/bulk_actions', array() );
	}
	
}