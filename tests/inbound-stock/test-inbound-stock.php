<?php
/**
 * Class InboundStockTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\InboundStock\InboundStock;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\Suppliers\Suppliers;
use Atum\Inc\Helpers;
use Symfony\Component\DomCrawler\Crawler;
use WP_Mock;


/**
 * Sample test case.
 */
class InboundStockTest extends WP_UnitTestCase {

	public function test_get_instance() {
		$this->assertInstanceOf( InboundStock::class, InboundStock::get_instance() );
	}

	public function test_add_menu() {
		$instance = InboundStock::get_instance();
		$menus    = $instance->add_menu( [ 'others' => 'foo' ] );

		$this->assertIsArray( $menus );
		$this->assertArrayHasKey( 'inbound-stock', $menus );
		$this->assertArrayHasKey( 'slug', $menus['inbound-stock'] );
	}

	public function test_display() {
		$instance = InboundStock::get_instance();

		$post_product = new StdClass();
		$post_product->ID = 1;
		$post_product = get_post( $post_product );
		$post_product->post_title   = 'Product 1';
		$post_product->post_content = 'Generic description for number 1 product.';
		$post_product->post_status  = 'published';
		$post_product->post_type    = 'product';

		$post_po = new StdClass();
		$post_po->ID = 2;
		$post_po = get_post( $post_po );
		$post_po->post_title   = 'PO for product';
		$post_po->post_content = 'Generic description for PO.';
		$post_po->post_status  = 'atum_ordered';
		$post_po->post_type    = ATUM_PREFIX . 'purchase_order';

		$po = new PurchaseOrder( $post_po->ID );
		$product = wc_get_product( $post_product );
		//echo "\n========== Product ==========";
		//print_r($product);
		//$po->add_product( $product );
		$po->save_meta( array(
			'_status'                    => 'atum_ordered',
			'_date_created'              => date( 'Y-m-d H:i:s', current_time( 'timestamp', TRUE ) ),
			Suppliers::SUPPLIER_META_KEY => '',
			'_multiple_suppliers'        => 'no',
			'_expected_at_location_date' => date( 'Y-m-d H:i:s', current_time( 'timestamp', TRUE ) ),
		) );
		//echo "\n========== Purchase Order ==========";
		//print_r($po);

		$reproduct = Helpers::get_atum_product( $post_product->ID );
		//$reproduct->set_inbound_stock( 16 );
		//echo "\n========== Reproduct ==========";
		//print_r($reproduct);




		ob_start();
		$instance->display();
		$result = ob_get_clean();

		//echo $result;


		$this->assertFalse(true);
	}

	public function importCSVProducts() {
		$path = dirname( dirname ( dirname( dirname( __FILE__ ) ) ) );
		require_once( $path . '/woocommerce/includes/import/class-wc-product-csv-importer.php' );
		require_once( $path . '/woocommerce/includes/admin/importers/class-wc-product-csv-importer-controller.php' );

		if ( ! is_file( $file = $path . '/woocommerce/sample-data/sample_products.csv' ) )
			$file = null;

		$params = array(
			'delimiter'       => ',', // PHPCS: input var ok.
			'start_pos'       => 0, // PHPCS: input var ok.
			'mapping'         => array(), // PHPCS: input var ok.
			'update_existing' => false, // PHPCS: input var ok.
			'lines'           => apply_filters( 'woocommerce_product_import_batch_size', 30 ),
			'parse'           => true,
		);
		$importer = WC_Product_CSV_Importer_Controller::get_importer( $file, $params );
		return $importer->import();
	}

}
