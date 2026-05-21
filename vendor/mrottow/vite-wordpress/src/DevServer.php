<?php
/**
 * Manages the integration of ViteJS HMR & dev server with WordPress during development.
 *
 * @package ViteWordPress
 */

namespace ViteWordPress;

/**
 * Class DevServer
 *
 * @phpstan-import-type PluginConfig from DevServerInterface
 * @phpstan-import-type ManifestChunk from ManifestResolverInterface
 */
class DevServer implements DevServerInterface {
	/**
	 * Vite server host.
	 *
	 * @var string
	 */
	protected string $vite_server_host;

	/**
	 * Vite server port.
	 *
	 * @var string
	 */
	protected string $vite_server_port = '5173';

	/**
	 * Vite plugin configuration array.
	 *
	 * @var PluginConfig|null
	 */
	protected ?array $vite_config = null;

	/**
	 * Vite build map array from the server.
	 *
	 * @var array<string, ManifestChunk>|null
	 */
	protected ?array $vite_build = null;

	/**
	 * Vite client hook priority.
	 *
	 * @var int
	 */
	protected int $vite_client_hook_priority = 5;

	/**
	 * Manifest resolver.
	 *
	 * @var ManifestResolverInterface|null
	 */
	protected ?ManifestResolverInterface $manifest = null;

	/**
	 * Resolved assets list.
	 *
	 * @var string[]
	 */
	protected array $resolved_assets = [];

	/**
	 * Constructor.
	 *
	 * @param ManifestResolverInterface|null $manifest Manifest resolver.
	 */
	public function __construct( ?ManifestResolverInterface $manifest = null ) {
		$this->vite_server_host = get_site_url();

		if ( null !== $manifest ) {
			$this->manifest = $manifest;
		}
	}

