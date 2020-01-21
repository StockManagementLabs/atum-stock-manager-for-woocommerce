<?php
/**
 * Class PurchaseOrdersControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\PurchaseOrdersController;
use Atum\InventoryLogs\InventoryLogs;
use TestHelpers\TestHelpers;

class AtumOrdersControllerTest {
	//AtumOrdersController abstract class tested in InventoryLogsControllerTest
}

class PurchaseOrdersControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( PurchaseOrdersController::class );
		$this->assertIsArray( $data );
		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new PurchaseOrdersController();
		$this->assertInstanceOf( PurchaseOrdersController::class, $obj );
	}

	public function test_get_item_schema() {
		$obj = new PurchaseOrdersController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '$schema', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'properties', $data );
	}

	public function test_get_collection_params() {
		$obj = new PurchaseOrdersController();
		$data = $obj->get_collection_params();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'date_expected', $data );
		$this->assertArrayHasKey( 'supplier', $data );
		$this->assertArrayHasKey( 'multiple_suppliers', $data );
	}

	public function test_get_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new PurchaseOrdersController();
		$this->assertTrue( $obj->get_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_update_item_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new PurchaseOrdersController();
		$this->assertTrue( $obj->update_item_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_item_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new PurchaseOrdersController();
		$this->assertTrue( $obj->get_item_permissions_check( new WP_REST_Request() ) );
	}

	public function test_delete_item_permissions_check() {
		wp_set_current_user( 1 );
		$il = new InventoryLogs();
		$il->register_post_type();
		$order = TestHelpers::create_order();
		$request = new WP_REST_Request();
		$request->set_param( 'order_id', $order->get_id() );
		$obj = new PurchaseOrdersController();
		$this->assertTrue( $obj->delete_item_permissions_check( $request ) );
	}

	public function test_batch_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new PurchaseOrdersController();
		$this->assertTrue( $obj->batch_items_permissions_check( new WP_REST_Request() ) );
	}

}