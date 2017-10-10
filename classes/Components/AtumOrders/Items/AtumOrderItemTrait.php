<?php
/**
 * @package         Atum\Components\AtumOrders
 * @subpackage      Items
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.9
 *
 * Shared trait for Atum Order item objects
 */

namespace Atum\Components\AtumOrders\Items;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumOrders\Models\AtumOrderItemModel;


trait AtumOrderItemTrait {

	/**
	 * The AtumOrderItem model object handler
	 * @var AtumOrderItemModel
	 */
	protected $atum_order_item_model;

	/**
	 * The parent Atum Order ID
	 * @var int
	 */
	protected $atum_order_id;

	/**
	 * Constructor
	 *
	 * @since 1.2.9
	 *
	 * @param int|object|array $item ID to load from the DB, or the Atum Order item object
	 */
	public function __construct( $item = 0 ) {

		if ( $item instanceof \WC_Order_Item ) {
			$this->set_id( $item->get_id() );
		}
		elseif ( is_numeric( $item ) && $item > 0 ) {
			$this->set_id( $item );
		}
		else {
			$this->set_object_read( true );
		}

		$this->data = array_merge( $this->data, $this->extra_data );
		$this->load();

	}

	/**
	 * Load the ATUM Order item model
	 * Must be implemented by all the classes using it
	 *
	 * @since 1.2.9
	 */
	abstract protected function load();

	/**
	 * Override the WC_Data method to save the item through AtumOrderItemModel instead
	 *
	 * @since 1.2.9
	 *
	 * @return int
	 */
	public function save() {

		// Save the Log Item and its meta data
		$atum_order_item_id = $this->atum_order_item_model->save();

		if ($atum_order_item_id) {
			$this->set_id( $this->id );
			$this->save_item_data();
			$this->save_meta_data();
		}

		return $atum_order_item_id;
	}

	/**
	 * Read meta data if no empty
	 *
	 * @since 1.2.9
	 */
	protected function maybe_read_meta_data() {
		if ( empty( $this->meta_data ) ) {
			$this->read_meta_data();
		}
	}

	/**
	 * Read Meta Data from the database. Ignore any internal properties
	 * Uses it's own caches because get_metadata does not provide meta_ids
	 *
	 * @since 1.2.9
	 *
	 * @param bool $force_read True to force a new DB read (and update cache)
	 */
	public function read_meta_data( $force_read = false ) {
		$this->meta_data = $this->atum_order_item_model->get_all_meta();
	}

	/**
	 * Update extra custom Meta Data in the database
	 *
	 * @since 1.2.9
	 */
	public function save_meta_data() {

		if ( empty( $this->meta_data ) ) {
			return;
		}

		foreach ( $this->meta_data as $key => $meta ) {

			// Bypass any internal meta key
			if ( $this->is_internal_meta($meta->key) ) {
				continue;
			}

			// Delete
			if ( empty( $meta->value ) ) {

				if ( ! empty( $meta->id ) ) {
					$this->atum_order_item_model->delete_meta( $meta );
					unset( $this->meta_data[ $key ] );
				}

			}
			// Add
			elseif ( empty( $meta->id ) ) {
				$new_meta_id = $this->atum_order_item_model->save_meta( array($meta->key => $meta->value) );
				$this->meta_data[ $key ]->id = $new_meta_id;
			}
			// Update
			else {
				$this->atum_order_item_model->save_meta(  array($meta->key => $meta->value) );
			}

		}

		if ( ! empty( $this->cache_group ) ) {
			$cache_key = \WC_Cache_Helper::get_cache_prefix( $this->cache_group ) . \WC_Cache_Helper::get_cache_prefix( 'object_' . $this->get_id() ) . 'object_meta_' . $this->get_id();
			wp_cache_delete( $cache_key, $this->cache_group );
		}

	}

	/**
	 * Override the WC_Data method to delete the item through AtumOrderItemModel instead
	 *
	 * @since 1.2.9
	 *
	 * @param bool $force_delete    Optional. Just for overriding compatibility with WC_Data method
	 *
	 * @return int
	 */
	public function delete( $force_delete = FALSE ) {
		$this->atum_order_item_model->delete();
	}

	/**
	 * Getter for the atum_order_id prop
	 *
	 * @since 1.2.9
	 *
	 * @return int
	 */
	public function get_atum_order_id() {
		return $this->atum_order_id;
	}

	/**
	 * Setter for the atum_order_id prop
	 *
	 * @since 1.2.9
	 *
	 * @param int $value
	 */
	public function set_atum_order_id($value) {
		$this->atum_order_id = absint( $value );
	}

	/**
	 * Check if the specified meta key is a reeserved internal meta key
	 *
	 * @since 1.2.9
	 *
	 * @param string $meta_key
	 *
	 * @return bool
	 */
	public function is_internal_meta ($meta_key) {
		return in_array($meta_key, $this->internal_meta_keys);
	}

}