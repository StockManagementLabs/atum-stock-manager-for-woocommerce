<?php
/**
 * REST ATUM API Addons controller
 * Handles requests to the /atum/addons endpoint.
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

use Atum\Addons\Addons;

class AddonsController extends \WC_REST_Controller {

	/**
	 * Endpoint namespace
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/addons';


	/**
	 * Register routes
	 *
	 * @since 1.6.2
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}


	/**
	 * Get the items schema, conforming to JSON Schema.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-addons',
			'type'       => 'object',
			'properties' => array(
				'name'   => array(
					'description' => __( 'The add-on name.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'key'    => array(
					'description' => __( 'The license key for the purchased add-on.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'status' => array(
					'description' => __( 'The license key status.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );

	}

	/**
	 * Makes sure the current user has access to READ the ATUM addons
	 *
	 * @since  1.6.2
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot list resources.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Get all the add-ons' settings
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ) {

		$name              = ! empty( $request['name'] ) ? $request['name'] : '';
		$registered_addons = Addons::get_keys( $name );
		$formatted_addons  = [];

		if ( $name && ! empty( $registered_addons ) ) {
			return rest_ensure_response( array_merge( [ 'name' => $request['name'] ], $registered_addons ) );
		}

		foreach ( $registered_addons as $name => $addon ) {
			$formatted_addons[] = array_merge( [ 'name' => $name ], $addon );
		}

		return rest_ensure_response( $formatted_addons );

	}

}
