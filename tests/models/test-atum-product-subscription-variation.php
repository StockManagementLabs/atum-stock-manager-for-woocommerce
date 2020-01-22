<?php
/**
 * Class AtumProductSubscriptionVariationTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use TestHelpers\TestHelpers;
use Atum\Models\Products\AtumProductSubscriptionVariation;

class AtumProductSubscriptionVariationTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_product_subscription() {
		include dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/woocommerce-subscriptions/includes/legacy/class-wcs-array-property-post-meta-black-magic.php';
		include dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/woocommerce-subscriptions/includes/class-wc-product-subscription-variation.php';
		$product = TestHelpers::create_product();
		$obj = new AtumProductSubscriptionVariation( $product );
		$this->assertInstanceOf( AtumProductSubscriptionVariation::class, $obj );
	}

}
