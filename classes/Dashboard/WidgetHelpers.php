<?php
/**
 * Helper functions for Widgets
 *
 * @package        Atum
 * @subpackage     Dashboard
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2018 Stock Management Labs™
 *
 * @since          1.4.0
 */

namespace Atum\Dashboard;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Legacy\WidgetHelpersLegacyTrait;
use Atum\Settings\Settings;


final class WidgetHelpers {

	/**
	 * The array of published Variable products' IDs
	 *
	 * @var array
	 */
	private static $variable_products = array();

	/**
	 * The array of published Grouped products' IDs
	 *
	 * @var array
	 */
	private static $grouped_products = array();

	/**
	 * The WC product data used in WP_Query (when using the new tables)
	 *
	 * @var array
	 */
	protected static $wc_query_data = array();

	/**
	 * Get the stats of products that were sold after the specified date
	 *
	 * @since 1.4.0
	 *
	 * @param array $atts {
	 *      Array of stats filter params.
	 *
	 *      @type array  $types              An array of stats to get. Possible values: "sales" and/or "lost_sales"
	 *      @type array  $products           The array of products to include in calculations
	 *      @type string $date_start         The date from when to start the items' sales calculations (must be a string format convertible with strtotime)
	 *      @type string $date_end           Optional. The max date to calculate the items' sales (must be a string format convertible with strtotime)
	 *      @type int    $days               Optional. The days used for lost sales calculations. Only used when asking for lost sales. If not passed will be calculated
	 *      @type bool   $formatted_value    Optional. Whether to return the value formatted as currency
	 * }
	 *
	 * @return array
	 */
	public static function get_sales_stats( $atts ) {

		/**
		 * Variables definition
		 *
		 * @var array  $types
		 * @var array  $products
		 * @var string $date_start
		 * @var string $date_end
		 * @var int    $days
		 * @var bool   $formatted_value
		 */
		extract( $atts );
		$stats = array();

		// Initialize values.
		if ( in_array( 'sales', $types ) ) {
			$stats['value']    = 0;
			$stats['products'] = 0;
		}

		if ( in_array( 'lost_sales', $types ) ) {
			$stats['lost_value']    = 0;
			$stats['lost_products'] = 0;
		}

		$products_sold  = Helpers::get_sold_last_days( $products, $date_start, ( isset( $date_end ) ? $date_end : NULL ) );
		$lost_processed = array();

		if ( $products_sold ) {

			foreach ( $products_sold as $row ) {

				if ( in_array( 'sales', $types ) ) {
					$stats['products'] += floatval( $row['QTY'] );
					$stats['value']    += floatval( $row['TOTAL'] );
				}

				if ( in_array( 'lost_sales', $types ) && ! in_array( $row['PROD_ID'], $lost_processed ) ) {

					if ( ! isset( $days ) || $days <= 0 ) {
						/** @noinspection PhpUnhandledExceptionInspection */
						$date_days_start = new \DateTime( $date_start );
						/** @noinspection PhpUnhandledExceptionInspection */
						$date_days_end = new \DateTime( ( isset( $date_end ) ? $date_end : 'now' ) );
						$days          = $date_days_end->diff( $date_days_start )->days;
					}

					$lost_sales = Helpers::get_product_lost_sales( $row['PROD_ID'], $days );

					if ( is_numeric( $lost_sales ) ) {
						$stats['lost_value'] += $lost_sales;
						$lost_processed[]     = $row['PROD_ID'];
					}
				}

			}

		}

		if ( in_array( 'sales', $types ) ) {
			$stats['value'] = ( ! isset( $formatted_value ) || $formatted_value ) ? Helpers::format_price( $stats['value'] ) : round( $stats['value'], 2 );
		}

		if ( in_array( 'lost_sales', $types ) ) {
			$stats['lost_value'] = ( ! isset( $formatted_value ) || $formatted_value ) ? Helpers::format_price( $stats['lost_value'] ) : round( $stats['lost_value'], 2 );
		}

		return $stats;

	}

