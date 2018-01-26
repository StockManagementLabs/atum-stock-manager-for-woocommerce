<?php
/**
 * @package         Atum
 * @subpackage      WPDashboard
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.3
 *
 * The abstract class resposible to add a new widget to the WP Dashboard
 */

namespace Atum\WPDashboard;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumWidget;


abstract class WPDashboardWidget extends AtumWidget {

	/**
	 * The key used by WP to store the WP Dashboard Widgets' options
	 * @var string
	 */
	protected $options_key = 'dashboard_widget_options';


	/**
	 * WPDashboardWidget constructor
	 */
	public function __construct() {

		if ( is_admin() ) {
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
			array($this, 'render'),                                 // Callback for the widget rendering
			($this->has_config) ? array($this, 'config') : NULL     // Optional callback for widget configuration
		);

	}
	
}