<?php
/**
 * WPML multilingual integration class
 *
 * @package         Atum
 * @subpackage      Integrations
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.4.1
 */

namespace Atum\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumListTables\AtumListTable;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;


class Wpml {

	/**
	 * The singleton instance holder
	 *
	 * @var Wpml
	 */
	private static $instance;
	
	/**
	 * Searchable MultiCurrency columns and their types
	 */
	const MULTICURRENCY_COLUMNS = array(
		'_regular_price',
		'_sale_price',
	);
	
	/**
	 * Whether the WC multicurrency option is active or not
	 *
	 * @var bool
	 */
	public $multicurrency_active = FALSE;

	/* @noinspection PhpUndefinedClassInspection */
	/**
	 * WC WPML object
	 *
	 * @var \woocommerce_wpml
	 */
	public $wpml;

	/* @noinspection PhpUndefinedClassInspection */
	/**
	 * To hold the $sitepress global variable
	 *
	 * @var \SitePress
	 */
	public static $sitepress;

	/**
	 * Current product and currency custom prices (WPML Multi-currency custom product prices)
	 *
	 * @var array|bool
	 */
	protected $custom_prices = FALSE;

	/**
	 * Original language product's id
	 *
	 * @var int
	 */
	protected $original_product_id;

	/**
	 * Current language
	 *
	 * @var string
	 */
	public $current_language;

	/**
	 * Current currency symbol
	 *
	 * @var string
	 */
	protected $current_currency;

	/**
	 * If current editing post is a new translation
	 *
	 * @since 1.9.7
	 *
	 * @var bool
	 */
	public $is_translation = FALSE;


	/**
	 * Wpml constructor
	 *
	 * @since 1.4.1
	 */
	private function __construct() {

		global $sitepress, $woocommerce_wpml;

		self::$sitepress = $sitepress;
		$this->wpml      = $woocommerce_wpml;

		/* @noinspection PhpUndefinedMethodInspection */
		$this->current_language = self::$sitepress->get_current_language();

		/* @noinspection PhpUndefinedConstantInspection */
		if ( WCML_MULTI_CURRENCIES_INDEPENDENT === $this->wpml->settings['enable_multi_currency'] ) {
			$this->multicurrency_active = TRUE;
			$this->current_currency     = $this->get_lang_currency();
		}
		else {
			$this->current_currency = get_woocommerce_currency();
		}

		$this->register_hooks();

	}

	/**
	 * Register the WPML hooks needed by ATUM
	 *
	 * @since 1.4.1
	 */
	public function register_hooks() {

		add_action( 'atum/data_store/after_save_product_data', array( $this, 'update_atum_data' ), 10, 2 );
		add_action( 'atum/after_delete_atum_product_data', array( $this, 'delete_atum_data' ), 10, 2 );

		// Get all product translations ids to calculate calculated properties.
		add_filter( 'atum/product_calc_stock_on_hold/product_ids', array( $this, 'get_product_translations_ids' ), 10, 2 );
		add_filter( 'atum/product_calc_stock_on_hold/product_ids', array( $this, 'get_products_translations_ids' ) );

		add_action( 'wpml_pro_translation_completed', array( $this, 'new_translation_completed' ), 111, 3 );
		
		if ( is_admin() ) {

			// Make Atum orders not translatable.
			add_action( 'atum/order_post_type/init', array( $this, 'register_atum_order_hooks' ) );

			// Load product data in AtumListTable.
			add_action( 'atum/list_table/before_single_row', array( $this, 'load_wpml_product' ), 10, 2 );
			add_action( 'atum/list_table/before_single_expandable_row', array( $this, 'load_wpml_product' ), 10, 2 );

			// Hook into AtumListTable columns.
			add_filter( 'atum/list_table/editable_column', array( $this, 'add_custom_prices_arg' ), 10, 2 );
			add_filter( 'atum/list_table/args_purchase_price', array( $this, 'add_custom_purchase_price' ) );
			
			// Hook into Controlled and UnControlled Stock Central ListTables columns.
			add_filter( 'atum/list_table/args_regular_price', array( $this, 'add_custom_regular_price' ), 10, 2 );
			add_filter( 'atum/list_table/args_sale_price', array( $this, 'add_custom_sale_price' ), 10, 2 );
			
			// Hook into AtumListTable Product Search.
			if ( $this->multicurrency_active ) {
				add_filter( 'atum/list_table/posts_search/numeric_meta_where', array( $this, 'change_multi_currency_meta_where' ), 10, 3 );
			}

			// Update product meta translations.
			add_filter( 'atum/product_data', array( $this, 'update_multicurrency_translations_data' ), 10, 2 );
			add_action( 'atum/product_data_updated', array( $this, 'update_translations_data' ), 10, 2 );
			add_filter( 'atum/model/product/supplier_sku_found', array( $this, 'skip_translations' ), 10, 3 );

			// Prevent WPML from deleting meta when updating from SC.
			add_filter( 'atum/ajax/before_update_product_meta', array( $this, 'prevent_deleting_product_translations_meta' ), 2 );

			// Filter current language translations from the unmanaged products query.
			add_filter( 'atum/get_unmanaged_products/where_query', array( $this, 'unmanaged_products_where' ) );
			
			// Add WPML filters to get_posts in Helpers::get_all_products and for Suppliers::get_supplier_products.
			//add_filter( 'atum/get_all_products/args', array( $this, 'filter_get_all_products' ) );
			add_filter( 'atum/suppliers/supplier_products_args', array( $this, 'filter_get_all_products' ) );
			
			// Add upgrade ATUM tasks.
			add_action( 'atum/after_upgrade', array( $this, 'upgrade' ) );
			
			// Filter original product parts shown in product json search.
			add_filter( 'atum/ajax/search_products/query_select', array( $this, 'select_add_icl_translations' ), 10, 3 );
			add_filter( 'atum/ajax/search_products/query_where', array( $this, 'where_add_icl_translations' ), 10, 3 );
			
			// Add Atum data rows when translations are created.
			// The priority 111 is because the Atum data must be inserted after WCML created the variations.
			add_action( 'icl_make_duplicate', array( $this, 'icl_make_duplicate' ), 111, 4 );

			// Activate blocking ATUM fields in translations.
			add_filter( 'wcml_after_load_lock_fields_js', array( $this, 'block_atum_fields' ) );

			// replace the BOM Panel.
			add_filter( 'atum/product_data/can_add_atum_panel', array( $this, 'maybe_remove_atum_panel' ), 10, 2 );

			// Check if this is a translation.
			add_action( 'post_edit_form_tag', array( $this, 'check_product_if_translation' ) );
			add_action( 'woocommerce_variation_header', array( $this, 'check_variation_if_translation' ) );
			
		}

	}

