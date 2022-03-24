<?php
/**
 * Handles the ATUM Notifications
 *
 * @oackage     Notifications
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @since 1.9.1
 */

namespace Atum\Components;

use Atum\Inc\Helpers as AtumHelpers;


defined( 'ABSPATH' ) || die;


class AtumNotifications {

	/**
	 * The singleton instance holder.
	 *
	 * @var AtumNotifications
	 */
	private static $instance;

	/**
	 * List of notifications handled by ATUM.
	 *
	 * @var array $notifications
	 */
	private $notifications = array();

	/**
	 * Singleton constructor
	 *
	 * @since 1.9.1
	 */
	private function __construct() {

		// Prepare notifications.
		add_action( 'init', array( $this, 'prepare_notifications' ) );

		// Add email classes.
		add_filter( 'woocommerce_email_classes', array( $this, 'init_classes' ) );

		// Add settings.
		add_filter( 'atum/settings/tabs', array( $this, 'add_settings_tab' ), 11 );
		add_filter( 'atum/settings/defaults', array( $this, 'add_settings_defaults' ), 11 );

	}

	/**
	 * Initialize notifications.
	 *
	 * @since 1.9.1
	 */
	public function prepare_notifications() {

		$notifications = apply_filters( 'atum/notifications_list', $this->notifications );

		$defaults = array(
			'id'      => '',
			'name'    => '',
			'desc'    => '',
			'default' => 'yes',
			'type'    => 'switcher',
			'class'   => '',
		);

		$parsed_notifications = array();

		if ( ! empty( $notifications ) ) {
			foreach ( $notifications as $notification ) {
				$parsed_notifications[] = array_merge( $defaults, $notification );
			}
		}

		$this->notifications = $parsed_notifications;
	}

	/**
	 * Add AtumNotifications classes to WC Emails classes list.
	 *
	 * @since 1.9.1
	 *
	 * @param  array $emails_classes
	 * @return array
	 */
	public function init_classes( $emails_classes ) {

		if ( ! empty( $this->notifications ) ) {

			foreach ( $this->notifications as $notification ) {
				$index = $notification['id'];

				if ( 'yes' === AtumHelpers::get_option( $index, 'yes' ) ) {
					$class                    = $notification['class'];
					$emails_classes[ $index ] = new $class();
				}
			}
		}

		return $emails_classes;
	}

	/**
	 * Add a new tab to the ATUM settings page
	 *
	 * @since 1.9.1
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_settings_tab( $tabs ) {

		if ( ! empty( $this->notifications ) ) {

			$tabs['notifications'] = array(
				'label'    => __( 'Notifications', ATUM_TEXT_DOMAIN ),
				'icon'     => 'atmi-alarm',
				'sections' => array(
					'atum_notifications' => __( 'ATUM Notifications', ATUM_TEXT_DOMAIN ),
				),
			);

		}

		return $tabs;
	}

	/**
	 * Add fields to the ATUM settings page
	 *
	 * @since 1.9.1
	 *
	 * @param array $defaults
	 * @return array
	 */
	public function add_settings_defaults( $defaults ) {

		if ( ! empty( $this->notifications ) ) {

			foreach ( $this->notifications as $notification ) {
				$index = $notification['id'];

				$defaults[ $index ] = array(
					'group'   => 'notifications',
					'section' => 'atum_notifications',
					'name'    => $notification['name'],
					'desc'    => $notification['desc'],
					'type'    => $notification['type'],
					'default' => $notification['default'],
				);
			}

		}

		return $defaults;
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
	 * @return AtumNotifications instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
