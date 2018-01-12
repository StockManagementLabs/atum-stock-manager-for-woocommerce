<?php
/**
 * @package         Atum
 * @subpackage      Addons
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.0
 *
 * ATUM add-ons loader
 */

namespace Atum\Addons;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumException;
use Atum\Inc\Helpers;


class Addons {

	/**
	 * The singleton instance holder
	 * @var Addons
	 */
	private static $instance;

	/**
	 * The list of registered ATUM's addons
	 * @var array
	 */
	private static $addons = array();

	/**
	 * The ATUM's addons store URL
	 */
	const ADDONS_STORE_URL = 'https://www.stockmanagementlabs.com/';

	/**
	 * The ATUM's addons API endpoint
	 */
	const ADDONS_API_ENDPOINT = 'addons-api';

	/**
	 * The name used to store the addons keys in db
	 */
	const ADDONS_KEY_OPTION = ATUM_PREFIX . 'addons_keys';

	/**
	 * The menu order for this module
	 */
	const MENU_ORDER = 90;

	/**
	 * Singleton constructor
	 *
	 * @since 1.2.0
	 */
	private function __construct() {

		// Get all the installed addons
		self::$addons = apply_filters('atum/addons/setup', self::$addons);

		// Add the module menu
		add_filter( 'atum/admin/menu_items', array($this, 'add_menu'), self::MENU_ORDER );

		// Initialize the addons
		add_action( 'after_setup_theme', array($this, 'init_addons'), 99 );

		if ( is_admin() ) {

			// Automatic updates for addons
			add_action( 'admin_init', array( $this, 'check_addons_updates' ), 0 );

			// Disable SSL verification in order to prevent addon download failures
			add_filter( 'http_request_args', array( $this, 'http_request_args' ), 10, 2 );

			// Allow downloading files from local servers while developing
			if ( ATUM_DEBUG ) {
				add_filter( 'http_request_host_is_external', '__return_true' );
			}

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
	public function add_menu ($menus) {

		$menus['addons'] = array(
			'title'      => __( 'Add-ons', ATUM_TEXT_DOMAIN ),
			'callback'   => array( $this, 'load_addons_page' ),
			'slug'       => ATUM_TEXT_DOMAIN . '-addons',
			'menu_order' => self::MENU_ORDER
		);

		return $menus;

	}

	/**
	 * Initialize the installed ATUM's addons
	 *
	 * @since 1.2.0
	 */
	public function init_addons () {

		if ( ! empty(self::$addons) ) {
			foreach (self::$addons as $addon) {

				// Load the addon. Each addon should have a callback method for bootstraping
				if ( ! empty( $addon['bootstrap'] ) && is_callable( $addon['bootstrap'] ) ) {
					call_user_func( $addon['bootstrap'] );
				}

			}
		}

	}

	/**
	 * Load the Addons page
	 *
	 * @since 1.2.0
	 */
	public function load_addons_page() {

		wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', FALSE, ATUM_VERSION );
		wp_register_style( 'atum-addons', ATUM_URL . 'assets/css/atum-addons.css', FALSE, ATUM_VERSION );

		$min = (! ATUM_DEBUG) ? '.min' : '';
		wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', FALSE, ATUM_VERSION );
		Helpers::maybe_es6_promise();
		wp_register_script( 'atum-addons', ATUM_URL . "assets/js/atum.addons$min.js", array('jquery', 'sweetalert2'), ATUM_VERSION );

		wp_localize_script( 'atum-addons', 'atumAddons', array(
			'error'                => __( 'Error!', ATUM_TEXT_DOMAIN ),
			'success'              => __( 'Success!', ATUM_TEXT_DOMAIN ),
			'activated'            => __( 'Activated!', ATUM_TEXT_DOMAIN ),
			'activation'           => __( 'License Activation', ATUM_TEXT_DOMAIN ),
			'activate'             => __( 'Activate', ATUM_TEXT_DOMAIN ),
			'addonActivated'       => __( 'Your add-on license has been activated.', ATUM_TEXT_DOMAIN ),
			'limitedDeactivations' => __( 'Limited Deactivations!', ATUM_TEXT_DOMAIN ),
			'allowedDeactivations' => __( 'You are allowed to deactivate a license a max of 2 times.', ATUM_TEXT_DOMAIN ),
			'ok'                   => __( 'OK', ATUM_TEXT_DOMAIN ),
			'cancel'               => __( 'Cancel', ATUM_TEXT_DOMAIN ),
			'continue'             => __( 'Continue', ATUM_TEXT_DOMAIN ),
			'invalidKey'           => __( 'Please enter a valid add-on license key.', ATUM_TEXT_DOMAIN )
		) );

		wp_enqueue_style('sweetalert2');
		wp_enqueue_style('atum-addons');

		if ( wp_script_is('es6-promise', 'registered') ) {
			wp_enqueue_script( 'es6-promise' );
		}

		wp_enqueue_script('sweetalert2');
		wp_enqueue_script('atum-addons');

		$args = array(
			'addons' => $this->get_addons_list(),
			'addons_keys' => self::get_keys()
		);

		Helpers::load_view( 'addons', $args );

	}

	/**
	 * Check for updates for the installed ATUM addons
	 *
	 * @since 1.2.0
	 */
	public function check_addons_updates () {

		$license_keys = self::get_keys();

		if ( ! empty($license_keys) ) {
			foreach ( $license_keys as $addon_name => $license_key ) {

				if ( $license_key && $license_key['status'] == 'valid' ) {

					// All the ATUM addons' names should start with 'ATUM'
					$addon_info = Helpers::is_plugin_installed( 'ATUM ' . $addon_name, 'name', FALSE);

					if ($addon_info) {

						// Setup the updater
						$addon_file = key($addon_info);

						new Updater( $addon_file, array(
								'version'   => $addon_info[$addon_file]['Version'],
								'license'   => $license_key['key'],
								'item_name' => $addon_name,
								'beta'      => FALSE
							)
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
	private function get_addons_list () {

		$addons = Helpers::get_transient( 'addons_list' );

		if (!$addons) {

			$args = array(
				'method'      => 'POST',
				'timeout'     => 15,
				'redirection' => 1,
				'httpversion' => '1.0',
				'user-agent'  => 'ATUM/' . ATUM_VERSION . ';' . home_url(),
				'blocking'    => TRUE,
				'headers'     => array(),
				'body'        => array(),
				'cookies'     => array()
			);

			$response = wp_remote_post( self::ADDONS_STORE_URL . self::ADDONS_API_ENDPOINT, $args );

			// Admin notification about the error
			if ( is_wp_error( $response ) ) {

				$error_message = $response->get_error_message();

				if ( ATUM_DEBUG == TRUE ) {
					error_log( __METHOD__ . ": $error_message" );
				}

				$this->display_addons_page_notice( 'error', sprintf( __( "Something failed getting the ATUM's add-ons list: %s", ATUM_TEXT_DOMAIN ), $error_message ) );
				return FALSE;

			}

			$addons = @json_decode( wp_remote_retrieve_body( $response ), TRUE );

			if ( !$addons) {
				$this->display_addons_page_notice( 'error', __( "Something failed getting the ATUM's add-ons list", ATUM_TEXT_DOMAIN ) );
				return FALSE;
			}

			Helpers::set_transient('addons_list', $addons, DAY_IN_SECONDS);

		}

		return $addons;

	}

	/**
	 * Display a notice on the Addons page
	 *
	 * @since 1.2.0
	 *
	 * @param string $type      The notice type: error, success, warning or info
	 * @param string $message   The message within the notice
	 */
	public function display_addons_page_notice ($type, $message) {
		echo '<div class="notice notice-' . $type . ' atum-notice"><p>' . $message . '</p></div>';
	}

	/**
	 * Disable SSL verification in order to prevent download update failures
	 *
	 * @since 1.2.0
	 *
	 * @param array   $args
	 * @param string  $url
	 * @return object $array
	 */
	public function http_request_args( $args, $url ) {
		// If it is an https request and we are performing a package download, disable ssl verification
		if ( strpos( $url, 'https://' ) !== FALSE && strpos( $url, 'package_download' ) !== FALSE && strpos( $url, self::ADDONS_STORE_URL ) !== FALSE ) {
			$args['sslverify'] = FALSE;
		}

		return $args;
	}

	/**
	 * Get an addon key from database (if a valid name is passed as parameter) or all the registered addon keys (with no params)
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name    Optional. The addon name from which get the key
	 *
	 * @return string|array|bool
	 */
	public static function get_keys ($addon_name = '') {

		$keys = get_option(self::ADDONS_KEY_OPTION);

		if ($addon_name) {

			if (! empty($keys) && is_array($keys) && isset( $keys[$addon_name] ) ) {
				return $keys[$addon_name];
			}

			return '';

		}

		return $keys;

	}

	/**
	 * Generate a license API request
	 *
	 * @param string $addon_name    The addon name (must match to the ATUM store's addon name)
	 * @param string $key           The license key
	 * @param string $endpoint      The API endpoint
	 * @param array  $extra_params  Optional. Any other param that will be sent to the API
	 *
	 * @return array|\WP_Error
	 */
	private static function api_request ( $addon_name, $key, $endpoint, $extra_params = array() ) {

		$params = array_merge($extra_params, array(
			'edd_action' => $endpoint,
			'license'    => $key,
			'item_name'  => urlencode( $addon_name ),
			'url'        => home_url()
		));

		$request_params = array(
			'timeout'   => 15,
			'sslverify' => FALSE,
			'body'      => $params
		);

		// Call the license manager API
		return wp_remote_post( self::ADDONS_STORE_URL, $request_params );

	}

	/**
	 * Update the license key and its current status for the specified addon
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name    The addon name
	 * @param string $key           The license key
	 */
	public static function update_key ($addon_name, $key) {
		$keys = get_option(self::ADDONS_KEY_OPTION);
		$keys[ $addon_name ] = $key;
		update_option(self::ADDONS_KEY_OPTION, $keys);
	}

	/**
	 * Get the status data of an ATUM addon knowing its name
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name  The addon name (must match with the ATUM store's name)
	 * @param string $addon_slug  The addon slug (must match with the plugin directory name)
	 *
	 * @return array  The addon status info
	 */
	public static function get_addon_status($addon_name, $addon_slug) {

		// Status defaults
		$addon_status = array(
			'installed' => Helpers::is_plugin_installed($addon_slug),
			'status'    => 'invalid',
			'key'       => ''
		);

		// TODO: USE TRANSIENTS TO IMPROVE THE PAGE LOAD

		$saved_license = self::get_keys($addon_name);

		if ( ! empty( $saved_license ) ) {
			$addon_status['key'] = $saved_license['key'];

			// Check the license
			$status = self::check_license($addon_name, $addon_status['key']);

			if ( ! is_wp_error( $status ) ) {
				$license_data = json_decode( wp_remote_retrieve_body( $status ) );

				if ($license_data) {
					$addon_status['status'] = $license_data->license;
				}
			}

		}

		switch ( $addon_status['status'] ) {

			case 'invalid':
			case 'disabled':
			case 'expired':
			case 'item_name_mismatch':
				$addon_status['status'] = 'invalid';
				$addon_status['button_text'] = __('Validate', ATUM_TEXT_DOMAIN);
				$addon_status['button_class'] = 'validate-key';
				$addon_status['button_action'] = ATUM_PREFIX . 'validate_license';
				break;

			case 'inactive':
			case 'site_inactive':
				$addon_status['status'] = 'inactive';
				$addon_status['button_text'] = __('Activate', ATUM_TEXT_DOMAIN);
				$addon_status['button_class'] = 'activate-key';
				$addon_status['button_action'] = ATUM_PREFIX . 'activate_license';
				break;

			case 'valid':
				$addon_status['button_text'] = __('Deactivate', ATUM_TEXT_DOMAIN);
				$addon_status['button_class'] = 'deactivate-key';
				$addon_status['button_action'] = ATUM_PREFIX . 'deactivate_license';
				break;
		}

		return $addon_status;

	}

	/**
	 * Download an ATUM addon and install it
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name    The addon name
	 * @param $addon_slug           The addon slug
	 * @param $download_link        The link to download the addon zip file
	 *
	 * @return array    An array with the result and the message
	 */
	public static function install_addon ($addon_name, $addon_slug, $download_link) {

		// Ensure that the download link URL is pointing to the right place
		if (
			! filter_var($download_link, FILTER_VALIDATE_URL) ||
		    trailingslashit( parse_url($download_link, PHP_URL_SCHEME) . '://' .parse_url($download_link, PHP_URL_HOST) ) != self::ADDONS_STORE_URL
		) {

			return array(
				'success' => FALSE,
				'data'    => __( 'The download link is not valid', ATUM_TEXT_DOMAIN )
			);

		}

		// Start the addon download and installation
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		WP_Filesystem();

		$skin       = new \Automatic_Upgrader_Skin;
		$upgrader   = new \WP_Upgrader( $skin );
		$plugin     = $addon_slug . '/' . $addon_slug . '.php';
		$installed  = Helpers::is_plugin_installed($addon_slug);
		$activate   = ($installed) ? ! is_plugin_active( $plugin ) : FALSE;

		// Install this new addon
		if ( ! $installed ) {

			// Suppress feedback
			ob_start();

			try {

				$download = $upgrader->download_package( $download_link );

				if ( is_wp_error( $download ) ) {
					throw new AtumException( 'addon_download_error', $download->get_error_message() );
				}

				$working_dir = $upgrader->unpack_package( $download, TRUE );

				if ( is_wp_error( $working_dir ) ) {
					throw new AtumException( 'addon_unpack_error', $working_dir->get_error_message() );
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
					throw new AtumException( 'addon_not_installed', $result->get_error_message() );
				}

				$activate = TRUE;

			} catch ( AtumException $e ) {

				return array(
					'success' => FALSE,
					'data'    => sprintf(
						__( 'ATUM %s could not be installed (%s). Please download it from your account and install it manually.', ATUM_TEXT_DOMAIN ),
						$addon_name,
						$e->getMessage()
					)
				);

			}

			// Discard feedback
			ob_end_clean();

		}

		wp_clean_plugins_cache();

		// Activate this thing
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
						__( 'ATUM %s was installed but could not be activated. %sPlease activate it manually by clicking here.%s', ATUM_TEXT_DOMAIN ),
						$addon_name,
						'<a href="' . admin_url( 'plugins.php' ) . '">',
						'</a>'
					)
				);

			}

			// Installed and activated
			return array(
				'success' => TRUE,
				'data'    => sprintf( __( 'The ATUM %s addon was installed and activated successfully.', ATUM_TEXT_DOMAIN ), $addon_name )
			);

		}

		// Installed
		return array(
			'success' => TRUE,
			'data'    => sprintf( __( 'The ATUM %s addon was installed successfully.', ATUM_TEXT_DOMAIN ), $addon_name )
		);

	}

	/**
	 * Call to the license manager API to validate a license
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name    The addon name (must match to the ATUM store's addon name)
	 * @param string $key           The license key
	 *
	 * @return array|\WP_Error
	 */
	public static function check_license ($addon_name, $key) {
		return self::api_request($addon_name, $key, 'check_license');
	}

	/**
	 * Call to the license manager API to activate a license
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name    The addon name (must match to the ATUM store's addon name)
	 * @param string $key           The license key
	 *
	 * @return array|\WP_Error
	 */
	public static function activate_license($addon_name, $key) {
		return self::api_request($addon_name, $key, 'activate_license');
	}

	/**
	 * Call to the license manager API to deactivate a license
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name    The addon name (must match to the ATUM store's addon name)
	 * @param string $key           The license key
	 *
	 * @return array|\WP_Error
	 */
	public static function deactivate_license($addon_name, $key) {
		return self::api_request($addon_name, $key, 'deactivate_license');
	}

	/**
	 * Call to the license manager API to get an addon version info
	 *
	 * @since 1.2.0
	 *
	 * @param string $addon_name    The addon name (must match to the ATUM store's addon name)
	 * @param string $key           The license key
	 * @param string $version       The current addon version
	 * @param bool   $beta          Whether to look for beta versions
	 *
	 * @return array|\WP_Error
	 */
	public static function get_version($addon_name, $key, $version, $beta = FALSE) {
		return self::api_request($addon_name, $key, 'get_version', array('version' => $version, 'beta' => $beta));
	}

	/**
	 * Getter for the installed addons arrray
	 *
	 * @since 1.2.0
	 * @return array
	 */
	public static function get_installed_addons() {
		return self::$addons;
	}


	/****************************
	 * Instance methods
	 ****************************/
	public function __clone() {

		// cannot be cloned
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	public function __sleep() {

		// cannot be serialized
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Addons instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}