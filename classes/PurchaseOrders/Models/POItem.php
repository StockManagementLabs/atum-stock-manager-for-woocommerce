<?php
/**
 * @package         Atum\PurchaseOrders
 * @subpackage      Models
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.9
 *
 * The model class for the Purchase Order Item objects
 */

namespace Atum\PurchaseOrders\Models;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumOrders\Models\AtumOrderItemModel;


class POItem extends AtumOrderItemModel {

	/**
	 * The WP cache key name
	 * @var string
	 */
	protected $cache_key = 'po-items';

	/**
	 * LogItem constructor
	 *
	 * @param \WC_Order_Item $po_item  The factory object for initialization
	 */
	public function __construct( \WC_Order_Item $po_item  ) {
		$this->atum_order_item = $po_item;
		parent::__construct( $po_item->get_id() );
	}

}