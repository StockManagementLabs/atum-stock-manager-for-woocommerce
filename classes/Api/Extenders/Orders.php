<?php
/**
 * Extender for the WC's orders endpoint
 *
 * @since       1.9.22
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 Stock Management Labs™
 *
 * @package     Atum\Api
 * @subpackage  Extenders
 */

namespace Atum\Api\Extenders;

defined( 'ABSPATH' ) || die;

class Orders {

	/**
	 * The singleton instance holder
	 *
	 * @var Orders
	 */
	private static $instance;

	/**
	 * Orders constructor
	 *
	 * @since 1.9.22
	 */
	private function __construct() {

		// Set the order date fields as editable in WC API.
		add_filter( 'woocommerce_rest_shop_order_schema', array( $this, 'alter_shop_order_schema' ) );

	}

	/**
	 * Alter the WC Orders endpoint schema.
	 *
	 * @since 1.9.22
	 *
	 * @param array $schema
	 *
	 * @return array
	 */
	public function alter_shop_order_schema( $schema ) {

		if ( isset( $schema['date_created']['readonly'] ) ) {
			$schema['date_created']['readonly'] = FALSE;
		}

		if ( isset( $schema['date_created_gmt']['readonly'] ) ) {
			$schema['date_created_gmt']['readonly'] = FALSE;
		}

		if ( isset( $schema['date_modified']['readonly'] ) ) {
			$schema['date_modified']['readonly'] = FALSE;
		}

		if ( isset( $schema['date_modified_gmt']['readonly'] ) ) {
			$schema['date_modified_gmt']['readonly'] = FALSE;
		}

		return $schema;

	}
	

	/****************************
	 * Instance methods
	 ****************************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_ST_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_ST_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Orders instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
