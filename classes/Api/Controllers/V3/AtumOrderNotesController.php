<?php
/**
 * Abstract REST API's ATUM Order Notes controller
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

abstract class AtumOrderNotesController extends \WC_REST_Order_Notes_Controller {

	/**
	 * Endpoint namespace
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

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

}
