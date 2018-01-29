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


final class WidgetHelpers {

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

}