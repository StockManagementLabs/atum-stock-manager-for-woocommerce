<?php
/**
 * @package         Atum\StockCentral
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.4.1
 *
 * List Table for the products not controlled by ATUM
 */

namespace Atum\StockCentral\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumListTables\AtumUncontrolledListTable;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Modules\ModuleManager;


class UncontrolledListTable extends AtumUncontrolledListTable {

	/**
	 * Whether to load the jQuery UI datepicker script (for sale price dates)
	 * @var bool
	 */
	protected $load_datepicker = TRUE;

	/**
	 * @inheritdoc
	 */
	public function __construct( $args = array() ) {

		$this->taxonomies[] = array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => Globals::get_product_types()
		);

		// NAMING CONVENTION: The column names starting by underscore (_) are based on meta keys (the name must match the meta key name),
		// the column names starting with "calc_" are calculated fields and the rest are WP's standard fields
		// *** Following this convention is necessary for column sorting functionality ***
		$args['table_columns'] = array(
			'thumb'                  => '<span class="wc-image tips" data-toggle="tooltip" data-placement="bottom" title="' . __( 'Image', ATUM_TEXT_DOMAIN ) . '">' . __( 'Thumb', ATUM_TEXT_DOMAIN ) . '</span>',
			'title'                  => __( 'Product Name', ATUM_TEXT_DOMAIN ),
			'_supplier'              => __( 'Supplier', ATUM_TEXT_DOMAIN ),
			'_sku'                   => __( 'SKU', ATUM_TEXT_DOMAIN ),
			'_supplier_sku'          => __( 'Supplier SKU', ATUM_TEXT_DOMAIN ),
			'ID'                     => __( 'ID', ATUM_TEXT_DOMAIN ),
			'calc_type'              => '<span class="wc-type tips" data-toggle="tooltip" data-placement="bottom" title="' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '">' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '</span>',
			'_regular_price'         => __( 'Regular Price', ATUM_TEXT_DOMAIN ),
			'_sale_price'            => __( 'Sale Price', ATUM_TEXT_DOMAIN ),
			'_purchase_price'        => __( 'Purchase Price', ATUM_TEXT_DOMAIN )
		);

		// Hide the purchase price column if the current user has not the capability
		if ( ! AtumCapabilities::current_user_can('view_purchase_price') || ! ModuleManager::is_module_active('purchase_orders') ) {
			unset( $args['table_columns']['_purchase_price'] );
		}

		// Hide the supplier's columns if the current user has not the capability
		if ( ! ModuleManager::is_module_active('purchase_orders') || ! AtumCapabilities::current_user_can('read_supplier') ) {
			unset( $args['table_columns']['_supplier'] );
			unset( $args['table_columns']['_supplier_sku'] );
		}

		$args['table_columns'] = (array) apply_filters( 'atum/uncontrolled_stock_central_list/table_columns', $args['table_columns'] );

		$args['group_members'] = (array) apply_filters( 'atum/uncontrolled_stock_central_list/column_group_members', array(
			'product-details'       => array(
				'title'   => __( 'Product Details', ATUM_TEXT_DOMAIN ),
				'members' => array(
					'thumb',
					'title',
					'_supplier',
					'_sku',
					'_supplier_sku',
					'ID',
					'calc_type',
					'_regular_price',
					'_sale_price',
					'_purchase_price'
				)
			)
		) );
		
