<?php
/**
* Class AtumLogsTest
*
* @package Atum_Stock_Manager_For_Woocommerce
*/

use Atum\Components\AtumLogs\AtumLogs;
use TestHelpers\TestHelpers;

class AtumLogsTest extends WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( AtumLogs::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$this->assertInstanceOf( AtumLogs::class, AtumLogs::get_instance() );
	}

	public function test_get_log_table() {
		$this->assertEquals( ATUM_PREFIX . 'log', AtumLogs::get_log_table() );
	}

}