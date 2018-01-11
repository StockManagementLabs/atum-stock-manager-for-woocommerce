<?php
/**
 * @package     Atum
 * @subpackage  Settings
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2017 Stock Management Labs™
 *
 * @since       0.0.2
 *
 * Build and display the ATUM settings page
 */

namespace Atum\Settings;

defined( 'ABSPATH' ) or die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;


class Settings {
	
	/**
	 * The singleton instance holder
	 * @var Settings
	 */
	private static $instance;
	
	/**
	 * Tabs and sections structure
	 * @var array
	 */
	private $tabs;
	
	/**
	 * Default active tab
	 * @var string
	 */
	private $active_tab = 'general';
	
	/**
	 * Store field structure and default values for the settings page
	 * @var array
	 */
	private $defaults;
	
	/**
	 * Holds the values to be used in the fields callbacks
	 * @var array
	 */
	private $options;
	
	/**
	 * keep value to restore WooCommerce manage_stock individual settings
	 * @var string
	 */
	private $restore_option_stock = 'yes';

	/*
	 * The admin page slug
	 */
	const UI_SLUG = 'atum-settings';
	
	/**
	 * The option key name for the plugin settings
	 */
	const OPTION_NAME = ATUM_PREFIX . 'settings';

	/**
	 * The menu order for this module
	 */
	const MENU_ORDER = 80;
	
	/**
	 * The sale days used when no value provided
	 */
	const DEFAULT_SALE_DAYS = 14;
	
	/**
	 * The default number of diaplayed posts per page
	 */
	const DEFAULT_POSTS_PER_PAGE = 20;

	/**
	 * Settings singleton constructor
	 */
	private function __construct() {

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'pre_update_option_' . self::OPTION_NAME, array( $this, 'update_woo_manage_stock' ), 10, 3 );

		// Add the module menu
		add_filter( 'atum/admin/menu_items', array($this, 'add_menu'), self::MENU_ORDER );

		$this->tabs = array(
			'general'       => array(
				'tab_name' => __( 'General', ATUM_TEXT_DOMAIN ),
				'sections' => array(
					'general' => __( 'General Options', ATUM_TEXT_DOMAIN )
				)
			)
		);

