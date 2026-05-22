<?php
/**
 * Vite dev server integration for ATUM (wp-admin HMR).
 *
 * @package        Atum
 * @subpackage     Inc
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2026 Stock Management Labs™
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;

use ViteWordPress\DevServer;
use ViteWordPress\Manifest;

/**
 * Trust the local Vite dev server's self-signed certificate.
 *
 * mrottow/vite-wordpress hits the dev server with raw cURL (no SSL options
 * exposed), so `is_config_active()` returns false against an HTTPS dev server
 * with a self-signed cert from `@vitejs/plugin-basic-ssl` and the asset-URL
 * rewriting silently no-ops. We override the request to skip verification
 * for the localhost dev server only.
 */
final class AtumLocalhostDevServer extends DevServer {

	/**
	 * @inheritDoc
	 *
	 * @phpcsSuppress WordPress.WP.AlternativeFunctions
	 */
	protected function vite_server_request( string $api_url ): array {
		$curl = curl_init();
		curl_setopt_array( $curl, [
			CURLOPT_URL            => $api_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_TIMEOUT        => 2,
		] );

		$body     = curl_exec( $curl );
		$errors   = curl_error( $curl ) ?: NULL;
		$response = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		curl_close( $curl );

		$data = [];

		if ( NULL === $errors && $response >= 200 && $response < 300 ) {
			$data = json_decode( $body, TRUE );
		}

		return [ 'errors' => $errors, 'response' => $response, 'data' => $data ];
	}

}

final class ViteDevServer {

	/**
	 * Bootstrap the Vite dev server when running locally with `bun run dev`.
	 *
	 * @since 2.0.0
	 */
	public static function maybe_bootstrap() {

		// Run once per request (defensive: avoids double-registering the
		// asset filters if this is hit from more than one entry point).
		static $bootstrapped = FALSE;
		if ( $bootstrapped ) {
			return;
		}
		$bootstrapped = TRUE;

		if ( ! is_admin() ) {
			return;
		}

		$is_local_env = in_array( wp_get_environment_type(), [ 'local', 'development' ], TRUE );
		$home_url     = home_url();
		$is_local_url = str_contains( $home_url, '.loc' )
			|| str_contains( $home_url, '.local' )
			|| str_contains( $home_url, 'localhost' );

		if ( ! $is_local_env || ! $is_local_url ) {
			return;
		}

		if ( ! class_exists( DevServer::class ) || ! class_exists( Manifest::class ) ) {
			return;
		}

		$manifest_path = ATUM_DIST_PATH . '.vite/manifest.json';

		if ( ! file_exists( $manifest_path ) ) {
			return;
		}

		$manifest = Manifest::create( $manifest_path );

		if ( ! $manifest ) {
			return;
		}

		$dev_server = new AtumLocalhostDevServer( $manifest );
		$dev_server
			->set_server_port( 5173 )
			->set_server_host( 'https://localhost' );

		if ( ! $dev_server->is_config_active() ) {
			return;
		}

		$dev_server->register();

		/*
		 * Dev mode policy for CSS: keep the static <link> pointing at the
		 * built `dist/css/<slug>.css` (no rewrite, no script-module).
		 *
		 * Rationale: serving SCSS through Vite as a JS module that injects
		 * a <style> tag has nontrivial cascade/ordering side-effects that
		 * caused broken layouts under our setup. The clean Vite pattern for
		 * CSS HMR is to `import './style.scss'` from the JS entry — outside
		 * the scope of this fix. Until then, we keep the production CSS in
		 * dev too, and CSS edits require `bun run build` to be visible.
		 *
		 * mrottow's `register()` already added a `style_loader_src` filter
		 * that rewrites the stylesheet URLs to the dev server. We remove it
		 * here so the `<link>` keeps the original `dist/` URL.
		 */
		remove_filter( 'style_loader_src', [ $dev_server, 'filter_asset_loader_src' ], 999 );

		add_action( 'admin_head', [ self::class, 'inject_vite_client' ], 5 );

		if ( ! defined( 'ATUM_VITE_DEV_SERVER_ACTIVE' ) ) {
			define( 'ATUM_VITE_DEV_SERVER_ACTIVE', TRUE );
		}
	}

	/**
	 * Inject the Vite client in wp-admin (DevServer only hooks wp_head by default).
	 *
	 * @since 2.0.0
	 */
	public static function inject_vite_client() {
		if ( ! defined( 'ATUM_VITE_DEV_SERVER_ACTIVE' ) || ! ATUM_VITE_DEV_SERVER_ACTIVE ) {
			return;
		}

		$manifest_path = ATUM_DIST_PATH . '.vite/manifest.json';

		if ( ! file_exists( $manifest_path ) || ! class_exists( Manifest::class ) ) {
			return;
		}

		$manifest = Manifest::create( $manifest_path );

		if ( ! $manifest ) {
			return;
		}

		$dev_server = new AtumLocalhostDevServer( $manifest );
		$dev_server
			->set_server_port( 5173 )
			->set_server_host( 'https://localhost' );

		if ( $dev_server->is_config_active() && $dev_server->is_client_active() ) {
			$dev_server->inject_vite_client();
		}
	}

}