	/**
	 * Add specific hooks if the Order Post Type is active and more specific if PO are active.
	 *
	 * @since 1.4.1
	 *
	 * @param string $post_type
	 */
	public function register_atum_order_hooks( $post_type ) {

		add_action( 'admin_head', array( $this, 'hide_multilingual_content_setup_box' ), 11 );
		add_action( 'init', array( $this, 'remove_language_switcher' ), 12 );

		if ( PurchaseOrders::POST_TYPE === $post_type ) {

			// Add purchase price to WPML custom prices.
			add_filter( 'wcml_custom_prices_fields', array( $this, 'wpml_add_purchase_price_to_custom_prices' ), 10, 2 );
			add_filter( 'wcml_custom_prices_fields_labels', array( $this, 'wpml_add_purchase_price_to_custom_price_labels' ), 10, 2 );
			add_filter( 'wcml_custom_prices_strings', array( $this, 'wpml_add_purchase_price_to_custom_price_labels' ), 10, 2 );
			add_filter( 'wcml_update_custom_prices_values', array( $this, 'wpml_sanitize_purchase_price_in_custom_prices' ), 10, 3 );
			add_action( 'wcml_after_save_custom_prices', array( $this, 'wpml_save_purchase_price_in_custom_prices' ), 10, 4 );

			// Save the product purchase price meta.
			add_action( 'atum/product_data/after_save_purchase_price', array( $this, 'save_translations_purchase_price' ), 10, 2 );

		}

	}

	/**
	 * Remove WPML multilingual content setup meta box.
	 *
	 * @since 1.3.7.1
	 */
	public function hide_multilingual_content_setup_box() {

		global $post_type;

		if ( $post_type && in_array( $post_type, Globals::get_order_types() ) ) {
			remove_meta_box( 'icl_div_config', convert_to_screen( $post_type ), 'normal' );
		}
	}

