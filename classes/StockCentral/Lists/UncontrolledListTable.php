<?php
/**
 * List Table for the products not controlled by ATUM
 *
 * @package         Atum\StockCentral
 * @subpackage      Lists
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.4.1
 */

namespace Atum\StockCentral\Lists;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumListTables\AtumUncontrolledListTable;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Modules\ModuleManager;


class UncontrolledListTable extends AtumUncontrolledListTable {

	/**
	 * Whether to load the jQuery UI datepicker script (for sale price dates)
	 *
	 * @var bool
	 */
	protected $load_datepicker = TRUE;

	/**
	 * UncontrolledListTable Constructor
	 *
	 * The child class should call this constructor from its own constructor to override the default $args
	 *
	 * @since 1.4.1
	 *
	 * @param array|string $args          {
	 *      Array or string of arguments.
	 *
	 *      @type array  $table_columns     The table columns for the list table
	 *      @type array  $group_members     The column grouping members
	 *      @type bool   $show_cb           Optional. Whether to show the row selector checkbox as first table column
	 *      @type bool   $show_controlled   Optional. Whether to show items controlled by ATUM or not
	 *      @type int    $per_page          Optional. The number of posts to show per page (-1 for no pagination)
	 *      @type array  $selected          Optional. The posts selected on the list table
	 *      @type array  $excluded          Optional. The posts excluded from the list table
	 * }
	 */
	public function __construct( $args = array() ) {

		$this->taxonomies[] = array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => Globals::get_product_types(),
		);

		// NAMING CONVENTION: The column names starting by underscore (_) are based on meta keys (the name must match the meta key name),
		// the column names starting with "calc_" are calculated fields and the rest are WP's standard fields
		// *** Following this convention is necessary for column sorting functionality ***!
		$args['table_columns'] = array(
			'thumb'           => '<span class="wc-image tips" data-toggle="tooltip" data-placement="bottom" title="' . __( 'Image', ATUM_TEXT_DOMAIN ) . '">' . __( 'Thumb', ATUM_TEXT_DOMAIN ) . '</span>',
			'title'           => __( 'Name', ATUM_TEXT_DOMAIN ),
			'_supplier'       => __( 'Supplier', ATUM_TEXT_DOMAIN ),
			'_sku'            => __( 'SKU', ATUM_TEXT_DOMAIN ),
			'_supplier_sku'   => __( 'Sup. SKU', ATUM_TEXT_DOMAIN ),
			'ID'              => __( 'ID', ATUM_TEXT_DOMAIN ),
			'calc_type'       => '<span class="wc-type tips" data-toggle="tooltip" data-placement="bottom" title="' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '">' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '</span>',
			'_regular_price'  => __( 'Regular Price', ATUM_TEXT_DOMAIN ),
			'_sale_price'     => __( 'Sale Price', ATUM_TEXT_DOMAIN ),
			'_purchase_price' => __( 'Purchase Price', ATUM_TEXT_DOMAIN ),
		);

		// Hide the purchase price column if the current user has not the capability.
		if ( ! AtumCapabilities::current_user_can( 'view_purchase_price' ) || ! ModuleManager::is_module_active( 'purchase_orders' ) ) {
			unset( $args['table_columns']['_purchase_price'] );
		}

		// Hide the supplier's columns if the current user has not the capability.
		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) || ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			unset( $args['table_columns']['_supplier'] );
			unset( $args['table_columns']['_supplier_sku'] );
		}

		$args['table_columns'] = (array) apply_filters( 'atum/uncontrolled_stock_central_list/table_columns', $args['table_columns'] );

		$args['group_members'] = (array) apply_filters( 'atum/uncontrolled_stock_central_list/column_group_members', array(
			'product-details' => array(
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
					'_purchase_price',
				),
			),
		) );
		
		parent::__construct( $args );
		
	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag. Deleted 'fixed' from standard function
	 *
	 * @since  0.0.2
	 *
	 * @return array List of CSS classes for the table tag
	 */
	protected function get_table_classes() {

		$table_classes   = parent::get_table_classes();
		$table_classes[] = 'stock-central-list';

		return $table_classes;
	}
	
	/**
	 * Column for regular price
	 *
	 * @since  1.4.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return float
	 */
	protected function column__regular_price( $item ) {

		$regular_price = self::EMPTY_COL;
		
		if ( $this->allow_calcs ) {
			
			$regular_price_value = $this->product->get_regular_price();
			$regular_price_value = ( is_numeric( $regular_price_value ) ) ? Helpers::format_price( $regular_price_value, [
				'trim_zeros' => TRUE,
				'currency'   => self::$default_currency,
			] ) : $regular_price;
			
			$args = apply_filters( 'atum/uncontrolled_stock_central_list/args_regular_price', array(
				'meta_key' => 'regular_price',
				'value'    => $regular_price_value,
				'symbol'   => get_woocommerce_currency_symbol(),
				'currency' => self::$default_currency,
				'tooltip'  => esc_attr__( 'Click to edit the regular price', ATUM_TEXT_DOMAIN ),
			) );
			
			$regular_price = self::get_editable_column( $args );
			
		}

		return apply_filters( 'atum/uncontrolled_stock_central_list/column_regular_price', $regular_price, $item, $this->product );
		
	}

	/**
	 * Column for sale price
	 *
	 * @since  1.4.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return float
	 */
	protected function column__sale_price( $item ) {

		$sale_price = self::EMPTY_COL;
		$product_id = $this->get_current_product_id();
		
		if ( $this->allow_calcs ) {
			
			$sale_price_value = $this->product->get_sale_price();
			$sale_price_value = is_numeric( $sale_price_value ) ? Helpers::format_price( $sale_price_value, [
				'trim_zeros' => TRUE,
				'currency'   => self::$default_currency,
			] ) : $sale_price;
			
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInTernaryCondition
			$sale_price_dates_from = ( $date = get_post_meta( $product_id, '_sale_price_dates_from', TRUE ) ) ? date( 'Y-m-d', $date ) : '';
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInTernaryCondition
			$sale_price_dates_to = ( $date = get_post_meta( $product_id, '_sale_price_dates_to', TRUE ) ) ? date( 'Y-m-d', $date ) : '';
			
			$args = apply_filters( 'atum/uncontrolled_stock_central_list/args_sale_price', array(
				'meta_key'   => 'sale_price',
				'value'      => $sale_price_value,
				'symbol'     => get_woocommerce_currency_symbol(),
				'currency'   => self::$default_currency,
				'tooltip'    => esc_attr__( 'Click to edit the sale price', ATUM_TEXT_DOMAIN ),
				'extra_meta' => array(
					array(
						'name'        => '_sale_price_dates_from',
						'type'        => 'text',
						'placeholder' => _x( 'Sale date from...', 'placeholder', ATUM_TEXT_DOMAIN ) . ' YYYY-MM-DD',
						'value'       => $sale_price_dates_from,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'datepicker from',
					),
					array(
						'name'        => '_sale_price_dates_to',
						'type'        => 'text',
						'placeholder' => _x( 'Sale date to...', 'placeholder', ATUM_TEXT_DOMAIN ) . ' YYYY-MM-DD',
						'value'       => $sale_price_dates_to,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'datepicker to',
					),
				),
			) );
			
			$sale_price = self::get_editable_column( $args );
			
		}

		return apply_filters( 'atum/uncontrolled_stock_central_list/column_sale_price', $sale_price, $item, $this->product );

	}
	
}
