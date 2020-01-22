<?php
/**
 * Class GlobalsTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class GlobalsTest extends PHPUnit_Framework_TestCase { //WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( Globals::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_get_product_types() {
		$data = Globals::get_product_types();
		$this->assertIsArray( $data );
		$this->assertTrue( in_array( 'simple', $data ) );
		$this->assertTrue( in_array( 'variable', $data ) );
		$this->assertTrue( in_array( 'grouped', $data ) );
	}

	public function test_get_inheritable_product_types() {
		$data = Globals::get_inheritable_product_types();
		$this->assertIsArray( $data );
		$this->assertTrue( in_array( 'variable', $data ) );
		$this->assertTrue( in_array( 'grouped', $data ) );
	}

	public function test_get_child_product_types() {
		$data = Globals::get_child_product_types();
		$this->assertTrue( in_array( 'variation', $data ) );
	}

	public function test_get_all_compatible_products() {
		$data = Globals::get_all_compatible_products();
		$this->assertIsArray( $data );
		$this->assertTrue( in_array( 'simple', $data ) );
		$this->assertTrue( in_array( 'variable', $data ) );
		$this->assertTrue( in_array( 'grouped', $data ) );
		$this->assertTrue( in_array( 'variation', $data ) );
	}

	public function test_get_product_types_with_stock() {
		$data = Globals::get_product_types_with_stock();
		$this->assertIsArray( $data );
		$this->assertTrue( in_array( 'simple', $data ) );
		$this->assertTrue( in_array( 'variable', $data ) );
		$this->assertTrue( in_array( 'variation', $data ) );
	}

	public function test_get_incompatible_products() {
		$data = Globals::get_incompatible_products();
		$this->assertIsArray( $data );
		$this->assertTrue( in_array( 'external', $data ) );
	}

	public function test_get_order_types() {
		$data = Globals::get_order_types();
		$this->assertIsArray( $data );
		$this->assertTrue( in_array( 'atum_purchase_order', $data ) );
		$this->assertTrue( in_array( 'atum_inventory_log', $data ) );
	}

	public function test_get_stock_decimals() {
		$data = Globals::get_stock_decimals();
		$this->assertEquals( 0, $data );
	}

	public function test_set_stock_decimals() {
		Globals::set_stock_decimals( 2 );
		$data = Globals::get_stock_decimals();
		$this->assertEquals( 2, $data );
	}

	public function test_get_product_tab_fields() {
		$data = Globals::get_product_tab_fields();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '_atum_manage_stock', $data );
		$this->assertEquals( $data['_atum_manage_stock'], 'checkbox' );
	}

	public function test_enable_atum_product_data_models() {
		Globals::enable_atum_product_data_models();
		$this->assertEquals( PHP_INT_MAX, TestHelpers::has_action( 'woocommerce_product_class', array( Globals::class, 'get_atum_product_data_model_class' ) ) );
		$this->assertEquals( PHP_INT_MAX, TestHelpers::has_action( 'woocommerce_data_stores', array( Globals::class, 'replace_wc_data_stores' ) ) );
	}

	public function test_disable_atum_product_data_models() {
		Globals::disable_atum_product_data_models();
		$this->assertFalse( TestHelpers::has_action( 'woocommerce_product_class', array( Globals::class, 'get_atum_product_data_model_class' ) ) );
		$this->assertFalse( TestHelpers::has_action( 'woocommerce_data_stores', array( Globals::class, 'replace_wc_data_stores' ) ) );
	}

	public function test_get_atum_product_data_model_class() {
		Globals::enable_atum_product_data_models();
		$product = Helpers::get_atum_product( TestHelpers::create_product() );
		$data = Globals::get_atum_product_data_model_class( WC_Product_Simple::class, 'simple', 'product', $product->get_id() );
		$this->assertIsString( $data );
	}

	public function test_replace_wc_data_stores() {
		$data = Globals::replace_wc_data_stores( [] );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'product', $data );
		$this->assertArrayHasKey( 'product-grouped', $data );
		$this->assertArrayHasKey( 'product-variable', $data );
		$this->assertArrayHasKey( 'product-variation', $data );
	}

	public function test_get_order_type_table_id() {
		$data = Globals::get_order_type_table_id( 'shop_order' );
		$this->assertEquals( 1, $data );
		$data = Globals::get_order_type_table_id( 'atum_purchase_order' );
		$this->assertEquals( 2, $data );
		$data = Globals::get_order_type_table_id( 'atum_inventory_log' );
		$this->assertEquals( 3, $data );
	}

	public function test_get_date_time_picker_js_vars() {
		$data = Globals::get_date_time_picker_js_vars( [] );
		$this->assertIsArray( $data );
	}

}
