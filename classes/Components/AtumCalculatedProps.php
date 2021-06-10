<?php
/**
 * Class reponsible of obtaining and saving the ATUM calculated props
 *
 * @package    Components
 * @author     Be Rebel - https://berebel.io
 * @copyright  ©2021 Stock Management Labs™
 *
 * @since      1.9.0
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Inc\Hooks;
use Atum\InventoryLogs\Models\Log;
use Atum\Models\Products\AtumProductTrait;

class AtumCalculatedProps {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumCalculatedProps
	 */
	private static $instance;

	/**
	 * Store the product IDs that need to have their calculated properties updated.
	 *
	 * @var array
	 */
	private static $deferred_product_calc_props = [];

	/**
	 * Store the product IDs that need to have their sales calculated properties updated.
	 *
	 * @var array
	 */
	private static $deferred_sales_calc_props = [];

	/**
	 * AtumCalculatedProps singleton constructor
	 *
	 * @since 1.9.0
	 */
	private function __construct() {

		// We must run this before the AtumQueues' "trigger_async_action" because we are using the same hook to register the async actions (when needed).
		add_action( 'shutdown', array( $this, 'maybe_create_defer_update_async_action' ), 1 );

		// Update atum_stock_status and low_stock if needed.
		add_action( 'woocommerce_after_product_object_save', array( $this, 'after_product_save' ), PHP_INT_MAX, 2 );

		// Update the sales-related calculated props when saving an order or changing its status.
		add_action( 'woocommerce_after_order_object_save', array( $this, 'after_order_save' ), PHP_INT_MAX, 2 );
		add_action( 'atum/order/after_object_save', array( $this, 'after_order_save' ), PHP_INT_MAX, 2 );

	}

	/**
	 * Add the asynchronous action for updating calculated product properties if any product has changed.
	 *
	 * @since 1.7.8
	 */
	public function maybe_create_defer_update_async_action() {

		// Security check to avoid unending loops when the current request is already coming from an async action.
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) && AtumQueues::get_async_request_user_agent() === $_SERVER['HTTP_USER_AGENT'] ) {
			return;
		}

		// As updating the sales props also updates de product calc props, the products already queued to get the sales updated,
		// will be removed from the second hook if present.
		$already_queued = [];

		// Add the async action for the sales prop first.
		if ( ! empty( self::$deferred_sales_calc_props ) ) {

			$already_queued = array_map( function( $id_str ) {

				list( $product_id, $order_type_id ) = explode( ':', $id_str );
				return absint( $product_id );

			}, self::$deferred_sales_calc_props );

			AtumQueues::add_async_action( 'update_atum_sales_calc_props_deferred_hook', array( get_class(), 'update_atum_sales_calc_props_deferred_hook' ), [ self::$deferred_sales_calc_props ] );
			self::$deferred_sales_calc_props = [];

		}

		// Add the async action for the product calc props.
		if ( ! empty( self::$deferred_product_calc_props ) ) {

			// Only those IDs that weren't added by the sales action.
			$product_ids = array_diff( self::$deferred_product_calc_props, $already_queued );

			if ( ! empty( $product_ids ) ) {
				AtumQueues::add_async_action( 'update_atum_product_calc_props_deferred_hook', array( get_class(), 'update_atum_product_calc_props_deferred_hook' ), [ $product_ids ] );
			}

			self::$deferred_product_calc_props = [];

		}

	}

	/**
	 * Update the ATUM product calculated props after saving a product object.
	 *
	 * @since 1.6.6
	 *
	 * @param \WC_Product                $product
	 * @param \WC_Product_Data_Store_CPT $data_store
	 */
	public function after_product_save( $product, $data_store ) {

		if ( $product instanceof \WC_Product ) {
			$product = $product->get_id();
		}

		if ( $product && is_numeric( $product ) ) {
			self::defer_update_atum_product_calc_props( $product );
		}

	}

	/**
	 * Update the ATUM sales calculated props after saving an order object.
	 *
	 * @since 1.7.1
	 *
	 * @param \WC_Order|AtumOrderModel $order
	 * @param \WC_Order_Data_Store_CPT $data_store
	 */
	public function after_order_save( $order, $data_store = NULL ) {

		$items = $order->get_items();

		foreach ( $items as $item ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			$product_id = (int) $item->get_variation_id() ?: $item->get_product_id();

			if ( $product_id ) {
				$order_type = $order instanceof AtumOrderModel ? $order->get_post_type() : $order->get_type();
				self::defer_update_atum_sales_calc_props( $product_id, Globals::get_order_type_table_id( $order_type ) );
			}

		}

	}

	/**
	 * Run the async hook to update the calculated sales props
	 *
	 * @since 1.8.1
	 *
	 * @param string|string[] $product_ids
	 */
	public static function update_atum_sales_calc_props_deferred_hook( $product_ids ) {

		$product_ids = is_array( $product_ids ) ? $product_ids : [ $product_ids ];

		foreach ( $product_ids as $ids_str ) {

			// Extract the product ID and the order table ID from the string.
			list( $product_id, $order_type_id ) = explode( ':', $ids_str );

			$product = Helpers::get_atum_product( $product_id );
			self::update_atum_sales_calc_props( $product, $order_type_id );

		}
	}

	/**
	 * Run the async hook to update the calculated product props
	 *
	 * @since 1.9.0
	 *
	 * @param int|int[] $product_ids
	 */
	public static function update_atum_product_calc_props_deferred_hook( $product_ids ) {

		$product_ids = is_array( $product_ids ) ? $product_ids : [ $product_ids ];

		foreach ( $product_ids as $product_id ) {
			$product = Helpers::get_atum_product( $product_id );
			self::update_atum_product_calc_props( $product, TRUE );
		}

	}

	/**
	 * Update ATUM calculated props related to the sales.
	 *
	 * @since 1.9.0
	 *
	 * @param int $product_id
	 * @param int $order_type_id
	 */
	public static function defer_update_atum_sales_calc_props( $product_id, $order_type_id = 1 ) {

		$ids_str = "$product_id:$order_type_id";

		if ( ! in_array( $ids_str, self::$deferred_sales_calc_props ) ) {
			self::$deferred_sales_calc_props[] = $ids_str;
		}

	}

	/**
	 * Update ATUM calculated props related to the product.
	 *
	 * @since 1.9.0
	 *
	 * @param int $product_id
	 */
	public static function defer_update_atum_product_calc_props( $product_id ) {

		if ( ! in_array( $product_id, self::$deferred_product_calc_props ) ) {
			self::$deferred_product_calc_props[] = $product_id;
		}

	}

	/**
	 * Update the expiring data (calculated props) for the specified product when updating an order item.
	 * NOTE: we set up this method as private to avoid using it directly (not deferred).
	 *
	 * @since 1.5.8
	 *
	 * @param \WC_Product|AtumProductTrait $product
	 * @param int                          $order_type_id
	 */
	private static function update_atum_sales_calc_props( $product, $order_type_id = 1 ) {

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		switch ( $order_type_id ) {
			// Purchase Orders.
			case 2:
				Helpers::get_product_inbound_stock( $product, TRUE ); // This already sets the prop to the column, so we just need to save it later.
				break;

			// Inventory Logs.
			case 3:
				foreach ( Log::get_log_type_columns() as $log_type => $log_type_column ) {
					Helpers::get_log_item_qty( $log_type, $product, 'pending', TRUE ); // This already sets the prop to the column, so we just need to save it later.
				}

				break;

			// WC Orders.
			default:
				$timestamp    = Helpers::get_current_timestamp();
				$current_time = Helpers::date_format( $timestamp, TRUE, TRUE );
				$sale_days    = Helpers::get_sold_last_days_option();
				$product_id   = $product->get_id();

				// Set stock "On Hold".
				Helpers::get_product_stock_on_hold( $product, TRUE ); // This already sets the value to the product.

				// Set sold today.
				$sold_today = Helpers::get_sold_last_days( 'today midnight', $current_time, $product_id );
				$product->set_sold_today( $sold_today );
				self::maybe_update_variable_calc_prop( $product, 'sold_today', $sold_today );

				// Sales last days.
				$sales_last_ndays = Helpers::get_sold_last_days( "$current_time -$sale_days days", $current_time, $product_id );
				$product->set_sales_last_days( $sales_last_ndays );
				self::maybe_update_variable_calc_prop( $product, 'sales_last_days', $sales_last_ndays );

				// Out stock days.
				$out_of_stock_days = Helpers::get_product_out_stock_days( $product );
				$product->set_out_stock_days( $out_of_stock_days );

				// Lost sales.
				$lost_sales = Helpers::get_product_lost_sales( $product );
				$product->set_lost_sales( $lost_sales );
				self::maybe_update_variable_calc_prop( $product, 'lost_sales', $lost_sales );

				break;
		}

		// As we are forcing the save, this method should save the product.
		self::update_atum_product_calc_props( $product, TRUE );

	}

	/**
	 * Update ATUM product data calculated fields that not depend exclusively on the sale.
	 *
	 * @since 1.7.1
	 *
	 * @param \WC_Product|int|int[] $product
	 * @param bool                  $force_save
	 */
	public static function update_atum_product_calc_props( $product, $force_save = FALSE ) {

		// Avoid infinite recursion.
		remove_action( 'woocommerce_after_product_object_save', array( self::get_instance(), 'after_product_save' ), PHP_INT_MAX );

		$products = is_array( $product ) ? $product : [ $product ];

		foreach ( $products as $product ) {

			if ( ! Helpers::is_atum_product( $product ) ) {
				// NOTE: We should be careful when using "get_atum_product" on a product with changes but not saved yet because the changes could be lost.
				$product = apply_filters( 'atum/before_update_product_calc_props', Helpers::get_atum_product( $product ) );
			}

			if ( $product instanceof \WC_Product ) {

				if ( 'yes' === Helpers::get_option( 'out_stock_threshold', 'no' ) ) {

					$out_of_stock_threshold = $product->get_out_stock_threshold();

					// Allow to be hooked externally.
					$out_of_stock_threshold = apply_filters( 'atum/out_of_stock_threshold_for_product', $out_of_stock_threshold, $product->get_id() );

					if ( FALSE !== $out_of_stock_threshold && '' !== $out_of_stock_threshold ) {

						// TODO: Refactory to move the Hooks functions to Helpers.
						Hooks::get_instance()->current_out_stock_threshold = (int) $out_of_stock_threshold;
						Hooks::get_instance()->add_stock_status_threshold();
						$product->save();
						Hooks::get_instance()->remove_stock_status_threshold();

					}

				}

				$update = FALSE;
				$bypass = '__return_false';

				if ( in_array( $product->get_type(), array_diff( Globals::get_inheritable_product_types(), [ 'grouped', 'bundle' ] ), TRUE ) ) {

					// Multi-Inventory compatibility.
					add_filter( 'atum/multi_inventory/bypass_mi_get_manage_stock', '__return_true' );

					if ( $product->managing_stock() ) {
						$bypass = '__return_true';
					}

					// Multi-Inventory compatibility.
					remove_filter( 'atum/multi_inventory/bypass_mi_get_manage_stock', '__return_true' );

				}

				// Multi-Inventory compatibility.
				add_filter( 'atum/multi_inventory/bypass_mi_get_stock_status', $bypass );
				$stock_status = $product->get_stock_status();
				// Multi-Inventory compatibility.
				remove_filter( 'atum/multi_inventory/bypass_mi_get_stock_status', $bypass );

				if ( $product->get_atum_stock_status() !== $stock_status ) {
					$product->set_atum_stock_status( $stock_status );
					$update = TRUE;
				}

				$low = wc_bool_to_string( Helpers::is_product_low_stock( $product ) );

				if ( $product->get_low_stock() !== $low ) {
					$product->set_low_stock( $low );
					$update = TRUE;
				}

				if ( $update || $force_save ) {
					$product->set_update_date( gmdate( 'Y-m-d H:i:s' ) );
					$product->save_atum_data();

					do_action( 'atum/after_update_product_calc_props', $product );
				}

			}
		}

		// Restore the hook.
		add_action( 'woocommerce_after_product_object_save', array( self::get_instance(), 'after_product_save' ), PHP_INT_MAX, 2 );

	}

	/**
	 * For some ATUM calc props, we need to store the sum of all the variations' calc props in the variable
	 *
	 * @since 1.7.2
	 *
	 * @param \WC_Product $product The variation product.
	 * @param string      $prop    The calculated prop name to update.
	 * @param mixed       $value   The current value for the specified prop on the specified product.
	 */
	public static function maybe_update_variable_calc_prop( $product, $prop, $value ) {

		// If it's a variation, sum up all the variations' inbound stocks and save the result as the variable inbound (so it can be used in SC sortings).
		if ( $product instanceof \WC_Product && $product->is_type( 'variation' ) && is_callable( array( $product, "get_$prop" ) ) ) {

			$product_id       = $product->get_id();
			$variable_product = Helpers::get_atum_product( $product->get_parent_id() );

			if ( ! $variable_product instanceof \WC_Product || ! is_callable( array( $variable_product, 'get_children' ) ) ) {
				return;
			}

			$children       = $variable_product->get_children();
			$variable_value = $value;

			foreach ( $children as $child_id ) {

				if ( $product_id === $child_id ) {
					continue;
				}

				$variation_product = Helpers::get_atum_product( $child_id );
				$variable_value   += call_user_func( array( $variation_product, "get_$prop" ) );

			}

			if ( is_callable( array( $variable_product, "set_$prop" ) ) ) {
				call_user_func( array( $variable_product, "set_$prop" ), $variable_value );
				$variable_product->save_atum_data();
			}

		}

	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 *
	 * @since 1.9.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 *
	 * @since 1.9.0
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @since 1.9.0
	 *
	 * @return AtumCalculatedProps instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
