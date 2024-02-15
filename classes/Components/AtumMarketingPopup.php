<?php
/**
 * ATUM Marketing Popup
 *
 * @package        Atum
 * @subpackage     Components
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2024 Stock Management Labs™
 *
 * @since          1.5.3
 */

namespace Atum\Components;

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
	 * The marketing popup version
	 *
	 * @var object
	 */
	protected $version = [];

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
	protected $images = [];

	/**
	 * The footer notice
	 *
	 * @var object
	 */
	protected $footer_notice = [];

	/**
	 * The marketing popup background
	 *
	 * @var object
	 */
	protected $background = [];

	/**
	 * The marketing popup dash background
	 *
	 * @var string
	 */
	protected $dash_background = '';

	/**
	 * The hide popup transient key
	 *
	 * @var string
	 */
	protected static $transient_key = '';

	/**
	 * Was the marketing popup content loaded?
	 *
	 * @var bool
	 */
	protected $loaded = FALSE;

	/**
	 * Additional class to add to the marketing popup
	 *
	 * @var string
	 */
	protected $additional_class = '';

	/**
	 * Whether to disable the popup completely
	 */
	const IS_DISABLED = FALSE;

	/**
	 * The singleton instance holder
	 *
	 * @var AtumMarketingPopup
	 */
	private static $instance;


	/**
	 * Singleton constructor
	 *
	 * @since 1.5.3
	 */
	private function __construct() {

		// Call marketing popup info.
		$marketing_popup = $this->get_marketing_popup_content();

		if ( ! empty( $marketing_popup ) && ! empty( $marketing_popup->transient_key ) ) {

			// Check if background params exist.
			$background_data      = $marketing_popup->background ?? [];
			$dash_background_data = $marketing_popup->dash_background ?? [];

			if ( ! empty( $background_data ) ) {

				$background_color    = $background_data->bg_color ?? '';
				$background_image    = $background_data->bg_image ?? '';
				$background_position = $background_data->bg_position ?? '';
				$background_size     = $background_data->bg_size ?? '';
				$background_repeat   = $background_data->bg_repeat ?? '';

				$this->background = "$background_color $background_image $background_position/$background_size $background_repeat";

			}

			if ( ! empty( $dash_background_data ) ) {

				$background_color    = $dash_background_data->bg_color ?? '';
				$background_image    = $dash_background_data->bg_image ?? '';
				$background_position = $dash_background_data->bg_position ?? '';
				$background_size     = $dash_background_data->bg_size ?? '';
				$background_repeat   = $dash_background_data->bg_repeat ?? '';

				$this->dash_background = "$background_color $background_image $background_position/$background_size $background_repeat;";

			}

			// Add attributes to marketing popup.
			$this->additional_class = $marketing_popup->additional_class ?? '';
			$this->images           = $marketing_popup->images ?? [];
			$this->title            = $marketing_popup->title ?? '';
			$this->description      = $marketing_popup->description ?? [];
			$this->version          = $marketing_popup->version ?? [];
			$this->buttons          = $marketing_popup->buttons ?? [];
			$this->footer_notice    = $marketing_popup->footer_notice ?? [];
			self::$transient_key    = $marketing_popup->transient_key;

			$this->loaded = TRUE;

		}

	}

	/**
	 * Get marketing popup content
	 *
	 * @since 1.5.3
	 *
	 * @return array|\WP_Error
	 */
	private function get_marketing_popup_content() {

		// Until we find a solution for the API calls limit, we will use get the JSON locally.
		return json_decode( file_get_contents( ATUM_PATH . 'includes/marketing-popup-content.json' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	}

	/**
	 * Enqueue the Marketing popup scripts if needed
	 *
	 * @since 1.5.3.2
	 *
	 * @return bool
	 */
	public function maybe_enqueue_scripts() {

		if ( $this->show() ) {

			$marketing_popup_vars = array(
				'nonce' => wp_create_nonce( 'atum-marketing-popup-nonce' ),
			);

			wp_register_style( 'atum-marketing-popup', ATUM_URL . 'assets/css/atum-marketing-popup.css', array(), ATUM_VERSION );
			wp_register_script( 'atum-marketing-popup', ATUM_URL . 'assets/js/build/atum-marketing-popup.js', array( 'jquery', 'sweetalert2' ), ATUM_VERSION, TRUE );
			wp_localize_script( 'atum-marketing-popup', 'atumMarketingPopupVars', $marketing_popup_vars );

			wp_enqueue_style( 'atum-marketing-popup' );
			wp_enqueue_script( 'atum-marketing-popup' );

			return TRUE;

		}

		return FALSE;

	}

	/**
	 * Check if it shows the marketing widget at popup or dashboard.
	 *
	 * @since 1.7.6
	 *
	 * @param string $which
	 *
	 * @return bool
	 */
	public function show( $which = 'popup' ) {

		// Only show the popup to users that can install plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return FALSE;
		}

		if ( apply_filters( 'atum/marketing_popup/is_disabled', self::IS_DISABLED ) ) {
			return FALSE;
		}

		if ( FALSE === in_array( $which, array( 'popup', 'dash' ) ) ) {
			return FALSE;
		}

		$transient_key = AtumCache::get_transient( 'atum-marketing-' . $which, TRUE );

		if ( ! $transient_key || self::get_transient_key() !== $transient_key ) {

			if ( ! $this->is_loaded() ) {
				return FALSE;
			}

			$transient_key = self::get_transient_key();
			AtumCache::set_transient( 'atum-marketing-' . $which, $transient_key, WEEK_IN_SECONDS, TRUE );

		}

		// Get marketing popup user meta.
		$marketing_popup_user_meta = get_user_meta( get_current_user_id(), 'atum-marketing-' . $which, TRUE );

		if ( $marketing_popup_user_meta && $marketing_popup_user_meta === $transient_key ) {
			return FALSE;
		}

		return TRUE;

	}

	/**
	 * Getter for the additional class
	 *
	 * @since 1.9.35
	 *
	 * @return string
	 */
	public function get_additional_class() {
		return $this->additional_class;
	}

	/**
	 * Getter for the title
	 *
	 * @since 1.5.3
	 *
	 * @return object
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Getter for the text
	 *
	 * @since 1.5.3
	 *
	 * @return object
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Getter for the version
	 *
	 * @since 1.5.3
	 *
	 * @return object
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Getter for the buttons
	 *
	 * @since 1.5.3
	 *
	 * @return array
	 */
	public function get_buttons() {
		return $this->buttons;
	}

	/**
	 * Generate the css block for buttons
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	public function get_buttons_hover_style_block() {

		$css = '';

		$hovered_buttons = array_filter( $this->buttons, function ( $button ) {
			return ! empty( $button->hover );
		} );

		if ( $hovered_buttons ) {

			$css .= '<style>.marketing-popup ';
			foreach ( $hovered_buttons as $hovered_button ) {
				$css .= '.' . implode( '.', explode( ' ', $hovered_button->class ) ) . ':hover';
				$css .= "{ {$hovered_button->hover} }";
			}

			$css .= '</style>';

		}

		return $css;

	}

	/**
	 * Getter for the images
	 *
	 * @since 1.5.3
	 *
	 * @return array
	 */
	public function get_images() {
		return $this->images;
	}

	/**
	 * Get the dahboard image
	 *
	 * @since 1.9.1
	 *
	 * @return array
	 */
	public function get_dashboard_image() {
		return $this->images->dash_logo ?? $this->images->logo;
	}

	/**
	 * Getter for the footer notice
	 *
	 * @since 1.5.8.7
	 *
	 * @return string
	 */
	public function get_footer_notice() {
		return $this->footer_notice;
	}

	/**
	 * Getter for the background
	 *
	 * @since 1.5.3
	 *
	 * @return object
	 */
	public function get_background() {
		return $this->background;
	}

	/**
	 * Getter for the dash_background
	 *
	 * @since 1.5.3
	 *
	 * @return object
	 */
	public function get_dash_background() {
		return $this->dash_background;
	}

	/**
	 * Getter for the transient key
	 *
	 * @since 1.5.3
	 *
	 * @return string
	 */
	public static function get_transient_key() {
		return self::$transient_key;
	}

	/**
	 * Getter for the loaded prop
	 *
	 * @return bool
	 */
	public function is_loaded() {
		return $this->loaded;
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
