<?php
/**
 * The model class for the PO Item Tax objects
 *
 * @package         Atum\PurchaseOrders
 * @subpackage      Items
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2024 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\PurchaseOrders\Items;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Items\AtumOrderItemTax;


class POItemTax extends AtumOrderItemTax {

	use POItemTrait;

}
