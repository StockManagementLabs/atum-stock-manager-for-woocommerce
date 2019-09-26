<?php
/**
 * Extender for the WC's products endpoint
 * Adds the ATUM Product Data to this endpoint
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2019 Stock Management Labs™
 *
 * @package     Atum\Api
 * @subpackage  Extenders
 */

namespace Atum\Api\Extenders;

defined( 'ABSPATH' ) || die;

class AtumProductData {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumProductData
	 */
	private static $instance;

	/**
	 * Custom ATUM API's product field names, indicating support for getting/updating.
	 *
	 * @var array
	 */
	private $product_fields = array(
		'purchase_price'        => [ 'get', 'update' ],
		'supplier_id'           => [ 'get', 'update' ],
		'supplier_sku'          => [ 'get', 'update' ],
		'atum_controlled'       => [ 'get', 'update' ],
		'out_stock_date'        => [ 'get', 'update' ],
		'out_stock_threshold'   => [ 'get', 'update' ],
		'inheritable'           => [ 'get', 'update' ],
		'bom_sellable'          => [ 'get', 'update' ],
		'minimum_threshold'     => [ 'get', 'update' ],
		'available_to_purchase' => [ 'get', 'update' ],
		'selling_priority'      => [ 'get', 'update' ],
		'inbound_stock'         => [ 'get', 'update' ],
		'stock_on_hold'         => [ 'get', 'update' ],
		'sold_today'            => [ 'get', 'update' ],
		'sales_last_days'       => [ 'get', 'update' ],
		'reserved_stock'        => [ 'get', 'update' ],
		'customer_returns'      => [ 'get', 'update' ],
		'warehouse_damage'      => [ 'get', 'update' ],
		'lost_in_post'          => [ 'get', 'update' ],
		'other_logs'            => [ 'get', 'update' ],
		'out_stock_days'        => [ 'get', 'update' ],
		'lost_sales'            => [ 'get', 'update' ],
		'has_location'          => [ 'get', 'update' ],
		'update_date'           => [ 'get', 'update' ],
		'calculated_stock'      => [ 'get', 'update' ],
	);

	/**
	 * AtumProductData constructor
	 *
	 * @since 1.6.2
	 */
	private function __construct() {

		// Register the WC API custom product fields.
		add_action( 'rest_api_init', array( $this, 'register_product_fields' ), 0 );

		// Hooks to add WC v1-v3 API custom order fields.
		$this->add_legacy_hooks();

	}

	/**
	 * Register the WC API custom fields for product requests.
	 *
	 * @since 1.6.2
	 */
	public function register_product_fields() {

		foreach ( $this->product_fields as $field_name => $field_supports ) {

			$args = array(
				'schema' => $this->get_product_field_schema( $field_name ),
			);

			if ( in_array( 'get', $field_supports ) ) {
				$args['get_callback'] = array( $this, 'get_product_field_value' );
			}

			if ( in_array( 'update', $field_supports ) ) {
				$args['update_callback'] = array( $this, 'update_product_field_value' );
			}

			// Add the field to the product endpoint.
			register_rest_field( 'product', $field_name, $args );

		}

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
