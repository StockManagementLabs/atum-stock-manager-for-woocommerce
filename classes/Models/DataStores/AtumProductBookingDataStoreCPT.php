<?php
/**
 * WC Booking Product data store: using legacy tables
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.5.4
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;

class AtumProductBookingDataStoreCPT extends \WC_Product_Booking_Data_Store_CPT {
	
	use AtumDataStoreCPTTrait, AtumDataStoreCommonTrait;

	/**
	 * Read resources from the database.
	 * Method overridden to use cache in order to improve performance.
	 *
	 * @param \WC_Product $product
	 */
	protected function read_resources( &$product ) {

		$cache_key = AtumCache::get_cache_key( 'product_booking_resource_ids', $product->get_id() );
		AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			global $wpdb;

			$resource_ids = wp_parse_id_list( $wpdb->get_col( $wpdb->prepare( "
				SELECT posts.ID
				FROM {$wpdb->prefix}wc_booking_relationships AS relationships
				LEFT JOIN $wpdb->posts AS posts ON posts.ID = relationships.resource_id
				WHERE relationships.product_id = %d
				ORDER BY sort_order ASC
			", $product->get_id() ) ) );

			/* @noinspection PhpPossiblePolymorphicInvocationInspection */
			$product->set_resource_ids( $resource_ids );
			/* @noinspection PhpPossiblePolymorphicInvocationInspection */
			$product->set_resource_base_costs( get_post_meta( $product->get_id(), '_resource_base_costs', true ) );
			/* @noinspection PhpPossiblePolymorphicInvocationInspection */
			$product->set_resource_block_costs( get_post_meta( $product->get_id(), '_resource_block_costs', true ) );

			AtumCache::set_cache( $cache_key, $resource_ids );

		}

	}

	/**
	 * Read person types from the database.
	 *
	 * @param \WC_Product $product
	 */
	protected function read_person_types( &$product ) {

		$cache_key = AtumCache::get_cache_key( 'product_booking_person_types', $product->get_id() );
		AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			$person_type_objects = get_posts( array(
				'post_parent'    => $product->get_id(),
				'post_type'      => 'bookable_person',
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
				'orderby'        => 'menu_order',
				'order'          => 'asc',
			) );

			$person_types = array();

			foreach ( $person_type_objects as $person_type_object ) {
				$person_types[ $person_type_object->ID ] = new \WC_Product_Booking_Person_Type( $person_type_object );
			}

			/* @noinspection PhpPossiblePolymorphicInvocationInspection */
			$product->set_person_types( $person_types );
			AtumCache::set_cache( $cache_key, $person_types );

		}

	}
	
}
