<?php
/**
 * Abstract class for the REST ATUM API Dashboard Widgets
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

use Atum\Components\AtumCapabilities;
use Atum\Modules\ModuleManager;

abstract class DashboardWidgetController extends \WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Register the routes for the Dashboard widget
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
	 * Check whether a given request has permission to read widget data
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! AtumCapabilities::current_user_can( 'view_statistics' ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot list resources.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Get widget items
	 *
	 * @since 1.6.2
	 *
	 * @param |WP_REST_Request $request
	 *
	 * @return array|\WP_Error
	 */
	public function get_items( $request ) {

		$data   = array();
		$item   = $this->prepare_item_for_response( NULL, $request );
		$data[] = $this->prepare_response_for_collection( $item );

		return rest_ensure_response( $data );

	}

}
