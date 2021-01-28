<?php
/**
 * The model class for the PO Item Product objects
 *
 * @package         Atum\PurchaseOrders
 * @subpackage      Items
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\PurchaseOrders\Items;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;


class POItemProduct extends AtumOrderItemProduct {

	/**
	 * POItemProduct constructor
	 *
	 * @param int $item
	 */
	public function __construct( $item = 0 ) {
		
		parent::__construct( $item );
		
	}

	use POItemTrait;

}
