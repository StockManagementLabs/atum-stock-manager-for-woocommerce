<?php
/**
 * ATUM Stock Manager for WooCommerce
 *
 * @link              https://www.stockmanagementlabs.com/
 * @since             0.0.1
 * @package           Atum
 *
 * @wordpress-plugin
 * Plugin Name:          ATUM Stock Manager for WooCommerce
 * Plugin URI:           https://www.stockmanagementlabs.com/
 * Description:          The ultimate stock management plugin for serious WooCommerce sellers
 * Version:              1.3.8.1
 * Author:               Stock Management Labs™
 * Author URI:           https://www.stockmanagementlabs.com/
 * Contributors:         Salva Machí and Jose Piera - https://sispixels.com
 * Requires at least:    4.4
 * Tested up to:         4.9.1
 * Requires PHP:         5.6
 * WC requires at least: 3.0.0
 * WC tested up to:      3.2.6
 * Text Domain:          atum
 * Domain Path:          /languages
 * License:              GPLv2 or later
 * License URI:          http://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) or die;

if ( ! defined('ATUM_VERSION') ) {
	define( 'ATUM_VERSION', '1.3.8.1' );
}

if ( ! defined('ATUM_PATH') ) {
	define( 'ATUM_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined('ATUM_URL') ) {
	define( 'ATUM_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined('ATUM_TEXT_DOMAIN') ) {
	define( 'ATUM_TEXT_DOMAIN', 'atum' );
}

if ( ! defined('ATUM_PREFIX') ) {
	define( 'ATUM_PREFIX', 'atum_' );
}

if ( ! defined('ATUM_DEBUG') ) {
	define( 'ATUM_DEBUG', FALSE );
}


// Use Composer's autoloader and PSR4 for naming convention
require ATUM_PATH . 'vendor/autoload.php';
\Atum\Bootstrap::get_instance();