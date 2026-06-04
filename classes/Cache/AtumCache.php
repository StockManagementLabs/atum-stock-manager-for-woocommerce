<?php
/**
 * Cache helpers for ATUM
 *
 * @package        Atum
 * @subpackage     Components
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2026 Stock Management Labs™
 *
 * @since          1.5.0
 */

namespace Atum\Cache;

defined( 'ABSPATH' ) || die;


final class AtumCache {

	/**
	 * The generic group for ATUM caches
	 */
	const CACHE_GROUP = ATUM_TEXT_DOMAIN;

	/**
	 * Schema version for the cache layout.
	 * Bump this whenever the cached value shape changes, to force a global invalidation of all groups.
	 * The version is baked into every cache key prefix, so bumping it makes every legacy entry orphan.
	 *
	 * @var int
	 */
	const CACHE_SCHEMA_VERSION = 2;

	/**
	 * Store the distinct cache groups (in-process tracking — only used for delete_all_atum_caches()).
	 *
	 * @var array
	 */
	private static $cache_groups = [];

	/**
	 * L1 in-process cache: stores ORIGINAL values (including objects) for the current request only.
	 * Never serialized, never crosses a request boundary — so it is safe to put live objects here
	 * regardless of which persistent backend is configured.
	 *
	 * Key format: "{group}|{cache_key}".
	 *
	 * @var array
	 */
	private static $local_cache = [];

	/**
	 * Per-request memoization of the versioned group prefix.
	 * Avoids hitting wp_cache_get for every single set/get/delete to resolve the prefix —
	 * over a list-table render that can mean hundreds of extra Redis round trips.
	 * Cleared (per group) whenever rotate_group_prefix() runs.
	 *
	 * @var array<string,string>
	 */
	private static $group_prefix_cache = [];

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
	public static function get_cache_key( $name, $args = [], $prefix = ATUM_PREFIX ) {

		if ( ! is_array( $args ) ) {
			$args = (array) $args;
		}

		array_walk_recursive( $args, function ( &$item, $key ) {
			$item = (string) ( is_scalar( $item ) ? $item : $key ); // Make sure the arg can be converted to string to avoid issues.
		} );

		return self::prepare_key( $name, $args, $prefix );

	}

	/**
	 * Get an ATUM cache.
	 *
	 * Reads in order:
	 *   1. L1 in-process cache (live objects, request-local).
	 *   2. L2 persistent cache (wp_cache_get over the group-versioned key).
	 *
	 * Defensive checks:
	 *   - `__PHP_Incomplete_Class` is always treated as a miss (corrupted persistent cache entry).
	 *   - `expected_type` (if provided in $options) is validated; mismatches are treated as misses and the poisoned entry is deleted.
	 *   - `from_storage` callback (if provided in $options) is applied to L2 hits to rehydrate objects from their cacheable shape.
	 *
	 * @since 1.5.0
	 *
	 * @param string $cache_key     The cache key.
	 * @param string $cache_group   Optional. The cache group.
	 * @param bool   $force         Optional. Whether to force an update of the local cache from the persistent cache.
	 * @param bool   $found         Optional. Whether key was found in the cache. Disambiguates a return of false, a storable value.
	 * @param array  $options       Optional. Advanced options {
	 *    @type bool     $allow_disable Optional. Whether the cache can be disabled or not.
	 *    @type string   $expected_type FQCN or one of 'object'|'array'|'int'|'string'|'numeric'.
	 *    @type callable $from_storage  Rehydrator applied to the L2 value before returning.
	 * }
	 * @return mixed|bool  The ATUM cache value or FALSE if the cache does not exist.
	 */
	public static function get_cache( $cache_key, &$found = NULL, $cache_group = self::CACHE_GROUP, $force = FALSE, $options = [] ) {

		$allow_disable = isset( $options['allow_disable'] ) ? (bool) $options['allow_disable'] : TRUE;

		if ( self::$disable_cache && $allow_disable ) {
			$found = FALSE;
			return FALSE;
		}

		$expected_type = isset( $options['expected_type'] ) ? $options['expected_type'] : '';
		$from_storage  = isset( $options['from_storage'] ) && is_callable( $options['from_storage'] ) ? $options['from_storage'] : NULL;

		// L1: in-process lookup. Holds live objects safely.
		$local_key = self::local_key( $cache_group, $cache_key );
		if ( ! $force && array_key_exists( $local_key, self::$local_cache ) ) {
			$value = self::$local_cache[ $local_key ];

			if ( self::is_cache_value_valid( $value, $expected_type ) ) {
				$found = TRUE;
				return $value;
			}

			// Local entry doesn't match the expected type — drop it.
			unset( self::$local_cache[ $local_key ] );
		}

		// L2: persistent lookup via wp_cache (uses the versioned group prefix).
		$prefixed_key = self::apply_group_prefix( $cache_key, $cache_group );
		$value        = wp_cache_get( $prefixed_key, $cache_group, $force, $found );

		if ( ! $found ) {
			return FALSE;
		}

		// Filter out `__PHP_Incomplete_Class` and bad-type entries; delete poisoned cache lines so the next reader rebuilds.
		if ( ! self::is_cache_value_valid( $value, $expected_type ) ) {
			wp_cache_delete( $prefixed_key, $cache_group );
			$found = FALSE;
			return FALSE;
		}

		// Optional rehydration: turn the cacheable shape back into the object the caller expects.
		if ( $from_storage ) {
			try {
				$value = call_user_func( $from_storage, $value );
			}
			catch ( \Throwable $e ) {
				wp_cache_delete( $prefixed_key, $cache_group );
				if ( defined( 'ATUM_DEBUG' ) && ATUM_DEBUG ) {
					error_log( '[ATUM] AtumCache::get_cache from_storage rehydration failed for key "' . $cache_key . '" in group "' . $cache_group . '": ' . $e->getMessage() );
				}
				$found = FALSE;
				return FALSE;
			}
		}

		// Populate L1 so the rest of the request skips the persistent layer.
		self::$local_cache[ $local_key ] = $value;

		return $value;
	}

