<?php
/**
 * Class DataExportTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\DataExport\DataExport;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class DataExportTest extends WP_UnitTestCase {

	public function test_get_instance() {
		$instance = new DataExport();

		$this->assertInstanceOf( DataExport::class, $instance );
	}

	/*
	public function test_enqueue_scripts() {
		$instance = new DataExport();

		$this->assertFalse( wp_script_is( 'atum-data-export', 'registered' ) );
		$instance->enqueue_scripts( 'toplevel_page_atum-stock-central' );
		$this->assertTrue( wp_script_is( 'atum-data-export', 'registered' ) );
	}*/

	/*
	public function test_export() {
		$instance = new DataExport();
		//Product needed
		$this->factory->post->create( array( 'post_title' => 'Foo', 'post_type' => 'product' ) );

		//ob_start();
		$a = $instance->export_data();
		//$b = ob_get_clean();

		var_dump($a);
		//var_dump($b);

		$this->assertTrue(false);
	}*/

	/*
	public function test_html_report() {
		$html = new \Atum\DataExport\Reports\HtmlReport();

		ob_start();
		$a = $html->display();
		$b = ob_get_clean();

		var_dump($a);
		var_dump($b);
	}*/
}
