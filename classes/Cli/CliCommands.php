<?php
/**
 * Class CliCommands
 *
 * @package        Atum
 * @subpackage     Cli
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2021 Stock Management Labs™
 *
 * @since          1.9.3.1
 */

namespace Atum\Cli;

use Atum\Components\AtumCache;
use Atum\Components\AtumCalculatedProps;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;


final class CliCommands {

	/**
	 * Executes the ATUM tool manage_stock from CLI.
	 *
	 * @since 1.9.3.1
	 *
	 * @param mixed $args
	 */
	public static function atum_tool_manage_stock( $args ) {

		if ( empty( $args ) ) {
			\WP_CLI::line( '' );
			\WP_CLI::line( __( 'Usage', ATUM_TEXT_DOMAIN ) . ': wp atum ' . __FUNCTION__ . ' <manage/unmanage>' );
			\WP_CLI::error( __( 'Missing parameter', ATUM_TEXT_DOMAIN ), FALSE );
			\WP_CLI::line( '' );
			exit();
		}

		switch ( $args[0] ) {
			case 'manage':
				$manage_status = 'yes';
				break;
			case 'unmanage':
				$manage_status = 'no';
				break;
			default:
				\WP_CLI::line( __( 'Usage', ATUM_TEXT_DOMAIN ) . ': wp atum ' . __FUNCTION__ . ' <manage/unmanage>' );
				\WP_CLI::error( __( 'Wrong parameter', ATUM_TEXT_DOMAIN ), FALSE );
				exit();
		}
		do_action( 'atum/cli/tool_change_manage_stock' );
		$message = Helpers::change_status_meta( '_manage_stock', $manage_status, TRUE );
		\WP_CLI::line( '' );
		\WP_CLI::success( $message );
		\WP_CLI::line( '' );
	}

	/**
	 * Executes the ATUM tool control_stock from CLI.
	 *
	 * @since 1.9.3.1
	 *
	 * @param mixed $args
	 */
	public static function atum_tool_control_stock( $args ) {

		if ( empty( $args ) ) {
			\WP_CLI::line( '' );
			\WP_CLI::line( __( 'Usage', ATUM_TEXT_DOMAIN ) . ': wp atum ' . __FUNCTION__ . ' <control/uncontrol>' );
			\WP_CLI::error( __( 'Missing parameter', ATUM_TEXT_DOMAIN ), FALSE );
			\WP_CLI::line( '' );
			exit();
		}

		switch ( $args[0] ) {
			case 'control':
				$control_status = 'yes';
				break;
			case 'uncontrol':
				$control_status = 'no';
				break;
			default:
				\WP_CLI::line( '' );
				\WP_CLI::line( __( 'Usage', ATUM_TEXT_DOMAIN ) . ': wp atum ' . __FUNCTION__ . ' <control/uncontrol>' );
				\WP_CLI::error( __( 'Wrong parameter', ATUM_TEXT_DOMAIN ), FALSE );
				\WP_CLI::line( '' );
				exit();
		}
		do_action( 'atum/cli/tool_change_control_stock' );
		$message = Helpers::change_status_meta( Globals::ATUM_CONTROL_STOCK_KEY, $control_status, TRUE );
		\WP_CLI::line( '' );
		\WP_CLI::success( $message );
		\WP_CLI::line( '' );
	}

	/**
	 * Executes the ATUM tool clear_out_stock_threshold from CLI.
	 *
	 * @since 1.9.3.1
	 */
	public static function atum_tool_clear_out_stock_threshold() {
		Helpers::force_rebuild_stock_status( NULL, TRUE, TRUE );

		\WP_CLI::line( '' );
		if ( FALSE === Helpers::is_any_out_stock_threshold_set() ) {
			do_action( 'atum/cli/tool_clear_out_stock_threshold' );
			\WP_CLI::success( __( 'All your previously saved values were cleared successfully.', ATUM_TEXT_DOMAIN ) );
			exit();
		}

		\WP_CLI::error( __( 'Something failed clearing the Out of Stock Threshold values', ATUM_TEXT_DOMAIN ), FALSE );
		\WP_CLI::line( '' );
	}

	/**
	 * Executes the ATUM tool update_calc_props from CLI.
	 *
	 * @since 1.9.3.1
	 */
	public static function atum_tool_update_calc_props() {

		global $wpdb;
		$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM $atum_product_data_table;" ); // phpcs:ignore WordPress.DB.NotPreparedSQL

		$products = $wpdb->get_col( "SELECT product_id FROM $atum_product_data_table" ); // phpcs:ignore WordPress.DB.NotPreparedSQL

		\WP_CLI::line( '' );
		$progress = \WP_CLI\Utils\make_progress_bar( __( 'Updating calculated properties', ATUM_TEXT_DOMAIN ), $total );
		foreach ( $products as $product_id ) {
			$product = Helpers::get_atum_product( $product_id );
			AtumCalculatedProps::update_atum_sales_calc_props_cli_call( $product );
			$progress->tick();
		}
		$progress->finish();
		do_action( 'atum/cli/tool_update_calc_props' );
		\WP_CLI::line( '' );
		\WP_CLI::success( __( 'Calculated properties successfully updated', ATUM_TEXT_DOMAIN ) );
		\WP_CLI::line( '' );
	}

	/**
	 * Executes the ATUM tool clear_out_atum_transients from CLI.
	 *
	 * @since 1.9.3.1
	 */
	public static function atum_tool_clear_out_atum_transients() {
		AtumCache::delete_transients();

		do_action( 'atum/cli/tool_clear_out_atum_transients' );
		\WP_CLI::line( '' );
		\WP_CLI::success( __( 'All your saved temporary data were cleared successfully.', ATUM_TEXT_DOMAIN ) );
		\WP_CLI::line( '' );

	}
}
