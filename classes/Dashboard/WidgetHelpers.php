<?php
/**
 * Helper functions for Widgets
 *
 * @package        Atum
 * @subpackage     Dashboard
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2021 Stock Management Labs™
 *
 * @since          1.4.0
 */

namespace Atum\Dashboard;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Legacy\WidgetHelpersLegacyTrait;


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

		$which       = FALSE !== strpos( $time_window, 'previous' ) ? 'last' : 'this';
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

		$products = Helpers::get_all_products();

		if ( ! empty( $products ) ) {

			$show_unmanaged_counter      = 'yes' === Helpers::get_option( 'unmanaged_counters' );
			$stock_counters['count_all'] = count( $products );

			$variations = self::get_children( 'variable', 'product_variation' );

			// Add the Variations to the posts list.
			if ( $variations ) {
				// The Variable products are just containers and don't count for the list views.
				$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
			}

			$group_items = self::get_children( 'grouped' );

			// Add the Group Items to the posts list.
			if ( $group_items ) {
				// The Grouped products are just containers and don't count for the list views.
				$stock_counters['count_all'] += ( count( $group_items ) - count( self::$grouped_products ) );

			}

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

			$product_statuses = current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ];

			/*
			 * Products In Stock
			 */
			$args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'post_status'    => $product_statuses,
				'fields'         => 'ids',
				// Exclude unmanaged products.
				'meta_query'     => array(
					array(
						'key'   => '_manage_stock',
						'value' => 'yes',
					),
				),
			);

			self::$wc_query_data['where'] = array(
				'relation' => 'AND',
				array(
					'key'   => 'stock_status',
					'value' => [ 'instock', 'onbackorder' ],
				),
				// Exclude variable and grouped products.
				array(
					'key'     => 'type',
					'value'   => [ 'variable', 'variable-subscription', 'grouped' ],
					'compare' => 'NOT IN',
				),
			);

			add_filter( 'posts_clauses', array( __CLASS__, 'wc_product_data_query_clauses' ) );
			$products_in_stock = new \WP_Query( apply_filters( 'atum/dashboard/stock_control_widget/in_stock_product__args', $args ) );

			$products_in_stock                 = $products_in_stock->posts;
			$stock_counters['count_in_stock'] += count( $products_in_stock );
			
			/*
			 * Products Out of Stock
			 */
			self::$wc_query_data['where'] = array(
				'relation' => 'AND',
				array(
					'key'   => 'stock_status',
					'value' => 'outofstock',
				),
				// Exclude variable and grouped products.
				array(
					'key'     => 'type',
					'value'   => [ 'variable', 'variable-subscription', 'grouped' ],
					'compare' => 'NOT IN',
				),
			);

			$products_out_stock                 = new \WP_Query( apply_filters( 'atum/dashboard/stock_control_widget/out_stock_products_args', $args ) );
			$products_out_stock                 = $products_out_stock->posts;
			$stock_counters['count_out_stock'] += count( $products_out_stock );

			// WC query clauses not needed anymore.
			remove_filter( 'posts_clauses', array( __CLASS__, 'wc_product_data_query_clauses' ) );

			/*
			 * Products with low stock
			 */
			if ( ! empty( $products_in_stock ) ) {

				$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
				$str_sql                 = apply_filters( 'atum/dashboard/get_stock_levels/low_stock_products', "
					SELECT product_id FROM $atum_product_data_table WHERE low_stock = 1
				" );

				$products_low_stock                = $wpdb->get_col( $str_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$stock_counters['count_low_stock'] = count( $products_low_stock );

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
		$products_visibility = current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ];
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
			if ( strpos( $parent_type, 'variable' ) !== FALSE ) {
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

		if ( ! Helpers::is_using_new_wc_tables() ) {
			return self::get_items_in_stock_legacy( $category, $product_type );
		}

		// Init values counter.
		$counters = [
			'items_stocks_counter'         => 0,
			'items_purchase_price_total'   => 0,
			'items_without_purchase_price' => 0,
		];

		/*
		 * Products In Stock
		 */
		$temp_wc_query_data           = self::$wc_query_data; // Save the original value.
		self::$wc_query_data['where'] = []; // Reset value.

		$args = array(
			'post_type'      => [ 'product', 'product_variation' ],
			'posts_per_page' => - 1,
			'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
			'fields'         => 'ids',
			'tax_query'      => array(
				'relation' => 'AND',
			),
			'meta_query'     => array(
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

		// Check if category filter data exist.
		if ( $category ) {

			array_push( $args['tax_query'], array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $category,
			) );

			$children_query_needed = TRUE;

		}

		// Check if product type filter data exist.
		if ( $product_type ) {

			if ( 'downloadable' === $product_type ) {

				self::$wc_query_data['where'][] = array(
					'key'   => 'downloadable',
					'value' => [ '1' ],
				);

			}
			elseif ( 'virtual' === $product_type ) {

				self::$wc_query_data['where'][] = array(
					'key'   => 'virtual',
					'value' => [ '1' ],
				);

			}
			else {

				self::$wc_query_data['where'][] = array(
					'key'   => 'type',
					'value' => $product_type,
				);

				$children_query_needed = TRUE;

			}

		}

		// Get products.
		add_filter( 'posts_clauses', array( __CLASS__, 'wc_product_data_query_clauses' ) );
		$products_in_stock_query = new \WP_Query( apply_filters( 'atum/dashboard/get_items_in_stock/in_stock_products_args', $args ) );
		remove_filter( 'posts_clauses', array( __CLASS__, 'wc_product_data_query_clauses' ) );

		$products_in_stock = $products_in_stock_query->posts;

		if ( ! empty( $products_in_stock ) ) {

			if ( $children_query_needed ) {

				global $wpdb;
				$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

				// phpcs:disable WordPress.DB.PreparedSQL
				$children_in_stock = $wpdb->get_col( "
					SELECT p.ID FROM $wpdb->posts p
					LEFT JOIN $atum_product_data_table apd ON (p.ID = apd.product_id)
					WHERE apd.atum_stock_status IN ('instock', 'onbackorder') AND p.post_type = 'product_variation'
					AND p.post_parent IN (" . $products_in_stock_query->request . ');
				' );
				// phpcs:enable

				$products_in_stock = array_unique( array_merge( $products_in_stock, $children_in_stock ) );

			}

			// Get current stock values.
			foreach ( $products_in_stock as $product_id ) {

				$product = Helpers::get_atum_product( $product_id );

				if ( ! apply_filters( 'atum/dashboard/get_items_in_stock/allowed_product', TRUE, $product ) ) {
					continue;
				}

				$product_stock          = (float) apply_filters( 'atum/dashboard/get_items_in_stock/product_stock', $product->get_stock_quantity(), $product );
				$product_purchase_price = (float) apply_filters( 'atum/dashboard/get_items_in_stock/product_price', $product->get_purchase_price(), $product );

				if ( $product_stock && $product_stock > 0 ) {

					$counters['items_stocks_counter'] += $product_stock;
					if ( $product_purchase_price && ! empty( $product_purchase_price ) ) {
						$counters['items_purchase_price_total'] += ( $product_purchase_price * $product_stock );
					}
					else {
						$counters['items_without_purchase_price'] += $product_stock;
					}

				}

			}

		}

		self::$wc_query_data = $temp_wc_query_data; // Restore the original value.

		return self::format_counters_items_in_stock( apply_filters( 'atum/dashboard/get_items_in_stock/counters', $counters ) );

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
		foreach ( $counters as $index => $counter ) {

			if ( 'items_purchase_price_total' === $index ) {
				$counters[ $index ] = wc_price( $counter );
			}
			else {

				$num_parts          = explode( '.', (string) $counter );
				$counters[ $index ] = number_format(
					$counter,
					isset( $num_parts[1] ) ? strlen( $num_parts[1] ) : 0,
					wc_get_price_decimal_separator(),
					wc_get_price_thousand_separator()
				);

			}

		}

		return $counters;

	}

}
