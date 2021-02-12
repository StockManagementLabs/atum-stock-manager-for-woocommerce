<?php
/**
 * Check order prices from orders list
 *
 * @package         Atum
 * @subpackage      Orders
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.8.6
 */

namespace Atum\Orders;

defined( 'ABSPATH' ) || die;

class CheckOrderPrices {

	/**
	 * The singleton instance holder
	 *
	 * @var CheckOrderPrices
	 */
	private static $instance;

	/**
	 * CheckOrderPrices singleton constructor
	 *
	 * @since 1.8.6
	 */
	private function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Enqueue scripts
	 *
	 * @param string $hook_suffix
	 */
	public function enqueue_scripts( $hook_suffix ) {

		global $post_type;

		if ( 'edit.php' === $hook_suffix && 'shop_order' === $post_type ) {

			wp_register_style( 'atum-check-orders', ATUM_URL . 'assets/css/atum-check-orders.css', [], ATUM_VERSION );
			wp_enqueue_style( 'atum-check-orders' );

			wp_register_script( 'atum-check-orders', ATUM_URL . 'assets/js/build/atum-check-orders.js', [ 'jquery' ], ATUM_VERSION, TRUE );

			wp_localize_script( 'atum-check-orders', 'atumCheckOrders', array(
				'checkOrderPrices' => __( 'Check order prices', ATUM_TEXT_DOMAIN ),
				'checkingPrices'   => __( 'Checking prices...', ATUM_TEXT_DOMAIN ),
			) );

			wp_enqueue_script( 'atum-check-orders' );

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
	 * @return CheckOrderPrices instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
