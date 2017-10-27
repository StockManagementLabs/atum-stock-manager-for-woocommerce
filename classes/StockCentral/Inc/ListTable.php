<?php
/**
 * @package         Atum\StockCentral
 * @subpackage      Inc
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           0.0.1
 */

namespace Atum\StockCentral\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumListTables\AtumListTable;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\Models\Log;
use Atum\PurchaseOrders\PurchaseOrders;


class ListTable extends AtumListTable {

	/**
	 * No stock Threshold
	 * @var int
	 */
	protected $no_stock;
	
	/**
	 * Time of query
	 * @var date
	 */
	protected $day;
	
	/**
	 * Values for the calculated columns form current page products
	 * @var array
	 */
	protected $calc_columns = array();

	/**
	 * Whether to load the jQuery UI datepicker script (for sale price dates)
	 * @var bool
	 */
	protected $load_datepicker = TRUE;

	/**
	 * @inheritdoc
	 */
	public function __construct( $args = array() ) {
		
		$this->no_stock = intval( get_option( 'woocommerce_notify_no_stock_amount' ) );
		
		// TODO: Allow to specify the day of query in constructor atts
		$this->day = Helpers::date_format( current_time('timestamp'), TRUE );
		
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
			'_regular_price'       => __( 'Regular Price', ATUM_TEXT_DOMAIN ),
			'_sale_price'          => __( 'Sale Price', ATUM_TEXT_DOMAIN ),
			'_purchase_price'      => __( 'Purchase Price', ATUM_TEXT_DOMAIN ),
			'_stock'               => __( 'Current Stock', ATUM_TEXT_DOMAIN ),
			'calc_inbound'         => __( 'Inbound Stock', ATUM_TEXT_DOMAIN ),
			'calc_hold'            => __( 'Stock on Hold', ATUM_TEXT_DOMAIN ),
			'calc_reserved'        => __( 'Reserved Stock', ATUM_TEXT_DOMAIN ),
			'calc_back_orders'     => __( 'Back Orders', ATUM_TEXT_DOMAIN ),
			'calc_sold_today'      => __( 'Sold Today', ATUM_TEXT_DOMAIN ),
			'calc_returns'         => __( 'Customer Returns', ATUM_TEXT_DOMAIN ),
			'calc_damages'         => __( 'Warehouse Damages', ATUM_TEXT_DOMAIN ),
			'calc_lost_in_post'    => __( 'Lost in Post', ATUM_TEXT_DOMAIN ),
			'calc_sales14'         => __( 'Sales Last 14 Days', ATUM_TEXT_DOMAIN ),
			'calc_sales7'          => __( 'Sales Last 7 Days', ATUM_TEXT_DOMAIN ),
			'calc_will_last'       => __( 'Stock will Last (Days)', ATUM_TEXT_DOMAIN ),
			'calc_stock_out_days'  => __( 'Out of Stock for (Days)', ATUM_TEXT_DOMAIN ),
			'calc_lost_sales'      => __( 'Lost Sales', ATUM_TEXT_DOMAIN ),
			'calc_stock_indicator' => __( 'Stock Indicator', ATUM_TEXT_DOMAIN ),
		);
		
		// TODO: Add group table functionality if some columns are invisible
		$args['group_members'] = array(
			'product-details'       => array(
				'title'   => __( 'Product Details', ATUM_TEXT_DOMAIN ),
				'members' => array( 'thumb', '_sku', 'ID', 'calc_type', 'title', '_regular_price', '_sale_price', '_purchase_price' )
			),
			'stock-counters'        => array(
				'title'   => __( 'Stock Counters', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'_stock',
					'calc_inbound',
					'calc_hold',
					'calc_reserved',
					'calc_back_orders',
					'calc_sold_today'
				)
			),
			'stock-negatives'       => array(
				'title'   => __( 'Stock Negatives', ATUM_TEXT_DOMAIN ),
				'members' => array( 'calc_returns', 'calc_damages', 'calc_lost_in_post' )
			),
			'stock-selling-manager' => array(
				'title'   => __( 'Stock Selling Manager', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'calc_sales14',
					'calc_sales7',
					'calc_will_last',
					'calc_stock_out_days',
					'calc_lost_sales',
					'calc_stock_indicator'
				)
			),
		);

