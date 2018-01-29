<?php
/**
 * @package     Atum\WPDashboard
 * @subpackage  Widgets
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2018 Stock Management Labs™
 *
 * @since       1.2.3
 *
 * Add the Statistics widget to the WP Dashboard
 */

namespace Atum\WPDashboard\Widgets;

defined( 'ABSPATH' ) or die;

use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Settings\Settings;
use Atum\WPDashboard\WPDashboardWidget;

class Statistics extends WPDashboardWidget {

	/**
	 * The id of this widget
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'wp_dashboard_statistics';

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
	 * The widget default options
	 * @var array
	 */
	protected $widget_defaults = array(
		'sold_today'            => array(
			'enabled' => TRUE,
			'data'    => array(
				'earnings' => TRUE,
				'products' => TRUE
			)
		),
		'sold_this_month'       => array(
			'enabled' => TRUE,
			'data'    => array(
				'earnings' => TRUE,
				'products' => TRUE
			)
		),
		'lost_sales_today'      => array(
			'enabled' => TRUE,
			'data'    => array(
				'earnings' => TRUE,
				'products' => TRUE
			)
		),
		'lost_sales_this_month' => array(
			'enabled' => TRUE,
			'data'    => array(
				'earnings' => TRUE,
				'products' => TRUE
			)
		),
		'orders_total'          => array(
			'enabled' => TRUE,
			'data'    => array(
				//'this_year'      => TRUE,
				'this_month'     => TRUE,
				'previous_month' => TRUE,
				'this_week'      => TRUE,
				'today'          => TRUE
			)
		),
		'revenue'               => array(
			'enabled' => TRUE,
			'data'    => array(
				//'this_year'      => TRUE,
				'this_month'     => TRUE,
				'previous_month' => TRUE,
				'this_week'      => TRUE,
				'today'          => TRUE
			)
		),
		'promo_products'        => array(
			'enabled' => TRUE,
			'data'    => array(
				//'this_year'      => TRUE,
				'this_month'     => TRUE,
				'previous_month' => TRUE,
				'this_week'      => TRUE,
				'today'          => TRUE
			)
		),
		'promo_value'           => array(
			'enabled' => TRUE,
			'data'    => array(
				//'this_year'      => TRUE,
				'this_month'     => TRUE,
				'previous_month' => TRUE,
				'this_week'      => TRUE,
				'today'          => TRUE
			)
		),
		'circle_stats'          => array(
			'enabled' => TRUE
		)
	);


	/**
	 * Statistics constructor
	 */
	public function __construct() {

		$this->title = __( 'ATUM Statistics', ATUM_TEXT_DOMAIN );
		parent::__construct();
	}

	/**
	 * @inheritdoc
	 */
	public function init() {

		$this->widget_defaults = apply_filters('atum/wp_dashboard/statistics_widget/defaults', $this->widget_defaults );

		// Register widget's default settings (if empty)
		$this->update_dashboard_widget_options( $this->widget_defaults, TRUE );
		parent::init();

	}

