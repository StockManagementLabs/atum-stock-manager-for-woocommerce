<?php
/**
 * Class AjaxTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Components\AtumCache;
use Atum\Inc\Ajax;
use Atum\Models\Products\AtumProductSimple;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;
use Symfony\Component\DomCrawler\Crawler;
use Atum\Inc\Helpers;
use Atum\Components\AtumOrders\AtumComments;
use TestHelpers\TestHelpers;

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

		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_dashboard_save_layout', array( Ajax::class, 'save_dashboard_layout' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_dashboard_restore_layout', array( Ajax::class, 'restore_dashboard_layout' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_dashboard_add_widget', array( Ajax::class, 'add_new_widget' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_statistics_widget_chart', array( Ajax::class, 'statistics_widget_chart' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_videos_widget_sorting', array( Ajax::class, 'videos_widget_sorting' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_current_stock_values', array( Ajax::class, 'current_stock_values' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_fetch_stock_central_list', array( Ajax::class, 'fetch_stock_central_list' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_fetch_inbound_stock_list', array( Ajax::class, 'fetch_inbound_stock_list' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_rated', array( Ajax::class, 'rated' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_update_data', array( Ajax::class, 'update_list_data' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_apply_bulk_action', array( Ajax::class, 'apply_bulk_action' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_control_all_products', array( Ajax::class, 'control_all_products' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_validate_license', array( Ajax::class, 'validate_license' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_activate_license', array( Ajax::class, 'activate_license' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_deactivate_license', array( Ajax::class, 'deactivate_license' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_install_addon', array( Ajax::class, 'install_addon' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_dismiss_notice', array( Ajax::class, 'dismiss_notice' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_json_search_products', array( Ajax::class, 'search_products' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_json_search_orders', array( Ajax::class, 'search_wc_orders' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_json_search_suppliers', array( Ajax::class, 'search_suppliers' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_add_note', array( Ajax::class, 'add_atum_order_note' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_delete_note', array( Ajax::class, 'delete_atum_order_note' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_load_items', array( Ajax::class, 'load_atum_order_items' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_add_item', array( Ajax::class, 'add_atum_order_item' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_add_fee', array( Ajax::class, 'add_atum_order_fee' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_add_shipping', array( Ajax::class, 'add_atum_order_shipping' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_add_tax', array( Ajax::class, 'add_atum_order_tax' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_remove_item', array( Ajax::class, 'remove_atum_order_item' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_remove_tax', array( Ajax::class, 'remove_atum_order_tax' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_calc_line_taxes', array( Ajax::class, 'calc_atum_order_line_taxes' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_save_items', array( Ajax::class, 'save_atum_order_items' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_increase_items_stock', array( Ajax::class, 'increase_atum_order_items_stock' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_decrease_items_stock', array( Ajax::class, 'decrease_atum_order_items_stock' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_change_purchase_price', array( Ajax::class, 'change_atum_order_item_purchase_price' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_mark_status', array( Ajax::class, 'mark_atum_order_status' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_order_import_items', array( Ajax::class, 'import_wc_order_items' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_set_variations_control_status', array( Ajax::class, 'set_variations_control_status' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_get_locations_tree', array( Ajax::class, 'get_locations_tree' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_set_locations_tree', array( Ajax::class, 'set_locations_tree' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_tool_manage_stock', array( Ajax::class, 'change_manage_stock' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_tool_control_stock', array( Ajax::class, 'change_control_stock' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_tool_clear_out_stock_threshold', array( Ajax::class, 'clear_out_stock_threshold' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_change_table_style_setting', array( Ajax::class, 'change_table_style_user_meta' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_get_marketing_popup_info', array( Ajax::class, 'get_marketing_popup_info' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_hide_marketing_popup', array( Ajax::class, 'marketing_popup_state' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_ajax_atum_get_color_scheme', array( Ajax::class, 'get_color_scheme' ) ) );
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
	 * Tests add_new_widget method
	 */
	public function DISABLEDtest_add_new_widget() {
		wp_set_current_user( 1 );
		set_current_screen( 'atum-dashboard' );
		$dash = \Atum\Dashboard\Dashboard::get_instance();
		$dash->load_widgets();
		$_REQUEST['token'] = wp_create_nonce( 'atum-dashboard-widgets' );
		$_POST['widget']   = 'stock_control_widget';

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->add_new_widget();
		} catch ( Exception $e ) {
			var_dump( $e->getMessage() );
			var_dump( $e->getTraceAsString() );
			unset( $e );
		}
		$data = json_decode( $this->_last_response, TRUE );
		var_dump( $data );
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
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
	public function test_current_stock_values() {
		wp_set_current_user( 1 );
		$_REQUEST['token'] = wp_create_nonce( 'atum-dashboard-widgets' );
		// Product needed.
		TestHelpers::create_atum_simple_product();

		$_POST['categorySelected']    = NULL;
		$_POST['productTypeSelected'] = NULL;

		try {
			//$this->_handleAjax( 'atum_current_stock_values' );
			ob_start();
			$ajax = Ajax::get_instance();
			$ajax->current_stock_values();
		} catch ( Exception $e ) {
			$this->assertInstanceOf( WPDieException::class, $e );
			unset( $e );
		}
		ob_clean();
		$data = json_decode( $this->_last_response, TRUE );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'current_stock_values', $data['data'] );
		$this->assertGreaterThan( 0, $data['data']['current_stock_values']['items_stocks_counter'] );
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
		$product = TestHelpers::create_product();
		Helpers::get_atum_product( $product->get_id() );

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
		TestHelpers::create_product();

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
		TestHelpers::create_product();

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
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		set_current_screen( 'atum-stock-central' );

		$product = TestHelpers::create_product();
		$price   = $product->get_regular_price();
		$pid     = $product->get_id();

		$_POST['first_edit_key'] = '';
		$_REQUEST['token'] = wp_create_nonce( 'atum-list-table-nonce' );
		$_POST['data']     = wp_json_encode( [
			$pid => [
				'regular_price'          => '25',
				'regular_price_custom'   => 'no',
				'regular_price_currency' => 'EUR',
			],
		] );

		try {
			ob_start();
			//$ajax->update_list_data();
			$this->_handleAjax('wp_ajax_atum_update_data');
		} catch ( Exception $e ) {
			echo $e->getTraceAsString();
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
	 */
	public function test_apply_bulk_action( $action ) {
		$ajax = Ajax::get_instance();
		$_REQUEST['token']    = wp_create_nonce( 'atum-list-table-nonce' );
		$_POST['bulk_action'] = $action;
		$_POST['ids']         = [];

		for ( $i = 0; $i < 4; $i++ ) {
			$product = TestHelpers::create_atum_simple_product();
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
			ob_start();
			$ajax->apply_bulk_action();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		foreach ( $_POST['ids'] as $id ) {
			$product = Helpers::get_atum_product( $id );
			switch ( $action ) {
				case 'uncontrol_stock':
				case 'control_stock':
					$this->assertEquals( 'control_stock' === $action ? 'yes' : 'no', Helpers::get_atum_control_status( $product ) );
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

		$data = json_decode( $this->_last_response, true );

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

		$data = json_decode( $this->_last_response, true );

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
		$data = json_decode( $this->_last_response, true );

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
		$data = json_decode( $this->_last_response, true );

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
		} catch ( Exception $e ) {
			// var_dump( $e );
			// echo "\n" . $e->getTraceAsString();.
			unset( $e );
		}
		ob_clean();

		$data = json_decode( $this->_last_response, true );

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
		$_SERVER['REQUEST_URI']  = home_url() . '/?term=Dummy';
		$_SERVER['HTTP_REFERER'] = home_url() . '/foo/?term=Dummy';
		$_REQUEST['security']    = wp_create_nonce( 'search-products' );
		$_GET['term']            = 'Dummy';

		TestHelpers::create_product();

		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->search_products();
			ob_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_end_clean();

		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		foreach ( $data as $k => $d )
			$this->assertNotFalse( strpos( $d, $_GET['term'] ) );

		$_GET['term']         = 'foo';
		$this->_last_response = null;
		try {
			$ajax = Ajax::get_instance();
			ob_start();
			$ajax->search_products();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_end_clean();
		$data = json_decode( $this->_last_response, true );
		$this->assertEmpty( $data );
	}

	/**
	 * Tests search_wc_orders method
	 */
	public function test_search_wc_orders() {
		wp_set_current_user( 1 );
		$_SERVER['REQUEST_URI']  = home_url() . '/?term=Dummy';
		$_SERVER['HTTP_REFERER'] = home_url() . '/foo/?term=Dummy';
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

		$data = json_decode( $this->_last_response, true );

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

		$data = json_decode( $this->_last_response, true );

		$this->assertIsArray( $data );
		foreach ( $data as $k => $v )
			$this->assertGreaterThanOrEqual( 0, strpos( $v, 'Foo' ) );
	}

	/**
	 * Tests add_atum_order_note and delete_atum_order_note methods
	 */
	public function DISABLEDtest_atum_order_notes() {
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

		//var_dump($this->_last_response);
		$html = new Crawler( $this->_last_response );

		$this->assertEquals( 1, $html->filter( 'li.note' )->count() );



		$comment_id = $html->filter( 'li.note' )->attr( 'rel' );
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
		//var_dump($comment);
		// print_r($comment); die;.
		//$this->assertInstanceOf( WP_Comment::class, $comment );
		//$this->assertEquals( $comment->comment_approved, 'trash' );
		//$this->assertNull( $comment );
	}

	/**
	 * Tests add_atum_order_items, load_atum_order_items, remove_atum_order_item methods
	 */
	public function test_atum_order_items() {
		wp_set_current_user( 1 );
		$ajax                 = Ajax::get_instance();
		$_REQUEST['security'] = wp_create_nonce( 'atum-order-item' );
		$po                   = new PurchaseOrders();
		$po->register_post_type();

		// Purchase Order.
		$pid   = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$order = Helpers::get_atum_order_model( $pid );

		// Product.
		$product = TestHelpers::create_product();

		// Add new item to order.
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

		// Load order items list.
		try {
			ob_start();
			$ajax->load_atum_order_items();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$html = new Crawler( $this->_last_response );
		$this->assertEquals( 1, $html->filter( '.atum_order_items_wrapper' )->count() );
		$this->assertEquals( $new_atum_order_item, $html->filter( 'input.atum_order_item_id' )->attr( 'value' ) );
		unset( $html );
		$this->_last_response = '';

		// Remove item.
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

		// Save items.
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

		// Reload list again.
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
		$po                   = new PurchaseOrders();
		$po->register_post_type();

		// Purchase Order.
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
		$this->assertEquals( 1, $html->filter( 'tr.fee' )->count() );
	}

	/**
	 * Tests add_atum_order_shipping method
	 */
	public function test_add_atum_order_shipping() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['security'] = wp_create_nonce( 'atum-order-item' );
		$po                   = new PurchaseOrders();
		$po->register_post_type();

		// Purchase Order.
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
		$this->assertEquals( 1, $html->filter( 'tr.shipping' )->count() );
	}

	/**
	 * Tests add_atum_order_tax, change_atum_order_item_purchase_price, remove_atum_order_tax methods
	 */
	public function test_add_atum_order_price_tax() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['security'] = wp_create_nonce( 'atum-order-item' );
		$po                   = new PurchaseOrders();
		$po->register_post_type();

		// Purchase Order.
		$pid   = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$order = Helpers::get_atum_order_model( $pid );

		// Product.
		$product = TestHelpers::create_product();
		$product = Helpers::get_atum_product( $product->get_id() );

		// Add new item to order.
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

		// Purchase price.
		$price                        = 25;
		$_POST['_purchase_price']     = $price;
		$_POST['atum_purchase_price'] = $price;
		$_POST['atum_order_item_id']  = $new_atum_order_item;
		try {
			ob_start();
			$ajax->change_atum_order_item_purchase_price();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$data = json_decode( $this->_last_response, true );
		$this->assertTrue( $data['success'] );
		$product->set_purchase_price( $price );
		$this->assertEquals( $price, $product->get_purchase_price() );
		unset( $data );
		$this->_last_response = '';

		// Tax.
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
		$this->assertEquals( 1, $html->filter( '#tmpl-atum-modal-add-tax' )->count() );
		unset( $data );
		unset( $html );
		$this->_last_response = '';

		// Remove tax.
		$_POST['rate_id'] = $tax->get_id();
		try {
			ob_start();
			$ajax->remove_atum_order_tax();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$html = new Crawler( $this->_last_response );
		$this->assertEquals( 1, $html->filter( 'div.atum_purchase_order_items' )->count() );
		unset( $html );
		$this->_last_response = '';
	}

	/**
	 * Tests calc_atum_order_line_taxes method
	 */
	public function test_calc_atum_order_line_taxes() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['security'] = wp_create_nonce( 'calc-totals' );
		$po                   = new PurchaseOrders();
		$po->register_post_type();

		// Purchase Order.
		$pid   = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$order = Helpers::get_atum_order_model( $pid );

		// Product.
		$product = TestHelpers::create_product();
		$product = Helpers::get_atum_product( $product->get_id() );

		$tax = new WC_Order_Item_Tax();
		$tax->set_props( [
			'name'    => 'Dummy tax',
			'rate_id' => 5,
		] );
		$tax->save();

		$order->add_product( $product, 1 );
		$order->add_tax( array( 'rate_id' => $tax->get_id() ) );

		$_POST['atum_order_id'] = $order->get_id();
		$_POST['items']         = $product->get_id();

		try {
			ob_start();
			$ajax->calc_atum_order_line_taxes();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$html = new Crawler( $this->_last_response );
		$this->assertEquals( 1, $html->filter( '#tmpl-atum-modal-add-tax' )->count() );
	}

	/**
	 * Tests increase_atum_order_items_stock, decrease_atum_order_items_stock method
	 */
	public function test_atum_order_items_stock() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['security'] = wp_create_nonce( 'atum-order-item' );
		$po                   = new PurchaseOrders();
		$po->register_post_type();

		// Purchase Order.
		$pid   = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$order = Helpers::get_atum_order_model( $pid );

		// Product.
		$product = TestHelpers::create_product();
		$product = Helpers::get_atum_product( $product->get_id() );
		Helpers::update_wc_manage_stock( $product->get_id() );

		$order->add_product( $product, 1 );
		$items = $order->get_items();

		$_POST['atum_order_id']       = $order->get_id();
		$_POST['atum_order_item_ids'] = [];
		$_POST['quantities']          = [];

		foreach ( $items as $k => $v ) {
			$_POST['quantities'][ $k ]      = 47;
			$_POST['atum_order_item_ids'][] = $k;
		}

		try {
			ob_start();
			$ajax->increase_atum_order_items_stock();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		unset( $data );
		$this->_last_response = '';

		foreach ( $items as $k => $v )
			$_POST['quantities'][ $k ] = 19;

		try {
			ob_start();
			$ajax->decrease_atum_order_items_stock();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
	}

	/**
	 * Tests mark_atum_order_status method
	 */
	public function test_mark_atum_order_status() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'atum-order-mark-status' );
		$po                   = new PurchaseOrders();
		$po->register_post_type();

		// Purchase Order.
		$pid   = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$order = Helpers::get_atum_order_model( $pid );

		$_GET['atum_order_id'] = $order->get_id();
		$_GET['status']        = 'foo_status';

		try {
			$this->_handleAjax( 'atum_order_mark_status' );
		} catch ( Exception $e ) {
			unset( $e );
		}

		$this->assertIsString( $order->get_post()->post_status );
	}

	/**
	 * Tests import_wc_order_items method
	 */
	public function DISABLEDtest_import_wc_order_items() {
		wp_set_current_user( 1 );
		$ajax                 = Ajax::get_instance();
		$_REQUEST['security'] = wp_create_nonce( 'import-order-items' );
		$po                   = new PurchaseOrders();
		$po->register_post_type();

		// Purchase Order.
		$pid   = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$order = Helpers::get_atum_order_model( $pid );

		// Product.
		//$product = Helpers::get_atum_product( TestHelpers::create_product() );
		$product = TestHelpers::create_atum_simple_product();
		// WC Order.
		$wcorder = TestHelpers::create_order( $product );

		$_POST['atum_order_id'] = $order->get_id();
		$_POST['wc_order_id']   = $wcorder->get_id();

		try {
			ob_start();
			// TODO: Call to undefined method WC_Product_Simple::get_purchase_price().
			$ajax->import_wc_order_items();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
	}

	/**
	 * Tests set_variations_control_status method
	 *
	 * @param string $status
	 *
	 * @dataProvider provideStatus
	 */
	public function test_set_variations_control_status( $status ) {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['security'] = wp_create_nonce( 'atum-product-data-nonce' );

		$product = TestHelpers::create_variation_product( true );

		$_POST['parent_id'] = $product->get_parent_id();
		$_POST['status']    = $status;

		try {
			ob_start();
			$ajax->set_variations_control_status();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'All the variations were updated successfully', $data['data'] );
	}

	/**
	 * Tests get_locations_tree, set_locations_tree methods
	 */
	public function test_locations_tree() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['token'] = wp_create_nonce( 'atum-list-table-nonce' );

		$product = TestHelpers::create_product();

		$_POST['product_id'] = $product->get_id();
		$_POST['terms'] = 'foo,faa,fee';

		try {
			ob_start();
			$ajax->set_locations_tree();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'ok', $data['data'] );
		unset($data);
		$this->_last_response = '';

		try {
			ob_start();
			$ajax->get_locations_tree();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
		$this->assertIsString( $data['data'] );
	}

	/**
	 * Tests change_manage_stock method
	 *
	 * @param $option
	 *
	 * @dataProvider provideManage
	 */
	public function test_change_manage_stock( $option ) {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['token'] = wp_create_nonce( 'atum-script-runner-nonce' );

		$_POST['option'] = $option;

		try {
			ob_start();
			$ajax->change_manage_stock();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'All your products were updated successfully', $data['data'] );
	}

	/**
	 * Tests change_control_stock method
	 *
	 * @param $option
	 *
	 * @dataProvider provideControl
	 */
	public function test_change_control_stock( $option ) {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['token'] = wp_create_nonce( 'atum-script-runner-nonce' );

		$_POST['option'] = $option;

		try {
			ob_start();
			$ajax->change_control_stock();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'All your products were updated successfully', $data['data'] );
	}

	/**
	 * Tests clear_out_stock_threshold method
	 */
	public function test_clear_out_stock_threshold() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['token'] = wp_create_nonce( 'atum-script-runner-nonce' );

		try {
			ob_start();
			$ajax->clear_out_stock_threshold();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();
		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'All your previously saved values were cleared successfully.', $data['data'] );
	}

	/**
	 * Tests change_table_style_user_meta method
	 *
	 * @param $enabled
	 * @param $feature
	 *
	 * @dataProvider provideTableConf
	 */
	public function test_change_table_style_user_meta( $enabled, $feature ) {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['token'] = wp_create_nonce( 'atum-list-table-style' );

		$_POST['enabled'] = $enabled;
		$_POST['feature'] = $feature;

		try {
			ob_start();
			$ajax->change_table_style_user_meta();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$data = Helpers::get_atum_user_meta( $feature, 1 );

		foreach ( $data as $k => $v )
			$this->assertEquals( $enabled ? 'yes' : 'no', $v );
	}

	/**
	 * Tests get_marketing_popup_info and marketing_popup_state methods
	 */
	public function test_get_marketing_popup_info() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['token'] = wp_create_nonce( 'atum-marketing-popup-nonce' );

		try {
			ob_start();
			$ajax->get_marketing_popup_info();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'title', $data['data'] );
		$this->assertArrayHasKey( 'version', $data['data'] );
		$this->assertArrayHasKey( 'transient_key', $data['data'] );

		$key = $data['data']['transient_key'];
		unset( $data );
		$this->_last_response  = '';
		$_POST['transientKey'] = $key;

		try {
			ob_start();
			$ajax->marketing_popup_state();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$this->assertEquals( $key, AtumCache::get_transient( 'atum-marketing-popup' ) );
	}

	/**
	 * Tests get_scheme_color method
	 */
	public function test_get_scheme_color() {
		$ajax = Ajax::get_instance();
		wp_set_current_user( 1 );
		$_REQUEST['token'] = wp_create_nonce( 'atum-color-scheme-nonce' );

		$_POST['reset'] = 1;

		try {
			ob_start();
			$ajax->get_color_scheme();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_clean();

		$data = json_decode( $this->_last_response, true );
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
		foreach( $data['data'] as $key => $value ) {
			$this->assertEquals( 1, preg_match( '/#([a-f0-9]{3}){1,2}\b/i', $value ) );
		}
	}

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
	public function provideTableConf() {
		$result = [];
		$features = [
			'sticky-columns',
			'normal-columns',
		];
		$enabled  = [
			true,
			false,
		];
		foreach ( $features as $f ) {
			foreach ( $enabled as $e ) {
				$result[] = [ $e, $f ];
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
			//[ 'uncontrol_stock' ],
			[ 'control_stock' ],
			[ 'unmanage_stock' ],
			[ 'manage_stock' ],
		];
	}

	/**
	 * Data provider for test methods
	 *
	 * @return array
	 */
	public function provideStatus() {
		return [
			[ 'controlled' ],
			[ 'uncontrolled' ],
		];
	}

	/**
	 * Data provider for test methods
	 *
	 * @return array
	 */
	public function provideManage() {
		return [
			[ 'manage' ],
			[ 'unmanage' ],
		];
	}

	/**
	 * Data provider for test methods
	 *
	 * @return array
	 */
	public function provideControl() {
		return [
			[ 'control' ],
			[ 'uncontrol' ],
		];
	}

}
