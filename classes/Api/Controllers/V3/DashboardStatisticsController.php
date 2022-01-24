<?php
/**
 * REST ATUM API Dashboard Statistics widget controller
 * Handles requests to the /atum/dashboard/statistics endpoint.
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

class DashboardStatisticsController extends DashboardWidgetController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/dashboard/statistics';

	/**
	 * Get the Statistics' schema, conforming to JSON Schema
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-dashboard-statistics',
			'type'       => 'object',
			'properties' => array(
				'dataset' => array(
					'description' => __( 'The collection of data for the statistics.', ATUM_TEXT_DOMAIN ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'value'    => array(
								'description' => __( 'An array of all the money values for the period.', ATUM_TEXT_DOMAIN ),
								'type'        => 'array',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
								'items'       => array(
									'type' => 'number',
								),
							),
							'products' => array(
								'description' => __( 'An array with all the product quantities for the period.', ATUM_TEXT_DOMAIN ),
								'type'        => 'array',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
								'items'       => array(
									'type' => 'number',
								),
							),
						),
					),
				),
				'period'  => array(
					'description' => __( 'The period window used to get the statistics for.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'legends' => array(
					'description' => __( 'The legends used on the statistics charts.', ATUM_TEXT_DOMAIN ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
					'items'       => array(
						'type'       => 'string',
						'properties' => array(
							'value'    => array(
								'description' => __( "The label for the values' data.", ATUM_TEXT_DOMAIN ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
							),
							'products' => array(
								'description' => __( "The label for the products' data.", ATUM_TEXT_DOMAIN ),
								'type'        => 'array',
								'context'     => array( 'view' ),
								'readonly'    => TRUE,
								'items'       => array(
									'type' => 'number',
								),
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

		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			'data'    => array(
				'description'       => __( 'The type of data to return.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'enum'              => array( 'sales', 'lost-sales', 'promo-sales', 'orders' ),
				'default'           => 'sales',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'period'  => array(
				'description'       => __( 'The period to get statistics from.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'enum'              => array(
					'this_year',
					'previous_year',
					'this_month',
					'previous_month',
					'this_week',
					'previous_week',
				),
				'default'           => 'this_year',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

	}

	/**
	 * Prepare a statistics object for serialization
	 *
	 * @since 1.6.2
	 *
	 * @param null             $_
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $_, $request ) {

		$chart_data   = $request['data'];
		$chart_period = $request['period'];
		$data         = array();

		switch ( $chart_data ) {
			case 'sales':
				$data['dataset'] = WidgetHelpers::get_sales_chart_data( $chart_period );
				$data['legends'] = array(
					'value'    => __( 'Sales', ATUM_TEXT_DOMAIN ),
					'products' => __( 'Products', ATUM_TEXT_DOMAIN ),
				);
				break;

			case 'lost-sales':
				$data['dataset'] = WidgetHelpers::get_sales_chart_data( $chart_period, [ 'lost_sales' ] );
				$data['legends'] = array(
					'value'    => __( 'Lost Sales', ATUM_TEXT_DOMAIN ),
					'products' => __( 'Products', ATUM_TEXT_DOMAIN ),
				);
				break;

			case 'promo-sales':
				$data['dataset'] = WidgetHelpers::get_promo_sales_chart_data( $chart_period );
				$data['legends'] = array(
					'value'    => __( 'Sales', ATUM_TEXT_DOMAIN ),
					'products' => __( 'Products', ATUM_TEXT_DOMAIN ),
				);
				break;

			case 'orders':
				$data['dataset'] = WidgetHelpers::get_orders_chart_data( $chart_period );
				$data['legends'] = array(
					'value'    => __( 'Value', ATUM_TEXT_DOMAIN ),
					'products' => __( 'Orders', ATUM_TEXT_DOMAIN ),
				);
				break;

		}

		if ( strpos( $chart_period, 'year' ) !== FALSE ) {
			$data['period'] = 'month';
		}
		elseif ( strpos( $chart_period, 'month' ) !== FALSE ) {
			$data['period'] = 'monthDay';
		}
		else {
			$data['period'] = 'weekDay';
		}

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
		 * Filter the statistics returned from the API.
		 * Allows modification of the data right before it is returned.
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \stdClass         $data     The original statistics widget object.
		 * @param \WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'atum/api/rest_prepare_dashboard_statistics', $response, (object) $data, $request );

	}

}
