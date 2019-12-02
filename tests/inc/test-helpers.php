<?php
/**
 * Class HelpersTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;


/**
 * Sample test case.
 */
class HelpersTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_get_term_ids_by_slug() {
		$cat = wp_insert_category( [
			'cat_ID'               => 0,
			'taxonomy'             => 'atum_location',
			'cat_name'             => 'foo',
			'category_description' => '',
			'category_nicename'    => 'Foo',
			'category_parent'      => '',
		], false );
		$data = Helpers::get_term_ids_by_slug( [ 'Foo' ], 'atum_location' );
		$this->assertTrue( in_array( $cat, $data ) );
	}

	public function test_add_help_tab() {
		set_current_screen( 'atum_inventory_log' );
		$help_tabs = array(
			array(
				'name'  => 'columns',
				'title' => __( 'Columns', ATUM_TEXT_DOMAIN ),
			),
		);

		ob_start();
		Helpers::add_help_tab( $help_tabs, new \Atum\InventoryLogs\InventoryLogs() );
		$data = ob_get_clean();
		$this->assertIsString( $data );
	}

	public function test_array_to_data() {
		$array = [ 'foo' => 'far' ];
		$data  = Helpers::array_to_data( $array );
		$this->assertIsString( $data );
		$this->assertGreaterThanOrEqual( 0, strpos( $data, 'foo' ) );
		$this->assertGreaterThanOrEqual( 0, strpos( $data, 'far' ) );
	}

	public function test_atum_field_input_addon() {
		ob_start();
		Helpers::atum_field_input_addon();
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('span.input-group-prepend')->count() );
		$this->assertEquals( 1, $html->filter('span.input-group-text')->count() );
	}

	//public function test_date_format() {}

	public function test_decode_json_string() {
		$array = [ 'foo' => 'test' ];
		$json  = json_encode( $array );
		$data  = Helpers::decode_json_string( $json );
		$this->assertEquals( $array, $data );
	}

	public function test_get_data_att() {
		$a = 'foo';
		$v = '200';
		$this->assertEquals( ' data-' . $a . '="' . $v .'"', Helpers::get_data_att( $a, $v ));
	}

	public function test_get_all_products() {
		TestHelpers::create_product();
		$data = Helpers::get_all_products();
		$this->assertIsArray( $data );
		foreach ( $data as $d )
			$this->assertIsInt( $d );
	}

	public function test_get_atum_product_class() {
		$data = Helpers::get_atum_product_class( 'product' );
		$this->assertEquals( '\Atum\Models\Products\AtumProductSimple', $data );
	}

	public function test_sanitize_psr4_class_name() {
		$expected = 'FooClassName';
		$data = Helpers::sanitize_psr4_class_name( 'FooClassName' );
		$this->assertEquals( $expected, $data );
		$data = Helpers::sanitize_psr4_class_name( 'foo_class_name' );
		$this->assertEquals( $expected, $data );
		$data = Helpers::sanitize_psr4_class_name( 'foo-class-name' );
		$this->assertEquals( $expected, $data );
	}

	public function test_get_orders() {
		TestHelpers::create_order();
		$data = Helpers::get_orders();
		$this->assertIsArray( $data );
		foreach ( $data as $dt ) {
			$this->assertInstanceOf( WC_Order::class, $dt );
		}
	}

	//public function test_get_sold_last_days() {}

	/*
	public function test_get_product_lost_sales() {
		Globals::enable_atum_product_data_models();
		$product = Helpers::get_atum_product( TestHelpers::create_product() );
		$data = Helpers::get_product_lost_sales( $product );
		var_dump($data);

	}*/

	/*
	public function test_get_product_out_stock_days() {
		$product = Helpers::get_atum_product( TestHelpers::create_product() );
		$data = Helpers::get_product_out_stock_days( $product );
		print_r($data);
	}*/

	//public function test_get_log_item_qty() {}

	public function test_get_option() {
		$data = Helpers::get_option( 'foo', 'test' );
		$this->assertEquals( 'test', $data );
	}

	public function test_get_options() {
		$data = Helpers::get_options();
		$this->assertIsArray( $data );
	}

	public function test_get_product_setting() {
		$product = Helpers::get_atum_product( TestHelpers::create_product() );
		$data = Helpers::get_product_setting( $product, 'foo', 'test' );
		$this->assertEquals( 'test', $data );
	}

	public function test_get_sold_last_days_option() {
		$data = Helpers::get_sold_last_days_option();
		$this->assertIsInt( $data );
	}

	//public function test_get_unmanaged_products() {}

	public function test_format_price() {
		$price = 50.99;
		$data = Helpers::format_price( $price );
		$this->assertGreaterThanOrEqual( 0, strpos( $data, chr( $price ) ) );
	}

	//public function test_load_view() {}

	//public function test_load_view_to_string() {}

	//public function test_locate_template() {}

	public function test_is_atum_controlling_stock() {
		$product = Helpers::get_atum_product( TestHelpers::create_product() );
		$this->assertIsBool( Helpers::is_atum_controlling_stock( $product ) );
		Helpers::update_atum_control( $product, 'enable');
		$this->assertTrue( Helpers::is_atum_controlling_stock( $product ) );
	}

	public function test_is_inheritable_type() {
		$this->assertIsBool( Helpers::is_inheritable_type( 'product' ) );
	}

	public function test_is_child_type() {
		$this->assertIsBool( Helpers::is_child_type( 'product' ) );
	}

	//public function test_is_plugin_installed() {}

	//public function test_display_notice() {}

	//public function test_dismiss_notice() {}

	//public function test_get_dismissed_notices() {}

	//public function test_is_notice_dismissed() {}

	public function test_trim_input() {
		$this->assertEquals( 'foo', Helpers::trim_input(' foo ') );
	}

	public function test_product_types_dropdown() {
		$data = Helpers::product_types_dropdown();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('select.dropdown_product_type')->count() );
	}

	/*
	public function test_suppliers_dropdown() {
		$data = Helpers::suppliers_dropdown();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter('select.dropdown_supplier')->count() );
	}*/

	//public function test_get_logs() {}

	public function test_get_order_items() {
		$order = TestHelpers::create_order();
		$data = Helpers::get_order_items( $order->get_id() );
		$this->assertIsArray( $data );
	}

	public function test_get_atum_order_model() {
		Globals::enable_atum_product_data_models();
		$po = new PurchaseOrders();
		$po->register_post_type();

		// Purchase Order.
		$pid = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		$order = Helpers::get_atum_order_model( $pid );

		$this->assertInstanceOf( Atum\PurchaseOrders\Models\PurchaseOrder::class, $order );
	}

	public function test_get_product_inbound_stock()  {
		$po = new PurchaseOrders();
		$po->register_post_type();

		// Post
		$pid = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		// Product
		$product = TestHelpers::create_atum_simple_product();
		$product->set_inbound_stock( 25 );
		// Purchase Order.
		$order = Helpers::get_atum_order_model( $pid );
		$order->add_product( $product );
		$order->save_meta( array(
			'_status'                    => 'atum_ordered',
			'_date_created'              => date( 'Y-m-d H:i:s', current_time( 'timestamp', TRUE ) ),
			Suppliers::SUPPLIER_META_KEY => '',
			'_multiple_suppliers'        => 'no',
			'_expected_at_location_date' => date( 'Y-m-d H:i:s', current_time( 'timestamp', TRUE ) ),
		) );

		$data = Helpers::get_product_inbound_stock( $product );
		$this->assertEquals( 25, $data );
	}

	public function test_get_product_stock_on_hold()  {
		$po = new PurchaseOrders();
		$po->register_post_type();

		// Post
		$pid = $this->factory()->post->create( array(
			'post_title'  => 'Foo',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => 'publish',
		) );
		// Product
		$product = TestHelpers::create_atum_simple_product();
		$product->set_stock_on_hold( 25 );
		// Purchase Order.
		$order = Helpers::get_atum_order_model( $pid );
		$order->add_product( $product );
		$order->save_meta( array(
			'_status'                    => 'atum_ordered',
			'_date_created'              => date( 'Y-m-d H:i:s', current_time( 'timestamp', TRUE ) ),
			Suppliers::SUPPLIER_META_KEY => '',
			'_multiple_suppliers'        => 'no',
			'_expected_at_location_date' => date( 'Y-m-d H:i:s', current_time( 'timestamp', TRUE ) ),
		) );

		$data = Helpers::get_product_stock_on_hold( $product );
		$this->assertEquals( 25, $data );
	}

	public function test_get_atum_order_post_type_statuses() {
		$data = Helpers::get_atum_order_post_type_statuses( 'atum_purchase_order' );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'atum_pending', $data );
		$this->assertArrayHasKey( 'atum_ordered', $data );
		$this->assertArrayHasKey( 'atum_onthewayin', $data );
		$this->assertArrayHasKey( 'atum_receiving', $data );
		$this->assertArrayHasKey( 'atum_received', $data );
	}

	public function test_get_atum_product() {
		$product = TestHelpers::create_atum_simple_product();
		$data = Helpers::get_atum_product( $product->get_id() );
		$this->assertInstanceOf( \Atum\Models\Products\AtumProductSimple::class, $data );
	}

	public function test_update_product_data() {
		$product = TestHelpers::create_atum_simple_product();
		$product->set_manage_stock( 'yes' );
		$product->save();
		$product_id = $product->get_id();
		$product_data = array(
			'stock'         => 100,
			'regular_price' => 25,
			'sale_price'    => 19.99,
		);

		Helpers::update_product_data( $product_id, $product_data );

		//Reinstance
		$product = Helpers::get_atum_product( $product_id );
		$this->assertEquals( 100, $product->get_stock_quantity() );
		$this->assertEquals( 25, $product->get_regular_price() );
		$this->assertEquals( 19.99, $product->get_sale_price() );
	}

	public function test_get_support_buttons() {
		$data = Helpers::get_support_buttons();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'support_link', $data );
		$this->assertArrayHasKey( 'support_button_text', $data );
	}

	public function test_force_rebuild_stock_status() {
		$product = TestHelpers::create_atum_simple_product();
		$predata = [
			'stock'  => $product->get_stock_quantity(),
			'manage' => $product->get_manage_stock(),
			'status' => $product->get_stock_status(),
			'osthre' => $product->get_out_stock_threshold(),
		];
		Helpers::force_rebuild_stock_status( $product, true );
		$postdata = [
			'stock'  => $product->get_stock_quantity(),
			'manage' => $product->get_manage_stock(),
			'status' => $product->get_stock_status(),
			'osthre' => $product->get_out_stock_threshold(),
		];
		$this->assertEquals( $predata, $postdata );
	}

	public function test_is_any_out_stock_threshold_set() {
		$this->assertEquals( 0, Helpers::is_any_out_stock_threshold_set() );
		$product = TestHelpers::create_atum_simple_product();
		$product->set_out_stock_threshold( 5 );
		$product->save();
		$this->assertGreaterThan( 0, Helpers::is_any_out_stock_threshold_set() );
	}

	public function test_in_multi_array() {
		$arr = [[[[[ 'foo' ]]]]];
		$this->assertTrue( Helpers::in_multi_array( 'foo', $arr ));
	}

	public function test_array_keys_exist() {
		$req = [ 'foo', 'test' ];
		$data = [ 'data' => [], 'foo' => 1, 'test' => 7, 'name' => 'xo' ];
		$this->assertTrue( Helpers::array_keys_exist( $req, $data ) );
	}

	public function test_array_group_by() {
		$key = 'foo';
		$data = [
			[ 'data' => 'yes', 'foo' => 1, 'name' => 'xo'],
			[ 'data' => 'yes', 'foo' => 2, 'name' => 'kk'],
			[ 'data' => 'no',  'foo' => 3, 'name' => 'ma'],
		];
		$result = Helpers::array_group_by( $data, $key );
		$this->assertIsArray( $result );
		$this->assertEquals( 3, count( $result ) );
	}

	/**
	 * @dataProvider provideColors
	 */
	public function test_validate_color( $color = '#000000' ) {
		$this->assertTrue( Helpers::validate_color( $color ));
	}

	public function test_unique_multidim_array() {
		$key = 'one';
		$data = [
			[ 'data' => 'yes', 'foo' => 1, 'name' => 'xo', 'one' => 1 ],
			[ 'data' => 'yes', 'foo' => 2, 'name' => 'kk', 'one' => 1 ],
			[ 'data' => 'no',  'foo' => 3, 'name' => 'ma', 'one' => 1 ],
		];
		$result = Helpers::unique_multidim_array( $data, $key );
		$this->assertIsArray( $result );
	}

	public function test_get_input_step() {
		$this->assertIsNumeric( Helpers::get_input_step() );
	}

	public function test_read_parent_product_type() {
		$product = TestHelpers::create_variation_product( true );
		$data = Helpers::read_parent_product_type( $product->get_id() );
		$this->assertEquals( 'variable', $data );
	}

	public function test_atum_user_meta() {
		Helpers::set_atum_user_meta( 'foo', 7, 1 );
		$this->assertEquals( 7, Helpers::get_atum_user_meta( 'foo', 1) );
	}

	public function test_get_wc_time() {
		$data = Helpers::get_wc_time(5);
		$this->assertInstanceOf( WC_DateTime::class, $data );
	}

	public function test_image_placeholder() {
		$data = Helpers::image_placeholder( false, false, false );
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter( 'span.thumb-placeholder' )->count() );
	}

	public function test_show_marketing_popup() {
		$this->assertIsBool( Helpers::show_marketing_popup() );
	}

	public function test_get_atum_icon_type() {
		$product = TestHelpers::create_atum_simple_product();
		$data = Helpers::get_atum_icon_type( $product );
		$this->assertEquals( 'atum-icon atmi-wc-simple', $data );
	}

	public function test_get_bundle_items() {
		include dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . 'woocommerce-product-bundles'.
		        DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'class-wc-pb-db.php';
		$data = Helpers::get_bundle_items( [] );
		$this->assertIsArray( $data );
	}

	//public function test_is_product_data_outdated() {}

	public function test_is_atum_ajax() {
		$this->assertFalse( Helpers::is_atum_ajax() );
	}

	public function test_get_visual_mode_style() {
		$data = Helpers::get_visual_mode_style();
		$this->assertIsString( $data );
		$this->assertGreaterThan( 200, strlen( $data ) );
	}

	/**
	 * @param string $color_name
	 *
	 * @dataProvider provideColorName
	 */
	public function test_get_color_value( $color_name = '' ) {
		$this->assertTrue( Helpers::validate_color( Helpers::get_color_value( $color_name ) ) );
	}

	/**
	 * @param $type
	 * @dataProvider provideType
	 */
	public function DISABLEDtest_update_order_item_product_data( $type ) {
		$product = TestHelpers::create_atum_simple_product();
		$id = $product->get_id();
		switch ( $type ) {
			case 2:
				$inbound_stock = $product->get_inbound_stock();
				break;
			case 3:
				$log = Helpers::get_log_item_qty();
				break;
			default:
				break;
		}

		$data = Helpers::update_order_item_product_data( $product, $type );
		var_dump($data);
		//Reinstance
		$product = Helpers::get_atum_product( $id );
		switch ( $type ) {
			case 2:
				$this->assertNotEquals( $inbound_stock, $product->get_inbound_stock() );
				break;
			case 3:
				break;
			default:
				break;
		}
	}

	public function test_get_option_group_hidden_classes() {
		$classes = Helpers::get_option_group_hidden_classes();
		$this->assertTrue( in_array( 'hide_if_external', $classes ) );
	}

	public function DISABLEDtest_duplicate_atum_product() {
		$p = TestHelpers::create_atum_simple_product();
		$p2 = Helpers::duplicate_atum_product( $p->get_id(), 0 );
		var_dump( $p2 );
	}

	public function test_is_rest_request() {
		$this->assertFalse( Helpers::is_rest_request() );
	}

	// Data Providers
	public function provideColors() {
		$colors[] = [ 'transparent' ];
		for($i=0;$i<100;$i++)
			$colors[] = [ 'rgba('.rand(0,255).','.rand(0,255).','.rand(0,255).',.'.rand(0,99).')' ];
		for($i=0;$i<100;$i++)
			$colors[] = [ 'rgb('.rand(0,255).','.rand(0,255).','.rand(0,255).')' ];
		for($i=0;$i<100;$i++)
			$colors[] = [ '#'. substr('0'.dechex(rand(0,255)),-2).substr('0'.dechex(rand(0,255)),-2).substr('0'.dechex(rand(0,255)),-2) ];

		return $colors;
	}

	public function provideColorName() {
		$data = \Atum\Components\AtumColors::get_instance()->add_settings_defaults( [] );
		foreach($data as $k => $v) {
			if( 'theme'===$k ) continue;
			$names[] = [ $k ];
		}
		return $names;
	}

	public function provideType() {
		return [ [1], [2], [3], ];
	}

}
