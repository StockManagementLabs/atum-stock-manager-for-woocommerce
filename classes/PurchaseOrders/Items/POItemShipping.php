<?php
/**
 * @package         Atum\PurchaseOrders
 * @subpackage      Items
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.9
 *
 * The model class for the PO Item Shipping objects
 */

namespace Atum\PurchaseOrders\Items;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumOrders\Items\AtumOrderItemShipping;


class POItemShipping extends AtumOrderItemShipping {

	use POItemTrait;

}