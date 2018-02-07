<?php
/**
 * @package        Atum
 * @subpackage     Dashboard
 * @author         Salva Machí and Jose Piera - https://sispixels.com
 * @copyright      ©2018 Stock Management Labs™
 *
 * @since          1.3.9
 *
 * Helper functions for Widgets
 */

namespace Atum\Dashboard;

defined( 'ABSPATH' ) or die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Settings\Settings;


final class WidgetHelpers {

	/**
	 * The array of published Variable products' IDs
	 * @var array
	 */
	private static $variable_products = array();

	/**
	 * The array of published Grouped products' IDs
	 * @var array
	 */
	private static $grouped_products = array();

	/**
	 * Get a list of all the products used for calculating stats
	 *
	 * @since 1.3.9
	 *
	 * @return array
	 */
	public static function get_all_product_ids () {

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => Globals::get_product_types()
				)
			),
			'fields' => 'ids'
		);

		if ( ! Helpers::is_atum_managing_stock() ) {

			// Only products with the _manage_stock meta set to yes
			$args['meta_query'] = array(
				array(
					'key'   => '_manage_stock',
					'value' => 'yes'
				)
			);
		}

		return get_posts($args);

	}

	/**
	 * Get the stats of products that were sold after the specified date
	 *
	 * @since 1.3.9
	 *
	 *  @param array $atts {
	 *      Array of stats filter params
	 *
	 *      @type array  $types      An array of stats to get. Possible values: "sales" and/or "lost_sales"
	 *      @type array  $products   The array of products to include in calculations
	 *      @type string $date       The date from when to start the items' sales calculations (must be a string format convertible with strtotime)
	 *      @type int    $days       The days used for lost sales calculations. Only required when asking for lost sales
	 * }
	 *
	 * @return array
	 */
	public static function get_sales_stats ($atts) {

		/**
		 * @var array  $types
		 * @var array  $products
		 * @var string $date
		 * @var int    $days
		 */
		extract($atts);
		$stats = array();

		// Initialize values
		if ( in_array('sales', $types) ) {
			$stats['earnings'] = 0;
			$stats['products'] = 0;
		}

		if ( in_array('lost_sales', $types) ) {
			$stats['lost_earnings'] = 0;
			$stats['lost_products'] = 0;
		}

		$products_sold = Helpers::get_sold_last_days( $products, $date );
		$lost_processed = array();

		if ( $products_sold ) {

			foreach ( $products_sold as $row ) {

				if ( in_array('sales', $types) ) {
					$stats['products'] += floatval( $row['QTY'] );
					$stats['earnings'] += floatval( $row['TOTAL'] );
				}

				if ( in_array('lost_sales', $types) && ! in_array($row['PROD_ID'], $lost_processed) ) {
					$lost_sales = Helpers::get_product_lost_sales( $row['PROD_ID'], $days );

					if ( is_numeric($lost_sales) ) {
						$stats['lost_earnings'] += $lost_sales;
						$lost_processed[] = $row['PROD_ID'];
					}
				}

			}

		}

		if ( in_array('sales', $types) ) {
			$stats['earnings'] = Helpers::format_price( $stats['earnings'] );
		}

		if ( in_array('lost_sales', $types) ) {
			$stats['lost_earnings'] = Helpers::format_price( $stats['lost_earnings'] );
		}

		return $stats;

	}

	/**
	 * Get the promo sales stats within a specified time window
	 *
	 * @since 1.3.9
	 *
	 * @param array $order_args
	 *
	 * @return array
	 */
	public static function get_promo_sales_stats ($order_args) {

		// Initialize
		$stats = array(
			'value'    => 0,
			'products' => 0
		);

		$orders = Helpers::get_orders($order_args);

		foreach ($orders as $order) {

			/**
			 * @var \WC_Order $order
			 */

			// Check if this order had discounts
			$order_discount = $order->get_discount_total();

			if ($order_discount) {
				$stats['value'] += $order_discount;

				$order_items = $order->get_items();

				foreach ($order_items as $order_item) {
					/**
					 * @var \WC_Order_Item $order_item
					 */
					$stats['products'] += $order_item->get_quantity();
				}
			}
		}

		$stats['value'] = Helpers::format_price( $stats['value'] );

		return $stats;

	}

	/**
	 * Get the orders stats within a specified time window
	 *
	 * @since 1.3.9
	 *
	 * @param array $order_args
	 *
	 * @return array
	 */
	public static function get_orders_stats ($order_args) {

		// Initialize
		$stats = array(
			'revenue' => 0,
			'orders'  => 0
		);

		$orders = Helpers::get_orders($order_args);
		$stats['orders'] = count($orders);

		foreach ($orders as $order) {

			/**
			 * @var \WC_Order $order
			 */
			$stats['revenue'] += floatval( $order->get_total() );

		}

		$stats['revenue'] = Helpers::format_price( $stats['revenue'] );

		return $stats;

	}

	/**
	 * Get the current stock levels
	 *
	 * @since 1.3.9
	 *
	 * @return array
	 */
	public static function get_stock_levels () {

		global $wpdb;

		$taxonomies = Globals::get_product_types();

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => - 1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $taxonomies
				)
			)
		);

		$stock_counters = array(
			'count_in_stock'  => 0,
			'count_out_stock' => 0,
			'count_low_stock' => 0,
			'count_all'       => 0
		);

		$posts = new \WP_Query( apply_filters( 'atum/wp_dashboard_statistics/stock_counters/all', $args ) );
		$posts = $posts->posts;
		$stock_counters['count_all'] = count( $posts );

		$variations = self::get_children( 'variable', 'product_variation' );

		// Add the Variations to the posts list
		if ( $variations ) {
			// The Variable products are just containers and don't count for the list views
			$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
			$posts = array_unique( array_merge( array_diff( $posts, self::$variable_products ), $variations ) );
		}

		$group_items = self::get_children( 'grouped' );

		// Add the Group Items to the posts list
		if ( $group_items ) {
			// The Grouped products are just containers and don't count for the list views
			$stock_counters['count_all'] += ( count( $group_items ) - count( self::$grouped_products ) );
			$posts = array_unique( array_merge( array_diff( $posts, self::$grouped_products ), $group_items ) );

		}

		// WC Subscriptions compatibility
		if ( class_exists('\WC_Subscriptions') ) {

			$subscription_variations = self::get_children( 'variable-subscription', 'product_variation' );

			// Add the Variations to the posts list
			if ( $subscription_variations ) {
				// The Variable products are just containers and don't count for the list views
				$stock_counters['count_all'] += ( count( $variations ) - count( self::$variable_products ) );
				$posts = array_unique( array_merge( array_diff( $posts, self::$variable_products ), $variations ) );
			}

		}

		if ( $posts ) {

			$post_types = ($variations) ? array('product', 'product_variation') : 'product';

			// Products In Stock
			$args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'post__in'       => $posts
			);

			// If ATUM is not managing the stock, get those that have the stock status set as "In stock"
			if ( ! Helpers::is_atum_managing_stock() ) {

				$args['meta_query'][] = array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '='
				);

			}
			else {

				$args['meta_query'][] = array(
					'key'     => '_stock',
					'value'   => 0,
					'type'    => 'numeric',
					'compare' => '>'
				);

			}

			$posts_in_stock = new \WP_Query( apply_filters( 'atum/wp_dashboard_statistics/stock_counters/in_stock', $args ) );
			$stock_counters['count_in_stock'] = count( $posts_in_stock->posts );

			// As the Group items might be displayed multiple times, we should count them multiple times too
			if ($group_items && ( empty($_REQUEST['type']) || $_REQUEST['type'] != 'grouped' )) {
				$stock_counters['count_in_stock'] += count( array_intersect($group_items, $posts_in_stock->posts) );
			}

			$stock_counters['count_out_stock'] = $stock_counters['count_all'] - $stock_counters['count_in_stock'];

			if ( $stock_counters['count_in_stock'] ) {

				$days_to_reorder = absint( Helpers::get_option( 'sale_days', Settings::DEFAULT_SALE_DAYS ) );

				// Products in LOW stock (compare last seven days average sales per day * re-order days with current stock )
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

				$str_sql = apply_filters( 'atum/wp_dashboard_statistics/stock_counters/low_stock', "SELECT `ID` FROM $str_states WHERE state IS FALSE;" );

				$result = $wpdb->get_results( $str_sql );
				$result = wp_list_pluck( $result, 'ID' );
				$stock_counters['count_low_stock'] = count( $result );

			}

		}

		return $stock_counters;

	}

	/**
	 * Get all the available children products of the published parent products (Variable and Grouped)
	 *
	 * @since 1.3.9
	 *
	 * @param string $parent_type   The parent product type
	 * @param string $post_type     Optional. The children post type
	 *
	 * @return array|bool
	 */
	private static function get_children ($parent_type, $post_type = 'product') {

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
				self::$variable_products = array_merge(self::$variable_products, $parents->posts);
			}
			else {
				self::$grouped_products = array_merge(self::$grouped_products, $parents->posts);
			}

			$children_args = array(
				'post_type'       => $post_type,
				'post_status'     => 'publish',
				'posts_per_page'  => - 1,
				'fields'          => 'ids',
				'post_parent__in' => $parents->posts
			);

			$children = new \WP_Query( apply_filters( 'atum/wp_dashboard_statistics/get_children', $children_args ) );

			if ($children->found_posts) {
				return $children->posts;
			}

		}

		return FALSE;

	}

}