<?php
/**
 * Class ProductDataQueryTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Queries\ProductDataQuery;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class ProductDataQueryTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( ProductDataQuery::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new ProductDataQuery();
		$this->assertInstanceOf( ProductDataQuery::class, $obj );
	}

	public function test_get_sql() {
		$obj = new ProductDataQuery();
		$data = $obj->get_sql();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'join', $data );
		$this->assertArrayHasKey( 'where', $data );
	}

	public function test_get_sql_for_clause() {
		$obj = new ProductDataQuery();
		$query = $obj->get_sql();
		$data = $obj->get_sql_for_clause( $query, [] );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'join', $data );
		$this->assertArrayHasKey( 'where', $data );
	}

}