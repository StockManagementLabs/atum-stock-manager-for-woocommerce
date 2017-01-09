<?php
/**
 * @package     Atum
 * @author      Salva Machí <salvamb@sispixels.com>
 * @copyright   (c)2017 Stock Management Labs
 *
 * @since 0.0.1
 *
 * Main loader
 */

namespace Atum;

defined( 'ABSPATH' ) or die;

use Atum\Inc\Ajax;
use Atum\Inc\Init;


class Bootstrap {
	
	/**
	 * The singleton instance holder
	 * @var Bootstrap
	 */
	private static $instance;
	
	/**
	 * Flag to indicate the plugin has been boostrapped
	 * @var bool
	 */
	private $bootstrapped = FALSE;
	
	/**
	 * Error message holder
	 * @var string
	 */
	private $admin_message;
	
	const ALREADY_BOOTSTRAPED = 1;
	const DEPENDENCIES_UNSATISFIED = 2;
	

	private function __construct() {

		// Disregard WP AJAX 'heartbeat' calls. Why waste resources?
		if ( is_admin() && isset( $_POST['action'] ) && $_POST['action'] == 'heartbeat' ) {

			// Hook, for purists.
			if ( ! has_action( 'atum/admin/ajax/heartbeat' ) ) {
				do_action( 'atum/admin/ajax/heartbeat' );
			}

			return;
		}
		
		// Ensure that WooCommerce is already loaded before start the bootstrap
		add_action( 'plugins_loaded', array( $this, 'maybe_bootstrap' ) );

	}
	
	/**
	 * Initial checking and plugin bootstrap
	 *
	 * @since 0.0.2
	 */
	public function maybe_bootstrap () {
		
		try {
			
			if ( $this->bootstrapped ) {
				throw new Exception( __( '%s plugin can only be called once', ATUM_TEXT_DOMAIN ), self::ALREADY_BOOTSTRAPED );
			}
			
			// Check that the plugin dependencies are present
			if ( is_admin() ) {
				$this->check_dependencies();
				Ajax::get_instance();
			}
			
			// Bootstrap the plugin
			Init::get_instance();
			$this->bootstrapped = TRUE;
			
		} catch (\Exception $e) {
			
			if ( in_array( $e->getCode(), array( self::ALREADY_BOOTSTRAPED, self::DEPENDENCIES_UNSATISFIED ) ) ) {
				$this->admin_message = $e->getMessage();
				add_action( 'admin_notices', array( $this, 'show_bootstrap_warning' ) );
			}
			
		}
		
	}
	
	/**
	 * Check the plugin dependencies before bootstrapping
	 *
	 * @since 0.0.2
	 * @throws \Exception
	 */
	private function check_dependencies() {
		
		// WooCommerce required
		if ( ! function_exists( 'WC' ) ) {
			throw new \Exception( __( '%s requires WooCommerce to be activated', ATUM_TEXT_DOMAIN ), self::DEPENDENCIES_UNSATISFIED );
		}
		// WooCommerce "Manage Stock" option must be enabled
		else {
			
			$woo_inventory_page = 'page=wc-settings&tab=products&section=inventory';
			
			// Special case for when the user is currently changing the stock option
			if ( isset($_POST['_wp_http_referer']) && strpos($_POST['_wp_http_referer'], $woo_inventory_page) !== FALSE ) {
				// It's a checkbox, so it's not sent with the form if unchecked
				$display_stock_option_notice = ! isset($_POST['woocommerce_manage_stock']);
			}
			else {
				$manage = get_option('woocommerce_manage_stock');
				$display_stock_option_notice = (!$manage || $manage == 'no');
			}
			
			if ($display_stock_option_notice) {
				
				$stock_option_msg = __( "You need to enable WooCommerce 'Manage Stock' option for ATUM plugin to work.");
				
				if (
					! isset( $_GET['page'] ) || $_GET['page'] != 'wc-settings' ||
					! isset( $_GET['tab'] ) || $_GET['tab'] != 'products' ||
					! isset( $_GET['section'] ) || $_GET['section'] != 'inventory'
				) {
					$stock_option_msg .= ' ' . sprintf(
						__( 'Go to %sWooCommerce inventory settings%s to fix this.', ATUM_TEXT_DOMAIN ),
						'<a href="' . admin_url( "admin.php?$woo_inventory_page" ) . '">',
						'</a>'
					);
				}
				
				throw new \Exception( $stock_option_msg, self::DEPENDENCIES_UNSATISFIED );
				
			}
			
		}
		
		// WooCommerce version greater than 2.5 required
		if ( version_compare( WC()->version, '2.5', '<' ) ) {
			throw new \Exception( __( '%s requires WooCommerce version 2.5 or greater', ATUM_TEXT_DOMAIN ), self::DEPENDENCIES_UNSATISFIED );
		}
		
	}
	
	/**
	 * Display an admin notice if was not possible to bootstrap the plugin
	 *
	 * @since 0.0.2
	 */
	public function show_bootstrap_warning() {
		
		if ( ! empty($this->admin_message ) ) {
			
			$plugin_data = get_plugin_data(ATUM_PATH . 'atum.php');
			$plugin_name = (! empty($plugin_data['Name']) ) ? $plugin_data['Name'] : ucfirst(ATUM_TEXT_DOMAIN);
			?>
			<div class="error fade">
				<p>
					<strong><?php echo sprintf($this->admin_message, $plugin_name); ?></strong>
				</p>
			</div>
			<?php
		}
		
		// TODO: ADD NOTICES FOR NON-REGISTERED PRO/PREMIUM VERSIONS
		
	}
	
	
	/****************************
	 * Instance methods
	 ****************************/
	public function __clone() {
		// cannot be cloned
		_doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN), '1.0.0' );
	}
	
	public function __sleep() {
		// cannot be serialized
		_doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN), '1.0.0' );
	}
	
	/**
	 * Get Singleton instance
	 *
	 * @static
	 * @return Bootstrap instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}