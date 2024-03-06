<?php
/**
 * Helper functions for Widgets
 *
 * @package        Atum
 * @subpackage     Dashboard
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2024 Stock Management Labs™
 *
 * @since          1.4.0
 */

namespace Atum\Dashboard;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;


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
	 * The ATUM product data used in WP_Query
	 *
	 * @var array
	 */
	protected static $atum_query_data = array();

	/**
	 * Get the stats of products that were sold after the specified date
	 *
	 * @since 1.4.0
	 *
	 * @param array $atts {
	 *      Array of stats filter params.
	 *
	 *      @type array  $types              An array of stats to get. Possible values: "sales" and/or "lost_sales"*
	 *      @type string $date_start         The date from when to start the items' sales calculations (must be a string format convertible with strtotime)
	 *      @type string $date_end           Optional. The max date to calculate the items' sales (must be a string format convertible with strtotime)
	 *      @type array  $products           Optional. The array of products to include in calculations
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
		 * @var string $date_start
		 * @var string $date_end
		 * @var array  $products
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

		$products_sold  = Helpers::get_sold_last_days( $date_start, ( isset( $date_end ) ? $date_end : NULL ), ! empty( $products ) ? $products : NULL, [ 'qty', 'total', 'prod_id' ] );
		$lost_processed = array();

		if ( $products_sold ) {

			foreach ( $products_sold as $row ) {

				if ( in_array( 'sales', $types ) ) {
					$stats['products'] += floatval( $row['QTY'] );
					$stats['value']    += floatval( $row['TOTAL'] );
				}

				if ( in_array( 'lost_sales', $types ) && ! in_array( $row['PROD_ID'], $lost_processed ) ) {

					if ( ! isset( $days ) || $days <= 0 ) {
						/* @noinspection PhpUnhandledExceptionInspection */
						$date_days_start = new \DateTime( $date_start );
						/* @noinspection PhpUnhandledExceptionInspection */
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

		$data = $dataset = array();

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

		$which       = str_contains( $time_window, 'previous' ) ? 'last' : 'this';
		$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
		$period      = NULL;

		switch ( $period_time ) {
			case 'year':
				$period = self::get_date_period( "first day of January $which year midnight", "last day of December $which year 23:59:59", '1 month' );
				break;

			case 'month':
				$period = self::get_date_period( "first day of $which month midnight", "last day of $which month 23:59:59" );
				break;

			case 'week':
				$period = self::get_date_period( "$which week midnight", "$which week +6 days 23:59:59" );
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

		/* @noinspection PhpUnhandledExceptionInspection */
		$start    = new \DateTime( $date_start );
		$interval = \DateInterval::createFromDateString( $interval );
		/* @noinspection PhpUnhandledExceptionInspection */
		$end = new \DateTime( $date_end );

		return new \DatePeriod( $start, $interval, $end );
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
			'count_in_stock'       => 0,
			'count_out_stock'      => 0,
			'count_restock_status' => 0,
			'count_all'            => 0,
			'count_unmanaged'      => 0,
		);

		$products = Helpers::get_all_products();

		if ( ! empty( $products ) ) {

			$show_unmanaged_counter      = 'yes' === Helpers::get_option( 'unmanaged_counters', 'no' );
			$stock_counters['count_all'] = count( $products );

			$variations = self::get_children( 'variable', 'product_variation' );

			// Add the Variations to the posts list.
			if ( ! empty( $variations ) ) {
				// The Variable products are just containers and don't count for the list views.
				$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
			}

			// TODO: WE ARE EXCLUDING GROUPED PRODUCTS FOR NOW AS THEIR CHILDREN ARE NOT BEING CALCULATED CORRECTLY.
			/*$group_items = self::get_children( 'grouped' );

			// Add the Group Items to the posts list.
			if ( ! empty( $group_items ) ) {
				// The Grouped products are just containers and don't count for the list views.
				$stock_counters['count_all'] += ( count( $group_items ) - count( self::$grouped_products ) );

			}*/

			// WC Subscriptions compatibility.
			$subscription_variations = [];
			if ( class_exists( '\WC_Subscriptions' ) ) {

				$subscription_variations = self::get_children( 'variable-subscription', 'product_variation' );

				// Add the Variations to the posts list.
				if ( $subscription_variations ) {
					// The Variable products are just containers and don't count for the list views.
					$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
				}

			}

			$post_types = ( ! empty( $variations ) || ! empty( $subscription_variations ) ) ? [ 'product', 'product_variation' ] : [ 'product' ];

			/*
			 * Unmanaged products
			 */
			if ( $show_unmanaged_counter ) {

				$products_unmanaged_status = Helpers::get_unmanaged_products( $post_types, TRUE );

				$stock_counters['count_in_stock'] += count( array_filter( $products_unmanaged_status, function ( $row ) {
					return 'instock' === $row[1];
				} ) );

				$stock_counters['count_out_stock'] += count( array_filter( $products_unmanaged_status, function ( $row ) {
					return 'outofstock' === $row[1];
				} ) );

			}
			else {
				$products_unmanaged_status = Helpers::get_unmanaged_products( $post_types, FALSE );
			}

			$products_unmanaged                = array_column( $products_unmanaged_status, 0 );
			$stock_counters['count_unmanaged'] = count( $products_unmanaged );

			$product_statuses = Globals::get_queryable_product_statuses();

			/*
			 * Products In Stock
			 */
			// TODO: WHAT ABOUT PRODUCTS WITH MI OR PRODUCTS WITH CALCULATED STOCK?
			$args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'post_status'    => $product_statuses,
				'fields'         => 'ids',
				// Exclude variable and grouped products.
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => [ 'variable', 'variable-subscription', 'grouped' ],
						'operator' => 'NOT IN',
					),
				),
				// Exclude unmanaged products.
				'meta_query'     => array(
					array(
						'key'   => '_manage_stock',
						'value' => 'yes',
					),
				),
			);

			self::$atum_query_data['where'][] = apply_filters( 'atum/dashboard/get_stock_levels/in_stock_products_atum_args', array(
				'key'   => 'atum_stock_status',
				'value' => [ 'instock', 'onbackorder' ],
				'type'  => 'CHAR',
			) );

			add_filter( 'posts_clauses', array( __CLASS__, 'atum_product_data_query_clauses' ) );

			$products_in_stock                 = new \WP_Query( apply_filters( 'atum/dashboard/get_stock_levels/in_stock_products_args', $args ) );
			$products_in_stock                 = $products_in_stock->posts;
			$stock_counters['count_in_stock'] += count( $products_in_stock );
			self::$atum_query_data             = array(); // Empty the ATUM query data to not conflict with next queries.

			/*
			 * Products Out of Stock
			 */
			self::$atum_query_data['where'][] = apply_filters( 'atum/dashboard/get_stock_levels/out_stock_products_atum_args', array(
				'key'   => 'atum_stock_status',
				'value' => 'outofstock',
				'type'  => 'CHAR',
			) );

			$products_out_stock                 = new \WP_Query( apply_filters( 'atum/dashboard/get_stock_levels/out_stock_products_args', $args ) );
			$products_out_stock                 = $products_out_stock->posts;
			$stock_counters['count_out_stock'] += count( $products_out_stock );
			self::$atum_query_data              = array(); // Empty the ATUM query data to not conflict with next queries.

			// ATUM query clauses not needed anymore.
			remove_filter( 'posts_clauses', array( __CLASS__, 'atum_product_data_query_clauses' ) );

			/*
			 * Products in restock status
			 */
			if ( ! empty( $products_in_stock ) ) {

				$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
				$str_sql                 = apply_filters( 'atum/dashboard/get_stock_levels/restock_status_products', "
					SELECT product_id FROM $atum_product_data_table WHERE restock_status = 1
				" );

				$products_restock_status                = $wpdb->get_col( $str_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$stock_counters['count_restock_status'] = count( $products_restock_status );

			}

		}

		return $stock_counters;

	}

	/**
	 * Customize the WP_Query to handle ATUM product data
	 *
	 * @since 1.7.1
	 *
	 * @param array $pieces
	 *
	 * @return array
	 */
	public static function atum_product_data_query_clauses( $pieces ) {
		return Helpers::product_data_query_clauses( self::$atum_query_data, $pieces );
	}

	/**
	 * Get all the available children products of the published parent products
	 *
	 * @since 1.4.0
	 *
	 * @param string $parent_type The parent product type.
	 * @param string $post_type   Optional. The children post type.
	 *
	 * @return array|bool
	 */
	private static function get_children( $parent_type, $post_type = 'product' ) {

		global $wpdb;

		// Get the published parents first.
		$products_visibility = Globals::get_queryable_product_statuses();
		$parent_product_type = get_term_by( 'slug', $parent_type, 'product_type' );

		if ( ! $parent_product_type ) {
			return FALSE;
		}

		// Let adding extra parent types externally.
		$parent_product_type_ids = apply_filters( 'atum/dashboard/get_children/parent_product_types', [ $parent_product_type->term_taxonomy_id ], $parent_type );

		$parents_sql = "
			SELECT DISTINCT p.ID FROM $wpdb->posts p
			LEFT JOIN $wpdb->term_relationships tr ON (p.ID = tr.object_id) 
			WHERE tr.term_taxonomy_id IN (" . implode( ',', $parent_product_type_ids ) . ") AND p.post_type = 'product' 
			AND p.post_status IN ('" . implode( "','", $products_visibility ) . "') 
			GROUP BY p.ID		 
		";

		// TODO: THIS CANNOT WORK FOR GROUPED PRODUCTS BECAUSE THESE ITEMS ARE NOT SAVING THEIR PARENTS IN THE post_parent COL ANYMORE.
		// phpcs:disable WordPress.DB.PreparedSQL
		$children_sql = $wpdb->prepare("
			SELECT p.ID, p.post_parent FROM $wpdb->posts p
			WHERE p.post_parent IN (
				$parents_sql
			) AND p.post_type = %s AND p.post_status IN ('" . implode( "','", $products_visibility ) . "')
		", $post_type );
		// phpcs:enable

		$results = $wpdb->get_results( $children_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $results ) ) {

			$parents = array_unique( array_map( 'absint', wp_list_pluck( $results, 'post_parent' ) ) );

			// Save them to be used when counting products.
			if ( str_contains( $parent_type, 'variable' ) ) {
				self::$variable_products = array_merge( self::$variable_products, $parents );
			}
			else {
				self::$grouped_products = array_merge( self::$grouped_products, $parents );
			}

			return array_map( 'absint', wp_list_pluck( $results, 'ID' ) );

		}

		return FALSE;

	}

	/**
	 * Builds a product type dowpdown for current stock value widget
	 *
	 * @since 1.5.0.3
	 *
	 * @param string $selected       The pre-selected option.
	 * @param string $class          The dropdown class name.
	 * @param array  $excluded_types Excluded types.
	 *
	 * @return string
	 */
	public static function product_types_dropdown( $selected = '', $class = 'dropdown_product_type', $excluded_types = [] ) {

		$terms = get_terms( array(
			'taxonomy'   => 'product_type',
			'hide_empty' => FALSE,
		) );

		$allowed_types = apply_filters( 'atum/product_types_dropdown/allowed_types', Globals::get_product_types() );

		$output  = '<select name="product_type" class="' . $class . '" autocomplete="off">';
		$output .= '<option value=""' . selected( $selected, '', FALSE ) . '>' . __( 'All product types', ATUM_TEXT_DOMAIN ) . '</option>';

		foreach ( $terms as $term ) {

			if ( ! in_array( $term->slug, $allowed_types ) || in_array( $term->slug, $excluded_types ) ) {
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

		$output .= apply_filters( 'atum/dashboard/current_stock_value_widget/product_types_dropdown', $extra_output );

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

		global $wpdb;
		$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		// Init values counter.
		$counters = [
			'items_stocks_counter'         => 0,
			'items_purchase_price_total'   => 0,
			'items_without_purchase_price' => 0,
		];

		self::$atum_query_data = array(); // Reset value.

		$args = array(
			'post_type'      => [ 'product', 'product_variation' ],
			'posts_per_page' => - 1,
			'post_status'    => Globals::get_queryable_product_statuses(),
			'fields'         => 'ids',
			'tax_query'      => array(
				'relation' => 'AND',
				// Exclude the grouped products.
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => [ 'grouped', 'bundle', 'composite' ],
					'operator' => 'NOT IN',
				),
			),
			'meta_query'     => array(
				// Exclude unmanaged products.
				// TODO: DO WE NEED TO EXCLUDE UNMANAGED PRODUCTS?
				/*array(
					'key'   => '_manage_stock',
					'value' => 'yes',
				),*/
			),
		);

		// As when we filter by any taxonomy, the variation products are lost,
		// we need to create another query to get the children.
		$children_query_needed = FALSE;

		// Check if category filter exists.
		if ( $category ) {

			$args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $category,
			);

			$children_query_needed = TRUE;

		}

		// Check if product type filter exists.
		if ( $product_type ) {

			if ( 'downloadable' === $product_type ) {

				$args['meta_query'][] = array(
					'key'     => '_downloadable',
					'value'   => 'yes',
					'compare' => '=',
				);

			}
			elseif ( 'virtual' === $product_type ) {

				$args['meta_query'][] = array(
					'key'     => '_virtual',
					'value'   => 'yes',
					'compare' => '=',
				);

			}
			else {

				$args['tax_query'][] = array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $product_type,
				);

				$children_query_needed = TRUE;

			}

		}

		// We need to apply the stock_status criteria to query only if we aren't filtering. Otherwise, may exclude unmanaged parents and get no results.
		if ( ! $children_query_needed ) {
			self::$atum_query_data['where'][] = apply_filters( 'atum/dashboard/get_items_in_stock/in_stock_products_atum_args', array(
				'key'   => 'atum_stock_status',
				'value' => [ 'instock', 'onbackorder' ],
				'type'  => 'CHAR',
			) );
		}

		// Get products in stock.
		add_filter( 'posts_clauses', array( __CLASS__, 'atum_product_data_query_clauses' ) );
		$products_in_stock_query = new \WP_Query( apply_filters( 'atum/dashboard/get_items_in_stock/in_stock_products_args', $args ) );
		remove_filter( 'posts_clauses', array( __CLASS__, 'atum_product_data_query_clauses' ) );

		$products_in_stock_sql = $products_in_stock_query->request;

		if ( class_exists( '\WC_Product_Bundle' ) && ( ! $product_type || 'bundle' === $product_type ) ) {
			$bundle_args = array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'bundle',
					'operator' => 'IN',
				),
			);
			$bundle_query   = new \WP_Tax_Query( $bundle_args );
			$bundle_clauses = $bundle_query->get_sql( 'sbp', 'ID' );

			$stock_bundles_sql = "OR p0.ID IN ( SELECT sbp.ID FROM $wpdb->posts sbp\n\t
				{$bundle_clauses['join']}\n\t
				WHERE sbp.post_status IN ('" . implode( "', '", Globals::get_queryable_product_statuses() ) . "')\n\t" .
				"AND sbp.ID NOT IN (
					SELECT wbi.bundle_id FROM {$wpdb->prefix}woocommerce_bundled_items wbi
					LEFT JOIN {$wpdb->prefix}woocommerce_bundled_itemmeta wbm ON wbi.bundled_item_id = wbm.bundled_item_id AND wbm.meta_key = 'optional'
					WHERE wbm.meta_value = 'no'
				)" .
				$bundle_clauses['where'] . ' )';

			//$products_in_stock_sql = "( ( $products_in_stock_sql ) UNION ( $stock_bundles_sql ) )";
		}
		else {
			$stock_bundles_sql = '';
		}

		if ( $children_query_needed ) {

			$products_in_stock_sql = "SELECT DISTINCT pist.ID FROM ( ( $products_in_stock_sql ) UNION ( SELECT p.ID FROM $wpdb->posts p
				LEFT JOIN $atum_product_data_table apd ON (p.ID = apd.product_id)
				WHERE apd.atum_stock_status IN ('instock', 'onbackorder') AND p.post_type = 'product_variation'
				AND p.post_parent IN ( $products_in_stock_sql ) ) ) pist ";
		}

		$variation_children = "
			SELECT pch1.ID FROM $wpdb->posts pch1
            WHERE pch1.post_parent = p0.ID AND pch1.post_type = 'product_variation'
        ";
		$unmanaged_children = "
			SELECT pch2.ID FROM $wpdb->posts pch2
            LEFT JOIN $wpdb->postmeta pmchild2 ON pch2.ID = pmchild2.post_id AND pmchild2.meta_key = '_manage_stock'
            WHERE pch2.post_parent = p0.ID AND pmchild2.meta_value = 'no'
        ";
		$children_with_pp   = "
			SELECT pch3.ID FROM $wpdb->posts pch3
			LEFT JOIN $atum_product_data_table apd3 ON pch3.ID = apd3.product_id
            WHERE pch3.post_parent = p0.ID AND apd3.purchase_price IS NOT NULL AND apd3.purchase_price > 0
        ";

		$base_stock_field                   = apply_filters( 'atum/dashboard/stock_field', "IF( ms.meta_value = 'yes', CAST( st.meta_value AS DECIMAL(10,6) ), 0 )" );
		$stock_field                        = "IF( NOT EXISTS($variation_children) OR EXISTS($unmanaged_children), IF( $base_stock_field > 0, $base_stock_field, 0 ), 0 )";
		$stock_wo_purchase_price_field      = 'IF( ' . apply_filters( 'atum/dashboard/purchase_price_field', 'apd0.purchase_price > 0' ) . ", 0, $base_stock_field )";
		$stock_without_purchase_price_field = "IF( NOT EXISTS($children_with_pp) AND (NOT EXISTS($variation_children) OR EXISTS($unmanaged_children)), $stock_wo_purchase_price_field, 0 )";

		$items_in_stock_sql = apply_filters( 'atum/dashboard/current_stock_query_parts', array(
			'fields' => array(
				'ID'                           => 'p0.ID',
				'items_stocks_counter'         => "IFNULL( SUM( $stock_field ), 0 ) items_stocks_counter",
				'items_purchase_price_total'   => 'IFNULL( SUM( ' . apply_filters( 'atum/dashboard/purchase_price_total', "apd0.purchase_price * $stock_field" ) . ' ), 0 ) items_purchase_price_total',
				'items_without_purchase_price' => "IFNULL( SUM( $stock_without_purchase_price_field ), 0 ) items_without_purchase_price",
			),
			'join'   => array(
				"LEFT JOIN $wpdb->postmeta st ON p0.ID = st.post_id AND st.meta_key = '_stock'",
				"LEFT JOIN $wpdb->postmeta ms ON p0.ID = ms.post_id AND ms.meta_key = '_manage_stock'",
				"LEFT JOIN $atum_product_data_table apd0 ON p0.ID = apd0.product_id",
			),
			'where'  => "p0.ID IN ( $products_in_stock_sql ) $stock_bundles_sql",
		) );

		// phpcs:disable
		$sql_string = 'SELECT ' . implode( ",\n", $items_in_stock_sql['fields'] ) .
		              "\n\nFROM $wpdb->posts p0 " . implode( "\n\t", $items_in_stock_sql['join'] ) .
		              "\n\nWHERE " . $items_in_stock_sql['where'] .
		              "\n\nGROUP BY " . apply_filters( 'atum/dashboard/current_stock_query_group_by', 'p0.ID' );

		$counters = (array) $wpdb->get_row( "SELECT SUM( t.items_stocks_counter ) items_stocks_counter,
            SUM( t.items_purchase_price_total ) items_purchase_price_total,
            SUM( t.items_without_purchase_price ) items_without_purchase_price
			FROM ( $sql_string ) t" );
		// phpcs:enable

		return self::format_counters_items_in_stock( $counters );

	}

	/**
	 * Format widget counters.
	 *
	 * @since 1.8.8
	 *
	 * @param array $counters
	 *
	 * @return array
	 */
	protected static function format_counters_items_in_stock( $counters ) {

		// Format counters.
		foreach ( $counters as $key => $counter ) {

			if ( 'items_purchase_price_total' === $key ) {
				$counters[ $key ] = wc_price( $counter );
			}
			else {

				$stock_decimals = Helpers::get_option( 'stock_quantity_decimals', 0 );

				if ( $stock_decimals <= 0 ) {
					$counters[ $key ] = absint( $counter );
				}
				else {

					$counters[ $key ] = number_format(
						$counter,
						$stock_decimals,
						wc_get_price_decimal_separator(),
						wc_get_price_thousand_separator()
					);

					// Trim trailing zeros.
					$counters[ $key ] = rtrim( rtrim( $counters[ $key ], '0' ), '.' );

					$price_decimal_separator  = wc_get_price_decimal_separator();
					$price_thousand_separator = wc_get_price_thousand_separator();
					$last_char                = substr( $counters['items_stocks_counter'], -1 );

					// If there are no decimals, the comma is removed.
					if ( $last_char === $price_decimal_separator || $last_char === $price_thousand_separator ) {
						$counters['items_stocks_counter'] = substr( $counters['items_stocks_counter'], 0, -1 );
					}
				}

			}

		}

		return $counters;

	}

}
