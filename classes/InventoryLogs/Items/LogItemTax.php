<?php
/**
 * The model class for the Log Item Tax objects
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

use Atum\Components\AtumOrders\Items\AtumOrderItemTax;


class LogItemTax extends AtumOrderItemTax {

	use LogItemTrait;

}
