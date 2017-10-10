<?php
/**
 * @package         Atum\PurchaseOrders
 * @subpackage      Items
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.9
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
		$this->atum_order_id = $this->atum_order_item_model->get_atum_order_id();
		$this->read_meta_data();

	}

}