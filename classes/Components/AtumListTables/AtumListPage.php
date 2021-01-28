<?php
/**
 * The abstract class for the ATUM admin list pages
 *
 * @package         Atum\Components
 * @subpackage      AtumListTables
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.1.2
 */

namespace Atum\Components\AtumListTables;

defined( 'ABSPATH' ) || die;


abstract class AtumListPage {
	
	/**
	 * Table rows per page
	 *
	 * @var int
	 */
	protected $per_page;
	
	/**
	 * The list
	 *
	 * @var AtumListTable
	 */
	protected $list;

	/**
	 * Whether the currently displayed List Table is showing Controlled or Uncontrolled products
	 *
	 * @var bool
	 */
	protected $is_uncontrolled_list = FALSE;
	
	/**
	 * Initialize common hooks
	 *
	 * @since 1.1.2
	 */
	protected function init_hooks() {
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
	}
	
	/**
	 * Display the admin list page
	 *
	 * @since 1.1.2
	 */
	protected function display() {
		$this->list->prepare_items();
	}

	/**
	 * Save products per page option
	 *
	 * @since 1.1.2
	 *
	 * @param bool|int $status Screen option value. Default false to skip.
	 * @param string   $option The option name.
	 * @param int      $value  The number of rows to use.
	 *
	 * @return mixed
	 */
	public function set_screen_option( $status, $option, $value ) {
		return $value;
	}

	/**
	 * Enable Screen options creating the list table before the Screen option panel is rendered and enable
	 * "per page" option. Also add help tabs and help sidebar
	 *
	 * @since 1.1.2
	 */
	abstract public function screen_options();

	/**
	 * Setter for the AtumListTable object
	 *
	 * @since 1.2.5
	 *
	 * @param AtumListTable $list
	 */
	public function set_list_table( AtumListTable $list ) {

		if ( $list instanceof AtumListTable ) {
			$this->list = $list;
		}

	}

}
