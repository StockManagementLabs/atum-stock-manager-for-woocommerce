<?php
/**
 * Stock Central List Table
 *
 * @package         Atum\StockCentral
 * @subpackage      Lists
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           0.0.1
 */

namespace Atum\StockCentral\Lists;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumListTables\AtumListTable;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\Models\Log;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\PurchaseOrders;


class ListTable extends AtumListTable {

	/**
	 * No stock Threshold
	 *
	 * @var int
	 */
	protected $no_stock;
	
	/**
	 * Time of query
	 *
	 * @var string
	 */
	protected $day;
	
	/**
	 * Values for the calculated columns form current page products
	 *
	 * @var array
	 */
	protected $calc_columns = array();

	/**
	 * Whether to load the jQuery UI datepicker script (for sale price dates)
	 *
	 * @var bool
	 */
	protected $load_datepicker = TRUE;

	/**
	 * The columns hidden by default
	 *
	 * @var array
	 */
	protected static $default_hidden_columns = array(
		'ID',
		'_weight',
		'calc_inbound',
		'calc_hold',
		'calc_reserved',
		'calc_back_orders',
		'calc_sold_today',
		'calc_returns',
		'calc_damages',
		'calc_lost_in_post',
		'calc_sales_last_ndays',
		'calc_will_last',
		'calc_stock_out_days',
		'calc_lost_sales',
	);

	/**
	 * What columns are numeric and searchable? and strings? append to this two keys
	 *
	 * @var array
	 */
	protected $default_searchable_columns = array(
		'string'  => array(
			'title',
			'_supplier',
			'_sku',
			'_supplier_sku',
		),
		'numeric' => array(
			'ID',
			'_regular_price',
			'_sale_price',
			'_purchase_price',
			'_weight',
			'_stock',
			'_out_stock_threshold',
		),
	);


	/**
	 * ListTable Constructor
	 *
	 * The child class should call this constructor from its own constructor to override the default $args
	 *
	 * @since 0.0.1
	 *
	 * @param array|string $args          {
	 *      Array or string of arguments.
	 *
	 *      @type array  $table_columns     The table columns for the list table
	 *      @type array  $group_members     The column grouping members
	 *      @type bool   $show_cb           Optional. Whether to show the row selector checkbox as first table column
	 *      @type bool   $show_controlled   Optional. Whether to show items controlled by ATUM or not
	 *      @type int    $per_page          Optional. The number of posts to show per page (-1 for no pagination)
	 *      @type array  $selected          Optional. The posts selected on the list table
	 *      @type array  $excluded          Optional. The posts excluded from the list table
	 * }
	 */
	public function __construct( $args = array() ) {
		
		$this->no_stock = intval( get_option( 'woocommerce_notify_no_stock_amount' ) );
		
		// Activate managed/unmanaged counters separation.
		$this->show_unmanaged_counters = 'yes' === Helpers::get_option( 'unmanaged_counters' );
		
		// TODO: Allow to specify the day of query in constructor atts.
		$this->day = Helpers::date_format( current_time( 'timestamp' ), TRUE );
		
		$this->taxonomies[] = array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => Globals::get_product_types(),
		);

		// Prepare the table columns.
		$args['table_columns'] = self::get_table_columns();

		// TODO: Add group table functionality if some columns are hidden.
		$args['group_members'] = (array) apply_filters( 'atum/stock_central_list/column_group_members', array(
			'product-details'       => array(
				'title'   => __( 'Product Details', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'thumb',
					'title',
					'_supplier',
					'_sku',
					'_supplier_sku',
					'ID',
					'calc_type',
					'calc_location',
					'_regular_price',
					'_sale_price',
					'_purchase_price',
					'_weight',
				),
			),
			'stock-counters'        => array(
				'title'   => __( 'Stock Counters', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'_stock',
					'_out_stock_threshold',
					'calc_inbound',
					'calc_hold',
					'calc_reserved',
					'calc_back_orders',
					'calc_sold_today',
				),
			),
			'stock-negatives'       => array(
				'title'   => __( 'Stock Negatives', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'calc_returns',
					'calc_damages',
					'calc_lost_in_post',
				),
			),
			'stock-selling-manager' => array(
				'title'   => __( 'Stock Selling Manager', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'calc_sales_last_ndays',
					'calc_will_last',
					'calc_stock_out_days',
					'calc_lost_sales',
					'calc_stock_indicator',
				),
			),
		) );

