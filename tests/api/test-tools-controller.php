<?php
/**
 * Class ToolsControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\ToolsController;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class ToolsControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( ToolsController::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new ToolsController();
		$this->assertInstanceOf( ToolsController::class, $obj );
	}

	public function test_register_routes() {
		$this->expectNotToPerformAssertions();
	}

	public function test_get_item_schema() {
		$obj = new ToolsController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '$schema', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'properties', $data );
	}

	public function test_get_collection_params() {
		$obj = new ToolsController();
		$data = $obj->get_collection_params();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'context', $data );
	}

	public function test_get_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new ToolsController();
		$this->assertTrue( $obj->get_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_item_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new ToolsController();
		$this->assertTrue( $obj->get_item_permissions_check( new WP_REST_Request() ) );
	}

	public function test_update_item_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new ToolsController();
		$this->assertTrue( $obj->update_item_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_tools() {
		$obj = new ToolsController();
		$data = $obj->get_tools();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'enable-manage-stock', $data );
		$this->assertArrayHasKey( 'disable-manage-stock', $data );
		$this->assertArrayHasKey( 'enable-atum-control', $data );
		$this->assertArrayHasKey( 'disable-atum-control', $data );
		$this->assertArrayHasKey( 'clear-out-stock-threshold', $data );
	}

	public function test_get_items() {
		$obj = new ToolsController();
		$data = $obj->get_items( new WP_REST_Request() );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	public function test_get_item() {
		$obj = new ToolsController();
		$request = new WP_REST_Request();
		$request->set_param( 'id', 'enable-atum-control' );
		$data = $obj->get_item( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	public function test_update_item() {
		$request = new WP_REST_Request();
		$request->set_param( 'id', 'enable-atum-control' );
		$request->set_param( 'value', 'yes' );
		$obj = new ToolsController();
		$data = $obj->update_item( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	/**
	 * @param $tool
	 * @dataProvider provideTool
	 */
	public function test_run_tool( $tool ) {
		$obj = new ToolsController();
		$data = $obj->run_tool( $tool, new WP_REST_Request() );
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'message', $data );

	}

	public function provideTool() {
		return [
			[ 'enable-atum-control' ],
			[ 'disable-atum-control' ],
			[ 'enable-manage-stock' ],
			[ 'disable-manage-stock' ],
			[ 'clear-out-stock-threshold' ],
		];
	}

	public function test_prepare_item_for_response() {
		$obj = new ToolsController();
		$data = $obj->prepare_item_for_response( [ 'id' => 'enable-atum-control' ], new WP_REST_Request() );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

}