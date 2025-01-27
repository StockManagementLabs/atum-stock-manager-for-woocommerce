<?php
/**
 * Class AtumCli for WP CLI calls.
 *
 * @package        Atum
 * @subpackage     Cli
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2025 Stock Management Labs™
 *
 * @since          1.9.3.1
 */

namespace Atum\Cli;

defined( 'ABSPATH' ) || die;

use Atum\Settings\Tools;

class AtumCli {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumCli
	 */
	private static $instance;

	/**
	 * Tools list.
	 *
	 * @var array
	 */
	private $commands = [];

	/**
	 * Ajax singleton constructor.
	 *
	 * @since 1.9.3.1
	 */
	private function __construct() {

		add_action( 'cli_init', array( $this, 'init' ) );

	}

	/**
	 * Store ATUM tools in AtumCli commands
	 *
	 * @since 1.9.3.1
	 *
	 * @param array  $args
	 * @param string $namespace
	 */
	public function add_tools_to_cli_commands( $args, $namespace ) {

		$class = explode( '\\', $namespace )[0] . '\\Cli\\CliCommands';

		foreach ( $args as $i => $item ) {

			if ( 'tools' !== $item['group'] || 'tools' !== $item['section'] ) {
				continue;
			}

			$this->commands[ $i ] = array(
				'desc'   => $item['desc'],
				'action' => $item['options']['script_action'],
				'class'  => $class,
			);
		}

	}

	/**
	 * Registers ATUM CLI commands and hooks to WP_CLI.
	 *
	 * @since 1.9.3.1
	 */
	public function init() {

		// Add tools to ATUM CLI commands.
		if ( method_exists( '\WP_CLI', 'add_hook' ) ) {
			\WP_CLI::add_hook( 'before_add_command:atum', array( $this, 'add_tools_to_cli_commands' ) );
		}

		if ( method_exists( '\WP_CLI', 'do_hook' ) ) {
			\WP_CLI::do_hook( 'before_add_command:atum', Tools::get_instance()->add_settings_defaults( [] ), 'Atum\Settings' );

			do_action( 'atum/cli/register_hooks', $this );
		}

		// Add ATUM CLI commands to WP CLI.
		if ( method_exists( '\WP_CLI', 'add_command' ) ) {

			$parent = 'atum';
			\WP_CLI::add_command( "$parent list", array( $this, 'display_commands_list' ) );

			foreach ( $this->commands as $command => $content ) {
				// $function = $this->find_hooked_function( 'wp_ajax_' . $content['action'] );
				$function = array( $content['class'], $content['action'] );

				if ( class_exists( $content['class'] ) && method_exists( $content['class'], $content['action'] ) ) {
					\WP_CLI::add_command( "$parent $command", $function );
				}
			}

		}

	}

	/**
	 * Shows help about 'wp atum' command
	 *
	 * @since 1.9.3.1
	 */
	public function display_commands_list() {

		\WP_CLI::line( '' );
		\WP_CLI::line( 'usage: ' . \WP_CLI::colorize( '%Wwp atum list%n' ) );
		\WP_CLI::line( 'Available commands:' );

		$list = array();

		foreach ( $this->commands as $command => $content ) {
			if ( str_contains( $content['desc'], '<br>' ) ) {
				$desc = explode( '<br>', $content['desc'] )[0];
			}
			else {
				$desc = $content['desc'];
			}
			$list[] = array(
				'Command'     => \WP_CLI::colorize( "%Wwp atum $command%n" ),
				'Description' => str_replace( '<br>', ' ', $desc ),
			);
		}

		\WP_CLI::line( '' );
		\WP_CLI\Utils\format_items( 'table', $list, array( 'Command', 'Description' ) );
		\WP_CLI::line( '' );

	}

	/**
	 * Write TABs for console output.
	 *
	 * @since 1.9.3.1
	 *
	 * @param int $number
	 *
	 * @return string
	 */
	private function tab( $number ) {
		$tb = "\t";

		$response = '';

		for ( $i = 0; $i < $number; $i++ ) {
			$response .= $tb;
		}

		return $response;
	}

	/**
	 * Finds the function called by the ajax action hook,
	 *
	 * @since 1.9.3.1
	 *
	 * @param string $hook
	 *
	 * @return string|array;
	 */
	private function find_hooked_function( $hook ) {
		global $wp_filter;

		$function = FALSE;

		foreach ( $wp_filter as $key => $val ) {
			if ( $hook === $key ) {
				foreach ( $val->callbacks as $callback ) {
					foreach ( $callback as $f ) {
						$function = $f['function'];
					}
				}
			}
		}

		return $function;
	}

	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return AtumCli instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

