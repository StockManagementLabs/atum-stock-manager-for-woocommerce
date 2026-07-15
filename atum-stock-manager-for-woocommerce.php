<?php
/**
 * ATUM Inventory Management for WooCommerce
 *
 * @link                 https://stockmanagementlabs.com/
 * @package              Atum
 *
 * @wordpress-plugin
 * Plugin Name:          ATUM Inventory Management for WooCommerce
 * Requires Plugins:     woocommerce
 * Plugin URI:           https://stockmanagementlabs.com/
 * Description:          The ultimate stock management plugin for serious WooCommerce sellers
 * Version:              2.0.2
 * Author:               Stock Management Labs™
 * Author URI:           https://stockmanagementlabs.com/
 * Contributors:         BE REBEL - https://berebel.studio
 * Requires at least:    6.8
 * Tested up to:         7.0
 * Requires PHP:         7.4
 * WC requires at least: 8.0
 * WC tested up to:      10.9.4
 * Text Domain:          atum-stock-manager-for-woocommerce
 * Domain Path:          /languages
 * License:              GPLv2 or later
 * License URI:          http://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || die;

if ( ! defined( 'ATUM_VERSION' ) ) {
	define( 'ATUM_VERSION', '2.0.2' );
}

if ( ! defined( 'ATUM_WC_MINIMUM_VERSION' ) ) {
	define( 'ATUM_WC_MINIMUM_VERSION', '8.0' );
}

if ( ! defined( 'ATUM_WP_MINIMUM_VERSION' ) ) {
	define( 'ATUM_WP_MINIMUM_VERSION', '6.8' );
}

if ( ! defined( 'ATUM_PHP_MINIMUM_VERSION' ) ) {
	define( 'ATUM_PHP_MINIMUM_VERSION', '7.4' );
}

if ( ! defined( 'ATUM_PATH' ) ) {
	define( 'ATUM_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ATUM_URL' ) ) {
	define( 'ATUM_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ATUM_DIST_URL' ) ) {
	define( 'ATUM_DIST_URL', ATUM_URL . 'dist/' );
}

if ( ! defined( 'ATUM_DIST_PATH' ) ) {
	define( 'ATUM_DIST_PATH', ATUM_PATH . 'dist/' );
}

if ( ! defined( 'ATUM_BASENAME' ) ) {
	define( 'ATUM_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'ATUM_TEXT_DOMAIN' ) ) {
	define( 'ATUM_TEXT_DOMAIN', 'atum-stock-manager-for-woocommerce' );
}

if ( ! defined( 'ATUM_SHORT_NAME' ) ) {
	define( 'ATUM_SHORT_NAME', 'atum' );
}

if ( ! defined( 'ATUM_PREFIX' ) ) {
	define( 'ATUM_PREFIX', 'atum_' );
}

if ( ! defined( 'ATUM_DEBUG' ) ) {
	define( 'ATUM_DEBUG', FALSE );
}

// Check minimum PHP version required.
if ( version_compare( phpversion(), ATUM_PHP_MINIMUM_VERSION, '<' ) ) {

	add_action( 'admin_notices', function() {
		?>
		<div class="error fade">
			<p>
				<strong>
					<?php
					/* translators: the minimum PHP version */
					echo esc_html( sprintf( __( 'ATUM requires PHP version %s or greater. Please, update or contact your hosting provider.', ATUM_TEXT_DOMAIN ), ATUM_PHP_MINIMUM_VERSION ) ); ?>
				</strong>
			</p>
		</div>
		<?php
	} );

}
else {

	// Use Composer's autoloader and PSR4 for naming convention.
	require ATUM_PATH . 'vendor/autoload.php';

	\Atum\Inc\ViteDevServer::maybe_bootstrap();

	\Atum\Bootstrap::get_instance();

}
