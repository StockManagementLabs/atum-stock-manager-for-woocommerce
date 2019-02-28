<?php
/**
 * Cache helpers for ATUM
 *
 * @package        Atum
 * @subpackage     Components
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2018 Stock Management Labs™
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
	 * Store the distinct chace groups.
	 *
	 * @var array
	 */
	private static $cache_groups = [];


	/****************
	 * CACHE HELPERS
	 ****************/

	/**
	 * Get an ATUM cache identifier
	 *
	 * @since 1.5.0
	 *
	 * @param string $name  The cache name.
	 * @param mixed  $args  Optional. The args to hash.
	 *
	 * @return string
	 */
	public static function get_cache_key( $name, $args = array() ) {

		if ( ! is_array( $args ) ) {
			$args = (array) $args;
		}
		
		array_walk_recursive( $args, function ( &$item, $key ) {
			$item = (string) $item;
		} );
		
		return self::prepare_key( $name, $args );

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
	 *
	 * @return mixed|bool  The ATUM cache value or FALSE if the cache does not exist
	 */
	public static function get_cache( $cache_key, $cache_group = self::CACHE_GROUP, $force = FALSE, &$found = NULL ) {
		
		self::$cache_groups[ $cache_group ] = empty( self::$cache_groups[ $cache_group ] ) ? $cache_group : self::$cache_groups[ $cache_group ];
		
		return wp_cache_get( $cache_key, self::$cache_groups[ $cache_group ], $force, $found );
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
		
		if ( ! isset( self::$cache_groups[ $cache_group ] ) ) {
			self::reset_group( $cache_group );
		}
		
		return wp_cache_set( $cache_key, $value, self::$cache_groups[ $cache_group ], $expire );
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
		
		if ( ! isset( self::$cache_groups[ $cache_group ] ) ) {
			self::reset_group( $cache_group );
		}
		
		return wp_cache_delete( $cache_key, $cache_group );
	}
	
	/**
	 * Regenerate the group name to pretend like it's been erased.
	 *
	 * @since 1.5.5
	 *
	 * @param string $cache_group
	 */
	public static function reset_group( $cache_group = self::CACHE_GROUP ) {
		
		self::$cache_groups[ $cache_group ] = uniqid();
	}

	/********************
	 * TRANSIENTS HELPERS
	 ********************/

	/**
	 * Get an ATUM transient identifier
	 *
	 * @since 0.0.3
	 *
	 * @param string $name  The transient name.
	 * @param mixed  $args  Optional. The args to hash.
	 *
	 * @return string
	 */
	public static function get_transient_key( $name, $args = array() ) {

		if ( ! is_array( $args ) ) {
			$args = (array) $args;
		}

		return self::prepare_key( $name, $args );
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
	 * @return bool  FALSE if value was not set or TRUE if value was set
	 */
	public static function set_transient( $transient_key, $value, $expiration = 0, $force = FALSE ) {

		return ( $force || TRUE !== ATUM_DEBUG ) ? set_transient( $transient_key, $value, $expiration ) : FALSE;
	}

	/**
	 * Delete all the ATUM transients
	 *
	 * @since 0.1.5
	 *
	 * @param string $type  Optional. If specified will remove specific type of ATUM transients.
	 *
	 * @return int|bool The number of transients deleted on success or false on error
	 */
	public static function delete_transients( $type = '' ) {

		global $wpdb;

		$type         = esc_attr( $type );
		$transient_id = $type ?: ATUM_PREFIX;

		return $wpdb->query( "DELETE FROM $wpdb->options WHERE `option_name` LIKE '_transient_{$transient_id}%'" ); // WPCS: unprepared SQL ok.
	}

	/**
	 * Prepare a cache key
	 *
	 * @since 1.5.0
	 *
	 * @param string $name  The cache name.
	 * @param array  $args  Optional. The args to hash.
	 *
	 * @return string
	 */
	private static function prepare_key( $name, $args ) {

		$key = 0 !== strpos( $name, ATUM_PREFIX ) ? ATUM_PREFIX . $name : $name;

		if ( ! empty( $args ) ) {

			if ( '_' !== substr( $key, -1, 1 ) ) {
				$key .= '_';
			}

			// Get md5 hash of the array of args to create unique transient key.
			$key .= md5( maybe_serialize( $args ) );

		}

		return $key;

	}
}
