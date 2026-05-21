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
	 * Register the isolated atum-* vendor script handles.
	 *
	 * Each handle points to a UMD shipped under `dist/vendor/` and wrapped at
	 * build time so its top-level global lives under a private namespace
	 * (`window.atumChart`, `window.atumBootstrap`, …) and the real
	 * `window.Chart` / `window.bootstrap` / etc. stays untouched — preventing
	 * conflicts with other plugins shipping a different version of the same lib.
	 *
	 * @since 2.0.0
	 */
	public static function register_atum_vendor_scripts() {
		self::register_script( 'atum-chartjs', 'chart.bundle.min.js', [], TRUE );
		self::register_script( 'atum-bootstrap', 'bootstrap.bundle.min.js', [], TRUE );
		self::register_script( 'atum-introjs', 'introjs.min.js', [], TRUE );
		self::register_script( 'atum-hammer', 'hammer.min.js', [], TRUE );
		self::register_style( 'atum-sweetalert2', 'sweetalert2.min.css' );
		self::register_script( 'atum-sweetalert2', 'sweetalert2.min.js', [], TRUE );
	}

	/**
	 * Get asset metadata from Vite-generated .asset.php files in dist/.
	 *
	 * @since 2.0.0
	 *
	 * @param string      $slug      Bundle slug without extension (e.g. atum-settings).
	 * @param string|null $attribute Optional. version|dependencies.
	 *
	 * @return string|array|null
	 */
	public static function get_asset_info( $slug, $attribute = NULL ) {

		// If a filename was passed, strip the extension.
		if ( str_contains( $slug, '.' ) ) {
			$slug_parts = explode( '.', $slug );
			$slug       = $slug_parts[0];
		}

		if ( file_exists( self::get_dist_path( "{$slug}.asset.php" ) ) ) {
			$asset = require_once self::get_dist_path( "{$slug}.asset.php" );
		}
		elseif ( file_exists( self::get_dist_path( "{$slug}.asset.php", 'css' ) ) ) {
			$asset = require_once self::get_dist_path( "{$slug}.asset.php", 'css' );
		}
		else {
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
	 *
	 * @return array
	 */
	public static function get_asset_dependencies( $slug, array $fallback = [] ) {
		$deps = self::get_asset_info( $slug, 'dependencies' );

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
	 *
	 * @return string
	 */
	public static function get_asset_version( $slug ) {
		$version = self::get_asset_info( $slug, 'version' );
		return $version ? (string) $version : ATUM_VERSION;
	}

	/**
	 * Filemtime-based cache-bust version for a file under `dist/vendor/`.
	 *
	 * @since 2.0.0
	 *
	 * @param string $filename Bare filename, e.g. `chart.bundle.min.js`.
	 *
	 * @return string
	 */
	public static function get_vendor_version( $filename ) {
		$path = self::get_dist_path( $filename, 'js', TRUE );
		return file_exists( $path ) ? (string) filemtime( $path ) : ATUM_VERSION;
	}

	/**
	 * Get the JS or CSS dist URL
	 *
	 * @since 2.0.0
	 *
	 * @param string $file_name The file name.
	 * @param string $kind		Optional. "css", "js", "images" or "fonts". Defaults to "js".
	 * @param bool   $is_vendor Optional. If it is a vendor asset. Defaults to false.
	 *
	 * @return string
	 */
	public static function get_dist_url( $file_name, $kind = 'js', $is_vendor = FALSE ) {

		$vendor_path = $is_vendor ? 'vendor' : '';
		$kind        = in_array( $kind, [ 'js', 'css', 'images', 'fonts' ] ) ? $kind : 'js';

		return ATUM_DIST_URL . "$kind/$vendor_path/$file_name";

	}

	/**
	 * Get the JS or CSS dist path
	 *
	 * @since 2.0.0
	 *
	 * @param string $file_name The file name.
	 * @param string $kind		Optional. "css", "js", "images" or "fonts". Defaults to "js".
	 * @param bool   $is_vendor Optional. If it is a vendor asset. Defaults to false.
	 *
	 * @return string
	 */
	public static function get_dist_path( $file_name, $kind = 'js', $is_vendor = FALSE ) {

		$vendor_path = $is_vendor ? 'vendor' : '';
		$kind        = in_array( $kind, [ 'js', 'css', 'images', 'fonts' ] ) ? $kind : 'js';

		return ATUM_DIST_PATH . "$kind/$vendor_path/$file_name";

	}

	/**
	 * Registers a script
	 *
	 * @since 2.0.0
	 *
	 * @param string   $handle
	 * @param string   $file_name
	 * @param string[] $deps
	 * @param bool     $in_footer
	 * @param bool     $is_vendor
	 */
	public static function register_script( $handle, $file_name, $deps = [], $is_vendor = FALSE, $in_footer = TRUE ) {
		wp_register_script(
			$handle,
			AtumAssets::get_dist_url( $file_name, 'js', $is_vendor ),
			! $is_vendor ? AtumAssets::get_asset_dependencies( $file_name, $deps ) : $deps,
			! $is_vendor ? AtumAssets::get_asset_version( $file_name ) : AtumAssets::get_vendor_version( $file_name ),
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
	 * @param bool     $in_footer
	 * @param bool     $is_vendor
	 */
	public static function register_style( $handle, $file_name, $deps = [], $is_vendor = FALSE ) {
		wp_register_style(
			$handle,
			AtumAssets::get_dist_url( $file_name, 'css', $is_vendor ),
			$deps,
			! $is_vendor ? AtumAssets::get_asset_version( $file_name ) : AtumAssets::get_vendor_version( $file_name )
		);
	}
	
}