	/**
	 * Registers hooks and filters for the dev server.
	 *
	 * @return self
	 */
	public function register(): self {
		if ( $this->is_config_active() && $this->is_client_active() ) {
			add_action( 'wp_head', [ $this, 'inject_vite_client' ], $this->vite_client_hook_priority );
			add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'inject_into_elementor_editor' ] );
			add_action( 'init', [ $this, 'prioritize_import_map_hook' ] );
			add_filter( 'body_class', [ $this, 'filter_body_class' ], 999 );
			add_filter( 'script_module_loader_src', [ $this, 'filter_asset_loader_src' ], 999, 2 );
			add_filter( 'script_loader_src', [ $this, 'filter_asset_loader_src' ], 999, 2 );
			add_filter( 'style_loader_src', [ $this, 'filter_asset_loader_src' ], 999, 2 );
			add_filter( 'script_loader_tag', [ $this, 'filter_asset_loader_tags' ], 999, 3 );
			add_filter( 'block_type_metadata', [ $this, 'filter_block_type_metadata' ] );
		}

		return $this;
	}

	/**
	 * Sets the Vite server host.
	 *
	 * @param string $server_host The server host.
	 *
	 * @return self
	 */
	public function set_server_host( string $server_host ): self {
		$this->vite_server_host = $server_host;

		return $this;
	}

	/**
	 * Sets the Vite server port.
	 *
	 * @param int $server_port The server port.
	 *
	 * @return self
	 */
	public function set_server_port( int $server_port ): self {
		$this->vite_server_port = (string) $server_port;

		return $this;
	}

	/**
	 * Sets the priority for the Vite client hook.
	 *
	 * @param int $level The priority level.
	 *
	 * @return self
	 */
	public function set_client_hook( int $level ): self {
		$this->vite_client_hook_priority = $level;

		return $this;
	}

	/**
	 * Sets the config (mainly used for tests).
	 *
	 * @param PluginConfig $config The config.
	 *
	 * @return $this
	 */
	public function set_config( array $config ): self {
		$this->vite_config = $config;

		return $this;
	}

	/**
	 * Makes a request to the Vite dev server.
	 *
	 * @param string $api_url API endpoint URL.
	 *
	 * @return array{
	 *     errors: string|null,
	 *     response: int,
	 *     data: array<string, mixed>
	 * }
	 */
	protected function vite_server_request( string $api_url ): array {
		// phpcs:disable WordPress.WP.AlternativeFunctions
		$curl    = curl_init();
		$options = [
			CURLOPT_URL            => $api_url,
			CURLOPT_RETURNTRANSFER => true,
		];

		curl_setopt_array( $curl, $options );

		$json_data = curl_exec( $curl );
		$errors    = curl_error( $curl ) ? curl_error( $curl ) : null;
		$response  = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		curl_close( $curl );
		// phpcs:enable

		$data = [];

		if ( null === $errors && $response >= 200 && $response < 300 ) {
			$data = json_decode( $json_data, true );
		}

		return [
			'errors'   => $errors,
			'response' => $response,
			'data'     => $data,
		];
	}

	/**
	 * Checks if the Vite client is active.
	 *
	 * @return bool True if the client is active, false otherwise.
	 */
	public function is_client_active(): bool {
		$request = $this->vite_server_request( $this->get_client_url() );

		return ! ( ! empty( $request['errors'] ) || ( $request['response'] ?? 0 ) !== 200 );
	}

	/**
	 * Checks if the Vite plugin config is active.
	 *
	 * @return bool True if the plugin config is active, false otherwise.
	 */
	public function is_config_active(): bool {
		$request = $this->vite_server_request( $this->get_config_url() );

		if ( ! empty( $request['errors'] ) || ( $request['response'] ?? 0 ) !== 200 ) {
			return false;
		}

		$this->vite_config = $request['data'];
		$this->vite_build  = ! empty( $request['data']['buildMap'] )
			? $request['data']['buildMap']
			: null;

		return true;
	}

	/**
	 * Injects the Vite.js script into the WordPress head section.
	 *
	 * @return void
	 */
	public function inject_vite_client(): void {
		// phpcs:disable WordPress.WP.EnqueuedResources
		?>
		<script type="module" src="<?php echo esc_url( $this->get_client_url() ); ?>"></script>
		<script type="module">window.process = {env: {NODE_ENV: 'development'}};</script>
		<?php
		// phpcs:enable
	}

	/**
	 * Hooks vite client into Elementor editor head (wp_head hook doesn't trigger in the editor).
	 *
	 * @return void
	 */
	public function inject_into_elementor_editor() {
		if ( class_exists( 'Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$this->inject_vite_client();
		}
	}

	/**
	 * Re-hooks the WordPress import map to a more prioritized position.
	 *
	 * This ensures the WP import map is loaded before the Vite client script (for modules).
	 *
	 * @return void
	 */
	public function prioritize_import_map_hook(): void {
		global $wp_script_modules;

		if ( isset( $wp_script_modules ) ) {
			$position = wp_is_block_theme() ? 'wp_head' : 'wp_footer';
			remove_action( $position, [ $wp_script_modules, 'print_import_map' ] );
			add_action( $position, [ $wp_script_modules, 'print_import_map' ], $this->vite_client_hook_priority - 1 );
		}
	}

	/**
	 * Adds a class to the body element if the Vite dev server is active.
	 *
	 * @param string[] $classes Body classes.
	 *
	 * @return string[]
	 */
	public function filter_body_class( array $classes ): array {
		$classes[] = 'vite-dev-server-is-active';

		return $classes;
	}

	/**
	 * Filters the block type metadata render path and resolves it to the un-compiled file.
	 *
	 * @param array<string, mixed> $metadata Block metadata.
	 *
	 * @return array<string, mixed> Modified metadata.
	 */
	public function filter_block_type_metadata( array $metadata ): array {
		if ( ! isset( $metadata['render'] ) ) {
			return $metadata;
		}

		$block_dir_path   = dirname( $metadata['file'] );
		$render_file_path = path_join( $block_dir_path, basename( $metadata['render'] ) );

		if ( ! $this->contains_base( $render_file_path ) || ! is_file( $render_file_path ) ) {
			return $metadata;
		}

		$resolved_path = $this->get_source_path( $render_file_path );

		if ( $resolved_path ) {
			$resolved_path = "{$this->get_server_path()}/{$resolved_path}";

			$metadata['render'] = $this->get_relative_local_path( $block_dir_path, $resolved_path );
		}

		return $metadata;
	}

	/**
	 * Filters the script loader src and resolves it with the
	 * un-compiled URL from the dev server.
	 *
	 * @param string $src The original script source URL.
	 * @param string $id The script ID/Handle.
	 *
	 * @return string The modified or original source URL.
	 */
	public function filter_asset_loader_src( string $src, string $id ): string {
		if ( ! $this->contains_base( $src ) ) {
			return $src;
		}

		if ( isset( $this->resolved_assets[ $id ] ) ) {
			return $this->resolved_assets[ $id ];
		}

		$resolved_path = $this->get_source_path( $src );

		if ( $resolved_path ) {
			$resolved_path = "{$this->get_base_url()}/{$resolved_path}";

			$this->resolved_assets[ $id ] = $resolved_path;

			return $resolved_path;
		}

		return $src;
	}

	/**
	 * Ensures we use the import syntax when loading un-compiled
	 * scripts from the dev server.
	 *
	 * This function ensures that block modules are not affected and only
	 * modifies the required scripts.
	 *
	 * @param string $tag The original script tag.
	 * @param string $id The script ID/Handle.
	 * @param string $src The script source URL.
	 *
	 * @return string The modified script tag or the original.
	 */
	public function filter_asset_loader_tags( string $tag, string $id, string $src ): string {
		/** At this point the src already got modified by {@see self::filter_asset_loader_src()} */
		if ( $this->contains_server_url( $src ) && isset( $this->resolved_assets[ $id ] ) ) {
			return '<script type="module" src="' . esc_url( $src ) . '"></script>'; // phpcs:disable WordPress.WP.EnqueuedResources
		}

		return $tag;
	}

	/**
	 * Resolves an asset path either from the manifest or the file system.
	 *
	 * @param string $file_path The original file path.
	 *
	 * @return string|false The source path (relative from the srcDir) or false if not found.
	 */
	public function get_source_path( string $file_path ) {
		$file_name = $this->get_file_name( $file_path );

		if ( false === $file_name ) {
			return false;
		}

		// Check if manifest exists and resolve from the manifest.
		if ( isset( $this->manifest ) ) {
			$manifest_entry = $this->manifest->get_by_file( $file_name );

			if ( $manifest_entry && isset( $manifest_entry['src'] ) ) {
				return $manifest_entry['src'];
			}
		}

		// If not resolved from the manifest, try resolving via server's build map.
		if ( isset( $this->vite_build[ $file_name ] ) ) {
			$build_entry = $this->vite_build[ $file_name ];

			if ( isset( $build_entry['src'] ) ) {
				return $build_entry['src'];
			}
		}

		// If not resolved from the build map, try resolving from the file system.
		$file_name        = str_replace( '.css', ".{$this->get_config('css')}", $file_name );
		$file_system_path = "{$this->get_server_path()}/{$this->get_config('srcDir')}/{$file_name}";

		if ( file_exists( $file_system_path ) ) {
			return "{$this->get_config('srcDir')}/{$file_name}";
		}

		return false;
	}

	/**
	 * Gets the relative local path for block.json. This approach is based
	 * on how npm handles local paths for packages.
	 *
	 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#wpdefinedpath
	 *
	 * @example
	 * ```
	 * $from = '/absolute/path/to/my/folder/';
	 * $to = '/absolute/path/to/my/local/render.php';
	 *
	 * echo $this->get_relative_local_path($from, $to);
	 * // Output: "file:./../local/render.php"
	 * ```
	 *
	 * @param string $from The path from which it needs to be relative from.
	 * @param string $to The path to construct the relative path.
	 *
	 * @return string
	 */
	public function get_relative_local_path( string $from, string $to ): string {
		$from_parts = explode( DIRECTORY_SEPARATOR, rtrim( $from, DIRECTORY_SEPARATOR ) );
		$to_parts   = explode( DIRECTORY_SEPARATOR, rtrim( $to, DIRECTORY_SEPARATOR ) );

		// Remove common prefix.
		while ( $from_parts && $to_parts && $from_parts[0] === $to_parts[0] ) {
			array_shift( $from_parts );
			array_shift( $to_parts );
		}

		// Construct relative path.
		return 'file:./' . str_repeat( '..' . DIRECTORY_SEPARATOR, count( $from_parts ) ) . implode( DIRECTORY_SEPARATOR, $to_parts );
	}

	/**
	 * Removes query parameters, base and outDir from path to get the file name.
	 *
	 * @param string $path Path to get the file name from.
	 *
	 * @return false|string The file name or false if no valid file name.
	 */
	public function get_file_name( string $path ) {
		$file_name = preg_replace( '/\?.*$/', '', $path );
		$file_name = explode( "{$this->get_config( 'base' )}/{$this->get_config('outDir')}/", $file_name );

		return ! isset( $file_name[1] ) ? false : $file_name[1];
	}

	/**
	 * Get the URL to the Vite plugin configuration.
	 *
	 * @return string Full URL to the Vite plugin config (eg: my-host:5173/vite-wordpress.json).
	 */
	public function get_config_url(): string {
		return "{$this->get_server_url()}/vite-wordpress.json";
	}

	/**
	 * Get the URL to the Vite client.
	 *
	 * @return string Full URL to the Vite client (eg: my-host:5173/wp-content/plugins/my-plugin/@vite/client).
	 */
	public function get_client_url(): string {
		return "{$this->get_base_url()}/@vite/client";
	}

	/**
	 * Get the server base URL for Vite.
	 *
	 * @return string The base URL (eg: my-host:5173/wp-content/plugins/my-plugin).
	 */
	public function get_base_url(): string {
		return untrailingslashit( "{$this->get_server_url()}{$this->get_config( 'base' )}" );
	}

	/**
	 * Get the server URL for Vite.
	 *
	 * @return string The base URL (eg: my-host:5173).
	 */
	public function get_server_url(): string {
		return "{$this->vite_server_host}:{$this->vite_server_port}";
	}

	/**
	 * Get the server path for Vite.
	 *
	 * @return string Server path (eg: server/path/wp-content/plugins/my-plugin).
	 */
	public function get_server_path(): string {
		return untrailingslashit( ABSPATH ) . $this->get_config( 'base' );
	}

	/**
	 * Get the server port.
	 *
	 * @return string The server port (eg: 5173).
	 */
	public function get_server_port(): string {
		return $this->vite_server_port;
	}

	/**
	 * Get the server host.
	 *
	 * @return string The server host (eg: my-host).
	 */
	public function get_server_host(): string {
		return $this->vite_server_host;
	}

	/**
	 * Get the vite plugin config.
	 *
	 * @param string|null $key Config key to get.
	 *
	 * @return PluginConfig|string|boolean|null The plugin config.
	 */
	public function get_config( ?string $key = null ) {
		if ( ! isset( $this->vite_config ) ) {
			return null;
		}

		return isset( $key ) ? $this->vite_config[ $key ] : $this->vite_config;
	}

	/**
	 * Check if the path contains the base path.
	 *
	 * @param string $path Source path.
	 *
	 * @return bool Whether the path contains the base path.
	 */
	public function contains_base( string $path ): bool {
		if ( '' !== $this->get_config( 'base' ) && false !== strpos( $path, $this->get_config( 'base' ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if the URL contains the server base URL.
	 *
	 * @param string $url Source URL.
	 *
	 * @return bool Whether the URL contains the server base URL.
	 */
	public function contains_server_url( string $url ): bool {
		if ( '' !== $this->get_base_url() && false !== strpos( $url, $this->get_base_url() ) ) {
			return true;
		} else {
			return false;
		}
	}
}
