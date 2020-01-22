<?php
/**
 * Class AtumProductBundleTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use TestHelpers\TestHelpers;
use Atum\Models\Products\AtumProductBundle;

class AtumProductBundleTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_product_bundle() {
		include dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/woocommerce-product-bundles/includes/class-wc-product-bundle.php';
		$product = TestHelpers::create_product();
		$obj = new AtumProductBundle( $product );
		$this->assertInstanceOf( AtumProductBundle::class, $obj );
	}

}
