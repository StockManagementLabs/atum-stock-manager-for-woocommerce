<?php
/**
 * Extender for the WC's products endpoint
 * Adds the ATUM Product Data to this endpoint
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @package     Atum\Api
 * @subpackage  Extenders
 */

namespace Atum\Api\Extenders;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumCalculatedProps;
use Atum\Components\AtumCapabilities;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Modules\ModuleManager;


class AtumProductData {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumProductData
	 */
	private static $instance;

	/**
	 * The ATUM product data used in WP_Query
	 *
	 * @var array
	 */
	protected $atum_query_data = array();

	/**
	 * Custom ATUM API's product field names, indicating support for getting/updating.
	 *
	 * @var array
	 */
	private $product_fields = array(
		'purchase_price'      => [ 'get', 'update' ],
		'supplier_id'         => [ 'get', 'update' ],
		'supplier_sku'        => [ 'get', 'update' ],
		'barcode'             => [ 'get', 'update' ],
		'atum_controlled'     => [ 'get', 'update' ],
		'out_stock_date'      => [ 'get', 'update' ],
		'out_stock_threshold' => [ 'get', 'update' ],
		'inheritable'         => [ 'get', 'update' ],
		'inbound_stock'       => [ 'get', 'update' ],
		'stock_on_hold'       => [ 'get', 'update' ],
		'sold_today'          => [ 'get', 'update' ],
		'sales_last_days'     => [ 'get', 'update' ],
		'reserved_stock'      => [ 'get', 'update' ],
		'customer_returns'    => [ 'get', 'update' ],
		'warehouse_damage'    => [ 'get', 'update' ],
		'lost_in_post'        => [ 'get', 'update' ],
		'other_logs'          => [ 'get', 'update' ],
		'out_stock_days'      => [ 'get', 'update' ],
		'lost_sales'          => [ 'get', 'update' ],
		'has_location'        => [ 'get', 'update' ],
		'update_date'         => [ 'get', 'update' ],
		'atum_locations'      => [ 'get', 'update' ],
		'atum_stock_status'   => [ 'get', 'update' ],
		'restock_status'      => [ 'get', 'update' ],
		'low_stock_amount'    => [ 'get', 'update' ], // The WC's low stock threshold.
		'sales_update_date'   => [ 'get', 'update' ],
		'calc_backorders'     => [ 'get', 'update' ],
	);

	/**
	 * Internal meta keys that shouldn't appear on the product's meta_data
	 *
	 * @var array
	 */
	private $internal_meta_keys = array(
		'_atum_manage_stock',
		'_supplier',
		'_supplier_sku',
		'_purchase_price',
		'_out_stock_threshold',
		'_out_stock_threshold_custom',
		'_out_stock_threshold_currency',
		'_inheritable',
	);

