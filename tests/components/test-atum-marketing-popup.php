<?php
/**
 * Class AtumMarketingPopupTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Components\AtumMarketingPopup;
use Atum\Inc\Helpers;
use TestHelpers\TestHelpers;


/**
 * Sample test case.
 */
class AtumMarketingPopupTest extends WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( AtumMarketingPopup::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$this->assertInstanceOf( AtumMarketingPopup::class, AtumMarketingPopup::get_instance() );
	}

	public function test_maybe_enqueue_scripts() {
		if ( Helpers::show_marketing_popup() ) {
			AtumMarketingPopup::maybe_enqueue_scripts();
			$this->assertTrue( wp_script_is( 'atum-marketing-popup', 'registered' ) );
			$this->assertTrue( wp_style_is( 'atum-marketing-popup', 'registered' ) );
		} else {
			$this->assertTrue( TRUE );
		}
	}

	public function test_get_title() {
		$mk = AtumMarketingPopup::get_instance();
		$data = $mk->get_title();
		$this->assertInstanceOf( stdClass::class, $data );
		$this->assertObjectHasAttribute( 'text', $data );
	}

	public function test_get_version() {
		$mk = AtumMarketingPopup::get_instance();
		$data = $mk->get_version();
		$this->assertInstanceOf( stdClass::class, $data );
		$this->assertObjectHasAttribute( 'text', $data );
	}

	public function test_get_description() {
		$mk = AtumMarketingPopup::get_instance();
		$data = $mk->get_description();
		$this->assertInstanceOf( stdClass::class, $data );
		$this->assertObjectHasAttribute( 'text', $data );
	}

	public function test_get_buttons() {
		$mk = AtumMarketingPopup::get_instance();
		$data = $mk->get_buttons();
		$this->assertIsArray( $data );
		foreach($data as $b) {
			$this->assertInstanceOf( stdClass::class, $b );
			$this->assertObjectHasAttribute( 'text', $b );
		}
	}

	public function test_get_images() {
		$mk = AtumMarketingPopup::get_instance();
		$data = $mk->get_images();
		$this->assertInstanceOf( stdClass::class, $data );
		$this->assertObjectHasAttribute( 'logo', $data );
	}

	public function test_get_footer_notice() {
		$mk = AtumMarketingPopup::get_instance();
		$data = $mk->get_footer_notice();
		$this->assertIsArray( $data );
		foreach($data as $b) {
			$this->assertInstanceOf( stdClass::class, $b );
			$this->assertObjectHasAttribute( 'text', $b );
		}
	}

	public function test_get_background() {
		$mk = AtumMarketingPopup::get_instance();
		$data = $mk->get_background();
		$this->assertIsString( $data );
	}

	public function test_get_dash_background() {
		$mk = AtumMarketingPopup::get_instance();
		$data = $mk->get_dash_background();
		$this->assertEquals( 'white url(https://www.stockmanagementlabs.com/wp-content/uploads/2019/06/waves-export.svg) bottom/cover no-repeat;', $data );
	}

	public function test_get_transient_key() {
		$mk = AtumMarketingPopup::get_instance();
		$data = $mk->get_transient_key();
		$this->assertIsString( $data );
	}

	public function test_is_loaded() {
		$mk = AtumMarketingPopup::get_instance();
		$data = $mk->is_loaded();
		$this->assertIsBool( $data );
	}

}
