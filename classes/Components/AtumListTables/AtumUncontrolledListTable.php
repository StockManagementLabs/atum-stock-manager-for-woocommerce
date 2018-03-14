<?php
/**
 * @package         Atum\Components
 * @subpackage      AtumListTables
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.4.1
 *
 * List Table for the products not controlled by ATUM
 */

namespace Atum\Components\AtumListTables;

defined( 'ABSPATH' ) or die;

class AtumUncontrolledListTable extends AtumListTable {

	/**
	 * @inheritdoc
	 */
	public function __construct( $args = array() ) {
		
		parent::__construct( $args );

		// Add the "Apply Bulk Action" button to the title section
		add_action( 'atum/list_table/page_title_buttons', array( $this, 'add_apply_bulk_action_button' ) );
		
	}

	/**
	 * @inheritdoc
	 */
	protected function get_views() {

		$views = parent::get_views();
		unset($views['in_stock'], $views['low_stock'], $views['out_stock']);

		return $views;
	}

	/**
	 * @inheritdoc
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