		parent::__construct( $args );
		
	}

	/**
	 * @inheritdoc
	 */
	protected function get_table_classes() {

		$table_classes = parent::get_table_classes();
		$table_classes[] = 'stock-central-list';

		return $table_classes;
	}
	
	/**
	 * Column for regular price
	 *
	 * @since  1.4.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return float
	 */
	protected function column__regular_price( $item ) {

		$regular_price = self::EMPTY_COL;
		$product_id = $this->get_current_product_id();
		
		if ( $this->allow_calcs ) {
			
			if ( !empty($this->custom_prices[$this->current_currency]) ) {
				
				$currency            = $this->current_currency;
				$regular_price_value = $this->custom_prices[ $currency ]['custom_price']['_regular_price'];
				$symbol              = $this->custom_prices[ $currency ]['currency_symbol'];
				$is_custom           = 'yes';
			
			}
			else {

				// WPML Multicurrency
				if ($this->is_wpml_multicurrency && $product_id !== $this->original_product_id) {
					$product = wc_get_product($this->original_product_id);
					$regular_price_value =  $product->get_regular_price();
				}
				else {
					$regular_price_value = $this->product->get_regular_price();
				}

				$symbol = get_woocommerce_currency_symbol();
				$currency = $this->default_currency;
				$is_custom = 'no';

			}
			
			$regular_price_value = ( is_numeric( $regular_price_value ) ) ? Helpers::format_price( $regular_price_value, [ 'trim_zeros' => TRUE, 'currency' => $currency] ) : $regular_price;
			
			$args = array(
				'post_id'  => $product_id,
				'meta_key' => 'regular_price',
				'value'    => $regular_price_value,
				'symbol'    => $symbol,
				'currency'  => $currency,
				'is_custom' => $is_custom,
				'tooltip'  => __( 'Click to edit the regular price', ATUM_TEXT_DOMAIN )
			);
			
			$regular_price = $this->get_editable_column( $args );
			
		}

		return apply_filters( 'atum/uncontrolled_stock_central_list/column_regular_price', $regular_price, $item, $this->product );
		
	}

	/**
	 * Column for sale price
	 *
	 * @since  1.4.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return float
	 */
	protected function column__sale_price( $item ) {

		$sale_price = self::EMPTY_COL;
		$product_id = $this->get_current_product_id();
		
		if ( $this->allow_calcs ) {

			if ( ! empty( $this->custom_prices[ $this->current_currency ] ) ) {

				$currency         = $this->current_currency;
				$sale_price_value = $this->custom_prices[ $currency ]['custom_price']['_sale_price'];
				$symbol           = $this->custom_prices[ $currency ]['currency_symbol'];

				// Dates come already formatted
				$sale_price_dates_from = $this->custom_prices[ $currency ]['sale_price_dates_from'];
				$sale_price_dates_to   = $this->custom_prices[ $currency ]['sale_price_dates_to'];
				$is_custom             = 'yes';

			}
			else {

				// WPML Multicurrency
				if ( $this->is_wpml_multicurrency && $product_id !== $this->original_product_id ) {
					$product               = wc_get_product( $this->original_product_id );
					$sale_price_value      = $product->get_sale_price();
					$sale_price_dates_from = ( $date = get_post_meta( $this->original_product_id, '_sale_price_dates_from', TRUE ) ) ? date_i18n( 'Y-m-d', $date ) : '';
					$sale_price_dates_to   = ( $date = get_post_meta( $this->original_product_id, '_sale_price_dates_to', TRUE ) ) ? date_i18n( 'Y-m-d', $date ) : '';
				}
				else {
					$sale_price_value      = $this->product->get_sale_price();
					$sale_price_dates_from = ( $date = get_post_meta( $product_id, '_sale_price_dates_from', TRUE ) ) ? date_i18n( 'Y-m-d', $date ) : '';
					$sale_price_dates_to   = ( $date = get_post_meta( $product_id, '_sale_price_dates_to', TRUE ) ) ? date_i18n( 'Y-m-d', $date ) : '';
				}

				$symbol    = get_woocommerce_currency_symbol();
				$currency  = $this->default_currency;
				$is_custom = 'no';

			}
			
			$sale_price_value = ( is_numeric( $sale_price_value ) ) ? Helpers::format_price( $sale_price_value, [ 'trim_zeros' => TRUE, 'currency' => $currency ] ) : $sale_price;
			
			$args = array(
				'post_id'    => $product_id,
				'meta_key'   => 'sale_price',
				'value'      => $sale_price_value,
				'symbol'     => $symbol,
				'currency'   => $currency,
				'is_custom'  => $is_custom,
				
				'tooltip'    => __( 'Click to edit the sale price', ATUM_TEXT_DOMAIN ),
				'extra_meta' => array(
					array(
						'name'        => '_sale_price_dates_from',
						'type'        => 'text',
						'placeholder' => _x( 'Sale date from...', 'placeholder', ATUM_TEXT_DOMAIN ) . ' YYYY-MM-DD',
						'value'       => $sale_price_dates_from,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'datepicker from'
					),
					array(
						'name'        => '_sale_price_dates_to',
						'type'        => 'text',
						'placeholder' => _x( 'Sale date to...', 'placeholder', ATUM_TEXT_DOMAIN ) . ' YYYY-MM-DD',
						'value'       => $sale_price_dates_to,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'datepicker to'
					)
				)
			);
			
			$sale_price = $this->get_editable_column( $args );
			
		}

		return apply_filters( 'atum/uncontrolled_stock_central_list/column_sale_price', $sale_price, $item, $this->product );

	}
	
}