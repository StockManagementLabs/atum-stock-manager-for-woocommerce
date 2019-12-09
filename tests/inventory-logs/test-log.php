<?php
/**
 * Class LogTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\InventoryLogs\InventoryLogs;
use Atum\InventoryLogs\Models\Log;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class LogTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( Log::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new Log();
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/atum_order/item_bulk_controls', array( Log::class, 'add_stock_buttons' ) ) );
	}

	public function test_add_stock_buttons() {
		$obj = new Log();
		ob_start();
		$obj->add_stock_buttons();
		$data = ob_get_clean();

		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('button.bulk-increase-stock')->count() );
		$this->assertEquals( 1, $html->filter('button.bulk-decrease-stock')->count() );
	}

	public function test_get_title() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( 'Foo post', $obj->get_title() );
	}

	public function test_set_order() {
		//Tested in next method
		$this->expectNotToPerformAssertions();
	}

	public function test_get_order() {
		global $post;
		$order = TestHelpers::create_order();
		$obj = new Log( $post->ID );
		$obj->set_order( $order->get_id() );
		$data = $obj->get_order();
		$this->assertEquals( $order->get_id(), $data->get_id() );
	}

	public function test_get_log_types() {
		$data = Log::get_log_types();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'reserved-stock', $data );
		$this->assertArrayHasKey( 'customer-returns', $data );
		$this->assertArrayHasKey( 'warehouse-damage', $data );
		$this->assertArrayHasKey( 'lost-in-post', $data );
		$this->assertArrayHasKey( 'other', $data );
	}

	public function test_get_log_type_columns() {
		$data = Log::get_log_type_columns();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'reserved-stock', $data );
		$this->assertArrayHasKey( 'customer-returns', $data );
		$this->assertArrayHasKey( 'warehouse-damage', $data );
		$this->assertArrayHasKey( 'lost-in-post', $data );
		$this->assertArrayHasKey( 'other', $data );
	}

	public function test_set_log_type() {
		//Tested in next method
		$this->expectNotToPerformAssertions();
	}

	public function test_get_log_type() {
		global $post;
		$obj = new Log( $post->ID );
		$obj->set_log_type( 'other' );
		$this->assertEquals( 'other', $obj->get_log_type() );
	}

	public function test_get_type() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( InventoryLogs::POST_TYPE, $obj->get_type() );
	}

	public function test_set_reservation_date() {
		global $post;
		try {
			$date = date( 'Y-m-d' );
			$obj  = new Log( $post->ID );
			$obj->set_reservation_date( $date );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_get_reservation_date() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertContains( date('Y-m-d'), $obj->get_reservation_date() );
	}

	public function test_set_damage_date() {
		global $post;
		try {
			$date = date( 'Y-m-d' );
			$obj  = new Log( $post->ID );
			$obj->set_damage_date( $date );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_get_damage_date() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertContains( date('Y-m-d '), $obj->get_damage_date() );
	}

	public function test_set_return_date() {
		global $post;
		try {
			$date = date( 'Y-m-d' );
			$obj  = new Log( $post->ID );
			$obj->set_return_date( $date );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_get_return_date() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertContains( date('Y-m-d '), $obj->get_return_date() );
	}

	public function test_set_custom_name() {
		//Tested in next method
		$this->expectNotToPerformAssertions();
	}

	public function test_get_custom_name() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( 'Foo custom name', $obj->get_custom_name() );
		$obj->set_custom_name( 'Test name' );
		$this->assertEquals( 'Test name', $obj->get_custom_name() );
	}

	public function test_set_shipping_company() {
		//Tested in next method
		$this->expectNotToPerformAssertions();
	}

	public function test_get_shipping_company() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( 'Foo company', $obj->get_shipping_company() );
		$obj->set_shipping_company( 'Another foo company' );
		$this->assertEquals( 'Another foo company', $obj->get_shipping_company() );
	}

	public function test_get_atum_order_item() {
		$this->expectNotToPerformAssertions();
		/* FIXME
		global $post;
		$obj = new Log( $post->ID );
		$product = TestHelpers::create_atum_simple_product();
		$order = TestHelpers::create_order( $product );

		$item = new WC_Order_Item_Product();
		$item->set_props( [
			'product'  => $product,
			'quantity' => 4,
			'subtotal' => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
			'total'    => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
		] );
		$item->set_order_id( $order->get_id() );
		$item->save();
		//$obj->set_order( $order->get_id() );
		$obj->add_item( $item );

		$this->assertIsObject( $obj->get_atum_order_item( $item ) );*/
	}

	public function test_after_save() {
		global $post;
		$obj = new Log( $post->ID );
		$obj->after_save( $obj );
		$this->assertTrue( true );
	}

	public function test_get_data() {
		global $post;
		$obj = new Log( $post->ID );
		$data = $obj->get_data();
		$this->assertIsArray( $data );
		$this->assertEquals( 'other', $data['type'] );
		$this->assertEquals( 'Foo company', $data['shipping_company'] );
		$this->assertEquals( 'Foo custom name', $data['custom_name'] );
	}

	public function setUp() {
		parent::setUp();

		wp_set_current_user( 1 );
		global $post;
		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo post',
			'post_type'   => InventoryLogs::POST_TYPE,
			'post_status' => ATUM_PREFIX . 'pending',
			'log_type'    => 'other',
		] );

		$metas = [
			'_type'             => 'other',
			'_reservation_date' => date('Y-m-d H:i:s'),
			'_damage_date'      => date('Y-m-d H:i:s'),
			'_return_date'      => date('Y-m-d H:i:s'),
			'atum_order_type'   => InventoryLogs::POST_TYPE,
			'status'            => ATUM_PREFIX . 'pending',
			'atum_meta_nonce'   => wp_create_nonce( 'atum_save_meta_data' ),
			'_order'            => 555,
			'_custom_name'      => 'Foo custom name',
			'description'       => 'Some description',
			'_shipping_company' => 'Foo company',
		];
		foreach( $metas as $k => $m )
			add_post_meta( $post->ID, $k, $m );
	}
}
