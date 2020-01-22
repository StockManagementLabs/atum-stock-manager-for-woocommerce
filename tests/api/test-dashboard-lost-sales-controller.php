<?php
/**
 * Class DashboardLostSalesControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\DashboardLostSalesController;
use TestHelpers\TestHelpers;

class DashboardLostSalesControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( DashboardLostSalesController::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new DashboardLostSalesController();
		$this->assertInstanceOf( DashboardLostSalesController::class, $obj );
	}

	public function test_get_item_schema() {
		$obj = new DashboardLostSalesController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '$schema', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'properties', $data );
	}

	public function test_get_collection_params() {
		$obj = new DashboardLostSalesController();
		$data = $obj->get_collection_params();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'context', $data );
		$this->assertArrayHasKey( 'type', $data['context'] );
		$this->assertArrayHasKey( 'default', $data['context'] );
		$this->assertArrayHasKey( 'enum', $data['context'] );
		$this->assertArrayHasKey( 'description', $data['context'] );
		$this->assertArrayHasKey( 'sanitize_callback', $data['context'] );
		$this->assertArrayHasKey( 'validate_callback', $data['context'] );
	}

	public function test_get_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new DashboardLostSalesController();
		$this->assertTrue( $obj->get_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_items() {
		TestHelpers::create_product();
		$obj = new DashboardLostSalesController();
		//\Atum\Dashboard\Dashboard::get_instance()->load_widgets();
		$data = $obj->get_items( new WP_REST_Request() );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	public function test_prepare_item_for_response() {
		TestHelpers::create_product();
		$obj = new DashboardLostSalesController();
		$data = $obj->prepare_item_for_response( null, new WP_REST_Request() );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

}