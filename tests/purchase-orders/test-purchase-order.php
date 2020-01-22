<?php
/**
 * Class PurchaseOrderTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\InventoryLogs\Models\Log;
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

		$this->product = TestHelpers::create_atum_simple_product();
		$this->order = TestHelpers::create_atum_purchase_order( $this->product );
		$this->po = new PurchaseOrder( $this->order->get_id() );
	}

	public function test_methods() {
		$data = TestHelpers::count_public_methods( PurchaseOrder::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
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

	public function test_set_supplier() {
		//Tested in next method
		$this->expectNotToPerformAssertions();
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

		$supplier2        = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo2 supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		update_post_meta( $this->po->get_id(), '_supplier', $supplier2->ID );
		$this->po->set_supplier( $supplier2->ID );
		$data = $this->po->get_supplier();
		$this->assertEquals( 'foo2-supplier', $data->post_name );
	}

	public function test_set_multiple_suppliers() {
		//Tested in next method
		$this->expectNotToPerformAssertions();
	}

	public function test_has_multiple_suppliers() {
		$this->assertIsBool( $this->po->has_multiple_suppliers() );
		$this->po->set_multiple_suppliers( TRUE );
		$this->assertTrue( $this->po->has_multiple_suppliers() );
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

	public function test_set_date_expected() {
		try {
			$date = date( 'Y-m-d' );
			$this->po->set_date_expected( $date );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();	}

	public function test_get_date_expected() {
		update_post_meta( $this->po->get_id(), '_expected_at_location_date', 'foo' );
		$data = $this->po->get_date_expected();
		$this->assertEquals( 'foo', $data );
	}

	public function test_use_purchase_price() {
		$qty = 5;
		$this->product->set_purchase_price(10);
		$this->assertEquals( 50, $this->po->use_purchase_price( 0, $qty, $this->product ) );
	}

	public function test_change_stock_levels() {
		try {
			$this->po->change_stock_levels( $this->po, 'increase' );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
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

	public function test_maybe_increase_stock_levels() {
		$this->product->set_manage_stock( TRUE );
		try {
			$this->po->maybe_increase_stock_levels( $this->order->get_id(), $this->po );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_after_save() {
		$start = $this->product->get_inbound_stock();
		$this->po->change_stock_levels( $this->order, 'decrease' );
		$this->po->after_save( $this->order );

		$product = \Atum\Inc\Helpers::get_atum_product( $this->product->get_id() );
		$end = $product->get_inbound_stock();
		$this->assertEquals( $start, $end );
	}

	public function test_get_data() {
		$data = $this->po->get_data();
		$this->assertIsArray( $data );
		$this->assertEquals( $this->po->get_id(), $data['id'] );
		$this->assertArrayHasKey( 'currency', $data );
		$this->assertArrayHasKey( 'supplier', $data );
		$this->assertArrayHasKey( 'multiple_suppliers', $data );
		$this->assertArrayHasKey( 'date_expected', $data );
	}

	public function provideStatus() {
		return [
			[ ['atum_received'], ['atum_pending'] ],
			[ ['atum_pending'], ['atum_received'] ],
		];
	}

}