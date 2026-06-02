<?php
/**
 * Class AtumAssets
 *
 * @since		2.0
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2026 BE REBEL Studio
 *
 * @package     Atum\Components
 */

namespace Atum\Components;

final class AtumAssets {

	/**
	 * Register the full set of `atum-*` vendor handles needed in the admin.
	 *
	 * Two groups:
	 *
	 * 1. **Isolated UMDs** (`atum-chartjs`, `atum-bootstrap`, `atum-introjs`,
	 *    `atum-hammer`): wrapped at build time so their top-level global lives
	 *    under a private namespace (`window.atumChart`, `window.atumBootstrap`,
	 *    …). The real `window.Chart` / `window.bootstrap` / etc. stays untouched —
	 *    preventing conflicts with other plugins shipping a different version.
	 *
	 * 2. **jQuery / DOM plugins** (`atum-select2`, `atum-jquery-address`,
	 *    `atum-floatthead`, `atum-jscrollpane`, `atum-easytree`, `atum-dragscroll`):
	 *    NOT isolated. They must mutate the real `window.jQuery` (or `window`)
	 *    to attach `$.fn.select2`, `$.fn.floatThead`, etc. Previously bundled
	 *    via `import '../vendor/<file>'` in TS; the Vite/Rolldown migration
	 *    broke that pattern (UMD CommonJS branch, factory never invoked) so
	 *    they're now shipped as standalone vendor scripts and enqueued as deps
	 *    of the ATUM scripts that need them.
	 *
	 * @since 2.0.0
	 *
	 * @see self::register_atum_vendor_scripts_public() For the narrower frontend subset.
	 */
	public static function register_atum_vendor_scripts() {
		// Isolated UMDs.
		self::register_script( 'atum-chartjs', 'chart.bundle.min.js', [], TRUE );
		self::register_script( 'atum-bootstrap', 'bootstrap.bundle.min.js', [], TRUE );
		self::register_script( 'atum-introjs', 'introjs.min.js', [], TRUE );
		self::register_script( 'atum-hammer', 'hammer.min.js', [], TRUE );
		self::register_style( 'atum-sweetalert2', 'sweetalert2.min.css', [], TRUE );
		self::register_script( 'atum-sweetalert2', 'sweetalert2.min.js', [], TRUE );

		// jQuery / DOM plugins that must patch the real global.
		self::register_script( 'atum-select2', 'select2.min.js', [ 'jquery' ], TRUE );
		self::register_script( 'atum-jquery-address', 'jquery.address.min.js', [ 'jquery' ], TRUE );
		self::register_script( 'atum-floatthead', 'jquery.floatThead.min.js', [ 'jquery' ], TRUE );
		self::register_script( 'atum-jscrollpane', 'jquery.jscrollpane.min.js', [ 'jquery' ], TRUE );
		self::register_script( 'atum-easytree', 'jquery.easytree.min.js', [ 'jquery' ], TRUE );
		self::register_script( 'atum-dragscroll', 'dragscroll.min.js', [], TRUE );
	}

	/**
	 * Register the narrow `atum-*` vendor subset actually needed on the public
	 * (frontend) side.
	 *
	 * Only the handles used by frontend features get registered here, so the
	 * `wp_enqueue_scripts` hook doesn't pay the cost of registering the full
	 * admin set on every public page request.
	 *
	 * Current frontend consumers:
	 *   - `atum-select2` — required by Multi-Inventory's `GeoPrompt` /
	 *     `UserDestinationForm` widget which call `$select.select2(...)`.
	 *
	 * If a future ATUM feature needs another `atum-*` vendor on the frontend,
	 * add it here (do NOT just hook `register_atum_vendor_scripts` to
	 * `wp_enqueue_scripts` — that would re-introduce the per-request bloat).
	 *
	 * @since 2.0.0
	 */
	public static function register_atum_vendor_scripts_public() {
		self::register_script( 'atum-select2', 'select2.min.js', [ 'jquery' ], TRUE );
	}

