<?php
/**
 * The model class for the ATUM Order Item Fee objects
 *
 * @package         Atum\Components\AtumOrders
 * @subpackage      Items
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\Components\AtumOrders\Items;

defined( 'ABSPATH' ) || die;


abstract class AtumOrderItemFee extends \WC_Order_Item_Fee {

	/**
	 * The Fee item data array
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'tax_class'  => '',
		'tax_status' => 'taxable',
		'total'      => '',
		'total_tax'  => '',
		'taxes'      => array(
			'total' => array(),
		),
	);

	/**
	 * Meta keys reserved for internal use
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array( '_tax_class', '_tax_status', '_line_subtotal', '_line_subtotal_tax', '_line_total', '_line_tax', '_line_tax_data' );


	// Load the shared methods.
	use AtumOrderItemTrait;


	/**
	 * Saves an item's meta data to the database
	 * Runs after both create and update, so $id will be set
	 *
	 * @since 1.2.9
	 */
	public function save_item_data() {

		$save_values = (array) apply_filters( 'atum/orders/item_fee/save_data', array(
			'_tax_class'     => $this->get_tax_class( 'edit' ),
			'_tax_status'    => $this->get_tax_status( 'edit' ),
			'_line_total'    => $this->get_total( 'edit' ),
			'_line_tax'      => $this->get_total_tax( 'edit' ),
			'_line_tax_data' => $this->get_taxes( 'edit' ),
		), $this );

		$this->atum_order_item_model->save_meta( $save_values );

	}

}
