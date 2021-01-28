<?php
/**
 * The model class for the Log Item Product objects
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

use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;


class LogItemProduct extends AtumOrderItemProduct {

	use LogItemTrait;

}
