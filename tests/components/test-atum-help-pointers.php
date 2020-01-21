<?php
/**
 * Class AtumHelpPointersTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Components\AtumHelpPointers;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class AtumHelpPointersTest extends WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( AtumHelpPointers::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		set_current_screen( 'my-foo-screen' );
		$this->assertInstanceOf( AtumHelpPointers::class, new AtumHelpPointers() );

		$this->assertEquals( 1000, TestHelpers::has_action( 'admin_enqueue_scripts', [ AtumHelpPointers::class, 'add_pointers' ] ));
		$this->assertEquals( 10, TestHelpers::has_action( 'admin_head', [ AtumHelpPointers::class, 'add_scripts' ] ));
	}

	public function test_register_pointers() {
		set_current_screen( 'my-foo-screen' );
		$pointers = [
			[ 'id' => 'foo-pointer', 'title' => 'Foo', 'content' => 'My foo help', 'position' => 1, 'target' => 'top', 'screen' => 'my-foo-screen' ],
		];
		$hp = new AtumHelpPointers();
		$hp->register_pointers( $pointers );
		$hp->add_pointers();
		ob_start();
		$hp->add_scripts();
		$data = ob_get_clean();
		$this->assertContains( '<script type="text/javascript">', $data );
		$this->assertTrue( wp_script_is( 'wp-pointer' ) );
		$this->assertTrue( wp_style_is( 'wp-pointer' ) );
	}

	public function test_add_pointers() {
		//Tested in previous method
		$this->expectNotToPerformAssertions();
	}

	public function test_add_scripts() {
		//Tested in previous method
		$this->expectNotToPerformAssertions();
	}

}
