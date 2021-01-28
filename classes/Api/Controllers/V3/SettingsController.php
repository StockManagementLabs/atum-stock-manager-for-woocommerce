<?php
/**
 * REST ATUM API Settings controller
 * Handles requests to the /atum/settings endpoint.
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

use Atum\Components\AtumCapabilities;
use Atum\Settings\Settings;

class SettingsController extends \WC_REST_Settings_Controller {

	/**
	 * Endpoint namespace
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/settings';

	/**
	 * The ATUM Settings object
	 *
	 * @var Settings
	 */
	protected $atum_settings;

	/**
	 * Get the groups schema, conforming to JSON Schema.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-setting-group',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'A unique identifier that can be used to link settings together.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'label'       => array(
					'description' => __( 'A human readable label for the setting used in interfaces.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'description' => array(
					'description' => __( 'A human readable description for the setting used in interfaces.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'sections'    => array(
					'description' => __( 'IDs for settings sections within groups.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );

	}

	/**
	 * Makes sure the current user has access to READ the settings APIs
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! AtumCapabilities::current_user_can( 'manage_settings' ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot list resources.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Makes sure the current user has access to WRITE the settings APIs
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function update_items_permissions_check( $request ) {

		if ( ! AtumCapabilities::current_user_can( 'manage_settings' ) ) {
			return new \WP_Error( 'atum_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Get all settings groups items.
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {

		$this->atum_settings = Settings::get_instance();
		$this->atum_settings->register_settings();

		$groups = apply_filters( 'atum/api/rest_settings_groups', $this->atum_settings->get_groups() );

		if ( empty( $groups ) ) {
			return new \WP_Error( 'atum_rest_setting_groups_empty', __( 'No setting groups have been registered.', ATUM_TEXT_DOMAIN ), [ 'status' => 500 ] );
		}

		$defaults        = $this->group_defaults();
		$filtered_groups = array();

		foreach ( $groups as $group_id => $group ) {

			$group['id'] = $group_id;
			$group       = array_merge( $defaults, $group );

			if ( ! is_null( $group['label'] ) ) {
				$group_obj  = $this->filter_group( $group );
				$group_data = $this->prepare_item_for_response( $group_obj, $request );
				$group_data = $this->prepare_response_for_collection( $group_data );

				$filtered_groups[] = $group_data;
			}

		}

		return rest_ensure_response( $filtered_groups );

	}

	/**
	 * Update a setting
	 *
	 * @since 1.6.2
	 *
	 * @param  \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {

		$options_controller = new SettingOptionsController();

		return $options_controller->update_item( $request );

	}

	/**
	 * Returns default settings for groups. null means the field is required.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	protected function group_defaults() {

		return array(
			'id'          => NULL,
			'label'       => NULL,
			'description' => '',
			'sections'    => array(),
		);

	}

	/**
	 * Callback for allowed keys for each group response.
	 *
	 * @since 1.6.2
	 *
	 * @param string $key Key to check.
	 *
	 * @return bool
	 */
	public function allowed_group_keys( $key ) {
		return in_array( $key, [ 'id', 'label', 'description', 'sections' ] );
	}

}
