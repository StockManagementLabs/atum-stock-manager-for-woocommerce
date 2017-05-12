<?php
/**
 * @package     Atum
 * @subpackage  Dashboard
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2017 Stock Management Labs™
 *
 * @since       1.2.3
 *
 * Add the Statistics widget to the WP Dashboard
 */

namespace Atum\Dashboard;

use Atum\Components\DashboardWidget;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Settings\Settings;


defined( 'ABSPATH' ) or die;

class Statistics extends DashboardWidget {

	/**
	 * The id of this widget
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'dashboard_statistics_widget';

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
	 * Whether the current widget has the config settings enabled
	 * @var bool
	 */
	protected $has_config = FALSE;

	/**
	 * Hook to wp_dashboard_setup to add the widget
	 * NOTE: We only need to overwrite this method if we want to add default values to widget options
	 *
	 * @since 1.2.3
	 *
	 * @param array $widget_options   Associative array of options & default values for this widget
	 */
	public function init( $widget_options = array() ) {

		$widget_options = array(
			'example_number' => 42
		);

		parent::init($widget_options);
	}


	/**
	 * Load the widget view
	 *
	 * @since 1.2.3
	 */
	public function widget() {

		// Get the products that will use for calculations
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

		if ( Helpers::get_option( 'manage_stock', 'no' ) == 'no' ) {

			// Only products with the _manage_stock meta set to yes
			$args['meta_query'] = array(
				array(
					'key'   => '_manage_stock',
					'value' => 'yes'
				)
			);
		}

		$products = get_posts($args);

		if ( empty($products) ) {
			return;
		}

		// Do the calculations
		$first_day_of_month = new \DateTime('first day of this month 00:00:00');
		$today = new \DateTime('today 00:00:00');
		$days_elapsed = $today->diff($first_day_of_month)->days;

		$stats_this_month = $this->get_stats( $products, $first_day_of_month->getTimestamp(), $days_elapsed );
		$stats_today = $this->get_stats( $products, $today->getTimestamp(), 1 );

		$order_status = apply_filters( 'atum/dashboard_statistics/order_status', 'wc-processing, wc-completed' );

		/**
		 * Orders this year
		 */
		$args = array(
			'order_status'     => $order_status,
			'order_date_start' => strtotime( 'first day of January 00:00:00' )
		);

		$orders_this_year = Helpers::get_orders($args);
		$orders_amount_this_year = count($orders_this_year);
		$orders_revenue_this_year = 0;

		// TODO: REFACTORY TO EXTRACT ALL THE BELOW INFO FROM THIS YEAR'S ORDERS
		foreach ($orders_this_year as $order) {
			$orders_revenue_this_year += floatval( $order->get_total() );
		}

		$orders_revenue_this_year = Helpers::format_price($orders_revenue_this_year);

		/**
		 * Orders this month
		 */
		$args = array(
			'order_status'     => $order_status,
			'order_date_start' => $first_day_of_month->getTimestamp()
		);

		$orders_this_month = Helpers::get_orders($args);
		$orders_amount_this_month = count($orders_this_month);
		$orders_revenue_this_month = 0;

		foreach ($orders_this_month as $order) {
			$orders_revenue_this_month += floatval( $order->get_total() );
		}

		$orders_revenue_this_month = Helpers::format_price($orders_revenue_this_month);

		/**
		 * Orders this week
		 */
		$args = array(
			'order_status'     => $order_status,
			'order_date_start' => strtotime( 'Monday this week 00:00:00' )
		);

		$orders_this_week = Helpers::get_orders($args);
		$orders_amount_this_week = count($orders_this_week);
		$orders_revenue_this_week = 0;

		foreach ($orders_this_week as $order) {
			$orders_revenue_this_week += floatval( $order->get_total() );
		}

		$orders_revenue_this_week = Helpers::format_price($orders_revenue_this_week);

		/**
		 * Orders today
		 */
		$args = array(
			'order_status'     => $order_status,
			'order_date_start' => $today->getTimestamp()
		);

		$orders_today = Helpers::get_orders($args);
		$orders_amount_today = count($orders_today);
		$orders_revenue_today = 0;

		foreach ($orders_today as $order) {
			$orders_revenue_today += floatval( $order->get_total() );
		}

		$orders_revenue_today = Helpers::format_price($orders_revenue_today);

		// Stock indicators
		$stock_counters = $this->get_stock_levels();

		include ATUM_PATH . 'views/dashboard-widgets/statistics.php';

	}

