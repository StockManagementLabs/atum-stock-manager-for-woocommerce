<?php
/**
 * Class AddonsTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Addons\Addons;
use Atum\Components\AtumCache;
use Atum\Inc\Helpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class AddonsTest extends WP_UnitTestCase {

	public function test_get_instance() {
		$this->assertInstanceOf( Addons::class, Addons::get_instance() );
	}

	public function test_maybe_bootstrap() {

		$function = function_exists( 'WC' );
		$option   = get_option( 'woocommerce_manage_stock' );

		$this->assertEquals( true, $function, 'Woocommerce plugin needed for testing ATUM Stock Manager for Woocommerce.' );
		$this->assertEquals( 'yes', $option, 'Option `woocommerce_manage_stock` must be `yes` for testing ATUM Stock Manager for Woocommerce.' );

	}

	public function test_add_menu() {
		$instance = Addons::get_instance();
		$menu = array( 'others' => 'foo' );
		$menu = $instance->add_menu( $menu );

		$this->assertIsArray( $menu );
		$this->assertArrayHasKey( 'addons', $menu );
		$this->assertArrayHasKey( 'slug', $menu['addons'] );
		$this->assertEquals( ATUM_SHORT_NAME . '-addons', $menu['addons']['slug'] );
	}

	/*
	 * Nothing to check.
	public function test_init_addons() {
		$instance = Addons::get_instance();

		$actual = $instance->init_addons();

		print_r($actual);

		$this->assertTrue(true);
	}*/

	public function test_load_addons_page() {
		$instance = Addons::get_instance();

		ob_start();
		$instance->load_addons_page();
		$response = ob_get_clean();

		$this->assertTrue( wp_script_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_script_is( 'atum-addons', 'registered' ) );
		$this->assertTrue( wp_style_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_style_is( 'atum-addons', 'registered' ) );

		$html = new Crawler( $response );

		$this->assertEquals( 1, $html->filter('div.atum-addons')->count() );
		$this->assertGreaterThan( 0, $html->filter('div.theme')->count() );
	}

	/*
	 * No output to check.
	public function test_check_addons_updates() {
		$this->add_addons_keys();
		$instance = Addons::get_instance();

		ob_start();
		$instance->check_addons_updates();
		$response = ob_get_clean();

		print_r($response);

		$this->assertTrue(true);
	}*/

	public function test_show_addons_activation_notice() {
		$instance = Addons::get_instance();

		ob_start();
		$instance->show_addons_activation_notice();

		$response = ob_get_clean();
		$html     = new Crawler( $response );

		$this->assertEquals( 1, $html->filter('div.atum-notice')->count() );
		$this->assertEquals( 1, $html->filter('div.atum-notice a')->count() );
		$this->assertEquals( 1, $html->filter('script')->count() );
	}

	public function test_http_request_args() {
		$instance = Addons::get_instance();
		$url = 'https://www.stockmanagementlabs.com/package_download/';
		$args = array( 'sslverify' => true );
		$args = $instance->http_request_args( $args, $url );

		$this->assertIsArray( $args );
		$this->assertEquals( false, $args['sslverify'] );
	}

	public function test_get_keys() {
		$keys     = $this->add_addons_keys();
		$get_keys = Addons::get_keys();

		$this->assertIsArray( $get_keys );
		$this->assertEquals( $keys, $get_keys );
	}

	public function test_update_key() {
		$this->add_addons_keys();
		$newkey = [ 'key' => 'xxxxxxxxxxxxxx', 'status' => 'valid' ];
		Addons::update_key( 'Stock Takes', $newkey );

		$this->assertEquals( $newkey, Addons::get_keys('Stock Takes') );
	}

	public function test_get_addon_status() {
		$keys = $this->add_addons_keys();

		if ( ! empty( $keys ) ) {
			foreach ( $keys as $name => $key ) {
				$status = Addons::get_addon_status( $name, sanitize_title( ATUM_PREFIX . $name ) );
				$this->assertIsArray( $status );
				$this->assertArrayHasKey( 'status', $status );
				$this->assertArrayHasKey( 'key', $status );
			}
		} else {
			$this->assertEmpty( $keys );
		}
	}

	public function test_delete_status_transient() {
		$this->add_addons_keys();
		$addon_name     = 'Stock Takes';
		$transient_name = AtumCache::get_transient_key( 'addon_status', $addon_name );

		AtumCache::set_transient( $transient_name, 'foo', 0, TRUE );
		$this->assertEquals( 'foo', AtumCache::get_transient( $transient_name ) );

		//TODO: No se borra!!
		//Addons::delete_status_transient( $addon_name );
		//$this->assertFalse( AtumCache::get_transient( $transient_name ) );
	}

	/* TODO: Test code or tested code did not (only) close its own output buffers
	public function test_install_addon() {
		$keys = $this->add_addons_keys_2();

		if ( ! empty( $keys ) ) {
			foreach ( $keys as $name => $key ) {
				try {
					Addons::install_addon( $name, sanitize_title( ATUM_PREFIX . $name ), 'https://www.stockmanagementlabs.com/' );
					$this->assertTrue( TRUE );
				} catch ( Exception $e ) {
					$this->assertEquals( 'Undefined index: incompatible_archive', $e->getMessage() );
				}
			}
		} else {
			$this->assertTrue(true);
		}
	}*/

	public function test_check_license() {
		$keys = $this->add_addons_keys();
		foreach ( $keys as $key => $val ) {
			$response = Addons::check_license( $key, $val['key'] );
			$this->assertIsArray( $response );
			$this->assertArrayHasKey( 'response', $response );
			$this->assertArrayHasKey( 'code', $response['response'] );
		}
	}

	public function test_activate_license() {
		$keys = $this->add_addons_keys();
		foreach ( $keys as $key => $val ) {
			$response = Addons::activate_license( $key, $val['key'] );
			$this->assertIsArray( $response );
			$this->assertArrayHasKey( 'response', $response );
			$this->assertArrayHasKey( 'code', $response['response'] );
		}
	}

	public function test_deactivate_license() {
		$keys = $this->add_addons_keys();
		foreach ( $keys as $key => $val ) {
			$response = Addons::deactivate_license( $key, $val['key'] );
			$this->assertIsArray( $response );
			$this->assertArrayHasKey( 'response', $response );
			$this->assertArrayHasKey( 'code', $response['response'] );
		}
	}

	public function test_get_version() {
		$keys = $this->add_addons_keys();
		foreach ( $keys as $key => $val ) {
			$response = Addons::get_version( $key, '1.0', true );
			$this->assertIsArray( $response );
			$this->assertArrayHasKey( 'response', $response );
			$this->assertArrayHasKey( 'code', $response['response'] );
		}
	}

	public function test_get_installed_addons() {
		$addons = Addons::get_installed_addons();
		$this->assertIsArray( $addons );
	}

	public function test_has_valid_key() {
		$actual = Addons::has_valid_key();
		$this->assertIsBool( $actual );
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

	private function add_addons_keys_2() {
		$keys = array(
			'Product Levels'           => [ 'key' => 'aaaaaaaaaaaaaa', 'status' => 'valid' ],
			'Multi-Inventory'          => [ 'key' => '45345634564567', 'status' => 'valid' ],
		);
		update_option( Addons::ADDONS_KEY_OPTION, $keys );
		return $keys;
	}
}
