<?php
/**
 * @package         Atum\InventoryLogs
 * @subpackage      Models
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * The model class for the Log Item objects
 */

namespace Atum\InventoryLogs\Models;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumOrders\Models\AtumOrderItemModel;


class LogItem extends AtumOrderItemModel {

	/**
	 * The WP cache key name
	 * @var string
	 */
	protected $cache_key = 'inventory-log-items';

	/**
	 * LogItem constructor
	 *
	 * @param \WC_Order_Item $log_item  The factory object for initialization
	 */
	public function __construct( \WC_Order_Item $log_item ) {
		$this->atum_order_item = $log_item;
		parent::__construct( $log_item->get_id() );
	}

}