	/**
	 * Get asset metadata from Vite-generated .asset.php files in dist/.
	 *
	 * @since 2.0.0
	 *
	 * @param string      $slug      Bundle slug without extension (e.g. atum-settings).
	 * @param string|null $attribute Optional. version|dependencies.
	 * @param array       $asset_context {
	 *     Optional addon asset context.
	 *
	 *     @type string $dist_url  Base dist URL. Defaults to ATUM_DIST_URL.
	 *     @type string $dist_path Base dist path. Defaults to ATUM_DIST_PATH.
	 *     @type string $version   Fallback version. Defaults to ATUM_VERSION.
	 * }
	 *
	 * @return string|array|null
	 */
	public static function get_asset_info( $slug, $attribute = NULL, array $asset_context = [] ) {

		static $cache = [];
		$asset_context = self::normalize_asset_context( $asset_context );

		// If a filename was passed, strip the extension.
		if ( str_contains( $slug, '.' ) ) {
			$slug_parts = explode( '.', $slug );
			$slug       = $slug_parts[0];
		}

		$cache_key = $asset_context['dist_path'] . '|' . $slug;

		if ( ! array_key_exists( $cache_key, $cache ) ) {

			$js_path  = self::get_dist_path( "{$slug}.asset.php", 'js', FALSE, $asset_context );
			$css_path = self::get_dist_path( "{$slug}.asset.php", 'css', FALSE, $asset_context );

			/*
			 * NOTE: must be `require`, NOT `require_once`. `require_once`
			 * returns `true` on subsequent includes, which would corrupt the
			 * per-bundle metadata (the boolean casts to `'1'` for version and
			 * fails the `is_array()` check for dependencies). The static
			 * `$cache` avoids re-reading the same file multiple times.
			 */
			if ( file_exists( $js_path ) ) {
				$cache[ $cache_key ] = require $js_path;
			}
			elseif ( file_exists( $css_path ) ) {
				$cache[ $cache_key ] = require $css_path;
			}
			else {
				$cache[ $cache_key ] = NULL;
			}
		}

		$asset = $cache[ $cache_key ];

		if ( ! is_array( $asset ) ) {
			return NULL;
		}

		if ( ! empty( $attribute ) && isset( $asset[ $attribute ] ) ) {
			return $asset[ $attribute ];
		}

		return $asset;

	}

	/**
	 * WordPress script/style dependencies from .asset.php, unioned with the
	 * declared fallback.
	 *
	 * The Vite/Rolldown asset-detection is best-effort: externals consumed as
	 * runtime globals (e.g. `wp.hooks`, `wp-color-picker`) may not appear as
	 * module imports, so the generated `.asset.php` can be partial. Returning
	 * the union (fallback ∪ detected) guarantees a declared dependency is never
	 * dropped while still allowing detection to add extra handles.
	 *
	 * @since 2.0.0
	 *
	 * @param string $slug
	 * @param array  $fallback
	 * @param array  $asset_context Optional addon asset context.
	 *
	 * @return array
	 */
	public static function get_asset_dependencies( $slug, array $fallback = [], array $asset_context = [] ) {
		$deps = self::get_asset_info( $slug, 'dependencies', $asset_context );

		if ( ! is_array( $deps ) ) {
			$deps = [];
		}

		return array_values( array_unique( array_merge( $fallback, $deps ) ) );
	}

	/**
	 * Asset version from .asset.php with fallback to ATUM_VERSION.
	 *
	 * @since 2.0.0
	 *
	 * @param string $slug
	 * @param array  $asset_context Optional addon asset context.
	 *
	 * @return string
	 */
	public static function get_asset_version( $slug, array $asset_context = [] ) {
		$asset_context = self::normalize_asset_context( $asset_context );
		$version = self::get_asset_info( $slug, 'version', $asset_context );

		return $version ? (string) $version : $asset_context['version'];
	}

	/**
	 * Filemtime-based cache-bust version for a file under `dist/vendor/`.
	 *
	 * @since 2.0.0
	 *
	 * @param string $filename Bare filename, e.g. `chart.bundle.min.js`.
	 * @param array  $asset_context Optional addon asset context.
	 *
	 * @return string
	 */
	public static function get_vendor_version( $filename, array $asset_context = [] ) {
		$asset_context = self::normalize_asset_context( $asset_context );

		// Vendor files live under dist/js/vendor or dist/css/vendor; pick by extension.
		$kind = 'css' === pathinfo( $filename, PATHINFO_EXTENSION ) ? 'css' : 'js';
		$path = self::get_dist_path( $filename, $kind, TRUE, $asset_context );

		return file_exists( $path ) ? (string) filemtime( $path ) : $asset_context['version'];
	}

