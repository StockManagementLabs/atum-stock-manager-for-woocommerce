<?php
/**
 * REST ATUM API Tools controller
 * Handles requests to the /atum/tools endpoint.
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

use Atum\Components\AtumCapabilities;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;

class ToolsController extends \WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/tools';

	/**
	 * Register the routes for tools
	 *
	 * @since 1.6.2
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', ATUM_TEXT_DOMAIN ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-tools',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'A unique identifier for the tool.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'name'        => array(
					'description' => __( 'Tool nice name.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'description' => array(
					'description' => __( 'Tool description.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'config'      => array(
					'description' => __( 'If the tool needs config in order to work.', ATUM_TEXT_DOMAIN ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
				),
				'success'     => array(
					'description' => __( 'Did the tool run successfully?', ATUM_TEXT_DOMAIN ),
					'type'        => 'boolean',
					'context'     => array( 'edit' ),
				),
				'message'     => array(
					'description' => __( 'Tool return message.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'result'      => array(
					'description' => __( 'Data returned in case there were.', ATUM_TEXT_DOMAIN ),
					'type'        => 'array',
					'context'     => array( 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );

	}

	/**
	 * Get the query params for collections
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_collection_params() {

		return array( 'context' => $this->get_context_param( array( 'default' => 'view' ) ) );

	}

	/**
	 * Check whether a given request has permission to read the tools
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! AtumCapabilities::current_user_can( 'manage_settings' ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot list resources.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check whether a given request has permission to view a specific ATUM tool
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {

		if ( ! AtumCapabilities::current_user_can( 'manage_settings' ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot view this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check whether a given request has permission to execute a specific ATUM tool
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {

		if ( ! AtumCapabilities::current_user_can( 'manage_settings' ) ) {
			return new \WP_Error( 'atum_rest_cannot_update', __( 'Sorry, you cannot update resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * A list of available tools for use in the ATUM Settings section
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_tools() {

		$tools = array(
			'enable-manage-stock'       => array(
				'name' => __( "Enable WC's Manage Stock", ATUM_TEXT_DOMAIN ),
				'desc' => __( "Enable the WooCommerce's manage stock at product level for all the products at once.", ATUM_TEXT_DOMAIN ),
			),
			'disable-manage-stock'      => array(
				'name' => __( "Disable WC's Manage Stock", ATUM_TEXT_DOMAIN ),
				'desc' => __( "Disable the WooCommerce's manage stock at product level for all the products at once.", ATUM_TEXT_DOMAIN ),
			),
			'enable-atum-control'       => array(
				'name' => __( 'Enable ATUM Stock Control', ATUM_TEXT_DOMAIN ),
				'desc' => __( "Enable the ATUM's stock control option for all the products at once.", ATUM_TEXT_DOMAIN ),
			),
			'disable-atum-control'      => array(
				'name' => __( 'Disable ATUM Stock Control', ATUM_TEXT_DOMAIN ),
				'desc' => __( "Disable the ATUM's stock control option for all the products at once.", ATUM_TEXT_DOMAIN ),
			),
			'clear-out-stock-threshold' => array(
				'name' => __( 'Clear Out of Stock Threshold', ATUM_TEXT_DOMAIN ),
				'desc' => __( 'Clear all previously saved Out of Stock Threshold values.', ATUM_TEXT_DOMAIN ),
			),
		);

		return apply_filters( 'atum/api/tools', $tools );

	}

	/**
	 * Get a list of ATUM tools
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {

		$tools = array();
		foreach ( $this->get_tools() as $id => $tool ) {

			$tools[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response(
					array(
						'id'          => $id,
						'name'        => $tool['name'],
						'description' => $tool['desc'],
					),
					$request
				)
			);

		}

		return rest_ensure_response( $tools );

	}

	/**
	 * Return a single tool
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {

		$tools = $this->get_tools();

		if ( empty( $tools[ $request['id'] ] ) ) {
			return new \WP_Error( 'atum_rest_tool_invalid_id', __( 'Invalid tool ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$tool = $tools[ $request['id'] ];

		return rest_ensure_response(
			$this->prepare_item_for_response(
				array(
					'id'          => $request['id'],
					'name'        => $tool['name'],
					'description' => $tool['desc'],
				),
				$request
			)
		);

	}

	/**
	 * Update (run) a tool
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {

		$tools = $this->get_tools();

		if ( empty( $tools[ $request['id'] ] ) ) {
			return new \WP_Error( 'atum_rest_tool_invalid_id', __( 'Invalid tool ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$tool = $tools[ $request['id'] ];
		$tool = array(
			'id'          => $request['id'],
			'name'        => $tool['name'],
			'description' => $tool['desc'],
		);

		if ( ! empty( $request['config'] ) ) {
			$tool['config'] = $request['config'];
		}

		$run_return = $this->run_tool( $request['id'], $request );
		$tool       = array_merge( $tool, $run_return );

		/**
		 * Fires after an ATUM tool has been executed.
		 *
		 * @param array            $tool    Details about the tool that has been executed.
		 * @param \WP_REST_Request $request The current WP_REST_Request object.
		 */
		do_action( 'atum/api/rest_run_tool', $tool, $request );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $tool, $request );

		return rest_ensure_response( $response );

	}

	/**
	 * Prepare a tool item for serialization
	 *
	 * @since 1.6.2
	 *
	 * @param  array            $item     Object.
	 * @param  \WP_REST_Request $request  Request object.
	 *
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {

		$context = empty( $request['context'] ) ? 'view' : $request['context'];
		$data    = $this->add_additional_fields_to_object( $item, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item['id'] ) );

		return $response;

	}

	/**
	 * Prepare links for the request
	 *
	 * @since 1.6.2
	 *
	 * @param string $id ID.
	 *
	 * @return array
	 */
	protected function prepare_links( $id ) {

		$base = '/' . $this->namespace . '/' . $this->rest_base;

		return array(
			'item' => array(
				'href'       => rest_url( trailingslashit( $base ) . $id ),
				'embeddable' => true,
			),
		);

	}

	/**
	 * Runs a tool
	 *
	 * @since 1.6.2
	 *
	 * @param string           $tool    The tool ID.
	 * @param \WP_REST_Request $request The request.
	 *
	 * @return array
	 */
	public function run_tool( $tool, $request ) {

		$ran = TRUE;

		switch ( $tool ) {
			case 'enable-manage-stock':
				$message = Helpers::change_status_meta( '_manage_stock', 'yes', TRUE );
				break;

			case 'disable-manage-stock':
				$message = Helpers::change_status_meta( '_manage_stock', 'no', TRUE );
				break;

			case 'enable-atum-control':
				$message = Helpers::change_status_meta( Globals::ATUM_CONTROL_STOCK_KEY, 'yes', TRUE );
				break;

			case 'disable-atum-control':
				$message = Helpers::change_status_meta( Globals::ATUM_CONTROL_STOCK_KEY, 'no', TRUE );
				break;

			case 'clear-out-stock-threshold':
				Helpers::force_rebuild_stock_status( NULL, TRUE, TRUE );

				if ( FALSE === Helpers::is_any_out_stock_threshold_set() ) {
					$message = __( 'All your previously saved values were cleared successfully.', ATUM_TEXT_DOMAIN );
				}
				else {
					$message = __( 'Something failed clearing the Out of Stock Threshold values', ATUM_TEXT_DOMAIN );
				}

				break;

			default:
				$tools = $this->get_tools();

				if ( isset( $tools[ $tool ]['callback'] ) ) {

					$callback = $tools[ $tool ]['callback'];
					$return   = call_user_func( $callback, $request );

					if ( is_string( $return ) ) {
						$message = $return;
					}
					elseif ( FALSE === $return ) {
						$callback_string = is_array( $callback ) ? get_class( $callback[0] ) . '::' . $callback[1] : $callback;
						$ran             = FALSE;
						/* translators: %s: callback string */
						$message = sprintf( __( 'There was an error calling %s', ATUM_TEXT_DOMAIN ), $callback_string );
					}
					elseif ( is_array( $return ) ) {
						$message = $return['message'];
						$data    = $return['result'];
					}
					else {
						$message = __( 'Tool ran.', ATUM_TEXT_DOMAIN );
					}

				}
				else {
					$ran     = FALSE;
					$message = __( 'There was an error calling this tool. There is no callback present.', ATUM_TEXT_DOMAIN );
				}

				break;
		}

		$result = [
			'success' => $ran,
			'message' => $message,
		];

		if ( ! empty( $data ) ) {
			$result['result'] = $data;
		}

		return $result;

	}

}
