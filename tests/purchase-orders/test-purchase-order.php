<?php
/**
 * Class PurchaseOrderTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\PurchaseOrders\Models\PurchaseOrder;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class PurchaseOrderTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	private $product;
	private $order;
	private $po;

	public function setUp() {
		parent::setUp();

		$this->product = TestHelpers::create_atum_product();
		$this->order = TestHelpers::create_atum_purchase_order( $this->product );
		$this->po = new PurchaseOrder( $this->order->get_id() );
	}

	public function test_instance() {
		$this->assertInstanceOf( PurchaseOrder::class, $this->po );

		if ( version_compare( wc()->version, '3.5.0', '<' ) ) {
			$this->assertEquals( 10, TestHelpers::has_action( 'atum/atum_order/item_bulk_controls', array( PurchaseOrder::class, 'add_stock_button' ) ) );
		}
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/atum_order/item_meta_controls', array( PurchaseOrder::class, 'set_purchase_price_button' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/atum_order/before_product_search_modal', array( PurchaseOrder::class, 'product_search_message' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/order/add_product/price', array( PurchaseOrder::class, 'use_purchase_price' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/orders/status_atum_received', array( PurchaseOrder::class, 'maybe_increase_stock_levels' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/orders/status_changed', array( PurchaseOrder::class, 'maybe_decrease_stock_levels' ) ) );
	}

	public function test_add_stock_button() {
		ob_start();
		$this->po->add_stock_button();
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter( 'button.bulk-increase-stock' )->count() );
	}

	public function test_set_purchase_price_button() {
		foreach( $this->order->get_items() as $item ) {
			$item->product_type = 'line_item';
			ob_start();
			$this->po->set_purchase_price_button( $item );
			$data = ob_get_clean();
			$html = new Crawler( $data );
			$this->assertEquals( 1, $html->filter( 'button.set-purchase-price' )->count() );
		}
	}

	public function test_product_search_message() {
		$supplier         = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		update_post_meta( $this->order->get_id(), '_supplier', $supplier->ID );
		ob_start();
		$this->po->product_search_message( $this->order );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter( 'em.alert' )->count() );
	}

	public function test_get_title() {
		$data = $this->po->get_title();
		$this->assertEquals( 'PO &ndash; Jan 01, 1970 @ 12:00 AM', $data );
	}

	public function test_get_supplier() {
		$supplier         = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		update_post_meta( $this->po->get_id(), '_supplier', $supplier->ID );
		$data = $this->po->get_supplier();
		$this->assertInstanceOf( WP_Post::class, $data);
		$this->assertEquals( 'foo-supplier', $data->post_name );
		$this->assertEquals( 'atum_supplier', $data->post_type );
	}

	public function test_has_multiple_suppliers() {
		$this->assertIsBool( $this->po->has_multiple_suppliers() );
	}

	public function test_get_type() {
		$this->assertEquals( ATUM_PREFIX . 'purchase_order', $this->po->get_type() );
	}

	public function test_get_atum_order_item() {
		foreach( $this->order->get_items() as $item ) {
			$item->set_id(1111);
			$data = $this->order->get_atum_order_item( $item );
			$this->assertIsObject( $data );
			$this->assertInstanceOf( \Atum\PurchaseOrders\Items\POItemProduct::class, $data );
		}
	}

	public function test_get_expected_at_location_date() {
		update_post_meta( $this->po->get_id(), '_expected_at_location_date', 'foo' );
		$data = $this->po->get_expected_at_location_date();
		$this->assertEquals( 'foo', $data );
	}

	public function test_use_purchase_price() {
		$qty = 5;
		$this->product->set_purchase_price(10);
		$this->assertEquals( 50, $this->po->use_purchase_price( 0, $qty, $this->product ) );
	}

	/**
	 * @param $old_status
	 * @param $new_status
	 *
	 * @dataProvider provideStatus
	 */
	public function test_maybe_decrease_stock_levels( $old_status, $new_status ) {
		$this->product->set_stock_quantity( 10 );
		$start = $this->product->get_stock_quantity();
		$this->po->maybe_decrease_stock_levels( $this->order->get_id(), $old_status, $new_status, $this->order );
		$end = $this->product->get_stock_quantity();
		if( 'atum_received' === $old_status && 'atum_received' !== $new_status )
			$this->assertGreaterThan( $start, $end );
		else
			$this->assertEquals( $start, $end );
	}

	public function DISABLEDtest_maybe_increase_stock_levels() {
		$this->product->set_manage_stock( TRUE );
		$this->po->maybe_increase_stock_levels( $this->order->get_id(), $this->order );
		foreach( $this->order->get_items() as $item ) {
			//print_r($item);
			//print_r( $item->get_product() );
			$this->assertTrue( $item->get_meta( '_stock_changed' ) );
		}
	}

	public function DISABLEDtest_after_save() {
		$start = $this->product->get_inbound_stock();
		$this->po->change_stock_levels( $this->order, 'decrease' );
		$this->po->after_save( $this->order );
		$end = $this->product->get_inbound_stock();
		$this->assertNotEquals( $start, $end );
	}

	public function provideStatus() {
		return [
			[ ['atum_received'], ['atum_pending'] ],
			[ ['atum_received'], ['atum_pending'] ],
		];
	}

}