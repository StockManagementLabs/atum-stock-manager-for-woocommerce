<?php
/**
 * Class AtumHelpGuide
 *
 * @since       1.9.10
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @package     Atum\Components
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

class AtumHelpGuide {

	/**
	 * Path where the JSON file guides are located
	 *
	 * @var string
	 */
	protected $guides_path;

	/**
	 * User meta key name where the closed auto guides will be saved
	 */
	const CLOSED_AUTO_GUIDES_KEY = '_atum_closed_auto_guides';

	/**
	 * AtumHelpGuide constructor.
	 *
	 * @param string $guides_path
	 */
	public function __construct( $guides_path ) {

		$this->guides_path = $guides_path;

	}

	/**
	 * Extract the help guide steps from the JSON file
	 *
	 * @since 1.9.10
	 *
	 * @param string $guide_name
	 *
	 * @return array
	 */
	public function get_guide_steps( $guide_name ) {

		$guide_file = trailingslashit( $this->guides_path ) . $guide_name . '.json';

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
	 *
	 * @return string
	 */
	public function get_help_guide_button( $guide ) {

		$default_path = ATUM_PATH . 'help-guides';
		$path_data    = $this->guides_path && $this->guides_path !== $default_path ? ' data-path="' . $this->guides_path . '"' : '';

		return '<i class="atum-icon atmi-indent-increase show-intro-guide atum-tooltip" 
			data-guide="' . $guide . '"' . $path_data . ' 
			title="' . __( 'Show help guide', ATUM_TEXT_DOMAIN ) . '"></i>';

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
	 * @param int $screen_id
	 */
	public static function save_closed_auto_guide( $user_id, $screen_id ) {

		$closed_auto_guides = self::get_closed_auto_guides( $user_id );

		if ( is_array( $closed_auto_guides ) ) {

			if ( ! in_array( $screen_id, $closed_auto_guides ) ) {
				$closed_auto_guides[] = $screen_id;
			}

		}
		else {
			$closed_auto_guides = [ $screen_id ];
		}

		update_user_meta( $user_id, self::CLOSED_AUTO_GUIDES_KEY, $closed_auto_guides );

	}

	/**
	 * Prepare the JS vars commonly used for the help guides
	 *
	 * @since 1.9.11
	 *
	 * @param string $auto_guide_file Full guide path for the auto guide (if any).
	 *
	 * @return array
	 */
	public static function get_help_guide_js_vars( $auto_guide_file = '' ) {

		$screen = get_current_screen();
		$vars   = array(
			'helpGuideNonce' => wp_create_nonce( 'help-guide-nonce' ),
			'introJsOptions' => array(
				'nextLabel'          => __( 'Next', ATUM_TEXT_DOMAIN ),
				'prevLabel'          => __( 'Prev', ATUM_TEXT_DOMAIN ),
				'doneLabel'          => __( 'Done', ATUM_TEXT_DOMAIN ),
				'tooltipClass'       => 'atum-help-guide-tooltip',
				'disableInteraction' => TRUE,
				'scrollToElement'    => TRUE,
			),
			'showHelpGuide'  => __( 'Show help guide', ATUM_TEXT_DOMAIN ),
			'screenId'       => $screen ? $screen->id : '',
		);

		// Add the auto help guide if passed and the user has not closed it yet.
		if ( $auto_guide_file && file_exists( $auto_guide_file ) ) {

			$closed_auto_guides = self::get_closed_auto_guides( get_current_user_id() );

			if ( ! is_array( $closed_auto_guides ) || ! in_array( $screen->id, $closed_auto_guides ) ) {
				$vars['autoHelpGuide'] = json_decode( file_get_contents( $auto_guide_file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}

		}

		return $vars;

	}

}
