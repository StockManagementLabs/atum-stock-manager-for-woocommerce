<?php
/**
 * Class AtumProductVariationTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use TestHelpers\TestHelpers;
use Atum\Models\Products\AtumProductVariation;

class AtumProductVariationTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_product_variation() {
		$product = TestHelpers::create_variation_product( true );
		$obj = new AtumProductVariation( $product );
		$this->assertInstanceOf( AtumProductVariation::class, $obj );
	}

}
