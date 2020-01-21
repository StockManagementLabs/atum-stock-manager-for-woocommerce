<?php
/**
 * Class InventoryLogsControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\InventoryLogsController;
use Atum\InventoryLogs\InventoryLogs;
use TestHelpers\TestHelpers;

class InventoryLogsControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( InventoryLogsController::class );
		$this->assertIsArray( $data );
		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new InventoryLogsController();
		$this->assertInstanceOf( InventoryLogsController::class, $obj );
	}

	public function test_get_item_schema() {
		$obj = new InventoryLogsController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '$schema', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'properties', $data );
	}

	public function test_get_collection_params() {
		$obj = new InventoryLogsController();
		$data = $obj->get_collection_params();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'type', $data );
		$this->assertArrayHasKey( 'context', $data );
		$this->assertArrayHasKey( 'page', $data );
		$this->assertArrayHasKey( 'offset', $data );
		$this->assertArrayHasKey( 'include', $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'product', $data );
		$this->assertArrayHasKey( 'dp', $data );
	}

	public function test_get_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new InventoryLogsController();
		$this->assertTrue( $obj->get_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_update_item_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new InventoryLogsController();
		$this->assertTrue( $obj->update_item_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_item_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new InventoryLogsController();
		$this->assertTrue( $obj->get_item_permissions_check( new WP_REST_Request() ) );
	}

	public function test_delete_item_permissions_check() {
		wp_set_current_user( 1 );
		$il = new InventoryLogs();
		$il->register_post_type();
		$order = TestHelpers::create_order();
		$request = new WP_REST_Request();
		$request->set_param( 'order_id', $order->get_id() );
		$obj = new InventoryLogsController();
		$this->assertTrue( $obj->delete_item_permissions_check( $request ) );
	}

	public function test_batch_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new InventoryLogsController();
		$this->assertTrue( $obj->batch_items_permissions_check( new WP_REST_Request() ) );
	}

}