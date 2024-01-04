<?php
/**
 * The model class for the Log Item Shipping objects
 *
 * @package         Atum\InventoryLogs
 * @subpackage      Items
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2024 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\InventoryLogs\Items;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Items\AtumOrderItemShipping;


class LogItemShipping extends AtumOrderItemShipping {

	use LogItemTrait;

}
