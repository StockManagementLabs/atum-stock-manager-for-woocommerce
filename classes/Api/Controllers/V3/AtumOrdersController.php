<?php
/**
 * Abstract REST API's ATUM Orders controller
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

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Items\AtumOrderItemFee;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Components\AtumOrders\Items\AtumOrderItemShipping;
use Atum\Components\AtumOrders\Items\AtumOrderItemTax;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Helpers;
use Atum\PurchaseOrders\PurchaseOrders;


abstract class AtumOrdersController extends \WC_REST_Orders_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';


	/**
	 * Get the ATUM Order's schema, conforming to JSON Schema.
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
		$schema['properties']['status']['enum']    = $this->get_atum_order_statuses();

		$schema['properties']['description'] = array(
			'description' => __( 'The ATUM order description.', ATUM_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);

		// Allow editing order dates.
		unset(
			$schema['properties']['date_created']['readonly'],
			$schema['properties']['date_created_gmt']['readonly'],
			$schema['properties']['date_completed']['readonly'],
			$schema['properties']['date_completed_gmt']['readonly']
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
			'description'       => __( 'Limit result set to ATUM orders which have specific statuses.', ATUM_TEXT_DOMAIN ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => $this->get_atum_order_statuses(),
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['modified_before'] = [
			'description' => __( 'Limit response to orders modified before a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
			'type'        => 'string',
			'format'      => 'date-time',
		];

		$params['modified_after'] = [
			'description' => __( 'Limit response to orders modified after a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
			'type'        => 'string',
			'format'      => 'date-time',
		];

		return $params;

	}

	/**
	 * Check if a given request has access to read items
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {

		$cap = PurchaseOrders::POST_TYPE === $this->post_type ? 'read_private_purchase_orders' : 'read_private_inventory_logs';

		if ( ! AtumCapabilities::current_user_can( $cap ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot list resources.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access to read an item
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {

		$object = $this->get_object( (int) $request['id'] );
		$cap    = PurchaseOrders::POST_TYPE === $this->post_type ? 'read_purchase_order' : 'read_inventory_log';

		if ( $object && 0 !== $object->get_id() && ! AtumCapabilities::current_user_can( $cap, $object->get_id() ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot view this resource.', ATUM_TEXT_DOMAIN ), array( 'status' => rest_authorization_required_code() ) );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access to update an item
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {

		$object = $this->get_object( (int) $request['id'] );
		$cap    = PurchaseOrders::POST_TYPE === $this->post_type ? 'edit_purchase_order' : 'edit_inventory_log';

		if ( $object && 0 !== $object->get_id() && ! AtumCapabilities::current_user_can( $cap, $object->get_id() ) ) {
			return new \WP_Error( 'atum_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access to delete an item
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function delete_item_permissions_check( $request ) {

		$object = $this->get_object( (int) $request['id'] );
		$cap    = PurchaseOrders::POST_TYPE === $this->post_type ? 'delete_purchase_order' : 'delete_inventory_log';

		if ( $object && 0 !== $object->get_id() && ! AtumCapabilities::current_user_can( $cap, $object->get_id() ) ) {
			return new \WP_Error( 'atum_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access batch create, update and delete items
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function batch_items_permissions_check( $request ) {

		$cap = PurchaseOrders::POST_TYPE === $this->post_type ? 'edit_others_purchase_orders' : 'edit_others_inventory_logs';

		if ( ! AtumCapabilities::current_user_can( $cap ) ) {
			return new \WP_Error( 'atum_rest_cannot_batch', __( 'Sorry, you are not allowed to batch manipulate this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Get object. Return false if object is not of required type.
	 *
	 * @since  1.6.2
	 *
	 * @param  int|\WP_Post $post Object ID.
	 *
	 * @return AtumOrderModel|bool
	 */
	protected function get_object( $post ) {

		$id    = $post instanceof \WP_Post ? $post->ID : $post;
		$order = Helpers::get_atum_order_model( $id, TRUE, $this->post_type );

		// In case id is not an ATUM Order, don't expose it via API.
		if ( ! $order ) {
			return FALSE;
		}

		return $order;

	}

	/**
	 * Prepare a single ATUM Order for create or update.
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request  Request object.
	 * @param  bool             $creating If is creating a new object.
	 *
	 * @return \WP_Error|AtumOrderModel
	 *
	 * @throws \WC_REST_Exception When fails to set any item.
	 */
	protected function prepare_object_for_database( $request, $creating = FALSE ) {

		$id        = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$order     = $this->get_object( $id );
		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		// Handle all writable props.
		foreach ( $data_keys as $key ) {

			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {

				switch ( $key ) {
					case 'status':
						// Change must be done later so transitions have the new data.
						break;

					case 'line_items':
					case 'shipping_lines':
					case 'fee_lines':
						if ( is_array( $value ) ) {

							foreach ( $value as $item ) {

								if ( is_array( $item ) ) {

									if ( $this->item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
										$order->remove_item( $item['id'] );
									}
									else {
										$this->set_item( $order, $key, $item );
									}

								}

							}

						}

						break;

					case 'meta_data':
						if ( is_array( $value ) && $id ) {

							foreach ( $value as $meta ) {

								$meta_key = strpos( $meta['key'], '_' ) === 0 ? $meta['key'] : "_{$meta['key']}";

								if ( is_callable( array( $order, "set$meta_key" ) ) ) {
									call_user_func( array( $order, "set$meta_key" ), $meta['value'] );
								}
							}

						}

						break;

					// The right way to send dates is by using GMT formats, so we can convert them now to the site's timezone.
					case 'date_created_gmt':
					case 'date_completed_gmt':
						$formatted_key = str_replace( '_gmt', '', $key );

						if ( is_callable( array( $order, "set_{$formatted_key}" ) ) ) {
							unset( $data_keys[ $formatted_key ] );

							$date_value     = get_date_from_gmt( $value );
							$formatted_date = Helpers::get_wc_time( $date_value );

							$order->{"set_{$formatted_key}"}( $formatted_date );
						}
						break;

					// Any ATUM order prop with a setter.
					default:
						if ( is_callable( array( $order, "set_{$key}" ) ) ) {
							$order->{"set_{$key}"}( $value );
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
		 * @param AtumOrderModel   $order    Object object.
		 * @param \WP_REST_Request $request  Request object.
		 * @param bool             $creating If is creating a new object.
		 */
		return apply_filters( "atum/api/rest_pre_insert_{$this->post_type}_object", $order, $request, $creating );

	}

	/**
	 * Save an object data.
	 *
	 * @since  1.6.2
	 *
	 * @param  \WP_REST_Request $request  Full details about the request.
	 * @param  bool             $creating If is creating a new object.
	 *
	 * @return AtumOrderModel|\WP_Error
	 *
	 * @throws \WC_Data_Exception But all errors are validated before returning any data.
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

			do_action( 'atum/api/before_save_atum_order', $object, $request, $creating );

			$object->save();

			return $this->get_object( $object->get_id() );

		} catch ( \WC_Data_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}
		/* @noinspection PhpRedundantCatchClauseInspection */
		catch ( \WC_REST_Exception $e ) {
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

			if ( in_array( $status, $this->get_atum_order_statuses(), TRUE ) ) {
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

			// TODO: SEARCH ATUM Orders.
			$order_ids = wc_order_search( $args['s'] );

			if ( ! empty( $order_ids ) ) {
				unset( $args['s'] );
				$args['post__in'] = array_merge( $order_ids, [ 0 ] );
			}

		}

		// Before modification date filter.
		if ( isset( $request['modified_before'] ) && ! isset( $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['modified_before'];
			$args['date_query'][0]['column'] = 'post_modified_gmt';
		}

		// After modification date filter.
		if ( isset( $request['modified_after'] ) && ! isset( $request['after'] ) ) {
			$args['date_query'][0]['after']  = $request['modified_after'];
			$args['date_query'][0]['column'] = 'post_modified_gmt';
		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for an ATUM order collection request.
		 *
		 * @since 1.6.2
		 *
		 * @param array            $args    Key value array of query var to query value.
		 * @param \WP_REST_Request $request The request used.
		 */
		return apply_filters( 'atum/api/rest_orders_prepare_object_query', $args, $request );

	}

	/**
	 * Get formatted item data.
	 *
	 * @since  1.6.2
	 *
	 * @param AtumOrderModel $object ATUM Order instance.
	 *
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {

		$data       = $object->get_data();
		$data['id'] = $object->get_id();

		$format_decimal    = array( 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax' );
		$format_date       = array( 'date_created', 'date_modified', 'date_completed' );
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
	 * Expands an ATUM order item to get its data.
	 *
	 * @since 1.6.2
	 *
	 * @param AtumOrderItemFee|AtumOrderItemProduct|AtumOrderItemShipping|AtumOrderItemTax $item ATUM Order item data.
	 *
	 * @return array
	 */
	protected function get_order_item_data( $item ) {

		$data = parent::get_order_item_data( $item );

		// Get rid of the internal meta data from the meta_data object.
		if ( ! empty( $data['meta_data'] ) ) {

			foreach ( $data['meta_data'] as $index => $meta ) {
				$meta = (object) $meta;
				if ( $item->is_internal_meta( $meta->key ) ) {
					unset( $data['meta_data'][ $index ] );
				}
			}

		}

		return $data;

	}

	/**
	 * Prepare an item product
	 *
	 * @since 1.6.2
	 *
	 * @param array                $posted Line item data.
	 * @param string               $action 'create' to add line item or 'update' to update it.
	 * @param AtumOrderItemProduct $item   The item to prepare.
	 *
	 * @throws \WC_REST_Exception
	 */
	protected function prepare_item_product( $posted, $action, $item ) {

		$product = Helpers::get_atum_product( $this->get_product_id( $posted ) );

		if ( $product instanceof \WC_Product && $product !== $item->get_product() ) {

			$item->set_product( $product );

			if ( 'create' === $action ) {
				$quantity = isset( $posted['quantity'] ) ? $posted['quantity'] : 1;
				$total    = wc_get_price_excluding_tax( $product, [ 'qty' => $quantity ] );
				$item->set_total( $total );
				$item->set_subtotal( $total );
			}

		}

		$this->maybe_set_item_props( $item, array(
			'name',
			'quantity',
			'total',
			'subtotal',
			'tax_class',
			'stock_changed',
		), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

	}

	/**
	 * Prepare an item shipping
	 *
	 * @since 1.6.2
	 *
	 * @param array                 $posted Line item data.
	 * @param string                $action 'create' to add line item or 'update' to update it.
	 * @param AtumOrderItemShipping $item   The item to prepare.
	 *
	 * @throws \WC_REST_Exception
	 */
	protected function prepare_item_shipping( $posted, $action, $item ) {

		if ( 'create' === $action && empty( $posted['method_id'] ) ) {
			throw new \WC_REST_Exception( 'atum_rest_invalid_shipping_item', __( 'Shipping method ID is required.', ATUM_TEXT_DOMAIN ), 400 );
		}

		$this->maybe_set_item_props( $item, array( 'method_id', 'method_title', 'total' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

	}

	/**
	 * Prepare an item fee
	 *
	 * @since 1.6.2
	 *
	 * @param array            $posted Line item data.
	 * @param string           $action 'create' to add line item or 'update' to update it.
	 * @param AtumOrderItemFee $item   The item to prepare.
	 *
	 * @throws \WC_REST_Exception
	 */
	protected function prepare_item_fee( $posted, $action, $item ) {

		if ( 'create' === $action && empty( $posted['name'] ) ) {
			throw new \WC_REST_Exception( 'atum_rest_invalid_fee_item', __( 'Fee name is required.', ATUM_TEXT_DOMAIN ), 400 );
		}

		$this->maybe_set_item_props( $item, array( 'name', 'tax_class', 'tax_status', 'total' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

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

		return array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

	}

	/**
	 * Wrapper method to create/update order items.
	 * When updating, the item ID provided is checked to ensure it is associated with the ATUM order.
	 *
	 * @since 1.6.2
	 *
	 * @param AtumOrderModel $order     ATUM Order object.
	 * @param string         $item_type The item type.
	 * @param array          $posted    Item provided in the request body.
	 *
	 * @throws \WC_REST_Exception If item ID is not associated with the ATUM order.
	 */
	protected function set_item( $order, $item_type, $posted ) {

		$action = ! empty( $posted['id'] ) ? 'update' : 'create';
		$method = "prepare_$item_type";
		$item   = NULL;

		// Verify provided line item ID is associated with ATUM Order.
		if ( 'update' === $action ) {

			$item = $order->get_atum_order_item( absint( $posted['id'] ) );

			if ( ! $item ) {
				throw new \WC_REST_Exception( 'atum_rest_invalid_item_id', __( 'Order item ID provided is not associated with ATUM order.', ATUM_TEXT_DOMAIN ), 400 );
			}

		}

		// Prepare item data.
		$item = $this->$method( $posted, $action, $item );

		do_action( 'atum/api/rest_set_atum_order_item', $item, $posted );

		// If updating the ATUM Order, add the item to it.
		if ( 'update' === $action ) {
			$item->save();
		}

		// Add/Update the item on the ATUM Order object.
		$order->add_item( $item );

		do_action( 'atum/api/rest_after_set_atum_order_item', $item, $posted, $action );
	}

	/**
	 * Get the post statuses allowed for ATUM Orders
	 *
	 * @since 1.7.5
	 *
	 * @return array
	 */
	private function get_atum_order_statuses() {
		return apply_filters( 'atum/api/atum_orders/statuses', array_merge( array_keys( Helpers::get_atum_order_post_type_statuses( $this->post_type ) ), [ 'any', 'trash' ] ) );
	}

}
