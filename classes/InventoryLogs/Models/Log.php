<?php
/**
 * @package         Atum\InventoryLogs
 * @subpackage      Models
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * The model class for the Log objects
 */

namespace Atum\InventoryLogs\Models;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumException;
use Atum\Components\AtumModel;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\InventoryLogs;
use Atum\InventoryLogs\Items\LogItemFee;
use Atum\InventoryLogs\Items\LogItemProduct;
use Atum\InventoryLogs\Items\LogItemShipping;
use Atum\InventoryLogs\Items\LogItemTax;


class Log extends AtumModel {

	/**
	 * The WP post linked to this object
	 * @var \WP_Post
	 */
	protected $post;

	/**
	 * The array of items belonging to this Log
	 * @var array
	 */
	protected $items = [];

	/**
	 * Log items that need deleting are stored here
	 * @var array
	 */
	protected $items_to_delete = array();

	/**
	 * The available log item types
	 * @var array
	 */
	protected $item_types = [ 'line_item', 'tax', 'shipping', 'fee' ];

	/**
	 * Log constructor
	 *
	 * @param int $id   Optional. The object ID for initialization
	 */
	public function __construct( $id = 0 ) {

		parent::__construct($id);

		if ($this->id) {
			$this->load_post();
			$this->read_items();
		}

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
	 * Read Log Items of a specific type from the database for this Log
	 *
	 * @since 1.2.4
	 *
	 * @param  string $type Optional. Filter by item type
	 *
	 * @return array
	 */
	public function read_items( $type = ''  ) {

		global $wpdb;

		// Get from cache if available
		$items = wp_cache_get( 'log-items-' . $this->id, 'inventory-logs' );

		if ( FALSE === $items ) {

			$query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}atum_log_items WHERE log_id = %d ORDER BY log_item_id", $this->id );
			$items = $wpdb->get_results( $query );

			if (! $items) {
				return array();
			}

			foreach ( $items as $item ) {
				wp_cache_set( 'item-' . $item->log_item_id, $item, 'inventory-log-items' );
			}

			wp_cache_set( 'log-items-' . $this->id, $items, 'inventory-logs' );

		}

		if ($type) {
			$items = wp_list_filter( $items, array( 'log_item_type' => $type ) );
		}

		$log_items =  array_map( array( $this, 'get_log_item' ), array_combine( wp_list_pluck( $items, 'log_item_id' ), $items ) );

		if ( ! empty($log_items) ) {
			foreach ( $log_items as $log_item ) {
				$this->add_item( $log_item );
			}
		}

	}

