<?php
/**
 * REST ATUM API Dashboard controller
 * Handles requests to the /atum/dashboard endpoint.
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
use Atum\Modules\ModuleManager;

class DashboardController extends \WC_REST_Controller {

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
	protected $rest_base = 'atum/dashboard';

	/**
	 * Register the routes for dashboard
	 *
	 * @since 1.6.2
	 */
	public function register_routes() {

		if ( ModuleManager::is_module_active( 'dashboard' ) ) {

			register_rest_route( $this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			) );

		}

	}

	/**
	 * Get the Dashboard's schema, conforming to JSON Schema.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-dashboard',
			'type'       => 'object',
			'properties' => array(
				'slug'        => array(
					'description' => __( 'An alphanumeric identifier for the resource.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'description' => array(
					'description' => __( 'A human-readable description of the resource.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
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
	 * Check whether a given request has permission to read the dashboard
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! AtumCapabilities::current_user_can( 'view_statistics' ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot list resources.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Get reports list.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	protected function get_dashboard_widgets() {

		return array(
			array(
				'slug'        => 'statistics',
				'description' => __( 'Displays both: your earnings and product sales over time.', ATUM_TEXT_DOMAIN ),
			),
			array(
				'slug'        => 'sales',
				'description' => __( 'Displays all of your sales and number of products sold by day or month.', ATUM_TEXT_DOMAIN ),
			),
			array(
				'slug'        => 'lost-sales',
				'description' => __( 'Displays all of your lost revenue and number of products not sold by day or month.', ATUM_TEXT_DOMAIN ),
			),
			array(
				'slug'        => 'orders',
				'description' => __( 'Displays all of your orders by day, week or month.', ATUM_TEXT_DOMAIN ),
			),
			array(
				'slug'        => 'promo-sales',
				'description' => __( 'Displays all of your promo sales and number of promo products sold by day, week or month.', ATUM_TEXT_DOMAIN ),
			),
			array(
				'slug'        => 'stock-control',
				'description' => __( 'Displays the number of your products that are in stock, out of stock, running low and unmanaged.', ATUM_TEXT_DOMAIN ),
			),
			array(
				'slug'        => 'current-stock-value',
				'description' => __( 'Displays the total quantity of items physically in stock (that have known purchase price) and their cumulated purchase value.', ATUM_TEXT_DOMAIN ),
			),
		);

	}

	/**
	 * Get all dashboard endpoints
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array|\WP_Error
	 */
	public function get_items( $request ) {

		$data    = array();
		$widgets = $this->get_dashboard_widgets();

		foreach ( $widgets as $widget ) {
			$item   = $this->prepare_item_for_response( (object) $widget, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );

	}

	/**
	 * Prepare a dashboard object for serialization
	 *
	 * @since 1.6.2
	 *
	 * @param \stdClass        $widget  Report data.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $widget, $request ) {

		$data = array(
			'slug'        => $widget->slug,
			'description' => $widget->description,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $widget->slug ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		) );

		/**
		 * Filter a dashboard returned from the API.
		 * Allows modification of the dashboard data right before it is returned.
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param object            $widget   The original report object.
		 * @param \WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'atum/api/rest_prepare_dashboard', $response, $widget, $request );

	}

}
