<?php
/**
 * Extender for the WC's product categories endpoint
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

use Atum\Components\AtumCapabilities;
use Atum\Modules\ModuleManager;

class ProductCategories {

	/**
	 * The singleton instance holder
	 *
	 * @var ProductCategories
	 */
	private static $instance;

	/**
	 * The default product category
	 *
	 * @var int
	 */
	private $default_product_cat;


	/**
	 * ProductCategories constructor
	 *
	 * @since 1.7.5
	 */
	private function __construct() {

		$this->default_product_cat = absint( get_option( 'default_product_cat', 0 ) );

		add_filter( 'woocommerce_rest_prepare_product_cat', array( $this, 'add_default_category_field' ), 10, 3 );

	}

	/**
	 * Add the default category field to the right category term before sending the response
	 *
	 * @since 1.7.5
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param object            $item     The original term object.
	 * @param \WP_REST_Request  $request  Request used to generate the response.
	 *
	 * @return \WP_REST_Response
	 */
	public function add_default_category_field( $response, $item, $request ) {

		$response_data = $response->get_data();

		if ( $response_data['id'] === $this->default_product_cat ) {
			$response_data['is_default'] = 'yes';
		}

		if ( ModuleManager::is_module_active( 'barcodes' ) && AtumCapabilities::current_user_can( 'view_barcode' ) ) {
			$response_data['barcode'] = get_term_meta( $item->term_id, 'barcode', TRUE );
		}

		$response->set_data( $response_data );

		return $response;

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
	 * @return ProductCategories instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
