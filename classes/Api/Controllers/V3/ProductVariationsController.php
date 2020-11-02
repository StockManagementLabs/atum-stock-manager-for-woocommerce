<?php
/**
 * REST ATUM API Product Variations controller
 * Handles requests to the /atum/product-variations endpoint.
 * The purpose of this class is to handle BATCH actions for all the variations at once (no matter its parent product) with one request.
 *
 * @since       1.8.0
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2020 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

class ProductVariationsController extends \WC_REST_Product_Variations_Controller {

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
	protected $rest_base = 'atum/product-variations';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'product';


	/**
	 * Register routes
	 *
	 * @since 1.8.0
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/batch', array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);

	}

	/**
	 * Bulk create, update and delete items.
	 *
	 * @since 1.8.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return array of \WP_Error or \WP_REST_Response.
	 */
	public function batch_items( $request ) {

		$items = array_filter( $request->get_params() );
		$query = $request->get_query_params();

		$request = new \WP_REST_Request( $request->get_method() );
		$request->set_body_params( $items );
		$request->set_query_params( $query );

		// Call the grandparent class instead of the parent (WC_REST_Product_Variations_Controller) class.
		return \WC_REST_CRUD_Controller::batch_items( $request );

	}

	/**
	 * Save an object data.
	 *
	 * @since  1.8.0
	 *
	 * @param  \WP_REST_Request $request  Full details about the request.
	 * @param  bool             $creating If is creating a new object.
	 *
	 * @return \WC_Data|\WP_Error
	 */
	protected function save_object( $request, $creating = false ) {

		// Avoid the parent_id to be overridden by WC when no being sent on every batch update.
		if ( isset( $request['id'] ) && ! isset( $request['product_id'] ) ) {
			$variation = wc_get_product( absint( $request['id'] ) );
			$request->set_url_params( [ 'product_id' => $variation->get_parent_id() ] );
		}

		return parent::save_object( $request, $creating );

	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 1.8.0
	 *
	 * @param \WC_Data         $object  Object data.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {

		$product_id = $object->get_parent_id();
		$base       = "products/$product_id/variations"; // Use the "WC_REST_Product_Variations_Controller" rest base instead.
		$links      = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $object->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
			'up'         => array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $product_id ) ),
			),
		);

		return $links;

	}

}
