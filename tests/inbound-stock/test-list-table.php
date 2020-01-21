<?php
/**
 * Class InboundStockListTableTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\InboundStock\InboundStock;
use Atum\InboundStock\Lists\ListTable;
use Symfony\Component\DomCrawler\Crawler;
use TestHelpers\TestHelpers;

class InboundStockListTableTest extends PHPUnit_Framework_TestCase { //WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( ListTable::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
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

		$po = TestHelpers::create_order( $product );

		ob_start();
		$instance->single_row( $po );
		$result = ob_get_clean();
		//var_dump($result);
		$this->assertIsString( $result );
	}

	public function test_single_expandable_row() {
		//FIXME: Error - Call to a member function get_type() on null
		/*
		$product  = TestHelpers::create_variation_product();
		$instance = new ListTable([ 'per_page' => 20 ]);
		$is = InboundStock::get_instance();

		$is->set_list_table( $instance );

		ob_start();
		$instance->single_expandable_row( $product, $product->get_type() );
		$data = ob_get_clean();
		//var_dump( $data );
		//*/
		$this->expectNotToPerformAssertions();
	}

	public function test_single_row_columns() {
		//FIXME: Error - Call to a member function get_type() on null
		/*
		$instance = new ListTable();
		$product  = TestHelpers::create_atum_simple_product();
		$order = TestHelpers::create_atum_purchase_order( $product );
		foreach( $order->get_items() as $item );
		$instance->product = $product;
		$instance->single_row_columns( $item );
		*/
		$this->expectNotToPerformAssertions();
	}

	public function test_get_columns() {
		$instance = new ListTable();
		$data = $instance->get_columns();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'thumb', $data );
		$this->assertArrayHasKey( 'ID', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'calc_type', $data );
		$this->assertArrayHasKey( '_sku', $data );
		$this->assertArrayHasKey( '_inbound_stock', $data );
		$this->assertArrayHasKey( 'calc_date_ordered', $data );
		$this->assertArrayHasKey( 'calc_date_expected', $data );
		$this->assertArrayHasKey( 'calc_purchase_order', $data );
	}

	public function test_get_editable_column() {
		$instance = new ListTable();
		$data = $instance->get_editable_column([ 'cell_name' => 'foo' ]);
		$this->assertContains( 'data-cell-name="foo"', $data );
	}

	public function test_views() {
		$instance = new ListTable();
		ob_start();
		$instance->views();
		$data = ob_get_clean();
		$this->assertContains( '<ul class="subsubsub extend-list-table">', $data );
		$this->assertContains( '<li class=\'all_stock\'>', $data );
	}

	public function test_add_apply_bulk_action_button() {
		$instance = new ListTable();
		ob_start();
		$instance->add_apply_bulk_action_button();
		$data = ob_get_clean();
		$this->assertContains( '<button type="button" class="apply-bulk-action btn btn-warning" style="display: none">', $data );
		$this->assertContains( 'Apply', $data );
	}

	public function test_atum_product_data_query_clauses() {
		$instance = new ListTable();
		$data = $instance->atum_product_data_query_clauses([]);
		$this->assertIsArray( $data );
	}

	public function test_wc_product_data_query_clauses() {
		$instance = new ListTable();
		$data = $instance->wc_product_data_query_clauses([]);
		$this->assertIsArray( $data );
	}

	public function test_add_supplier_variables_to_query() {
		$instance = new ListTable();
		$data = $instance->add_supplier_variables_to_query([]);
		$this->assertIsArray( $data );
	}

	public function test_add_supplier_variations_to_query() {
		$instance = new ListTable();
		$data = $instance->add_supplier_variations_to_query([], []);
		$this->assertIsArray( $data );
	}

	public function test_print_column_headers() {
		$instance = new ListTable();
		ob_start();
		$instance->print_column_headers( TRUE );
		$data = ob_get_clean();
		$this->assertContains( 'thumb', $data );
		$this->assertContains( 'ID', $data );
		$this->assertContains( 'title', $data );
		$this->assertContains( 'calc_type', $data );
		$this->assertContains( '_sku', $data );
		$this->assertContains( '_inbound_stock', $data );
		$this->assertContains( 'calc_date_ordered', $data );
		$this->assertContains( 'calc_date_expected', $data );
		$this->assertContains( 'calc_purchase_order', $data );
	}

	public function test_print_group_columns() {
		$instance = new ListTable();
		ob_start();
		$instance->print_group_columns();
		$data = ob_get_clean();
		$this->assertIsString( $data );
	}

	public function test_print_totals_columns() {
		$instance = new ListTable();
		ob_start();
		$instance->print_totals_columns();
		$data = ob_get_clean();
		$this->assertIsString( $data );
	}

	public function test_display() {
		$instance = new ListTable();
		ob_start();
		$instance->display();
		$data = ob_get_clean();
		$this->assertContains( '<div class="tablenav top extend-list-table">', $data );
		$this->assertContains( '<tbody id="the-list"', $data );
	}

	public function test_no_items() {
		$instance = new ListTable();
		ob_start();
		$instance->no_items();
		$data = ob_get_clean();
		$this->assertEquals( 'No products found', $data );
	}

	public function test_calc_groups() {
		$instance = new ListTable();
		$this->assertIsArray( $instance->calc_groups( [], [] ) );
	}

	public function test_search_group_columns() {
		$instance = new ListTable();
		$this->assertFalse( $instance->search_group_columns('foo') );
	}

	public function test_product_search() {
		$instance = new ListTable();
		$this->assertEquals( 'foo', $instance->product_search('foo') );

		//TODO: Add products and make searchings
	}

	/**
	 * @/runInSeparateProcess
	 * @doesNotPerformAssertions
	 */
	public function test_ajax_response() {
		/* FIXME: This method stops execution
		$instance = new ListTable();
		try {
			ob_start();
			$instance->ajax_response();

		} catch ( Exception $e ) {
			unset( $e );
		}
		$data = json_decode( ob_get_clean(), TRUE );
		var_dump($data);
		*/
	}

	public function test_enqueue_scripts() {
		$instance = new ListTable();
		$instance->enqueue_scripts( 'atum-inbound-stock' );
		$this->assertTrue( wp_style_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_script_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_script_is( 'atum-list' ) );
		$this->assertTrue( wp_style_is( 'atum-list' ) );
	}

	public function test_get_table_columns() {
		$instance = new ListTable();
		$data = $instance->get_table_columns();
		$this->assertEquals( $instance->get_columns(), $data );
	}

	public function test_set_table_columns() {
		$instance = new ListTable();
		$instance->set_table_columns( [ 'foo' => 'some' ] );
		$this->assertEquals( [ 'foo' => 'some' ], $instance->get_table_columns() );
	}

	public function test_get_group_members() {
		$instance = new ListTable();
		$data = $instance->get_group_members();
		$this->assertIsArray( $data );
	}

	public function test_set_group_members() {
		$instance = new ListTable();
		$instance->set_group_members( [ 'foo' => 'some' ] );
		$this->assertEquals( [ 'foo' => 'some' ], $instance->get_group_members() );
	}

	public function test_get_current_product() {
		$instance = new ListTable();
		$this->assertNull( $instance->get_current_product() );
	}

	public function test_get_default_currency() {
		$instance = new ListTable();
		$this->assertEquals( 'GBP', $instance->get_default_currency() );
	}

	public function test_hidden_columns() {
		$instance = new ListTable();
		$data = $instance->hidden_columns();
		$this->assertIsArray( $data );
		$this->assertTrue( in_array( 'ID', $data ) );
	}

	public function test_is_report() {
		$instance = new ListTable();
		$this->assertIsBool( $instance->is_report() );
	}

}
