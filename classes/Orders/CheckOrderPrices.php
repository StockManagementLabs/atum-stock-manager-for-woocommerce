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

use Atum\Inc\Helpers as AtumHelpers;

class CheckOrderPrices {

	/**
	 * The singleton instance holder
	 *
	 * @var CheckOrderPrices
	 */
	private static $instance;

	/**
	 * The editable order statuses (where the prices should be checked)
	 *
	 * @var array
	 */
	private $editable_order_statuses = [];


	/**
	 * CheckOrderPrices singleton constructor
	 *
	 * @since 1.8.6
	 */
	private function __construct() {

		// Add the option to ATUM settings.
		add_filter( 'atum/settings/defaults', array( $this, 'add_check_prices_settings' ) );

		$this->editable_order_statuses = apply_filters( 'atum/check_order_prices/editable_order_statuses', array(
			'wc-pending',
			'wc-on-hold',
		) );

		if ( is_admin() ) {

			// Only load this feature if the option is enabled in ATUM settings.
			if ( 'yes' === AtumHelpers::get_option( 'enable_check_order_prices', 'no' ) ) {

				// Enqueue scripts.
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

				// Check order pricess via Ajax.
				add_action( 'wp_ajax_atum_check_order_prices', array( $this, 'check_order_prices' ) );

				// Filter the orders query before running it.
				add_action( 'pre_get_posts', array( $this, 'filter_mismatching_orders' ) );

			}

		}

	}

	/**
	 * Add the check prices options to ATUM settings
	 *
	 * @since 1.8.6
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function add_check_prices_settings( $settings ) {

		$settings['enable_check_order_prices'] = array(
			'group'   => 'general',
			'section' => 'list_tables',
			'name'    => __( 'Check order prices button', ATUM_TEXT_DOMAIN ),
			'desc'    => __( "When enabled, it'll display a button in WC orders list to check mismatching prices in editable orders.", ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'no',
		);

		return $settings;

	}

	/**
	 * Enqueue scripts
	 *
	 * @since 1.8.6
	 *
	 * @param string $hook_suffix
	 */
	public function enqueue_scripts( $hook_suffix ) {

		global $post_type;

		if ( 'edit.php' === $hook_suffix && 'shop_order' === $post_type ) {

			// Do not add the button when filtering by any non-editable status.
			if ( ! empty( $_GET['post_status'] ) && ! in_array( $_GET['post_status'], $this->editable_order_statuses ) ) {
				return;
			}

			wp_register_style( 'atum-check-orders', ATUM_URL . 'assets/css/atum-check-orders.css', [], ATUM_VERSION );
			wp_enqueue_style( 'atum-check-orders' );

			wp_register_script( 'atum-check-orders', ATUM_URL . 'assets/js/build/atum-check-orders.js', [ 'jquery' ], ATUM_VERSION, TRUE );

			wp_localize_script( 'atum-check-orders', 'atumCheckOrders', array(
				'checkOrderPrices' => __( 'Check order prices', ATUM_TEXT_DOMAIN ),
				'checkingPrices'   => __( 'Checking prices...', ATUM_TEXT_DOMAIN ),
				'nonce'            => wp_create_nonce( 'atum-check-order-prices-nonce' ),
			) );

			wp_enqueue_script( 'atum-check-orders' );

		}

	}

	/**
	 * Check order prices
	 *
	 * @since 1.8.6
	 */
	public function check_order_prices() {

		check_ajax_referer( 'atum-check-order-prices-nonce', 'token' );

		parse_str( ltrim( $_POST['query_string'], '?' ), $query_args );

		$editable_statuses  = ! empty( $query_args['post_status'] ) ? [ esc_attr( $query_args['post_status'] ) ] : $this->editable_order_statuses;
		$mismatching_orders = $this->get_mismatching_orders( $editable_statuses );

		$mismatching_orders_count = count( $mismatching_orders );

		if ( empty( $mismatching_orders ) ) {
			wp_send_json_success( '<span id="atum-mismatching-orders" class="atum-tooltip background-success" title="' . esc_attr__( 'There are no orders with mismatching prices', ATUM_TEXT_DOMAIN ) . '">' . $mismatching_orders_count . '</span>' );
		}
		else {

			/* translators: the number of mismatching orders */
			$tooltip  = sprintf( _n( 'There is %d order with mismatching prices.', 'There are %d orders with mismatching prices.', $mismatching_orders_count, ATUM_TEXT_DOMAIN ), $mismatching_orders_count );
			$tooltip .= '<br>' . _n( 'Click to show the order', 'Click to show the orders', $mismatching_orders_count, ATUM_TEXT_DOMAIN );

			wp_send_json_success( '<a href="' . $_POST['query_string'] . '&show_mismatching=yes" id="atum-mismatching-orders" class="atum-tooltip" title="' . esc_attr( $tooltip ) . '">' . $mismatching_orders_count . '</a>' );

		}

	}

