<?php
/**
 * Class BootstrapTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Bootstrap;
use TestHelpers\TestHelpers;

class BootstrapTest extends PHPUnit_Framework_TestCase { // WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( Bootstrap::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$this->assertInstanceOf( Bootstrap::class, Bootstrap::get_instance() );
	}

	public function test_maybe_bootstrap() {
		$boot = Bootstrap::get_instance();
		$boot->maybe_bootstrap();
		$this->expectNotToPerformAssertions();
	}

	public function test_show_bootstrap_warning() {
		$boot = Bootstrap::get_instance();
		ob_start();
		$boot->show_bootstrap_warning();
		$data = ob_get_clean();
		$this->assertContains( '<div class="error fade">', $data );
	}

	public function test_uninstall() {
		Bootstrap::uninstall();
		$this->expectNotToPerformAssertions();
	}
}
