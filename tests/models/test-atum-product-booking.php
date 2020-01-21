<?php
/**
 * Class AtumProductBookingTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use TestHelpers\TestHelpers;
use Atum\Models\Products\AtumProductBooking;

class AtumProductBookingTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_product_booking() {
		include dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/woocommerce-bookings/includes/data-objects/class-wc-product-booking.php';
		$product = TestHelpers::create_product();
		$obj = new AtumProductBooking( $product );
		$this->assertInstanceOf( AtumProductBooking::class, $obj );
	}

}
