<?php
/**
 * Class InboundStockControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\InboundStockController;
use Atum\Inc\Helpers;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;
use TestHelpers\TestHelpers;

class InboundStockControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( InboundStockController::class );
		$this->assertIsArray( $data );
		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new InboundStockController();
		$this->assertInstanceOf( InboundStockController::class, $obj );
	}

	public function test_register_routes() {
		//$obj = new InboundStockController();
		//$obj->register_routes();
		$this->expectNotToPerformAssertions();
	}

	public function test_get_item_schema() {
		$obj = new InboundStockController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '$schema', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'properties', $data );
	}

	public function test_get_collection_params() {
		$obj = new InboundStockController();
		$data = $obj->get_collection_params();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'page', $data );
		$this->assertArrayHasKey( 'per_page', $data );
		$this->assertArrayHasKey( 'search', $data );
		$this->assertArrayHasKey( 'after', $data );
		$this->assertArrayHasKey( 'before', $data );
		$this->assertArrayHasKey( 'include', $data );
		$this->assertArrayHasKey( 'exclude', $data );
		$this->assertArrayHasKey( 'offset', $data );
		$this->assertArrayHasKey( 'order', $data );
		$this->assertArrayHasKey( 'orderby', $data );
	}

	public function test_get_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new InboundStockController();
		$this->assertTrue( $obj->get_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_item_permissions_check() {
		wp_set_current_user( 1 );
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

		$request = new WP_REST_Request();
		$request->set_param( 'id', $pid );
		$obj = new InboundStockController();
		$this->assertTrue( $obj->get_item_permissions_check( $request ) );
	}

	public function test_get_items() {
		$obj = new InboundStockController();
		$request = new WP_REST_Request();
		$request->set_param( 'orderby', 'ID' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'paged', 1 );
		$data = $obj->get_items( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	public function test_get_item() {
		$obj = new InboundStockController();
		$request = new WP_REST_Request();
		//Product
		$product = TestHelpers::create_atum_simple_product();
		// Post
		$pos = new \Atum\PurchaseOrders\PurchaseOrders();
		$pos->register_post_type();
		$pid = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$po = Helpers::get_atum_order_model( $pid );
		$po->add_product( $product );
		$request->set_param( 'id', $product->get_id() );
		$data = $obj->get_item( $request );
		$this->assertInstanceOf( WP_Error::class, $data );
	}

	public function test_prepare_object_for_response() {
		$obj = new InboundStockController();
		$data = $obj->prepare_item_for_response( null, new WP_REST_Request() );
		$this->assertInstanceOf( WP_Error::class, $data );
	}

}