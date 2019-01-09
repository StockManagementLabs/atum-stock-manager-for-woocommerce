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
	 * @var object
	 */
	protected $title = [];

	/**
	 * The marketing popup description
	 *
	 * @var object
	 */
	protected $description = [];

	/**
	 * The marketing popup buttons
	 *
	 * @var array
	 */
	protected $buttons = [];

	/**
	 * The marketing popup images
	 *
	 * @var object
	 */
	protected $images = '';

	/**
	 * The marketing popup background
	 *
	 * @var object
	 */
	protected $background = [];

	/**
	 * The hide popup transient key
	 *
	 * @var string
	 */
	protected $transient_key = '';

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

		// Call marketing popup info.
		$marketing_popup = $this->get_marketing_popup_content();

		if ( ! is_wp_error( $marketing_popup ) ) {

			$marketing_popup = json_decode( wp_remote_retrieve_body( $marketing_popup ) );

			if ( $marketing_popup ) {

				// Check if background params exist.
				$background_data = isset( $marketing_popup->background ) ? $marketing_popup->background : [];

				if ( ! empty( $background_data ) ) {

					$background_color    = isset( $background_data->background_color ) ? $background_data->background_color : '';
					$background_image    = isset( $background_data->background_image ) ? $background_data->background_image : '';
					$background_position = isset( $background_data->background_position ) ? $background_data->background_position : '';
					$background_size     = isset( $background_data->background_size ) ? $background_data->background_size : '';
					$background_repeat   = isset( $background_data->background_repeat ) ? $background_data->background_repeat : '';

					$this->background = $background_color . ' ' . $background_image . ' ' . $background_position . '/' . $background_size . ' ' . $background_repeat;

				}

				// Add attributes to marketing popup.
				$this->images        = isset( $marketing_popup->images ) ? $marketing_popup->images : [];
				$this->title         = isset( $marketing_popup->title ) ? $marketing_popup->title : '';
				$this->description   = isset( $marketing_popup->description ) ? $marketing_popup->description : [];
				$this->buttons       = isset( $marketing_popup->buttons ) ? $marketing_popup->buttons : [];
				$this->transient_key = isset( $marketing_popup->transient_key ) ? $marketing_popup->transient_key : '';

			}

		}

	}

	/**
	 * Get marketing popup content
	 *
	 * @return array|\WP_Error
	 */
	private static function get_marketing_popup_content() {

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

		// Call marketing popup info.
		return wp_remote_post( self::MARKETING_POPUP_STORE_URL . self::MARKETING_POPUP_API_ENDPOINT, $request_params );

	}

	/**
	 * Getter for the title
	 *
	 * @since 1.5.2
	 *
	 * @return object
	 */
	public function get_title() {

		return $this->title;
	}

	/**
	 * Getter for the text
	 *
	 * @since 1.5.2
	 *
	 * @return object
	 */
	public function get_description() {

		return $this->description;
	}

	/**
	 * Getter for the buttons
	 *
	 * @since 1.5.2
	 *
	 * @return object
	 */
	public function get_buttons() {

		return $this->buttons;
	}

	/**
	 * Getter for the images
	 *
	 * @since 1.5.2
	 *
	 * @return string
	 */
	public function get_images() {

		return $this->images;
	}

	/**
	 * Getter for the background
	 *
	 * @since 1.5.2
	 *
	 * @return object
	 */
	public function get_background() {

		return $this->background;

	}

	/**
	 * Getter for the transient key
	 *
	 * @since 1.5.2
	 *
	 * @return string
	 */
	public function get_transient_key() {

		return $this->transient_key;

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
