<?php
/**
 * Class AtumExceptionTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Components\AtumException;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class AtumExceptionTest extends WP_UnitTestCase {

	private $code = 107;

	private $msg  = 'Foo';

	public function test_methods() {
		$data = TestHelpers::count_public_methods( AtumException::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$this->assertInstanceOf( AtumException::class, new AtumException( $this->code, $this->msg ) );
	}

	public function test_getErrorCode() {
		$e = new AtumException( $this->code, $this->msg );
		$this->assertEquals( $this->code, $e->getErrorCode() );
	}

	public function test_getErrorData() {
		$e = new AtumException( $this->code, $this->msg );
		$data = $e->getErrorData();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'status', $data );
	}

	public function test_getMessage() {
		$e = new AtumException( $this->code, $this->msg );
		$this->assertEquals( $this->msg, $e->getMessage() );
	}

	public function test_getCode() {
		$e = new AtumException( $this->code, $this->msg );
		$this->assertIsNumeric( $e->getCode() );
	}

	public function test_getFile() {
		$e = new AtumException( $this->code, $this->msg );
		$this->assertEquals( __FILE__, $e->getFile() );
	}

	public function test_getLine() {
		$e = new AtumException( $this->code, $this->msg );
		$this->assertEquals( __LINE__-1, $e->getLine() );
	}

	public function test_getTrace() {
		$e = new AtumException( $this->code, $this->msg );
		$this->assertIsArray( $e->getTrace() );
	}

	public function test_getTraceAsString() {
		$e = new AtumException( $this->code, $this->msg );
		$this->assertContains( __FUNCTION__, $e->getTraceAsString() );
	}

	public function test_getPrevious() {
		$e = new AtumException( $this->code, $this->msg );
		$this->assertNull( $e->getPrevious() );
	}

	public function test___toString() {
		$e = new AtumException( $this->code, $this->msg );
		$this->assertContains( __FILE__, $e->__toString() );
	}

}
