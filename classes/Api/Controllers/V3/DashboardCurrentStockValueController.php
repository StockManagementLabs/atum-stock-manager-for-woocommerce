<?php
/**
 * REST ATUM API Dashboard Current Stock Value widget controller
 * Handles requests to the /atum/dashboard/stock-control endpoint.
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

use Atum\Dashboard\WidgetHelpers;

class DashboardCurrentStockValueController extends DashboardWidgetController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/dashboard/current-stock-value';

	/**
	 * Get the current stock value schema, conforming to JSON Schema
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-dashboard-current-stock-value',
			'type'       => 'object',
			'properties' => array(
				'current_stock_values' => array(
					'description' => __( 'The current stock value data.', ATUM_TEXT_DOMAIN ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'items_stock_counter'          => array(
								'description' => __( 'The total quantity of items physically in stock (that have known purchase price).', ATUM_TEXT_DOMAIN ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
							),
							'items_purchase_price_total'   => array(
								'description' => __( 'The cumulated purchase price value.', ATUM_TEXT_DOMAIN ),
								'type'        => 'number',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
							),
							'items_without_purchase_price' => array(
								'description' => __( 'The total amount of products that have no purchase price set.', ATUM_TEXT_DOMAIN ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
							),
						),
					),
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
	 * Prepare a current stock value object for serialization
	 *
	 * @since 1.6.2
	 *
	 * @param null             $_
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $_, $request ) {

		$data['current_stock_values'] = WidgetHelpers::get_items_in_stock();

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( array(
			'about' => array(
				'href' => rest_url( sprintf( '%s/atum/dashboard', $this->namespace ) ),
			),
		) );

		/**
		 * Filter the data returned from the API.
		 * Allows modification of the data right before it is returned.
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \stdClass         $data     The original current stock value widget object.
		 * @param \WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'atum/api/rest_prepare_dashboard_current_stock_value', $response, (object) $data, $request );

	}

}
