<?php
/**
 * REST ATUM API Product Locations controller
 *
 * Handles requests to the products/atum-locations endpoint.
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
