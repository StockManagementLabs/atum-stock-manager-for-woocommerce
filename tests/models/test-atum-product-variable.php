<?php
/**
 * Class AtumProductVariableTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use TestHelpers\TestHelpers;
use Atum\Models\Products\AtumProductVariable;

class AtumProductVariableTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_product_variable() {
		$product = TestHelpers::create_variation_product();
		$obj = new AtumProductVariable( $product );
		$this->assertInstanceOf( AtumProductVariable::class, $obj );
	}

}
