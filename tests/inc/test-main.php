<?php
/**
 * Class MainTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Inc\Globals;
use Atum\Inc\Main;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class MainTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_instance() {
		wp_set_current_user( 1 );
		$this->assertInstanceOf( Main::class, Main::get_instance() );

		if ( is_admin() ) {
			$this->assertEquals( 1, TestHelpers::has_action( 'admin_menu', array( Main::class, 'create_menu' ) ) );
			$this->assertEquals( 10, TestHelpers::has_action( 'init', array( Main::class, 'admin_load' ) ) );
		} else {
			$this->assertFalse( TestHelpers::has_action( 'admin_menu', array( Main::class, 'create_menu' ) ) );
			$this->assertFalse( TestHelpers::has_action( 'init', array( Main::class, 'admin_load' ) ) );
		}
		$this->assertEquals( 1, TestHelpers::has_action( 'init', array( Main::class, 'pre_init' ) ) );
		$this->assertEquals( 11, TestHelpers::has_action( 'init', array( Main::class, 'init' ) ) );
		//$this->assertEquals( 10, TestHelpers::has_action( 'custom_menu_order', '__return_true' ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'menu_order', array( Main::class, 'set_menu_order' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'wp_before_admin_bar_render', array( Main::class, 'add_admin_bar_menu' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'setup_theme', array( Main::class, 'load_modules' ) ) );
	}

	public function test_pre_init() {
		//It updates private attributes that can not be tested
		$this->markTestSkipped();
	}

	public function test_init() {
		global $wp_taxonomies;
		$main = Main::get_instance();
		$main->init();

		$this->assertIsArray( $wp_taxonomies );
		$this->assertArrayHasKey( Globals::PRODUCT_LOCATION_TAXONOMY, $wp_taxonomies );
	}

	public function test_admin_load() {
		wp_set_current_user( 1 );
		$main = Main::get_instance();
		$main->admin_load();
		$this->assertEquals( 1, TestHelpers::has_action( 'admin_footer_text', array( Main::class, 'admin_footer_text' ) ) );
	}

	//public function test_load_modules() {}

	public function test_create_menu() {
		wp_set_current_user( 1 );
		global $admin_page_hooks;
		$main = Main::get_instance();
		$main->create_menu();
		$this->assertIsArray( $admin_page_hooks );
		$this->assertArrayHasKey( 'atum-dashboard', $admin_page_hooks );
		$this->assertEquals( 'atum-inventory', $admin_page_hooks['atum-dashboard'] );
	}

	public function test_set_menu_order() {
		wp_set_current_user( 1 );
		global $submenu;
		$main = Main::get_instance();
		$main->set_menu_order( [] );
		$this->assertIsArray( $submenu );
		$this->assertArrayHasKey( 'atum-dashboard', $submenu );
		$this->assertNotEmpty( $submenu['atum-dashboard'] );
	}

	/*
	public function test_add_admin_bar_menu() {
		wp_set_current_user( 1 );
		global $wp_admin_bar;
		set_current_screen( 'atum-dashboard' );
		$main = Main::get_instance();
		$main->add_admin_bar_menu();
		print_r($wp_admin_bar);
		$this->assertIsArray( $wp_admin_bar );
	}*/

	public function test_admin_footer_text() {
		wp_set_current_user( 1 );
		global $wp_admin_bar;
		set_current_screen( 'atum-dashboard' );
		$main = Main::get_instance();
		$result = $main->admin_footer_text( 'foo text' );

		$this->assertEquals( 'foo text', $result );

	}

	public function test_get_main_menu_item() {
		$data = Main::get_main_menu_item();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'callback', $data );
		$this->assertArrayHasKey( 'slug', $data );
		$this->assertEquals( 'Dashboard', $data['title'] );
		$this->assertEquals( 'atum-dashboard', $data['slug'] );
	}

}
