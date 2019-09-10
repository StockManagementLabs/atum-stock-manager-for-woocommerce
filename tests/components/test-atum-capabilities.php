<?php
/**
 * Class AtumCapabilitiesTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Components\AtumCapabilities;

/**
 * Sample test case.
 */
class AtumCapabilitiesTest extends WP_UnitTestCase {

	public function test_get_instance() {
		$this->assertInstanceOf( AtumCapabilities::class, AtumCapabilities::get_instance() );
	}

	public function test_current_user_can() {
		$this->assertIsBool( AtumCapabilities::current_user_can( 'edit_purchase_order' ) );
		$this->assertIsBool( AtumCapabilities::current_user_can( 'view_admin_menu' ) );
		$this->assertIsBool( AtumCapabilities::current_user_can( 'edit_others_multiple_post_types' ) );
	}
}
