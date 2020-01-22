<?php
/**
 * Class AtumListTableTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\InboundStock\Lists\ListTable;
use Atum\StockCentral\StockCentral;
use TestHelpers\TestHelpers;

class AtumListTableTest extends WP_UnitTestCase {

	public function test_single_row() {
		$_SERVER['QUERY_STRING']   = '';
		$hook                      = wp_parse_url( 'atum-stock-central' );
		$GLOBALS['hook_suffix']    = $hook['path'];

		$instance = new ListTable();
		$product  =  TestHelpers::create_atum_simple_product();

		$po = TestHelpers::create_order( $product );

		ob_start();
		$instance->single_row( $po );
		$result = ob_get_clean();
		$this->assertIsString( $result );
	}

	public function test_single_expandable_row() {
		$this->expectNotToPerformAssertions();
	}

}
