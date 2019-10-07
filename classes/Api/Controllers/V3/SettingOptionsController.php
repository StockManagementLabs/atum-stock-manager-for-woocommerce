<?php
/**
 * REST ATUM API Setting Options controller
 * Handles requests to the /atum/settings/$group/$setting endpoint.
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2019 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

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
	 * Get setting data
	 *
	 * @since 1.6.2
	 *
	 * @param string $group_id   Group (section) ID.
	 * @param string $setting_id Setting ID.
	 *
	 * @return \stdClass|\WP_Error
	 */
	public function get_setting( $group_id, $setting_id ) {

		$setting = parent::get_setting( $group_id, $setting_id );

		if ( is_wp_error( $setting ) ) {
			return $setting;
		}

		$setting['group_id'] = $group_id;

		return $setting;

	}

	/**
	 * Callback for allowed keys for each setting response
	 *
	 * @since 1.6.2
	 *
	 * @param  string $key Key to check.
	 *
	 * @return boolean
	 */
	public function allowed_setting_keys( $key ) {

		return in_array(
			$key, array(
				'id',
				'group_id',
				'label',
				'description',
				'default',
				'tip',
				'placeholder',
				'type',
				'options',
				'value',
				'option_key',
			), true
		);

	}

	/**
	 * Return all settings in a group.
	 *
	 * @since  1.6.2
	 *
	 * @param  \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {

		$this->atum_settings = Settings::get_instance();

		return parent::get_items( $request );

	}

	/**
	 * Get all settings in a group (section)
	 *
	 * @since 1.6.2
	 *
	 * @param string $group_id Group (section) ID.
	 *
	 * @return array|\WP_Error
	 */
	public function get_group_settings( $group_id ) {

		if ( empty( $group_id ) ) {
			return new \WP_Error( 'atum_rest_setting_group_invalid', __( 'Invalid setting group.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$default_settings = $this->atum_settings->get_default_settings();
		$settings         = apply_filters( "atum/api/rest_settings-$group_id", $default_settings );

		// Find the group within the settings array.
		if ( $group_id ) {
			$settings = wp_list_filter( $settings, [ 'section' => $group_id ] );
		}

		if ( empty( $settings ) ) {
			return new \WP_Error( 'atum_rest_setting_group_invalid', __( 'Invalid setting group.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$filtered_settings = array();
		foreach ( $settings as $setting ) {

			$option_key = $setting['option_key'];
			$setting    = $this->filter_setting( $setting );
			$default    = isset( $setting['default'] ) ? $setting['default'] : '';

			// Get the option value.
			if ( is_array( $option_key ) ) {
				$option           = get_option( $option_key[0] );
				$setting['value'] = isset( $option[ $option_key[1] ] ) ? $option[ $option_key[1] ] : $default;
			}
			else {
				$admin_setting_value = WC_Admin_Settings::get_option( $option_key, $default );
				$setting['value']    = $admin_setting_value;
			}

			if ( 'multi_select_countries' === $setting['type'] ) {
				$setting['options'] = WC()->countries->get_countries();
				$setting['type']    = 'multiselect';
			}
			elseif ( 'single_select_country' === $setting['type'] ) {
				$setting['type']    = 'select';
				$setting['options'] = $this->get_countries_and_states();
			}
			elseif ( 'single_select_page' === $setting['type'] ) {

				$pages   = get_pages(
					array(
						'sort_column'  => 'menu_order',
						'sort_order'   => 'ASC',
						'hierarchical' => 0,
					)
				);
				$options = array();

				foreach ( $pages as $page ) {
					$options[ $page->ID ] = ! empty( $page->post_title ) ? $page->post_title : '#' . $page->ID;
				}

				$setting['type']    = 'select';
				$setting['options'] = $options;

			}

			$filtered_settings[] = $setting;

		}

		return $filtered_settings;

	}

	/**
	 * Returns a list of countries and states for use in the base location setting.
	 *
	 * @since  1.6.2
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
	 * Get the settings schema, conforming to JSON Schema
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'setting',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'A unique identifier for the setting.', 'woocommerce' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'group_id'    => array(
					'description' => __( 'An identifier for the group this setting belongs to.', 'woocommerce' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'label'       => array(
					'description' => __( 'A human readable label for the setting used in interfaces.', 'woocommerce' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'A human readable description for the setting used in interfaces.', 'woocommerce' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'value'       => array(
					'description' => __( 'Setting value.', 'woocommerce' ),
					'type'        => 'mixed',
					'context'     => array( 'view', 'edit' ),
				),
				'default'     => array(
					'description' => __( 'Default value for the setting.', 'woocommerce' ),
					'type'        => 'mixed',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'tip'         => array(
					'description' => __( 'Additional help text shown to the user about the setting.', 'woocommerce' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'placeholder' => array(
					'description' => __( 'Placeholder text to be displayed in text inputs.', 'woocommerce' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'type'        => array(
					'description' => __( 'Type of setting.', 'woocommerce' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'text', 'email', 'number', 'color', 'password', 'textarea', 'select', 'multiselect', 'radio', 'image_width', 'checkbox' ),
					'readonly'    => true,
				),
				'options'     => array(
					'description' => __( 'Array of options (key value pairs) for inputs such as select, multiselect, and radio buttons.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
