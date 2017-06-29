<?php
/**
 * @package         Atum\InventoryLogs
 * @subpackage      Items
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * Shared methods for the Log Item objects
 */

namespace Atum\InventoryLogs\Items;

defined( 'ABSPATH' ) or die;

use Atum\InventoryLogs\Models\LogItemModel;


trait LogItemTrait {

	/**
	 * The LogItemModel object handler
	 * @var LogItemModel
	 */
	protected $log_item_model;

	/**
	 * The parent Log ID
	 * @var int
	 */
	protected $log_id;

	/**
	 * Constructor
	 *
	 * @since 1.2.4
	 *
	 * @param int|object|array $item ID to load from the DB, or the Log Item Object
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
		$this->log_item_model = new LogItemModel( $this );
		$this->read_meta_data();

	}

	/**
	 * Override the WC_Data method to save the item through LogItemModel instead
	 *
	 * @since 1.2.4
	 *
	 * @return int
	 */
	public function save() {

		// Save the Log Item and its meta data
		$log_item_id = $this->log_item_model->save();

		if ($log_item_id) {
			$this->set_id( $this->id );
			$this->save_item_data();
			$this->save_meta_data();
		}

		return $log_item_id;
	}

	/**
	 * Read meta data if no empty
	 *
	 * @since 3.0.0
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
	 * @since 1.2.4
	 *
	 * @param bool $force_read True to force a new DB read (and update cache)
	 */
	public function read_meta_data( $force_read = false ) {
		$this->meta_data  = $this->log_item_model->get_all_meta();
	}

	/**
	 * Update extra custom Meta Data in the database
	 *
	 * @since 1.2.4
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
					$this->log_item_model->delete_meta( $meta );
					unset( $this->meta_data[ $key ] );
				}

			}
			// Add
			elseif ( empty( $meta->id ) ) {
				$new_meta_id = $this->log_item_model->save_meta( array($meta->key => $meta->value) );
				$this->meta_data[ $key ]->id = $new_meta_id;
			}
			// Update
			else {
				$this->log_item_model->save_meta(  array($meta->key => $meta->value) );
			}

		}

		if ( ! empty( $this->cache_group ) ) {
			$cache_key = \WC_Cache_Helper::get_cache_prefix( $this->cache_group ) . \WC_Cache_Helper::get_cache_prefix( 'object_' . $this->get_id() ) . 'object_meta_' . $this->get_id();
			wp_cache_delete( $cache_key, $this->cache_group );
		}

	}

	/**
	 * Override the WC_Data method to delete the item through LogItemModel instead
	 *
	 * @since 1.2.4
	 *
	 * @param bool $force_delete    Optional. Just for overriding compatibility with WC_Data method
	 *
	 * @return int
	 */
	public function delete( $force_delete = FALSE ) {
		$this->log_item_model->delete();
	}

	/**
	 * Getter for the Log ID prop
	 *
	 * @since 1.2.4
	 *
	 * @return int $log_id
	 */
	public function get_log_id () {
		return $this->get_prop('log_id');
	}

	/**
	 * Setter for the Log ID prop
	 *
	 * @since 1.2.4
	 *
	 * @param  int $value
	 */
	public function set_log_id( $value ) {
		$this->set_prop( 'log_id', absint( $value ) );
	}

	/**
	 * Check if the specified meta key is a reeserved internal meta key
	 *
	 * @since 1.2.4
	 *
	 * @param string $meta_key
	 *
	 * @return bool
	 */
	public function is_internal_meta ($meta_key) {
		return in_array($meta_key, $this->internal_meta_keys);
	}

}