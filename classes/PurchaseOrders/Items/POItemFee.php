<?php
/**
 * @package         Atum\PurchaseOrders
 * @subpackage      Items
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.9
 *
 * The model class for the PO Item Fee objects
 */

namespace Atum\PurchaseOrders\Items;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumOrders\Items\AtumOrderItemFee;


class POItemFee extends AtumOrderItemFee {

	use POItemTrait;

}