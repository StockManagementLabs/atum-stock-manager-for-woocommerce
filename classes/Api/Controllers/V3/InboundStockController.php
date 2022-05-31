<?php
/**
 * REST ATUM API Inbound Stock controller
 *
 * Handles requests to the atum/inbound-stock endpoint.
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
use Atum\Components\AtumOrders\Models\AtumOrderItemModel;
use Atum\Inc\Helpers;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;

class InboundStockController  extends \WC_REST_Products_Controller {

	/**
	 * Endpoint namespace
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/inbound-stock';

	/**
	 * Hold the inbound stock items here
	 *
	 * @var array
	 */
	protected $inbound_stock_items = array();

	/**
	 * Register the routes for inbound stock
	 *
	 * @since 1.6.2
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', ATUM_TEXT_DOMAIN ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Get the Product's schema, conforming to JSON Schema
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-inbound-stock',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => __( 'Unique identifier for the resource.', ATUM_TEXT_DOMAIN ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'name'              => array(
					'description' => __( 'Product name.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'type'              => array(
					'description' => __( 'Product type.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'default'     => 'simple',
					'enum'        => array_keys( wc_get_product_types() ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'sku'               => array(
					'description' => __( "Product's Stock Keeping Unit.", ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'inbound_stock'     => array(
					'description' => __( 'The quantity of the product set within the purchase order.', ATUM_TEXT_DOMAIN ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'date_ordered'      => array(
					'description' => __( "The date when the Purchase Order was created, in the site's timezone.", ATUM_TEXT_DOMAIN ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'date_ordered_gmt'  => array(
					'description' => __( 'The date when the Purchase Order was created, as GMT.', ATUM_TEXT_DOMAIN ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'date_expected'     => array(
					'description' => __( "The date when the Purchase Order is expected, in the site's timezone.", ATUM_TEXT_DOMAIN ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'date_expected_gmt' => array(
					'description' => __( 'The date when the Purchase Order is expected, as GMT.', ATUM_TEXT_DOMAIN ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'purchase_order'    => array(
					'description' => __( 'Unique identifier for the Purchase Order.', ATUM_TEXT_DOMAIN ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );

	}

	/**
	 * Exclude unneeded params from the collection
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$allowed_params = array(
			'page',
			'per_page',
			'search',
			'after',
			'before',
			'exclude',
			'include',
			'offset',
			'order',
			'orderby',
		);

		$params = parent::get_collection_params();

		foreach ( $params as $param => $data ) {

			if ( ! in_array( $param, $allowed_params, TRUE ) ) {
				unset( $params[ $param ] );
			}

		}

		// Add custom params.
		$params['include_po'] = array(
			'description'       => __( 'Limit result set to products with specified Purchase Order IDs.', ATUM_TEXT_DOMAIN ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => [],
			'sanitize_callback' => 'wp_parse_id_list',
		);

		$params['exclude_po'] = array(
			'description'       => __( 'Ensure result set excludes specific Purchasr Order IDs.', ATUM_TEXT_DOMAIN ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		$params['expected_after'] = array(
			'description'       => __( 'Limit response to purchase orders expected after a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['expected_before'] = array(
			'description'       => __( 'Limit response to purchase orders expected before a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;

	}

	/**
	 * Check if a given request has access to read items
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read' ) || ! AtumCapabilities::current_user_can( 'read_inbound_stock' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access to read an item
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {

		$objects = $this->get_object( (int) $request['id'] );

		if ( ! empty( $objects ) && is_array( $objects ) ) {

			foreach ( $objects as $object ) {
				if (
					! wc_rest_check_post_permissions( $this->post_type, 'read', $object->ID ) ||
					! AtumCapabilities::current_user_can( 'read_inbound_stock', $object->ID )
				) {
					return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot view this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
				}
			}

		}

		return TRUE;

	}

	/**
	 * Get a collection of products in Inbound Stock
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {

		$query_args    = $this->prepare_objects_query( $request );
		$query_results = $this->get_objects( $query_args );

		$objects = array();
		foreach ( $query_results['objects'] as $object ) {

			if (
				! wc_rest_check_post_permissions( $this->post_type, 'read', $object->ID ) ||
				! AtumCapabilities::current_user_can( 'read_inbound_stock', $object->ID )
			) {
				continue;
			}

			$data      = $this->prepare_object_for_response( $object, $request );
			$objects[] = $this->prepare_response_for_collection( $data );

		}

		$page      = (int) $query_args['paged'];
		$max_pages = $query_results['pages'];

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base          = $this->rest_base;
		$attrib_prefix = '(?P<';
		if ( strpos( $base, $attrib_prefix ) !== FALSE ) {

			$attrib_names = array();
			preg_match( '/\(\?P<[^>]+>.*\)/', $base, $attrib_names, PREG_OFFSET_CAPTURE );

			foreach ( $attrib_names as $attrib_name_match ) {

				$beginning_offset = strlen( $attrib_prefix );
				$attrib_name_end  = strpos( $attrib_name_match[0], '>', $attrib_name_match[1] );
				$attrib_name      = substr( $attrib_name_match[0], $beginning_offset, $attrib_name_end - $beginning_offset );

				if ( isset( $request[ $attrib_name ] ) ) {
					$base = str_replace( "(?P<$attrib_name>[\d]+)", $request[ $attrib_name ], $base );
				}

			}

		}

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ) );

		if ( $page > 1 ) {

			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );

		}

		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;

	}

	/**
	 * Get a single item
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {

		$objects = $this->get_object( (int) $request['id'] );

		if ( empty( $objects ) ) {
			return new \WP_Error( "atum_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		// It could return multiple products (same product within distinct POs.
		if ( is_array( $objects ) && count( $objects ) > 1 ) {

			$objects = array();

			foreach ( $objects as $item ) {
				$data      = $this->prepare_object_for_response( $item, $request );
				$objects[] = $this->prepare_response_for_collection( $data );
			}

			$response = rest_ensure_response( $objects );

		}
		else {

			$objects  = ( is_array( $objects ) && 1 === count( $objects ) ) ? current( $objects ) : $objects;
			$data     = $this->prepare_object_for_response( $objects, $request );
			$response = rest_ensure_response( $data );

		}

		if ( $this->public ) {
			$response->link_header( 'alternate', $this->get_permalink( $objects ), [ 'type' => 'text/html' ] );
		}

		return $response;

	}

	/**
	 * Get objects
	 *
	 * @since 1.6.2
	 *
	 * @param array $query_args Query args.
	 *
	 * @return array
	 */
	protected function get_objects( $query_args ) {

		global $wpdb;

		$search_query = '';
		$found_posts  = 0;

		if ( ! empty( $query_args['s'] ) ) {

			$search = esc_attr( $query_args['s'] );

			if ( is_numeric( $search ) ) {
				$search_query .= 'AND `meta_value` = ' . absint( $query_args['s'] );
			}
			else {
				$search_query .= "AND `order_item_name` LIKE '%{$search}%'";
			}

		}

		$order_by = 'ORDER BY `order_id`';
		if ( ! empty( $query_args['orderby'] ) ) {

			switch ( $query_args['orderby'] ) {
				case 'title':
					$order_by = 'ORDER BY `order_item_name`';
					break;

				case 'ID':
					$order_by = 'ORDER BY oi.`order_item_id`';
					break;

			}

		}

		$order           = ( ! empty( $query_args['order'] ) && in_array( strtoupper( $query_args['order'] ), [ 'ASC', 'DESC' ] ) ) ? strtoupper( $query_args['order'] ) : 'DESC';
		$statuses        = array_diff( array_keys( PurchaseOrders::get_statuses() ), [ PurchaseOrders::FINISHED ] );
		$post_in         = ! empty( $query_args['post__in'] ) ? 'AND oim.`meta_value` IN (' . implode( ',', $query_args['post__in'] ) . ')' : '';
		$po_in           = ! empty( $query_args['include_po'] ) ? 'AND p.`ID` IN (' . implode( ',', $query_args['include_po'] ) . ')' : '';
		$post_not_in     = ! empty( $query_args['post__not_in'] ) ? 'AND oim.`meta_value` NOT IN (' . implode( ',', $query_args['post__not_in'] ) . ')' : '';
		$po_not_in       = ! empty( $query_args['exclude_po'] ) ? 'AND p.`ID` NOT IN (' . implode( ',', $query_args['exclude_po'] ) . ')' : '';
		$date_query      = ! empty( $query_args['date_query'] ) ? current( $query_args['date_query'] ) : [];
		$before          = ! empty( $date_query['before'] ) ? "AND p.post_date < '" . $date_query['before'] . "'" : '';
		$expected_before = ! empty( $query_args['expected_before'] ) ? "AND de.`meta_value` < '" . $query_args['expected_before'] . "'" : '';
		$after           = ! empty( $date_query['after'] ) ? "AND p.`post_date` > '" . $date_query['after'] . "'" : '';
		$expected_after  = ! empty( $query_args['expected_after'] ) ? "AND de.`meta_value` > '" . $query_args['expected_after'] . "'" : '';
		$expected_join   = $expected_before || $expected_after ? "LEFT JOIN `$wpdb->postmeta` AS de ON (de.`post_id` = p.`ID` AND de.`meta_key` = '_date_expected')" : '';

		// phpcs:disable
		$sql = $wpdb->prepare("
			SELECT MAX(CAST( oim.`meta_value` AS SIGNED )) AS product_id, oi.`order_item_id`, `order_id`, `order_item_name` 			
			FROM `$wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` AS oi 
			LEFT JOIN `$wpdb->atum_order_itemmeta` AS oim ON oi.`order_item_id` = oim.`order_item_id`
			LEFT JOIN `$wpdb->posts` AS p ON oi.`order_id` = p.`ID`
			$expected_join
			WHERE oim.`meta_key` IN ('_product_id', '_variation_id') AND `order_item_type` = 'line_item' 
			AND p.`post_type` = %s AND oim.`meta_value` > 0 AND `post_status` IN ('" . implode( "','", $statuses ) . "')
			$post_in $po_in $post_not_in $po_not_in $before $expected_before $after $expected_after $search_query
			GROUP BY oi.`order_item_id`
			$order_by $order;",
			PurchaseOrders::POST_TYPE
		);
		// phpcs:enable

		$po_products = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$per_page    = intval( isset( $query_args['posts_per_page'] ) ? $query_args['posts_per_page'] : get_option( 'posts_per_page' ) );

		if ( ! empty( $po_products ) ) {

			$found_posts = count( $po_products );

			// Paginate the results (if needed).
			if ( -1 !== $per_page && $found_posts > $per_page ) {
				$page   = isset( $query_args['paged'] ) ? $query_args['paged'] : 0;
				$offset = ! empty( $query_args['offset'] ) ? $query_args['offset'] : ( $page - 1 ) * $per_page;

				$po_products = array_slice( $po_products, $offset, $per_page );
			}

			foreach ( $po_products as $po_product ) {

				$post = get_post( $po_product->product_id );

				if ( $post ) {
					$post->po_id                 = $po_product->order_id;
					$post->po_item_id            = $po_product->order_item_id;
					$this->inbound_stock_items[] = $post;
				}
				// In case there are some products still added to POs but not exists on the shop anymore.
				else {
					$found_posts--;
				}

			}

		}

		return array(
			'objects' => $this->inbound_stock_items,
			'total'   => $found_posts,
			'pages'   => $found_posts ? (int) ceil( $found_posts / $per_page ) : 0,
		);

	}

	/**
	 * Get object.
	 *
	 * @param int $id Object ID.
	 *
	 * @since  1.6.2
	 *
	 * @return array
	 */
	protected function get_object( $id ) {

		if ( empty( $this->inbound_stock_items ) ) {

			$this->get_objects( array(
				'post__in'       => [ $id ],
				'posts_per_page' => - 1,
			) );

		}

		return $this->inbound_stock_items;

	}

	/**
	 * Get product data
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_Post $item    Post instance.
	 * @param string   $context Request context. Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_product_data( $item, $context = 'view' ) {

		$product = Helpers::get_atum_product( $item );

		if ( ! $product instanceof \WC_Product ) {
			return [];
		}

		$po_id = absint( $item->po_id );

		/**
		 * Variable definition
		 *
		 * @var PurchaseOrder $po
		 */
		$po = Helpers::get_atum_order_model( $po_id, FALSE, PurchaseOrders::POST_TYPE );

		if ( ! $po->exists() ) {
			return [];
		}

		return array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name( $context ),
			'type'              => $product->get_type(),
			'sku'               => $product->get_sku( $context ),
			'inbound_stock'     => (float) AtumOrderItemModel::get_item_meta( $item->po_item_id, '_qty' ),
			'date_ordered'      => wc_rest_prepare_date_response( $item->post_date, FALSE ),
			'date_ordered_gmt'  => wc_rest_prepare_date_response( $item->post_date ),
			'date_expected'     => $po->date_expected ? wc_rest_prepare_date_response( $po->date_expected, FALSE ) : '',
			'date_expected_gmt' => $po->date_expected ? wc_rest_prepare_date_response( $po->date_expected ) : '',
			'purchase_order'    => $po_id,
		);

	}

	/**
	 * Prepare the args for the db query
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {

		$args = parent::prepare_objects_query( $request );

		if ( ! empty( $request['include_po'] ) ) {
			$args['include_po'] = $request['include_po'];
		}

		if ( ! empty( $request['exclude_po'] ) ) {
			$args['exclude_po'] = $request['exclude_po'];
		}

		if ( ! empty( $request['expected_before'] ) ) {
			$args['expected_before'] = $request['expected_before'];
		}

		if ( ! empty( $request['expected_after'] ) ) {
			$args['expected_after'] = $request['expected_after'];
		}

		return $args;

	}

	/**
	 * Prepare a single product output for response
	 *
	 * @since  1.6.2
	 *
	 * @param \WP_Post         $object  Object data.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->get_product_data( $object, $context );
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to object type being prepared for the response.
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \WP_Post          $object   Object post.
		 * @param \WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'atum/api/rest_prepare_inbound_stock_object', $response, $object, $request );

	}

	/**
	 * Prepare links for the request
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_Post         $object  Object post.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return array                   Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {

		return array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

	}

}
