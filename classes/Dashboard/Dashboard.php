<?php
/**
 * @package     Atum
 * @subpackage  Dashboard
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2018 Stock Management Labs™
 *
 * @since       1.3.9
 *
 * The ATUM Dashboard main class
 */

namespace Atum\Dashboard;

defined( 'ABSPATH' ) or die;

use Atum\Inc\Helpers;


class Dashboard {

	/**
	 * The singleton instance holder
	 * @var Dashboard
	 */
	private static $instance;

	/**
	 * An array of ATUM Widget objects
	 * @var array
	 */
	protected $widgets = array();

	/**
	 * If the current user has no specific setup, will load the default widgets layout
	 * @var array
	 */
	private $default_widgets_layout = array(
		ATUM_PREFIX . 'statistics_widget'    => array(
			'x'      => 0,                              // X edge position
			'y'      => 0,                              // Y edge position
			'width'  => 12,                             // Width in columns (based in 12 columns)
			'height' => 4                               // Height in rows
		),
		ATUM_PREFIX . 'sales_widget'         => array(
			'x'      => 0,
			'y'      => 4,
			'width'  => 3,
			'height' => 4
		),
		ATUM_PREFIX . 'lost_sales_widget'    => array(
			'x'      => 3,
			'y'      => 4,
			'width'  => 3,
			'height' => 4
		),
		ATUM_PREFIX . 'orders_widget'        => array(
			'x'      => 6,
			'y'      => 4,
			'width'  => 3,
			'height' => 4
		),
		ATUM_PREFIX . 'promo_sales_widget'   => array(
			'x'      => 9,
			'y'      => 4,
			'width'  => 3,
			'height' => 4
		),
		ATUM_PREFIX . 'stock_control_widget' => array(
			'x'      => 0,
			'y'      => 8,
			'width'  => 6,
			'height' => 4
		),
		ATUM_PREFIX . 'news_widget'          => array(
			'x'      => 6,
			'y'      => 8,
			'width'  => 6,
			'height' => 4
		),
		ATUM_PREFIX . 'videos_widget'        => array(
			'x'      => 0,
			'y'      => 12,
			'width'  => 12,
			'height' => 5
		),
	);

	/**
	 * Widgets' layout for the current user
	 * @var array
	 */
	protected $user_widgets_layout = array();

	/**
	 * The ATUM Dashboard admin page slug
	 */
	const UI_SLUG = 'atum-dashboard';

	/**
	 * The menu order for this module
	 */
	const MENU_ORDER = 1;

	/**
	 * Dashboard constructor
	 *
	 * @since 1.3.9
	 */
	private function __construct() {

		// Add the module menu
		add_filter( 'atum/admin/menu_items', array($this, 'add_menu'), self::MENU_ORDER );

		// Enqueue dashboard scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Add the Dashboard menu. Must be the first element in the array
	 *
	 * @since 1.3.9
	 *
	 * @param array $menus
	 *
	 * @return array
	 */
	public function add_menu ($menus) {

		$menus['dashboard'] = array(
			'title'      => __( 'Dashboard', ATUM_TEXT_DOMAIN ),
			'callback'   => array( $this, 'display' ),
			'slug'       => self::UI_SLUG,
			'menu_order' => self::MENU_ORDER
		);

		return $menus;

	}

	/**
	 * Display the Stock Central admin page
	 *
	 * @since 1.3.9
	 */
	public function display() {

		// Load all the available widgets
		$this->load_widgets();
		$user_widgets_layout = $this->get_user_widgets_layout();

		Helpers::load_view( 'dashboard', array('widgets' => $this->widgets, 'layout' => $user_widgets_layout) );

	}

	/**
	 * Load all the available widgets
	 *
	 * @since 1.3.9
	 */
	private function load_widgets() {

		// Allow others to add paths to overwrite existing widgets or to create new ones
		$widgets_paths = (array) apply_filters( 'atum/dashboard/widget_paths', [ trailingslashit( trailingslashit( dirname( __FILE__ ) ) . 'Widgets' ) ] );

		foreach ($widgets_paths as $widgets_path) {

			$widgets_dir = @scandir( $widgets_path );

			if ( ! empty( $widgets_dir ) ) {

				foreach ( $widgets_dir as $widget_name ) {

					if ( in_array($widget_name, ['.', '..']) ) {
						continue;
					}

					if ( is_file( $widgets_path . $widget_name ) ) {

						$widget_name = __NAMESPACE__ . "\\Widgets\\" . str_replace( '.php', '', $widget_name );

						// Load the widget (the class and file naming convention must follow PSR4 standards)
						if ( class_exists( $widget_name ) ) {
							$widget = new $widget_name();
							$this->widgets[ $widget->get_id() ] = $widget;
						}
					}

				}

			}

		}

	}

	/**
	 * Enqueue the required scripts
	 *
	 * @since 1.3.9
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		if ( strpos($hook, self::UI_SLUG) !== FALSE ) {

			$user_widgets_layout = $this->get_user_widgets_layout();

			wp_register_style( 'atum-dashboard', ATUM_URL . 'assets/css/atum-dashboard.css', array(), ATUM_VERSION );
			wp_enqueue_style( 'atum-dashboard' );

			$min = (! ATUM_DEBUG) ? '.min' : '';
			$dash_vars = array();

			/*
			 * Gridstack scripts
			 */
			wp_register_script( 'lodash', ATUM_URL . 'assets/js/vendor/lodash.min.js', array(), ATUM_VERSION, TRUE );
			wp_register_script( 'jquery-ui-touch', ATUM_URL . 'assets/js/vendor/jquery.ui.touch-punch.min.js', array(), ATUM_VERSION, TRUE );
			wp_register_script( 'gridstack', ATUM_URL . 'assets/js/vendor/gridstack.min.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-draggable', 'jquery-ui-resizable', 'jquery-ui-touch', 'lodash'), ATUM_VERSION, TRUE );
			wp_register_script( 'gridstack-jquery-ui', ATUM_URL . 'assets/js/vendor/gridstack.jqueryui.min.js', array('gridstack'), ATUM_VERSION, TRUE );

