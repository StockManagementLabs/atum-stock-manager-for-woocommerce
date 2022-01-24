<?php
/**
 * The abstract class for the ATUM admin list pages
 *
 * @package         Atum\Components
 * @subpackage      AtumListTables
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.1.2
 */

namespace Atum\Components\AtumListTables;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;
use Atum\Settings\Settings;

abstract class AtumListPage {
	
	/**
	 * Entries per page
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
	 * Get the URL for the current List Table page.
	 *
	 * @since 1.9.6
	 *
	 * @return string
	 */
	public function get_list_table_page_url() {
		return defined( 'static::UI_SLUG' ) ? add_query_arg( 'page', static::UI_SLUG, admin_url( 'admin.php' ) ) : '';
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

	/**
	 * Setter the entries per page
	 *
	 * @since 1.9.7
	 *
	 * @param string $default_option_key
	 */
	protected function set_per_page( $default_option_key = 'posts_per_page' ) {

		// Already set?
		if ( ! is_null( $this->per_page ) ) {
			return;
		}

		$user_option = 0;

		if ( defined( 'static::UI_SLUG' ) ) {
			// The screen options is replacing hyphens by underscores before saving the meta key.
			$user_meta_key = str_replace( '-', '_', static::UI_SLUG . '_entries_per_page' );
			$user_option   = get_user_meta( get_current_user_id(), $user_meta_key, TRUE );
		}

		$this->per_page = (int) $user_option ?: Helpers::get_option( $default_option_key, Settings::DEFAULT_POSTS_PER_PAGE );

	}

}
