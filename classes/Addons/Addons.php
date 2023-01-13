<?php
/**
 * ATUM add-ons manager
 *
 * @package         Atum
 * @subpackage      Addons
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2023 Stock Management Labs™
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


class Addons {

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
	 * @var string[]
	 */
	private $addons_paths = array(
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
		'product_levels'  => [
			'name'     => 'Product Levels',
			'basename' => 'atum-product-levels/atum-product-levels.php',
		],
		'purchase_orders' => [
			'name'     => 'Purchase Orders PRO',
			'basename' => 'atum-purchase-orders/atum-purchase-orders.php',
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
	const MENU_ORDER = 81;

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
		if ( defined( 'ATUM_DEBUG' ) && TRUE === ATUM_DEBUG && class_exists( '\Atum\Addons\AddonsLoaderDev' ) ) {
			new AddonsLoaderDev();
		}
		else {
			new AddonsLoader();
		}

		if ( is_admin() ) {

			// Automatic updates for addons.
			add_action( 'admin_init', array( $this, 'check_addons_updates' ), 0 );

			// Disable SSL verification in order to prevent addon download failures.
			add_filter( 'http_request_args', array( $this, 'http_request_args' ), 10, 2 );

			// Allow downloading files from local servers while developing.
			if ( ATUM_DEBUG ) {
				add_filter( 'http_request_host_is_external', '__return_true' );
			}

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

					AtumAdminNotices::add_notice( $message, 'info', TRUE, FALSE, 'activate-addons' );

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

		wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', array(), ATUM_VERSION );
		wp_register_style( 'atum-addons', ATUM_URL . 'assets/css/atum-addons.css', array( 'sweetalert2' ), ATUM_VERSION );

		wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', array(), ATUM_VERSION, TRUE );
		Helpers::maybe_es6_promise();

		// ATUM marketing popup.
		AtumMarketingPopup::get_instance()->maybe_enqueue_scripts();

		wp_register_script( 'atum-addons', ATUM_URL . 'assets/js/build/atum-addons.js', array( 'jquery', 'sweetalert2' ), ATUM_VERSION, TRUE );

		wp_localize_script( 'atum-addons', 'atumAddons', array(
			'activate'             => __( 'Activate', ATUM_TEXT_DOMAIN ),
			'activated'            => __( 'Activated!', ATUM_TEXT_DOMAIN ),
			'activation'           => __( 'License Activation', ATUM_TEXT_DOMAIN ),
			'addonActivated'       => __( 'Your add-on license has been activated.', ATUM_TEXT_DOMAIN ),
			'allowedDeactivations' => __( 'You are allowed to remove a license a max of 2 times.', ATUM_TEXT_DOMAIN ),
			'cancel'               => __( 'Cancel', ATUM_TEXT_DOMAIN ),
			'continue'             => __( 'Continue', ATUM_TEXT_DOMAIN ),
			'error'                => __( 'Error!', ATUM_TEXT_DOMAIN ),
			'invalidKey'           => __( 'Please enter a valid add-on license key.', ATUM_TEXT_DOMAIN ),
			'limitedDeactivations' => __( 'Limited Deactivations!', ATUM_TEXT_DOMAIN ),
			'ok'                   => __( 'OK', ATUM_TEXT_DOMAIN ),
			'success'              => __( 'Success!', ATUM_TEXT_DOMAIN ),
		) );

		wp_enqueue_style( 'sweetalert2' );
		wp_enqueue_style( 'atum-addons' );

		if ( is_rtl() ) {
			wp_register_style( 'atum-addons-rtl', ATUM_URL . 'assets/css/atum-addons-rtl.css', array( 'atum-addons' ), ATUM_VERSION );
			wp_enqueue_style( 'atum-addons-rtl' );
		}

		if ( wp_script_is( 'es6-promise', 'registered' ) ) {
			wp_enqueue_script( 'es6-promise' );
		}

		wp_enqueue_script( 'sweetalert2' );
		wp_enqueue_script( 'atum-addons' );

		$args = array(
			'addons'      => self::get_addons_list(),
			'addons_keys' => self::get_keys(),
		);

		Helpers::load_view( 'addons', $args );

	}

	/**
	 * Check for updates for the installed ATUM addons
	 *
	 * @since 1.2.0
	 */
	public function check_addons_updates() {

		$license_keys = self::get_keys();

		if ( ! empty( $license_keys ) ) {

			$installed_addons = self::$addons;

			// We must check if there are others not enabled that should be updated.
			foreach ( array_keys( $this->addons_paths ) as $addon_key ) {

				if ( ! array_key_exists( $addon_key, self::$addons ) ) {

					// Check if it really exists.
					$plugin_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->addons_paths[ $addon_key ]['basename'];
					if ( file_exists( $plugin_file ) ) {

						$file_data = get_file_data( $plugin_file, array(
							'addon_url'   => 'Plugin URI',
							'version'     => 'Version',
							'description' => 'Description',
						) );

						if ( ! empty( $file_data ) ) {
							$installed_addons[ $addon_key ] = array_merge( $file_data, $this->addons_paths[ $addon_key ] );
						}

					}

				}

			}

			foreach ( $license_keys as $addon_name => $license_key ) {

				$addon_slug = '';

				foreach ( $installed_addons as $slug => $addon_data ) {

					if (
						strtolower( $addon_data['name'] ) === strtolower( $addon_name ) &&
						array_key_exists( $slug, $this->addons_paths )
					) {
						$addon_slug = $slug;
						break;
					}

				}

				if (
					$addon_slug && $license_key &&
					is_array( $license_key ) && ! empty( $license_key['key'] )
				) {

					if ( 'valid' === $license_key['status'] ) {

						// All the ATUM addons' names should start with 'ATUM'.
						$addon_info = Helpers::is_plugin_installed( "ATUM $addon_name", '', 'name', FALSE );

						if ( $addon_info ) {

							// Set up the updater.
							$addon_file = key( $addon_info );

							new Updater( $addon_file, array(
								'version'   => $addon_info[ $addon_file ]['Version'],
								'license'   => $license_key['key'],
								'item_name' => $addon_name,
								'beta'      => FALSE,
							) );

						}

					}
					elseif ( in_array( $license_key['status'], [ 'disabled', 'expired', 'invalid' ] ) ) {
						/* translators: the add-on name */
						AtumAdminNotices::add_notice( sprintf( __( "ATUM %1\$s license has expired or is invalid. You can no longer update or take advantage of support. Running outdated plugins may cause functionality issues and compromise your site's security and data. %2\$sYou can extend your license for 15%% OFF now (valid 14 days after the license expires).%3\$s", ATUM_TEXT_DOMAIN ), $addon_name, '<a href="https://stockmanagementlabs.com/login" target="_blank">', '</a>' ), 'warning', TRUE, TRUE );
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
			if ( FALSE !== self::get_last_api_access() ) {
				return FALSE;
			}

			$args = array(
				'timeout'     => 20,
				'redirection' => 1,
				'user-agent'  => 'ATUM/' . ATUM_VERSION . ';' . home_url(),
				'sslverify'   => FALSE,
			);

			$response = wp_remote_get( self::ADDONS_STORE_URL . self::ADDONS_API_ENDPOINT, $args );

			// Admin notification about the error.
			if ( is_wp_error( $response ) ) {

				$error_message = $response->get_error_message();

				if ( TRUE === ATUM_DEBUG ) {
					error_log( __METHOD__ . ": $error_message" );
				}

				/* translators: the error message */
				AtumAdminNotices::add_notice( sprintf( __( "Something failed getting the ATUM's add-ons list: %s", ATUM_TEXT_DOMAIN ), $error_message ), 'error', TRUE, TRUE );

				$addons = FALSE;

			}
			elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {

				AtumAdminNotices::add_notice( __( "Something failed getting the ATUM's add-ons list. Please retry after some minutes.", ATUM_TEXT_DOMAIN ), 'error', TRUE, TRUE );
				$addons = FALSE;

			}
			else {

				$response_body = wp_remote_retrieve_body( $response );
				$addons        = $response_body ? json_decode( $response_body, TRUE ) : array();

				if ( empty( $addons ) ) {
					AtumAdminNotices::add_notice( __( "Something failed getting the ATUM's add-ons list. Please retry after some minutes.", ATUM_TEXT_DOMAIN ), 'error', TRUE, TRUE );
					$addons = FALSE;
				}

			}

			if ( ! empty( $addons ) ) {
				self::set_last_api_access( TRUE );
				AtumCache::set_transient( $transient_name, $addons, DAY_IN_SECONDS, TRUE );
			}
			else {
				self::set_last_api_access();
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

			foreach ( $addons as $addon ) {
				if ( $addon_slug === $addon['info']['slug'] ) {
					return $addon['info']['folder'];
				}
			}

		}

		return FALSE;

	}

	/**
	 * Disable SSL verification in order to prevent download update failures
	 *
	 * @since 1.2.0
	 *
	 * @param array  $args
	 * @param string $url
	 *
	 * @return array
	 */
	public function http_request_args( $args, $url ) {

		// If it is a https request, and we are performing a package download, disable ssl verification.
		if ( strpos( $url, 'https://' ) !== FALSE && strpos( $url, 'package_download' ) !== FALSE && strpos( $url, self::ADDONS_STORE_URL ) !== FALSE ) {
			$args['sslverify'] = FALSE;
		}

		return $args;
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

		$keys = get_option( self::ADDONS_KEY_OPTION );

		if ( empty( $addon_name ) ) {
			$keys = self::check_addons_keys( $keys );
		}

		$lower_keys = array();
		$addon_name = strtolower( $addon_name );

		if ( ! empty( $keys ) ) {
			foreach ( $keys as $key_name => $key_value ) {
				if ( FALSE === in_array( $key_name, array_keys( $lower_keys ) ) ) {
					$lower_key                = strtolower( $key_name );
					$lower_keys[ $lower_key ] = $key_value;
				}
			}
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

		$result  = array();
		$checked = array();

		foreach ( $keys as $addon => $license ) {

			// Remove inactive licenses.
			if ( in_array( $license['status'], [ 'inactive', 'site_inactive' ] ) ) {
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

			foreach ( self::$addons as $slug => $registered_addon ) {
				self::delete_status_transient( $registered_addon['name'] );
			}
		}

		return $result;

	}

	/**
	 * Generate a license API request
	 *
	 * @param string $addon_name   The addon name (must match to the ATUM store's addon name).
	 * @param string $key          The license key.
	 * @param string $endpoint     The API endpoint.
	 * @param array  $extra_params Optional. Any other param that will be sent to the API.
	 *
	 * @return array|\WP_Error
	 */
	private static function api_request( $addon_name, $key, $endpoint, $extra_params = array() ) {

		$params = array_merge( $extra_params, array(
			'edd_action' => $endpoint,
			'license'    => $key,
			'item_name'  => rawurlencode( $addon_name ),
			'url'        => home_url(),
		) );

		$request_params = array(
			'timeout'   => 20,
			'sslverify' => FALSE,
			'body'      => $params,
		);

		// Call the license manager API.
		return wp_remote_post( self::ADDONS_STORE_URL, $request_params );

	}

	/**
	 * Update the license key and its current status for the specified addon
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name The addon name.
	 * @param array  $key        The license key.
	 */
	public static function update_key( $addon_name, $key ) {

		$keys                = get_option( self::ADDONS_KEY_OPTION );
		$keys[ strtolower( $addon_name ) ] = $key;
		update_option( self::ADDONS_KEY_OPTION, $keys );
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
	 * @return array  The addon status info.
	 */
	public static function get_addon_status( $addon_name, $addon_slug, $addon_folder ) {

		$transient_name = AtumCache::get_transient_key( 'addon_status', $addon_name );
		$addon_status   = AtumCache::get_transient( $transient_name, TRUE );
		$is_installed   = Helpers::is_plugin_installed( $addon_slug, $addon_folder );

		if ( empty( $addon_status ) || $is_installed !== $addon_status['installed'] ) {

			// Status defaults.
			$addon_status = array(
				'installed' => $is_installed,
				'status'    => 'invalid',
				'key'       => '',
			);

			$saved_license = self::get_keys( $addon_name );

			if (
				empty( $saved_license ) ||
				// When any add-on was previously activated but is no longer installed and the license is not valid, get rid of it.
				( 'valid' !== $saved_license['status'] && ! $addon_status['installed'] )
			) {

				self::update_key( $addon_name, [
					'key'    => '',
					'status' => ! empty( $saved_license ) && 'expired' === $saved_license['status'] ? 'expired' : 'invalid',
				] );

			}
			else {

				$addon_status['key'] = $saved_license['key'];

				if ( ! empty( $addon_status['key'] ) ) {

					// Check the license.
					$status = self::check_license( $addon_name, $addon_status['key'] );

					if ( ! is_wp_error( $status ) ) {

						$license_data = json_decode( wp_remote_retrieve_body( $status ) );

						if ( $license_data ) {

							$addon_status['status']  = $license_data->license;
							$addon_status['expires'] = Helpers::validate_mysql_date( $license_data->expires ) ? Helpers::date_format( $license_data->expires, FALSE, FALSE, 'Y-m-d') : $license_data->expires;

							if ( $license_data->license !== $saved_license['status'] ) {
								$saved_license['status'] = $license_data->license;
								self::update_key( $addon_name, $saved_license );
							}

						}

					}
				}
				else {
					$addon_status['status']  = 'not-activated';
				}

			}
			if ( ! $is_installed ) {
				$addon_status['status']        = 'not-installed';
				$addon_status['button_text']   = __( 'Activate and Install', ATUM_TEXT_DOMAIN );
				$addon_status['button_class']  = 'install-addon';
				$addon_status['button_action'] = ATUM_PREFIX . 'install';
			}
			else {
				switch ( $addon_status['status'] ) {
					case 'invalid':
					case 'disabled':
					case 'item_name_mismatch':
						$addon_status['status']        = 'invalid';
						$addon_status['button_text']   = __( 'Validate', ATUM_TEXT_DOMAIN );
						$addon_status['button_class']  = 'validate-key';
						$addon_status['button_action'] = ATUM_PREFIX . 'validate_license';
						break;
					case 'expired':
						$addon_status['status']        = 'expired';
						$addon_status['button_text']   = '';
						$addon_status['button_class']  = '';
						$addon_status['button_action'] = ATUM_PREFIX . 'remove_license';
						break;

					// Not possible??
					case 'inactive':
					case 'site_inactive':
						$addon_status['status']        = 'inactive';
						$addon_status['button_text']   = __( 'Activate', ATUM_TEXT_DOMAIN );
						$addon_status['button_class']  = 'activate-key';
						$addon_status['button_action'] = ATUM_PREFIX . 'activate_license';
						break;

					case 'valid':
						$addon_status['button_text']   = '';
						$addon_status['button_class']  = '';
						$addon_status['button_action'] = ATUM_PREFIX . 'deactivate_license';
						break;

					case 'not-activated':
						$addon_status['button_text']   = __( 'Activate', ATUM_TEXT_DOMAIN );
						$addon_status['button_class']  = 'activate-key';
						$addon_status['button_action'] = ATUM_PREFIX . 'activate_license';
						break;
				}
			}



			AtumCache::set_transient( $transient_name, $addon_status, DAY_IN_SECONDS, TRUE );

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

		$transient_name = AtumCache::get_transient_key( 'addon_status', $addon_name );
		AtumCache::delete_transients( $transient_name );
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
		$installed    = Helpers::is_plugin_installed( $addon_slug, $addon_folder );
		$activate     = $installed ? ! is_plugin_active( $plugin ) : FALSE;

		// Install this new addon.
		if ( ! $installed ) {

			// Suppress feedback.
			ob_start();

			try {

				$download = $upgrader->download_package( $download_link );

				if ( is_wp_error( $download ) ) {
					throw new AtumException( 'addon_download_error', $download->get_error_message() ?: $download->get_error_data() );
				}

				$working_dir = $upgrader->unpack_package( $download, TRUE );

				if ( is_wp_error( $working_dir ) ) {
					throw new AtumException( 'addon_unpack_error', $working_dir->get_error_message() ?: $working_dir->get_error_data() );
				}

				$result = $upgrader->install_package( array(
					'source'                      => $working_dir,
					'destination'                 => WP_PLUGIN_DIR,
					'clear_destination'           => FALSE,
					'abort_if_destination_exists' => FALSE,
					'clear_working'               => TRUE,
					'hook_extra'                  => array(
						'type'   => 'plugin',
						'action' => 'install',
					),
				) );

				if ( is_wp_error( $result ) ) {
					throw new AtumException( 'addon_not_installed', $result->get_error_message() ?: $result->get_error_data() );
				}

				$activate = TRUE;

			} catch ( AtumException $e ) {

				return array(
					'success' => FALSE,
					'data'    => sprintf(
					/* translators: first one is the add-on nam and the second the error message */
						__( 'ATUM %1$s could not be installed (%2$s).<br>Please, do %3$sopen a ticket%4$s to contact with the ATUM support team.', ATUM_TEXT_DOMAIN ),
						$addon_name,
						$e->getMessage(),
						'<a href="https://stockmanagementlabs.ticksy.com/" target="_blank">',
						'</a>'
					),
				);

			}

			// Discard feedback.
			ob_end_clean();

		}

		wp_clean_plugins_cache();

		// Activate this thing.
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
						__( 'ATUM %1$s was installed but could not be activated. %2$sPlease activate it manually by clicking here.%3$s', ATUM_TEXT_DOMAIN ),
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

		return self::api_request( $addon_name, $key, 'check_license' );
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

		$result = self::api_request( $addon_name, $key, 'activate_license' );
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

		$result = self::api_request( $addon_name, $key, 'deactivate_license' );
		do_action( 'atum/addons/deactivate_license', $result );

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
	public static function get_version( $addon_name, $key, $version, $beta = FALSE ) {

		return self::api_request( $addon_name, $key, 'get_version', array(
			'version' => $version,
			'beta'    => $beta,
		) );
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
	 * @param string $addon_name
	 *
	 * @return bool
	 */
	public static function is_addon_active( $addon_name ) {

		return isset( self::$addons[ $addon_name ] );
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
		if ( FALSE === strpos( $url, 'http://' ) && FALSE === strpos( $url, 'https://' ) ) {
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
				'.test',
			);

			foreach ( $tlds_to_check as $tld ) {
				if ( FALSE !== strpos( $host, $tld ) ) {
					$is_local_url = TRUE;
					break;
				}
			}

			if ( substr_count( $host, '.' ) > 1 ) {

				$subdomains_to_check = array(
					'dev.',
					'test.',
					'*.staging.',
					'*.test.',
					'staging-*.',
					'*.wpengine.com',
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
	 * @return bool|mixed
	 */
	public static function get_last_api_access() {

		$limit_requests_transient = AtumCache::get_transient_key( 'sml_api_limit' );

		return AtumCache::get_transient( $limit_requests_transient, TRUE );

	}

	/**
	 * Set or deletes the last API access transient, so we can do a new request
	 *
	 * @since 1.9.23.1
	 *
	 * @param bool $delete
	 */
	public static function set_last_api_access( $delete = FALSE ) {

		$limit_requests_transient = AtumCache::get_transient_key( 'sml_api_limit' );

		if ( $delete ) {
			// Remove the access blocking transient.
			AtumCache::delete_transients( $limit_requests_transient );
		}
		else {
			// Block access for 15 minutes.
			AtumCache::set_transient( $limit_requests_transient, time(), 15 * MINUTE_IN_SECONDS, TRUE );
		}

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
