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
function _manually_load_plugin() {
	//Woocommerce is required for ATUM
	require dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce/woocommerce.php';
	//ATUM requires the manage stock option enabled
	update_option( 'woocommerce_manage_stock', 'yes' );
	//Load ATUM
	require dirname( dirname( __FILE__ ) ) . '/atum-stock-manager-for-woocommerce.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
