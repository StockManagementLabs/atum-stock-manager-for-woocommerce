<?php
/**
 * Class AtumListPageTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\StockCentral\StockCentral;
use TestHelpers\TestHelpers;

class AtumListPageTest extends WP_UnitTestCase {
	//Tested in StockCentralTest class

	public function test_set_screen_option() {
		$obj = StockCentral::get_instance();
		$obj->set_screen_option( FALSE, 'foo-option', 'something' );
		$this->expectNotToPerformAssertions();
	}

	public function test_set_list_table() {
		$_SERVER['QUERY_STRING']   = '';
		$hook                      = wp_parse_url( 'atum-stock-central' );
		$GLOBALS['hook_suffix']    = $hook['path'];

		$obj = StockCentral::get_instance();
		$obj->set_list_table( new \Atum\StockCentral\Lists\ListTable( [] ) );
		$this->expectNotToPerformAssertions();
	}

}
