<?php
/**
 * Class AtumExceptionTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Components\AtumException;

/**
 * Sample test case.
 */
class AtumExceptionTest extends WP_UnitTestCase {

	private $code = 107;

	private $msg  = 'Foo';

	public function test_get_error_code() {
		$e = new AtumException( $this->code, $this->msg );
		$this->assertEquals( $this->code, $e->getErrorCode() );
	}

	public function test_get_error_message() {
		$e = new AtumException( $this->code, $this->msg );
		$data = $e->getErrorData();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'status', $data );
	}
}
