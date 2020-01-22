<?php
/**
 * Class AddonsControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\AddonsController;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class AddonsControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( AddonsController::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new AddonsController();
		$this->assertInstanceOf( AddonsController::class, $obj );
	}

	public function test_register_routes() {
		//$obj = new AddonsController();
		//$obj->register_routes();
		//$data = rest_get_server()->get_routes();
		//$this->assertArrayHasKey( '/wc/v3/atum/addons', $data );
		$this->expectNotToPerformAssertions();
	}

	public function test_get_item_schema() {
		$obj = new AddonsController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '$schema', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'properties', $data );
	}

	public function test_get_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new AddonsController();
		$this->assertTrue( $obj->get_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_items() {
		//$obj = new AddonsController();
		//Addons::get_instance()->init_addons();
		//$data = $obj->get_items( new WP_REST_Request() );
		$this->expectNotToPerformAssertions();
	}

}