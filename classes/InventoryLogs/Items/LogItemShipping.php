<?php
/**
 * @package         Atum\InventoryLogs
 * @subpackage      Items
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * The model class for the Log Item Shipping objects
 */

namespace Atum\InventoryLogs\Items;

defined( 'ABSPATH' ) or die;


class LogItemShipping extends \WC_Order_Item_Shipping {

	/**
	 * The Shipping item data array
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
		'log_id'     => 0
	);

	/**
	 * Meta keys reserved for internal use
	 * @var array
	 */
	protected $internal_meta_keys = array( '_method_id', '_cost', '_total_tax', '_taxes', '_log_id' );

	// Load the shared methods
	use LogItemTrait;

	/**
	 * Saves an item's meta data to the database
	 * Runs after both create and update, so $id will be set
	 *
	 * @since 1.2.4
	 */
	public function save_item_data() {

		$save_values = (array) apply_filters( 'atum/inventory_logs/log_item_shipping/save_data', array(
			'_method_id' => $this->get_method_id( 'edit' ),
			'_cost'      => $this->get_total( 'edit' ),
			'_total_tax' => $this->get_total_tax( 'edit' ),
			'_taxes'     => $this->get_taxes( 'edit' )
		) );

		$this->log_item_model->save_meta( $save_values );

	}

}