			/*
			 * Dependencies
			 */
			$deps = array('gridstack', 'gridstack-jquery-ui');

			/*
			 * Widgets scripts
			 */
			$widget_keys = array_keys($user_widgets_layout);

			if ( in_array(ATUM_PREFIX . 'statistics_widget', $widget_keys) || in_array(ATUM_PREFIX . 'stock_control_widget', $widget_keys) ) {
				wp_register_script( 'chart-js-bundle', ATUM_URL . 'assets/js/vendor/Chart.bundle.min.js', array(), ATUM_VERSION, TRUE );
				$deps[] = 'chart-js-bundle';
				$dash_vars = array(
					'inStockLabel' => __('In Stock', ATUM_PREFIX),
					'lowStockLabel' => __('Low Stock', ATUM_PREFIX),
					'outStockLabel' => __('Out of Stock', ATUM_PREFIX),
				);
			}

			if ( in_array(ATUM_PREFIX . 'videos_widget', $widget_keys) ) {
				wp_register_script( 'jquery-scrollbar', ATUM_URL . 'assets/js/vendor/jquery.scrollbar.min.js', array('jquery'), ATUM_VERSION, TRUE );
				$deps[] = 'jquery-scrollbar';
			}

			/*
			 * ATUM Dashboard script
			 */
			wp_register_script( 'atum-dashboard', ATUM_URL . "assets/js/atum.dashboard{$min}.js", $deps, ATUM_VERSION, TRUE );
			wp_localize_script( 'atum-dashboard', 'atumDashVars', $dash_vars );
			wp_enqueue_script( 'atum-dashboard' );

		}

	}

	/**
	 * Save the user's widgets layout as user meta
	 *
	 * @since 1.3.9
	 *
	 * @param int   $user_id
	 * @param array $layout
	 */
	public static function save_user_widgets_layout($user_id, $layout) {
		update_user_meta( $user_id, ATUM_PREFIX . 'dashboard_widgets_layout', $layout );
	}

	/**
	 * Getter for the user_widgets_layout prop
	 *
	 * @since 1.3.9
	 *
	 * @return array
	 */
	public function get_user_widgets_layout() {

		if ( empty($this->user_widgets_layout) ) {

			// Load the current user's layout
			$user_id                   = get_current_user_id();
			$this->user_widgets_layout = get_user_meta( $user_id, ATUM_PREFIX . 'dashboard_widgets_layout', TRUE );

			// If the current user has no layout, load the default and save it as user meta
			if ( $this->user_widgets_layout == '' ) {
				$this->user_widgets_layout = $this->default_widgets_layout;
				self::save_user_widgets_layout( $user_id, $this->user_widgets_layout );
			}

		}

		return $this->user_widgets_layout;

	}


	/****************************
	 * Instance methods
	 ****************************/
	public function __clone() {
		// cannot be cloned
		_doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN), '1.0.0' );
	}

	public function __sleep() {
		// cannot be serialized
		_doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Dashboard instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}