	/**
	 * Set an ATUM cache.
	 *
	 * Writes to:
	 *   - L1 in-process cache: the original $value (typically the live object).
	 *   - L2 persistent cache: $value, or the result of $options['to_storage']( $value ) if provided.
	 *
	 * Best practice: when caching objects, ALWAYS pass `to_storage`/`from_storage` callbacks so the
	 * persistent backend never receives a PHP object instance (which is fragile under Redis/Memcached/etc.).
	 *
	 * @since 1.5.0
	 *
	 * @param string $cache_key   The cache key.
	 * @param mixed  $value       Value to store.
	 * @param string $cache_group Optional. The cache group.
	 * @param int    $expire      Optional. The expiration time in seconds.
	 * @param array  $options     Optional. Advanced options {
	 *   @type int    	$expire     The expiration time in seconds. Defaults to 30.
	 * 	 @type callable $to_storage Transformer applied before writing to the persistent layer.
	 *      						Must return a primitive/array (no PHP object instances).
	 *   @type bool 	$l1_only 	Skip the persistent (L2) write. Use this when the L2 hit would
	 *      						cost the same as a fresh rebuild (e.g. the rebuild path only consults
	 *      						lower-level caches). L1 still stores the value so same-request reads
	 *      						keep their O(1) lookup. Cross-request reads fall through to a clean
	 *      						miss-and-rebuild, which avoids the redundant persistent layer hop.
	 * }
	 * @return bool  FALSE if value was not set or TRUE if value was set
	 */
	public static function set_cache( $cache_key, $value, $cache_group = self::CACHE_GROUP, $options = [] ) {

		// Symmetric with get_cache: when the cache is globally disabled (e.g. during a bulk import job that
		// wants to skip caching), don't populate L1 or L2 — otherwise re-enabling later would expose values
		// that were never meant to be cached, leading to stale reads.
		if ( self::$disable_cache ) {
			return FALSE;
		}

		// Track the group (used by delete_all_atum_caches). Empty-string group is valid and used by a few
		// deferred/request hand-off caches, so don't skip it here.
		if ( NULL !== $cache_group && ( ! isset( self::$cache_groups[ $cache_group ] ) || ! in_array( $cache_key, self::$cache_groups[ $cache_group ], TRUE ) ) ) {
			self::$cache_groups[ $cache_group ][] = $cache_key;
		}

		$to_storage = isset( $options['to_storage'] ) && is_callable( $options['to_storage'] ) ? $options['to_storage'] : NULL;
		$l1_only    = ! empty( $options['l1_only'] );

		// L1: store the original value (objects ok — never serialized).
		self::$local_cache[ self::local_key( $cache_group, $cache_key ) ] = $value;

		// Short-circuit when the caller has opted out of the persistent layer for this entry.
		// Typical case: the rebuild path on a cache miss is no more expensive than reading L2 + rehydrating
		// (because the underlying data is already cached at a lower level), so writing L2 here would be
		// pure overhead. See get_atum_product / get_atum_order_model / MI's get_inventory for examples.
		if ( $l1_only ) {
			return TRUE;
		}

		// Build the persistent payload.
		$persistent_value = $value;
		if ( $to_storage ) {

			try {
				$persistent_value = call_user_func( $to_storage, $value );
			}
			catch ( \Throwable $e ) {

				if ( defined( 'ATUM_DEBUG' ) && ATUM_DEBUG ) {
					error_log( '[ATUM] AtumCache::set_cache to_storage transform failed for key "' . $cache_key . '" in group "' . $cache_group . '": ' . $e->getMessage() );
				}

				// L1 still holds the live value — just skip the persistent write.
				return FALSE;

			}

		}
		elseif ( defined( 'ATUM_DEBUG' ) && ATUM_DEBUG ) {
			self::warn_if_unsafe_value( $persistent_value, $cache_key, $cache_group );
		}

		$prefixed_key = self::apply_group_prefix( $cache_key, $cache_group );

		return wp_cache_set( $prefixed_key, $persistent_value, $cache_group, ! empty( $options['expire'] ) ? absint( $options['expire'] ) : 30 );

	}

