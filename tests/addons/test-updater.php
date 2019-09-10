<?php
/**
 * Class AddonsTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Addons\Updater;
use Atum\Addons\Addons;
use Atum\Inc\Helpers;
use Symfony\Component\DomCrawler\Crawler;


/**
 * Sample test case.
 */
class UpdaterTest extends WP_UnitTestCase {

	public function test_get_instance() {
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

					var_dump($instance);
				} else {
					$this->assertFalse( $addon_info );
				}
			}
		}
		$this->assertTrue(true);
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
