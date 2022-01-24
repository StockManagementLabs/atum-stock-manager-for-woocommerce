<?php
/**
 * The abstact class that acts as a skeleton for all the ATUM Widgets
 *
 * @package         Atum
 * @subpackage      Components
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.4.0
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;


abstract class AtumWidget {

	/**
	 * The id of this widget
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * The widget title
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * The widget description
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * The widget thumbnail URL (for the "Add Widget" popup)
	 *
	 * @var string
	 */
	protected $thumbnail = '';

	/**
	 * The widget default layout (for the "Add Widget" popup)
	 *
	 * @var array
	 */
	protected $default_layout = [];


	/**
	 * Whether the current widget has the config settings enabled
	 *
	 * @var bool
	 */
	protected $has_config = TRUE;

	/**
	 * The key used to store the ATUM Widget options in db
	 *
	 * @var string
	 */
	protected $options_key = ATUM_PREFIX . 'dashboard_widget_options';


	/**
	 * AtumWidget constructor
	 */
	public function __construct() {

		// The widgets are used in the admin-side only.
		if ( is_admin() ) {
			add_action( 'atum/dashboard/setup', array( $this, 'init' ) );
		}

	}

	/**
	 * Widget initialization
	 *
	 * @since 1.4.0
	 */
	abstract public function init();

	/**
	 * Load the widget view
	 *
	 * @since 1.4.0
	 */
	abstract public function render();

	/**
	 * Load widget config view
	 * This is what will display when an admin clicks "Configure" at widget header
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	abstract protected function get_config();

	/**
	 * Gets the options for a widget of the specified name
	 *
	 * @since 1.4.0
	 *
	 * @return mixed An associative array containing the widget's options and values. False if no opts
	 */
	protected function get_dashboard_widget_options() {

		// Fetch all the dashboard widget options from db.
		$opts = get_option( $this->options_key );

		// If we request a widget and it exists, return it.
		if ( isset( $opts[ $this->id ] ) ) {
			return $opts[ $this->id ];
		}

		// Something went wrong.
		return FALSE;

	}

	/**
	 * Gets one specific option for the specified widget
	 *
	 * @since 1.4.0
	 *
	 * @param string $option
	 * @param string $default
	 *
	 * @return string
	 */
	protected function get_widget_option( $option, $default = '' ) {

		$opts = $this->get_dashboard_widget_options();

		// If widget opts dont exist, return false.
		if ( ! $opts ) {
			return FALSE;
		}

		// Otherwise fetch the option or use default.
		if ( ! empty( $opts[ $option ] ) ) {
			return $opts[ $option ];
		}

		return ( isset( $default ) ) ? $default : FALSE;

	}

	/**
	 * Saves an array of options for a single dashboard widget to the database
	 * Can also be used to define default values for a widget
	 *
	 * @since 1.4.0
	 *
	 * @param array $args      An associative array of options being saved.
	 * @param bool  $add_only  If true, options will not be added if widget options already exist.
	 */
	protected function update_dashboard_widget_options( $args = array(), $add_only = FALSE ) {

		// Fetch ALL dashboard widget options from the db...
		$opts = get_option( $this->options_key );

		// Get just our widget's options, or set empty array.
		$w_opts            = isset( $opts[ $this->id ] ) ? $opts[ $this->id ] : array();
		$opts[ $this->id ] = $add_only ? array_merge( $args, $w_opts ) : array_merge( $w_opts, $args );

		// Save the entire widgets array back to the db.
		update_option( $this->options_key, $opts );

	}

	/**
	 * Getter for the id prop
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_id() {

		return $this->id;
	}

	/**
	 * Getter for the title prop
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_title() {

		return $this->title;
	}

	/**
	 * Getter for the description prop
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_description() {

		return $this->description;
	}

	/**
	 * Getter for the thumbnail prop
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_thumbnail() {

		return $this->thumbnail;
	}

	/**
	 * Getter for the default_layout
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	public function get_default_layout() {

		return $this->default_layout;
	}

	/**
	 * Getter for the has_config prop
	 *
	 * @since 1.4.0
	 *
	 * @return bool
	 */
	public function has_config() {

		return $this->has_config;
	}
	
}
