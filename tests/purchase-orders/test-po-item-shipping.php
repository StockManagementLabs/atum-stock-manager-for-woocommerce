<?php
/**
 * Class POItemShippingTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\PurchaseOrders\Items\POItemShipping;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class POItemShippingTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( POItemShipping::class );

		if( count( $data['methods'] ) > 0 ) {
			foreach ( $data['methods'] as $method ) {
				$this->assertTrue( method_exists( $this, 'test_' . $method ), "Method `test_$method` doesn't exist in class " . self::class );
			}
		} else {
			$this->expectNotToPerformAssertions();
		}
	}

	public function test_instance() {
		$wcoi = new WC_Order_Item();
		$obj = new POItemShipping( $wcoi );
		$this->assertInstanceOf( POItemShipping::class, $obj );
	}


}