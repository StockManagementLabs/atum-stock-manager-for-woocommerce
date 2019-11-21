<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function atum_manually_load_plugins() {
	// Woocommerce is required for ATUM.
	require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce/woocommerce.php';
	// ATUM requires the manage stock option enabled.
	update_option( 'woocommerce_manage_stock', 'yes' );
	// Load ATUM.
	require_once dirname( dirname( __FILE__ ) ) . '/atum-stock-manager-for-woocommerce.php';
	require ATUM_PATH . 'vendor/autoload.php';
	\Atum\Bootstrap::get_instance();
}

/**
 * Manually install the plugin being tested.
 */
function atum_manually_install_plugins() {
	//Woocommerce installation
	define( 'WP_UNINSTALL_PLUGIN', true );
	define( 'WC_REMOVE_ALL_DATA', true );
	include_once dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce/uninstall.php';
	WC_Install::install();
	// Reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374
	if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
		$GLOBALS['wp_roles']->reinit();
	} else {
		$GLOBALS['wp_roles'] = null; // WPCS: override ok.
		wp_roles();
	}
	echo esc_html( 'Installing WooCommerce...' . PHP_EOL );

	//ATUM Stock Manager installation
	include_once dirname( dirname( __FILE__ ) ) . '/classes/Inc/Upgrade.php';
	new \Atum\Inc\Upgrade( '0.0.1' );
	\Atum\Inc\Main::get_instance()->load_modules();
	echo esc_html( 'Installing ATUM...' . PHP_EOL );

	// Load helpers methods
	include 'helpers.php';
	echo esc_html( 'Loading helper functions...' . PHP_EOL );
}


tests_add_filter( 'muplugins_loaded', 'atum_manually_load_plugins' );
tests_add_filter( 'setup_theme', 'atum_manually_install_plugins' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
