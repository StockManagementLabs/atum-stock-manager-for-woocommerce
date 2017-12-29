<?php
/**
 * @package         Atum
 * @subpackage      Components
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.3
 *
 * The class resposible to add a new widget to the WP Dashboard
 */

namespace Atum\Components;

defined( 'ABSPATH' ) or die;


abstract class DashboardWidget {

	/**
	 * The id of this widget
	 * @var string
	 */
	protected $id;

	/**
	 * The widget title
	 * @var string
	 */
	protected $title;

	/**
	 * Whether the current widget has the config settings enabled
	 * @var bool
	 */
	protected $has_config = TRUE;


	/**
	 * DashboardWidget constructor
	 *
	 * @param string $widget_title  The title for the widget
	 */
	public function __construct($widget_title) {

		if ( is_admin() ) {
			$this->title = $widget_title;
			add_action( 'wp_dashboard_setup', array( $this, 'init' ) );
		}

	}

	/**
	 * Hook to wp_dashboard_setup to add the widget
	 *
	 * @since 1.2.3
	 */
	public function init() {

		// Register the widget
		wp_add_dashboard_widget(
			$this->id,                                              // A unique slug/ID
			$this->title,                                           // Visible name for the widget
			array($this, 'widget'),                                 // Callback for the main widget content
			($this->has_config) ? array($this, 'config') : NULL     // Optional callback for widget configuration content
		);

	}

	/**
	 * Load the widget view
	 *
	 * @since 1.2.3
	 */
	abstract public function widget();

	/**
	 * Load widget config view
	 * This is what will display when an admin clicks "Configure" at widget header
	 *
	 * @since 1.2.3
	 */
	public function config() {

	}

	/**
	 * Gets the options for a widget of the specified name
	 *
	 * @since 1.2.3
	 *
	 * @return mixed An associative array containing the widget's options and values. False if no opts
	 */
	protected function get_dashboard_widget_options() {

		// Fetch ALL dashboard widget options from the db
		$opts = get_option( 'dashboard_widget_options' );


		// If we request a widget and it exists, return it
		if ( isset( $opts[ $this->id ] ) ) {
			return $opts[ $this->id ];
		}

		// Something went wrong
		return FALSE;

	}

	/**
	 * Gets one specific option for the specified widget
	 *
	 * @since 1.2.3
	 *
	 * @param string $option
	 * @param string $default
	 *
	 * @return string
	 */
	protected function get_widget_option( $option, $default = '' ) {

		$opts = $this->get_dashboard_widget_options();

		// If widget opts dont exist, return false
		if ( ! $opts ) {
			return FALSE;
		}

		// Otherwise fetch the option or use default
		if ( ! empty($opts[$option]) ) {
			return $opts[ $option ];
		}

		return ( isset( $default ) ) ? $default : FALSE;

	}

	/**
	 * Saves an array of options for a single dashboard widget to the database
	 * Can also be used to define default values for a widget
	 *
	 * @since 1.2.3
	 *
	 * @param array  $args      An associative array of options being saved
	 * @param bool   $add_only  If true, options will not be added if widget options already exist
	 */
	protected function update_dashboard_widget_options( $args = array(), $add_only = FALSE ) {

		// Fetch ALL dashboard widget options from the db...
		$opts = get_option( 'dashboard_widget_options' );

		// Get just our widget's options, or set empty array
		$w_opts = ( isset( $opts[ $this->id ] ) ) ? $opts[ $this->id ] : array();
		$opts[ $this->id ] = ( $add_only ) ? array_merge($args, $w_opts) : array_merge($w_opts, $args);

		// Save the entire widgets array back to the db
		update_option('dashboard_widget_options', $opts);

	}
	
}