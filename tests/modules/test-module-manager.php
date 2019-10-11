<?php
/**
 * Class ModulesManagerTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use TestHelpers\TestHelpers;
use Atum\Modules\ModuleManager;

/**
 * Sample test case.
 */
class ModulesManagerTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_instance() {
		$this->assertInstanceOf( ModuleManager::class, ModuleManager::get_instance() );
	}

	public function test_add_settings_tab() {
		$obj = ModuleManager::get_instance();
		$data = $obj->add_settings_tab([]);
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'module_manager', $data );
		$this->assertEquals( 'Module Manager', $data['module_manager']['sections']['module_manager'] );
	}

	/**
	 * @dataProvider provideModules
	 */
	public function test_add_settings_defaults( $module ) {
		$obj = ModuleManager::get_instance();
		$data = $obj->add_settings_defaults([]);
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( $module . '_module', $data );
		$this->assertArrayHasKey( 'section', $data[ $module . '_module' ] );
		$this->assertEquals( 'yes', $data[ $module . '_module' ]['default'] );
	}

	/**
	 * @dataProvider provideModules
	 */
	public function test_get_module_status( $module ) {
		$obj = ModuleManager::get_instance();
		$data = $obj->get_module_status( $module );
		$this->assertEquals( 'yes', $data );
	}

	/**
	 * @dataProvider provideModules
	 */
	public function test_is_module_active( $module ) {
		$obj = ModuleManager::get_instance();
		$data = $obj->is_module_active( $module );
		$this->assertTrue( $data );
	}


	public function provideModules() {
		return [
			[ 'dashboard' ],
			[ 'stock_central' ],
			[ 'inventory_logs' ],
			[ 'purchase_orders' ],
			[ 'data_export' ],
			[ 'visual_settings' ],
		];
	}
}