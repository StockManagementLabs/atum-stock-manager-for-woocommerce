<?php
/**
 * Stock Central List Table
 *
 * @package         Atum\StockCentral
 * @subpackage      Lists
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           0.0.1
 */

namespace Atum\StockCentral\Lists;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumListTables\AtumListTable;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\Items\LogItemProduct;
use Atum\InventoryLogs\Models\Log;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Settings\Settings;


class ListTable extends AtumListTable {

	/**
	 * The columns hidden by default
	 *
	 * @var array
	 */
	protected static $default_hidden_columns = array(
		'ID',
		'_weight',
		'_inbound_stock',
		'_stock_on_hold',
		'_reserved_stock',
		'calc_back_orders',
		'_sold_today',
		'_customer_returns',
		'_warehouse_damage',
		'_lost_in_post',
		'_other_logs',
		'_sales_last_days',
		'calc_will_last',
		'_out_stock_days',
		'_lost_sales',
	);

	/**
	 * What columns are numeric and searchable? and strings? append to this two keys
	 *
	 * @var array
	 */
	protected $searchable_columns = array(
		'string'  => array(
			'title',
			'_supplier',
			'_sku',
			'_supplier_sku',
			'IDs', // ID as string to allow the use of commas ex: s = '12, 13, 89'.
		),
		'numeric' => array(
			'ID',
			'_regular_price',
			'_sale_price',
			'_purchase_price',
			'_weight',
			'_stock',
			'_out_stock_threshold',
			'_inbound_stock',
			'_stock_on_hold',
			'_reserved_stock',
			'_sold_today',
			'_customer_returns',
			'_warehouse_damage',
			'_lost_in_post',
			'_other_logs',
			'_sales_last_days',
			'_out_stock_days',
			'_lost_sales',
		),
	);

