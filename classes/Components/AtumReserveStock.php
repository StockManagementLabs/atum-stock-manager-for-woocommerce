<?php
/**
 * Abstract class responsible for handling the reserved stock in ATUM.
 *
 * @since       1.9.37
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 Stock Management Labs™
 *
 * @package     Atum\Components
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

use Automattic\WooCommerce\Checkout\Helpers\ReserveStockException;
use Automattic\WooCommerce\Utilities\OrderUtil;


abstract class AtumReserveStock {

	/**
	 * WooCommerce out of stock threshold
	 *
	 * @var int
	 */
	protected $wc_threshold;

	/**
	 * Register all the hooks related with the WC reserved stock.
	 *
	 * @since 1.9.37
	 */
	protected function register_hooks() {

		// Replace the WC actions with our own copies to handle MIs.
		remove_action( 'woocommerce_checkout_order_created', 'wc_reserve_stock_for_order' );
		add_action( 'woocommerce_checkout_order_created', array( $this, 'maybe_reserve_stock_for_order' ) );

		// Remove reserved inventories when needed.
		add_action( 'woocommerce_checkout_order_exception', array( $this, 'release_atum_stock_for_order' ), 11 );
		add_action( 'woocommerce_payment_complete', array( $this, 'release_atum_stock_for_order' ), 12 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'release_atum_stock_for_order' ), 12 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'release_atum_stock_for_order' ), 12 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'release_atum_stock_for_order' ), 12 );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'release_atum_stock_for_order' ), 12 );

		// Hack the Store API response to handle the ATUM reserved stock.
		add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'maybe_reserve_stock_for_store_api' ), PHP_INT_MAX );

	}

	/**
	 * Hold stock for an order.
	 *
	 * @since 1.9.37
	 *
	 * @param \WC_Order|int $order Order ID or instance.
	 *
	 * @throws ReserveStockException If reserve stock fails.
	 */
	public function maybe_reserve_stock_for_order( $order ) {

		/**
		 * Filter: woocommerce_hold_stock_for_checkout
		 * Allows enabling/disable hold stock functionality on checkout.
		 *
		 * NOTE: Preserving the WC filter name here to maintain compatibility.
		 *
		 * @param bool $enabled Default to true if managing stock globally.
		 */
		if ( ! apply_filters( 'woocommerce_hold_stock_for_checkout', wc_string_to_bool( get_option( 'woocommerce_manage_stock', 'yes' ) ) ) ) {
			return;
		}

		$order = $order instanceof \WC_Order ? $order : wc_get_order( $order );

		if ( $order ) {
			$this->reserve_stock_for_order( $order );
		}

	}

	/**
	 * Put a temporary hold on stock for an order if enough is available.
	 *
	 * @since 1.9.37
	 *
	 * @param \WC_Order|object $order Order object.
	 * @param int              $minutes How long to reserve stock in minutes. Defaults to woocommerce_hold_stock_minutes.
	 *
	 * @throws ReserveStockException If stock cannot be reserved.
	 */
	public function reserve_stock_for_order( $order, $minutes = 0 ) {

		$minutes = $minutes ?: (int) get_option( 'woocommerce_hold_stock_minutes', 60 );

		if ( ! $minutes ) {
			return;
		}

		try {

			$this->wc_threshold = wc_stock_amount( get_option( 'woocommerce_notify_no_stock_amount' ) );

			$items = array_filter(
				$order->get_items(),
				function ( $item ) {

					/**
					 * Variable declaration.
					 *
					 * @var \WC_Order_Item_Product $item
					 */
					return $item->is_type( 'line_item' ) && $item->get_product() instanceof \WC_Product && $item->get_quantity() > 0;
				}
			);

			$product_rows = array();

			foreach ( $items as $item ) {

				/**
				 * Variable declaration.
				 *
				 * @var \WC_Product            $product
				 * @var \WC_Order_Item_Product $item
				 */
				$product = $item->get_product();

				if ( ! $product instanceof \WC_Product || ! $product->is_in_stock() ) {

					throw new ReserveStockException(
						'woocommerce_product_out_of_stock',
						sprintf(
						/* translators: %s: product name */
							__( '&quot;%s&quot; is out of stock and cannot be purchased.', ATUM_TEXT_DOMAIN ),
							$product->get_name()
						),
						403
					);

				}

				// If stock management is off, no need to reserve any stock here.
				if ( ! $product->managing_stock() || $product->backorders_allowed() ) {
					continue;
				}

				$managed_by_id = $product->get_stock_managed_by_id();

				/**
				 * Filter order item quantity.
				 *
				 * @param int|float             $quantity Quantity.
				 * @param \WC_Order              $order    Order data.
				 * @param \WC_Order_Item_Product $item Order item data.
				 */
				$item_quantity = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item ); // Using the WC filter here.

				$product_rows[ $managed_by_id ] = isset( $product_rows[ $managed_by_id ] ) ? $product_rows[ $managed_by_id ] + $item_quantity : $item_quantity;

			}

			if ( ! empty( $product_rows ) ) {
				foreach ( $product_rows as $product_id => $quantity ) {
					$this->reserve_stock_for_product( $product_id, $quantity, $order, $minutes );
				}
			}

		} catch ( ReserveStockException $e ) {

			$this->release_stock_for_order( $order );
			throw $e;

		}

	}

	/**
	 * Reserve stock for a product by inserting rows into the DB.
	 *
	 * @since 1.9.37
	 *
	 * @param int              $product_id     Product ID which is having stock reserved.
	 * @param int|float        $stock_quantity Stock amount to reserve.
	 * @param \WC_Order|object $order          Order object which contains the product.
	 * @param int              $minutes        How long to reserve stock in minutes.
	 *
	 * @throws ReserveStockException If a not enough inventories stock.
	 */
	abstract public function reserve_stock_for_product( $product_id, $stock_quantity, $order, $minutes );

	/**
	 * Returns query statement for getting reserved stock of a product.
	 * Based on \Automattic\WooCommerce\Checkout\Helpers\ReserveStock::get_query_for_reserved_stock().
	 *
	 * @since 1.9.37
	 *
	 * @param int     $product_id       Product ID.
	 * @param integer $exclude_order_id Optional order to exclude from the results.
	 *
	 * @return string Query statement.
	 */
	protected function get_query_for_reserved_stock( $product_id, $exclude_order_id = 0 ) {

		global $wpdb;

		$join         = "$wpdb->posts posts ON stock_table.`order_id` = posts.ID";
		$where_status = "posts.post_status IN ( 'wc-checkout-draft', 'wc-pending' )";
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$join         = "{$wpdb->prefix}wc_orders orders ON stock_table.`order_id` = orders.id";
			$where_status = "orders.status IN ( 'wc-checkout-draft', 'wc-pending' )";
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $wpdb->prepare(
			"
			SELECT COALESCE( SUM( stock_table.`stock_quantity` ), 0 ) FROM $wpdb->wc_reserved_stock stock_table
			LEFT JOIN $join
			WHERE $where_status
			AND stock_table.`expires` > NOW()
			AND stock_table.`product_id` = %d
			AND stock_table.`order_id` != %d
			",
			$product_id,
			$exclude_order_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		/**
		 * Filter: woocommerce_query_for_reserved_stock
		 * Allows to filter the query for getting reserved stock of a product.
		 *
		 * @param string $query            The query for getting reserved stock of a product.
		 * @param int    $product_id       Product ID.
		 * @param int    $exclude_order_id Order to exclude from the results.
		 */
		return apply_filters( 'woocommerce_query_for_reserved_stock', $query, $product_id, $exclude_order_id ); // Using the WC filter here.

	}

	/**
	 * Release a temporary hold on stock for an order.
	 *
	 * @since 1.9.37
	 *
	 * @param \WC_Order|object|int $order Order object.
	 */
	public function release_stock_for_order( $order ) {

		global $wpdb;

		$order_id = $order instanceof \WC_Order ? $order->get_id() : (int) $order;

		$wpdb->delete(
			$wpdb->wc_reserved_stock,
			array(
				'order_id' => $order_id,
			)
		);

		$this->release_atum_stock_for_order( $order );

	}

	/**
	 * Release a temporary hold on special ATUM stocks for an order.
	 *
	 * @since 1.9.37
	 *
	 * @param \WC_Order|object|int $order Order object.
	 */
	abstract public function release_atum_stock_for_order( $order );

	/**
	 * Reserve the ATUM stock during requests to the checkout endpoint on the Store API.
	 *
	 * @since 1.9.37
	 *
	 * @param \WC_Order $order
	 */
	public function maybe_reserve_stock_for_store_api( $order ) {

		/**
		 * Try to reserve stock for the order.
		 */
		try {

			$this->reserve_stock_for_order( $order );

			// As we are not receiving the request here, we've to try to generate it ourselves.
			$request = new \WP_REST_Request( 'GET', '/wc/store/v1/checkout' );


		} catch ( ReserveStockException $e ) {

			throw new ReserveStockException(
				$e->getErrorCode(),
				$e->getMessage(),
				$e->getCode()
			);

		}

	}

}
