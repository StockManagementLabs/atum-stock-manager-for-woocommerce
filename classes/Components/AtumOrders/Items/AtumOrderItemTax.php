<?php
/**
 * The model class for the ATUM Order Item Tax objects
 *
 * @package         Atum\Components\AtumOrders
 * @subpackage      Items
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\Components\AtumOrders\Items;

defined( 'ABSPATH' ) || die;


abstract class AtumOrderItemTax extends \WC_Order_Item_Tax {

	/**
	 * The Tax item data array
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'rate_code'          => '',
		'rate_id'            => 0,
		'label'              => '',
		'compound'           => FALSE,
		'tax_total'          => 0,
		'shipping_tax_total' => 0,
	);

	/**
	 * The internal meta keys
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_rate_id',
		'_label',
		'_compound',
		'_tax_amount',
		'_shipping_tax_amount',
	);

	// Load the shared methods.
	use AtumOrderItemTrait;

	/**
	 * Saves an item's meta data to the database
	 * Runs after both create and update, so $id will be set
	 *
	 * @since 1.2.9
	 */
	public function save_item_data() {

		$save_values = (array) apply_filters( 'atum/orders/item_tax/save_data', array(
			'_rate_id'             => $this->get_rate_id( 'edit' ),
			'_label'               => $this->get_label( 'edit' ),
			'_compound'            => $this->get_compound( 'edit' ),
			'_tax_amount'          => $this->get_tax_total( 'edit' ),
			'_shipping_tax_amount' => $this->get_shipping_tax_total( 'edit' ),
		) );

		$this->atum_order_item_model->save_meta( $save_values );

	}

}
