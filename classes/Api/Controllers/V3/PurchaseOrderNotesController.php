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

use Atum\Components\AtumOrders\AtumComments;
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
	 * Get order notes from an purchase order
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return array|\WP_Error
	 */
	public function get_items( $request ) {

		$order = new PurchaseOrder( (int) $request['order_id'] );

		if ( ! $order || $this->post_type !== $order->get_type() ) {
			return new \WP_Error( "atum_rest_{$this->post_type}_invalid_id", __( 'Invalid purchase order ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$args = array(
			'post_id' => $order->get_id(),
			'approve' => 'approve',
			'type'    => AtumComments::NOTES_KEY,
		);

		// Bypass the AtumComments filter to get rid of ATUM Order notes comments from queries.
		$atum_comments = AtumComments::get_instance();

		remove_filter( 'comments_clauses', array( $atum_comments, 'exclude_atum_order_notes' ) );
		$notes = get_comments( $args );
		add_filter( 'comments_clauses', array( $atum_comments, 'exclude_atum_order_notes' ) );

		$data = array();
		foreach ( $notes as $note ) {
			$order_note = $this->prepare_item_for_response( $note, $request );
			$order_note = $this->prepare_response_for_collection( $order_note );
			$data[]     = $order_note;
		}

		return rest_ensure_response( $data );

	}

}
