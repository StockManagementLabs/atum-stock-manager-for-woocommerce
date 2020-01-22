<?php
/**
 * Class AtumProductGroupedTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use TestHelpers\TestHelpers;
use Atum\Models\Products\AtumProductGrouped;

class AtumProductGroupedTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_product_grouped() {
		$obj = new AtumProductGrouped();
		$this->assertInstanceOf( AtumProductGrouped::class, $obj );
	}

}
