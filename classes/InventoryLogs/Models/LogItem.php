<?php
/**
 * The model class for the Log Item objects
 *
 * @package         Atum\InventoryLogs
 * @subpackage      Models
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\InventoryLogs\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Models\AtumOrderItemModel;


class LogItem extends AtumOrderItemModel {

	/**
	 * The WP cache key name
	 *
	 * @var string
	 */
	protected $cache_key = 'inventory-log-item';

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
