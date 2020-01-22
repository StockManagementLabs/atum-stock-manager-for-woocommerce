<?php
/**
 * Class DashboardControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\DashboardController;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class DashboardControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( DashboardController::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new DashboardController();
		$this->assertInstanceOf( DashboardController::class, $obj );
	}

	public function test_register_routes() {
		//$obj = new DashboardController();
		//$obj->register_routes();
		//$data = rest_get_server()->get_routes();
		//$this->assertArrayHasKey( '/wc/v3/atum/dashboard', $data );
		$this->expectNotToPerformAssertions();
	}

	public function test_get_item_schema() {
		$obj = new DashboardController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '$schema', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'properties', $data );
	}

	public function test_get_collection_params() {
		$obj = new DashboardController();
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
		$obj = new DashboardController();
		$this->assertTrue( $obj->get_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_items() {
		$obj = new DashboardController();
		//\Atum\Dashboard\Dashboard::get_instance()->load_widgets();
		$data = $obj->get_items( new WP_REST_Request() );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	public function test_prepare_item_for_response() {
		$obj = new DashboardController();
		$widget = new stdClass();
		$widget->slug = 'my-cool-slug';
		$widget->description = 'My amazing description';
		$data = $obj->prepare_item_for_response( $widget, new WP_REST_Request() );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

}