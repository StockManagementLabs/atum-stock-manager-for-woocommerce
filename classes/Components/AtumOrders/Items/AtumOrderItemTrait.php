<?php
/**
 * Shared trait for Atum Order item objects
 *
 * @package         Atum\Components\AtumOrders
 * @subpackage      Items
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\Components\AtumOrders\Items;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Models\AtumOrderItemModel;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Helpers;


trait AtumOrderItemTrait {

	/**
	 * The AtumOrderItem model object handler
	 *
	 * @var AtumOrderItemModel
	 */
	protected $atum_order_item_model;

	/**
	 * The parent Atum Order ID
	 *
	 * @var int
	 */
	protected $atum_order_id;

	/**
	 * Constructor
	 *
	 * @since 1.2.9
	 *
	 * @param int|object|array $item ID to load from the DB, or the Atum Order item object.
	 */
	public function __construct( $item = 0 ) {

		/* @noinspection PhpUndefinedFieldInspection */
		$this->extra_data = apply_filters( 'atum/atum_order/order_item_extra_data', $this->extra_data, $this );
		/* @noinspection PhpUndefinedFieldInspection */
		$this->internal_meta_keys = apply_filters( 'atum/atum_order/order_item_internal_meta_keys', $this->internal_meta_keys, $this );

		if ( $item instanceof \WC_Order_Item ) {
			$this->set_id( $item->get_id() );
		}
		elseif ( is_numeric( $item ) && $item > 0 ) {
			$this->set_id( $item );
		}
		else {
			$this->set_object_read( true );
		}

		/* @noinspection PhpUndefinedFieldInspection */
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

		// Save the Log Item and its meta data.
		$atum_order_item_id = $this->atum_order_item_model->save();

		if ( $atum_order_item_id && ! is_wp_error( $atum_order_item_id ) ) {
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
	 * @param bool $force_read
	 */
	public function read_meta_data( $force_read = FALSE ) {
		if ( $this->atum_order_item_model ) {
			$this->meta_data = $this->atum_order_item_model->get_all_meta();
		}
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

			// Bypass any internal meta key.
			if ( $this->is_internal_meta( $meta->key ) ) {
				continue;
			}

			// Delete.
			if ( empty( $meta->value ) ) {

				if ( ! empty( $meta->id ) ) {
					$this->atum_order_item_model->delete_meta( $meta );
					unset( $this->meta_data[ $key ] );
				}

			}
			// Add.
			elseif ( empty( $meta->id ) ) {
				$this->atum_order_item_model->save_meta( array( $meta->key => $meta->value ) );
			}
			// Update.
			else {
				$this->atum_order_item_model->save_meta( array( $meta->key => $meta->value ) );
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
	 * @param bool $force_delete
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
	public function set_atum_order_id( $value ) {
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
	public function is_internal_meta( $meta_key ) {

		return in_array( $meta_key, $this->internal_meta_keys );
	}
	
	/**
	 * Get parent order object.
	 *
	 * @since 1.5.3
	 *
	 * @return AtumOrderModel|\WP_Error
	 */
	public function get_order() {
		
		return Helpers::get_atum_order_model( $this->get_atum_order_id(), TRUE );
	}

	/**
	 * Expands things like term slugs before return
	 *
	 * @since 1.6.1.1
	 *
	 * @param string $hideprefix  Meta data prefix, (default: _).
	 * @param bool   $include_all Include all meta data, this stop skip items with values already in the product name.
	 *
	 * @return array
	 */
	public function get_formatted_meta_data( $hideprefix = '_', $include_all = FALSE ) {

		$formatted_meta    = array();
		$meta_data         = $this->get_meta_data();
		$hideprefix_length = ! empty( $hideprefix ) ? strlen( $hideprefix ) : 0;
		$product           = is_callable( array( $this, 'get_product' ) ) ? $this->get_product() : FALSE;
		$order_item_name   = $this->get_name();
		$item_meta         = $this->atum_order_item_model->get_all_meta();

		foreach ( $meta_data as $meta ) {

			// After adding meta to any ATUM order item, it was discarding all the custom meta until reloading the page.
			if ( $meta instanceof \WC_Meta_Data ) {

				if ( empty( $meta->id ) ) {

					$found_meta = wp_list_filter( $item_meta, [ 'key' => $meta->key ] );

					if ( ! empty( $found_meta ) ) {
						$found_meta = current( $found_meta );
						$meta->id   = $found_meta->id;
					}

				}

			}

			if ( empty( $meta->id ) || '' === $meta->value || ! is_scalar( $meta->value ) || ( $hideprefix_length && substr( $meta->key, 0, $hideprefix_length ) === $hideprefix ) ) {
				continue;
			}

			$meta->key     = rawurldecode( (string) $meta->key );
			$meta->value   = rawurldecode( (string) $meta->value );
			$attribute_key = str_replace( 'attribute_', '', $meta->key );
			$display_key   = wc_attribute_label( $attribute_key, $product );
			$display_value = wp_kses_post( $meta->value );

			if ( taxonomy_exists( $attribute_key ) ) {
				$term = get_term_by( 'slug', $meta->value, $attribute_key );
				if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
					$display_value = $term->name;
				}
			}

			/**
			 * Variable definition
			 *
			 * @var \WC_Product $product
			 */

			// Skip items with values already in the product details area of the product name.
			if ( ! $include_all && $product && $product->is_type( 'variation' ) && wc_is_attribute_in_product_name( $display_value, $order_item_name ) ) {
				continue;
			}

			$formatted_meta[ $meta->id ] = (object) array(
				'key'           => $meta->key,
				'value'         => $meta->value,
				'display_key'   => apply_filters( 'atum/order_item/display_meta_key', $display_key, $meta, $this ),
				'display_value' => wpautop( make_clickable( apply_filters( 'atum/order_item/display_meta_value', $display_value, $meta, $this ) ) ),
			);
		}

		return apply_filters( 'atum/order_item/get_formatted_meta_data', $formatted_meta, $this );
	}

	/**
	 * Allow cloning ATUM Order items
	 *
	 * @since 1.8.2
	 */
	public function __clone() {

		$this->id = 0;
		$this->load();

	}

}