		parent::__construct( $args );

		// Filtering with extra filters
		if ( ! empty( $_REQUEST['extra_filter'] ) ) {
			add_action( 'pre_get_posts', array($this, 'do_extra_filter') );
		}
		
	}

	/**
	 * @inheritdoc
	 */
	protected function table_nav_filters() {

		parent::table_nav_filters();

		// Extra filters
		$extra_filters = (array) apply_filters( 'atum/stock_central_list/extra_filters', array(
			'inbound_stock'     => __( 'Inbound Stock', ATUM_TEXT_DOMAIN ),
			'stock_on_hold'     => __( 'Stock on Hold', ATUM_TEXT_DOMAIN ),
			'reserved_stock'    => __( 'Reserved Stock', ATUM_TEXT_DOMAIN ),
			'back_orders'       => __( 'Back Orders', ATUM_TEXT_DOMAIN ),
			'sold_today'        => __( 'Sold Today', ATUM_TEXT_DOMAIN ),
			'customer_returns'  => __( 'Customer Returns', ATUM_TEXT_DOMAIN ),
			'warehouse_damages' => __( 'Warehouse Damages', ATUM_TEXT_DOMAIN ),
			'lost_in_post'      => __( 'Lost in Post', ATUM_TEXT_DOMAIN )
		));
		?>

		<select name="extra_filter" class="dropdown_extra_filter">
			<option value=""><?php _e( 'Show all', ATUM_TEXT_DOMAIN ) ?></option>

			<?php foreach ($extra_filters as $extra_filter => $label): ?>
				<option value="<?php echo $extra_filter ?>"<?php selected( ! empty( $_REQUEST['extra_filter'] ) && $_REQUEST['extra_filter'] == $extra_filter, TRUE ) ?>><?php echo $label ?></option>
			<?php endforeach; ?>
		</select>
		<?php

	}
	
	/**
	 * Column for regular price
	 *
	 * @since  1.2.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return float
	 */
	protected function column__regular_price( $item ) {

		$regular_price = self::EMPTY_COL;
		$product_id = $this->get_current_product_id($this->product);

		if ($this->allow_calcs) {

			$regular_price_value = $this->product->get_regular_price();
			$regular_price_value = ( is_numeric($regular_price_value) ) ? Helpers::format_price($regular_price_value, ['trim_zeros' => TRUE]) : $regular_price;

			$args = array(
				'post_id'  => $product_id,
				'meta_key' => 'regular_price',
				'value'    => $regular_price_value,
				'symbol'   => get_woocommerce_currency_symbol(),
				'tooltip'  => __( 'Click to edit the regular price', ATUM_TEXT_DOMAIN )
			);

			$regular_price = $this->get_editable_column($args);

		}

		return apply_filters( 'atum/stock_central_list/column_regular_price', $regular_price, $item, $this->product );
		
	}

	/**
	 * Column for sale price
	 *
	 * @since  1.2.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return float
	 */
	protected function column__sale_price( $item ) {

		$sale_price = self::EMPTY_COL;
		$product_id = $this->get_current_product_id($this->product);

		if ($this->allow_calcs) {

			$sale_price_value = $this->product->get_sale_price();
			$sale_price_value = ( is_numeric($sale_price_value) ) ? Helpers::format_price($sale_price_value, ['trim_zeros'=> TRUE]) : $sale_price;
			$sale_price_dates_from = ( $date = get_post_meta( $product_id, '_sale_price_dates_from', TRUE ) ) ? date_i18n( 'Y-m-d', $date ) : '';
			$sale_price_dates_to   = ( $date = get_post_meta( $product_id, '_sale_price_dates_to', TRUE ) ) ? date_i18n( 'Y-m-d', $date ) : '';

			$args = array(
				'post_id'    => $product_id,
				'meta_key'   => 'sale_price',
				'value'      => $sale_price_value,
				'symbol'     => get_woocommerce_currency_symbol(),
				'tooltip'    => __( 'Click to edit the sale price', ATUM_TEXT_DOMAIN ),
				'extra_meta' => array(
					array(
						'name'        => '_sale_price_dates_from',
						'type'        => 'text',
						'placeholder' => _x( 'Sale date from...', 'placeholder', ATUM_TEXT_DOMAIN ) . ' YYYY-MM-DD',
						'value'       => $sale_price_dates_from,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'datepicker from'
					),
					array(
						'name'        => '_sale_price_dates_to',
						'type'        => 'text',
						'placeholder' => _x( 'Sale date to...', 'placeholder', ATUM_TEXT_DOMAIN ) . ' YYYY-MM-DD',
						'value'       => $sale_price_dates_to,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'datepicker to'
					)
				)
			);

			$sale_price = $this->get_editable_column($args);

		}

		return apply_filters( 'atum/stock_central_list/column_sale_price', $sale_price, $item, $this->product );

	}

	/**
	 * Column for stock on hold: show amount of items with pending payment.
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int
	 */
	protected function column_calc_hold( $item ) {

		$stock_on_hold = self::EMPTY_COL;

		if ($this->allow_calcs) {

			$stock_on_hold = 0;
			$orders = Helpers::get_orders( array( 'order_status' => 'wc-on-hold, wc-pending' ) );

			foreach ( $orders as $order ) {

				$products = $order->get_items();

				foreach ( $products as $product ) {
					if ( $this->product->get_id() == $product['product_id'] ) {
						$stock_on_hold += $product['qty'];
					}

				}

			}
		}

		return apply_filters( 'atum/stock_central_list/column_stock_hold', $stock_on_hold, $item, $this->product );
	}

	/**
	 * Column for reserved stock: sums the items within "Reserved Stock" logs
	 *
	 * @since  1.2.4
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int
	 */
	protected function column_calc_reserved( $item ) {

		$reserved_stock = (! $this->allow_calcs) ? self::EMPTY_COL : $this->get_log_item_qty( 'reserved-stock', $this->product->get_id() );
		return apply_filters( 'atum/stock_central_list/column_reserved_stock', $reserved_stock, $item, $this->product );
	}
	
	/**
	 * Column for back orders amount: show amount if items pending to serve and without existences
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int|string
	 */
	protected function column_calc_back_orders( $item ) {

		$back_orders = self::EMPTY_COL;

		if ($this->allow_calcs) {

			$back_orders = '--';
			if ( $this->product->backorders_allowed() ) {

				$stock_quantity = $this->product->get_stock_quantity();
				$back_orders = 0;
				if ( $stock_quantity < $this->no_stock ) {
					$back_orders = $this->no_stock - $stock_quantity;
				}

			}

		}
		
		return apply_filters( 'atum/stock_central_list/column_back_orders', $back_orders, $item, $this->product );
		
	}
	
	/**
	 * Column for items sold today
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int
	 */
	protected function column_calc_sold_today( $item ) {

		if (! $this->allow_calcs) {
			$sold_today = self::EMPTY_COL;
		}
		else {
			$sold_today = ( empty( $this->calc_columns[ $this->product->get_id() ]['sold_today'] ) ) ? 0 : $this->calc_columns[ $this->product->get_id() ]['sold_today'];
		}
		
		return apply_filters( 'atum/stock_central_list/column_sold_today', $sold_today, $item, $this->product );
		
	}

	/**
	 * Column for customer returns: sums the items within "Reserved Stock" logs
	 *
	 * @since  1.2.4
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int
	 */
	protected function column_calc_returns( $item ) {

		$consumer_returns = (! $this->allow_calcs) ? self::EMPTY_COL : $this->get_log_item_qty( 'customer-returns', $this->product->get_id() );
		return apply_filters( 'atum/stock_central_list/column_cutomer_returns', $consumer_returns, $item, $this->product );
	}

	/**
	 * Column for warehouse damages: sums the items within "Warehouse Damage" logs
	 *
	 * @since  1.2.4
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int
	 */
	protected function column_calc_damages( $item ) {

		$warehouse_damages = (! $this->allow_calcs) ? self::EMPTY_COL : $this->get_log_item_qty( 'warehouse-damage', $this->product->get_id() );
		return apply_filters( 'atum/stock_central_list/column_warehouse_damage', $warehouse_damages, $item, $this->product );
	}

	/**
	 * Column for lost in post: sums the items within "Lost in Post" logs
	 *
	 * @since  1.2.4
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int
	 */
	protected function column_calc_lost_in_post( $item ) {

		$lost_in_post = (! $this->allow_calcs) ? self::EMPTY_COL : $this->get_log_item_qty( 'lost-in-post', $this->product->get_id() );
		return apply_filters( 'atum/stock_central_list/column_lost_in_post', $lost_in_post, $item, $this->product );
	}
	
	/**
	 * Column for items sold during the last week
	 *
	 * @since  0.1.2
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int
	 */
	protected function column_calc_sales7( $item ) {

		if (! $this->allow_calcs) {
			$sales7 = self::EMPTY_COL;
		}
		else {
			$sales7 = ( empty( $this->calc_columns[ $this->product->get_id() ]['sold_7'] ) ) ? 0 : $this->calc_columns[ $this->product->get_id() ]['sold_7'];
		}
		
		return apply_filters( 'atum/stock_central_list/column_sold_last_7_days', $sales7, $item, $this->product );
		
	}
	
	/**
	 * Column for items sold during the last 2 weeks
	 *
	 * @since  0.1.2
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int
	 */
	protected function column_calc_sales14( $item ) {

		if (! $this->allow_calcs) {
			$sales14 = self::EMPTY_COL;
		}
		else {
			$sales14 = ( empty( $this->calc_columns[ $this->product->get_id() ]['sold_14'] ) ) ? 0 : $this->calc_columns[ $this->product->get_id() ]['sold_14'];
		}
		
		return apply_filters( 'atum/stock_central_list/column_sold_last_14_days', $sales14, $item, $this->product );
		
	}
	
	/**
	 * Column for number of days the stock will be sufficient to fulfill orders
	 * Formula: Current Stock Value / (Sales Last 7 Days / 7)
	 *
	 * @since  0.1.3
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int|string
	 */
	protected function column_calc_will_last( $item ) {
			
		// NOTE: FOR NOW IS FIXED TO 7 DAYS AVERAGE
		$will_last = self::EMPTY_COL;

		if ($this->allow_calcs) {
			$sales = $this->column_calc_sales7( $item );
			$stock = $this->product->get_stock_quantity();

			if ( $stock > 0 && $sales > 0 ) {
				$will_last = ceil( $stock / ( $sales / 7 ) );
			}
			elseif ( $stock > 0 ) {
				$will_last = '>30';
			}
		}
		
		return apply_filters( 'atum/stock_central_list/column_stock_will_last_days', $will_last, $item, $this->product );
		
	}
	
	/**
	 * Column for number of days the product is out of stock
	 *
	 * @since  0.1.4
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int|string
	 */
	protected function column_calc_stock_out_days( $item ) {

		$out_of_stock_days = '';

		if ($this->allow_calcs) {
			$out_of_stock_days = Helpers::get_product_out_of_stock_days( $this->product->get_id() );
		}

		$out_of_stock_days = ( is_numeric($out_of_stock_days) ) ? $out_of_stock_days : self::EMPTY_COL;
		
		return apply_filters( 'atum/stock_central_list/column_stock_out_days', $out_of_stock_days, $item, $this->product );
		
	}

	/**
	 * Column for lost sales
	 *
	 * @since  1.2.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int|string
	 */
	protected function column_calc_lost_sales( $item ) {

		$lost_sales = '';

		if ($this->allow_calcs) {
			$lost_sales = Helpers::get_product_lost_sales( $this->product->get_id() );
		}

		$lost_sales = ( is_numeric($lost_sales) ) ? Helpers::format_price( $lost_sales, ['trim_zeros' => TRUE] ) : self::EMPTY_COL;

		return apply_filters( 'atum/stock_central_list/column_lost_sales', $lost_sales, $item, $this->product );

	}
	
	/**
	 * Prepare the table data
	 *
	 * @since  0.0.2
	 */
	public function prepare_items() {
		
		parent::prepare_items();

		// Calc products sold today (since midnight)
		$rows = Helpers::get_sold_last_days( $this->current_products, 'today 00:00:00', $this->day );
		
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_today'] = $row['QTY'];
			}
		}

		// Calc products sold during the last week
		$rows = Helpers::get_sold_last_days( $this->current_products, $this->day . ' -1 week', $this->day );
		
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_7'] = $row['QTY'];
			}
		}

		// Calc products sold during the last 2 weeks
		$rows = Helpers::get_sold_last_days( $this->current_products, $this->day . ' -2 weeks', $this->day );
		
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_14'] = $row['QTY'];
			}
		}

		// Calc products sold the $last_days days
		$rows = Helpers::get_sold_last_days( $this->current_products, "-$this->last_days days", $this->day );
		
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_last_days'] = $row['QTY'];
			}
		}
		
	}

	/**
	 * Get the Inventory Log item quantity for a specific type of log
	 *
	 * @since 1.2.4
	 *
	 * @type string $log_type   Type of log
	 * @type int    $item_id    Item (WC Product) ID to check
	 * @type string $log_status Optional. Log status (completed or pending)
	 *
	 * @return int
	 */
	protected function get_log_item_qty( $log_type, $item_id, $log_status = 'pending' ) {

		$qty = 0;
		$log_ids = Helpers::get_logs($log_type, $log_status);

		if ( ! empty($log_ids) ) {

			global $wpdb;

			foreach ($log_ids as $log_id) {

				// Get the _qty meta for the specified product in the specified log
				$query = $wpdb->prepare(
					"SELECT SUM(meta_value) 				  
					 FROM {$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEM_META_TABLE . " om
		             JOIN {$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . " oi
		             ON om.order_item_id = oi.order_item_id
					 WHERE order_id = %d AND order_item_type = %s 
					 AND meta_key = '_qty' AND om.order_item_id IN (
					 	SELECT order_item_id FROM {$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEM_META_TABLE . " 
					 	WHERE meta_key IN ('_product_id', '_variation_id') AND meta_value = %d
					 )",
					$log_id,
					'line_item',
					$item_id
				);

				$qty += $wpdb->get_var($query);

			}

		}

		return absint( $qty );

	}

	/**
	 * Apply an extra filter to the current List Table query
	 *
	 * @since 1.2.8
	 *
	 * @param \WP_Query $query
	 */
	public function do_extra_filter($query) {

		// Avoid calling the "pre_get_posts" again when querying orders
		if ( $query->query_vars['post_type'] != 'product' ) {
			return;
		}

		if ( ! empty($query->query_vars['post__in']) ) {
			return;
		}

		$extra_filter = esc_attr( $_REQUEST['extra_filter'] );
		$filtered_products = array();

		switch ( $extra_filter ) {

			case 'inbound_stock':

				// Get all the products within pending Purchase Orders
				global $wpdb;

				$sql = $wpdb->prepare("
					SELECT MAX(CAST( oim.`meta_value` AS SIGNED )) AS product_id, SUM(oim2.`meta_value`) AS quantity 			
					FROM `{$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` AS oi 
					LEFT JOIN `{$wpdb->atum_order_itemmeta}` AS oim ON oi.`order_item_id` = oim.`order_item_id`
					LEFT JOIN `{$wpdb->atum_order_itemmeta}` AS oim2 ON oi.`order_item_id` = oim2.`order_item_id`
					LEFT JOIN `{$wpdb->posts}` AS p ON oi.`order_id` = p.`ID`
					WHERE oim.`meta_key` IN ('_product_id', '_variation_id') AND `order_item_type` = 'line_item' 
					AND p.`post_type` = %s AND oim.`meta_value` > 0 AND `post_status` = 'atum_pending' AND oim2.`meta_key` = '_qty'	
					GROUP BY oim.`meta_value`;",
					PurchaseOrders::POST_TYPE
				);

				$product_results = $wpdb->get_results($sql, OBJECT_K);

				if ( ! empty($product_results) ) {

					array_walk($product_results, function(&$item) {
						$item = $item->quantity;
					});

					$filtered_products = $product_results;

				}

		        break;

			case 'stock_on_hold':

				$orders = Helpers::get_orders( array( 'order_status' => 'wc-on-hold, wc-pending' ) );

				foreach ( $orders as $order ) {

					$products = $order->get_items();

					foreach ( $products as $product ) {

						if ( isset( $filtered_products[ $product['product_id'] ] ) ) {
							$filtered_products[ $product['product_id'] ] += $product['qty'];
						}
						else {
							$filtered_products[ $product['product_id'] ] = $product['qty'];
						}

					}

				}

				break;

			case 'reserved_stock':

				// Get all the products within 'Reserved Stock' logs
				$filtered_products = $this->get_log_products('reserved-stock', 'pending');
				break;

			case 'back_orders':

				// Avoid infinite loop of recalls
				remove_action( 'pre_get_posts', array($this, 'do_extra_filter') );

				// Get all the products that allow back orders
				$args = array(
					'post_type'      => 'product',
					'posts_per_page' => - 1,
					'meta_key'       => '_backorders',
					'meta_value'     => 'yes'
				);
				$products = get_posts($args);

				foreach ($products as $product) {

					$wc_product     = wc_get_product( $product->ID );
					$back_orders    = 0;
					$stock_quantity = $wc_product->get_stock_quantity();

					if ( $stock_quantity < $this->no_stock ) {
						$back_orders = $this->no_stock - $stock_quantity;
					}

					if ($back_orders) {
						$filtered_products[ $wc_product->get_id() ] = $back_orders;
					}

				}

				// Re-add the action
				add_action( 'pre_get_posts', array($this, 'do_extra_filter') );

				break;

			case 'sold_today':

				// Get the orders processed today
				$atts = array(
					'order_status'     => 'wc-processing, wc-completed',
					'order_date_start' => 'today 00:00:00'
				);
				$today_orders = Helpers::get_orders($atts);

				foreach ( $today_orders as $today_order ) {

					$products = $today_order->get_items();

					foreach ( $products as $product ) {

						if ( isset( $filtered_products[ $product['product_id'] ] ) ) {
							$filtered_products[ $product['product_id'] ] += $product['qty'];
						}
						else {
							$filtered_products[ $product['product_id'] ] = $product['qty'];
						}

					}

				}

				break;

			case 'customer_returns':

				// Get all the products within 'Customer Returns' logs
				$filtered_products = $this->get_log_products('customer-returns', 'pending');
				break;

			case 'warehouse_damages':

				// Get all the products within 'Warehouse Damage' logs
				$filtered_products = $this->get_log_products('warehouse-damage', 'pending');
				break;

			case 'lost_in_post':

				// Get all the products within 'Lost in Post' logs
				$filtered_products = $this->get_log_products('lost-in-post', 'pending');
				break;

		}

		if ( ! empty($filtered_products) ) {

			// Order desc by quantity and get the ordered IDs
			arsort($filtered_products);
			$filtered_products = array_keys($filtered_products);

			// Filter the query posts by these IDs
			$query->set( 'post__in', $filtered_products );

		}
		// Force no results ("-1" never will be a post ID)
		else {
			$query->set( 'post__in', array(-1) );
		}

	}

	/**
	 * Get all the products with total quantity within a specific type of Log
	 *
	 * @since 1.2.8
	 *
	 * @param string $log_type
	 * @param string $log_status
	 *
	 * @return array|bool
	 */
	protected function get_log_products($log_type, $log_status = '') {

		$log_types = array_keys( Log::get_types() );

		if ( ! in_array($log_type, $log_types) ) {
			return FALSE;
		}

		$log_ids = Helpers::get_logs($log_type, $log_status);
		$products = array();

		if ( ! empty($log_ids) ) {

			foreach ($log_ids as $log_id) {

				$log       = new Log( $log_id );
				$log_items = $log->get_items();

				if ( ! empty($log_items) ) {

					foreach ( $log_items as $log_item ) {

						if ( ! is_a($log_item, '\Atum\InventoryLogs\Items\LogItemProduct') ) {
							continue;
						}

						$qty          = $log_item->get_quantity();
						$variation_id = $log_item->get_variation_id();
						$product_id   = ( $variation_id ) ? $variation_id : $log_item->get_product_id();

						if ( isset( $products[ $product_id ] ) ) {
							$products[ $product_id ] += $qty;
						}
						else {
							$products[ $product_id ] = $qty;
						}

					}

				}

			}

		}

		return $products;

	}
	
}