<?php
/**
 * @package     Atum
 * @subpackage  Settings
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2018 Stock Management Labs™
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 11 );

		// Add the module menu
		add_filter( 'atum/admin/menu_items', array($this, 'add_menu'), self::MENU_ORDER );
		
		$default_country = get_option('woocommerce_default_country');

		$this->tabs = array(
			'general'       => array(
				'tab_name' => __( 'General', ATUM_TEXT_DOMAIN ),
				'sections' => array(
					'general' => __( 'General Options', ATUM_TEXT_DOMAIN )
				)
			),
			'store_details' => array(
				'tab_name' => __( 'Store Details', ATUM_TEXT_DOMAIN ),
				'sections' => array(
					'company'  => __( 'Company info', ATUM_TEXT_DOMAIN ),
					'shipping' => __( 'Shipping info', ATUM_TEXT_DOMAIN )
				)
			),
		);

		$this->defaults = array(
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
			'show_totals'    => array(
				'section' => 'general',
				'name'    => __( 'Show Totals Row', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'When enabled, ATUM will display new row at the bottom of Stock Central. You will be able to preview page column totals of essential stock counters.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes'
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
			'out_stock_threshold' => array(
				'section' => 'general',
				'name'    => __( 'ATUM per product Out of Stock Threshold', ATUM_TEXT_DOMAIN ),
				'desc'    => __( 'Activate the switch to disable WooCommerce global threshold settings and enable ATUM per product threshold. All products will inherit the WooCommerce global value that you can now amend.<br><br>
								  Deactivate the switch to disable ATUM per product threshold and re-enable the WooCommerce global threshold. All your amended per product values will remain saved in the system and ready for future use, in case you decide to return to per product control.<br><br> 
								  We have a tool to reset or change all per product values in the Tool tab above.', ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
				'is_any_out_stock_threshold_set' => Helpers::is_any_out_stock_threshold_set(),
				'confirm_msg'   => esc_attr( __("This will clean all the Out Stock Threshold values that have been set in all products", ATUM_TEXT_DOMAIN) )
			),
			'unmanaged_counters' => array(
				'section' => 'general',
				'name'    => __( 'Unmanaged Product Counters', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Add 'In Stock', 'Out of Stock' and 'Back Ordered' counters and views for Unmanaged by WooCommerce Products in all ATUM list tables. This option will also add these products to the Dashboard Stock Control Widget. Please note that enabling this option can affect the performance in stores with a large number of products.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no'
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
				'name'    => __( 'Delete Data When Uninstalling', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Enable before uninstalling to remove all the data stored by ATUM in your database. Not recommended if you plan to reinstall ATUM in the future.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no'
			),
			'company_name' => array(
				'section' => 'company',
				'name'    => __( 'Company Name', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Fill your company's name", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => ''
			),
			'tax_number' => array(
				'section' => 'company',
				'name'    => __( 'Tax/VAT Number', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Fill your company's Tax/VAT Number", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => ''
			),
			'address_1' => array(
				'section' => 'company',
				'name'    => __( 'Address Line 1', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "The company's street address", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => ''
			),
			'address_2' => array(
				'section' => 'company',
				'name'    => __( 'Address Line 2', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Optional additional info for the Address", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => ''
			),
			'city' => array(
				'section' => 'company',
				'name'    => __( 'City', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "The city where your business is located", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => ''
			),
			'country' => array(
				'section' => 'company',
				'name'    => __( 'Country/State', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "The country and state or province if any", ATUM_TEXT_DOMAIN ),
				'type'    => 'wc_country',
				'default' => $default_country
			),
			'zip' => array(
				'section' => 'company',
				'name'    => __( 'Postcode/ZIP', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "The postal code of your business", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => ''
			),
			'same_ship_address' => array(
				'section' => 'company',
				'name'    => __( "Use as Shipping Address", ATUM_TEXT_DOMAIN ),
				'desc'    => __( "When enabled, the shipping address will be the same that the company's address.", ATUM_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
				'dependency' => array(
					'section' => 'shipping',
					'value'   => 'no'
				)
			),
			'ship_to' => array(
				'section' => 'shipping',
				'name'    => __( 'Ship to Name', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "The ship to name that will appear in the Shipping address", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => ''
			),
			'ship_address_1' => array(
				'section' => 'shipping',
				'name'    => __( 'Address Line 1', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "The shipping street address", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => ''
			),
			'ship_address_2' => array(
				'section' => 'shipping',
				'name'    => __( 'Address Line 2', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "Optional additional info for the Shipping Address", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => ''
			),
			'ship_city' => array(
				'section' => 'shipping',
				'name'    => __( 'City', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "The city where is your Shipping address", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => ''
			),
			'ship_country' => array(
				'section' => 'shipping',
				'name'    => __( 'Country/State', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "The country and state or province if any", ATUM_TEXT_DOMAIN ),
				'type'    => 'wc_country',
				'default' => $default_country
			),
			'ship_zip' => array(
				'section' => 'shipping',
				'name'    => __( 'Postcode/ZIP', ATUM_TEXT_DOMAIN ),
				'desc'    => __( "The postal code of your Shipping address", ATUM_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => ''
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

			if ( array_key_exists( $field, $settings ) ) {
				$options[ $field ] = $settings[ $field ];
			}
			elseif ( isset($default['default']) ) {
				$options[ $field ] =  $default['default'];
			}

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

			wp_register_style( 'switchery', ATUM_URL . 'assets/css/vendor/switchery.min.css', array(), ATUM_VERSION );
			wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', array(), ATUM_VERSION );
			wp_register_style( self::UI_SLUG, ATUM_URL . 'assets/css/atum-settings.css', array('switchery', 'sweetalert2'), ATUM_VERSION );

			wp_register_script( 'jquery.address', ATUM_URL . 'assets/js/vendor/jquery.address.min.js', array( 'jquery' ), ATUM_VERSION, TRUE );
			wp_register_script( 'switchery', ATUM_URL . 'assets/js/vendor/switchery.min.js', array(), ATUM_VERSION, TRUE );
			wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', array(), ATUM_VERSION, TRUE );
			Helpers::maybe_es6_promise();

			$min = ! ATUM_DEBUG ? '.min' : '';
			wp_register_script( self::UI_SLUG, ATUM_URL . "assets/js/atum.settings$min.js", array( 'jquery', 'jquery.address', 'switchery', 'sweetalert2', 'wc-enhanced-select' ), ATUM_VERSION );

			wp_localize_script( self::UI_SLUG, 'atumSettingsVars', array(
				'areYouSure'                => __( 'Are you sure?', ATUM_TEXT_DOMAIN ),
				'unsavedData'               => __( "If you move to another section without saving, you'll lose the changes you made to this Settings section", ATUM_TEXT_DOMAIN ),
				'continue'                  => __( "I don't want to save, Continue", ATUM_TEXT_DOMAIN ),
				'cancel'                    => __( 'Cancel', ATUM_TEXT_DOMAIN ),
				'run'                       => __( 'Run', ATUM_TEXT_DOMAIN ),
				'ok'                        => __( 'OK', ATUM_TEXT_DOMAIN ),
				'done'                      => __( 'Done!', ATUM_TEXT_DOMAIN ),
				'error'                     => __( 'Error!', ATUM_TEXT_DOMAIN ),
				'runnerNonce'               => wp_create_nonce( 'atum-script-runner-nonce' ),
				'isAnyOutStockThresholdSet' => Helpers::is_any_out_stock_threshold_set(),
				'OutStockThresholdSetCleanButton'   => __( 'Star Fresh', ATUM_TEXT_DOMAIN ),
				'OutStockThresholdSetCleanScript' => 'atum_tool_clean_out_stock_threshold',
				'OutStockThresholdSetCleanText'   => __( 'We have saved all your products values the last time you used this option. Would you like to clear all saved data and start fresh? If you added new products since, these will inherit the global WooCommerce value.', ATUM_TEXT_DOMAIN ),
				'OutStockThresholdDisable'   => __( 'We will save all your values for future use, in case you decide to reactive the ATUM Out of Stock per product threshold. Press OK to start using the WooCommerce global Out of Stock threshold value.', ATUM_TEXT_DOMAIN ),
			) );
			
			wp_enqueue_style( 'woocommerce_admin_styles' );
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

		// Load the tools tab
		Tools::get_instance();
		
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
							$this->options[ $key ] = isset( $input[ $key ] ) ? 'yes' : 'no';
							break;
						
						case 'number':
							$this->options[ $key ] = isset( $input[ $key ] ) ? intval( $input[ $key ] ) : $atts['default'];
							break;

						default:
							$this->options[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $atts['default'];
							break;
					}
					
				}
			}
			
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
			'<input class="atum-settings-input regular-text" type="text" id="%s" name="%s" value="%s" %s>',
			ATUM_PREFIX . $args['id'],
			self::OPTION_NAME . "[{$args['id']}]",
			$this->options[ $args['id'] ],
			$this->get_dependency($args)
		) . $this->get_description( $args );
		
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
			'<input class="atum-settings-input" type="number" min="%s" step="%s" id="%s" name="%s" value="%s" %s>',
			$min,
			$step,
			ATUM_PREFIX . $args['id'],
			self::OPTION_NAME . "[{$args['id']}]",
			$this->options[ $args['id'] ],
			$this->get_dependency($args)
		) . $this->get_description( $args );

		echo apply_filters( 'atum/settings/display_number', $output, $args );

	}

	/**
	 * Get a dropdow of countries registered in WC
	 *
	 * @since 1.3.1
	 *
	 * @param array $args Field arguments
	 */
	public function display_wc_country($args) {
	
		$country_setting = (string) $this->options[ $args['id'] ] ;
		
		if ( strstr( $country_setting, ':' ) ) {
			$country_setting = explode( ':', $country_setting );
			$country         = current( $country_setting );
			$state           = end( $country_setting );
		}
		else {
			$country = $country_setting;
			$state   = '*';
		}

		ob_start();

		?>
		<select id="<?php echo ATUM_PREFIX . $args['id'] ?>" name="<?php echo self::OPTION_NAME ."[{$args['id']}]" ?>" class="wc-enhanced-select" style="width: 25em"<?php echo $this->get_dependency($args) ?>>
			<?php WC()->countries->country_dropdown_options( $country, $state ); ?>
		</select>
		<?php
		
		$output = ob_get_clean() . $this->get_description($args);
		
		echo apply_filters('atum/settings/display_wc_country', $output , $args);
		
	}
	
	/**
	 * Get the settings option array and prints a switcher
	 *
	 * @since 0.0.2
	 *
	 * @param array $args Field arguments
	 */
	public function display_switcher( $args ) {

		$output = sprintf(
			'<input type="checkbox" id="%s" name="%s" value="yes" %s class="js-switch atum-settings-input" style="display: none" %s>',
			ATUM_PREFIX . $args['id'],
			self::OPTION_NAME . "[{$args['id']}]",
			checked( 'yes', $this->options[ $args['id'] ], FALSE ),
			$this->get_dependency($args)
		) . $this->get_description( $args );
		
		echo apply_filters( 'atum/settings/display_switcher', $output, $args );
	}

	/**
	 * Get the settings option array and prints a button group
	 *
	 * @since 1.4.6
	 *
	 * @param array $args Field arguments
	 */
	public function display_button_group( $args ) {

		$name  = self::OPTION_NAME . "[{$args['id']}]";
		$value = $this->options[ $args['id'] ];
		$style = isset( $args['options']['style'] ) ? $args['options']['style'] : 'secondary';
		$size  = isset( $args['options']['size'] ) ? $args['options']['size'] : 'sm';

		ob_start();
		?>
		<div class="multi_inventory_buttons btn-group btn-group-<?php echo $size ?> btn-group-toggle" data-toggle="buttons">
			<?php foreach ($args['options']['values'] as $option_value => $option_label): ?>
			<label class="btn btn-<?php echo $style ?><?php if ($value == $option_value) echo ' active'?>">
				<input type="radio" name="<?php echo $name ?>" autocomplete="off"<?php checked($option_value, $value) ?> value="<?php echo $option_value ?>"<?php echo $this->get_dependency($args) ?>> <?php echo $option_label ?>
		    </label>
			<?php endforeach; ?>
		</div>
		<?php

		echo $this->get_description( $args );

		echo apply_filters( 'atum/settings/display_button_group', ob_get_clean(), $args );

	}

	/**
	 * Get the settings option array and prints select dropdown
	 *
	 * @since 1.4.9
	 *
	 * @param array $args Field arguments
	 */
	public function display_select( $args ) {

		$name  = self::OPTION_NAME . "[{$args['id']}]";
		$value = $this->options[ $args['id'] ];
		$style = isset( $args['options']['style'] ) ? ' style="' . $args['options']['style'] . '"' : '';

		ob_start();
		?>
		<select class="atum-select2" name="<?php echo $name ?>" id="<?php echo ATUM_PREFIX . $args['id'] ?>"<?php echo $this->get_dependency($args) . $style ?>>
			<?php foreach ($args['options']['values'] as $option_value => $option_label): ?>
			<option value="<?php echo $option_value ?>>"<?php selected($option_value, $value) ?>"><?php echo $option_label ?></option>
			<?php endforeach; ?>
		</select>
		<?php

		echo $this->get_description( $args );

		echo apply_filters( 'atum/settings/display_button_group', ob_get_clean(), $args );

	}

	/**
	 * Get the settings option array and prints an script runner field
	 *
	 * @since 1.4.5
	 *
	 * @param array $args  Field arguments
	 */
	public function display_script_runner( $args ) {

		ob_start();
		?>
		<div class="script-runner" data-action="<?php echo $args['options']['script_action'] ?>" data-confirm="<?php echo $args['options']['confirm_msg'] ?>">

			<?php if ( isset( $args['options']['select'] ) ): ?>
			<select class="wc-enhanced-select" style="width: 12em">
				<?php foreach ( $args['options']['select'] as $key => $label ): ?>
				<option value="<?php echo $key ?>"><?php echo $label ?></option>
				<?php endforeach ?>
			</select>
			&nbsp;
			<?php endif; ?>

			<button type="button" class="btn btn-primary">
				<?php echo $args['options']['button_text'] ?>
			</button>

		</div>
		<?php

		$output = ob_get_clean() . $this->get_description($args);
		echo apply_filters( 'atum/settings/display_script_runner', $output, $args );

	}
	
	/**
	 * Print field description if it exists
	 *
	 * @since 0.0.2
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_description( $args ) {
		
		$label = '';
		
		if ( array_key_exists( 'desc', $args ) ) {
			$label = '<div class="atum-setting-info">' . apply_filters( 'atum/settings/print_label', $args['desc'], $args ) . '</div>';
		}
		
		return $label;
	}

	/**
	 * Get the dependency data (if any)
	 *
	 * @since 1.4.8
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_dependency($args) {

		if ( isset($args['dependency']) ) {
			return " data-dependency='" . json_encode( $args['dependency'] ) . "'";
		}

		return '';

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