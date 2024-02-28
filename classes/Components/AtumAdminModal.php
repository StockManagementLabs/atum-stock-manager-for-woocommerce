<?php
/**
 * ATUM Admin Modals that are shown in all admin pages
 *
 * @package        Atum
 * @subpackage     Components
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2024 Stock Management Labs™
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
	 * Pages where the modal will be excluded
	 *
	 * @var string[]
	 */
	protected $exclusions = [];

	/**
	 * Any extra JS dependencies required for the modal
	 *
	 * @var string[]
	 */
	protected $js_dependencies = [];


	/**
	 * AtumAdminModals singleton constructor
	 *
	 * @since 1.9.27
	 *
	 * @param string[] $exclusions
	 */
	private function __construct( $exclusions = [] ) {

		$this->default_swal_config = array(
			'confirmButtonText' => __( 'OK', ATUM_TEXT_DOMAIN ),
			'cancelButtonText'  => __( 'Cancel', ATUM_TEXT_DOMAIN ),
		);

		$this->exclusions = $exclusions;

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

		if (
			isset( $this->swal_configs[ $key ] ) || ! is_admin() || wp_doing_ajax() ||
			Helpers::is_rest_request() || ! current_user_can( 'administrator' )
		) {
			return;
		}

		// Check if the modal was already closed by the user.
		$closed_transient_key = AtumCache::get_transient_key( 'closed_admin_modal', $key );

		if ( AtumCache::get_transient( $closed_transient_key, TRUE ) ) {
			return;
		}

		$this->swal_configs[ $key ] = array_merge( $this->default_swal_config, $swal_config );

		if ( $template ) {
			$this->swal_configs[ $key ]['html'] = $template;
		}

		if ( ! has_action( 'wp_loaded', array( $this, 'show' ) ) ) {
			add_action( 'wp_loaded', array( $this, 'show' ), PHP_INT_MAX );
		}

	}

	/**
	 * Enqueue the modal scripts if needed
	 *
	 * @since 1.9.27
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		$admin_modal_vars = [
			'swal_configs' => $this->swal_configs,
			'nonce'        => wp_create_nonce( 'atum-admin-modals-nonce' ),
		];

		if ( ! empty( $this->exclusions ) ) {
			foreach ( $this->exclusions as $exclusion ) {

				if ( str_contains( $hook, $exclusion ) ) {
					return;
				}

			}
		}

		Helpers::register_swal_scripts();

		wp_register_style( 'atum-admin-modals', ATUM_URL . 'assets/css/atum-admin-modals.css', [ 'sweetalert2' ], ATUM_VERSION );

		$js_deps = array_merge( [ 'jquery', 'sweetalert2' ], $this->js_dependencies );

		wp_register_script( 'atum-admin-modals', ATUM_URL . 'assets/js/build/atum-admin-modals.js', $js_deps, ATUM_VERSION, TRUE );
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

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 11 );

		return TRUE;

	}

	/**
	 * Add a transient to hide an admin modal temporarily
	 *
	 * @since 1.9.27
	 *
	 * @param string $key
	 */
	public static function hide_modal( $key ) {

		$transient_key = AtumCache::get_transient_key( 'closed_admin_modal', $key );
		AtumCache::set_transient( $transient_key, 1, DAY_IN_SECONDS, TRUE );

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

	/**
	 * Setter for the modal's JS dependencies
	 *
	 * @since 1.9.27.1
	 *
	 * @param string[] $deps
	 */
	public function set_js_dependencies( array $deps ) {
		$this->js_dependencies = $deps;
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
	 * @param string[] $exclusions
	 *
	 * @return AtumAdminModal
	 */
	public static function get_instance( $exclusions = [] ) {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self( $exclusions );
		}

		return self::$instance;

	}

}
