<?php
/**
 * Class AtumProductSubscriptionTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use TestHelpers\TestHelpers;
use Atum\Models\Products\AtumProductSubscription;

class AtumProductSubscriptionTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_product_subscription() {
		include dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/woocommerce-subscriptions/includes/class-wc-product-subscription.php';
		$product = TestHelpers::create_product();
		$obj = new AtumProductSubscription( $product );
		$this->assertInstanceOf( AtumProductSubscription::class, $obj );
	}

}
