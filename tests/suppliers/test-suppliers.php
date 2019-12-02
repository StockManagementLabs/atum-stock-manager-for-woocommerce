<?php
/**
 * Class SuppliersTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Inc\Helpers;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class SuppliersTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_instance() {
		wp_set_current_user( 1 );
		set_current_screen( 'atum-stock-central' );

		$obj = Suppliers::get_instance();
		$this->assertInstanceOf( Suppliers::class, $obj );

		$this->assertEquals( 10, TestHelpers::has_action( 'init', array( $obj, 'register_post_type' ) ) );
		$this->assertEquals( 12, TestHelpers::has_action( 'atum/admin/top_bar/menu_items', array( $obj, 'add_admin_bar_link' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/admin/menu_items_order', array( $obj, 'add_item_order' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'manage_' . Suppliers::POST_TYPE . '_posts_columns', array( $obj, 'add_columns' ) ) );
		$this->assertEquals( 2, TestHelpers::has_action( 'manage_' . Suppliers::POST_TYPE . '_posts_custom_column', array( $obj, 'render_columns' ) ) );
		$this->assertEquals( 30, TestHelpers::has_action( 'add_meta_boxes_' . Suppliers::POST_TYPE, array( $obj, 'add_meta_boxes' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'save_post_' . Suppliers::POST_TYPE, array( $obj, 'save_meta_boxes' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'admin_enqueue_scripts', array( $obj, 'enqueue_scripts' ) ) );
	}

	public function test_register_post_type() {
		$obj = Suppliers::get_instance();
		try {
			$obj->register_post_type();
			$this->assertTrue( TRUE );
		} catch ( Exception $e ) {
			$this->expectExceptionMessage( $e->getMessage() );
		}
	}

	/**
	 * @param $column
	 *
	 * @dataProvider provideColumn
	 */
	public function test_add_columns( $column ) {
		$obj     = Suppliers::get_instance();
		$columns = [ 'date' => 'foo' ];
		$data    = $obj->add_columns( $columns );

		$this->assertIsArray( $data );
		$this->assertArrayNotHasKey( 'date', $data );
		$this->assertArrayHasKey( $column, $data );
	}

	/**
	 * @param $column
	 *
	 * @dataProvider provideColumn
	 */
	public function test_render_columns( $column ) {
		global $post;
		$key = $val = '';
		$obj = Suppliers::get_instance();
		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		switch( $column ) {
			case 'company_code':  $key = '_supplier_details_code';        $val = '01234';     break;
			case 'company_phone': $key = '_supplier_details_phone';       $val = '987654321'; break;
			case 'assigned_to':   $key = '_default_settings_assigned_to'; $val = 1;           break;
			case 'location':      $key = '_default_settings_location';    $val = 'Here';      break;
		}
		add_post_meta( $post->ID, $key, $val );

		ob_start();
		$obj->render_columns( $column );
		$data = ob_get_clean();

		if( 'assigned_to' === $column )
			$this->assertEquals( '<a href="" target="_blank">admin</a>', $data );
		else
			$this->assertEquals( $val, $data );
	}

	public function test_add_meta_boxes() {
		$obj = Suppliers::get_instance();
		try {
			$obj->add_meta_boxes();
			$this->assertTrue( TRUE );
		} catch ( Exception $e ) {
			$this->expectExceptionMessage( $e->getMessage() );
		}
	}

	public function test_show_supplier_details_meta_box() {
		$obj  = Suppliers::get_instance();
		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		$obj->add_meta_boxes();
		ob_start();
		$obj->show_supplier_details_meta_box( $post );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter( 'div.atum-meta-box.supplier' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#company_code' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#tax_number' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#company_phone' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#company_fax' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#website' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#ordering_url' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#general_email' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#ordering_email' )->count() );
		$this->assertEquals( 1, $html->filter( 'textarea#description' )->count() );
	}

	public function test_show_billing_information_meta_box() {
		$obj  = Suppliers::get_instance();
		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		$obj->add_meta_boxes();
		ob_start();
		$obj->show_billing_information_meta_box( $post );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter( 'div.atum-meta-box.supplier' )->count() );
		$this->assertEquals( 1, $html->filter( 'select#currency' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#address' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#city' )->count() );
		$this->assertEquals( 1, $html->filter( 'select#country' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#state' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#zip_code' )->count() );
	}

	public function test_show_default_settings_meta_box() {
		$obj  = Suppliers::get_instance();
		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		$obj->add_meta_boxes();
		ob_start();
		$obj->show_default_settings_meta_box( $post );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter( 'div.atum-meta-box.supplier' )->count() );
		$this->assertEquals( 1, $html->filter( 'select#assigned_to' )->count() );
		$this->assertEquals( 1, $html->filter( 'input#location' )->count() );
	}

	public function test_save_meta_boxes() {
		$_POST['supplier_details'] = [
			'code' => '01234',
			'tax_number' => '5',
			'phone' => '987654321',
			'fax' => '999999999',
			'website' => 'http://site.foo',
			'ordering_url' => 'http://site.foo/order',
			'general_email' => 'general@site.foo',
			'ordering_email' => 'client@site.foo',
			'description' => 'Foo description',
		];
		$_POST['billing_information'] = [
			'currency' => 'EUR',
			'address' => 'Foo address',
			'city' => 'Sim city',
			'country' => 'ES',
			'state' => 'Foo',
			'zip_code' => '12345',
		];
		$_POST['default_settings'] = [
			'assigned_to' => 1,
			'location' => 'Here',
		];

		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );

		$obj = Suppliers::get_instance();
		$obj->save_meta_boxes( $post->ID );

		foreach ( [ 'supplier_details', 'billing_information', 'default_settings' ] as $metabox_key ) {
			foreach ( array_map( 'esc_attr', $_POST[ $metabox_key ] ) as $meta_key => $meta_value ) {
				$this->assertEquals( $meta_value, get_post_meta( $post->ID, "_{$metabox_key}_{$meta_key}", true ) );
			}
		}
	}

	public function test_enqueue_scripts() {
		global $post_type;
		$post_type = Suppliers::POST_TYPE;

		$obj = Suppliers::get_instance();
		$obj->enqueue_scripts( 'edit.php' );

		$this->assertTrue( wp_style_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_script_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_style_is( 'atum-suppliers', 'registered' ) );
		$this->assertTrue( wp_script_is( 'atum-suppliers-table', 'registered' ) );
	}

	public function test_get_supplier_products() {
		$pos = new PurchaseOrders();
		$pos->register_post_type();

		$product = TestHelpers::create_atum_simple_product();
		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo post',
			'post_type'   => PurchaseOrders::POST_TYPE,
			'post_status' => ATUM_PREFIX . 'pending',
			'log_type'    => 'other',
		] );
		$supplier = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		$order = Helpers::get_atum_order_model( $post->ID );
		$order->add_product( $product );

		update_post_meta( $post->ID, '_supplier', $supplier->ID );

		$data = Suppliers::get_supplier_products( $supplier->ID );
		$this->assertIsArray( $data );
	}

	public function test_add_admin_bar_link() {
		$obj  = Suppliers::get_instance();
		$data = $obj->add_admin_bar_link( [] );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'suppliers', $data );
		$this->assertArrayHasKey( 'slug', $data['suppliers'] );
		$this->assertEquals( ATUM_TEXT_DOMAIN . '-suppliers', $data['suppliers']['slug'] );
	}

	public function test_add_item_order() {
		$obj  = Suppliers::get_instance();
		$data = $obj->add_item_order( [] );

		$this->assertIsArray( $data );
		$this->assertEquals( 'edit.php?post_type=' . Suppliers::POST_TYPE, $data[0]['slug'] );
		$this->assertEquals( Suppliers::MENU_ORDER, $data[0]['menu_order'] );
	}

	public function DISABLEDtest_get_product_id_by_supplier_sku() {
		$sku = 'foosku';
		$product = TestHelpers::create_atum_simple_product();
		$product->set_sku( $sku );
		$supplier = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo supplier',
			'post_type'   => 'atum_supplier',
			'post_status' => 'published',
			'log_type'    => 'other',
		] );
		add_post_meta( $supplier->ID, Suppliers::SUPPLIER_SKU_META_KEY, $sku );
		$data = Suppliers::get_product_id_by_supplier_sku( $product->get_id(), $sku );
		print_r($data);
	}

	public function provideColumn() {
		return [
			[ 'company_code' ],
			[ 'company_phone' ],
			[ 'assigned_to' ],
			[ 'location' ],
		];
	}

}