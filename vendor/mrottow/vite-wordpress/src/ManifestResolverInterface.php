<?php
/**
 * Interface for the Manifest Resolver.
 *
 * @package ViteWordPress
 */

namespace ViteWordPress;

/**
 * Interface ManifestResolverInterface
 *
 * @phpstan-type ManifestChunk array{
 *        name: string,
 *        file: string,
 *        src: string,
 *        isEntry: bool,
 *        css: string[]
 *    }
 */
interface ManifestResolverInterface {
	/**
	 * Sets the manifest file path and resolves it.
	 *
	 * @param string $manifest_path Path to the manifest file.
	 *
	 * @return self
	 */
	public function set_manifest( string $manifest_path ): self;

	/**
	 * Sets the source directory.
	 *
	 * @param string $src_dir Path to the source directory.
	 *
	 * @return self
	 */
	public function set_src( string $src_dir ): self;

	/**
	 * Checks if a specific entry exists in the manifest.
	 *
	 * @param string $id The identifier of the manifest entry.
	 *
	 * @return bool True if the entry exists, false otherwise.
	 */
	public function has( string $id ): bool;

	/**
	 * Retrieves a manifest entry or the entire manifest.
	 *
	 * @param string|null $id The identifier of the manifest entry, or null to retrieve the entire manifest.
	 *
	 * @return ManifestChunk|array<string, ManifestChunk>|null The manifest entry, the entire manifest or null if not found.
	 */
	public function get( ?string $id = null ): ?array;

	/**
	 * Retrieves a manifest entry by file key.
	 *
	 * @param string $file The file value to search for.
	 *
	 * @return ManifestChunk|false The matching manifest entry or false if not found.
	 */
	public function get_by_file( string $file );

	/**
	 * Retrieves a manifest entry by name key.
	 *
	 * @param string $name The name value to search for.
	 *
	 * @return ManifestChunk|false The matching manifest entry or false if not found.
	 */
	public function get_by_name( string $name );
}
