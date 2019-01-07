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
	 * The marketing popup image
	 *
	 * @var string
	 */
	protected $image = '';

	/**
	 * The marketing popup url
	 *
	 * @var string
	 */
	protected $url = '';

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
	const MARKETING_POPUP_STORE_URL = 'https://www.stockmanagementlabs.com/';

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
				$background_data = $marketing_popup->background;

				$this->background    = $background_data->background_color . ' ' . $background_data->background_image . ' ' . $background_data->background_position . '/100% 100% ' . $background_data->background_repeat;
				$this->image         = $marketing_popup->image;
				$this->title         = $marketing_popup->title;
				$this->description   = $marketing_popup->description;
				$this->url           = $marketing_popup->url;
				$this->transient_key = $marketing_popup->transient_key;
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
	 * Getter for the url
	 *
	 * @since 1.5.2
	 *
	 * @return string
	 */
	public function get_url() {

		return $this->url;
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
