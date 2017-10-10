<?php
/**
 * @package         Atum\InventoryLogs
 * @subpackage      Items
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * The model class for the Log Item Shipping objects
 */

namespace Atum\InventoryLogs\Items;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumOrders\Items\AtumOrderItemShipping;


class LogItemShipping extends AtumOrderItemShipping {

	use LogItemTrait;

}