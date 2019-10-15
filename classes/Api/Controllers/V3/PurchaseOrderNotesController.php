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

		return $this->get_order_notes( $order, $request );

	}

	/**
	 * Get a single order note.
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {

		$order = new PurchaseOrder( (int) $request['order_id'] );

		if ( ! $order || $this->post_type !== $order->get_type() ) {
			return new \WP_Error( 'atum_rest_order_invalid_id', __( 'Invalid purchase order ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		return $this->get_order_note( $order, $request );

	}

	/**
	 * Create a single purchase order note
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function create_item( $request ) {

		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: post type */
			return new \WP_Error( "atum_rest_{$this->post_type}_exists", sprintf( __( 'Cannot create existing %s.', ATUM_TEXT_DOMAIN ), $this->post_type ), [ 'status' => 400 ] );
		}

		$order = new PurchaseOrder( (int) $request['order_id'] );

		if ( ! $order || $this->post_type !== $order->get_type() ) {
			return new \WP_Error( 'atum_rest_order_invalid_id', __( 'Invalid purchase order ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		return $this->create_order_note( $order, $request );

	}

	/**
	 * Delete a single purchase order note
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {

		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for this type, error out.
		if ( ! $force ) {
			return new \WP_Error( 'atum_rest_trash_not_supported', __( 'Webhooks do not support trashing.', ATUM_TEXT_DOMAIN ), [ 'status' => 501 ] );
		}

		$order = new PurchaseOrder( (int) $request['order_id'] );

		if ( ! $order || $this->post_type !== $order->get_type() ) {
			return new \WP_Error( 'atum_rest_order_invalid_id', __( 'Invalid purchase order ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		return $this->delete_order_note( $order, $request );

	}

}
