<?php
/**
 * Inbound Stock List
 *
 * @package         Atum\InboundStock
 * @subpackage      Lists
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.3.0
 */

namespace Atum\InboundStock\Lists;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumListTables\AtumListTable;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Models\AtumOrderItemModel;
use Atum\Inc\Helpers;
use Atum\PurchaseOrders\PurchaseOrders;


class ListTable extends AtumListTable {

	/**
	 * The columns hidden by default
	 *
	 * @var array
	 */
	protected static $default_hidden_columns = array( 'ID' );

	/**
	 * Just to center the numeric columns on this table
	 *
	 * @var array
	 */
	protected $searchable_columns = array(
		'numeric' => array(
			'calc_inbound_stock',
		),
	);

	/**
	 * ListTable Constructor
	 *
	 * The child class should call this constructor from its own constructor to override the default $args
	 *
	 * @since 1.3.0
	 *
	 * @param array|string $args          {
	 *      Array or string of arguments.
	 *
	 *      @type bool   $show_cb           Optional. Whether to show the row selector checkbox as first table column.
	 *      @type bool   $show_controlled   Optional. Whether to show items controlled by ATUM or not.
	 *      @type int    $per_page          Optional. The number of posts to show per page (-1 for no pagination).
	 *      @type array  $selected          Optional. The posts selected on the list table.
	 *      @type array  $excluded          Optional. The posts excluded from the list table.
	 * }
	 */
	public function __construct( $args = array() ) {

		// Prevent unmanaged counters.
		$this->show_unmanaged_counters = FALSE;

		// NAMING CONVENTION: The column names starting by underscore (_) are based on meta keys (the name must match the meta key name),
		// the column names starting with "calc_" are calculated fields and the rest are WP's standard fields
		// *** Following this convention is necessary for column sorting functionality ***!
		self::$table_columns = array(
			'thumb'              => '<span class="atum-icon atmi-picture tips" data-bs-placement="bottom" data-tip="' . esc_attr__( 'Image', ATUM_TEXT_DOMAIN ) . '">' . __( 'Thumb', ATUM_TEXT_DOMAIN ) . '</span>',
			'ID'                 => __( 'ID', ATUM_TEXT_DOMAIN ),
			'title'              => __( 'Product Name', ATUM_TEXT_DOMAIN ),
			'calc_type'          => '<span class="atum-icon atmi-tag tips" data-bs-placement="bottom" data-tip="' . esc_attr__( 'Product Type', ATUM_TEXT_DOMAIN ) . '">' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '</span>',
			'_sku'               => __( 'SKU', ATUM_TEXT_DOMAIN ),
			'calc_inbound_stock' => __( 'Inbound Stock', ATUM_TEXT_DOMAIN ),
			'_date_ordered'      => __( 'Date Ordered', ATUM_TEXT_DOMAIN ),
			'_date_expected'     => __( 'Date Expected', ATUM_TEXT_DOMAIN ),
			'_purchase_order'    => __( 'PO', ATUM_TEXT_DOMAIN ),
		);

		// Initialize totalizers.
		$this->totalizers = apply_filters( 'atum/inbound_stock_list/totalizers', array( 'calc_inbound_stock' => 0 ) );

		parent::__construct( $args );

	}

	/**
	 * Get an associative array ( id => link ) with the list of available views on this table.
	 *
	 * @since 1.4.2
	 *
	 * @return array
	 */
	protected function get_views() {

		$views = parent::get_views();
		unset( $views['in_stock'], $views['restock_status'], $views['out_stock'], $views['unmanaged'], $views['back_order'] );

		return $views;
	}

	/**
	 * Extra controls to be displayed in table nav sections
	 *
	 * @since  1.4.2
	 *
	 * @param string $which 'top' or 'bottom' table nav.
	 */
	protected function extra_tablenav( $which ) {
		// Disable table nav.
	}

