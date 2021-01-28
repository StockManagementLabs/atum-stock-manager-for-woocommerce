<?php
/**
 * REST ATUM API Purchase Order Notes controller
 * Handles requests to the /atum/purchase-orders/<order_id>/notes endpoint.
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

use Atum\Inc\Helpers;
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
			$this->order = Helpers::get_atum_order_model( (int) $request['order_id'], FALSE, $this->post_type );
		}

		return $this->order;

	}

	/**
	 * Prepare links for the request
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_Comment $note Delivery order_note object.
	 *
	 * @return array Links for the given order note.
	 */
	protected function prepare_links( $note ) {

		$order_id    = (int) $note->comment_post_ID;
		$links       = parent::prepare_links( $note );
		$links['up'] = array(
			'href' => rest_url( sprintf( '/%s/atum/purchase-orders/%d', $this->namespace, $order_id ) ),
		);

		return $links;

	}

}
