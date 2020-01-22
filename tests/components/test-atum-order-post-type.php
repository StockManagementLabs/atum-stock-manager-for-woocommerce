<?php
/**
* Class AtumOrderPostTypeTest
*
* @package Atum_Stock_Manager_For_Woocommerce
*/

use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\PurchaseOrders\PurchaseOrders;
use TestHelpers\TestHelpers;

class AtumOrderPostTypeTest extends WP_UnitTestCase {

	public function test_register_post_type() {
		$po = new PurchaseOrders();
		$po->register_post_type();
		$this->expectNotToPerformAssertions();
	}

	public function test_render_columns() {
		$this->expectNotToPerformAssertions();
	}


}