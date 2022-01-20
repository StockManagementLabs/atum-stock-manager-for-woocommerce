<?php
/**
 * Extender for the WC's order notes endpoint
 *
 * @since       1.8.8
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @package     Atum\Api
 * @subpackage  Extenders
 */

namespace Atum\Api\Extenders;

defined( 'ABSPATH' ) || die;

class OrderNotes {

	/**
	 * The singleton instance holder
	 *
	 * @var OrderNotes
	 */
	private static $instance;

	/**
	 * AtumOrderNotes constructor
	 *
	 * @since 1.8.8
	 */
	private function __construct() {

		/**
		 * Register the ATUM Product data custom fields to the WC API.
		 */
		add_action( 'rest_api_init', array( $this, 'register_fields' ), 0 );

		// Add extra fields to order notes schema.
		add_filter( 'woocommerce_rest_order_note_schema', array( $this, 'add_extended_order_note_fields' ) );

	}

	/**
	 * Register the WC API custom fields for WC Order Notes & ATUM Order Notes requests.
	 *
	 * @since 1.8.8
	 */
	public function register_fields() {

		$fields = $this->get_extended_order_note_schema();

		foreach ( $fields as $field_name => $field_supports ) {

			$args = array(
				'schema'       => $this->get_order_note_field_schema( $field_name ),
				'get_callback' => array( $this, 'get_order_note_field_value' ),
			);

			// Add the field to the order notes endpoints.
			register_rest_field( 'atum_order_note', $field_name, $args );
			register_rest_field( 'order_note', $field_name, $args );

		}

	}

	/**
	 * Gets schema properties for order note fields
	 *
	 * @since 1.7.5
	 *
	 * @param string $field_name
	 *
	 * @return array
	 */
	public function get_order_note_field_schema( $field_name ) {

		$extended_schema = $this->get_extended_order_note_schema();

		return isset( $extended_schema[ $field_name ] ) ? $extended_schema[ $field_name ] : NULL;

	}

	/**
	 * Gets extended schema properties for order notes.
	 *
	 * @since 1.8.8
	 *
	 * @return array
	 */
	public function get_extended_order_note_schema() {

		$extended_schema = array(
			'action' => array(
				'description' => __( 'Action described in the note.', ATUM_TEXT_DOMAIN ),
				'type'        => 'string',
				'context'     => array( 'view' ),
				'readonly'    => TRUE,
			),
			'params' => array(
				'description' => __( 'List of parameters to parse the action.', ATUM_TEXT_DOMAIN ),
				'type'        => 'array',
				'context'     => array( 'view' ),
				'readonly'    => TRUE,
			),
		);

		return apply_filters( 'atum/api/atum_order_note/extended_schema', $extended_schema );

	}

	/**
	 * Add extra fields to order note schema.
	 *
	 * @since 1.8.8
	 *
	 * @param array $schema
	 *
	 * @return array
	 */
	public function add_extended_order_note_fields( $schema ) {

		global $wp_rest_additional_fields;

		$extra_fields = $this->get_extended_order_note_schema();

		foreach ( $extra_fields as $field_name => $field_schema ) {

			if ( ! isset( $schema[ $field_name ] ) ) {
				$schema[ $field_name ] = $field_schema;
			}

		}

		return $schema;
	}

	/**
	 * Gets values for ATUM order notes fields
	 *
	 * @since 1.8.8
	 *
	 * @param array            $response
	 * @param string           $field_name
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_order_note_field_value( $response, $field_name, $request ) {

		$data = NULL;

		if ( ! empty( $response['id'] ) ) {

			$comment = get_comment( $response['id'] );

			if ( $comment instanceof \WP_Comment ) {

				$schema = $this->get_order_note_field_schema( $field_name );
				$single = TRUE;

				switch ( $field_name ) {

					case 'action':
						$meta_key = 'note_type';
						break;
					case 'params':
						$meta_key = 'note_params';
						break;

				}

				switch ( $schema['type'] ) {
					case 'array':
						$single = FALSE;
						break;
				}

				$data = get_comment_meta( $comment->comment_ID, $meta_key, $single );

				// Allow to handle some fields externally.
				$data = apply_filters( 'atum/api/order_notes/get_field_value', $data, $response, $field_name );

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
	 * @return OrderNotes instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
