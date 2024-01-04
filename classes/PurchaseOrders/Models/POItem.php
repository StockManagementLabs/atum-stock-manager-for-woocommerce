<?php
/**
 * The model class for the Purchase Order Item objects
 *
 * @package         Atum\PurchaseOrders
 * @subpackage      Models
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2024 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\PurchaseOrders\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Models\AtumOrderItemModel;


class POItem extends AtumOrderItemModel {

	/**
	 * POItem constructor
	 *
	 * @param \WC_Order_Item $po_item The factory object for initialization.
	 *
	 * @throws \Atum\Components\AtumException
	 */
	public function __construct( \WC_Order_Item $po_item ) {
		$this->atum_order_item = $po_item;
		parent::__construct( $po_item->get_id() );
	}

}
