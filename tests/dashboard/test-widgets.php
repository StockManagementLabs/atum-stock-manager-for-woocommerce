<?php
/**
 * Class WidgetHelpersTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;
use Symfony\Component\DomCrawler\Crawler;
use Atum\Dashboard\Widgets\CurrentStockValue;
use Atum\Dashboard\Widgets\LostSales;
use Atum\Dashboard\Widgets\Orders;
use Atum\Dashboard\Widgets\PromoSales;
use Atum\Dashboard\Widgets\Sales;
use Atum\Dashboard\Widgets\Statistics;
use Atum\Dashboard\Widgets\StockControl;
use Atum\Dashboard\Widgets\Videos;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class WidgetHelpersTest extends WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( WidgetHelpers::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	/**
	 * Test method for get_sales_stats.
	 */
	public function test_get_sales_stats() {
		$types       = [ 'sales', 'lost_sales' ];
		$products    = Helpers::get_all_products( array( 'post_type' => [ 'product', 'product_variation' ] ), TRUE );
		$time_window = $this->get_time_window();

		foreach ( $time_window as $time ) {
			$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
			$period      = $this->generate_period( $time );
			foreach ( $period as $dt ) {
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

	/**
	 * Test method for get_promo_sales_stats.
	 */
	public function test_get_promo_sales_stats() {
		$types       = [ 'sales', 'lost_sales' ];
		$products    = Helpers::get_all_products( array( 'post_type' => [ 'product', 'product_variation' ] ), TRUE );
		$time_window = $this->get_time_window();

		foreach ( $time_window as $time ) {
			$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
			$period      = $this->generate_period( $time );
			foreach ( $period as $dt ) {
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

	/**
	 * Test method for get_orders_stats.
	 */
	public function test_get_orders_stats() {
		$types       = [ 'sales', 'lost_sales' ];
		$products    = Helpers::get_all_products( array( 'post_type' => [ 'product', 'product_variation' ] ), TRUE );
		$time_window = $this->get_time_window();

		foreach ( $time_window as $time ) {
			$period_time = str_replace( [ 'this', 'previous', '_' ], '', $time_window );
			$period      = $this->generate_period( $time );
			foreach ( $period as $dt ) {
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

	/**
	 * Test method for get_sales_chart_data.
	 */
	public function test_get_sales_chart_data() {
		$types       = [ 'sales', 'lost_sales' ];
		$time_window = $this->get_time_window();

		foreach ( $time_window as $time ) {
			$stats = WidgetHelpers::get_sales_chart_data( $time, $types );

			$this->assertIsArray( $stats );
		}
	}

	/**
	 * Test method for get_promo_sales_chart_data.
	 */
	public function test_get_promo_sales_chart_data() {
		$time_window = $this->get_time_window();

		foreach ( $time_window as $time ) {
			$stats = WidgetHelpers::get_promo_sales_chart_data( $time );

			$this->assertIsArray( $stats );
			$this->assertArrayHasKey( 'value', $stats );
			$this->assertArrayHasKey( 'products', $stats );
		}
	}

	/**
	 * Test method for get_orders_chart_data.
	 */
	public function test_get_orders_chart_data() {
		$time_window = $this->get_time_window();

		foreach ( $time_window as $time ) {
			$stats = WidgetHelpers::get_orders_chart_data( $time );

			$this->assertIsArray( $stats );
			$this->assertArrayHasKey( 'value', $stats );
			$this->assertArrayHasKey( 'products', $stats );
		}
	}

	/**
	 * Test method for get_date_period
	 */
	public function test_get_date_period() {
		$ini = new DateTime();
		$end = date_add( $ini, DateInterval::createFromDateString('3 days') );
		$data = WidgetHelpers::get_date_period( $ini->format( 'Y-m-d' ), $end->format( 'Y-m-d' ) );
		$this->assertInstanceOf( DatePeriod::class, $data );
	}

	/**
	 * Test method for get_stock_levels_legacy.
	 */
	public function test_get_stock_levels_legacy() {
		$stats = WidgetHelpers::get_stock_levels_legacy();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'count_in_stock', $stats );
		$this->assertArrayHasKey( 'count_out_stock', $stats );
		$this->assertArrayHasKey( 'count_low_stock', $stats );
		$this->assertArrayHasKey( 'count_all', $stats );
		$this->assertArrayHasKey( 'count_unmanaged', $stats );
	}

	/**
	 * Test method for get_stock_levels.
	 */
	public function test_get_stock_levels() {
		$stats = WidgetHelpers::get_stock_levels();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'count_in_stock', $stats );
		$this->assertArrayHasKey( 'count_out_stock', $stats );
		$this->assertArrayHasKey( 'count_low_stock', $stats );
		$this->assertArrayHasKey( 'count_all', $stats );
		$this->assertArrayHasKey( 'count_unmanaged', $stats );
	}

	/**
	 * Test method for wc_product_data_query_clauses
	 */
	public function test_wc_product_data_query_clauses() {
		$this->assertIsArray( WidgetHelpers::wc_product_data_query_clauses( [] ) );
	}

	/**
	 * Test method for product_types_dropdown.
	 */
	public function test_product_types_dropdown() {
		$dropdown = WidgetHelpers::product_types_dropdown();
		$html     = new Crawler( $dropdown );

		$this->assertEquals( 1, $html->filter( 'select.dropdown_product_type' )->count() );
	}

	/**
	 * Test method for get_items_in_stock.
	 */
	public function test_get_items_in_stock() {
		TestHelpers::create_product();

		$stats = WidgetHelpers::get_items_in_stock();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'items_stocks_counter', $stats );
		$this->assertArrayHasKey( 'items_purchase_price_total', $stats );
		$this->assertArrayHasKey( 'items_without_purchase_price', $stats );
	}

	/**
	 * Test method for stock_value_widget.
	 */
	public function test_current_stock_value_widget() {
		wp_set_current_user( 1 );
		$widget = new CurrentStockValue();
		TestHelpers::create_product();

		ob_start();
		$widget->render();
		$response = ob_get_clean();

		$html = new Crawler( $response );
		$this->assertEquals( 1, $html->filter( 'div.current-stock-value-filters' )->count() );
	}

	/**
	 * Test method for lost_sales_widget.
	 */
	public function test_lost_sales_widget() {
		wp_set_current_user( 1 );
		$widget = new LostSales();
		TestHelpers::create_product();

		ob_start();
		$widget->render();
		$response = ob_get_clean();

		$html = new Crawler( $response );
		$this->assertEquals( 1, $html->filter( 'div.stats-data-widget' )->count() );
	}

	/**
	 * Test method for promo_sales_widget.
	 */
	public function test_promo_sales_widget() {
		wp_set_current_user( 1 );
		$widget = new PromoSales();

		ob_start();
		$widget->render();
		$response = ob_get_clean();

		$html = new Crawler( $response );
		$this->assertEquals( 1, $html->filter( 'div.stats-data-widget' )->count() );
	}

	/**
	 * Test method for orders_widget.
	 */
	public function test_orders_widget() {
		wp_set_current_user( 1 );
		$widget = new Orders();

		ob_start();
		$widget->render();
		$response = ob_get_clean();

		$html = new Crawler( $response );
		$this->assertEquals( 1, $html->filter( 'div.stats-data-widget' )->count() );
	}

	/**
	 * Test method for statistics_widget.
	 */
	public function test_statistics_widget() {
		wp_set_current_user( 1 );
		$widget = new Statistics();

		ob_start();
		$widget->render();
		$response = ob_get_clean();

		$html = new Crawler( $response );
		$this->assertEquals( 1, $html->filter( 'div.statistics-widget' )->count() );
	}

	/**
	 * Test method for stock_control_widget.
	 */
	public function test_stock_control_widget() {
		wp_set_current_user( 1 );
		$widget = new StockControl();

		ob_start();
		$widget->render();
		$response = ob_get_clean();

		$html = new Crawler( $response );
		$this->assertEquals( 1, $html->filter( 'div.stock-control-widget' )->count() );
	}

	/**
	 * Test method for videos_widget.
	 */
	public function test_videos_widget() {
		wp_set_current_user( 1 );
		$widget = new Videos();

		ob_start();
		$widget->render();
		$response = ob_get_clean();

		$html = new Crawler( $response );
		$this->assertGreaterThan( 0, $html->filter( 'div.video-details' )->count() );
	}


	/**
	 * Aux method.
	 */
	public function get_time_window() {
		return [
			'this_year',
			'previous_year',
			'this_month',
			'previous_month',
			'this_week',
			'previous_week',
		];
	}

	/**
	 * Aux method.
	 */
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
