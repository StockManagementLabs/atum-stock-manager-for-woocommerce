<?php
/**
 * Manages the integration of ViteJS manifest with WordPress.
 *
 * @package ViteWordPress
 */

namespace ViteWordPress;

/**
 * Class ManifestResolver
 *
 * @phpstan-import-type ManifestChunk from ManifestResolverInterface
 */
class ManifestResolver implements ManifestResolverInterface {
	/**
	 * The parsed manifest data.
	 *
	 * @var array<string, ManifestChunk>
	 */
	private array $manifest;

	/**
	 * Path to the manifest file.
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * Directory where the source files are located.
	 *
	 * @var string
	 */
	private string $src_dir = 'src';

	/**
	 * Sets the manifest file path and resolves it.
	 *
	 * @param string $manifest_path Path to the manifest file.
	 *
	 * @return self
	 */
	public function set_manifest( string $manifest_path ): self {
		$this->path = $manifest_path;
		$this->resolve_manifest();

		return $this;
	}

	/**
	 * Sets the source directory.
	 *
	 * @param string $src_dir Path to the source directory.
	 *
	 * @return self
	 */
	public function set_src( string $src_dir ): self {
		$this->src_dir = $src_dir;

		return $this;
	}

	/**
	 * Checks if a specific entry exists in the manifest.
	 *
	 * @param string $id The identifier of the manifest entry.
	 *
	 * @return bool True if the entry exists, false otherwise.
	 */
	public function has( string $id ): bool {
		return isset( $this->get_manifest()[ "{$this->src_dir}/{$id}" ] );
	}

	/**
	 * Retrieves a manifest entry or the entire manifest.
	 *
	 * @param string|null $id The identifier of the manifest entry, or null to retrieve the entire manifest.
	 *
	 * @return ManifestChunk|array<string, ManifestChunk>|null The manifest entry, the entire manifest or null if not found.
	 */
	public function get( ?string $id = null ): ?array {
		return isset( $id ) ? $this->get_manifest()[ "{$this->src_dir}/{$id}" ] : $this->get_manifest();
	}

	/**
	 * Retrieves a manifest entry by file key.
	 *
	 * @param string $file The file value to search for.
	 *
	 * @return ManifestChunk|false The matching manifest entry or false if not found.
	 */
	public function get_by_file( string $file ) {
		foreach ( $this->get_manifest() as $item ) {
			if ( isset( $item['file'] ) && $item['file'] === $file ) {
				return $item;
			}
		}

		return false;
	}

	/**
	 * Retrieves a manifest entry by name key.
	 *
	 * @param string $name The name value to search for.
	 *
	 * @return ManifestChunk|false The matching manifest entry or false if not found.
	 */
	public function get_by_name( string $name ) {
		foreach ( $this->get_manifest() as $item ) {
			if ( isset( $item['name'] ) && $item['name'] === $name ) {
				return $item;
			}
		}

		return false;
	}

	/**
	 * Retrieves the parsed manifest data.
	 *
	 * @return array<string, ManifestChunk> The parsed manifest data.
	 * @throws \RuntimeException If the manifest has not been set.
	 */
	protected function get_manifest(): array {
		if ( ! isset( $this->manifest ) ) {
			throw new \RuntimeException(
				'ViteWordpress: Manifest has not been set yet.'
			);
		}

		return $this->manifest;
	}

	/**
	 * Resolves the manifest file and loads its content.
	 *
	 * @return void
	 * @throws \RuntimeException If the manifest file does not exist or cannot be parsed.
	 */
	protected function resolve_manifest() {
		$manifest_extension = pathinfo( $this->path, PATHINFO_EXTENSION );

		if ( ! file_exists( $this->path ) ) {
			throw new \RuntimeException(
				'ViteWordpress: Manifest file path does not exist. Try to run `yarn build` or `npm run build` first.'
			);
		}

		if ( 'json' === $manifest_extension ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$this->manifest = json_decode( file_get_contents( $this->path ), true );

			if ( json_last_error() ) {
				throw new \RuntimeException( esc_html( 'ViteWordpress: Error decoding manifest JSON: ' . json_last_error_msg() ) );
			}
		} elseif ( 'php' === $manifest_extension ) {
			$this->manifest = require $this->path;
		} else {
			throw new \RuntimeException( 'ViteWordpress: Unknown manifest file type.' );
		}
	}
}
