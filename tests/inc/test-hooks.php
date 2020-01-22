<?php
/**
 * Class HooksTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Inc\Hooks;
use Atum\Inc\Helpers;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class HooksTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( Hooks::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$this->assertInstanceOf( Hooks::class, Hooks::get_instance() );
	}

	public function test_register_admin_hooks() {
		$hooks = Hooks::get_instance();
		$hooks->register_admin_hooks();
		$this->assertEquals( 10, TestHelpers::has_action( 'plugin_row_meta', array( Hooks::class, 'plugin_row_meta' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'admin_enqueue_scripts', array( Hooks::class, 'enqueue_scripts' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_admin_stock_html', array( Hooks::class, 'set_wc_products_list_stock_status' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_admin_order_item_headers', array( Hooks::class, 'wc_order_add_location_column_header' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_admin_order_item_values', array( Hooks::class, 'wc_order_add_location_column_value' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_dropdown_cats', array( Hooks::class, 'set_dropdown_autocomplete' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'updated_option', array( Hooks::class, 'rebuild_stock_status_on_oost_changes' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_order_status_completed', array( Hooks::class, 'maybe_save_paid_date' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'delete_post', array( Hooks::class, 'before_delete_product' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'product_variation_linked', array( Hooks::class, 'save_variation_atum_data' ) ) );
		$this->assertEquals( PHP_INT_MAX, TestHelpers::has_action( 'woocommerce_ajax_order_items_added', array( Hooks::class, 'save_added_order_items_props' ) ) );
		$this->assertEquals( PHP_INT_MAX, TestHelpers::has_action( 'woocommerce_before_delete_order_item', array( Hooks::class, 'before_delete_order_item' ) ) );
		$this->assertEquals( PHP_INT_MAX, TestHelpers::has_action( 'woocommerce_delete_order_item', array( Hooks::class, 'after_delete_order_item' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_product_duplicate', array( Hooks::class, 'duplicate_product' ) ) );
	}

	public function test_register_global_hooks() {
		$hooks = Hooks::get_instance();
		$hooks->register_global_hooks();
		$this->assertEquals( 20, TestHelpers::has_action( 'woocommerce_product_set_stock', array( Hooks::class, 'record_out_of_stock_date' ) ) );
		$this->assertEquals( 11, TestHelpers::has_action( 'init', array( Hooks::class, 'stock_decimals' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_product_set_stock', array( Hooks::class, 'delete_transients' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_variation_set_stock', array( Hooks::class, 'delete_transients' ) ) );

		if ( 'yes' === Helpers::get_option( 'out_stock_threshold', 'no' ) ) {
			$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_product_set_stock', array( Hooks::class, 'maybe_change_stock_threshold' ) ) );
			$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_variation_set_stock', array( Hooks::class, 'maybe_change_stock_threshold' ) ) );
			$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_save_product_variation', array( Hooks::class, 'maybe_change_variation_stock_status' ) ) );
			$this->assertEquals( 19, TestHelpers::has_action( 'woocommerce_process_product_meta', array( Hooks::class, 'add_stock_status_threshold' ) ) );
			$this->assertEquals( 21, TestHelpers::has_action( 'woocommerce_process_product_meta', array( Hooks::class, 'remove_stock_status_threshold' ) ) );
			$this->assertEquals( 10, TestHelpers::has_action( 'atum/product_data/before_save_product_meta_boxes', array( Hooks::class, 'add_stock_status_threshold' ) ) );
			$this->assertEquals( 10, TestHelpers::has_action( 'atum/product_data/after_save_product_meta_boxes', array( Hooks::class, 'remove_stock_status_threshold' ) ) );
			$this->assertEquals( 10, TestHelpers::has_action( 'atum/product_data/before_save_product_variation_meta_boxes', array( Hooks::class, 'add_stock_status_threshold' ) ) );
			$this->assertEquals( 10, TestHelpers::has_action( 'atum/product_data/after_save_product_variation_meta_boxes', array( Hooks::class, 'remove_stock_status_threshold' ) ) );
		}

		$this->assertEquals( PHP_INT_MAX, TestHelpers::has_action( 'woocommerce_saved_order_items', array( Hooks::class, 'save_order_items_props' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'trashed_post', array( Hooks::class, 'maybe_save_order_items_props' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'untrashed_post', array( Hooks::class, 'maybe_save_order_items_props' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_after_order_object_save', array( Hooks::class, 'clean_up_update_date' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_after_product_object_save', array( Hooks::class, 'update_atum_calc_fields' ) ) );
	}

	public function test_enqueue_scripts() {
		wp_set_current_user(1);

		// Product needed.
		$pid = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => 'product',
			'post_status' => 'publish',
		) );

		$GLOBALS['post'] = get_post( $pid );
		$hooks = Hooks::get_instance();
		$hooks->enqueue_scripts( 'post.php' );

		$this->assertTrue( wp_script_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_script_is( 'atum-product-data', 'registered' ) );
		$this->assertTrue( wp_style_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_style_is( 'switchery', 'registered' ) );
		$this->assertTrue( wp_style_is( 'atum-product-data', 'registered' ) );
	}

	public function test_wc_orders_min_qty() {
		$hooks = Hooks::get_instance();
		ob_start();
		$hooks->wc_orders_min_qty( false );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('script')->count() );
	}

	public function test_set_wc_products_list_stock_status() {
		$product = TestHelpers::create_atum_simple_product();
		$stock_html = '<mark class="instock">' . __( 'In stock', 'woocommerce' ) . '</mark>';
		$hooks = Hooks::get_instance();
		$response = $hooks->set_wc_products_list_stock_status( $stock_html, $product );
		$this->assertEquals( $stock_html, $response );
	}

	public function test_wc_order_add_location_column_header() {
		$hooks = Hooks::get_instance();
		ob_start();
		$hooks->wc_order_add_location_column_header( false );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('th.item_location')->count() );
	}

	public function test_wc_order_add_location_column_value() {
		$hooks = Hooks::get_instance();
		$product = TestHelpers::create_atum_simple_product();
		ob_start();
		$hooks->wc_order_add_location_column_value( $product, false, false );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter( 'td.item_location' )->count() );
		$this->assertEquals( 1, $html->filter( 'div.view' )->count() );
	}

	public function test_record_out_of_stock_date() {
		$product = TestHelpers::create_product();
		$hooks = Hooks::get_instance();
		try {
			$hooks->record_out_of_stock_date( $product );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_delete_transients() {
		$product = TestHelpers::create_product();
		$hooks = Hooks::get_instance();
		try {
			$hooks->delete_transients( $product );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_stock_decimals() {
		$hooks = Hooks::get_instance();
		try {
			$hooks->stock_decimals();
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_stock_quantity_input_atts() {
		$hooks = Hooks::get_instance();
		$product = TestHelpers::create_product();
		try {
			$hooks->stock_quantity_input_atts( 50, $product );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_add_to_cart_message() {
		$hooks = Hooks::get_instance();
		$msg = $hooks->add_to_cart_message( 'Foo testing message', [ 5 => 1, 32 => 5, 10 => 2 ] );
		$this->assertGreaterThan( 0, strpos( $msg, 'have been added to your cart' ) );
	}

	public function test_rebuild_stock_status_on_oost_changes() {
		$hooks = Hooks::get_instance();
		try {
			$hooks->rebuild_stock_status_on_oost_changes( 'atum_settings', [], [ 'out_stock_threshold' => 'yes' ] );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_set_dropdown_autocomplete() {
		$hooks = Hooks::get_instance();
		$rep = $hooks->set_dropdown_autocomplete( '<select name="foo"></select>', [ 'name' => 'product_cat' ]);
		$this->assertGreaterThan( 0, strpos( $rep, 'name="foo"' ) );
		$this->assertGreaterThan( 0, strpos( $rep, 'autocomplete="off"' ) );
	}

	public function test_round_stock_quantity() {
		$hooks = Hooks::get_instance();
		$this->assertIsNumeric( $hooks->round_stock_quantity( 4.95 ) );
	}

	public function test_get_custom_stock_threshold() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_maybe_change_stock_threshold() {
		$product = TestHelpers::create_atum_simple_product();
		$hooks = Hooks::get_instance();
		try {
			$hooks->maybe_change_stock_threshold( $product );
			$this->assertTrue( TRUE );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->assertTrue( $hooks->get_custom_stock_threshold( TRUE, '', [] ) );
	}

	public function test_maybe_change_variation_stock_status() {
		$product = TestHelpers::create_variation_product( TRUE );
		$hooks = Hooks::get_instance();
		try {
			$hooks->maybe_change_variation_stock_status( $product->get_id(), 5 );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->assertTrue( TRUE );
	}

	public function test_remove_stock_status_threshold() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_add_stock_status_threshold() {
		$product = TestHelpers::create_atum_simple_product();
		$hooks = Hooks::get_instance();
		try {
			$hooks->add_stock_status_threshold( $product->get_id() );
			$hooks->remove_stock_status_threshold( $product->get_id() );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->assertTrue( TRUE );
	}

	public function test_plugin_row_meta() {
		$hooks = Hooks::get_instance();
		$data = $hooks->plugin_row_meta( [], ATUM_BASENAME );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'video_tutorials', $data);
		$this->assertArrayHasKey( 'addons', $data);
		$this->assertArrayHasKey( 'support', $data);
	}

	public function test_maybe_save_paid_date() {
		$product = TestHelpers::create_atum_simple_product();
		$order = TestHelpers::create_order( $product );
		$hooks = Hooks::get_instance();
		try {
			$hooks->maybe_save_paid_date( $order->get_id(), $order );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_save_order_items_props() {
		$product = TestHelpers::create_atum_simple_product();
		$order = TestHelpers::create_order( $product );
		$hooks = Hooks::get_instance();
		try {
			$hooks->save_order_items_props( $order->get_id(), false );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_save_added_order_items_props() {
		$product = TestHelpers::create_atum_simple_product();
		$order = TestHelpers::create_order( $product );
		foreach( $order->get_items() as $item ) ;
		$hooks = Hooks::get_instance();
		try {
			$hooks->save_added_order_items_props( [ $item->get_id() => $item ], $order );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_maybe_save_order_items_props() {
		$product = TestHelpers::create_atum_simple_product();
		$order = TestHelpers::create_order( $product );
		$hooks = Hooks::get_instance();
		try {
			$hooks->maybe_save_order_items_props( $order->get_id() );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_before_delete_order_item() {
		$product = TestHelpers::create_atum_simple_product();
		$order = TestHelpers::create_order( $product );
		foreach( $order->get_items() as $item ) ;
		$hooks = Hooks::get_instance();
		try {
			$hooks->before_delete_order_item( $item->get_id() );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_after_delete_order_item() {
		$product = TestHelpers::create_atum_simple_product();
		$order = TestHelpers::create_order( $product );
		foreach( $order->get_items() as $item ) ;
		$hooks = Hooks::get_instance();
		try {
			$hooks->after_delete_order_item( $item->get_id() );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_before_delete_product() {
		$product = TestHelpers::create_atum_simple_product();
		$hooks = Hooks::get_instance();
		try {
			$hooks->before_delete_product( $product->get_id() );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_save_variation_atum_data() {
		$product = TestHelpers::create_variation_product( TRUE );
		$hooks = Hooks::get_instance();
		try {
			$hooks->save_variation_atum_data( $product->get_id() );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_clean_up_update_date() {
		$product = TestHelpers::create_atum_simple_product();
		$order = TestHelpers::create_order( $product );
		$hooks = Hooks::get_instance();
		try {
			$hooks->clean_up_update_date( $order, FALSE );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_update_atum_calc_fields() {
		$product = TestHelpers::create_atum_simple_product();
		$hooks = Hooks::get_instance();
		$hooks->update_atum_calc_fields( $product, false );
		$this->expectNotToPerformAssertions();
	}

	public function test_duplicate_product() {
		$product = TestHelpers::create_atum_simple_product();
		$duplicated = TestHelpers::create_product();
		$hooks = Hooks::get_instance();
		$hooks->duplicate_product( $duplicated, $product, FALSE );
		$this->expectNotToPerformAssertions();
	}

}
