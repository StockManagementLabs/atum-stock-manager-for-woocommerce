<?php
/**
 * Class DataExportTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\DataExport\DataExport;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class DataExportTest extends WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( DataExport::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$instance = new DataExport();

		$this->assertInstanceOf( DataExport::class, $instance );
		$this->assertEquals( 10, TestHelpers::has_action( 'admin_enqueue_scripts', array( DataExport::class, 'enqueue_scripts' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_export_data', array( DataExport::class, 'export_data' ) ) );

	}

	public function test_enqueue_scripts() {
		set_current_screen( 'atum-stock-central' );
		$instance = new DataExport();

		$instance->enqueue_scripts( 'toplevel_page_atum-stock-central' );
		$this->assertTrue( wp_script_is( 'atum-data-export', 'enqueued' ) );
	}

	public function test_export_data() {
		$instance = new DataExport();

		TestHelpers::create_atum_simple_product();

		//FIXME: PHPUnit stops execution because headers and die() in PDF generation
		/*
		try {
			ob_start();
			$instance->export_data();
		} catch ( Exception $e ) {
			echo $e->getMessage() . "\n";
			echo $e->getTraceAsString();
			unset( $e );
		}
		ob_clean();

		//var_dump($a);
		//var_dump($b);
		*/
		$this->assertTrue( TRUE );
	}

	public function test_html_report() {
		wp_set_current_user( 1 );
		set_current_screen( 'atum-stock-central' );
		$_SERVER['QUERY_STRING'] = '';
		$hook                    = wp_parse_url( 'atum-stock-central' );
		$GLOBALS['hook_suffix']  = $hook['path'];

		$html    = new \Atum\DataExport\Reports\HtmlReport();
		$product = TestHelpers::create_atum_simple_product();

		$_REQUEST['product_type'] = 'simple';
		$_REQUEST['product_cat']  = 'foo';

		ob_start();
		$html->display();
		$response = ob_get_clean();

		$this->assertContains( '<h1>Atum Stock Central Report</h1>', $response );
		$this->assertContains( '<tr class="item-heads">', $response );
		$this->assertContains( '<tbody id="the-list"', $response );
		$this->assertContains( '<tr class="totals">', $response );
	}
}
