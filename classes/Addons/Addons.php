<?php
/**
 * ATUM add-ons manager
 *
 * @package         Atum
 * @subpackage      Addons
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2024 Stock Management Labs™
 *
 * @since           1.2.0
 */

namespace Atum\Addons;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumAdminNotices;
use Atum\Components\AtumCache;
use Atum\Components\AtumException;
use Atum\Components\AtumMarketingPopup;
use Atum\Inc\Helpers;
use Westsworld\TimeAgo;


final class Addons {

	/**
	 * The singleton instance holder
	 *
	 * @var Addons
	 */
	private static $instance;

	/**
	 * The list of registered ATUM's add-ons
	 *
	 * @var array
	 */
	private static $addons = array();

	/**
	 * The list of add-ons installed with no activated licenses
	 *
	 * @var array
	 */
	private $no_activated_licenses = array();

	/**
	 * Array with paths used for every add-on
	 * NOTE: The name must match the one used within the ADDON_NAME constant within each add-on.
	 *
	 * @var array
	 */
	private static $addons_paths = array(
		'action_logs'     => [
			'name'     => 'Action Logs',
			'basename' => 'atum-logs/atum-logs.php',
		],
		'export_pro'      => [
			'name'     => 'Export Pro',
			'basename' => 'atum-export-pro/atum-export-pro.php',
		],
		'multi_inventory' => [
			'name'     => 'Multi-Inventory',
			'basename' => 'atum-multi-inventory/atum-multi-inventory.php',
		],
		'pick_pack'       => [
			'name'     => 'Pick & Pack',
			'basename' => 'atum-pick-pack/atum-pick-pack.php',
		],
		'product_levels'  => [
			'name'     => 'Product Levels',
			'basename' => 'atum-product-levels/atum-product-levels.php',
		],
		'purchase_orders' => [
			'name'     => 'Purchase Orders PRO',
			'basename' => 'atum-purchase-orders/atum-purchase-orders.php',
		],
		'stock_takes'     => [
			'name'     => 'Stock Takes',
			'basename' => 'atum-stock-takes/atum-stock-takes.php',
		],
	);

	/**
	 * The ATUM's addons store URL
	 */
	const ADDONS_STORE_URL = 'https://stockmanagementlabs.com/';

	/**
	 * The ATUM's addons API endpoint
	 */
	const ADDONS_API_ENDPOINT = 'wp-json/atum/v1/addons';

	/**
	 * The name used to store the addons keys in db
	 */
	const ADDONS_KEY_OPTION = ATUM_PREFIX . 'addons_keys';

	/**
	 * The menu order for this module
	 */
	const MENU_ORDER = 999; // The last one.

