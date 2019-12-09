<?php
/**
 * Class InventoryLogsTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\InventoryLogs\InventoryLogs;
use Atum\Inc\Helpers;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class InventoryLogsTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( InventoryLogs::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		wp_set_current_user( 1 );
		$obj = new InventoryLogs();

		$this->assertEquals( 10, TestHelpers::has_action( 'atum/admin/menu_items_order', array( InventoryLogs::class, 'add_item_order' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/admin/top_bar/menu_items', array( InventoryLogs::class, 'add_admin_bar_link' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'restrict_manage_posts', array( InventoryLogs::class, 'add_log_filters' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'load-edit.php', array( InventoryLogs::class, 'add_help_tab' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/' . InventoryLogs::POST_TYPE . '/search_results', array( InventoryLogs::class, 'il_search' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/' . InventoryLogs:: POST_TYPE . '/search_fields', array( InventoryLogs::class, 'search_fields' ) ) );
	}

	public function test_show_data_meta_box() {
		$obj = new InventoryLogs();
		$post = $this->factory()->post->create_and_get( [
			'post_title' => 'Foo post',
			'post_type'  => InventoryLogs::POST_TYPE,
			'post_status' => ATUM_PREFIX . 'publish',
		] );
		ob_start();
		$obj->show_data_meta_box( $post );
		$data = ob_get_clean();

		$html = new Crawler( $data );
		$this->assertEquals( 'Foo post', $html->filter('input[name=post_title]')->attr('value') );
	}

	public function test_save_meta_boxes() {
		wp_set_current_user( 1 );
		$obj = new InventoryLogs();
		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo post',
			'post_type'   => InventoryLogs::POST_TYPE,
			'post_status' => ATUM_PREFIX . 'publish',
		] );
		$_POST = [
			'atum_order_type'  => InventoryLogs::POST_TYPE,
			'status'           => ATUM_PREFIX . 'pending',
			'atum_meta_nonce'  => wp_create_nonce( 'atum_save_meta_data' ),
			'wc_order'         => $post->ID,
			'custom_name'      => 'Foo custom name',
			'description'      => 'Some description',
			'shipping_company' => 0,
		];

		$obj->save_meta_boxes( $post->ID );

		$order = Helpers::get_atum_order_model( $post->ID );
		$this->assertEquals( 'Some description', $order->get_description() );
		$this->assertEquals( ATUM_PREFIX . 'pending', $order->get_status() );
	}

	public function test_add_log_filters() {
		//Nothing to test
		$this->assertTrue( true );
	}

	public function test_add_columns() {
		$obj = new InventoryLogs();
		$data = $obj->add_columns( [ 'cb' => 'foo' ] );
		$this->assertArrayHasKey( 'cb', $data );
		$this->assertArrayHasKey( 'atum_order_title', $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'type', $data );
		$this->assertArrayHasKey( 'date', $data );
		$this->assertArrayHasKey( 'wc_order', $data );
		$this->assertArrayHasKey( 'total', $data );
		$this->assertArrayHasKey( 'actions', $data );
	}

	/**
	 * @dataProvider provideColumns
	 */
	public function test_render_columns( $column ) {
		global $post;
		$obj = new InventoryLogs();
		$obj->register_post_type();
		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo post',
			'post_type'   => InventoryLogs::POST_TYPE,
			'post_status' => ATUM_PREFIX . 'pending',
			'log_type'    => 'other',
		] );
		$_POST = [
			'_type'            => 'other',
			'atum_order_type'  => InventoryLogs::POST_TYPE,
			'status'           => ATUM_PREFIX . 'pending',
			'atum_meta_nonce'  => wp_create_nonce( 'atum_save_meta_data' ),
			'wc_order'         => 555,
			'custom_name'      => 'Foo custom name',
			'description'      => 'Some description',
			'shipping_company' => 0,
		];
		$obj->save_meta_boxes( $post->ID );

		ob_start();
		$obj->render_columns( $column );
		$data = ob_get_clean();

		$html = new Crawler( $data );

		switch ($column) {
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
			case 'type':
				$this->assertEquals( 1, $html->filter('small strong')->count() );
				break;
			case 'wc_order':
				if( '&ndash;' === $data )
					$this->assertEquals( '&ndash;', $data );
				else
					$this->assertEquals( 1, $html->filter('a')->count() );
				break;
		}
	}

	public function test_bulk_post_updated_messages() {
		$obj = new InventoryLogs();
		$counts = [
			'updated'   => 10,
			'locked'    => 10,
			'deleted'   => 10,
			'trashed'   => 10,
			'untrashed' => 10,
		];
		$data = $obj->bulk_post_updated_messages( [], $counts );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( InventoryLogs::POST_TYPE, $data );
		foreach ( $counts as $c => $j )
			$this->assertArrayHasKey( $c, $data[ InventoryLogs::POST_TYPE ] );
	}

	public function test_post_updated_messages() {
		global $post;
		$post = $this->factory()->post->create_and_get( [
			'post_title'  => 'Foo post',
			'post_type'   => InventoryLogs::POST_TYPE,
			'post_status' => ATUM_PREFIX . 'pending',
			'log_type'    => 'other',
		] );
		$obj = new InventoryLogs();
		$data = $obj->post_updated_messages( [] );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( InventoryLogs::POST_TYPE, $data );
		$this->assertEquals( 12, count($data[ InventoryLogs::POST_TYPE ] ) );
		foreach ( $data[ InventoryLogs::POST_TYPE ] as $k => $m ) {
			if( 5 === $k)
				$this->assertIsBool( $m );
			else
				$this->assertIsString( $m );
		}
	}

	public function test_add_admin_bar_link() {
		$obj = new InventoryLogs();
		$data = $obj->add_admin_bar_link( [] );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'inventory-logs', $data );
		$this->assertArrayHasKey( 'slug', $data['inventory-logs'] );
		$this->assertArrayHasKey( 'title', $data['inventory-logs'] );
		$this->assertArrayHasKey( 'href', $data['inventory-logs'] );
		$this->assertArrayHasKey( 'menu_order', $data['inventory-logs'] );
	}

	public function test_add_item_order() {
		$obj = new InventoryLogs();
		$data = $obj->add_item_order( [] );

		$this->assertIsArray( $data );
		$this->assertEquals( 2, count( $data[ 0 ] ) );
		$this->assertArrayHasKey( 'slug', $data[ 0 ] );
		$this->assertArrayHasKey( 'menu_order', $data[ 0 ] );
	}

	public function test_add_help_tab() {
		$obj = new InventoryLogs();
		$obj->add_help_tab();
		ob_start();
		$obj->help_tabs_content( 'atum-inventory-logs', [ 'name' => 'foo' ] );
		$data = ob_get_clean();

		//Nothing to test
		$this->assertTrue( true );
	}

	public function test_help_tabs_content() {
		//Tested in previous method
		$this->assertTrue( true );
	}

	public function test_search_fields() {
		$obj = new InventoryLogs();
		$data = $obj->search_fields( [] );
		$this->assertEquals( '_order', $data[0] );
		$this->assertEquals( '_total', $data[1] );
		$this->assertEquals( '_type', $data[2] );
		$this->assertEquals( '_custom_name', $data[3] );
	}

	public function test_il_search() {
		$obj = new InventoryLogs();
		$data = $obj->il_search( [], '', [] );
		$this->assertIsArray( $data );
	}

	public function provideColumns() {
		return [
			[ 'status' ],
			[ 'atum_order_title' ],
			[ 'date' ],
			[ 'notes' ],
			[ 'total' ],
			//[ 'type' ],
			[ 'wc_order' ],
		];
	}

}
