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
use Atum\Settings\Settings;


class ListTable extends AtumListTable {
	
	/**
	 * Current product used
	 * @var \WC_Product
	 */
	protected $product;

	/**
	 * No stock Threshold
	 * @var int
	 */
	protected $no_stock;
	
	/**
	 * Day of consult
	 * @var date
	 */
	protected $day;
	
	/**
	 * Sale days from settings
	 * @var int
	 */
	protected $last_days;
	
	/**
	 * Values for the calculated columns form current page products
	 * @var array
	 */
	protected $calc_columns = array();
	
	/**
	 * Whether the currently displayed product is an expandable child product
	 * @var bool
	 */
	protected $is_child = FALSE;

	/**
	 * Whether or not the current product should do the calculations for the columns
	 * @var bool
	 */
	protected $allow_calcs = TRUE;

	/**
	 * The array of published Variable products' IDs
	 * @var array
	 */
	protected $variable_products = array();

	/**
	 * The array of published Grouped products' IDs
	 * @var array
	 */
	protected $grouped_products = array();

	/**
	 * Whether to load the jQuery UI datepicker script (for sale price dates)
	 * @var bool
	 */
	protected $load_datepicker = TRUE;

	/**
	 * Constructor
	 *
	 * The child class should call this constructor to override the default $args.
	 *
	 * @since 0.0.1
	 *
	 * @param array|string $args {
	 *      Optional. Array or serialized string of arguments.
	 *
	 *      @type array $selected   Optional. The posts selected on the list table
	 *      @type bool  $show_cb    Optional. Whether to show the row selector checkbox as first table column
	 *      @type int   $per_page   Optional. The number of posts to show per page (-1 for no pagination)
	 * }
	 */
	public function __construct( $args = array() ) {
		
		$this->no_stock = intval( get_option( 'woocommerce_notify_no_stock_amount' ) );
		
		// TODO: Allow to specify the day of query in constructor atts
		$this->day       = Helpers::date_format( current_time('timestamp'), TRUE );
		$this->last_days = absint( Helpers::get_option( 'sale_days', Settings::DEFAULT_SALE_DAYS ) );
		
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
	 * Extra controls to be displayed in table nav sections
	 *
	 * @since  0.0.2
	 *
	 * @param string $which 'top' or 'bottom' table nav
	 */
	protected function extra_tablenav( $which ) {
		
		if ( $which == 'top' ): ?>
			
			<div class="alignleft actions">
				<div class="actions-wrapper">

					<?php
					// Category filtering
					wc_product_dropdown_categories( array(
						'show_count' => 0,
						'selected'   => ( ! empty( $_REQUEST['product_cat'] ) ) ? esc_attr( $_REQUEST['product_cat'] ) : '',
					) );

					// Product type filtering
					echo Helpers::product_types_dropdown( ( isset( $_REQUEST['product_type'] ) ) ? esc_attr( $_REQUEST['product_type'] ) : '' );

					// Extra filters
					$extra_filters = (array) apply_filters( 'atum/stock_central_list/extra_filters', array(
						//'inbound_stock'     => __( 'Inbound Stock', ATUM_TEXT_DOMAIN ),
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

					<?php if ( Helpers::get_option( 'enable_ajax_filter', 'yes' ) == 'no' ): ?>
						<input type="submit" name="filter_action" class="button search-category" value="<?php _e('Filter', ATUM_TEXT_DOMAIN) ?>">
					<?php endif; ?>

				</div>
			</div>

		<?php endif;
		
	}
	
	
	/**
	 * Loads the current product
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 */
	public function single_row( $item ) {
		
		$this->product = wc_get_product( $item );
		$type = $this->product->get_type();

		// If a product is set as hidden from the catalog and is part of a Grouped product, don't display it on the list
		/*if ( $type == 'simple' && $this->product->visibility == 'hidden' && ! empty($this->product->post->post_parent) ) {
			return;
		}*/

		$this->allow_calcs = ( in_array($type, ['variable', 'grouped']) ) ? FALSE : TRUE;
		parent::single_row( $item );
		
		// Add the children products of each Variable and Grouped product
		if ( in_array($type, ['variable', 'grouped']) ) {
			
			$product_class = '\\WC_Product_' . ucfirst($type);
			$parent_product = new $product_class( $this->product->get_id() );
			$child_products = $parent_product->get_children();
			
			if ( ! empty($child_products) ) {

				$this->allow_calcs = TRUE;

				foreach ($child_products as $child_id) {

					// Exclude some children if there is a "Views Filter" active
					if ( ! empty($_REQUEST['v_filter']) ) {

						$v_filter = esc_attr( $_REQUEST['v_filter'] );
						if ( ! in_array($child_id, $this->id_views[ $v_filter ]) ) {
							continue;
						}

					}

					$this->is_child = TRUE;
					$this->product = wc_get_product($child_id);
					$this->single_expandable_row($this->product, ($type == 'grouped' ? $type : 'variation'));
				}
			}
			
		}
		
		// Reset the child value
		$this->is_child = FALSE;
		
	}
	
	/**
	 * Generates content for a expandable row on the table
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 * @param string   $type The type of product
	 */
	public function single_expandable_row( $item, $type ) {
		echo '<tr class="' . $type . '" style="display: none">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	/**
	 * The default column (when no specific column method found)
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item          The WooCommerce product post
	 * @param string   $column_name   The current column name
	 *
	 * @return string|bool
	 */
	protected function column_default( $item, $column_name ) {

		$id = $this->get_current_product_id($this->product);
		$column_item = '';

		// Check if it's a hidden meta key (will start with underscore)
		if ( substr( $column_name, 0, 1 ) == '_' ) {
			$column_item = get_post_meta( $id, $column_name, TRUE );
		}

		if ($column_item === '' || $column_item === FALSE) {
			$column_item = self::EMPTY_COL;
		}
		
		return apply_filters( "atum/stock_central_list/column_default_$column_name", $column_item, $item, $this->product );
		
	}
	
	/**
	 * Column for thumbnail
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 *
	 * @return string
	 */
	protected function column_thumb( $item ) {
		
		return apply_filters( 'atum/stock_central_list/column_thumb', $this->product->get_image( [40, 40] ), $item, $this->product );
	}
	
	/**
	 * Post title column
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 *
	 * @return string
	 */
	protected function column_title( $item ) {
		
		$title = '';
		if ( $this->product->get_type() == 'variation' ) {
			
			$attributes = wc_get_product_variation_attributes( $this->get_current_product_id($this->product) );
			if ( ! empty($attributes) ) {
				$title = ucfirst( implode(' ', $attributes) );
			}
			
		}
		else {
			$title = $this->product->get_title();
		}
		
		if ( strlen( $title ) > 20 ) {
			$title = '<span class="tips" data-toggle="tooltip" title="' . $title . '">' . trim( substr( $title, 0, 20 ) ) .
			         '...</span><span class="atum-title-small">' . $title . '</span>';
		}
		
		return apply_filters( 'atum/stock_central_list/column_title', $title, $item, $this->product );
	}
	
	/**
	 * Product SKU column
	 *
	 * @since  1.1.2
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 *
	 * @return string
	 */
	protected function column__sku( $item ) {

		$id = $this->get_current_product_id($this->product);
		$sku = get_post_meta( $id, '_sku', TRUE );

		$args = array(
			'post_id'    => $id,
			'meta_key'   => 'sku',
			'value'      => ( $sku ) ? $sku : self::EMPTY_COL,
			'input_type' => 'text',
			'tooltip'    => __( 'Click to edit the SKU', ATUM_TEXT_DOMAIN )
		);

		return apply_filters( 'atum/stock_central_list/column_sku', $this->get_editable_column($args), $item, $this->product );
	}

	/**
	 * Post ID column
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 *
	 * @return int
	 */
	protected function column_ID( $item ) {
		return apply_filters( 'atum/stock_central_list/column_ID', $this->get_current_product_id($this->product), $item, $this->product );
	}
	
	/**
	 * Column for product type
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 *
	 * @return string
	 */
	protected function column_calc_type( $item ) {
		
		$type = $this->product->get_type();
		$product_types = wc_get_product_types();
		
		if ( isset($product_types[$type]) || $this->is_child ) {
			
			if ( ! $this->is_child ) {
				$product_tip = $product_types[ $type ];
			}
			
			switch ( $type ) {
				case 'simple':
					
					if ($this->is_child) {
						$type = 'grouped-item';
						$product_tip = __('Grouped item', ATUM_TEXT_DOMAIN);
					}
					elseif ( $this->product->is_downloadable() ) {
						$type = 'downloadable';
						$product_tip = __('Downloadable product', ATUM_TEXT_DOMAIN);
					}
					elseif ( $this->product->is_virtual() ) {
						$type = 'virtual';
						$product_tip = __('Virtual product', ATUM_TEXT_DOMAIN);
					}
					
			        break;
				
				case 'variable':
				case 'grouped':
					
					if ($this->is_child) {
						$type = 'grouped-item';
						$product_tip = __('Grouped item', ATUM_TEXT_DOMAIN);
					}
					elseif ( $this->product->has_child() ) {
						$product_tip .= '<br>' . sprintf(
							__('(click to show/hide the %s)', ATUM_TEXT_DOMAIN),
							( ($type == 'grouped') ? __('Grouped items', ATUM_TEXT_DOMAIN) : __('Variations', ATUM_TEXT_DOMAIN) )
						);
						$type .= ' has-child';
					}
					
					break;
					
				case 'variation':
					
					$product_tip = __('Variation', ATUM_TEXT_DOMAIN);
					break;
			}
			
			return apply_filters( 'atum/stock_central_list/column_type', '<span class="product-type tips ' . $type . '" data-toggle="tooltip" title="' . $product_tip . '"></span>', $item, $this->product );
			
		}
		
		return '';
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
	 * Column for purchase price
	 *
	 * @since  1.2.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return float
	 */
	protected function column__purchase_price( $item ) {

		$purchase_price = self::EMPTY_COL;
		$product_id = $this->get_current_product_id($this->product);

		if ($this->allow_calcs) {

			$purchase_price_value = get_post_meta($product_id, '_purchase_price', TRUE);
			$purchase_price_value = ( is_numeric($purchase_price_value) ) ? Helpers::format_price($purchase_price_value, ['trim_zeros' => TRUE]) : $purchase_price;

			$args = array(
				'post_id'  => $product_id,
				'meta_key' => 'purchase_price',
				'value'    => $purchase_price_value,
				'symbol'   => get_woocommerce_currency_symbol(),
				'tooltip'  => __( 'Click to edit the purchase price', ATUM_TEXT_DOMAIN )
			);

			$purchase_price = $this->get_editable_column($args);
		}

		return apply_filters( 'atum/stock_central_list/column_purchase_price', $purchase_price, $item, $this->product );

	}

	/**
	 * Column for stock amount
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return string|int
	 */
	protected function column__stock( $item ) {

		$stock = self::EMPTY_COL;
		$product_id = $this->get_current_product_id($this->product);

		if ($this->allow_calcs) {

			$args = array(
				'post_id'  => $product_id,
				'meta_key' => 'stock',
				'value'    => intval( $this->product->get_stock_quantity() ),
				'tooltip'  => __( 'Click to edit the stock quantity', ATUM_TEXT_DOMAIN )
			);

			$stock = $this->get_editable_column($args);
		}

		return apply_filters( 'atum/stock_central_list/column_stock', $stock, $item, $this->product );

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

		if (! $this->allow_calcs) {
			$column_item = self::EMPTY_COL;
		}
		else {
		
			$column_item = 0;
			$orders = Helpers::get_orders( array( 'order_status' => 'wc-on-hold, wc-pending' ) );

			foreach ( $orders as $order ) {

				$products = $order->get_items();

				foreach ( $products as $product ) {
					if ( $this->product->get_id() == $product['product_id'] ) {
						$column_item += $product['qty'];
					}

				}

			}
		}
		
		return apply_filters( 'atum/stock_central_list/column_stock_hold', $column_item, $item, $this->product );
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

		if (! $this->allow_calcs) {
			$column_item = self::EMPTY_COL;
		}
		else {
			$column_item = $this->get_log_item_qty( 'reserved-stock', $this->product->get_id() );
		}

		return apply_filters( 'atum/stock_central_list/column_reserved_stock', $column_item, $item, $this->product );
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

		if (! $this->allow_calcs) {
			$column_item = self::EMPTY_COL;
		}
		else {

			$column_item = '--';
			if ( $this->product->backorders_allowed() ) {

				$stock_quantity = $this->product->get_stock_quantity();
				$column_item = 0;
				if ( $stock_quantity < $this->no_stock ) {
					$column_item = $this->no_stock - $stock_quantity;
				}

			}

		}
		
		return apply_filters( 'atum/stock_central_list/column_back_orders', $column_item, $item, $this->product );
		
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
			$column_item = self::EMPTY_COL;
		}
		else {
			$column_item = ( empty( $this->calc_columns[ $this->product->get_id() ]['sold_today'] ) ) ? 0 : $this->calc_columns[ $this->product->get_id() ]['sold_today'];
		}
		
		return apply_filters( 'atum/stock_central_list/column_sold_today', $column_item, $item, $this->product );
		
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

		if (! $this->allow_calcs) {
			$column_item = self::EMPTY_COL;
		}
		else {
			$column_item = $this->get_log_item_qty( 'customer-returns', $this->product->get_id() );
		}

		return apply_filters( 'atum/stock_central_list/column_cutomer_returns', $column_item, $item, $this->product );
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

		if (! $this->allow_calcs) {
			$column_item = self::EMPTY_COL;
		}
		else {
			$column_item = $this->get_log_item_qty( 'warehouse-damage', $this->product->get_id() );
		}

		return apply_filters( 'atum/stock_central_list/column_warehouse_damage', $column_item, $item, $this->product );
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

		if (! $this->allow_calcs) {
			$column_item = self::EMPTY_COL;
		}
		else {
			$column_item = $this->get_log_item_qty( 'lost-in-post', $this->product->get_id() );
		}

		return apply_filters( 'atum/stock_central_list/column_lost_in_post', $column_item, $item, $this->product );
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
			$column_item = self::EMPTY_COL;
		}
		else {
			$column_item = ( empty( $this->calc_columns[ $this->product->get_id() ]['sold_7'] ) ) ? 0 : $this->calc_columns[ $this->product->get_id() ]['sold_7'];
		}
		
		return apply_filters( 'atum/stock_central_list/column_sold_last_7_days', $column_item, $item, $this->product );
		
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
			$column_item = self::EMPTY_COL;
		}
		else {
			$column_item = ( empty( $this->calc_columns[ $this->product->get_id() ]['sold_14'] ) ) ? 0 : $this->calc_columns[ $this->product->get_id() ]['sold_14'];
		}
		
		return apply_filters( 'atum/stock_central_list/column_sold_last_14_days', $column_item, $item, $this->product );
		
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
	 * Get an associative array ( id => link ) with the list of available views on this table.
	 *
	 * @since 0.0.2
	 *
	 * @return array
	 */
	protected function get_views() {
		
		$views    = array();
		$v_filter = ( ! empty( $_REQUEST['v_filter'] ) ) ? esc_attr( $_REQUEST['v_filter'] ) : 'all_stock';
		
		$views_name = array(
			'all_stock' => __('All', ATUM_TEXT_DOMAIN),
			'in_stock'  => __('In Stock', ATUM_TEXT_DOMAIN),
			'out_stock' => __('Out of Stock', ATUM_TEXT_DOMAIN),
			'low_stock' => __('Low Stock', ATUM_TEXT_DOMAIN)
		);
		
		$url = esc_url( add_query_arg( 'page', Globals::ATUM_UI_SLUG , admin_url()) );
		
		foreach ( $views_name as $key => $text ) {
			
			$id = '';
			if ( $key != 'all_stock' ) {
				$view_url = esc_url( add_query_arg( array( 'v_filter' => $key ), $url ) );
				$id = ' id="' . $key . '"';
			}
			else {
				$view_url = $url;
			}
			
			$class = ( $key == $v_filter || ( ! $v_filter && $key == 'all_stock' ) ) ? ' class="current"' : '';
			$count = $this->count_views[ 'count_' . ( ( $key == 'all_stock' ) ? 'all' : $key ) ];
			
			$views[ $key ] = '<a' . $id . $class . ' href="' . $view_url . '"><span>' . $text . ' (' . $count . ')</span></a>';
		}
		
		return apply_filters( 'atum/stock_central_list/view_filters', $views );
		
	}
	
	/**
	 * Prepare the table data
	 *
	 * @since  0.0.2
	 */
	public function prepare_items() {
		
		// Add product category to the tax query
		if ( ! empty( $_REQUEST['product_cat'] ) ) {
			$this->taxonomies[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => esc_attr( $_REQUEST['product_cat'] )
			);
		}
		
		// Change the product type tax query (initialized in constructor) to the current queried type
		if ( ! empty( $_REQUEST['product_type'] ) ) {

			$type = esc_attr( $_REQUEST['product_type'] );
			
			foreach($this->taxonomies as $index => $taxonomy) {

				if ($taxonomy['taxonomy'] == 'product_type') {

					if ( in_array($type, ['downloadable', 'virtual']) ) {
						$this->taxonomies[$index]['terms'] = 'simple';

						$this->extra_meta = array(
							'key'   => "_$type",
							'value' => 'yes'
						);

					}
					else {
						$this->taxonomies[$index]['terms'] = $type;
					}

					break;
				}

			}
			
		}
		
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
	 * Set views for table filtering and calculate total value counters for pagination
	 *
	 * @since 0.0.2
	 *
	 * @param array $args WP_Query arguments
	 */
	protected function set_views_data( $args ) {

		global $wpdb;

		$this->id_views = array(
			'in_stock'  => [ ],
			'out_stock' => [ ],
			'low_stock' => [ ]
		);

		$this->count_views = array(
			'count_in_stock'  => 0,
			'count_out_stock' => 0,
			'count_low_stock' => 0
		);

		// Get all the IDs in the two queries with no pagination
		$args['fields']         = 'ids';
		$args['posts_per_page'] = - 1;
		unset( $args['paged'] );

		$all_transient = 'stock_central_list_all_' . Helpers::get_transient_identifier( $args );
		$posts = Helpers::get_transient( $all_transient );

		if ( ! $posts ) {

			global $wp_query;
			$wp_query = new \WP_Query( apply_filters( 'atum/stock_central_list/set_views_data/all', $args ) );
			$posts = $wp_query->posts;

			// Save it as a transient to improve the performance
			Helpers::set_transient( $all_transient, $posts );

		}

		$this->count_views['count_all'] = count( $posts );

		$variations = $group_items = '';
		// If it's a search or a product filtering, include only the filtered items to search for children
		$post_in = ( ! empty($args['s']) || ! empty($_REQUEST['product_cat']) || ! empty($_REQUEST['product_type']) ) ? $posts : array();

		foreach($this->taxonomies as $index => $taxonomy) {

			if ( $taxonomy['taxonomy'] == 'product_type' ) {

				if ( in_array('variable', (array) $taxonomy['terms']) ) {

					$variations = $this->get_children( 'variable', $post_in, 'product_variation' );

					// Add the Variations to the posts list
					if ( $variations ) {
						// The Variable products are just containers and don't count for the list views
						$this->count_views['count_all'] += ( count( $variations ) - count( $this->variable_products ) );
						$posts = array_unique( array_merge( array_diff( $posts, $this->variable_products ), $variations ) );
					}

				}

				if ( in_array('grouped', (array) $taxonomy['terms']) ) {

					$group_items = $this->get_children( 'grouped', $post_in );

					// Add the Group Items to the posts list
					if ( $group_items ) {
						// The Grouped products are just containers and don't count for the list views
						$this->count_views['count_all'] += ( count( $group_items ) - count( $this->grouped_products ) );
						$posts = array_unique( array_merge( array_diff( $posts, $this->grouped_products ), $group_items ) );

					}

				}

				break;
			}

		}

		if ( $posts ) {

			$post_types = ($variations) ? array($this->post_type, 'product_variation') : $this->post_type;

			// Products in stock
			$args = array(
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
				'post__in'       => $posts
			);

			$in_stock_transient = 'stock_central_list_in_stock_' . Helpers::get_transient_identifier( $args );
			$posts_in_stock = Helpers::get_transient( $in_stock_transient );

			if ( ! $posts_in_stock ) {
				$posts_in_stock = new \WP_Query( apply_filters( 'atum/stock_central_list/set_views_data/in_stock', $args ) );
				Helpers::set_transient( $in_stock_transient, $posts_in_stock );
			}

			$this->id_views['in_stock'] = $posts_in_stock->posts;
			$this->count_views['count_in_stock'] = count( $posts_in_stock->posts );

			// As the Group items might be displayed multiple times, we should count them multiple times too
			if ($group_items && ( empty($_REQUEST['product_type']) || $_REQUEST['product_type'] != 'grouped' )) {
				$this->count_views['count_in_stock'] += count( array_intersect($group_items, $posts_in_stock->posts) );
			}

			$this->id_views['out_stock']          = array_diff( $posts, $posts_in_stock->posts );
			$this->count_views['count_out_stock'] = $this->count_views['count_all'] - $this->count_views['count_in_stock'];

			if ( $this->count_views['count_in_stock'] ) {

				$low_stock_transient = 'stock_central_list_low_stock_' . Helpers::get_transient_identifier( $args );
				$result = Helpers::get_transient( $low_stock_transient );

				if ( ! $result ) {

					// Products in LOW stock (compare last seven days average sales per day * re-order days with current stock )
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

					$low_stock_post_types = ($variations) ? "('product', 'product_variation')" : "('product')";

					$str_states = "(SELECT `{$wpdb->posts}`.`ID`,
						IF( CAST( IFNULL(`sales`.`qty`, 0) AS DECIMAL(10,2) ) <= 
							CAST( IF( LENGTH(`{$wpdb->postmeta}`.`meta_value`) = 0 , 0, `{$wpdb->postmeta}`.`meta_value`) AS DECIMAL(10,2) ), TRUE, FALSE) AS state
						FROM `{$wpdb->posts}`
						    LEFT JOIN `{$wpdb->postmeta}` ON (`{$wpdb->posts}`.`ID` = `{$wpdb->postmeta}`.`post_id`)
						    LEFT JOIN " . $str_sales . " ON (`{$wpdb->posts}`.`ID` = `sales`.`IDs`)
						WHERE (`{$wpdb->postmeta}`.`meta_key` = '_stock'
				            AND `{$wpdb->posts}`.`post_type` IN " . $low_stock_post_types . "
				            AND (`{$wpdb->posts}`.`ID` IN (" . implode( ', ', $posts_in_stock->posts ) . ")) )) AS states";

					$str_sql = apply_filters( 'atum/stock_central_list/set_views_data/low_stock', "SELECT `ID` FROM $str_states WHERE state IS FALSE;" );

					$result = $wpdb->get_results( $str_sql );
					$result = wp_list_pluck( $result, 'ID' );
					Helpers::set_transient( $low_stock_transient, $result );

				}

				$this->id_views['low_stock']          = $result;
				$this->count_views['count_low_stock'] = count( $result );

			}

		}

	}

	/**
	 * Get all the available children products of the published parent products (Variable and Grouped)
	 *
	 * @since 1.1.1
	 *
	 * @param string $parent_type   The parent product type
	 * @param array  $post_in       Optional. If is a search query, get only the children from the filtered products
	 * @param string $post_type     Optional. The children post type
	 *
	 * @return array|bool
	 */
	protected function get_children( $parent_type, $post_in = array(), $post_type = 'product' ) {

		// Get the published Variables first
		$parent_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $parent_type
				)
			)
		);

		if (! empty($post_in) ) {
			$parent_args['post__in'] = $post_in;
		}

		$parents = new \WP_Query($parent_args);

		if ($parents->found_posts) {

			// Save them to be used when preparing the list query
			if ($parent_type == 'variable') {
				$this->variable_products = $parents->posts;
			}
			else {
				$this->grouped_products = $parents->posts;
			}

			$children_args = array(
				'post_type'       => $post_type,
				'post_status'     => 'publish',
				'posts_per_page'  => - 1,
				'fields'          => 'ids',
				'post_parent__in' => $parents->posts
			);

			$children = new \WP_Query( apply_filters( 'atum/stock_central_list/get_children_args', $children_args ) );

			if ($children->found_posts) {
				return $children->posts;
			}

		}

		return FALSE;

	}

	/**
	 * Get the parent products from a list of product IDs
	 *
	 * @since 1.1.1
	 *
	 * @param array $product_ids  The array of children product IDs
	 *
	 * @return array
	 */
	protected function get_parents ($product_ids) {

		// Filter the parents of the current values
		$parents = array();
		foreach ($product_ids as $product_id) {
			$product = wc_get_product($product_id);

			// For Variations
			if ( is_a($product, 'WC_Product_Variation') ) {
				$parents[] = $product->get_parent_id();
			}
			// For Group Items (these have the grouped ID as post_parent property)
			else {
				$product_post = get_post( $product_id );

				if ($product_post->post_parent) {
					$parents[] = $product_post->post_parent;
				}
			}
		}

		return array_merge( $product_ids, array_unique($parents) );

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