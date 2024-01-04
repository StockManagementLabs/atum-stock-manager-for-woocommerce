<?php
/**
 * REST ATUM API All Order Refunds controller
 * Handles requests to the /atum/order-refunds endpoint.
 *
 * @since       1.9.22
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

use Atum\Inc\Helpers;
use Automattic\WooCommerce\Utilities\OrderUtil;

class AllOrderRefundsController extends \WC_REST_Order_Refunds_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/order-refunds';

	/**
	 * Register the routes for order refunds.
	 *
	 * @since 1.9.22
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', ATUM_TEXT_DOMAIN ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Prepare objects query.
	 *
	 * @since 1.9.22
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {

		$args                    = parent::prepare_objects_query( $request );
		$args['post_status']     = array_keys( wc_get_order_statuses() );
		$args['post_parent__in'] = [];

		return $args;

	}

	/**
	 * Prepare a single order output for response.
	 *
	 * @since 1.9.22
	 *
	 * @param \WC_Data         $object  Object data.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {

		$this->request       = $request;
		$this->request['dp'] = is_null( $this->request['dp'] ) ? wc_get_price_decimals() : absint( $this->request['dp'] );

		if ( ! $object || ! $object->get_parent_id() ) {
			return new \WP_Error( 'atum_rest_invalid_order_refund_id', __( 'Invalid order refund ID.', ATUM_TEXT_DOMAIN ), 404 );
		}

		$object_type = Helpers::is_using_hpos_tables() ? OrderUtil::get_order_type( $object->get_parent_id() ) : get_post_type( $object->get_parent_id() );

		if ( 'shop_order' !== $object_type ) {
			return new \WP_Error( 'atum_rest_invalid_order_refund_id', __( 'Invalid order refund.', ATUM_TEXT_DOMAIN ), 404 );
		}

		$data    = $this->get_formatted_item_data( $object );
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \WC_Data          $object   Object data.
		 * @param \WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "atum/api/rest_prepare_{$this->post_type}_object", $response, $object, $request );

	}

	/**
	 * Get formatted item data.
	 *
	 * @since 1.9.22
	 *
	 * @param \WC_Data $object WC_Data instance.
	 *
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {

		$formatted_item              = parent::get_formatted_item_data( $object );
		$formatted_item['parent_id'] = $object->get_parent_id();

		return $formatted_item;

	}

}
