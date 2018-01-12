<?php
/**
 * @package         Atum\Components\AtumOrders
 * @subpackage      AtumOrders
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * The abstract class for the ATUM Order Item model
 */

namespace Atum\Components\AtumOrders\Models;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumException;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Helpers;


abstract class AtumOrderItemModel {

	/**
	 * The object ID
	 * @var int
	 */
	protected $id;

	/**
	 * An array containing all the meta keys attached to this item
	 * @var array
	 */
	protected $meta = [];

	/**
	 * The ATUM Order ID
	 * @var int
	 */
	protected $atum_order_id;

	/**
	 * The ATUM Order item object
	 * @var \WC_Order_Item
	 */
	protected $atum_order_item;

	/**
	 * The WP cache key name
	 * @var string
	 */
	protected $cache_key = 'atum-order-items';

	/**
	 * AtumOrderItemModel constructor
	 *
	 * @param int $id   Optional. The object ID to initialize
	 */
	protected function __construct( $id = 0 ) {

		if ($id) {
			$this->id = absint($id);

			// Load the data from db
			if ($this->id) {
				$this->read();
			}
		}

	}

	/**
	 * Read a log item from the database
	 *
	 * @since 1.2.9
	 */
	protected function read() {

		global $wpdb;

		// Get from cache if available
		$data = wp_cache_get( 'item-' . $this->id, $this->cache_key );

		if ( FALSE === $data ) {
			$query = $wpdb->prepare( "SELECT order_id, order_item_name FROM {$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . " WHERE order_item_id = %d LIMIT 1;", $this->id );
			$data = $wpdb->get_row( $query );
			wp_cache_set( 'item-' . $this->id, $data, $this->cache_key );
		}

		if ( ! $data ) {
			throw new AtumException( 'invalid_item', __( 'Invalid item', ATUM_TEXT_DOMAIN ) );
		}

		$this->atum_order_item->set_atum_order_id($data->order_id);
		$this->atum_order_item->set_name($data->order_item_name);

		$this->read_meta();

		/*if ( ! empty($this->meta) ) {
			$this->atum_order_item->set_meta_data( $this->meta );
		}*/

		// Read the ATUM Order item props from db
		switch ( $this->atum_order_item->get_type() ) {

			case 'line_item':

				$this->atum_order_item->set_props( array(
					'product_id'   => $this->get_meta( '_product_id' ),
					'variation_id' => $this->get_meta( '_variation_id' ),
					'quantity'     => $this->get_meta( '_qty' ),
					'tax_class'    => $this->get_meta( '_tax_class' ),
					'subtotal'     => $this->get_meta( '_line_subtotal' ),
					'total'        => $this->get_meta( '_line_total' ),
					'taxes'        => $this->get_meta( '_line_tax_data' )
				) );

				break;

			case 'fee':

				$this->atum_order_item->set_props( array(
					'tax_class'  => $this->get_meta( '_tax_class' ),
					'tax_status' => $this->get_meta( '_tax_status' ),
					'total'      => $this->get_meta( '_line_total' ),
					'total_tax'  => $this->get_meta( '_line_tax' ),
					'taxes'      => $this->get_meta( '_line_tax_data' )
				) );

				break;

			case 'shipping':

				$this->atum_order_item->set_props( array(
					'method_id'  => $this->get_meta( '_method_id' ),
					'total'      => $this->get_meta( '_cost' ),
					'total_tax'  => $this->get_meta( '_total_tax' ),
					'taxes'      => $this->get_meta( '_taxes' )
				) );

				break;

			case 'tax':

				$this->atum_order_item->set_props( array(
					'rate_id'            => $this->get_meta( '_rate_id' ),
					'label'              => $this->get_meta( '_label' ),
					'compound'           => $this->get_meta( '_compound' ),
					'tax_total'          => $this->get_meta( '_tax_amount' ),
					'shipping_tax_total' => $this->get_meta( '_shipping_tax_amount' )
				) );

				break;

		}

		$this->atum_order_item->set_object_read( TRUE );

	}

	/**
	 * Save should create or update based on object existence
	 *
	 * @since  1.2.9
	 *
	 * @return int
	 */
	public function save() {

		// Trigger action before saving to the DB. Allows to adjust object props before save
		do_action( 'atum/orders/before_item_save', $this );

		$atum_order_id = $this->atum_order_item->get_atum_order_id();

		if ( !$atum_order_id ) {
			return new \WP_Error( 'empty_props', __('Please provide a valid ATUM Order ID', ATUM_TEXT_DOMAIN) );
		}

		$post_type = get_post_type($atum_order_id);
		$post_type_obj = get_post_type_object($post_type);
		$atum_order_label = $post_type_obj->labels->singular_name;

		if ( ! $this->atum_order_item->get_name() ) {
			return new \WP_Error( 'empty_props', sprintf( __('Please provide a valid name for the %s item', ATUM_TEXT_DOMAIN), $atum_order_label) );
		}

		if ( ! $this->atum_order_item->get_type() ) {
			return new \WP_Error( 'empty_props', sprintf( __('Please provide a valid type for the %s item', ATUM_TEXT_DOMAIN), $atum_order_label) );
		}

		if ( $this->id ) {
			$this->update();
		}
		else {
			$this->create();
		}

		return $this->id;

	}

	/**
	 * Create a new ATUM Order item in the database
	 *
	 * @since 1.2.9
	 */
	protected function create() {

		global $wpdb;

		$inserted = $wpdb->insert(
			$wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE,
			array(
				'order_item_name' => $this->atum_order_item->get_name(),
				'order_item_type' => $this->atum_order_item->get_type(),
				'order_id'        => $this->atum_order_item->get_atum_order_id(),
			)
		);

		if ($inserted) {
			$this->id = $wpdb->insert_id;
			$this->atum_order_item->set_id($this->id);
		}

		$this->clear_cache();

		do_action( 'atum/orders/new_item', $this->id, $this->atum_order_item );

	}

	/**
	 * Update an ATUM Order item in the database
	 *
	 * @since 1.2.9
	 */
	protected function update() {

		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE,
			array(
				'order_item_type' => $this->atum_order_item->get_type(),
				'order_item_name' => $this->atum_order_item->get_name(),
				'order_id'        => $this->atum_order_item->get_atum_order_id(),
			),
			array( 'order_item_id' => $this->id )
		);

		$this->clear_cache();

		do_action( 'atum/orders/update_item', $this->id, $this->atum_order_item );

	}

