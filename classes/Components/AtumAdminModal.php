<?php
/**
 * ATUM Admin Modals that are shown in all admin pages
 *
 * @package        Atum
 * @subpackage     Components
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2023 Stock Management Labs™
 *
 * @since          1.9.27
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

class AtumAdminModal {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumAdminModal
	 */
	private static $instance;

	/**
	 * The modal identifier key
	 *
	 * @var string
	 */
	private $key = '';

	/**
	 * Default swal configuration
	 *
	 * @var array
	 */
	private $default_swal_config;

	/**
	 * SweetAlert configurations
	 *
	 * @var array
	 */
	protected $swal_configs = [];


	/**
	 * AtumAdminModals singleton constructor
	 *
	 * @since 1.9.27
	 */
	private function __construct() {

		$this->default_swal_config = array(
			'confirmButtonText' => __( 'OK', ATUM_TEXT_DOMAIN ),
			'cancelButtonText'  => __( 'Cancel', ATUM_TEXT_DOMAIN ),
		);

	}

	/**
	 * Add a new modal to the queue
	 *
	 * @since 1.9.27
	 *
	 * @param string $key         The modal identifier key.
	 * @param array  $swal_config The config must be compatible with SweetAlert2 (https://sweetalert2.github.io/#configuration).
	 * @param string $template    Optional. Add any template for the modal content if necessary.
	 */
	public function add_modal( $key, $swal_config, $template = NULL ) {

		if ( isset( $this->swal_configs[ $key ] ) ) {
			return;
		}

		$this->swal_configs[ $key ] = array_merge( $this->default_swal_config, $swal_config );

		if ( $template ) {
			$this->swal_configs[ $key ]['html'] = $template;
		}

		if ( is_admin() && ! has_action( 'wp_loaded', array( $this, 'show' ) ) ) {
			add_action( 'wp_loaded', array( $this, 'show' ), PHP_INT_MAX );
		}

	}

	/**
	 * Enqueue the modal scripts if needed
	 *
	 * @since 1.9.27
	 */
	public function enqueue_scripts() {

		$admin_modal_vars = [
			'swal_configs' => $this->swal_configs,
			'nonce'        => wp_create_nonce( 'atum-admin-modals-nonce' ),
		];

		wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', array(), ATUM_VERSION );
		wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', array(), ATUM_VERSION, TRUE );
		Helpers::maybe_es6_promise();

		wp_register_style( 'atum-admin-modals', ATUM_URL . 'assets/css/atum-admin-modals.css', array( 'sweetalert2' ), ATUM_VERSION );
		wp_register_script( 'atum-admin-modals', ATUM_URL . 'assets/js/build/atum-admin-modals.js', array( 'jquery', 'sweetalert2' ), ATUM_VERSION, TRUE );
		wp_localize_script( 'atum-admin-modals', 'atumAdminModalVars', $admin_modal_vars );

		wp_enqueue_style( 'atum-admin-modals' );
		wp_enqueue_script( 'atum-admin-modals' );

	}

	/**
	 * Check if it shows the marketing widget at popup or dashboard.
	 *
	 * @since 1.9.27
	 *
	 * @return bool
	 */
	public function show() {

		// Only show the popup to users that can install plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return FALSE;
		}

		if ( empty( $this->swal_configs ) ) {
			return FALSE;
		}

		$transient_key = 'atum-admin-modal-' . $this->key;
		$key           = AtumCache::get_transient( $transient_key, TRUE );

		if ( ! $key || $this->key !== $key ) {
			$key = $this->key;
			AtumCache::set_transient( $transient_key, $key, DAY_IN_SECONDS, TRUE );
		}

		// Get modal user meta.
		$admin_modal_user_meta = get_user_meta( get_current_user_id(), $transient_key, TRUE );

		if ( $admin_modal_user_meta && $admin_modal_user_meta === $key ) {
			return FALSE;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		return TRUE;

	}

	/**
	 * Getter for the modal key
	 *
	 * @since 1.9.27
	 *
	 * @return string
	 */
	public function get_key() {
		return $this->key;
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
	 * @return AtumAdminModal
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

}
