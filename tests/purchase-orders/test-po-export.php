<?php
/**
 * Class POExportTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\PurchaseOrders\Exports\POExport;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class POExportTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	private $po;

	public function setUp() {
		$this->po = TestHelpers::create_atum_purchase_order();
		parent::setUp();
	}

	public function test_instance() {
		$obj = new POExport( $this->po->get_id() );
		$this->assertInstanceOf( POExport::class, $obj );
	}

	public function DISABLEDtest_get_content() {
		$obj = new POExport( $this->po->get_id() );

		try {
			ob_start();
			$obj->get_content();
		} catch( Exception $e ) {
			//var_dump($e->getMessage());
			unset( $e );
		}
		$data = ob_get_clean();
		var_dump($data);
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('???')->count() );
	}

	public function test_get_company_address() {
		global $atum_global_options;
		$atum_global_options = [ 'company_name' => 'Foo company', 'address_1' => '13th Foo Street' ];
		$obj  = new POExport( $this->po->get_id() );
		$data = $obj->get_company_address();
		$this->assertIsString( $data );
		$this->assertContains( 'Foo company', $data );
	}

	public function test_get_supplier_address() {
		$supplier         = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		update_post_meta( $this->po->get_id(), '_supplier', $supplier->ID );
		$obj  = new POExport( $this->po->get_id() );
		$data = $obj->get_supplier_address();

		$this->assertIsString( $data );
		$this->assertContains( 'Foo supplier', $data );
	}

	public function test_get_shipping_address() {
		global $atum_global_options;
		$atum_global_options = [ 'ship_to' => 'Foo company', 'ship_address_1' => '13th Foo Street' ];
		$obj  = new POExport( $this->po->get_id() );
		$data = $obj->get_shipping_address();
		$this->assertIsString( $data );
		$this->assertContains( 'Foo company', $data );
	}

	public function test_get_stylesheets() {
		$obj  = new POExport( $this->po->get_id() );
		$data = $obj->get_stylesheets();
		$this->assertIsArray( $data );
		$this->assertContains( 'atum-po-export.css', $data[0] );
	}

}