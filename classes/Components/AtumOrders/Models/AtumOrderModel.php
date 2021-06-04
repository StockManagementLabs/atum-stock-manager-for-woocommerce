<?php
/**
 * The abstract class for the ATUM Order model
 *
 * @package         Atum\Components\AtumOrders
 * @subpackage      AtumOrders
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\Components\AtumOrders\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumOrders\AtumComments;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Items\AtumOrderItemFee;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Components\AtumOrders\Items\AtumOrderItemShipping;
use Atum\Components\AtumOrders\Items\AtumOrderItemTax;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\Items\LogItemFee;
use Atum\InventoryLogs\Items\LogItemProduct;
use Atum\InventoryLogs\Items\LogItemShipping;
use Atum\PurchaseOrders\Items\POItemFee;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\Items\POItemShipping;


/**
 * Class AtumOrderModel
 *
 * Meta props available through the __get magic method:
 *
 * @property string $date_created
 * @property string $currency
 * @property float  $total
 * @property float  $discount_total
 * @property float  $discount_tax
 * @property float  $shipping_total
 * @property float  $shipping_tax
 * @property float  $cart_tax
 * @property float  $total_tax
 * @property string $status
 * @property string $created_via
 * @property string $prices_include_tax
 * @property string $date_completed
 */
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
	 * Database stored current Order status
	 *
	 * @var string
	 */
	protected $db_status;

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
	 * The default line item type
	 *
	 * @var string
	 */
	protected $line_item_type = 'line_item';

	/**
	 * The default line item group
	 *
	 * @var string
	 */
	protected $line_item_group = 'line_items';

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
	 * Message shown in the item's blocker
	 *
	 * @var string
	 */
	protected $block_message = '';

	/**
	 * Array to store the meta data to add/update
	 *
	 * @var array
	 */
	protected $meta = [
		'date_created'       => '',
		'currency'           => '',
		'total'              => NULL,
		'discount_total'     => NULL,
		'discount_tax'       => NULL,
		'shipping_total'     => NULL,
		'shipping_tax'       => NULL,
		'cart_tax'           => NULL,
		'total_tax'          => NULL,
		'status'             => '',
		'created_via'        => '',
		'prices_include_tax' => 'no',
		'date_completed'     => '',
	];

	/**
	 * Changes made to the ATUM Order that should be updated
	 *
	 * @var array
	 */
	protected $changes = array();
	
	/**
	 * AtumOrderModel constructor
	 *
	 * @param int  $id         Optional. The ATUM Order ID to initialize.
	 * @param bool $read_items Optional. Whether to read the inner items.
	 */
	public function __construct( $id = 0, $read_items = TRUE ) {
		
		$this->block_message = __( 'Click the Create button on the top right to add/edit items.', ATUM_TEXT_DOMAIN );

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

		if ( $this->post ) {
			$this->db_status = $this->post->post_status;

			// Load the order meta data.
			$this->read_meta();
		}

	}

	/**
	 * Read items of a specific type from the database for this ATUM Order
	 *
	 * @since 1.2.4
	 *
	 * @param string $type Optional. Filter by item type.
	 */
	public function read_items( $type = '' ) {

		// Get from cache if available.
		$cache_key = AtumCache::get_cache_key( $this->cache_key, $this->id );
		$items     = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			$items = Helpers::get_order_items( $this->id );

			if ( ! $items ) {
				return;
			}

			AtumCache::set_cache( $cache_key, $items );

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

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$meta_sql = $wpdb->prepare("
			DELETE FROM itemmeta USING $wpdb->prefix" . AtumOrderPostType::ORDER_ITEM_META_TABLE . " itemmeta 
			INNER JOIN $wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . ' items 
			WHERE itemmeta.order_item_id = items.order_item_id AND items.order_id = %d 
		', $this->id );
		// phpcs:enable

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$items_sql = $wpdb->prepare( "
			DELETE FROM $wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . '
			WHERE order_id = %d 
		', $this->id );
		// phpcs:enable

		if ( ! empty( $type ) ) {
			$type_sql   = $wpdb->prepare( ' AND order_item_type = %s', $type );
			$meta_sql  .= $type_sql;
			$items_sql .= $type_sql;
		}

		$wpdb->query( $meta_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $items_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
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
			$this->line_item_type => $this->line_item_group,
			'tax'                 => 'tax_lines',
			'shipping'            => 'shipping_lines',
			'fee'                 => 'fee_lines',
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
			$this->line_item_group => $this->line_item_type,
			'tax_lines'            => 'tax',
			'shipping_lines'       => 'shipping',
			'fee_lines'            => 'fee',
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
	 * @return AtumOrderItemProduct The product item added to ATUM Order
	 */
	public function add_product( $product, $qty = NULL, $args = array() ) {

		if ( $product instanceof \WC_Product ) {
			
			if ( is_null( $qty ) ) {
				$qty = $product->get_min_purchase_quantity();
			}

			$product_price = apply_filters( 'atum/order/add_product/price', wc_get_price_excluding_tax( $product, array( 'qty' => $qty ) ), $qty, $product, $this );

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
		$item_class = $this->get_items_class( $this->line_item_group );

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
	 * @return AtumOrderItemFee  The fee item added to the ATUM Order.
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
	 * @return AtumOrderItemShipping  The shipping cost item added to the ATUM Order.
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
			$item->set_method_id( $shipping->get_method_id() );
			$item->set_total( $shipping->get_total() );
			$item->set_taxes( $shipping->get_taxes() );
			$item->set_method_title( $shipping->get_method_title() );
		}

		$item->save();
		$this->add_item( $item );

		return $item;

	}

	/* @noinspection PhpDocSignatureInspection */
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
	 * @return AtumOrderItemTax|bool  The tax item added to the ATUM Order or false if the required rate_id value is not passed
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
					$item_data[ $key ] = isset( $items[ $key ][ $item_id ] ) ? wc_clean( wp_unslash( $items[ $key ][ $item_id ] ) ) : $default;
				}

				if ( '0' === $item_data['atum_order_item_qty'] ) {
					$item->delete();
					continue;
				}
				
				$line_total    = $item_data['line_total'];
				$line_subtotal = $item_data['line_subtotal'];

				$item->set_props( array(
					'name'      => $item_data['atum_order_item_name'],
					'quantity'  => $item_data['atum_order_item_qty'],
					'tax_class' => $item_data['atum_order_item_tax_class'],
					'total'     => $line_total,
					'subtotal'  => $line_subtotal < $line_total ? $line_total : $line_subtotal,
					'taxes'     => array(
						'total'    => $item_data['line_tax'],
						'subtotal' => $item_data['line_subtotal_tax'],
					),
				) );

				if ( isset( $items['meta_key'][ $item_id ], $items['meta_value'][ $item_id ] ) ) {
					$this->save_item_meta( $item, $items['meta_key'][ $item_id ], $items['meta_value'][ $item_id ] );
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
					$item_data[ $key ] = isset( $items[ $key ][ $item_id ] ) ? wc_clean( wp_unslash( $items[ $key ][ $item_id ] ) ) : $default;
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
					$this->save_item_meta( $item, $items['meta_key'][ $item_id ], $items['meta_value'][ $item_id ] );
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
	 * Save item meta
	 *
	 * @since 1.7.1
	 *
	 * @param AtumOrderItemFee|AtumOrderItemProduct|AtumOrderItemShipping|AtumOrderItemTax $item
	 * @param array                                                                        $meta_keys
	 * @param array                                                                        $meta_values
	 */
	public function save_item_meta( $item, $meta_keys, $meta_values ) {

		foreach ( $meta_keys as $meta_id => $meta_key ) {

			$meta_value = isset( $meta_values[ $meta_id ] ) ? wp_unslash( $meta_values[ $meta_id ] ) : '';

			if ( '' === $meta_key && '' === $meta_value ) {
				if ( ! strstr( $meta_id, 'new-' ) ) {
					$item->delete_meta_data_by_mid( $meta_id );
				}
			}
			elseif ( strstr( $meta_id, 'new-' ) ) {
				$item->add_meta_data( $meta_key, $meta_value, FALSE );
			}
			else {
				$item->update_meta_data( $meta_key, $meta_value, (string) $meta_id ); // NOTE: WC is doing a strict comparison for the meta_id and treating it as string.
			}

		}

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

		foreach ( $this->get_items( [ $this->line_item_type, 'fee' ] ) as $item_id => $item ) {

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
	 * @param bool $including_meta Optional. Whether to save the meta too.
	 *
	 * @return int order ID
	 */
	public function save( $including_meta = TRUE ) {

		// Trigger action before saving to the DB. Allows you to adjust object props before save.
		do_action( 'atum/order/before_object_save', $this );

		if ( $this->id ) {
			$this->update();
			$action = 'update';
		}
		else {
			$this->create();
			$action = 'create';
		}

		if ( $including_meta ) {
			$this->save_meta();
		}

		$this->process_status();
		$this->save_items();

		$this->after_save( $action );

		do_action( 'atum/order/after_object_save', $this );

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
					if ( absint( $item_id ) !== absint( $item_key ) ) {
						$this->items[ $item_group ][ $item_id ] = $item;
						unset( $this->items[ $item_group ][ $item_key ] ); // Remove old item to avoid duplicates.
					}

				}

				$this->clear_caches();

			}

		}

	}
	
	/**
	 * Process status changes
	 *
	 * @since 1.5.0
	 */
	public function process_status() {
		
		$new_status = $this->get_status();
		
		// if ! $new_status, order is still being created, so there aren't status changes.
		if ( $new_status ) {
			
			$old_status = $this->db_status;
			$statuses   = Helpers::get_atum_order_post_type_statuses( $this->get_post_type() );
			
			// If the old status is set but unknown (e.g. draft) assume its pending for action usage.
			if ( ! $old_status || ( $old_status && ! in_array( $old_status, array_keys( $statuses ) ) && ! in_array( $old_status, [ 'trash', 'any', 'auto-draft' ] ) ) ) {
				$old_status = 'atum_pending';
			}
			
			if ( $new_status !== $old_status ) {
				
				do_action( "atum/orders/status_$new_status", $this->id, $this );
				do_action( "atum/orders/status_{$old_status}_to_$new_status", $this->get_id(), $this );
				do_action( 'atum/orders/status_changed', $this->id, $old_status, $new_status, $this );
				
				/* translators: 1: old order status 2: new order status */
				$transition_note = sprintf( __( 'Order status changed from %1$s to %2$s.', ATUM_TEXT_DOMAIN ), $statuses[ $old_status ], $statuses[ $new_status ] );
				$note_id         = $this->add_order_note( $transition_note );
				Helpers::save_order_note_meta( $note_id, [
					'action'     => 'order_status_change',
					'old_status' => $old_status,
					'new_status' => $new_status,
				] );
				
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

			$currency = $this->currency;
			$this->set_currency( $currency && ! is_wp_error( $currency ) ? $currency : get_woocommerce_currency() );
			$status       = $this->get_status();
			$timestamp    = Helpers::get_current_timestamp();
			$date_created = Helpers::get_wc_time( $this->date_created ?: $timestamp );
			$this->set_date_created( $date_created );

			$id = wp_insert_post( apply_filters( 'atum/orders/new_order_data', array(
				'post_date'     => gmdate( 'Y-m-d H:i:s', $date_created->getOffsetTimestamp() ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $date_created->getTimestamp() ),
				'post_type'     => $this->get_post_type(),
				'post_status'   => in_array( $status, array_keys( Helpers::get_atum_order_post_type_statuses( $this->get_post_type() ) ) ) ? $status : ATUM_PREFIX . 'pending',
				'ping_status'   => 'closed',
				'post_author'   => get_current_user_id(),
				'post_title'    => $this->get_title(),
				'post_content'  => $this->get_description(),
				'post_password' => uniqid( ATUM_PREFIX . 'order_' ),
			) ), TRUE );

			if ( $id && ! is_wp_error( $id ) ) {
				$this->id = $id;
				$this->load_post();
				$this->clear_caches();
			}

		} catch ( \Exception $e ) {

			if ( ATUM_DEBUG ) {
				error_log( __METHOD__ . '::' . $e->getCode() . '::' . $e->getMessage() );
			}

		}

	}

	/**
	 * Update an ATUM Order in database
	 *
	 * @since 1.2.4
	 */
	public function update() {

		$status = $this->status;

		// Prevent creating the ATUM order when saving items when this order is in draft status.
		if ( 'auto-draft' === $this->db_status && '' === $status ) {
			return;
		}

		$date = $this->date_created;

		if ( ! $date ) {
			$date = ( ! empty( $this->post ) && $this->post->post_date ) ? $this->post->post_date : date_i18n( 'Y-m-d H:i:s' );
		}

		$date_created = Helpers::get_wc_time( $date );
		
		if ( ! empty( $this->post->post_date ) && $this->post->post_date !== $date ) {
			// Empty the post title to be updated by the get_title() method.
			$this->post->post_title = '';
		}

		$post_data = array(
			'post_date'         => gmdate( 'Y-m-d H:i:s', $date_created->getOffsetTimestamp() ),
			'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $date_created->getTimestamp() ),
			'post_status'       => in_array( $status, array_keys( Helpers::get_atum_order_post_type_statuses( $this->get_post_type() ) ) ) ? $status : ATUM_PREFIX . 'pending',
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
		if ( doing_action( "save_post_{$this->get_post_type()}" ) ) {
			global $wpdb;
			$wpdb->update( $wpdb->posts, $post_data, [ 'ID' => $this->id ] );
			clean_post_cache( $this->id );
		}
		else {
			wp_update_post( array_merge( [ 'ID' => $this->id ], $post_data ) );
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

		$statuses = Helpers::get_atum_order_post_type_statuses( $this->get_post_type() );

		// Only allow valid new status.
		if ( ! in_array( $new_status, array_keys( $statuses ) ) && 'trash' !== $new_status ) {
			$new_status = 'atum_pending';
		}

		do_action( 'atum/atum_order_model/update_status', $this, $new_status );

		$this->set_status( $new_status );
		$this->save();
		
	}

	/**
	 * Method to delete an ATUM order from the database.
	 *
	 * @since 1.6.2
	 *
	 * @param bool $force_delete Whether to skip the trash and remove the order definitely.
	 *
	 * @return void
	 */
	public function delete( $force_delete = FALSE ) {

		if ( ! $this->id ) {
			return;
		}

		if ( $force_delete ) {

			// Delete all associated the order items + meta.
			do_action( 'atum/orders/delete_order_items', $this->id );
			$this->delete_items();

			wp_delete_post( $this->id );
			$this->id = 0;
			do_action( 'atum/orders/delete_order', $this );

		}
		else {
			wp_trash_post( $this->id );
			$this->set_status( 'trash' );
			do_action( 'atum/orders/trash_order', $this );
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
			$default_country  = wc_get_base_location();
			$args['country']  = $default_country['country'];
			$args['state']    = $default_country['state'];
			$args['postcode'] = WC()->countries->get_base_postcode();
			$args['city']     = WC()->countries->get_base_city();
		}

		// Calc taxes for line items.
		foreach ( $this->get_items( [ $this->line_item_type, 'fee' ] ) as $item_id => $item ) {

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

				if ( $item->is_type( $this->line_item_type ) ) {

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

		return $this->shipping_total;
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

		$subtotal     = 0;
		$total        = 0;
		$fee_total    = 0;
		$subtotal_tax = 0;
		$total_tax    = 0;

		if ( $and_taxes ) {
			$this->calculate_taxes();
		}

		do_action( 'atum/purchase_orders/before_calculate_item_totals', $this );

		// Line items.
		foreach ( $this->get_items() as $item ) {
			$subtotal     += (float) $item->get_subtotal();
			$total        += (float) $item->get_total();
			$subtotal_tax += (float) $item->get_subtotal_tax();
			$total_tax    += (float) $item->get_total_tax();
		}

		$this->calculate_shipping();

		foreach ( $this->get_fees() as $item ) {
			$fee_total += (float) $item->get_total();
		}

		// Consider ATUM Order models that don't support shipping.
		$shipping_total = ! is_wp_error( $this->shipping_total ) ? (float) $this->shipping_total : 0;
		$shipping_tax   = ! is_wp_error( $this->shipping_tax ) ? (float) $this->shipping_tax : 0;

		/* @noinspection PhpWrongStringConcatenationInspection */
		$grand_total = round( $total + $fee_total + $shipping_total + (float) $this->cart_tax + $shipping_tax, wc_get_price_decimals() );

		$this->set_discount_total( $subtotal - $total );
		$this->set_discount_tax( $subtotal_tax - $total_tax );
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
			
			$qty = ! empty( $item->get_quantity() ) ? $item->get_quantity() : 1;

			if ( $inc_tax ) {
				$subtotal = ( $item->get_subtotal() + $item->get_subtotal_tax() ) / $qty;
			}
			else {
				$subtotal = ( floatval( $item->get_subtotal() ) / $qty );
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

			$qty = ! empty( $item->get_quantity() ) ? $item->get_quantity() : 1;

			if ( $inc_tax ) {
				/* @noinspection PhpWrongStringConcatenationInspection */
				$total = ( $item->get_total() + $item->get_total_tax() ) / $qty;
			}
			else {
				$total = floatval( $item->get_total() ) / $qty;
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

		$total_discount = $this->discount_total;

		if ( ! $ex_tax ) {
			$total_discount += $this->discount_tax;
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

		return apply_filters( 'atum/orders/get_subtotal', (float) $subtotal, $this );

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
			$tax_totals[ $code ]->formatted_amount = wc_price( wc_round_tax_total( $tax_totals[ $code ]->amount ), [ 'currency' => $this->currency ] );

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

		$amount          = $subtotal ? $this->get_subtotal() : $this->total;
		$formatted_total = wc_price( $amount, array( 'currency' => $this->currency ) );
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
				$tax_amount         = $this->total_tax;
				$tax_string_array[] = sprintf( '%s %s', wc_price( $tax_amount, [ 'currency' => $this->currency ] ), WC()->countries->tax_or_vat() );
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
		
		return apply_filters( 'atum/orders/is_editable', ! $status || 'auto-draft' === $status || array_key_exists( $status, Helpers::get_atum_order_post_type_statuses( $this->get_post_type(), TRUE ) ) );
	}

	/**
	 * Adds a note (comment) to the ATUM order. Order must exist
	 *
	 * @since 1.2.9
	 *
	 * @param string $note           Note to add.
	 * @param bool   $added_by_user  Optional. Whether the note was added manually by the user.
	 *
	 * @return int   Comment ID
	 */
	public function add_order_note( $note, $added_by_user = FALSE ) {

		if ( ! $this->id || ( is_user_logged_in() && ! AtumCapabilities::current_user_can( 'create_order_notes' ) ) ) {
			return 0;
		}

		if ( is_user_logged_in() && current_user_can( 'edit_shop_order', $this->get_id() ) && $added_by_user ) {
			$user                 = get_user_by( 'id', get_current_user_id() );
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;
		}
		else {
			$comment_author        = 'ATUM';
			$comment_author_email  = ATUM_SHORT_NAME . '@';
			$comment_author_email .= isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', wc_clean( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : 'noreply.com'; // WPCS: input var ok.
			$comment_author_email  = sanitize_email( $comment_author_email );
		}

		$commentdata = apply_filters( 'atum/orders/note_data', array(
			'comment_post_ID'      => $this->id,
			'comment_author'       => $comment_author,
			'comment_author_email' => $comment_author_email,
			'comment_author_url'   => '',
			'comment_content'      => $note,
			'comment_agent'        => 'ATUM',
			'comment_type'         => AtumComments::NOTES_KEY,
			'comment_parent'       => 0,
			'comment_approved'     => 1,
		), $this->id );

		$comment_id = wp_insert_comment( $commentdata );

		do_action( 'atum/orders/after_note_added', $comment_id, $this->id );

		return $comment_id;

	}

	/**
	 * Read the ATUM Order's meta data from db
	 *
	 * @since 1.7.1
	 */
	public function read_meta() {

		if ( $this->post ) {

			// Get the all the values from the meta table.
			$meta_data = get_metadata( 'post', $this->id, '', TRUE );

			foreach ( $meta_data as $meta_key => $meta_value ) {

				$meta_key_name = ltrim( $meta_key, '_' );
				$setter        = "set_$meta_key_name";

				// Make sure the setter exists for the current meta and the meta is allowed by the current model.
				if ( is_callable( array( $this, $setter ) ) && array_key_exists( $meta_key_name, $this->meta ) ) {
					// When reading the values, make sure there is no change registered.
					$this->$setter( is_array( $meta_value ) ? current( $meta_value ) : $meta_value, TRUE );
				}

			}

		}

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
			return isset( $this->meta[ $meta_key ] ) ? $this->meta[ $meta_key ] : get_post_meta( $this->id, $meta_key, $single );
		}
		else {
			return $this->meta;
		}

	}

	/**
	 * Register any change done to any data field
	 *
	 * @since 0.1.0
	 *
	 * @param string $meta_field
	 */
	protected function register_change( $meta_field ) {

		if ( ! in_array( $meta_field, $this->changes ) ) {
			$this->changes[] = $meta_field;
		}

	}

	/**
	 * Sets the meta key for the current ATUM Order
	 *
	 * @since 1.2.9
	 *
	 * @param string|array $meta_key    The meta key name or an array of meta_key => meta_value pairs.
	 * @param mixed        $meta_value  Optional. Only needed for settings single metas. The array should contain the values.
	 */
	protected function set_meta( $meta_key, $meta_value = NULL ) {

		if ( is_array( $meta_key ) ) {
			$this->meta = array_merge( $this->meta, $meta_key );
		}
		else {
			$this->meta[ $meta_key ] = $meta_value;
		}

	}

	/**
	 * Set multiple meta props at once
	 *
	 * @since 1.7.1
	 *
	 * @param array $meta_props
	 */
	public function set_props( array $meta_props ) {

		foreach ( $meta_props as $meta_key => $meta_value ) {

			if ( is_callable( array( $this, "set_$meta_key" ) ) ) {
				call_user_func( array( $this, "set_$meta_key" ), $meta_value );
			}

		}

	}

	/**
	 * Update all the previously-set meta fields to the current order post
	 *
	 * @since 1.6.2
	 */
	public function save_meta() {

		// Update only the changes.
		foreach ( $this->changes as $meta_key ) {

			do_action( "atum/order/before_save_meta$meta_key", $this->meta[ $meta_key ], $this );

			$meta_key_name = '_' !== substr( $meta_key, 0, 1 ) ? "_$meta_key" : $meta_key;
			update_post_meta( $this->id, $meta_key_name, $this->meta[ $meta_key ] );

			do_action( "atum/order/after_save_meta$meta_key", $this->meta[ $meta_key ], $this );

		}

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
		$cache_key = AtumCache::get_cache_key( $this->cache_key, $this->id );
		AtumCache::delete_cache( $cache_key );

	}

	/**
	 * Do stuff after saving an ATUM Order
	 *
	 * @since 1.5.8
	 *
	 * @param string $action
	 */
	abstract public function after_save( $action );

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
	 * Getter to collect all the ATUM Order data within an array
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_data() {

		// Prepare the data array based on the \WC_Order_Data structure (some unneeded data was excluded).
		$data = array(
			'id'                 => $this->id,
			'status'             => $this->status,
			'currency'           => $this->currency ?: get_woocommerce_currency(),
			'prices_include_tax' => metadata_exists( 'post', $this->id, '_prices_include_tax' ) ? 'yes' === $this->prices_include_tax : 'yes' === get_option( 'woocommerce_prices_include_tax' ),
			'date_created'       => wc_string_to_datetime( $this->date_created ),
			'date_modified'      => wc_string_to_datetime( $this->post->post_modified ),
			'discount_total'     => $this->discount_total,
			'discount_tax'       => $this->discount_tax,
			'shipping_total'     => $this->shipping_total,
			'shipping_tax'       => $this->shipping_tax,
			'cart_tax'           => $this->cart_tax,
			'total'              => $this->total,
			'total_tax'          => $this->total_tax,
			'date_completed'     => $this->date_completed ? wc_string_to_datetime( $this->date_completed ) : '',
			'line_items'         => $this->get_items(),
			'tax_lines'          => $this->get_items( 'tax' ),
			'shipping_lines'     => $this->get_items( 'shipping' ),
			'fee_lines'          => $this->get_items( 'fee' ),
			'description'        => $this->post->post_content,
		);

		return apply_filters( 'atum/orders/data', $data, $this->get_post_type() );

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
			$post_title = sprintf( __( 'ATUM Order &ndash; %s', ATUM_TEXT_DOMAIN ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'ATUM Order date parsed by strftime', ATUM_TEXT_DOMAIN ), strtotime( $this->date_created ) ) ); // phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText
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

		$status = $this->get_meta( 'status' ); // NOTE: Using the __get magic method within a getter is not allowed.
		$status = ( $status && strpos( $status, ATUM_PREFIX ) !== 0 && ! in_array( $status, [ 'trash', 'any', 'auto-draft' ], TRUE ) ) ? ATUM_PREFIX . $status : $status;

		if ( ! $status && ! empty( $this->post->post_status ) ) {
			$status = $this->post->post_status;
		}

		return $status;
	}

	/**
	 * Return an array of items within this ATUM Order
	 *
	 * @since 1.2.9
	 *
	 * @param string|array $types Optional. Types of line items to get (array or string).
	 *
	 * @return POItemProduct[]|LogItemProduct[]|POItemFee[]|LogItemFee[]|POItemShipping[]|LogItemShipping[]
	 */
	public function get_items( $types = NULL ) {

		if ( ! $types ) {
			$types = $this->line_item_type;
		}

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
	 * Get ATUM order's post type
	 *
	 * @since 1.4.16
	 *
	 * @return string
	 */
	abstract public function get_post_type();

	/**
	 * Get an ATUM Order item
	 *
	 * @since 1.2.9
	 *
	 * @param AtumOrderItemFee|AtumOrderItemProduct|AtumOrderItemShipping|AtumOrderItemTax|object|int $item
	 *
	 * @return AtumOrderItemFee|AtumOrderItemProduct|AtumOrderItemShipping|AtumOrderItemTax|false
	 */
	abstract public function get_atum_order_item( $item = NULL );

	/**
	 * Get key for where a certain item type is stored in items prop
	 *
	 * @since  1.2.9
	 *
	 * @param \WC_Order_Item|AtumOrderItemFee|AtumOrderItemProduct|AtumOrderItemShipping|AtumOrderItemTax $item  ATUM Order item object (product, shipping, fee, tax).
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
	 * @return \WC_Order_Item|AtumOrderItemFee|AtumOrderItemProduct|AtumOrderItemShipping|AtumOrderItemTax|bool
	 */
	public function get_item( $item_id, $type = NULL ) {

		if ( ! $type ) {
			$type = $this->line_item_type;
		}

		$type_group = $this->type_to_group( $type );

		if ( ! empty( $this->items ) && isset( $this->items[ $type_group ], $this->items[ $type_group ][ $item_id ] ) ) {
			return $this->items[ $type_group ][ $item_id ];
		}

		return FALSE;

	}

	/**
	 * Getter for the changes prop
	 *
	 * @since 1.7.1
	 *
	 * @return array
	 */
	public function get_changes() {
		return $this->changes;
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
	 * Calculate fees for all line items.
	 *
	 * @since 1.7.0
	 *
	 * @return float Fee total.
	 */
	public function get_total_fees() {
		return array_reduce(
			$this->get_fees(),
			function( $carry, $item ) {
				return $carry + (float) $item->get_total();
			}
		);
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

			$product = $item->get_product();

			if ( $product instanceof \WC_Product ) {
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
	
	/**
	 * Get the block items message
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_block_message() {
		return $this->block_message;
	}

	/**
	 * Getter for the line item type prop
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_line_item_type() {
		return $this->line_item_type;
	}

	/**
	 * Getter for the line item group prop
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_line_item_group() {
		return $this->line_item_group;
	}

	/**
	 * Check whether the post for the current ATUM order does exist
	 *
	 * @since 1.8.8
	 */
	public function exists() {
		return $this->id && $this->post;
	}

	/**********
	 * SETTERS
	 **********/

	/**
	 * Setter for the ID prop
	 *
	 * @since 1.8.2
	 *
	 * @param int $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Set the ATUM Order date
	 *
	 * @since 1.6.2
	 *
	 * @param string|\WC_DateTime $date_created
	 * @param bool                $skip_change
	 */
	public function set_date_created( $date_created, $skip_change = FALSE ) {

		$date_created = $date_created instanceof \WC_DateTime ? $date_created->date_i18n( 'Y-m-d H:i:s' ) : wc_clean( $date_created );

		// Only register the change if it was manually changed.
		if ( $date_created !== $this->date_created || ( $this->post && $this->post->post_date !== $date_created ) ) {

			if ( ! $skip_change ) {
				$this->register_change( 'date_created' );
			}

			$this->set_meta( 'date_created', $date_created );
		}

	}

	/**
	 * Set the ATUM Order completion date
	 *
	 * @since 1.8.7
	 *
	 * @param string|\WC_DateTime $date_completed
	 * @param bool                $skip_change
	 */
	public function set_date_completed( $date_completed, $skip_change = FALSE ) {

		$date_completed = $date_completed instanceof \WC_DateTime ? $date_completed->date_i18n( 'Y-m-d H:i:s' ) : wc_clean( $date_completed );

		// Only register the change if it was manually changed.
		if ( $date_completed !== $this->date_completed ) {

			if ( ! $skip_change ) {
				$this->register_change( 'date_completed' );
			}

			$this->set_meta( 'date_completed', $date_completed );
		}

	}

	/**
	 * Set ATUM Order currency
	 *
	 * @since 1.2.9
	 *
	 * @param string $currency
	 * @param bool   $skip_change
	 */
	public function set_currency( $currency, $skip_change = FALSE ) {

		$currency = wc_clean( $currency );

		if ( ! array_key_exists( $currency, get_woocommerce_currencies() ) ) {
			$currency = get_woocommerce_currency();
		}

		if ( $currency !== $this->currency ) {

			if ( ! $skip_change ) {
				$this->register_change( 'currency' );
			}

			$this->set_meta( 'currency', $currency );
		}

	}

	/**
	 * Set total
	 *
	 * @since 1.2.9
	 *
	 * @param float $total
	 * @param bool  $skip_change
	 */
	public function set_total( $total, $skip_change = FALSE ) {

		$total = wc_format_decimal( $total );

		if ( $total !== $this->total ) {

			if ( ! $skip_change ) {
				$this->register_change( 'total' );
			}

			$this->set_meta( 'total', $total );
		}

	}

	/**
	 * Set discount total
	 *
	 * @since 1.2.9
	 *
	 * @param float $discount_total
	 * @param bool  $skip_change
	 */
	public function set_discount_total( $discount_total, $skip_change = FALSE ) {

		$discount_total = wc_format_decimal( $discount_total );

		if ( $discount_total !== $this->discount_total ) {

			if ( ! $skip_change ) {
				$this->register_change( 'discount_total' );
			}

			$this->set_meta( 'discount_total', $discount_total );
		}

	}

	/**
	 * Set discount tax
	 *
	 * @since 1.2.9
	 *
	 * @param float $discount_tax
	 * @param bool  $skip_change
	 */
	public function set_discount_tax( $discount_tax, $skip_change = FALSE ) {

		$discount_tax = wc_format_decimal( $discount_tax );

		if ( $discount_tax !== $this->discount_tax ) {

			if ( ! $skip_change ) {
				$this->register_change( 'discount_tax' );
			}

			$this->set_meta( 'discount_tax', $discount_tax );
		}

	}

	/**
	 * Set shipping total
	 *
	 * @since 1.2.9
	 *
	 * @param float $shipping_total
	 * @param bool  $skip_change
	 */
	public function set_shipping_total( $shipping_total, $skip_change = FALSE ) {

		$shipping_total = wc_format_decimal( $shipping_total );

		if ( $shipping_total !== $this->shipping_total ) {

			if ( ! $skip_change ) {
				$this->register_change( 'shipping_total' );
			}

			$this->set_meta( 'shipping_total', $shipping_total );
		}

	}

	/**
	 * Set shipping tax
	 *
	 * @since 1.2.9
	 *
	 * @param float $shipping_tax
	 * @param bool  $skip_change
	 */
	public function set_shipping_tax( $shipping_tax, $skip_change = FALSE ) {

		$shipping_tax = wc_format_decimal( $shipping_tax );

		if ( $shipping_tax !== $this->shipping_tax ) {

			if ( ! $skip_change ) {
				$this->register_change( 'shipping_tax' );
			}

			$this->set_meta( 'shipping_tax', $shipping_tax );
			$this->set_total_tax( (float) $this->cart_tax + (float) $shipping_tax, $skip_change );
		}

	}

	/**
	 * Set cart tax
	 *
	 * @since 1.2.9
	 *
	 * @param float $cart_tax
	 * @param bool  $skip_change
	 */
	public function set_cart_tax( $cart_tax, $skip_change = FALSE ) {

		$cart_tax = wc_format_decimal( $cart_tax );

		if ( $cart_tax !== $this->cart_tax ) {

			if ( ! $skip_change ) {
				$this->register_change( 'cart_tax' );
			}

			$this->set_meta( 'cart_tax', $cart_tax );
			$this->set_total_tax( (float) $this->shipping_tax + (float) $cart_tax, $skip_change );
		}

	}

	/**
	 * Sets tax (sum of cart and shipping tax)
	 * Used internally only
	 *
	 * @since 1.2.9
	 *
	 * @param float $total_tax
	 * @param bool  $skip_change
	 */
	protected function set_total_tax( $total_tax, $skip_change = FALSE ) {

		$total_tax = wc_format_decimal( $total_tax );

		if ( $total_tax !== $this->total_tax ) {

			if ( ! $skip_change ) {
				$this->register_change( 'total_tax' );
			}

			$this->set_meta( 'total_tax', $total_tax );
		}

	}

	/**
	 * Set status
	 *
	 * @since 1.2.9
	 *
	 * @param string $status
	 * @param bool   $skip_change
	 */
	public function set_status( $status, $skip_change = FALSE ) {

		if ( $status && strpos( $status, ATUM_PREFIX ) !== 0 && ! in_array( $status, [ 'trash', 'any', 'auto-draft' ], TRUE ) ) {
			$status = ATUM_PREFIX . $status;
		}

		$status = wc_clean( $status );

		if ( ! $status && ! $this->status && ! empty( $this->post->post_status ) ) {
			$status = $this->post->post_status;
		}

		if ( $status !== $this->status ) {

			if ( ! $skip_change ) {
				$this->register_change( 'status' );
			}

			$this->set_meta( 'status', $status );
		}

	}

	/**
	 * Set created via
	 *
	 * @since 1.6.2
	 *
	 * @param string $created_via
	 * @param bool   $skip_change
	 */
	public function set_created_via( $created_via, $skip_change = FALSE ) {

		$created_via = wc_clean( $created_via );

		if ( $created_via !== $this->created_via ) {

			if ( ! $skip_change ) {
				$this->register_change( 'created_via' );
			}

			$this->set_meta( 'created_via', $created_via );
		}

	}

	/**
	 * Set prices_include_tax meta
	 *
	 * @since 1.6.2
	 *
	 * @param bool $prices_include_tax
	 * @param bool $skip_change
	 */
	public function set_prices_include_tax( $prices_include_tax, $skip_change = FALSE ) {

		$prices_include_tax = wc_bool_to_string( $prices_include_tax );

		if ( $prices_include_tax !== $this->prices_include_tax ) {

			if ( ! $skip_change ) {
				$this->register_change( 'prices_include_tax' );
			}

			$this->set_meta( 'prices_include_tax', $prices_include_tax );
		}

	}

	/**
	 * Set description
	 *
	 * @since 1.2.9
	 *
	 * @param string $value
	 * @param bool   $skip_change
	 */
	public function set_description( $value, $skip_change = FALSE ) {

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


	/***************
	 * MAGIC METHODS
	 ***************/

	/**
	 * Magic Getter (used for meta)
	 * To avoid illegal access errors, the property being accessed must be declared within data or meta prop arrays
	 *
	 * @since 1.7.1
	 *
	 * @param string $name
	 *
	 * @return mixed|\WP_Error
	 */
	public function __get( $name ) {

		// Sometimes a prop requires custom logic and needs to have its own method.
		if ( is_callable( array( $this, "get_$name" ) ) ) {
			return call_user_func( array( $this, "get_$name" ) );
		}

		// Search in declared class props.
		if ( isset( $this->$name ) ) {
			return $this->$name;
		}

		// Search in props array.
		if ( array_key_exists( $name, $this->meta ) ) {
			return $this->meta[ $name ];
		}

		return new \WP_Error( __( 'Invalid property', ATUM_TEXT_DOMAIN ) );

	}

	/**
	 * Magic Unset
	 *
	 * @since 1.7.1
	 *
	 * @param string $name
	 */
	public function __unset( $name ) {

		if ( isset( $this->$name ) ) {
			unset( $this->$name );
		}
		elseif ( array_key_exists( $name, $this->meta ) ) {
			unset( $this->meta[ $name ] );
		}

	}

}
