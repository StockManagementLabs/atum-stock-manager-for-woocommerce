<?php
/**
 * Add Marketing Popup
 *
 * @package        Atum
 * @subpackage     Components
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2018 Stock Management Labs™
 *
 * @since          1.5.2
 */

namespace Atum\Components;

use Atum\Inc\Helpers;


defined( 'ABSPATH' ) || die;


class AtumMarketingPopup {

	/**
	 * The marketing popup title
	 *
	 * @var string
	 */
	protected $text = '';

	/**
	 * The marketing popup title
	 *
	 * @var string
	 */
	protected $image = '';

	/**
	 * The marketing popup confirm button text
	 *
	 * @var string
	 */
	protected $confirm_button_text = '';

	/**
	 * The marketing popup confirm button color
	 *
	 * @var string
	 */
	protected $confirm_button_color = '';



	/**
	 * The marketing popup cancel button text
	 *
	 * @var string
	 */
	protected $cancel_button_text = '';

	/**
	 * The marketing popup cancel button color
	 *
	 * @var string
	 */
	protected $cancel_button_color = '';

	/**
	 * The marketing popup background
	 *
	 * @var string
	 */
	protected $background;

	/**
	 * The marketing popup plugin name
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * The ATUM's addons store URL
	 */
	const MARKETING_POPUP_STORE_URL = 'http://stockmanagementlabs.loc/';

	/**
	 * The ATUM's addons API endpoint
	 */
	const MARKETING_POPUP_API_ENDPOINT = 'marketing-popup-api';

	/**
	 * The singleton instance holder
	 *
	 * @var AtumMarketingPopup
	 */
	private static $instance;

	/**
	 * Singleton constructor
	 *
	 * @since 1.5.2
	 */
	public function __construct() {

		$request_params = array(
			'method'      => 'POST',
			'timeout'     => 15,
			'redirection' => 1,
			'httpversion' => '1.0',
			'user-agent'  => 'ATUM/' . ATUM_VERSION . ';' . home_url(),
			'blocking'    => TRUE,
			'headers'     => array(),
			'body'        => array(),
			'cookies'     => array(),
		);

		// Call marketing poup info.
		$info = wp_remote_post( self::MARKETING_POPUP_STORE_URL . self::MARKETING_POPUP_API_ENDPOINT, $request_params );

		if ( ! is_wp_error( $info ) ) {
			$info = json_decode( wp_remote_retrieve_body( $info ) );

			if ( $info ) {
				$this->background           = $info->background_color . ' ' . $info->background_image . ' ' . $info->background_position . '/' . $info->background_size . ' ' . $info->background_repeat;
				$this->image                = $info->image;
				$this->text                 = $info->text;
				$this->confirm_button_text  = $info->confirm_button_text;
				$this->confirm_button_color = $info->confirm_button_color;
				$this->cancel_button_text   = $info->cancel_button_text;
				$this->cancel_button_color  = $info->cancel_button_color;
				$this->plugin_name          = $info->plugin_name;

				// Enqueue dashboard scripts.
				//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			}
		}
	}

//	/**
//	 * Enqueue the required scripts
//	 *
//	 * @since 1.5.2
//	 *
//	 * @param string $hook
//	 */
//	public function enqueue_scripts( $hook ) {
//
//		$min                  = ! ATUM_DEBUG ? '.min' : '';
//		$marketing_popup_vars = array(
//			'nonce' => wp_create_nonce( 'atum-marketing-popup-nonce' ),
//		);
//
//		// Sweet Alert 2.
//		wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', array(), ATUM_VERSION );
//		wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', array(), ATUM_VERSION, TRUE );
//
//		wp_register_style( 'atum-marketing-popup', ATUM_URL . "assets/css/atum-marketing-popup{$min}.css", array( 'sweetalert2' ), ATUM_VERSION );
//
//		wp_register_script( 'atum-marketing-popup', ATUM_URL . "assets/js/atum.marketing.popup{$min}.js", array( 'sweetalert2' ), ATUM_VERSION, TRUE );
//		wp_localize_script( 'atum-marketing-popup', 'atumMarketingPopupVars', $marketing_popup_vars );
//
//		wp_enqueue_style( 'atum-marketing-popup' );
//		wp_enqueue_script( 'atum-marketing-popup' );
//
//	}

	/**
	 * Getter for the text
	 *
	 * @since 1.5.2
	 *
	 * @return string
	 */
	public function get_text() {

		return $this->text;
	}

	/**
	 * Getter for the image
	 *
	 * @since 1.5.2
	 *
	 * @return string
	 */
	public function get_image() {

		return $this->image;
	}

	/**
	 * Getter for the confirm button text
	 *
	 * @since 1.5.2
	 *
	 * @return string
	 */
	public function get_confirm_button_text() {

		return $this->confirm_button_text;
	}

	/**
	 * Getter for the confirm button color
	 *
	 * @since 1.5.2
	 *
	 * @return string
	 */
	public function get_confirm_button_color() {

		return $this->confirm_button_color;
	}

	/**
	 * Getter for the cancel button text
	 *
	 * @since 1.5.2
	 *
	 * @return string
	 */
	public function get_cancel_button_text() {

		return $this->cancel_button_text;
	}

	/**
	 * Getter for the cancel button color
	 *
	 * @since 1.5.2
	 *
	 * @return string
	 */
	public function get_cancel_button_color() {

		return $this->cancel_button_color;
	}

	/**
	 * Getter for the background
	 *
	 * @since 1.5.2
	 *
	 * @return string
	 */
	public function get_background() {

		return $this->background;

	}

	/**
	 * Getter for the plugin name
	 *
	 * @since 1.5.2
	 *
	 * @return string
	 */
	public function get_plugin_name() {

		return $this->plugin_name;

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
	 * @return AtumMarketingPopup instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
