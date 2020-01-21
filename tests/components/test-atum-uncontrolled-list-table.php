<?php
/**
 * Class AtumUncontrolledListTableTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\StockCentral\Lists\ListTable;
use Atum\StockCentral\StockCentral;
use TestHelpers\TestHelpers;

class AtumUncontrolledListTableTest extends WP_UnitTestCase {

	public function test_prepare_items() {
		$_SERVER['QUERY_STRING']   = '';
		$hook                      = wp_parse_url( 'atum-stock-central' );
		$GLOBALS['hook_suffix']    = $hook['path'];

		$instance = new ListTable();
		TestHelpers::create_atum_simple_product();

		$instance->prepare_items();
		$this->expectNotToPerformAssertions();
	}

}
