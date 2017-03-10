<?php
/**
 * @package         Atum
 * @subpackage      Components
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.1.2
 *
 * The abstract class for the ATUM admin list pages
 */

namespace Atum\Components;

defined( 'ABSPATH' ) or die;


abstract class AtumListPage {
	
	/**
	 * Table rows per page
	 * @var int
	 */
	protected $per_page;
	
	/**
	 * The list
	 * @var AtumListTable
	 */
	protected $list;
	
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
	
}