	/**
	 * Delete an ATUM cache (both L1 and L2 for the current request and beyond).
	 *
	 * @since 1.5.0
	 *
	 * @param string $cache_key   The cache key.
	 * @param string $cache_group Optional. The cache group.
	 *
	 * @return bool
	 */
	public static function delete_cache( $cache_key, $cache_group = self::CACHE_GROUP ) {

		unset( self::$local_cache[ self::local_key( $cache_group, $cache_key ) ] );

		$prefixed_key = self::apply_group_prefix( $cache_key, $cache_group );
		return wp_cache_delete( $prefixed_key, $cache_group );
	}

	/**
	 * Invalidate an entire cache group across processes by rotating its version prefix.
	 *
	 * After this call, all previously stored keys for this group are orphaned in the persistent backend
	 * (they will expire on their own TTL), and the next read with the same logical key sees a fresh miss.
	 *
	 * This mirrors WooCommerce's `WC_Cache_Helper::invalidate_cache_group()` / `CacheNameSpaceTrait` strategy,
	 * which is the only reliable group invalidation when an external persistent object cache is active.
	 *
	 * @since 1.5.5
	 *
	 * @param string $cache_group
	 */
	public static function delete_group_cache( $cache_group = self::CACHE_GROUP ) {

		if ( NULL === $cache_group ) {
			return;
		}

		// Drop the L1 entries for this group in the current request.
		$prefix = $cache_group . '|';
		foreach ( array_keys( self::$local_cache ) as $local_key ) {
			if ( 0 === strpos( $local_key, $prefix ) ) {
				unset( self::$local_cache[ $local_key ] );
			}
		}

		// Rotate the version → orphans every L2 key under this group.
		self::rotate_group_prefix( $cache_group );

		unset( self::$cache_groups[ $cache_group ] );
	}

	/**
	 * Invalidate every ATUM cache group known by ATUM.
	 *
	 * With persistent caches, per-request tracking isn't enough because an add-on group may not have been
	 * touched by the current request. Rotate every registered group plus any dynamically touched group.
	 *
	 * @since 1.5.8
	 */
	public static function delete_all_atum_caches() {

		$cache_groups = array_unique( array_merge( self::get_registered_cache_groups(), array_keys( self::$cache_groups ) ) );

		foreach ( $cache_groups as $cache_group ) {
			// Request hand-off group (e.g. Product Levels deferred BOM recalc). Do not rotate globally:
			// delete_all may run between defer() calls in the same request; shutdown consumes via delete_cache().
			if ( '' === $cache_group ) {
				continue;
			}

			self::delete_group_cache( $cache_group );
		}

		// And clear the rest of the L1 in case some entries weren't covered by tracked groups.
		self::$local_cache = [];

	}

	/**
	 * Get all ATUM cache groups that must be included in global invalidation.
	 *
	 * Add-on constants are optional because some add-ons may not be loaded on every request. Only defined
	 * constants are added, and the list remains filterable for extensions/custom ATUM add-ons.
	 *
	 * @since 1.9.57
	 *
	 * @return string[]
	 */
	private static function get_registered_cache_groups() {

		// All the addons will hook into this to add their cache groups.
		return apply_filters( 'atum/cache/groups', [ self::CACHE_GROUP ] );

	}