		// Hide the purchase price column if the current user has not the capability.
		if ( ! AtumCapabilities::current_user_can( 'view_purchase_price' ) ) {
			unset( $args['table_columns']['_purchase_price'] );
			$args['group_members']['product-details']['members'] = array_diff( $args['group_members']['product-details']['members'], [ '_purchase_price' ] );
		}

		// Hide the supplier's columns if the current user has not the capability.
		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) || ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			unset( $args['table_columns']['_supplier'] );
			unset( $args['table_columns']['_supplier_sku'] );
			$args['group_members']['product-details']['members'] = array_diff( $args['group_members']['product-details']['members'], [ '_sku', '_supplier_sku' ] );
		}

		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) ) {
			unset( $args['table_columns']['_purchase_price'] );
			unset( $args['table_columns']['calc_inbound'] );
			$args['group_members']['product-details']['members'] = array_diff( $args['group_members']['product-details']['members'], [ '_purchase_price' ] );
			$args['group_members']['stock-counters']['members']  = array_diff( $args['group_members']['stock-counters']['members'], [ 'calc_inbound' ] );
		}

		// Initialize totalizers.
		$this->totalizers = apply_filters( 'atum/stock_central_list/totalizers', array(
			'_stock'                => 0,
			'_out_stock_threshold'  => 0,
			'calc_inbound'          => 0,
			'calc_hold'             => 0,
			'calc_reserved'         => 0,
			'calc_back_orders'      => 0,
			'calc_sold_today'       => 0,
			'calc_returns'          => 0,
			'calc_damages'          => 0,
			'calc_lost_in_post'     => 0,
			'calc_sales_last_ndays' => 0,
			'calc_lost_sales'       => 0,
		));

		// Set the sticky columns.
		if ( 'yes' === Helpers::get_option( 'sticky_columns', 'no' ) ) {
			$this->sticky_columns = (array) apply_filters( 'atum/stock_central_list/sticky_columns', array(
				'cb',
				'thumb',
				'ID',
				'title',
				'calc_type',
			) );
		}

		// Call the parent class once all the $args are set.
		parent::__construct( $args );

		// Filtering with extra filters.
		if ( ! empty( $_REQUEST['extra_filter'] ) ) { // WPCS: CSRF ok.
			add_action( 'pre_get_posts', array( $this, 'do_extra_filter' ) );
		}

		// Add the "Apply Bulk Action" button to the title section.
		add_action( 'atum/stock_central_list/page_title_buttons', array( $this, 'add_apply_bulk_action_button' ) );

	}

	/**
	 * Prepare the table columns for Stock Central
	 *
	 * @since 1.4.16
	 *
	 * @return array
	 */
	public static function get_table_columns() {

		// Get the ndays set to last sales column.
		$sold_last_days = Helpers::get_sold_last_days_option();

		// NAMING CONVENTION: The column names starting by underscore (_) are based on meta keys (the name must match the meta key name),
		// the column names starting with "calc_" are calculated fields and the rest are WP's standard fields
		// *** Following this convention is necessary for column sorting functionality ***!
		$table_columns = array(
			'thumb'                 => '<span class="wc-image tips" data-placement="bottom" data-tip="' . esc_attr__( 'Image', ATUM_TEXT_DOMAIN ) . '">' . __( 'Thumb', ATUM_TEXT_DOMAIN ) . '</span>',
			'ID'                    => __( 'ID', ATUM_TEXT_DOMAIN ),
			'title'                 => __( 'Name', ATUM_TEXT_DOMAIN ),
			'calc_type'             => '<span class="wc-type tips" data-placement="bottom" data-tip="' . esc_attr__( 'Product Type', ATUM_TEXT_DOMAIN ) . '">' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '</span>',
			'_sku'                  => __( 'SKU', ATUM_TEXT_DOMAIN ),
			'_supplier'             => __( 'Supplier', ATUM_TEXT_DOMAIN ),
			'_supplier_sku'         => __( 'Sup. SKU', ATUM_TEXT_DOMAIN ),
			'calc_location'         => '<span class="dashicons dashicons-store tips" data-placement="bottom" data-tip="' . esc_attr__( 'Location', ATUM_TEXT_DOMAIN ) . '">' . __( 'Location', ATUM_TEXT_DOMAIN ) . '</span>',
			'_regular_price'        => __( 'Regular Price', ATUM_TEXT_DOMAIN ),
			'_sale_price'           => __( 'Sale Price', ATUM_TEXT_DOMAIN ),
			'_purchase_price'       => __( 'Purchase Price', ATUM_TEXT_DOMAIN ),
			'_weight'               => __( 'Weight', ATUM_TEXT_DOMAIN ),
			'_stock'                => __( 'Current Stock', ATUM_TEXT_DOMAIN ),
			'_out_stock_threshold'  => __( 'Out of Stock Threshold', ATUM_TEXT_DOMAIN ),
			'calc_inbound'          => __( 'Inbound Stock', ATUM_TEXT_DOMAIN ),
			'calc_hold'             => __( 'Stock on Hold', ATUM_TEXT_DOMAIN ),
			'calc_reserved'         => __( 'Reserved Stock', ATUM_TEXT_DOMAIN ),
			'calc_back_orders'      => __( 'Back Orders', ATUM_TEXT_DOMAIN ),
			'calc_sold_today'       => __( 'Sold Today', ATUM_TEXT_DOMAIN ),
			'calc_returns'          => __( 'Customer Returns', ATUM_TEXT_DOMAIN ),
			'calc_damages'          => __( 'Warehouse Damages', ATUM_TEXT_DOMAIN ),
			'calc_lost_in_post'     => __( 'Lost in Post', ATUM_TEXT_DOMAIN ),
			/* translators: the number of sales during last days */
			'calc_sales_last_ndays' => sprintf( _n( 'Sales last %s day', 'Sales last %s days', $sold_last_days, ATUM_TEXT_DOMAIN ), '<span class="set-header" id="sales_last_ndays_val" title="' . __( 'Click to change days', ATUM_TEXT_DOMAIN ) . '">' . $sold_last_days . '</span>' ),
			'calc_will_last'        => __( 'Stock will Last (Days)', ATUM_TEXT_DOMAIN ),
			'calc_stock_out_days'   => __( 'Out of Stock for (Days)', ATUM_TEXT_DOMAIN ),
			'calc_lost_sales'       => __( 'Lost Sales', ATUM_TEXT_DOMAIN ),
			'calc_stock_indicator'  => '<span class="dashicons dashicons-dashboard tips" data-placement="bottom" data-tip="' . esc_attr__( 'Stock Indicator', ATUM_TEXT_DOMAIN ) . '">' . __( 'Stock Indicator', ATUM_TEXT_DOMAIN ) . '</span>',
		);

		// Hide the purchase price column if the current user has not the capability.
		if ( ! AtumCapabilities::current_user_can( 'view_purchase_price' ) ) {
			unset( $table_columns['_purchase_price'] );
		}

		// Hide the supplier's columns if the current user has not the capability.
		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) || ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			unset( $table_columns['_supplier'] );
			unset( $table_columns['_supplier_sku'] );
		}

		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) ) {
			unset( $table_columns['_purchase_price'] );
			unset( $table_columns['calc_inbound'] );
		}

		return (array) apply_filters( 'atum/stock_central_list/table_columns', $table_columns );

	}

	/**
	 * Add the filters to the table nav
	 *
	 * @since 1.3.0
	 */
	protected function table_nav_filters() {

		parent::table_nav_filters();

		// Extra filters.
		$extra_filters = (array) apply_filters( 'atum/stock_central_list/extra_filters', array(
			'inbound_stock'     => __( 'Inbound Stock', ATUM_TEXT_DOMAIN ),
			'stock_on_hold'     => __( 'Stock on Hold', ATUM_TEXT_DOMAIN ),
			'reserved_stock'    => __( 'Reserved Stock', ATUM_TEXT_DOMAIN ),
			'back_orders'       => __( 'Back Orders', ATUM_TEXT_DOMAIN ),
			'sold_today'        => __( 'Sold Today', ATUM_TEXT_DOMAIN ),
			'customer_returns'  => __( 'Customer Returns', ATUM_TEXT_DOMAIN ),
			'warehouse_damages' => __( 'Warehouse Damages', ATUM_TEXT_DOMAIN ),
			'lost_in_post'      => __( 'Lost in Post', ATUM_TEXT_DOMAIN ),
		));

		?>
		<select name="extra_filter" class="dropdown_extra_filter" autocomplete="off">
			<option value=""><?php esc_attr_e( 'Show all', ATUM_TEXT_DOMAIN ) ?></option>

			<?php foreach ( $extra_filters as $extra_filter => $label ) : ?>
				<option value="<?php echo esc_attr( $extra_filter ) ?>"<?php selected( ! empty( $_REQUEST['extra_filter'] ) && $_REQUEST['extra_filter'] === $extra_filter, TRUE ); ?>><?php echo esc_attr( $label ) ?></option>
			<?php endforeach; ?>
		</select>
		<?php

	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag. Deleted 'fixed' from standard function
	 *
	 * @since 1.1.4.2
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {

		$table_classes   = parent::get_table_classes();
		$table_classes[] = 'stock-central-list';

		return $table_classes;
	}
	
	/**
	 * Column for regular price
	 *
	 * @since 1.2.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return float
	 */
	protected function column__regular_price( $item ) {
		
		$regular_price = self::EMPTY_COL;
		
		if ( $this->allow_calcs ) {
			
			$regular_price_value = $this->product->get_regular_price();
			$regular_price_value = is_numeric( $regular_price_value ) ? Helpers::format_price( $regular_price_value, [
				'trim_zeros' => TRUE,
				'currency'   => self::$default_currency,
			] ) : $regular_price;

			$args = apply_filters( 'atum/stock_central_list/args_regular_price', array(
				'meta_key' => 'regular_price',
				'value'    => $regular_price_value,
				'symbol'   => get_woocommerce_currency_symbol(),
				'currency' => self::$default_currency,
				'tooltip'  => esc_attr__( 'Click to edit the regular price', ATUM_TEXT_DOMAIN ),
			) );
			
			$regular_price = self::get_editable_column( $args );
			
		}
		
		return apply_filters( 'atum/stock_central_list/column_regular_price', $regular_price, $item, $this->product );
		
	}
	
	/**
	 * Column for sale price
	 *
	 * @since 1.2.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return float
	 */
	protected function column__sale_price( $item ) {

		$sale_price = self::EMPTY_COL;
		$product_id = $this->get_current_product_id();
		
		if ( $this->allow_calcs ) {
			
			$sale_price_value = $this->product->get_sale_price();
			$sale_price_value = is_numeric( $sale_price_value ) ? Helpers::format_price( $sale_price_value, [
				'trim_zeros' => TRUE,
				'currency'   => self::$default_currency,
			] ) : $sale_price;
			
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInTernaryCondition
			$sale_price_dates_from = ( $date = get_post_meta( $product_id, '_sale_price_dates_from', TRUE ) ) ? date( 'Y-m-d', $date ) : '';
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInTernaryCondition
			$sale_price_dates_to = ( $date = get_post_meta( $product_id, '_sale_price_dates_to', TRUE ) ) ? date( 'Y-m-d', $date ) : '';

			$args = apply_filters( 'atum/stock_central_list/args_sale_price', array(
				'meta_key'   => 'sale_price',
				'value'      => $sale_price_value,
				'symbol'     => get_woocommerce_currency_symbol(),
				'currency'   => self::$default_currency,
				'tooltip'    => esc_attr__( 'Click to edit the sale price', ATUM_TEXT_DOMAIN ),
				'extra_meta' => array(
					array(
						'name'        => '_sale_price_dates_from',
						'type'        => 'text',
						'placeholder' => _x( 'Sale date from...', 'placeholder', ATUM_TEXT_DOMAIN ) . ' YYYY-MM-DD',
						'value'       => $sale_price_dates_from,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'datepicker from',
					),
					array(
						'name'        => '_sale_price_dates_to',
						'type'        => 'text',
						'placeholder' => _x( 'Sale date to...', 'placeholder', ATUM_TEXT_DOMAIN ) . ' YYYY-MM-DD',
						'value'       => $sale_price_dates_to,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'datepicker to',
					),
				),
			) );
			
			$sale_price = self::get_editable_column( $args );
			
		}

		return apply_filters( 'atum/stock_central_list/column_sale_price', $sale_price, $item, $this->product );

	}

	/**
	 * Column for stock on hold: show amount of items with pending payment.
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int
	 */
	protected function column_calc_hold( $item ) {

		$stock_on_hold = self::EMPTY_COL;

		if ( $this->allow_calcs ) {

			global $wpdb;
			$product_id_key = Helpers::is_child_type( $this->product->get_type() ) ? '_variation_id' : '_product_id';

			$sql = $wpdb->prepare("
				SELECT SUM(omq.`meta_value`) AS qty 
				FROM `{$wpdb->prefix}woocommerce_order_items` oi			
				LEFT JOIN `$wpdb->order_itemmeta` omq ON omq.`order_item_id` = oi.`order_item_id`
				LEFT JOIN `$wpdb->order_itemmeta` omp ON omp.`order_item_id` = oi.`order_item_id`			  
				WHERE `order_id` IN (
					SELECT `ID` FROM $wpdb->posts WHERE `post_type` = 'shop_order' AND `post_status` IN ('wc-pending', 'wc-on-hold')
				)
				AND omq.`meta_key` = '_qty' AND `order_item_type` = 'line_item' AND omp.`meta_key` = %s AND omp.`meta_value` = %d 
				GROUP BY omp.`meta_value`",
				$product_id_key,
				$this->product->get_id()
			);

			$stock_on_hold = wc_stock_amount( $wpdb->get_var( $sql ) ); // WPCS: unprepared SQL ok.
			$this->increase_total( 'calc_hold', $stock_on_hold );

		}

		return apply_filters( 'atum/stock_central_list/column_stock_hold', $stock_on_hold, $item, $this->product );
	}

	/**
	 * Column for reserved stock: sums the items within "Reserved Stock" logs
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int
	 */
	protected function column_calc_reserved( $item ) {

		$reserved_stock = ! $this->allow_calcs ? self::EMPTY_COL : $this->get_log_item_qty( 'reserved-stock', $this->product->get_id() );
		$this->increase_total( 'calc_reserved', $reserved_stock );

		return apply_filters( 'atum/stock_central_list/column_reserved_stock', $reserved_stock, $item, $this->product );
	}
	
	/**
	 * Column for back orders amount: show amount if items pending to serve and without existences
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int|string
	 */
	protected function column_calc_back_orders( $item ) {

		$back_orders = self::EMPTY_COL;

		if ( $this->allow_calcs ) {

			$back_orders = '--';
			if ( $this->product->backorders_allowed() ) {

				// TODO: threshold recalc if needed.
				$stock_quantity = $this->product->get_stock_quantity();
				$back_orders    = 0;
				if ( $stock_quantity < $this->no_stock ) {
					$back_orders = $this->no_stock - $stock_quantity;
				}

			}

			$this->increase_total( 'calc_back_orders', $back_orders );

		}
		
		return apply_filters( 'atum/stock_central_list/column_back_orders', $back_orders, $item, $this->product );
		
	}
	
	/**
	 * Column for items sold today
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int
	 */
	protected function column_calc_sold_today( $item ) {

		if ( ! $this->allow_calcs ) {
			$sold_today = self::EMPTY_COL;
		}
		else {
			$sold_today = empty( $this->calc_columns[ $this->product->get_id() ]['sold_today'] ) ? 0 : $this->calc_columns[ $this->product->get_id() ]['sold_today'];
		}

		$this->increase_total( 'calc_sold_today', $sold_today );
		
		return apply_filters( 'atum/stock_central_list/column_sold_today', $sold_today, $item, $this->product );
		
	}

	/**
	 * Column for customer returns: sums the items within "Reserved Stock" logs
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int
	 */
	protected function column_calc_returns( $item ) {

		$consumer_returns = ! $this->allow_calcs ? self::EMPTY_COL : $this->get_log_item_qty( 'customer-returns', $this->product->get_id() );
		$this->increase_total( 'calc_returns', $consumer_returns );

		return apply_filters( 'atum/stock_central_list/column_cutomer_returns', $consumer_returns, $item, $this->product );
	}

	/**
	 * Column for warehouse damages: sums the items within "Warehouse Damage" logs
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int
	 */
	protected function column_calc_damages( $item ) {

		$warehouse_damages = ! $this->allow_calcs ? self::EMPTY_COL : $this->get_log_item_qty( 'warehouse-damage', $this->product->get_id() );
		$this->increase_total( 'calc_damages', $warehouse_damages );

		return apply_filters( 'atum/stock_central_list/column_warehouse_damage', $warehouse_damages, $item, $this->product );
	}

	/**
	 * Column for lost in post: sums the items within "Lost in Post" logs
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int
	 */
	protected function column_calc_lost_in_post( $item ) {

		$lost_in_post = ! $this->allow_calcs ? self::EMPTY_COL : $this->get_log_item_qty( 'lost-in-post', $this->product->get_id() );
		$this->increase_total( 'calc_lost_in_post', $lost_in_post );

		return apply_filters( 'atum/stock_central_list/column_lost_in_post', $lost_in_post, $item, $this->product );
	}

	/**
	 * Column for items sold during the last N days (set on atum's general settings) sales_last_ndays or via jquery ?sold_last_days=N
	 *
	 * @since 1.4.11
	 *
	 * @param \WP_Post $item         The WooCommerce product post to use in calculations.
	 * @param bool     $add_to_total
	 *
	 * @return int
	 */
	protected function column_calc_sales_last_ndays( $item, $add_to_total = TRUE ) {

		if ( ! $this->allow_calcs ) {
			$sales_last_ndays = self::EMPTY_COL;
		}
		else {

			$sales_last_ndays = empty( $this->calc_columns[ $this->product->get_id() ]['sold_last_ndays'] ) ? 0 : $this->calc_columns[ $this->product->get_id() ]['sold_last_ndays'];
			if ( $add_to_total ) {
				$this->increase_total( 'calc_sales_last_ndays', $sales_last_ndays );
			}

		}

		return apply_filters( 'atum/stock_central_list/column_sold_last_ndays', $sales_last_ndays, $item, $this->product );

	}
	
	/**
	 * Column for number of days the stock will be sufficient to fulfill orders
	 * Formula: Current Stock Value / (Sales Last N Days / N)
	 *
	 * @since 0.1.3
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int|string
	 */
	protected function column_calc_will_last( $item ) {

		$sold_last_days = Helpers::get_sold_last_days_option();
			
		$will_last = self::EMPTY_COL;

		if ( $this->allow_calcs ) {
			$sales = $this->column_calc_sales_last_ndays( $item, FALSE );
			$stock = $this->product->get_stock_quantity();

			if ( $stock > 0 && $sales > 0 ) {
				$will_last = ceil( $stock / ( $sales / $sold_last_days ) );
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
	 * @since 0.1.4
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int|string
	 */
	protected function column_calc_stock_out_days( $item ) {

		$out_of_stock_days = '';

		if ( $this->allow_calcs ) {
			$out_of_stock_days = Helpers::get_product_out_of_stock_days( $this->product->get_id() );
		}

		$out_of_stock_days = is_numeric( $out_of_stock_days ) ? $out_of_stock_days : self::EMPTY_COL;
		
		return apply_filters( 'atum/stock_central_list/column_stock_out_days', $out_of_stock_days, $item, $this->product );
		
	}

	/**
	 * Column for lost sales
	 *
	 * @since 1.2.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int|string
	 */
	protected function column_calc_lost_sales( $item ) {

		$lost_sales = '';

		if ( $this->allow_calcs ) {
			$lost_sales = Helpers::get_product_lost_sales( $this->product->get_id() );
		}

		$lost_sales = is_numeric( $lost_sales ) ? Helpers::format_price( $lost_sales, [ 'trim_zeros' => TRUE ] ) : self::EMPTY_COL;
		$this->increase_total( 'calc_lost_sales', $lost_sales );

		return apply_filters( 'atum/stock_central_list/column_lost_sales', $lost_sales, $item, $this->product );

	}
	
	/**
	 * Prepare the table data
	 *
	 * @since 0.0.2
	 */
	public function prepare_items() {

		parent::prepare_items();
		$calc_products = array_merge( $this->current_products, $this->children_products );

		// Calc products sold today (since midnight).
		$rows = Helpers::get_sold_last_days( $calc_products, 'today 00:00:00', $this->day );
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_today'] = $row['QTY'];
			}
		}

		// Calc products sold during the last ndays_settings.
		$sold_last_days = Helpers::get_sold_last_days_option();
		$rows           = Helpers::get_sold_last_days( $calc_products, $this->day . ' -' . $sold_last_days . ' days', $this->day );
		
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_last_ndays'] = $row['QTY'];
			}
		}

		do_action( 'atum/stock_central_list/after_prepare_items', $calc_products, $this->day );
		
	}

	/**
	 * Get the Inventory Log item quantity for a specific type of log
	 *
	 * @since 1.2.4
	 *
	 * @param string $log_type   Type of log.
	 * @param int    $item_id    Item (WC Product) ID to check.
	 * @param string $log_status Optional. Log status (completed or pending).
	 *
	 * @return int
	 */
	protected function get_log_item_qty( $log_type, $item_id, $log_status = 'pending' ) {

		$qty     = 0;
		$log_ids = Helpers::get_logs( $log_type, $log_status );

		if ( ! empty( $log_ids ) ) {

			global $wpdb;

			foreach ( $log_ids as $log_id ) {

				// Get the _qty meta for the specified product in the specified log.
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
				); // WPCS: unprepared SQL ok.

				$qty += $wpdb->get_var( $query ); // WPCS: unprepared SQL ok.

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
	public function do_extra_filter( $query ) {

		// Avoid calling the "pre_get_posts" again when querying orders.
		if ( 'product' !== $query->query_vars['post_type'] ) {
			return;
		}

		if ( ! empty( $query->query_vars['post__in'] ) ) {
			return;
		}

		global $wpdb;
		$extra_filter = esc_attr( $_REQUEST['extra_filter'] ); // WPCS: CSRF ok.
		$sorted       = FALSE;

		$extra_filter_transient = Helpers::get_transient_identifier( $extra_filter, 'list_table_extra_filter' );
		$filtered_products      = Helpers::get_transient( $extra_filter_transient );

		if ( empty( $filtered_products ) ) {

			switch ( $extra_filter ) {

				case 'inbound_stock':
					// Get all the products within pending Purchase Orders.
					$sql = $wpdb->prepare( "
						SELECT product_id, SUM(qty) AS qty FROM (
							SELECT MAX(CAST(omp.`meta_value` AS SIGNED)) AS product_id, omq.`meta_value` AS qty 
							FROM `{$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` oi			
							LEFT JOIN `$wpdb->atum_order_itemmeta` omq ON omq.`order_item_id` = oi.`order_item_id`
							LEFT JOIN `$wpdb->atum_order_itemmeta` omp ON omp.`order_item_id` = oi.`order_item_id`			  
							WHERE `order_id` IN (
								SELECT `ID` FROM `$wpdb->posts` WHERE `post_type` = %s AND `post_status` = 'atum_pending'
							)
							AND omq.`meta_key` = '_qty' AND `order_item_type` = 'line_item' AND omp.`meta_key` IN ('_product_id', '_variation_id' ) 
							GROUP BY oi.order_item_id
						) AS T 
						GROUP BY product_id
						ORDER by qty DESC;",
						PurchaseOrders::POST_TYPE
					); // WPCS: unprepared SQL ok.

					$product_results = $wpdb->get_results( $sql, OBJECT_K ); // WPCS: unprepared SQL ok.

					if ( ! empty( $product_results ) ) {

						array_walk( $product_results, function ( &$item ) {
							$item = $item->qty;
						} );

						$filtered_products = $product_results;
						$sorted            = TRUE;

					}

					break;

				case 'stock_on_hold':
					$sql = "
						SELECT product_id, SUM(qty) AS qty FROM (
							SELECT  MAX(CAST(omp.`meta_value` AS SIGNED)) AS product_id, omq.`meta_value` AS qty FROM `{$wpdb->prefix}woocommerce_order_items` oi			
							LEFT JOIN `$wpdb->order_itemmeta` omq ON omq.`order_item_id` = oi.`order_item_id`
							LEFT JOIN `$wpdb->order_itemmeta` omp ON omp.`order_item_id` = oi.`order_item_id`			  
							WHERE `order_id` IN (
								SELECT `ID` FROM `$wpdb->posts` WHERE `post_type` = 'shop_order' AND `post_status` IN ('wc-pending', 'wc-on-hold')
							)
							AND omq.`meta_key` = '_qty' AND `order_item_type` = 'line_item'
							AND (omp.`meta_key` IN ('_product_id', '_variation_id' )) 
							GROUP BY oi.`order_item_id`
						) AS T 
						GROUP BY product_id
						ORDER BY qty DESC;
					";

					$product_results = $wpdb->get_results( $sql, OBJECT_K ); // WPCS: unprepared SQL ok.

					if ( ! empty( $product_results ) ) {

						array_walk( $product_results, function ( &$item ) {
							$item = $item->qty;
						} );

						$filtered_products = $product_results;
						$sorted            = TRUE;

					}

					break;

				case 'reserved_stock':
					// Get all the products within 'Reserved Stock' logs.
					$filtered_products = $this->get_log_products( 'reserved-stock', 'pending' );
					break;

				case 'back_orders':
					// Avoid infinite loop of recalls.
					remove_action( 'pre_get_posts', array( $this, 'do_extra_filter' ) );

					// Get all the products that allow back orders.
					$args     = array(
						'post_type'      => 'product',
						'posts_per_page' => - 1,
						'meta_key'       => '_backorders',
						'meta_value'     => 'yes',
					);
					$products = get_posts( $args );

					foreach ( $products as $product ) {

						$wc_product     = wc_get_product( $product->ID );
						$back_orders    = 0;
						$stock_quantity = $wc_product->get_stock_quantity();

						if ( $stock_quantity < $this->no_stock ) {
							$back_orders = $this->no_stock - $stock_quantity;
						}

						if ( $back_orders ) {
							$filtered_products[ $wc_product->get_id() ] = $back_orders;
						}

					}

					// Re-add the action.
					add_action( 'pre_get_posts', array( $this, 'do_extra_filter' ) );

					break;

				case 'sold_today':
					// Get the orders processed today.
					$atts = array(
						'status'     => [ 'wc-processing', 'wc-completed' ],
						'date_start' => 'today 00:00:00',
					);

					$today_orders = Helpers::get_orders( $atts );

					foreach ( $today_orders as $today_order ) {

						/**
						 * Variable definition
						 *
						 * @var \WC_Order $today_order
						 */
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
					// Get all the products within 'Customer Returns' logs.
					$filtered_products = $this->get_log_products( 'customer-returns', 'pending' );
					break;

				case 'warehouse_damages':
					// Get all the products within 'Warehouse Damage' logs.
					$filtered_products = $this->get_log_products( 'warehouse-damage', 'pending' );
					break;

				case 'lost_in_post':
					// Get all the products within 'Lost in Post' logs.
					$filtered_products = $this->get_log_products( 'lost-in-post', 'pending' );
					break;

			}

			// Allow extra filters to be added externally.
			$filtered_products = apply_filters( 'atum/stock_central_list/extra_filter_products', $filtered_products, $extra_filter );

			if ( ! empty( $filtered_products ) ) {

				// Order desc by quantity and get the ordered IDs.
				if ( ! $sorted ) {
					arsort( $filtered_products );
				}

				$filtered_products = array_keys( $filtered_products );
			}

			// Set the transient to expire in 60 seconds.
			Helpers::set_transient( $extra_filter_transient, $filtered_products, 60 );

		}

		// Filter the query posts by these IDs.
		if ( ! empty( $filtered_products ) ) {

			$query->set( 'post__in', $filtered_products );
			$query->set( 'orderby', 'post__in' );

		}
		// Force no results ("-1" never will be a post ID).
		else {
			$query->set( 'post__in', [ -1 ] );
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
	protected function get_log_products( $log_type, $log_status = '' ) {

		$log_types = array_keys( Log::get_log_types() );

		if ( ! in_array( $log_type, $log_types ) ) {
			return FALSE;
		}

		$log_ids  = Helpers::get_logs( $log_type, $log_status );
		$products = array();

		if ( ! empty( $log_ids ) ) {

			foreach ( $log_ids as $log_id ) {

				$log       = new Log( $log_id );
				$log_items = $log->get_items();

				if ( ! empty( $log_items ) ) {

					foreach ( $log_items as $log_item ) {

						if ( ! is_a( $log_item, '\Atum\InventoryLogs\Items\LogItemProduct' ) ) {
							continue;
						}

						$qty          = $log_item->get_quantity();
						$variation_id = $log_item->get_variation_id();
						$product_id   = $variation_id ?: $log_item->get_product_id();

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

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 1.4.10
	 */
	public function no_items() {

		parent::no_items();

		// Do not add the message when filtering.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Makes no sense to show the message if there are no products in the shop.
		global $wpdb;
		$product_count_sql = "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'product' AND post_status != 'trash'";

		if ( 0 === absint( $wpdb->get_var( $product_count_sql ) ) ) { // WPCS: unprepared SQL ok.
			return;
		}

		// Display an alert to ask the user to control all the product at once.
		?>
		<div class="alert alert-primary">
			<h3>
				<i class="dashicons dashicons-megaphone"></i>
				<?php esc_attr_e( "Your products need to be set as 'Controlled by ATUM' to appear here", ATUM_TEXT_DOMAIN ) ?>
			</h3>

			<p><?php esc_attr_e( 'You can do it in 3 ways:', ATUM_TEXT_DOMAIN ) ?></p>

			<ol>
				<li><?php _e( "Using the <strong>ATUM Control Switch</strong> that you'll find in every product <strong>edit</strong> page within the <strong>Product Data</strong> section. It may take a lot of time as this is per product edit.", ATUM_TEXT_DOMAIN ); // WPCS: XSS ok. ?></li>
				<li><?php _e( "Going to the <strong>Uncontrolled</strong> list using the above button (<strong>Show Uncontrolled</strong>).<br>You can select all products you'd like to take control of, open the bulk action drop-down and press <strong>Enable ATUM's Stock Control</strong> option.", ATUM_TEXT_DOMAIN ); // WPCS: XSS ok. ?></li>
				<li>
					<?php
					/* translators: first one is the button html tag and second is the closing tag */
					printf( __( 'We can add all your products at once! Just click the button below. If you change your mind later, you can revert the action by using the <code>ATUM Settings menu > Tools</code>.<br>%1$sControl all my products%2$s', ATUM_TEXT_DOMAIN ), '<button class="btn btn-sm btn-secondary" id="control-all-products" data-nonce="' . wp_create_nonce( 'atum-control-all-products-nonce' ) . '">', '</button>' ); // WPCS: XSS ok.
					?>
				</li>
			</ol>
		</div>
		<?php

	}
	
}
