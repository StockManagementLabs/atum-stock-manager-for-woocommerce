<?php
/**
 * @package     Atum
 * @author      Salva Machí <salvamb@sispixels.com>
 * @copyright   ©2017 Stock Management Labs™
 *
 * @since 0.0.1
 *
 * Main loader
 */

namespace Atum;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumOrders\AtumComments;
use Atum\Components\AtumException;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Helpers;
use Atum\Inc\Main;
use Atum\InventoryLogs\InventoryLogs;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;


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

	/**
	 * Bootstrap constructor
	 *
	 * @since 0.0.2
	 */
	private function __construct() {
		
		// Check all the requirements before bootstraping
		add_action( 'plugins_loaded', array( $this, 'maybe_bootstrap' ) );

		// Uninstallation tasks
		register_uninstall_hook( ATUM_PATH . 'atum-stock-manager-for-woocommerce.php', array( __CLASS__, 'uninstall' ) );

	}
	
	/**
	 * Initial checking and plugin bootstrap
	 *
	 * @since 0.0.2
	 */
	public function maybe_bootstrap () {
		
		try {
			
			if ( $this->bootstrapped ) {
				throw new AtumException( 'already_bootstrapped', __( 'ATUM plugin can only be called once', ATUM_TEXT_DOMAIN ), self::ALREADY_BOOTSTRAPED );
			}

			/**
			 * @deprecated
			 * Namespaces were changed in 1.3.0 and the class aliases may be deleted in the next major version
			 */
			class_alias('\Atum\Components\AtumListTables\AtumListPage', '\Atum\Components\AtumListPage');
			class_alias('\Atum\Components\AtumListTables\AtumListTable', '\Atum\Components\AtumListTable');

			// The ATUM comments must be instantiated before checking dependencies to ensure that are not displayed
			// in queries when any dependency is not met
			AtumComments::get_instance();
			
			// Check that the plugin dependencies are met
			$this->check_dependencies();
			
			// Bootstrap the plugin
			Main::get_instance();
			$this->bootstrapped = TRUE;
			
		} catch (AtumException $e) {
			
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
	 *
	 * @throws AtumException
	 */
	private function check_dependencies() {
		
		// WooCommerce required
		if ( ! function_exists( 'WC' ) ) {
			throw new AtumException( 'woocommerce_disabled', __( 'ATUM requires WooCommerce to be activated', ATUM_TEXT_DOMAIN ), self::DEPENDENCIES_UNSATISFIED );
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
						'<a href="' . self_admin_url( "admin.php?$woo_inventory_page" ) . '">',
						'</a>'
					);
				}
				
				throw new AtumException( 'woocommerce_manage_stock_disabled', $stock_option_msg, self::DEPENDENCIES_UNSATISFIED );
				
			}
			
		}

		// Minimum PHP version required: 5.6
		if ( version_compare( phpversion(), '5.6', '<' ) ) {
			throw new AtumException( 'php_min_version_required', __( 'ATUM requires PHP version 5.6 or greater. Please, update or contact your hosting provider.', ATUM_TEXT_DOMAIN ), self::DEPENDENCIES_UNSATISFIED );
		}

		// Minimum WordPress version required: 4.0
		global $wp_version;
		if ( version_compare( $wp_version, '4.0', '<' ) ) {
			throw new AtumException( 'wordpress_min_version_required', sprintf( __( 'ATUM requires Wordpress version 4.0 or greater. Please, %supdate now%s.', ATUM_TEXT_DOMAIN ), '<a href="' . esc_url( self_admin_url('update-core.php?force-check=1') ) . '">', '</a>' ), self::DEPENDENCIES_UNSATISFIED );
		}

		// Minimum WooCommerce version required: 2.5
		if ( version_compare( WC()->version, '2.5', '<' ) ) {
			throw new AtumException( 'woocommerce_min_version_required', sprintf( __( 'ATUM requires WooCommerce version 2.5 or greater. Please, %supdate now%s.', ATUM_TEXT_DOMAIN ), '<a href="' . esc_url( self_admin_url('update-core.php?force-check=1') ) . '">', '</a>' ), self::DEPENDENCIES_UNSATISFIED );
		}
		
	}
	
	/**
	 * Display an admin notice if was not possible to bootstrap the plugin
	 *
	 * @since 0.0.2
	 */
	public function show_bootstrap_warning() {
		
		if ( ! empty($this->admin_message ) ): ?>
			<div class="error fade">
				<p>
					<strong><?php echo $this->admin_message ?></strong>
				</p>
			</div>
		<?php endif;
		
	}

	/**
	 * Uninstallation checks (this will run only once at plugin uninstallation)
	 *
	 * @since 1.3.7.1
	 */
	public static function uninstall() {

		global $wpdb;

		if ( Helpers::get_option('delete_data') == 'yes' ) {

			$items_table    = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
			$itemmeta_table = $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE;

			// Delete the ATUM tables in db
			$wpdb->query( "DROP TABLE IF EXISTS $items_table" );
			$wpdb->query( "DROP TABLE IF EXISTS $itemmeta_table" );

			// Delete all the posts of ATUM's custom post types and their meta
			$atum_post_types = array(
				PurchaseOrders::get_post_type(),
				InventoryLogs::get_post_type(),
				Suppliers::POST_TYPE
			);

			foreach ( $atum_post_types as $atum_post_type ) {

				$args       = array(
					'post_type'      => $atum_post_type,
					'posts_per_page' => - 1,
					'fields'         => 'ids',
					'post_status'    => 'any'
				);
				$atum_posts = get_posts( $args );

				if ( ! empty( $atum_posts ) ) {
					$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN (" . implode( ',', $atum_posts ) . ")" );
					$wpdb->delete( $wpdb->posts, array( 'post_type' => $atum_post_type ) );
				}

			}

			// Delete all the ATUM order notes
			$wpdb->query( "DELETE FROM $wpdb->comments WHERE comment_type LIKE '" . ATUM_PREFIX . "%'" );

			// Delete the ATUM options
			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name IN ('" . ATUM_PREFIX . "version', '" . ATUM_PREFIX . "settings')" );

			// Delete all the user meta related to ATUM
			$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '" . ATUM_PREFIX . "%'" );

		}

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
	 * @return Bootstrap instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}