	/**
	 * AtumProductData constructor
	 *
	 * @since 1.6.2
	 */
	private function __construct() {

		/**
		 * Pre-filter the data props according to the enabled modules and current user's capabilities.
		 */
		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) ) {
			unset(
				$this->product_fields['purchase_price'],
				$this->product_fields['supplier_id'],
				$this->product_fields['supplier_sku'],
				$this->product_fields['inbound_stock'],
				$this->product_fields['has_location']
			);
		}
		elseif ( ! AtumCapabilities::current_user_can( 'view_purchase_price' ) ) {
			unset( $this->product_fields['purchase_price'] );
		}
		elseif ( ! AtumCapabilities::current_user_can( 'read_inbound_stock' ) ) {
			unset( $this->product_fields['inbound_stock'] );
		}
		elseif ( ! AtumCapabilities::current_user_can( 'read_private_suppliers' ) ) {
			unset( $this->product_fields['supplier_id'], $this->product_fields['supplier_sku'] );
		}
		elseif ( ! AtumCapabilities::current_user_can( 'manage_location_terms' ) ) {
			unset(
				$this->product_fields['has_location'],
				$this->product_fields['atum_locations']
			);
		}

		if ( ! ModuleManager::is_module_active( 'inventory_logs' ) ) {
			unset(
				$this->product_fields['reserved_stock'],
				$this->product_fields['customer_returns'],
				$this->product_fields['warehouse_damage'],
				$this->product_fields['lost_in_post'],
				$this->product_fields['other_logs']
			);
		}

		if ( ! ModuleManager::is_module_active( 'barcodes' ) ) {
			unset( $this->product_fields['barcode'] );
		}

		/**
		 * Register the ATUM Product data custom fields to the WC API.
		 */
		add_action( 'rest_api_init', array( $this, 'register_product_fields' ), 0 );

		// Exclude internal meta keys from the product's meta_data.
		add_filter( 'woocommerce_data_store_wp_post_read_meta', array( $this, 'filter_product_meta' ), 10, 3 );

		foreach ( [ 'product', 'product_variation' ] as $post_type ) {

			// Add extra data to the products' query.
			add_filter( "woocommerce_rest_{$post_type}_object_query", array( $this, 'prepare_objects_query' ), 10, 2 );

			// Add extra filtering params to products.
			add_filter( "rest_{$post_type}_collection_params", array( $this, 'add_collection_params' ), 10, 2 );

			// Make stock_quantity property accept decimal numbers.
			add_filter( "woocommerce_rest_{$post_type}_schema", array( $this, 'change_product_stock_quantity_schema' ) );

		}

		// Alter some of the WC fields before sending the response.
		add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'prepare_rest_response' ), 10, 3 );

		// Update ATUM calc properties after saving.
		add_filter( 'woocommerce_rest_insert_product_object', array( $this, 'after_rest_product_save' ), 10, 3 );

		// Allow API to save out_stock_date as it, no utc/gmt time.
		add_filter( 'atum/data_store/date_columns', array( $this, 'remove_out_stock_date_column' ) );

		// Force API to save out_stock_date as string.
		add_filter( 'atum/product_data/maybe_set_out_stock_date_as_string', '__return_true' );
	}

	/**
	 * Register the WC API custom fields for product requests.
	 *
	 * @since 1.6.2
	 */
	public function register_product_fields() {

		$product_fields = apply_filters( 'atum/api/product_data/product_fields', $this->product_fields );

		foreach ( $product_fields as $field_name => $field_supports ) {

			$args = array(
				'schema' => $this->get_product_field_schema( $field_name ),
			);

			if ( in_array( 'get', $field_supports ) ) {
				$args['get_callback'] = array( $this, 'get_product_field_value' );
			}

			if ( in_array( 'update', $field_supports ) ) {
				$args['update_callback'] = array( $this, 'update_product_field_value' );
			}

			// Add the field to the product and product_variations endpoints.
			register_rest_field( 'product', $field_name, $args );

			// Some fields are not needed in variations.
			if ( ! in_array( $field_name, [ 'has_location', 'atum_locations', 'inheritable' ], TRUE ) ) {
				register_rest_field( 'product_variation', $field_name, $args );
			}

		}

	}

	/**
	 * Gets extended (unprefixed) schema properties for products.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	private function get_extended_product_schema() {

		$extended_product_schema = array(
			'purchase_price'      => array(
				'required'    => FALSE,
				'description' => __( "Product's purchase price.", ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'supplier_id'         => array(
				'required'    => FALSE,
				'description' => __( 'The ID of the ATUM Supplier that is linked to the product.', ATUM_TEXT_DOMAIN ),
				'type'        => 'integer',
			),
			'supplier_sku'        => array(
				'required'    => FALSE,
				'description' => __( "The Supplier's SKU for the product.", ATUM_TEXT_DOMAIN ),
				'type'        => 'string',
			),
			'barcode'             => array(
				'required'    => FALSE,
				'description' => __( "The product's barcode.", ATUM_TEXT_DOMAIN ),
				'type'        => 'string',
			),
			'atum_controlled'     => array(
				'required'    => FALSE,
				'description' => __( 'Whether the product is being controlled by ATUM.', ATUM_TEXT_DOMAIN ),
				'type'        => 'boolean',
				'default'     => FALSE,
			),
			'out_stock_date'      => array(
				'required'    => FALSE,
				'description' => __( 'The date when the product run out of stock.', ATUM_TEXT_DOMAIN ),
				'type'        => 'date-time',
			),
			'out_stock_threshold' => array(
				'required'    => FALSE,
				'description' => __( 'Out of stock threshold at product level.', ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'inheritable'         => array(
				'required'    => FALSE,
				'description' => __( 'Whether this product may have children.', ATUM_TEXT_DOMAIN ),
				'type'        => 'boolean',
				'default'     => FALSE,
			),
			'inbound_stock'       => array(
				'required'    => FALSE,
				'description' => __( "Product's inbound stock.", ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'stock_on_hold'       => array(
				'required'    => FALSE,
				'description' => __( "Product's stock on hold.", ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'sold_today'          => array(
				'required'    => FALSE,
				'description' => __( 'Units sold today.', ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'sales_last_days'     => array(
				'required'    => FALSE,
				'description' => __( 'Sales the last 14 days.', ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'reserved_stock'      => array(
				'required'    => FALSE,
				'description' => __( "Stock set as 'reserved_stock' within Inventory Logs.", ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'customer_returns'    => array(
				'required'    => FALSE,
				'description' => __( "Stock set as 'customer returns' within Inventory Logs.", ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'warehouse_damage'    => array(
				'required'    => FALSE,
				'description' => __( "Stock set as 'warehouse damage' within Inventory Logs.", ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'lost_in_post'        => array(
				'required'    => FALSE,
				'description' => __( "Stock set as 'lost in post' within Inventory Logs.", ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'other_logs'          => array(
				'required'    => FALSE,
				'description' => __( "Stock set as 'other' within Inventory Logs.", ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'out_stock_days'      => array(
				'required'    => FALSE,
				'description' => __( 'The number of days that the product is Out of stock.', ATUM_TEXT_DOMAIN ),
				'type'        => 'integer',
			),
			'lost_sales'          => array(
				'required'    => FALSE,
				'description' => __( 'Product lost sales.', ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
			'has_location'        => array(
				'required'    => FALSE,
				'description' => __( 'Whether the product has any ATUM location set.', ATUM_TEXT_DOMAIN ),
				'type'        => 'boolean',
			),
			'update_date'         => array(
				'required'    => FALSE,
				'description' => __( 'Last date when the ATUM product data was calculated and saved for the product.', ATUM_TEXT_DOMAIN ),
				'type'        => 'date-time',
			),
			'atum_locations'      => array(
				'description' => __( 'List of ATUM locations linked to the product.', ATUM_TEXT_DOMAIN ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'   => array(
							'description' => __( 'Location ID.', ATUM_TEXT_DOMAIN ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'name' => array(
							'description' => __( 'Location name.', ATUM_TEXT_DOMAIN ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => TRUE,
						),
						'slug' => array(
							'description' => __( 'Location slug.', ATUM_TEXT_DOMAIN ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => TRUE,
						),
					),
				),
			),
			'atum_stock_status'   => array(
				'required'    => FALSE,
				'description' => __( 'Used to store the stock status (same as WC) but both values may differ for MI enable products.', ATUM_TEXT_DOMAIN ),
				'type'        => 'string',
			),
			'restock_status'      => array(
				'required'    => FALSE,
				'description' => __( 'Indicates whether the stock will last soon and should be reordered.', ATUM_TEXT_DOMAIN ),
				'type'        => 'boolean',
			),
			'sales_update_date'   => array(
				'required'    => FALSE,
				'description' => __( 'Last date when the sales fields on ATUM product data were calculated and saved for the product.', ATUM_TEXT_DOMAIN ),
				'type'        => 'date-time',
			),
			'calc_backorders'     => array(
				'required'    => FALSE,
				'description' => __( 'Backordered items (if backorders is enabled and it has a negative stock value).', ATUM_TEXT_DOMAIN ),
				'type'        => 'number',
			),
		);

		return apply_filters( 'atum/api/product_data/extended_schema', $extended_product_schema );

	}

	/**
	 * Gets schema properties for ATUM product data fields
	 *
	 * @since 1.6.2
	 *
	 * @param string $field_name
	 *
	 * @return array
	 */
	public function get_product_field_schema( $field_name ) {

		$extended_schema = $this->get_extended_product_schema();

		return isset( $extended_schema[ $field_name ] ) ? $extended_schema[ $field_name ] : NULL;

	}

	/**
	 * Gets values for ATUM product data fields
	 *
	 * @since 1.6.2
	 *
	 * @param array            $response
	 * @param string           $field_name
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_product_field_value( $response, $field_name, $request ) {

		$data = NULL;

		if ( ! empty( $response['id'] ) ) {

			$product = Helpers::get_atum_product( $response['id'] );
			$getter  = "get_$field_name";

			if ( $product instanceof \WC_Product ) {

				if ( 'atum_locations' === $field_name ) {

					$data = array();

					foreach ( wc_get_object_terms( $product->get_id(), Globals::PRODUCT_LOCATION_TAXONOMY ) as $term ) {
						$data[] = array(
							'id'   => $term->term_id,
							'name' => $term->name,
							'slug' => $term->slug,
						);
					}

				}
				elseif ( is_callable( array( $product, $getter ) ) ) {
					$data = call_user_func( array( $product, $getter ) );
				}

				// Allow to handle some fields externally.
				$data = apply_filters( 'atum/api/product_data/get_field_value', $data, $response, $field_name, $product );

				$schema = $this->get_extended_product_schema();

				if ( ! is_null( $data ) && isset( $schema[ $field_name ], $schema[ $field_name ]['type'] ) ) {

					switch ( $schema[ $field_name ]['type'] ) {
						case 'date-time':
							if ( $data instanceof \WC_DateTime ) {
								$data = wc_rest_prepare_date_response( $data );
							}
							break;

						case 'number':
							$data = is_numeric( $data ) ? (float) $data : NULL;
							break;

						case 'integer':
							$data = is_numeric( $data ) ? (int) $data : NULL;
							break;

						case 'boolean':
							$data = wc_string_to_bool( $data );
							break;
					}

				}

			}

		}

		return $data;

	}

	/**
	 * Updates values for the ATUM product data fields
	 *
	 * @since 1.6.2
	 *
	 * @param mixed  $field_value
	 * @param mixed  $response
	 * @param string $field_name
	 *
	 * @return bool
	 *
	 * @throws \WC_REST_Exception
	 */
	public function update_product_field_value( $field_value, $response, $field_name ) {

		if (
			( 'purchase_price' === $field_name && ! AtumCapabilities::current_user_can( 'edit_purchase_price' ) ) ||
			( 'out_stock_threshold' === $field_name && ! AtumCapabilities::current_user_can( 'edit_out_stock_threshold' ) )
		) {
			/* translators: the field name */
			throw new \WC_REST_Exception( 'atum_rest_invalid_product', sprintf( __( 'You are not allowed to edit the %s field.', ATUM_TEXT_DOMAIN ), $field_name ), 400 );
		}

		$product_id = NULL;

		if ( $response instanceof \WC_Product ) {
			$product_id = $response->get_id();
		}
		elseif ( $response instanceof \WP_Post ) {
			$product_id = absint( $response->ID );
		}

		$product = Helpers::get_atum_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			/* translators: the product ID */
			throw new \WC_REST_Exception( 'atum_rest_invalid_product', sprintf( __( 'Invalid product with ID #%s.', ATUM_TEXT_DOMAIN ), $product_id ), 400 );
		}

		$setter = "set_$field_name";

		if ( 'atum_locations' === $field_name ) {

			$term_ids = wp_list_pluck( $field_value, 'id' );
			$term_ids = array_unique( array_filter( array_map( 'absint', $term_ids ) ) );

			wp_set_object_terms( $product_id, $term_ids, Globals::PRODUCT_LOCATION_TAXONOMY );

		}
		elseif ( is_callable( array( $product, $setter ) ) ) {
			call_user_func( array( $product, $setter ), $field_value );
			AtumCache::disable_cache();
			$product->save_atum_data();
			AtumCache::enable_cache();
		}

		do_action( 'atum/api/product_data/update_product_field', $field_value, $response, $field_name, $product );

		return TRUE;

	}

	/**
	 * Exclude the ATUM's known meta keys from the meta_data prop
	 *
	 * @since 1.6.2
	 *
	 * @param array $meta_data
	 * @param mixed $object
	 * @param mixed $data_store
	 *
	 * @return array
	 */
	public function filter_product_meta( $meta_data, $object, $data_store ) {

		if ( $object instanceof \WC_Product ) {

			$internal_meta_keys = apply_filters( 'atum/api/product_data/internal_meta_keys', $this->internal_meta_keys );

			foreach ( $meta_data as $index => $meta ) {
				if ( in_array( $meta->meta_key, $internal_meta_keys, TRUE ) ) {
					unset( $meta_data[ $index ] );
				}
			}

		}

		return $meta_data;

	}

	/**
	 * Prepare the products query for filtering by ATUM fields
	 *
	 * @since 1.6.3
	 *
	 * @param array            $args    Key value array of query var to query value.
	 * @param \WP_REST_Request $request The request used.
	 *
	 * @return array
	 */
	public function prepare_objects_query( $args, $request ) {

		/**
		 * NOTE: We must prefix all the ATUM props to avoid conflicts with 3rd party plugins.
		 */

		// ATUM Locations filter.
		if ( ! empty( $request['atum_location'] ) ) {

			$args['tax_query'][] = array(
				'taxonomy' => Globals::PRODUCT_LOCATION_TAXONOMY,
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', explode( ',', $request['atum_location'] ) ),
			);

		}

		// ATUM controlled filter.
		if ( isset( $request['atum_controlled'] ) ) {

			$this->atum_query_data['where'][] = array(
				'key'   => 'atum_controlled',
				'value' => TRUE === wc_string_to_bool( $request['atum_controlled'] ) ? 1 : 0,
				'type'  => 'NUMERIC',
			);

		}

		// Price filter.
		if ( isset( $request['atum_min_purchase_price'] ) || isset( $request['atum_max_purchase_price'] ) ) {

			$current_min_price = isset( $request['atum_min_purchase_price'] ) ? floatval( $request['atum_min_purchase_price'] ) : 0;
			$current_max_price = isset( $request['atum_max_purchase_price'] ) ? floatval( $request['atum_max_purchase_price'] ) : PHP_INT_MAX;

			$this->atum_query_data['where'][] = array(
				'key'     => 'purchase_price',
				'value'   => array( $current_min_price, $current_max_price ),
				'compare' => 'BETWEEN',
				'type'    => 'DECIMAL(10,' . wc_get_price_decimals() . ')',
			);

		}

		// Supplier filter.
		if ( ! empty( $request['atum_supplier'] ) ) {

			$this->atum_query_data['where'][] = array(
				'key'   => 'supplier_id',
				'value' => absint( $request['atum_supplier'] ),
				'type'  => 'NUMERIC',
			);

		}

		// Supplier SKU filter.
		if ( ! empty( $request['atum_supplier_sku'] ) ) {

			$this->atum_query_data['where'][] = array(
				'key'   => 'supplier_sku',
				'value' => esc_attr( $request['atum_supplier_sku'] ),
			);

		}

		// Barcode filter.
		if ( ! empty( $request['atum_barcode'] ) ) {

			$this->atum_query_data['where'][] = array(
				'key'   => 'barcode',
				'value' => esc_attr( $request['atum_barcode'] ),
			);

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

		$this->atum_query_data = apply_filters( 'atum/api/product_data/atum_query_args', $this->atum_query_data, $request );

		if ( ! empty( $this->atum_query_data ) ) {
			add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
		}

		return apply_filters( 'atum/api/product_data/objects_query_args', $args, $request );

	}

	/**
	 * Customize the WP_Query to handle ATUM product data
	 *
	 * @since 1.6.3
	 *
	 * @param array $pieces
	 *
	 * @return array
	 */
	public function atum_product_data_query_clauses( $pieces ) {
		return Helpers::product_data_query_clauses( $this->atum_query_data, $pieces );
	}

	/**
	 * Alter some WC fields before sending the response
	 *
	 * @since 1.7.5
	 *
	 * @param \WP_REST_Response $response
	 * @param \WC_Product       $object
	 * @param \WP_REST_Request  $request
	 *
	 * @return \WP_REST_Response
	 */
	public function prepare_rest_response( $response, $object, $request ) {

		if ( $object instanceof \WC_Product ) {

			$product_data = $response->get_data();

			// Add an array with option_ids for all the attributes (WC was only returning the attribute names).
			if ( ! empty( $product_data['attributes'] ) ) {

				$product_attributes = $object->get_attributes();

				foreach ( $product_data['attributes'] as $index => $attribute ) {

					foreach ( $product_attributes as $attribute_slug => $attribute_data ) {

						if ( isset( $attribute['id'], $attribute_data['id'] ) && $attribute['id'] === $attribute_data['id'] ) {
							$product_data['attributes'][ $index ]['option_ids'] = $attribute_data['options'];
							break;
						}

					}

				}

			}

			$response->set_data( $product_data );

		}

		return $response;

	}

	/**
	 * Do tasks after REST product save
	 *
	 * @since 1.8.2
	 *
	 * @param \WC_Product      $product   Post data.
	 * @param \WP_REST_Request $request   Request object.
	 * @param bool             $creating  True when creating item, false when updating.
	 */
	public function after_rest_product_save( $product, $request, $creating ) {

		AtumCalculatedProps::update_atum_product_calc_props( $product );

		// Save low_stock_amount (WC's low stock threshold) since WC missed it...
		if ( ! is_null( $request->get_param( 'low_stock_amount' ) ) ) {
			$product->set_low_stock_amount( floatval( $request->get_param( 'low_stock_amount' ) ) );
			$product->save();
		}

	}

	/**
	 * Add extra filtering params to the products endpoint.
	 *
	 * @since 1.8.9
	 *
	 * @param array  $params
	 * @param string $post_type
	 *
	 * @return array
	 */
	public function add_collection_params( $params, $post_type ) {

		$params['modified_before'] = [
			'description' => __( 'Limit response to products modified before a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
			'type'        => 'string',
			'format'      => 'date-time',
		];

		$params['modified_after'] = [
			'description' => __( 'Limit response to products modified after a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
			'type'        => 'string',
			'format'      => 'date-time',
		];

		return $params;

	}

	/**
	 * Make stock_quantity product property to accept decimal numbers.
	 *
	 * @since 1.9.13
	 *
	 * @param array $schema
	 *
	 * @return array
	 */
	public function change_product_stock_quantity_schema( $schema ) {

		if ( 0 < Helpers::get_option( 'stock_quantity_decimals', 0 ) ) {
			$schema['stock_quantity']['type'] = 'mixed';
		}

		return $schema;
	}

	/**
	 * Removes out_stock_date from the date fields to avoid pass through wc functions and be converted in gmt date.
	 *
	 * @since 1.9.14
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function remove_out_stock_date_column( $fields ) {

		if ( in_array( 'out_stock_date', $fields ) ) {
			$index = array_search( 'out_stock_date', $fields );
			array_splice( $fields, $index, 1 );
		}

		return $fields;
	}

	/****************************
	 * Instance methods
	 ****************************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return AtumProductData instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
