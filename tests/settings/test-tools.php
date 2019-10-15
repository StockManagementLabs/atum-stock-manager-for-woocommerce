<?php
/**
 * Class ToolsTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Settings\Tools;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class ToolsTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_instance() {
		$this->assertInstanceOf( Tools::class, Tools::get_instance() );
		//$this->assertEquals( 999, TestHelpers::has_action( 'atum/settings/tabs', array( Tools::class, 'add_settings_tab' ) ) );
		//$this->assertEquals( 10, TestHelpers::has_action( 'atum/settings/defaults', array( Tools::class, 'add_settings_defaults' ) ) );
	}

	public function test_add_settings_tab() {
		$obj = Tools::get_instance();
		$data = $obj->add_settings_tab( [] );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'tools', $data );
		$this->assertArrayHasKey( 'label', $data['tools'] );
		$this->assertArrayHasKey( 'sections', $data['tools'] );
	}

	public function test_add_settings_defaults() {
		$obj = Tools::get_instance();
		$data = $obj->add_settings_defaults( [] );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'update_manage_stock', $data );
		$this->assertArrayHasKey( 'update_control_stock', $data );
		$this->assertArrayHasKey( 'clear_out_stock_threshold', $data );
		$this->assertEquals( 'tools', $data['update_manage_stock']['section'] );
		$this->assertEquals( 'tools', $data['update_control_stock']['section'] );
		$this->assertEquals( 'tools', $data['clear_out_stock_threshold']['section'] );
		$this->assertEquals( 'script_runner', $data['update_manage_stock']['type'] );
		$this->assertEquals( 'script_runner', $data['update_control_stock']['type'] );
		$this->assertEquals( 'script_runner', $data['clear_out_stock_threshold']['type'] );
	}

}