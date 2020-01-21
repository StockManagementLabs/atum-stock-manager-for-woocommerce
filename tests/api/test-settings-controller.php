<?php
/**
 * Class SettingsControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\SettingsController;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class SettingsControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( SettingsController::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new SettingsController();
		$this->assertInstanceOf( SettingsController::class, $obj );
	}

	public function test_get_item_schema() {
		$obj = new SettingsController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '$schema', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'properties', $data );
	}

	public function test_get_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new SettingsController();
		$this->assertTrue( $obj->get_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_update_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new SettingsController();
		$this->assertTrue( $obj->update_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_items() {
		$obj = new SettingsController();
		$request = new WP_REST_Request();
		$data = $obj->get_items( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	public function test_update_item() {
		$obj = new SettingsController();
		$request = new WP_REST_Request();
		$request->set_param( 'group_id', 'general' );
		$request->set_param( 'id', 'delete_data' );
		$request->set_param( 'value', 'yes' );
		$data = $obj->update_item( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	public function test_allowed_group_keys() {
		$obj = new SettingsController();
		$this->assertTrue( $obj->allowed_group_keys( 'id' ) );
		$this->assertTrue( $obj->allowed_group_keys( 'label' ) );
		$this->assertTrue( $obj->allowed_group_keys( 'description' ) );
		$this->assertTrue( $obj->allowed_group_keys( 'sections' ) );
		$this->assertFalse( $obj->allowed_group_keys( 'foo' ) );
		$this->assertFalse( $obj->allowed_group_keys( 'random_key' ) );
	}

}