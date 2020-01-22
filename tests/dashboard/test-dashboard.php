<?php
/**
 * Class DashboardTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Dashboard\Dashboard;
use Symfony\Component\DomCrawler\Crawler;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class DashboardTest extends WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( Dashboard::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$this->assertInstanceOf( Dashboard::class, Dashboard::get_instance() );
	}

	public function test_add_menu() {
		$dash  = Dashboard::get_instance();
		$menus = [ 'others' => 'foo' ];
		$menus = $dash->add_menu( $menus );

		$this->assertIsArray( $menus );
		$this->assertArrayHasKey( 'dashboard', $menus );
		$this->assertArrayHasKey( 'slug', $menus['dashboard'] );
	}

	public function test_display() {
		$dash = Dashboard::get_instance();

		ob_start();
		$dash->display();
		$response = ob_get_clean();

		$html = new Crawler( $response );
		$this->assertEquals( 1, $html->filter('div.atum-dashboard')->count() );
		$this->assertEquals( 1, $html->filter('section.dash-header')->count() );
		$this->assertEquals( 1, $html->filter('section.dash-marketing-banner-container')->count() );
		$this->assertEquals( 1, $html->filter('section.atum-widgets')->count() );
		$this->assertEquals( 1, $html->filter('section.add-dash-widget')->count() );
	}

	public function test_add_widget() {
		$dash    = Dashboard::get_instance();
		$widgets = $dash->get_widgets();
		$layout  = [ 'x' => 0, 'y' => 10, 'width' => 6, 'height' => 4, 'min-height' => 5 ];

		$this->assertGreaterThan( 0, count($widgets) );

		foreach($widgets as $widget) {
			ob_start();
			$dash->add_widget( $widget, $layout );
			$response = ob_get_clean();
			$html = new Crawler( $response );
			$this->assertEquals( 1, $html->filter('div.atum-widget')->count() );
		}
	}

	public function test_load_widgets() {
		$dash = Dashboard::get_instance();
		// TODO: Enable when News_DISABLED be enabled or removed
		//$data = $dash->load_widgets();
		//$this->assertIsArray( $data );
		//$this->assertNotEmpty( $data );
		$this->assertTrue( TRUE );
	}

	public function test_enqueue_scripts() {
		wp_set_current_user(1);
		$dash = Dashboard::get_instance();
		$dash->enqueue_scripts( 'atum-dashboard' );

		$this->assertTrue( wp_script_is( 'atum-lodash', 'registered' ) );
		$this->assertTrue( wp_script_is( 'gridstack', 'registered' ) );
		$this->assertTrue( wp_script_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_script_is( 'atum-dashboard', 'registered' ) );
		$this->assertTrue( wp_style_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_style_is( 'owl.carousel', 'registered' ) );
		$this->assertTrue( wp_style_is( 'owl.carousel.theme', 'registered' ) );
		$this->assertTrue( wp_style_is( 'atum-dashboard', 'registered' ) );
	}

	public function test_save_user_widgets_layout() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_restore_user_widgets_layout() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_get_user_widgets_layout() {
		wp_set_current_user( 1 );

		$lout = [
			'atum_statistics_widget'    => [ 'x' => 0, 'y' => 5, 'width' => 12, 'height' => 10, 'min-height' => 5 ],
			'atum_sales_widget'         => [ 'x' => 0, 'y' => 5, 'width' =>  6, 'height' => 10, 'min-height' => 5 ],
			'atum_lost_sales_widget'    => [ 'x' => 1, 'y' => 6, 'width' =>  6, 'height' => 10, 'min-height' => 5 ],
			'atum_orders_widget'        => [ 'x' => 1, 'y' => 6, 'width' =>  6, 'height' => 10, 'min-height' => 5 ],
			'atum_promo_sales_widget'   => [ 'x' => 1, 'y' => 5, 'width' =>  6, 'height' => 10, 'min-height' => 5 ],
			'atum_stock_control_widget' => [ 'x' => 1, 'y' => 5, 'width' => 12, 'height' => 10, 'min-height' => 5 ],
			'atum_videos_widget'        => [ 'x' => 1, 'y' => 5, 'width' => 12, 'height' => 10, 'min-height' => 7 ],
		];

		Dashboard::save_user_widgets_layout( 1, $lout );

		$user_data = get_user_meta( 1, ATUM_PREFIX . 'dashboard_widgets_layout', true );

		//print_r($user_data);
		$this->assertEquals( $lout, $user_data );

		$ulout = Dashboard::get_user_widgets_layout();
		foreach ( $lout as $l => $val )
			$this->assertArrayHasKey( $l, $ulout );

		Dashboard::restore_user_widgets_layout( 1 );

		$this->assertEquals( '', get_user_meta( 1, ATUM_PREFIX . 'dashboard_widgets_layout', true ) );
	}

	public function test_get_default_widgets_layout() {
		wp_set_current_user( 1 );
		$data = Dashboard::get_default_widgets_layout();
		$ulout = Dashboard::get_user_widgets_layout();

		$this->assertEquals( $ulout, $data );
	}

	public function test_get_widgets() {
		$dash    = Dashboard::get_instance();
		$widgets = $dash->get_widgets();
		$this->assertGreaterThan( 0, count($widgets) );
	}

	public function test_get_widget_grid_item_defaults() {
		$dash = Dashboard::get_instance();
		$data = $dash->get_widget_grid_item_defaults();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'min-width', $data );
		$this->assertArrayHasKey( 'max-width', $data );
	}

}
