<?php
/**
 * Class AtumCapabilitiesTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Components\AtumCapabilities;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class AtumCapabilitiesTest extends WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( AtumCapabilities::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$this->assertInstanceOf( AtumCapabilities::class, AtumCapabilities::get_instance() );
	}

	public function test_current_user_can() {
		$this->assertIsBool( AtumCapabilities::current_user_can( 'edit_purchase_order' ) );
		$this->assertIsBool( AtumCapabilities::current_user_can( 'view_admin_menu' ) );
		$this->assertIsBool( AtumCapabilities::current_user_can( 'edit_others_multiple_post_types' ) );
	}
}
