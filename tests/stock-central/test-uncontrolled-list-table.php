<?php
/**
 * Class UncontrolledListTableTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\StockCentral\Lists\UncontrolledListTable;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class UncontrolledListTableTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( UncontrolledListTable::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		wp_set_current_user( 1 );
		set_current_screen( 'atum-stock-central' );
		$_SERVER['QUERY_STRING'] = '';
		$hook                    = wp_parse_url( 'atum-stock-central' );
		$GLOBALS['hook_suffix']  = $hook['path'];

		$obj = new UncontrolledListTable();
		$this->assertInstanceOf( UncontrolledListTable::class, $obj );
	}

}