	/**
	 * Get the promo sales stats within a specified time window
	 *
	 * @since 1.4.0
	 *
	 * @param array $order_args  See: Helpers::get_orders() param description.
	 *
	 * @return array
	 */
	public static function get_promo_sales_stats( $order_args ) {

		// Initialize counters.
		$stats = array(
			'value'    => 0,
			'products' => 0,
		);

		$orders = Helpers::get_orders( $order_args );

		foreach ( $orders as $order ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order $order
			 */

			// Check if this order had discounts.
			$order_discount = $order->get_discount_total();

			if ( $order_discount ) {

				/* @noinspection PhpWrongStringConcatenationInspection */
				$stats['value'] += $order_discount;

				$order_items = $order->get_items();

				foreach ( $order_items as $order_item ) {
					/**
					 * Variable definition
					 *
					 * @var \WC_Order_Item $order_item
					 */
					$stats['products'] += $order_item->get_quantity();
				}
			}
		}

		$stats['value'] = ( ! isset( $order_args['formatted_value'] ) || $order_args['formatted_value'] ) ? Helpers::format_price( $stats['value'] ) : round( $stats['value'], 2 );

		return $stats;

	}

	/**
	 * Get the orders stats within a specified time window
	 *
	 * @since 1.4.0
	 *
	 * @param array $order_args
	 *
	 * @return array
	 */
	public static function get_orders_stats( $order_args ) {

		// Initialize.
		$stats = array(
			'value'  => 0,
			'orders' => 0,
		);

		$orders          = Helpers::get_orders( $order_args );
		$stats['orders'] = count( $orders );

		foreach ( $orders as $order ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order $order
			 */
			$stats['value'] += floatval( $order->get_total() );

		}

		$stats['value'] = ( ! isset( $order_args['formatted_value'] ) || $order_args['formatted_value'] ) ? Helpers::format_price( $stats['value'] ) : round( $stats['value'], 2 );

		return $stats;

	}

	/**
	 * Get the Sales data for the statistics chart
	 *
	 * @since 1.4.0
	 *
	 * @param string $time_window The time window that will specify the x axis in the chart.
	 *                            Possible values: "this_year", "previous_year", "this_month", "previous_month", "this_week", "previous_week".
	 * @param array  $types       Optional. An array of stats to get. Possible values: "sales" and/or "lost_sales".
	 *
	 * @return array
	 */
	public static function get_sales_chart_data( $time_window, $types = [ 'sales' ] ) {

		$products = Helpers::get_all_products( array(
			'post_type' => [ 'product', 'product_variation' ],
		), TRUE );

		$data = $dataset = array();

		if ( empty( $products ) ) {
			return $dataset;
		}

		$period = self::get_chart_data_period( $time_window );

		if ( ! $period ) {
			return $dataset;
		}

		/** @noinspection PhpUnhandledExceptionInspection */
		$date_now    = new \DateTime();
		$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );

		foreach ( $period as $dt ) {

			/**
			 * Variable definition
			 *
			 * @var \DateTime $dt
			 */
			$interval = date_diff( $dt, $date_now );

			// Bypass all the future dates.
			if ( $interval->invert ) {
				break;
			}

			$data[] = self::get_sales_stats( array(
				'types'           => $types,
				'products'        => $products,
				'date_start'      => $dt->format( 'Y-m-d H:i:s' ),
				'date_end'        => 'year' === $period_time ? 'last day of ' . $dt->format( 'F Y' ) . ' 23:59:59' : $dt->format( 'Y-m-d 23:59:59' ),
				'formatted_value' => FALSE,
			) );
		}

		if ( ! empty( $data ) ) {

			// The chart must use sales or lost_sales types (not both).
			if ( in_array( 'sales', $types ) ) {
				$dataset['value']    = wp_list_pluck( $data, 'value' );
				$dataset['products'] = wp_list_pluck( $data, 'products' );
			}
			elseif ( in_array( 'lost_sales', $types ) ) {
				$dataset['value']    = wp_list_pluck( $data, 'lost_value' );
				$dataset['products'] = wp_list_pluck( $data, 'lost_products' );
			}

		}

