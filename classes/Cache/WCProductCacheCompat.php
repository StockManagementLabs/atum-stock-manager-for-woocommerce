<?php
/**
 * WooCommerce ProductCache compatibility helpers.
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


final class WCProductCacheCompat {

	/**
	 * WooCommerce ProductCache cache group.
	 */
	const PRODUCT_OBJECTS_GROUP = 'product_objects';

	/**
	 * WooCommerce product instance caching feature flag.
	 */
	const PRODUCT_INSTANCE_CACHING_FEATURE = 'product_instance_caching';

	/**
	 * Prefix used to isolate ATUM product objects in WooCommerce's ProductCache.
	 */
	const ATUM_CACHE_KEY_PREFIX = 'atum:';

	/**
	 * Whether hooks were registered.
	 *
	 * @var bool
	 */
	private static $initialized = FALSE;

	/**
	 * Request-local ATUM product context depth.
	 *
	 * @var int
	 */
	private static $atum_product_context_depth = 0;

	/**
	 * Product cache engine wrappers created during this request.
	 *
	 * @var WCProductCacheEngine[]
	 */
	private static $product_cache_engines = [];

	/**
	 * Register WooCommerce ProductCache compatibility hooks.
	 *
	 * @since 1.9.56
	 */
	public static function init() {

		if ( self::$initialized ) {
			return;
		}

		self::$initialized = TRUE;

		add_filter( 'wc_object_cache_get_engine', array( __CLASS__, 'wrap_product_cache_engine' ), 10, 2 );

	}

	/**
	 * Enter the request-local ATUM product context.
	 *
	 * @since 1.9.56
	 */
	public static function enter_atum_product_context() {

		self::$atum_product_context_depth++;

	}

	/**
	 * Leave the request-local ATUM product context.
	 *
	 * @since 1.9.56
	 */
	public static function leave_atum_product_context() {

		self::$atum_product_context_depth = max( 0, self::$atum_product_context_depth - 1 );

	}

	/**
	 * Check whether the current call stack is resolving an ATUM product instance.
	 *
	 * @since 1.9.56
	 *
	 * @return bool
	 */
	public static function is_atum_product_context() {

		return self::$atum_product_context_depth > 0;

	}

	/**
	 * Namespace a Woo ProductCache key for ATUM product objects.
	 *
	 * @since 1.9.56
	 *
	 * @param string $key The original ProductCache key.
	 *
	 * @return string
	 */
	public static function get_atum_cache_key( $key ) {

		return self::ATUM_CACHE_KEY_PREFIX . $key;

	}

	/**
	 * Wrap WooCommerce's ProductCache engine so ATUM products use a separate key namespace.
	 *
	 * @since 1.9.56
	 *
	 * @param mixed $engine         The cache engine to wrap.
	 * @param mixed $cache_instance The object cache instance that requested the engine.
	 *
	 * @return mixed
	 */
	public static function wrap_product_cache_engine( $engine, $cache_instance ) {

		if ( $engine instanceof WCProductCacheEngine || ! self::is_product_cache_instance( $cache_instance ) ) {
			return $engine;
		}

		$wrapper                       = new WCProductCacheEngine( $engine );
		self::$product_cache_engines[] = $wrapper;

		return $wrapper;

	}

	/**
	 * Delete only ATUM's namespaced ProductCache variant for a product.
	 *
	 * @since 1.9.56
	 *
	 * @param int|string $product_id The product ID.
	 *
	 * @return bool
	 */
	public static function delete_atum_cached_product( $product_id ) {

		$product_id = (string) (int) $product_id;
		$deleted    = FALSE;

		foreach ( self::$product_cache_engines as $engine ) {
			$deleted = $engine->delete_atum_cached_object( $product_id, self::PRODUCT_OBJECTS_GROUP ) || $deleted;
		}

		return $deleted;

	}

	/**
	 * Delete all ProductCache variants for a product.
	 *
	 * This is only used as a defensive retry path when ATUM asked for an ATUM product but WooCommerce returned a
	 * regular WC product. Normally the namespaced ProductCache wrapper prevents that; if it happened anyway, the
	 * cache engine may have been initialized before ATUM installed the wrapper, so clearing WooCommerce's ProductCache
	 * lets the retry instantiate the ATUM product class.
	 *
	 * @since 1.9.56
	 *
	 * @param int|string $product_id The product ID.
	 */
	public static function delete_cached_product_variants( $product_id ) {

		if ( self::delete_atum_cached_product( $product_id ) ) {
			return;
		}

		if (
			! class_exists( '\Automattic\WooCommerce\Internal\Caches\ProductCache' ) ||
			! function_exists( 'wc_get_container' )
		) {
			return;
		}

		try {
			wc_get_container()->get( \Automattic\WooCommerce\Internal\Caches\ProductCache::class )->remove( (int) $product_id );
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Product cache compatibility must never prevent loading a product.
		}

	}

	/**
	 * Check whether WooCommerce's ProductCache feature is active.
	 *
	 * @since 1.9.56
	 *
	 * @return bool
	 */
	public static function is_product_instance_caching_enabled() {

		return class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) &&
			\Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( self::PRODUCT_INSTANCE_CACHING_FEATURE );

	}

	/**
	 * Check whether an ObjectCache instance is WooCommerce's product object cache.
	 *
	 * @since 1.9.56
	 *
	 * @param mixed $cache_instance The object cache instance to check.
	 *
	 * @return bool
	 */
	private static function is_product_cache_instance( $cache_instance ) {

		$product_cache_class = '\Automattic\WooCommerce\Internal\Caches\ProductCache';

		return (
			class_exists( $product_cache_class ) &&
			$cache_instance instanceof $product_cache_class &&
			method_exists( $cache_instance, 'get_object_type' ) &&
			self::PRODUCT_OBJECTS_GROUP === $cache_instance->get_object_type()
		);

	}

}
