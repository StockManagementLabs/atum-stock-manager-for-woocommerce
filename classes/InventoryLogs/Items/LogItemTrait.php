<?php
/**
 * Shared methods for the Log Item objects
 *
 * @package         Atum\InventoryLogs
 * @subpackage      Items
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\InventoryLogs\Items;

defined( 'ABSPATH' ) || die;

use Atum\InventoryLogs\Models\LogItem;


trait LogItemTrait {

	/**
	 * Load the log item
	 *
	 * @since 1.2.4
	 *
	 * @throws \Atum\Components\AtumException
	 */
	protected function load() {

		/* @noinspection PhpParamsInspection */
		$this->atum_order_item_model = new LogItem( $this );

		if ( ! $this->atum_order_id ) {
			$this->atum_order_id = $this->atum_order_item_model->get_atum_order_id();
		}

		$this->read_meta_data();

	}

}
