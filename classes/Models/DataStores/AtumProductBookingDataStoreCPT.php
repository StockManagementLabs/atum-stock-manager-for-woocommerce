<?php
/**
 * WC Booking Product data store
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2026 Stock Management Labs™
 *
 * @since           1.5.4
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

use Atum\Cache\AtumCache;

class AtumProductBookingDataStoreCPT extends \WC_Product_Booking_Data_Store_CPT {
	
	use AtumDataStoreCPTTrait, AtumDataStoreCommonTrait;

	/**
	 * Read resources from the database.
	 * Method overridden to use cache in order to improve performance.
	 *
	 * @param \WC_Product $product
	 */
	protected function read_resources( &$product ) {

		// Pre-existing bug: on cache HIT, the original code never re-populated resource_ids / resource_base_costs /
		// resource_block_costs on the product. Fixed here while we're touching the file — the cached value is
		// now properly read back and applied. resource_base_costs / resource_block_costs use post meta which is
		// cached at the WP core level, so calling get_post_meta() on every hit is cheap.
		$cache_key    = AtumCache::get_cache_key( 'product_booking_resource_ids', $product->get_id() );
		$resource_ids = AtumCache::get_cache( $cache_key, $has_cache );

		if ( ! $has_cache ) {

			global $wpdb;

			$resource_ids = wp_parse_id_list( $wpdb->get_col( $wpdb->prepare( "
				SELECT posts.ID
				FROM {$wpdb->prefix}wc_booking_relationships AS relationships
				LEFT JOIN $wpdb->posts AS posts ON posts.ID = relationships.resource_id
				WHERE relationships.product_id = %d
				ORDER BY sort_order ASC
			", $product->get_id() ) ) );

			AtumCache::set_cache( $cache_key, $resource_ids );

		}

		/* @noinspection PhpPossiblePolymorphicInvocationInspection */
		$product->set_resource_ids( $resource_ids );
		/* @noinspection PhpPossiblePolymorphicInvocationInspection */
		$product->set_resource_base_costs( get_post_meta( $product->get_id(), '_resource_base_costs', true ) );
		/* @noinspection PhpPossiblePolymorphicInvocationInspection */
		$product->set_resource_block_costs( get_post_meta( $product->get_id(), '_resource_block_costs', true ) );

	}

	/**
	 * Read person types from the database.
	 *
	 * @param \WC_Product $product
	 */
	protected function read_person_types( &$product ) {

		$cache_key       = AtumCache::get_cache_key( 'product_booking_person_types', $product->get_id() );
		$cached_post_ids = AtumCache::get_cache( $cache_key, $has_cache );

		if ( $has_cache && is_array( $cached_post_ids ) ) {

			// Rebuild the WC_Product_Booking_Person_Type instances from the cached post IDs.
			// We never store the booking-plugin object instances in the persistent cache because they
			// belong to a third-party plugin's namespace and may not be autoloaded yet on every request.
			$person_types = array();
			foreach ( $cached_post_ids as $post_id ) {
				$post = get_post( $post_id );
				if ( $post && 'bookable_person' === $post->post_type ) {
					$person_types[ $post->ID ] = new \WC_Product_Booking_Person_Type( $post );
				}
			}

			/* @noinspection PhpPossiblePolymorphicInvocationInspection */
			$product->set_person_types( $person_types );
			return;

		}

		$person_type_objects = get_posts( array(
			'post_parent'    => $product->get_id(),
			'post_type'      => 'bookable_person',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'orderby'        => 'menu_order',
			'order'          => 'asc',
		) );

		$person_types = array();
		$post_ids     = array();

		foreach ( $person_type_objects as $person_type_object ) {
			$person_types[ $person_type_object->ID ] = new \WC_Product_Booking_Person_Type( $person_type_object );
			$post_ids[]                              = (int) $person_type_object->ID;
		}

		/* @noinspection PhpPossiblePolymorphicInvocationInspection */
		$product->set_person_types( $person_types );

		// Persist only the IDs — they are scalars and survive any external object cache backend safely.
		AtumCache::set_cache( $cache_key, $post_ids );

	}
	
}