	/**
	 * Get the versioned prefix for a cache group.
	 *
	 * The prefix is a microtime() stored in wp_cache under a well-known meta-key.
	 * Including it in every cache key means rotating it (writing a new microtime) effectively
	 * invalidates every key under the group, regardless of which persistent backend is active.
	 *
	 * The schema version is also baked in, so bumping CACHE_SCHEMA_VERSION across an upgrade
	 * orphans every entry written by older plugin versions.
	 *
	 * @since 1.9.57
	 *
	 * @param string $cache_group
	 *
	 * @return string
	 */
	private static function get_group_prefix( $cache_group ) {

		// Per-request memoization — avoids one wp_cache_get round trip per cache operation.
		if ( isset( self::$group_prefix_cache[ $cache_group ] ) ) {
			return self::$group_prefix_cache[ $cache_group ];
		}

		$meta_key = ATUM_PREFIX . $cache_group . '_cache_prefix';
		$prefix   = wp_cache_get( $meta_key, $cache_group );

		// Defensive: if a stale or poisoned entry returns a non-string, treat as miss and regenerate.
		if ( FALSE === $prefix || ! is_string( $prefix ) ) {
			$prefix = microtime();
			wp_cache_set( $meta_key, $prefix, $cache_group );
		}

		$full_prefix                              = ATUM_PREFIX . 'cache_v' . self::CACHE_SCHEMA_VERSION . '_' . $prefix . '_';
		self::$group_prefix_cache[ $cache_group ] = $full_prefix;

		return $full_prefix;

	}

	/**
	 * Rotate the version prefix for a cache group — orphans all existing keys.
	 *
	 * @since 1.9.57
	 *
	 * @param string $cache_group
	 *
	 * @return bool
	 */
	private static function rotate_group_prefix( $cache_group ) {
		$meta_key = ATUM_PREFIX . $cache_group . '_cache_prefix';
		// Drop the per-request memoization so the new prefix is read on the next call.
		unset( self::$group_prefix_cache[ $cache_group ] );
		return wp_cache_set( $meta_key, microtime(), $cache_group );
	}

	/**
	 * Apply the versioned group prefix to a logical cache key, producing the final wp_cache key.
	 *
	 * @since 1.9.57
	 *
	 * @param string $cache_key
	 * @param string $cache_group
	 *
	 * @return string
	 */
	private static function apply_group_prefix( $cache_key, $cache_group ) {
		return self::get_group_prefix( $cache_group ) . $cache_key;
	}

	/**
	 * Build the L1 (in-process) lookup key.
	 *
	 * @since 1.9.57
	 *
	 * @param string $cache_group
	 * @param string $cache_key
	 *
	 * @return string
	 */
	private static function local_key( $cache_group, $cache_key ) {
		return $cache_group . '|' . $cache_key;
	}

	/**
	 * Validate a cache value against an optional expected type.
	 * Always rejects `__PHP_Incomplete_Class`.
	 *
	 * @since 1.9.57
	 *
	 * @param mixed  $value
	 * @param string $expected_type Optional. FQCN or one of 'object'|'array'|'int'|'string'|'numeric'.
	 *
	 * @return bool
	 */
	private static function is_cache_value_valid( $value, $expected_type = '' ) {

		// `__PHP_Incomplete_Class` is what unserialize() returns for an object whose class hasn't been autoloaded yet.
		// We never want to return one of those — calling any method on it produces a fatal.
		if ( $value instanceof \__PHP_Incomplete_Class ) {
			return FALSE;
		}

		if ( '' === $expected_type ) {
			return TRUE;
		}

		switch ( $expected_type ) {
			case 'object':
				return is_object( $value );
			case 'array':
				return is_array( $value );
			case 'int':
				return is_int( $value );
			case 'string':
				return is_string( $value );
			case 'numeric':
				return is_numeric( $value );
			default:
				// Treat as a fully qualified class name.
				return $value instanceof $expected_type;
		}
	}

