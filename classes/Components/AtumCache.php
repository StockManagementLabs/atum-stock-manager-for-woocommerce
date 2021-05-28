<?php
/**
 * Cache helpers for ATUM
 *
 * @package        Atum
 * @subpackage     Components
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2021 Stock Management Labs™
 *
 * @since          1.5.0
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;


final class AtumCache {

	/**
	 * The generic group for ATUM caches
	 */
	const CACHE_GROUP = ATUM_TEXT_DOMAIN;
	
	/**
	 * Store the distinct cache groups.
	 *
	 * @var array
	 */
	private static $cache_groups = [];

	/**
	 * Indicates whether to disable the cache temporarily
	 *
	 * @var bool
	 */
	private static $disable_cache = FALSE;


	/****************
	 * CACHE HELPERS
	 ****************/

	/**
	 * Get an ATUM cache identifier
	 *
	 * @since 1.5.0
	 *
	 * @param string $name   The cache name.
	 * @param mixed  $args   Optional. The args to hash.
	 * @param string $prefix Optional. The prefix to use for the key.
	 *
	 * @return string
	 */
	public static function get_cache_key( $name, $args = array(), $prefix = ATUM_PREFIX ) {

		if ( ! is_array( $args ) ) {
			$args = (array) $args;
		}
		
		array_walk_recursive( $args, function ( &$item, $key ) {
			$item = (string) $item;
		} );
		
		return self::prepare_key( $name, $args, $prefix );

	}

	/**
	 * Get an ATUM cache
	 *
	 * @since 1.5.0
	 *
	 * @param string $cache_key     The cache key.
	 * @param string $cache_group   Optional. The cache group.
	 * @param bool   $force         Optional. Whether to force an update of the local cache from the persistent cache.
	 * @param bool   $found         Optional. Whether key was found in the cache. Disambiguates a return of false, a storable value.
	 * @param bool   $allow_disable Optional. Whether the cache can be disabled or not.
	 *
	 * @return mixed|bool  The ATUM cache value or FALSE if the cache does not exist
	 */
	public static function get_cache( $cache_key, $cache_group = self::CACHE_GROUP, $force = FALSE, &$found = NULL, $allow_disable = TRUE ) {

		if ( self::$disable_cache && $allow_disable ) {
			$found = FALSE;
			return $found;
		}

		return wp_cache_get( $cache_key, $cache_group, $force, $found );
	}

	/**
	 * Set an ATUM cache
	 *
	 * @since 1.5.0
	 *
	 * @param string $cache_key   The cache key.
	 * @param mixed  $value       Value to store.
	 * @param string $cache_group Optional. The cache group.
	 * @param int    $expire      Optional. The expiration time in seconds.
	 *
	 * @return bool  FALSE if value was not set or TRUE if value was set
	 */
	public static function set_cache( $cache_key, $value, $cache_group = self::CACHE_GROUP, $expire = 30 ) {

		// Save the current key under its own group to be able to delete the entire group later.
		if ( $cache_group && ( ! isset( self::$cache_groups[ $cache_group ] ) || ! in_array( $cache_key, self::$cache_groups[ $cache_group ] ) ) ) {
			self::$cache_groups[ $cache_group ][] = $cache_key;
		}
		
		return wp_cache_set( $cache_key, $value, $cache_group, $expire );
	}

	/**
	 * Delete an ATUM cache
	 *
	 * @since 1.5.0
	 *
	 * @param string $cache_key   The cache key.
	 * @param string $cache_group Optional. The cache group.
	 *
	 * @return bool
	 */
	public static function delete_cache( $cache_key, $cache_group = self::CACHE_GROUP ) {
		
		return wp_cache_delete( $cache_key, $cache_group );
	}
	
	/**
	 * Regenerate the group name to pretend like it's been erased.
	 *
	 * @since 1.5.5
	 *
	 * @param string $cache_group
	 */
	public static function delete_group_cache( $cache_group = self::CACHE_GROUP ) {

		if ( $cache_group && isset( self::$cache_groups[ $cache_group ] ) ) {

			foreach ( self::$cache_groups[ $cache_group ] as $cache_key ) {
				self::delete_cache( $cache_key, $cache_group );
			}

			unset( self::$cache_groups[ $cache_group ] );

		}
	}

	/**
	 * Regenerate all groups names to pretend like they've been erased
	 *
	 * @since 1.5.8
	 */
	public static function delete_all_atum_caches() {

		if ( ! empty( self::$cache_groups ) ) {

			foreach ( array_keys( self::$cache_groups ) as $cache_group ) {
				self::delete_group_cache( $cache_group );
			}

		}
	}

	/********************
	 * TRANSIENTS HELPERS
	 ********************/

	/**
	 * Get an ATUM transient identifier
	 *
	 * @since 0.0.3
	 *
	 * @param string $name   The transient name.
	 * @param mixed  $args   Optional. The args to hash.
	 * @param string $prefix Optional. The prefix to use for the key.
	 *
	 * @return string
	 */
	public static function get_transient_key( $name, $args = array(), $prefix = ATUM_PREFIX ) {

		if ( ! is_array( $args ) ) {
			$args = (array) $args;
		}

		return self::prepare_key( $name, $args, $prefix );
	}

	/**
	 * Get an ATUM transient
	 *
	 * @since 0.0.2
	 *
	 * @param string $transient_key Transient key.
	 * @param bool   $force         Optional. If set to TRUE, will get the transient in debug mode too.
	 *
	 * @return mixed|bool  The ATUM transient value or FALSE if the transient does not exist or debug mode is on
	 */
	public static function get_transient( $transient_key, $force = FALSE ) {

		return ( $force || TRUE !== ATUM_DEBUG ) ? get_transient( $transient_key ) : FALSE;
	}

	/**
	 * Set an ATUM transient
	 *
	 * @since 0.0.2
	 *
	 * @param string $transient_key Transient key.
	 * @param mixed  $value         Value to store.
	 * @param int    $expiration    Optional. Time until expiration in seconds. By default is set to 0 (does not expire).
	 * @param bool   $force         Optional. If set to TRUE, will set the transient in debug mode too.
	 *
	 * @return bool  FALSE if value was not set or TRUE if value was set.
	 * NOTE: When updating a transient, if the old and new values are identical, it'll return FALSE too.
	 */
	public static function set_transient( $transient_key, $value, $expiration = 0, $force = FALSE ) {

		return ( $force || TRUE !== ATUM_DEBUG ) ? set_transient( $transient_key, $value, $expiration ) : FALSE;
	}

	/**
	 * Delete all the ATUM transients
	 *
	 * @since 0.1.5
	 *
	 * @param string $type   Optional. If specified will remove specific type of ATUM transients.
	 * @param string $prefix Optional. The prefix for the transients that should be deleted.
	 *
	 * @return int|bool The number of transients deleted on success or false on error
	 */
	public static function delete_transients( $type = '', $prefix = ATUM_PREFIX ) {

		global $wpdb;

		$type         = esc_attr( $type );
		$transient_id = $type ?: $prefix;
		$transient    = "_transient_{$transient_id}";
		$timeout      = "_transient_timeout_{$transient_id}";
		
		// Ensure the transient isn't in the WP cache.
		$all_options = wp_cache_get( 'alloptions', 'options', FALSE );

		if ( isset( $all_options[ $transient ] ) ) {
			unset( $all_options[ $transient ] );
			wp_cache_delete( 'alloptions', 'options' );
			wp_cache_add( 'alloptions', $all_options, 'options' );
		}

		return $wpdb->query( "DELETE FROM $wpdb->options WHERE `option_name` LIKE '$transient%' OR `option_name` LIKE '$timeout%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Prepare a cache key
	 *
	 * @since 1.5.0
	 *
	 * @param string $name   The cache name.
	 * @param array  $args   The args to hash.
	 * @param string $prefix Optional. The prefix to use for the key.
	 *
	 * @return string
	 */
	private static function prepare_key( $name, $args, $prefix = ATUM_PREFIX ) {

		$key = 0 !== strpos( $name, $prefix ) ? $prefix . $name : $name;

		if ( ! empty( $args ) ) {

			if ( '_' !== substr( $key, -1, 1 ) ) {
				$key .= '_';
			}

			// Get md5 hash of the array of args to create unique transient key.
			$key .= md5( maybe_serialize( $args ) );

		}

		return $key;

	}

	/**
	 * Whether the ATUM cache is actually disabled
	 *
	 * @since 1.5.8
	 *
	 * @return bool
	 */
	public static function is_cache_disabled() {
		return self::$disable_cache;
	}

	/**
	 * Set the disable cache prop
	 *
	 * @since 1.5.8
	 *
	 * @param bool $disable_cache
	 */
	public static function set_disable_cache( $disable_cache ) {
		self::$disable_cache = $disable_cache;
	}

	/**
	 * Enable the ATUM Cache
	 *
	 * @since 1.5.8
	 */
	public static function enable_cache() {
		self::$disable_cache = FALSE;
	}

	/**
	 * Disable the ATUM Cache
	 *
	 * @since 1.5.8
	 */
	public static function disable_cache() {
		self::$disable_cache = TRUE;
	}

}
