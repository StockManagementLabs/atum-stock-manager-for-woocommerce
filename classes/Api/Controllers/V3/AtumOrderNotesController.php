<?php
/**
 * Abstract REST API's ATUM Order Notes controller
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
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumOrders\AtumComments;
use Atum\Components\AtumOrders\Models\AtumOrderModel;

abstract class AtumOrderNotesController extends \WC_REST_Order_Notes_Controller {

	/**
	 * Endpoint namespace
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * The current order object
	 *
	 * @var AtumOrderModel
	 */
	protected $order = NULL;

	/**
	 * Get the Purchase Order Notes schema, conforming to JSON Schema
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		unset( $schema['properties']['customer_note'] );
		$schema['properties']['added_by_user']['context'][] = 'view';

		return $schema;

	}

	/**
	 * Get the query params for collections
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$params = array();

		$params['type'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result to user or system notes.', ATUM_TEXT_DOMAIN ),
			'type'              => 'string',
			'enum'              => array( 'any', 'user', 'system' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;

	}

	/**
	 * Check whether a given request has permission to read ATUM order notes
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! AtumCapabilities::current_user_can( 'read_order_notes' ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot list resources.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access create ATUM order notes
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function create_item_permissions_check( $request ) {

		if ( ! AtumCapabilities::current_user_can( 'create_order_notes' ) ) {
			return new \WP_Error( 'atum_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access to read an ATUM order note
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {

		$order = $this->get_atum_order( $request );

		if ( $order && ! AtumCapabilities::current_user_can( 'read_order_notes' ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot view this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;
	}

	/**
	 * Check if a given request has access delete an ATUM order note.
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function delete_item_permissions_check( $request ) {

		$order = $this->get_atum_order( $request );

		if ( $order && ! wc_rest_check_post_permissions( $this->post_type, 'delete', $order->get_id() ) ) {
			return new \WP_Error( 'atum_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;
	}

	/**
	 * Get the current ATUM order object
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return AtumOrderModel
	 */
	abstract protected function get_atum_order( $request );

	/**
	 * Get order notes from an ATUM order
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return array|\WP_Error
	 */
	public function get_items( $request ) {

		$order = $this->get_atum_order( $request );

		if ( ! $order || $this->post_type !== $order->get_post_type() ) {
			return new \WP_Error( "atum_rest_{$this->post_type}_invalid_id", __( 'Invalid order ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
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

			/**
			 * Variable definition
			 *
			 * @var \WP_Comment $note
			 */
			if (
				( 'system' === $request['type'] && 'ATUM' !== $note->comment_author ) ||
				( 'user' === $request['type'] && 'ATUM' === $note->comment_author )
			) {
				continue;
			}

			$order_note = $this->prepare_item_for_response( $note, $request );
			$order_note = $this->prepare_response_for_collection( $order_note );
			$data[]     = $order_note;

		}

		return rest_ensure_response( $data );

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

		$order = $this->get_atum_order( $request );

		if ( ! $order || $this->post_type !== $order->get_post_type() ) {
			return new \WP_Error( 'atum_rest_order_invalid_id', __( 'Invalid order ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$id   = (int) $request['id'];
		$note = get_comment( $id );

		if ( empty( $id ) || empty( $note ) || intval( $note->comment_post_ID ) !== intval( $order->get_id() ) ) {
			return new \WP_Error( 'atum_rest_invalid_id', __( 'Invalid resource ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$order_note = $this->prepare_item_for_response( $note, $request );

		return rest_ensure_response( $order_note );

	}

	/**
	 * Create a single ATUM order note
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

		$order = $this->get_atum_order( $request );

		if ( ! $order || $this->post_type !== $order->get_post_type() ) {
			return new \WP_Error( 'atum_rest_order_invalid_id', __( 'Invalid order ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		// Create the note.
		$note_id = $order->add_order_note( $request['note'], $request['added_by_user'] );

		if ( ! $note_id ) {
			return new \WP_Error( 'atum_api_cannot_create_order_note', __( 'Cannot create order note, please try again.', ATUM_TEXT_DOMAIN ), [ 'status' => 500 ] );
		}

		Helpers::save_order_note_meta( $note_id, [ 'action' => 'api_note' ] );

		$note = get_comment( $note_id );
		$this->update_additional_fields_for_object( $note, $request );

		/**
		 * Fires after a order note is created or updated via the REST API.
		 *
		 * @since 1.6.2
		 *
		 * @param \WP_Comment      $note    New order note object.
		 * @param \WP_REST_Request $request Request object.
		 */
		do_action( 'atum/api/rest_insert_order_note', $note, $request );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $note, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, str_replace( '(?P<order_id>[\d]+)', $order->get_id(), $this->rest_base ), $note_id ) ) );

		return $response;

	}

	/**
	 * Delete a single ATUM order note
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
			return new \WP_Error( 'atum_rest_trash_not_supported', __( 'Order notes do not support trashing.', ATUM_TEXT_DOMAIN ), [ 'status' => 501 ] );
		}

		$order = $this->get_atum_order( $request );

		if ( ! $order || $this->post_type !== $order->get_post_type() ) {
			return new \WP_Error( 'atum_rest_order_invalid_id', __( 'Invalid order ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$id   = (int) $request['id'];
		$note = get_comment( $id );

		if ( empty( $id ) || empty( $note ) || intval( $note->comment_post_ID ) !== intval( $order->get_id() ) ) {
			return new \WP_Error( 'atum_rest_invalid_id', __( 'Invalid resource ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $note, $request );

		$result = wc_delete_order_note( $note->comment_ID );

		if ( ! $result ) {
			return new \WP_Error( 'atum_rest_cannot_delete', __( 'The order note cannot be deleted.', ATUM_TEXT_DOMAIN ), [ 'status' => 500 ] );
		}

		/**
		 * Fires after a order note is deleted or trashed via the REST API
		 *
		 * @param \WP_Comment       $note     The deleted or trashed order note.
		 * @param \WP_REST_Response $response The response data.
		 * @param \WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'atum/api/rest_delete_order_note', $note, $response, $request );

		return $response;

	}

	/**
	 * Prepare a single order note output for response
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_Comment      $note    Order note object.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $note, $request ) {

		$data = array(
			'id'               => (int) $note->comment_ID,
			'author'           => $note->comment_author,
			'date_created'     => wc_rest_prepare_date_response( $note->comment_date ),
			'date_created_gmt' => wc_rest_prepare_date_response( $note->comment_date_gmt ),
			'note'             => $note->comment_content,
			'added_by_user'    => 'ATUM' !== $note->comment_author,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $note ) );

		/**
		 * Filter ATUM order note object returned from the REST API
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \WP_Comment       $note     Order note object used to create response.
		 * @param \WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'atum/api/rest_prepare_atum_order_note', $response, $note, $request );

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

		$order_id = (int) $note->comment_post_ID;
		$base     = str_replace( '(?P<order_id>[\d]+)', $order_id, $this->rest_base );

		return array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $note->comment_ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
		);

	}

}
