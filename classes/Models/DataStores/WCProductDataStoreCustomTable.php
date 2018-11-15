<?php
/**
 * WC Product data store: using new custom tables
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;

class WCProductDataStoreCustomTable extends \WC_Product_Data_Store_Custom_Table implements \WC_Object_Data_Store_Interface, \WC_Product_Data_Store_Interface {

	/**
	 * Store data into WC's and ATUM's custom product data tables
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product $product The product object.
	 */
	protected function update_product_data( &$product ) {

		parent::update_product_data( $product );
		$this->update_atum_product_data( $product );

	}

	/**
	 * Store data into ATUM's custom product data table
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product $product The product object.
	 */
	public function update_atum_product_data( &$product ) {

		global $wpdb;

		$changes = $product->get_changes();
		$data    = [];
		$insert  = FALSE;
		$row     = $this->get_product_row_from_db( $product->get_id() );

		if ( ! $row ) {
			$insert = TRUE;
		}
		elseif ( empty( $changes ) ) {
			return;
		}

		$columns = array(
			'purchase_price',
			'supplier_id',
			'supplier_sku',
			'atum_controlled',
			'out_stock_date',
			'out_stock_threshold',
			'inheritable',
		);

		// Columns data need to be converted to datetime.
		$date_columns = array(
			'out_stock_date',
		);

		// Values which can be null in the database.
		$allow_null = array(
			'purchase_price',
			'out_stock_date',
			'out_stock_threshold',
		);

		foreach ( $columns as $column ) {

			if ( $insert || array_key_exists( $column, $changes ) ) {

				$value = $product->{"get_$column"}( 'edit' );

				if ( in_array( $column, $date_columns, TRUE ) ) {
					$data[ $column ] = empty( $value ) ? NULL : gmdate( 'Y-m-d H:i:s', $product->{"get_$column"}( 'edit' )->getOffsetTimestamp() );
				}
				else {
					$data[ $column ] = '' === $value && in_array( $column, $allow_null, TRUE ) ? NULL : $value;
				}

				$this->updated_props[] = $column;

			}

		}

		if ( empty( $data ) ) {
			return;
		}

		if ( $insert ) {
			$data['product_id'] = $product->get_id();
			$wpdb->insert( $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE, $data ); // WPCS: db call ok, cache ok.
		}
		elseif ( ! empty( $data ) ) {

			$wpdb->update(
				$wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE,
				$data,
				array(
					'product_id' => $product->get_id(),
				)
			); // WPCS: db call ok, cache ok.

		}

	}

	/**
	 * Get product data row from the DB whilst utilising cache.
	 *
	 * @since 1.5.0
	 *
	 * @param int $product_id Product ID to grab from the database.
	 *
	 * @return array
	 */
	protected function get_product_row_from_db( $product_id ) {

		global $wpdb;

		$data = wp_cache_get( ATUM_PREFIX . 'woocommerce_product_' . $product_id, 'product' );

		if ( FALSE === $data ) {

			// Get the default data from parent class.
			$data = parent::get_product_row_from_db( $product_id );

			// Get the extra ATUM data for the product.
			$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
			$atum_data               = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $atum_product_data_table WHERE product_id = %d;", $product_id ), ARRAY_A ); // WPCS: Unprepared SQL ok.

			$data = array_merge( $data, $atum_data );

			wp_cache_set( ATUM_PREFIX . 'woocommerce_product_' . $product_id, $data, 'product' );

		}

		return (array) apply_filters( 'atum/model/product_data_store/product_data', $data, $product_id );

	}

	/**
	 * Method to create a new product in the database.
	 *
	 * @param WC_Product $product The product object.
	 * @throws Exception Thrown if product cannot be created.
	 */
	public function create( &$product ) {
		try {
			wc_transaction_query( 'start' );

			if ( ! $product->get_date_created( 'edit' ) ) {
				$product->set_date_created( current_time( 'timestamp', true ) );
			}

			if ( $product->get_manage_stock( 'edit' ) && ! $product->get_stock_quantity( 'edit' ) ) {
				$product->set_stock_quantity( 0 );
			}

			$id = wp_insert_post(
				apply_filters(
					'woocommerce_new_product_data',
					array(
						'post_type'      => 'product',
						'post_status'    => $product->get_status() ? $product->get_status() : 'publish',
						'post_author'    => get_current_user_id(),
						'post_title'     => $product->get_name() ? $product->get_name() : __( 'Product', 'woocommerce' ),
						'post_content'   => $product->get_description(),
						'post_excerpt'   => $product->get_short_description(),
						'post_parent'    => $product->get_parent_id(),
						'comment_status' => $product->get_reviews_allowed() ? 'open' : 'closed',
						'ping_status'    => 'closed',
						'menu_order'     => $product->get_menu_order(),
						'post_date'      => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getOffsetTimestamp() ),
						'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getTimestamp() ),
						'post_name'      => $product->get_slug( 'edit' ),
					)
				),
				true
			);

			if ( empty( $id ) || is_wp_error( $id ) ) {
				throw new Exception( 'db_error' );
			}

			$product->set_id( $id );

			$this->update_product_data( $product );
			$this->update_post_meta( $product, true );
			$this->update_terms( $product, true );
			$this->update_visibility( $product, true );
			$this->update_attributes( $product, true );
			$this->handle_updated_props( $product );

			$product->save_meta_data();
			$product->apply_changes();

			update_post_meta( $product->get_id(), '_product_version', WC_VERSION );

			$this->clear_caches( $product );

			wc_transaction_query( 'commit' );

			do_action( 'woocommerce_new_product', $id );
		} catch ( Exception $e ) {
			wc_transaction_query( 'rollback' );
		}
	}

	/**
	 * Method to update a product in the database.
	 *
	 * @param WC_Product $product The product object.
	 */
	public function update( &$product ) {
		$product->save_meta_data();
		$changes = $product->get_changes();

		if ( array_key_exists( 'manage_stock', $changes ) && ! $product->get_stock_quantity( 'edit' ) ) {
			$product->set_stock_quantity( 0 );
		}

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'description', 'short_description', 'name', 'parent_id', 'reviews_allowed', 'status', 'menu_order', 'date_created', 'date_modified', 'slug' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_content'   => $product->get_description( 'edit' ),
				'post_excerpt'   => $product->get_short_description( 'edit' ),
				'post_title'     => $product->get_name( 'edit' ),
				'post_parent'    => $product->get_parent_id( 'edit' ),
				'comment_status' => $product->get_reviews_allowed( 'edit' ) ? 'open' : 'closed',
				'post_status'    => $product->get_status( 'edit' ) ? $product->get_status( 'edit' ) : 'publish',
				'menu_order'     => $product->get_menu_order( 'edit' ),
				'post_name'      => $product->get_slug( 'edit' ),
				'post_type'      => 'product',
			);
			if ( $product->get_date_created( 'edit' ) ) {
				$post_data['post_date']     = gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getOffsetTimestamp() );
				$post_data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getTimestamp() );
			}
			if ( isset( $changes['date_modified'] ) && $product->get_date_modified( 'edit' ) ) {
				$post_data['post_modified']     = gmdate( 'Y-m-d H:i:s', $product->get_date_modified( 'edit' )->getOffsetTimestamp() );
				$post_data['post_modified_gmt'] = gmdate( 'Y-m-d H:i:s', $product->get_date_modified( 'edit' )->getTimestamp() );
			} else {
				$post_data['post_modified']     = current_time( 'mysql' );
				$post_data['post_modified_gmt'] = current_time( 'mysql', 1 );
			}

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update(
					$GLOBALS['wpdb']->posts,
					$post_data,
					array(
						'ID' => $product->get_id(),
					)
				);
				clean_post_cache( $product->get_id() );
			} else {
				wp_update_post(
					array_merge(
						array(
							'ID' => $product->get_id(),
						),
						$post_data
					)
				);
			}
			$product->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}

		$this->update_product_data( $product );
		$this->update_post_meta( $product );
		$this->update_terms( $product );
		$this->update_visibility( $product );
		$this->update_attributes( $product );
		$this->handle_updated_props( $product );

		$product->apply_changes();

		update_post_meta( $product->get_id(), '_product_version', WC_VERSION );

		$this->clear_caches( $product );

		do_action( 'woocommerce_update_product', $product->get_id() );
	}

	/**
	 * Method to delete a product from the database.
	 *
	 * @param WC_Product $product The product object.
	 * @param array      $args Array of args to pass to the delete method.
	 */
	public function delete( &$product, $args = array() ) {
		global $wpdb;

		$id        = $product->get_id();
		$post_type = $product->is_type( 'variation' ) ? 'product_variation' : 'product';

		$args = wp_parse_args(
			$args, array(
				'force_delete' => false,
			)
		);

		if ( ! $id ) {
			return;
		}

		$this->clear_caches( $product );

		if ( $args['force_delete'] ) {
			$wpdb->delete( "{$wpdb->prefix}wc_products", array( 'product_id' => $id ), array( '%d' ) ); // WPCS: db call ok, cache ok.
			$wpdb->delete( "{$wpdb->prefix}wc_product_relationships", array( 'product_id' => $id ), array( '%d' ) ); // WPCS: db call ok, cache ok.
			$wpdb->delete( "{$wpdb->prefix}wc_product_downloads", array( 'product_id' => $id ), array( '%d' ) ); // WPCS: db call ok, cache ok.
			$wpdb->delete( "{$wpdb->prefix}wc_product_variation_attribute_values", array( 'product_id' => $id ), array( '%d' ) ); // WPCS: db call ok, cache ok.
			$wpdb->delete( "{$wpdb->prefix}wc_product_attribute_values", array( 'product_id' => $id ), array( '%d' ) ); // WPCS: db call ok, cache ok.
			wp_delete_post( $id );
			$product->set_id( 0 );
			do_action( 'woocommerce_delete_' . $post_type, $id );
		} else {
			wp_trash_post( $id );
			$product->set_status( 'trash' );
			do_action( 'woocommerce_trash_' . $post_type, $id );
		}
	}

	/**
	 * Clear any ATUM's data store caches.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product $product The product object.
	 */
	protected function clear_caches( &$product ) {

		parent::clear_caches( $product );
		wp_cache_delete( ATUM_PREFIX . 'woocommerce_product_' . $product->get_id(), 'product' );
	}

	/**
	 * Helper method that updates all the post meta for a product based on it's settings in the WC_Product class.
	 *
	 * @param WC_Product $product Product object.
	 * @param bool       $force Force update. Used during create.
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$product, $force = false ) {
		$meta_key_to_props = array(
			'_backorders'        => 'backorders',
			'_sold_individually' => 'sold_individually',
			'_purchase_note'     => 'purchase_note',
			'_wc_rating_count'   => 'rating_counts',
			'_wc_review_count'   => 'review_count',
			'_download_limit'    => 'download_limit',
			'_download_expiry'   => 'download_expiry',
			'_thumbnail_id'      => 'image_id', // For compatibility with WordPress functions like has_post_thumbnail.
		);

		// Make sure to take extra data (like product url or text for external products) into account.
		$extra_data_keys = $product->get_extra_data_keys();

		foreach ( $extra_data_keys as $key ) {
			$meta_key_to_props[ '_' . $key ] = $key;
		}

		$props_to_update = $force ? $meta_key_to_props : $this->get_props_to_update( $product, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $product->{"get_$prop"}( 'edit' );
			switch ( $prop ) {
				case 'sold_individually':
					$updated = update_post_meta( $product->get_id(), $meta_key, wc_bool_to_string( $value ) );
					break;
				default:
					$updated = update_post_meta( $product->get_id(), $meta_key, $value );
					break;
			}
			if ( $updated ) {
				$this->updated_props[] = $prop;
			}
		}

		// Update extra data associated with the product like button text or product URL for external products.
		if ( ! $this->extra_data_saved ) {
			foreach ( $extra_data_keys as $key ) {
				if ( ! array_key_exists( $key, $props_to_update ) ) {
					continue;
				}
				$function = 'get_' . $key;
				if ( is_callable( array( $product, $function ) ) ) {
					if ( update_post_meta( $product->get_id(), '_' . $key, $product->{$function}( 'edit' ) ) ) {
						$this->updated_props[] = $key;
					}
				}
			}
		}

		if ( $this->update_downloads( $product, $force ) ) {
			$this->updated_props[] = 'downloads';
		}
	}

	/**
	 * Handle updated meta props after updating meta data.
	 *
	 * @since  3.0.0
	 * @param  WC_Product $product Product Object.
	 */
	protected function handle_updated_props( &$product ) {
		global $wpdb;

		if ( in_array( 'regular_price', $this->updated_props, true ) || in_array( 'sale_price', $this->updated_props, true ) ) {
			if ( $product->get_sale_price( 'edit' ) >= $product->get_regular_price( 'edit' ) ) {
				$wpdb->update(
					"{$wpdb->prefix}wc_products",
					array(
						'sale_price' => null,
					),
					array(
						'product_id' => $product->get_id(),
					)
				); // WPCS: db call ok, cache ok.
				$product->set_sale_price( '' );
			}
		}
		if ( in_array( 'date_on_sale_from', $this->updated_props, true ) || in_array( 'date_on_sale_to', $this->updated_props, true ) || in_array( 'regular_price', $this->updated_props, true ) || in_array( 'sale_price', $this->updated_props, true ) || in_array( 'product_type', $this->updated_props, true ) ) {
			if ( $product->is_on_sale( 'edit' ) ) {
				$wpdb->update(
					"{$wpdb->prefix}wc_products",
					array(
						'price' => $product->get_sale_price( 'edit' ),
					),
					array(
						'product_id' => $product->get_id(),
					)
				); // WPCS: db call ok, cache ok.
				$product->set_price( $product->get_sale_price( 'edit' ) );
			} else {
				$wpdb->update(
					"{$wpdb->prefix}wc_products",
					array(
						'price' => $product->get_regular_price( 'edit' ),
					),
					array(
						'product_id' => $product->get_id(),
					)
				); // WPCS: db call ok, cache ok.
				$product->set_price( $product->get_regular_price( 'edit' ) );
			}
		}

		if ( in_array( 'stock_quantity', $this->updated_props, true ) ) {
			do_action( $product->is_type( 'variation' ) ? 'woocommerce_variation_set_stock' : 'woocommerce_product_set_stock', $product );
		}

		if ( in_array( 'stock_status', $this->updated_props, true ) ) {
			do_action( $product->is_type( 'variation' ) ? 'woocommerce_variation_set_stock_status' : 'woocommerce_product_set_stock_status', $product->get_id(), $product->get_stock_status(), $product );
		}

		// Trigger action so 3rd parties can deal with updated props.
		do_action( 'woocommerce_product_object_updated_props', $product, $this->updated_props );

		// After handling, we can reset the props array.
		$this->updated_props = array();
	}

	/**
	 * Update a product's stock amount directly.
	 *
	 * @since  3.0.0 this supports set, increase and decrease.
	 * @param  int      $product_id_with_stock Product ID to update.
	 * @param  int|null $stock_quantity Quantity to set.
	 * @param  string   $operation set, increase and decrease.
	 */
	public function update_product_stock( $product_id_with_stock, $stock_quantity = null, $operation = 'set' ) {
		global $wpdb;

		switch ( $operation ) {
			case 'increase':
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wc_products SET stock_quantity = stock_quantity + %f WHERE product_id = %d;", $stock_quantity, $product_id_with_stock ) ); // WPCS: db call ok, cache ok.
				break;
			case 'decrease':
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wc_products SET stock_quantity = stock_quantity - %f WHERE product_id = %d;", $stock_quantity, $product_id_with_stock ) ); // WPCS: db call ok, cache ok.
				break;
			default:
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wc_products SET stock_quantity = %f WHERE product_id = %d;", $stock_quantity, $product_id_with_stock ) ); // WPCS: db call ok, cache ok.
				break;
		}

		wp_cache_delete( 'woocommerce_product_' . $product_id_with_stock, 'product' );
	}

	/**
	 * Read extra data associated with the product, like button text or product URL for external products.
	 *
	 * @param WC_Product $product Product object.
	 * @since 3.0.0
	 */
	/*protected function read_extra_data( &$product ) {
		foreach ( $product->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $product, $function ) ) ) {
				$product->{$function}( get_post_meta( $product->get_id(), '_' . $key, true ) );
			}
		}
	}*/

}
