<?php
/**
 * WooCommerce ProductCache engine wrapper.
 *
 * @package        Atum
 * @subpackage     Components
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2026 Stock Management Labs™
 *
 * @since          1.9.56
 */

namespace Atum\Cache;

defined( 'ABSPATH' ) || die;

use Automattic\WooCommerce\Caching\CacheEngine;


class WCProductCacheEngine implements CacheEngine {

	/**
	 * Wrapped WooCommerce cache engine.
	 *
	 * @var CacheEngine
	 */
	private $engine;

	/**
	 * Constructor.
	 *
	 * @since 1.9.56
	 *
	 * @param CacheEngine $engine The WooCommerce cache engine to wrap.
	 */
	public function __construct( CacheEngine $engine ) {

		$this->engine = $engine;

	}

	/**
	 * Retrieves an object cached under a given key.
	 *
	 * @since 1.9.56
	 *
	 * @param string $key   The key under which the object to retrieve is cached.
	 * @param string $group The group under which the object is cached.
	 *
	 * @return array|object|null
	 */
	public function get_cached_object( string $key, string $group = '' ) {

		return $this->engine->get_cached_object( $this->maybe_namespace_key( $key, $group ), $group );

	}

	/**
	 * Retrieves a set of objects cached under the given keys.
	 *
	 * @since 1.9.56
	 *
	 * @param string[] $keys  The keys under which the objects to retrieve are cached.
	 * @param string   $group The group under which the objects are cached.
	 *
	 * @return array
	 */
	public function get_cached_objects( array $keys, string $group = '' ) {

		$namespaced_keys = $this->maybe_namespace_keys( $keys, $group );

		if ( method_exists( $this->engine, 'get_cached_objects' ) ) {
			$cached_values = $this->engine->get_cached_objects( $namespaced_keys, $group );

			if ( $namespaced_keys === $keys ) {
				return $cached_values;
			}

			$return_values = [];
			foreach ( $keys as $index => $key ) {
				$namespaced_key        = $namespaced_keys[ $index ];
				$return_values[ $key ] = array_key_exists( $namespaced_key, $cached_values ) ? $cached_values[ $namespaced_key ] : NULL;
			}

			return $return_values;
		}

		$return_values = [];
		foreach ( $keys as $index => $key ) {
			$return_values[ $key ] = $this->engine->get_cached_object( $namespaced_keys[ $index ], $group );
		}

		return $return_values;

	}

	/**
	 * Caches an object under a given key, and with a given expiration.
	 *
	 * @since 1.9.56
	 *
	 * @param string       $key        The key under which the object will be cached.
	 * @param array|object $object     The object to cache.
	 * @param int          $expiration Expiration for the cached object, in seconds.
	 * @param string       $group      The group under which the object will be cached.
	 *
	 * @return bool
	 */
	public function cache_object( string $key, $object, int $expiration, string $group = '' ): bool {

		return $this->engine->cache_object( $this->maybe_namespace_key( $key, $group ), $object, $expiration, $group );

	}

	/**
	 * Caches objects under the given keys, and with a given expiration.
	 *
	 * @since 1.9.56
	 *
	 * @param array  $objects    The objects to cache keyed by the key to cache under.
	 * @param int    $expiration Expiration for the cached objects, in seconds.
	 * @param string $group      The group under which the objects will be cached.
	 *
	 * @return array
	 */
	public function cache_objects( array $objects, int $expiration, string $group = '' ): array {

		$objects = $this->maybe_namespace_objects( $objects, $group );

		if ( method_exists( $this->engine, 'cache_objects' ) ) {
			return $this->engine->cache_objects( $objects, $expiration, $group );
		}

		$results = [];
		foreach ( $objects as $key => $object ) {
			$results[ $key ] = $this->engine->cache_object( $key, $object, $expiration, $group );
		}

		return $results;

	}

