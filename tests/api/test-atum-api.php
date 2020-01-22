<?php
/**
 * Class AtumApiTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Api\AtumApi;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class AtumApiTest extends WP_UnitTestCase {
	public function test_methods() {
		$data = TestHelpers::count_public_methods( AtumApi::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$this->assertInstanceOf( AtumApi::class, AtumApi::get_instance() );
	}

	public function test_register_api_controllers() {
		$api = AtumApi::get_instance();
		$data = $api->register_api_controllers( [ 'wc/v3' => [ 'foo' ] ] );
		$this->assertIsArray( $data );
		unset( $data['wc/v3'][0] );
		foreach ( $data['wc/v3'] as $k => $v ) {
			$this->assertContains( 'atum-', $k );
			$this->assertContains( 'Atum\Api\Controllers\V3', $v );
		}
	}

	public function test_load_extenders() {
		$api = AtumApi::get_instance();
		$api->load_extenders();
		$this->expectNotToPerformAssertions();
	}

}