	/**
	 * Set up the ATUM columns and types for correct sorting
	 *
	 * @var array
	 */
	protected $atum_sortable_columns = array(
		'_purchase_price'      => array(
			'type'  => 'NUMERIC',
			'field' => 'purchase_price',
		),
		'_supplier'            => array(
			'type'  => 'NUMERIC',
			'field' => 'supplier_id',
		),
		'_supplier_sku'        => array(
			'type'  => '',
			'field' => 'supplier_sku',
		),
		'_out_stock_threshold' => array(
			'type'  => 'NUMERIC',
			'field' => 'out_stock_threshold',
		),
		'_inbound_stock'       => array(
			'type'  => 'NUMERIC',
			'field' => 'inbound_stock',
		),
		'_stock_on_hold'       => array(
			'type'  => 'NUMERIC',
			'field' => 'stock_on_hold',
		),
		'_reserved_stock'      => array(
			'type'  => 'NUMERIC',
			'field' => 'reserved_stock',
		),
		'_sold_today'          => array(
			'type'  => 'NUMERIC',
			'field' => 'sold_today',
		),
		'_customer_returns'    => array(
			'type'  => 'NUMERIC',
			'field' => 'customer_returns',
		),
		'_warehouse_damage'    => array(
			'type'  => 'NUMERIC',
			'field' => 'warehouse_damage',
		),
		'_lost_in_post'        => array(
			'type'  => 'NUMERIC',
			'field' => 'lost_in_post',
		),
		'_other_logs'          => array(
			'type'  => 'NUMERIC',
			'field' => 'other_logs',
		),
		'_sales_last_days'     => array(
			'type'  => 'NUMERIC',
			'field' => 'sales_last_days',
		),
		'_out_stock_days'      => array(
			'type'  => 'NUMERIC',
			'field' => 'out_stock_days',
		),
		'_lost_sales'          => array(
			'type'  => 'NUMERIC',
			'field' => 'lost_sales',
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
	 *      @type bool   $show_cb           Optional. Whether to show the row selector checkbox as first table column.
	 *      @type bool   $show_controlled   Optional. Whether to show items controlled by ATUM or not.
	 *      @type int    $per_page          Optional. The number of posts to show per page (-1 for no pagination).
	 *      @type array  $selected          Optional. The posts selected on the list table.
	 *      @type array  $excluded          Optional. The posts excluded from the list table.
	 * }
	 */
	public function __construct( $args = array() ) {
		
		// Activate managed/unmanaged counters separation.
		$this->show_unmanaged_counters = 'yes' === Helpers::get_option( 'unmanaged_counters' );

		$this->days_to_reorder = absint( Helpers::get_option( 'sale_days', Settings::DEFAULT_SALE_DAYS ) );

		// Prepare the table columns.
		self::$table_columns = self::get_table_columns();

		// By default, we have no actions for SC but we have to add the ability to add them externally.
		self::$row_actions = (array) apply_filters( 'atum/stock_central_list/row_actions', [] );

		// TODO: Add group table functionality if some columns are hidden.
		$this->group_members = (array) apply_filters( 'atum/stock_central_list/column_group_members', array(
			'product-details'       => array(
				'title'   => __( 'Product Details', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'thumb',
					'ID',
					'title',
					'calc_type',
					'_sku',
					'_supplier',
					'_supplier_sku',
					'calc_location',
					'_regular_price',
					'_sale_price',
					'_purchase_price',
					'calc_gross_profit',
					'_weight',
				),
			),
			'stock-counters'        => array(
				'title'   => __( 'Stock Counters', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'_stock',
					'_out_stock_threshold',
					'_inbound_stock',
					'_stock_on_hold',
					'_reserved_stock',
					'calc_back_orders',
					'_sold_today',
				),
			),
			'stock-negatives'       => array(
				'title'   => __( 'Stock Negatives', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'_customer_returns',
					'_warehouse_damage',
					'_lost_in_post',
					'_other_logs',
				),
			),
			'stock-selling-manager' => array(
				'title'   => __( 'Stock Selling Manager', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'_sales_last_days',
					'calc_will_last',
					'_out_stock_days',
					'_lost_sales',
					'calc_stock_indicator',
				),
			),
		) );

		// Hide the purchase price and the gross profit columns if the current user has not the capability.
		if ( ! AtumCapabilities::current_user_can( 'view_purchase_price' ) ) {
			$this->group_members['product-details']['members'] = array_diff( $this->group_members['product-details']['members'], [ '_purchase_price', 'calc_gross_profit' ] );
		}

		// Hide the supplier's columns if the current user has not the capability.
		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) || ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			$this->group_members['product-details']['members'] = array_diff( $this->group_members['product-details']['members'], [ '_sku', '_supplier_sku' ] );
		}

		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) ) {
			$this->group_members['product-details']['members'] = array_diff( $this->group_members['product-details']['members'], [ '_purchase_price', 'calc_gross_profit' ] );
			$this->group_members['stock-counters']['members']  = array_diff( $this->group_members['stock-counters']['members'], [ '_inbound_stock' ] );
		}

		// Initialize totalizers.
		$this->totalizers = apply_filters( 'atum/stock_central_list/totalizers', array(
			'_stock'            => 0,
			'_inbound_stock'    => 0,
			'_stock_on_hold'    => 0,
			'_reserved_stock'   => 0,
			'calc_back_orders'  => 0,
			'_sold_today'       => 0,
			'_customer_returns' => 0,
			'_warehouse_damage' => 0,
			'_lost_in_post'     => 0,
			'_other_logs'       => 0,
			'_sales_last_days'  => 0,
			'_lost_sales'       => 0,
		) );

		// Set the sticky columns.
		$this->sticky_columns = (array) apply_filters( 'atum/stock_central_list/sticky_columns', array(
			'cb',
			'thumb',
			'ID',
			'title',
			'calc_type',
		) );

		$this->searchable_columns = apply_filters( 'atum/stock_central_list/searchable_columns', $this->searchable_columns );

		// Call the parent class once all the $args are set.
		parent::__construct( $args );

		// Filtering with extra filters.
		if ( ! empty( $_REQUEST['extra_filter'] ) ) {
			add_action( 'pre_get_posts', array( $this, 'do_extra_filter' ) );
		}

	}

	/**
	 * Prepare the table columns for Stock Central
	 *
	 * @since 1.4.16
	 *
	 * @return array
	 */
	public static function get_table_columns() {

		// Can be called statically so it should need the sale_days before initializing the list.
		self::$sale_days = Helpers::get_sold_last_days_option();

		// NAMING CONVENTION: The column names starting by underscore (_) are based on meta keys (the name must match the meta key name),
		// the column names starting with "calc_" are calculated fields and the rest are WP's standard fields
		// *** Following this convention is necessary for column sorting functionality ***!
		$table_columns = array(
			'thumb'                => '<span class="atum-icon atmi-picture tips" data-bs-placement="bottom" data-tip="' . esc_attr__( 'Image', ATUM_TEXT_DOMAIN ) . '">' . esc_attr__( 'Image', ATUM_TEXT_DOMAIN ) . '</span>',
			'ID'                   => __( 'ID', ATUM_TEXT_DOMAIN ),
			'title'                => __( 'Product Name', ATUM_TEXT_DOMAIN ),
			'calc_type'            => '<span class="atum-icon atmi-tag tips" data-bs-placement="bottom" data-tip="' . esc_attr__( 'Product Type', ATUM_TEXT_DOMAIN ) . '">' . esc_attr__( 'Product Type', ATUM_TEXT_DOMAIN ) . '</span>',
			'_sku'                 => __( 'SKU', ATUM_TEXT_DOMAIN ),
			'_supplier'            => __( 'Supplier', ATUM_TEXT_DOMAIN ),
			'_supplier_sku'        => __( 'Supplier SKU', ATUM_TEXT_DOMAIN ),
			'calc_location'        => '<span class="atum-icon atmi-map-marker tips" data-bs-placement="bottom" data-tip="' . esc_attr__( 'Location', ATUM_TEXT_DOMAIN ) . '">' . esc_attr__( 'Location', ATUM_TEXT_DOMAIN ) . '</span>',
			'_regular_price'       => __( 'Regular Price', ATUM_TEXT_DOMAIN ),
			'_sale_price'          => __( 'Sale Price', ATUM_TEXT_DOMAIN ),
			'_purchase_price'      => __( 'Purchase Price', ATUM_TEXT_DOMAIN ),
			'calc_gross_profit'    => __( 'Gross Profit', ATUM_TEXT_DOMAIN ),
			'_weight'              => __( 'Weight', ATUM_TEXT_DOMAIN ),
			'_stock'               => __( 'Current Stock', ATUM_TEXT_DOMAIN ),
			'_out_stock_threshold' => __( 'Out of Stock Threshold', ATUM_TEXT_DOMAIN ),
			'_inbound_stock'       => __( 'Inbound Stock', ATUM_TEXT_DOMAIN ),
			'_stock_on_hold'       => __( 'Stock on Hold', ATUM_TEXT_DOMAIN ),
			'_reserved_stock'      => __( 'Reserved Stock', ATUM_TEXT_DOMAIN ),
			'calc_back_orders'     => __( 'Back Orders', ATUM_TEXT_DOMAIN ),
			'_sold_today'          => __( 'Sold Today', ATUM_TEXT_DOMAIN ),
			'_customer_returns'    => __( 'Customer Returns', ATUM_TEXT_DOMAIN ),
			'_warehouse_damage'    => __( 'Warehouse Damages', ATUM_TEXT_DOMAIN ),
			'_lost_in_post'        => __( 'Lost in Post', ATUM_TEXT_DOMAIN ),
			'_other_logs'          => __( 'Other Logs', ATUM_TEXT_DOMAIN ),
			/* translators: the number of sales during last days */
			'_sales_last_days'     => sprintf( _n( 'Sales last %s day', 'Sales last %s days', self::$sale_days, ATUM_TEXT_DOMAIN ), '<span class="set-header" id="sales_last_ndays_val" title="' . __( 'Click to change days', ATUM_TEXT_DOMAIN ) . '">' . self::$sale_days . '</span>' ),
			'calc_will_last'       => __( 'Stock will Last (Days)', ATUM_TEXT_DOMAIN ),
			'_out_stock_days'      => __( 'Out of Stock for (Days)', ATUM_TEXT_DOMAIN ),
			'_lost_sales'          => __( 'Lost Sales', ATUM_TEXT_DOMAIN ),
			'calc_stock_indicator' => '<span class="atum-icon atmi-layers stock-indicator-icon tips" data-bs-placement="bottom" data-tip="' . esc_attr__( 'Stock Indicator', ATUM_TEXT_DOMAIN ) . '">' . esc_attr__( 'Stock Indicator', ATUM_TEXT_DOMAIN ) . '</span>',
		);

		// Hide the purchase price and gross profit columns if the current user has not the capability.
		if ( ! AtumCapabilities::current_user_can( 'view_purchase_price' ) ) {
			unset( $table_columns['_purchase_price'], $table_columns['calc_gross_profit'] );
		}

		// Hide the supplier's columns if the current user has not the capability.
		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) || ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			unset( $table_columns['_supplier'], $table_columns['_supplier_sku'] );
		}

		// Hide the purchase price and gross profit columns if the purchase orders module is disabled.
		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) ) {
			unset( $table_columns['_purchase_price'], $table_columns['_inbound_stock'], $table_columns['calc_gross_profit'] );
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
			'other'             => __( 'Other', ATUM_TEXT_DOMAIN ),
			'best_seller'       => __( 'Best Sellers', ATUM_TEXT_DOMAIN ),
			'worst_seller'      => __( 'Worst Sellers', ATUM_TEXT_DOMAIN ),
		));

		$no_auto_filter = array(
			'best_seller',
			'worst_seller',
		);

		?>
		<select name="extra_filter" class="wc-enhanced-select atum-enhanced-select dropdown_extra_filter auto-filter date-selector" autocomplete="off">
			<option value=""><?php esc_attr_e( 'Extra Filters', ATUM_TEXT_DOMAIN ) ?></option>

			<?php foreach ( $extra_filters as $extra_filter => $label ) : ?>
				<option value="<?php echo esc_attr( $extra_filter ) ?>"
					<?php selected( ! empty( $_REQUEST['extra_filter'] ) && $_REQUEST['extra_filter'] === $extra_filter, TRUE ); ?>
					<?php if ( in_array( $extra_filter, $no_auto_filter, TRUE ) ) echo ' data-auto-filter="no"' ?>
				><?php echo esc_attr( $label ) ?></option>
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
	 * Column for stock on hold: show amount of items with pending payment.
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int
	 */
	protected function column__stock_on_hold( $item ) {

		$stock_on_hold = self::EMPTY_COL;

		if ( $this->allow_calcs ) {
			$stock_on_hold = Helpers::get_product_stock_on_hold( $this->product );
			$this->increase_total( '_stock_on_hold', $stock_on_hold );
		}

		return apply_filters( 'atum/stock_central_list/column_stock_hold', $stock_on_hold, $item, $this->product, $this );

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
	protected function column__reserved_stock( $item ) {

		$reserved_stock = ! $this->allow_calcs ? self::EMPTY_COL : Helpers::get_log_item_qty( 'reserved-stock', $this->product );
		$this->increase_total( '_reserved_stock', $reserved_stock );

		return apply_filters( 'atum/stock_central_list/column_reserved_stock', $reserved_stock, $item, $this->product, $this );
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
	protected function column__sold_today( $item ) {

		if ( ! $this->allow_calcs ) {
			$sold_today = self::EMPTY_COL;
		}
		else {

			$sold_today = $this->product->get_sold_today();

			if ( is_null( $sold_today ) || Helpers::is_product_data_outdated( $this->product ) ) {
				$sold_today = Helpers::get_sold_last_days( 'today midnight', $this->day, $this->product->get_id() );
				$this->product->set_sold_today( $sold_today );
				$timestamp = Helpers::get_current_timestamp();
				$this->product->set_update_date( $timestamp ); // This will force the update even when the values didn't chnage.
			}

		}

		$this->increase_total( '_sold_today', $sold_today );
		
		return apply_filters( 'atum/stock_central_list/column_sold_today', $sold_today, $item, $this->product, $this );
		
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
	protected function column__customer_returns( $item ) {

		$consumer_returns = ! $this->allow_calcs ? self::EMPTY_COL : Helpers::get_log_item_qty( 'customer-returns', $this->product );
		$this->increase_total( '_customer_returns', $consumer_returns );

		return apply_filters( 'atum/stock_central_list/column_customer_returns', $consumer_returns, $item, $this->product, $this );
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
	protected function column__warehouse_damage( $item ) {

		$warehouse_damages = ! $this->allow_calcs ? self::EMPTY_COL : Helpers::get_log_item_qty( 'warehouse-damage', $this->product );
		$this->increase_total( '_warehouse_damage', $warehouse_damages );

		return apply_filters( 'atum/stock_central_list/column_warehouse_damage', $warehouse_damages, $item, $this->product, $this );
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
	protected function column__lost_in_post( $item ) {

		$lost_in_post = ! $this->allow_calcs ? self::EMPTY_COL : Helpers::get_log_item_qty( 'lost-in-post', $this->product );
		$this->increase_total( '_lost_in_post', $lost_in_post );

		return apply_filters( 'atum/stock_central_list/column_lost_in_post', $lost_in_post, $item, $this->product, $this );
	}

	/**
	 * Column for other: sums the items within "Other" logs
	 *
	 * @since 1.1.1.2
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int
	 */
	protected function column__other_logs( $item ) {

		$others = ! $this->allow_calcs ? self::EMPTY_COL : Helpers::get_log_item_qty( 'other', $this->product );
		$this->increase_total( '_other_logs', $others );

		return apply_filters( 'atum/stock_central_list/column_other_logs', $others, $item, $this->product, $this );
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
	protected function column__sales_last_days( $item, $add_to_total = TRUE ) {

		$sales_last_ndays = self::EMPTY_COL;

		if ( $this->allow_calcs ) {

			$sale_days        = self::$sale_days;
			$sales_last_ndays = $this->product->get_sales_last_days();

			if (
				is_null( $sales_last_ndays ) || Settings::DEFAULT_SALE_DAYS !== $sale_days ||
				Helpers::is_product_data_outdated( $this->product )
			) {

				$sales_last_ndays = Helpers::get_sold_last_days( "$this->day -$sale_days days", $this->day, $this->product->get_id() );
				$this->product->set_sales_last_days( $sales_last_ndays );
				$timestamp = Helpers::get_current_timestamp();
				$this->product->set_update_date( $timestamp ); // This will force the update even when the values didn't chnage.

			}

			if ( ! is_numeric( $sales_last_ndays ) ) {
				$sales_last_ndays = 0;
			}

			if ( $add_to_total ) {
				$this->increase_total( '_sales_last_days', $sales_last_ndays );
			}

		}

		return apply_filters( 'atum/stock_central_list/column_sales_last_days', $sales_last_ndays, $item, $this->product, $this );

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
			
		$will_last = self::EMPTY_COL;

		if ( $this->allow_calcs ) {
			$sales = $this->column__sales_last_days( $item, FALSE );
			$stock = $this->product->get_stock_quantity();

			if ( $stock > 0 && $sales > 0 ) {
				$sale_days = max( 1, self::$sale_days );
				$will_last = ceil( $stock / ( $sales / $sale_days ) );
			}
			elseif ( $stock > 0 ) {
				$will_last = '>30';
			}
		}
		
		return apply_filters( 'atum/stock_central_list/column_stock_will_last_days', $will_last, $item, $this->product, $this );
		
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
	protected function column__out_stock_days( $item ) {

		$out_stock_days = '';

		if ( $this->allow_calcs ) {

			$out_stock_days = $this->product->get_out_stock_days();

			if ( is_null( $out_stock_days ) || Helpers::is_product_data_outdated( $this->product ) ) {
				$out_stock_days = Helpers::get_product_out_stock_days( $this->product );
				$this->product->set_out_stock_days( $out_stock_days );
				$timestamp = Helpers::get_current_timestamp();
				$this->product->set_update_date( $timestamp ); // This will force the update even when the values didn't change.
			}

		}

		$out_stock_days = is_numeric( $out_stock_days ) ? $out_stock_days : self::EMPTY_COL;
		
		return apply_filters( 'atum/stock_central_list/column_out_stock_days', $out_stock_days, $item, $this->product, $this );
		
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
	protected function column__lost_sales( $item ) {

		$lost_sales = '';

		if ( $this->allow_calcs ) {

			$lost_sales = $this->product->get_lost_sales();

			if ( is_null( $lost_sales ) || Helpers::is_product_data_outdated( $this->product ) ) {
				$lost_sales = Helpers::get_product_lost_sales( $this->product );
				$this->product->set_lost_sales( $lost_sales );
				$timestamp = Helpers::get_current_timestamp();
				$this->product->set_update_date( $timestamp ); // This will force the update even when the values didn't chnage.
			}

		}

		$lost_sales = is_numeric( $lost_sales ) ? Helpers::format_price( $lost_sales ) : self::EMPTY_COL;
		$this->increase_total( '_lost_sales', $lost_sales );

		return apply_filters( 'atum/stock_central_list/column_lost_sales', $lost_sales, $item, $this->product, $this );

	}
	
	/**
	 * Prepare the table data
	 *
	 * @since 0.0.2
	 */
	public function prepare_items() {

		parent::prepare_items();
		$calc_products = array_unique( array_merge( $this->current_products, $this->children_products ) );

		do_action( 'atum/stock_central_list/after_prepare_items', $calc_products );
		
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
		$extra_filter = esc_attr( $_REQUEST['extra_filter'] );
		$sorted       = FALSE;

		$extra_filter_transient = AtumCache::get_transient_key( 'list_table_extra_filter', $extra_filter );
		$filtered_products      = AtumCache::get_transient( $extra_filter_transient );

		if ( empty( $filtered_products ) ) {

			switch ( $extra_filter ) {

				case 'best_seller':
					$dates_where = '';

					if ( isset( $_REQUEST['date_from'] ) && ! empty( $_REQUEST['date_from'] ) ) {
						$dates_where .= " AND posts.post_date >= '" . $_REQUEST['date_from'] . "' ";
					}

					if ( isset( $_REQUEST['date_from'] ) && ! empty( $_REQUEST['date_from'] ) ) {
						$dates_where .= " AND posts.post_date < '" . $_REQUEST['date_to'] . "' ";
					}

					$sql = "
						SELECT order_item_meta__product_id.meta_value as product_id, order_item_meta__variation_id.meta_value as variation_id,
						SUM( order_item_meta__qty.meta_value) as order_item_qty
						FROM $wpdb->posts AS posts 
						INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON (posts.ID = order_items.order_id) AND (order_items.order_item_type = 'line_item') 
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id ON (order_items.order_item_id = order_item_meta__product_id.order_item_id)  AND (order_item_meta__product_id.meta_key = '_product_id') 
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__variation_id ON (order_items.order_item_id = order_item_meta__variation_id.order_item_id)  AND (order_item_meta__variation_id.meta_key = '_variation_id') 
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__qty ON (order_items.order_item_id = order_item_meta__qty.order_item_id)  AND (order_item_meta__qty.meta_key = '_qty') 
						WHERE posts.post_type IN ( 'shop_order', 'shop_order_refund' )
						AND posts.post_status IN ( '" . implode( "','", Globals::get_order_statuses_change_stock() ) . "')
						$dates_where
						GROUP BY variation_id, product_id ORDER BY order_item_qty DESC
					";

					$product_results = $wpdb->get_results( $sql, OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					if ( ! empty( $product_results ) ) {

						$filtered_products = array();

						array_walk( $product_results, function ( &$item ) use ( &$filtered_products ) {

							// Add the variable products too, so the variations can be displayed.
							if ( $item->variation_id ) {
								$filtered_products[ $item->variation_id ] = $item->order_item_qty;
							}

							$item = $item->order_item_qty;

						} );

						$filtered_products = $product_results + $filtered_products;
						$sorted            = TRUE;

					}

					break;

				case 'worst_seller':
					$dates_where = '';

					if ( isset( $_REQUEST['date_from'] ) && ! empty( $_REQUEST['date_from'] ) ) {
						$dates_where .= ' AND ord.post_date >= "' . esc_attr( $_REQUEST['date_from'] ) . '" ';
					}

					if ( isset( $_REQUEST['date_from'] ) && ! empty( $_REQUEST['date_from'] ) ) {
						$dates_where .= ' AND ord.post_date < "' . esc_attr( $_REQUEST['date_to'] ) . '" ';
					}

					$sql = "
						SELECT pr.ID product_id, SUM(meta_qty.meta_value) qty
						FROM $wpdb->posts pr 
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta meta_pr_id ON (pr.ID = meta_pr_id.meta_value) AND (meta_pr_id.meta_key IN ('_product_id', '_variation_id') ) 
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta meta_qty ON ( meta_pr_id.order_item_id = meta_qty.order_item_id) AND (meta_qty.meta_key = '_qty') 
						LEFT JOIN {$wpdb->prefix}woocommerce_order_items order_items ON (meta_pr_id.order_item_id = order_items.order_item_id)
						LEFT JOIN $wpdb->posts ord ON (order_items.order_id = ord.ID)
						WHERE (pr.post_type IN ('product', 'product_variation')
						AND ord.post_status IN ( '" . implode( "','", Globals::get_order_statuses_change_stock() ) . "')
						$dates_where ) OR ord.post_date IS NULL
						GROUP BY product_id order by qty ASC;
					";

					$product_results = $wpdb->get_results( $sql, OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					if ( ! empty( $product_results ) ) {

						array_walk( $product_results, function ( &$item ) {
							$item = $item->qty;
						} );

						$filtered_products = $product_results;
						$sorted            = TRUE;

					}

					break;

				case 'inbound_stock':
					$statuses = array_diff( array_keys( PurchaseOrders::get_statuses() ), [ PurchaseOrders::FINISHED ] );

					// Get all the products within pending Purchase Orders.
					// phpcs:disable WordPress.DB.PreparedSQL
					$sql = $wpdb->prepare( "
						SELECT product_id, SUM(qty) AS qty FROM (
							SELECT MAX(CAST(omp.`meta_value` AS SIGNED)) AS product_id, omq.`meta_value` AS qty 
							FROM `{$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` oi			
							LEFT JOIN `$wpdb->atum_order_itemmeta` omq ON omq.`order_item_id` = oi.`order_item_id`
							LEFT JOIN `$wpdb->atum_order_itemmeta` omp ON omp.`order_item_id` = oi.`order_item_id`			  
							WHERE `order_id` IN (
								SELECT `ID` FROM `$wpdb->posts` WHERE `post_type` = %s AND 
								`post_status` IN ('" . implode( "','", $statuses ) . "')
							)
							AND omq.`meta_key` = '_qty' AND `order_item_type` = 'line_item' AND omp.`meta_key` IN ('_product_id', '_variation_id' ) 
							GROUP BY oi.order_item_id
						) AS T 
						GROUP BY product_id
						ORDER by qty DESC;",
						PurchaseOrders::POST_TYPE
					);
					// phpcs:enable

					$product_results = $wpdb->get_results( $sql, OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					if ( ! empty( $product_results ) ) {

						$filtered_products = array();

						array_walk( $product_results, function ( &$item ) use ( &$filtered_products ) {

							// Add the variable products too, so the variations can be displayed.
							$product = wc_get_product( $item->product_id );

							if ( $product instanceof \WC_Product && $product->is_type( 'variation' ) ) {

								$variable_id = $product->get_parent_id();

								if ( ! isset( $filtered_products[ $variable_id ] ) || ( isset( $filtered_products[ $variable_id ] ) && $filtered_products[ $variable_id ] < $item->qty ) ) {
									$filtered_products[ $variable_id ] = $item->qty;
								}

							}

							$item = $item->qty;

						} );

						$filtered_products += $product_results;

						arsort( $filtered_products );
						$sorted = TRUE;

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

					$product_results = $wpdb->get_results( $sql, OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					if ( ! empty( $product_results ) ) {

						$filtered_products = array();

						array_walk( $product_results, function ( &$item ) use ( &$filtered_products ) {

							// Add the variable products too, so the variations can be displayed.
							$product = wc_get_product( $item->product_id );

							if ( $product instanceof \WC_Product && $product->is_type( 'variation' ) ) {

								$variable_id = $product->get_parent_id();

								if ( ! isset( $filtered_products[ $variable_id ] ) || ( isset( $filtered_products[ $variable_id ] ) && $filtered_products[ $variable_id ] < $item->qty ) ) {
									$filtered_products[ $variable_id ] = $item->qty;
								}

							}

							$item = $item->qty;

						} );

						$filtered_products += $product_results;

						arsort( $filtered_products );
						$sorted = TRUE;

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
						'post_type'      => [ 'product', 'product_variation' ],
						'posts_per_page' => - 1,
						'meta_key'       => '_backorders',
						'meta_value'     => 'yes',
					);
					$products = get_posts( $args );

					foreach ( $products as $product ) {

						$wc_product = wc_get_product( $product->ID ); // We don't need to use the ATUM models here.

						if ( $wc_product instanceof \WC_Product ) {

							$stock_quantity = $wc_product->get_stock_quantity();

							$filtered_products[ $wc_product->get_id() ] = $stock_quantity;

							// Add the variable products, so the variations can be displayed.
							if ( $wc_product->is_type( 'variation' ) ) {

								$variable_id = $wc_product->get_parent_id();

								if ( ! isset( $filtered_products[ $variable_id ] ) || ( isset( $filtered_products[ $variable_id ] ) && $filtered_products[ $variable_id ] < $stock_quantity ) ) {
									$filtered_products[ $variable_id ] = $stock_quantity;
								}

							}

						}

					}

					// The backorders products better to be sorted ASC, so those with lower stock at top.
					asort( $filtered_products );
					$sorted = TRUE;

					// Re-add the action.
					add_action( 'pre_get_posts', array( $this, 'do_extra_filter' ) );

					break;

				case 'sold_today':
					// Get the orders processed today.
					$atts = array(
						'status'     => [ 'wc-processing', 'wc-completed' ],
						'date_start' => 'today midnight',
					);

					// Disable our custom filters to not alter the orders query.
					$has_atum_clauses_filter = $has_wc_clauses_filter = FALSE;

					if ( has_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) ) ) {
						remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
						$has_atum_clauses_filter = TRUE;
					}

					if ( has_filter( 'posts_clauses', array( $this, 'wc_product_data_query_clauses' ) ) ) {
						remove_filter( 'posts_clauses', array( $this, 'wc_product_data_query_clauses' ) );
						$has_wc_clauses_filter = TRUE;
					}

					$today_orders = Helpers::get_orders( $atts );

					// Re-add the filters if necessary.
					if ( $has_atum_clauses_filter ) {
						add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
					}

					if ( $has_wc_clauses_filter ) {
						add_filter( 'posts_clauses', array( $this, 'wc_product_data_query_clauses' ) );
					}

					foreach ( $today_orders as $today_order ) {

						/**
						 * Variable definition
						 *
						 * @var \WC_Order $today_order
						 */
						$products = $today_order->get_items();

						foreach ( $products as $product ) {

							$product_id = ! empty( $product['variation_id'] ) ? $product['variation_id'] : $product['product_id'];

							if ( isset( $filtered_products[ $product_id ] ) ) {
								$filtered_products[ $product_id ] += $product['qty'];
							}
							else {
								$filtered_products[ $product_id ] = $product['qty'];
							}

							// Add the variable products, so the variations can be displayed.
							if ( ! empty( $product['variation_id'] ) ) {

								if (
									! isset( $filtered_products[ $product['product_id'] ] ) ||
									( isset( $filtered_products[ $product['product_id'] ] ) && $filtered_products[ $product['product_id'] ] < $filtered_products[ $product_id ] )
								) {
									$filtered_products[ $product['product_id'] ] = $filtered_products[ $product_id ];
								}

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
				case 'other':
					// Get all the products within 'Other' logs.
					$filtered_products = $this->get_log_products( 'other', 'pending' );
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
			AtumCache::set_transient( $extra_filter_transient, $filtered_products, 60 );

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

						if ( ! $log_item instanceof LogItemProduct ) {
							continue;
						}

						$qty        = $log_item->get_quantity();
						$product_id = $log_item->get_variation_id() ?: $log_item->get_product_id();

						if ( $product_id ) {
							if ( isset( $products[ $product_id ] ) ) {
								$products[ $product_id ] += $qty;
							}
							else {
								$products[ $product_id ] = $qty;
							}
						}

						$log_item_product = $log_item->get_product();

						// Add the variable products, so the variations can be displayed.
						if ( $log_item_product instanceof \WC_Product && $log_item_product->is_type( 'variation' ) ) {

							$variable_id = $log_item_product->get_parent_id();

							if ( ! isset( $products[ $variable_id ] ) || ( isset( $products[ $variable_id ] ) && $products[ $variable_id ] < $qty ) ) {
								$products[ $variable_id ] = $qty;
							}

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
		if ( wp_doing_ajax() ) {
			return;
		}

		// Makes no sense to show the message if there are no products in the shop.
		global $wpdb;
		$product_count_sql = "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'product' AND post_status != 'trash'";

		// // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( 0 === absint( $wpdb->get_var( $product_count_sql ) ) ) {
			return;
		}

		// Display an alert to ask the user to control all the product at once.
		?>
		<div class="alert alert-primary">
			<h3>
				<i class="atum-icon atmi-bullhorn"></i>
				<?php esc_attr_e( "Your products need to be set as 'Controlled by ATUM' to appear here", ATUM_TEXT_DOMAIN ) ?>
			</h3>

			<p><?php esc_attr_e( 'You can do it in 3 ways:', ATUM_TEXT_DOMAIN ) ?></p>

			<ol>
				<li><?php _e( "Using the <strong>ATUM Control Switch</strong> that you'll find in every product <strong>edit</strong> page within the <strong>Product Data</strong> section. It may take a lot of time as this is per product edit.", ATUM_TEXT_DOMAIN ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
				<li><?php _e( "Going to the <strong>Uncontrolled</strong> list using the above button (<strong>Show Uncontrolled</strong>).<br>You can select all products you'd like to take control of, open the bulk action drop-down and press <strong>Enable ATUM's Stock Control</strong> option.", ATUM_TEXT_DOMAIN ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
				<li>
					<?php
					/* translators: first one is the button html tag and second is the closing tag */
					printf( __( 'We can add all your products at once! Just click the button below. If you change your mind later, you can revert the action by using the <code>ATUM Settings menu > Tools</code>.<br>%1$sControl all my products%2$s', ATUM_TEXT_DOMAIN ), '<button class="btn btn-secondary" id="control-all-products" data-nonce="' . wp_create_nonce( 'atum-control-all-products-nonce' ) . '">', '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</li>
			</ol>
		</div>
		<?php

	}
	
}