	/**
	 * Removes a cached object from the cache.
	 *
	 * Product invalidations delete both the normal Woo key and ATUM's namespaced variant so stale ATUM
	 * product objects cannot survive normal WooCommerce invalidation hooks.
	 *
	 * @since 1.9.56
	 *
	 * @param string $key   The key under which the object is cached.
	 * @param string $group The group under which the object is cached.
	 *
	 * @return bool
	 */
	public function delete_cached_object( string $key, string $group = '' ): bool {

		$deleted = $this->engine->delete_cached_object( $key, $group );

		if ( $this->is_product_objects_group( $group ) ) {
			$deleted = $this->delete_atum_cached_object( $key, $group ) || $deleted;
		}

		return $deleted;

	}

	/**
	 * Checks if an object is cached under a given key.
	 *
	 * @since 1.9.56
	 *
	 * @param string $key   The key to verify.
	 * @param string $group The group under which the object is cached.
	 *
	 * @return bool
	 */
	public function is_cached( string $key, string $group = '' ): bool {

		return $this->engine->is_cached( $this->maybe_namespace_key( $key, $group ), $group );

	}

	/**
	 * Deletes all cached objects under a given group.
	 *
	 * @since 1.9.56
	 *
	 * @param string $group The group to delete.
	 *
	 * @return bool
	 */
	public function delete_cache_group( string $group = '' ): bool {

		return $this->engine->delete_cache_group( $group );

	}

	/**
	 * Delete only ATUM's namespaced cached object.
	 *
	 * @since 1.9.56
	 *
	 * @param string $key   The normal Woo ProductCache key.
	 * @param string $group The group under which the object is cached.
	 *
	 * @return bool
	 */
	public function delete_atum_cached_object( string $key, string $group = '' ): bool {

		return $this->engine->delete_cached_object( WCProductCacheCompat::get_atum_cache_key( $key ), $group );

	}

	/**
	 * Namespace a ProductCache key when ATUM product context is active.
	 *
	 * @since 1.9.56
	 *
	 * @param string $key   The cache key.
	 * @param string $group The cache group.
	 *
	 * @return string
	 */
	private function maybe_namespace_key( $key, $group ) {

		if ( $this->should_namespace_product_key( $group ) ) {
			return WCProductCacheCompat::get_atum_cache_key( $key );
		}

		return $key;

	}

	/**
	 * Namespace multiple ProductCache keys when ATUM product context is active.
	 *
	 * @since 1.9.56
	 *
	 * @param string[] $keys  The cache keys.
	 * @param string   $group The cache group.
	 *
	 * @return string[]
	 */
	private function maybe_namespace_keys( array $keys, $group ) {

		if ( ! $this->should_namespace_product_key( $group ) ) {
			return $keys;
		}

		return array_map( array( WCProductCacheCompat::class, 'get_atum_cache_key' ), $keys );

	}

	/**
	 * Namespace keys for a batch of cached objects when ATUM product context is active.
	 *
	 * @since 1.9.56
	 *
	 * @param array  $objects The objects keyed by cache key.
	 * @param string $group   The cache group.
	 *
	 * @return array
	 */
	private function maybe_namespace_objects( array $objects, $group ) {

		if ( ! $this->should_namespace_product_key( $group ) ) {
			return $objects;
		}

		$namespaced_objects = [];

		foreach ( $objects as $key => $object ) {
			$namespaced_objects[ WCProductCacheCompat::get_atum_cache_key( $key ) ] = $object;
		}

		return $namespaced_objects;

	}

	/**
	 * Check whether a key should be namespaced.
	 *
	 * @since 1.9.56
	 *
	 * @param string $group The cache group.
	 *
	 * @return bool
	 */
	private function should_namespace_product_key( $group ) {

		return $this->is_product_objects_group( $group ) && WCProductCacheCompat::is_atum_product_context();

	}

	/**
	 * Check whether a cache group is WooCommerce's ProductCache group.
	 *
	 * @since 1.9.56
	 *
	 * @param string $group The cache group.
	 *
	 * @return bool
	 */
	private function is_product_objects_group( $group ) {

		return WCProductCacheCompat::PRODUCT_OBJECTS_GROUP === $group;

	}

}
