<?php
/**
 * List Table for the products not controlled by ATUM
 *
 * @package         Atum\StockCentral
 * @subpackage      Lists
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.4.1
 */

namespace Atum\StockCentral\Lists;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumListTables\AtumUncontrolledListTable;
use Atum\Modules\ModuleManager;


class UncontrolledListTable extends AtumUncontrolledListTable {

	/**
	 * What columns are numeric and searchable? and strings? append to this two keys
	 *
	 * @var array
	 */
	protected $searchable_columns = array(
		'string'  => array(
			'title',
			'_supplier',
			'_sku',
			'_supplier_sku',
			'IDs', // ID as string to allow the use of commas ex: s = '12, 13, 89'.
		),
		'numeric' => array(
			'ID',
			'_regular_price',
			'_sale_price',
			'_purchase_price',
		),
	);

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
	 *      @type bool   $show_cb           Optional. Whether to show the row selector checkbox as first table column.
	 *      @type bool   $show_controlled   Optional. Whether to show items controlled by ATUM or not.
	 *      @type int    $per_page          Optional. The number of posts to show per page (-1 for no pagination).
	 *      @type array  $selected          Optional. The posts selected on the list table.
	 *      @type array  $excluded          Optional. The posts excluded from the list table.
	 * }
	 */
	public function __construct( $args = array() ) {

		// NAMING CONVENTION: The column names starting by underscore (_) are based on meta keys (the name must match the meta key name),
		// the column names starting with "calc_" are calculated fields and the rest are WP's standard fields
		// *** Following this convention is necessary for column sorting functionality ***!
		self::$table_columns = array(
			'thumb'           => '<span class="wc-image tips" data-bs-placement="bottom" data-tip="' . __( 'Image', ATUM_TEXT_DOMAIN ) . '">' . __( 'Thumb', ATUM_TEXT_DOMAIN ) . '</span>',
			'title'           => __( 'Name', ATUM_TEXT_DOMAIN ),
			'_supplier'       => __( 'Supplier', ATUM_TEXT_DOMAIN ),
			'_sku'            => __( 'SKU', ATUM_TEXT_DOMAIN ),
			'_supplier_sku'   => __( 'Sup. SKU', ATUM_TEXT_DOMAIN ),
			'ID'              => __( 'ID', ATUM_TEXT_DOMAIN ),
			'calc_type'       => '<span class="wc-type tips" data-bs-placement="bottom" data-tip="' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '">' . __( 'Product Type', ATUM_TEXT_DOMAIN ) . '</span>',
			'_regular_price'  => __( 'Regular Price', ATUM_TEXT_DOMAIN ),
			'_sale_price'     => __( 'Sale Price', ATUM_TEXT_DOMAIN ),
			'_purchase_price' => __( 'Purchase Price', ATUM_TEXT_DOMAIN ),
		);

		// By default, we have no actions for SC but we have to add the ability to add them externally.
		self::$row_actions = (array) apply_filters( 'atum/uncontrolled_stock_central_list/row_actions', [] );

		// Hide the purchase price column if the current user has not the capability.
		if ( ! AtumCapabilities::current_user_can( 'view_purchase_price' ) || ! ModuleManager::is_module_active( 'purchase_orders' ) ) {
			unset( self::$table_columns['_purchase_price'] );
		}

		// Hide the supplier's columns if the current user has not the capability.
		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) || ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			unset( self::$table_columns['_supplier'] );
			unset( self::$table_columns['_supplier_sku'] );
		}

		self::$table_columns = (array) apply_filters( 'atum/uncontrolled_stock_central_list/table_columns', self::$table_columns );

		$this->group_members = (array) apply_filters( 'atum/uncontrolled_stock_central_list/column_group_members', array(
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
	
}
