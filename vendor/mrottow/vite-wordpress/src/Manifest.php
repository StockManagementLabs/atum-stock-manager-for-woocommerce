<?php
/**
 * Facade for {@see \ViteWordPress\ManifestResolver}
 *
 * Provides a static interface for interacting with a manifest resolver.
 *
 * @package ViteWordPress
 */

namespace ViteWordPress;

/**
 * Class Manifest
 *
 * @phpstan-import-type ManifestChunk from ManifestResolverInterface
 */
class Manifest {
	/**
	 * The singleton instance of the manifest resolver.
	 *
	 * @var ManifestResolverInterface
	 */
	protected static ManifestResolverInterface $manifest;

	/**
	 * Creates and initializes the manifest resolver instance.
	 *
	 * @param string|null $manifest_path Path to the manifest file. Optional.
	 * @param string|null $src_dir Path to the source directory. Optional.
	 *
	 * @return ManifestResolverInterface The initialized manifest resolver instance.
	 */
	public static function create( ?string $manifest_path = null, ?string $src_dir = null ): ManifestResolverInterface {
		if ( ! isset( static::$manifest ) ) {
			static::$manifest = ( new ManifestResolver() )->set_manifest( $manifest_path );

			if ( isset( $src_dir ) ) {
				static::$manifest->set_src( $src_dir );
			}
		}

		return static::$manifest;
	}

	/**
	 * Checks if the manifest contains a specific asset by its ID.
	 *
	 * @param string $id The asset ID.
	 *
	 * @return bool True if the asset exists; false otherwise.
	 */
	public static function has( string $id ): bool {
		return static::$manifest->has( $id );
	}

	/**
	 * Retrieves an asset entry from the manifest by its ID.
	 *
	 * @param string|null $id The asset ID. If null, returns the entire manifest.
	 *
	 * @return ManifestChunk|array<string, ManifestChunk>|null The manifest entry, the entire manifest or null if not found.
	 */
	public static function get( ?string $id = null ): ?array {
		return static::$manifest->get( $id );
	}

	/**
	 * Retrieves asset entry from the manifest by file key.
	 *
	 * @param string $file The file value to search for.
	 *
	 * @return ManifestChunk|false The matching manifest entry or false if not found.
	 */
	public static function get_by_file( string $file ) {
		return static::$manifest->get_by_file( $file );
	}

	/**
	 * Retrieves asset entry from the manifest by name key.
	 *
	 * @param string $name The name value to search for.
	 *
	 * @return ManifestChunk|false The matching manifest entry or false if not found.
	 */
	public static function get_by_name( string $name ) {
		return static::$manifest->get_by_name( $name );
	}

	/**
	 * Retrieves the file name for a specific asset by its ID.
	 *
	 * @param string $id The asset ID.
	 *
	 * @return string The file path, or an empty string if not found.
	 */
	public static function get_file( string $id ): string {
		$entry = static::get( $id );

		return $entry['file'] ?? '';
	}

	/**
	 * Retrieves the imported CSS associated with a specific asset by its ID.
	 *
	 * @param string $id The asset ID.
	 *
	 * @return string[] An array of CSS file names, or an empty array if none are found.
	 */
	public static function get_css( string $id ): array {
		$entry = static::get( $id );

		return $entry['css'] ?? [];
	}

	/**
	 * Retrieves the singleton instance of the manifest resolver.
	 *
	 * @return ManifestResolverInterface The manifest resolver instance.
	 */
	public static function get_instance(): ManifestResolverInterface {
		return static::$manifest;
	}
}
