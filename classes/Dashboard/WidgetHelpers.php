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

use Atum\Inc\Helpers;
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
						$date_days_start = new \DateTime( $date_start );
						$date_days_end   = new \DateTime( ( isset( $date_end ) ? $date_end : 'now' ) );
						$days            = $date_days_end->diff( $date_days_start )->days;
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
		) );

		$data = $dataset = array();

		if ( empty( $products ) ) {
			return $dataset;
		}

		$period = self::get_chart_data_period( $time_window );

		if ( ! $period ) {
			return $dataset;
		}

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

		$period_time  = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
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

		$period_time  = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
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

		$start       = new \DateTime( $date_start );
		$interval    = \DateInterval::createFromDateString( $interval );
		$end         = new \DateTime( $date_end );
		$date_period = new \DatePeriod( $start, $interval, $end );

		return $date_period;
	}

	/**
	 * Get the current stock levels
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	public static function get_stock_levels() {

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
		if ( class_exists( '\WC_Subscriptions' ) ) {

			$subscription_variations = self::get_children( 'variable-subscription', 'product_variation' );

			// Add the Variations to the posts list.
			if ( $subscription_variations ) {
				// The Variable products are just containers and don't count for the list views.
				$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
				$products                     = array_unique( array_merge( array_diff( $products, self::$variable_products ), $variations ) );
			}

		}

		if ( $products ) {

			$post_types = $variations ? [ 'product', 'product_variation' ] : [ 'product' ];

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
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_manage_stock',
						'value' => 'yes',
					),
					array(
						'key'     => '_stock',
						'value'   => 0,
						'type'    => 'numeric',
						'compare' => '>',
					),
				),
			);

			$products_in_stock                 = new \WP_Query( apply_filters( 'atum/dashboard_widgets/stock_counters/in_stock', $args ) );
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
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => '_stock',
							'value'   => 0,
							'type'    => 'numeric',
							'compare' => '<=',
						),
						array(
							'key'     => '_stock',
							'compare' => 'NOT EXISTS',
						),
					),
					array(
						'relation' => 'OR',
						array(
							'key'   => '_backorders',
							'value' => 'no',
							'type'  => 'char',
						),
						array(
							'key'     => '_backorders',
							'compare' => 'NOT EXISTS',
						),
					),

				),
				'post__in'       => $products_not_stock,
			);

			$products_out_stock                 = new \WP_Query( apply_filters( 'atum/dashboard_widgets/stock_counters/out_stock', $args ) );
			$products_out_stock                 = $products_out_stock->posts;
			$stock_counters['count_out_stock'] += count( $products_out_stock );

			/*
			 * Products with low stock
			 */
			if ( $stock_counters['count_in_stock'] ) {

				$days_to_reorder = absint( Helpers::get_option( 'sale_days', Settings::DEFAULT_SALE_DAYS ) );

				// Compare last seven days average sales per day * re-order days with current stock.
				$str_sales = "(SELECT			   
				    (SELECT MAX(CAST( meta_value AS SIGNED )) AS q FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key IN ('_product_id', '_variation_id') AND order_item_id = `item`.`order_item_id`) AS IDs,
				    CEIL(SUM((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_qty' AND order_item_id = `item`.`order_item_id`))/7*$days_to_reorder) AS qty
					FROM `{$wpdb->posts}` AS `order`
					    INNER JOIN `{$wpdb->prefix}woocommerce_order_items` AS `item` ON (`order`.`ID` = `item`.`order_id`)
						INNER JOIN `{$wpdb->postmeta}` AS `order_meta` ON (`order`.ID = `order_meta`.`post_id`)
					WHERE (`order`.`post_type` = 'shop_order'
					    AND `order`.`post_status` IN ('wc-completed', 'wc-processing') AND `item`.`order_item_type` ='line_item'
					    AND `order_meta`.`meta_key` = '_paid_date'
					    AND `order_meta`.`meta_value` >= '" . Helpers::date_format( '-7 days' ) . "')
					GROUP BY IDs) AS sales";

				$str_states = "(SELECT `{$wpdb->posts}`.`ID`,
					IF( CAST( IFNULL(`sales`.`qty`, 0) AS DECIMAL(10,2) ) <= 
						CAST( IF( LENGTH(`{$wpdb->postmeta}`.`meta_value`) = 0 , 0, `{$wpdb->postmeta}`.`meta_value`) AS DECIMAL(10,2) ), TRUE, FALSE) AS state
					FROM `{$wpdb->posts}`
					    LEFT JOIN `{$wpdb->postmeta}` ON (`{$wpdb->posts}`.`ID` = `{$wpdb->postmeta}`.`post_id`)
					    LEFT JOIN " . $str_sales . " ON (`{$wpdb->posts}`.`ID` = `sales`.`IDs`)
					WHERE (`{$wpdb->postmeta}`.`meta_key` = '_stock'
			            AND `{$wpdb->posts}`.`post_type` IN ('" . implode( "','", $post_types ) . "')
			            AND (`{$wpdb->posts}`.`ID` IN (" . implode( ', ', $products_in_stock ) . ')) )) AS states';

				$str_sql = apply_filters( 'atum/dashboard_widgets/stock_counters/low_stock', "SELECT `ID` FROM $str_states WHERE state IS FALSE;" );

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

		// Get the published Variables first.
		$parent_args = (array) apply_filters( 'atum/dashboard_widgets/get_children/parent_args', array(
			'post_type'      => 'product',
			'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
			'posts_per_page' => - 1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $parent_type,
				),
			),
		) );

		$parents = new \WP_Query( $parent_args );

		if ( $parents->found_posts ) {

			// Save them to be used when preparing the list query.
			if ( 'variable' === $parent_type ) {
				self::$variable_products = array_merge( self::$variable_products, $parents->posts );
			}
			else {
				self::$grouped_products = array_merge( self::$grouped_products, $parents->posts );
			}

			$children_args = (array) apply_filters( 'atum/dashboard_widgets/get_children/children_args', array(
				'post_type'       => $post_type,
				'post_status'     => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
				'posts_per_page'  => - 1,
				'fields'          => 'ids',
				'post_parent__in' => $parents->posts,
			) );

			$children = new \WP_Query( $children_args );

			if ( $children->found_posts ) {
				return $children->posts;
			}

		}

		return FALSE;

	}

}
