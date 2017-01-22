<?php
/**
 * @package     Atum
 * @subpackage  Inc
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   (c)2017 Stock Management Labs
 *
 * @since       0.0.2
 *
 * Build and display the plugin settings page
 */

namespace Atum\Settings;

defined( 'ABSPATH' ) or die;

use Atum\Inc\Helpers;


class Settings {
	
	/**
	 * The singleton instance holder
	 * @var Settings
	 */
	private static $instance;
	
	/**
	 * Store sections structure
	 * @var array
	 */
	private $sections;
	
	/**
	 * Default active section
	 * @var string
	 */
	private $active_section = 'general';
	
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
	
	/**
	 * The option key name for the plugin settings
	 */
	const OPTION_NAME = ATUM_PREFIX . 'settings';
	
	/**
	 * The sale days used when no value provided
	 */
	const DEFAULT_SALE_DAYS = 14;
	
	/**
	 * The default number of diaplayed posts per page
	 */
	const DEFAULT_POSTS_PER_PAGE = 20;
	
	
	private function __construct() {
		
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// TODO: Should this be changed to init???
		add_filter( "pre_update_option_" . self::OPTION_NAME, array( $this, 'update_woo_manage_stock' ), 10, 3 );
		
		$this->sections = array(
			'general'       => array(
				'tab_name' => __( 'General', ATUM_TEXT_DOMAIN ),
				'name'     => __( 'General Options', ATUM_TEXT_DOMAIN )
			),
			'stock_central' => array(
				'tab_name' => __( 'Stock Central', ATUM_TEXT_DOMAIN ),
				'name'     => __( 'Stock Central Options', ATUM_TEXT_DOMAIN )
			)
		);
		
		$this->defaults = array(
			'enable_ajax_filter' => array(
				'section' => 'general',
				'name'    => __( 'Enable Filter Autosearch', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "When enabled, the manual search button disappears. Disable this function if you don't use or find the automatic search feature helpful.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes'
			),
			'posts_per_page'     => array(
				'section' => 'stock_central',
				'name'    => __( 'Products per Page', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Controls the amount of products displayed per page across the ATUM plugin. Please note, you can set this value within the 'Screen Options' tab on every page. Enter '-1' to remove the pagination and display all products on one page (not recommended if you have many products).", ATUM_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => self::DEFAULT_POSTS_PER_PAGE
			),
			'manage_stock'       => array(
				'section' => 'stock_central',
				'name'    => __( 'Manage Stock', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Activate this option to override all WooCommerce inventory configurations.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no'
			),
			'sale_days'          => array(
				'section' => 'stock_central',
				'name'    => __( 'Days to Re-Order', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "This value sets the number of days a user needs to replenish the stock levels. It controls the 'Low Stock' indicator within the 'Stock Central' page.", ATUM_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => self::DEFAULT_SALE_DAYS
			)
		);
		
	}
	
	/**
	 * Display the settings page view
	 *
	 * @since 0.0.2
	 */
	public function display() {
		
		
		$this->options = $this->get_settings( Helpers::get_options(), $this->defaults );
		
		if ( isset( $_GET['section'] ) ) {
			$this->active_section = $_GET['section'];
		}
		
		Helpers::load_view( 'settings-page', array(
			'sections' => $this->sections,
			'active'   => $this->active_section
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
		
		if ( $hook == 'stock-central_page_atum-settings' ) {
			
			wp_register_style( 'switchery', ATUM_URL . 'assets/css/vendor/switchery.min.css', FALSE, ATUM_VERSION );
			wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', FALSE, ATUM_VERSION );
			wp_register_style( 'atum-settings', ATUM_URL . 'assets/css/atum-settings.css', FALSE, ATUM_VERSION );
			wp_register_script( 'switchery', ATUM_URL . 'assets/js/vendor/switchery.min.js', FALSE, ATUM_VERSION );
			wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', FALSE, ATUM_VERSION );
			wp_register_script( 'atum-settings', ATUM_URL . 'assets/js/atum.settings.js', array( 'jquery', 'switchery', 'jquery-tiptip', 'sweetalert2' ), ATUM_VERSION );
			
			wp_localize_script( 'atum-settings', 'atumSettings', array(
				'stockMsgTitle' => __( "Would you want to restore the 'Manage Stock' status of all products to their original state?", ATUM_TEXT_DOMAIN ),
				'stockMsgText'  => __( "<p>Select 'NO' to keep all the products in the current state.</p>", ATUM_TEXT_DOMAIN ),
				'restoreThem'   => __( 'Yes, restore them', ATUM_TEXT_DOMAIN ),
				'keepThem'      => __( 'No, keep them', ATUM_TEXT_DOMAIN )
			) );
			
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( 'switchery' );
			wp_enqueue_style( 'sweetalert2' );
			wp_enqueue_style( 'atum-settings' );
			
			wp_enqueue_script( 'atum-settings' );
			
		}
	}
	
	
	/**
	 * Register the settings using WP's Settings API
	 *
	 * @since 0.0.2
	 */
	public function register_settings() {
		
		do_action( 'atum/settings/before_register_settings', $this );
		
		// Add the sections
		foreach ( $this->sections as $section => $atts ) {
			
			add_settings_section(
				ATUM_PREFIX . "setting_$section",                   // ID
				( empty( $atts['name'] ) ) ? '' : $atts['name'],    // Title
				FALSE,                                              // Callback
				ATUM_PREFIX . "setting_$section"                    // Page
			
			);
			
			// Register the settings
			register_setting(
				ATUM_PREFIX . "setting_$section",                   // Option group
				self::OPTION_NAME,                                  // Option name
				array( $this, 'sanitize' )
			);
			
		}
		
		// Add the fields
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
		
		do_action( 'atum/settings/after_register_settings', $this );
		
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
				if ( $atts['section'] == $input['settings_section'] ) {
					
					switch ( $this->defaults[ $key ]['type'] ) {
						
						case 'switcher':
							$this->options[ $key ] = ( isset( $input[ $key ] ) ) ? 'yes' : 'no';
							break;
						
						case 'number':
							$this->options[ $key ] = ( isset( $input[ $key ] ) ) ? intval( $input[ $key ] ) : $atts['default'];
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
	 * Get the settings option array and print a integer
	 *
	 * @since 0.0.2
	 *
	 * @param array $args  Field arguments
	 */
	public function display_number( $args ) {
		
		$output = $this->get_label( $args ) . sprintf(
				'<input class="atum-settings-input" type="number" min="1" step="1" id="' . ATUM_PREFIX . $args['id'] . '" name="' . self::OPTION_NAME . '[' . $args['id'] . ']" value="%s">',
				$this->options[ $args['id'] ]
			);
		
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
		
		$output = $this->get_label( $args ) . '<input type="checkbox" id="' . ATUM_PREFIX . $args['id'] . '" name="' . self::OPTION_NAME
		          . '[' . $args['id'] . ']" value="yes" ' . checked( 'yes', $this->options[ $args['id'] ], FALSE )
		          . 'class="js-switch atum-settings-input" style="display: none">';
		
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
			
			$label = '<span class="atum-setting-info tips" data-tip="'
			         . apply_filters( 'atum/settings/print_label', $args['desc'], $args ) . '"><i class="dashicons dashicons-editor-help '
			         . $args['id'] . '""></i></span>';
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
	 * @static
	 * @return Settings instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}