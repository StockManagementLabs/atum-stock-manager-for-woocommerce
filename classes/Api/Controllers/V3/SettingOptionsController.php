<?php
/**
 * REST ATUM API Setting Options controller
 * Handles requests to the /atum/settings/$group/$setting endpoint.
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

use Atum\Components\AtumCapabilities;
use Atum\Inc\Helpers;
use Atum\Settings\Settings;

class SettingOptionsController extends \WC_REST_Setting_Options_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/settings/(?P<group_id>[\w-]+)';

	/**
	 * The ATUM Settings object
	 *
	 * @var Settings
	 */
	protected $atum_settings;

	/**
	 * All the options stored in ATUM settings
	 *
	 * @var array
	 */
	protected $atum_settings_options = array();

	/**
	 * Get the settings schema, conforming to JSON Schema
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-setting',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'A unique identifier for the setting.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'group_id'    => array(
					'description' => __( 'An identifier for the group this setting belongs to.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'section'     => array(
					'description' => __( 'An identifier for the group section this setting belongs to.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'name'        => array(
					'description' => __( 'A human readable name for the setting used in interfaces.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'desc'        => array(
					'description' => __( 'A human readable description for the setting used in interfaces.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'value'       => array(
					'description' => __( 'Setting value.', ATUM_TEXT_DOMAIN ),
					'type'        => 'mixed',
					'context'     => array( 'view', 'edit' ),
				),
				'default'     => array(
					'description' => __( 'Default value for the setting.', ATUM_TEXT_DOMAIN ),
					'type'        => 'mixed',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'type'        => array(
					'description' => __( 'Type of setting.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'enum'        => array(
						'text',
						'number',
						'color',
						'switcher',
						'textarea',
						'select',
						'html',
						'wc_country',
						'button_group',
						'script_runner',
						'theme_selector',
					),
					'readonly'    => TRUE,
				),
				'options'     => array(
					'description' => __( 'Array of options (key value pairs) for inputs such as select, multiselect, and radio buttons.', ATUM_TEXT_DOMAIN ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'dependency'  => array(
					'description' => __( 'The dependency config array for any depedent settings.', ATUM_TEXT_DOMAIN ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'confirm_msg' => array(
					'description' => __( 'The message that is shown when a confirmation is needed when changing a setting.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
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
	 * Callback for allowed keys for each setting response
	 *
	 * @since 1.6.2
	 *
	 * @param string $key Key to check.
	 *
	 * @return boolean
	 */
	public function allowed_setting_keys( $key ) {

		return in_array( $key, array(
			'id',
			'group',
			'section',
			'name',
			'desc',
			'default',
			'type',
			'options',
			'value',
			'dependency',
			'confirm_msg',
			'to_user_meta',
		), TRUE );

	}

	/**
	 * Boolean for if a setting type is a valid supported setting type.
	 *
	 * @since 1.6.2
	 *
	 * @param string $type Type.
	 *
	 * @return bool
	 */
	public function is_setting_type_valid( $type ) {

		return in_array( $type, array(
			'text',
			'switcher',
			'number',
			'color',
			'textarea',
			'select',
			'button_group',
			'wc_country',
			'theme_selector',
		) );

	}

	/**
	 * Return all settings in a group.
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {

		$this->maybe_register_atum_settings();

		return parent::get_items( $request );

	}

	/**
	 * Get all settings in a group (section)
	 *
	 * @since 1.6.2
	 *
	 * @param string $group_id Group ID.
	 *
	 * @return array|\WP_Error
	 */
	public function get_group_settings( $group_id ) {

		if ( empty( $group_id ) ) {
			return new \WP_Error( 'atum_rest_setting_group_invalid', __( 'Invalid setting group.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$this->maybe_register_atum_settings();
		$default_settings = $this->atum_settings->get_default_settings();

		// Find the group within the settings array.
		$settings = wp_list_filter( $default_settings, [ 'group' => $group_id ] );

		if ( empty( $settings ) ) {
			return new \WP_Error( 'atum_rest_setting_group_invalid', __( 'Invalid setting group.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$filtered_settings = $this->maybe_add_extra_settings( $group_id );

		foreach ( $settings as $setting_id => $setting ) {

			$setting = array_merge( [ 'id' => $setting_id ], $setting );

			// Get the option value.
			$setting['value'] = Helpers::get_option( $setting_id, $default );
			$setting          = $this->filter_setting( $setting );
			$default          = isset( $setting['default'] ) ? $setting['default'] : '';

			if ( 'wc_country' === $setting['type'] ) {
				$setting['options'] = array( 'values' => $this->get_countries_and_states() );
			}

			$filtered_settings[] = $setting;

		}

		return $filtered_settings;

	}

	protected function maybe_add_extra_settings( $group_id ) {

		$settings = array();

		if ( 'store_details' === $group_id ) {

			$site_icon_id = get_option( 'site_icon' );

			$settings[] = array(
				'id'    => 'site_icon',
				'type'  => 'number',
				'value' => absint( $site_icon_id ) ?: NULL,
			);
		}

		return $settings;

	}

	/**
	 * Filters out bad values from the settings array/filter so we only return known values via the API.
	 *
	 * @since 1.6.2
	 *
	 * @param  array $setting Settings.
	 *
	 * @return array
	 */
	public function filter_setting( $setting ) {

		$setting = parent::filter_setting( $setting );

		if ( 'button_group' === $setting['type'] && isset( $setting['options']['multiple'] ) && TRUE === $setting['options']['multiple'] ) {
			$setting['value'] = maybe_unserialize( $setting['value'] );
		}

		return $setting;

	}

	/**
	 * Returns a list of countries and states for use in the base location setting.
	 *
	 * @since 1.6.2
	 *
	 * @return array Array of states and countries.
	 */
	protected function get_countries_and_states() {

		$countries = WC()->countries->get_countries();

		if ( ! $countries ) {
			return array();
		}

		$output = array();
		foreach ( $countries as $key => $value ) {

			$states = WC()->countries->get_states( $key );

			if ( $states ) {

				foreach ( $states as $state_key => $state_value ) {
					$output[ $key . ':' . $state_key ] = $value . ' - ' . $state_value;
				}

			}
			else {
				$output[ $key ] = $value;
			}

		}

		return $output;

	}

	/**
	 * Register the ATUM Settings object (if not registered yet)
	 *
	 * @since 1.6.2
	 */
	protected function maybe_register_atum_settings() {

		if ( ! $this->atum_settings instanceof Settings ) {
			$this->atum_settings = Settings::get_instance();
		}

		if ( empty( $this->atum_settings->get_groups() ) ) {
			$this->atum_settings->register_settings();
		}

	}

	/**
	 * Update a single setting in a group.
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {

		$setting = $this->get_setting( $request['group_id'], $request['id'] );

		if ( is_wp_error( $setting ) ) {
			return $setting;
		}

		$value = $this->atum_settings->sanitize_option( $request['id'], [ $request['id'] => $request['value'] ], (array) $setting );

		if ( is_wp_error( $value ) ) {
			return $value;
		}

		// Handle user meta setting.
		if ( ! empty( $setting['to_user_meta'] ) ) {

			// Save the the user meta options and exclude them from global settings.
			if ( ! empty( $this->atum_settings->get_user_meta_options() ) ) {

				foreach ( $this->atum_settings->get_user_meta_options() as $user_meta_key => $user_meta_options ) {

					$user_options = Helpers::get_atum_user_meta( $user_meta_key );

					foreach ( $user_meta_options as $user_meta_option ) {
						if ( $request['id'] === $user_meta_option ) {
							$user_options[ $user_meta_option ] = $setting['value'] = $value;
							break;
						}
					}

					Helpers::set_atum_user_meta( $user_meta_key, $user_options );

				}

			}

		}
		// Handle global setting.
		else {

			// When using the BATCH mode, this can be executed multiple times, so we need to avoid problems with cache storing the options as a prop.
			if ( empty( $this->atum_settings_options ) ) {
				$this->atum_settings_options = Helpers::get_options();
			}

			if ( isset( $this->atum_settings_options[ $request['id'] ] ) && $value !== $this->atum_settings_options[ $request['id'] ] ) {
				$this->atum_settings_options[ $request['id'] ] = $setting['value'] = $value;
				update_option( Settings::OPTION_NAME, $this->atum_settings_options );
			}

		}

		$response = $this->prepare_item_for_response( $setting, $request );

		return rest_ensure_response( $response );

	}

}
