<?php
/**
 * Class ProductLocationsControllerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Controllers\V3\ProductLocationsController;
use TestHelpers\TestHelpers;

class ProductLocationsControllerTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( ProductLocationsController::class );
		$this->assertIsArray( $data );
		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new ProductLocationsController();
		$this->assertInstanceOf( ProductLocationsController::class, $obj );
	}

	public function test_get_item_schema() {
		$obj = new ProductLocationsController();
		$data = $obj->get_item_schema();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'id', $data['properties'] );
		$this->assertArrayHasKey( 'name', $data['properties'] );
		$this->assertArrayHasKey( 'slug', $data['properties'] );
		$this->assertArrayHasKey( 'parent', $data['properties'] );
		$this->assertArrayHasKey( 'description', $data['properties'] );
	}

	public function test_prepare_item_for_response() {
		$obj = new ProductLocationsController();
		$term = new WP_Term(wp_create_term( 'foo-loc', 'Foo Location' ));
		$request = new WP_REST_Request();
		$data = $obj->prepare_item_for_response( $term, $request );
		$this->assertInstanceOf( WP_REST_Response::class, $data );
		$this->assertEquals( 200, $data->status );
	}

}