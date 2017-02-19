<?php
/**
 * @package        Atum
 * @subpackage     Inc
 * @author         Salva Machí and Jose Piera - https://sispixels.com
 * @copyright      (c)2017 Stock Management Labs
 *
 * @since          0.0.1
 *
 * Ajax callbacks
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Addons\Addons;
use Atum\Settings\Settings;
use Atum\StockCentral\Inc\StockCentralList;


final class Ajax {
	
	/**
	 * The singleton instance holder
	 * @var Ajax
	 */
	private static $instance;
	
	private function __construct() {
		
		// Ajax callback for Stock Central List
		add_action( 'wp_ajax_atum_fetch_stock_central_list', array( $this, 'fetch_stock_central_list' ) );
		
		// Ajax callback for Management Stock notice
		add_action( 'wp_ajax_atum_manage_stock_notice', array( $this, 'manage_stock_notice' ) );
		
		// Welcome notice dismissal
		add_action( 'wp_ajax_atum_welcome_notice', array( $this, 'welcome_notice' ) );

		// Save the rate link click on the ATUM pages footer
		add_action( 'wp_ajax_atum_rated', array($this, 'rated') );

		// Manage addon licenses
		add_action( 'wp_ajax_atum_validate_license', array($this, 'validate_license') );
		add_action( 'wp_ajax_atum_activate_license', array($this, 'activate_license') );
		add_action( 'wp_ajax_atum_deactivate_license', array($this, 'deactivate_license') );
		add_action( 'wp_ajax_atum_install_addon', array($this, 'install_addon') );


	}
	
	/**
	 * Loads the Stock Central ListTable class and calls ajax_response method
	 *
	 * @since 0.0.1
	 */
	public function fetch_stock_central_list() {
		
		check_ajax_referer( 'atum-post-type-table-nonce', 'token' );
		
		$per_page = ( ! empty($_REQUEST['per_page']) ) ? absint( $_REQUEST['per_page'] ) : Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE );
		
		do_action( 'atum/ajax/stock_central_list/before_fetch_stock', $this );
		
		$list = new StockCentralList( compact('per_page') );
		$list->ajax_response();
		
	}
	
	/**
	 * Handle the ajax requests sent by the Atum's "Manage Stock" notice
	 *
	 * @since 0.1.0
	 */
	public function manage_stock_notice() {
		
		check_ajax_referer( ATUM_PREFIX . 'manage-stock-notice', 'token' );
		
		$action = ( ! empty($_POST['data']) ) ? $_POST['data'] : '';
		
		// Enable stock management
		if ($action == 'manage') {
			Helpers::update_option('manage_stock', 'yes');
		}
		// Dismiss the notice permanently
		elseif ( $action == 'dismiss') {
			Helpers::dismiss_notice('manage_stock');
		}
		
		wp_die();
		
	}
	
	/**
	 * Handle the ajax requests sent by the Atum's "Welcome" notice
	 *
	 * @since 1.1.1
	 */
	public function welcome_notice() {
		check_ajax_referer( 'dismiss-welcome-notice', 'token' );
		Helpers::dismiss_notice('welcome');
		wp_die();
	}

	/**
	 * Triggered when clicking the rating footer
	 *
	 * @since 1.2.0
	 */
	public function rated() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die(-1);
		}

		update_option( 'atum_admin_footer_text_rated', 1 );
		wp_die();
	}

	/**
	 * Validate an addon license key through API
	 *
	 * @since 1.2.0
	 */
	public function validate_license () {

		$this->check_license_post_data();

		$addon_name = esc_attr( $_POST['addon'] );
		$key = esc_attr( $_POST['key'] );

		if (!$addon_name || !$key) {
			wp_send_json_error( __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN ) );
		}

		$error_message = __('This license is not valid.', ATUM_TEXT_DOMAIN);

		// Validate the license through API
		$response = Addons::check_license($addon_name, $key);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( __('ATUM API error', ATUM_TEXT_DOMAIN) );
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		switch ( $license_data->license ) {

			case 'valid':

				// Save the valid license
				Addons::update_key( $addon_name, array(
					'key'    => $key,
					'status' => 'valid'
				) );

				wp_send_json_success( __('Your add-on license was saved.', ATUM_TEXT_DOMAIN) );
		        break;

			case 'inactive':
			case 'site_inactive':

				Addons::update_key( $addon_name, array(
					'key'    => $key,
					'status' => 'inactive'
				) );

				if ($license_data->activations_left < 1) {
					wp_send_json_error( __("You've reached your license activation limit for this add-on.<br>Please contact the Stock Management Labs support team.", ATUM_TEXT_DOMAIN) );
				}

				$licenses_after_activation = $license_data->activations_left - 1;

				wp_send_json( array(
					'success' => 'activate',
					'data' => sprintf(
						_n(
							'Your license is valid.<br>After the activation you will have %s remaining license.<br>Please, click the button to activate.',
							'Your license is valid.<br>After the activation you will have %s remaining licenses.<br>Please, click the button to activate.',
							$licenses_after_activation,
							ATUM_TEXT_DOMAIN
						),
						$licenses_after_activation
					)
				) );
				break;

			case 'expired':

				$error_message = sprintf(
					__( 'Your license key expired on %s.', ATUM_TEXT_DOMAIN ),
					date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
				);

				break;

			case 'disabled':

				$error_message = __('This license has been disabled', ATUM_TEXT_DOMAIN);
				break;

		}

		Addons::update_key( $addon_name, array(
			'key'    => $key,
			'status' => 'invalid'
		) );

		wp_send_json_error($error_message);

	}

	/**
	 * First check before validating|activating|deactivating an addon license
	 *
	 * @since 1.2.0
	 */
	private function check_license_post_data () {
		check_ajax_referer(ATUM_PREFIX . 'manage_license', 'token');

		if ( empty( $_POST['addon'] ) ) {
			wp_send_json_error( __('No addon name provided', ATUM_TEXT_DOMAIN) );
		}

		if ( empty( $_POST['key'] ) ) {
			wp_send_json_error( __('Please enter a valid addon license key', ATUM_TEXT_DOMAIN) );
		}
	}

	/**
	 * Activate an addon license key through API
	 *
	 * @since 1.2.0
	 */
	public function activate_license () {

		$this->check_license_post_data();

		$addon_name = esc_attr( $_POST['addon'] );
		$key = esc_attr( $_POST['key'] );
		$default_error = __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN );

		if (!$addon_name || !$key) {
			wp_send_json_error($default_error);
		}

		$response = Addons::activate_license($addon_name, $key);

		// Make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			$message = ( is_wp_error( $response ) ) ? $response->get_error_message() : $default_error;

		}
		else {

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {

				switch( $license_data->error ) {

					case 'expired':

						$message = sprintf(
							__( 'Your license key expired on %s.', ATUM_TEXT_DOMAIN ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;

					case 'revoked':

						$message = __( 'Your license key has been disabled.', ATUM_TEXT_DOMAIN );
						break;

					case 'missing':

						$message = __( 'Invalid license.', ATUM_TEXT_DOMAIN );
						break;

					case 'invalid':
					case 'site_inactive':

						$message = __( 'Your license is not active for this URL.', ATUM_TEXT_DOMAIN );
						break;

					case 'item_name_mismatch':

						$message = sprintf( __( 'This appears to be an invalid license key for %s.', ATUM_TEXT_DOMAIN ), $addon_name );
						break;

					case 'no_activations_left':

						$message = __( 'Your license key has reached its activation limit.', ATUM_TEXT_DOMAIN );
						break;

					default :

						$message = $default_error;
						break;
				}

			}

		}

		if ( ! empty( $message ) ) {
			wp_send_json_error($message);
		}

		// Update the key in database
		Addons::update_key( $addon_name, array(
			'key'    => $key,
			'status' => $license_data->license
		) );

		if ($license_data->license == 'valid') {
			wp_send_json_success( __('Your license has been activated.', ATUM_TEXT_DOMAIN) );
		}

		wp_send_json_error($default_error);

	}

	/**
	 * Deactivate an addon license key through API
	 *
	 * @since 1.2.0
	 */
	public function deactivate_license () {

		$this->check_license_post_data();

		$addon_name = esc_attr( $_POST['addon'] );
		$key = esc_attr( $_POST['key'] );
		$default_error = __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN );

		if (!$addon_name || !$key) {
			wp_send_json_error($default_error);
		}

		$response = Addons::deactivate_license($addon_name, $key);

		// Make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = ( is_wp_error( $response ) ) ? $response->get_error_message() : $default_error;
			wp_send_json_error($message);
		}

		// Decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed" or "limit_reached"
		if( $license_data->license == 'deactivated' ) {

			// Update the key status
			Addons::update_key( $addon_name, array(
				'key'    => $key,
				'status' => 'inactive'
			) );

			wp_send_json_success( __('Your license has been deactivated.', ATUM_TEXT_DOMAIN) );

		}
		elseif ( $license_data->license == 'limit_reached' ) {

			wp_send_json_error( sprintf(
				__("You've reached the limit of allowed deactivations for this license. Please %sopen a support ticket%s to request the deactivation.", ATUM_TEXT_DOMAIN),
				'<a href="https://stockmanagementlabs.ticksy.com/" target="_blank">',
				'</a>'
			) );

		}

		wp_send_json_error($default_error);

	}

	/**
	 * Install an addon from the addons page
	 *
	 * @since 1.2.0
	 */
	public function install_addon () {

		$this->check_license_post_data();

		$addon_name = esc_attr( $_POST['addon'] );
		$addon_slug = esc_attr( $_POST['slug'] );
		$key = esc_attr( $_POST['key'] );
		$default_error =  __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN );

		if (!$addon_name || !$addon_slug || !$key) {
			wp_send_json_error($default_error);
		}

		$response = Addons::get_version($addon_name, $key, '0.0');

		// Make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = ( is_wp_error( $response ) ) ? $response->get_error_message() : $default_error;
			wp_send_json_error($message);
		}

		// Decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ($license_data->download_link) {
			$result = Addons::install_addon($addon_name, $addon_slug, $license_data->download_link);
			wp_send_json($result);
		}

		wp_send_json_error($default_error);

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
	 * @return Ajax instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}