		$this->defaults = array(
			'manage_stock'          => array(
				'section' => 'general',
				'name'    => __( 'Manage Stock', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'Activate this option to manage/unmanage all your inventory at product level.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no'
			),
			'enable_ajax_filter'    => array(
				'section' => 'general',
				'name'    => __( 'Enable Filter Autosearch', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "When enabled, the manual search button disappears. Disable this function if you don't use or find the automatic search feature helpful.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes'
			),
			'enhanced_suppliers_filter'    => array(
				'section' => 'general',
				'name'    => __( "Enhanced Suppliers' Filter", ATUM_TEXT_DOMAIN ),
				'desc'    => __( "When enabled, the List Tables Suppliers’ filter will be replaced by an advanced search box. Recommended for sites with many suppliers.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no'
			),
			'enable_admin_bar_menu' => array(
				'section' => 'general',
				'name'    => __( 'Enable Admin Bar Menu', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'When enabled, the ATUM menu will be accessible through the WP admin bar.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes'
			),
			'show_variations_stock' => array(
				'section' => 'general',
				'name'    => __( "Override 'Out of stock' Status", ATUM_TEXT_DOMAIN ),
				'desc'    => __( "When enabled, the variations' stock status will be displayed in WooCommerce products' list for variable products. This overrides the 'Out of stock' status displayed by WooCommerce, when stock is managed at product level for variable products.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes'
			),
			'stock_quantity_decimals' => array(
				'section' => 'general',
				'name'    => __( 'Decimals in Stock Quantity', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'Enter the number of decimal places your shop needs in stock quantity fields.  Set 0 to keep or 1 and higher to override the default WooCommerce NO decimal setting.', ATUM_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => 0,
				'options'    => array(
					'min' => 0
				)
			),
			'delete_data' => array(
				'section' => 'general',
				'name'    => __( 'Delete data when uninstalling', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Enable before uninstalling to remove all the data stored by ATUM in your database. Not recommended if you plan to reinstall ATUM in the future.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no'
			),
		);

		// WC Subscriptions compatibility
		if ( class_exists('\WC_Subscriptions') ) {

			$this->defaults['show_subscriptions'] = array(
				'section' => 'stock_central',
				'name'    => __( 'Show WC Subscriptions', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'When enabled, ATUM will show the WC Subscriptions in Stock Central.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes'
			);

		}

	}

	/**
	 * Add the Settings menu
	 *
	 * @since 1.3.6
	 *
	 * @param array $menus
	 *
	 * @return array
	 */
	public function add_menu ($menus) {

		$menus['settings'] = array(
			'title'      => __( 'Settings', ATUM_TEXT_DOMAIN ),
			'callback'   => array( $this, 'display' ),
			'slug'       => self::UI_SLUG,
			'menu_order' => self::MENU_ORDER
		);

		return $menus;

	}
	
	/**
	 * Display the settings page view
	 *
	 * @since 0.0.2
	 */
	public function display() {
		
		$this->options = $this->get_settings( Helpers::get_options(), $this->defaults );
		
		if ( isset( $_GET['tab'] ) ) {
			$this->active_tab = $_GET['tab'];
		}

		Helpers::load_view( 'settings-page', array(
			'tabs'   => $this->tabs,
			'active' => $this->active_tab
		) );
	}
	
	/**
	 * Get the option settings ans merge them with defaults. With parameters in case we need this function in Helpers
	 *
	 * @since   0.0.2
	 *
	 * @param   $settings   array   The settings
	 * @param   $defaults   array   The default options
	 *
	 * @return  array       The options array mixed
	 *
	 */
	public function get_settings( $settings, $defaults ) {
		
		$options = array();
		
		if ( ! $settings || ! is_array( $settings ) ) {
			$settings = array();
		}
		
		foreach ( $defaults as $field => $default ) {
			$options[ $field ] = ( array_key_exists( $field, $settings ) ) ? $settings[ $field ] : $default['default'];
		}
		
		return apply_filters( 'atum/settings/get_settings', $options );
	}
	
	
	/**
	 * Enqueues scripts and styles needed for the Settings Page
	 *
	 * @since 0.0.2
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {
		
		if ( in_array( $hook, [Globals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG, 'toplevel_page_' . self::UI_SLUG] ) ) {
			
			wp_register_style( 'switchery', ATUM_URL . 'assets/css/vendor/switchery.min.css', FALSE, ATUM_VERSION );
			wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', FALSE, ATUM_VERSION );
			wp_register_style( self::UI_SLUG, ATUM_URL . 'assets/css/atum-settings.css', FALSE, ATUM_VERSION );

			wp_register_script( 'switchery', ATUM_URL . 'assets/js/vendor/switchery.min.js', FALSE, ATUM_VERSION );
			wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', FALSE, ATUM_VERSION );
			Helpers::maybe_es6_promise();

			$min = (! ATUM_DEBUG) ? '.min' : '';
			wp_register_script( self::UI_SLUG, ATUM_URL . "assets/js/atum.settings$min.js", array( 'jquery', 'switchery', 'sweetalert2' ), ATUM_VERSION );
			
			wp_localize_script( self::UI_SLUG, 'atumSettings', array(
				'stockMsgTitle' => __( "Would you want to restore the 'Manage Stock' status of all products to their original state?", ATUM_TEXT_DOMAIN ),
				'stockMsgText'  => __( "<p>Select 'NO' to keep all the products in the current state.</p>", ATUM_TEXT_DOMAIN ),
				'restoreThem'   => __( 'Yes, restore them', ATUM_TEXT_DOMAIN ),
				'keepThem'      => __( 'No, keep them', ATUM_TEXT_DOMAIN ),
				'areYouSure'    => __( 'Are you sure?', ATUM_TEXT_DOMAIN ),
				'unsavedData'   => __( "If you move to another section without saving, you'll lose the changes you made to this Settings section", ATUM_TEXT_DOMAIN ),
				'continue'      => __( "I don't want to save, Continue", ATUM_TEXT_DOMAIN ),
				'cancel'        => __( 'Cancel', ATUM_TEXT_DOMAIN )
			) );
			
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( 'switchery' );
			wp_enqueue_style( 'sweetalert2' );
			wp_enqueue_style( self::UI_SLUG );

			if ( wp_script_is('es6-promise', 'registered') ) {
				wp_enqueue_script( 'es6-promise' );
			}

			wp_enqueue_script( self::UI_SLUG );
			
		}

	}
	
	/**
	 * Register the settings using WP's Settings API
	 *
	 * @since 0.0.2
	 */
	public function register_settings() {
		
		// Add the tabs
		$this->tabs = (array) apply_filters( 'atum/settings/tabs', $this->tabs);
		foreach ( $this->tabs as $tab => $tab_data ) {

			foreach ($tab_data['sections'] as $section_key => $section_name) {

				add_settings_section(
					ATUM_PREFIX . "setting_$section_key",    // ID
					$section_name,                           // Title
					FALSE,                                   // Callback
					ATUM_PREFIX . "setting_$section_key"     // Page
				);

				// Register the settings
				register_setting(
					ATUM_PREFIX . "setting_$section_key",    // Option group
					self::OPTION_NAME,                       // Option name
					array( $this, 'sanitize' )               // Sanitization callback
				);

			}
			
		}
		
		// Add the fields
		$this->defaults = (array) apply_filters( 'atum/settings/defaults', $this->defaults);
		foreach ( $this->defaults as $field => $options ) {
			
			$options['id'] = $field;
			
			add_settings_field(
				$field,                                             // ID
				$options['name'],                                   // Title
				array( $this, 'display_' . $options['type'] ),      // Callback
				ATUM_PREFIX . 'setting_' . $options['section'],     // Page
				ATUM_PREFIX . 'setting_' . $options['section'],     // Section
				$options
			);
		}
		
	}
	
	/**
	 * Sanitize each setting field as needed
	 *
	 * @since 0.0.2
	 *
	 * @param array $input Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public function sanitize( $input ) {
		
		$this->options = Helpers::get_options();
		
		if ( isset( $input['settings_section'] ) ) {
			
			// Only accept settings defined
			foreach ( $this->defaults as $key => $atts ) {
				
				// Only current section
				if (
					! empty( $this->tabs[ $input['settings_section'] ] ) &&
					in_array( $atts['section'], array_keys( $this->tabs[ $input['settings_section'] ]['sections'] ) )
				) {
					
					switch ( $this->defaults[ $key ]['type'] ) {
						
						case 'switcher':
							$this->options[ $key ] = ( isset( $input[ $key ] ) ) ? 'yes' : 'no';
							break;
						
						case 'number':
							$this->options[ $key ] = ( isset( $input[ $key ] ) ) ? intval( $input[ $key ] ) : $atts['default'];
							break;

						default:
							$this->options[ $key ] = ( isset( $input[ $key ] ) ) ? sanitize_text_field( $input[ $key ] ) : $atts['default'];
							break;
					}
					
				}
			}
			
		}
		
		// It's not a setting, but it's needed.
		if ( isset( $input['restore_option_stock'] ) ) {
			$this->restore_option_stock = $input['restore_option_stock'];
		}
		
		return apply_filters( 'atum/settings/sanitize', $this->options );
		
	}
	
	
	/**
	 * Get the settings option array and print a text field
	 *
	 * @since 1.2.0
	 *
	 * @param array $args  Field arguments
	 */
	public function display_text( $args ) {
		
		$output = sprintf(
			'<input class="atum-settings-input regular-text" type="text" id="' . ATUM_PREFIX . $args['id'] . '" name="' . self::OPTION_NAME . '[' . $args['id'] . ']" value="%s">',
			$this->options[ $args['id'] ]
		) . $this->get_label( $args );
		
		echo apply_filters( 'atum/settings/display_text', $output, $args );
		
	}

	/**
	 * Get the settings option array and print a number field
	 *
	 * @since 0.0.2
	 *
	 * @param array $args  Field arguments
	 */
	public function display_number( $args ) {

		$step = isset( $args['options']['step'] ) ? $args['options']['step'] : 1;
		$min  = isset( $args['options']['min'] ) ? $args['options']['min'] : 1;

		$output = sprintf(
			'<input class="atum-settings-input" type="number" min="%s" step="%s" id="' . ATUM_PREFIX . $args['id'] . '" name="' . self::OPTION_NAME . '[' . $args['id'] . ']" value="%s">',
			$min,
			$step,
			$this->options[ $args['id'] ]
		) . $this->get_label( $args );

		echo apply_filters( 'atum/settings/display_number', $output, $args );

	}
	
	
	/**
	 * Get the settings option array and prints a switcher
	 *
	 * @since 0.0.2
	 *
	 * @param array $args   Label for the field
	 */
	public function display_switcher( $args ) {
		
		$output = '<input type="checkbox" id="' . ATUM_PREFIX . $args['id'] . '" name="' . self::OPTION_NAME
		          . '[' . $args['id'] . ']" value="yes" ' . checked( 'yes', $this->options[ $args['id'] ], FALSE )
		          . 'class="js-switch atum-settings-input" style="display: none">' . $this->get_label( $args );
		
		echo apply_filters( 'atum/settings/display_switcher', $output, $args );
	}
	
	/**
	 * Print label if it exists
	 *
	 * @since 0.0.2
	 *
	 * @param array $args   Label for the field
	 *
	 * @return string
	 */
	public function get_label( $args ) {
		
		$label = '';
		
		if ( array_key_exists( 'desc', $args ) ) {
			$label = '<p class="atum-setting-info">' . apply_filters( 'atum/settings/print_label', $args['desc'], $args ) . '</p>';
		}
		
		return $label;
	}
	
	/**
	 * "Save and replace" or "Load and restore" individual manage stock option from WooCommerce depending on $new_value value
	 *
	 * @since 0.1.0
	 *
	 * @param array $new_value    For now, only interested in manage_stock. 'yes/no' When allow the user to let Atum manage stock
	 * @param array $old_value
	 * @param string $option_name
	 *
	 * @return array
	 */
	public function update_woo_manage_stock( $new_value, $old_value, $option_name ) {
		
		$ms_old_value = ( isset( $old_value['manage_stock'] ) && $old_value['manage_stock'] == 'yes' ) ? 'yes' : 'no';
		$ms_vew_value = ( isset( $new_value['manage_stock'] ) && $new_value['manage_stock'] == 'yes' ) ? 'yes' : 'no';
		
		if ( $ms_old_value == 'no' && $ms_vew_value == 'yes' ) {
			Helpers::activate_manage_stock_option();
		}
		elseif ( $ms_old_value == 'yes' && $ms_vew_value == 'no' && $this->restore_option_stock == 'yes' ) {
			
			$products = get_option( ATUM_PREFIX . 'restore_option_stock' );
			delete_option( ATUM_PREFIX . 'restore_option_stock' );
			
			if ( $products && is_array( $products ) ) {
				foreach ( $products as $product ) {
					delete_post_meta( $product, '_manage_stock' );
					delete_post_meta( $product, '_stock' );
				}
			}
			
		}
		
		return $new_value;
	}

	
	/****************************
	 * Instance methods
	 ****************************/
	public function __clone() {
		
		// cannot be cloned
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}
	
	public function __sleep() {
		
		// cannot be serialized
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}
	
	/**
	 * Get Singleton instance
	 *
	 * @return Settings instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}