<?php
/**
 * The model class for the Log Item Fee objects
 *
 * @package         Atum\InventoryLogs
 * @subpackage      Items
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\InventoryLogs\Items;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Items\AtumOrderItemFee;


class LogItemFee extends AtumOrderItemFee {

	use LogItemTrait;

}