	/**
	 * Get the products statistics that were sold after the specified date
	 *
	 * @since 1.2.3
	 *
	 * @param array $products   The array of products to include in calculations
	 * @param int   $date       The UNIX timestamp date
	 * @param int   $days       The days used for lost sales calculations
	 *
	 * @return array
	 */
	private function get_stats ($products, $date, $days) {

		// Initialize values
		$stats = array(
			'earnings'      => 0,
			'products'      => 0,
			'lost_earnings' => 0,
			'lost_products' => 0
		);

		$products_sold = Helpers::get_sold_last_days( $products, $date );
		$lost_processed = array();

		if ( $products_sold ) {

			foreach ( $products_sold as $row ) {

				$stats['products'] += $row['QTY'];
				$product = wc_get_product( $row['PROD_ID'] );
				$stats['earnings'] += $row['QTY'] * $product->get_price();

				if ( ! in_array($row['PROD_ID'], $lost_processed) ) {
					$lost_sales = Helpers::get_product_lost_sales( $row['PROD_ID'], $days );

					if ( is_numeric($lost_sales) ) {
						$stats['lost_earnings'] += $lost_sales;
						$lost_processed[] = $row['PROD_ID'];
					}
				}

			}

		}

		$stats['earnings'] = $this->format_price( $stats['earnings'] );
		$stats['lost_earnings'] = $this->format_price( $stats['lost_earnings'] );

		return $stats;

	}

	/**
	 * Format a price as currency
	 *
	 * @since 1.2.3.2
	 *
	 * @param float $price
	 *
	 * @return string
	 */
	private function format_price ($price) {
		return strip_tags( wc_price( $price ) );
	}

	/**
	 * Get the current stock levels
	 *
	 * @since 1.2.3
	 *
	 * @return array
	 */
	private function get_stock_levels() {

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

		$posts = new \WP_Query( apply_filters( 'atum/dashboard_statistics/stock_counters/all', $args ) );
		$posts = $posts->posts;
		$stock_counters['count_all'] = count( $posts );

		$variations = $this->get_children( 'variable', 'product_variation' );

		// Add the Variations to the posts list
		if ( $variations ) {
			// The Variable products are just containers and don't count for the list views
			$stock_counters['count_all'] += ( count( $variations ) - count( $this->variable_products ) );
			$posts = array_unique( array_merge( array_diff( $posts, $this->variable_products ), $variations ) );
		}


		$group_items = $this->get_children( 'grouped' );

		// Add the Group Items to the posts list
		if ( $group_items ) {
			// The Grouped products are just containers and don't count for the list views
			$stock_counters['count_all'] += ( count( $group_items ) - count( $this->grouped_products ) );
			$posts = array_unique( array_merge( array_diff( $posts, $this->grouped_products ), $group_items ) );

		}

		if ( $posts ) {

			$post_types = ($variations) ? array('product', 'product_variation') : 'product';

			// Products In Stock
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

			$posts_in_stock = new \WP_Query( apply_filters( 'atum/dashboard_statistics/stock_counters/in_stock', $args ) );
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
				    (SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND order_item_id = `item`.`order_item_id`) AS IDs,
				    CEIL(SUM((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_qty' AND order_item_id = `item`.`order_item_id`))/7*$days_to_reorder) AS qty
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

				$str_sql = apply_filters( 'atum/dashboard_statistics/stock_counters/low_stock', "SELECT `ID` FROM $str_states WHERE state IS FALSE;" );

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
	 * @since 1.2.3
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

			$children = new \WP_Query( apply_filters( 'atum/dashboard_statistics/get_children', $children_args ) );

			if ($children->found_posts) {
				return $children->posts;
			}

		}

		return FALSE;

	}

}