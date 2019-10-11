<?php
/**
 * Class POItemTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\PurchaseOrders\Models\POItem;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class POItemTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_instance() {
		$wcoi = new WC_Order_Item();
		$obj = new POItem( $wcoi );
		$this->assertInstanceOf( POItem::class, $obj );
	}


}