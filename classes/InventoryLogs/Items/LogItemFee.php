<?php
/**
 * @package         Atum\InventoryLogs
 * @subpackage      Items
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * The model class for the Log Item Fee objects
 */

namespace Atum\InventoryLogs\Items;

defined( 'ABSPATH' ) or die;


class LogItemFee extends \WC_Order_Item_Fee {

	/**
	 * The Fee item data array
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
		'log_id'     => 0
	);

	/**
	 * Saves an item's meta data to the database
	 * Runs after both create and update, so $id will be set
	 *
	 * @since 1.2.4
	 */
	public function save_item_data() {

		$save_values = (array) apply_filters( 'atum/inventory_logs/log_item_fee/save_data', array(
			'_tax_class'     => $this->get_tax_class( 'edit' ),
			'_tax_status'    => $this->get_tax_status( 'edit' ),
			'_line_total'    => $this->get_total( 'edit' ),
			'_line_tax'      => $this->get_total_tax( 'edit' ),
			'_line_tax_data' => $this->get_taxes( 'edit' )
		) );

		$this->log_item_model->save_meta( $save_values );

	}

	// Load the common methods
	use LogItemTrait;

}