	/**
	 * Get the JS or CSS dist URL
	 *
	 * @since 2.0.0
	 *
	 * @param string $file_name The file name.
	 * @param string $kind		Optional. "css", "js", "images" or "fonts". Defaults to "js".
	 * @param bool   $is_vendor Optional. If it is a vendor asset. Defaults to false.
	 * @param array  $asset_context Optional addon asset context.
	 *
	 * @return string
	 */
	public static function get_dist_url( $file_name, $kind = 'js', $is_vendor = FALSE, array $asset_context = [] ) {
		$asset_context = self::normalize_asset_context( $asset_context );

		$kind        = in_array( $kind, [ 'js', 'css', 'images', 'fonts' ] ) ? $kind : 'js';
		$vendor_path = $is_vendor ? 'vendor/' : '';

		return $asset_context['dist_url'] . "$kind/$vendor_path$file_name";

	}

	/**
	 * Get the JS or CSS dist path
	 *
	 * @since 2.0.0
	 *
	 * @param string $file_name The file name.
	 * @param string $kind		Optional. "css", "js", "images" or "fonts". Defaults to "js".
	 * @param bool   $is_vendor Optional. If it is a vendor asset. Defaults to false.
	 * @param array  $asset_context Optional addon asset context.
	 *
	 * @return string
	 */
	public static function get_dist_path( $file_name, $kind = 'js', $is_vendor = FALSE, array $asset_context = [] ) {
		$asset_context = self::normalize_asset_context( $asset_context );

		$kind        = in_array( $kind, [ 'js', 'css', 'images', 'fonts' ] ) ? $kind : 'js';
		$vendor_path = $is_vendor ? 'vendor/' : '';

		return $asset_context['dist_path'] . "$kind/$vendor_path$file_name";

	}

	/**
	 * Registers a script
	 *
	 * @since 2.0.0
	 *
	 * @param string   $handle
	 * @param string   $file_name
	 * @param string[] $deps
	 * @param bool     $is_vendor
	 * @param bool     $in_footer
	 * @param array    $asset_context Optional addon asset context.
	 */
	public static function register_script( $handle, $file_name, $deps = [], $is_vendor = FALSE, $in_footer = TRUE, array $asset_context = [] ) {
		wp_register_script(
			$handle,
			AtumAssets::get_dist_url( $file_name, 'js', $is_vendor, $asset_context ),
			! $is_vendor ? AtumAssets::get_asset_dependencies( $file_name, $deps, $asset_context ) : $deps,
			! $is_vendor ? AtumAssets::get_asset_version( $file_name, $asset_context ) : AtumAssets::get_vendor_version( $file_name, $asset_context ),
			$in_footer
		);
	}

	/**
	 * Registers a style
	 *
	 * @since 2.0.0
	 *
	 * @param string   $handle
	 * @param string   $file_name
	 * @param string[] $deps
	 * @param bool     $is_vendor
	 * @param array    $asset_context Optional addon asset context.
	 */
	public static function register_style( $handle, $file_name, $deps = [], $is_vendor = FALSE, array $asset_context = [] ) {
		wp_register_style(
			$handle,
			AtumAssets::get_dist_url( $file_name, 'css', $is_vendor, $asset_context ),
			$deps,
			! $is_vendor ? AtumAssets::get_asset_version( $file_name, $asset_context ) : AtumAssets::get_vendor_version( $file_name, $asset_context )
		);
	}

	/**
	 * Normalize an asset context for base-plugin and addon asset registration.
	 *
	 * @since 2.0.0
	 *
	 * @param array $asset_context
	 *
	 * @return array{dist_url:string,dist_path:string,version:string}
	 */
	private static function normalize_asset_context( array $asset_context = [] ) {
		return [
			'dist_url'  => trailingslashit( $asset_context['dist_url'] ?? ATUM_DIST_URL ),
			'dist_path' => trailingslashit( $asset_context['dist_path'] ?? ATUM_DIST_PATH ),
			'version'   => (string) ( $asset_context['version'] ?? ATUM_VERSION ),
		];
	}
	
}
