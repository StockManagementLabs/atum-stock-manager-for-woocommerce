<?php
/**
 * REST ATUM API Purchase Order Notes controller
 * Handles requests to the /atum/purchase-orders/<order_id>/notes endpoint.
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2019 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;

class PurchaseOrderNotesController extends AtumOrderNotesController {

	/**
	 * Route base
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/purchase-orders/(?P<order_id>[\d]+)/notes';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = PurchaseOrders::POST_TYPE;

	/**
	 * Get the current order object
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return PurchaseOrder
	 */
	protected function get_atum_order( $request ) {

		if ( is_null( $this->order ) ) {
			$this->order = new PurchaseOrder( (int) $request['order_id'] );
		}

		return $this->order;

	}

}
