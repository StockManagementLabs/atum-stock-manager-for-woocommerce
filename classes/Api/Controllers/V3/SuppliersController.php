<?php
/**
 * REST ATUM API Suppliers controller
 * Handles requests to the /atum/suppliers endpoint.
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Inc\Helpers;
use Atum\Suppliers\Suppliers;

class SuppliersController extends \WC_REST_Posts_Controller {

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
	protected $rest_base = 'atum/suppliers';

	/**
	 * Post type
	 *
	 * @var string
	 */
	protected $post_type = Suppliers::POST_TYPE;

	/**
	 * Instance of a post meta fields object.
	 *
	 * @var \WP_REST_Post_Meta_Fields
	 */
	protected $meta;

	/**
	 * If object is hierarchical
	 *
	 * @var bool
	 */
	protected $hierarchical = TRUE;

	/**
	 * Register the routes for Suppliers
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
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
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
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => FALSE,
							'description' => __( 'Whether to bypass trash and force deletion.', ATUM_TEXT_DOMAIN ),
							'type'        => 'boolean',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/batch',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
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
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => __( 'Unique identifier for the resource.', ATUM_TEXT_DOMAIN ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'name'              => array(
					'description' => __( 'Supplier name.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'slug'              => array(
					'description' => __( 'Supplier slug.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink'         => array(
					'description' => __( 'Supplier URL.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'date_created'      => array(
					'description' => __( "The date the supplier was created, in the site's timezone.", ATUM_TEXT_DOMAIN ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt'  => array(
					'description' => __( 'The date the supplier was created, as GMT.', ATUM_TEXT_DOMAIN ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified'     => array(
					'description' => __( "The date the supplier was last modified, in the site's timezone.", ATUM_TEXT_DOMAIN ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'date_modified_gmt' => array(
					'description' => __( 'The date the supplier was last modified, as GMT.', ATUM_TEXT_DOMAIN ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'status'            => array(
					'description' => __( 'Supplier status (post status).', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => $this->get_supplier_post_statuses(),
					'context'     => array( 'view', 'edit' ),
				),
				'code'              => array(
					'description' => __( 'Supplier code.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'tax_number'        => array(
					'description' => __( 'Supplier tax/VAT number.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'phone'             => array(
					'description' => __( 'Supplier phone number.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'fax'               => array(
					'description' => __( 'Supplier fax number.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'website'           => array(
					'description' => __( 'Supplier website.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'ordering_url'      => array(
					'description' => __( 'Supplier ordering URL.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'general_email'     => array(
					'description' => __( 'Supplier general email.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'ordering_email'    => array(
					'description' => __( 'Supplier ordering email.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'description'       => array(
					'description' => __( 'Supplier description.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'currency'          => array(
					'description' => __( 'Supplier currency.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( get_woocommerce_currencies() ),
				),
				'address'           => array(
					'description' => __( 'Supplier address.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'city'              => array(
					'description' => __( 'Supplier city.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'country'           => array(
					'description' => __( 'Supplier country.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( WC()->countries->get_countries() ),
				),
				'state'             => array(
					'description' => __( 'Supplier state.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'zip_code'          => array(
					'description' => __( 'Supplier ZIP code.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'assigned_to'       => array(
					'description' => __( 'The user ID that this supplier is assigned to.', ATUM_TEXT_DOMAIN ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'location'          => array(
					'description' => __( 'The location used in Purchase Orders assigned to this supplier.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'image'             => array(
					'description' => __( 'Supplier featured image.', ATUM_TEXT_DOMAIN ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                => array(
								'description' => __( 'Image ID.', ATUM_TEXT_DOMAIN ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'date_created'      => array(
								'description' => __( "The date the image was created, in the site's timezone.", ATUM_TEXT_DOMAIN ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => TRUE,
							),
							'date_created_gmt'  => array(
								'description' => __( 'The date the image was created, as GMT', ATUM_TEXT_DOMAIN ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => TRUE,
							),
							'date_modified'     => array(
								'description' => __( "The date the image was modified, in the site's timezone.", ATUM_TEXT_DOMAIN ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => TRUE,
							),
							'date_modified_gmt' => array(
								'description' => __( 'The date the image was modified, as GMT.', ATUM_TEXT_DOMAIN ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => TRUE,
							),
							'src'               => array(
								'description' => __( 'Image URL.', ATUM_TEXT_DOMAIN ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view', 'edit' ),
							),
							'name'              => array(
								'description' => __( 'Image name.', ATUM_TEXT_DOMAIN ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'alt'               => array(
								'description' => __( 'Image alternative text.', ATUM_TEXT_DOMAIN ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'meta_data'         => array(
					'description' => __( 'Meta data.', ATUM_TEXT_DOMAIN ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => __( 'Meta ID.', ATUM_TEXT_DOMAIN ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => TRUE,
							),
							'key'   => array(
								'description' => __( 'Meta key.', ATUM_TEXT_DOMAIN ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => __( 'Meta value.', ATUM_TEXT_DOMAIN ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );

	}

	/**
	 * Get the query params for collections of suppliers (for filtering purposes)
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		$supplier_params = array(
			'slug'            => array(
				'description'       => __( 'Limit result set to suppliers with a specific slug.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'status'          => array(
				'default'           => 'any',
				'description'       => __( 'Limit result set to suppliers assigned a specific status.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'enum'              => $this->get_supplier_post_statuses(),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'currency'        => array(
				'description'       => __( 'Limit result set to suppliers using the specified currency code.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => array_keys( get_woocommerce_currencies() ),
			),
			'country'         => array(
				'description'       => __( 'Limit result set to suppliers from the specified country code.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => array_keys( WC()->countries->get_countries() ),
			),
			'assigned_to'     => array(
				'description'       => __( 'Limit result set to suppliers assigned to the specified user ID.', ATUM_TEXT_DOMAIN ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'product'         => array(
				'description'       => __( 'Limit result set to suppliers assigned to the specific product ID.', ATUM_TEXT_DOMAIN ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'modified_before' => array(
				'description' => __( 'Limit response to orders modified before a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
				'type'        => 'string',
				'format'      => 'date-time',
			),
			'modified_after'  => array(
				'description' => __( 'Limit response to orders modified after a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
				'type'        => 'string',
				'format'      => 'date-time',
			),
		);

		return array_merge( $params, $supplier_params );

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

		if ( ! AtumCapabilities::current_user_can( 'read_private_suppliers' ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot list resources.', ATUM_TEXT_DOMAIN ), array( 'status' => rest_authorization_required_code() ) );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access to create an item
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {

		if ( ! AtumCapabilities::current_user_can( 'publish_suppliers' ) ) {
			return new \WP_Error( 'atum_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
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

		if ( $object && 0 !== $object->ID && ! AtumCapabilities::current_user_can( 'read_supplier', $object->ID ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot view this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
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
	 * @return \WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {

		$object = $this->get_object( (int) $request['id'] );

		if ( $object && 0 !== $object->ID && ! AtumCapabilities::current_user_can( 'edit_supplier', $object->ID ) ) {
			return new \WP_Error( 'atum_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access to delete an item
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function delete_item_permissions_check( $request ) {

		$object = $this->get_object( (int) $request['id'] );

		if ( $object && 0 !== $object->ID && ! AtumCapabilities::current_user_can( 'delete_supplier', $object->ID ) ) {
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

		if ( ! AtumCapabilities::current_user_can( 'edit_others_suppliers' ) ) {
			return new \WP_Error( 'atum_rest_cannot_batch', __( 'Sorry, you are not allowed to batch manipulate this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Get a collection of supplier posts.
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {

		$args            = array();
		$args['offset']  = $request['offset'];
		$args['order']   = $request['order'];
		$args['orderby'] = $request['orderby'];
		$args['paged']   = $request['page'];

		$included = (array) $request['include'];

		// When filtering by product, get the supplier of the specified product ID.
		if ( ! empty( $request['product'] ) ) {

			$product_id = $request['product'];
			$product    = Helpers::get_atum_product( $product_id );

			if ( $product instanceof \WC_Product ) {
				$supplier_id = $product->get_supplier_id();
				$included[]  = $supplier_id;
			}

		}

		$args['post__in']            = array_unique( $included );
		$args['post__not_in']        = $request['exclude'];
		$args['posts_per_page']      = $request['per_page'];
		$args['name']                = $request['slug'];
		$args['post_parent__in']     = $request['parent'];
		$args['post_parent__not_in'] = $request['parent_exclude'];
		$args['s']                   = $request['search'];
		$args['post_status']         = $request['status'];

		// When filtering by currency, get all the suppliers that are using the specified currency.
		if ( ! empty( $request['currency'] ) ) {

			$args['meta_query'] = array(
				array(
					'key'   => '_billing_information_currency',
					'value' => $request['currency'],
				),
			);

		}

		// When filtering by currency, get all the suppliers within the specified country.
		if ( ! empty( $request['country'] ) ) {

			if ( ! isset( $args['meta_query'] ) ) {
				$args['meta_query'] = array();
			}

			$args['meta_query'][] = array(
				'key'   => '_billing_information_country',
				'value' => $request['country'],
			);

		}

		// When filtering by assigned user, get all the suppliers that have the specified user ID assigned.
		if ( ! empty( $request['assigned_to'] ) ) {

			if ( ! isset( $args['meta_query'] ) ) {
				$args['meta_query'] = array();
			}

			$args['meta_query'][] = array(
				'key'   => '_default_settings_assigned_to',
				'value' => $request['assigned_to'],
				'type'  => 'NUMERIC',
			);

		}

		$args['date_query'] = array();

		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}
		// Before modification date filter.
		elseif ( isset( $request['modified_before'] ) ) {
			$args['date_query'][0]['before'] = $request['modified_before'];
			$args['date_query'][0]['column'] = 'post_modified_gmt';
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}
		// After modification date filter.
		elseif ( isset( $request['modified_after'] ) ) {
			$args['date_query'][0]['after']  = $request['modified_after'];
			$args['date_query'][0]['column'] = 'post_modified_gmt';
		}

		// Force the post_type argument, since it's not a user input variable.
		$args['post_type'] = $this->post_type;

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post collection request.
		 *
		 * @param array            $args    Key value array of query var to query value.
		 * @param \WP_REST_Request $request The request used.
		 */
		$args       = apply_filters( "atum/api/rest_{$this->post_type}_query", $args, $request );
		$query_args = $this->prepare_items_query( $args, $request );

		$posts_query  = new \WP_Query();
		$query_result = $posts_query->query( $query_args );

		$posts = array();
		foreach ( $query_result as $post ) {

			if ( ! AtumCapabilities::current_user_can( 'read_supplier', $post->ID ) ) {
				continue;
			}

			$data    = $this->prepare_item_for_response( $post, $request );
			$posts[] = $this->prepare_response_for_collection( $data );

		}

		$page        = (int) $query_args['paged'];
		$total_posts = $posts_query->found_posts;

		if ( $total_posts < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );
			$count_query = new \WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		$max_pages = ceil( $total_posts / (int) $query_args['posts_per_page'] );

		$response = rest_ensure_response( $posts );
		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();

		if ( ! empty( $request_params['filter'] ) ) {
			// Normalize the pagination params.
			unset( $request_params['filter']['posts_per_page'] );
			unset( $request_params['filter']['paged'] );
		}

		$base = add_query_arg( $request_params, rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );

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
	 * Get Supplier object
	 *
	 * @param int $id Object ID.
	 *
	 * @since  1.6.2
	 *
	 * @return \WP_Post
	 */
	protected function get_object( $id ) {
		return get_post( $id );
	}

	/**
	 * Prepare a single supplier for create or update.
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param bool             $creating If is creating a new object.
	 *
	 * @return \WP_Error|\WP_Post
	 */
	protected function prepare_item_for_database( $request, $creating = FALSE ) {

		$prepared_post = new \stdClass();

		// Post ID.
		if ( isset( $request['id'] ) ) {

			$existing_post = $this->get_object( $request['id'] );

			if ( is_wp_error( $existing_post ) ) {
				return $existing_post;
			}

			$prepared_post->ID = $existing_post->ID;

		}

		$schema = $this->get_item_schema();

		// Post title.
		if ( ! empty( $schema['properties']['name'] ) && isset( $request['name'] ) ) {

			if ( is_string( $request['name'] ) ) {
				$prepared_post->post_title = $request['name'];
			}
			elseif ( ! empty( $request['name']['raw'] ) ) {
				$prepared_post->post_title = $request['name']['raw'];
			}

		}

		// Post type.
		$prepared_post->post_type = $this->post_type;

		// Post status.
		if ( ! empty( $schema['properties']['status'] ) && isset( $request['status'] ) ) {

			$status = $this->handle_status_param( $request['status'], get_post_type_object( $this->post_type ) );

			if ( is_wp_error( $status ) ) {
				return $status;
			}

			$prepared_post->post_status = $status;

		}

		// Post date.
		if ( ! empty( $schema['properties']['date_created'] ) && ! empty( $request['date_created'] ) ) {

			$date_data = rest_get_date_with_gmt( $request['date_created'] );

			if ( ! empty( $date_data ) ) {
				list( $prepared_post->post_date, $prepared_post->post_date_gmt ) = $date_data;

				$prepared_post->edit_date = TRUE;
			}

		}
		elseif ( ! empty( $schema['properties']['date_created_gmt'] ) && ! empty( $request['date_created_gmt'] ) ) {

			$date_data = rest_get_date_with_gmt( $request['date_created_gmt'], TRUE );

			if ( ! empty( $date_data ) ) {
				list( $prepared_post->post_date, $prepared_post->post_date_gmt ) = $date_data;

				$prepared_post->edit_date = TRUE;
			}

		}

		// Post slug.
		if ( ! empty( $schema['properties']['slug'] ) && isset( $request['slug'] ) ) {
			$prepared_post->post_name = $request['slug'];
		}
		elseif ( ! empty( $prepared_post->post_title ) ) {
			$prepared_post->post_name = sanitize_title( $prepared_post->post_title );
		}

		// Menu order.
		if ( ! empty( $schema['properties']['menu_order'] ) && isset( $request['menu_order'] ) ) {
			$prepared_post->menu_order = (int) $request['menu_order'];
		}

		$prepared_post->meta_input = array();

		// Supplier's meta.
		foreach (
			[
				'code',
				'tax_number',
				'phone',
				'fax',
				'website',
				'ordering_url',
				'general_email',
				'ordering_email',
				'description',
				'currency',
				'address',
				'city',
				'country',
				'state',
				'zip_code',
				'assigned_to',
				'location',
			] as $meta_key
		) {

			if ( ! empty( $schema['properties'][ $meta_key ] ) && isset( $request[ $meta_key ] ) ) {
				$prepared_post->meta_input[ "_$meta_key" ] = sanitize_text_field( $request[ $meta_key ] );
			}

		}

		/**
		 * Filters a supplier before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @param \stdClass        $prepared_post An object representing a single supplier prepared for inserting or updating the database.
		 * @param \WP_REST_Request $request       Request object.
		 */
		return apply_filters( "atum/api/rest_pre_insert_{$this->post_type}", $prepared_post, $request );

	}

	/**
	 * Prepares a single supplier output for response
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_Post         $supplier Supplier post object.
	 * @param \WP_REST_Request $request  Request object.
	 *
	 * @return \WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $supplier, $request ) {

		$GLOBALS['post'] = $supplier;
		setup_postdata( $supplier );

		$fields = $this->get_fields_for_response( $request );

		// Base fields for every post.
		$data = array();

		if ( in_array( 'id', $fields, TRUE ) ) {
			$data['id'] = $supplier->ID;
		}

		if ( in_array( 'name', $fields, TRUE ) ) {
			$data['name'] = $supplier->post_title;
		}

		if ( in_array( 'slug', $fields, TRUE ) ) {
			$data['slug'] = $supplier->post_name;
		}

		if ( in_array( 'permalink', $fields, TRUE ) ) {
			$data['permalink'] = get_permalink( $supplier->ID );
		}

		if ( in_array( 'date_created', $fields, TRUE ) ) {
			$data['date_created'] = wc_rest_prepare_date_response( $supplier->post_date );
		}

		if ( in_array( 'date_created_gmt', $fields, TRUE ) ) {
			$data['date_created_gmt'] = wc_rest_prepare_date_response( $supplier->post_date_gmt );
		}

		if ( in_array( 'date_modified', $fields, TRUE ) ) {
			$data['date_modified'] = wc_rest_prepare_date_response( $supplier->post_modified );
		}

		if ( in_array( 'date_modified_gmt', $fields, TRUE ) ) {
			$data['date_modified_gmt'] = wc_rest_prepare_date_response( $supplier->post_modified_gmt );
		}

		if ( in_array( 'status', $fields, TRUE ) ) {
			$data['status'] = $supplier->post_status;
		}

		$supplier_meta      = get_metadata( 'post', $supplier->ID );
		$supplier_meta_keys = array_keys( $supplier_meta );

		// Supplier's meta.
		foreach (
			[
				'code',
				'tax_number',
				'phone',
				'fax',
				'website',
				'ordering_url',
				'general_email',
				'ordering_email',
				'description',
				'currency',
				'address',
				'city',
				'country',
				'state',
				'zip_code',
				'assigned_to',
				'location',
			] as $meta_key
		) {

			if ( in_array( $meta_key, $fields, TRUE ) && in_array( "_$meta_key", $supplier_meta_keys, TRUE ) ) {
				$data[ $meta_key ] = current( $supplier_meta[ "_$meta_key" ] );
			}

		}

		if ( in_array( 'image', $fields, TRUE ) ) {

			$attachment_id   = (int) get_post_thumbnail_id( $supplier->ID );
			$attachment_post = get_post( $attachment_id );

			if ( ! is_null( $attachment_post ) ) {

				$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );

				if ( is_array( $attachment ) ) {

					$data['image'] = array(
						'id'                => (int) $attachment_id,
						'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, FALSE ),
						'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
						'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, FALSE ),
						'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
						'src'               => current( $attachment ),
						'name'              => get_the_title( $attachment_id ),
						'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', TRUE ),
					);

				}

			}

		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$links = $this->prepare_links( $supplier, $request );
		$response->add_links( $links );

		/**
		 * Filters the post data for a response.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \WP_Post          $supplier Post object.
		 * @param \WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "atum/api/rest_prepare_{$this->post_type}", $response, $supplier, $request );

	}

	/**
	 * Creates a single supplier.
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or \WP_Error object on failure.
	 */
	public function create_item( $request ) {

		if ( ! empty( $request['id'] ) ) {
			return new \WP_Error( 'atum_rest_supplier_exists', __( 'Cannot create existing supplier.', ATUM_TEXT_DOMAIN ), [ 'status' => 400 ] );
		}

		$prepared_post = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $prepared_post ) ) {
			return $prepared_post;
		}

		$prepared_post->post_type = $this->post_type;

		$post_id = wp_insert_post( wp_slash( (array) $prepared_post ), TRUE );

		if ( is_wp_error( $post_id ) ) {

			$post_id->add_data( [ 'status' => ( 'db_insert_error' === $post_id->get_error_code() ? 500 : 400 ) ] );

			return $post_id;
		}

		$post = $this->get_object( $post_id );

		/**
		 * Fires after a single post is created or updated via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @param \WP_Post         $post     Inserted or updated post object.
		 * @param \WP_REST_Request $request  Request object.
		 * @param bool             $creating True when creating a post, false when updating.
		 */
		do_action( "atum/api/rest_insert_{$this->post_type}", $post, $request, TRUE );

		$schema = $this->get_item_schema();

		if ( ! empty( $schema['properties']['image'] ) && isset( $request['image'], $request['image']['id'] ) ) {
			$this->handle_featured_media( $request['image']['id'], $post_id );
		}

		if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
			$meta_update = $this->meta->update_value( $request['meta'], $post_id );

			if ( is_wp_error( $meta_update ) ) {
				return $meta_update;
			}
		}

		$fields_update = $this->update_additional_fields_for_object( $post, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		/**
		 * Fires after a single post is completely created or updated via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @param \WP_Post         $post     Inserted or updated post object.
		 * @param \WP_REST_Request $request  Request object.
		 * @param bool             $creating True when creating a post, false when updating.
		 */
		do_action( "atum/api/rest_after_insert_{$this->post_type}", $post, $request, TRUE );

		$response = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $post_id ) ) );

		return $response;

	}

	/**
	 * Update a single supplier.
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {

		$id   = absint( $request['id'] );
		$post = $this->get_object( $id );

		if ( empty( $id ) || empty( $post->ID ) || $post->post_type !== $this->post_type ) {
			return new \WP_Error( "atum_rest_{$this->post_type}_invalid_id", __( 'ID is invalid.', ATUM_TEXT_DOMAIN ), [ 'status' => 400 ] );
		}

		/**
		 * Fires before a single item will be updated via the REST API.
		 *
		 * @param \WP_Post         $post      Post object.
		 * @param \WP_REST_Request $request   Request object.
		 */
		do_action( "atum/api/rest_before_insert_{$this->post_type}", $post, $request );

		$post = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		// Convert the post object to an array, otherwise wp_update_post will expect non-escaped input.
		$post_id = wp_update_post( (array) $post, TRUE );
		if ( is_wp_error( $post_id ) ) {

			$post_id->add_data( [ 'status' => ( 'db_update_error' === $post_id->get_error_code() ? 500 : 400 ) ] );

			return $post_id;

		}

		$post = $this->get_object( $post_id );
		$this->update_additional_fields_for_object( $post, $request );

		if ( isset( $request['image'], $request['image']['id'] ) ) {
			$this->handle_featured_media( $request['image']['id'], $post_id );
		}

		// Update meta fields.
		$meta_fields = $this->update_post_meta_fields( $post, $request );
		if ( is_wp_error( $meta_fields ) ) {
			return $meta_fields;
		}

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param \WP_Post         $post      Post object.
		 * @param \WP_REST_Request $request   Request object.
		 * @param bool             $creating  True when creating item, false when updating.
		 */
		do_action( "atum/api/rest_insert_{$this->post_type}", $post, $request, FALSE );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );

		return rest_ensure_response( $response );

	}

	/**
	 * Delete a single supplier
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {

		$id     = absint( $request['id'] );
		$force  = (bool) $request['force'];
		$object = $this->get_object( $id );

		if ( ! $object || 0 === $object->ID ) {
			return new \WP_Error( "atum_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$supports_trash = EMPTY_TRASH_DAYS > 0;

		/**
		 * Filter whether an object is trashable.
		 *
		 * Return false to disable trash support for the object.
		 *
		 * @param boolean $supports_trash Whether the object type support trashing.
		 * @param \WP_Post $object        The object being considered for trashing support.
		 */
		$supports_trash = apply_filters( "atum/api/rest_{$this->post_type}_object_trashable", $supports_trash, $object );

		if ( ! AtumCapabilities::current_user_can( 'delete_supplier', $object->ID ) ) {
			return new \WP_Error( "atum_rest_user_cannot_delete_{$this->post_type}", __( 'Sorry, you are not allowed to delete Suppliers.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $object, $request );

		// If we're forcing, then delete permanently.
		if ( $force ) {

			// Get all the products that were linked to this supplier and unlink them.
			$supplier_products = Suppliers::get_supplier_products( $object->ID );

			foreach ( $supplier_products as $product_id ) {

				$product = Helpers::get_atum_product( $product_id );

				if ( $product instanceof \WC_Product ) {
					$product->set_supplier_id( NULL );
					$product->save_atum_data();
				}

			}

			$result = wp_delete_post( $object->ID, TRUE );

		}
		else {

			// If we don't support trashing for this type, error out.
			if ( ! $supports_trash ) {
				return new \WP_Error( 'atum_rest_trash_not_supported', __( 'The Suppliers do not support trashing.', ATUM_TEXT_DOMAIN ), [ 'status' => 501 ] );
			}

			// Otherwise, only trash if we haven't already.
			if ( 'trash' === $object->post_status ) {
				return new \WP_Error( 'atum_rest_already_trashed', __( 'The Supplier has already been deleted.', ATUM_TEXT_DOMAIN ), [ 'status' => 410 ] );
			}

			$result = wp_trash_post( $object->ID );

		}

		if ( ! $result ) {
			return new \WP_Error( 'atum_rest_cannot_delete', __( 'The Supplier cannot be deleted.', ATUM_TEXT_DOMAIN ), [ 'status' => 500 ] );
		}

		/**
		 * Fires after a single object is deleted or trashed via the REST API.
		 *
		 * @param \WP_Post          $object   The deleted or trashed object.
		 * @param \WP_REST_Response $response The response data.
		 * @param \WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "atum/api/rest_delete_{$this->post_type}_object", $object, $response, $request );

		return $response;

	}

	/**
	 * Prepare links for the request.
	 *
	 * @param \WP_Post         $object  Object data.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return array                    Links for the given post.
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

	/**
	 * Determines the featured media based on a request param.
	 *
	 * @since 1.6.2
	 *
	 * @param int $featured_media Featured Media ID.
	 * @param int $post_id        Post ID.
	 *
	 * @return bool|\WP_Error Whether the post thumbnail was successfully deleted, otherwise \WP_Error.
	 */
	protected function handle_featured_media( $featured_media, $post_id ) {

		$featured_media = (int) $featured_media;
		if ( $featured_media ) {

			$result = set_post_thumbnail( $post_id, $featured_media );

			if ( $result ) {
				return TRUE;
			}
			else {
				return new \WP_Error( 'atum_rest_invalid_featured_media', __( 'Invalid featured media ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 400 ] );
			}

		}
		else {
			return delete_post_thumbnail( $post_id );
		}

	}

	/**
	 * Determines validity and normalizes the given status parameter.
	 *
	 * @since 1.6.2
	 *
	 * @param string $post_status Post status.
	 * @param object $post_type   Post type.
	 *
	 * @return string|\WP_Error Post status or \WP_Error if lacking the proper permission.
	 */
	protected function handle_status_param( $post_status, $post_type ) {

		switch ( $post_status ) {
			case 'draft':
			case 'pending':
				break;

			case 'private':
				if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
					return new \WP_Error( 'rest_cannot_publish', __( 'Sorry, you are not allowed to create private posts in this post type.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
				}
				break;

			case 'publish':
			case 'future':
				if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
					return new \WP_Error( 'rest_cannot_publish', __( 'Sorry, you are not allowed to publish posts in this post type.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
				}
				break;

			default:
				if ( ! get_post_status_object( $post_status ) ) {
					$post_status = 'draft';
				}
				break;
		}

		return $post_status;

	}

	/**
	 * Get the post statuses allowed for suppliers
	 *
	 * @since 1.7.5
	 *
	 * @return array
	 */
	private function get_supplier_post_statuses() {
		return apply_filters( 'atum/api/suppliers/statuses', array_merge( array_keys( get_post_statuses() ), [ 'any', 'trash' ] ) );
	}

}
