<?php
/**
 * List Table for the products not controlled by ATUM
 *
 * @package         Atum\Components
 * @subpackage      AtumListTables
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.4.1
 */

namespace Atum\Components\AtumListTables;

defined( 'ABSPATH' ) || die;

class AtumUncontrolledListTable extends AtumListTable {

	/**
	 * Whether to show the totals row
	 *
	 * @var bool
	 */
	protected $show_totals = FALSE;

	/**
	 * AtumUncontrolledListTable Constructor
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
		
		parent::__construct( $args );
		
	}

	/**
	 * Get an associative array ( id => link ) with the list of available views on this table.
	 *
	 * @since 1.3.0
	 *
	 * @return array
	 */
	protected function get_views() {

		$views = parent::get_views();
		unset( $views['in_stock'], $views['low_stock'], $views['out_stock'], $views['unmanaged'], $views['back_order'] );

		return $views;
	}

	/**
	 * Add the filters to the table nav
	 *
	 * @since 1.3.0
	 */
	protected function table_nav_filters() {
		parent::table_nav_filters();
	}
	
	/**
	 * Prepare the table data
	 *
	 * @since  1.4.1
	 */
	public function prepare_items() {
		parent::prepare_items();
	}
	
}
