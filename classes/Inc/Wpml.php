<?php
/**
 * @package         Atum
 * @subpackage      Inc
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.4.1
 *
 * WPML multilingual adapt class
 */

namespace Atum\Inc;

use Atum\PurchaseOrders\PurchaseOrders;


defined( 'ABSPATH' ) or die;

class Wpml {
	
	/**
	 * The singleton instance holder
	 * @var Wpml
	 */
	private static $instance;
	
	/**
	 * Store current existent Atum Order Post Types
	 *
	 * @var array
	 */
	private static $atum_order_types = array(
		ATUM_PREFIX . 'purchase_order',
		ATUM_PREFIX . 'inventory_log'
	);
	
	/**
	 * Whether the WC multicurrency option is active or not
	 *
	 * @var bool
	 */
	public $multicurrency_active = FALSE;
	
	/**
	 * WC WPML object
	 *
	 * @var \woocommerce_wpml
	 */
	protected $wpml;
	
	/**
	 * @var \SitePress
	 */
	protected $sitepress;
	
	/**
	 * Current product and currency custom prices (WPML Multi-currency custom product prices)
	 * @var array|bool
	 */
	protected $custom_prices = FALSE;
	
	/**
	 * Original language product's id
	 * @var int
	 */
	protected $original_product_id;
	
	/**
	 * Current currency symbol
	 *
	 * @var string
	 */
	protected $current_currency;
	
	
	/**
	 * Wpml constructor.
	 */
	private function __construct() {
	
		global $sitepress;
		
		$this->sitepress = $sitepress;
		
		$this->wpml = \woocommerce_wpml::instance();
		
		if ( $this->wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {
			$this->multicurrency_active = TRUE;
			$this->current_currency = $this->get_lang_currency();
		}
		else {
			$this->current_currency = get_woocommerce_currency();
		}
		
	}
	
	/**
	 * Add the hooks WPML Atum os goint to use
	 *
	 * @since 1.4.1
	 */
	public function add_hooks() {
		
		if (is_admin()) {
			// Make Atum orders not translatable
			add_action( 'atum/order_post_type/init', array( $this, 'add_atum_order_hooks' ) );
			
			// Load product data in AtumListTable
			add_action('atum/list_table/before_single_row', array($this, 'load_wpml_product'), 10, 2);
			add_action('atum/list_table/before_single_expandable_row', array($this, 'load_wpml_product'), 10, 2);
			
			// Hook into AtumListTable columns
			add_filter('atum/list_table/editable_column', array( $this, 'add_custom_prices_arg' ), 10, 2);
			add_filter('atum/stock_central_list/args_purchase_price', array( $this, 'add_custom_purchase_price'));
			
			// Hook into Stock Central ListTable columns
			add_filter('atum/stock_central_list/args_regular_price', array( $this, 'add_custom_regular_price'));
			add_filter('atum/stock_central_list/args_sale_price', array( $this, 'add_custom_sale_price'));
			
			// Update product meta translations
			add_filter('atum/product_meta', array( $this, 'update_multicurrency_translations_meta'), 10, 2);
			add_action('atum/product_meta_updated', array( $this, 'update_translations_meta'), 10, 2);
		}
	
	
	}
	
	/**
	 * Add specific hooks to the Order post type
	 *
	 * @param string $post_type
	 */
	public function add_atum_order_hooks( $post_type) {
		
		add_action( 'admin_head', array( __CLASS__, 'hide_multilingual_content_setup_box' ) );
		add_action( 'init', array( __CLASS__, 'remove_language_switcher' ), 12 );
		
		if ( $post_type == PurchaseOrders::POST_TYPE) {
			// Add purchase price to WPML custom prices
			add_filter( 'wcml_custom_prices_fields', array(__CLASS__, 'wpml_add_purchase_price_to_custom_prices'), 10, 2 );
			add_filter( 'wcml_custom_prices_fields_labels', array(__CLASS__, 'wpml_add_purchase_price_to_custom_price_labels'), 10, 2 );
			add_filter( 'wcml_custom_prices_strings', array(__CLASS__, 'wpml_add_purchase_price_to_custom_price_labels'), 10, 2 );
			add_filter( 'wcml_update_custom_prices_values', array(__CLASS__, 'wpml_sanitize_purchase_price_in_custom_prices'), 10, 3 );
			add_action( 'wcml_after_save_custom_prices', array(__CLASS__, 'wpml_save_purchase_price_in_custom_prices'), 10, 4 );
			
			// Save the product purchase price meta
			add_action( 'atum/hooks/after_save_purchase_price', array($this, 'save_translations_purchase_price'), 10, 2 );
			
		}
		
	}
	
	/**
	 * Remove WPML post type content setup box. Moved from AtumOrderPostType.
	 *
	 * @since 1.3.7.1
	 */
	public static function hide_multilingual_content_setup_box() {
		
		if (isset( $_GET['post_type'] ) && in_array($_GET['post_type'], self::$atum_order_types)) {
			remove_meta_box( 'icl_div_config', convert_to_screen( $_GET['post_type'] ), 'normal' );
		}
	}
	
	/**
	 * Remove WPML language switcher if current one is an Atum Order post type screen. Moved from AtumOrderPostType.
	 *
	 * @since 1.3.7.1
	 */
	public static function remove_language_switcher() {
		
		global $sitepress, $pagenow;
		
		$is_order_post_type = ( isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], self::$atum_order_types ) ) ? TRUE : FALSE;
		$get_post           = isset( $_GET['post'] ) ? $_GET['post'] : FALSE;
		$is_order_edit      = $get_post && $pagenow == 'post.php' && in_array( get_post_type( $get_post ), self::$atum_order_types );
		
