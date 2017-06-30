<?php
/**
 * @package         Atum\InventoryLogs
 * @subpackage      Models
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * The model class for the Log Item objects
 */

namespace Atum\InventoryLogs\Models;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumException;
use Atum\Components\AtumModel;
use Atum\Inc\Helpers;


class LogItemModel extends AtumModel {

	/**
	 * An array containing all the meta keys attached to this item
	 * @var array
	 */
	private $meta = [];

	/**
	 * @var int
	 */
	private $log_id;

	/**
	 * The Inventory Log item object
	 * @var \WC_Order_Item
	 */
	private $log_item;

	/**
	 * LogItemModel constructor
	 *
	 * @param \WC_Order_Item $log_item  The factory object for initialization
	 */
	public function __construct( \WC_Order_Item $log_item ) {

		parent::__construct( $log_item->get_id() );
		$this->log_item = $log_item;

		// Load the data from db
		if ($this->id) {
			$this->read();
		}

	}

	/**
	 * Save should create or update based on object existence
	 *
	 * @since  1.2.4
	 *
	 * @return int|\WP_Error
	 */
	public function save() {

		// Trigger action before saving to the DB. Allows you to adjust object props before save
		do_action( 'atum/inventory_logs/log_item/before_item_save', $this );

		if ( ! $this->log_item->get_name() ) {
			return new \WP_Error( 'empty_props', __('Please provide a valid name for the Log Item', ATUM_TEXT_DOMAIN) );
		}

		if ( ! $this->log_item->get_type() ) {
			return new \WP_Error( 'empty_props', __('Please provide a valid type for the Log Item', ATUM_TEXT_DOMAIN) );
		}

		if ( ! $this->log_item->get_log_id() ) {
			return new \WP_Error( 'empty_props', __('Please provide ID of the log to which this item belongs', ATUM_TEXT_DOMAIN) );
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
	 * Create a new log item in the database
	 *
	 * @since 1.2.4
	 */
	protected function create() {

		global $wpdb;

		$inserted = $wpdb->insert(
			$wpdb->prefix . ATUM_PREFIX . 'log_items',
			array(
				'log_item_name' => $this->log_item->get_name(),
				'log_item_type' => $this->log_item->get_type(),
				'log_id'        => $this->log_item->get_log_id(),
			)
		);

		if ($inserted) {
			$this->id = $wpdb->insert_id;
			$this->log_item->set_id($this->id);
		}

		$this->clear_cache();

		do_action( 'atum/inventory_logs/log_item/new_item', $this->id, $this->log_item );

	}

	/**
	 * Update a log item in the database
	 *
	 * @since 1.2.4
	 */
	protected function update() {

		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . ATUM_PREFIX . 'log_items',
			array(
				'log_item_type' => $this->log_item->get_type(),
				'log_item_name' => $this->log_item->get_name(),
				'log_id'        => $this->log_item->get_log_id(),
			),
			array( 'log_item_id' => $this->id )
		);

		$this->clear_cache();

		do_action( 'atum/inventory_logs/log_item/update_item', $this->id, $this->log_item );

	}

	/**
	 * Remove a log item from the database
	 *
	 * @since 1.2.4
	 */
	public function delete() {

		if ( $this->id ) {
			global $wpdb;
			do_action( 'atum/inventory_logs/log_item/before_delete_item', $this->id );
			$wpdb->delete( $wpdb->prefix . ATUM_PREFIX . 'log_items', array( 'log_item_id' => $this->id ) );
			$wpdb->delete( $wpdb->prefix . ATUM_PREFIX . 'log_itemmeta', array( 'log_item_id' => $this->id ) );
			do_action( 'atum/inventory_logs/log_item/after_delete_item', $this->id );
		}

	}

	/**
	 * Read a log item from the database
	 *
	 * @since 1.2.4
	 */
	protected function read() {

		global $wpdb;

		// Get from cache if available
		$data = wp_cache_get( 'item-' . $this->id, 'inventory-log-items' );

		if ( FALSE === $data ) {
			$query = $wpdb->prepare( "SELECT log_id, log_item_name FROM {$wpdb->prefix}atum_log_items WHERE log_item_id = %d LIMIT 1;", $this->id );
			$data = $wpdb->get_row( $query );
			wp_cache_set( 'item-' . $this->id, $data, 'inventory-log-items' );
		}

		if ( ! $data ) {
			throw new AtumException( 'invalid_log_item', __( 'Invalid log item', ATUM_TEXT_DOMAIN ) );
		}

		$this->log_item->set_log_id($data->log_id);
		$this->log_item->set_name($data->log_item_name);

		$this->read_meta();

		/*if ( ! empty($this->meta) ) {
			$this->log_item->set_meta_data( $this->meta );
		}*/

		// Read the log item props from db
		switch ( $this->log_item->get_type() ) {

			case 'line_item':

				$this->log_item->set_props( array(
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

				$this->log_item->set_props( array(
					'tax_class'  => $this->get_meta( '_tax_class' ),
					'tax_status' => $this->get_meta( '_tax_status' ),
					'total'      => $this->get_meta( '_line_total' ),
					'total_tax'  => $this->get_meta( '_line_tax' ),
					'taxes'      => $this->get_meta( '_line_tax_data' )
				) );

				break;

			case 'shipping':

				$this->log_item->set_props( array(
					'method_id'  => $this->get_meta( '_method_id' ),
					'total'      => $this->get_meta( '_cost' ),
					'total_tax'  => $this->get_meta( '_total_tax' ),
					'taxes'      => $this->get_meta( '_taxes' )
				) );

				break;

			case 'tax':

				$this->log_item->set_props( array(
					'rate_id'            => $this->get_meta( '_rate_id' ),
					'label'              => $this->get_meta( '_label' ),
					'compound'           => $this->get_meta( '_compound' ),
					'tax_total'          => $this->get_meta( '_tax_amount' ),
					'shipping_tax_total' => $this->get_meta( '_shipping_tax_amount' )
				) );

				break;

		}

		$this->log_item->set_object_read( TRUE );

	}

	/**
	 * Clear the item's cache
	 *
	 * @since 1.2.4
	 */
	public function clear_cache() {
		wp_cache_delete( 'item-' . $this->id, 'inventory-log-items' );
	}

	/**
	 * Read all the meta key/value pairs registered for this Log item and save it as a prop
	 *
	 * @since  1.2.4
	 */
	public function read_meta() {

		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT meta_id AS `id`, meta_key AS `key`, meta_value AS `value`
			FROM {$wpdb->log_itemmeta}
			WHERE log_item_id = %d
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
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public function get_all_meta() {
		return $this->meta;
	}

	/**
	 * @inheritDoc
	 */
	public function get_meta( $meta_key = NULL, $single = TRUE ) {

		if ( $meta_key && isset( $this->meta[$meta_key] ) ) {
			return ($single) ? reset( $this->meta[$meta_key] ) : $this->meta[$meta_key];
		}
		elseif ( !$meta_key && ! empty($this->meta) ) {
			return $this->meta;
		}

		return get_metadata( 'log_item', $this->id, $meta_key, $single);

	}

	/**
	 * @inheritDoc
	 */
	public function save_meta( $meta = array(), $trim = FALSE ) {

		foreach ( $meta as $key => $value ) {

			if ($trim) {
				$value = Helpers::trim_input($value);
			}

			update_metadata( 'log_item', $this->id, $key, $value );
		}

	}

	/**
	 * @inheritDoc
	 */
	public function delete_meta( $meta ) {
		delete_metadata_by_mid( 'log_item', $meta->id );
	}

}