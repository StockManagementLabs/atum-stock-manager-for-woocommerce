<?php
/**
 * @package        Atum
 * @subpackage     Inc
 * @author         Salva Machí and Jose Piera - https://sispixels.com
 * @copyright      (c)2017 Stock Management Labs
 *
 * @since          0.0.1
 *
 * Ajax callbacks
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumListTable;
use Atum\Settings\Settings;
use Atum\StockCentral\Inc\StockCentralList;


final class Ajax {
	
	/**
	 * The singleton instance holder
	 * @var Ajax
	 */
	private static $instance;
	
	private function __construct() {
		
		// Ajax callback for Stock Central List
		add_action( 'wp_ajax_atum_fetch_stock_central_list', array( $this, 'fetch_stock_central_list' ) );
		
		// Ajax callback for Management Stock notice
		add_action( 'wp_ajax_atum_manage_stock_notice', array( $this, 'manage_stock_notice' ) );
	}
	
	/**
	 * Loads the Stock Central ListTable class and calls ajax_response method
	 *
	 * @since 0.0.1
	 */
	public function fetch_stock_central_list() {
		
		check_ajax_referer( 'atum-post-type-table-nonce', 'token' );
		
		//pro version
		//$selected = Sanitize::sanitize_post_type_selector($_REQUEST['selected']);
		
		$per_page = ( ! empty($_REQUEST['per_page']) ) ? absint( $_REQUEST['per_page'] ) : Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE );
		
		do_action( 'atum/ajax/stock_central_list/before_fetch_stock', $this );
		
		$list = new StockCentralList( compact('per_page') );
		$list->ajax_response();
		
	}
	
	/**
	 * Handle the ajax requests send by the Atum List table notices
	 *
	 * @since 0.1.0
	 */
	public function manage_stock_notice() {
		
		check_ajax_referer( ATUM_PREFIX . 'manage-stock-notice', 'token' );
		
		$action = ( ! empty($_POST['data']) ) ? $_POST['data'] : '';
		
		// Enable stock management
		if ($action == 'manage') {
			Helpers::update_option('manage_stock', 'yes');
		}
		// Dismiss the notice permanently
		elseif ( $action == 'dismiss') {
			update_user_meta(get_current_user_id(), AtumListTable::DISMISS_MANAGE_STOCK, 'yes');
		}
		
		wp_die();
		
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
	 * @static
	 * @return Ajax instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}