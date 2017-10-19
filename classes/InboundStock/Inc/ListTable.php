<?php
/**
 * @package         Atum\InboundStock
 * @subpackage      Inc
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.3.0
 */

namespace Atum\InboundStock\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumListTables\AtumListTable;
use Atum\Components\AtumOrders\AtumOrderPostType;
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
			'calc_date_expected'   => __( 'Date Expected', ATUM_TEXT_DOMAIN )
		);

		parent::__construct( $args );
		
	}

	/**
	 * @inheritdoc
	 */
	public function views() {
		// Disable views filters
	}

	/**
	 * @inheritdoc
	 */
	protected function table_nav_filters() {
		// Disable filters
	}

	/**
	 * @inheritdoc
	 */
	protected function set_views_data( $args ) {
		// No need to calculate views
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

		return apply_filters( 'atum/stock_central_list/column_type', '<span class="product-type tips ' . $type . '" data-toggle="tooltip" title="' . $product_tip . '"></span>', $item, $this->product );

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

		// Get the quantity for the order item
		$order_item_id = $item->po_item_id;
		$qty = get_metadata( 'atum_order_item', $order_item_id, '_qty', TRUE);

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
	 * @inheritdoc
	 */
	public function prepare_items() {

		global $wpdb;

		$sql = $wpdb->prepare("
			SELECT MAX(CAST( `meta_value` AS SIGNED )) AS product_id, oi.`order_item_id`, `order_id` 			
			FROM `{$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` AS oi 
			LEFT JOIN `{$wpdb->atum_order_itemmeta}` AS oim ON oi.`order_item_id` = oim.`order_item_id`
			LEFT JOIN `{$wpdb->posts}` AS p ON oi.`order_id` = p.`ID`
			WHERE meta_key IN ('_product_id', '_variation_id') AND `order_item_type` = 'line_item' 
			AND p.`post_type` = %s AND `meta_value` > 0 AND `post_status` = 'atum_pending'
			GROUP BY oi.`order_item_id`
			ORDER BY oi.`order_item_id` DESC;",
			PurchaseOrders::POST_TYPE
		);

		$po_products = $wpdb->get_results($sql);

		if ( ! empty($po_products) ) {

			$found_posts = count($po_products);

			// Paginate the results (if needed)
			if ($this->per_page != -1 && $found_posts > $this->per_page) {
				$page = $this->get_pagenum();
				$offset = ($page > 1) ? ($page - 1) + $this->per_page : 0;

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

			$this->set_pagination_args( array(
				'total_items' => $found_posts,
				'per_page'    => $this->per_page,
				'total_pages' => ( $this->per_page == - 1 ) ? 0 : ceil( $found_posts / $this->per_page )
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
	
}