	/**
	 * Addons singleton constructor
	 *
	 * @since 1.2.0
	 */
	private function __construct() {

		// Get all the registered (installed + enabled) addons.
		self::$addons = apply_filters( 'atum/addons/setup', self::$addons );

		// Add the module menu.
		add_filter( 'atum/admin/menu_items', array( $this, 'add_menu' ), self::MENU_ORDER );

		// Initialize the addons.
		add_action( 'after_setup_theme', array( $this, 'init_addons' ), 100 );

		// Load addons.
		$addons_loader = self::get_addons_loader_class();
		new $addons_loader();

		if ( is_admin() ) {

			// Automatic updates for addons.
			add_action( 'admin_init', array( $this, 'check_addons_updates' ), 0 );

			// Check if there are ATUM add-ons installed that are not activated.
			if ( ! empty( self::$addons ) ) {

				foreach ( self::$addons as $registered_addon ) {
					$addon_key = self::get_keys( $registered_addon['name'] );

					if ( empty( $addon_key ) || ! $addon_key['key'] || in_array( $addon_key['status'], [ 'invalid', 'expired' ], TRUE ) ) {
						$this->no_activated_licenses[] = $registered_addon['name'];
					}
				}

				if ( ! empty( $this->no_activated_licenses ) ) {

					$message = sprintf(
						/* translators: opening and closing HTML link to the add-ons page  */
						__( 'Please, activate %1$syour ATUM premium add-ons licenses%2$s to receive automatic updates.', ATUM_TEXT_DOMAIN ),
						'<a href="' . add_query_arg( 'page', 'atum-addons', admin_url( 'admin.php' ) ) . '">',
						'</a>'
					);

					AtumAdminNotices::add_notice( $message, 'activate_addons_licenses', 'info', TRUE, FALSE, 'activate-addons' );

				}

			}

			// Add the ATUM add-ons to the WooCommerce suggestions.
			if ( 'yes' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) ) {
				add_filter( 'option_woocommerce_marketplace_suggestions', array( $this, 'add_atum_addons_suggestions' ), 100, 2 );
			}

		}

	}

	/**
	 * Initialize the installed ATUM's addons
	 *
	 * @since 1.2.0
	 */
	public function init_addons() {

		if ( ! empty( self::$addons ) ) {

			// Add extra links to the plugin desc row.
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		}

	}

	/**
	 * Add the Add-ons menu
	 *
	 * @since 1.3.6
	 *
	 * @param array $menus
	 *
	 * @return array
	 */
	public function add_menu( $menus ) {

		$menus['addons'] = array(
			'title'      => __( 'Add-ons', ATUM_TEXT_DOMAIN ),
			'callback'   => array( $this, 'load_addons_page' ),
			'slug'       => ATUM_SHORT_NAME . '-addons',
			'menu_order' => self::MENU_ORDER,
		);

		return $menus;

	}

	/**
	 * Load the Addons page
	 *
	 * @since 1.2.0
	 */
	public function load_addons_page() {

		Helpers::register_swal_scripts();

		wp_register_style( 'atum-addons', ATUM_URL . 'assets/css/atum-addons.css', [ 'sweetalert2' ], ATUM_VERSION );

		// ATUM marketing popup.
		AtumMarketingPopup::get_instance()->maybe_enqueue_scripts();

		wp_register_script( 'atum-addons', ATUM_URL . 'assets/js/build/atum-addons.js', [ 'jquery', 'jquery-blockui', 'sweetalert2' ], ATUM_VERSION, TRUE );

		$addons_vars = array(
			'activate'             => __( 'Activate', ATUM_TEXT_DOMAIN ),
			'activated'            => __( 'Activated!', ATUM_TEXT_DOMAIN ),
			'activation'           => __( 'License Activation', ATUM_TEXT_DOMAIN ),
			'addonActivated'       => __( 'Your add-on license has been activated.', ATUM_TEXT_DOMAIN ),
			'addonInstalled'       => __( 'Add-on installed successfully', ATUM_TEXT_DOMAIN ),
			'addonsPageUrl'        => add_query_arg( 'page', 'atum-addons', admin_url( 'admin.php' ) ),
			'addonNotInstalled'    => __( 'Add-on not installed', ATUM_TEXT_DOMAIN ),
			'agree'                => __( 'Yes, I agree!', ATUM_TEXT_DOMAIN ),
			'allAddonsInstalled'   => __( 'All the add-ons were installed successfully', ATUM_TEXT_DOMAIN ),
			'allowedDeactivations' => __( 'You are allowed to remove a license a max of 2 times.', ATUM_TEXT_DOMAIN ),
			'autoInstaller'        => __( 'ATUM Add-ons Auto-Installer', ATUM_TEXT_DOMAIN ),
			'cancel'               => __( 'Cancel', ATUM_TEXT_DOMAIN ),
			'continue'             => __( 'Continue', ATUM_TEXT_DOMAIN ),
			'extend'               => __( 'Yes, Extend it!', ATUM_TEXT_DOMAIN ),
			'error'                => __( 'Error!', ATUM_TEXT_DOMAIN ),
			'hide'                 => __( 'Hide', ATUM_TEXT_DOMAIN ),
			'install'              => __( 'Install', ATUM_TEXT_DOMAIN ),
			'installing'           => __( 'Installing...', ATUM_TEXT_DOMAIN ),
			'invalidKey'           => __( 'Please enter a valid add-on license key.', ATUM_TEXT_DOMAIN ),
			'key'                  => __( 'key', ATUM_TEXT_DOMAIN ),
			'limitedDeactivations' => __( 'Limited Deactivations!', ATUM_TEXT_DOMAIN ),
			'ok'                   => __( 'OK', ATUM_TEXT_DOMAIN ),
			'nonce'                => wp_create_nonce( ATUM_PREFIX . 'manage_license' ),
			'show'                 => __( 'Show', ATUM_TEXT_DOMAIN ),
			'success'              => __( 'Success!', ATUM_TEXT_DOMAIN ),
			'toBeInstalled'        => __( 'The following add-ons are going to be installed', ATUM_TEXT_DOMAIN ),
			'trial'                => __( 'Trial License!', ATUM_TEXT_DOMAIN ),
			'trialActivated'       => __( 'Your trial add-on license has been activated.', ATUM_TEXT_DOMAIN ),
			'trialDeactivation'    => __( 'Trial deactivation notice!', ATUM_TEXT_DOMAIN ),
			'trialExpired'         => __( 'Trial expired!', ATUM_TEXT_DOMAIN ),
			'trialExtension'       => __( 'Trial extension', ATUM_TEXT_DOMAIN ),
			'trialWillDisable'     => __( 'If you remove a trial license, your installed add-on will be disabled. Please, only remove this trial license if you are going to uninstall the add-on or replace it by a full version license key.', ATUM_TEXT_DOMAIN ),
			'trialWillExtend'      => __( 'You are going to extend this trial for 7 days more', ATUM_TEXT_DOMAIN ),
		);

		if ( isset( $_GET['auto-install'], $_GET['token'] ) && '1' === $_GET['auto-install'] ) {
			$addons_vars['autoInstallData'] = Helpers::unrot_token( $_GET['token'] );
		}

		wp_localize_script( 'atum-addons', 'atumAddons', $addons_vars );
		wp_enqueue_style( 'atum-addons' );

		if ( is_rtl() ) {
			wp_register_style( 'atum-addons-rtl', ATUM_URL . 'assets/css/atum-addons-rtl.css', [ 'atum-addons' ], ATUM_VERSION );
			wp_enqueue_style( 'atum-addons-rtl' );
		}

		wp_enqueue_script( 'atum-addons' );

		$args = array(
			'addons'           => self::get_addons_list(),
			'installed_addons' => self::get_installed_addons(),
		);

		Helpers::load_view( 'add-ons/list', $args );

	}

	/**
	 * Check for updates for the installed ATUM addons
	 *
	 * @since 1.2.0
	 */
	public function check_addons_updates() {

		if ( Helpers::doing_heartbeat() ) {
			return;
		}

		$license_keys = self::get_keys();

		if ( ! empty( $license_keys ) ) {

			$installed_addons = self::$addons;
			$addons_paths     = self::$addons_paths;

			// We must check if there are others not enabled that should be updated.
			$installed_plugins = get_plugins();

			foreach ( $installed_plugins as $plugin_file => $plugin_data ) {

				if ( strpos( $plugin_file, 'atum-' ) === 0 && empty( wp_list_filter( $installed_addons, [ 'basename' => $plugin_file ] ) ) ) {

					// Get the plugin slug from the URL.
					$plugin_url_paths = parse_url( $plugin_data['PluginURI'] ?? '' );

					// Bypass the ATUM free plugin.
					if ( ! empty( $plugin_url_paths['path'] ) && '/' !== $plugin_url_paths['path'] ) {

						$plugin_url_paths  = explode( '/', untrailingslashit( $plugin_url_paths['path'] ) );
						$full_version_slug = str_replace( '-', '_', str_replace( 'atum-', '', end( $plugin_url_paths ) ) );
						$is_trial_addon    = str_contains( strtolower( $plugin_data['Name'] ), 'trial' );
						$addon_slug        = $is_trial_addon ? "{$full_version_slug}_trial" : $full_version_slug;
						$addon_path        = [];

						if ( array_key_exists( $full_version_slug, $addons_paths ) ) {
							$addon_path             = $addons_paths[ $full_version_slug ];
							$addon_path['name']     = $is_trial_addon ? "{$addon_path['name']} Trial" : $addon_path['name']; // The addon name doesn't always match with the name added to the WP plugin.
							$addon_path['basename'] = $plugin_file;
						}

						$installed_addons[ $addon_slug ] = array_merge( array(
							'name'        => $plugin_data['Name'],
							'description' => $plugin_data['Description'],
							'addon_url'   => $plugin_data['PluginURI'],
							'basename'    => $plugin_file,
						), $addon_path );

					}

				}

			}

			foreach ( $license_keys as $addon => $license_key ) {

				$addon_slug       = $addon_name = '';
				$is_trial_license = ! empty( $license_key['trial'] );

				foreach ( $installed_addons as $addon_key => $addon_data ) {

					if ( str_contains( strtolower( $addon_data['name'] ), strtolower( $addon ) ) ) {

						$is_trial_addon = str_contains( $addon_key, 'trial' );

						// Avoid getting the wrong installed add-on (full or trial).
						if ( ( $is_trial_license && ! $is_trial_addon ) || ( ! $is_trial_license && $is_trial_addon ) ) {
							continue;
						}

						$addon_slug = $addon_key;
						$addon_name = $addon_data['name'];
						break;

					}

				}

				if (
					$addon_slug && $license_key &&
					is_array( $license_key ) && ! empty( $license_key['key'] )
				) {

					$is_trial_addon = str_contains( $addon_slug, 'trial' );

					if ( 'valid' === $license_key['status'] ) {

						// All the ATUM addons' names should start with 'ATUM'.
						$plugin_name = $is_trial_addon ? "ATUM $addon (Trial version)" : "ATUM $addon";
						$addon_info  = Helpers::is_plugin_installed( $plugin_name, 'name', FALSE );

						if ( ! empty( $addon_info ) ) {

							// Check if is a trial license.
							if ( $is_trial_addon && ! $is_trial_license ) {

								AtumAdminNotices::add_notice(
								/* translators: the add-on name and the open and closing link tags */
									sprintf( __( 'The ATUM %1$s license is invalid. Please, enter a valid trial license on the %2$sadd-ons page.%3$s', ATUM_TEXT_DOMAIN ), $addon_name, '<a href="' . add_query_arg( 'page', 'atum-addons', admin_url( 'admin.php' ) ) . '">', '</a>' ),
									strtolower( $addon_name ),
									'warning',
									TRUE,
									TRUE
								);

							}
							elseif ( ! $is_trial_addon && $is_trial_license ) {

								AtumAdminNotices::add_notice(
								/* translators: the add-on name and the open and closing link tags */
									sprintf( __( 'The ATUM %1$s license is invalid. Please, enter a valid full version license on the %2$sadd-ons page.%3$s', ATUM_TEXT_DOMAIN ), $addon_name, '<a href="' . add_query_arg( 'page', 'atum-addons', admin_url( 'admin.php' ) ) . '">', '</a>' ),
									strtolower( $addon_name ),
									'warning',
									TRUE,
									TRUE
								);

							}
							else {

								// Set up the updater.
								$addon_file = key( $addon_info );

								// If it's a trial add-on, pass the trial path to the updater.
								new Updater( $addon_file, array(
									'version'   => $addon_info[ $addon_file ]['Version'],
									'license'   => $license_key['key'],
									'item_name' => $addon_name,
									'slug'      => str_replace( '_', '-', ATUM_PREFIX . $addon_slug ),
								) );

							}

						}

					}
					elseif ( in_array( $license_key['status'], [ 'disabled', 'expired', 'invalid' ] ) ) {

						if ( $is_trial_addon ) {

							AtumAdminNotices::add_notice(
							/* translators: the add-on name */
								sprintf( __( 'The ATUM %1$s license is invalid. Please, enter a valid trial license on the %2$sadd-ons page%3$s or purchase the full version.<br>If you already have upgraded to full, please uninstall the trial and reinstall the full version from the ATUM add-ons page.', ATUM_TEXT_DOMAIN ), $addon_name, '<a href="' . add_query_arg( 'page', 'atum-addons', admin_url( 'admin.php' ) ) . '">', '</a>' ),
								strtolower( $addon_name ),
								'warning',
								TRUE,
								TRUE
							);

						}
						else {

							AtumAdminNotices::add_notice(
							/* translators: the add-on name */
								sprintf( __( "ATUM %1\$s license has expired or is invalid. You can no longer update or take advantage of support. Running outdated plugins may cause functionality issues and compromise your site's security and data. %2\$sYou can extend your license for 15%% OFF now (valid 14 days after the license expires).%3\$s", ATUM_TEXT_DOMAIN ), $addon_name, '<a href="https://stockmanagementlabs.com/login" target="_blank">', '</a>' ),
								strtolower( $addon_name ),
								'warning',
								TRUE,
								TRUE
							);

						}
					}
					elseif ( 'trial_used' === $license_key['status'] && str_contains( $addon_slug, 'trial' ) ) {

						AtumAdminNotices::add_notice(
						/* translators: the add-on name */
							sprintf( __( 'ATUM %1$s license is being used on another site and is for a single use only. Please, remove it from the %2$sadd-ons page%3$s.', ATUM_TEXT_DOMAIN ), $addon_name, '<a href="' . add_query_arg( 'page', 'atum-addons', admin_url( 'admin.php' ) ) . '">', '</a>' ),
							strtolower( $addon_name ),
							'warning',
							TRUE,
							TRUE
						);

					}

				}

			}

		}

	}

	/**
	 * Get the list of available ATUM addons and their data
	 *
	 * @since 1.2.0
	 *
	 * @return array|bool
	 */
	private static function get_addons_list() {

		$transient_name = AtumCache::get_transient_key( 'addons_list' );
		$addons         = AtumCache::get_transient( $transient_name, TRUE );

		if ( empty( $addons ) ) {

			// Avoid doing requests to the API too many times if for some reason is down.
			if ( FALSE !== self::get_last_api_access( 'addons_list' ) ) {
				return FALSE;
			}

			$response = self::atum_api_request();

			// Admin notification about the error.
			if ( is_wp_error( $response ) ) {

				$error_message = $response->get_error_message();

				if ( TRUE === ATUM_DEBUG ) {
					error_log( __METHOD__ . ": $error_message" );
				}

				AtumAdminNotices::add_notice(
					/* translators: the error message */
					sprintf( __( "Something failed getting the ATUM's add-ons list: %s", ATUM_TEXT_DOMAIN ), $error_message ),
					'addons_list',
					'error',
					TRUE,
					TRUE
				);

				$addons = FALSE;

			}
			elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {

				AtumAdminNotices::add_notice(
					__( "Something failed getting the ATUM's add-ons list. Please retry after some minutes.", ATUM_TEXT_DOMAIN ),
					'addons_list',
					'error',
					TRUE,
					TRUE
				);
				$addons = FALSE;

			}
			else {

				$response_body = wp_remote_retrieve_body( $response );
				$addons        = $response_body ? json_decode( $response_body, TRUE ) : [];

				if ( empty( $addons ) ) {

					AtumAdminNotices::add_notice(
						__( "Something failed getting the ATUM's add-ons list. Please retry after some minutes.", ATUM_TEXT_DOMAIN ),
						'addons_list',
						'error',
						TRUE,
						TRUE
					);
					$addons = FALSE;

				}

			}

			if ( ! empty( $addons ) ) {
				self::set_last_api_access( 'addons_list', TRUE );
				AtumCache::set_transient( $transient_name, $addons, DAY_IN_SECONDS, TRUE );
			}
			else {
				self::set_last_api_access( 'addons_list' );
			}

		}

		return $addons;

	}

	/**
	 * Remove the add-ons list transient
	 *
	 * @since 1.9.9
	 */
	public static function delete_addons_list_transient() {

		$transient_name = AtumCache::get_transient_key( 'addons_list' );
		AtumCache::delete_transients( $transient_name );
	}

	/**
	 * Retrieves addon folder
	 *
	 * @since 1.7.5
	 *
	 * @param string $addon_slug
	 *
	 * @return bool|string
	 */
	private static function get_addon_folder( $addon_slug ) {

		$addons = self::get_addons_list();

		if ( ! empty( $addons ) ) {

			$is_trial = FALSE;

			if ( str_contains( $addon_slug, 'trial' ) ) {
				$addon_slug = str_replace( '-trial', '', $addon_slug );
				$is_trial   = TRUE;
			}

			foreach ( $addons as $addon ) {
				if ( $addon_slug === $addon['info']['slug'] ) {
					return ! $is_trial ? $addon['info']['folder'] : "{$addon['info']['folder']}-trial";
				}
			}

		}

		return FALSE;

	}

	/**
	 * Add the ATUM add-ons suggestions to the WC's product data tab
	 *
	 * @since 1.6.7
	 *
	 * @param mixed  $value
	 * @param string $option
	 *
	 * @return mixed
	 */
	public function add_atum_addons_suggestions( $value, $option ) {

		if ( ! isset( $value['suggestions'] ) ) {
			return $value;
		}

		foreach ( self::$addons as $slug => $addon ) {

			array_unshift( $value['suggestions'], array(
				'slug'        => $slug,
				'product'     => $slug,
				'context'     => [ 'product-edit-meta-tab-body' ],
				'icon'        => '#',
				'title'       => $addon['name'],
				'copy'        => $addon['description'],
				'button_text' => __( 'Learn More', ATUM_TEXT_DOMAIN ),
				'url'         => $addon['addon_url'],
			) );

		}

		return $value;

	}

	/**
	 * Get an addon key from database (if a valid name is passed as parameter) or all the registered addon keys (with no params)
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name Optional. The addon name from which get the key.
	 *
	 * @return string|array|bool
	 */
	public static function get_keys( $addon_name = '' ) {

		$keys = get_option( self::ADDONS_KEY_OPTION, [] );

		if ( ! is_array( $keys ) ) {
			$keys = [];
		}

		if ( empty( $addon_name ) ) {
			$keys = self::check_addons_keys( $keys );
		}

		$lower_keys = array();

		if ( ! empty( $keys ) ) {

			foreach ( $keys as $key_name => $key_value ) {
				if ( ! in_array( $key_name, array_keys( $lower_keys ) ) ) {
					$lower_key                = strtolower( html_entity_decode( $key_name ) );
					$lower_keys[ $lower_key ] = $key_value;
				}
			}

		}

		$addon_name = strtolower( html_entity_decode( $addon_name ) );

		// Make sure the full-version names are used for keys.
		if ( str_contains( $addon_name, 'trial' ) ) {
			$addon_name = trim( str_replace( 'trial', '', $addon_name ) );
		}

		if ( $addon_name ) {

			if ( ! empty( $keys ) && is_array( $keys ) && in_array( $addon_name, array_keys( $lower_keys ) ) ) {
				return $lower_keys[ $addon_name ];
			}

			return '';

		}

		return $keys;
	}

	/**
	 * Check the registered addons in database and update them.
	 *
	 * @since 1.9.21
	 *
	 * @param array $keys
	 *
	 * @return array
	 */
	private static function check_addons_keys( $keys ) {

		$result = $checked = array();

		foreach ( $keys as $addon => $license ) {

			// Remove inactive licenses.
			if ( empty( $license['status'] ) || in_array( $license['status'], [ 'inactive', 'site_inactive', 'disabled' ] ) ) {
				continue;
			}

			$addon = strtolower( $addon );

			if ( in_array( $addon, $checked ) ) {
				continue;
			}

			$sensitive_name = '';

			// Get addon slug.
			foreach ( self::$addons as $addon_data ) {

				if ( strtolower( $addon_data['name'] ) === $addon ) {
					$sensitive_name = strtolower( $addon_data['name'] );
				}

			}

			$duplicated = [];

			// Find duplicated.
			foreach ( $keys as $addon2 => $license2 ) {

				// Remove inactive licenses.
				if ( in_array( $license['status'], [ 'inactive', 'site_inactive' ] ) ) {
					continue;
				}

				$addon2 = strtolower( $addon2 );

				if ( strtolower( $addon2 ) === $addon ) {
					$checked[] = $addon2;

					$duplicated[] = [
						'index' => $addon2,
						'data'  => $license2,
					];

				}
			}

			if ( count( $duplicated ) > 1 ) {

				$selected = FALSE;

				foreach ( $duplicated as $dup ) {

					if ( $dup['data']['key'] && 'valid' === $dup['data']['status'] ) {
						if ( ! $selected || 'valid' !== $selected['status'] ) {
							$selected = $dup['data'];
						}
						elseif ( $dup['index'] === $sensitive_name ) {
							$selected = $dup['data'];
						}
					}
					else {

						if ( $selected && ! $selected['key'] && $dup['data']['key'] ) {
							$selected = $dup['data'];
						}
						elseif ( ! $selected ) {
							$selected = $dup['data'];
						}
					}

				}

				$result[ $addon ] = $selected;

			} elseif ( 1 === count( $duplicated ) ) {

				$result[ $addon ] = $duplicated[0]['data'];

			}

		}

		if ( $keys !== $result ) {

			// If the addons keys changed, update the option and delete transients.
			update_option( self::ADDONS_KEY_OPTION, $result );

			foreach ( self::$addons as $registered_addon ) {
				self::delete_status_transient( $registered_addon['name'] );
			}
		}

		return $result;

	}

	/**
	 * Generate a license manager API request
	 *
	 * @param string $addon_name   The addon name (must match to the ATUM store's addon name).
	 * @param string $key          The license key.
	 * @param string $endpoint     The API endpoint.
	 * @param string $method       Optional. The request method.
	 * @param array  $extra_params Optional. Any other param that will be sent to the API.
	 *
	 * @return array|\WP_Error
	 */
	private static function lm_api_request( $addon_name, $key, $endpoint, $method = 'POST', $extra_params = array() ) {

		$params = array_merge( $extra_params, array(
			'edd_action' => $endpoint,
			'license'    => $key,
			'item_name'  => rawurlencode( $addon_name ),
			'url'        => home_url(),
		) );

		$request_params = array(
			'timeout'     => 20,
			'redirection' => 1,
			'sslverify'   => FALSE,
			'body'        => $params,
			'user-agent'  => Helpers::get_atum_user_agent(),
		);

		$function = 'wp_remote_post';

		if ( 'GET' === strtoupper( $method ) ) {
			$function = 'wp_remote_get';
		}

		// Call the license manager API.
		return call_user_func( $function, self::ADDONS_STORE_URL, $request_params );

	}

	/**
	 * Generate a SML ATUM API request
	 *
	 * @since 1.9.27
	 *
	 * @param string $method
	 * @param string $endpoint
	 *
	 * @return array|\WP_Error
	 */
	private static function atum_api_request( $method = 'GET', $endpoint = '' ) {

		$args = array(
			'timeout'     => 20,
			'redirection' => 1,
			'user-agent'  => Helpers::get_atum_user_agent(),
			'sslverify'   => FALSE,
		);

		$function = 'wp_remote_get';

		if ( 'POST' === strtoupper( $method ) ) {
			$function = 'wp_remote_post';
		}

		return call_user_func( $function, self::ADDONS_STORE_URL . self::ADDONS_API_ENDPOINT . $endpoint, $args );

	}

	/**
	 * Update the license key and its current status for the specified addon
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name The addon name.
	 * @param array  $key_info   The license key info.
	 */
	public static function update_key( $addon_name, $key_info ) {

		$addon_name = strtolower( html_entity_decode( $addon_name ) );
		$is_trial   = FALSE;

		// Strip the 'trial' word from the addon name and use the same name for both.
		if ( str_contains( $addon_name, 'trial' ) ) {
			$addon_name = trim( str_replace( 'trial', '', $addon_name ) );
			$is_trial   = TRUE;
		}

		$keys = get_option( self::ADDONS_KEY_OPTION, [] );

		if ( ! is_array( $keys ) ) {
			$keys = [];
		}

		if ( $is_trial ) {
			$key_info['trial'] = TRUE;
		}

		$keys[ $addon_name ] = $key_info;
		update_option( self::ADDONS_KEY_OPTION, $keys );

		// Delete the status transient.
		self::delete_status_transient( $addon_name );

		// Delete any possible wrong persistent notice.
		AtumAdminNotices::clear_permament_notices();

	}

	/**
	 * Remove an addon key from the database.
	 *
	 * @since 1.9.27
	 *
	 * @param string $addon_name
	 */
	public static function remove_key( $addon_name ) {

		$addon_name = strtolower( html_entity_decode( $addon_name ) );

		if ( str_contains( $addon_name, 'trial' ) ) {
			$addon_name = trim( str_replace( 'trial', '', $addon_name ) );
		}

		$saved_keys = self::get_keys();

		if ( ! empty( $saved_keys ) && array_key_exists( $addon_name, $saved_keys ) ) {
			unset( $saved_keys[ $addon_name ] );
			self::delete_status_transient( $addon_name );
			update_option( self::ADDONS_KEY_OPTION, $saved_keys );
		}

	}

	/**
	 * Get the status data of an ATUM addon knowing its name
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name   The addon name (must match with the ATUM store's name).
	 * @param string $addon_slug   The addon slug (may match with the plugin directory name).
	 * @param string $addon_folder The addon slug (may match with the plugin directory name).
	 *
	 * @return object  The addon status info.
	 */
	public static function get_addon_status( $addon_name, $addon_slug, $addon_folder ) {

		$transient_key = AtumCache::get_transient_key( 'addon_status', strtolower( $addon_name ) );
		$addon_status  = AtumCache::get_transient( $transient_key, TRUE );

		if ( empty( $addon_status ) ) {

			$is_installed       = Helpers::is_plugin_installed( "ATUM $addon_name", 'name', FALSE );
			$is_trial_installed = Helpers::is_plugin_installed( "ATUM $addon_name (Trial version)", 'name', FALSE );

			// Status defaults.
			$addon_status = (object) array(
				'installed'   => ! empty( $is_installed ) || ! empty( $is_trial_installed ),
				'status'      => 'invalid',
				'key'         => '',
				'classes'     => [],
				'notice_type' => 'primary',
			);

			if (
				! empty( $is_trial_installed ) &&
				( empty( $is_installed ) || ( is_array( $is_trial_installed ) && is_plugin_active( key( $is_trial_installed ) ) ) )
			) {
				$addon_status->is_trial = TRUE;
			}
			else {

				$trial_name = strtolower( ! str_contains( $addon_name, 'Trial' ) ? "$addon_name Trial" : $addon_name );

				foreach ( self::$addons as $addon_key => $installed_addon ) {
					if ( strtolower( $installed_addon['name'] ) === $trial_name ) {
						if ( str_contains( $addon_key, '_trial' ) ) {
							$addon_status->is_trial = TRUE;
						}
						break;
					}
				}

			}

			$saved_license = self::get_keys( $addon_name );

			if (
				empty( $saved_license ) ||
				// When any add-on was previously activated but is no longer installed and the license is not valid, get rid of it.
				( 'valid' !== $saved_license['status'] && ! $addon_status->installed )
			) {

				$key_info = array(
					'key'    => '',
					'status' => ( ! empty( $saved_license ) && 'expired' === $saved_license['status'] ) ? 'expired' : 'invalid',
				);

				if ( ! empty( $saved_license['expires'] ) ) {
					$key_info['expires'] = $saved_license['expires'];
				}

				self::update_key( $addon_name, $key_info );

			}
			else {

				$addon_status->key = $saved_license['key'];

				if ( ! empty( $addon_status->key ) ) {

					// Check the license.
					$license_status = self::check_license( $addon_name, $addon_status->key );

					if ( ! is_wp_error( $license_status ) ) {

						$license_data = json_decode( wp_remote_retrieve_body( $license_status ) );

						if ( $license_data && TRUE === $license_data->success ) {

							// Confirm that the license belongs to the installed add-on.
							if ( isset( $addon_status->is_trial ) && TRUE === $addon_status->is_trial && ! str_contains( strtolower( $license_data->item_name ), 'trial' ) ) {

								// Is the user upgrading?
								if ( 'valid' === $license_data->license && strtolower( $license_data->item_name ) === strtolower( $addon_name ) ) {
									$addon_status->status           = $license_data->license;
									$addon_status->upgrade_required = TRUE;
								}
								else {
									$addon_status->status = $saved_license['status'] = 'invalid';
								}

							}
							elseif ( empty( $addon_status->is_trial ) && str_contains( strtolower( $license_data->item_name ), 'trial' ) ) {
								$addon_status->status = $saved_license['status'] = 'invalid';
							}
							else {

								$addon_status->status = $license_data->license;

								if ( ! empty( $license_data->expires ) ) {
									$addon_status->expires = ! Helpers::validate_mysql_date( $license_data->expires ) ? Helpers::date_format( $license_data->expires, FALSE ) : $license_data->expires;
								}

								if ( $addon_status->status !== $saved_license['status'] ) {
									$saved_license['status'] = $addon_status->status;
								}

							}

							if ( empty( $saved_license['expires'] ) || ( ! empty( $license_data->expires ) && $saved_license['expires'] !== $license_data->expires ) ) {
								$addon_status->expires = $saved_license['expires'] = $license_data->expires;
							}

							if ( ! empty( $license_data->trial ) ) {
								$addon_status->is_trial = $saved_license['trial'] = TRUE;

								if ( empty( $license_data->trial_extendable ) ) {
									$saved_license['extended'] = TRUE;
								}
							}

							self::update_key( $addon_name, $saved_license );

						}

					}

				}
				else {

					$addon_status->status     = 'no_key';
					$addon_status->classes[]  = 'no-key';
					$addon_status->label_text = __( 'Missing License!', ATUM_TEXT_DOMAIN );

					if ( ! empty( $is_installed ) ) {
						$addon_status->notice      = esc_html__( 'License key missing! Please, add your key to continue receiving automatic updates.', ATUM_TEXT_DOMAIN );
						$addon_status->notice_type = 'warning';
					}

				}

			}

			$is_expired = FALSE;

			if ( ! empty( $saved_license['expires'] ) ) {

				$addon_status->expires = $saved_license['expires'];

				$actual_timestamp     = time();
				$expiration_timestamp = strtotime( $addon_status->expires ?? 'now' );
				$is_expired           = 'expired' === $addon_status->status || $expiration_timestamp <= $actual_timestamp;

				if ( $is_expired ) {
					$addon_status->is_expired = TRUE;
				}

			}

			if ( empty( $is_installed ) && empty( $is_trial_installed ) ) {
				$addon_status->status        = 'not-installed';
				$addon_status->button_text   = __( 'Activate and Install', ATUM_TEXT_DOMAIN );
				$addon_status->button_class  = 'install-atum-addon';
				$addon_status->button_action = ATUM_PREFIX . 'install';
				$addon_status->label_text    = __( 'Not Installed', ATUM_TEXT_DOMAIN );
				$addon_status->classes[]     = 'not-installed';
			}
			elseif ( ! empty( $addon_status->is_trial ) ) {

				$addon_status->classes[]   = 'trial';
				$addon_status->label_text  = __( 'Trial', ATUM_TEXT_DOMAIN );
				$addon_status->extended    = ! isset( $license_data->trial_extendable ) || TRUE !== $license_data->trial_extendable;
				$addon_status->notice_type = 'warning';

				if ( empty( $addon_status->key ) && $addon_status->installed ) {

					$addon_status->button_text   = __( 'Activate', ATUM_TEXT_DOMAIN );
					$addon_status->button_class  = 'activate-key';
					$addon_status->button_action = ATUM_PREFIX . 'activate_license';
					$addon_status->notice        = esc_html__( 'License key missing! Please, add your key to continue using this trial.', ATUM_TEXT_DOMAIN );
					$addon_status->notice_type   = 'danger';

					if ( 'no_key' !== $addon_status->status ) {
						$addon_status->classes[]  = 'inactive';
						$addon_status->label_text = __( 'Not Activated', ATUM_TEXT_DOMAIN );
					}

				}
				elseif ( 'trial_used' === $addon_status->status ) {
					$addon_status->notice      = esc_html__( 'This trial has already been used on another site and is for a single use only.', ATUM_TEXT_DOMAIN );
					$addon_status->notice_type = 'danger';
				}
				elseif ( ! empty( $addon_status->upgrade_required ) ) {
					$addon_status->notice      = esc_html__( 'You are still using a trial and your license is for a full version. Please, uninstall the trial and reinstall from here to get the full version.', ATUM_TEXT_DOMAIN );
					$addon_status->notice_type = 'danger';
				}
				elseif ( ! empty( $license_data ) && TRUE !== $license_data->success && ! empty( $license_data->message ) ) {
					$addon_status->notice      = esc_html( $license_data->message );
					$addon_status->notice_type = 'danger';
				}
				elseif ( ! $is_expired && ! empty( $addon_status->expires ) ) {
					$time_ago        = new TimeAgo();
					$expiration_date = date_i18n( 'Y-m-d H:i:s', $expiration_timestamp ?? time() );
					/* translators: the time remaining */
					$addon_status->notice = sprintf( esc_html__( 'Trial period: %s ', ATUM_TEXT_DOMAIN ), str_replace( 'ago', esc_html__( 'remaining', ATUM_TEXT_DOMAIN ), $time_ago->inWordsFromStrings( $expiration_date ) ) );
				}
				else {

					if ( ! $addon_status->extended ) {
						$addon_status->notice = __( 'Trial period expired. You can extend it for 7 days more or unlock the full version by purchasing a license.<br>If you have already upgraded your license, uninstall the trial and reinstall the full version.', ATUM_TEXT_DOMAIN );
					}
					else {
						$addon_status->notice      = __( 'Trial period expired. Please, purchase a license to unlock the full version.<br>If you have already upgraded your license, uninstall the trial and reinstall the full version.', ATUM_TEXT_DOMAIN );
						$addon_status->notice_type = 'danger';
					}
				}

			}
			else {

				switch ( $addon_status->status ) {
					case 'invalid':
					case 'disabled':
					case 'item_name_mismatch':
						$addon_status->status        = 'invalid';
						$addon_status->button_text   = __( 'Validate', ATUM_TEXT_DOMAIN );
						$addon_status->button_class  = 'validate-key';
						$addon_status->button_action = ATUM_PREFIX . 'validate_license';
						$addon_status->classes[]     = 'invalid';
						$addon_status->label_text    = __( 'Invalid License', ATUM_TEXT_DOMAIN );
						/* translators: opening and closing link tags */
						$addon_status->notice      = sprintf( __( 'Your license is invalid. Please, remove it or reactivate your subscription to continue receiving updates. If you have already reactivated it, click %1$shere%2$s to recheck', ATUM_TEXT_DOMAIN ), '<a class="alert-link refresh-status" href="#">', '</a>' );
						$addon_status->notice_type = 'warning';
						break;

					case 'expired':
						$addon_status->button_text   = '';
						$addon_status->button_class  = '';
						$addon_status->button_action = ATUM_PREFIX . 'remove_license';
						$addon_status->classes[]     = 'expired';
						$addon_status->label_text    = __( 'Expired', ATUM_TEXT_DOMAIN );
						/* translators: opening and closing link tags */
						$addon_status->notice      = sprintf( __( 'Your license has expired. If you have already renewed the license, please click&nbsp; %1$shere%2$s &nbsp;to recheck.', ATUM_TEXT_DOMAIN ), '<a class="alert-link refresh-status" href="#">', '</a>' );
						$addon_status->notice_type = 'warning';
						break;

					case 'inactive':
					case 'site_inactive':
					case 'no_key':
						$addon_status->button_text   = __( 'Activate', ATUM_TEXT_DOMAIN );
						$addon_status->button_class  = 'activate-key';
						$addon_status->button_action = ATUM_PREFIX . 'activate_license';

						if ( 'no_key' !== $addon_status->status ) :
							$addon_status->classes[]  = 'inactive';
							$addon_status->label_text = __( 'Not Activated', ATUM_TEXT_DOMAIN );
						endif;
						break;

					case 'valid':
						$addon_status->button_text   = '';
						$addon_status->button_class  = '';
						$addon_status->button_action = ATUM_PREFIX . 'deactivate_license';
						$addon_status->classes[]     = 'valid';
						$addon_status->label_text    = __( 'Activated', ATUM_TEXT_DOMAIN );
						break;

				}

			}

			AtumCache::set_transient( $transient_key, $addon_status, DAY_IN_SECONDS, TRUE );

		}

		return $addon_status;

	}

	/**
	 * Delete an addon status transient
	 *
	 * @since 1.4.1.2
	 *
	 * @param string $addon_name
	 */
	public static function delete_status_transient( $addon_name ) {

		$transient_key = AtumCache::get_transient_key( 'addon_status', strtolower( $addon_name ) );
		AtumCache::delete_transients( $transient_key );

		// Delete the trial status transient too.
		if ( $addon_name ) {
			$trial_transient_key = AtumCache::get_transient_key( 'addon_status', strtolower( "$addon_name Trial" ) );
			AtumCache::delete_transients( $trial_transient_key );
		}

	}

	/* @noinspection PhpDocRedundantThrowsInspection */
	/**
	 * Download an ATUM addon and install it
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name    The addon name.
	 * @param string $addon_slug    The addon slug.
	 * @param string $download_link The link to download the addon zip file.
	 *
	 * @return array    An array with the result and the message.
	 *
	 * @throws AtumException If the download fails could throw an exception.
	 */
	public static function install_addon( $addon_name, $addon_slug, $download_link ) {

		// Ensure that the download link URL is pointing to the right place.
		if (
			! filter_var( $download_link, FILTER_VALIDATE_URL ) ||
			trailingslashit( wp_parse_url( $download_link, PHP_URL_SCHEME ) . '://' . wp_parse_url( $download_link, PHP_URL_HOST ) ) !== self::ADDONS_STORE_URL
		) {

			return array(
				'success' => FALSE,
				'data'    => __( 'The download link is not valid', ATUM_TEXT_DOMAIN ),
			);

		}

		// Start the addon download and installation.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		WP_Filesystem();

		$skin         = new \Automatic_Upgrader_Skin();
		$upgrader     = new \WP_Upgrader( $skin );
		$addon_folder = self::get_addon_folder( $addon_slug );
		$plugin       = $addon_folder && $addon_folder !== $addon_slug ? "$addon_folder/$addon_folder.php" : "$addon_slug/$addon_slug.php";
		$installed    = Helpers::is_plugin_installed( $plugin, 'file' );
		$activate     = $installed && ! is_plugin_active( $plugin );

		// Install this new addon.
		if ( ! $installed ) {

			// Suppress feedback.
			ob_start();

			try {

				$download = $upgrader->download_package( $download_link );

				if ( is_wp_error( $download ) ) {
					throw new AtumException( 'addon_download_error', $download->get_error_message() ?: $download->get_error_data() );
				}

				$working_dir = $upgrader->unpack_package( $download );

				if ( is_wp_error( $working_dir ) ) {
					throw new AtumException( 'addon_unpack_error', $working_dir->get_error_message() ?: $working_dir->get_error_data() );
				}

				$result = $upgrader->install_package( array(
					'source'                      => $working_dir,
					'destination'                 => WP_PLUGIN_DIR,
					'clear_destination'           => TRUE,
					'abort_if_destination_exists' => FALSE,
					'clear_working'               => TRUE,
					'hook_extra'                  => array(
						'type'   => 'plugin',
						'action' => 'install',
					),
				) );

				// If the user is upgrading a trial to full, uninstall the trial and block any uninstallation hooks.
				$is_trial_addon = str_contains( strtolower( $addon_name ), 'trial' );
				if ( ! $is_trial_addon && Helpers::is_plugin_installed( "ATUM $addon_name (Trial version)", 'name' ) ) {
					add_filter( 'atum/addons/prevent_uninstall_data_removal', '__return_true' );
					$plugin = [ "{$addon_folder}-trial/{$addon_folder}-trial.php" ];
					deactivate_plugins( $plugin, TRUE );
					delete_plugins( $plugin );
					remove_filter( 'atum/addons/prevent_uninstall_data_removal', '__return_true' );
				}

				if ( is_wp_error( $result ) ) {
					throw new AtumException( 'addon_not_installed', $result->get_error_message() ?: $result->get_error_data() );
				}

				$activate = TRUE;

			} catch ( AtumException $e ) {

				if ( $e->getMessage() ) {
					$message = sprintf(
						/* translators: first one is the add-on name and the second the error message */
						__( "ATUM %1\$s could not be installed (reason: %2\$s). Please, contact with ATUM's support.", ATUM_TEXT_DOMAIN ),
						$addon_name,
						lcfirst( $e->getMessage() )
					);
				}
				else {
					$message = sprintf(
						/* translators: the add-on name */
						__( "ATUM %s could not be installed. Please, contact with ATUM's support.", ATUM_TEXT_DOMAIN ),
						$addon_name
					);
				}

				return array(
					'success' => FALSE,
					'data'    => $message,
				);

			}

			// Discard feedback.
			ob_end_clean();

		}

		wp_clean_plugins_cache();

		// Activate the add-on.
		if ( $activate ) {

			try {
				$result = activate_plugin( $plugin );

				if ( is_wp_error( $result ) ) {
					throw new AtumException( 'addon_activation_error', $result->get_error_message() );
				}

			} catch ( AtumException $e ) {

				return array(
					'success' => FALSE,
					'data'    => sprintf(
						/* translators: first one is the add-on nam, and the others are the opening and closing HTML link tags to the plugins page */
						__( 'ATUM %1$s was installed but could not be activated.<br>Please, activate it manually from the %2$splugins page.%3$s', ATUM_TEXT_DOMAIN ),
						$addon_name,
						'<a href="' . admin_url( 'plugins.php' ) . '">',
						'</a>'
					),
				);

			}

			// Installed and activated.
			return array(
				'success' => TRUE,
				/* translators: the add-on name */
				'data'    => sprintf( __( 'The ATUM %s addon was installed and activated successfully.', ATUM_TEXT_DOMAIN ), $addon_name ),
			);

		}

		// Installed.
		return array(
			'success' => TRUE,
			/* translators: the add-on name */
			'data'    => sprintf( __( 'The ATUM %s addon was installed successfully.', ATUM_TEXT_DOMAIN ), $addon_name ),
		);

	}

	/**
	 * Call to the license manager API to validate a license
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name The addon name (must match to the ATUM store's addon name).
	 * @param string $key        The license key.
	 *
	 * @return array|\WP_Error
	 */
	public static function check_license( $addon_name, $key ) {

		return self::lm_api_request( $addon_name, $key, 'check_license' );
	}

	/**
	 * Call to the license manager API to activate a license
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name The addon name (must match to the ATUM store's addon name).
	 * @param string $key        The license key.
	 *
	 * @return array|\WP_Error
	 */
	public static function activate_license( $addon_name, $key ) {

		$result = self::lm_api_request( $addon_name, $key, 'activate_license' );
		do_action( 'atum/addons/activate_license', $result );

		return $result;
	}

	/**
	 * Call to the license manager API to deactivate a license
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name The addon name (must match to the ATUM store's addon name).
	 * @param string $key        The license key.
	 *
	 * @return array|\WP_Error
	 */
	public static function deactivate_license( $addon_name, $key ) {

		$result = self::lm_api_request( $addon_name, $key, 'deactivate_license' );
		do_action( 'atum/addons/deactivate_license', $result );

		return $result;
	}

	/**
	 * Call to the SML ATUM API to extend an extendable trial license
	 *
	 * @since 1.9.27
	 *
	 * @param string $key The trial license key.
	 *
	 * @return array|\WP_Error
	 */
	public static function extend_trial( $key ) {

		$result = self::atum_api_request( 'POST', "/extend-trial?key=$key" );

		if ( is_wp_error( $result ) ) {

			$error_message = $result->get_error_message();

			if ( TRUE === ATUM_DEBUG ) {
				error_log( __METHOD__ . ": $error_message" );
			}

		}
		elseif ( 200 !== wp_remote_retrieve_response_code( $result ) ) {

			$error = $resp_body->message ?? __( "Unexpected error. Please contact ATUM's support", ATUM_TEXT_DOMAIN );

			if ( TRUE === ATUM_DEBUG ) {
				error_log( __METHOD__ . ": $error" );
			}

			$result = new \WP_Error( 'unexpected_error', $error );

		}
		else {
			$response_body = wp_remote_retrieve_body( $result );
			$result        = $response_body ? json_decode( $response_body, TRUE ) : [];
		}

		return $result;

	}

	/**
	 * Call to the license manager API to get an addon version info
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name The addon name (must match to the ATUM store's addon name).
	 * @param string $key        The license key.
	 * @param string $version    The current addon version.
	 * @param bool   $beta       Whether to look for beta versions.
	 *
	 * @return array|\WP_Error
	 */
	public static function get_latest_version( $addon_name, $key, $version, $beta = FALSE ) {

		return self::lm_api_request( $addon_name, $key, 'get_version', 'GET', array(
			'version' => $version,
			'beta'    => $beta,
		) );

	}

	/**
	 * Get the currently installed version number for any ATUM add-on
	 *
	 * @since 1.9.27
	 *
	 * @param string $addon_name
	 *
	 * @return string|NULL
	 */
	public static function get_installed_version( $addon_name ) {

		$update_cache = get_site_transient( 'update_plugins' );

		if ( empty( $update_cache ) ) {
			return NULL;
		}

		$version    = NULL;
		$addon_path = wp_list_filter( self::$addons_paths, [ 'name' => $addon_name ] );

		if (
			! empty( $addon_path ) && ! empty( $update_cache->checked ) &&
			is_array( $update_cache->checked ) && array_key_exists( current( $addon_path )['basename'], $update_cache->checked )
		) {
			$version = $update_cache->checked[ current( $addon_path )['basename'] ];
		}

		return $version;

	}

	/**
	 * Getter for the installed addons array
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	public static function get_installed_addons() {
		return self::$addons;
	}

	/**
	 * Setter for the installed addons array
	 *
	 * @since 1.9.26
	 *
	 * @param array $addons
	 */
	public static function set_installed_addons( $addons ) {
		self::$addons = $addons;
	}

	/**
	 * Check whether the specified add-on is active (so installed + enabled + meeting all the minimum requirements)
	 *
	 * @since 1.7.0
	 *
	 * @param string $addon_key
	 *
	 * @return bool
	 */
	public static function is_addon_active( $addon_key ) {
		return ( ! empty( self::$addons[ $addon_key ] ) || ! empty( self::$addons[ "{$addon_key}_trial" ] ) ) && ( self::is_addon_bootstrapped( $addon_key ) || self::is_addon_bootstrapped( "{$addon_key}_trial" ) );
	}

	/**
	 * Checks if there is a valid key installed in the current site
	 *
	 * @since 1.4.1.2
	 *
	 * @return bool
	 */
	public static function has_valid_key() {

		$keys = self::get_keys();

		if ( ! empty( $key ) ) {
			foreach ( $keys as $key ) {
				if ( ! empty( $key['key'] ) && ! empty( $key['status'] ) && 'valid' === $key['status'] ) {
					return TRUE;
				}
			}
		}

		return FALSE;

	}

	/**
	 * Show row meta for the premium add-ons on the plugins screen
	 *
	 * @since 1.6.8
	 *
	 * @param array  $links Plugin row meta.
	 * @param string $file  Plugin base file.
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {

		if ( strpos( $file, 'atum-' ) === 0 && 'atum-stock-manager-for-woocommerce/atum-stock-manager-for-woocommerce.php' !== $file ) {

			$row_meta = array(
				'support' => '<a href="https://stockmanagementlabs.ticksy.com/" aria-label="' . esc_attr__( 'Open a private ticket', ATUM_TEXT_DOMAIN ) . '" target="_blank">' . esc_html__( 'Premium Support', ATUM_TEXT_DOMAIN ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return $links;

	}

	/**
	 * Check if the current site is considered a local/test/staging site
	 *
	 * @since 1.9.1
	 *
	 * @return bool If we're considering the URL local or not
	 */
	public static function is_local_url() {

		$is_local_url = FALSE;

		// Trim it up.
		$url = strtolower( trim( home_url() ) );

		// Need to get the host...so let's add the scheme so we can use parse_url.
		if ( ! str_contains( $url, 'http://' ) && ! str_contains( $url, 'https://' ) ) {
			$url = "http://$url";
		}

		$url_parts = wp_parse_url( $url );
		$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : FALSE;

		if ( ! empty( $url ) && ! empty( $host ) ) {

			if ( FALSE !== ip2long( $host ) ) {

				if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					$is_local_url = TRUE;
				}

			}
			elseif ( 'localhost' === $host ) {
				$is_local_url = TRUE;
			}

			$tlds_to_check = array(
				'.local',
				'.localhost',
				'.test',
			);

			foreach ( $tlds_to_check as $tld ) {
				if ( str_contains( $host, $tld ) ) {
					$is_local_url = TRUE;
					break;
				}
			}

			if ( substr_count( $host, '.' ) > 1 ) {

				$subdomains_to_check = array(
					'dev.',
					'www.dev.',
					'test.',
					'*.staging.',
					'*.test.',
					'staging*.',
					'*.wpengine.com',
					'*.cloudwaysapps.com',
					'*.wpenginepowered.com',
					'*.pantheonsite.io',
					'*.flywheelsites.com',
					'*.flywheelstaging.com',
					'*.kinsta.com',
					'*.kinsta.cloud',
					'*.azurewebsites.net',
					'*.wpserveur.net',
					'*-liquidwebsites.com',
					'*.myftpupload.com',
					'*.dream.press',
					'*.sg-host.com',
					'*.platformsh.site',
					'*.wpstage.net',
					'*.bigscoots-staging.com',
					'*.wpsc.site',
					'*.runcloud.link',
					'*.onrocket.site',
					'*.singlestaging.com',
					'*.nxcli.net',
				);

				foreach ( $subdomains_to_check as $subdomain ) {

					$subdomain = str_replace( '.', '(.)', $subdomain );
					$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );

					if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
						$is_local_url = TRUE;
						break;
					}
				}

			}
		}

		return $is_local_url;

	}

	/**
	 * Get the last API access transient in order to check if the limits are reached
	 *
	 * @since 1.9.23.1
	 *
	 * @param string $key
	 *
	 * @return bool|mixed
	 */
	public static function get_last_api_access( $key ) {

		if ( defined( 'ATUM_DEBUG' ) && TRUE === ATUM_DEBUG ) {
			return FALSE;
		}

		$limit_requests_transient = AtumCache::get_transient_key( 'sml_api_limit', $key );

		return AtumCache::get_transient( $limit_requests_transient, TRUE );

	}

	/**
	 * Set or deletes the last API access transient, so we can do a new request
	 *
	 * @since 1.9.23.1
	 *
	 * @param string $key
	 * @param bool   $delete
	 */
	public static function set_last_api_access( $key, $delete = FALSE ) {

		$limit_requests_transient = AtumCache::get_transient_key( 'sml_api_limit', $key );

		if ( $delete ) {
			// Remove the access blocking transient.
			AtumCache::delete_transients( $limit_requests_transient );
		}
		else {
			// Block access for 15 minutes.
			AtumCache::set_transient( $limit_requests_transient, time(), 15 * MINUTE_IN_SECONDS, TRUE );
		}

	}

	/**
	 * Getter for the addons path prop
	 *
	 * @since 1.9.27
	 *
	 * @return array
	 */
	public static function get_addons_paths() {
		return self::$addons_paths;
	}

	/**
	 * Get class name for the addons loader
	 *
	 * @since 1.9.27
	 *
	 * @return string
	 */
	public static function get_addons_loader_class() {
		return ( defined( 'ATUM_DEBUG' ) && TRUE === ATUM_DEBUG && file_exists( ATUM_PATH . 'classes/Addons/AddonsLoaderDev.php' ) ) ?
			'\Atum\Addons\AddonsLoaderDev' : '\Atum\Addons\AddonsLoader';
	}

	/**
	 * Check whether an addon is bootstrapped
	 *
	 * @since 1.9.27
	 *
	 * @param string $addon
	 *
	 * @return bool
	 */
	public static function is_addon_bootstrapped( $addon ) {
		return in_array( $addon, self::get_addons_loader_class()::get_bootstrapped_addons() );
	}


	/****************************
	 * Instance methods
	 ****************************/

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
	 * @return Addons
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

}
