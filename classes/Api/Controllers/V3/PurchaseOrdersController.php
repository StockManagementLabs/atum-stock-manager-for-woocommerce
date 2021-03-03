<?php
/**
 * REST ATUM API Purchase Orders controller
 * Handles requests to the /atum/purchase-orders endpoint.
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

use Atum\Inc\Helpers;
use Atum\PurchaseOrders\Items\POItemFee;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\Items\POItemShipping;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;

class PurchaseOrdersController extends AtumOrdersController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/purchase-orders';

	/**
	 * Post type
	 *
	 * @var string
	 */
	protected $post_type = PurchaseOrders::POST_TYPE;

	/**
	 * Allowed data keys for the PO
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
		'supplier',
		'multiple_suppliers',
		'date_expected',
		'date_expected_gmt',
		'line_items',
		'tax_lines',
		'shipping_lines',
		'fee_lines',
		'meta_data',
		'description',
	);


	/**
	 * Get the Purchase Order's schema, conforming to JSON Schema.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		$schema['properties']['date_expected'] = array(
			'description' => __( "The date when the purchase order is expected at location in the site's timezone.", ATUM_TEXT_DOMAIN ),
			'type'        => 'date-time',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['date_expected_gmt'] = array(
			'description' => __( 'The date when the purchase order is expected at location, as GMT.', ATUM_TEXT_DOMAIN ),
			'type'        => 'date-time',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['supplier'] = array(
			'description' => __( 'The supplier ID linked to the purchase order.', ATUM_TEXT_DOMAIN ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['multiple_suppliers'] = array(
			'description' => __( 'Whether the multiple_suppliers switch is enabled or not.', ATUM_TEXT_DOMAIN ),
			'type'        => 'boolean',
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

		$params['date_expected'] = array(
			'description'       => __( 'Limit result set to purchase orders expected at location on a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['supplier'] = array(
			'description'       => __( 'Limit result set to purchase orders linked to the specified supplier ID.', ATUM_TEXT_DOMAIN ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['multiple_suppliers'] = array(
			'description'       => __( 'Limit result set to purchase orders depending on their multiple_suppliers switch status.', ATUM_TEXT_DOMAIN ),
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;

	}

	/**
	 * Prepare a single purchase order for create or update.
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request  Request object.
	 * @param  bool             $creating If is creating a new object.
	 *
	 * @return \WP_Error|PurchaseOrder
	 *
	 * @throws \WC_REST_Exception When fails to set any item.
	 */
	protected function prepare_object_for_database( $request, $creating = FALSE ) {

		/**
		 * Variable definition
		 *
		 * @var PurchaseOrder $po
		 */
		$po = parent::prepare_object_for_database( $request, $creating );

		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		// If the expected date is coming as GMT, localize and save it.
		if ( in_array( 'date_expected_gmt', $data_keys ) && ! is_null( $request['date_expected_gmt'] ) ) {

			$date_value     = get_date_from_gmt( $request['date_expected_gmt'] );
			$formatted_date = Helpers::get_wc_time( $date_value );

			$po->set_date_expected( $formatted_date );

		}

		// All the POs must have a supplier or multiple_suppliers set (any of them).
		if ( ! $po->get_supplier( 'id' ) && ! $po->has_multiple_suppliers() ) {
			$po->set_multiple_suppliers( TRUE );
		}

		return $po;

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

		// Expected at location date.
		if ( ! empty( $request['date_expected'] ) ) {

			$args['meta_query'] = array(
				array(
					'key'   => '_date_expected',
					'value' => $request['date_expected'],
					'type'  => 'DATETIME',
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
	 * @param PurchaseOrder $object PurchaseOrder instance.
	 *
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {

		// Format the specific purchase order's data.
		$formatted_data = parent::get_formatted_item_data( $object );
		$format_date    = [ 'date_expected' ];

		// Format date values.
		foreach ( $format_date as $key ) {
			if ( in_array( $key, $this->rest_data_keys ) ) {
				$datetime                       = $formatted_data[ $key ];
				$formatted_data[ $key ]         = wc_rest_prepare_date_response( $datetime, FALSE );
				$formatted_data[ "{$key}_gmt" ] = wc_rest_prepare_date_response( $datetime );
			}
		}

		return $formatted_data;

	}

	/**
	 * Create or update a line item
	 *
	 * @since 1.6.2
	 *
	 * @param array  $posted Line item data.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @param object $item Passed when updating an item. NULL during creation.
	 *
	 * @return POItemProduct
	 *
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_line_items( $posted, $action = 'create', $item = NULL ) {

		// TODO: IF THIS PO HAS A SUPPLIER ASSIGNED WE SHOULD ONLY ALLOW TO ADD PRODUCTS LINKED TO IT.
		$item = is_null( $item ) ? new POItemProduct( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;
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
	 * @param object $item Passed when updating an item. NULL during creation.
	 *
	 * @return POItemShipping
	 *
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_shipping_lines( $posted, $action = 'create', $item = NULL ) {

		$item = is_null( $item ) ? new POItemShipping( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;
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
	 * @param object $item Passed when updating an item. NULL during creation.
	 *
	 * @return POItemFee
	 *
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_fee_lines( $posted, $action = 'create', $item = NULL ) {

		$item = is_null( $item ) ? new POItemFee( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;
		$this->prepare_item_fee( $posted, $action, $item );

		return $item;

	}

}
