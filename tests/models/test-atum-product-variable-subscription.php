<?php
/**
 * Class AtumProductVariableSubscriptionTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use TestHelpers\TestHelpers;
use Atum\Models\Products\AtumProductVariableSubscription;

class AtumProductVariableSubscriptionTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_product_variable_subscription() {
		include dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/woocommerce-subscriptions/includes/class-wc-product-variable-subscription.php';
		$product = TestHelpers::create_variation_product();
		$obj = new AtumProductVariableSubscription( $product );
		$this->assertInstanceOf( AtumProductVariableSubscription::class, $obj );
	}

}
