<?php
/**
 * Class ProductDataMetaboxesTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Inc\Globals;
use Atum\MetaBoxes\ProductDataMetaBoxes;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class ProductDataMetaboxesTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_instance() {
		$this->assertInstanceOf( ProductDataMetaBoxes::class, ProductDataMetaBoxes::get_instance() );
	}

	public function test_purchase_price_hooks() {
		$obj = ProductDataMetaBoxes::get_instance();
		$obj->purchase_price_hooks();
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_product_options_pricing', array( ProductDataMetaBoxes::class, 'add_purchase_price_field' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_variation_options_pricing', array( ProductDataMetaBoxes::class, 'add_purchase_price_field' ) ) );
	}

	public function test_add_product_data_tab() {
		$obj = ProductDataMetaBoxes::get_instance();
		$data = $obj->add_product_data_tab( [] );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'atum', $data );
		$this->assertArrayHasKey( 'label', $data['atum'] );
		$this->assertEquals( 'ATUM Inventory', $data['atum']['label'] );
	}

	public function test_add_product_data_tab_panel() {
		include dirname( dirname( dirname( __FILE__ ) ) ) . '/../woocommerce/includes/admin/wc-meta-box-functions.php';
		global $post;
		$product = TestHelpers::create_atum_simple_product();
		$post = get_post( $product->get_id() );
		$obj = ProductDataMetaBoxes::get_instance();
		ob_start();
		$obj->add_product_data_tab_panel();
		$data = ob_get_clean();

		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('div#atum_product_data')->count() );
	}

	public function test_add_product_variation_data_panel() {
		$obj = ProductDataMetaBoxes::get_instance();
		$parent = TestHelpers::create_variation_product();
		$children = $parent->get_children();

		ob_start();
		$obj->add_product_variation_data_panel( 0, $children, get_post( $children[0] ) );
		$data = ob_get_clean();

		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('div.atum-data-panel')->count() );
	}

	public function test_add_out_stock_threshold_field() {
		$obj = ProductDataMetaBoxes::get_instance();
		$parent = TestHelpers::create_variation_product();
		$children = $parent->get_children();

		ob_start();
		$obj->add_out_stock_threshold_field( 0, $children, get_post( $children[0] ) );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('input#_out_stock_threshold0')->count() );
	}

	public function test_add_purchase_price_field() {
		wp_set_current_user( 1 );
		global $post;
		$obj = ProductDataMetaBoxes::get_instance();
		$parent = TestHelpers::create_variation_product();
		$children = $parent->get_children();
		$post = get_post( $children[0] );
		ob_start();
		$obj->add_purchase_price_field( 0, $children, get_post( $children[0] ) );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('input#variation_purchase_price0')->count() );
	}

	public function test_add_product_supplier_fields() {
		global $post;
		$obj = ProductDataMetaBoxes::get_instance();
		$parent = TestHelpers::create_variation_product();
		$children = $parent->get_children();
		$post = get_post( $children[0] );
		ob_start();
		$obj->add_product_supplier_fields( 0, $children, get_post( $children[0] ) );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('input#_supplier0')->count() );
		$this->assertEquals( 1, $html->filter('input#_supplier_sku0')->count() );
	}

	public function test_save_product_meta_boxes() {
		$obj = ProductDataMetaBoxes::get_instance();
		$product = TestHelpers::create_atum_simple_product();
		$obj->save_product_meta_boxes( $product->get_id(), get_post( $product->get_id() ) );
		$this->assertTrue( true );
	}

	public function test_save_product_variation_meta_boxes() {
		$obj = ProductDataMetaBoxes::get_instance();
		$product = TestHelpers::create_variation_product( true );
		$obj->save_product_variation_meta_boxes( $product->get_id(), get_post( $product->get_id() ) );
		$this->assertTrue( true );
	}

	public function test_save_atum_meta_boxes() {
		$obj = ProductDataMetaBoxes::get_instance();
		$product = TestHelpers::create_variation_product( true );
		$obj->save_atum_meta_boxes();
		$this->assertTrue( true );
	}
}