	/**
	 * Remove an ATUM Order item from the database
	 *
	 * @since 1.2.9
	 */
	public function delete() {

		if ( $this->id ) {
			global $wpdb;
			do_action( 'atum/orders/before_delete_item', $this->id );
			$wpdb->delete( $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE, array( 'order_item_id' => $this->id ) );
			$wpdb->delete( $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE, array( 'order_item_id' => $this->id ) );
			do_action( 'atum/orders/after_delete_item', $this->id );
		}

	}

	/**
	 * Read all the meta key/value pairs registered for this ATUM Order item and save it as a prop
	 *
	 * @since  1.2.9
	 */
	public function read_meta() {

		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT meta_id AS `id`, meta_key AS `key`, meta_value AS `value`
			FROM {$wpdb->atum_order_itemmeta}
			WHERE order_item_id = %d
			ORDER BY meta_id
		", $this->id );

		$raw_meta_data = $wpdb->get_results( $query );

		if ($raw_meta_data) {
			$this->meta = $raw_meta_data;
		}

	}

	/**
	 * Get all the item meta data
	 *
	 * @since 1.2.9
	 *
	 * @return array
	 */
	public function get_all_meta() {
		return $this->meta;
	}

	/**
	 * Clear the item's cache
	 *
	 * @since 1.2.9
	 */
	public function clear_cache() {
		wp_cache_delete( 'item-' . $this->id, $this->cache_key );
	}

	/**
	 * Returns requested meta keys' values
	 *
	 * @since 1.2.9
	 *
	 * @param string $meta_key Optional. A string indicating which meta key to retrieve, or NULL to return all keys
	 * @param bool   $single   Optional. TRUE to return the first value, FALSE to return an array of values
	 *
	 * @return string|array
	 */
	public function get_meta( $meta_key = NULL, $single = TRUE ) {

		if ( $meta_key && isset( $this->meta[$meta_key] ) ) {
			return ($single) ? reset( $this->meta[$meta_key] ) : $this->meta[$meta_key];
		}
		elseif ( !$meta_key && ! empty($this->meta) ) {
			return $this->meta;
		}

		self::sanitize_order_item_name();
		return get_metadata( 'atum_order_item', $this->id, $meta_key, $single);

	}

	/**
	 * Saves the given meta key/value pairs
	 *
	 * @since 1.2.9
	 *
	 * @param array $meta An associative array of meta keys and their values to save
	 * @param bool  $trim
	 *
	 * @return void
	 */
	public function save_meta( $meta = array(), $trim = FALSE ) {

		foreach ( $meta as $key => $value ) {

			if ($trim) {
				$value = Helpers::trim_input($value);
			}

			self::sanitize_order_item_name();
			update_metadata( 'atum_order_item', $this->id, $key, $value );
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
		self::sanitize_order_item_name();
		delete_metadata_by_mid( 'atum_order_item', $meta->id );
	}

	/**
	 * Getter for the ATUM Order ID
	 * @return int
	 */
	public function get_atum_order_id() {
		return $this->atum_order_id;
	}

	/**
	 * Get a meta_key value for the specified ATUM Order ID
	 * Used externally
	 *
	 * @since 1.3.0
	 *
	 * @param int    $item_id
	 * @param string $meta_key
	 *
	 * @return mixed
	 */
	public static function get_item_meta($item_id, $meta_key) {
		self::sanitize_order_item_name();
		return get_metadata( 'atum_order_item', $item_id, $meta_key, TRUE);
	}

	/**
	 * Add the hook to sanitize the order_item_id's column name
	 *
	 * @since 1.3.0
	 */
	public static function sanitize_order_item_name() {
		add_filter( 'sanitize_key', array(__CLASS__, 'fix_order_item_id_column'), 10, 2 );
	}

	/**
	 * Fix the order_item_id column name from atum_order_itemmeta table when getting meta
	 *
	 * @since 1.3.0
	 *
	 * @param string $key
	 * @param string $raw_key
	 *
	 * @return string
	 */
	public static function fix_order_item_id_column($key, $raw_key) {

		if ($key == 'atum_order_item_id') {
			$key = 'order_item_id';
		}

		return $key;

	}

}