		if ( $is_order_post_type || $is_order_edit ) {
			remove_action( 'wp_before_admin_bar_render', array( $sitepress, 'admin_language_switcher' ) );
		}
		
	}
	
	/**
	 * Load WPML product variables to use in the row fields
	 *
	 * @since 1.4.1
	 *
	 * @param \WC_Product $item
	 * @param string      $post_type
	 */
	public function load_wpml_product( $item, $post_type = '' ) {
		
		$this->original_product_id = $this->get_original_product_id( $this->get_product_id( $item ), $post_type );
		$this->custom_prices = FALSE;
		
		if ( get_post_meta( $this->original_product_id, '_wcml_custom_prices_status', TRUE ) ) {
			$custom_price_ui = new \WCML_Custom_Prices_UI( $this->wpml, $this->original_product_id );
			
			if ( $custom_price_ui ) {
				
				global $thepostid;
				$keep_id   = ( $thepostid ) ? $thepostid : 0;
				$thepostid = $this->original_product_id;
				
				$this->custom_prices = $custom_price_ui->get_currencies_info();
				
				$thepostid = $keep_id;
			}
		}
	}
	
	/**
	 * Get a WPML language's currency
	 *
	 * @since 1.4.1
	 *
	 * @param string $lang Language. if not provided current language will be assumed
	 *
	 * @return string
	 */
	public function get_lang_currency( $lang = '' ) {
		
		$currency = get_woocommerce_currency();
		
		$lang = $lang ? $lang : $this->sitepress->get_current_language();
		
		if ( ! empty( $this->wpml->settings['default_currencies'][ $lang ] ) ) {
			$currency = $this->wpml->settings['default_currencies'][ $lang ];
		}
		
		return $currency;
		
	}
	
	/**
	 * Add is_custom data to the editable column if the arg is set.
	 *
	 * @since 1.4.1
	 *
	 * @param string $editable_col the html of the editable column
	 * @param array $args the original args passes to AtumListTable
	 *
	 * @return string
	 */
	public function add_custom_prices_arg($editable_col, $args) {
		
		// string $is_custom         For prices, whether value is a WPML custom price value or not
		
		if ( ! empty($args['is_custom'])) {
		
			$editable_col = str_replace(' data-currency=', 'data-custom="' . $args['is_custom'] . '" data-currency=', $editable_col);
		
		}
		
		return $editable_col;
		
	}
	
	/**
	 * Add custom prices to purchase price
	 *
	 * @since 1.4.1
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function add_custom_purchase_price( $args ) {
		
		if ( ! empty( $this->custom_prices[ $this->current_currency ] ) ) {
			$purchase_price_value = $this->custom_prices[ $this->current_currency ]['custom_price']['_purchase_price'];
			$args['value'] = ( is_numeric( $purchase_price_value ) ) ? Helpers::format_price( $purchase_price_value, [ 'trim_zeros' => TRUE, 'currency'   => $this->current_currency ] ) : $args['value'];
			
			$args['currency'] = $this->current_currency;
			$args['symbol'] = $this->custom_prices[ $this->current_currency ]['currency_symbol'];
			$args['is_custom'] = 'yes';
		}
		
		return $args;
	}
	
	/**
	 * Add custom prices to regular price
	 *
	 * @since 1.4.1
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function add_custom_regular_price( $args ) {
		
		if ( ! empty( $this->custom_prices[ $this->current_currency ] ) ) {
			
			$regular_price_value = $this->custom_prices[ $this->current_currency ]['custom_price']['_regular_price'];
			$args['value']       = ( is_numeric( $regular_price_value ) ) ? Helpers::format_price( $regular_price_value, [ 'trim_zeros' => TRUE, 'currency'   => $this->current_currency ] ) : $args['value'];
			
			$args['currency']  = $this->current_currency;
			$args['symbol']    = $this->custom_prices[ $this->current_currency ]['currency_symbol'];
			$args['is_custom'] = 'yes';
		}
		elseif ( $this->multicurrency_active && $this->original_product_id !== $args['post_id'] ) {
			$product             = wc_get_product( $this->original_product_id );
			$regular_price_value = $product->get_regular_price();
			$args['value']       = ( is_numeric( $regular_price_value ) ) ? Helpers::format_price( $regular_price_value, [ 'trim_zeros' => TRUE, 'currency'   => $args['currency'] ] ) : $args['value'];
			
		}
		
		return $args;
	}
	
	/**
	 * Add custom prices to sale price
	 *
	 * @since 1.4.1
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function add_custom_sale_price( $args ) {
		
		if ( ! empty( $this->custom_prices[ $this->current_currency ] ) ) {
			
			$args['currency'] = $this->current_currency;
			$sale_price_value = $this->custom_prices[ $this->current_currency ]['custom_price']['_sale_price'];
			$args['value']    = ( is_numeric( $sale_price_value ) ) ? Helpers::format_price( $sale_price_value, [ 'trim_zeros' => TRUE, 'currency'   => $this->current_currency ] ) : $args['value'];
			$args['symbol']   = $this->custom_prices[ $this->current_currency ]['currency_symbol'];
			
			// Dates come already formatted
			$args['extra_meta'][0]['value'] = $this->custom_prices[ $this->current_currency ]['sale_price_dates_from'];
			$args['extra_meta'][1]['value'] = $this->custom_prices[ $this->current_currency ]['sale_price_dates_to'];
			
			$args['is_custom'] = 'yes';
		}
		elseif ( $this->multicurrency_active && $this->original_product_id !== $args['post_id'] ) {
			
			$product                        = wc_get_product( $this->original_product_id );
			$sale_price_value               = $product->get_sale_price();
			$args['value']                  = ( is_numeric( $sale_price_value ) ) ? Helpers::format_price( $sale_price_value, [ 'trim_zeros' => TRUE, 'currency'   => $args['currency'] ] ) : $args['value'];
			$args['extra_meta'][0]['value'] = ( $date = get_post_meta( $this->original_product_id, '_sale_price_dates_from', TRUE ) ) ? date_i18n( 'Y-m-d', $date ) : '';
			$args['extra_meta'][1]['value'] = ( $date = get_post_meta( $this->original_product_id, '_sale_price_dates_to', TRUE ) ) ? date_i18n( 'Y-m-d', $date ) : '';
			
		}
		
		return $args;
	}
	
	/**
	 * Update current product translations meta
	 *
	 * @since 1.4.1
	 *
	 * @param array $product_meta
	 * @param int   $product_id
	 *
	 * @return array
	 */
	public function update_multicurrency_translations_meta( $product_meta, $product_id ) {
		
		if ( $this->multicurrency_active ) {
			
			$original_product_id = $this->get_original_product_id( $product_id );
			
			foreach ( $product_meta as $meta_key => $meta_value ) {
				
				$meta_key = esc_attr( $meta_key );
				
				switch ( $meta_key ) {
					
					// stock id updated
					case 'regular_price':
						if ( isset( $product_meta['regular_price_custom'] ) && $product_meta['regular_price_custom'] == 'yes' ) {
							
							$custom_prices                   = $this->wpml->multi_currency->custom_prices->get_product_custom_prices( $product_id, $product_meta['regular_price_currency'] );
							$custom_prices['_regular_price'] = $meta_value;
							
							$this->wpml->multi_currency->custom_prices->update_custom_prices( $original_product_id, $custom_prices, $product_meta['regular_price_currency'] );
							
							// Unset the meta values to prevent next translations updates in update_translations_meta
							unset( $product_meta['regular_price'], $product_meta['regular_price_custom'], $product_meta['regular_price_currency'] );
							
							break;
						}
					
					case 'sale_price':
						
						if ( isset( $product_meta['sale_price_custom'] ) && $product_meta['sale_price_custom'] == 'yes' ) {
							
							$custom_prices                = $this->wpml->multi_currency->custom_prices->get_product_custom_prices( $product_id, $product_meta['sale_price_currency'] );
							$custom_prices['_sale_price'] = $meta_value;
							
							if ( isset( $product_meta['_sale_price_dates_from'], $product_meta['_sale_price_dates_to'] ) ) {
								
								$date_from = wc_clean( $product_meta['_sale_price_dates_from'] );
								$date_to   = wc_clean( $product_meta['_sale_price_dates_to'] );
								
								$custom_prices['_sale_price_dates_from'] = ( $date_from ? strtotime( $date_from ) : '' );
								$custom_prices['_sale_price_dates_to']   = ( $date_to ? strtotime( $date_to ) : '' );
								
								// Ensure these meta keys are not handled on next iterations
								unset( $product_meta['_sale_price_dates_from'], $product_meta['_sale_price_dates_to'] );
							}
							
							$this->wpml->multi_currency->custom_prices->update_custom_prices( $original_product_id, $custom_prices, $product_meta['sale_price_currency'] );
							
							unset( $product_meta['sale_price'], $product_meta['sale_price_custom'], $product_meta['sale_price_currency'] );
						}
						
						
						break;
					
					case 'purchase_price':
						
						if ( isset( $product_meta['purchase_price_custom'] ) && $product_meta['purchase_price_custom'] == 'yes' ) {
							update_post_meta( $original_product_id, '_' . $meta_key . '_' . $product_meta['purchase_price_currency'], wc_format_decimal( $meta_value ) );
							unset( $product_meta['purchase_price'], $product_meta['purchase_price_custom'], $product_meta['purchase_price_currency'] );
						}
						
						break;
					
				}
			}
			
		}
		
		return $product_meta;
		
	}
	
	/**
	 * Update current product translations meta
	 *
	 * @since 1.4.1
	 *
	 * @param int   $product_id
	 * @param array $product_meta
	 */
	public function update_translations_meta( $product_id, $product_meta ) {
		
		$post_type = get_post_type( $product_id );
		
		$product_translations = $this->sitepress->get_element_translations( $this->sitepress->get_element_trid( $product_id, 'post_' . $post_type ), 'post_' . $post_type );
		foreach ( $product_translations as $translation ) {
			if ( $translation->element_id !== $product_id ) {
				Helpers::update_product_meta( $translation->element_id, $product_meta, TRUE );
			}
		}
		
	}
	
	/**
	 * Add purchase price to WPML's custom price fields. Moved from Hooks class.
	 *
	 * @since 1.3.0
	 *
	 * @param array   $prices      Custom prices fields
	 * @param integer $product_id  The product ID
	 *
	 * @return array
	 */
	public static function wpml_add_purchase_price_to_custom_prices( $prices, $product_id ) {
		
		$prices[] = '_purchase_price';
		return $prices;
	}
	
	/**
	 * Add purchase price to WPML's custom price fields labels. Moved from Hooks class.
	 *
	 * @since 1.3.0
	 *
	 * @param array   $labels       Custom prices fields labels
	 * @param integer $product_id   The product ID
	 *
	 * @return array
	 */
	public static function wpml_add_purchase_price_to_custom_price_labels( $labels, $product_id ) {
		
		$labels['_purchase_price'] = __( 'Purchase Price', ATUM_TEXT_DOMAIN );
		return $labels;
	}
	
	/**
	 * Sanitize WPML's purchase prices. Moved from Hooks class.
	 *
	 * @since 1.3.0
	 *
	 * @param array  $prices
	 * @param string $code
	 * @param bool   $variation_id
	 *
	 * @return array
	 */
	public static function wpml_sanitize_purchase_price_in_custom_prices( $prices, $code, $variation_id = false ) {
		
		if ($variation_id) {
			$prices['_purchase_price'] = ( ! empty( $_POST['_custom_variation_purchase_price'][$code][$variation_id]) ) ? wc_format_decimal( $_POST['_custom_variation_purchase_price'][$code][$variation_id] ) : '';
		}
		else {
			$prices['_purchase_price'] = ( ! empty( $_POST['_custom_purchase_price'][$code]) )? wc_format_decimal( $_POST['_custom_purchase_price'][$code] ) : '';
		}
		
		return $prices;
	}
	
	/**
	 * Save WPML's purchase price when custom prices are enabled. Moved from Hooks class.
	 *
	 * @since 1.3.0
	 *
	 * @param int    $post_id
	 * @param float  $product_price
	 * @param array  $custom_prices
	 * @param string $code
	 */
	public static function wpml_save_purchase_price_in_custom_prices( $post_id, $product_price, $custom_prices, $code ) {
		
		if ( isset( $custom_prices[ '_purchase_price'] ) ) {
			update_post_meta( $post_id, "_purchase_price_{$code}", $custom_prices['_purchase_price'] );
		}
	}
	
	/**
	 * Save product translations' purchase price
	 *
	 * @since 1.4.1
	 *
	 * @param int $post_id
	 * @param string $purchase_price
	 */
	public function save_translations_purchase_price( $post_id, $purchase_price ) {
		
		$post_type = get_post_type( $post_id );
		
		$product_translations = $this->sitepress->get_element_translations( $this->sitepress->get_element_trid($post_id, "post_{$post_type}"), "post_{$post_type}" );
		foreach($product_translations as $translation){
			
			if( $translation->element_id !==  $post_id){
				update_post_meta( $translation->element_id, '_purchase_price', $purchase_price);
			}
			
		}
	}
	
	/**
	 * Get the original product id from a translation
	 *
	 * @since 1.4.1
	 *
	 * @param int $product_id
	 * @param     $post_type
	 *
	 * @return int
	 */
	public function get_original_product_id( $product_id = 0, $post_type = '' ) {
		
		if ( $product_id ) {
			
			$post_type = $post_type ? $post_type : get_post_type( $product_id );
			
			$product_translations = $this->sitepress->get_element_translations( $this->sitepress->get_element_trid( $product_id, 'post_' . $post_type ), 'post_' . $post_type );
			foreach ( $product_translations as $translation ) {
				if ( $translation->original ) {
					$product_id = $translation->element_id;
					break;
				}
			}
			
		}
		
		return $product_id;
		
	}
	
	/**
	 * Get the product translation's ids
	 *
	 * @since 1.4.1
	 *
	 * @param int $product_id
	 * @param     $post_type
	 *
	 * @return array
	 */
	public function get_product_translations_ids( $product_id = 0, $post_type = '' ) {
		
		$translations = [];
		
		if ( $product_id ) {
			
			$post_type = $post_type ? $post_type : get_post_type( $product_id );
			
			$product_translations = $this->sitepress->get_element_translations( $this->sitepress->get_element_trid( $product_id, 'post_' . $post_type ), 'post_' . $post_type );
			foreach ( $product_translations as $translation ) {
				$translations[$translation->language_code] = $translation->element_id;
			}
			
		}
		
		return $translations;
		
	}
	
	/**
	 * A wrapper to get the right product ID (or variation ID)
	 *
	 * @since 1.4.1
	 *
	 * @param \WC_Product $product
	 *
	 * @return int
	 */
	protected function get_product_id( $product ) {
		
		if ( $product->get_type() == 'variation' ) {
			/**
			 * @deprecated
			 * The get_variation_id() method was deprecated in WC 3.0.0
			 * In newer versions the get_id() method always be the variation_id if it's a variation
			 */
			return ( version_compare( WC()->version, '3.0.0', '<' ) == - 1 ) ? $product->get_variation_id() : $product->get_id();
		}
		
		return $product->get_id();
		
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
	 * @return Wpml instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
}