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

final class ViteDevServer {

	/**
	 * Bootstrap the Vite dev server when running locally with `bun run dev`.
	 *
	 * @since 2.0.0
	 */
	public static function maybe_bootstrap() {
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

		$dev_server = new DevServer( $manifest );
		$dev_server
			->set_server_port( 5173 )
			->set_server_host( 'http://localhost' );

		if ( ! $dev_server->is_config_active() ) {
			return;
		}

		$dev_server->register();

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

		$dev_server = new DevServer( $manifest );
		$dev_server
			->set_server_port( 5173 )
			->set_server_host( 'http://localhost' );

		if ( $dev_server->is_config_active() && $dev_server->is_client_active() ) {
			$dev_server->inject_vite_client();
		}
	}

}
