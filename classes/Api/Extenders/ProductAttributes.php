<?php
/**
 * Extender for the WC's product attributes endpoint
 *
 * @since       1.7.5
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @package     Atum\Api
 * @subpackage  Extenders
 */

namespace Atum\Api\Extenders;

defined( 'ABSPATH' ) || die;

class ProductAttributes {

	/**
	 * The singleton instance holder
	 *
	 * @var ProductAttributes
	 */
	private static $instance;

	/**
	 * Custom ATUM API's field names, indicating support for getting/updating.
	 *
	 * @var array
	 */
	private $custom_fields = array(
		'terms' => [ 'get' ],
	);

	/**
	 * ProductAttributes constructor
	 *
	 * @since 1.7.5
	 */
	private function __construct() {

		/**
		 * Register the ATUM Product data custom fields to the WC API.
		 */
		add_action( 'rest_api_init', array( $this, 'register_fields' ), 0 );

	}

	/**
	 * Register the WC API custom fields for product attribute requests.
	 *
	 * @since 1.7.5
	 */
	public function register_fields() {

		$fields = apply_filters( 'atum/api/product_attributes/fields', $this->custom_fields );

		foreach ( $fields as $field_name => $field_supports ) {

			$args = array(
				'schema' => $this->get_product_attributes_field_schema( $field_name ),
			);

			if ( in_array( 'get', $field_supports ) ) {
				$args['get_callback'] = array( $this, 'get_product_attributes_field_value' );
			}

			// Add the field to the product attributes endpoints.
			register_rest_field( 'product_attribute', $field_name, $args );

		}

	}

	/**
	 * Gets schema properties for ATUM product data fields
	 *
	 * @since 1.7.5
	 *
	 * @param string $field_name
	 *
	 * @return array
	 */
	public function get_product_attributes_field_schema( $field_name ) {

		$extended_schema = $this->get_extended_product_attributes_schema();

		return isset( $extended_schema[ $field_name ] ) ? $extended_schema[ $field_name ] : NULL;

	}

	/**
	 * Gets extended (unprefixed) schema properties for product attributes.
	 *
	 * @since 1.7.5
	 *
	 * @return array
	 */
	private function get_extended_product_attributes_schema() {

		$extended_product_attributes_schema = array(
			'terms' => array(
				'description' => __( 'List of terms linked to the attribute.', ATUM_TEXT_DOMAIN ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'   => array(
							'description' => __( 'Term ID.', ATUM_TEXT_DOMAIN ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'name' => array(
							'description' => __( 'Term name.', ATUM_TEXT_DOMAIN ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => TRUE,
						),
						'slug' => array(
							'description' => __( 'Term slug.', ATUM_TEXT_DOMAIN ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => TRUE,
						),
					),
				),
			),
		);

		return apply_filters( 'atum/api/product_attributes/extended_schema', $extended_product_attributes_schema );

	}

	/**
	 * Gets values for ATUM product attributes fields
	 *
	 * @since 1.7.5
	 *
	 * @param array            $response
	 * @param string           $field_name
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_product_attributes_field_value( $response, $field_name, $request ) {

		$data = NULL;

		// Only show the terms if requested using the 'with_terms' param.
		if ( ! empty( $response['id'] ) && 'yes' === $request['with_terms'] ) {

			$terms = get_terms( [
				'taxonomy'   => $response['slug'],
				'hide_empty' => FALSE,
			] );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {

				$data = array();

				foreach ( $terms as $term ) {
					$data[] = array(
						'id'   => $term->term_id,
						'name' => $term->name,
						'slug' => $term->slug,
					);
				}

			}

		}

		return $data;

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
	 * @return ProductAttributes instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