	/**
	 * Remove all line items (products, coupons, shipping, taxes) from the log
	 *
	 * @since 1.2.4
	 *
	 * @param string $type Log item type. Default null
	 */
	public function delete_items( $type = NULL ) {

		global $wpdb;

		if ( ! empty( $type ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}atum_log_itemmeta itemmeta INNER JOIN {$wpdb->prefix}atum_log_items items WHERE itemmeta.log_item_id = items.log_item_id AND items.log_id = %d AND items.log_item_type = %s", $this->id, $type ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}atum_log_items WHERE log_id = %d AND log_item_type = %s", $this->id, $type ) );
		}
		else {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}atum_log_itemmeta itemmeta INNER JOIN {$wpdb->prefix}atum_log_items items WHERE itemmeta.log_item_id = items.log_item_id AND items.log_id = %d", $this->id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}atum_log_items WHERE log_id = %d", $this->id ) );
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

		$type_to_group = (array) apply_filters( 'atum/inventory_logs/log/item_type_to_group', array(
			'line_item' => 'line_items',
			'tax'       => 'tax_lines',
			'shipping'  => 'shipping_lines',
			'fee'       => 'fee_lines'
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

		$group_to_type = (array) apply_filters( 'atum/inventory_logs/log/item_group_to_type', array(
			'line_items'     => 'line_item',
			'tax_lines'      => 'tax',
			'shipping_lines' => 'shipping',
			'fee_lines'      => 'fee'
		) );

		return isset( $group_to_type[ $group ] ) ? $group_to_type[ $group ] : '';

	}

	/**
	 * Adds an item to this log. The Log Item will not persist until save
	 *
	 * @since 1.2.4
	 *
	 * @param \WC_Order_Item $item  Order item object (product, shipping, fee, coupon, tax)
	 *
	 * @return void|bool
	 */
	public function add_item( $item ) {

		if ( ! $items_key = $this->type_to_group( $item->get_type() ) ) {
			return FALSE;
		}

		// Make sure existing items are loaded so we can append this new one
		if ( ! isset( $this->items[ $items_key ] ) ) {
			$this->items[ $items_key ] = $this->get_items( $item->get_type() );
		}

		// Set parent
		$item->set_log_id( $this->id );

		// Append new item with generated temporary ID
		if ( $item_id = $item->get_id() ) {
			$this->items[ $items_key ][ $item_id ] = $item;
		}
		else {
			$this->items[ $items_key ][ 'new:' . $items_key . sizeof( $this->items[ $items_key ] ) ] = $item;
		}

	}

	/**
	 * Add a product line item to the log
	 *
	 * @since 1.2.4
	 *
	 * @param  \WC_Product  $product
	 * @param  int          $qty
	 * @param  array        $args
	 *
	 * @return LogItemProduct The product item added to Log
	 */
	public function add_product( $product, $qty = 1, $args = array() ) {

		if ( is_a( $product, '\WC_Product' )  ) {

			$default_args = array(
				'name'         => $product->get_name(),
				'tax_class'    => $product->get_tax_class(),
				'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
				'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
				'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
				'subtotal'     => wc_get_price_excluding_tax( $product, array( 'qty' => $qty ) ),
				'total'        => wc_get_price_excluding_tax( $product, array( 'qty' => $qty ) ),
				'quantity'     => $qty
			);

		}
		else {

			$default_args = array(
				'quantity' => $qty
			);

		}

		$args = wp_parse_args( $args, $default_args );
		$item = new LogItemProduct();
		$item->set_props( $args );
		$item->set_backorder_meta();
		$item->set_log_id( $this->id );
		$item->save();
		$this->add_item( $item, 'line_item' );

		return $item;

	}

	/**
	 * Add a fee item to the Log
	 *
	 * @since 1.2.4
	 *
	 * @param \WC_Order_Item_Fee $fee   Optional. Fee item to import
	 *
	 * @return LogItemFee  The fee item added to the Log
	 */
	public function add_fee ( \WC_Order_Item_Fee $fee = NULL ) {

		$item = new LogItemFee();
		$item->set_log_id( $this->id );

		if ($fee) {
			$item->set_tax_status( $fee->get_tax_status() );
			$item->set_taxes( $fee->get_taxes() );
			$item->set_tax_class( $fee->get_tax_class() );
			$item->set_total( $fee->get_total() );
		}

		$item->save();
		$this->add_item( $item, 'fee' );

		return $item;

	}

	/**
	 * Add a shipping cost item to the Log
	 *
	 * @since 1.2.4
	 *
	 * @param \WC_Order_Item_Shipping $shipping  Optional. Shipping cost item to import
	 *
	 * @return LogItemShipping  The shipping cost item added to the Log
	 */
	public function add_shipping_cost ( \WC_Order_Item_Shipping $shipping = NULL ) {

		$item = new LogItemShipping();
		$item->set_shipping_rate( new \WC_Shipping_Rate() );
		$item->set_log_id( $this->id );

		if ($shipping) {
			$item->set_method_id( $shipping->get_method_id() );
			$item->set_total( $shipping->get_total() );
			$item->set_taxes( $shipping->get_taxes() );
			$item->set_method_title( $shipping->get_method_title() );
		}

		$item->save();
		$this->add_item( $item, 'shipping' );

		return $item;

	}

	/**
	 * Add a tax item to the Log
	 *
	 * @since 1.2.4
	 *
	 * @param array $values {
	 *      The array of tax values to add to the created tax item
	 *
	 *      @type int    $rate_id            The tax rate ID
	 *      @type string $name               The tax item name
	 *      @type float  $tax_total          The tax total
	 *      @type float  $shipping_tax_total The shipping tax total
	 *
	 * }
	 * @param \WC_Order_Item_Tax $tax Optional. Tax item to import
	 *
	 * @return LogItemTax|bool  The tax item added to the Log or false if the required rate_id value is not passed
	 */
	public function add_tax ( array $values, \WC_Order_Item_Tax $tax = NULL ) {

		if ( empty( $values['rate_id'] ) ) {
			return FALSE;
		}

		$item = new LogItemTax();
		$item->set_rate( $values['rate_id'] );
		$item->set_log_id( $this->id );

		if ($tax) {
			$item->set_name( $tax->get_name() );
			$item->set_tax_total( $tax->get_tax_total() );
			$item->set_shipping_tax_total( $tax->get_shipping_tax_total() );
		}
		else {

			if ( isset($values['name']) ) {
				$item->set_name( $values['name'] );
			}

			if ( isset($values['tax_total']) ) {
				$item->set_tax_total( $values['tax_total'] );
			}

			if ( isset($values['shipping_tax_total']) ) {
				$item->set_shipping_tax_total( $values['shipping_tax_total'] );
			}

		}

		$item->save();
		$this->add_item( $item, 'tax' );

		return $item;

	}

	/**
	 * Remove item from the log
	 *
	 * @since 1.2.4
	 *
	 * @param int $item_id
	 *
	 * @return void|bool
	 */
	public function remove_item( $item_id ) {

		$item = $this->get_log_item( $item_id );

		if ( ! $item || ! ( $items_key = $this->get_items_key( $item ) ) ) {
			return FALSE;
		}

		// Unset and remove later
		$this->items_to_delete[] = $item;
		unset( $this->items[ $items_key ][ $item->get_id() ] );

	}

	/**
	 * Save log items. Uses the CRUD
	 *
	 * @since 1.2.4
	 *
	 * @param array $items      Order items to save
	 */
	public function save_log_items( $items ) {

		// Allow other plugins to check changes in log items before they are saved
		do_action( 'atum/inventory_logs/log/before_save_items', $this, $items );

		// Line items and fees
		if ( isset( $items['log_item_id'] ) ) {

			$data_keys = array(
				'line_tax'           => array(),
				'line_subtotal_tax'  => array(),
				'log_item_name'      => NULL,
				'log_item_qty'       => NULL,
				'log_item_tax_class' => NULL,
				'line_total'         => NULL,
				'line_subtotal'      => NULL
			);

			foreach ( $items['log_item_id'] as $item_id ) {

				if ( ! $item = $this->get_log_item( absint( $item_id ) ) ) {
					continue;
				}

				$item_data = array();

				foreach ( $data_keys as $key => $default ) {
					$item_data[ $key ] = ( isset( $items[ $key ][ $item_id ] ) ) ? wc_clean( wp_unslash( $items[ $key ][ $item_id ] ) ) : $default;
				}

				if ( '0' === $item_data['log_item_qty'] ) {
					$item->delete();
					continue;
				}

				$item->set_props( array(
					'name'         => $item_data['log_item_name'],
					'quantity'     => $item_data['log_item_qty'],
					'tax_class'    => $item_data['log_item_tax_class'],
					'total'        => $item_data['line_total'],
					'subtotal'     => $item_data['line_subtotal'],
					'taxes'        => array(
						'total'    => $item_data['line_tax'],
						'subtotal' => $item_data['line_subtotal_tax']
					)
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

				$this->add_item($item);
				$item->save();

			}

		}

		// Shipping Rows
		if ( isset( $items['shipping_method_id'] ) ) {

			$data_keys = array(
				'shipping_method'       => NULL,
				'shipping_method_title' => NULL,
				'shipping_cost'         => 0,
				'shipping_taxes'        => array()
			);

			foreach ( $items['shipping_method_id'] as $item_id ) {

				if ( ! $item = $this->get_log_item( absint( $item_id ) ) ) {
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
						'total'    => $item_data['shipping_taxes']
					)
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

				$this->add_item($item);
				$item->save();

			}

		}

		// Updates tax totals
		$this->update_taxes();

		// Calc totals - this also triggers save
		$this->calculate_totals( FALSE );

		// Inform other plugins that the items have been saved
		do_action( 'atum/inventory_logs/log/after_save_items', $this, $items );

	}

	/**
	 * Save log data to the database
	 *
	 * @since 1.2.4
	 *
	 * @return int order ID
	 */
	public function save() {

		// Trigger action before saving to the DB. Allows you to adjust object props before save
		do_action( 'atum/inventory_logs/log/before_object_save', $this );

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
	 * Save all the items which are part of this log
	 *
	 * @since 1.2.4
	 */
	public function save_items() {

		foreach ( $this->items_to_delete as $item ) {
			$item->delete();
		}
		$this->items_to_delete = array();

		// Add/save items
		foreach ( $this->items as $item_group => $items ) {

			if ( is_array( $items ) ) {

				foreach ( array_filter( $items ) as $item_key => $item ) {
					$item->set_log_id( $this->id );
					$item_id = $item->save();

					// If ID changed (new item saved to DB)...
					if ( $item_id !== $item_key ) {
						$this->items[ $item_group ][ $item_id ] = $item;
					}
				}

			}

		}

	}

	//-------------
	//
	// CRUD METHODS
	//
	//-------------

	/**
	 * Create a new log in the database
	 *
	 * @since 1.2.4
	 */
	public function create() {

		$this->set_currency( $this->get_currency() ? $this->get_currency() : get_woocommerce_currency() );
		$status = $this->get_status();

		$id = wp_insert_post( apply_filters( 'atum/inventory_logs/log/new_log_data', array(
			'post_date'     => gmdate( 'Y-m-d H:i:s' ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s' ),
			'post_type'     => InventoryLogs::POST_TYPE,
			'post_status'   => ( in_array($status, array_keys( self::get_statuses() )) ) ? ATUM_PREFIX . $status : 'publish',
			'ping_status'   => 'closed',
			'post_author'   => get_current_user_id(),
			'post_title'    => $this->get_title(),
			'post_content'  => $this->get_description(),
			'post_password' => uniqid( ATUM_PREFIX . 'log_' )
		) ), TRUE );

		if ( $id && ! is_wp_error($id) ) {
			$this->set_id( $id );
			$this->clear_caches();
		}

	}

	/**
	 * Update a log in the database
	 *
	 * @since 1.2.4
	 */
	public function update() {

		$status = $this->get_status();
		$date = $this->get_date();

		if ($this->post->post_date != $date) {
			// Empty the post title to be updated by the get_title() method
			$this->post->post_title = '';
		}

		$post_data = array(
			'post_date'         => $date,
			'post_date_gmt'     => $date,
			'post_status'       => ( in_array($status, array_keys( self::get_statuses() )) ) ? ATUM_PREFIX . $status : 'publish',
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', 1 ),
			'post_title'        => $this->get_title(),
			'post_content'      => $this->get_description()
		);

		/**
		 * When updating this object, to prevent infinite loops, use $wpdb
		 * to update data, since wp_update_post spawns more calls to the save_post action
		 *
		 * This ensures hooks are fired by either WP itself (admin screen save), or an update purely from CRUD
		 */
		if ( doing_action( 'save_post_' . InventoryLogs::POST_TYPE ) ) {
			$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $this->id ) );
			clean_post_cache( $this->id );
		}
		else {
			wp_update_post( array_merge( array( 'ID' => $this->id ), $post_data ) );
		}

		$this->clear_caches();

	}

	/**
	 * Update the log status
	 *
	 * @since 1.2.4
	 *
	 * @param string $new_status    Status to change the log to. No internal "atum_" prefix is required
	 */
	public function update_status( $new_status ) {

		$old_status = $this->get_status();
		$new_status = ( strpos($new_status, ATUM_PREFIX) !== FALSE )  ? str_replace(ATUM_PREFIX, '', $new_status) : $new_status;

		// Only allow valid new status
		if ( ! in_array( $new_status, array_keys( self::get_statuses() ) ) && 'trash' !== $new_status ) {
			$new_status = 'pending';
		}

		// If the old status is set but unknown (e.g. draft) assume its pending for action usage
		if ( $old_status && ! in_array( $old_status, array_keys( self::get_statuses() ) ) && 'trash' !== $old_status ) {
			$old_status = 'pending';
		}

		if ($new_status != $old_status) {
			$this->set_status( $new_status );
			$this->save();
		}

	}

	/**
	 * Update tax lines for the log based on the line item taxes themselves
	 *
	 * @since 1.2.4
	 */
	public function update_taxes() {

		$cart_taxes     = array();
		$shipping_taxes = array();
		$existing_taxes = $this->get_taxes();
		$saved_rate_ids = array();

		foreach ( $this->get_items( ['line_item', 'fee'] ) as $item_id => $item ) {

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

			// Remove taxes which no longer exist for cart/shipping
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

		// New taxes
		foreach ( $new_rate_ids as $tax_rate_id ) {

			$this->add_tax( array(
				'rate_id'            => $tax_rate_id,
				'tax_total'          => isset( $cart_taxes[ $tax_rate_id ] ) ? $cart_taxes[ $tax_rate_id ] : 0,
				'shipping_tax_total' => ! empty( $shipping_taxes[ $tax_rate_id ] ) ? $shipping_taxes[ $tax_rate_id ] : 0
			) );

		}

		// Save tax totals
		$this->set_shipping_tax( \WC_Tax::round( array_sum( $shipping_taxes ) ) );
		$this->set_cart_tax( \WC_Tax::round( array_sum( $cart_taxes ) ) );
		$this->save();

	}

	//--------------
	//
	// CALCULATIONS
	//
	//--------------

	/**
	 * Calculate taxes for all line items and shipping, and store the totals and tax rows
	 *
	 * @since 1.2.4
	 *
	 * @param $args array Optional. To pass things like location
	 */
	public function calculate_taxes( $args = array() ) {

		// For now we'll avoid calculating location based taxes
		/*$tax_based_on = get_option( 'woocommerce_tax_based_on' );
		$args         = wp_parse_args( $args, array(
			'country'  => 'billing' === $tax_based_on ? $this->get_billing_country()  : $this->get_shipping_country(),
			'state'    => 'billing' === $tax_based_on ? $this->get_billing_state()    : $this->get_shipping_state(),
			'postcode' => 'billing' === $tax_based_on ? $this->get_billing_postcode() : $this->get_shipping_postcode(),
			'city'     => 'billing' === $tax_based_on ? $this->get_billing_city()     : $this->get_shipping_city(),
		) );*/

		$tax_based_on = 'base';

		// Default to base
		if ( 'base' === $tax_based_on || empty( $args['country'] ) ) {
			$default          = wc_get_base_location();
			$args['country']  = $default['country'];
			$args['state']    = $default['state'];
			$args['postcode'] = '';
			$args['city']     = '';
		}

		// Calc taxes for line items
		foreach ( $this->get_items( ['line_item', 'fee'] ) as $item_id => $item ) {

			$tax_class  = $item->get_tax_class();
			$tax_status = $item->get_tax_status();

			if ( '0' !== $tax_class && 'taxable' === $tax_status && wc_tax_enabled() ) {

				$tax_rates = \WC_Tax::find_rates( array(
					'country'   => $args['country'],
					'state'     => $args['state'],
					'postcode'  => $args['postcode'],
					'city'      => $args['city'],
					'tax_class' => $tax_class
				) );

				$total = $item->get_total();
				$taxes = \WC_Tax::calc_tax( $total, $tax_rates, FALSE );

				if ( $item->is_type( 'line_item' ) ) {
					$subtotal = $item->get_subtotal();
					$subtotal_taxes = \WC_Tax::calc_tax( $subtotal, $tax_rates, FALSE );
					$item->set_taxes( array( 'total' => $taxes, 'subtotal' => $subtotal_taxes ) );
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

		// Calc taxes for shipping
		foreach ( $this->get_shipping_methods() as $item_id => $item ) {

			if ( wc_tax_enabled() ) {

				$shipping_tax_class = get_option( 'woocommerce_shipping_tax_class' );

				// Inherit tax class from items
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
	 * @since 2.2
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
	 * Calculate totals by looking at the contents of the log
	 * Stores the totals and returns the log's final total
	 *
	 * @since 1.2.4
	 *
	 * @param bool $and_taxes Optional. Calc taxes if true
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

		// line items
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

	//------------
	//
	// TOTALIZERS
	//
	//------------

	/**
	 * Get item subtotal - this is the cost before discount
	 *
	 * @since 1.2.4
	 *
	 * @param LogItemProduct $item
	 * @param bool           $inc_tax
	 * @param bool           $round
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

		return apply_filters( 'atum/inventory_logs/log/amount_item_subtotal', $subtotal, $this, $item, $inc_tax, $round );

	}

	/**
	 * Calculate item cost
	 *
	 * @since 1.2.4
	 *
	 * @param LogItemProduct $item
	 * @param bool           $inc_tax
	 * @param bool           $round
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

		return apply_filters( 'atum/inventory_logs/log/amount_item_total', $total, $this, $item, $inc_tax, $round );

	}

	/**
	 * Gets the total discount amount
	 *
	 * @since 1.2.4
	 *
	 * @param  bool $ex_tax  Optional. Show discount excl any tax
	 *
	 * @return float
	 */
	public function get_total_discount( $ex_tax = TRUE ) {

		$total_discount = $this->get_discount_total();

		if ( !$ex_tax ) {
			$total_discount += $this->get_discount_tax();
		}

		return apply_filters( 'atum/inventory_logs/log/get_total_discount', round( $total_discount, WC_ROUNDING_PRECISION ), $this );

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

		return apply_filters( 'atum/inventory_logs/log/get_subtotal', (double) $subtotal, $this );

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
				$tax_totals[ $code ] = new \stdClass();
				$tax_totals[ $code ]->amount = 0;
			}

			$tax_totals[ $code ]->id                = $key;
			$tax_totals[ $code ]->rate_id           = $tax->get_rate_id();
			$tax_totals[ $code ]->is_compound       = $tax->is_compound();
			$tax_totals[ $code ]->label             = $tax->get_label();
			$tax_totals[ $code ]->amount           += (float) $tax->get_tax_total() + (float) $tax->get_shipping_tax_total();
			$tax_totals[ $code ]->formatted_amount  = wc_price( wc_round_tax_total( $tax_totals[ $code ]->amount ), array( 'currency' => $this->get_currency() ) );

		}

		if ( apply_filters( 'atum/inventory_logs/log/hide_zero_taxes', TRUE ) ) {
			$amounts    = array_filter( wp_list_pluck( $tax_totals, 'amount' ) );
			$tax_totals = array_intersect_key( $tax_totals, $amounts );
		}

		return apply_filters( 'atum/inventory_logs/log/get_tax_totals', $tax_totals, $this );

	}

	/**
	 * Gets log total - formatted for display
	 *
	 * @since 1.2.4
	 *
	 * @param  string $tax_display          Type of tax display
	 * @param  bool   $display_refunded     Optional. If should include refunded value
	 *
	 * @return string
	 */
	public function get_formatted_total( $tax_display = '', $display_refunded = TRUE ) {

		$formatted_total = wc_price( $this->get_total(), array( 'currency' => $this->get_currency() ) );
		$log_total    = $this->get_total();
		//$total_refunded = $this->get_total_refunded();
		$tax_string     = '';

		// Tax for inclusive prices
		if ( wc_tax_enabled() && 'incl' == $tax_display ) {

			$tax_string_array = array();

			if ( 'itemized' == get_option( 'woocommerce_tax_total_display' ) ) {

				foreach ( $this->get_tax_totals() as $code => $tax ) {
					$tax_amount         = /*( $total_refunded && $display_refunded ) ? wc_price( \WC_Tax::round( $tax->amount - $this->get_total_tax_refunded_by_rate_id( $tax->rate_id ) ), array( 'currency' => $this->get_currency() ) ) :*/ $tax->formatted_amount;
					$tax_string_array[] = sprintf( '%s %s', $tax_amount, $tax->label );
				}

			}
			else {
				$tax_amount         = /*( $total_refunded && $display_refunded ) ? $this->get_total_tax() - $this->get_total_tax_refunded() :*/ $this->get_total_tax();
				$tax_string_array[] = sprintf( '%s %s', wc_price( $tax_amount, array( 'currency' => $this->get_currency() ) ), WC()->countries->tax_or_vat() );
			}

			if ( ! empty( $tax_string_array ) ) {
				$tax_string = ' <small class="includes_tax">' . sprintf( __( '(includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) ) . '</small>';
			}

		}

		/*if ( $total_refunded && $display_refunded ) {
			$formatted_total = '<del>' . strip_tags( $formatted_total ) . '</del> <ins>' . wc_price( $log_total - $total_refunded, array( 'currency' => $this->get_currency() ) ) . $tax_string . '</ins>';
		}
		else {*/
			$formatted_total .= $tax_string;
		//}

		return apply_filters( 'atum/inventory_logs/log/get_formatted_order_total', $formatted_total, $this, $tax_display, $display_refunded );

	}

	/**
	 * Checks if a log can be edited, specifically for use on the Edit Log screen
	 *
	 * @since 1.2.4
	 *
	 * @return bool
	 */
	public function is_editable() {
		$log_status = $this->get_status();
		return apply_filters( 'atum/inventory_logs/log/is_editable', !$log_status || $log_status == 'pending', $this );
	}

	/**
	 * Adds a note (comment) to the log. Log must exist
	 *
	 * @since 1.2.4
	 *
	 * @param string $note Note to add
	 *
	 * @return int   Comment ID
	 */
	public function add_note( $note ) {

		if ( ! $this->id || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
			return 0;
		}

		$user = get_user_by( 'id', get_current_user_id() );
		$comment_author = $user->display_name;
		$comment_author_email = $user->user_email;

		$commentdata = apply_filters( 'atum/inventory_logs/log/note_data', array(
			'comment_post_ID'      => $this->id,
			'comment_author'       => $comment_author,
			'comment_author_email' => $comment_author_email,
			'comment_author_url'   => '',
			'comment_content'      => $note,
			'comment_agent'        => 'ATUM',
			'comment_type'         => 'log_note',
			'comment_parent'       => 0,
			'comment_approved'     => 1,
		), $this->id );

		$comment_id = wp_insert_comment( $commentdata );

		do_action('atum/inventory_logs/log/after_note_added', $comment_id, $this->id);

		return $comment_id;

	}

	/**
	 * @inheritDoc
	 */
	public function get_meta( $meta_key = NULL, $single = TRUE ) {

		if ( $meta_key !== NULL ) { // get a single field
			return get_post_meta( $this->id, $meta_key, $single );
		}
		else {
			return get_post_custom( $this->id );
		}

	}

	/**
	 * @inheritDoc
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
	 * Sets the meta key for the current Log
	 *
	 * @since 1.2.4
	 *
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 */
	public function set_meta( $meta_key, $meta_value ) {
		update_post_meta( $this->id, $meta_key, $meta_value );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_meta( $meta ) {

		foreach ( $meta as $key => $value ) {
			delete_post_meta( $this->id, $key, $value );
		}

	}

	/**
	 * Clear any caches
	 *
	 * @since 1.2.4
	 */
	protected function clear_caches() {
		clean_post_cache( $this->id );
		wp_cache_delete( 'log-items-' . $this->id, 'inventory-logs' );
	}

	/**
	 * When invalid data is found, throw an exception unless reading from the DB
	 *
	 * @since 1.2.4
	 *
	 * @param string $code             Error code
	 * @param string $message          Error message
	 * @param int    $http_status_code HTTP status code
	 * @param array  $data             Extra error data
	 *
	 * @throws AtumException
	 */
	public function error( $code, $message, $http_status_code = 400, $data = array() ) {
		throw new AtumException( $code, $message, $http_status_code, $data );
	}

	//---------
	//
	// GETTERS
	//
	//---------

	/**
	 * Getter for the post property
	 *
	 * @since 1.2.4
	 *
	 * @return \WP_Post
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 * Get the title for the Log post
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_title() {

		if ( ! empty($this->post->post_title) && $this->post->post_title != __('Auto Draft') ) {
			$post_title = $this->post->post_title;
		}
		else {
			$post_title = sprintf( __( 'Log &ndash; %s', ATUM_TEXT_DOMAIN ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Log date parsed by strftime', ATUM_TEXT_DOMAIN ), strtotime( $this->get_date() ) ) );
		}

		return apply_filters('atum/inventory_logs/log/title', $post_title);
	}

	/**
	 * Get the description for the Log post
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_description() {

		$description = ( ! empty($this->post->post_content) ) ? $this->post->post_content : '';
		return apply_filters('atum/inventory_logs/log/description', $description);
	}

	/**
	 * Get the order associated to this log
	 *
	 * @since 1.2.4
	 *
	 * @return \WC_Order|bool
	 */
	public function get_order() {

		$order_id = $this->get_meta('_order');

		if ($order_id) {
			$order = wc_get_order($order_id);

			return $order;
		}

		return FALSE;

	}

	/**
	 * Getter for the Inventory Logs types
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public static function get_types() {

		return (array) apply_filters( 'atum/inventory_logs/log/types', array(
			'reserved-stock'   => __( 'Reserved Stock', ATUM_TEXT_DOMAIN ),
			'customer-returns' => __( 'Customer Returns', ATUM_TEXT_DOMAIN ),
			'warehouse-damage' => __( 'Warehouse Damage', ATUM_TEXT_DOMAIN ),
			'lost-in-post'     => __( 'Lost in Post', ATUM_TEXT_DOMAIN ),
			'other'            => __( 'Other', ATUM_TEXT_DOMAIN )
		) );
	}

	/**
	 * Get the log type
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->get_meta('_type');
	}

	/**
	 * Get the available Inventory Logs statuses
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public static function get_statuses() {

		return (array) apply_filters( 'atum/inventory_logs/log/statuses', array(
			'pending'   => __( 'Pending', ATUM_TEXT_DOMAIN ),
			'completed' => __( 'Completed', ATUM_TEXT_DOMAIN )
		) );

	}

	/**
	 * Get the log status
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->get_meta('_status');
	}

	/**
	 * Get the log date
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_date() {
		return $this->get_meta('_date_created');
	}

	/**
	 * Get the log reservation date
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_reservation_date() {
		return $this->get_meta('_reservation_date');
	}

	/**
	 * Get the log damage date
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_damage_date() {
		return $this->get_meta('_damage_date');
	}

	/**
	 * Get the log return date
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_return_date() {
		return $this->get_meta('_return_date');
	}

	/**
	 * Get the custom log name (for "Other" type logs)
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_custom_name() {
		return $this->get_meta('_custom_name');
	}

	/**
	 * Get log currency
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_currency() {
		return $this->get_meta( '_currency' );
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
	 * Get shipping company
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_shipping_company() {
		return $this->get_meta( '_shipping_company' );
	}

	/**
	 * Gets cart's tax amount
	 *
	 * @since 1.2.4
	 *
	 * @return float
	 */
	public function get_cart_tax( ) {
		return $this->get_meta( '_cart_tax' );
	}

	/**
	 * Gets order grand total. incl. taxes
	 *
	 * @since 1.2.4
	 *
	 * @return float
	 */
	public function get_total() {
		return $this->get_meta( '_total' );
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
	 * Return an array of items within this log
	 *
	 * @since 1.2.4
	 *
	 * @param string|array $types Optional. Types of line items to get (array or string)
	 *
	 * @return LogItemModel array
	 */
	public function get_items( $types = 'line_item' ) {

		$items = array();
		$types = array_filter( (array) $types );

		foreach ( $types as $type ) {

			if ( $group = $this->type_to_group( $type ) ) {

				// Don't use array_merge here because keys are numeric
				$items = ( isset( $this->items[ $group ] ) ) ? array_filter( $items + $this->items[ $group ] ) : $items;
			}

		}

		return apply_filters( 'atum/inventory_logs/log/get_items', $items, $this );

	}

	/**
	 * Return an array of fees within this log
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public function get_fees() {
		return $this->get_items( 'fee' );
	}

	/**
	 * Return an array of taxes within this log
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public function get_taxes() {
		return $this->get_items( 'tax' );
	}

	/**
	 * Get all tax classes for items in the log
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public function get_items_tax_classes() {

		$found_tax_classes = array();

		foreach ( $this->get_items() as $item ) {
			if ( $product = $item->get_product() ) {
				$found_tax_classes[] = $product->get_tax_class();
			}
		}

		return array_unique( $found_tax_classes );

	}

	/**
	 * Return an array of shipping costs within this log
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public function get_shipping_methods() {
		return $this->get_items( 'shipping' );
	}

	/**
	 * Get log item
	 *
	 * @since 1.2.4
	 *
	 * @param object $item
	 *
	 * @return \WC_Order_Item|false if not found
	 *
	 * @throws AtumException
	 */
	public function get_log_item( $item = NULL ) {

		if ( is_a( $item, '\WC_Order_Item' ) ) {
			$item_type = $item->get_type();
			$id        = $item->get_id();
		}
		elseif ( is_object( $item ) && ! empty( $item->log_item_type ) ) {
			$id        = $item->log_item_id;
			$item_type = $item->log_item_type;
		}
		elseif ( is_numeric($item) && ! empty($this->items) ) {
			$id = $item;

			foreach ($this->items as $group => $group_items) {

				foreach ($group_items as $item_id => $stored_item) {
					if ($id == $item_id) {
						$item_type = $this->group_to_type($group);
						break 2;
					}
				}

			}

		}
		else {
			$item_type = FALSE;
			$id        = FALSE;
		}

		if ( $id && $item_type ) {

			$classname = FALSE;
			$items_namespace = '\\Atum\\InventoryLogs\\Items\\';

			switch ( $item_type ) {

				case 'line_item' :
				case 'product' :
					$classname = "{$items_namespace}LogItemProduct";
					break;

				case 'fee' :
					$classname = "{$items_namespace}LogItemFee";
					break;

				case 'shipping' :
					$classname = "{$items_namespace}LogItemShipping";
					break;

				case 'tax' :
					$classname = "{$items_namespace}LogItemTax";
					break;

				default :
					$classname = apply_filters( 'atum/inventory_logs/log_item/get_log_item_classname', $classname, $item_type, $id );
					break;

			}

			if ( $classname && class_exists( $classname ) ) {

				try {
					return new $classname( $id );
				} catch ( AtumException $e ) {
					return FALSE;
				}

			}

		}

		return FALSE;

	}

	/**
	 * Get key for where a certain item type is stored in items prop
	 *
	 * @since  1.2.4
	 *
	 * @param  \WC_Order_Item $item  Log item object (product, shipping, fee, tax)
	 *
	 * @return string
	 */
	protected function get_items_key( $item ) {

		$items_namespace = '\\Atum\\InventoryLogs\\Items\\';

		if ( is_a( $item, "{$items_namespace}LogItemProduct" ) ) {
			return 'line_items';
		}
		elseif ( is_a( $item, "{$items_namespace}LogItemFee" ) ) {
			return 'fee_lines';
		}
		elseif ( is_a( $item, "{$items_namespace}LogItemShipping" ) ) {
			return 'shipping_lines';
		}
		elseif ( is_a( $item, "{$items_namespace}LogItemTax" ) ) {
			return 'tax_lines';
		}
		else {
			return '';
		}

	}

	/**
	 * Get a specified item linked to this log
	 *
	 * @since 1.2.4
	 *
	 * @param int $item_id
	 * @param string $type
	 *
	 * @return \WC_Order_Item|bool
	 */
	public function get_item ($item_id, $type = 'line_item') {

		$type_group = $this->type_to_group($type);
		if ( ! empty( $this->items ) && isset( $this->items[$type_group], $this->items[$type_group][$item_id] ) ) {
			return $this->items[$type_group][$item_id];
		}

		return FALSE;

	}

	//---------
	//
	// SETTERS
	//
	//---------

	/**
	 * Set log currency
	 *
	 * @since 1.2.4
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
	 * Set discount total
	 *
	 * @since 1.2.4
	 *
	 * @param float $value
	 */
	public function set_discount_total( $value ) {
		$this->set_meta( '_discount_total', wc_format_decimal( $value ) );
	}

	/**
	 * Set discount tax
	 *
	 * @since 1.2.4
	 *
	 * @param float $value
	 */
	public function set_discount_tax( $value ) {
		$this->set_meta( '_discount_tax', wc_format_decimal( $value ) );
	}

	/**
	 * Set shipping total
	 *
	 * @since 1.2.4
	 *
	 * @param float $value
	 */
	public function set_shipping_total( $value ) {
		$this->set_meta( '_shipping_total', wc_format_decimal( $value ) );
	}

	/**
	 * Set shipping tax
	 *
	 * @since 1.2.4
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
	 * @since 1.2.4
	 *
	 * @param float $value
	 */
	public function set_cart_tax( $value ) {
		$this->set_meta( '_cart_tax', wc_format_decimal( $value ) );
		$this->set_total_tax( (float) $this->get_cart_tax() + (float) $this->get_shipping_tax() );
	}

	/**
	 * Sets order tax (sum of cart and shipping tax)
	 * Used internally only
	 *
	 * @since 1.2.4
	 *
	 * @param float $value
	 */
	protected function set_total_tax( $value ) {
		$this->set_meta( '_total_tax', wc_format_decimal( $value ) );
	}

	/**
	 * Set total
	 *
	 * @since 1.2.4
	 *
	 * @param float $value
	 */
	public function set_total( $value ) {
		$this->set_meta( '_total', wc_format_decimal( $value, wc_get_price_decimals() ) );
	}

	/**
	 * Set status
	 *
	 * @since 1.2.4
	 *
	 * @param string $value
	 */
	public function set_status( $value ) {
		$this->set_meta( '_status', wc_clean( $value ) );
	}

	/**
	 * Set description
	 *
	 * @since 1.2.4
	 *
	 * @param string $value
	 */
	public function set_description( $value ) {

		$allowed_html = apply_filters( 'atum/inventory_logs/log/allowed_html_in_description', array(
			'a'      => array(
				'href'  => [],
				'title' => [],
				'style' => []
			),
			'span'   => array(
				'style' => []
			),
			'p'      => array(
				'style' => []
			),
			'br'     => [],
			'em'     => [],
			'strong' => [],
			'ul'     => [],
			'ol'     => [],
			'li'     => []
		) );

		$this->post->post_content = wp_kses( $value, $allowed_html );
	}

}