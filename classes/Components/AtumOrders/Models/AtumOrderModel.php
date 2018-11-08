<?php
/**
 * The abstract class for the ATUM Order model
 *
 * @package         Atum\Components\AtumOrders
 * @subpackage      AtumOrders
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\Components\AtumOrders\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumException;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Items\AtumOrderItemFee;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Components\AtumOrders\Items\AtumOrderItemShipping;
use Atum\Components\AtumOrders\Items\AtumOrderItemTax;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;


abstract class AtumOrderModel {

	/**
	 * The object ID
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * The WP post linked to this object
	 *
	 * @var \WP_Post
	 */
	protected $post;

	/**
	 * The array of items belonging to this Order
	 *
	 * @var array
	 */
	protected $items = [];

	/**
	 * Order items that need deleting are stored here
	 *
	 * @var array
	 */
	protected $items_to_delete = array();

	/**
	 * The available ATUM Order item types
	 *
	 * @var array
	 */
	protected $item_types = [ 'line_item', 'tax', 'shipping', 'fee' ];

	/**
	 * The WP cache key name
	 *
	 * @var string
	 */
	protected $cache_key = 'atum-order-items';
	
	/**
	 * Whether the item's quantity will affect positively or negatively (or both) the stock
	 *
	 * @var string
	 */
	protected $action = 'both';
	
	/**
	 * AtumOrderModel constructor
	 *
	 * @param int  $id         Optional. The ATUM Order ID to initialize.
	 * @param bool $read_items Optional. Whether to read the inner items.
	 */
	protected function __construct( $id = 0, $read_items = TRUE ) {

		if ( $id ) {

			$this->id = absint( $id );
			$this->load_post();

			if ( $read_items ) {
				$this->read_items();
			}

		}

	}

	/**
	 * Getter for the id property
	 *
	 * @since 1.2.4
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Load the WP post to the post property
	 *
	 * @since 1.2.4
	 */
	protected function load_post() {
		$this->post = get_post( $this->id );
	}

	/**
	 * Read items of a specific type from the database for this ATUM Order
	 *
	 * @since 1.2.4
	 *
	 * @param  string $type Optional. Filter by item type.
	 *
	 * @return void
	 */
	public function read_items( $type = '' ) {

		// Get from cache if available.
		$cache_key   = "{$this->cache_key}-{$this->id}";
		$cache_group = $this->post->post_type;
		$items       = wp_cache_get( $cache_key, $cache_group );

		if ( FALSE === $items ) {

			$items = Helpers::get_order_items( $this->id );

			if ( ! $items ) {
				return;
			}

			wp_cache_set( $cache_key, $items, $cache_group, 20 );

		}

		if ( $type ) {
			$items = wp_list_filter( $items, array( 'order_item_type' => $type ) );
		}

		$atum_order_items = array_map( array( $this, 'get_atum_order_item' ), array_combine( wp_list_pluck( $items, 'order_item_id' ), $items ) );

		if ( ! empty( $atum_order_items ) ) {

			foreach ( $atum_order_items as $atum_order_item ) {
				$this->add_item( $atum_order_item );
			}

		}

	}

	/**
	 * Remove all line items (products, coupons, shipping, taxes) from the ATUM Order
	 *
	 * @since 1.2.4
	 *
	 * @param string $type  ATUM Order item type. Default null.
	 */
	public function delete_items( $type = NULL ) {

		global $wpdb;

		if ( ! empty( $type ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEM_META_TABLE . " itemmeta INNER JOIN {$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . ' items WHERE itemmeta.order_item_id = items.order_item_id AND items.order_id = %d AND items.order_item_type = %s', $this->id, $type ) ); // WPCS: unprepared SQL ok.
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . ' WHERE order_id = %d AND order_item_type = %s', $this->id, $type ) ); // WPCS: unprepared SQL ok.
		}
		else {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEM_META_TABLE . " itemmeta INNER JOIN {$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . ' items WHERE itemmeta.order_item_id = items.order_item_id AND items.order_id = %d', $this->id ) ); // WPCS: unprepared SQL ok.
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . ' WHERE order_id = %d', $this->id ) ); // WPCS: unprepared SQL ok.
		}

		$this->clear_caches();

	}

	/**
	 * Convert a type to a types group
	 *
	 * @since 1.2.4
	 *
	 * @param string $type
	 *
	 * @return string group
	 */
	protected function type_to_group( $type ) {

		$type_to_group = (array) apply_filters( 'atum/order/item_type_to_group', array(
			'line_item' => 'line_items',
			'tax'       => 'tax_lines',
			'shipping'  => 'shipping_lines',
			'fee'       => 'fee_lines',
		) );

		return isset( $type_to_group[ $type ] ) ? $type_to_group[ $type ] : '';

	}

	/**
	 * Convert a type of group to a type
	 *
	 * @since 1.2.4
	 *
	 * @param string $group
	 *
	 * @return string type
	 */
	protected function group_to_type( $group ) {

		$group_to_type = (array) apply_filters( 'atum/order/item_group_to_type', array(
			'line_items'     => 'line_item',
			'tax_lines'      => 'tax',
			'shipping_lines' => 'shipping',
			'fee_lines'      => 'fee',
		) );

		return isset( $group_to_type[ $group ] ) ? $group_to_type[ $group ] : '';

	}

	/**
	 * Adds an item to this Order. The Order Item will not persist until save
	 *
	 * @since 1.2.4
	 *
	 * @param AtumOrderItemFee|AtumOrderItemProduct|AtumOrderItemShipping|AtumOrderItemTax $item  Order item object (product, shipping, fee, coupon, tax).
	 *
	 * @return void
	 */
	public function add_item( $item ) {

		if ( ! $item || ! $items_key = $this->type_to_group( $item->get_type() ) ) {
			return;
		}

		// Make sure existing items are loaded so we can append this new one.
		if ( ! isset( $this->items[ $items_key ] ) ) {
			$this->items[ $items_key ] = $this->get_items( $item->get_type() );
		}

		// Set parent ATUM Order ID.
		$item->set_atum_order_id( $this->id );

		// Append new item with generated temporary ID.
		if ( $item_id = $item->get_id() ) {
			$this->items[ $items_key ][ $item_id ] = $item;
		}
		else {
			$this->items[ $items_key ][ 'new:' . $items_key . count( $this->items[ $items_key ] ) ] = $item;
		}

	}

	/**
	 * Remove item from this order
	 *
	 * @since 1.2.4
	 *
	 * @param int $item_id
	 *
	 * @return void
	 */
	public function remove_item( $item_id ) {

		$item = $this->get_atum_order_item( $item_id );

		if ( ! $item || ! ( $items_key = $this->get_items_key( $item ) ) ) {
			return;
		}

		// Unset and remove later.
		$this->items_to_delete[] = $item;
		unset( $this->items[ $items_key ][ $item->get_id() ] );

	}
	
	/**
	 * Add a product line item to the ATUM Order
	 *
	 * @since 1.2.9
	 *
	 * @param  \WC_Product $product
	 * @param  int|float   $qty
	 * @param  array       $args
	 *
	 * @return \WC_Order_Item_Product The product item added to ATUM Order
	 */
	public function add_product( $product, $qty = NULL, $args = array() ) {

		if ( is_a( $product, '\WC_Product' ) ) {
			
			if ( is_null( $qty ) ) {
				$qty = Helpers::get_input_step();
			}

			$product_price = apply_filters( 'atum/order/add_product/price', wc_get_price_excluding_tax( $product, array( 'qty' => $qty ) ), $qty, $product );

			$default_args = array(
				'name'         => $product->get_name(),
				'tax_class'    => $product->get_tax_class(),
				'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
				'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
				'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
				'subtotal'     => $product_price,
				'total'        => $product_price,
				'quantity'     => $qty,
			);

		}
		else {

			$default_args = array(
				'quantity' => $qty,
			);

		}

		$args       = wp_parse_args( $args, $default_args );
		$item_class = $this->get_items_class( 'line_items' );

		/**
		 * Variable definition
		 *
		 * @var AtumOrderItemProduct $item
		 */
		$item = new $item_class();
		$item->set_props( $args );
		$item->set_backorder_meta();
		$item->set_atum_order_id( $this->id );
		$item->save();
		$this->add_item( $item );

		return $item;

	}

	/**
	 * Add a fee item to the ATUM Order
	 *
	 * @since 1.2.9
	 *
	 * @param \WC_Order_Item_Fee $fee   Optional. Fee item to import.
	 *
	 * @return \WC_Order_Item_Fee  The fee item added to the ATUM Order.
	 */
	public function add_fee( \WC_Order_Item_Fee $fee = NULL ) {

		$item_class = $this->get_items_class( 'fee_lines' );

		/**
		 * Variable definition
		 *
		 * @var AtumOrderItemFee $item
		 */
		$item = new $item_class();
		$item->set_atum_order_id( $this->id );

		if ( $fee ) {
			$item->set_tax_status( $fee->get_tax_status() );
			$item->set_taxes( $fee->get_taxes() );
			$item->set_tax_class( $fee->get_tax_class() );
			$item->set_total( $fee->get_total() );
		}

		$item->save();
		$this->add_item( $item );

		return $item;

	}

	/* @noinspection PhpDocMissingThrowsInspection */
	/**
	 * Add a shipping cost item to the ATUM Order
	 *
	 * @since 1.2.9
	 *
	 * @param \WC_Order_Item_Shipping $shipping  Optional. Shipping cost item to import.
	 *
	 * @return \WC_Order_Item_Shipping  The shipping cost item added to the ATUM Order.
	 */
	public function add_shipping_cost( \WC_Order_Item_Shipping $shipping = NULL ) {

		$item_class = $this->get_items_class( 'shipping_lines' );

		/**
		 * Variable definition
		 *
		 * @var AtumOrderItemShipping $item
		 */
		$item = new $item_class();
		$item->set_shipping_rate( new \WC_Shipping_Rate() );
		$item->set_atum_order_id( $this->id );

		if ( $shipping ) {
			/* @noinspection PhpUnhandledExceptionInspection */
			$item->set_method_id( $shipping->get_method_id() );
			/* @noinspection PhpUnhandledExceptionInspection */
			$item->set_total( $shipping->get_total() );
			/* @noinspection PhpUnhandledExceptionInspection */
			$item->set_taxes( $shipping->get_taxes() );
			/* @noinspection PhpUnhandledExceptionInspection */
			$item->set_method_title( $shipping->get_method_title() );
		}

		$item->save();
		$this->add_item( $item );

		return $item;

	}

	/**
	 * Add a tax item to the ATUM Order
	 *
	 * @since 1.2.9
	 *
	 * @param array              $values {
	 *      The array of tax values to add to the created tax item.
	 *
	 *      @type int    $rate_id            The tax rate ID
	 *      @type string $name               The tax item name
	 *      @type float  $tax_total          The tax total
	 *      @type float  $shipping_tax_total The shipping tax total
	 *
	 * }
	 * @param \WC_Order_Item_Tax $tax Optional. Tax item to import.
	 *
	 * @return \WC_Order_Item_Tax|bool  The tax item added to the ATUM Order or false if the required rate_id value is not passed
	 */
	public function add_tax( array $values, \WC_Order_Item_Tax $tax = NULL ) {

		if ( empty( $values['rate_id'] ) ) {
			return FALSE;
		}

		$item_class = $this->get_items_class( 'tax_lines' );

		/**
		 * Variable definition
		 *
		 * @var AtumOrderItemTax $item
		 */
		$item = new $item_class();
		$item->set_rate( $values['rate_id'] );
		$item->set_atum_order_id( $this->id );

		if ( $tax ) {
			$item->set_name( $tax->get_name() );
			$item->set_tax_total( $tax->get_tax_total() );
			$item->set_shipping_tax_total( $tax->get_shipping_tax_total() );
		}
		else {

			if ( isset( $values['name'] ) ) {
				$item->set_name( $values['name'] );
			}

			if ( isset( $values['tax_total'] ) ) {
				$item->set_tax_total( $values['tax_total'] );
			}

			if ( isset( $values['shipping_tax_total'] ) ) {
				$item->set_shipping_tax_total( $values['shipping_tax_total'] );
			}

		}

		$item->save();
		$this->add_item( $item );

		return $item;

	}

	/**
	 * Save ATUM Order items. Uses the CRUD
	 *
	 * @since 1.2.9
	 *
	 * @param array $items Order items to save.
	 */
	public function save_order_items( $items ) {

		// Allow other plugins to check changes in ATUM Order items before they are saved.
		do_action( 'atum/orders/before_save_items', $this, $items );

		// Line items and fees.
		if ( isset( $items['atum_order_item_id'] ) ) {

			$data_keys = array(
				'line_tax'                  => array(),
				'line_subtotal_tax'         => array(),
				'atum_order_item_name'      => NULL,
				'atum_order_item_qty'       => NULL,
				'atum_order_item_tax_class' => NULL,
				'line_total'                => NULL,
				'line_subtotal'             => NULL,
			);

			foreach ( $items['atum_order_item_id'] as $item_id ) {

				/**
				 * Variable definition
				 *
				 * @var AtumOrderItemFee|AtumOrderItemProduct|AtumOrderItemShipping|AtumOrderItemTax $item
				 */
				if ( ! $item = $this->get_atum_order_item( absint( $item_id ) ) ) {
					continue;
				}

				$item_data = array();

				foreach ( $data_keys as $key => $default ) {
					$item_data[ $key ] = ( isset( $items[ $key ][ $item_id ] ) ) ? wc_clean( wp_unslash( $items[ $key ][ $item_id ] ) ) : $default;
				}

				if ( '0' === $item_data['atum_order_item_qty'] ) {
					$item->delete();
					continue;
				}

				$item->set_props( array(
					'name'      => $item_data['atum_order_item_name'],
					'quantity'  => $item_data['atum_order_item_qty'],
					'tax_class' => $item_data['atum_order_item_tax_class'],
					'total'     => $item_data['line_total'],
					'subtotal'  => $item_data['line_subtotal'],
					'taxes'     => array(
						'total'    => $item_data['line_tax'],
						'subtotal' => $item_data['line_subtotal_tax'],
					),
				) );

				if ( isset( $items['meta_key'][ $item_id ], $items['meta_value'][ $item_id ] ) ) {

					foreach ( $items['meta_key'][ $item_id ] as $meta_id => $meta_key ) {

						$meta_value = ( isset( $items['meta_value'][ $item_id ][ $meta_id ] ) ) ? wp_unslash( $items['meta_value'][ $item_id ][ $meta_id ] ) : '';

						if ( '' === $meta_key && '' === $meta_value ) {
							if ( ! strstr( $meta_id, 'new-' ) ) {
								$item->delete_meta_data_by_mid( $meta_id );
							}
						}
						elseif ( strstr( $meta_id, 'new-' ) ) {
							$item->add_meta_data( $meta_key, $meta_value, false );
						}
						else {
							$item->update_meta_data( $meta_key, $meta_value, $meta_id );
						}

					}

				}

				$this->add_item( $item );
				$item->save();

			}

		}

		// Shipping Rows.
		if ( isset( $items['shipping_method_id'] ) ) {

			$data_keys = array(
				'shipping_method'       => NULL,
				'shipping_method_title' => NULL,
				'shipping_cost'         => 0,
				'shipping_taxes'        => array(),
			);

			foreach ( $items['shipping_method_id'] as $item_id ) {

				if ( ! $item = $this->get_atum_order_item( absint( $item_id ) ) ) {
					continue;
				}

				$item_data = array();

				foreach ( $data_keys as $key => $default ) {
					$item_data[ $key ] = ( isset( $items[ $key ][ $item_id ] ) ) ? wc_clean( wp_unslash( $items[ $key ][ $item_id ] ) ) : $default;
				}

				$item->set_props( array(
					'method_id'    => $item_data['shipping_method'],
					'method_title' => $item_data['shipping_method_title'],
					'total'        => $item_data['shipping_cost'],
					'taxes'        => array(
						'total' => $item_data['shipping_taxes'],
					),
				) );

				if ( isset( $items['meta_key'][ $item_id ], $items['meta_value'][ $item_id ] ) ) {

					foreach ( $items['meta_key'][ $item_id ] as $meta_id => $meta_key ) {

						$meta_value = ( isset( $items['meta_value'][ $item_id ][ $meta_id ] ) ) ? wp_unslash( $items['meta_value'][ $item_id ][ $meta_id ] ) : '';

						if ( '' === $meta_key && '' === $meta_value ) {
							if ( ! strstr( $meta_id, 'new-' ) ) {
								$item->delete_meta_data_by_mid( $meta_id );
							}
						}
						elseif ( strstr( $meta_id, 'new-' ) ) {
							$item->add_meta_data( $meta_key, $meta_value, FALSE );
						}
						else {
							$item->update_meta_data( $meta_key, $meta_value, $meta_id );
						}

					}

				}

				$this->add_item( $item );
				$item->save();

			}

		}

		// Updates tax totals.
		$this->update_taxes();

		// Calc totals - this also triggers save.
		$this->calculate_totals( FALSE );

		// Inform other plugins that the items have been saved.
		do_action( 'atum/orders/after_save_items', $this, $items );

	}

	/**
	 * Update tax lines for the ATUM Order based on the line item taxes themselves
	 *
	 * @since 1.2.9
	 */
	public function update_taxes() {

		$cart_taxes     = array();
		$shipping_taxes = array();
		$existing_taxes = $this->get_taxes();
		$saved_rate_ids = array();

		foreach ( $this->get_items( [ 'line_item', 'fee' ] ) as $item_id => $item ) {

			$taxes = $item->get_taxes();

			foreach ( $taxes['total'] as $tax_rate_id => $tax ) {
				$cart_taxes[ $tax_rate_id ] = ( isset( $cart_taxes[ $tax_rate_id ] ) ) ? $cart_taxes[ $tax_rate_id ] + (float) $tax : (float) $tax;
			}

		}

		foreach ( $this->get_shipping_methods() as $item_id => $item ) {

			$taxes = $item->get_taxes();
			foreach ( $taxes['total'] as $tax_rate_id => $tax ) {
				$shipping_taxes[ $tax_rate_id ] = isset( $shipping_taxes[ $tax_rate_id ] ) ? $shipping_taxes[ $tax_rate_id ] + (float) $tax : (float) $tax;
			}

		}

		foreach ( $existing_taxes as $tax ) {

			// Remove taxes which no longer exist for cart/shipping.
			if ( ( ! array_key_exists( $tax->get_rate_id(), $cart_taxes ) && ! array_key_exists( $tax->get_rate_id(), $shipping_taxes ) ) || in_array( $tax->get_rate_id(), $saved_rate_ids ) ) {
				$this->remove_item( $tax->get_id() );
				continue;
			}

			$saved_rate_ids[] = $tax->get_rate_id();
			$tax->set_tax_total( isset( $cart_taxes[ $tax->get_rate_id() ] ) ? $cart_taxes[ $tax->get_rate_id() ] : 0 );
			$tax->set_shipping_tax_total( ! empty( $shipping_taxes[ $tax->get_rate_id() ] ) ? $shipping_taxes[ $tax->get_rate_id() ] : 0 );
			$tax->save();

		}

		$new_rate_ids = wp_parse_id_list( array_diff( array_keys( $cart_taxes + $shipping_taxes ), $saved_rate_ids ) );

		// New taxes.
		foreach ( $new_rate_ids as $tax_rate_id ) {

			$this->add_tax( array(
				'rate_id'            => $tax_rate_id,
				'tax_total'          => isset( $cart_taxes[ $tax_rate_id ] ) ? $cart_taxes[ $tax_rate_id ] : 0,
				'shipping_tax_total' => ! empty( $shipping_taxes[ $tax_rate_id ] ) ? $shipping_taxes[ $tax_rate_id ] : 0,
			) );

		}

		// Save tax totals.
		$this->set_shipping_tax( \WC_Tax::round( array_sum( $shipping_taxes ) ) );
		$this->set_cart_tax( \WC_Tax::round( array_sum( $cart_taxes ) ) );
		$this->save();

	}

	/**
	 * Save ATUM Order data to the database
	 *
	 * @since 1.2.4
	 *
	 * @return int order ID
	 */
	public function save() {

		// Trigger action before saving to the DB. Allows you to adjust object props before save.
		do_action( 'atum/order/before_object_save', $this );

		if ( $this->id ) {
			$this->update();
		}
		else {
			$this->create();
		}

		$this->save_items();

		return $this->id;

	}

	/**
	 * Save all the items within this ATUM Order
	 *
	 * @since 1.2.4
	 */
	public function save_items() {

		foreach ( $this->items_to_delete as $item ) {
			$item->delete();
		}
		$this->items_to_delete = array();

		// Add/save items.
		foreach ( $this->items as $item_group => $items ) {

			if ( is_array( $items ) ) {

				foreach ( array_filter( $items ) as $item_key => $item ) {

					/**
					 * Variable definition
					 *
					 * @var AtumOrderItemFee|AtumOrderItemProduct|AtumOrderItemShipping|AtumOrderItemTax $item
					 */
					$item->set_atum_order_id( $this->id );
					$item_id = $item->save();

					// TODO: HANDLE ERRORS.
					if ( is_wp_error( $item_id ) ) {
						continue;
					}

					// If ID changed (new item saved to DB)...
					if ( $item_id != $item_key ) { // WPCS: loose comparison ok.
						$this->items[ $item_group ][ $item_id ] = $item;
					}

				}

			}

		}

	}

	/***************
	 * CRUD METHODS
	 ***************/

	/**
	 * Create a new ATUM Order in database
	 *
	 * @since 1.2.4
	 */
	public function create() {

		try {

			$current_date = $this->get_wc_time( current_time( 'timestamp', TRUE ) );
			$this->set_currency( $this->get_currency() ?: get_woocommerce_currency() );
			$status = $this->get_status();

			$id = wp_insert_post( apply_filters( 'atum/orders/new_order_data', array(
				'post_date'     => gmdate( 'Y-m-d H:i:s', $current_date->getOffsetTimestamp() ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $current_date->getTimestamp() ),
				'post_type'     => $this->post->post_type,
				'post_status'   => in_array( $status, array_keys( AtumOrderPostType::get_statuses() ) ) ? ATUM_PREFIX . $status : 'publish',
				'ping_status'   => 'closed',
				'post_author'   => get_current_user_id(),
				'post_title'    => $this->get_title(),
				'post_content'  => $this->get_description(),
				'post_password' => uniqid( ATUM_PREFIX . 'order_' ),
			) ), TRUE );

			if ( $id && ! is_wp_error( $id ) ) {
				$this->id = $id;
				$this->clear_caches();
			}

		} catch ( AtumException $e ) {

			if ( ATUM_DEBUG ) {
				error_log( __METHOD__ . '::' . $e->getErrorCode() . '::' . $e->getMessage() );
			}

		}

	}

	/**
	 * Update an ATUM Order in database
	 *
	 * @since 1.2.4
	 */
	public function update() {
		
		$status       = $this->get_status();
		$date         = $this->get_date();
		$created_date = $this->get_wc_time( $date );
		
		if ( $this->post->post_date !== $date ) {
			// Empty the post title to be updated by the get_title() method.
			$this->post->post_title = '';
		}

		$post_data = array(
			'post_date'         => gmdate( 'Y-m-d H:i:s', $created_date->getOffsetTimestamp() ),
			'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $created_date->getTimestamp() ),
			'post_status'       => ( in_array( $status, array_keys( AtumOrderPostType::get_statuses() ) ) ) ? ATUM_PREFIX . $status : 'publish',
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', 1 ),
			'post_title'        => $this->get_title(),
			'post_content'      => $this->get_description(),
		);

		/**
		 * When updating this object, to prevent infinite loops, use $wpdb
		 * to update data, since wp_update_post spawns more calls to the save_post action
		 *
		 * This ensures hooks are fired by either WP itself (admin screen save), or an update purely from CRUD
		 */
		if ( doing_action( "save_post_{$this->post->post_type}" ) ) {
			$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $this->id ) );
			clean_post_cache( $this->id );
		}
		else {
			wp_update_post( array_merge( array( 'ID' => $this->id ), $post_data ) );
		}

		$this->clear_caches();

	}

	/**
	 * Update the ATUM Order status
	 *
	 * @since 1.2.9
	 *
	 * @param string $new_status    Status to set to the ATUM Order. No "atum_" prefix is required.
	 */
	public function update_status( $new_status ) {

		$old_status = $this->get_status();
		$new_status = FALSE !== strpos( $new_status, ATUM_PREFIX ) ? str_replace( ATUM_PREFIX, '', $new_status ) : $new_status;
		$statuses   = AtumOrderPostType::get_statuses();

		// Only allow valid new status.
		if ( ! in_array( $new_status, array_keys( $statuses ) ) && 'trash' !== $new_status ) {
			$new_status = 'pending';
		}

		// If the old status is set but unknown (e.g. draft) assume its pending for action usage.
		if ( $old_status && ! in_array( $old_status, array_keys( $statuses ) ) && 'trash' !== $old_status ) {
			$old_status = 'pending';
		}

		if ( $new_status !== $old_status ) {
			$this->set_status( $new_status );
			$this->save();
		}

	}

	/***************
	 * CALCULATIONS
	 ***************/

	/**
	 * Calculate taxes for all line items and shipping, and store the totals and tax rows
	 *
	 * @since 1.2.4
	 *
	 * @param array $args Optional. To pass things like location.
	 */
	public function calculate_taxes( $args = array() ) {

		$tax_based_on = 'base';

		// Default to base.
		if ( 'base' === $tax_based_on || empty( $args['country'] ) ) {
			$default          = wc_get_base_location();
			$args['country']  = $default['country'];
			$args['state']    = $default['state'];
			$args['postcode'] = '';
			$args['city']     = '';
		}

		// Calc taxes for line items.
		foreach ( $this->get_items( [ 'line_item', 'fee' ] ) as $item_id => $item ) {

			$tax_class  = $item->get_tax_class();
			$tax_status = $item->get_tax_status();

			if ( '0' !== $tax_class && 'taxable' === $tax_status && wc_tax_enabled() ) {

				$tax_rates = \WC_Tax::find_rates( array(
					'country'   => $args['country'],
					'state'     => $args['state'],
					'postcode'  => $args['postcode'],
					'city'      => $args['city'],
					'tax_class' => $tax_class,
				) );

				$total = $item->get_total();
				$taxes = \WC_Tax::calc_tax( $total, $tax_rates, FALSE );

				if ( $item->is_type( 'line_item' ) ) {
					$subtotal       = $item->get_subtotal();
					$subtotal_taxes = \WC_Tax::calc_tax( $subtotal, $tax_rates, FALSE );
					$item->set_taxes( array(
						'total'    => $taxes,
						'subtotal' => $subtotal_taxes,
					) );
				}
				else {
					$item->set_taxes( array( 'total' => $taxes ) );
				}

			}
			else {
				$item->set_taxes( FALSE );
			}

			$item->save();

		}

		// Calc taxes for shipping.
		foreach ( $this->get_shipping_methods() as $item_id => $item ) {

			if ( wc_tax_enabled() ) {

				$shipping_tax_class = get_option( 'woocommerce_shipping_tax_class' );

				// Inherit tax class from items.
				if ( 'inherit' === $shipping_tax_class ) {

					$tax_rates         = array();
					$tax_classes       = array_merge( array( '' ), \WC_Tax::get_tax_class_slugs() );
					$found_tax_classes = $this->get_items_tax_classes();

					foreach ( $tax_classes as $tax_class ) {

						if ( in_array( $tax_class, $found_tax_classes ) ) {

							$tax_rates = \WC_Tax::find_shipping_rates( array(
								'country'   => $args['country'],
								'state'     => $args['state'],
								'postcode'  => $args['postcode'],
								'city'      => $args['city'],
								'tax_class' => $tax_class,
							) );

							break;

						}

					}

				}
				else {

					$tax_rates = \WC_Tax::find_shipping_rates( array(
						'country'   => $args['country'],
						'state'     => $args['state'],
						'postcode'  => $args['postcode'],
						'city'      => $args['city'],
						'tax_class' => $shipping_tax_class,
					) );

				}

				$item->set_taxes( array( 'total' => \WC_Tax::calc_tax( $item->get_total(), $tax_rates, FALSE ) ) );

			}
			else {
				$item->set_taxes( FALSE );
			}

			$item->save();

		}

		$this->update_taxes();

	}

	/**
	 * Calculate shipping total
	 *
	 * @since 1.2.4
	 *
	 * @return float
	 */
	public function calculate_shipping() {

		$shipping_total = 0;

		foreach ( $this->get_shipping_methods() as $shipping ) {
			$shipping_total += $shipping->get_total();
		}

		$this->set_shipping_total( $shipping_total );
		$this->save();

		return $this->get_shipping_total();
	}

	/**
	 * Calculate totals by looking at the contents of the ATUM Order
	 * Stores the totals and returns the ATUM order's final total
	 *
	 * @since 1.2.4
	 *
	 * @param bool $and_taxes Optional. Calc taxes if true.
	 *
	 * @return float Calculated grand total
	 */
	public function calculate_totals( $and_taxes = TRUE ) {

		$cart_subtotal     = 0;
		$cart_total        = 0;
		$fee_total         = 0;
		$cart_subtotal_tax = 0;
		$cart_total_tax    = 0;

		if ( $and_taxes ) {
			$this->calculate_taxes();
		}

		// Line items.
		foreach ( $this->get_items() as $item ) {
			$cart_subtotal     += $item->get_subtotal();
			$cart_total        += $item->get_total();
			$cart_subtotal_tax += $item->get_subtotal_tax();
			$cart_total_tax    += $item->get_total_tax();
		}

		$this->calculate_shipping();

		foreach ( $this->get_fees() as $item ) {
			$fee_total += $item->get_total();
		}

		$grand_total = round( $cart_total + $fee_total + $this->get_shipping_total() + $this->get_cart_tax() + $this->get_shipping_tax(), wc_get_price_decimals() );

		$this->set_discount_total( $cart_subtotal - $cart_total );
		$this->set_discount_tax( $cart_subtotal_tax - $cart_total_tax );
		$this->set_total( $grand_total );
		$this->save();

		return $grand_total;

	}

	/*************
	 * TOTALIZERS
	 *************/

	/**
	 * Get item subtotal - this is the cost before discount
	 *
	 * @since 1.2.4
	 *
	 * @param AtumOrderItemProduct $item
	 * @param bool                 $inc_tax
	 * @param bool                 $round
	 *
	 * @return float
	 */
	public function get_item_subtotal( $item, $inc_tax = FALSE, $round = TRUE ) {

		$subtotal = 0;

		if ( is_callable( array( $item, 'get_subtotal' ) ) ) {

			if ( $inc_tax ) {
				$subtotal = ( $item->get_subtotal() + $item->get_subtotal_tax() ) / max( 1, $item->get_quantity() );
			}
			else {
				$subtotal = ( floatval( $item->get_subtotal() ) / max( 1, $item->get_quantity() ) );
			}

			$subtotal = $round ? number_format( (float) $subtotal, wc_get_price_decimals(), '.', '' ) : $subtotal;

		}

		return apply_filters( 'atum/orders/amount_item_subtotal', $subtotal, $this, $item, $inc_tax, $round );

	}

	/**
	 * Calculate item cost
	 *
	 * @since 1.2.4
	 *
	 * @param AtumOrderItemProduct $item
	 * @param bool                 $inc_tax
	 * @param bool                 $round
	 *
	 * @return float
	 */
	public function get_item_total( $item, $inc_tax = FALSE, $round = TRUE ) {

		$total = 0;

		if ( is_callable( array( $item, 'get_total' ) ) ) {

			if ( $inc_tax ) {
				$total = ( $item->get_total() + $item->get_total_tax() ) / max( 1, $item->get_quantity() );
			}
			else {
				$total = floatval( $item->get_total() ) / max( 1, $item->get_quantity() );
			}

			$total = $round ? round( $total, wc_get_price_decimals() ) : $total;

		}

		return apply_filters( 'atum/orders/amount_item_total', $total, $this, $item, $inc_tax, $round );

	}

	/**
	 * Gets the total discount amount
	 *
	 * @since 1.2.4
	 *
	 * @param  bool $ex_tax  Optional. Show discount excl any tax.
	 *
	 * @return float
	 */
	public function get_total_discount( $ex_tax = TRUE ) {

		$total_discount = $this->get_discount_total();

		if ( ! $ex_tax ) {
			$total_discount += $this->get_discount_tax();
		}

		/* @noinspection PhpUndefinedConstantInspection */
		return apply_filters( 'atum/orders/get_total_discount', round( $total_discount, WC_ROUNDING_PRECISION ), $this );

	}

	/**
	 * Gets order subtotal
	 *
	 * @since 1.2.4
	 *
	 * @return float
	 */
	public function get_subtotal() {

		$subtotal = 0;

		foreach ( $this->get_items() as $item ) {
			$subtotal += $item->get_subtotal();
		}

		return apply_filters( 'atum/orders/get_subtotal', (double) $subtotal, $this );

	}

	/**
	 * Get taxes, merged by code, formatted ready for output
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public function get_tax_totals() {

		$tax_totals = array();

		foreach ( $this->get_items( 'tax' ) as $key => $tax ) {

			$code = $tax->get_rate_code();

			if ( ! isset( $tax_totals[ $code ] ) ) {
				$tax_totals[ $code ]         = new \stdClass();
				$tax_totals[ $code ]->amount = 0;
			}

			$tax_totals[ $code ]->id               = $key;
			$tax_totals[ $code ]->rate_id          = $tax->get_rate_id();
			$tax_totals[ $code ]->is_compound      = $tax->is_compound();
			$tax_totals[ $code ]->label            = $tax->get_label();
			$tax_totals[ $code ]->amount          += (float) $tax->get_tax_total() + (float) $tax->get_shipping_tax_total();
			$tax_totals[ $code ]->formatted_amount = wc_price( wc_round_tax_total( $tax_totals[ $code ]->amount ), array( 'currency' => $this->get_currency() ) );

		}

		if ( apply_filters( 'atum/orders/hide_zero_taxes', TRUE ) ) {
			$amounts    = array_filter( wp_list_pluck( $tax_totals, 'amount' ) );
			$tax_totals = array_intersect_key( $tax_totals, $amounts );
		}

		return apply_filters( 'atum/orders/get_tax_totals', $tax_totals, $this );

	}

	/**
	 * Gets ATUM order's total - formatted for display
	 *
	 * @since 1.2.9
	 *
	 * @param  string $tax_display  Optional. Type of tax display.
	 * @param  bool   $subtotal     Optional. If should return the tax free Subtotal instead.
	 *
	 * @return string
	 */
	public function get_formatted_total( $tax_display = '', $subtotal = FALSE ) {

		$amount          = $subtotal ? $this->get_subtotal() : $this->get_total();
		$formatted_total = wc_price( $amount, array( 'currency' => $this->get_currency() ) );
		$tax_string      = '';

		// Tax for inclusive prices.
		if ( wc_tax_enabled() && 'incl' === $tax_display && ! $subtotal ) {

			$tax_string_array = array();

			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {

				foreach ( $this->get_tax_totals() as $code => $tax ) {
					$tax_amount         = $tax->formatted_amount;
					$tax_string_array[] = sprintf( '%s %s', $tax_amount, $tax->label );
				}

			}
			else {
				$tax_amount         = $this->get_total_tax();
				$tax_string_array[] = sprintf( '%s %s', wc_price( $tax_amount, array( 'currency' => $this->get_currency() ) ), WC()->countries->tax_or_vat() );
			}

			if ( ! empty( $tax_string_array ) ) {
				/* translators: a list of comma-separated taxes */
				$tax_string = ' <small class="includes_tax">' . sprintf( __( '(includes %s)', ATUM_TEXT_DOMAIN ), implode( ', ', $tax_string_array ) ) . '</small>';
			}

		}

		$formatted_total .= $tax_string;

		return apply_filters( 'atum/orders/get_formatted_total', $formatted_total, $this, $tax_display, $subtotal );

	}

	/**
	 * Checks if an ATUM Order can be edited, specifically for use on the Edit screen
	 *
	 * @since 1.2.9
	 *
	 * @return bool
	 */
	public function is_editable() {
		$status = $this->get_status();
		return apply_filters( 'atum/orders/is_editable', ! $status || 'pending' === $status, $this );
	}

	/**
	 * Adds a note (comment) to the ATUM order. Order must exist
	 *
	 * @since 1.2.9
	 *
	 * @param string $note Note to add.
	 *
	 * @return int   Comment ID
	 */
	public function add_note( $note ) {

		if ( ! $this->id || ! is_user_logged_in() || ! AtumCapabilities::current_user_can( 'create_order_notes' ) ) {
			return 0;
		}

		$user                 = get_user_by( 'id', get_current_user_id() );
		$comment_author       = $user->display_name;
		$comment_author_email = $user->user_email;

		$commentdata = apply_filters( 'atum/orders/note_data', array(
			'comment_post_ID'      => $this->id,
			'comment_author'       => $comment_author,
			'comment_author_email' => $comment_author_email,
			'comment_author_url'   => '',
			'comment_content'      => $note,
			'comment_agent'        => 'ATUM',
			'comment_type'         => 'atum_order_note',
			'comment_parent'       => 0,
			'comment_approved'     => 1,
		), $this->id );

		$comment_id = wp_insert_comment( $commentdata );

		do_action( 'atum/orders/after_note_added', $comment_id, $this->id );

		return $comment_id;

	}

	/**
	 * Returns requested meta keys' values
	 *
	 * @since 1.2.9
	 *
	 * @param string $meta_key Optional. A string indicating which meta key to retrieve, or NULL to return all keys.
	 * @param bool   $single   Optional. TRUE to return the first value, FALSE to return an array of values.
	 *
	 * @return string|array
	 */
	public function get_meta( $meta_key = NULL, $single = TRUE ) {

		if ( NULL !== $meta_key ) {
			// Get a single field.
			return get_post_meta( $this->id, $meta_key, $single );
		}
		else {
			return get_post_custom( $this->id );
		}

	}

	/**
	 * Saves the given meta key/value pairs
	 *
	 * @since 1.2.9
	 *
	 * @param array $meta An associative array of meta keys and their values to save.
	 * @param bool  $trim
	 *
	 * @return void
	 */
	public function save_meta( $meta = array(), $trim = FALSE ) {

		foreach ( $meta as $key => $value ) {

			if ( $trim ) {
				$value = Helpers::trim_input( $value );
			}

			$this->set_meta( $key, $value );

		}

	}

	/**
	 * Sets the meta key for the current ATUM Order
	 *
	 * @since 1.2.9
	 *
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 */
	public function set_meta( $meta_key, $meta_value ) {
		update_post_meta( $this->id, $meta_key, $meta_value );
	}

	/**
	 * Delete the given meta keys
	 *
	 * @since 1.2.9
	 *
	 * @param array $meta
	 */
	public function delete_meta( $meta ) {

		foreach ( $meta as $key => $value ) {
			delete_post_meta( $this->id, $key, $value );
		}

	}

	/**
	 * Clear any caches
	 *
	 * @since 1.2.9
	 */
	protected function clear_caches() {

		clean_post_cache( $this->id );
		$cache_key   = "{$this->cache_key}-{$this->id}";
		$cache_group = $this->post->post_type;
		wp_cache_delete( $cache_key, $cache_group );
	}

	/**
	 * Internal meta keys we don't want exposed as part of meta_data.
	 * This is in addition to all data props with _ prefix.
	 *
	 * @since 1.2.9
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function prefix_key( $key ) {
		return '_' === substr( $key, 0, 1 ) ? $key : '_' . $key;
	}

	/**
	 * When invalid data is found, throw an exception unless reading from the DB
	 *
	 * @since 1.2.9
	 *
	 * @param string $code             Error code.
	 * @param string $message          Error message.
	 * @param int    $http_status_code HTTP status code.
	 * @param array  $data             Extra error data.
	 *
	 * @throws AtumException
	 */
	public function error( $code, $message, $http_status_code = 400, $data = array() ) {
		throw new AtumException( $code, $message, $http_status_code, $data );
	}

	/**
	 * Sets a date prop whilst handling formatting and datetime objects.
	 *
	 * @since 1.4.18.2
	 *
	 * @param string|integer|\WC_DateTime $value
	 *
	 * @return \WC_DateTime
	 */
	protected function get_wc_time( $value ) {
		try {
			
			if ( is_a( $value, 'WC_DateTime' ) ) {
				$datetime = $value;
			} elseif ( is_numeric( $value ) ) {
				// Timestamps are handled as UTC timestamps in all cases.
				$datetime = new \WC_DateTime( "@{$value}", new \DateTimeZone( 'UTC' ) );
			} else {
				// Strings are defined in local WP timezone. Convert to UTC.
				if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $value, $date_bits ) ) {
					$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : wc_timezone_offset();
					$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
				} else {
					$timestamp = wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $value ) ) ) );
				}
				$datetime = new \WC_DateTime( "@{$timestamp}", new \DateTimeZone( 'UTC' ) );
			}
			
			// Set local timezone or offset.
			if ( get_option( 'timezone_string' ) ) {
				$datetime->setTimezone( new \DateTimeZone( wc_timezone_string() ) );
			} else {
				$datetime->set_utc_offset( wc_timezone_offset() );
			}
			
			return $datetime;
			
		} catch ( \Exception $e ) {} // @codingStandardsIgnoreLine.
	}

	/**********
	 * GETTERS
	 **********/

	/**
	 * Getter for the post property
	 *
	 * @since 1.2.9
	 *
	 * @return \WP_Post
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 * Get the title for the ATUM Order post
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public function get_title() {

		if ( ! empty( $this->post->post_title ) && __( 'Auto Draft' ) !== $this->post->post_title ) { // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			$post_title = $this->post->post_title;
		}
		else {
			/* translators: the order date */
			$post_title = sprintf( __( 'ATUM Order &ndash; %s', ATUM_TEXT_DOMAIN ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'ATUM Order date parsed by strftime', ATUM_TEXT_DOMAIN ), strtotime( $this->get_date() ) ) ); // phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText
		}

		return apply_filters( 'atum/orders/title', $post_title );
	}

	/**
	 * Get the description for the ATUM Order post
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public function get_description() {

		$description = ! empty( $this->post->post_content ) ? $this->post->post_content : '';
		return apply_filters( 'atum/orders/description', $description );
	}

	/**
	 * Get the ATUM Order status
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->get_meta( '_status' );
	}

	/**
	 * Get the ATUM Order date
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public function get_date() {
		return $this->get_meta( '_date_created' );
	}

	/**
	 * Get the ATUM Order currency
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public function get_currency() {
		return $this->get_meta( '_currency' );
	}

	/**
	 * Gets order grand total
	 *
	 * @since 1.2.9
	 *
	 * @return float
	 */
	public function get_total() {
		return $this->get_meta( '_total' );
	}

	/**
	 * Return an array of items within this ATUM Order
	 *
	 * @since 1.2.9
	 *
	 * @param string|array $types Optional. Types of line items to get (array or string).
	 *
	 * @return \WC_Order_Item_Product array
	 */
	public function get_items( $types = 'line_item' ) {

		$items = array();
		$types = array_filter( (array) $types );

		foreach ( $types as $type ) {

			if ( $group = $this->type_to_group( $type ) ) {

				// Don't use array_merge here because keys are numeric.
				$items = ( isset( $this->items[ $group ] ) ) ? array_filter( $items + $this->items[ $group ] ) : $items;
			}

		}

		return apply_filters( 'atum/orders/get_items', $items, $this );

	}
	
	/**
	 * Get order's type
	 *
	 * @since 1.4.16
	 *
	 * @return string
	 */
	abstract public function get_type();

	/**
	 * Get an ATUM Order item
	 *
	 * @since 1.2.9
	 *
	 * @param object $item
	 *
	 * @return \WC_Order_Item|false if not found
	 */
	abstract public function get_atum_order_item( $item = NULL );

	/**
	 * Get key for where a certain item type is stored in items prop
	 *
	 * @since  1.2.9
	 *
	 * @param  \WC_Order_Item $item  ATUM Order item object (product, shipping, fee, tax).
	 *
	 * @return string
	 */
	abstract protected function get_items_key( $item );

	/**
	 * This method is the inverse of the get_items_key method
	 * Gets the ATUM Order item's class given its key
	 *
	 * @since 1.2.9
	 *
	 * @param string $items_key The items key.
	 *
	 * @return string
	 */
	abstract protected function get_items_class( $items_key );

	/**
	 * Get a specified item linked to this ATUM Order
	 *
	 * @since 1.2.9
	 *
	 * @param int    $item_id
	 * @param string $type
	 *
	 * @return \WC_Order_Item|bool
	 */
	public function get_item( $item_id, $type = 'line_item' ) {

		$type_group = $this->type_to_group( $type );
		if ( ! empty( $this->items ) && isset( $this->items[ $type_group ], $this->items[ $type_group ][ $item_id ] ) ) {
			return $this->items[ $type_group ][ $item_id ];
		}

		return FALSE;

	}

	/**
	 * Get discount total
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_discount_total() {
		return $this->get_meta( '_discount_total' );
	}

	/**
	 * Get discount tax
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_discount_tax() {
		return $this->get_meta( '_discount_tax' );
	}

	/**
	 * Get shipping total
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_shipping_total() {
		return $this->get_meta( '_shipping_total' );
	}

	/**
	 * Get shipping tax
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_shipping_tax() {
		return $this->get_meta( '_shipping_tax' );
	}

	/**
	 * Gets cart's tax amount
	 *
	 * @since 1.2.4
	 *
	 * @return float
	 */
	public function get_cart_tax() {
		return $this->get_meta( '_cart_tax' );
	}

	/**
	 * Get total tax amount
	 *
	 * @since 1.2.4
	 *
	 * @return float
	 */
	public function get_total_tax() {
		return $this->get_meta( '_total_tax' );
	}

	/**
	 * Return an array of fees within this ATUM Order
	 *
	 * @since 1.2.4
	 *
	 * @return array|\WC_Order_Item_Product
	 */
	public function get_fees() {
		return $this->get_items( 'fee' );
	}

	/**
	 * Return an array of taxes within this ATUM Order
	 *
	 * @since 1.2.4
	 *
	 * @return array|\WC_Order_Item_Product
	 */
	public function get_taxes() {
		return $this->get_items( 'tax' );
	}

	/**
	 * Return an array of shipping costs within this ATUM Order
	 *
	 * @since 1.2.4
	 *
	 * @return array|\WC_Order_Item_Product
	 */
	public function get_shipping_methods() {
		return $this->get_items( 'shipping' );
	}

	/**
	 * Get all tax classes for items in the ATUM Order
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public function get_items_tax_classes() {

		$found_tax_classes = array();

		foreach ( $this->get_items() as $item ) {
			if ( $product = $item->get_product() ) {
				/**
				 * Variable definition
				 *
				 * @var \WC_Product $product
				 */
				$found_tax_classes[] = $product->get_tax_class();
			}
		}

		return array_unique( $found_tax_classes );

	}
	
	/**
	 * Get current Order Type item quantities sign
	 *
	 * @since 1.4.16
	 *
	 * @return string
	 */
	public function get_action() {
		
		return $this->action;
	}

	/**********
	 * SETTERS
	 **********/

	/**
	 * Set ATUM Order currency
	 *
	 * @since 1.2.9
	 *
	 * @param string $value
	 *
	 * @throws AtumException
	 */
	public function set_currency( $value ) {

		if ( $value && ! in_array( $value, array_keys( get_woocommerce_currencies() ) ) ) {
			$this->error( 'order_invalid_currency', __( 'Invalid currency code', ATUM_TEXT_DOMAIN ) );
		}

		$this->set_meta( '_currency', $value ? $value : get_woocommerce_currency() );

	}

	/**
	 * Set total
	 *
	 * @since 1.2.9
	 *
	 * @param float $value
	 */
	public function set_total( $value ) {
		$this->set_meta( '_total', wc_format_decimal( $value, wc_get_price_decimals() ) );
	}

	/**
	 * Set discount total
	 *
	 * @since 1.2.9
	 *
	 * @param float $value
	 */
	public function set_discount_total( $value ) {
		$this->set_meta( '_discount_total', wc_format_decimal( $value ) );
	}

	/**
	 * Set discount tax
	 *
	 * @since 1.2.9
	 *
	 * @param float $value
	 */
	public function set_discount_tax( $value ) {
		$this->set_meta( '_discount_tax', wc_format_decimal( $value ) );
	}

	/**
	 * Set shipping total
	 *
	 * @since 1.2.9
	 *
	 * @param float $value
	 */
	public function set_shipping_total( $value ) {
		$this->set_meta( '_shipping_total', wc_format_decimal( $value ) );
	}

	/**
	 * Set shipping tax
	 *
	 * @since 1.2.9
	 *
	 * @param float $value
	 */
	public function set_shipping_tax( $value ) {
		$this->set_meta( '_shipping_tax', wc_format_decimal( $value ) );
		$this->set_total_tax( (float) $this->get_cart_tax() + (float) $this->get_shipping_tax() );
	}

	/**
	 * Set cart tax
	 *
	 * @since 1.2.9
	 *
	 * @param float $value
	 */
	public function set_cart_tax( $value ) {
		$this->set_meta( '_cart_tax', wc_format_decimal( $value ) );
		$this->set_total_tax( (float) $this->get_cart_tax() + (float) $this->get_shipping_tax() );
	}

	/**
	 * Sets tax (sum of cart and shipping tax)
	 * Used internally only
	 *
	 * @since 1.2.9
	 *
	 * @param float $value
	 */
	protected function set_total_tax( $value ) {
		$this->set_meta( '_total_tax', wc_format_decimal( $value ) );
	}

	/**
	 * Set status
	 *
	 * @since 1.2.9
	 *
	 * @param string $value
	 */
	public function set_status( $value ) {
		$this->set_meta( '_status', wc_clean( $value ) );
	}

	/**
	 * Set description
	 *
	 * @since 1.2.9
	 *
	 * @param string $value
	 */
	public function set_description( $value ) {

		$allowed_html = apply_filters( 'atum/orders/allowed_html_in_description', array(
			'a'      => array(
				'href'  => [],
				'title' => [],
				'style' => [],
			),
			'span'   => array(
				'style' => [],
			),
			'p'      => array(
				'style' => [],
			),
			'br'     => [],
			'em'     => [],
			'strong' => [],
			'ul'     => [],
			'ol'     => [],
			'li'     => [],
		) );

		$this->post->post_content = wp_kses( $value, $allowed_html );
	}

}
