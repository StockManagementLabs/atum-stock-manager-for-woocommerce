<?php
/**
 * ATUM Admin Modals
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


class AtumAdminModal {

	/**
	 * The modal identifier key
	 *
	 * @var string
	 */
	protected static $key = '';

	/**
	 * SweetAlert configuration
	 *
	 * @var array
	 */
	protected $swal_config = [];


	/**
	 * AtumAdminModals constructor
	 *
	 * @since 1.9.27
	 *
	 * @param string $key         The modal identifier key.
	 * @param array  $swal_config The config must be compatible with SweetAlert2 (https://sweetalert2.github.io/#configuration).
	 */
	public function __construct( $key, $swal_config ) {

		self::$key = $key;

		if ( ! empty( $swal_config ) ) {

			$default_config = array(
				'icon'               => 'info',
				'confirmButtonText'  => __( 'OK', ATUM_TEXT_DOMAIN ),
				'cancelButtonText'   => __( 'Cancel', ATUM_TEXT_DOMAIN ),
				'confirmButtonColor' => '#00B8DB',
				'focusConfirm'       => FALSE,
				'showCloseButton'    => TRUE,
			);

			$this->swal_config = array_merge( $default_config, $swal_config );

		}

	}

	/**
	 * Enqueue the modal scripts if needed
	 *
	 * @since 1.9.27
	 */
	private function enqueue_scripts() {

		$admin_modal_vars = [
			'swal_config' => $this->swal_config,
			'nonce'       => wp_create_nonce( 'atum-admin-modals-nonce' ),
		];

		wp_register_style( 'atum-admin-modals', ATUM_URL . 'assets/css/atum-admin-modals.css', array(), ATUM_VERSION );
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

		if ( ! self::$key || empty( $this->swal_config ) ) {
			return FALSE;
		}

		$transient_key = 'atum-admin-modal-' . self::$key;
		$key           = AtumCache::get_transient( $transient_key, TRUE );

		if ( ! $key || self::get_key() !== $key ) {

			$key = self::$key;
			AtumCache::set_transient( $transient_key, $key, DAY_IN_SECONDS, TRUE );

		}

		// Get modal user meta.
		$admin_modal_user_meta = get_user_meta( get_current_user_id(), $transient_key, TRUE );

		if ( $admin_modal_user_meta && $admin_modal_user_meta === $key ) {
			return FALSE;
		}

		$this->enqueue_scripts();

		return TRUE;

	}

	/**
	 * Getter for the modal key
	 *
	 * @since 1.9.27
	 *
	 * @return string
	 */
	public static function get_key() {
		return self::$key;
	}

}
