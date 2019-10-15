<?php
/**
 * Class ListTableTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\StockCentral\Lists\ListTable;

/**
 * Sample test case.
 */
class ListTableTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_instance() {
		wp_set_current_user( 1 );
		set_current_screen( 'atum-stock-central' );
		$_SERVER['QUERY_STRING'] = '';
		$hook                    = wp_parse_url( 'atum-stock-central' );
		$GLOBALS['hook_suffix']  = $hook['path'];

		$obj = new ListTable();
		$this->assertInstanceOf( ListTable::class, $obj );
	}

	public function test_get_table_columns() {
		$data = ListTable::get_table_columns();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( '_regular_price', $data );
		$this->assertArrayHasKey( 'ID', $data );
		$this->assertArrayHasKey( '_stock', $data );
		$this->assertArrayHasKey( '_out_stock_threshold', $data );
		$this->assertArrayHasKey( '_inbound_stock', $data );
		$this->assertArrayHasKey( 'calc_back_orders', $data );
		$this->assertArrayHasKey( '_sales_last_days', $data );
		$this->assertArrayHasKey( '_lost_sales', $data );
	}

	public function test_prepare_items() {
		$obj = new ListTable();
		$obj->prepare_items();
		$this->assertTrue( TRUE );
	}

	/**
	 * @param $extra
	 *
	 * @dataProvider provideExtra
	 */
	public function test_do_extra_filter( $extra ) {
		global $wpdb;
		$obj = new ListTable();
		$query = new WP_Query();
		$wpdb->atum_order_itemmeta = $wpdb->prefix . ATUM_PREFIX . 'order_itemmeta';
		$_REQUEST['extra_filter'] = $extra;
		try {
			$obj->prepare_items( $query );
			$this->assertTrue( TRUE );
		} catch ( Exception $e ) {

		}
	}

	public function test_no_items() {
		$obj = new ListTable();
		ob_start();
		$obj->no_items();
		$data = ob_get_clean();
		$this->assertEquals( 'No products found', $data );
	}

	public function provideExtra() {
		return [
			[ 'best_seller' ],
			[ 'worst_seller' ],
			[ 'inbound_stock' ],
			[ 'stock_on_hold' ],
			[ 'reserved_stock' ],
			[ 'back_orders' ],
			[ 'sold_today' ],
			[ 'customer_returns' ],
			[ 'warehouse_damages' ],
			[ 'lost_in_post' ],
			[ 'other' ],
		];
	}

}