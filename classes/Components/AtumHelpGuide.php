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
			$guide_steps = json_decode( file_get_contents( $guide_file ) );

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
	 * @param string $path
	 *
	 * @return string
	 */
	public function get_help_guide_button( $guide ) {

		$default_path = ATUM_PATH . 'help-guides';
		$path_data    = $this->guides_path && $this->guides_path !== $default_path ? ' data-path="' . $this->guides_path . '"' : '';

		return '<i class="atum-icon atmi-indent-increase show-intro-guide atum-tooltip" 
			data-guide="' . $guide . '"' . $path_data . ' 
			title="' . __( 'Show help guide', ATUM_PO_TEXT_DOMAIN ) . '"></i>';

	}

	/**
	 * Get the JS localization vars for the intro.js library (https://introjs.com/docs/intro/options)
	 *
	 * @since 1.9.10
	 *
	 * @param array $replace Optional. Only needed if want to modify any default value.
	 *
	 * @return array
	 */
	public static function get_intro_js_vars( $replace = array() ) {

		$defaults = array(
			'nextLabel'          => __( 'Next', ATUM_TEXT_DOMAIN ),
			'prevLabel'          => __( 'Prev', ATUM_TEXT_DOMAIN ),
			'doneLabel'          => __( 'Done', ATUM_TEXT_DOMAIN ),
			'tooltipClass'       => 'atum-help-guide-tooltip',
			'disableInteraction' => TRUE,
			'scrollToElement'    => TRUE,
		);

		return array_merge( $defaults, $replace );

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
	 *
	 * @return string[]|false
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

}
