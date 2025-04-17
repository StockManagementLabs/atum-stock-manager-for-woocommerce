<?php
/**
 * Main loader
 *
 * @package     Atum
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since 0.0.1
 */

namespace Atum;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumAdminNotices;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumOrders\AtumComments;
use Atum\Components\AtumException;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Inc\Main;
use Atum\InventoryLogs\InventoryLogs;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;
use Automattic\WooCommerce\Utilities\FeaturesUtil;


class Bootstrap {

	/**
	 * The singleton instance holder
	 *
	 * @var Bootstrap
	 */
	private static $instance;

	/**
	 * Flag to indicate the plugin has been boostrapped
	 *
	 * @var bool
	 */
	private $bootstrapped = FALSE;

	/**
	 * The code for AtumException when throwing an exception trying to Bootstrap again
	 */
	const ALREADY_BOOTSTRAPPED = 1;

	/**
	 * The code for AtumException when throwing an exception of missing dependencies
	 */
	const DEPENDENCIES_UNSATISFIED = 2;

	/**
	 * Bootstrap constructor
	 *
	 * @since 0.0.2
	 */
	private function __construct() {

		// Check all the requirements before bootstrapping.
		add_action( 'plugins_loaded', array( $this, 'maybe_bootstrap' ) );

		// Register compatibility with new WC features.
		add_action( 'before_woocommerce_init', array( $this, 'declare_wc_compatibilities' ) );

		// Activation tasks.
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );

