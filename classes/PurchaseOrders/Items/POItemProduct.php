<?php
/**
 * @package         Atum\PurchaseOrders
 * @subpackage      Items
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.9
 *
 * The model class for the PO Item Product objects
 */

namespace Atum\PurchaseOrders\Items;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;


class POItemProduct extends AtumOrderItemProduct {

	/**
	 * POItemProduct constructor
	 *
	 * @param int $item
	 */
	public function __construct( $item = 0 ) {

		parent::__construct( $item );

		// Use the purchase price when adding products to a PO
		add_filter( 'woocommerce_get_price_excluding_tax', array($this, 'use_purchase_price'), 10, 3);
	}

	use POItemTrait;

	/**
	 * Use the purchase price for the products added to POs
	 *
	 * @since 1.3.0
	 *
	 * @param $price
	 * @param $qty
	 * @param $product
	 *
	 * @return float|mixed|string
	 */
	public function use_purchase_price($price, $qty, $product) {

		// Get the purchase price (if set)
		$price = get_post_meta($product->get_id(), '_purchase_price', TRUE);

		if ( !$price ) {
			return '';
		}
		elseif ( empty( $qty ) ) {
			return 0.0;
		}

		if ( $product->is_taxable() && wc_prices_include_tax() ) {
			$tax_rates  = \WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
			$taxes      = \WC_Tax::calc_tax( $price * $qty, $tax_rates, true );
			$price      = \WC_Tax::round( $price * $qty - array_sum( $taxes ) );
		}
		else {
			$price = $price * $qty;
		}

		return $price;

	}

}