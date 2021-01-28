<?php
/**
 * The model class for the Purchase Order Item objects
 *
 * @package         Atum\PurchaseOrders
 * @subpackage      Models
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\PurchaseOrders\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Models\AtumOrderItemModel;


class POItem extends AtumOrderItemModel {

	/**
	 * The WP cache key name
	 *
	 * @var string
	 */
	protected $cache_key = 'po-item';

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
