<?php
/**
 * REST ATUM API Product Locations controller
 *
 * Handles requests to the products/atum-locations endpoint.
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


class ProductLocationsController extends \WC_REST_Product_Categories_Controller {

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
	protected $rest_base = 'products/atum-locations';

	/**
	 * Taxonomy.
	 *
	 * @var string
	 */
	protected $taxonomy = Globals::PRODUCT_LOCATION_TAXONOMY;

	/**
	 * Allowed data keys
	 *
	 * @var array
	 */
	protected $rest_data_keys = array(
		'id',
		'name',
		'slug',
		'parent',
		'description',
	);


	/**
	 * Get the Category schema, conforming to JSON Schema
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		// Remove unneded props.
		foreach ( $schema['properties'] as $key => $data ) {

			if ( ! in_array( $key, $this->rest_data_keys, TRUE ) ) {
				unset( $schema['properties'][ $key ] );
			}

		}

		return $this->add_additional_fields_schema( $schema );

	}

	/**
	 * Check permissions
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @param string           $context Request context.
	 *
	 * @return bool|\WP_Error
	 */
	protected function check_permissions( $request, $context = 'read' ) {

		// Get taxonomy.
		$taxonomy = $this->get_taxonomy( $request );
		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'atum_rest_taxonomy_invalid', __( 'Taxonomy does not exist.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$contexts = array(
			'read'   => 'manage_location_terms',
			'create' => 'edit_location_terms',
			'edit'   => 'edit_location_terms',
			'delete' => 'delete_location_terms',
			'batch'  => 'edit_location_terms',
		);

		// Check permissions for a single term.
		$id = absint( $request['id'] );
		if ( $id ) {

			$term = get_term( $id, $taxonomy );

			if ( is_wp_error( $term ) || ! $term || $term->taxonomy !== $taxonomy ) {
				return new \WP_Error( 'atum_rest_term_invalid', __( 'Resource does not exist.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
			}

			return AtumCapabilities::current_user_can( $contexts[ $context ], $term->term_id );

		}

		return AtumCapabilities::current_user_can( $contexts[ $context ] );

	}

	/**
	 * Prepare a single product category output for response
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_Term         $item    Term object.
	 * @param \WP_REST_Request $request Request instance.
	 *
	 * @return \WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {

		$data = array(
			'id'          => (int) $item->term_id,
			'name'        => $item->name,
			'slug'        => $item->slug,
			'parent'      => (int) $item->parent,
			'description' => $item->description,
			'count'       => (int) $item->count,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item, $request ) );

		/**
		 * Filter a term item returned from the API.
		 *
		 * Allows modification of the term data right before it is returned.
		 *
		 * @param \WP_REST_Response  $response  The response object.
		 * @param object             $item      The original term object.
		 * @param \WP_REST_Request   $request   Request used to generate the response.
		 */
		return apply_filters( "atum/api/rest_prepare_{$this->taxonomy}", $response, $item, $request );

	}

	/**
	 * Update term meta fields
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_Term         $term    Term object.
	 * @param \WP_REST_Request $request Request instance.
	 *
	 * @return bool|\WP_Error
	 */
	protected function update_term_meta_fields( $term, $request ) {

		// We don't have term meta fields for ATUM Locations actually.
		return TRUE;

	}

}
