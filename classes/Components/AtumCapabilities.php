<?php
/**
 * @package        Atum
 * @subpackage     Components
 * @author         Salva Machí and Jose Piera - https://sispixels.com
 * @copyright      ©2017 Stock Management Labs™
 *
 * @since          1.3.1
 *
 * Add capabilities to WP user roles
 */

namespace Atum\Components;

defined( 'ABSPATH' ) or die;


class AtumCapabilities {

	/**
	 * The singleton instance holder
	 * @var AtumCapabilities
	 */
	private static $instance;

	/**
	 * List of custom ATUM capabilities
	 * @var array
	 */
	private $capabilities = array(
		ATUM_PREFIX . 'edit_purchase_price',
		ATUM_PREFIX . 'view_purchase_price',
		ATUM_PREFIX . 'manage_po',
		ATUM_PREFIX . 'view_inbound_stock'
	);

	/**
	 * Singleton constructor
	 *
	 * @since 1.3.1
	 */
	private function __construct() {
		add_action( 'admin_init', array($this, 'add_capabilities') );
	}

	/**
	 * Add the ATUM capabilities to admins
	 *
	 * @since 1.3.1
	 */
	public function add_capabilities() {

		$admin_role = get_role( 'administrator' );

		if ( is_a($admin_role, '\WP_Role') ) {
			foreach ( $this->capabilities as $cap ) {
				$admin_role->add_cap( $cap );
			}
		}

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
	 * @return AtumCapabilities instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}