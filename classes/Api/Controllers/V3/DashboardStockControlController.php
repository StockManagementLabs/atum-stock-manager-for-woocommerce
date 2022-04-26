<?php
/**
 * REST ATUM API Dashboard Stock Control widget controller
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

class DashboardStockControlController extends DashboardWidgetController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/dashboard/stock-control';

	/**
	 * Get the stock control schema, conforming to JSON Schema
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-dashboard-stock-control',
			'type'       => 'object',
			'properties' => array(
				'stock_counters' => array(
					'description' => __( 'The stock control data.', ATUM_TEXT_DOMAIN ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'count_in_stock'       => array(
								'description' => __( 'The total amount of products in stock.', ATUM_TEXT_DOMAIN ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
							),
							'count_out_stock'      => array(
								'description' => __( 'The total amount of products out of stock.', ATUM_TEXT_DOMAIN ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
							),
							'count_restock_status' => array(
								'description' => __( 'The total amount of products in restock status.', ATUM_TEXT_DOMAIN ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
							),
							'count_all'            => array(
								'description' => __( 'The total amount of products.', ATUM_TEXT_DOMAIN ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
							),
							'count_unmanaged'      => array(
								'description' => __( 'The total amount of products that are not being managed by WooCommerce.', ATUM_TEXT_DOMAIN ),
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
	 * Prepare a stock control object for serialization
	 *
	 * @since 1.6.2
	 *
	 * @param null             $_
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $_, $request ) {

		$data['stock_counters'] = WidgetHelpers::get_stock_levels();

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
		 * @param \stdClass         $data     The original stock control widget object.
		 * @param \WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'atum/api/rest_prepare_dashboard_stock_control', $response, (object) $data, $request );

	}

}
