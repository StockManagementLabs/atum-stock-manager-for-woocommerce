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
				$this->assertEquals( 1, $html->filter('small.includes_tax')->count() );
				break;
			case 'type':
				$this->assertEquals( 1, $html->filter('small strong')->count() );
				break;
			case 'wc_order':
				$this->assertEquals( 1, $html->filter('a')->count() );
				break;
		}
	}


	public function provideColumns() {
		return [
			[ 'status' ],
			[ 'atum_order_title' ],
			[ 'date' ],
			[ 'notes' ],
			[ 'total' ],
			[ 'type' ],
			[ 'wc_order' ],
		];
	}

}