		// Uninstallation tasks.
		register_uninstall_hook( ATUM_PATH . 'atum-stock-manager-for-woocommerce.php', array( __CLASS__, 'uninstall' ) );

	}

	/* @noinspection PhpDocRedundantThrowsInspection */
	/**
	 * Initial checking and plugin bootstrap
	 *
	 * @since 0.0.2
	 *
	 * @throws AtumException
	 */
	public function maybe_bootstrap() {

		try {

			if ( $this->bootstrapped ) {
				throw new AtumException( 'already_bootstrapped', __( 'ATUM plugin can only be called once', ATUM_TEXT_DOMAIN ), self::ALREADY_BOOTSTRAPPED );
			}

			/**
			 * NOTE: the 2 hooks below must be registered before accessing any user-related function (like "current_user_can") when doing API requests.
			 */

			// Allow authenticating some WP API's endpoints using the WC API keys.
			add_filter( 'woocommerce_rest_is_request_to_rest_api', array( $this, 'bypass_wp_endpoints_with_wc_keys' ) );

			// Fix for authenticating with application passwords on ATUM API endpoints.
			add_filter( 'application_password_is_api_request', array( $this, 'check_application_password_api_request' ) );

			// Make sure the capabilities are registered.
			if ( is_super_admin() && ! AtumCapabilities::current_user_can( 'view_admin_menu' ) ) {
				AtumCapabilities::register_atum_capabilities();
			}

			// The ATUM comments must be instantiated before checking dependencies to ensure that are not displayed
			// in queries when any dependency is not met.
			AtumComments::get_instance();

			// We need the ATUM notices to be instantiated here also. So we can display Bootstrap error notices.
			AtumAdminNotices::get_instance();

			// Check that the plugin dependencies are met.
			$this->check_dependencies();

			// Bootstrap the plugin.
			Main::get_instance();
			$this->bootstrapped = TRUE;

		} catch ( AtumException $e ) {

			if ( in_array( $e->getCode(), array( self::ALREADY_BOOTSTRAPPED, self::DEPENDENCIES_UNSATISFIED ) ) ) {
				AtumAdminNotices::add_notice( $e->getMessage(), $e->getErrorCode(), 'error' );
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

		// WooCommerce required.
		if ( ! function_exists( 'WC' ) ) {
			throw new AtumException( 'woocommerce_disabled', __( 'ATUM requires WooCommerce to be activated', ATUM_TEXT_DOMAIN ), self::DEPENDENCIES_UNSATISFIED );
		}
		// WooCommerce "Manage Stock" global option must be enabled.
		else {

			$woo_inventory_page = 'page=wc-settings&tab=products&section=inventory';

			// Special case for when the user is currently changing the stock option.
			if ( isset( $_POST['_wp_http_referer'] ) && str_contains( $_POST['_wp_http_referer'], $woo_inventory_page ) ) {
				// It's a checkbox, so it's not sent with the form if unchecked.
				$display_stock_option_notice = ! isset( $_POST['woocommerce_manage_stock'] );
			}
			else {
				$manage                      = get_option( 'woocommerce_manage_stock' );
				$display_stock_option_notice = ! $manage || 'no' === $manage;
			}

			if ( $display_stock_option_notice ) {

				$stock_option_msg = __( "You need to enable WooCommerce 'Manage Stock' option for ATUM plugin to work.", ATUM_TEXT_DOMAIN );

				if (
					! isset( $_GET['page'] ) || 'wc-settings' !== $_GET['page'] ||
					! isset( $_GET['tab'] ) || 'products' !== $_GET['tab'] ||
					! isset( $_GET['section'] ) || 'inventory' !== $_GET['section']
				) {
					$stock_option_msg .= ' ' . sprintf(
						/* translators: the first one is the WC inventory settings page link and the second is the link closing tag */
						__( 'Go to %1$sWooCommerce inventory settings%2$s to fix this.', ATUM_TEXT_DOMAIN ),
						'<a href="' . self_admin_url( "admin.php?$woo_inventory_page" ) . '">',
						'</a>'
					);
				}

				throw new AtumException( 'woocommerce_manage_stock_disabled', $stock_option_msg, self::DEPENDENCIES_UNSATISFIED );

			}

		}

		// Minimum PHP version required: 5.6.
		if ( version_compare( phpversion(), ATUM_PHP_MINIMUM_VERSION, '<' ) ) {
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
			throw new AtumException( 'php_min_version_required', __( 'ATUM requires PHP version ' . ATUM_PHP_MINIMUM_VERSION . ' or greater. Please, update or contact your hosting provider.', ATUM_TEXT_DOMAIN ), self::DEPENDENCIES_UNSATISFIED );
		}

		// Minimum WordPress version required: 4.0.
		global $wp_version;
		if ( version_compare( $wp_version, ATUM_WP_MINIMUM_VERSION, '<' ) ) {
			/* translators: the first one is the WP updates page link and the second is the link closing tag */
			throw new AtumException( 'wordpress_min_version_required', sprintf( __( 'ATUM requires WordPress version ' . ATUM_WP_MINIMUM_VERSION . ' or greater. Please, %1$supdate now%2$s.', ATUM_TEXT_DOMAIN ), '<a href="' . esc_url( self_admin_url( 'update-core.php?force-check=1' ) ) . '">', '</a>' ), self::DEPENDENCIES_UNSATISFIED ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}

		// Minimum WooCommerce version required: 3.0.
		if ( version_compare( WC()->version, ATUM_WC_MINIMUM_VERSION, '<' ) ) {
			/* translators: the first one is the WP updates page link and the second is the link closing tag */
			throw new AtumException( 'woocommerce_min_version_required', sprintf( __( 'ATUM requires WooCommerce version ' . ATUM_WC_MINIMUM_VERSION . ' or greater. Please, %1$supdate now%2$s.', ATUM_TEXT_DOMAIN ), '<a href="' . esc_url( self_admin_url( 'update-core.php?force-check=1' ) ) . '">', '</a>' ), self::DEPENDENCIES_UNSATISFIED ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}

	}

	/**
	 * Register ATUM's compatibility with new WC features.
	 *
	 * @since 1.9.23
	 */
	public function declare_wc_compatibilities() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', ATUM_BASENAME ); // HPOS compatibility.
			FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', ATUM_BASENAME ); // Checkout block compatibility.
		}
	}

	/**
	 * Activation tasks (this will run only once at plugin activation)
	 *
	 * @since 1.9.45
	 */
	public static function activate() {
		// As the capabilities are saved in the DB, we need to register them once on activation.
		AtumCapabilities::register_atum_capabilities();
	}

	/**
	 * Uninstallation tasks (this will run only once at plugin uninstallation)
	 *
	 * @since 1.3.7.1
	 */
	public static function uninstall() {

		global $wpdb;

		if ( 'yes' === Helpers::get_option( 'delete_data', 'no' ) ) {

			$product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
			$items_table        = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
			$itemmeta_table     = $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE;

			// Delete the ATUM tables in db.
			$wpdb->query( "DROP TABLE IF EXISTS $product_data_table" ); // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->query( "DROP TABLE IF EXISTS $items_table" ); // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->query( "DROP TABLE IF EXISTS $itemmeta_table" ); // phpcs:ignore WordPress.DB.PreparedSQL

			// Delete all the posts of ATUM's custom post types and their meta.
			$atum_post_types = array(
				PurchaseOrders::get_post_type(),
				InventoryLogs::get_post_type(),
				Suppliers::POST_TYPE,
			);

			foreach ( $atum_post_types as $atum_post_type ) {

				$args       = array(
					'post_type'      => $atum_post_type,
					'posts_per_page' => - 1,
					'fields'         => 'ids',
					'post_status'    => 'any',
				);
				$atum_posts = get_posts( $args );

				if ( ! empty( $atum_posts ) ) {
					$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN (" . implode( ',', $atum_posts ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->delete( $wpdb->posts, array( 'post_type' => $atum_post_type ) );
				}

			}

			// Delete all the ATUM order notes.
			$wpdb->query( "DELETE FROM $wpdb->comments WHERE comment_type LIKE '" . ATUM_PREFIX . "%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// Delete all the user meta related to ATUM.
			$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '" . ATUM_PREFIX . "%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// Delete the ATUM options.
			delete_option( ATUM_PREFIX . 'version' );
			delete_option( ATUM_PREFIX . 'settings' );

			// Delete marketing popup transient.
			delete_transient( 'atum-marketing-popup' );

		}

		// Delete scheduled actions anyway. Can't use the AtumQueues class to get the actions.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {

			$actions = [ 'atum/update_expiring_product_props', 'atum/cron_update_sales_calc_props', 'atum/clean_up_tmp_folders', 'atum/check_addons' ];

			foreach ( $actions as $action ) {
				as_unschedule_all_actions( $action );
			}

		}

	}

	/**
	 * Allow authenticating some WP API's endpoints using the WC API keys, so we can upload images to products, list comments, etc.
	 *
	 * @since 1.7.5
	 *
	 * @param bool $is_request_to_rest_api
	 *
	 * @return bool
	 */
	public function bypass_wp_endpoints_with_wc_keys( $is_request_to_rest_api ) {

		if ( ! $is_request_to_rest_api ) {

			if ( empty( $_SERVER['REQUEST_URI'] ) ) {
				return FALSE;
			}

			$rest_prefix = trailingslashit( rest_get_url_prefix() );
			$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );

			$is_request_to_rest_api = apply_filters( 'atum/api/bypass_wp_endpoints_with_wc_keys',
				( str_contains( $request_uri, $rest_prefix . 'wp/v2/media' ) ) ||
				( str_contains( $request_uri, $rest_prefix . 'wp/v2/comments' ) )
			);

		}

		return $is_request_to_rest_api;

	}

	/**
	 * Fix for authentication with application passwords over ATUM API endpoints
	 *
	 * @since 1.9.39
	 *
	 * @param bool $is_api_request
	 *
	 * @return bool
	 */
	public function check_application_password_api_request ( $is_api_request ) {
		return $is_api_request || Helpers::is_rest_request();
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
	 * @return Bootstrap instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