		return $dataset;

	}

	/**
	 * Get the Promo Sales data for the statistics chart
	 *
	 * @since 1.4.0
	 *
	 * @param string $time_window   The time window that will specify the x axis in the chart.
	 *                              Possible values: "this_year", "previous_year", "this_month", "previous_month", "this_week", "previous_week".
	 *
	 * @return array
	 */
	public static function get_promo_sales_chart_data( $time_window ) {

		$data   = $dataset = array();
		$period = self::get_chart_data_period( $time_window );

		if ( ! $period ) {
			return $dataset;
		}

		$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
		/** @noinspection PhpUnhandledExceptionInspection */
		$date_now     = new \DateTime();
		$order_status = (array) apply_filters( 'atum/dashboard/statistics_widget/promo_sales/order_status', [ 'wc-processing', 'wc-completed' ] );

		foreach ( $period as $dt ) {

			/**
			 * Variable definition
			 *
			 * @var \DateTime $dt
			 */
			$interval = date_diff( $dt, $date_now );

			// Bypass all the future dates.
			if ( $interval->invert ) {
				break;
			}

			$data[] = self::get_promo_sales_stats( array(
				'status'          => $order_status,
				'date_start'      => $dt->format( 'Y-m-d H:i:s' ),
				'date_end'        => 'year' === $period_time ? 'last day of ' . $dt->format( 'F Y' ) . ' 23:59:59' : $dt->format( 'Y-m-d 23:59:59' ),
				'formatted_value' => FALSE,
			) );
		}

		if ( ! empty( $data ) ) {
			$dataset['value']    = wp_list_pluck( $data, 'value' );
			$dataset['products'] = wp_list_pluck( $data, 'products' );
		}

		return $dataset;

	}

	/**
	 * Get the Orders data for the statistics chart
	 *
	 * @since 1.4.0
	 *
	 * @param string $time_window   The time window that will specify the x axis in the chart.
	 *                              Possible values: "this_year", "previous_year", "this_month", "previous_month", "this_week", "previous_week".
	 *
	 * @return array
	 */
	public static function get_orders_chart_data( $time_window ) {

		$data   = $dataset = array();
		$period = self::get_chart_data_period( $time_window );

		if ( ! $period ) {
			return $dataset;
		}

		$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
		/** @noinspection PhpUnhandledExceptionInspection */
		$date_now     = new \DateTime();
		$order_status = (array) apply_filters( 'atum/dashboard/statistics_widget/orders/order_status', [ 'wc-processing', 'wc-completed' ] );

		foreach ( $period as $dt ) {

			/**
			 * Variable definition
			 *
			 * @var \DateTime $dt
			 */
			$interval = date_diff( $dt, $date_now );

			// Bypass all the future dates.
			if ( $interval->invert ) {
				break;
			}

			$data[] = self::get_orders_stats( array(
				'status'          => $order_status,
				'date_start'      => $dt->format( 'Y-m-d H:i:s' ),
				'date_end'        => 'year' === $period_time ? 'last day of ' . $dt->format( 'F Y' ) . ' 23:59:59' : $dt->format( 'Y-m-d 23:59:59' ),
				'formatted_value' => FALSE,
			) );
		}

		if ( ! empty( $data ) ) {
			$dataset['value']    = wp_list_pluck( $data, 'value' );
			$dataset['products'] = wp_list_pluck( $data, 'orders' );
		}

		return $dataset;

	}

	/**
	 * Get the right chart data's date period for the specified time window
	 *
	 * @since 1.4.0
	 *
	 * @param string $time_window   The time window that will specify the x axis in the chart.
	 *
	 * @return \DatePeriod|null
	 */
	private static function get_chart_data_period( $time_window ) {

		$which       = FALSE !== strpos( $time_window, 'previous' ) ? 'last' : 'this';
		$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
		$period      = NULL;

		switch ( $period_time ) {
			case 'year':
				$period = self::get_date_period( "first day of January $which year 00:00:00", "last day of December $which year 23:59:59", '1 month' );
				break;

			case 'month':
				$period = self::get_date_period( "first day of $which month 00:00:00", "last day of $which month 23:59:59" );
				break;

			case 'week':
				$period = self::get_date_period( "$which week 00:00:00", "$which week +6 days 23:59:59" );
				break;

		}

		return $period;

	}

	/**
	 * Get a date period in a time window at the specified interval
	 *
	 * @since 1.4.0
	 *
	 * @param string $date_start    The period's start date. Must be an string compatible with strtotime.
	 * @param string $date_end      The period's end date. Must be an string compatible with strtotime.
	 * @param string $interval      Optional. The period' interval. Must be an string compatible with strtotime.
	 *
	 * @return \DatePeriod
	 */
	public static function get_date_period( $date_start, $date_end, $interval = '1 day' ) {

		/** @noinspection PhpUnhandledExceptionInspection */
		$start    = new \DateTime( $date_start );
		$interval = \DateInterval::createFromDateString( $interval );
		/** @noinspection PhpUnhandledExceptionInspection */
		$end         = new \DateTime( $date_end );
		$date_period = new \DatePeriod( $start, $interval, $end );

		return $date_period;
	}

	/**
	 * If the site is not using the new tables, use the legacy methods
	 *
	 * @since 1.5.0
	 * @deprecated Only for backwards compatibility and will be removed in a future version.
	 */
	use WidgetHelpersLegacyTrait;

	/**
	 * Get the current stock levels
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	public static function get_stock_levels() {

		/**
		 * If the site is not using the new tables, use the legacy method
		 *
		 * @since 1.5.0
		 * @deprecated Only for backwards compatibility and will be removed in a future version.
		 */
		if ( ! Helpers::is_using_new_wc_tables() ) {
			return self::get_stock_levels_legacy();
		}

		global $wpdb;

		$stock_counters = array(
			'count_in_stock'  => 0,
			'count_out_stock' => 0,
			'count_low_stock' => 0,
			'count_all'       => 0,
			'count_unmanaged' => 0,
		);

		$show_unmanaged_counter      = 'yes' === Helpers::get_option( 'unmanaged_counters' );
		$products                    = Helpers::get_all_products();
		$stock_counters['count_all'] = count( $products );

		$variations = self::get_children( 'variable', 'product_variation' );

		// Add the Variations to the posts list.
		if ( $variations ) {
			// The Variable products are just containers and don't count for the list views.
			$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
			$products                     = array_unique( array_merge( array_diff( $products, self::$variable_products ), $variations ) );
		}

		$group_items = self::get_children( 'grouped' );

		// Add the Group Items to the posts list.
		if ( $group_items ) {
			// The Grouped products are just containers and don't count for the list views.
			$stock_counters['count_all'] += ( count( $group_items ) - count( self::$grouped_products ) );
			$products                     = array_unique( array_merge( array_diff( $products, self::$grouped_products ), $group_items ) );

		}

		// WC Subscriptions compatibility.
		$subscription_variations = [];
		if ( class_exists( '\WC_Subscriptions' ) ) {

			$subscription_variations = self::get_children( 'variable-subscription', 'product_variation' );

			// Add the Variations to the posts list.
			if ( $subscription_variations ) {
				// The Variable products are just containers and don't count for the list views.
				$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
				$products                     = array_unique( array_merge( array_diff( $products, self::$variable_products ), $subscription_variations ) );
			}

		}

		if ( $products ) {

			$post_types = $variations || $subscription_variations ? [ 'product', 'product_variation' ] : [ 'product' ];

			/*
			 * Unmanaged products
			 */
			if ( $show_unmanaged_counter ) {
				$products_unmanaged_status = Helpers::get_unmanaged_products( $post_types, TRUE );
				
				$stock_counters['count_in_stock'] += count( array_filter( $products_unmanaged_status, function ( $row ) {
					
					return ( 'instock' === $row[1] );
				} ) );
				
				$stock_counters['count_out_stock'] += count( array_filter( $products_unmanaged_status, function ( $row ) {
					
					return ( 'outofstock' === $row[1] );
				} ) );
			}
			else {
				$products_unmanaged_status = Helpers::get_unmanaged_products( $post_types, FALSE );
			}

			$products_unmanaged                = array_column( $products_unmanaged_status, 0 );
			$stock_counters['count_unmanaged'] = count( $products_unmanaged );

			// Remove the unmanaged from the products list.
			if ( ! empty( $products_unmanaged ) && ! empty( $products ) ) {
				$matching = array_intersect( $products, $products_unmanaged );

				if ( ! empty( $matching ) ) {
					$products = array_diff( $products, $matching );
				}
			}

			/*
			 * Products In Stock
			 */
			$args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
				'fields'         => 'ids',
				'post__in'       => $products,
			);
			
			self::$wc_query_data['where'][] = array(
				'key'   => 'stock_status',
				'value' => array( 'instock', 'onbackorder' ),
			);

			add_filter( 'posts_clauses', array( __CLASS__, 'wc_product_data_query_clauses' ) );
			$products_in_stock = new \WP_Query( apply_filters( 'atum/dashboard_widgets/stock_counters/in_stock', $args ) );
			remove_filter( 'posts_clauses', array( __CLASS__, 'wc_product_data_query_clauses' ) );

			$products_in_stock                 = $products_in_stock->posts;
			$stock_counters['count_in_stock'] += count( $products_in_stock );
			
			/*
			 * Products Out of Stock
			 */
			$products_not_stock = array_diff( $products, $products_in_stock, $products_unmanaged );
			$args               = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
				'fields'         => 'ids',
				'post__in'       => $products_not_stock,
			);

			self::$wc_query_data['where'] = array(
				array(
					'key'     => 'stock_status',
					'value'   => 'outofstock',
				),
			);

			$products_out_stock                 = new \WP_Query( apply_filters( 'atum/dashboard_widgets/stock_counters/out_stock', $args ) );
			$products_out_stock                 = $products_out_stock->posts;
			$stock_counters['count_out_stock'] += count( $products_out_stock );

			/*
			 * Products with low stock
			 */
			if ( ! empty( $products_in_stock ) ) {

				$days_to_reorder = absint( Helpers::get_option( 'sale_days', Settings::DEFAULT_SALE_DAYS ) );

				// Compare last seven days average sales per day * re-order days with current stock.
				$str_sales = "
					(SELECT	(
						SELECT MAX(CAST( meta_value AS SIGNED )) AS q 
						FROM {$wpdb->prefix}woocommerce_order_itemmeta 
						WHERE meta_key IN ('_product_id', '_variation_id') 
						AND order_item_id = item.order_item_id
					) AS IDs,
				    CEIL(SUM((
				        SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta 
				        WHERE meta_key = '_qty' AND order_item_id = item.order_item_id))/7*$days_to_reorder
			        ) AS qty
					FROM $wpdb->posts AS orders
				    INNER JOIN {$wpdb->prefix}woocommerce_order_items AS item ON (orders.ID = item.order_id)
					INNER JOIN $wpdb->postmeta AS order_meta ON (orders.ID = order_meta.post_id)
					WHERE (orders.post_type = 'shop_order'
				    AND orders.post_status IN ('wc-completed', 'wc-processing') AND item.order_item_type ='line_item'
				    AND order_meta.meta_key = '_paid_date'
				    AND order_meta.meta_value >= '" . Helpers::date_format( '-7 days' ) . "')
					GROUP BY IDs) AS sales";

				$str_statuses = "
					(SELECT p.ID, IF( 
						CAST( IFNULL(sales.qty, 0) AS DECIMAL(10,2) ) <= 
						CAST( IF( LENGTH({$wpdb->postmeta}.meta_value) = 0 , 0, {$wpdb->postmeta}.meta_value) AS DECIMAL(10,2) ), TRUE, FALSE
					) AS status
					FROM $wpdb->posts AS p
				    LEFT JOIN {$wpdb->postmeta} ON (p.ID = {$wpdb->postmeta}.post_id)
				    LEFT JOIN " . $str_sales . " ON (p.ID = sales.IDs)
					WHERE {$wpdb->postmeta}.meta_key = '_stock'
		            AND p.post_type IN ('" . implode( "','", $post_types ) . "')
		            AND p.ID IN (" . implode( ',', $products_in_stock ) . ' )
		            ) AS statuses';

				$str_sql = apply_filters( 'atum/dashboard_widgets/stock_counters/low_stock', "SELECT ID FROM $str_statuses WHERE status IS FALSE;" );

				$products_low_stock                = $wpdb->get_results( $str_sql ); // WPCS: unprepared SQL ok.
				$products_low_stock                = wp_list_pluck( $products_low_stock, 'ID' );
				$stock_counters['count_low_stock'] = count( $products_low_stock );

			}
			
		}

		return $stock_counters;

	}

	/**
	 * Get all the available children products of the published parent products (Variable and Grouped)
	 *
	 * @since 1.4.0
	 *
	 * @param string $parent_type   The parent product type.
	 * @param string $post_type     Optional. The children post type.
	 *
	 * @return array|bool
	 */
	private static function get_children( $parent_type, $post_type = 'product' ) {

		/**
		 * If the site is not using the new tables, use the legacy method
		 *
		 * @since 1.5.0
		 * @deprecated Only for backwards compatibility and will be removed in a future version.
		 */
		if ( ! Helpers::is_using_new_wc_tables() ) {
			return self::get_children_legacy( $parent_type, $post_type );
		}

		global $wpdb;

		// Get all the published Variables first.
		$post_statuses = current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ];
		$where         = " p.post_type = 'product' AND p.post_status IN('" . implode( "','", $post_statuses ) . "')";

		if ( ! empty( $post_in ) ) {
			$where .= ' AND p.ID IN (' . implode( ',', $post_in ) . ')';
		}

		$parents = $wpdb->get_col( $wpdb->prepare( "
			SELECT p.ID FROM $wpdb->posts p  
			LEFT JOIN {$wpdb->prefix}wc_products pr ON p.ID = pr.product_id  
			WHERE $where AND pr.type = %s
			GROUP BY p.ID
		", $parent_type ) ); // WPCS: unprepared sql ok.

		if ( ! empty( $parents ) ) {

			// Save them to be used when preparing the list query.
			// TODO: WHAT ABOUT VARIABLE PRODUCT LEVELS?
			if ( in_array( $parent_type, [ 'variable', 'variable-subscription' ], TRUE ) ) {
				self::$variable_products = array_merge( self::$variable_products, $parents );
			} elseif ( 'grouped' === $parent_type ) {
				self::$grouped_products = array_merge( self::$grouped_products, $parents );
			}

			$children_args = (array) apply_filters( 'atum/dashboard_widgets/get_children/children_args', array(
				'post_type'       => $post_type,
				'post_status'     => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
				'posts_per_page'  => - 1,
				'fields'          => 'ids',
				'post_parent__in' => $parents,
			) );

			$children = new \WP_Query( $children_args );

			if ( $children->found_posts ) {
				return $children->posts;
			}

		}

		return FALSE;

	}

	/**
	 * Customize the WP_Query to handle WC product data from the new tables
	 *
	 * @since 1.5.0
	 *
	 * @param array $pieces
	 *
	 * @return array
	 */
	public static function wc_product_data_query_clauses( $pieces ) {
		return Helpers::product_data_query_clauses( self::$wc_query_data, $pieces, 'wc_products' );
	}

	/**
	 * Builds a product type dowpdown for current stock value widget
	 *
	 * @since 1.5.0.3
	 *
	 * @param string $selected  The pre-selected option.
	 * @param string $class     The dropdown class name.
	 *
	 * @return string
	 */
	public static function product_types_dropdown( $selected = '', $class = 'dropdown_product_type' ) {

		$terms = get_terms( array(
			'taxonomy'   => 'product_type',
			'hide_empty' => FALSE,
		) );

		$allowed_types = apply_filters( 'atum/product_types_dropdown/allowed_types', Globals::get_product_types() );

		$output  = '<select name="product_type" class="' . $class . '" autocomplete="off">';
		$output .= '<option value=""' . selected( $selected, '', FALSE ) . '>' . __( 'All product types', ATUM_TEXT_DOMAIN ) . '</option>';

		foreach ( $terms as $term ) {

			if ( ! in_array( $term->slug, $allowed_types ) ) {
				continue;
			}

			$output .= '<option value="' . sanitize_title( $term->name ) . '"' . selected( $term->slug, $selected, FALSE ) . '>';

			switch ( $term->name ) {
				case 'grouped':
					$output .= __( 'Grouped product', ATUM_TEXT_DOMAIN );
					break;

				case 'variable':
					$output .= __( 'Variable product', ATUM_TEXT_DOMAIN );
					break;

				case 'simple':
					$output .= __( 'Simple product', ATUM_TEXT_DOMAIN );
					break;

				// Assuming that we'll have other types in future.
				default:
					$output .= ucfirst( $term->name );
					break;
			}

			$output .= '</option>';

			if ( 'simple' === $term->name ) {
				$output .= '<option value="downloadable"' . selected( 'downloadable', $selected, FALSE ) . '> &rarr; ' . __( 'Downloadable', ATUM_TEXT_DOMAIN ) . '</option>';
				$output .= '<option value="virtual"' . selected( 'virtual', $selected, FALSE ) . '> &rarr; ' . __( 'Virtual', ATUM_TEXT_DOMAIN ) . '</option>';
			}
		}

		$extra_output = '';

		$output .= apply_filters( 'atum/dashboard_widgets/current_stock_counters/product_types_dropdown', $extra_output );

		$output .= '</select>';

		return $output;

	}

	/**
	 * Get all products in stock count
	 *
	 * @since 1.5.1
	 *
	 * @param string $category
	 * @param string $product_type
	 *
	 * @return array
	 */
	public static function get_items_in_stock( $category = null, $product_type = null ) {

		if ( ! Helpers::is_using_new_wc_tables() ) {
			return self::get_items_in_stock_legacy( $category, $product_type );
		}

		// Init values counter.
		$counters = [
			'items_stocks_counter'          => 0,
			'items_purcharse_price_total'   => 0,
			'items_without_purcharse_price' => 0,
		];

		/*
		 * Products In Stock
		 */
		
		$products   = Helpers::get_all_products();
		$variations = $filtered_variations = [];
		
		foreach ( Globals::get_inheritable_product_types() as $inheritable_product_type ) {
			
			if ( 'grouped' === $inheritable_product_type && 'grouped' === $product_type ) {
				$filtered_variations = self::get_children( 'grouped' );
			}
			elseif ( 'grouped' !== $inheritable_product_type ) {
				
				$current_variations = self::get_children( $inheritable_product_type, 'product_variation' );
				
				if ( $inheritable_product_type === $product_type ) {
					$filtered_variations = $current_variations;
				}
				
				if ( $current_variations ) {
					$variations = array_unique( array_merge( $variations, $current_variations ) );
				}
				
			}
			
		}
		
		// Add the Variations to the posts list. We skip grouped products.
		if ( $variations ) {
			$products = array_unique( array_merge( $products, $variations ) );
		}
		
		$temp_wc_query_data = self::$wc_query_data; // Save the original value.
		
		self::$wc_query_data['where'] = [];
		
		if ( $products ) {
			
			$post_types = $variations ? [ 'product', 'product_variation' ] : [ 'product' ];
			
			$args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
				'fields'         => 'ids',
				'tax_query'      => array(
					'relation' => 'AND',
				),
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_manage_stock',
						'value' => 'yes',
					),
				),
			
			);
			
			// Check if category filter data exist.
			if ( $category ) {
				array_push( $args['tax_query'], array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => array( $category ),
				) );
			}
			
			// Check if product type filter data exist.
			if ( $product_type ) {
				if ( in_array( $product_type, Globals::get_inheritable_product_types() ) && 'bundle' !== $product_type ) {
					
					if ( $filtered_variations ) {
						$args['post__in'] = $filtered_variations;
					}
					else {
						return $counters;
					}
				}
				elseif ( 'downloadable' === $product_type ) {
					self::$wc_query_data['where'][] = array(
						'key'   => 'downloadable',
						'value' => array( '1' ),
					);
				}
				elseif ( 'virtual' === $product_type ) {
					self::$wc_query_data['where'][] = array(
						'key'   => 'virtual',
						'value' => array( '1' ),
					);
				}
				elseif ( in_array( $product_type, [ 'raw-material', 'product-part', 'variable-product-part', 'variable-raw-material' ] ) ) {
					self::$wc_query_data['where'][] = array(
						'key'   => 'type',
						'value' => $product_type,
					);
				}
				else {
					array_push( $args['tax_query'], array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => array( $product_type ),
					) );
				}
			}
			
		}

		// Get products.
		add_filter( 'posts_clauses', array( __CLASS__, 'wc_product_data_query_clauses' ) );
		$products_in_stock = new \WP_Query( apply_filters( 'atum/dashboard_widgets/current_stock_counters/in_stock', $args ) );
		remove_filter( 'posts_clauses', array( __CLASS__, 'wc_product_data_query_clauses' ) );

		// Get current stock values.
		foreach ( $products_in_stock->posts as $product_id ) {
			$product                 = Helpers::get_atum_product( $product_id );
			$product_stock           = (float) $product->get_stock_quantity();
			$product_purcharse_price = (float) $product->get_purchase_price();

			if ( $product_stock && $product_stock > 0 ) {
				$counters['items_stocks_counter'] += $product_stock;
				if ( $product_purcharse_price && ! empty( $product_purcharse_price ) ) {
					$counters['items_purcharse_price_total'] += ( $product_purcharse_price * $product_stock );
				}
				else {
					$counters['items_without_purcharse_price'] += $product_stock;
				}
			}
		}

		self::$wc_query_data = $temp_wc_query_data; // Restore the original value.

		return apply_filters( 'atum/dashboard_widgets/current_stock_counters/counters', $counters, $products_in_stock->posts );
	}

}
