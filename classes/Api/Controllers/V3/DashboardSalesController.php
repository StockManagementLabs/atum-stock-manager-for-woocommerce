<?php
/**
 * REST ATUM API Dashboard Sales widget controller
 * Handles requests to the /atum/dashboard/sales endpoint.
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

use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;


class DashboardSalesController extends DashboardWidgetController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/dashboard/sales';

	/**
	 * Get the sales schema, conforming to JSON Schema
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-dashboard-sales',
			'type'       => 'object',
			'properties' => array(
				'data'   => array(
					'description' => __( 'The sales data.', ATUM_TEXT_DOMAIN ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'value'    => array(
								'description' => __( 'The value of all the sales in a given period.', ATUM_TEXT_DOMAIN ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
							),
							'products' => array(
								'description' => __( 'The amount of products sold in a given period.', ATUM_TEXT_DOMAIN ),
								'type'        => 'number',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
							),
						),
					),
				),
				'period' => array(
					'description' => __( 'The period window used to get the sales data for.', ATUM_TEXT_DOMAIN ),
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

		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			'period'  => array(
				'description'       => __( 'The period to get sales data from.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'enum'              => array(
					'today',
					'month',
				),
				'default'           => 'today',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

	}

	/**
	 * Prepare a sales object for serialization
	 *
	 * @since 1.6.2
	 *
	 * @param null             $_
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $_, $request ) {

		$period = $request['period'];
		$data   = array(
			'period' => $period,
		);

		$data['data'] = WidgetHelpers::get_sales_stats( array(
			'types'      => array( 'sales' ),
			'date_start' => 'today' === $period ? 'today midnight' : 'first day of this month midnight',
		) );

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
		 * @param \stdClass         $data     The original sales widget object.
		 * @param \WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'atum/api/rest_prepare_dashboard_sales', $response, (object) $data, $request );

	}

}
