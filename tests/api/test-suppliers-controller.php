<?php
/**
 * Class SuppliersControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\SuppliersController;
use Atum\Suppliers\Suppliers;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class SuppliersControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( SuppliersController::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new SuppliersController();
		$this->assertInstanceOf( SuppliersController::class, $obj );
	}

	public function test_register_routes() {
		$this->expectNotToPerformAssertions();
	}

	public function test_get_item_schema() {
		$obj = new SuppliersController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '$schema', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'properties', $data );
	}

	public function test_get_collection_params() {
		$obj = new SuppliersController();
		$data = $obj->get_collection_params();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'slug', $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'currency', $data );
		$this->assertArrayHasKey( 'country', $data );
		$this->assertArrayHasKey( 'assigned_to', $data );
		$this->assertArrayHasKey( 'product', $data );
	}

	public function test_get_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new SuppliersController();
		$this->assertTrue( $obj->get_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_create_item_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new SuppliersController();
		$this->assertTrue( $obj->create_item_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_item_permissions_check() {
		wp_set_current_user( 1 );
		$supplier = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		$obj = new SuppliersController();
		$request = new WP_REST_Request();
		$request->set_param( 'id', $supplier->ID );
		$this->assertTrue( $obj->get_item_permissions_check( $request ) );
	}

	public function test_update_item_permissions_check() {
		wp_set_current_user( 1 );
		$supplier = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		$obj = new SuppliersController();
		$request = new WP_REST_Request();
		$request->set_param( 'id', $supplier->ID );
		$this->assertTrue( $obj->update_item_permissions_check( $request ) );
	}

	public function test_delete_item_permissions_check() {
		wp_set_current_user( 1 );
		$supplier = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		$obj = new SuppliersController();
		$request = new WP_REST_Request();
		$request->set_param( 'id', $supplier->ID );
		$this->assertTrue( $obj->delete_item_permissions_check( $request ) );
	}

	public function test_batch_items_permissions_check() {
		wp_set_current_user( 1 );
		$obj = new SuppliersController();
		$this->assertTrue( $obj->batch_items_permissions_check( new WP_REST_Request() ) );
	}

	public function test_get_items() {
		Suppliers::get_instance()->register_post_type();
		$obj = new SuppliersController();
		$this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		$request = new WP_REST_Request();
		$request->set_param( 'offset', 1 );
		$request->set_param( 'order', TRUE );
		$request->set_param( 'orderby', 'ID' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'include', '' );
		$request->set_param( 'exclude', [] );
		$request->set_param( 'per_page', 20 );
		$request->set_param( 'slug', 'foo-slug' );
		$request->set_param( 'parent', '' );
		$request->set_param( 'parent_exclude', [] );
		$request->set_param( 'search', '' );
		$request->set_param( 'status', '' );

		$data = $obj->get_items( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	public function test_prepare_item_for_response() {
		$obj = new SuppliersController();
		$supplier = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		$request = new WP_REST_Request();
		$request->set_param( '_fields', [ 'id', 'name', 'slug', 'permalink' ] );
		$data = $obj->prepare_item_for_response( $supplier, $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	public function test_create_item() {
		$obj = new SuppliersController();
		$request = new WP_REST_Request();
		$request->set_param( 'name', 'foo message');
		$data = $obj->create_item( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 201, $data->status );
	}

	public function test_update_item() {
		$supplier = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		$request = new WP_REST_Request();
		$request->set_param( 'id', $supplier->ID );
		$request->set_param( 'name', 'New name' );
		$obj = new SuppliersController();
		$data = $obj->update_item( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

	public function test_delete_item() {
		wp_set_current_user( 1 );
		$supplier = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		$request = new WP_REST_Request();
		$request->set_param( 'id', $supplier->ID );
		$obj = new SuppliersController();
		$data = $obj->delete_item( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

}