	/**
	 * Emit an error_log warning when a value being cached contains PHP object instances and the caller
	 * didn't provide a `to_storage` transformer. Helps catch regressions where new code paths cache
	 * objects directly, which is fragile under persistent object caches.
	 *
	 * Only runs when ATUM_DEBUG is on. Never affects behaviour — just logs.
	 *
	 * @since 1.9.57
	 *
	 * @param mixed  $value
	 * @param string $cache_key
	 * @param string $cache_group
	 */
	private static function warn_if_unsafe_value( $value, $cache_key, $cache_group ) {

		$found_class = self::find_first_object_class( $value );
		if ( ! $found_class ) {
			return;
		}

		$trace = function_exists( 'wp_debug_backtrace_summary' ) ? wp_debug_backtrace_summary( __CLASS__, 1 ) : '';
		error_log( sprintf(
			'[ATUM] AtumCache::set_cache was called with an object of class %s (key="%s", group="%s") without a to_storage transformer. This is unsafe under persistent object caches. Caller: %s',
			$found_class,
			$cache_key,
			$cache_group,
			$trace
		) );
	}

	/**
	 * Recursively look for the first PHP object instance inside a cache payload.
	 * Returns its class name, or empty string if no objects are found.
	 *
	 * @since 1.9.57
	 *
	 * @param mixed $value
	 * @param int   $depth Current recursion depth (capped at 6 to avoid runaway scans).
	 *
	 * @return string
	 */
	private static function find_first_object_class( $value, $depth = 0 ) {

		if ( $depth > 6 ) {
			if ( defined( 'ATUM_DEBUG' ) && ATUM_DEBUG && 0 === $depth % 7 ) {
				error_log( '[ATUM] AtumCache::find_first_object_class — depth limit reached, deeper objects might be missed.' );
			}
			return '';
		}

		if ( is_object( $value ) ) {

			// Classes that are safe to serialize through any persistent object cache backend.
			// They are all part of PHP or WordPress core — always loaded, no autoload race, no plugin-side
			// class definition changes between deploys.
			static $safe_object_classes = [
				'stdClass',
				'WP_Post',
				'WP_Term',
				'WP_User',
				'WP_Comment',
				'WP_Error',
				'DateTime',
				'DateTimeImmutable',
				'DateInterval',
			];

			$is_safe = FALSE;
			foreach ( $safe_object_classes as $safe_class ) {
				if ( $value instanceof $safe_class ) {
					$is_safe = TRUE;
					break;
				}
			}

			if ( $is_safe ) {
				// Still recurse on stdClass/WP_Post/etc. payloads in case they contain non-safe nested objects
				// (e.g. a WP_Post with a custom object attached to it).
				foreach ( (array) $value as $sub ) {
					$found = self::find_first_object_class( $sub, $depth + 1 );
					if ( '' !== $found ) {
						return $found;
					}
				}
				return '';
			}

			return get_class( $value );
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				$found = self::find_first_object_class( $item, $depth + 1 );
				if ( '' !== $found ) {
					return $found;
				}
			}
		}

		return '';
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
	public static function get_transient_key( $name, $args = [], $prefix = ATUM_PREFIX ) {

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

		$value = ( $force || TRUE !== ATUM_DEBUG ) ? get_transient( $transient_key ) : FALSE;

		// Reject `__PHP_Incomplete_Class` payloads from a poisoned external cache backend.
		if ( $value instanceof \__PHP_Incomplete_Class ) {
			delete_transient( $transient_key );
			return FALSE;
		}

		return $value;
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

		if ( defined( 'ATUM_DEBUG' ) && ATUM_DEBUG ) {
			self::warn_if_unsafe_value( $value, $transient_key, '(transient)' );
		}

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

		if ( $prefix && strpos( $transient_id, $prefix ) !== 0 ) {
			$transient_id = $prefix . $transient_id;
		}

		$transient = "_transient_{$transient_id}";
		$timeout   = "_transient_timeout_{$transient_id}";

		// Ensure the transient isn't in the WP cache.
		$all_options = wp_cache_get( 'alloptions', 'options' );

		if ( isset( $all_options[ $transient ] ) ) {
			unset( $all_options[ $transient ] );
			wp_cache_delete( 'alloptions', 'options' );
			wp_cache_add( 'alloptions', $all_options, 'options' );
		}

		// When using external caching systems, the transients aren't stored in the database.
		if ( wp_using_ext_object_cache() ) {
			wp_cache_delete( $transient_id, 'transient' );
		}

		// Make sure there is no option still saved for the transient or will fail if we try to regenerate the transient on the same request.
		wp_cache_delete( $transient, 'options' );

		return $wpdb->query( "DELETE FROM $wpdb->options WHERE `option_name` LIKE '$transient%' OR `option_name` LIKE '$timeout%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	}

}
