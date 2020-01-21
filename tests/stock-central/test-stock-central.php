<?php
/**
 * Class StockCentralTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\StockCentral\StockCentral;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

class StockCentralTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( StockCentral::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		wp_set_current_user( 1 );
		set_current_screen( 'atum-stock-central' );

		$obj = StockCentral::get_instance();
		$this->assertInstanceOf( StockCentral::class, $obj );

		$this->assertEquals( StockCentral::MENU_ORDER, TestHelpers::has_action( 'atum/admin/menu_items', array( $obj, 'add_menu' ) ) );
		//$this->assertEquals( 10, TestHelpers::has_action( 'load-atum-inventory_page_atum-stock-central', array( $obj, 'screen_options' ) ) );
		//$this->assertEquals( 10, TestHelpers::has_action( 'load-toplevel_page_atum-stock-central', array( $obj, 'screen_options' ) ) );
		//$this->assertEquals( 10, TestHelpers::has_action( 'atum/settings/tabs', array( StockCentral::class, 'add_settings_tab' ) ) );
		//$this->assertEquals( 10, TestHelpers::has_action( 'atum/settings/defaults', array( StockCentral::class, 'add_settings_defaults' ) ) );
		//$this->assertEquals( 10, TestHelpers::has_action( 'admin_enqueue_scripts', array( StockCentral::class, 'setup_help_pointers' ) ) );
	}

	public function test_add_menu() {
		$obj = StockCentral::get_instance();
		$data = $obj->add_menu( [] );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'stock-central', $data );
		$this->assertArrayHasKey( 'slug', $data['stock-central'] );
		$this->assertEquals( 'atum-stock-central', $data['stock-central']['slug'] );
	}

	public function test_display() {
		$_SERVER['QUERY_STRING'] = '';
		$hook                    = wp_parse_url( 'atum-stock-central' );
		$GLOBALS['hook_suffix']  = $hook['path'];
		$obj = StockCentral::get_instance();

		// Product needed.
		$p = $this->factory()->post->create( array(
			'post_title' => 'Foo',
			'post_type'  => 'product',
			'post_status' => 'publish',
		) );

		ob_start();
		$obj->screen_options();
		$obj->display();
		$data = ob_get_clean();

		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter( 'div#scroll-stock_central_nav')->count() );
	}

	public function test_screen_options() {
		$_SERVER['QUERY_STRING'] = '';
		$hook                    = wp_parse_url( 'atum-stock-central' );
		$GLOBALS['hook_suffix']  = $hook['path'];
		$obj = StockCentral::get_instance();
		$obj->screen_options();
		$this->assertTrue( true );
	}

	public function test_help_tabs_content() {
		$obj = StockCentral::get_instance();
		$tabs = $obj->add_settings_tab( [] );
		ob_start();
		$obj->help_tabs_content( 'atum-stock-central', ['name' => 'general' ] );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter( 'h2')->count() );
		$this->assertEquals( 3, $html->filter( 'p')->count() );
	}

	public function test_add_settings_tab() {
		$obj = StockCentral::get_instance();
		$data = $obj->add_settings_tab( [] );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'stock_central', $data );
		$this->assertArrayHasKey( 'sections', $data['stock_central'] );
		$this->assertArrayHasKey( 'stock_central', $data['stock_central']['sections'] );
	}

	public function test_add_settings_defaults() {
		$obj = StockCentral::get_instance();
		$data = $obj->add_settings_defaults( [] );
		foreach( $data as $dt ) {
			$this->assertIsArray( $dt );
			$this->assertArrayHasKey( 'group', $dt );
			$this->assertArrayHasKey( 'section', $dt );
			$this->assertArrayHasKey( 'name', $dt );
			$this->assertArrayHasKey( 'desc', $dt );
			$this->assertArrayHasKey( 'type', $dt );
			$this->assertArrayHasKey( 'default', $dt );
		}
	}

	public function test_setup_help_pointers() {
		$obj = StockCentral::get_instance();
		$obj->setup_help_pointers();
		$this->assertTrue( TRUE );
	}

}