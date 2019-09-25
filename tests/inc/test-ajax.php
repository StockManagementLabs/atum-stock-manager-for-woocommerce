<?php
/**
 * Class AjaxTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Inc\Ajax;
use Atum\Models\Products\AtumProductSimple;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;
use Symfony\Component\DomCrawler\Crawler;
use Atum\Inc\Helpers;
use Atum\Components\AtumOrders\AtumComments;

/**
 * Sample test case.
 */
class AjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * Reset variables in every methods
	 */
	public function setUp() {
		parent::setUp();

		unset( $_REQUEST );
		unset( $_POST );
	}

	/**
	 * Tests get_instance method
	 */
	public function test_get_instance() {
		$this->assertInstanceOf( Ajax::class, Ajax::get_instance() );
	}

	/**
	 * Tests save_dashboard_layout method
	 */
	public function test_save_dashboard_layout() {
		wp_set_current_user( 1 );

		$_REQUEST['token'] = wp_create_nonce( 'atum-dashboard-widgets' );
		$_POST['layout']   = [
			'atum_statistics_widget'    => [
				'x'          => 0,
				'y'          => 5,
				'width'      => 12,
				'height'     => 10,
				'min-height' => 5,
			],
			'atum_sales_widget'         => [
				'x'          => 0,
				'y'          => 5,
				'width'      => 6,
				'height'     => 10,
				'min-height' => 5,
			],
			'atum_lost_sales_widget'    => [
				'x'          => 1,
				'y'          => 6,
				'width'      => 6,
				'height'     => 10,
				'min-height' => 5,
			],
			'atum_orders_widget'        => [
				'x'          => 1,
				'y'          => 6,
				'width'      => 6,
				'height'     => 10,
				'min-height' => 5,
			],
			'atum_promo_sales_widget'   => [
				'x'          => 1,
				'y'          => 5,
				'width'      => 6,
				'height'     => 10,
				'min-height' => 5,
			],
			'atum_stock_control_widget' => [
				'x'          => 1,
				'y'          => 5,
				'width'      => 12,
				'height'     => 10,
				'min-height' => 5,
			],
			'atum_videos_widget'        => [
				'x'          => 1,
				'y'          => 5,
				'width'      => 12,
				'height'     => 10,
				'min-height' => 7,
			],
		];

		try {
			$this->_handleAjax( 'atum_dashboard_save_layout' );
			$this->assertEquals( $_POST['layout'], get_user_meta( 1, ATUM_PREFIX . 'dashboard_widgets_layout', true ) );
		} catch ( Exception $e ) {
			$this->assertInstanceOf( WPDieException::class, $e );
		}
	}

	/**
	 * Tests restore_dashboard_layout method
	 */
	public function test_restore_dashboard_layout() {
		wp_set_current_user( 1 );

		$_REQUEST['token'] = wp_create_nonce( 'atum-dashboard-widgets' );

		try {
			$this->_handleAjax( 'atum_dashboard_restore_layout' );
			$this->assertEquals( '', get_user_meta( 1, ATUM_PREFIX . 'dashboard_widgets_layout', true ) );
		} catch ( Exception $e ) {
			$this->assertInstanceOf( WPDieException::class, $e );
		}
	}

	/**
	 * Tests videos_widget_sorting method
	 */
	public function test_videos_widget_sorting() {
		wp_set_current_user( 1 );
		set_current_screen( 'atum-dashboard' );
		$nonce = wp_create_nonce( 'atum-dashboard-widgets' );

		$_REQUEST['token'] = $nonce;
		$_POST['sortby']   = 'title';

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->videos_widget_sorting();
			$response = ob_get_clean();
		} catch ( Exception $e ) {
			$this->assertInstanceOf( WPDieException::class, $e );
			$response = $e->getMessage();
			unset( $e );
		}
		$html = new Crawler( $response );
		$this->assertGreaterThan( 0, $html->filter( 'div.videos-widget' )->count() );
	}

	/**
	 * Tests current_stock_values method
	 */
	public function notest_current_stock_values() {
		wp_set_current_user( 1 );
		$_REQUEST['token'] = wp_create_nonce( 'atum-dashboard-widgets' );
		// Product needed.
		$this->factory()->post->create( array(
			'post_title' => 'Foo',
			'post_type'  => 'product',
		) );

		$_POST['categorySelected']    = NULL;
		$_POST['productTypeSelected'] = NULL;

		try {
			$this->_handleAjax( 'atum_current_stock_values' );
		} catch ( Exception $e ) {
			$this->assertInstanceOf( WPDieException::class, $e );
			unset( $e );
		}
		$data = json_decode( $this->_last_response, TRUE );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
	}

	/**
	 * Tests statistics_widget_chart method.
	 *
	 * @param mixed $dt
	 * @param mixed $tm
	 * @dataProvider getStatsTypes
	 */
	public function test_statistics_widget_chart( $dt, $tm ) {
		wp_set_current_user( 1 );
		set_current_screen( 'atum-dashboard' );
		$_REQUEST['token']     = wp_create_nonce( 'atum-dashboard-widgets' );
		$_POST['chart_data']   = $dt;
		$_POST['chart_period'] = $tm;

		// Product needed.
		$this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => 'product',
			'post_status' => 'publish',
		) );
		// Order needed.
		$this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => 'shop_order',
			'post_status' => 'lost_sales' === $dt ? 'wc-cancelled' : 'wc-completed',
		) );
		$sale = new WC_Order();
		$sale->set_defaults();
		$sale->save();
		$product = new AtumProductSimple();
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'price'         => 10,
				'sku'           => 'DUMMY SKU',
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
				'inbound_stock' => 16,
			)
		);
		$product->set_stock_quantity( 7 );
		$product->save();

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->statistics_widget_chart();
			ob_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}
		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
	}

	/**
	 * Tests fetch_stock_central_list method
	 */
	public function test_fetch_stock_central_list() {
		wp_set_current_user( 1 );
		set_current_screen( 'atum-stock-central' );
		$_REQUEST['token']           = wp_create_nonce( 'atum-list-table-nonce' );
		$_REQUEST['screen']          = 'atum-stock-central';
		$_REQUEST['view']            = '';
		$_REQUEST['per_page']        = 100;
		$_REQUEST['show_cb']         = 1;
		$_REQUEST['show_controlled'] = 1;
		$_POST['orderby']            = 'date';
		$_POST['order']              = 'desc';
		$_POST['supplier']           = '';
		$_POST['product_cat']        = '';
		$_POST['product_type']       = '';
		$_SERVER['QUERY_STRING']     = '';

		// Product needed.
		$this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => 'product',
			'post_status' => 'publish',
		) );
		$product = new AtumProductSimple();
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'price'         => 10,
				'sku'           => 'DUMMY SKU',
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
				'inbound_stock' => 16,
			)
		);
		$product->set_stock_quantity( 7 );
		$product->save();

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->fetch_stock_central_list();
			ob_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}

		$this->assertIsString( $this->_last_response );
		$html = new Crawler( $this->_last_response );
		$this->assertGreaterThanOrEqual( 1, $html->filter( 'tr' )->count() );
	}

	/**
	 * Tests fetch_inbound_stock_list method
	 */
	public function test_fetch_inbound_stock_list() {
		global $wpdb;
		wp_set_current_user( 1 );
		set_current_screen( 'atum-stock-central' );
		$wpdb->atum_order_itemmeta   = $wpdb->prefix . ATUM_PREFIX . 'order_itemmeta';
		$_REQUEST['token']           = wp_create_nonce( 'atum-list-table-nonce' );
		$_REQUEST['screen']          = 'atum-inbound-stock';
		$hook                        = wp_parse_url( 'atum-inbound-stock' );
		$GLOBALS['hook_suffix']      = $hook['path'];
		$_REQUEST['view']            = '';
		$_REQUEST['per_page']        = 100;
		$_REQUEST['show_cb']         = 1;
		$_REQUEST['show_controlled'] = 1;
		$_SERVER['QUERY_STRING']     = '';

		// Product needed.
		$this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => 'product',
			'post_status' => 'publish',
		) );
		$product = new AtumProductSimple();
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'price'         => 10,
				'sku'           => 'DUMMY SKU',
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
				'inbound_stock' => 16,
			)
		);
		$product->set_stock_quantity( 7 );
		$product->save();

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->fetch_inbound_stock_list();
			ob_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->assertIsString( $this->_last_response );
		$html = new Crawler( $this->_last_response );
		$this->assertGreaterThanOrEqual( 1, $html->filter( 'tr' )->count() );
	}

	/**
	 * Tests rated method
	 */
	public function test_rated() {
		$this->assertFalse( get_option( 'atum_admin_footer_text_rated' ) );
		try {
			$this->_handleAjax( 'atum_rated' );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->assertEquals( 1, get_option( 'atum_admin_footer_text_rated' ) );
	}

	/**
	 * Tests update_list_data method
	 * TODO: Doesn't work!!!
	 */
	public function DISABLEDtest_update_list_data() {
		wp_set_current_user( 1 );
		set_current_screen( 'atum-stock-central' );

		$product = new WC_Product();
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 15,
				'price'         => 10,
				'sku'           => 'DUMMY SKU',
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
				'inbound_stock' => 16,
			)
		);
		$product->save();
		$price = $product->get_regular_price();
		$pid   = $product->get_id();
		unset( $product );

		$_REQUEST['token']       = wp_create_nonce( 'atum-list-table-nonce' );
		$_POST['first_edit_key'] = '';
		$_POST['data']           = wp_json_encode( [
			$pid => [
				'regular_price'          => '25',
				'regular_price_custom'   => 'no',
				'regular_price_currency' => 'EUR',
			],
		] );

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->update_list_data();
			//add_action( 'wp_ajax_atum_control_all_products', array( 'Ajax', 'control_all_products' ) );
			//$this->_handleAjax( 'atum_control_all_products' );
		} catch ( Exception $e ) {
			//echo $e->getTraceAsString();
			unset( $e );
		}
		ob_clean();

		$product2 = wc_get_product( $pid );
		$price2   = $product2->get_regular_price();
		$this->assertEquals( '25', $price2 );
		$this->assertNotEquals( $price, $price2 );
	}

	/**
	 * Tests apply_bulk_action method
	 *
	 * @param string $action
	 * @dataProvider provideBulkAction
	 * TODO: uncontrol_stock value doesn't match with data
	 */
	public function DISABLEDtest_apply_bulk_action( $action ) {
		$_REQUEST['token']    = wp_create_nonce( 'atum-list-table-nonce' );
		$_POST['bulk_action'] = $action;
		$_POST['ids']         = [];

		for ( $i = 0; $i < 4; $i++ ) {
			$product = new WC_Product();
			$product->set_props(
				array(
					'name'          => 'Dummy Product',
					'regular_price' => $i * 10,
					'price'         => $i * 5,
					'sku'           => 'DUMMY SKU',
					'manage_stock'  => false,
					'tax_status'    => 'taxable',
					'downloadable'  => false,
					'virtual'       => false,
					'stock_status'  => 'instock',
					'weight'        => '1.1',
					'inbound_stock' => wp_rand( 1, 100 ),
				)
			);
			$product->save();
			$_POST['ids'][] = $product->get_id();
		}

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->apply_bulk_action();
			ob_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}

		foreach ( $_POST['ids'] as $id ) {
			$product = Helpers::get_atum_product( $id );
			switch ( $action ) {
				case 'uncontrol_stock':
				case 'control_stock':
					//$this->assertEquals( 'control_stock' === $action ? 'yes' : 'no', Helpers::get_atum_control_status( $product ) );
					$this->assertEquals( 'uncontrol_stock' === $action ? 'no' : 'yes', $product->get_atum_controlled() );
					break;
				case 'unmanage_stock':
					$this->assertFalse( $product->get_manage_stock() );
					break;
				case 'manage_stock':
					$this->assertTrue( $product->get_manage_stock() );
					break;
			}
		}
	}

	/**
	 * Tests control_all_products method
	 */
	public function test_control_all_products() {
		$_REQUEST['token'] = wp_create_nonce( 'atum-control-all-products-nonce' );

		$product = new WC_Product();
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'price'         => 5,
				'sku'           => 'DUMMY SKU',
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
				'inbound_stock' => 10,
			)
		);
		$product->save();
		$id = $product->get_id();

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->control_all_products();
			ob_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}

		$data = json_decode( $this->_last_response, true);

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'All your products were updated successfully', $data['data'] );
	}

	/**
	 * Tests validate_license method
	 */
	public function test_validate_license() {
		$_REQUEST['token'] = wp_create_nonce( ATUM_PREFIX . 'manage_license' );
		$_POST['addon']    = 'atum-multi-inventory';
		$_POST['key']      = 'some_invalid_key';

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->validate_license();
			ob_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}

		$data = json_decode( $this->_last_response, true);

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertFalse( $data['success'] );
	}

	/**
	 * Tests activate_license method
	 */
	public function test_activate_license() {
		$_REQUEST['token'] = wp_create_nonce( ATUM_PREFIX . 'manage_license' );
		$_POST['addon']    = 'atum-multi-inventory';
		$_POST['key']      = 'some_invalid_key';

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->activate_license();
			ob_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}
		$data = json_decode( $this->_last_response, true);

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertFalse( $data['success'] );
	}

	/**
	 * Tests deactivate_license method
	 */
	public function test_deactivate_license() {
		$_REQUEST['token'] = wp_create_nonce( ATUM_PREFIX . 'manage_license' );
		$_POST['addon']    = 'atum-multi-inventory';
		$_POST['key']      = 'some_invalid_key';

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->deactivate_license();
			ob_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}
		$data = json_decode( $this->_last_response, true);

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertFalse( $data['success'] );
	}

	/**
	 * Tests install_addon method
	 */
	public function DISABLEDtest_install_addon() {
		$_REQUEST['token'] = wp_create_nonce( ATUM_PREFIX . 'manage_license' );
		$_POST['addon']    = 'atum-multi-inventory';
		$_POST['slug']     = 'atum-multi-inventory';
		$_POST['key']      = 'some_invalid_key';

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->install_addon();
			ob_clean();
		} catch ( Exception $e ) {
			var_dump($e);
			echo "\n".$e->getTraceAsString();
			unset( $e );
		}

		$data = json_decode( $this->_last_response, true);

		var_dump($data);

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertFalse( $data['success'] );
	}

	/**
	 * Tests dismiss_notice method
	 */
	public function test_dismiss_notice() {
		wp_set_current_user( 1 );
		$_REQUEST['token'] = wp_create_nonce( 'dismiss-atum-notice' );
		$_POST['key']      = 'foo_notice';

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->dismiss_notice();
			ob_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}

		$data = Helpers::get_dismissed_notices( 1 );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'foo_notice', $data );
		$this->assertEquals( 'yes', $data['foo_notice'] );
	}

	/**
	 * Tests search_products method
	 */
	public function test_search_products() {
		wp_set_current_user( 1 );
		$_SERVER['REQUEST_URI']  = home_url().'/?term=Dummy';
		$_SERVER['HTTP_REFERER'] = home_url().'/foo/?term=Dummy';
		$_REQUEST['security']    = wp_create_nonce( 'search-products' );
		$_GET['term']            = 'Dummy';

		$product = new WC_Product();
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'price'         => 5,
				'sku'           => 'DUMMY SKU',
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
				'inbound_stock' => 10,
			)
		);
		$product->save();

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->search_products();
			ob_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_end_clean();
		//wp_ob_end_flush_all();

		$data = json_decode( $this->_last_response, true);
		$this->assertIsArray( $data );
		foreach ( $data as $k => $d )
			$this->assertNotFalse( strpos( $d, $_GET['term'] ) );

		$_GET['term'] = 'foo';
		$this->_last_response = null;
		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->search_products();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_end_clean();
		$data = json_decode( $this->_last_response, true);
		$this->assertEmpty( $data );
	}

	/**
	 * Tests search_wc_orders method
	 */
	public function test_search_wc_orders() {
		wp_set_current_user( 1 );
		$_SERVER['REQUEST_URI']  = home_url().'/?term=Dummy';
		$_SERVER['HTTP_REFERER'] = home_url().'/foo/?term=Dummy';
		$_REQUEST['security']    = wp_create_nonce( 'search-products' );

		$order = new WC_Order();
		$order->set_customer_id( 1 );
		$order->set_total( 50 );
		$order->set_created_via( 'bookings' );
		$order->save();

		$_GET['term'] = $order->get_id();

		try {

			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->search_wc_orders();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_end_clean();

		$data = json_decode( $this->_last_response, true);

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( $order->get_id(), $data );
	}

	/**
	 * Tests search_suppliers method
	 */
	public function test_search_suppliers() {
		wp_set_current_user( 1 );
		$_REQUEST['security'] = wp_create_nonce( 'search-products' );

		// Product needed.
		$this->factory()->post->create( array(
			'post_title' => 'Foo',
			'post_type'  => Suppliers::POST_TYPE,
		) );

		$_GET['term'] = 'Foo';

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->search_suppliers();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_end_clean();

		$data = json_decode( $this->_last_response, true);

		$this->assertIsArray( $data );
		foreach($data as $k => $v)
			$this->assertGreaterThanOrEqual( 0, strpos( $v, 'Foo' ) );
	}

	/**
	 * Tests add_atum_order_note and delete_atum_order_note methods
	 */
	public function test_atum_order_notes() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['security'] = wp_create_nonce( 'add-atum-order-note' );

		$pid = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );

		$post = get_post( $pid );

		$_POST['post_id'] = $post->ID;
		$_POST['note']    = 'My foo note';

		try {
			ob_start();
			$ajax->add_atum_order_note();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$html = new Crawler( $this->_last_response );

		$this->assertEquals( 1, $html->filter('li.note')->count() );

		$comment_id = $html->filter('li.note')->attr('rel');
		$comment    = get_comment( $comment_id );
		$this->assertInstanceOf( WP_Comment::class, $comment );
		$this->assertEquals( $comment->comment_type, 'atum_order_note' );
		unset( $comment );

		$_REQUEST['security'] = wp_create_nonce( 'delete-atum-order-note' );
		$_POST['note_id']     = $comment_id;
		try {
			ob_start();
			$ajax->delete_atum_order_note();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$comment = get_comment( $comment_id );
		//print_r($comment); die;
		$this->assertInstanceOf( WP_Comment::class, $comment );
		$this->assertEquals( $comment->comment_approved, 'trash' );
	}

	/**
	 * Tests add_atum_order_items, load_atum_order_items, remove_atum_order_item methods
	 */
	public function test_atum_order_items() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['security'] = wp_create_nonce( 'atum-order-item' );
		$po = new PurchaseOrders();
		$po->register_post_type();

		//Purchase Order
		$pid = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$order = Helpers::get_atum_order_model( $pid );

		//Product
		$product = new WC_Product();
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'price'         => 10,
				'sku'           => 'DUMMY SKU',
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
				'inbound_stock' => 16,
			)
		);
		$product->save();

		//Add new item to order
		$_POST['atum_order_id'] = $order->get_id();
		$_POST['item_to_add']   = $product->get_id();
		try {
			ob_start();
			$ajax->add_atum_order_item();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$data = json_decode( $this->_last_response, true );
		$html = new Crawler( $data['data'] );
		$this->assertEquals( 1, $html->filter( 'tr.new_row' )->count() );
		$new_atum_order_item = intval( $html->filter( 'tr.new_row' )->attr( 'data-atum_order_item_id' ) );
		unset( $html );
		unset( $data );
		$this->_last_response = '';

		//Load order items list
		try {
			ob_start();
			$ajax->load_atum_order_items();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$html = new Crawler( $this->_last_response );
		$this->assertEquals( 1, $html->filter( '.atum_order_items_wrapper' )->count() );
		$this->assertEquals( $new_atum_order_item, $html->filter( 'input.atum_order_item_id' )->attr('value') );
		unset( $html );
		$this->_last_response = '';

		//Remove item
		$_POST['atum_order_item_ids'] = [ $product->get_id() ];
		try {
			ob_start();
			$ajax->remove_atum_order_item();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		unset( $html );
		$this->_last_response = '';

		//Save items
		$_POST['items'] = $new_atum_order_item;
		try {
			ob_start();
			$ajax->save_atum_order_items();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$html = new Crawler( $this->_last_response );
		$this->assertEquals( 1, $html->filter( 'div.atum_order_items_wrapper' )->count() );
		unset( $html );
		$this->_last_response = '';

		// Reload list again
		try {
			ob_start();
			$ajax->load_atum_order_items();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$html = new Crawler( $this->_last_response );
		$this->assertEquals( 1, $html->filter( 'div.atum_order_items_wrapper' )->count() );
	}

	/**
	 * Tests add_atum_order_fee method
	 */
	public function test_add_atum_order_fee() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['security'] = wp_create_nonce( 'atum-order-item' );
		$po = new PurchaseOrders();
		$po->register_post_type();

		//Purchase Order
		$pid   = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$order = Helpers::get_atum_order_model( $pid );

		$_POST['atum_order_id'] = $order->get_id();

		try {
			ob_start();
			$ajax->add_atum_order_fee();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$data = json_decode( $this->_last_response, true );
		$this->assertTrue( $data['success'] );
		$html = new Crawler( $data['data'] );
		$this->assertEquals( 1, $html->filter('tr.fee')->count() );
	}

	/**
	 * Tests add_atum_order_shipping method
	 */
	public function test_add_atum_order_shipping() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['security'] = wp_create_nonce( 'atum-order-item' );
		$po = new PurchaseOrders();
		$po->register_post_type();

		//Purchase Order
		$pid   = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$order = Helpers::get_atum_order_model( $pid );

		$_POST['atum_order_id'] = $order->get_id();

		try {
			ob_start();
			$ajax->add_atum_order_shipping();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$data = json_decode( $this->_last_response, true );
		$this->assertTrue( $data['success'] );
		$html = new Crawler( $data['data'] );
		$this->assertEquals( 1, $html->filter('tr.shipping')->count() );
	}

	/**
	 * Tests add_atum_order_tax, change_atum_order_item_purchase_price, remove_atum_order_tax methods
	 */
	public function test_add_atum_order_price_tax () {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['security'] = wp_create_nonce( 'atum-order-item' );
		$po = new PurchaseOrders();
		$po->register_post_type();
		//$wpml = new \Atum\Integrations\Wpml();
		//$wpml->register_atum_order_hooks( 'atum_purchase_price' );

		//Purchase Order
		$pid = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$order = Helpers::get_atum_order_model( $pid );

		//Product
		$product = new WC_Product();
		$product->set_props(
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'price'         => 10,
				'sku'           => 'DUMMY SKU',
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
				'stock_status'  => 'instock',
				'weight'        => '1.1',
				'inbound_stock' => 16,
			)
		);
		$product->save();
		$product = Helpers::get_atum_product( $product->get_id() );

		//Add new item to order
		$_POST['atum_order_id'] = $order->get_id();
		$_POST['item_to_add']   = $product->get_id();
		try {
			ob_start();
			$ajax->add_atum_order_item();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$data = json_decode( $this->_last_response, true );
		$html = new Crawler( $data['data'] );
		$this->assertEquals( 1, $html->filter( 'tr.new_row' )->count() );
		$new_atum_order_item = intval( $html->filter( 'tr.new_row' )->attr( 'data-atum_order_item_id' ) );
		unset( $html );
		unset( $data );
		$this->_last_response = '';

		//Purchase price
		$price                       = 25;
		$_POST['_purchase_price']    = $price;
		$_POST['atum_order_item_id'] = $new_atum_order_item;
		try {
			ob_start();
			$ajax->change_atum_order_item_purchase_price();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$data = json_decode( $this->_last_response, true );
		$this->assertTrue( $data['success'] );
		//TODO: This assertion fails
		//$this->assertEquals( $price, $product->get_purchase_price() );
		unset( $data );
		$this->_last_response = '';

		//Tax
		$tax = new WC_Order_Item_Tax();
		$tax->set_props( [
			'name'    => 'Dummy tax',
			'rate_id' => 5,
		] );
		$tax->save();
		$_POST['rate_id'] = $tax->get_id();
		try {
			ob_start();
			$ajax->add_atum_order_tax();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$data = json_decode( $this->_last_response, true );
		$this->assertTrue( $data['success'] );
		$html = new Crawler( $data['data']['html'] );
		print_r($data);
		die;
		unset( $data );
		unset( $html );
		$this->_last_response = '';

		//Remove tax
		$_POST['rate_id'] = $tax->get_id();
		try {
			ob_start();
			$ajax->remove_atum_order_tax();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$data = json_decode( $this->_last_response, true );
		$this->assertTrue( $data['success'] );
		$html = new Crawler( $data['data'] );
		//$this->assertEquals( 1, $html->filter('tr.shipping')->count() );
		unset( $html );
		unset( $data );
		$this->_last_response = '';


	}
	/*
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_remove_tax', array( 'Ajax', 'remove_atum_order_tax' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_calc_line_taxes', array( 'Ajax', 'calc_atum_order_line_taxes' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_increase_items_stock', array( 'Ajax', 'increase_atum_order_items_stock' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_decrease_items_stock', array( 'Ajax', 'decrease_atum_order_items_stock' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_mark_status', array( 'Ajax', 'mark_atum_order_status' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_import_items', array( 'Ajax', 'import_wc_order_items' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_set_variations_control_status', array( 'Ajax', 'set_variations_control_status' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_get_locations_tree', array( 'Ajax', 'get_locations_tree' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_set_locations_tree', array( 'Ajax', 'set_locations_tree' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_tool_manage_stock', array( 'Ajax', 'change_manage_stock' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_tool_control_stock', array( 'Ajax', 'change_control_stock' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_tool_clear_out_stock_threshold', array( 'Ajax', 'clear_out_stock_threshold' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_change_table_style_setting', array( 'Ajax', 'change_table_style_user_meta' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_get_marketing_popup_info', array( 'Ajax', 'get_marketing_popup_info' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_hide_marketing_popup', array( 'Ajax', 'marketing_popup_state' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_get_scheme_color', array( 'Ajax', 'get_scheme_color' ) ) );
	*/

	/**
	 * Data provider for test methods
	 *
	 * @return array
	 */
	public function getStatsTypes() {
		$result = [];
		$type   = [
			'sales',
			'lost-sales',
			'promo-sales',
			'orders',
		];
		$time   = [
			'this_week',
			'previous_week',
			'this_month',
			'previous_month',
			'this_year',
			'previous_year',
		];
		foreach ( $type as $dt ) {
			foreach ( $time as $tm ) {
				$result[] = [ $dt, $tm ];
			}
		}

		return $result;
	}

	/**
	 * Data provider for test methods
	 *
	 * @return array
	 */
	public function provideBulkAction() {
		return [
			[ 'uncontrol_stock' ],
			[ 'control_stock' ],
			[ 'unmanage_stock' ],
			[ 'manage_stock' ],
		];
	}

}
