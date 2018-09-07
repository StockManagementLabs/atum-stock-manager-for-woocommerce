<?php
/**
 * The ATUM Dashboard main class
 *
 * @package         Atum
 * @subpackage      Dashboard
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since       1.4.0
 */

namespace Atum\Dashboard;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumWidget;
use Atum\Inc\Helpers;


class Dashboard {

	/**
	 * The singleton instance holder
	 *
	 * @var Dashboard
	 */
	private static $instance;

	/**
	 * An array of ATUM Widget objects
	 *
	 * @var array
	 */
	protected $widgets = array();

	/**
	 * If the current user has no specific setup, will load the default widgets layout
	 *
	 * @var array
	 */
	private static $default_widgets_layout = array(
		ATUM_PREFIX . 'statistics_widget'    => array(
			'x'          => 0,                              // X edge position.
			'y'          => 0,                              // Y edge position.
			'width'      => 12,                             // Width in columns (based in 12 columns).
			'height'     => 4,                              // Height in rows.
			'min-height' => 5,                              // Min height in rows.
		),
		ATUM_PREFIX . 'sales_widget'         => array(
			'x'          => 0,
			'y'          => 5,
			'width'      => 3,
			'height'     => 4,
			'min-height' => 5,
		),
		ATUM_PREFIX . 'lost_sales_widget'    => array(
			'x'          => 3,
			'y'          => 5,
			'width'      => 3,
			'height'     => 4,
			'min-height' => 5,
		),
		ATUM_PREFIX . 'orders_widget'        => array(
			'x'          => 6,
			'y'          => 5,
			'width'      => 3,
			'height'     => 4,
			'min-height' => 5,
		),
		ATUM_PREFIX . 'promo_sales_widget'   => array(
			'x'          => 9,
			'y'          => 5,
			'width'      => 3,
			'height'     => 4,
			'min-height' => 5,
		),
		ATUM_PREFIX . 'stock_control_widget' => array(
			'x'          => 0,
			'y'          => 10,
			'width'      => 6,
			'height'     => 4,
			'min-height' => 5,
		),
		ATUM_PREFIX . 'news_widget'          => array(
			'x'          => 6,
			'y'          => 10,
			'width'      => 6,
			'height'     => 4,
			'min-height' => 5,
		),
		ATUM_PREFIX . 'videos_widget'        => array(
			'x'          => 0,
			'y'          => 15,
			'width'      => 12,
			'height'     => 5,
			'min-height' => 7,
		),
	);

	/**
	 * Default settings for widget grid items
	 *
	 * @var array
	 */
	protected $widget_grid_item_defaults = array(
		'id'        => '',
		'min-width' => 3,
		'max-width' => 12,
	);

