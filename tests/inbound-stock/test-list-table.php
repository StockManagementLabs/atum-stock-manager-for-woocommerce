<?php
/**
 * Class InboundStockListTableTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\InboundStock\InboundStock;
use Atum\InboundStock\Lists\ListTable;
use Atum\Inc\Helpers;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\Suppliers\Suppliers;
use Symfony\Component\DomCrawler\Crawler;
use TestHelpers\TestHelpers;


/**
 * Sample test case.
 */
class InboundStockListTableTest extends PHPUnit_Framework_TestCase { //WP_UnitTestCase {

	public function test_get_instance() {
		$hook = wp_parse_url( 'atum-inbound-stock' );
		$GLOBALS['hook_suffix'] = $hook['path'];

		$_SERVER['QUERY_STRING'] = false;

		set_current_screen();

		$instance = new ListTable();
		$this->assertInstanceOf( ListTable::class, $instance );
	}

	public function test_prepare_items() {
		global $wpdb;
		$wpdb->atum_order_itemmeta = $wpdb->prefix . ATUM_PREFIX . 'order_itemmeta';

		$instance = new ListTable();
		$instance->prepare_items();

		//$instance->no_items();

		$columns = $instance->get_columns();
		$this->assertIsArray( $columns );
		$this->assertArrayHasKey( 'thumb', $columns );
		$this->assertArrayHasKey( 'ID', $columns );
		$this->assertArrayHasKey( 'title', $columns );
		$this->assertArrayHasKey( 'calc_type', $columns );
		$this->assertArrayHasKey( '_sku', $columns );

		ob_start();
		$instance->display();
		$response = ob_get_clean();

		$html = new Crawler( $response );
		$this->assertGreaterThan( 0, $html->filter( 'div.tablenav-pages-container' )->count() );
		$this->assertGreaterThan( 0, $html->filter( 'table.inbound-stock-list' )->count() );
	}

	public function test_single_row() {
		$instance = new ListTable();
		$product  =  TestHelpers::create_atum_simple_product();

		$po = TestHelpers::create_atum_purchase_order( $product );

		ob_start();
		$instance->single_row( $po );
		$result = ob_get_clean();

		$this->assertIsString( $result );
	}

}
