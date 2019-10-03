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
		$this->assertEquals( 10, TestHelpers::has_action( 'updated_option', array( Hooks::class, 'rebuild_wc_stock_status_on_disable' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_order_status_completed', array( Hooks::class, 'maybe_save_paid_date' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_delete_product', array( Hooks::class, 'after_delete_product' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_delete_product_variation', array( Hooks::class, 'after_delete_product' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'product_variation_linked', array( Hooks::class, 'save_variation_atum_data' ) ) );
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

		$this->assertEquals( PHP_INT_MAX, TestHelpers::has_action( 'woocommerce_process_shop_order_meta', array( Hooks::class, 'save_order_items_props' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'trashed_post', array( Hooks::class, 'maybe_save_order_items_props' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'untrashed_post', array( Hooks::class, 'maybe_save_order_items_props' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'woocommerce_after_order_object_save', array( Hooks::class, 'clean_up_update_date' ) ) );
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

	//public function test_set_wc_products_list_stock_status() {}

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
		$product = TestHelpers::create_atum_product();
		ob_start();
		$hooks->wc_order_add_location_column_value( $product, false, false );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter( 'td.item_location' )->count() );
		$this->assertEquals( 1, $html->filter( 'div.view' )->count() );
	}

	//public function test_stock_decimals() {}

	//public function test_stock_quantity_input_atts() {}

	public function test_add_to_cart_message() {
		$hooks = Hooks::get_instance();
		$msg = $hooks->add_to_cart_message( 'Foo testing message', [ 5 => 1, 32 => 5, 10 => 2 ] );
		$this->assertGreaterThan( 0, strpos( $msg, 'have been added to your cart' ) );
	}

	//public function test_rebuild_wc_stock_status_on_disable() {}

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

	//public function test_get_custom_stock_threshold() {}

	//public function test_maybe_change_stock_threshold() {}

	//public function test_maybe_change_variation_stock_status() {}

}
