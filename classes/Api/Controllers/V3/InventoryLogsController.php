<?php
/**
 * REST ATUM API Inventory Logs controller
 * Handles requests to the /atum/inventory-logs endpoint.
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

use Atum\Inc\Helpers;
use Atum\InventoryLogs\InventoryLogs;
use Atum\InventoryLogs\Items\LogItemFee;
use Atum\InventoryLogs\Items\LogItemProduct;
use Atum\InventoryLogs\Items\LogItemShipping;
use Atum\InventoryLogs\Models\Log;

class InventoryLogsController extends AtumOrdersController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/inventory-logs';

	/**
	 * Post type
	 *
	 * @var string
	 */
	protected $post_type = InventoryLogs::POST_TYPE;

	/**
	 * Allowed data keys for the Log
	 *
	 * @var array
	 */
	protected $rest_data_keys = array(
		'id',
		'status',
		'currency',
		'date_created',
		'date_created_gmt',
		'date_modified',
		'date_modified_gmt',
		'discount_total',
		'discount_tax',
		'shipping_total',
		'shipping_tax',
		'cart_tax',
		'total',
		'total_tax',
		'prices_include_tax',
		'date_completed',
		'date_completed_gmt',
		'type',
		'order',
		'reservation_date',
		'reservation_date_gmt',
		'return_date',
		'return_date_gmt',
		'damage_date',
		'damage_date_gmt',
		'shipping_company',
		'custom_name',
		'line_items',
		'tax_lines',
		'shipping_lines',
		'fee_lines',
		'meta_data',
		'description',
	);


	/**
	 * Get the Inventory Log's schema, conforming to JSON Schema.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		$schema['properties']['type'] = array(
			'description' => __( 'The log type.', ATUM_TEXT_DOMAIN ),
			'type'        => 'string',
			'enum'        => array_keys( Log::get_log_types() ),
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['order'] = array(
			'description' => __( "The WooCommerce's order ID to which this Log is linked to.", ATUM_TEXT_DOMAIN ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['reservation_date'] = array(
			'description' => __( "The date for when the stock is reserved, in the site's timezone.", ATUM_TEXT_DOMAIN ),
			'type'        => 'date-time',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['reservation_date_gmt'] = array(
			'description' => __( 'The date for when the stock is reserved, as GMT.', ATUM_TEXT_DOMAIN ),
			'type'        => 'date-time',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['return_date'] = array(
			'description' => __( "The date for when the customer returned the stock, in the site's timezone.", ATUM_TEXT_DOMAIN ),
			'type'        => 'date-time',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['return_date_gmt'] = array(
			'description' => __( 'The date for when the customer returned the stock, as GMT.', ATUM_TEXT_DOMAIN ),
			'type'        => 'date-time',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['damage_date'] = array(
			'description' => __( "The date for when the stock was damaged at warehouse, in the site's timezone.", ATUM_TEXT_DOMAIN ),
			'type'        => 'date-time',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['damage_date_gmt'] = array(
			'description' => __( 'The date for when the stock was damaged at warehouse, as GMT.', ATUM_TEXT_DOMAIN ),
			'type'        => 'date-time',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['shipping_company'] = array(
			'description' => __( 'The name of the company that lost in post.', ATUM_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['custom_name'] = array(
			'description' => __( "The custom name for the 'other' log types.", ATUM_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		return $schema;

	}

	/**
	 * Get the query params for collections.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		$params['type'] = array(
			'description'       => __( 'Limit result set to inventory logs of the specified type(s).', ATUM_TEXT_DOMAIN ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_keys( Log::get_log_types() ),
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order_id'] = array(
			'description'       => __( 'Limit result set to inventory logs linked to the specified WC order ID.', ATUM_TEXT_DOMAIN ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['reservation_date'] = array(
			'description'       => __( 'Limit result set to inventory logs with the reservation date set on a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['return_date'] = array(
			'description'       => __( 'Limit result set to inventory logs with the return date set on a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['damage_date'] = array(
			'description'       => __( 'Limit result set to inventory logs with the damage date set on a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['shipping_company'] = array(
			'description'       => __( 'Limit result set to the inventory logs where the specified company lost the stock in post.', ATUM_TEXT_DOMAIN ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['custom_name'] = array(
			'description'       => __( "Limit result set to the inventory logs with type 'other' and the specified custom name.", ATUM_TEXT_DOMAIN ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;

	}

	/**
	 * Prepare a single inventory log for create or update.
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request  Request object.
	 * @param  bool             $creating If is creating a new object.
	 *
	 * @return \WP_Error|Log
	 *
	 * @throws \WC_REST_Exception When fails to set any item.
	 */
	protected function prepare_object_for_database( $request, $creating = FALSE ) {

		/**
		 * Variable definition
		 *
		 * @var Log $log
		 */
		$log = parent::prepare_object_for_database( $request, $creating );

		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		// Log type's setter does not follow the naming convention.
		if ( in_array( 'type', $data_keys, TRUE ) && ! is_null( $request['type'] ) ) {
			$log->set_type( $request['type'] );
		}

		// If any of the IL dates are coming as GMT, localize and save them.
		foreach ( [ 'reservation_date', 'return_date', 'damage_date' ] as $date_key ) {

			if ( in_array( "{$date_key}_gmt", $data_keys ) && ! is_null( $request[ "{$date_key}_gmt" ] ) ) {

				$date_value     = get_date_from_gmt( $request[ "{$date_key}_gmt" ] );
				$formatted_date = Helpers::get_wc_time( $date_value );

				if ( is_callable( array( $log, "set_{$date_key}" ) ) ) {
					$log->{"set_{$date_key}"}( $formatted_date );
				}

			}

		}

		return $log;

	}

	/**
	 * Prepare objects query.
	 *
	 * @since  1.6.2
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {

		$args = parent::prepare_objects_query( $request );

		// Log type.
		if ( ! empty( $request['type'] ) ) {

			$args['meta_query'] = array(
				array(
					'key'   => '_type',
					'value' => $request['type'],
				),
			);

		}

		// Linked WC order.
		if ( ! empty( $request['order_id'] ) ) {

			$args['meta_query'] = array(
				array(
					'key'   => '_order',
					'value' => $request['order_id'],
					'type'  => 'NUMERIC',
				),
			);

		}

		// Reservation date.
		if ( ! empty( $request['reservation_date'] ) && ( empty( $request['type'] ) || 'reserved-stock' === $request['type'] ) ) {

			$args['meta_query'] = array(
				array(
					'key'   => '_reservation_date',
					'value' => $request['reservation_date'],
					'type'  => 'DATETIME',
				),
			);

		}

		// Customer returns date.
		if ( ! empty( $request['return_date'] ) && ( empty( $request['type'] ) || 'customer-returns' === $request['type'] ) ) {

			$args['meta_query'] = array(
				array(
					'key'   => '_return_date',
					'value' => $request['return_date'],
					'type'  => 'DATETIME',
				),
			);

		}

		// Warehouse damage date.
		if ( ! empty( $request['damage_date'] ) && ( empty( $request['type'] ) || 'warehouse-damage' === $request['type'] ) ) {

			$args['meta_query'] = array(
				array(
					'key'   => '_damage_date',
					'value' => $request['damage_date'],
					'type'  => 'DATETIME',
				),
			);

		}

		// Shipping company.
		if ( ! empty( $request['shipping_company'] ) && ( empty( $request['type'] ) || 'lost-in-post' === $request['type'] ) ) {

			$args['meta_query'] = array(
				array(
					'key'   => '_shipping_company',
					'value' => $request['shipping_company'],
				),
			);

		}

		// Custom log type name.
		if ( ! empty( $request['custom_name'] ) && ( empty( $request['type'] ) || 'other' === $request['type'] ) ) {

			$args['meta_query'] = array(
				array(
					'key'   => '_custom_name',
					'value' => $request['custom_name'],
				),
			);

		}

		return $args;

	}

	/**
	 * Get formatted item data.
	 *
	 * @since  1.6.2
	 *
	 * @param Log $object Log instance.
	 *
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {

		// Format the specific inventory log's data.
		$formatted_data     = parent::get_formatted_item_data( $object );
		$format_date        = [ 'reservation_date', 'return_date', 'damage_date' ];
		$conditional_fields = array(
			'reservation_date',
			'reservation_date_gmt',
			'return_date',
			'return_date_gmt',
			'damage_date',
			'damage_date_gmt',
			'shipping_company',
			'custom_name',
		);

		// Filter out some fields depending on the Log type.
		switch ( $formatted_data['type'] ) {
			case 'reserved-stock':
				unset(
					$conditional_fields[ array_search( 'reservation_date', $conditional_fields ) ],
					$conditional_fields[ array_search( 'reservation_date_gmt', $conditional_fields ) ]
				);
				break;

			case 'customer-returns':
				unset(
					$conditional_fields[ array_search( 'return_date', $conditional_fields ) ],
					$conditional_fields[ array_search( 'return_date_gmt', $conditional_fields ) ]
				);
				break;

			case 'warehouse-damage':
				unset(
					$conditional_fields[ array_search( 'damage_date', $conditional_fields ) ],
					$conditional_fields[ array_search( 'damage_date_gmt', $conditional_fields ) ]
				);
				break;

			case 'lost-in-post':
				unset( $conditional_fields[ array_search( 'shipping_company', $conditional_fields ) ] );
				break;

			case 'other':
				unset( $conditional_fields[ array_search( 'custom_name', $conditional_fields ) ] );
				break;
		}

		$object_rest_data_keys = array_diff( $this->rest_data_keys, $conditional_fields );

		// Format date values.
		foreach ( $format_date as $key ) {
			if ( in_array( $key, $object_rest_data_keys ) ) {
				$datetime                       = $formatted_data[ $key ];
				$formatted_data[ $key ]         = wc_rest_prepare_date_response( $datetime, FALSE );
				$formatted_data[ "{$key}_gmt" ] = wc_rest_prepare_date_response( $datetime );
			}
		}

		// Format the order ID.
		if ( ! empty( $formatted_data['order'] ) ) {
			$formatted_data['order'] = $formatted_data['order']->get_id();
		}

		$formatted_data = array_intersect_key( $formatted_data, array_flip( $object_rest_data_keys ) );

		return $formatted_data;

	}

	/**
	 * Create or update a line item
	 *
	 * @since 1.6.2
	 *
	 * @param array  $posted Line item data.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 *
	 * @return LogItemProduct
	 *
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_line_items( $posted, $action = 'create', $item = NULL ) {

		// TODO: IF THIS LOG HAS AN ORDER ASSIGNED WE SHOULD ONLY ALLOW TO ADD PRODUCTS LINKED TO IT.
		$item = is_null( $item ) ? new LogItemProduct( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;
		$this->prepare_item_product( $posted, $action, $item );

		return $item;

	}

	/**
	 * Create or update an order shipping method
	 *
	 * @since 1.6.2
	 *
	 * @param array  $posted $shipping Item data.
	 * @param string $action 'create' to add shipping or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 *
	 * @return LogItemShipping
	 *
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_shipping_lines( $posted, $action = 'create', $item = NULL ) {

		$item = is_null( $item ) ? new LogItemShipping( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;
		$this->prepare_item_shipping( $posted, $action, $item );

		return $item;

	}

	/**
	 * Create or update an order fee
	 *
	 * @since 1.6.2
	 *
	 * @param array  $posted Item data.
	 * @param string $action 'create' to add fee or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 *
	 * @return LogItemFee
	 *
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_fee_lines( $posted, $action = 'create', $item = NULL ) {

		$item = is_null( $item ) ? new LogItemFee( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;
		$this->prepare_item_fee( $posted, $action, $item );

		return $item;

	}

}
