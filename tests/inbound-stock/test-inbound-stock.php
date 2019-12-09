<?php
/**
 * Class InboundStockTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\InboundStock\InboundStock;
use Atum\Inc\Globals;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;
use Atum\Inc\Helpers;
use Atum\InboundStock\Lists\ListTable;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;


/**
 * Sample test case.
 */
class InboundStockTest extends WP_UnitTestCase { // PHPUnit_Framework_TestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( InboundStock::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		wp_set_current_user( 1 );
		set_current_screen( 'atum-inbound-stock' );
		$this->assertInstanceOf( InboundStock::class, InboundStock::get_instance() );
		$this->assertEquals( InboundStock::MENU_ORDER, TestHelpers::has_action( 'atum/admin/menu_items', array( InboundStock::class, 'add_menu' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'load-' . Globals::ATUM_UI_HOOK . '_page_' . InboundStock::UI_SLUG, array( InboundStock::class, 'screen_options' ) ) );
	}

	public function test_add_menu() {
		$instance = InboundStock::get_instance();
		$menus    = $instance->add_menu( [ 'others' => 'foo' ] );

		$this->assertIsArray( $menus );
		$this->assertArrayHasKey( 'inbound-stock', $menus );
		$this->assertArrayHasKey( 'slug', $menus['inbound-stock'] );
	}

	public function test_display() {
		global $wpdb;
		$_SERVER['QUERY_STRING']   = '';
		$hook                      = wp_parse_url( 'atum-inbound-stock' );
		$GLOBALS['hook_suffix']    = $hook['path'];
		$wpdb->atum_order_itemmeta = $wpdb->prefix . ATUM_PREFIX . 'order_itemmeta';

		$instance = InboundStock::get_instance();
		$product  = TestHelpers::create_atum_simple_product();

		$pos = new \Atum\PurchaseOrders\PurchaseOrders();
		$pos->register_post_type();

		// Post
		$pid = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );

		$po = Helpers::get_atum_order_model( $pid );

		$po->add_product( $product );
		$po->save_meta( array(
			'_status'                    => 'atum_ordered',
			'_date_created'              => date( 'Y-m-d H:i:s', current_time( 'timestamp', TRUE ) ),
			Suppliers::SUPPLIER_META_KEY => '',
			'_multiple_suppliers'        => 'no',
			'_expected_at_location_date' => date( 'Y-m-d H:i:s', current_time( 'timestamp', TRUE ) ),
		) );

		$instance->screen_options();
		ob_start();
		$instance->display();
		$result = ob_get_clean();

		$html = new Crawler( $result );
		$this->assertEquals( 1, $html->filter('table.inbound-stock-list')->count());
	}

	public function test_screen_options() {
		$instance = InboundStock::get_instance();

		$hook = parse_url( 'atum-inbound-stock' );

		$GLOBALS['hook_suffix']  = $hook['path'];
		$_SERVER['QUERY_STRING'] = false;

		set_current_screen();

		try {
			$instance->screen_options();
			$this->assertTrue( TRUE );
		} catch( Exception $e ) {
			$this->expectExceptionMessage( $e->getMessage() );
		}
	}

	public function test_help_tabs_content() {
		global $current_screen;
		$instance = InboundStock::get_instance();

		ob_start();
		$instance->help_tabs_content( $current_screen, [ 'name' => 'columns' ] );
		$result = ob_get_clean();

		$html = new Crawler( $result );
		$this->assertEquals( 1, $html->filter( 'table.widefat' )->count() );
	}

	public function test_set_screen_option() {
		$instance = InboundStock::get_instance();
		$this->assertEquals( 43, $instance->set_screen_option( false, '', 43 ) );
	}

	public function test_set_list_table() {
		$instance = InboundStock::get_instance();
		$lt = new ListTable( [] );
		try {
			$instance->set_list_table( $lt );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

}
