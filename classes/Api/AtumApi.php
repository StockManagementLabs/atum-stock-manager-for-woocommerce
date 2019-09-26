<?php
/**
 * The ATUM's API class
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2019 Stock Management Labs™
 *
 * @package     Atum/Api/Legacy
 * @subpackage  V3
 */

namespace Atum\Api;

defined( 'ABSPATH' ) || die;

use Atum\Api\Extenders\AtumProductData;

class AtumApi {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumApi
	 */
	private static $instance;

	/**
	 * The legacy API endpoints classes
	 *
	 * @var array
	 */
	private $api_legacy_classes = array(
		__NAMESPACE__ . '\Legacy\V3\AtumProductData',
	);

	/**
	 * The ATUM API controllers
	 *
	 * @var array
	 */
	private $api_controllers = array(
		'suppliers' => __NAMESPACE__ . '\Controllers\V3\SuppliersController',
	);

	/**
	 * AtumApi constructor
	 *
	 * @since 1.6.2
	 */
	private function __construct() {

		/**
		 * Register the API classes (legacy API support: /wc-api/v3)
		 *
		 * @deprecated WC 2.6.0
		 */
		add_filter( 'woocommerce_api_classes', array( $this, 'register_legacy_api_classes' ) );

		/**
		 * Add the ATUM controllers to the WooCommerce API (/wp-json/wc/v3)
		 */
		add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this, 'register_api_controllers' ) );

		$this->load_extenders();

	}

	/**
	 * Register the legacy API classes
	 *
	 * @since 1.6.2
	 *
	 * @param array $api_classes
	 *
	 * @return     array
	 * @deprecated WC 2.6.0
	 */
	public function register_legacy_api_classes( $api_classes ) {

		return array_merge( $api_classes, apply_filters( 'atum/legacy_api/registered_classes', $this->api_legacy_classes ) );

	}

	/**
	 * Register the ATUM API controllers
	 *
	 * @since 1.6.2
	 *
	 * @param array $api_controllers
	 *
	 * @return array
	 */
	public function register_api_controllers( $api_controllers ) {

		if ( ! empty( $api_controllers['wc/v3'] ) ) {
			$api_controllers['wc/v3'] = array_merge( $api_controllers['wc/v3'], apply_filters( 'atum/api/registered_controllers', $this->api_controllers ) );
		}

		return $api_controllers;

	}

	/**
	 * Load the ATUM API extenders (all those that are extending an existing WC endpoint)
	 *
	 * @since 1.6.2
	 */
	public function load_extenders() {

		AtumProductData::get_instance();

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
	 * @return AtumApi instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
