<?php
/**
 * Class AtumHelpGuide
 *
 * @since       1.9.10
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 Stock Management Labs™
 *
 * @package     Atum\Components
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

class AtumHelpGuide {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumHelpGuide
	 */
	private static $instance;

	/**
	 * Path where the JSON file guides are located
	 *
	 * @var string[]
	 */
	protected $guides_paths = [];

	/**
	 * User meta key name where the closed auto guides will be saved
	 */
	const CLOSED_AUTO_GUIDES_KEY = '_atum_closed_auto_guides';

	/**
	 * AtumHelpGuide singleton constructor
	 */
	private function __construct() {

		$this->guides_paths = apply_filters( 'atum/help_guides/guides_paths', array(
			//'atum_stock_central' => ATUM_PATH . 'help-guides/stock-central', // NOTE: DISABLED UNTIL COMPLETE.
		) );

	}

	/**
	 * Extract the help guide steps from the JSON file
	 *
	 * @since 1.9.10
	 *
	 * @param string $guide_file
	 *
	 * @return array
	 */
	public function get_guide_steps( $guide_file ) {

		$guide_file .= '.json';

		if ( file_exists( $guide_file ) ) {
			$guide_steps = json_decode( file_get_contents( $guide_file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			if ( ! empty( $guide_steps ) && is_array( $guide_steps ) ) {
				return $guide_steps;
			}
		}

		return [];

	}

	/**
	 * Add a show guide button for any specific guide, anywhere
	 *
	 * @since 1.9.11
	 *
	 * @param string $guide
	 */
	public function show_help_guide_buttons( $guide ) {

		if ( ! $guide || ! array_key_exists( $guide, $this->guides_paths ) ) {
			return;
		}

		?>
		<span class="spacer"></span>
		<span class="help-guide-buttons" data-guide="<?php echo esc_attr( $this->guides_paths[ $guide ] ) ?>">
			<i class="show-help-markers atum-icon atmi-flag atum-tooltip"
			   title="<?php esc_attr_e( 'Display ATUM help guide markers', ATUM_TEXT_DOMAIN ) ?>">
			</i>

			<i class="show-intro-guide atum-icon atmi-indent-increase atum-tooltip"
			   title="<?php esc_attr_e( 'Show ATUM help guide', ATUM_TEXT_DOMAIN ) ?>">
			</i>
		</span>
		<?php

	}

	/**
	 * Get the closed auto guides for the specified user
	 *
	 * @since 1.9.11
	 *
	 * @param int $user_id
	 *
	 * @return string[]|false
	 */
	public static function get_closed_auto_guides( $user_id ) {
		return get_user_meta( $user_id, self::CLOSED_AUTO_GUIDES_KEY, TRUE );
	}

	/**
	 * Save a closed auto to the specified user meta
	 *
	 * @since 1.9.11
	 *
	 * @param int $user_id
	 * @param int $guide
	 */
	public static function save_closed_auto_guide( $user_id, $guide ) {

		$closed_auto_guides = self::get_closed_auto_guides( $user_id );

		if ( is_array( $closed_auto_guides ) ) {

			if ( ! in_array( $guide, $closed_auto_guides ) ) {
				$closed_auto_guides[] = $guide;
			}

		}
		else {
			$closed_auto_guides = [ $guide ];
		}

		update_user_meta( $user_id, self::CLOSED_AUTO_GUIDES_KEY, $closed_auto_guides );

	}

	/**
	 * Prepare the JS vars commonly used for the help guides
	 *
	 * @since 1.9.11
	 *
	 * @param string $auto_guide Optional. Key for the auto-guide (if any).
	 * @param string $main_guide Optional. Key for the main guide (used for help markers and the show intro buttons).
	 *
	 * @return array
	 */
	public function get_help_guide_js_vars( $auto_guide = '', $main_guide = '' ) {

		$vars = array(
			'hgGotIt'              => __( 'Got it!', ATUM_TEXT_DOMAIN ),
			'hgGuideButtonsTitle'  => __( 'Help guide buttons', ATUM_TEXT_DOMAIN ),
			'hgGuideButtonsNotice' => __( 'You can access this help guide at any time from here.', ATUM_TEXT_DOMAIN ),
			'hgIntroJsOptions'     => array(
				'nextLabel'          => __( 'Next', ATUM_TEXT_DOMAIN ),
				'prevLabel'          => __( 'Prev', ATUM_TEXT_DOMAIN ),
				'doneLabel'          => __( 'Done', ATUM_TEXT_DOMAIN ),
				'tooltipClass'       => 'atum-help-guide-tooltip',
				'disableInteraction' => TRUE,
				'scrollToElement'    => TRUE,
			),
			'hgNonce'              => wp_create_nonce( 'help-guide-nonce' ),
			'hgShowHelpGuide'      => __( 'Show help guide', ATUM_TEXT_DOMAIN ),
			'hgShowHelpMarkers'    => __( 'Display ATUM help guide markers', ATUM_TEXT_DOMAIN ),
		);

		// Add the auto help guide if passed and the user has not closed it yet.
		if ( $auto_guide && array_key_exists( $auto_guide, $this->guides_paths ) && file_exists( $this->guides_paths[ $auto_guide ] . '.json' ) ) {

			$closed_auto_guides = self::get_closed_auto_guides( get_current_user_id() );

			if ( ! is_array( $closed_auto_guides ) || ! in_array( $auto_guide, $closed_auto_guides ) ) {
				$vars['hgAutoGuide'] = json_decode( file_get_contents( $this->guides_paths[ $auto_guide ] . '.json' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

				// Disable the marketing popup while an auto-guide is running.
				add_filter( 'atum/marketing_popup/is_disabled', '__return_true' );
			}

		}

		// Add the help markers and main guide vars (if requested).
		if ( $main_guide && array_key_exists( $main_guide, $this->guides_paths ) && file_exists( $this->guides_paths[ $main_guide ] . '.json' ) ) {
			$vars['hgMainGuide'] = $main_guide;
		}

		return $vars;

	}

	/**
	 * Getter for the guides_paths prop
	 *
	 * @since 1.9.30
	 *
	 * @return string[]
	 */
	public function get_guides_paths() {
		return $this->guides_paths;
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
	 * @return AtumHelpGuide instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
