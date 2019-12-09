<?php
/**
* Class AtumQueuesTest
*
* @package Atum_Stock_Manager_For_Woocommerce
*/

use Atum\Components\AtumQueues;
use TestHelpers\TestHelpers;


/**
* Sample test case.
*/
class AtumQueuesTest extends WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( AtumQueues::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$this->assertInstanceOf( AtumQueues::class, AtumQueues::get_instance() );

		$this->assertEquals( 10, TestHelpers::has_action( 'init', array( AtumQueues::class, 'check_queues' ) ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'atum/update_expiring_product_props', array( AtumQueues::class, 'update_expiring_product_props_action' ) ) );
	}

	public function test_check_queues() {
		$aq = AtumQueues::get_instance();

		try {
			$aq->check_queues();
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_update_expiring_product_props_action() {
		$aq = AtumQueues::get_instance();

		try {
			$aq->update_expiring_product_props_action();
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

}