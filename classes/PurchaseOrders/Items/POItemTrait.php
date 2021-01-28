<?php
/**
 * Shared methods for the PO Item objects
 *
 * @package         Atum\PurchaseOrders
 * @subpackage      Items
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\PurchaseOrders\Items;

defined( 'ABSPATH' ) || die;

use Atum\PurchaseOrders\Models\POItem;


trait POItemTrait {

	/**
	 * Load the Purchase Order item
	 *
	 * @since 1.2.9
	 */
	protected function load() {

		/* @noinspection PhpParamsInspection PhpUnhandledExceptionInspection */
		$this->atum_order_item_model = new POItem( $this );

		if ( ! $this->atum_order_id ) {
			$this->atum_order_id = $this->atum_order_item_model->get_atum_order_id();
		}

		$this->read_meta_data();

	}

}
