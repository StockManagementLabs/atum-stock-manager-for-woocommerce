<?php
/**
 * REST ATUM API Purchase Orders controller
 * Handles requests to the /atum/purchase-orders endpoint.
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

use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Helpers;
use Atum\PurchaseOrders\Items\POItemFee;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\Items\POItemShipping;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;

class PurchaseOrdersController extends \WC_REST_Orders_Controller {

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
	);

	/**
	 * Get object. Return false if object is not of required type.
	 *
	 * @since  1.6.2
	 *
	 * @param  int|\WP_Post $post Object ID.
	 *
	 * @return PurchaseOrder|bool
	 */
	protected function get_object( $post ) {

		$id = is_a( $post, '\WP_Post' ) ? $post->ID : $post;

		$po = new PurchaseOrder( $id );

		// In case id is not a PO, don't expose it via /orders/ path.
		if ( ! $po ) {
			return FALSE;
		}

		return $po;

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

		$id        = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$po        = $this->get_object( $id );
		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		// Handle all writable props.
		foreach ( $data_keys as $key ) {

			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {

				switch ( $key ) {
					case 'status':
						// Change should be done later so transitions have new data.
						break;

					case 'line_items':
					case 'shipping_lines':
					case 'fee_lines':
						if ( is_array( $value ) ) {

							foreach ( $value as $item ) {

								if ( is_array( $item ) ) {

									if ( $this->item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
										$po->remove_item( $item['id'] );
									}
									else {
										$this->set_item( $po, $key, $item );
									}

								}

							}

						}

						break;

					case 'meta_data':
						if ( is_array( $value ) && $id ) {

							foreach ( $value as $meta ) {
								$po->set_meta( $meta['key'], $meta['value'] );
							}

						}

						break;

					// date_expected || multiple_suppliers || supplier.
					default:
						if ( is_callable( array( $po, "set_{$key}" ) ) ) {
							$po->{"set_{$key}"}( $value );
						}

						break;
				}

			}

		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the object type slug.
		 *
		 * @param PurchaseOrder    $po       Object object.
		 * @param \WP_REST_Request $request  Request object.
		 * @param bool             $creating If is creating a new object.
		 */
		return apply_filters( "atum/api/rest_pre_insert_{$this->post_type}_object", $po, $request, $creating );

	}

	/**
	 * Save an object data.
	 *
	 * @since  1.6.2
	 *
	 * @param  \WP_REST_Request $request  Full details about the request.
	 * @param  bool             $creating If is creating a new object.
	 *
	 * @return PurchaseOrder|\WP_Error
	 *
	 * @throws \WC_REST_Exception|\WC_Data_Exception But all errors are validated before returning any data.
	 */
	protected function save_object( $request, $creating = FALSE ) {

		try {

			$object = $this->prepare_object_for_database( $request, $creating );

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			if ( $creating ) {
				$object->set_created_via( 'rest-api' );
				$object->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
				$object->calculate_totals();
			}
			// If items have changed, recalculate order totals.
			elseif ( isset( $request['line_items'] ) || isset( $request['shipping_lines'] ) || isset( $request['fee_lines'] ) ) {
				$object->calculate_totals( TRUE );
			}

			// Set status.
			if ( ! empty( $request['status'] ) ) {
				$object->set_status( $request['status'] );
			}

			$object->save();

			return $this->get_object( $object->get_id() );

		} catch ( \WC_Data_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( \WC_REST_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

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

		// This is needed to get around an array to string notice in \WC_REST_Orders_V2_Controller::prepare_objects_query.
		$statuses = $request['status'];
		unset( $request['status'] );
		$args = \WC_REST_CRUD_Controller::prepare_objects_query( $request );

		$args['post_status'] = array();
		foreach ( $statuses as $status ) {

			if ( in_array( $status, array_keys( Helpers::get_atum_order_post_type_statuses( $this->post_type ) ), TRUE ) ) {
				$args['post_status'][] = $status;
			}
			elseif ( 'any' === $status ) {
				// Set status to "any" and short-circuit out.
				$args['post_status'] = 'any';
				break;
			}

		}

		// Put the statuses back for further processing (next/prev links, etc).
		$request['status'] = $statuses;

		// Search by product.
		if ( ! empty( $request['product'] ) ) {

			global $wpdb;

			$atum_order_items_table     = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
			$atum_order_item_meta_table = $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE;

			// phpcs:disable WordPress.DB.PreparedSQL
			$order_ids = $wpdb->get_col(
				$wpdb->prepare( "
					SELECT DISTINCT order_id
					FROM $atum_order_items_table
					WHERE order_item_id IN ( 
						SELECT order_item_id FROM $atum_order_item_meta_table WHERE meta_key = '_product_id' AND meta_value = %d 
					)
					AND order_id IN (
						SELECT order_id FROM $wpdb->posts WHERE post_type = %s
					)
					AND order_item_type = 'line_item'",
					$request['product'],
					$this->post_type
				)
			);
			// phpcs:enable

			// Force WP_Query return empty if don't found any order.
			$order_ids = ! empty( $order_ids ) ? $order_ids : [ 0 ];

			$args['post__in'] = $order_ids;

		}

		// Search.
		if ( ! empty( $args['s'] ) ) {

			// TODO: SEARCH POs.
			$order_ids = wc_order_search( $args['s'] );

			if ( ! empty( $order_ids ) ) {
				unset( $args['s'] );
				$args['post__in'] = array_merge( $order_ids, [ 0 ] );
			}

		}

		// Expected at location date.
		if ( ! empty( $request['date_expected'] ) ) {

			$args['meta_query'] = array(
				array(
					'key'   => '_expected_at_location_date',
					'value' => $request['date_expected'],
					'type'  => 'DATETIME',
				),
			);

		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for an order collection request.
		 *
		 * @since 1.6.2
		 *
		 * @param array            $args    Key value array of query var to query value.
		 * @param \WP_REST_Request $request The request used.
		 */
		$args = apply_filters( 'atum/api/rest_orders_prepare_object_query', $args, $request );

		return $args;

	}

	/**
	 * Get formatted item data.
	 *
	 * @since  1.6.2
	 *
	 * @param  PurchaseOrder $object PurchaseOrder instance.
	 *
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {

		$data       = $object->get_data();
		$data['id'] = $object->get_id();

		$format_decimal    = array( 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax' );
		$format_date       = array( 'date_created', 'date_modified', 'date_completed', 'date_expected' );
		$format_line_items = array( 'line_items', 'tax_lines', 'shipping_lines', 'fee_lines' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			$data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
		}

		// Format date values.
		foreach ( $format_date as $key ) {
			$datetime             = $data[ $key ];
			$data[ $key ]         = wc_rest_prepare_date_response( $datetime, FALSE );
			$data[ "{$key}_gmt" ] = wc_rest_prepare_date_response( $datetime );
		}

		// Format line items.
		foreach ( $format_line_items as $key ) {
			$data[ $key ] = array_values( array_map( array( $this, 'get_order_item_data' ), $data[ $key ] ) );
		}

		$formatted_data = array();

		foreach ( $data as $data_key => $data_value ) {

			if ( in_array( $data_key, $this->rest_data_keys ) ) {
				$formatted_data[ $data_key ] = $data_value;
			}

		}

		return $formatted_data;

	}

	/**
	 * Get the Order's schema, conforming to JSON Schema.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		// Remove unneeded properties from the schema.
		foreach ( $schema['properties'] as $key => $value ) {

			if ( ! in_array( $key, $this->rest_data_keys ) ) {
				unset( $schema['properties'][ $key ] );
			}

		}

		$schema['properties']['status']['default'] = ATUM_PREFIX . 'pending';
		$schema['properties']['status']['enum']    = array_keys( Helpers::get_atum_order_post_type_statuses( $this->post_type ) );

		$schema['properties']['date_expected'] = array(
			'description' => __( 'The date when the purchase order is expected at location.', ATUM_TEXT_DOMAIN ),
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

		// We don't need the customer here.
		unset( $params['customer'] );

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to purchase orders which have specific statuses.', ATUM_TEXT_DOMAIN ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_merge( [ 'any', 'trash' ], array_keys( Helpers::get_atum_order_post_type_statuses( $this->post_type ) ) ),
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

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
	 * Prepare links for the request
	 *
	 * @since 1.6.2
	 *
	 * @param \WC_Data         $object  Object data.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {

		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;

	}

	/**
	 * Wrapper method to create/update order items.
	 * When updating, the item ID provided is checked to ensure it is associated with the purchase order.
	 *
	 * @since 1.6.2
	 *
	 * @param PurchaseOrder $po        Purchase Order object.
	 * @param string        $item_type The item type.
	 * @param array         $posted    Item provided in the request body.
	 *
	 * @throws \WC_REST_Exception If item ID is not associated with the purchase order.
	 */
	protected function set_item( $po, $item_type, $posted ) {

		$action = ! empty( $posted['id'] ) ? 'update' : 'create';
		$method = "prepare_$item_type";
		$item   = NULL;

		// Verify provided line item ID is associated with PO.
		if ( 'update' === $action ) {

			$item = $po->get_atum_order_item( absint( $posted['id'] ), FALSE );

			if ( ! $item ) {
				throw new \WC_REST_Exception( 'atum_rest_invalid_item_id', __( 'Order item ID provided is not associated with purchase order.', ATUM_TEXT_DOMAIN ), 400 );
			}

		}

		// Prepare item data.
		$item = $this->$method( $posted, $action, $item );

		do_action( 'atum/api/rest_set_order_item', $item, $posted );

		// If updating the PO, add the item to it.
		if ( 'update' === $action ) {
			$item->save();
		}

		// Add/Update the item on the PO object.
		$po->add_item( $item );

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
	 * @return POItemProduct
	 *
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_line_items( $posted, $action = 'create', $item = NULL ) {

		$item    = is_null( $item ) ? new POItemProduct( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;
		$product = Helpers::get_atum_product( $this->get_product_id( $posted ) );

		if ( $product !== $item->get_product() ) {

			$item->set_product( $product );

			if ( 'create' === $action ) {
				$quantity = isset( $posted['quantity'] ) ? $posted['quantity'] : 1;
				$total    = wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
				$item->set_total( $total );
				$item->set_subtotal( $total );
			}

		}

		$this->maybe_set_item_props( $item, array( 'name', 'quantity', 'total', 'subtotal', 'tax_class' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

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
	 * @return POItemShipping
	 *
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_shipping_lines( $posted, $action = 'create', $item = NULL ) {

		$item = is_null( $item ) ? new POItemShipping( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;

		if ( 'create' === $action ) {
			if ( empty( $posted['method_id'] ) ) {
				throw new \WC_REST_Exception( 'atum_rest_invalid_shipping_item', __( 'Shipping method ID is required.', ATUM_TEXT_DOMAIN ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'method_id', 'method_title', 'total' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

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
	 * @return POItemFee
	 *
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_fee_lines( $posted, $action = 'create', $item = NULL ) {

		$item = is_null( $item ) ? new POItemFee( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;

		if ( 'create' === $action && empty( $posted['name'] ) ) {
			throw new \WC_REST_Exception( 'atum_rest_invalid_fee_item', __( 'Fee name is required.', ATUM_TEXT_DOMAIN ), 400 );
		}

		$this->maybe_set_item_props( $item, array( 'name', 'tax_class', 'tax_status', 'total' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;

	}

}
