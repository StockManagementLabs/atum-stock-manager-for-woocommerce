<?php
/**
 * REST ATUM API Wordpress Settings controller
 * Handles additional fields in wp settings.
 *
 * @since       1.8.8
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

class WordpressSettingsController extends \WP_REST_Settings_Controller {

	/**
	 * Get all the settings
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_item( $request ) {

		$response = parent::get_item( $request );

		$site_icon_id = get_option( 'site_icon' );

		$extra_fields = array(
			'site_icon' => absint( $site_icon_id ) ?: NULL
		);
		//return rest_ensure_response( $formatted_addons );
		return array_merge( $response, $extra_fields );

	}

}
