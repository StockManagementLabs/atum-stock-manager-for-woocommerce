<?php
/**
 * ATUM Inventory Management for WooCommerce
 *
 * @link                 https://www.stockmanagementlabs.com/
 * @since                0.0.1
 * @package              Atum
 *
 * @wordpress-plugin
 * Plugin Name:          ATUM Inventory Management for WooCommerce
 * Plugin URI:           https://www.stockmanagementlabs.com/
 * Description:          The ultimate stock management plugin for serious WooCommerce sellers
 * Version:              1.4.12
 * Author:               Stock Management Labs™
 * Author URI:           https://www.stockmanagementlabs.com/
 * Contributors:         Be Rebel Studio - https://berebel.io
 * Requires at least:    4.4
 * Tested up to:         4.9.6
 * Requires PHP:         5.6
 * WC requires at least: 3.0.0
 * WC tested up to:      3.4.3
 * Text Domain:          atum
 * Domain Path:          /languages
 * License:              GPLv2 or later
 * License URI:          http://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) or die;

if ( ! defined('ATUM_VERSION') ) {
	define( 'ATUM_VERSION', '1.4.12' );
}

if ( ! defined('ATUM_PATH') ) {
	define( 'ATUM_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined('ATUM_URL') ) {
	define( 'ATUM_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined('ATUM_BASENAME') ) {
	define( 'ATUM_BASENAME', plugin_basename( __FILE__ ) );
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
/** @noinspection PhpIncludeInspection */
require ATUM_PATH . 'vendor/autoload.php';
\Atum\Bootstrap::get_instance();