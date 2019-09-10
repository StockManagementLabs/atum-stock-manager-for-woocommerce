<?php
/**
 * Class WidgetsHelpersTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;
use Symfony\Component\DomCrawler\Crawler;


/**
 * Sample test case.
 */
class WidgetsHelpersTest extends WP_UnitTestCase {

	public function test_get_sales_stats() {
		$types       = [ 'sales', 'lost_sales' ];
		$products    = Helpers::get_all_products( array( 'post_type' => [ 'product', 'product_variation' ], ), TRUE );
		$time_window = $this->get_time_window();

		foreach($time_window as $time) {
			$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
			$period      = $this->generate_period($time);
			foreach($period as $dt) {
				$stats = WidgetHelpers::get_sales_stats( array(
					'types'           => $types,
					'products'        => $products,
					'date_start'      => $dt->format( 'Y-m-d H:i:s' ),
					'date_end'        => 'year' === $period_time ? 'last day of ' . $dt->format( 'F Y' ) . ' 23:59:59' : $dt->format( 'Y-m-d 23:59:59' ),
					'formatted_value' => FALSE,
				) );

				$this->assertIsArray( $stats );
				$this->assertArrayHasKey( 'value', $stats );
				$this->assertArrayHasKey( 'products', $stats );
				$this->assertArrayHasKey( 'lost_value', $stats );
				$this->assertArrayHasKey( 'lost_products', $stats );
			}
		}
	}

	public function test_get_promo_sales_stats() {
		$types       = [ 'sales', 'lost_sales' ];
		$products    = Helpers::get_all_products( array( 'post_type' => [ 'product', 'product_variation' ], ), TRUE );
		$time_window = $this->get_time_window();

		foreach($time_window as $time) {
			$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
			$period      = $this->generate_period($time);
			foreach($period as $dt) {
				$stats = WidgetHelpers::get_promo_sales_stats( array(
					'types'           => $types,
					'products'        => $products,
					'date_start'      => $dt->format( 'Y-m-d H:i:s' ),
					'date_end'        => 'year' === $period_time ? 'last day of ' . $dt->format( 'F Y' ) . ' 23:59:59' : $dt->format( 'Y-m-d 23:59:59' ),
					'formatted_value' => FALSE,
				) );

				$this->assertIsArray( $stats );
				$this->assertArrayHasKey( 'value', $stats );
				$this->assertArrayHasKey( 'products', $stats );
			}
		}
	}

	public function test_get_orders_stats() {
		$types       = [ 'sales', 'lost_sales' ];
		$products    = Helpers::get_all_products( array( 'post_type' => [ 'product', 'product_variation' ], ), TRUE );
		$time_window = $this->get_time_window();

		foreach($time_window as $time) {
			$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
			$period      = $this->generate_period($time);
			foreach($period as $dt) {
				$stats = WidgetHelpers::get_orders_stats( array(
					'types'           => $types,
					'products'        => $products,
					'date_start'      => $dt->format( 'Y-m-d H:i:s' ),
					'date_end'        => 'year' === $period_time ? 'last day of ' . $dt->format( 'F Y' ) . ' 23:59:59' : $dt->format( 'Y-m-d 23:59:59' ),
					'formatted_value' => FALSE,
				) );

				$this->assertIsArray( $stats );
				$this->assertArrayHasKey( 'value', $stats );
				$this->assertArrayHasKey( 'orders', $stats );
			}
		}
	}

	public function test_get_sales_chart_data() {
		$types       = [ 'sales', 'lost_sales' ];
		$time_window = $this->get_time_window();

		foreach($time_window as $time) {
			$stats = WidgetHelpers::get_sales_chart_data( $time, $types );

			$this->assertIsArray( $stats );
		}
	}

	public function test_get_promo_sales_chart_data() {
		$time_window = $this->get_time_window();

		foreach($time_window as $time) {
			$stats = WidgetHelpers::get_promo_sales_chart_data( $time );

			$this->assertIsArray( $stats );
			$this->assertArrayHasKey( 'value', $stats );
			$this->assertArrayHasKey( 'products', $stats );
		}
	}

	public function test_get_orders_chart_data() {
		$time_window = $this->get_time_window();

		foreach($time_window as $time) {
			$stats = WidgetHelpers::get_orders_chart_data( $time );

			$this->assertIsArray( $stats );
			$this->assertArrayHasKey( 'value', $stats );
			$this->assertArrayHasKey( 'products', $stats );
		}
	}

	public function test_get_stock_levels() {
		$stats = WidgetHelpers::get_stock_levels();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'count_in_stock', $stats );
		$this->assertArrayHasKey( 'count_out_stock', $stats );
		$this->assertArrayHasKey( 'count_low_stock', $stats );
		$this->assertArrayHasKey( 'count_all', $stats );
		$this->assertArrayHasKey( 'count_unmanaged', $stats );
	}

	public function test_product_types_dropdown() {
		$dropdown = WidgetHelpers::product_types_dropdown();
		$html     = new Crawler( $dropdown );

		$this->assertEquals( 1, $html->filter('select.dropdown_product_type')->count() );
	}

	public function test_get_items_in_stock() {
		$stats = WidgetHelpers::get_items_in_stock();
		$this->assertIsString( $stats );
	}

	public function get_time_window() {
		return [ "this_year", "previous_year", "this_month", "previous_month", "this_week", "previous_week" ];
	}

	public function generate_period( $time_window ) {
		$which       = FALSE !== strpos( $time_window, 'previous' ) ? 'last' : 'this';
		$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
		$period      = NULL;

		switch ( $period_time ) {
			case 'year':
				$period = WidgetHelpers::get_date_period( "first day of January $which year midnight", "last day of December $which year 23:59:59", '1 month' );
				break;

			case 'month':
				$period = WidgetHelpers::get_date_period( "first day of $which month midnight", "last day of $which month 23:59:59" );
				break;

			case 'week':
				$period = WidgetHelpers::get_date_period( "$which week midnight", "$which week +6 days 23:59:59" );
				break;

		}
		return $period;
	}

}
