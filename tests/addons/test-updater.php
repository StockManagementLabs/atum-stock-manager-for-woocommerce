<?php
/**
 * Class AddonsTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Addons\Updater;
use Atum\Addons\Addons;
use Atum\Components\AtumCache;
use Atum\Inc\Helpers;
use Symfony\Component\DomCrawler\Crawler;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class UpdaterTest extends WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( Updater::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}


	public function test_instance() {
		$license_keys = $this->add_addons_keys();

		foreach ( $license_keys as $addon_name => $license_key ) {

			if ( $license_key && 'valid' === $license_key['status'] ) {

				// All the ATUM addons' names should start with 'ATUM'.
				$addon_info = Helpers::is_plugin_installed( 'ATUM ' . $addon_name, 'name', FALSE );

				if ( $addon_info ) {

					// Setup the updater.
					$addon_file = key( $addon_info );

					$instance = new Updater( $addon_file, array(
						'version'   => $addon_info[ $addon_file ]['Version'],
						'license'   => $license_key['key'],
						'item_name' => $addon_name,
						'beta'      => FALSE,
					) );

					ob_start();
					$instance->show_update_notification();
					$response = ob_get_clean();

					$html = new Crawler( $response );
					$this->assertTrue( $response );
				} else {
					$this->assertFalse( $addon_info );
				}
			}
		}
		$this->assertTrue(true);
	}

	public function test_init() {
		$obj = new Updater( 'foo-file.zip', [ 'version' => '0.0.1', 'license' => 'free' ] );
		$obj->init();
		$this->assertEquals( 10, TestHelpers::has_action( 'pre_set_site_transient_update_plugins', array( Updater::class, 'check_update' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'plugins_api', array( Updater::class, 'plugins_api_filter' ) ) );
		$this->assertFalse( TestHelpers::has_action( 'after_plugin_row_foo-file.zip', 'wp_plugin_update_row' ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'after_plugin_row_foo-file.zip', array( Updater::class, 'show_update_notification' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'admin_init', array( Updater::class, 'show_changelog' ) ) );
	}

	public function test_check_update() {
		$obj = new Updater( 'foo-file.zip', [ 'version' => '0.0.1', 'license' => 'free' ] );
		//FIXME!!!
		$obj->get_version_info_transient();
		/*
		try {
			$data = $obj->check_update( [] );
		} catch ( Exception $e ) {
			echo $e->getTraceAsString();
		}
		var_dump($data);*/
		$this->expectNotToPerformAssertions();
	}

	public function test_show_update_notification() {
		$obj = new Updater( 'foo-file.zip', [ 'version' => '0.0.1', 'license' => 'free' ] );
		try {
			$obj->show_update_notification( 'foo-file.zip', [ 'version' => '0.0.1', 'license' => 'free' ] );
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_plugins_api_filter() {
		$obj = new Updater( 'foo-file.zip', [ 'version' => '0.0.1', 'license' => 'free' ] );
		$data = $obj->plugins_api_filter( [], 'plugin_information', $obj );
		$this->assertIsArray( $data );
	}

	public function test_show_changelog() {
		$obj = new Updater( 'foo-file.zip', [ 'version' => '0.0.1', 'license' => 'free' ] );
		try {
			ob_start();
			$obj->show_changelog();
			$data = ob_get_clean();
		} catch ( Exception $e ) {
			unset( $e );
		}
		//var_dump( $data );
		$this->expectNotToPerformAssertions();
	}

	public function test_get_version_info_transient() {
		$obj = new Updater( 'foo-file.zip', [ 'version' => '0.0.1', 'license' => 'free' ] );
		$transient_name = AtumCache::get_transient_key( 'addon_status', 'foo-file.zip' );
		$data = $obj->get_version_info_transient( $transient_name );
		//var_dump( $data );
		$this->expectNotToPerformAssertions();
	}

	public function test_set_version_info_transient() {
		$obj = new Updater( 'foo-file.zip', [ 'version' => '0.0.1', 'license' => 'free' ] );
		$transient_name = AtumCache::get_transient_key( 'addon_status', 'foo-file.zip' );
		$data = $obj->set_version_info_transient( 'foo', $transient_name );
		//var_dump( $data );
		$this->expectNotToPerformAssertions();
	}

	private function add_addons_keys() {
		$keys = array(
			'Product Levels'           => [ 'key' => 'aaaaaaaaaaaaaa', 'status' => 'valid' ],
			'Stock Takes'              => [ 'key' => 'bbbbbbbbbbbbbb', 'status' => 'valid' ],
			'Stock Logs'               => [ 'key' => 'cccccccccccccc', 'status' => 'valid' ],
			'Dashboard Statistics Pro' => [ 'key' => 'dddddddddddddd', 'status' => 'valid' ],
			'User Restrictions'        => [ 'key' => 'eeeeeeeeeeeeee', 'status' => 'valid' ],
			'Data Export'              => [ 'key' => 'ffffffffffffff', 'status' => 'valid' ],
			'Multi-Inventory'          => [ 'key' => '45345634564567', 'status' => 'valid' ],
		);
		update_option( Addons::ADDONS_KEY_OPTION, $keys );
		return $keys;
	}
}
