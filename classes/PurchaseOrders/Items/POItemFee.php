<?php
/**
 * The model class for the PO Item Fee objects
 *
 * @package         Atum\PurchaseOrders
 * @subpackage      Items
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2024 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\PurchaseOrders\Items;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Items\AtumOrderItemFee;


class POItemFee extends AtumOrderItemFee {

	use POItemTrait;

}
