<?php
/**
 * The model class for the Log Item objects
 *
 * @package         Atum\InventoryLogs
 * @subpackage      Models
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2024 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\InventoryLogs\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Models\AtumOrderItemModel;


class LogItem extends AtumOrderItemModel {

	/**
	 * LogItem constructor
	 *
	 * @param \WC_Order_Item $log_item The factory object for initialization.
	 *
	 * @throws \Atum\Components\AtumException
	 */
	public function __construct( \WC_Order_Item $log_item ) {

		$this->atum_order_item = $log_item;

		parent::__construct( $log_item->get_id() );

	}

}
