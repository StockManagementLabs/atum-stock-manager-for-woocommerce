<?php
/**
 * Class AjaxTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Inc\Ajax;
use Atum\Models\Products\AtumProductSimple;
use Symfony\Component\DomCrawler\Crawler;
use Atum\Components\AtumCache;
use Atum\Inc\Helpers;


/**
 * Sample test case.
 */
class AjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * Reset variables in every methods
	 */
	public function setUp() {
		unset( $_REQUEST );
		unset( $_POST );

		parent::setUp();
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
	 */
	public function test_update_list_data() {
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
			ob_clean();

		} catch ( Exception $e ) {
			unset( $e );
		}

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

	/*
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_apply_bulk_action', array( 'Ajax', 'apply_bulk_action' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_control_all_products', array( 'Ajax', 'control_all_products' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_validate_license', array( 'Ajax', 'validate_license' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_activate_license', array( 'Ajax', 'activate_license' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_deactivate_license', array( 'Ajax', 'deactivate_license' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_install_addon', array( 'Ajax', 'install_addon' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_dismiss_notice', array( 'Ajax', 'dismiss_notice' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_json_search_products', array( 'Ajax', 'search_products' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_json_search_orders', array( 'Ajax', 'search_wc_orders' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_json_search_suppliers', array( 'Ajax', 'search_suppliers' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_add_note', array( 'Ajax', 'add_atum_order_note' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_delete_note', array( 'Ajax', 'delete_atum_order_note' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_load_items', array( 'Ajax', 'load_atum_order_items' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_add_item', array( 'Ajax', 'add_atum_order_item' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_add_fee', array( 'Ajax', 'add_atum_order_fee' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_add_shipping', array( 'Ajax', 'add_atum_order_shipping' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_add_tax', array( 'Ajax', 'add_atum_order_tax' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_remove_item', array( 'Ajax', 'remove_atum_order_item' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_remove_tax', array( 'Ajax', 'remove_atum_order_tax' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_calc_line_taxes', array( 'Ajax', 'calc_atum_order_line_taxes' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_save_items', array( 'Ajax', 'save_atum_order_items' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_increase_items_stock', array( 'Ajax', 'increase_atum_order_items_stock' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_decrease_items_stock', array( 'Ajax', 'decrease_atum_order_items_stock' ) ) );
	$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_change_purchase_price', array( 'Ajax', 'change_atum_order_item_purchase_price' ) ) );
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
