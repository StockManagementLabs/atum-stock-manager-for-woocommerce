<?php
/**
 * The model class for the Log Item Fee objects
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

use Atum\Components\AtumOrders\Items\AtumOrderItemFee;


class LogItemFee extends AtumOrderItemFee {

	use LogItemTrait;

}
