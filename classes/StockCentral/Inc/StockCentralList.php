<?php
/**
 * @package         Atum\StockCentral
 * @subpackage      Inc
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       (c)2017 Stock Management Labs
 *
 * @since           0.0.1
 */

namespace Atum\StockCentral\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumListTable;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Settings\Settings;


class StockCentralList extends AtumListTable {
	
	/**
	 * The post type used to build the table (WooCommerce product)
	 * @var string
	 */
	protected $post_type = 'product';
	
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
			'thumb'                => '<span class="wc-image tips" data-tip="' . __('Image', ATUM_TEXT_DOMAIN) . '">' . __( 'Thumb', ATUM_TEXT_DOMAIN ) . '</span>',
			'title'                => __( 'Product Name', ATUM_TEXT_DOMAIN ),
			'_sku'                 => __( 'SKU', ATUM_TEXT_DOMAIN ),
			'ID'                   => __( 'ID', ATUM_TEXT_DOMAIN ),
			'calc_type'            => '<span class="wc-type tips" data-tip="' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '">' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '</span>',
			'calc_stock'           => __( 'Current Stock', ATUM_TEXT_DOMAIN ),
			'calc_inbound'         => __( 'Inbound Stock', ATUM_TEXT_DOMAIN ),
			'calc_hold'            => __( 'Stock on Hold', ATUM_TEXT_DOMAIN ),
			'calc_reserved'        => __( 'Reserved Stock', ATUM_TEXT_DOMAIN ),
			'calc_back_orders'     => __( 'Back Orders', ATUM_TEXT_DOMAIN ),
			'calc_sold_today'      => __( 'Sold Today', ATUM_TEXT_DOMAIN ),
			'calc_returns'         => __( 'Customer Returns', ATUM_TEXT_DOMAIN ),
			'calc_damages'         => __( 'Warehouse Damages', ATUM_TEXT_DOMAIN ),
			'calc_lost_post'       => __( 'Lost in Post', ATUM_TEXT_DOMAIN ),
			'calc_sales14'         => __( 'Sales Last 14 Days', ATUM_TEXT_DOMAIN ),
			'calc_sales7'          => __( 'Sales Last 7 Days', ATUM_TEXT_DOMAIN ),
			'calc_will_last'       => __( 'Stock will Last (Days)', ATUM_TEXT_DOMAIN ),
			'calc_stock_out_days'  => __( 'Out of Stock for (Days)', ATUM_TEXT_DOMAIN ),
			'calc_sold_lost_sales' => __( 'Lost Sales', ATUM_TEXT_DOMAIN ),
			'calc_stock_indicator' => __( 'Stock Indicator', ATUM_TEXT_DOMAIN ),
		);
		
		// TODO: Add group table functionality if some columns are invisible
		$args['group_members'] = array(
			'product-details'       => array(
				'title'   => __( 'Product Details', ATUM_TEXT_DOMAIN ),
				'members' => array( 'thumb', '_sku', 'ID', 'calc_type', 'title' )
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
				'members' => array( 'calc_returns', 'calc_damages', 'calc_lost_post' )
			),
			'stock-selling-manager' => array(
				'title'   => __( 'Stock Selling Manager', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'calc_sales14',
					'calc_sales7',
					'calc_will_last',
					'calc_stock_out_days',
					'calc_sold_lost_sales',
					'calc_stock_indicator'
				)
			),
		);
		
		parent::__construct( $args );
		
		add_action( 'admin_notices', array( $this, 'add_premium_columns_notice' ) );
		
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
			$output  = '<select name="product_type" id="dropdown_product_type">';
			$output .= '<option value=""' . selected($type, '', FALSE) . '>' . __( 'Show all product types', ATUM_TEXT_DOMAIN ) . '</option>';
			
			foreach ( $terms as $term ) {
				
				if ( 'external' == $term->name ) {
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
		$type = $this->product->product_type;

		// If a product is set as hidden from the catalog and is part of a Grouped product, don't display it on the list
		/*if ( $type == 'simple' && $this->product->visibility == 'hidden' && ! empty($this->product->post->post_parent) ) {
			return;
		}*/

		$this->allow_calcs = ( in_array($type, ['variable', 'grouped']) ) ? FALSE : TRUE;
		parent::single_row( $item );
		
		// Add the children products of each Variable and Grouped product
		if ( in_array($type, ['variable', 'grouped']) ) {
			
			$product_class = '\\WC_Product_' . ucfirst($type);
			$parent_product = new $product_class($this->product->id);
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
					$this->single_expandable_row($this->product->post, ($type == 'grouped' ? $type : 'variation'));
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
		
		// Check if it's a hidden meta key (will start with underscore)
		$id = ($this->product->product_type == 'variation') ? $this->product->variation_id : $item->ID;
		$column_item = ( substr( $column_name, 0, 1 ) == '_' ) ? get_post_meta( $id, $column_name, TRUE ) : '&mdash;';
		
		return apply_filters( "atum/atum_list_table/column_default_$column_name", $column_item );
		
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
		
		return apply_filters( 'atum/stock_central_list/column_thumb', $this->product->get_image( 'thumbnail' ) );
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
		if ( $this->product->product_type == 'variation' ) {
			
			$attributes = wc_get_product_variation_attributes($this->product->variation_id);
			if ( ! empty($attributes) ) {
				$title = ucfirst( implode(' ', $attributes) );
			}
			
		}
		else {
			$title = $item->post_title;
		}
		
		if ( strlen( $title ) > 20 ) {
			$title = '<span class="tips" data-tip="' . $title . '">' . trim( substr( $title, 0, 20 ) ) .
			         '...</span><span class="atum-title-small">' . $title . '</span>';
		}
		
		return apply_filters( 'atum/stock_central_list/column_title', $title );
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
		
		$id = ($this->product->product_type == 'variation') ? $this->product->variation_id : $item->ID;
		return apply_filters( 'atum/stock_central_list/column_ID', $id );
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
			
			return apply_filters( 'atum/stock_central_list/column_type', '<span class="product-type tips ' . $type . '" data-tip="' . $product_tip . '"></span>' );
			
		}
		
		return '';
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

		$stock = '&mdash;';
		$product_id = ($this->product->product_type == 'variation') ? $this->product->variation_id :$item->ID;

		if ($this->allow_calcs) {
			$stock = '<span class="set-stock tips" data-tip="' . __( 'Click to edit the stock quantity', ATUM_TEXT_DOMAIN ) . '" data-position="top" data-item="' . $product_id . '">' .
			         intval( $this->product->get_total_stock() ) . '</span>';
		}

		return apply_filters( 'atum/stock_central_list/column_stock', $stock );
		
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
			
		$stock = intval( $this->product->get_total_stock() );
		
		// Add css class to the <td> elements depending on the quantity in stock compared to the last days sales
		if (! $this->allow_calcs) {
			$content = '&mdash;';
		}
		elseif ( $stock <= 0 ) {
			// no stock
			$classes .= ' cell-red';
			$content = '<span class="dashicons dashicons-dismiss"></span>';
		}
		elseif ( isset( $this->calc_columns[ $this->product->id ]['sold_last_days'] ) ) {
			
			// stock ok
			if ( $stock >= $this->calc_columns[ $this->product->id ]['sold_last_days'] ) {
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
		
		echo '<td ' . $data . $classes . '>' . apply_filters( 'atum/stock_central_list/column_stock', $content ) .
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
			$column_item = '&mdash;';
		}
		else {
		
			$column_item = 0;
			$orders = Helpers::get_orders( array( 'order_status' => 'wc-on-hold, wc-processing' ) );

			foreach ( $orders as $order ) {

				$products = $order->get_items();

				foreach ( $products as $product ) {
					if ( $item->ID == $product['product_id'] ) {
						$column_item += $product['qty'];
					}

				}

			}
		}
		
		return apply_filters( 'atum/stock_central_list/column_hold', $column_item );
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
			$column_item = '&mdash;';
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
		
		return apply_filters( 'atum/stock_central_list/column_back_orders', $column_item );
		
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
			$column_item = '&mdash;';
		}
		else {
			$column_item = ( empty( $this->calc_columns[ $item->ID ]['sold_today'] ) ) ? 0 : $this->calc_columns[ $item->ID ]['sold_today'];
		}
		
		return apply_filters( 'atum/stock_central_list/column_sold_today', $column_item );
		
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
			$column_item = '&mdash;';
		}
		else {
			$column_item = ( empty( $this->calc_columns[ $item->ID ]['sold_7'] ) ) ? 0 : $this->calc_columns[ $item->ID ]['sold_7'];
		}
		
		return apply_filters( "atum/stock_central_list/column_sold_last_7_days", $column_item );
		
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
			$column_item = '&mdash;';
		}
		else {
			$column_item = ( empty( $this->calc_columns[ $item->ID ]['sold_14'] ) ) ? 0 : $this->calc_columns[ $item->ID ]['sold_14'];
		}
		
		return apply_filters( "atum/stock_central_list/column_sold_last_14_days", $column_item );
		
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
		$will_last = '&mdash;';

		if ($this->allow_calcs) {
			$sales = $this->column_calc_sales7( $item );
			$stock = $this->product->get_total_stock();

			if ( $stock > 0 && $sales > 0 ) {
				$will_last = ceil( $stock / ( $sales / 7 ) );
			}
			elseif ( $stock > 0 ) {
				$will_last = '>30';
			}
		}
		
		return apply_filters( 'atum/stock_central_list/column_stock_will_last_days', $will_last );
		
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

		if ($this->allow_calcs) {
			// Check if the current product has the "Out of stock" date recorded
			$out_of_stock_date = get_post_meta( $item->ID, Globals::get_out_of_stock_date_key(), TRUE );
			if ( $out_of_stock_date ) {
				$out_date_time = new \DateTime( $out_of_stock_date );
				$now_date_time = new \DateTime( 'now' );
				$interval      = date_diff( $out_date_time, $now_date_time );

				return $interval->days;
			}
		}
		
		return '&mdash;';
		
	}
	
	/**
	 * Get the amount of items sold since $date_start or between $date_start and $date_end
	 *
	 * @since 1.0.0
	 *
	 * @param array  $items      Array of Product IDs we want to calculate sell
	 * @param string $date_start The date from which start the items sold calculations
	 * @param string $date_end   Optional. The max date to calculate the items sold
	 *
	 * @return array
	 */
	private function get_sold_last_days( $items, $date_start, $date_end = '' ) {
		
		$items_sold = array();
		$orders     = Helpers::get_orders( array(
			'order_status'         => 'wc-processing, wc-completed',
			'completed_date_start' => $date_start,
			'completed_date_end'   => $date_end
		), TRUE );
		
		if ( $orders ) {
			
			global $wpdb;
			
			$orders   = implode( ',', $orders );
			$products = implode( ',', $items );
			
			
			$str_sql = "SELECT 
				   SUM(`META_PROD_QTY`.`meta_value`) AS `QTY`  
				    ,`META_PROD_ID`.`meta_value` AS `PROD_ID`
				FROM
				    `{$wpdb->posts}` AS `ORDERS`
				    INNER JOIN `{$wpdb->prefix}woocommerce_order_items` AS `ITEMS` 
				        ON (`ORDERS`.`ID` = `ITEMS`.`order_id`)
				    INNER JOIN `{$wpdb->prefix}woocommerce_order_itemmeta` AS `META_PROD_ID`
				        ON (`ITEMS`.`order_item_id` = `META_PROD_ID`.`order_item_id`)
				    INNER JOIN `{$wpdb->prefix}woocommerce_order_itemmeta` AS `META_PROD_QTY`
				        ON (`META_PROD_ID`.`order_item_id` = `META_PROD_QTY`.`order_item_id`)
				WHERE (`ORDERS`.`ID` IN ($orders)
				    AND `META_PROD_ID`.`meta_value` IN ($products)
				    AND `META_PROD_ID`.`meta_key` = '_product_id'
				    AND `META_PROD_QTY`.`meta_key` = '_qty')
				GROUP BY `META_PROD_ID`.`meta_value`
				HAVING (`QTY` IS NOT NULL);";
			
			$result = $wpdb->get_results( $str_sql, ARRAY_A );
			if ( $result ) {
				$items_sold = $result;
			}
		}
		
		return $items_sold;
		
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
		
		// Set array with calculated columns
		$rows = $this->get_sold_last_days( $this->current_products, $this->day );
		
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_today'] = $row['QTY'];
			}
		}
		
		$date_start = Helpers::date_format( $this->day . ' -1 week' );
		$date_end   = $this->day;
		
		$rows = $this->get_sold_last_days( $this->current_products, $date_start, $date_end );
		
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_7'] = $row['QTY'];
			}
		}
		
		$date_start = Helpers::date_format( $this->day . ' -2 weeks' );
		$date_end   = $this->day;
		
		$rows = $this->get_sold_last_days( $this->current_products, $date_start, $date_end );
		
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$this->calc_columns[ $row['PROD_ID'] ]['sold_14'] = $row['QTY'];
			}
		}
		
		$date_start = Helpers::date_format( "-$this->last_days days" );
		$date_end   = $this->day;
		
		$rows = $this->get_sold_last_days( $this->current_products, $date_start, $date_end );
		
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
		$posts         = Helpers::get_transient( $all_transient );
		
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
			$posts_stock = Helpers::get_transient( $in_stock_transient );
			
			if ( ! $posts_stock ) {
				$posts_stock = new \WP_Query( apply_filters( 'atum/stock_central_list/set_views_data/in_stock', $args ) );
				Helpers::set_transient( $in_stock_transient, $posts_stock );
			}
			
			$this->id_views['in_stock'] = $posts_stock->posts;
			$this->count_views['count_in_stock'] = count( $posts_stock->posts );

			// As the Group items might be displayed multiple times, we should count them multiple times too
			if ($group_items && ( empty($_REQUEST['type']) || $_REQUEST['type'] != 'grouped' )) {
				$this->count_views['count_in_stock'] += count( array_intersect($group_items, $posts_stock->posts) );
			}
			
			$this->id_views['out_stock']          = array_diff( $posts, $posts_stock->posts );
			$this->count_views['count_out_stock'] = $this->count_views['count_all'] - $this->count_views['count_in_stock'];
			
			if ( $this->count_views['count_in_stock'] ) {
				
				$low_stock_transient = 'stock_central_list_low_stock_' . Helpers::get_transient_identifier( $args );
				$result = Helpers::get_transient( $low_stock_transient );
				
				if ( ! $result ) {
					
					// Products in LOW stock
					$str_sales = "(SELECT			   
					    (SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND order_item_id = `item`.`order_item_id`) AS IDs,
					    SUM((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_qty' AND order_item_id = `item`.`order_item_id`)) AS qty
						FROM `{$wpdb->posts}` AS `order`
						    INNER JOIN `{$wpdb->prefix}woocommerce_order_items` AS `item` ON (`order`.`ID` = `item`.`order_id`)
							INNER JOIN `{$wpdb->postmeta}` AS `order_meta` ON (`order`.ID = `order_meta`.`post_id`)
						WHERE (`order`.`post_type` = 'shop_order'
						    AND `order`.`post_status` = 'wc-completed' AND `item`.`order_item_type` ='line_item'
						    AND `order_meta`.`meta_key` = '_completed_date'
						    AND `order_meta`.`meta_value` >= '" . Helpers::date_format( "-$this->last_days days" ) . "')
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
				            AND (`{$wpdb->posts}`.`ID` IN (" . implode( ', ', $posts_stock->posts ) . ")) )) AS states";
					
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

			$children = new \WP_Query( apply_filters( 'atum/stock_central_list/get_children', $children_args ) );

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
			if ( ! empty($product->parent) ) {
				$parents[] = $product->parent->id;
			}
			// For Group Items
			elseif ($product->post->post_parent) {
				$parents[] = $product->post->post_parent;
			}
		}

		return array_merge( $product_ids, array_unique($parents) );

	}
	
	/**
	 * Add a notice the first time the user enter the Stock Central informing about disabled columns
	 *
	 * @since 1.1.0
	 */
	public function add_premium_columns_notice() {
		
		$user_dismissed_notices = Helpers::get_dismissed_notices();
		
		if ( !$user_dismissed_notices || ! isset($user_dismissed_notices['welcome']) || $user_dismissed_notices['welcome'] != 'yes' ):
			?>
			<div class="notice notice-info atum-notice welcome-notice is-dismissible" data-nonce="<?php echo wp_create_nonce('dismiss-welcome-notice') ?>">
				<p>
					<?php _e("Welcome to ATUM Stock Central, we hope you'll enjoy and benefit from this great plugin.", ATUM_TEXT_DOMAIN) ?><br><br>
					<?php _e("We have disabled some inactive columns by default as these are only available for future upgrades or our Premium and PRO users. If you'd like to preview them, please, use the Screen Options in the top corner.", ATUM_TEXT_DOMAIN)  ?><br><br>
					<?php _e('Thank you very much for using ATUM as your inventory manager.') ?>
				</p>
			</div>
			<?php
		endif;
		
	}
	
}