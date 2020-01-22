<?php
/**
 * Class SettingOptionsControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\SettingOptionsController;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class SettingOptionsControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( SettingOptionsController::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new SettingOptionsController();
		$this->assertInstanceOf( SettingOptionsController::class, $obj );
	}

	public function test_get_item_schema() {
		$obj = new SettingOptionsController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '$schema', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'properties', $data );
	}

	public function test_get_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new SettingOptionsController();
		$this->assertTrue( $obj->get_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_update_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new SettingOptionsController();
		$this->assertTrue( $obj->update_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_allowed_setting_keys() {
		$obj = new SettingOptionsController();
		$this->assertTrue( $obj->allowed_setting_keys( 'id' ) );
		$this->assertTrue( $obj->allowed_setting_keys( 'group' ) );
		$this->assertTrue( $obj->allowed_setting_keys( 'section' ) );
		$this->assertTrue( $obj->allowed_setting_keys( 'name' ) );
		$this->assertTrue( $obj->allowed_setting_keys( 'desc' ) );
		$this->assertTrue( $obj->allowed_setting_keys( 'default' ) );
		$this->assertTrue( $obj->allowed_setting_keys( 'type' ) );
		$this->assertTrue( $obj->allowed_setting_keys( 'options' ) );
		$this->assertTrue( $obj->allowed_setting_keys( 'value' ) );
		$this->assertTrue( $obj->allowed_setting_keys( 'dependency' ) );
		$this->assertTrue( $obj->allowed_setting_keys( 'confirm_msg' ) );
		$this->assertTrue( $obj->allowed_setting_keys( 'to_user_meta' ) );
		$this->assertFalse( $obj->allowed_setting_keys( 'foo' ) );
	}

	public function test_is_setting_type_valid() {
		$obj = new SettingOptionsController();
		$this->assertTrue( $obj->is_setting_type_valid( 'text' ) );
		$this->assertTrue( $obj->is_setting_type_valid( 'switcher' ) );
		$this->assertTrue( $obj->is_setting_type_valid( 'number' ) );
		$this->assertTrue( $obj->is_setting_type_valid( 'color' ) );
		$this->assertTrue( $obj->is_setting_type_valid( 'textarea' ) );
		$this->assertTrue( $obj->is_setting_type_valid( 'select' ) );
		$this->assertTrue( $obj->is_setting_type_valid( 'button_group' ) );
		$this->assertTrue( $obj->is_setting_type_valid( 'wc_country' ) );
		$this->assertTrue( $obj->is_setting_type_valid( 'theme_selector' ) );
		$this->assertFalse( $obj->is_setting_type_valid( 'radio' ) );
		$this->assertFalse( $obj->is_setting_type_valid( 'foo' ) );
	}

	public function test_get_items() {
		$obj = new SettingOptionsController();
		$request = new WP_REST_Request();
		$request->set_param( 'group_id', 'general' );
		$data = $obj->get_items( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	public function test_get_group_settings() {
		$obj = new SettingOptionsController();
		$data = $obj->get_group_settings( 'general' );
		foreach ( $data as $option )
			$this->assertEquals( 'general', $option['group'] );
	}

	public function test_filter_setting() {
		$obj = new SettingOptionsController();
		$general = $obj->get_group_settings( 'general' );
		foreach ( $general as $g) {
			$data = $obj->filter_setting( $g );
			$this->assertEquals( $g, $data );
		}
	}

	public function test_update_item() {
		$obj = new SettingOptionsController();
		$request = new WP_REST_Request();
		$request->set_param( 'group_id', 'general' );
		$request->set_param( 'id', 'delete_data' );
		$request->set_param( 'value', 'yes' );
		$data = $obj->update_item( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

}