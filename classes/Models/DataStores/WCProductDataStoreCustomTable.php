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

use Atum\Inc\Globals;


defined( 'ABSPATH' ) || die;

class WCProductDataStoreCustomTable extends \WC_Product_Data_Store_Custom_Table implements \WC_Object_Data_Store_Interface, \WC_Product_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_backorders',
		'_sold_individually',
		'_purchase_note',
		'_wc_rating_count',
		'_wc_review_count',
		'_product_version',
		'_wp_old_slug',
		'_edit_last',
		'_edit_lock',
		'_download_limit',
		'_download_expiry',
	);

	/**
	 * If we have already saved our extra data, don't do automatic / default handling.
	 *
	 * @var bool
	 */
	protected $extra_data_saved = false;

	/**
	 * Stores updated props.
	 *
	 * @var array
	 */
	protected $updated_props = array();

	/**
	 * Relationships.
	 *
	 * @since 4.0.0
	 * @var   array
	 */
	protected $relationships = array(
		'image'      => 'gallery_image_ids',
		'upsell'     => 'upsell_ids',
		'cross_sell' => 'cross_sell_ids',
		'grouped'    => 'children',
	);

	/**
	 * Update relationships.
	 *
	 * @since 4.0.0
	 * @param WC_Product $product Product instance.
	 * @param string     $type    Type of relationship.
	 */
	protected function update_relationship( &$product, $type = '' ) {
		global $wpdb;

		if ( empty( $this->relationships[ $type ] ) ) {
			return;
		}

		$prop          = $this->relationships[ $type ];
		$new_values    = $product->{"get_$prop"}( 'edit' );
		$relationships = array_filter(
			$this->get_product_relationship_rows_from_db( $product->get_id() ), function ( $relationship ) use ( $type ) {
				return ! empty( $relationship->type ) && $relationship->type === $type;
			}
		);
		$old_values    = wp_list_pluck( $relationships, 'object_id' );
		$missing       = array_diff( $old_values, $new_values );

		// Delete from database missing values.
		foreach ( $missing as $object_id ) {
			$wpdb->delete(
				$wpdb->prefix . 'wc_product_relationships', array(
					'object_id'  => $object_id,
					'product_id' => $product->get_id(),
				), array(
					'%d',
					'%d',
				)
			); // WPCS: db call ok, cache ok.
		}

		// Insert or update relationship.
		$existing = wp_list_pluck( $relationships, 'relationship_id', 'object_id' );
		foreach ( $new_values as $priority => $object_id ) {
			$relationship = array(
				'relationship_id' => isset( $existing[ $object_id ] ) ? $existing[ $object_id ] : 0,
				'type'            => $type,
				'product_id'      => $product->get_id(),
				'object_id'       => $object_id,
				'priority'        => $priority,
			);

			$wpdb->replace(
				"{$wpdb->prefix}wc_product_relationships",
				$relationship,
				array(
					'%d',
					'%s',
					'%d',
					'%d',
					'%d',
				)
			); // WPCS: db call ok, cache ok.
		}
	}

	/**
	 * Store data into our custom product data table.
	 *
	 * @param WC_Product $product The product object.
	 */
	protected function update_product_data( &$product ) {
		global $wpdb;

		$data    = array(
			'type' => $product->get_type(),
		);
		$changes = $product->get_changes();
		$insert  = false;
		$row     = $this->get_product_row_from_db( $product->get_id() );

		if ( ! $row ) {
			$insert = true;
		}

		$columns = array(
			'sku',
			'image_id',
			'height',
			'length',
			'width',
			'weight',
			'stock_quantity',
			'type',
			'virtual',
			'downloadable',
			'tax_class',
			'tax_status',
			'total_sales',
			'price',
			'regular_price',
			'sale_price',
			'date_on_sale_from',
			'date_on_sale_to',
			'average_rating',
			'stock_status',
		);

		// Columns data need to be converted to datetime.
		$date_columns = array(
			'date_on_sale_from',
			'date_on_sale_to',
		);

		// Values which can be null in the database.
		$allow_null = array(
			'height',
			'length',
			'width',
			'weight',
			'stock_quantity',
			'price',
			'regular_price',
			'sale_price',
			'date_on_sale_from',
			'date_on_sale_to',
			'average_rating',
		);

		foreach ( $columns as $column ) {
			if ( $insert || array_key_exists( $column, $changes ) ) {
				$value = $product->{"get_$column"}( 'edit' );

				if ( in_array( $column, $date_columns, true ) ) {
					$data[ $column ] = empty( $value ) ? null : gmdate( 'Y-m-d H:i:s', $product->{"get_$column"}( 'edit' )->getOffsetTimestamp() );
				} else {
					$data[ $column ] = '' === $value && in_array( $column, $allow_null, true ) ? null : $value;
				}
				$this->updated_props[] = $column;
			}
		}

		// Manage stock over stock_quantity.
		if ( isset( $changes['manage_stock'] ) && ! $changes['manage_stock'] ) {
			$data['stock_quantity'] = null;
			$this->updated_props[]  = 'stock_quantity';
		}

		if ( $insert ) {
			$data['product_id'] = $product->get_id();
			$wpdb->insert( "{$wpdb->prefix}wc_products", $data ); // WPCS: db call ok, cache ok.
		} elseif ( ! empty( $data ) ) {
			$wpdb->update(
				"{$wpdb->prefix}wc_products", $data, array(
					'product_id' => $product->get_id(),
				)
			); // WPCS: db call ok, cache ok.
		}

		foreach ( $this->relationships as $type => $prop ) {
			if ( array_key_exists( $prop, $changes ) ) {
				$this->update_relationship( $product, $type );
				$this->updated_props[] = $type;
			}
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
	 * Get product relationship data rows from the DB whilst utilising cache.
	 *
	 * @param int $product_id Product ID to grab from the database.
	 * @return array
	 */
	protected function get_product_relationship_rows_from_db( $product_id ) {
		global $wpdb;

		$data = wp_cache_get( 'woocommerce_product_relationships_' . $product_id, 'product' );

		if ( false === $data ) {
			$data = $wpdb->get_results( $wpdb->prepare( "SELECT `relationship_id`, `object_id`, `type` FROM {$wpdb->prefix}wc_product_relationships WHERE `product_id` = %d ORDER BY `priority` ASC", $product_id ) ); // WPCS: db call ok.

			wp_cache_set( 'woocommerce_product_relationships_' . $product_id, $data, 'product' );
		}

		return (array) $data;
	}

	/**
	 * Read product data. Can be overridden by child classes to load other props.
	 *
	 * @param WC_Product $product Product object.
	 * @since 3.0.0
	 */
	protected function read_product_data( &$product ) {
		$id            = $product->get_id();
		$props         = $this->get_product_row_from_db( $product->get_id() );
		$review_count  = get_post_meta( $id, '_wc_review_count', true );
		$rating_counts = get_post_meta( $id, '_wc_rating_count', true );

		if ( '' === $review_count ) {
			WC_Comments::get_review_count_for_product( $product );
		} else {
			$props['review_count'] = $review_count;
		}

		if ( '' === $rating_counts ) {
			WC_Comments::get_rating_counts_for_product( $product );
		} else {
			$props['rating_counts'] = $rating_counts;
		}

		$props['manage_stock'] = isset( $props['stock_quantity'] ) && ! is_null( $props['stock_quantity'] );

		$meta_to_props = array(
			'_backorders'        => 'backorders',
			'_sold_individually' => 'sold_individually',
			'_purchase_note'     => 'purchase_note',
			'_download_limit'    => 'download_limit',
			'_download_expiry'   => 'download_expiry',
		);

		foreach ( $meta_to_props as $meta_key => $prop ) {
			$props[ $prop ] = get_post_meta( $id, $meta_key, true );
		}

		$taxonomies_to_props = array(
			'product_cat'            => 'category_ids',
			'product_tag'            => 'tag_ids',
			'product_shipping_class' => 'shipping_class_id',
		);

		foreach ( $taxonomies_to_props as $taxonomy => $prop ) {
			$props[ $prop ] = $this->get_term_ids( $product, $taxonomy );

			if ( 'shipping_class_id' === $prop ) {
				$props[ $prop ] = current( $props[ $prop ] );
			}
		}

		$relationship_rows_from_db = $this->get_product_relationship_rows_from_db( $product->get_id() );

		foreach ( $this->relationships as $type => $prop ) {
			$relationships  = array_filter(
				$relationship_rows_from_db, function ( $relationship ) use ( $type ) {
					return ! empty( $relationship->type ) && $relationship->type === $type;
				}
			);
			$values         = array_map( 'intval', array_values( wp_list_pluck( $relationships, 'object_id' ) ) );
			$props[ $prop ] = $values;
		}

		$product->set_props( $props );

		// Handle sale dates on the fly in case of missed cron schedule.
		if ( $product->is_type( 'simple' ) && $product->is_on_sale( 'edit' ) && $product->get_sale_price( 'edit' ) !== $product->get_price( 'edit' ) ) {
			$product->set_price( $product->get_sale_price( 'edit' ) );
		}
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
	 * Method to read a product from the database.
	 *
	 * @param WC_Product $product The product object.
	 * @throws Exception Exception if the product cannot be read due to being invalid.
	 */
	public function read( &$product ) {
		$product->set_defaults();

		$post_object = $product->get_id() ? get_post( $product->get_id() ) : null;

		if ( ! $post_object || 'product' !== $post_object->post_type ) {
			throw new Exception( __( 'Invalid product.', 'woocommerce' ) );
		}

		$product->set_props(
			array(
				'name'              => $post_object->post_title,
				'slug'              => $post_object->post_name,
				'date_created'      => 0 < $post_object->post_date_gmt ? wc_string_to_timestamp( $post_object->post_date_gmt ) : null,
				'date_modified'     => 0 < $post_object->post_modified_gmt ? wc_string_to_timestamp( $post_object->post_modified_gmt ) : null,
				'status'            => $post_object->post_status,
				'description'       => $post_object->post_content,
				'short_description' => $post_object->post_excerpt,
				'parent_id'         => $post_object->post_parent,
				'menu_order'        => $post_object->menu_order,
				'reviews_allowed'   => 'open' === $post_object->comment_status,
			)
		);

		$this->read_attributes( $product );
		$this->read_downloads( $product );
		$this->read_visibility( $product );
		$this->read_product_data( $product );
		$this->read_extra_data( $product );
		$product->set_object_read( true );
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
	 * Clear any caches.
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
	 * Returns an array of products.
	 *
	 * @param  array $args Args to pass to WC_Product_Query().
	 * @return array|object
	 * @see wc_get_products
	 */
	public function get_products( $args = array() ) {
		$query = new \WC_Product_Query( $args );
		return $query->get_products();
	}

	/**
	 * Read extra data associated with the product, like button text or product URL for external products.
	 *
	 * @param WC_Product $product Product object.
	 * @since 3.0.0
	 */
	protected function read_extra_data( &$product ) {
		foreach ( $product->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $product, $function ) ) ) {
				$product->{$function}( get_post_meta( $product->get_id(), '_' . $key, true ) );
			}
		}
	}

	/**
	 * Search product data for a term and return ids.
	 *
	 * @param  string $term Search term.
	 * @param  string $type Type of product.
	 * @param  bool   $include_variations Include variations in search or not.
	 * @param  bool   $all_statuses Should we search all statuses or limit to published.
	 * @return array of ids
	 */
	public function search_products( $term, $type = '', $include_variations = false, $all_statuses = false ) {
		global $wpdb;

		$post_types    = $include_variations ? array( 'product', 'product_variation' ) : array( 'product' );
		$post_statuses = current_user_can( 'edit_private_products' ) ? array( 'private', 'publish' ) : array( 'publish' );
		$status_where  = '';
		$type_where    = '';
		$term          = wc_strtolower( $term );

		if ( 'virtual' === $type ) {
			$type_where = ' AND products.virtual = 1 ';
		} elseif ( 'downloadable' === $type ) {
			$type_where = ' AND products.downloadable = 1 ';
		}

		// See if search term contains OR keywords.
		if ( strstr( $term, ' or ' ) ) {
			$term_groups = explode( ' or ', $term );
		} else {
			$term_groups = array( $term );
		}

		$search_where   = '';
		$search_queries = array();

		foreach ( $term_groups as $term_group ) {
			// Parse search terms.
			if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $term_group, $matches ) ) {
				$search_terms = $this->get_valid_search_terms( $matches[0] );
				$count        = count( $search_terms );

				// if the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence.
				if ( 9 < $count || 0 === $count ) {
					$search_terms = array( $term_group );
				}
			} else {
				$search_terms = array( $term_group );
			}

			$term_group_query = '';
			$searchand        = '';

			foreach ( $search_terms as $search_term ) {
				$like              = '%' . $wpdb->esc_like( $search_term ) . '%';
				$term_group_query .= $wpdb->prepare(
					" {$searchand} ( ( posts.post_title LIKE %s) OR ( posts.post_excerpt LIKE %s) OR ( posts.post_content LIKE %s ) OR ( products.sku LIKE %s ) )", // phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared
					$like,
					$like,
					$like,
					$like
				);
				$searchand         = ' AND ';
			}

			if ( $term_group_query ) {
				$search_queries[] = $term_group_query;
			}
		}

		if ( $search_queries ) {
			$search_where = 'AND (' . implode( ') OR (', $search_queries ) . ')';
		}

		if ( ! $all_statuses ) {
			$status_where = " AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "') ";
		}

		// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
		$search_results = $wpdb->get_results(
			"SELECT DISTINCT posts.ID as product_id, posts.post_parent as parent_id FROM {$wpdb->posts} posts
			INNER JOIN {$wpdb->prefix}wc_products products ON posts.ID = products.product_id
			WHERE posts.post_type IN ('" . implode( "','", $post_types ) . "')
			AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "')
			$search_where
			$status_where
			$type_where
			ORDER BY posts.post_parent ASC, posts.post_title ASC"
		);
		// phpcs:enable

		$product_ids = wp_parse_id_list( array_merge( wp_list_pluck( $search_results, 'product_id' ), wp_list_pluck( $search_results, 'parent_id' ) ) );

		if ( is_numeric( $term ) ) {
			$post_id   = absint( $term );
			$post_type = get_post_type( $post_id );

			if ( 'product_variation' === $post_type && $include_variations ) {
				$product_ids[] = $post_id;
			} elseif ( 'product' === $post_type ) {
				$product_ids[] = $post_id;
			}

			$product_ids[] = wp_get_post_parent_id( $post_id );
		}

		return wp_parse_id_list( $product_ids );
	}

	/**
	 * Get valid WP_Query args from a WC_Product_Query's query variables.
	 *
	 * @param array $query_vars Query vars from a WC_Product_Query.
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {
		// Map query vars to ones that get_wp_query_args or WP_Query recognize.
		$key_mapping = array(
			'status'       => 'post_status',
			'page'         => 'paged',
			'include'      => 'post__in',
			'stock'        => 'stock_quantity',
			'review_count' => 'wc_review_count',
		);
		foreach ( $key_mapping as $query_key => $db_key ) {
			if ( isset( $query_vars[ $query_key ] ) ) {
				$query_vars[ $db_key ] = $query_vars[ $query_key ];
				unset( $query_vars[ $query_key ] );
			}
		}

		// Handle date queries.
		$date_queries = array(
			'date_created'  => 'post_date',
			'date_modified' => 'post_modified',
		);
		foreach ( $date_queries as $query_var_key => $db_key ) {
			if ( isset( $query_vars[ $query_var_key ] ) && '' !== $query_vars[ $query_var_key ] ) {
				$query_vars = $this->parse_date_for_wp_query( $query_vars[ $query_var_key ], $db_key, $query_vars );
			}
		}

		// Map boolean queries that are stored as 'yes'/'no' in the DB to 'yes' or 'no'.
		$boolean_queries = array(
			'sold_individually',
		);
		foreach ( $boolean_queries as $boolean_query ) {
			if ( isset( $query_vars[ $boolean_query ] ) && '' !== $query_vars[ $boolean_query ] ) {
				$query_vars[ $boolean_query ] = $query_vars[ $boolean_query ] ? 'yes' : 'no';
			}
		}

		// Allow parent class to process the query vars and set defaults.
		$wp_query_args = wp_parse_args(
			parent::get_wp_query_args( $query_vars ),
			array(
				'date_query'        => array(),
				'meta_query'        => array(), // @codingStandardsIgnoreLine.
				'wc_products_query' => array(), // Custom table queries will be stored here and turned into queries later.
			)
		);

		/**
		 * Custom table maping - Map fields in the wc_products table.
		 */
		$product_table_queries = array(
			'sku',
			'type',
			'virtual',
			'downloadable',
			'total_sales',
			'stock_quantity',
			'average_rating',
			'stock_status',
			'height',
			'width',
			'length',
			'weight',
			'tax_class',
			'tax_status',
			'price',
			'regular_price',
			'sale_price',
		);
		foreach ( $product_table_queries as $column_name ) {
			if ( isset( $query_vars[ $column_name ] ) && '' !== $query_vars[ $column_name ] ) {
				$query = array(
					'value'   => $query_vars[ $column_name ],
					'format'  => '%s',
					'compare' => is_array( $query_vars[ $column_name ] ) ? 'IN' : '=',
				);
				switch ( $column_name ) {
					case 'virtual':
					case 'downloadable':
						$query['value']  = $query_vars[ $column_name ] ? 1 : 0;
						$query['format'] = '%d';
						break;
					case 'sku':
						$query['compare'] = 'LIKE';
						break;
				}
				$wp_query_args['wc_products_query'][ $column_name ] = $query;
				unset( $wp_query_args[ $column_name ] );
			}
		}

		if ( isset( $query_vars['date_on_sale_from'] ) && '' !== $query_vars['date_on_sale_from'] ) {
			$wp_query_args = $this->parse_date_for_wp_query( $query_vars['date_on_sale_from'], 'date_on_sale_from', $wp_query_args );
			unset( $wp_query_args['date_on_sale_from'] );
		}

		if ( isset( $query_vars['date_on_sale_to'] ) && '' !== $query_vars['date_on_sale_to'] ) {
			$wp_query_args = $this->parse_date_for_wp_query( $query_vars['date_on_sale_to'], 'date_on_sale_to', $wp_query_args );
			unset( $wp_query_args['date_on_sale_to'] );
		}

		// Handle product types.
		if ( 'variation' === $query_vars['type'] ) {
			$wp_query_args['post_type'] = 'product_variation';
		} elseif ( is_array( $query_vars['type'] ) && in_array( 'variation', $query_vars['type'], true ) ) {
			$wp_query_args['post_type'] = array( 'product_variation', 'product' );
		} else {
			$wp_query_args['post_type'] = 'product';
		}

		// Manage stock/stock queries.
		if ( isset( $query_vars['manage_stock'] ) && '' !== $query_vars['manage_stock'] ) {
			if ( ! isset( $wp_query_args['wc_products_query']['stock_quantity'] ) ) {
				$wp_query_args['wc_products_query']['stock_quantity'] = array(
					'compare' => $query_vars['manage_stock'] ? 'IS NOT NULL' : 'IS NULL',
				);
			}
		}

		/**
		 * TAXONOMIES - convert query vars to tax_query syntax.
		 */
		if ( ! empty( $query_vars['category'] ) ) {
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $query_vars['category'],
			);
		}

		if ( ! empty( $query_vars['tag'] ) ) {
			unset( $wp_query_args['tag'] );
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'    => $query_vars['tag'],
			);
		}

		if ( ! empty( $query_vars['shipping_class'] ) ) {
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'product_shipping_class',
				'field'    => 'slug',
				'terms'    => $query_vars['shipping_class'],
			);
		}

		if ( isset( $query_vars['featured'] ) && '' !== $query_vars['featured'] ) {
			$product_visibility_term_ids = wc_get_product_visibility_term_ids();
			if ( $query_vars['featured'] ) {
				$wp_query_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => array( $product_visibility_term_ids['featured'] ),
				);
				$wp_query_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => array( $product_visibility_term_ids['exclude-from-catalog'] ),
					'operator' => 'NOT IN',
				);
			} else {
				$wp_query_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => array( $product_visibility_term_ids['featured'] ),
					'operator' => 'NOT IN',
				);
			}
			unset( $wp_query_args['featured'] );
		}

		if ( isset( $query_vars['visibility'] ) && '' !== $query_vars['visibility'] ) {
			switch ( $query_vars['visibility'] ) {
				case 'search':
					$wp_query_args['tax_query'][] = array(
						'taxonomy' => 'product_visibility',
						'field'    => 'slug',
						'terms'    => array( 'exclude-from-search' ),
						'operator' => 'NOT IN',
					);
					break;
				case 'catalog':
					$wp_query_args['tax_query'][] = array(
						'taxonomy' => 'product_visibility',
						'field'    => 'slug',
						'terms'    => array( 'exclude-from-catalog' ),
						'operator' => 'NOT IN',
					);
					break;
				case 'visible':
					$wp_query_args['tax_query'][] = array(
						'taxonomy' => 'product_visibility',
						'field'    => 'slug',
						'terms'    => array( 'exclude-from-catalog', 'exclude-from-search' ),
						'operator' => 'NOT IN',
					);
					break;
				case 'hidden':
					$wp_query_args['tax_query'][] = array(
						'taxonomy' => 'product_visibility',
						'field'    => 'slug',
						'terms'    => array( 'exclude-from-catalog', 'exclude-from-search' ),
						'operator' => 'AND',
					);
					break;
			}
			unset( $wp_query_args['visibility'] );
		}

		// Handle reviews allowed.
		if ( isset( $query_vars['reviews_allowed'] ) && is_bool( $query_vars['reviews_allowed'] ) ) {
			$wp_query_args['comment_status'] = $query_vars['reviews_allowed'] ? 'open' : 'closed';
			unset( $wp_query_args['reviews_allowed'] );
		}

		// Handle paginate.
		if ( empty( $query_vars['paginate'] ) ) {
			$wp_query_args['no_found_rows'] = true;
		}

		if ( empty( $wp_query_args['date_query'] ) ) {
			unset( $wp_query_args['date_query'] );
		}

		if ( empty( $wp_query_args['meta_query'] ) ) {
			unset( $wp_query_args['meta_query'] );
		}

		if ( empty( $wp_query_args['wc_products_query'] ) ) {
			unset( $wp_query_args['wc_products_query'] );
		}

		return apply_filters( 'woocommerce_product_data_store_cpt_get_products_query', $wp_query_args, $query_vars, $this );
	}

	/**
	 * Join our custom products table to the posts table.
	 *
	 * @param string $join Join string.
	 * @return string
	 */
	public function products_join( $join ) {
		global $wpdb;

		$join .= " LEFT JOIN {$wpdb->prefix}wc_products products ON {$wpdb->posts}.ID = products.product_id ";

		return $join;
	}

	/**
	 * Add where clauses for our custom table.
	 *
	 * @param string   $where Where query.
	 * @param WP_Query $query Query object.
	 * @return string
	 */
	public function products_where( $where, $query ) {
		global $wpdb;

		if ( ! empty( $query->query_vars['wc_products_query'] ) ) {
			foreach ( $query->query_vars['wc_products_query'] as $name => $query ) {
				$name    = sanitize_key( $name );
				$value   = isset( $query['value'] ) ? $query['value'] : '';
				$compare = isset( $query['compare'] ) ? $query['compare'] : '=';
				$format  = isset( $query['format'] ) ? $query['format'] : '%s';

				$compare_operators = array( '=', '!=', '>', '>=', '<', '<=', 'IS NULL', 'IS NOT NULL', 'LIKE', 'IN', 'NOT IN' );

				if ( ! in_array( $compare, $compare_operators, true ) ) {
					$compare = '=';
				}

				$allowed_formats = array( '%s', '%f', '%d' );

				if ( ! in_array( $format, $allowed_formats, true ) ) {
					$format = '%s';
				}

				switch ( $compare ) {
					case 'IS NULL':
					case 'IS NOT NULL':
						$where .= " AND products.`{$name}` {$compare} ";
						break;
					case 'IN':
					case 'NOT IN':
						$where .= " AND products.`{$name}` {$compare} ('" . implode( "','", array_map( 'esc_sql', $value ) ) . "') ";
						break;
					case 'LIKE':
						// phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
						$where .= $wpdb->prepare( " AND products.`{$name}` LIKE {$format} ", '%' . $wpdb->esc_like( $value ) . '%' );
						break;
					default:
						// phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
						$where .= $wpdb->prepare( " AND products.`{$name}`{$compare}{$format} ", $value );
				}
			}
		}

		return $where;
	}

	/**
	 * Query for Products matching specific criteria.
	 *
	 * @since 3.2.0
	 *
	 * @param array $query_vars Query vars from a WC_Product_Query.
	 *
	 * @return array|object
	 */
	public function query( $query_vars ) {
		$args = $this->get_wp_query_args( $query_vars );

		if ( ! empty( $args['errors'] ) ) {
			$query = (object) array(
				'posts'         => array(),
				'found_posts'   => 0,
				'max_num_pages' => 0,
			);
		} else {
			add_filter( 'posts_join', array( $this, 'products_join' ), 10 );
			add_filter( 'posts_where', array( $this, 'products_where' ), 10, 2 );
			$query = new \WP_Query( $args );
			remove_filter( 'posts_join', array( $this, 'products_join' ), 10 );
			remove_filter( 'posts_where', array( $this, 'products_where' ), 10, 2 );
		}

		if ( isset( $query_vars['return'] ) && 'objects' === $query_vars['return'] && ! empty( $query->posts ) ) {
			// Prime caches before grabbing objects.
			update_post_caches( $query->posts, array( 'product', 'product_variation' ) );
		}

		$products = ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) ? $query->posts : array_filter( array_map( 'wc_get_product', $query->posts ) );

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			return (object) array(
				'products'      => $products,
				'total'         => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return $products;
	}
}