	/**
	 * Add the filters to the table nav
	 *
	 * @since 1.4.2
	 */
	protected function table_nav_filters() {
		// Disable filters.
	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag. Deleted 'fixed' from standard function
	 *
	 * @since  1.1.3.1
	 *
	 * @return array List of CSS classes for the table tag
	 */
	protected function get_table_classes() {

		$table_classes   = parent::get_table_classes();
		$table_classes[] = 'inbound-stock-list';
		$table_classes[] = 'striped';

		return $table_classes;
	}

	/**
	 * Set views for table filtering and calculate total value counters for pagination
	 *
	 * @since 1.4.2
	 *
	 * @param array $args WP_Query arguments.
	 */
	protected function set_views_data( $args = array() ) {

		$this->count_views = array(
			'count_in_stock'       => 0,
			'count_out_stock'      => 0,
			'count_back_order'     => 0,
			'count_restock_status' => 0,
			'count_unmanaged'      => 0,
		);

	}

	/**
	 * All columns are sortable by default except cb and thumbnail
	 *
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
	 * you will need to register it here. This should return an array where the
	 * key is the column that needs to be sortable, and the value is db column to
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @since 1.4.2
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs' => array('data_values', bool)
	 */
	protected function get_sortable_columns() {

		$sortable_columns = parent::get_sortable_columns();

		// Disable SKU sortable.
		if ( isset( $sortable_columns['_sku'] ) ) {
			unset( $sortable_columns['_sku'] );
		}

		$sortable_columns['calc_purchase_order'] = array( 'PO', FALSE );
		return apply_filters( 'atum/inbound_stock_list/sortable_columns', $sortable_columns );

	}

	/**
	 * Post title column
	 *
	 * @since  1.4.2
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 *
	 * @return string
	 */
	protected function column_title( $item ) {

		$product_id = $this->get_current_list_item_id();

		if ( 'variation' === $this->list_item->get_type() ) {

			$parent_data = $this->list_item->get_parent_data();
			$title       = $parent_data['title'];

			$attributes = wc_get_product_variation_attributes( $product_id );
			if ( ! empty( $attributes ) ) {
				$title .= ' - ' . ucfirst( implode( ' - ', $attributes ) );
			}

			// Get the variable product ID to get the right link.
			$product_id = $this->list_item->get_parent_id();

		}
		else {
			$title = $this->list_item->get_title();
		}

		$title_length = absint( apply_filters( 'atum/inbound_stock_list/column_title_length', 20 ) );

		if ( mb_strlen( $title ) > $title_length ) {
			$title = '<span class="tips" data-tip="' . esc_attr( $title ) . '">' . trim( mb_substr( $title, 0, $title_length ) ) .
			         '...</span><span class="atum-title-small">' . $title . '</span>';
		}

		$title = '<a href="' . get_edit_post_link( $product_id ) . '" target="_blank">' . $title . '</a>';

		return apply_filters( 'atum/inbound_stock_list/column_title', $title, $item, $this->list_item );
	}

	/**
	 * Product SKU column
	 *
	 * @since  1.4.2
	 *
	 * @param \WP_Post $item     The WooCommerce product post.
	 * @param bool     $editable Whether the SKU will be editable.
	 *
	 * @return string
	 */
	protected function column__sku( $item, $editable = FALSE ) {
		return parent::column__sku( $item, $editable );
	}

	/**
	 * Column for product type
	 *
	 * @since 1.4.2
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 *
	 * @return string
	 */
	protected function column_calc_type( $item ) {

		$type          = $this->list_item->get_type();
		$product_types = wc_get_product_types();

		switch ( $type ) {
			case 'variation':
				$type        = 'variable';
				$product_tip = __( 'Variation Product', ATUM_TEXT_DOMAIN );
				break;

			case 'variable':
			case 'grouped':
				$product_tip = $product_types[ $type ];
				break;

			default:
				return parent::column_calc_type( $item );
		}

		$data_tip = ! $this->is_report ? ' data-tip="' . esc_attr( $product_tip ) . '"' : '';

		return apply_filters( 'atum/inbound_stock_list/column_type', '<span class="product-type tips ' . $type . '"' . $data_tip . '></span>', $item, $this->list_item );

	}

	/**
	 * Column for inbound stock quantity
	 *
	 * @since 1.3.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return string
	 */
	protected function column_calc_inbound_stock( $item ) {

		// Get the quantity for the ATUM Order Item.
		$qty = AtumOrderItemModel::get_item_meta( $item->po_item_id, '_qty' );
		$this->increase_total( 'calc_inbound_stock', $qty );

		return apply_filters( 'atum/inbound_stock_list/column_inbound_stock', $qty, $item, $this->list_item );

	}

	/**
	 * Column for date ordered
	 *
	 * @since  1.3.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return string
	 */
	protected function column__date_ordered( $item ) {

		$date_ordered = get_post_meta( $item->po_id, '_date_created', TRUE );

		if ( $date_ordered ) {
			$date_ordered = Helpers::date_format( $date_ordered, FALSE );
		}

		return apply_filters( 'atum/inbound_stock_list/column_date_ordered', $date_ordered, $item, $this->list_item );
	}

	/**
	 * Column for date expected
	 *
	 * @since  1.3.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return string
	 */
	protected function column__date_expected( $item ) {

		$date_expected = get_post_meta( $item->po_id, '_date_expected', TRUE );

		if ( $date_expected ) {
			$date_expected = Helpers::date_format( $date_expected, FALSE );
		}

		return apply_filters( 'atum/inbound_stock_list/column_date_expected', $date_expected, $item, $this->list_item );
	}

	/**
	 * Column for purchase order
	 *
	 * @since 1.3.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return string
	 */
	protected function column__purchase_order( $item ) {

		$po_link = '<a href="' . get_edit_post_link( $item->po_id ) . '" target="_blank">#' . $item->po_id . '</a>';
		return apply_filters( 'atum/inbound_stock_list/column_purchase_order', $po_link, $item, $this->list_item );

	}

	/**
	 * Prepare the table data
	 *
	 * @since 1.4.2
	 */
	public function prepare_items() {

		global $wpdb;

		$due_statuses = PurchaseOrders::get_due_statuses();

		$joins = array(
			"LEFT JOIN `$wpdb->atum_order_itemmeta` AS oim ON oi.`order_item_id` = oim.`order_item_id`",
			"LEFT JOIN `$wpdb->posts` AS p ON oi.`order_id` = p.`ID`",
		);

		$where = array(
			"`meta_key` IN ('_product_id', '_variation_id')",
			"`order_item_type` = 'line_item'",
			$wpdb->prepare( 'p.`post_type` = %s', PurchaseOrders::POST_TYPE ),
			'`meta_value` > 0',
			"`post_status` IN ('" . implode( "','", $due_statuses ) . "')",
		);

		if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) > 0 ) {

			$search = esc_attr( $_REQUEST['s'] );

			if ( is_numeric( $search ) ) {
				$where[] = '`meta_value` = ' . absint( $_REQUEST['s'] );
			}
			else {
				$where[] = "`order_item_name` LIKE '%{$search}%'";
			}

		}

