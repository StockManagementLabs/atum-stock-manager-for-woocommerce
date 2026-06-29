<?php
/**
 * The ATUM Dashboard main class
 *
 * @package         Atum
 * @subpackage      Dashboard
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2026 Stock Management Labs™
 *
 * @since       1.4.0
 */

namespace Atum\Dashboard;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumAssets;
use Atum\Components\AtumColors;
use Atum\Components\AtumMarketingPopup;
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
		/*
		 * NOTE on heights: `min-h` is intentionally GREATER than `h` (legacy
		 * v1 behaviour). Both gridstack v1 and v12 clamp the rendered height
		 * up to `min-h` (`h = max(h, min-h)`), so the standard widgets render
		 * at 5 rows at 7. With cellHeight 90 (see _dashboard.ts)
		 * that reproduces the exact v1 pixel heights. Do NOT "fix" min-h to be
		 * <= h or every existing user's widgets would shrink.
		 */
		ATUM_PREFIX . 'statistics_widget'          => array(
			'x'          => 0,                              // X edge position.
			'y'          => 0,                              // Y edge position.
			'w'          => 12,                             // Width in columns (based in 12 columns).
			'h'          => 4,                              // Height in rows.
			'min-h'      => 5,                              // Effective render height (clamped up).
		),
		ATUM_PREFIX . 'sales_widget'               => array(
			'x'          => 0,
			'y'          => 5,
			'w'          => 3,
			'h'          => 4,
			'min-h'      => 5,
		),
		ATUM_PREFIX . 'lost_sales_widget'          => array(
			'x'          => 3,
			'y'          => 5,
			'w'          => 3,
			'h'          => 4,
			'min-h'      => 5,
		),
		ATUM_PREFIX . 'orders_widget'              => array(
			'x'          => 6,
			'y'          => 5,
			'w'          => 3,
			'h'          => 4,
			'min-h'      => 5,
		),
		ATUM_PREFIX . 'promo_sales_widget'         => array(
			'x'          => 9,
			'y'          => 5,
			'w'          => 3,
			'h'          => 4,
			'min-h'      => 5,
		),
		ATUM_PREFIX . 'stock_control_widget'       => array(
			'x'          => 0,
			'y'          => 10,
			'w'          => 6,
			'h'          => 4,
			'min-h'      => 5,
		),
		ATUM_PREFIX . 'current_stock_value_widget' => array(
			'x'          => 6,
			'y'          => 10,
			'w'          => 6,
			'h'          => 4,
			'min-h'      => 5,
		),
	);

	/**
	 * Default settings for widget grid items
	 *
	 * @var array
	 */
	protected $widget_grid_item_defaults = array(
		'id'    => '',
		'min-w' => 3,
		'max-w' => 12,
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

		// Get Marketing popup content.
		$marketing_popup = AtumMarketingPopup::get_instance();

		Helpers::load_view( 'dashboard', array_merge( array(
			'widgets'         => $this->widgets,
			'layout'          => $user_widgets_layout,
			'dashboard'       => $this,
			'marketing_popup' => $marketing_popup,
			'dark_mode'       => 'dark_mode' === AtumColors::get_user_theme(),
		), Helpers::get_support_buttons() ) );
		
	}

	/**
	 * Add a new widget to the Dashboard
	 *
	 * @since 1.4.0
	 *
	 * @param AtumWidget $widget
	 * @param array      $widget_layout
	 * @param bool       $new_widget
	 */
	public function add_widget( $widget, $widget_layout, $new_widget = FALSE ) {
		if ( $new_widget ) {
			$widget_id           = $widget_layout['id'];
			$widget_layout       = self::$default_widgets_layout[ $widget_layout['id'] ];
			$widget_layout['id'] = $widget_id;
		}

		$widget_data = self::build_gridstack_attrs( $widget_layout );
		Helpers::load_view( 'widgets/widget-wrapper', compact( 'widget', 'widget_data' ) );
	}

	/**
	 * Build the gridstack item HTML attributes from a widget layout.
	 *
	 * Gridstack v12 reads layout from plain `gs-x` / `gs-y` / `gs-w` / `gs-h`
	 * attributes (the `data-` prefix used by v1.x was removed in v3.0). We
	 * can't use the generic `Helpers::array_to_data()` here because that one
	 * always prepends `data-`.
	 *
	 * @since 2.0.0
	 *
	 * @param array $layout
	 *
	 * @return string
	 */
	private static function build_gridstack_attrs( array $layout ) {

		$attrs = [];

		foreach ( $layout as $key => $value ) {
			if ( is_array( $value ) ) {
				continue;
			}
			$attrs[] = sprintf( "gs-%s='%s'", $key, esc_attr( $value ) );
		}

		return implode( ' ', $attrs );

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

			$widgets_dir = @scandir( $widgets_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			if ( ! empty( $widgets_dir ) ) {

				foreach ( $widgets_dir as $widget_name ) {

					if ( in_array( $widget_name, [ '.', '..' ] ) ) {
						continue;
					}

					if ( is_file( $widgets_path . $widget_name ) ) {

						$widget_name = __NAMESPACE__ . '\\Widgets\\' . str_replace( '.php', '', $widget_name );

						// Load the widget (the class and file naming convention must follow PSR4 standards).
						if ( class_exists( $widget_name ) ) {

							/**
							 * Variable definition
							 *
							 * @var AtumWidget $widget
							 */
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
	 * @since 1.4.0
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		if ( str_contains( $hook, self::UI_SLUG ) ) {

			$user_widgets_layout = self::get_user_widgets_layout();

			/*
			 * Owl Carousel.
			 */
			AtumAssets::register_style( 'owl.carousel', 'owl.carousel.min.css', [], TRUE );
			AtumAssets::register_style( 'owl.carousel.theme', 'owl.theme.default.min.css', [], TRUE );

			/*
			 * NiceScroll.
			 */
			AtumAssets::register_script( 'atum-jquery-nice-scroll', 'jquery.nicescroll.min.js', [ 'jquery' ], TRUE );

			/*
			 * Dependencies. `atum-chartjs`, `atum-bootstrap`, etc. are auto-added
			 * by `get_asset_dependencies()` if the bundle imports them (union with
			 * the fallback below).
			 */
			$script_deps = [ 'atum-sweetalert2', 'atum-jquery-nice-scroll', 'jquery-ui-sortable', 'jquery-ui-draggable', 'wp-hooks' ];
			$style_deps  = [ 'atum-sweetalert2', 'owl.carousel', 'owl.carousel.theme' ];

			/* @deprecated since WC 10.3.0 */
			if ( version_compare( WC()->version, '10.3.0', '<' ) ) {
				$script_deps[] = 'jquery-blockui';
			}
			else {
				$script_deps[] = 'wc-jquery-blockui';
			}

			/*
			 * Widgets scripts
			 */
			$widget_keys = array_keys( $user_widgets_layout );

			if ( in_array( ATUM_PREFIX . 'current_stock_value_widget', $widget_keys ) ) {
				$script_deps[] = 'jquery-blockui';
			}

			/*
			 * ATUM Dashboard scripts.
			 */
			AtumAssets::register_style( 'atum-dashboard', 'atum-dashboard.css', $style_deps );
			wp_enqueue_style( 'atum-dashboard' );

			if ( is_rtl() ) {
				AtumAssets::register_style( 'atum-dashboard-rtl', 'atum-dashboard-rtl.css', [ 'atum-dashboard' ] );
				wp_enqueue_style( 'atum-dashboard-rtl' );
			}

			// Load the ATUM colors.
			AtumColors::enqueue_atum_colors( 'atum-dashboard' );

			AtumAssets::register_script( 'atum-dashboard', 'atum-dashboard.js', $script_deps );

			$dash_vars = array(
				'areYouSure'            => __( 'Are you sure?', ATUM_TEXT_DOMAIN ),
				'availableWidgets'      => __( 'Available Widgets', ATUM_TEXT_DOMAIN ),
				'cancel'                => __( 'Cancel', ATUM_TEXT_DOMAIN ),
				'continue'              => __( 'Yes, restore it!', ATUM_TEXT_DOMAIN ),
				'days'                  => array(
					__( 'Monday', ATUM_TEXT_DOMAIN ),
					__( 'Tuesday', ATUM_TEXT_DOMAIN ),
					__( 'Wednesday', ATUM_TEXT_DOMAIN ),
					__( 'Thursday', ATUM_TEXT_DOMAIN ),
					__( 'Friday', ATUM_TEXT_DOMAIN ),
					__( 'Saturday', ATUM_TEXT_DOMAIN ),
					__( 'Sunday', ATUM_TEXT_DOMAIN ),
				),
				'defaultsWillRestore'   => __( 'This will restore the default layout and widgets', ATUM_TEXT_DOMAIN ),
				'inStockLabel'          => __( 'In Stock', ATUM_TEXT_DOMAIN ),
				'restockStatusLabel'    => __( 'Restock Status', ATUM_TEXT_DOMAIN ),
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
				'numDaysCurMonth'       => date_i18n( 't' ),
				'outStockLabel'         => __( 'Out of Stock', ATUM_TEXT_DOMAIN ),
				'statsValueCurPosition' => get_option( 'woocommerce_currency_pos' ),
				'statsValueCurSymbol'   => get_woocommerce_currency_symbol(),
				'unmanagedLabel'        => __( 'Unmanaged by WC', ATUM_TEXT_DOMAIN ),
			);

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
				$default_layouts = self::get_default_widgets_layout();

				foreach ( $default_layouts as $key => $layout ) {
					if ( isset( $layout['default'] ) && ! $layout['default'] ) {
						unset( $default_layouts[ $key ] );
					}
				}
				self::$user_widgets_layout = $default_layouts;
				self::save_user_widgets_layout( $user_id, self::$user_widgets_layout );
			}
			else {
				// Backward-compat: legacy layouts (saved under gridstack v1) use
				// `width`/`height`/`min-height`/`min-width`/`max-width`. Gridstack
				// v12 expects `w`/`h`/`min-h`/`min-w`/`max-w`. Normalize on read
				// so existing users keep their saved layout after the upgrade.
				self::$user_widgets_layout = self::normalize_legacy_layout_keys( self::$user_widgets_layout );
			}

		}

		return self::$user_widgets_layout;

	}

	/**
	 * Convert legacy gridstack-v1 layout keys (width, height, min-width,
	 * max-width, min-height, max-height) to the gridstack-v12 short form
	 * (w, h, min-w, max-w, min-h, max-h).
	 *
	 * NOTE: we deliberately do NOT clamp `min-h` to `h`. The legacy defaults
	 * shipped with `min-height` > `height` (e.g. h:4 / min-h:5) ON PURPOSE:
	 * gridstack v1 clamped the rendered height UP to min-height
	 * (`height = Math.max(height, minHeight)`), so widgets actually rendered
	 * at 5 rows. Gridstack v12 does the same clamp, so leaving the values
	 * untouched reproduces the v1 height exactly for existing user layouts.
	 *
	 * @since 2.0.0
	 *
	 * @param array $layout
	 *
	 * @return array
	 */
	private static function normalize_legacy_layout_keys( $layout ) {

		if ( ! is_array( $layout ) ) {
			return $layout;
		}

		$rename = [
			'width'      => 'w',
			'height'     => 'h',
			'min-width'  => 'min-w',
			'max-width'  => 'max-w',
			'min-height' => 'min-h',
			'max-height' => 'max-h',
		];

		foreach ( $layout as $widget_id => $widget_data ) {

			if ( ! is_array( $widget_data ) ) {
				continue;
			}

			foreach ( $rename as $old => $new ) {
				if ( array_key_exists( $old, $widget_data ) && ! array_key_exists( $new, $widget_data ) ) {
					$widget_data[ $new ] = $widget_data[ $old ];
				}
				unset( $widget_data[ $old ] );
			}

			$layout[ $widget_id ] = $widget_data;
		}

		return $layout;

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
