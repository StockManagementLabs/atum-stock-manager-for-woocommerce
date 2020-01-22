<?php
/**
 * Class AtumProductDataTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\Extenders\AtumProductData;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class AtumProductDataTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( AtumProductData::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$this->assertInstanceOf( AtumProductData::class, AtumProductData::get_instance() );
	}

	public function test_register_product_fields() {
		$apd = AtumProductData::get_instance();
		$apd->register_product_fields();
		$this->expectNotToPerformAssertions();
	}

	public function test_get_product_field_schema() {
		$apd = AtumProductData::get_instance();
		$fields = [ 'low_stock', 'atum_stock_status', 'atum_locations', 'lost_sales' ];
		foreach( $fields as $field ) {
			$data = $apd->get_product_field_schema( $field );
			$this->assertIsArray( $data );
			$this->assertArrayHasKey( 'type', $data );
			$this->assertArrayHasKey( 'description', $data );
		}
	}

	public function test_get_product_field_value() {
		$apd = AtumProductData::get_instance();
		$product = TestHelpers::create_atum_simple_product();
		$data = $apd->get_product_field_value( [ 'id' => $product->get_id() ], 'purchase_price', new WP_REST_Request() );
		$this->assertEquals( $product->get_purchase_price(), $data );
	}

	public function test_update_product_field_value() {
		wp_set_current_user( 1 );
		$apd = AtumProductData::get_instance();
		$product = TestHelpers::create_atum_simple_product();
		$data = $apd->update_product_field_value( 21, $product, 'purchase_price' );
		$this->assertTrue( $data );
	}

	public function test_filter_product_meta() {
		$this->expectNotToPerformAssertions();
	}

	public function test_prepare_objects_query() {
		$this->expectNotToPerformAssertions();
	}

	public function test_atum_product_data_query_clauses() {
		$this->expectNotToPerformAssertions();
	}

}