	/**
	 * Remove WPML language switcher if current one is an Atum Order post type screen. Moved from AtumOrderPostType.
	 *
	 * @since 1.3.7.1
	 */
	public function remove_language_switcher() {

		global $pagenow;

		$is_order_post_type = ( isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], Globals::get_order_types() ) ) ? TRUE : FALSE;
		$get_post           = isset( $_GET['post'] ) ? $_GET['post'] : FALSE;
		$is_order_edit      = $get_post && 'post.php' === $pagenow && in_array( get_post_type( $get_post ), Globals::get_order_types() );

		if ( $is_order_post_type || $is_order_edit ) {
			remove_action( 'wp_before_admin_bar_render', array( self::$sitepress, 'admin_language_switcher' ) );
		}

	}

	/**
	 * Load WPML product variables to use in the row fields
	 *
	 * @since 1.4.1
	 *
	 * @param \WP_Post      $item
	 * @param AtumListTable $list_table
	 */
	public function load_wpml_product( $item, $list_table = NULL ) {

		$product                   = $list_table->get_current_product();
		$this->original_product_id = self::get_original_product_id( $product->get_id() );
		$this->custom_prices       = FALSE;

		if ( get_post_meta( $this->original_product_id, '_wcml_custom_prices_status', TRUE ) ) {

			/* @noinspection PhpUndefinedClassInspection */
			$custom_price_ui = new \WCML_Custom_Prices_UI( $this->wpml, $this->original_product_id );

			if ( $custom_price_ui && $this->multicurrency_active ) {

				global $thepostid;
				$keep_id   = $thepostid ?: 0;
				$thepostid = $this->original_product_id;

				/* @noinspection PhpUndefinedMethodInspection */
				$this->custom_prices = $custom_price_ui->get_currencies_info();
				$thepostid           = $keep_id;

			}

		}
	}

	/**
	 * Get a WPML language's currency
	 *
	 * @since 1.4.1
	 *
	 * @param string $lang Language. if not provided current language will be assumed.
	 *
	 * @return string
	 */
	public function get_lang_currency( $lang = '' ) {

		$currency = get_woocommerce_currency();

		$lang = $lang ?: $this->current_language;

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
	 * @param string $editable_col  The html of the editable column.
	 * @param array  $args          The original args passes to AtumListTable.
	 *
	 * @return string
	 */
	public function add_custom_prices_arg( $editable_col, $args ) {

		// string $is_custom  For prices, whether value is a WPML custom price value or not.
		if ( ! empty( $args['is_custom'] ) ) {
			$editable_col = str_replace( ' data-meta=', 'data-custom="' . $args['is_custom'] . '" data-currency="' . AtumListTable::get_default_currency() . '" data-meta=', $editable_col );
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

			$purchase_price_value = $this->custom_prices[ $this->current_currency ]['custom_price'][ Globals::PURCHASE_PRICE_KEY ];
			$args['value']        = is_numeric( $purchase_price_value ) ? Helpers::format_price( $purchase_price_value, [
				'currency' => $this->current_currency,
			] ) : $args['value'];

			$args['currency']  = $this->current_currency;
			$args['symbol']    = $this->custom_prices[ $this->current_currency ]['currency_symbol'];
			$args['is_custom'] = 'yes';

		}

		return $args;
	}
	
	/**
	 * Add custom prices to regular price
	 *
	 * @since 1.4.1
	 *
	 * @param array       $args
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	public function add_custom_regular_price( $args, $product ) {
		
		if ( ! empty( $this->custom_prices[ $this->current_currency ] ) ) {

			$regular_price_value = $this->custom_prices[ $this->current_currency ]['custom_price']['_regular_price'];
			$args['value']       = is_numeric( $regular_price_value ) ? Helpers::format_price( $regular_price_value, [
				'currency' => $this->current_currency,
			] ) : $args['value'];
			
			$args['currency']  = $this->current_currency;
			$args['symbol']    = $this->custom_prices[ $this->current_currency ]['currency_symbol'];
			$args['is_custom'] = 'yes';
			
		}
		elseif ( $this->multicurrency_active && $this->original_product_id !== $product->get_id() ) {

			$product             = wc_get_product( $this->original_product_id );
			$regular_price_value = $product->get_regular_price();
			$args['value']       = is_numeric( $regular_price_value ) ? Helpers::format_price( $regular_price_value, [
				'currency' => $args['currency'],
			] ) : $args['value'];
			
		}
		
		return $args;
	}

	/**
	 * Add custom prices to sale price
	 *
	 * @since 1.4.1
	 *
	 * @param array       $args
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	public function add_custom_sale_price( $args, $product ) {
		
		if ( ! empty( $this->custom_prices[ $this->current_currency ] ) ) {

			$args['currency'] = $this->current_currency;
			$sale_price_value = $this->custom_prices[ $this->current_currency ]['custom_price']['_sale_price'];
			$args['value']    = is_numeric( $sale_price_value ) ? Helpers::format_price( $sale_price_value, [
				'currency' => $this->current_currency,
			] ) : $args['value'];
			$args['symbol']   = $this->custom_prices[ $this->current_currency ]['currency_symbol'];
			
			// Dates come already formatted.
			$args['extra_meta'][0]['value'] = $this->custom_prices[ $this->current_currency ]['sale_price_dates_from'];
			$args['extra_meta'][1]['value'] = $this->custom_prices[ $this->current_currency ]['sale_price_dates_to'];
			
			$args['is_custom'] = 'yes';
		}
		elseif ( $this->multicurrency_active && $this->original_product_id !== $product->get_id() ) {

			$product          = wc_get_product( $this->original_product_id );
			$sale_price_value = $product->get_sale_price();
			$args['value']    = is_numeric( $sale_price_value ) ? Helpers::format_price( $sale_price_value, [
				'currency' => $args['currency'],
			] ) : $args['value'];
			
			$date_from = get_post_meta( $this->original_product_id, '_sale_price_dates_from', TRUE );
			$date_to   = get_post_meta( $this->original_product_id, '_sale_price_dates_to', TRUE );
			
			$args['extra_meta'][0]['value'] = $date_from ? date_i18n( 'Y-m-d', $date_from ) : '';
			$args['extra_meta'][1]['value'] = $date_to ? date_i18n( 'Y-m-d', $date_to ) : '';
			
		}
		
		return $args;
	}
	
	/**
	 * Change meta where for values with custom multicurrency set
	 *
	 * @since 1.4.10
	 *
	 * @param string        $where
	 * @param string        $search_column
	 * @param integer|float $value
	 *
	 * @return mixed
	 */
	public function change_multi_currency_meta_where( $where, $search_column, $value ) {

		if ( in_array( $search_column, self::MULTICURRENCY_COLUMNS ) ) {
			
			$translated_meta = "{$search_column}_{$this->current_currency}";
			global $wpdb;
			
			// Basically: if the original translation has set _wcml_custom_prices_status to 1,
			// then took specific currency meta from original translation,
			// else took current post meta value.
			$where = "IF( (SELECT pmtrans.meta_value FROM {$wpdb->prefix}icl_translations AS trans1
						INNER JOIN {$wpdb->prefix}icl_translations AS trans2 ON trans2.trid = trans1.trid
						INNER JOIN {$wpdb->postmeta} pmtrans ON trans2.element_id = pmtrans.post_ID
						WHERE trans1.element_type IN ('post_product', 'post_product_variation')
						AND trans1.element_id = p.ID AND trans2.source_language_code IS NULL
						AND pmtrans.meta_key = '_wcml_custom_prices_status') = 1,
				        (SELECT pmtrans.meta_value FROM {$wpdb->prefix}icl_translations AS trans1
							INNER JOIN {$wpdb->prefix}icl_translations AS trans2 ON trans2.trid = trans1.trid
							INNER JOIN {$wpdb->postmeta} pmtrans ON trans2.element_id = pmtrans.post_ID
							WHERE trans1.element_type IN ('post_product', 'post_product_variation')
							AND trans1.element_id = p.ID AND trans2.source_language_code IS NULL
							AND pmtrans.meta_key = '$translated_meta'),
				        (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = '$search_column')) = '{$value}';";
			
		}
		
		return $where;
	}

	/**
	 * Update current product translations data
	 *
	 * @since 1.4.1
	 *
	 * @param array $product_data
	 * @param int   $product_id
	 *
	 * @return array
	 */
	public function update_multicurrency_translations_data( $product_data, $product_id ) {

		if ( $this->multicurrency_active ) {

			$original_product_id = self::get_original_product_id( $product_id );

			foreach ( $product_data as $meta_key => $meta_value ) {

				$meta_key = esc_attr( $meta_key );

				switch ( $meta_key ) {

					// Stock is updated.
					case 'regular_price':
						if ( isset( $product_data['regular_price_custom'] ) && 'yes' === $product_data['regular_price_custom'] ) {

							$custom_prices                   = $this->wpml->multi_currency->custom_prices->get_product_custom_prices( $product_id, $product_data['regular_price_currency'] );
							$custom_prices['_regular_price'] = $meta_value;

							$this->wpml->multi_currency->custom_prices->update_custom_prices( $original_product_id, $custom_prices, $product_data['regular_price_currency'] );

							// Unset the meta values to prevent next translations updates in update_translations_data.
							unset( $product_data['regular_price'], $product_data['regular_price_custom'], $product_data['regular_price_currency'] );

						}

						break;

					case 'sale_price':
						if ( isset( $product_data['sale_price_custom'] ) && 'yes' === $product_data['sale_price_custom'] ) {

							$custom_prices                = $this->wpml->multi_currency->custom_prices->get_product_custom_prices( $product_id, $product_data['sale_price_currency'] );
							$custom_prices['_sale_price'] = $meta_value;

							if ( isset( $product_data['_sale_price_dates_from'], $product_data['_sale_price_dates_to'] ) ) {

								$date_from = wc_clean( $product_data['_sale_price_dates_from'] );
								$date_to   = wc_clean( $product_data['_sale_price_dates_to'] );

								$custom_prices['_sale_price_dates_from'] = $date_from ? strtotime( $date_from ) : '';
								$custom_prices['_sale_price_dates_to']   = $date_to ? strtotime( $date_to ) : '';

								// Ensure these meta keys are not handled on next iterations.
								unset( $product_data['_sale_price_dates_from'], $product_data['_sale_price_dates_to'] );
							}

							$this->wpml->multi_currency->custom_prices->update_custom_prices( $original_product_id, $custom_prices, $product_data['sale_price_currency'] );

							unset( $product_data['sale_price'], $product_data['sale_price_custom'], $product_data['sale_price_currency'] );
						}

						break;

					case 'purchase_price':
						if ( isset( $product_data['purchase_price_custom'] ) && 'yes' === $product_data['purchase_price_custom'] ) {
							update_post_meta( $original_product_id, '_' . $meta_key . '_' . $product_data['purchase_price_currency'], wc_format_decimal( $meta_value ) );
							unset( $product_data['purchase_price'], $product_data['purchase_price_custom'], $product_data['purchase_price_currency'] );
						}

						break;

				}
			}

		}

		return $product_data;

	}

	/**
	 * Update current product translations data
	 *
	 * @since 1.4.1
	 *
	 * @param int   $product_id
	 * @param array $product_data
	 */
	public function update_translations_data( $product_id, $product_data ) {

		$post_type = get_post_type( $product_id );

		/* @noinspection PhpUndefinedMethodInspection */
		$product_translations = self::$sitepress->get_element_translations( self::$sitepress->get_element_trid( $product_id, "post_$post_type" ), "post_$post_type" );

		foreach ( $product_translations as $translation ) {

			if ( $product_id !== (int) $translation->element_id ) {
				Helpers::update_product_data( $translation->element_id, $product_data, TRUE );
			}

		}

	}

	/**
	 * Add purchase price to WPML's custom price fields. Moved from Hooks class.
	 *
	 * @since 1.3.0
	 *
	 * @param array   $prices      Custom prices fields.
	 * @param integer $product_id  The product ID.
	 *
	 * @return array
	 */
	public function wpml_add_purchase_price_to_custom_prices( $prices, $product_id ) {

		$prices[] = Globals::PURCHASE_PRICE_KEY;
		return $prices;
	}

	/**
	 * Add purchase price to WPML's custom price fields labels. Moved from Hooks class.
	 *
	 * @since 1.3.0
	 *
	 * @param array   $labels       Custom prices fields labels.
	 * @param integer $product_id   The product ID.
	 *
	 * @return array
	 */
	public function wpml_add_purchase_price_to_custom_price_labels( $labels, $product_id ) {

		$labels[ Globals::PURCHASE_PRICE_KEY ] = __( 'Purchase Price', ATUM_TEXT_DOMAIN );
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
	public function wpml_sanitize_purchase_price_in_custom_prices( $prices, $code, $variation_id = false ) {

		if ( $variation_id ) {
			$prices[ Globals::PURCHASE_PRICE_KEY ] = ! empty( $_POST['_custom_variation_purchase_price'][ $code ][ $variation_id ] ) ? wc_format_decimal( $_POST['_custom_variation_purchase_price'][ $code ][ $variation_id ] ) : '';
		}
		else {
			$prices[ Globals::PURCHASE_PRICE_KEY ] = ! empty( $_POST['_custom_purchase_price'][ $code ] ) ? wc_format_decimal( $_POST['_custom_purchase_price'][ $code ] ) : '';
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
	public function wpml_save_purchase_price_in_custom_prices( $post_id, $product_price, $custom_prices, $code ) {

		if ( isset( $custom_prices[ Globals::PURCHASE_PRICE_KEY ] ) ) {
			update_post_meta( $post_id, "_purchase_price_{$code}", $custom_prices[ Globals::PURCHASE_PRICE_KEY ] );
		}
	}

	/**
	 * Save product translations' purchase price
	 *
	 * @since 1.4.1
	 *
	 * @param int    $post_id
	 * @param string $purchase_price
	 */
	public function save_translations_purchase_price( $post_id, $purchase_price ) {

		$post_type = get_post_type( $post_id );

		/* @noinspection PhpUndefinedMethodInspection */
		$product_translations = self::$sitepress->get_element_translations( self::$sitepress->get_element_trid( $post_id, "post_{$post_type}" ), "post_{$post_type}" );
		foreach ( $product_translations as $translation ) {

			if ( $translation->element_id !== $post_id ) {
				$product = Helpers::get_atum_product( $post_id );
				$product->set_purchase_price( $purchase_price );
				$product->save_atum_data();
			}

		}

	}
	
	/**
	 * Get the original product id from a translation, . If there are not translations, it's the original one
	 *
	 * @since 1.4.1
	 *
	 * @param int|array    $product_id integer or array with the maybe translated products ids.
	 * @param string|array $post_type  post type of the arrays we're searching for. Default to product, product_variation. Allows to search by other pos types.
	 *
	 * @return int|array
	 */
	public static function get_original_product_id( $product_id = 0, $post_type = array( 'product', 'product_variation' ) ) {

		$cache_key   = AtumCache::get_cache_key( 'wpml_original_id', $product_id );
		$original_id = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {
			$return_array = is_array( $product_id );
			$results      = array( 0 );

			if ( $product_id ) {

				global $wpdb;

				$product_id = (array) $product_id;
				$post_type  = $post_type ? (array) $post_type : (array) get_post_type( $product_id[0] );
				// phpcs:ignore Squiz.Strings.DoubleQuoteUsage.NotRequired
				$str_sql = "
				SELECT ori.element_id FROM {$wpdb->prefix}icl_translations tra
				LEFT OUTER JOIN {$wpdb->prefix}icl_translations ori ON tra.trid = ori.trid
  				WHERE tra.element_id IN (" . implode( ',', $product_id ) . ")
  				AND tra.element_type IN ( 'post_" . implode( "','post_", $post_type ) . "')
  				AND ori.`source_language_code` IS NULL AND ori.`trid` IS NOT NULL
            ";

				$results = $wpdb->get_col( $str_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				$results = array_map( 'intval', $results ?: $product_id );
			}

			$original_id = $return_array ? $results : $results[0];

			AtumCache::set_cache( $cache_key, $original_id, ATUM_TEXT_DOMAIN );
		}

		return $original_id;
		
	}

	/**
	 * Get the product translation's ids
	 *
	 * @since 1.4.1
	 *
	 * @param int    $product_id
	 * @param string $post_type
	 *
	 * @return array
	 */
	public static function get_product_translations_ids( $product_id = 0, $post_type = '' ) {

		$translations = [];

		if ( $product_id ) {

			$post_type = $post_type ? $post_type : get_post_type( $product_id );

			/* @noinspection PhpUndefinedMethodInspection */
			$product_translations = self::$sitepress->get_element_translations( self::$sitepress->get_element_trid( $product_id, 'post_' . $post_type ), 'post_' . $post_type );
			foreach ( $product_translations as $translation ) {
				$translations[ $translation->language_code ] = (int) $translation->element_id;
			}

		}

		return $translations;

	}

	/**
	 * Get all translations ids from an array of products
	 *
	 * @since 1.8.4
	 *
	 * @param int|array $product_ids
	 * @param string    $post_type
	 *
	 * @return array
	 */
	public static function get_products_translations_ids( $product_ids, $post_type = '' ) {

		$translations = [];

		if ( ! is_array( $product_ids ) ) {
			$product_ids = [ $product_ids ];
		}

		foreach ( $product_ids as $product_id ) {

			$translations = $translations + self::get_product_translations_ids( $product_id, $post_type );
		}

		return array_unique( $translations );

	}
	
	/**
	 * Filter for the Unmanaged products query (where part) to only exclude WPML translations
	 *
	 * @since 1.4.1
	 *
	 * @param array $unmng_where
	 *
	 * @return array
	 */
	public function unmanaged_products_where( $unmng_where ) {
		
		global $wpdb;
		
		$unmng_where[] = "
			AND posts.ID IN (
				SELECT DISTINCT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type IN ('post_product', 'post_product_variation') AND language_code = '{$this->current_language}'
			)
		";
		
		return $unmng_where;
	}
	
	/**
	 * Set suppress_filters to 0 to add WPML filters to get_posts functions
	 *
	 * @ince 1.4.3.3
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function filter_get_all_products( $args ) {
		
		$args['suppress_filters'] = 0;
		
		return $args;
	}
	
	/**
	 * Add ICL_TRANSLATIONS table to the Select clause
	 *
	 * @since 1.1.8
	 *
	 * @param string $query_select
	 * @param string $product_type
	 * @param array  $post_types
	 *
	 * @return string
	 */
	public function select_add_icl_translations( $query_select, $product_type = '', $post_types = [] ) {
		
		global $wpdb;
		
		$query_select .= " LEFT JOIN {$wpdb->prefix}icl_translations tr ON (posts.ID = tr.element_id AND
						   CONCAT('post_', posts.post_type) = tr.element_type)";
		
		return $query_select;
		
	}
	
	/**
	 * Add ICL_TRANSLATIONS where to sect default language (WC shows not translatable product data in the default language)
	 *
	 * @since 1.1.8
	 *
	 * @param string $where_clause
	 * @param string $product_type
	 * @param array  $post_types
	 *
	 * @return string
	 */
	public function where_add_icl_translations( $where_clause, $product_type = '', $post_types = [] ) {
		
		$where_clause .= ' AND tr.source_language_code IS NULL';
		
		return $where_clause;
		
	}
	
	/**
	 * Returns false if the product found is a translation of the current product
	 *
	 * @since 1.5.0
	 *
	 * @param int         $product_id
	 * @param string      $supplier_sku
	 * @param \WC_Product $product
	 *
	 * @return integer|bool
	 */
	public function skip_translations( $product_id, $supplier_sku, $product ) {
		
		if ( $product_id ) {
			
			$post_type = get_post_type( $product_id );

			/* @noinspection PhpUndefinedMethodInspection */
			if ( self::$sitepress->get_element_trid( $product_id, 'post_' . $post_type ) === self::$sitepress->get_element_trid( $product->get_id(), 'post_' . $post_type ) ) {
				return FALSE;
			}
		}
		
		return $product_id;
	}
	
	/**
	 * Duplicate the ATUM data
	 *
	 * @since 1.5.8.4
	 *
	 * @param integer $master_post_id post id where the duplication was called.
	 * @param string  $lang
	 * @param array   $postarr
	 * @param integer $id             New post id.
	 */
	public function icl_make_duplicate( $master_post_id, $lang, $postarr, $id ) {
		
		if ( 'product' === get_post_type( $master_post_id ) ) {
			
			$master_post_id = $this->wpml->products->get_original_product_id( $master_post_id );
			
			$this->duplicate_atum_product( $master_post_id, $id );
			
			$product = wc_get_product( $id );
			
			if ( in_array( $product->get_type(), array_diff( Globals::get_inheritable_product_types(), [ 'grouped' ] ) ) ) {
			
				$childs = $product->get_children();
				
				foreach ( $childs as $child_id ) {
					
					$original_product_id = $this->wpml->products->get_original_product_id( $child_id );
					
					$this->duplicate_atum_product( $original_product_id, $child_id );
				}
				
			}
		}
	}

	/**
	 * Saves ATUM data after a translation is completed.
	 *
	 * @since 1.9.7
	 *
	 * @param int $new_post_id
	 * @param array $fields
	 * @param \WPML_Translation_Job_Factory|\stdClass $job
	 */
	public function new_translation_completed( $new_post_id, $fields, $job ) {

		if ( 'product' === get_post_type( $new_post_id ) ) {

			$master_post_id = $this->wpml->products->get_original_product_id( $new_post_id );

			self::duplicate_atum_product( $master_post_id, $new_post_id );
		}
	}

	/**
	 * Duplicates an entry from atum product data table.
	 * Needs to be updated when the database changes.
	 *
	 * @since 1.5.8.4
	 *
	 * @param int $original_id
	 * @param int $destination_id
	 */
	public static function duplicate_atum_product( $original_id, $destination_id ) {

		global $wpdb;

		$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		$extra_fields = apply_filters( 'atum/duplicate_atum_product/add_fields', [] );
		$fields       = empty( $extra_fields ) ? '' : ',' . implode( ',', $extra_fields );

		// phpcs:disable WordPress.DB.PreparedSQL
		$wpdb->query( "
			INSERT IGNORE INTO $atum_product_data_table (
				product_id,purchase_price,supplier_id,supplier_sku,atum_controlled,out_stock_date,
				out_stock_threshold,inheritable,inbound_stock,stock_on_hold,sold_today,sales_last_days,
				reserved_stock,customer_returns,warehouse_damage,lost_in_post,other_logs,out_stock_days,
				lost_sales,has_location,update_date,atum_stock_status,low_stock,sales_update_date$fields)
			SELECT $destination_id,purchase_price,supplier_id,supplier_sku,atum_controlled,out_stock_date,
			out_stock_threshold,inheritable,inbound_stock,stock_on_hold,sold_today,sales_last_days,
			reserved_stock,customer_returns,warehouse_damage,lost_in_post,other_logs,out_stock_days,
			lost_sales,has_location,update_date,atum_stock_status,low_stock,sales_update_date$fields
			FROM $atum_product_data_table WHERE product_id = $original_id;
		" );
		// phpcs:enable
	}
	
	/**
	 * Ensure all translations have the same data
	 *
	 * @since 1.5.8.4
	 *
	 * @param array   $data
	 * @param integer $product_id
	 */
	public function update_atum_data( $data, $product_id ) {
		
		global $wpdb;
		
		$post_type        = get_post_type( $product_id );
		$translations_ids = [];
		
		/* @noinspection PhpUndefinedMethodInspection */
		$product_translations = self::$sitepress->get_element_translations( self::$sitepress->get_element_trid( $product_id, "post_$post_type" ), "post_$post_type" );
		
		foreach ( $product_translations as $translation ) {
			
			$translation_id = (int) $translation->element_id;
			if ( $product_id !== $translation_id ) {
				
				$translations_ids[] = $translation_id;
			}
			
		}
		
		if ( $translations_ids ) {
			// Don't need prepare, all are integers.
			$translations_ids_str = implode( ',', $translations_ids );
			$table                = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
			$in_atum              = $wpdb->get_col( "SELECT product_id FROM $table WHERE product_id IN( $translations_ids_str )" ); // phpcs:ignore WordPress.DB.PreparedSQL
			
			foreach ( $translations_ids as $translation_id ) {
				
				if ( in_array( $translation_id, $in_atum ) ) {
					
					// If present it's not needed.
					unset( $data['product_id'] );
					
					$wpdb->update(
						$wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE,
						$data,
						array(
							'product_id' => $translation_id,
						)
					);
				}
				else {
					
					// if inserting, it's needed.
					self::duplicate_atum_product( $product_id, $translation_id );
				}
			}
			
		}
		
	}

	/**
	 * Prevent WPML deleting meta from product translations when saving from Stock Central (it will be deleted by ATUM).
	 *
	 * @since 1.8.8
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function prevent_deleting_product_translations_meta( $data ) {

		remove_action( 'deleted_post_meta', array( $this->wpml->sync_product_data, 'delete_empty_post_meta_for_translations' ) );

		return $data;
	}
	
	/**
	 * Ensure all translation ATUM data is removed when removing a product
	 *
	 * @since 1.5.8.4
	 *
	 * @param \WC_Product $product The product object.
	 */
	public function delete_atum_data( $product ) {
		
		global $wpdb;
		
		// Delete the ATUM data for this product.
		$product_id       = $product->get_id();
		$post_type        = get_post_type( $product_id );
		$translations_ids = [];

		/* @noinspection PhpUndefinedMethodInspection */
		$product_translations = self::$sitepress->get_element_translations( self::$sitepress->get_element_trid( $product_id, "post_$post_type" ), "post_$post_type" );

		foreach ( $product_translations as $translation ) {

			$translation_id = (int) $translation->element_id;
			if ( $product_id !== $translation_id ) {

				$translations_ids[] = $translation_id;
			}

		}

		if ( $translations_ids ) {

			// Don't need prepare, all are integers.
			$translations_ids_str = implode( ',', $translations_ids );
			$table                = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

			$wpdb->query( "DELETE FROM $table WHERE product_id IN( $translations_ids_str)" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		
	}

	/**
	 * Modifies the localized ATUM product data to block the ATUM fields when editing.
	 *
	 * @since 1.9.0
	 */
	public function block_atum_fields() {

		/**
		 * Modifies the ATUM product data localized variables.
		 *
		 * @dincre 1.9.0
		 *
		 * @param array $vars
		 *
		 * @return array
		 */
		add_filter( 'atum/product_data/localized_vars', function ( $vars ) {

			$vars['lockFields'] = 'yes';

			return $vars;
		} );

	}

	/**
	 * Prevent ATUM Panel to be shown if the product is a translation
	 *
	 * @since 1.9.0
	 *
	 * @param bool        $add_panel
	 * @param \WC_Product $product
	 *
	 * @return mixed
	 */
	public function maybe_remove_atum_panel( $add_panel, $product ) {

		if ( $this->is_translation ) {

			$add_panel = FALSE;
			if ( $product instanceof \WC_Product_Variation ) {
				$id     = "atum_product_data_{$product->get_id()}";
				$hidden = '';
			}
			else {
				$id     = 'atum_product_data';
				$hidden = ' hidden';
			}

			?>
			<div id="<?php esc_attr_e( $id ); ?>" class="panel woocommerce_options_panel<?php esc_attr_e( $hidden );?>">

				<div class="options-group translated-atum-product">
					<div class="alert alert-warning">
						<h3>
							<i class="atum-icon atmi-warning"></i>
							<?php esc_html_e( 'ATUM settings can not be edited within translations', ATUM_TEXT_DOMAIN ) ?>
						</h3>

						<p><?php esc_html_e( 'You must edit original product instead.', ATUM_TEXT_DOMAIN ) ?></p>
					</div>
				</div>

			</div>

			<?php
		}

		return $add_panel;

	}

	/**
	 * Check whether the current edited post is a translation
	 *
	 * @since 1.6.7
	 *
	 * @param \WP_Post $post
	 */
	public function check_product_if_translation( $post ){

		global $pagenow, $wpml_post_translations;

		if ( 'post-new.php' === $pagenow  ) {

			$source_lang = filter_var( $_GET['source_lang'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$source_lang = 'all' === $source_lang ? self::$sitepress->get_default_language() : $source_lang;
			$lang        = filter_var( $_GET['lang'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$source_lang = ! $source_lang && isset( $_GET['post'] ) && $lang !== self::$sitepress->get_default_language()
				? $wpml_post_translations->get_source_lang_code( $post->ID ) : $source_lang;

			$this->is_translation = $source_lang && $source_lang !== $lang;
		}
		else {

			$is_edit_product     = 'post.php' === $pagenow && isset( $_GET['post'] ) && 'product' === get_post_type( $_GET['post'] );
			$is_original_product = isset( $_GET['post'] ) && ! is_array( $_GET['post'] ) && $this->wpml->products->is_original_product( $_GET['post'] );

			$this->is_translation = $is_edit_product && ! $is_original_product;
		}

	}

	/**
	 * Check if a variation is a translation
	 *
	 * @since 1.6.7
	 *
	 * @param \WC_Product_Variation|\WP_Post $variation
	 */
	public function check_variation_if_translation( $variation ) {

		$this->is_translation = ! $this->wpml->products->is_original_product( $variation instanceof \WC_Product ? $variation->get_id() : $variation->ID );
	}

	/**
	 * Do upgrade tasks after ATUM's updated
	 *
	 * @since 1.4.1.2
	 *
	 * @param string $old_version Version before the upgrade tasks.
	 */
	public function upgrade( $old_version ) {
		
		global $wpdb;
		
		if ( version_compare( $old_version, '1.4.1.2', '<' ) ) {
			
			// Delete previous existent metas in translations to prevent duplicates.
			// phpcs:disable WordPress.DB.PreparedSQL
			$ids_to_delete = $wpdb->get_results( "
				SELECT tr.element_id FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->prefix}icl_translations tr
 				ON pm.post_id = tr.element_id WHERE pm.meta_key = '" . Globals::ATUM_CONTROL_STOCK_KEY . "' AND
 				NULLIF(tr.source_language_code, '') IS NOT NULL AND tr.element_type IN ('post_product', 'post_product_variation');
            ", ARRAY_N );
			// phpcs:enable
			
			if ( $ids_to_delete ) {
				
				$ids_to_delete = implode( ',', wp_list_pluck( $ids_to_delete, 0 ) );
				// phpcs:disable WordPress.DB.PreparedSQL
				$wpdb->query( "
					DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('" . Globals::ATUM_CONTROL_STOCK_KEY . "', '" . Globals::IS_INHERITABLE_KEY . "', '" . Globals::OUT_OF_STOCK_DATE_KEY . "')
 					AND post_id IN({$ids_to_delete});
                " );
				// phpcs:enable
				
			}
			
			$ids_to_refresh = $wpdb->get_results("
				SELECT DISTINCT element_id FROM {$wpdb->prefix}icl_translations
				WHERE NULLIF(source_language_code, '') IS NOT NULL AND element_type IN ('post_product', 'post_product_variation');
			", ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( $ids_to_refresh ) {

				$ids_to_refresh = wp_list_pluck( $ids_to_refresh, 0 );

				foreach ( $ids_to_refresh as $id ) {
					$original_id = self::get_original_product_id( $id );
					/* @noinspection PhpUndefinedMethodInspection */
					self::$sitepress->sync_custom_field( $original_id, $id, Globals::IS_INHERITABLE_KEY );
					/* @noinspection PhpUndefinedMethodInspection */
					self::$sitepress->sync_custom_field( $original_id, $id, Globals::ATUM_CONTROL_STOCK_KEY );
					/* @noinspection PhpUndefinedMethodInspection */
					self::$sitepress->sync_custom_field( $original_id, $id, Globals::OUT_OF_STOCK_DATE_KEY );
				}

			}
			
		}
		
		if ( version_compare( $old_version, '1.4.4', '<' ) ) {
			
			// Delete previous existent metas in translations to prevent duplicates.
			// phpcs:disable WordPress.DB.PreparedSQL
			$ids_to_delete = $wpdb->get_results( "
				SELECT DISTINCT tr.element_id FROM $wpdb->postmeta pm LEFT JOIN {$wpdb->prefix}icl_translations tr
 				ON pm.post_id = tr.element_id WHERE pm.meta_key IN ('" . Suppliers::SUPPLIER_META_KEY . "', '" . Suppliers::SUPPLIER_SKU_META_KEY . "') AND
 				NULLIF(tr.source_language_code, '') IS NOT NULL AND tr.element_type IN ('post_product', 'post_product_variation');
            ", ARRAY_N );
			// phpcs:enable
			
			if ( $ids_to_delete ) {
				
				$ids_to_delete = implode( ',', wp_list_pluck( $ids_to_delete, 0 ) );
				// phpcs:disable WordPress.DB.PreparedSQL
				$wpdb->query( "
					DELETE FROM $wpdb->postmeta WHERE meta_key IN ('" . Suppliers::SUPPLIER_META_KEY . "', '" . Suppliers::SUPPLIER_SKU_META_KEY . "')
 					AND post_id IN($ids_to_delete);
                " );
				// phpcs:enable
				
			}
			
			$ids_to_refresh = $wpdb->get_results("
				SELECT DISTINCT element_id FROM {$wpdb->prefix}icl_translations
				WHERE NULLIF(source_language_code, '') IS NOT NULL AND element_type IN ('post_product', 'post_product_variation');
			", ARRAY_N );

			if ( $ids_to_refresh ) {

				$ids_to_refresh = wp_list_pluck( $ids_to_refresh, 0 );

				foreach ( $ids_to_refresh as $id ) {
					$original_id = self::get_original_product_id( $id );
					/* @noinspection PhpUndefinedMethodInspection */
					self::$sitepress->sync_custom_field( $original_id, $id, Suppliers::SUPPLIER_META_KEY );
					/* @noinspection PhpUndefinedMethodInspection */
					self::$sitepress->sync_custom_field( $original_id, $id, Suppliers::SUPPLIER_SKU_META_KEY );
				}
			}
			
		}
		
		if ( version_compare( $old_version, '1.5.0', '<' ) ) {
			
			// Ensure all meta data in the ATUM table is properly copied to all translations.
			$product_meta_table = $wpdb->prefix . ATUM_PREFIX . 'product_data';

			// phpcs:disable WordPress.DB.PreparedSQL
			$results = $wpdb->get_results("
				SELECT DISTINCT t.trid, apd.* FROM {$wpdb->prefix}icl_translations t
				INNER JOIN $product_meta_table apd ON (t.element_id = apd.product_id)
				WHERE NULLIF(t.source_language_code, '') IS NULL AND t.element_type IN ('post_product', 'post_product_variation');
			");
			// phpcs:enable
			
			if ( $results ) {
				
				foreach ( $results as $result ) {
					
					$ids = $wpdb->get_col( $wpdb->prepare( "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid = %d", $result->trid ) );
					
					if ( $ids ) {
						
						if ( ( $key = array_search( $result->product_id, $ids ) ) !== FALSE ) {
							unset( $ids[ $key ] );
						}

						$update = "
							UPDATE $product_meta_table
							SET purchase_price = " . ( is_null( $result->purchase_price ) ? 'NULL' : $result->purchase_price ) . ',
							supplier_id =' . ( is_null( $result->supplier_id ) ? 'NULL' : $result->supplier_id ) . ',
							supplier_sku = ' . ( is_null( $result->supplier_sku ) ? 'NULL' : "'$result->supplier_sku'" ) . ',
							atum_controlled = ' . ( is_null( $result->atum_controlled ) ? 'NULL' : $result->atum_controlled ) . ',
							out_stock_date = ' . ( is_null( $result->out_stock_date ) ? 'NULL' : "'$result->out_stock_date'" ) . ',
							out_stock_threshold = ' . ( is_null( $result->out_stock_threshold ) ? 'NULL' : $result->out_stock_threshold ) . ',
							inheritable = ' . ( is_null( $result->inheritable ) ? 'NULL' : $result->inheritable ) . "
							WHERE product_id IN ('" . implode( ',', $ids ) . "');
						";
						
						$wpdb->query( $update ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						
					}
				}
			}
			
		}
		
	}

	/******************
	 * Instace methods
	 ******************/

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
	 * @return Wpml instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
