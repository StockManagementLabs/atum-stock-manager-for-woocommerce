<?php
/**
 * Class AtumProductSimpleTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use TestHelpers\TestHelpers;
use Atum\Models\Products\AtumProductSimple;

class AtumProductSimpleTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_product_simple() {
		$obj = new AtumProductSimple();
		$this->assertInstanceOf( AtumProductSimple::class, $obj );
	}

}
