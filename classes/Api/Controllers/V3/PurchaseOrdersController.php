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

		// In case id is a refund's id (or it's not an order at all), don't expose it via /orders/ path.
		if ( ! $po ) {
			return FALSE;
		}

		return $po;

	}


	/**
	 * Prepare a single order for create or update.
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request  Request object.
	 * @param  bool             $creating If is creating a new object.
	 *
	 * @return \WP_Error|\WC_Data
	 *
	 * @throws \WC_REST_Exception When fails to set any item.
	 */
	protected function prepare_object_for_database( $request, $creating = FALSE ) {

		$id        = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$order     = new WC_Order( $id );
		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		// Handle all writable props.
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {
				switch ( $key ) {
					case 'coupon_lines':
					case 'status':
						// Change should be done later so transitions have new data.
						break;
					case 'billing':
					case 'shipping':
						$this->update_address( $order, $value, $key );
						break;
					case 'line_items':
					case 'shipping_lines':
					case 'fee_lines':
						if ( is_array( $value ) ) {
							foreach ( $value as $item ) {
								if ( is_array( $item ) ) {
									if ( $this->item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
										$order->remove_item( $item['id'] );
									} else {
										$this->set_item( $order, $key, $item );
									}
								}
							}
						}
						break;
					case 'meta_data':
						if ( is_array( $value ) ) {
							foreach ( $value as $meta ) {
								$order->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
							}
						}
						break;
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
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param \WC_Data         $order    Object object.
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
	 * @return \WC_Data|\WP_Error
	 *
	 * @throws \WC_REST_Exception But all errors are validated before returning any data.
	 */
	protected function save_object( $request, $creating = FALSE ) {

		try {
			$object = $this->prepare_object_for_database( $request, $creating );

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			// Make sure gateways are loaded so hooks from gateways fire on save/create.
			wc()->payment_gateways();

			if ( ! is_null( $request['customer_id'] ) && 0 !== $request['customer_id'] ) {

				// Make sure customer exists.
				if ( false === get_user_by( 'id', $request['customer_id'] ) ) {
					throw new \WC_REST_Exception( 'atum_rest_invalid_customer_id', __( 'Customer ID is invalid.', ATUM_TEXT_DOMAIN ), 400 );
				}

				// Make sure customer is part of blog.
				if ( is_multisite() && ! is_user_member_of_blog( $request['customer_id'] ) ) {
					add_user_to_blog( get_current_blog_id(), $request['customer_id'], 'customer' );
				}

			}

			if ( $creating ) {
				$object->set_created_via( 'rest-api' );
				$object->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
				$object->calculate_totals();
			}
			else {
				// If items have changed, recalculate order totals.
				if ( isset( $request['billing'] ) || isset( $request['shipping'] ) || isset( $request['line_items'] ) || isset( $request['shipping_lines'] ) || isset( $request['fee_lines'] ) || isset( $request['coupon_lines'] ) ) {
					$object->calculate_totals( TRUE );
				}
			}

			// Set coupons.
			$this->calculate_coupons( $request, $object );

			// Set status.
			if ( ! empty( $request['status'] ) ) {
				$object->set_status( $request['status'] );
			}

			$object->save();

			// Actions for after the order is saved.
			if ( true === $request['set_paid'] ) {
				if ( $creating || $object->needs_payment() ) {
					$object->payment_complete( $request['transaction_id'] );
				}
			}

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
					SELECT order_id
					FROM $atum_order_items_table
					WHERE order_item_id IN ( 
						SELECT order_item_id FROM $atum_order_item_meta_table WHERE meta_key = '_product_id' AND meta_value = %d 
					)
					AND order_id IN (
						SELECT order_id FROM $wpdb->posts WHERE post_type = %s
					)
					AND order_item_type = 'line_item'",
					$this->post_type,
					$request['product']
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

		$data              = $object->get_data();
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

		// Refunds.
		// TODO??
		/*$data['refunds'] = array();
		foreach ( $object->get_refunds() as $refund ) {
			$data['refunds'][] = array(
				'id'     => $refund->get_id(),
				'reason' => $refund->get_reason() ? $refund->get_reason() : '',
				'total'  => '-' . wc_format_decimal( $refund->get_amount(), $this->request['dp'] ),
			);
		}*/

		return array(
			'id'                 => $object->get_id(),
			'status'             => $data['status'],
			'currency'           => $data['currency'],
			'date_created'       => $data['date_created'],
			'date_created_gmt'   => $data['date_created_gmt'],
			'date_modified'      => $data['date_modified'],
			'date_modified_gmt'  => $data['date_modified_gmt'],
			'discount_total'     => $data['discount_total'],
			'discount_tax'       => $data['discount_tax'],
			'shipping_total'     => $data['shipping_total'],
			'shipping_tax'       => $data['shipping_tax'],
			'cart_tax'           => $data['cart_tax'],
			'total'              => $data['total'],
			'total_tax'          => $data['total_tax'],
			'prices_include_tax' => $data['prices_include_tax'],
			'date_completed'     => $data['date_completed'],
			'date_completed_gmt' => $data['date_completed_gmt'],
			'supplier'           => $data['supplier'],
			'multiple_suppliers' => $data['multiple_suppliers'],
			'date_expected'      => $data['date_expected'],
			'date_expected_gmt'  => $data['date_expected_gmt'],
			'line_items'         => $data['line_items'],
			'tax_lines'          => $data['tax_lines'],
			'shipping_lines'     => $data['shipping_lines'],
			'fee_lines'          => $data['fee_lines'],
		);

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

		$schema['properties']['coupon_lines']['items']['properties']['discount']['readonly'] = TRUE;

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

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to orders which have specific statuses.', ATUM_TEXT_DOMAIN ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_merge( array( 'any', 'trash' ), $this->get_order_statuses() ),
			),
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

}
