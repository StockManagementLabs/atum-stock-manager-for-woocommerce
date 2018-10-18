<?php
/**
 * ATUM tools and scripts for multiple purposes
 *
 * @package     Atum
 * @subpackage  Settings
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2018 Stock Management Labs™
 *
 * @since       1.4.5
 */

namespace Atum\Settings;

defined( 'ABSPATH' ) || die;


class Tools {

	/**
	 * The singleton instance holder
	 *
	 * @var Tools
	 */
	private static $instance;

	/**
	 * Tools singleton constructor
	 *
	 * @since 1.4.5
	 */
	private function __construct() {

		add_filter( 'atum/settings/tabs', array( $this, 'add_settings_tab' ), 999 );
		add_filter( 'atum/settings/defaults', array( $this, 'add_settings_defaults' ) );

	}

	/**
	 * Add a new tab to the ATUM settings page
	 *
	 * @since 1.4.5
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_settings_tab( $tabs ) {

		$tabs['tools'] = array(
			'tab_name'  => __( 'Tools', ATUM_TEXT_DOMAIN ),
			'sections'  => array(
				'tools' => __( 'ATUM Tools', ATUM_TEXT_DOMAIN ),
			),
			'no_submit' => TRUE,
		);

		return $tabs;
	}

	/**
	 * Add fields to the ATUM settings page
	 *
	 * @since 1.4.5
	 *
	 * @param array $defaults
	 *
	 * @return array
	 */
	public function add_settings_defaults( $defaults ) {

		$atum_label = '<br><span class="label label-secondary">ATUM</span>';

		$defaults['update_manage_stock'] = array(
			'section' => 'tools',
			'name'    => __( "Update WC's Manage Stock", ATUM_TEXT_DOMAIN ) . $atum_label,
			'desc'    => __( "Update the WooCommerce's manage stock at product level for all the products at once.", ATUM_TEXT_DOMAIN ),
			'type'    => 'script_runner',
			'options' => array(
				'select'        => array(
					'manage'   => __( 'Manage Stock', ATUM_TEXT_DOMAIN ),
					'unmanage' => __( 'Unmanage Stock', ATUM_TEXT_DOMAIN ),
				),
				'button_text'   => __( 'Update Now!', ATUM_TEXT_DOMAIN ),
				'script_action' => 'atum_tool_manage_stock',
				'confirm_msg'   => esc_attr( __( "This will change the WooCommerce's manage stock option for all your products", ATUM_TEXT_DOMAIN ) ),
			),
		);

		$defaults['update_control_stock'] = array(
			'section' => 'tools',
			'name'    => __( "Update ATUM's stock control", ATUM_TEXT_DOMAIN ) . $atum_label,
			'desc'    => __( "Update the ATUM's stock control option for all the products at once.", ATUM_TEXT_DOMAIN ),
			'type'    => 'script_runner',
			'options' => array(
				'select'        => array(
					'control'   => __( 'Control Stock', ATUM_TEXT_DOMAIN ),
					'uncontrol' => __( 'Uncontrol Stock', ATUM_TEXT_DOMAIN ),
				),
				'button_text'   => __( 'Update Now!', ATUM_TEXT_DOMAIN ),
				'script_action' => 'atum_tool_control_stock',
				'confirm_msg'   => esc_attr( __( "This will change the ATUM's stock control option for all your products", ATUM_TEXT_DOMAIN ) ),
			),
		);

		$defaults['clear_out_stock_threshold'] = array(
			'section' => 'tools',
			'name'    => __( 'Clear Out Stock Threshold', ATUM_TEXT_DOMAIN ) . $atum_label,
			'desc'    => __( 'Clear all previously saved Out of Stock Threshold values.', ATUM_TEXT_DOMAIN ),
			'type'    => 'script_runner',
			'options' => array(
				'button_text'   => __( 'Clear Now!', ATUM_TEXT_DOMAIN ),
				'script_action' => 'atum_tool_clear_out_stock_threshold',
				'confirm_msg'   => esc_attr( __( 'This will clear all the Out Stock Threshold values that have been set in all products', ATUM_TEXT_DOMAIN ) ),
			),
		);

		return $defaults;

	}


	/****************************
	 * Instance methods
	 ****************************/

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
	 * @return Tools instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
