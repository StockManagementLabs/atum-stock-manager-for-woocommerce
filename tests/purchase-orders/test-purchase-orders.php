<?php
/**
 * Class PurchaseOrdersTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\MetaBoxes\ProductDataMetaBoxes;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use TestHelpers\TestHelpers;
use Atum\PurchaseOrders\PurchaseOrders;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class PurchaseOrdersTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( PurchaseOrders::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$obj = new PurchaseOrders();
		$this->assertInstanceOf( PurchaseOrders::class, $obj );
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/admin/menu_items_order', array( PurchaseOrders::class, 'add_item_order') ));
		$this->assertEquals( 11, TestHelpers::has_action( 'atum/admin/top_bar/menu_items', array( PurchaseOrders::class, 'add_admin_bar_link') ));
		$this->assertEquals( 10, TestHelpers::has_action( 'load-edit.php', array( PurchaseOrders::class, 'add_help_tab') ));
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/' . PurchaseOrders::POST_TYPE . '/admin_order_actions', array( PurchaseOrders::class, 'add_generate_pdf') ));
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_pdf', array( PurchaseOrders::class, 'generate_order_pdf') ));
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/' . PurchaseOrders::POST_TYPE . '/search_results', array( PurchaseOrders::class, 'po_search') ));
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/' . PurchaseOrders::POST_TYPE . '/search_fields', array( PurchaseOrders::class, 'search_fields') ));
	}

	public function test_show_data_meta_box() {
		$obj = new PurchaseOrders();
		$order = TestHelpers::create_atum_purchase_order();

		ob_start();
		$obj->show_data_meta_box( get_post( $order->get_id() ) );
		$data = ob_get_clean();

		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('input#atum_order_is_editable')->count() );
		$this->assertEquals( 1, $html->filter('input#atum_order_has_multiple_suppliers')->count() );
	}

	public function test_save_meta_boxes() {
		$obj   = new PurchaseOrders();
		$order = TestHelpers::create_atum_purchase_order();
		$post  = get_post( $order->get_id() );
		wp_set_current_user( 1 );

		$_POST['atum_meta_nonce'] = wp_create_nonce( 'atum_save_meta_data' );
		$_POST['status']          = ATUM_PREFIX . 'pending';
		$_POST['description']     = 'Foo description';

		$obj->save_meta_boxes( $post->ID );

		$order = new PurchaseOrder( $post->ID );

		$this->assertEquals( $_POST['description'], $order->get_description() );
	}

	public function test_add_columns() {
		$obj  = new PurchaseOrders();
		$data = $obj->add_columns( [ 'cb' => 'foo' ] );
		$this->assertIsArray( $data );
		$this->assertEquals( 'PO', $data['atum_order_title'] );
	}

	/**
	 * @dataProvider provideColumns
	 */
	public function test_render_columns( $column ) {
		global $post;
		$obj   = new PurchaseOrders();
		$order = TestHelpers::create_atum_purchase_order();
		$post  = get_post( $order->get_id() );
		if( 'expected_date' === $column )
			update_post_meta( $post->ID, '_expected_at_location_date', current_time( 'timestamp', TRUE ) );

		ob_start();
		$obj->render_columns( $column );
		$data = ob_get_clean();

		$html = new Crawler( $data );

		switch( $column ) {
			case 'status':
				$this->assertEquals( 1, $html->filter('div.order-status-container')->count() );
				break;
			case 'atum_order_title':
				$this->assertEquals( 1, $html->filter('a.row-title')->count() );
				break;
			case 'date':
				$this->assertEquals( 1, $html->filter('time')->count() );
				break;
			case 'notes':
				$this->assertEquals( 1, $html->filter('span.note-on')->count() + $html->filter('span.na')->count() );
				break;
			case 'total':
				$this->assertEquals( 1, $html->filter('span.woocommerce-Price-amount')->count() );
				break;
			case 'actions':
				$this->assertEquals( 1, $html->filter('a.complete')->count() );
				$this->assertEquals( 1, $html->filter('a.pdf')->count() );
				break;
			case 'supplier':
				$this->assertEquals( '', $data );
				break;
			case 'expected_date':
				$this->assertEquals( 1, $html->filter('abbr')->count() );
				break;
		}
	}

	public function test_bulk_post_updated_messages() {
		$obj    = new PurchaseOrders();
		$counts = [
			'updated'   => 10,
			'locked'    => 10,
			'deleted'   => 10,
			'trashed'   => 10,
			'untrashed' => 10,
		];
		$data = $obj->bulk_post_updated_messages( [], $counts );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( PurchaseOrders::POST_TYPE, $data );
		foreach ( $counts as $c => $j )
			$this->assertArrayHasKey( $c, $data[ PurchaseOrders::POST_TYPE ] );
	}

	public function test_post_updated_messages() {
		global $post;
		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo post',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => ATUM_PREFIX . 'pending',
			'log_type'    => 'other',
		] );
		$obj = new PurchaseOrders();
		$data = $obj->post_updated_messages( [] );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( PurchaseOrders::POST_TYPE, $data );
		$this->assertEquals( 12, count($data[ PurchaseOrders::POST_TYPE ] ) );
		foreach ( $data[ PurchaseOrders::POST_TYPE ] as $k => $m ) {
			if( 5 === $k)
				$this->assertIsBool( $m );
			else
				$this->assertIsString( $m );
		}
	}

	public function test_add_admin_bar_link() {
		$obj = new PurchaseOrders();
		$data = $obj->add_admin_bar_link( [] );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'purchase-orders', $data );
		$this->assertArrayHasKey( 'slug', $data['purchase-orders'] );
		$this->assertArrayHasKey( 'title', $data['purchase-orders'] );
		$this->assertArrayHasKey( 'href', $data['purchase-orders'] );
		$this->assertArrayHasKey( 'menu_order', $data['purchase-orders'] );
	}

	public function test_add_item_order() {
		$obj = new PurchaseOrders();
		$data = $obj->add_item_order( [] );

		$this->assertIsArray( $data );
		$this->assertEquals( 2, count( $data[ 0 ] ) );
		$this->assertArrayHasKey( 'slug', $data[ 0 ] );
		$this->assertArrayHasKey( 'menu_order', $data[ 0 ] );
	}

	public function test_get_statuses() {
		$data = PurchaseOrders::get_statuses();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( ATUM_PREFIX . 'pending', $data );
		$this->assertArrayHasKey( ATUM_PREFIX . 'ordered', $data );
		$this->assertArrayHasKey( ATUM_PREFIX . 'onthewayin', $data );
		$this->assertArrayHasKey( ATUM_PREFIX . 'receiving', $data );
		$this->assertArrayHasKey( ATUM_PREFIX . 'received', $data );
	}

	public function test_add_help_tab() {
		//Tested in next method
		$this->expectNotToPerformAssertions();
	}

	public function test_help_tabs_content() {
		set_current_screen( 'atum-purchase-orders' );
		$obj = new PurchaseOrders();

		ob_start();
		$obj->add_help_tab();
		$obj->help_tabs_content( 'atum-purchase-orders', array(
			'name'  => 'columns',
			'title' => 'Columns') );
		$data = ob_get_clean();
		$html = new Crawler( $data );

		$this->assertEquals( 1, $html->filter('table.widefat.fixed.striped')->count() );
	}

	public function test_add_generate_pdf() {
		wp_set_current_user( 1 );
		$obj   = new PurchaseOrders();
		$order = TestHelpers::create_atum_purchase_order();
		$data  = $obj->add_generate_pdf( [], $order );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'pdf', $data );
		$this->assertContains( 'atum_order_id=' . $order->get_id(), $data['pdf']['url'] );
	}

	public function test_get_pdf_generation_link() {
		$data = PurchaseOrders::get_pdf_generation_link(7);
		$this->assertContains( 'atum_order_id=7', $data );
	}

	public function test_generate_order_pdf() {
		wp_set_current_user( 1 );

		$obj   = new PurchaseOrders();
		$order = TestHelpers::create_atum_purchase_order();

		$_REQUEST['_wp_http_referer'] = 'admin.php?page=atum-purchase-order';
		$_REQUEST['_wpnonce']     = wp_create_nonce( 'atum-order-pdf' );
		$_GET['atum_order_id'] = $order->get_id();
		update_option( 'siteurl', 'http://test.foo' );

		try {
			ob_start();
			$obj->generate_order_pdf();
		} catch( Exception $e ) {
			$data = $e->getMessage();
			$this->assertContains( 'Data has already been sent to output', $data);
			$this->assertContains( 'unable to output PDF file', $data);
			unset( $e );
		}
		$data = ob_get_clean();
		$this->assertEmpty( $data);
	}

	public function test_search_fields() {
		$obj  = new PurchaseOrders();
		$data = $obj->search_fields( [ 'foo' ] );
		$this->assertIsArray( $data );
		$this->assertEquals( 'foo', $data[0] );
		$this->assertEquals( '_total', $data[ count( $data ) - 1 ] );
	}

	public function test_po_search() {
		$obj = new PurchaseOrders();
		$names = [ 'Foo', 'Fee', 'Faa' ];
		$atum_order_ids = [];
		foreach( $names as $name ) {
			$post             = $this->factory()->post->create_and_get( [
				'post_title'  => $name.' post',
				'post_type'   => PurchaseOrders::POST_TYPE,
				'post_status' => ATUM_PREFIX . 'pending',
				'log_type'    => 'other',
			] );
			$supplier         = $this->factory()->post->create_and_get( [
				'post_title'  => $name.' supplier',
				'post_type'   => 'atum_supplier',
				'post_status' => 'published',
				'log_type'    => 'other',
			] );
			update_post_meta( $post->ID, '_supplier', $supplier->ID );
			//$atum_order_ids[] = $post->ID;
		}

		$data = $obj->po_search( $atum_order_ids, 'Foo', 'atum_order_title' );
		$this->assertIsArray($data);
		$this->assertEquals( 1, count( $data ) );

		$data = $obj->po_search( $atum_order_ids, 'supplier', 'atum_order_title' );
		$this->assertIsArray($data);
		$this->assertEquals( 3, count( $data ) );
	}


	public function provideColumns() {
		return [
			[ 'status' ],
			[ 'atum_order_title' ],
			[ 'date' ],
			[ 'notes' ],
			[ 'total' ],
			[ 'actions' ],
			[ 'supplier' ],
			[ 'expected_date' ],
		];
	}
}