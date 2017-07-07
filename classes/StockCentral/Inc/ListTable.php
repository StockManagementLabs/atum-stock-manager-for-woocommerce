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

use Atum\Components\AtumListTable;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\InventoryLogs;
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
	 * The child class should call this constructor from its own constructor to override the default $args.
	 *
	 * @since 0.0.1
	 *
	 * @param array|string $args       {
	 *      Array or string of arguments.
	 *
	 *      @type array         $selected   Optional. The posts selected on the list table
	 *      @type bool          $show_cb    Optional. Whether to show the row selector checkbox as first table column
	 *      @type int           $per_page   Optional. The number of posts to show per page (-1 for no pagination)
	 * }
	 */
	public function __construct( $args ) {
		
		$this->no_stock = intval( get_option( 'woocommerce_notify_no_stock_amount' ) );
		
		// TODO: Allow to specify the day of query in constructor atts
		$this->day       = Helpers::date_format( time(), TRUE );
		$this->last_days = absint( Helpers::get_option( 'sale_days', Settings::DEFAULT_SALE_DAYS ) );
		
		$this->taxonomies[] = array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => Globals::get_product_types()
		);

		$args['table_columns'] = array(
			'thumb'                => '<span class="wc-image tips" data-toggle="tooltip" data-placement="bottom" title="' . __( 'Image', ATUM_TEXT_DOMAIN ) . '">' . __( 'Thumb', ATUM_TEXT_DOMAIN ) . '</span>',
			'title'                => __( 'Product Name', ATUM_TEXT_DOMAIN ),
			'sku'                  => __( 'SKU', ATUM_TEXT_DOMAIN ),
			'ID'                   => __( 'ID', ATUM_TEXT_DOMAIN ),
			'calc_type'            => '<span class="wc-type tips" data-toggle="tooltip" data-placement="bottom" title="' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '">' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '</span>',
			'calc_regular_price'   => __( 'Regular Price', ATUM_TEXT_DOMAIN ),
			'calc_sale_price'      => __( 'Sale Price', ATUM_TEXT_DOMAIN ),
			'calc_purchase_price'  => __( 'Purchase Price', ATUM_TEXT_DOMAIN ),
			'calc_stock'           => __( 'Current Stock', ATUM_TEXT_DOMAIN ),
			'calc_inbound'         => __( 'Inbound Stock', ATUM_TEXT_DOMAIN ),
			'calc_hold'            => __( 'Stock on Hold', ATUM_TEXT_DOMAIN ),
			'calc_reserved'        => __( 'Reserved Stock', ATUM_TEXT_DOMAIN ),
			'calc_back_orders'     => __( 'Back Orders', ATUM_TEXT_DOMAIN ),
			'calc_sold_today'      => __( 'Sold Today', ATUM_TEXT_DOMAIN ),
			'calc_returns'         => __( 'Customer Returns', ATUM_TEXT_DOMAIN ),
			'calc_damages'         => __( 'Warehouse Damages', ATUM_TEXT_DOMAIN ),
			'calc_lost_in_post'       => __( 'Lost in Post', ATUM_TEXT_DOMAIN ),
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
				'members' => array( 'thumb', '_sku', 'ID', 'calc_type', 'title', 'calc_regular_price', 'calc_sale_price', 'calc_purchase_price' )
			),
			'stock-counters'        => array(
				'title'   => __( 'Stock Counters', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'calc_stock',
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
		
	}
	
	/**
	 * Extra controls to be displayed in table nav sections
	 *
	 * @since  0.0.2
	 *
	 * @param string $which 'top' or 'bottom' table nav
	 */
	protected function extra_tablenav( $which ) {
		
		if ( $which == 'top' ) {
			
			echo '<div class="alignleft actions"><div class="actions-wrapper">';
			
			// Months filtering
			$this->months_dropdown('product');
			
			// Category filtering
			wc_product_dropdown_categories( array(
				'show_count' => 0,
				'selected'   => ( ! empty( $_REQUEST['category'] ) ) ? esc_attr( $_REQUEST['category'] ) : '',
			) );
			
			// Type filtering
			$terms   = get_terms( 'product_type' );
			$type    = ( isset( $_REQUEST['type'] ) ) ? esc_attr( $_REQUEST['type'] ) : '';
			$allowed_types = apply_filters( 'atum/stock_central_list/allowed_type_filters', Globals::get_product_types() );

			$output  = '<select name="product_type" id="dropdown_product_type">';
			$output .= '<option value=""' . selected($type, '', FALSE) . '>' . __( 'Show all product types', ATUM_TEXT_DOMAIN ) . '</option>';
			
			foreach ( $terms as $term ) {
				
				if ( ! in_array($term->slug, $allowed_types) ) {
					continue;
				}
				
				$output .= '<option value="' . sanitize_title( $term->name ) . '"' . selected( $term->slug, $type, FALSE ) . '>';
				
				switch ( $term->name ) {
					case 'grouped' :
						$output .= __( 'Grouped product', ATUM_TEXT_DOMAIN );
						break;
					/*case 'external' :
						$output .= __( 'External/Affiliate product', ATUM_TEXT_DOMAIN );
						break;*/
					case 'variable' :
						$output .= __( 'Variable product', ATUM_TEXT_DOMAIN );
						break;
					case 'simple' :
						$output .= __( 'Simple product', ATUM_TEXT_DOMAIN );
						break;
					default :
						// Assuming that we have other types in future
						$output .= ucfirst( $term->name );
						break;
				}
				
				$output .= '</option>';
				
				if ( 'simple' == $term->name ) {
					
					$output .= '<option value="downloadable"' . selected( 'downloadable', $type, FALSE ) . '> &rarr; '
					           . __( 'Downloadable', ATUM_TEXT_DOMAIN ) . '</option>';
					
					$output .= '<option value="virtual"' . selected( 'virtual', $type, FALSE ) . '> &rarr; '
					           . __( 'Virtual', ATUM_TEXT_DOMAIN ) . '</option>';
				}
			}
			
			$output .= '</select>';
			echo $output;
			
			if ( Helpers::get_option( 'enable_ajax_filter', 'yes' ) == 'no' ) {
				echo '<input type="submit" name="filter_action" class="button search-category" value="' . __('Filter', ATUM_TEXT_DOMAIN) . '">';
			}
			
			echo '</div></div>';
		}
		
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
	protected function column_sku( $item ) {

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
	protected function column_calc_regular_price( $item ) {

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
	protected function column_calc_sale_price( $item ) {

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
	protected function column_calc_purchase_price( $item ) {

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
	protected function column_calc_stock( $item ) {

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
	 * Column for stock indicators
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 * @param string   $classes
	 * @param string   $data
	 * @param string   $primary
	 */
	protected function _column_calc_stock_indicator( $item, $classes, $data, $primary ) {
			
		$stock = intval( $this->product->get_stock_quantity() );
		
		// Add css class to the <td> elements depending on the quantity in stock compared to the last days sales
		if (! $this->allow_calcs) {
			$content = self::EMPTY_COL;
		}
		elseif ( $stock <= 0 ) {
			// no stock
			$classes .= ' cell-red';
			$content = '<span class="dashicons dashicons-dismiss"></span>';
		}
		elseif ( isset( $this->calc_columns[ $this->product->get_id() ]['sold_last_days'] ) ) {
			
			// stock ok
			if ( $stock >= $this->calc_columns[ $this->product->get_id() ]['sold_last_days'] ) {
				$classes .= ' cell-green';
				$content = '<span class="dashicons dashicons-yes"></span>';
			}
			// stock low
			else {
				$classes .= ' cell-yellow';
				$content = '<span class="dashicons dashicons-warning"></span>';
			}
			
		}
		else {
			$classes .= ' cell-green';
			$content = '<span class="dashicons dashicons-yes"></span>';
		}
		
		$classes = ( $classes ) ? ' class="' . $classes . '"' : '';
		
		echo '<td ' . $data . $classes . '>' .
		     apply_filters( 'atum/stock_central_list/column_stock_indicator', $content, $item, $this->product ) .
		     $this->handle_row_actions( $item, 'calc_stock_indicator', $primary ) . '</td>';
		
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
			$orders = Helpers::get_orders( array( 'order_status' => 'wc-on-hold, wc-processing' ) );

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
			
		// TODO: FOR THE FREE VERSION IS FIXED TO 7 DAYS AVERAGE
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
			'all_stock' => 'All',
			'in_stock'  => 'In Stock',
			'out_stock' => 'Out of Stock',
			'low_stock' => 'Low Stock'
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
		if ( ! empty( $_REQUEST['category'] ) ) {
			$this->taxonomies[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => esc_attr( $_REQUEST['category'] )
			);
		}
		
		// Change the product type tax query (initialized in constructor) to the current queried type
		if ( ! empty( $_REQUEST['type'] ) ) {
			
			foreach($this->taxonomies as $index => $taxonomy) {
				if ($taxonomy['taxonomy'] == 'product_type') {
					$this->taxonomies[$index]['terms'] = esc_attr( $_REQUEST['type'] );
				}
			}
			
		}
		
		parent::prepare_items();

		$date_end = strtotime( $this->day );
		
		// Set array with calculated columns
		$rows = Helpers::get_sold_last_days( $this->current_products, $date_end );
		
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_today'] = $row['QTY'];
			}
		}
		
		$date_start = strtotime( $this->day . ' -1 week' );

		$rows = Helpers::get_sold_last_days( $this->current_products, $date_start, $date_end );
		
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_7'] = $row['QTY'];
			}
		}
		
		$date_start = strtotime( $this->day . ' -2 weeks' );
		$rows = Helpers::get_sold_last_days( $this->current_products, $date_start, $date_end );
		
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_14'] = $row['QTY'];
			}
		}
		
		$date_start = strtotime( "-$this->last_days days" );
		$rows = Helpers::get_sold_last_days( $this->current_products, $date_start, $date_end );
		
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
		$args['query_args']['fields']         = 'ids';
		$args['query_args']['posts_per_page'] = - 1;
		unset( $args['query_args']['paged'] );
		
		$all_transient = 'stock_central_list_all_' . Helpers::get_transient_identifier( $args['query_args'] );
		$posts = Helpers::get_transient( $all_transient );
		
		if ( ! $posts ) {

			$posts = new \WP_Query( apply_filters( 'atum/stock_central_list/set_views_data/all', $args['query_args'] ) );
			$posts = $posts->posts;
			
			if ( isset( $args['meta'] ) ) {
				
				unset( $args['query_args']['s'] );
				
				if ( array_key_exists( 'meta_query', $args['query_args'] ) ) {
					$args['query_args']['meta_query'][] = $args['meta'];
				}
				else {
					$args['query_args']['meta_query'] = $args['meta'];
				}
				
				$posts_meta_query = new \WP_Query( $args['query_args'] );

				if ($posts_meta_query->found_posts) {
					$posts = array_merge( $posts, $posts_meta_query->posts );
				}
				
			}
			
			// Save it as a transient to improve the performance
			Helpers::set_transient( $all_transient, $posts );

		}

		$this->count_views['count_all'] = count( $posts );

		$variations = $group_items = '';
		foreach($this->taxonomies as $index => $taxonomy) {

			if ( $taxonomy['taxonomy'] == 'product_type' ) {

				if ( in_array('variable', (array) $taxonomy['terms']) ) {

					$variations = $this->get_children( 'variable', 'product_variation' );

					// Add the Variations to the posts list
					if ( $variations ) {
						// The Variable products are just containers and don't count for the list views
						$this->count_views['count_all'] += ( count( $variations ) - count( $this->variable_products ) );
						$posts = array_unique( array_merge( array_diff( $posts, $this->variable_products ), $variations ) );
					}

				}

				if ( in_array('grouped', (array) $taxonomy['terms']) ) {

					$group_items = $this->get_children( 'grouped' );

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
			if ($group_items && ( empty($_REQUEST['type']) || $_REQUEST['type'] != 'grouped' )) {
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
					    (SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND order_item_id = `item`.`order_item_id`) AS IDs,
					    CEIL(SUM((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_qty' AND order_item_id = `item`.`order_item_id`))/7*$this->last_days) AS qty
						FROM `{$wpdb->posts}` AS `order`
						    INNER JOIN `{$wpdb->prefix}woocommerce_order_items` AS `item` ON (`order`.`ID` = `item`.`order_id`)
							INNER JOIN `{$wpdb->postmeta}` AS `order_meta` ON (`order`.ID = `order_meta`.`post_id`)
						WHERE (`order`.`post_type` = 'shop_order'
						    AND `order`.`post_status` IN ('wc-completed', 'wc-processing') AND `item`.`order_item_type` ='line_item'
						    AND `order_meta`.`meta_key` = '_paid_date'
						    AND `order_meta`.`meta_value` >= '" . Helpers::date_format( "-7 days" ) . "')
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
	 * @param string $post_type     Optional. The children post type
	 *
	 * @return array|bool
	 */
	private function get_children($parent_type, $post_type = 'product') {

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

		$args = array(
			'post_type'      => InventoryLogs::POST_TYPE,
			'posts_per_page' => - 1,
			'fields'         => 'ids'
		);

		$qty = 0;

		// Filter by log type meta key
		$log_types = Log::get_types();

		if ( ! in_array( $log_type, array_keys($log_types) ) ) {
			return $qty;
		}

		$args['meta_query'] = array(
			array(
				'key'     => '_type',
				'value'   => $log_type
			)
		);

		// Filter by log status
		if ( strpos($log_status, ATUM_PREFIX) === FALSE ) {
			$log_status = ATUM_PREFIX . $log_status;
		}

		$args['post_status'] = $log_status;
		$log_ids = get_posts( apply_filters('atum/stock_central_list/get_log_items_args', $args) );

		if ( ! empty($log_ids) ) {

			global $wpdb;

			foreach ($log_ids as $log_id) {

				// Get the _qty meta for the specified product in the specified log
				$query = $wpdb->prepare(
					"SELECT meta_value 				  
					 FROM {$wpdb->prefix}atum_log_itemmeta lm
		             JOIN {$wpdb->prefix}atum_log_items li
		             ON lm.log_item_id = li.log_item_id
					 WHERE log_id = %d AND log_item_type = %s 
					 AND meta_key = '_qty' AND lm.log_item_id IN (
					 	SELECT log_item_id FROM {$wpdb->prefix}atum_log_itemmeta 
					 	WHERE meta_key = '_product_id' AND meta_value = %d
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
	
}