<?php
/**
 * Class AtumHelpGuide
 *
 * @since       2.0.0
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
	 * AtumHelpGuide constructor.
	 *
	 * @param array $guides_path
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

}
