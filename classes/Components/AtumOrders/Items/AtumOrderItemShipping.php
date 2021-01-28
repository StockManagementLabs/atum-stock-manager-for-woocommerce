<?php
/**
 * The model class for the ATUM Order Item Shipping objects
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


abstract class AtumOrderItemShipping extends \WC_Order_Item_Shipping {

	/**
	 * The Shipping item data array
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'method_title' => '',
		'method_id'    => '',
		'total'        => 0,
		'total_tax'    => 0,
		'taxes'        => array(
			'total' => array(),
		),
	);

	/**
	 * Meta keys reserved for internal use
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array( '_method_id', '_cost', '_total_tax', '_taxes' );

	// Load the shared methods.
	use AtumOrderItemTrait;

	/**
	 * Saves an item's meta data to the database
	 * Runs after both create and update, so $id will be set
	 *
	 * @since 1.2.9
	 */
	public function save_item_data() {

		$save_values = (array) apply_filters( 'atum/orders/item_shipping/save_data', array(
			'_method_id' => $this->get_method_id( 'edit' ),
			'_cost'      => $this->get_total( 'edit' ),
			'_total_tax' => $this->get_total_tax( 'edit' ),
			'_taxes'     => $this->get_taxes( 'edit' ),
		) );

		$this->atum_order_item_model->save_meta( $save_values );

	}

}
