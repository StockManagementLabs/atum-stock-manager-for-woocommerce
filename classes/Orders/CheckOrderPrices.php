<?php
/**
 * Check order prices from orders list
 *
 * @package         Atum
 * @subpackage      Orders
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
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

				// Add the action button to the filtered mismatching orders.
				add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_fix_prices_button' ), 10, 2 );

				// Fix the prices for the specified orders.
				add_action( 'wp_ajax_atum_fix_order_prices', array( $this, 'fix_order_prices' ) );

				// Add a bulk action to the orders list table.
				add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_action' ) );

				// Process the bulk action for fixing prices.
				add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_fix_order_prices_bulk_action' ), 10, 3 );

				// Show and admin notice after the prices have been changed.
				add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );

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
				'checkingPrices'   => __( 'Checking prices...', ATUM_TEXT_DOMAIN ),
				'checkOrderPrices' => __( 'Check order prices', ATUM_TEXT_DOMAIN ),
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

		check_ajax_referer( 'atum-check-order-prices-nonce', 'security' );

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

			wp_send_json_success( '<a href="' . $_POST['query_string'] . '&atum_show_mismatching=yes" id="atum-mismatching-orders" class="atum-tooltip" title="' . esc_attr( $tooltip ) . '">' . $mismatching_orders_count . '</a>' );

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

		if ( $query->get( 'post_type' ) === 'shop_order' && ! empty( $_GET['atum_show_mismatching'] ) && 'yes' === $_GET['atum_show_mismatching'] ) {

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

	/**
	 * Fix the prices for any order with mismatching prices
	 *
	 * @since 1.8.7
	 *
	 * @param array     $actions
	 * @param \WC_Order $order
	 *
	 * @return array
	 */
	public function add_fix_prices_button( $actions, $order ) {

		if ( isset( $_GET['atum_show_mismatching'] ) && 'yes' === $_GET['atum_show_mismatching'] && $order->has_status( [ 'pending', 'on-hold' ] ) ) {

			$actions['atum_fix_prices'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=atum_fix_order_prices&order_id=' . $order->get_id() ), 'atum-fix-order-prices-nonce' ),
				'name'   => __( 'Fix prices', ATUM_TEXT_DOMAIN ),
				'action' => 'atum_fix_prices',
			);

		}

		return $actions;

	}

	/**
	 * Fix the prices for the specified orders
	 *
	 * @since 1.8.7
	 *
	 * @param int[] $order_ids Optional. Only passed when processing a bulk action.
	 *
	 * @return int|void
	 */
	public function fix_order_prices( $order_ids = [] ) {

		$changed      = 0;
		$redirect_url = wp_get_referer() ?: admin_url( 'edit.php?post_type=shop_order&atum_show_mismatching=yes' );

		// For ajax requests, check the nonce first.
		if ( wp_doing_ajax() && ! check_admin_referer( 'atum-fix-order-prices-nonce' ) ) {
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// The order IDs to be processed are required.
		if ( ( empty( $order_ids ) && empty( $_GET['order_id'] ) ) || ! current_user_can( 'edit_shop_orders' ) ) {

			if ( wp_doing_ajax() ) {
				wp_safe_redirect( $redirect_url );
				exit;
			}
			else {
				return $changed;
			}

		}

		$order_ids = ! empty( $order_ids ) ? $order_ids : [ absint( wp_unslash( $_GET['order_id'] ) ) ];

		foreach ( $order_ids as $order_id ) {

			$order = wc_get_order( $order_id );

			if ( $order ) {

				$items = $order->get_items();

				foreach ( $items as $item ) {

					/**
					 * Variable definition
					 *
					 * @var \WC_Order_Item_Product $item
					 */
					$product       = $item->get_product();
					$price         = (float) $product->get_price();
					$qty           = $item->get_quantity();
					$item_subtotal = (float) $item->get_subtotal();
					$item_total    = (float) $item->get_total();

					$item_cost = $price * $qty;

					if ( $item_cost !== $item_subtotal ) {

						$item->set_subtotal( $item_cost );

						// Check if there is a discount applied and, if so, apply it proportionally.
						if ( $item_subtotal > $item_total ) {
							$item_discount  = $item_subtotal - $item_total;
							$discount_ratio = $item_discount / $item_subtotal;
							$item->set_total( $item_cost * $discount_ratio );
						}
						else {
							$item->set_total( $item_cost );
						}

						$item->save();
						$item->calculate_taxes();

					}

				}

				$order->calculate_totals();
				$changed++;

			}

		}

		if ( wp_doing_ajax() ) {
			wp_safe_redirect( $redirect_url );
			exit;
		}
		else {
			return $changed;
		}

	}

	/**
	 * Add a bulk action to the WC orders list table
	 *
	 * @since 1.8.7
	 *
	 * @param array $actions
	 *
	 * @return array
	 */
	public function add_bulk_action( $actions ) {

		$actions['atum_fix_prices'] = __( '[ATUM] Fix prices', ATUM_TEXT_DOMAIN );

		return $actions;
	}

	/**
	 * Handle the bulk action to fix order prices
	 *
	 * @since 1.8.7
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $action      Action name.
	 * @param  array  $ids         List of IDs.
	 *
	 * @return string
	 */
	public function handle_fix_order_prices_bulk_action( $redirect_to, $action, $ids ) {

		if ( 'atum_fix_prices' === $action ) {

			$ids     = array_reverse( array_map( 'absint', $ids ) );
			$changed = $this->fix_order_prices( $ids );

			if ( $changed ) {

				$redirect_to = add_query_arg(
					array(
						'post_type'   => 'shop_order',
						'bulk_action' => 'prices_fixed',
						'changed'     => $changed,
						'ids'         => join( ',', $ids ),
					),
					$redirect_to
				);

			}

		}

		return $redirect_to;

	}

	/**
	 * Show an admin notice when the prices have been fixed through a bulk action
	 *
	 * @since 1.8.7
	 */
	public function bulk_admin_notices() {

		global $post_type, $pagenow;

		// Bail out if not on shop order list page.
		if ( 'edit.php' !== $pagenow || 'shop_order' !== $post_type || ! isset( $_REQUEST['bulk_action'] ) ) {
			return;
		}

		$number      = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
		$bulk_action = wc_clean( wp_unslash( $_REQUEST['bulk_action'] ) );

		if ( 'prices_fixed' === $bulk_action ) {
			$message = _n( 'The prices for the selected order were fixed successfully.', 'The prices for the selected orders were fixed successfully.', $number, ATUM_TEXT_DOMAIN );
			echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
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
