<?php
/**
 * REST ATUM API Suppliers controller
 * Handles requests to the /atum/suppliers endpoint.
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2019 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || die;

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
	 * Get the query params for collections of suppliers (for filtering purposes)
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		$supplier_params = array(
			'slug'        => array(
				'description'       => __( 'Limit result set to suppliers with a specific slug.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'status'      => array(
				'default'           => 'publish',
				'description'       => __( 'Limit result set to suppliers assigned a specific status.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'enum'              => array_keys( get_post_statuses() ),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'currency'    => array(
				'description'       => __( 'Limit result set to suppliers using the specified currency code.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => array_keys( get_woocommerce_currencies() ),
			),
			'country'     => array(
				'description'       => __( 'Limit result set to suppliers from the specified country code.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => array_keys( wc()->countries->get_countries() ),
			),
			'assigned_to' => array(
				'description'       => __( 'Limit result set to suppliers assigned to the specified user ID.', ATUM_TEXT_DOMAIN ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'product'     => array(
				'description'       => __( 'Limit result set to suppliers assigned to the specific product ID.', ATUM_TEXT_DOMAIN ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		return array_merge( $params, $supplier_params );

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

		// TODO: CHECK OUR OWN SUPPLIER PERMISSIONS.
		if ( $object && 0 !== $object->ID && ! wc_rest_check_post_permissions( $this->post_type, 'read', $object->ID ) ) {
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

		// TODO: CHECK OUR OWN SUPPLIER PERMISSIONS.
		if ( $object && 0 !== $object->ID && ! wc_rest_check_post_permissions( $this->post_type, 'edit', $object->ID ) ) {
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

		// TODO: CHECK OUR OWN SUPPLIER PERMISSIONS.
		if ( $object && 0 !== $object->ID && ! wc_rest_check_post_permissions( $this->post_type, 'delete', $object->ID ) ) {
			return new \WP_Error( 'atum_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Prepare a single product output for response
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_Post         $object  Object data.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_product_data( $object, $context );

		// Add variations to variable products.
		if ( $object->is_type( 'variable' ) && $object->has_child() ) {
			$data['variations'] = $object->get_children();
		}

		// Add grouped products data.
		if ( $object->is_type( 'grouped' ) && $object->has_child() ) {
			$data['grouped_products'] = $object->get_children();
		}

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \WC_Data          $object   Object data.
		 * @param \WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "atum/api/rest_prepare_{$this->post_type}_object", $response, $object, $request );

	}

	/**
	 * Make extra product orderby features supported by WooCommerce available to the WC API.
	 * This includes 'price', 'popularity', and 'rating'.
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {

		$args = \WC_REST_Posts_Controller::prepare_objects_query( $request );

		// Set post_status.
		$args['post_status'] = $request['status'];

		// Taxonomy query to filter products by type, category, tag, shipping class, and attribute.
		$tax_query = array();

		// Map between taxonomy name and arg's key.
		$taxonomies = array(
			'product_cat'            => 'category',
			'product_tag'            => 'tag',
			'product_shipping_class' => 'shipping_class',
		);

		// Set tax_query for each passed arg.
		foreach ( $taxonomies as $taxonomy => $key ) {
			if ( ! empty( $request[ $key ] ) ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $request[ $key ],
				);
			}
		}

		// Filter product type by slug.
		if ( ! empty( $request['type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $request['type'],
			);
		}

		// Filter by attribute and term.
		if ( ! empty( $request['attribute'] ) && ! empty( $request['attribute_term'] ) ) {
			if ( in_array( $request['attribute'], wc_get_attribute_taxonomy_names(), true ) ) {
				$tax_query[] = array(
					'taxonomy' => $request['attribute'],
					'field'    => 'term_id',
					'terms'    => $request['attribute_term'],
				);
			}
		}

		// Build tax_query if taxonomies are set.
		if ( ! empty( $tax_query ) ) {
			if ( ! empty( $args['tax_query'] ) ) {
				$args['tax_query'] = array_merge( $tax_query, $args['tax_query'] ); // WPCS: slow query ok.
			} else {
				$args['tax_query'] = $tax_query; // WPCS: slow query ok.
			}
		}

		// Filter featured.
		if ( is_bool( $request['featured'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'featured',
				'operator' => true === $request['featured'] ? 'IN' : 'NOT IN',
			);
		}

		// Filter by sku.
		if ( ! empty( $request['sku'] ) ) {
			$skus = explode( ',', $request['sku'] );
			// Include the current string as a SKU too.
			if ( 1 < count( $skus ) ) {
				$skus[] = $request['sku'];
			}

			$args['meta_query'] = $this->add_meta_query( // WPCS: slow query ok.
				$args, array(
					'key'     => '_sku',
					'value'   => $skus,
					'compare' => 'IN',
				)
			);
		}

		// Filter by tax class.
		if ( ! empty( $request['tax_class'] ) ) {
			$args['meta_query'] = $this->add_meta_query( // WPCS: slow query ok.
				$args, array(
					'key'   => '_tax_class',
					'value' => 'standard' !== $request['tax_class'] ? $request['tax_class'] : '',
				)
			);
		}

		// Price filter.
		if ( ! empty( $request['min_price'] ) || ! empty( $request['max_price'] ) ) {
			$args['meta_query'] = $this->add_meta_query( $args, wc_get_min_max_price_meta_query( $request ) );  // WPCS: slow query ok.
		}

		// Filter product by stock_status.
		if ( ! empty( $request['stock_status'] ) ) {
			$args['meta_query'] = $this->add_meta_query( // WPCS: slow query ok.
				$args, array(
					'key'   => '_stock_status',
					'value' => $request['stock_status'],
				)
			);
		}

		// Filter by on sale products.
		if ( is_bool( $request['on_sale'] ) ) {
			$on_sale_key = $request['on_sale'] ? 'post__in' : 'post__not_in';
			$on_sale_ids = wc_get_product_ids_on_sale();

			// Use 0 when there's no on sale products to avoid return all products.
			$on_sale_ids = empty( $on_sale_ids ) ? array( 0 ) : $on_sale_ids;

			$args[ $on_sale_key ] += $on_sale_ids;
		}

		// Force the post_type argument, since it's not a user input variable.
		if ( ! empty( $request['sku'] ) ) {
			$args['post_type'] = array( 'product', 'product_variation' );
		}
		else {
			$args['post_type'] = $this->post_type;
		}

		$orderby = $request->get_param( 'orderby' );
		$order   = $request->get_param( 'order' );

		$ordering_args   = WC()->query->get_catalog_ordering_args( $orderby, $order );
		$args['orderby'] = $ordering_args['orderby'];
		$args['order']   = $ordering_args['order'];

		if ( $ordering_args['meta_key'] ) {
			$args['meta_key'] = $ordering_args['meta_key']; // WPCS: slow query ok.
		}

		return $args;

	}

	/**
	 * Prepare a single product for create or update.
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param bool             $creating If is creating a new object.
	 *
	 * @return \WP_Error|\WC_Data
	 */
	protected function prepare_object_for_database( $request, $creating = FALSE ) {

		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;

		// Type is the most important part here because we need to be using the correct class and methods.
		if ( isset( $request['type'] ) ) {
			$classname = \WC_Product_Factory::get_classname_from_product_type( $request['type'] );

			if ( ! class_exists( $classname ) ) {
				$classname = 'WC_Product_Simple';
			}

			$product = new $classname( $id );
		}
		elseif ( isset( $request['id'] ) ) {
			$product = wc_get_product( $id );
		}
		else {
			$product = new \WC_Product_Simple();
		}

		if ( 'variation' === $product->get_type() ) {
			return new \WP_Error(
				"woocommerce_rest_invalid_{$this->post_type}_id", __( 'To manipulate product variations you should use the /products/&lt;product_id&gt;/variations/&lt;id&gt; endpoint.', ATUM_TEXT_DOMAIN ), array(
					'status' => 404,
				)
			);
		}

		// Post title.
		if ( isset( $request['name'] ) ) {
			$product->set_name( wp_filter_post_kses( $request['name'] ) );
		}

		// Post content.
		if ( isset( $request['description'] ) ) {
			$product->set_description( wp_filter_post_kses( $request['description'] ) );
		}

		// Post excerpt.
		if ( isset( $request['short_description'] ) ) {
			$product->set_short_description( wp_filter_post_kses( $request['short_description'] ) );
		}

		// Post status.
		if ( isset( $request['status'] ) ) {
			$product->set_status( get_post_status_object( $request['status'] ) ? $request['status'] : 'draft' );
		}

		// Post slug.
		if ( isset( $request['slug'] ) ) {
			$product->set_slug( $request['slug'] );
		}

		// Menu order.
		if ( isset( $request['menu_order'] ) ) {
			$product->set_menu_order( $request['menu_order'] );
		}

		// Comment status.
		if ( isset( $request['reviews_allowed'] ) ) {
			$product->set_reviews_allowed( $request['reviews_allowed'] );
		}

		// Virtual.
		if ( isset( $request['virtual'] ) ) {
			$product->set_virtual( $request['virtual'] );
		}

		// Tax status.
		if ( isset( $request['tax_status'] ) ) {
			$product->set_tax_status( $request['tax_status'] );
		}

		// Tax Class.
		if ( isset( $request['tax_class'] ) ) {
			$product->set_tax_class( $request['tax_class'] );
		}

		// Catalog Visibility.
		if ( isset( $request['catalog_visibility'] ) ) {
			$product->set_catalog_visibility( $request['catalog_visibility'] );
		}

		// Purchase Note.
		if ( isset( $request['purchase_note'] ) ) {
			$product->set_purchase_note( wp_kses_post( wp_unslash( $request['purchase_note'] ) ) );
		}

		// Featured Product.
		if ( isset( $request['featured'] ) ) {
			$product->set_featured( $request['featured'] );
		}

		// Shipping data.
		$product = $this->save_product_shipping_data( $product, $request );

		// SKU.
		if ( isset( $request['sku'] ) ) {
			$product->set_sku( wc_clean( $request['sku'] ) );
		}

		// Attributes.
		if ( isset( $request['attributes'] ) ) {
			$attributes = array();

			foreach ( $request['attributes'] as $attribute ) {
				$attribute_id   = 0;
				$attribute_name = '';

				// Check ID for global attributes or name for product attributes.
				if ( ! empty( $attribute['id'] ) ) {
					$attribute_id   = absint( $attribute['id'] );
					$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
				}
				elseif ( ! empty( $attribute['name'] ) ) {
					$attribute_name = wc_clean( $attribute['name'] );
				}

				if ( ! $attribute_id && ! $attribute_name ) {
					continue;
				}

				if ( $attribute_id ) {

					if ( isset( $attribute['options'] ) ) {
						$options = $attribute['options'];

						if ( ! is_array( $attribute['options'] ) ) {
							// Text based attributes - Posted values are term names.
							$options = explode( WC_DELIMITER, $options );
						}

						$values = array_map( 'wc_sanitize_term_text_based', $options );
						$values = array_filter( $values, 'strlen' );
					}
					else {
						$values = array();
					}

					if ( ! empty( $values ) ) {
						// Add attribute to array, but don't set values.
						$attribute_object = new \WC_Product_Attribute();
						$attribute_object->set_id( $attribute_id );
						$attribute_object->set_name( $attribute_name );
						$attribute_object->set_options( $values );
						$attribute_object->set_position( isset( $attribute['position'] ) ? (string) absint( $attribute['position'] ) : '0' );
						$attribute_object->set_visible( ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0 );
						$attribute_object->set_variation( ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0 );
						$attributes[] = $attribute_object;
					}
				}
				elseif ( isset( $attribute['options'] ) ) {
					// Custom attribute - Add attribute to array and set the values.
					if ( is_array( $attribute['options'] ) ) {
						$values = $attribute['options'];
					}
					else {
						$values = explode( WC_DELIMITER, $attribute['options'] );
					}
					$attribute_object = new \WC_Product_Attribute();
					$attribute_object->set_name( $attribute_name );
					$attribute_object->set_options( $values );
					$attribute_object->set_position( isset( $attribute['position'] ) ? (string) absint( $attribute['position'] ) : '0' );
					$attribute_object->set_visible( ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0 );
					$attribute_object->set_variation( ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0 );
					$attributes[] = $attribute_object;
				}
			}

			$product->set_attributes( $attributes );

		}

		// Sales and prices.
		if ( in_array( $product->get_type(), array( 'variable', 'grouped' ), TRUE ) ) {
			$product->set_regular_price( '' );
			$product->set_sale_price( '' );
			$product->set_date_on_sale_to( '' );
			$product->set_date_on_sale_from( '' );
			$product->set_price( '' );
		}
		else {
			// Regular Price.
			if ( isset( $request['regular_price'] ) ) {
				$product->set_regular_price( $request['regular_price'] );
			}

			// Sale Price.
			if ( isset( $request['sale_price'] ) ) {
				$product->set_sale_price( $request['sale_price'] );
			}

			if ( isset( $request['date_on_sale_from'] ) ) {
				$product->set_date_on_sale_from( $request['date_on_sale_from'] );
			}

			if ( isset( $request['date_on_sale_from_gmt'] ) ) {
				$product->set_date_on_sale_from( $request['date_on_sale_from_gmt'] ? strtotime( $request['date_on_sale_from_gmt'] ) : NULL );
			}

			if ( isset( $request['date_on_sale_to'] ) ) {
				$product->set_date_on_sale_to( $request['date_on_sale_to'] );
			}

			if ( isset( $request['date_on_sale_to_gmt'] ) ) {
				$product->set_date_on_sale_to( $request['date_on_sale_to_gmt'] ? strtotime( $request['date_on_sale_to_gmt'] ) : NULL );
			}
		}

		// Product parent ID.
		if ( isset( $request['parent_id'] ) ) {
			$product->set_parent_id( $request['parent_id'] );
		}

		// Sold individually.
		if ( isset( $request['sold_individually'] ) ) {
			$product->set_sold_individually( $request['sold_individually'] );
		}

		// Stock status; stock_status has priority over in_stock.
		if ( isset( $request['stock_status'] ) ) {
			$stock_status = $request['stock_status'];
		}
		else {
			$stock_status = $product->get_stock_status();
		}

		// Stock data.
		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			// Manage stock.
			if ( isset( $request['manage_stock'] ) ) {
				$product->set_manage_stock( $request['manage_stock'] );
			}

			// Backorders.
			if ( isset( $request['backorders'] ) ) {
				$product->set_backorders( $request['backorders'] );
			}

			if ( $product->is_type( 'grouped' ) ) {
				$product->set_manage_stock( 'no' );
				$product->set_backorders( 'no' );
				$product->set_stock_quantity( '' );
				$product->set_stock_status( $stock_status );
			}
			elseif ( $product->is_type( 'external' ) ) {
				$product->set_manage_stock( 'no' );
				$product->set_backorders( 'no' );
				$product->set_stock_quantity( '' );
				$product->set_stock_status( 'instock' );
			}
			elseif ( $product->get_manage_stock() ) {
				// Stock status is always determined by children so sync later.
				if ( ! $product->is_type( 'variable' ) ) {
					$product->set_stock_status( $stock_status );
				}

				// Stock quantity.
				if ( isset( $request['stock_quantity'] ) ) {
					$product->set_stock_quantity( wc_stock_amount( $request['stock_quantity'] ) );
				}
				elseif ( isset( $request['inventory_delta'] ) ) {
					$stock_quantity  = wc_stock_amount( $product->get_stock_quantity() );
					$stock_quantity += wc_stock_amount( $request['inventory_delta'] );
					$product->set_stock_quantity( wc_stock_amount( $stock_quantity ) );
				}
			}
			else {
				// Don't manage stock.
				$product->set_manage_stock( 'no' );
				$product->set_stock_quantity( '' );
				$product->set_stock_status( $stock_status );
			}
		}
		elseif ( ! $product->is_type( 'variable' ) ) {
			$product->set_stock_status( $stock_status );
		}

		// Upsells.
		if ( isset( $request['upsell_ids'] ) ) {
			$upsells = array();
			$ids     = $request['upsell_ids'];

			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $id && $id > 0 ) {
						$upsells[] = $id;
					}
				}
			}

			$product->set_upsell_ids( $upsells );
		}

		// Cross sells.
		if ( isset( $request['cross_sell_ids'] ) ) {
			$crosssells = array();
			$ids        = $request['cross_sell_ids'];

			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $id && $id > 0 ) {
						$crosssells[] = $id;
					}
				}
			}

			$product->set_cross_sell_ids( $crosssells );
		}

		// Product categories.
		if ( isset( $request['categories'] ) && is_array( $request['categories'] ) ) {
			$product = $this->save_taxonomy_terms( $product, $request['categories'] );
		}

		// Product tags.
		if ( isset( $request['tags'] ) && is_array( $request['tags'] ) ) {
			$product = $this->save_taxonomy_terms( $product, $request['tags'], 'tag' );
		}

		// Downloadable.
		if ( isset( $request['downloadable'] ) ) {
			$product->set_downloadable( $request['downloadable'] );
		}

		// Downloadable options.
		if ( $product->get_downloadable() ) {

			// Downloadable files.
			if ( isset( $request['downloads'] ) && is_array( $request['downloads'] ) ) {
				$product = $this->save_downloadable_files( $product, $request['downloads'] );
			}

			// Download limit.
			if ( isset( $request['download_limit'] ) ) {
				$product->set_download_limit( $request['download_limit'] );
			}

			// Download expiry.
			if ( isset( $request['download_expiry'] ) ) {
				$product->set_download_expiry( $request['download_expiry'] );
			}
		}

		// Product url and button text for external products.
		if ( $product->is_type( 'external' ) ) {
			if ( isset( $request['external_url'] ) ) {
				$product->set_product_url( $request['external_url'] );
			}

			if ( isset( $request['button_text'] ) ) {
				$product->set_button_text( $request['button_text'] );
			}
		}

		// Save default attributes for variable products.
		if ( $product->is_type( 'variable' ) ) {
			$product = $this->save_default_attributes( $product, $request );
		}

		// Set children for a grouped product.
		if ( $product->is_type( 'grouped' ) && isset( $request['grouped_products'] ) ) {
			$product->set_children( $request['grouped_products'] );
		}

		// Check for featured/gallery images, upload it and set it.
		if ( isset( $request['images'] ) ) {
			$product = $this->set_product_images( $product, $request['images'] );
		}

		// Allow set meta_data.
		if ( is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$product->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		if ( ! empty( $request['date_created'] ) ) {
			$date = rest_parse_date( $request['date_created'] );

			if ( $date ) {
				$product->set_date_created( $date );
			}
		}

		if ( ! empty( $request['date_created_gmt'] ) ) {
			$date = rest_parse_date( $request['date_created_gmt'], true );

			if ( $date ) {
				$product->set_date_created( $date );
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the object type slug.
		 *
		 * @param \WC_Data         $product  Object object.
		 * @param \WP_REST_Request $request  Request object.
		 * @param bool             $creating If is creating a new object.
		 */
		return apply_filters( "atum/api/rest_pre_insert_{$this->post_type}_object", $product, $request, $creating );

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

		// Supplier details' meta.
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
			] as $meta_key
		) {

			if ( in_array( $meta_key, $fields, TRUE ) && in_array( "_supplier_details_$meta_key", $supplier_meta_keys, TRUE ) ) {
				$data[ $meta_key ] = current( $supplier_meta[ "_supplier_details_$meta_key" ] );
			}

		}

		// Billing information's meta.
		foreach (
			[
				'currency',
				'address',
				'city',
				'country',
				'state',
				'zip_code',
			] as $meta_key
		) {

			if ( in_array( $meta_key, $fields, TRUE ) && in_array( "_billing_information_$meta_key", $supplier_meta_keys, TRUE ) ) {
				$data[ $meta_key ] = current( $supplier_meta[ "_billing_information_$meta_key" ] );
			}

		}

		// Default settings' meta.
		foreach (
			[
				'assigned_to',
				'location',
			] as $meta_key
		) {

			if ( in_array( $meta_key, $fields, TRUE ) && in_array( "_default_settings_$meta_key", $supplier_meta_keys, TRUE ) ) {
				$data[ $meta_key ] = current( $supplier_meta[ "_default_settings_$meta_key" ] );
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
	 * Delete a single item
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {

		$id     = (int) $request['id'];
		$force  = (bool) $request['force'];
		$object = $this->get_object( $id );
		$result = FALSE;

		if ( ! $object || 0 === $object->ID ) {
			return new \WP_Error( "atum_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$supports_trash = EMPTY_TRASH_DAYS > 0 && is_callable( array( $object, 'get_status' ) );

		/**
		 * Filter whether an object is trashable.
		 *
		 * Return false to disable trash support for the object.
		 *
		 * @param boolean $supports_trash Whether the object type support trashing.
		 * @param \WP_Post $object        The object being considered for trashing support.
		 */
		$supports_trash = apply_filters( "atum/api/rest_{$this->post_type}_object_trashable", $supports_trash, $object );

		if ( ! wc_rest_check_post_permissions( $this->post_type, 'delete', $object->ID ) ) {
			return new \WP_Error( "atum_rest_user_cannot_delete_{$this->post_type}", __( 'Sorry, you are not allowed to delete Suppliers.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $object, $request );

		// If we're forcing, then delete permanently.
		if ( $force ) {

			// Get all the products that were linked to this supplier and unlink them.
			$supplier_products = Suppliers::get_supplier_products( $object->ID );

			foreach ( $supplier_products as $product_id ) {

				$product = Helpers::get_atum_product( $product_id );

				if ( is_a( $product, '\WC_Product' ) ) {
					$product->set_supplier_id( NULL );
					$product->save_atum_data();
				}

			}

			$object->delete( TRUE );
			$result = 0 === $object->ID;

		}
		else {

			// If we don't support trashing for this type, error out.
			if ( ! $supports_trash ) {
				return new \WP_Error( 'atum_rest_trash_not_supported', __( 'The Suppliers does not support trashing.', ATUM_TEXT_DOMAIN ), [ 'status' => 501 ] );
			}

			// Otherwise, only trash if we haven't already.
			if ( is_callable( array( $object, 'get_status' ) ) ) {

				if ( 'trash' === $object->get_status() ) {
					return new \WP_Error( 'atum_rest_already_trashed', __( 'The Supplier has already been deleted.', ATUM_TEXT_DOMAIN ), [ 'status' => 410 ] );
				}

				$object->delete();
				$result = 'trash' === $object->get_status();

			}

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
					'description' => __( 'Product URL.', ATUM_TEXT_DOMAIN ),
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
					'enum'        => array_keys( get_post_statuses() ),
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
					'description' => __( 'Supplier city.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( wc()->countries->get_countries() ),
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
	 * Prepare links for the request.
	 *
	 * @param \WP_Post         $object  Object data.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return array                    Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {

		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;

	}

}
