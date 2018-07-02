<?php
/**
 * @package         Atum\InventoryLogs
 * @subpackage      Items
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * @noinspection    PhpParamsInspection
 *
 * Shared methods for the Log Item objects
 */

namespace Atum\InventoryLogs\Items;

defined( 'ABSPATH' ) or die;

use Atum\InventoryLogs\Models\LogItem;


trait LogItemTrait {

	/**
	 * @inheritdoc
	 */
	protected function load() {

		$this->atum_order_item_model = new LogItem( $this );

		if (! $this->atum_order_id) {
			$this->atum_order_id = $this->atum_order_item_model->get_atum_order_id();
		}

		$this->read_meta_data();

	}

}