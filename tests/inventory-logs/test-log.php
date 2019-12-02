<?php
/**
 * Class LogTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\InventoryLogs\InventoryLogs;
use Atum\InventoryLogs\Models\Log;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class LogTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_instance() {
		$obj = new Log();
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/atum_order/item_bulk_controls', array( Log::class, 'add_stock_buttons' ) ) );
	}

	public function test_add_stock_buttons() {
		$obj = new Log();
		ob_start();
		$obj->add_stock_buttons();
		$data = ob_get_clean();

		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('button.bulk-increase-stock')->count() );
		$this->assertEquals( 1, $html->filter('button.bulk-decrease-stock')->count() );
	}

	public function test_get_title() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( 'Foo post', $obj->get_title() );
	}

	public function test_get_order() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertFalse( $obj->get_order() );
	}

	public function test_get_log_types() {
		$data = Log::get_log_types();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'reserved-stock', $data );
		$this->assertArrayHasKey( 'customer-returns', $data );
		$this->assertArrayHasKey( 'warehouse-damage', $data );
		$this->assertArrayHasKey( 'lost-in-post', $data );
		$this->assertArrayHasKey( 'other', $data );
	}

	public function test_get_log_type_columns() {
		$data = Log::get_log_type_columns();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'reserved-stock', $data );
		$this->assertArrayHasKey( 'customer-returns', $data );
		$this->assertArrayHasKey( 'warehouse-damage', $data );
		$this->assertArrayHasKey( 'lost-in-post', $data );
		$this->assertArrayHasKey( 'other', $data );
	}

	public function test_get_log_type() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( 'other', $obj->get_log_type() );
	}

	public function test_get_type() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( InventoryLogs::POST_TYPE, $obj->get_type() );
	}

	public function test_get_reservation_date() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( 'foo date', $obj->get_reservation_date() );
	}

	public function test_get_damage_date() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( 'foo date', $obj->get_damage_date() );
	}

	public function test_get_return_date() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( 'foo date', $obj->get_return_date() );
	}

	public function test_get_custom_name() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( 'Foo custom name', $obj->get_custom_name() );
	}

	public function test_get_shipping_company() {
		global $post;
		$obj = new Log( $post->ID );
		$this->assertEquals( 'Foo company', $obj->get_shipping_company() );
	}

	public function DISABLEDtest_get_atum_order_item() {
		global $post;
		$obj = new Log( $post->ID );
		$product = TestHelpers::create_atum_simple_product();
		$item = new WC_Order_Item_Product();
		$item->set_props( [
			'product'  => $product,
			'quantity' => 4,
			'subtotal' => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
			'total'    => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
		] );
		$item->save();
		$obj->add_item( $item );

		$this->assertIsObject( $obj->get_atum_order_item( $item ) );
	}

	public function test_after_save() {
		global $post;
		$obj = new Log( $post->ID );
		$obj->after_save( $obj );
		$this->assertTrue( true );
	}

	public function setUp() {
		parent::setUp();

		wp_set_current_user( 1 );
		global $post;
		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo post',
			'post_type'   => InventoryLogs::POST_TYPE,
			'post_status' => ATUM_PREFIX . 'pending',
			'log_type'    => 'other',
		] );

		$metas = [
			'_type'             => 'other',
			'_reservation_date' => 'foo date',
			'_damage_date'      => 'foo date',
			'_return_date'      => 'foo date',
			'atum_order_type'   => InventoryLogs::POST_TYPE,
			'status'            => ATUM_PREFIX . 'pending',
			'atum_meta_nonce'   => wp_create_nonce( 'atum_save_meta_data' ),
			'_order'            => 555,
			'_custom_name'      => 'Foo custom name',
			'description'       => 'Some description',
			'_shipping_company' => 'Foo company',
		];
		foreach( $metas as $k => $m )
			add_post_meta( $post->ID, $k, $m );
	}
}
