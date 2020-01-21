<?php
/**
 * Class InventoryLogNotesControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\InventoryLogNotesController;
use Atum\InventoryLogs\InventoryLogs;
use TestHelpers\TestHelpers;

class InventoryLogNotesControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( InventoryLogNotesController::class );
		$this->assertIsArray( $data );
		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new InventoryLogNotesController();
		$this->assertInstanceOf( InventoryLogNotesController::class, $obj );
	}

	public function test_get_item_schema() {
		$obj = new InventoryLogNotesController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '$schema', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'properties', $data );
	}

	public function test_get_collection_params() {
		$obj = new InventoryLogNotesController();
		$data = $obj->get_collection_params();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'type', $data );
		$this->assertArrayHasKey( 'default', $data['type'] );
		$this->assertArrayHasKey( 'description', $data['type'] );
		$this->assertArrayHasKey( 'type', $data['type'] );
		$this->assertArrayHasKey( 'enum', $data['type'] );
		$this->assertArrayHasKey( 'sanitize_callback', $data['type'] );
		$this->assertArrayHasKey( 'validate_callback', $data['type'] );
	}

	public function test_get_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new InventoryLogNotesController();
		$this->assertTrue( $obj->get_item_permissions_check( new WP_REST_Request() ) );
	}

	public function test_create_item_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new InventoryLogNotesController();
		$this->assertTrue( $obj->create_item_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_item_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new InventoryLogNotesController();
		$this->assertTrue( $obj->get_item_permissions_check( new WP_REST_Request() ) );
	}

	public function test_delete_item_permissions_check() {
		wp_set_current_user( 1 );
		$il = new InventoryLogs();
		$il->register_post_type();
		$order = TestHelpers::create_order();
		$request = new WP_REST_Request();
		$request->set_param( 'order_id', $order->get_id() );
		$obj = new InventoryLogNotesController();
		$this->assertTrue( $obj->delete_item_permissions_check( $request ) );
	}

	public function test_get_items() {
		$obj = new InventoryLogNotesController();
		$order = TestHelpers::create_order();
		$request = new WP_REST_Request();
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'type', 'system' );
		$data = $obj->get_items( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
	}

	public function test_get_item() {
		//Tested in next method
		$this->expectNotToPerformAssertions();
	}

	public function test_create_item() {
		$obj = new InventoryLogNotesController();
		$order = TestHelpers::create_order();

		//Create method
		$request = new WP_REST_Request();
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'note', 'Hello world' );
		$request->set_param( 'added_by_user', 1 );
		//$request->set_param( 'id', 0 );
		$data = $obj->create_item( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 'Hello world', $data->data['note'] );
		$note_id = $data->data['id'];

		//Get method
		$request2 = new WP_REST_Request();
		$request2->set_param( 'order_id', $order->get_id() );
		$request2->set_param( 'id', $note_id );
		$data2 = $obj->get_item( $request2 );
		$this->assertInstanceOf( WP_REST_Response::class, $data2 );
		$this->assertEquals( 'Hello world', $data2->data['note'] );
		$this->assertEquals( 200, $data2->status );

		//Prepare method
		$note = get_comment( $note_id );
		$data3 = $obj->prepare_item_for_response( $note, $request2 );
		$this->assertEquals( $data2, $data3 );

		//Delete method
		$request3 = new WP_REST_Request();
		$request3->set_param( 'order_id', $order->get_id() );
		$request3->set_param( 'id', $note_id );
		$request3->set_param( 'force', TRUE );
		$data4 = $obj->delete_item( $request3 );
		$this->assertEquals( $data2, $data4 );

		//Get again
		$data5 = $obj->get_item( $request2 );
		$this->assertInstanceOf( WP_Error::class, $data5 );
		$this->assertEquals( 'Invalid resource ID.', $data5->errors['atum_rest_invalid_id'][0] );
	}

	public function test_prepare_item_for_response() {
		//Tested in previous method
		$this->expectNotToPerformAssertions();
	}

	public function test_delete_item() {
		//Tested in previous method
		$this->expectNotToPerformAssertions();
	}

}