	/**
	 * @inheritdoc
	 */
	public function render() {

		$products = WidgetHelpers::get_all_product_ids();

		if ( empty($products) ) {
			return;
		}

		$widget_options = $this->get_dashboard_widget_options();

		// Do the calculations
		$first_day_of_month = new \DateTime('first day of this month 00:00:00');
		$today = new \DateTime('today 00:00:00');
		$days_elapsed = $today->diff($first_day_of_month)->days;

		$stats_this_month = WidgetHelpers::get_sales_stats( array(
			'types'    => array( 'sales', 'lost_sales' ),
			'products' => $products,
			'date'     => 'first day of this month 00:00:00',
			'days'     => $days_elapsed
		) );

		$stats_today = WidgetHelpers::get_sales_stats( array(
			'types'    => array( 'sales', 'lost_sales' ),
			'products' => $products,
			'date'     => 'today 00:00:00',
			'days'     => 1
		) );

		$order_status = (array) apply_filters( 'atum/wp_dashboard/statistics_widget/order_status', ['wc-processing', 'wc-completed'] );

		/**
		 * This year
		 * NOTE: COMMENTED DUE TO BAD PERFORMANCE ON SITES WITH MANY ORDERS
		 */
		/*$args = array(
			'order_status'     => $order_status,
			'order_date_start' => 'first day of January 00:00:00'
		);

		$promo_this_year = WidgetHelpers::get_promo_sales_stats($args);
		$orders_this_year = WidgetHelpers::get_orders_stats($args);*/

		/**
		 * This month
		 */
		$args = array(
			'status'     => $order_status,
			'date_start' => 'first day of this month 00:00:00'
		);

		$promo_this_month = WidgetHelpers::get_promo_sales_stats($args);
		$orders_this_month = WidgetHelpers::get_orders_stats($args);

		/**
		 * Previous month
		 */
		$args = array(
			'status'     => $order_status,
			'date_start' => 'first day of last month 00:00:00',
			'date_end'   => 'last day of last month 23:59:59'
		);

		$promo_previous_month = WidgetHelpers::get_promo_sales_stats($args);
		$orders_previous_month = WidgetHelpers::get_orders_stats($args);

		/**
		 * This week
		 */
		$args = array(
			'status'     => $order_status,
			'date_start' => 'Monday this week 00:00:00'
		);

		$promo_this_week = WidgetHelpers::get_promo_sales_stats($args);
		$orders_this_week = WidgetHelpers::get_orders_stats($args);

		/**
		 * Today
		 */
		$args = array(
			'status'     => $order_status,
			'date_start' => 'today 00:00:00'
		);

		$promo_today = WidgetHelpers::get_promo_sales_stats($args);
		$orders_today = WidgetHelpers::get_orders_stats($args);

		// Stock indicators
		$stock_counters = $this->get_stock_levels();

		include ATUM_PATH . 'views/wp-dashboard/statistics.php';

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

		$posts = new \WP_Query( apply_filters( 'atum/wp_dashboard_statistics/stock_counters/all', $args ) );
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

		// WC Subscriptions compatibility
		if ( class_exists('\WC_Subscriptions') ) {

			$subscription_variations = $this->get_children( 'variable-subscription', 'product_variation' );

			// Add the Variations to the posts list
			if ( $subscription_variations ) {
				// The Variable products are just containers and don't count for the list views
				$stock_counters['count_all'] += ( count( $variations ) - count( $this->variable_products ) );
				$posts = array_unique( array_merge( array_diff( $posts, $this->variable_products ), $variations ) );
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
				$this->variable_products = array_merge($this->variable_products, $parents->posts);
			}
			else {
				$this->grouped_products = array_merge($this->grouped_products, $parents->posts);
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

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.7
	 */
	public function config() {

		// Check if the settings' form is being submitted
		if ( ! empty($_POST['atum_statistics']) && $_POST['atum_statistics'] == 'yes' ) {

			// Save the settings
			foreach ($this->widget_defaults as $section => $config) {

				$this->widget_defaults[$section]['enabled'] = ${"$section"} = isset( $_POST[$section] ) && $_POST[$section];

				if ( isset( $config['data'] ) ) {
					foreach ( $config['data'] as $row => $status ) {
						$this->widget_defaults[$section]['data'][$row] = ${"{$section}_{$row}"} = isset( $_POST["{$section}_{$row}"] ) && $_POST["{$section}_{$row}"];
					}
				}

			}

			// Update the new settings
			$this->update_dashboard_widget_options( $this->widget_defaults );

		}
		else {

			$widget_options = $this->get_dashboard_widget_options();

			if ( ! empty( $widget_options ) ) {
				foreach ( $widget_options as $section => $config ) {

					${"$section"} = $config['enabled'];

					if ( isset( $config['data'] ) ) {
						foreach ( $config['data'] as $row => $status ) {
							${"{$section}_{$row}"} = $status;
						}
					}

				}
			}

		}

		include ATUM_PATH . 'views/wp-dashboard/statistics-config.php';

	}

}