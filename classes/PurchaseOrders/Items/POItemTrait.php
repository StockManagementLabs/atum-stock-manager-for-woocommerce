<?php
/**
 * @package         Atum\PurchaseOrders
 * @subpackage      Items
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.2.9
 *
 * @noinspection    PhpParamsInspection
 *
 * Shared methods for the PO Item objects
 */

namespace Atum\PurchaseOrders\Items;

defined( 'ABSPATH' ) or die;

use Atum\PurchaseOrders\Models\POItem;


trait POItemTrait {

	/**
	 * @inheritdoc
	 */
	protected function load() {

		$this->atum_order_item_model = new POItem( $this );

		if (! $this->atum_order_id) {
			$this->atum_order_id = $this->atum_order_item_model->get_atum_order_id();
		}

		$this->read_meta_data();

	}

}