	/**
	 * Widgets' layout for the current user
	 *
	 * @var array
	 */
	protected static $user_widgets_layout = array();

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
	 * @since 1.4.0
	 */
	private function __construct() {

		// Add the module menu.
		add_filter( 'atum/admin/menu_items', array( $this, 'add_menu' ), self::MENU_ORDER );

		// Enqueue dashboard scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Add the Dashboard menu. Must be the first element in the array
	 *
	 * @since 1.4.0
	 *
	 * @param array $menus
	 *
	 * @return array
	 */
	public function add_menu( $menus ) {

		$menus['dashboard'] = array(
			'title'      => __( 'Dashboard', ATUM_TEXT_DOMAIN ),
			'callback'   => array( $this, 'display' ),
			'slug'       => self::UI_SLUG,
			'menu_order' => self::MENU_ORDER,
		);

		return $menus;

	}

	/**
	 * Display the Stock Central admin page
	 *
	 * @since 1.4.0
	 */
	public function display() {
		
		// Load all the available widgets.
		$this->load_widgets();
		$user_widgets_layout = self::get_user_widgets_layout();
		
		Helpers::load_view( 'dashboard', array_merge( array(
			'widgets'   => $this->widgets,
			'layout'    => $user_widgets_layout,
			'dashboard' => $this,
		), Helpers::get_support_button() ) );
		
	}

	/**
	 * Add a new widget to the Dashboard
	 *
	 * @since 1.4.0
	 *
	 * @param AtumWidget $widget
	 * @param array      $widget_layout
	 */
	public function add_widget( $widget, $widget_layout ) {

		$widget_data = Helpers::array_to_data( $widget_layout, 'gs-' );
		Helpers::load_view( 'widgets/widget-wrapper', compact( 'widget', 'widget_data' ) );
	}

	/**
	 * Load all the available widgets
	 *
	 * @since 1.4.0
	 */
	public function load_widgets() {

		// Allow others to add paths to overwrite existing widgets or to create new ones.
		$widgets_paths = (array) apply_filters( 'atum/dashboard/widget_paths', [ trailingslashit( trailingslashit( dirname( __FILE__ ) ) . 'Widgets' ) ] );

		foreach ( $widgets_paths as $widgets_path ) {

			$widgets_dir = @scandir( $widgets_path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged

			if ( ! empty( $widgets_dir ) ) {

				foreach ( $widgets_dir as $widget_name ) {

					if ( in_array( $widget_name, [ '.', '..' ] ) ) {
						continue;
					}

					if ( is_file( $widgets_path . $widget_name ) ) {

						$widget_name = __NAMESPACE__ . '\\Widgets\\' . str_replace( '.php', '', $widget_name );

						// Load the widget (the class and file naming convention must follow PSR4 standards).
						if ( class_exists( $widget_name ) ) {
							$widget = new $widget_name();
							/* @noinspection PhpUndefinedMethodInspection */
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
	 * @since 1.4.0
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		if ( FALSE !== strpos( $hook, self::UI_SLUG ) ) {

			$user_widgets_layout = self::get_user_widgets_layout();

			wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', array(), ATUM_VERSION );
			wp_register_style( 'owl.carousel', ATUM_URL . 'assets/css/vendor/owl.carousel.min.css', array(), ATUM_VERSION );
			wp_register_style( 'owl.carousel.theme', ATUM_URL . 'assets/css/vendor/owl.theme.default.min.css', array(), ATUM_VERSION );
			wp_register_style( 'atum-dashboard', ATUM_URL . 'assets/css/atum-dashboard.css', array( 'sweetalert2', 'owl.carousel', 'owl.carousel.theme' ), ATUM_VERSION );
			wp_enqueue_style( 'atum-dashboard' );

			$min       = ! ATUM_DEBUG ? '.min' : '';
			$dash_vars = array(
				'availableWidgets' => __( 'Available Widgets', ATUM_TEXT_DOMAIN ),
			);

			/*
			 * Gridstack scripts
			 */
			wp_register_script( 'lodash', ATUM_URL . 'assets/js/vendor/lodash.min.js', array(), ATUM_VERSION, TRUE );
			wp_register_script( 'jquery-ui-touch', ATUM_URL . 'assets/js/vendor/jquery.ui.touch-punch.min.js', array(), ATUM_VERSION, TRUE );
			wp_register_script( 'gridstack', ATUM_URL . 'assets/js/vendor/gridstack.min.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-draggable', 'jquery-ui-resizable', 'jquery-ui-touch', 'lodash' ), ATUM_VERSION, TRUE );
			wp_register_script( 'gridstack-jquery-ui', ATUM_URL . 'assets/js/vendor/gridstack.jqueryui.min.js', array( 'gridstack' ), ATUM_VERSION, TRUE );

			/*
			 * SweetAlert 2
			 */
			wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', array(), ATUM_VERSION, TRUE );
			Helpers::maybe_es6_promise();

			/*
			 * NiceScroll
			 */
			wp_register_script( 'jquery.nicescroll', ATUM_URL . 'assets/js/vendor/jquery.nicescroll.min.js', array( 'jquery' ), ATUM_VERSION, TRUE );

			/*
			 * Owl Carousel
			 */
			wp_register_script( 'owl.carousel', ATUM_URL . 'assets/js/vendor/owl.carousel.min.js', array( 'jquery' ), ATUM_VERSION, TRUE );

			/*
			 * Dependencies
			 */
			$deps = array( 'gridstack', 'gridstack-jquery-ui', 'sweetalert2', 'jquery.nicescroll', 'owl.carousel' );

			/*
			 * Widgets scripts
			 */
			$widget_keys = array_keys( $user_widgets_layout );

			if ( in_array( ATUM_PREFIX . 'statistics_widget', $widget_keys ) || in_array( ATUM_PREFIX . 'stock_control_widget', $widget_keys ) ) {
				wp_register_script( 'chart-js-bundle', ATUM_URL . 'assets/js/vendor/Chart.bundle.min.js', array(), ATUM_VERSION, TRUE );
				$deps[]    = 'chart-js-bundle';
				$dash_vars = array_merge( $dash_vars, array(
					'inStockLabel'          => __( 'In Stock', ATUM_PREFIX ),
					'lowStockLabel'         => __( 'Low Stock', ATUM_PREFIX ),
					'outStockLabel'         => __( 'Out of Stock', ATUM_PREFIX ),
					'unmanagedLabel'        => __( 'Unmanaged by WC', ATUM_PREFIX ),
					'months'                => array(
						__( 'January', ATUM_TEXT_DOMAIN ),
						__( 'February', ATUM_TEXT_DOMAIN ),
						__( 'March', ATUM_TEXT_DOMAIN ),
						__( 'April', ATUM_TEXT_DOMAIN ),
						__( 'May', ATUM_TEXT_DOMAIN ),
						__( 'June', ATUM_TEXT_DOMAIN ),
						__( 'July', ATUM_TEXT_DOMAIN ),
						__( 'August', ATUM_TEXT_DOMAIN ),
						__( 'September', ATUM_TEXT_DOMAIN ),
						__( 'October', ATUM_TEXT_DOMAIN ),
						__( 'November', ATUM_TEXT_DOMAIN ),
						__( 'December', ATUM_TEXT_DOMAIN ),
					),
					'days'                  => array(
						__( 'Monday', ATUM_TEXT_DOMAIN ),
						__( 'Tuesday', ATUM_TEXT_DOMAIN ),
						__( 'Wednesday', ATUM_TEXT_DOMAIN ),
						__( 'Thursday', ATUM_TEXT_DOMAIN ),
						__( 'Friday', ATUM_TEXT_DOMAIN ),
						__( 'Saturday', ATUM_TEXT_DOMAIN ),
						__( 'Sunday', ATUM_TEXT_DOMAIN ),
					),
					'numDaysCurMonth'       => date_i18n( 't' ),
					'statsValueCurSymbol'   => get_woocommerce_currency_symbol(),
					'statsValueCurPosition' => get_option( 'woocommerce_currency_pos' ),
					'areYouSure'            => __( 'Are you sure?', ATUM_TEXT_DOMAIN ),
					'defaultsWillRestore'   => __( 'This will restore the default layout and widgets', ATUM_TEXT_DOMAIN ),
					'continue'              => __( 'Yes, restore it!', ATUM_TEXT_DOMAIN ),
					'cancel'                => __( 'Cancel', ATUM_TEXT_DOMAIN ),
				) );
			}

			if ( in_array( ATUM_PREFIX . 'statistics_widget', $widget_keys ) ) {
				wp_register_style( 'switchery', ATUM_URL . 'assets/css/vendor/switchery.min.css', FALSE, ATUM_VERSION );
				wp_enqueue_style( 'switchery' );
				wp_register_script( 'switchery', ATUM_URL . 'assets/js/vendor/switchery.min.js', FALSE, ATUM_VERSION, TRUE );
				$deps[] = 'switchery';
				$deps[] = 'jquery-ui-sortable';
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
	 * @since 1.4.0
	 *
	 * @param int   $user_id
	 * @param array $layout
	 */
	public static function save_user_widgets_layout( $user_id, $layout ) {
		update_user_meta( $user_id, ATUM_PREFIX . 'dashboard_widgets_layout', $layout );
	}

	/**
	 * Delete the user's widgets layout meta to restore defaults
	 *
	 * @since 1.4.0
	 *
	 * @param int $user_id
	 */
	public static function restore_user_widgets_layout( $user_id ) {
		delete_user_meta( $user_id, ATUM_PREFIX . 'dashboard_widgets_layout' );
	}

	/**
	 * Getter for the user_widgets_layout prop
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	public static function get_user_widgets_layout() {

		if ( empty( self::$user_widgets_layout ) ) {

			// Load the current user's layout.
			$user_id                   = get_current_user_id();
			self::$user_widgets_layout = get_user_meta( $user_id, ATUM_PREFIX . 'dashboard_widgets_layout', TRUE );

			// If the current user has no layout, load the default and save it as user meta.
			if ( '' === self::$user_widgets_layout ) {
				self::$user_widgets_layout = self::get_default_widgets_layout();
				self::save_user_widgets_layout( $user_id, self::$user_widgets_layout );
			}

		}

		return self::$user_widgets_layout;

	}

	/**
	 * Getter for the default_widgets_layout prop
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	public static function get_default_widgets_layout() {

		return apply_filters( 'atum/dashboard/default_widgets_layout', self::$default_widgets_layout );
	}

	/**
	 * Getter for the widgets prop
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	public function get_widgets() {

		return $this->widgets;
	}

	/**
	 * Getter for the widget_grid_item_defaults prop
	 *
	 * @since 1.4.0
	 *
	 * @param string $widget_id  Optional. If passed, the widget ID will be set to the returning array.
	 *
	 * @return array
	 */
	public function get_widget_grid_item_defaults( $widget_id = '' ) {

		$widget_grid_item_defaults = $this->widget_grid_item_defaults;

		if ( $widget_id ) {
			$widget_grid_item_defaults['id'] = $widget_id;
		}

		return apply_filters( 'atum/dashboard/widget_grid_item_defaults', $widget_grid_item_defaults );
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
	 * @return Dashboard instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
