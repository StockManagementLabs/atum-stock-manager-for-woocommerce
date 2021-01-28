<?php
/**
 * The ATUM Logging system component
 *
 * @package     Components\AtumLogs
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @since       1.4.15
 */

namespace Atum\Components\AtumLogs;

defined( 'ABSPATH' ) || die;

class AtumLogs {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumLogs
	 */
	private static $instance;

	/**
	 * Status of unread logs
	 */
	const UNREAD_STATUS = 0;

	/**
	 * Status of read logs
	 */
	const READ_STATUS = 1;

	/**
	 * Status of featured logs
	 */
	const FEATURED_STATUS = 3;

	/**
	 * Status of deleted logs
	 */
	const DELETED_STATUS = 4;


	/**
	 * The Log table name
	 *
	 * @var string
	 */
	private static $log_table = ATUM_PREFIX . 'log';


	/**
	 * AtumLogs singleton constructor
	 *
	 * @since 1.4.15
	 */
	private function __construct() {}

	/**
	 * Getter for the Log table name
	 *
	 * @return string
	 */
	public static function get_log_table() {
		return self::$log_table;
	}



	/*******************
	 * Instance methods
	 *******************/

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
	 * @return AtumLogs instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