	/**
	 * Get all the orders with mismatching prices from the db
	 *
	 * @since 1.8.6
	 *
	 * @param string[] $editable_statuses
	 *
	 * @return array
	 */
	private function get_mismatching_orders( $editable_statuses ) {

		global $wpdb;

		// Search for all the editable orders with mismatching prices.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$editable_order_items = $wpdb->get_results( "
			SELECT DISTINCT oimp.meta_value AS product_id, oimv.meta_value AS variation_id, oimq.meta_value AS qty, oimt.meta_value AS subtotal, oi.order_id 
			FROM `{$wpdb->prefix}woocommerce_order_items` oi
			LEFT JOIN `{$wpdb->prefix}woocommerce_order_itemmeta` oimp ON ( oimp.order_item_id = oi.order_item_id AND oimp.meta_key = '_product_id' )
			LEFT JOIN `{$wpdb->prefix}woocommerce_order_itemmeta` oimv ON ( oimv.order_item_id = oi.order_item_id AND oimv.meta_key = '_variation_id' )
		    LEFT JOIN `{$wpdb->prefix}woocommerce_order_itemmeta` oimq ON ( oimq.order_item_id = oi.order_item_id AND oimq.meta_key = '_qty' )
		    LEFT JOIN `{$wpdb->prefix}woocommerce_order_itemmeta` oimt ON ( oimt.order_item_id = oi.order_item_id AND oimt.meta_key = '_line_subtotal' )
			WHERE oi.`order_id` IN (
				SELECT ID FROM `{$wpdb->posts}` WHERE post_type = 'shop_order' AND post_status IN ( '" . implode( "','", $editable_statuses ) . "' )
			)
			AND oimp.meta_value IS NOT NULL;
		" );
		// phpcs:enable

		$mismatching_orders = [];

		if ( ! empty( $editable_order_items ) ) {

			foreach ( $editable_order_items as $editable_order_item ) {

				if ( in_array( $editable_order_item->order_id, $mismatching_orders ) ) {
					continue;
				}

				$old_price = $editable_order_item->subtotal / $editable_order_item->qty;

				$product_id = $editable_order_item->variation_id ?: $editable_order_item->product_id;
				$product    = wc_get_product( $product_id );

				if ( ! $product instanceof \WC_Product ) {
					continue;
				}

				$current_price = $product->get_price();

				if ( (float) $old_price !== (float) $current_price ) {
					$mismatching_orders[] = $editable_order_item->order_id;
				}

			}

		}

		return $mismatching_orders;

	}

	/**
	 * Filters the WC orders query before running it
	 *
	 * @since 1.8.6
	 *
	 * @param \WP_Query $query
	 */
	public function filter_mismatching_orders( $query ) {

		if ( $query->get( 'post_type' ) === 'shop_order' && ! empty( $_GET['show_mismatching'] ) && 'yes' === $_GET['show_mismatching'] ) {

			$queried_statuses          = (array) $query->get( 'post_status' );
			$queried_editable_statuses = array_intersect( $queried_statuses, $this->editable_order_statuses );

			if ( ! empty( $queried_editable_statuses ) ) {
				$mismatching_orders = $this->get_mismatching_orders( $queried_editable_statuses );

				if ( ! empty( $mismatching_orders ) ) {
					$query->set( 'post__in', $mismatching_orders );
				}
				else {
					$query->set( 'post__in', [ '-1' ] ); // Do not return any order.
				}
			}

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
