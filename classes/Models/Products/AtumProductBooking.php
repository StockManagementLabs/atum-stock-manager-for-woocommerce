<?php
/**
 * An abstraction layer of WC Booking Product to be able to handle ATUM's custom data.
 *
 * @package         Atum\Models
 * @subpackage      Products
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.5.1
 */

namespace Atum\Models\Products;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WC_Product_Booking' ) ) {
	return;
}

class AtumProductBooking extends \WC_Product_Booking {

	// Import the shared stuff.
	use AtumProductTrait;

	/**
	 * Initialize ATUM booking product
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product|int $product Product instance or ID.
	 */
	public function __construct( $product = 0 ) {

		$this->data = apply_filters( 'atum/model/product_booking/data', array_merge( $this->data, $this->atum_data ) );
		parent::__construct( $product );

		do_action( 'atum/model/product_booking', $product );

	}

}