		$order_by = '`order_id`';
		if ( ! empty( $_REQUEST['orderby'] ) ) {

			switch ( $_REQUEST['orderby'] ) {
				case 'title':
					$order_by = '`order_item_name`';
					break;

				case 'ID':
					$order_by = 'oi.`order_item_id`';
					break;

			}

		}

		$order = ( ! empty( $_REQUEST['order'] ) && in_array( strtoupper( $_REQUEST['order'] ), [ 'ASC', 'DESC' ] ) ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';

		$joins_str = implode( "\n", apply_filters( 'atum/inbound_stock_list/prepare_items_joins', $joins ) );
		$where_str = implode( ' AND ', apply_filters( 'atum/inbound_stock_list/prepare_items_where', $where ) );

		// phpcs:disable WordPress.DB.PreparedSQL
		$sql = "
			SELECT MAX(CAST( `meta_value` AS SIGNED )) AS product_id, oi.`order_item_id`, `order_id`, `order_item_name` 			
			FROM `$wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` AS oi 
			$joins_str
			WHERE $where_str
			GROUP BY oi.`order_item_id`
			ORDER BY $order_by $order;
		";
		// phpcs:enable

		$po_products = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $po_products ) ) {

			$found_posts = count( $po_products );

			// Paginate the results (if needed).
			if ( -1 !== $this->per_page && $found_posts > $this->per_page ) {
				$page   = $this->get_pagenum();
				$offset = ( $page - 1 ) * $this->per_page;

				$po_products = array_slice( $po_products, $offset, $this->per_page );
			}

			foreach ( $po_products as $po_product ) {

				$post = get_post( $po_product['product_id'] );

				if ( $post ) {
					$post->po_id      = $po_product['order_id'];
					$post->po_item_id = $po_product['order_item_id'];
					$this->items[]    = $post;
				}
				// In case there are some products still added to POs but not exists on the shop anymore.
				else {
					$found_posts--;
				}

			}

			$this->set_views_data();
			$this->count_views['count_all'] = $found_posts;

			$this->set_pagination_args( array(
				'total_items' => $found_posts,
				'per_page'    => $this->per_page,
				'total_pages' => -1 === $this->per_page ? 0 : ceil( $found_posts / $this->per_page ),
			) );

		}

		do_action( 'atum/inbound_stock_list/after_prepare_items', $po_products );

	}

	/**
	 * Loads the current product
	 *
	 * @since 1.4.2
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 */
	public function single_row( $item ) {

		$this->list_item = Helpers::get_atum_product( $item );

		if ( ! $this->list_item instanceof \WC_Product ) {
			return;
		}

		$this->allow_calcs = TRUE;

		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';

		// Reset the child value.
		$this->is_child = FALSE;

	}

	/**
	 * Bulk actions are an associative array in the format 'slug' => 'Visible Title'
	 *
	 * @since 1.4.2
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'.
	 */
	protected function get_bulk_actions() {
		// No bulk actions needed for Inbound Stock.
		return apply_filters( 'atum/inbound_stock_list/bulk_actions', array() );
	}

}
