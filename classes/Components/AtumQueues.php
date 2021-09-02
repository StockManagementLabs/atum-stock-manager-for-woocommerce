<?php
/**
 * Handles the ATUM queues and recurring jobs
 * It uses the WC_Queue that inherits from Action Scheduler: https://actionscheduler.org/
 *
 * @package     Components
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @since       1.5.8
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;

class AtumQueues {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumQueues
	 */
	private static $instance;

	/**
	 * The transient key for checking async queues availability
	 *
	 * @var string
	 */
	private static $async_available_transient = 'async_available_atum';

	/**
	 * Hooks that are executed in a recurring way and including the time when they run + the interval
	 *
	 * @var array
	 */
	private $recurring_hooks = array(
		'atum/update_expiring_product_props' => [
			'time'     => 'midnight tomorrow',
			'interval' => DAY_IN_SECONDS,
		],
	);

	/**
	 * Hooks that are triggered just one time asychronously
	 *
	 * @var array
	 */
	private static $async_hooks = array();

	/**
	 * AtumQueues singleton constructor
	 *
	 * @since 1.5.8
	 */
	private function __construct() {

		add_action( 'init', array( $this, 'check_queues' ) );

		// Add the ATUM's recurring hooks.
		add_action( 'atum/update_expiring_product_props', array( $this, 'update_expiring_product_props_action' ) );

		// Add the ATUM Queues async hooks listeners.
		add_action( 'wp_ajax_atum_async_hooks', array( $this, 'handle_async_hooks' ) );
		add_action( 'wp_ajax_nopriv_atum_async_hooks', array( $this, 'handle_async_hooks' ) );

	}

	/**
	 * Schedule the recurring hooks that are not in the queue.
	 *
	 * @since 1.5.8
	 */
	public function check_queues() {

		$wc = WC();

		// Ensure that the current WC version supports queues.
		if ( ! is_callable( array( $wc, 'queue' ) ) ) {
			return;
		}

		$wc_queue = $wc->queue();

		// Allow registering queues externally.
		$this->recurring_hooks = apply_filters( 'atum/queues/recurring_hooks', $this->recurring_hooks );

		foreach ( $this->recurring_hooks as $hook_name => $hook_data ) {

			$schedule_args       = isset( $hook_data['args'] ) && is_array( $hook_data['args'] ) ? $hook_data['args'] : [];
			$next_scheduled_date = $wc_queue->get_next( $hook_name, $schedule_args );

			if ( is_null( $next_scheduled_date ) ) {
				$wc_queue->cancel_all( $hook_name, $schedule_args ); // Ensure all the actions are cancelled before adding a new one.
				$wc_queue->schedule_recurring( strtotime( $hook_data['time'] ), $hook_data['interval'], $hook_name, $schedule_args );
			}

		}

	}

	/**
	 * Recalculate the expiring props for all the products
	 *
	 * @since 1.5.8
	 */
	public function update_expiring_product_props_action() {

		// Get all the products that weren't updated during the last 3 hours.
		global $wpdb;

		$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$date_max                = gmdate( 'Y-m-d H:i:s', strtotime( '3 hours ago' ) );

		// phpcs:disable
		$outdated_products = $wpdb->get_col( $wpdb->prepare( "
			SELECT product_id FROM $atum_product_data_table
			WHERE update_date <= %s OR update_date IS NULL
			ORDER BY update_date
		", $date_max ) );
		// phpcs:enable

		// TODO: WHAT ABOUT ILs AND PLs PROPS? IS UPDATING THEM ALSO?
		foreach ( $outdated_products as $product_id ) {
			AtumCalculatedProps::defer_update_atum_sales_calc_props( $product_id );
		}

	}

	/**
	 * Defer an action to run one time on the WP's 'shutodown' action.
	 *
	 * @since 1.7.3
	 *
	 * @param string   $hook     The hook name to call after shutdown.
	 * @param callable $callback A callable function or method.
	 * @param array    $params   Optional. Any params that need to be passed to the async action. These params will be unpacked with the spread operator.
	 * @param int      $priority Default to 10.
	 */
	public static function add_async_action( $hook, $callback, $params = array(), $priority = 10 ) {

		// NOTE: For now we only allow unique calls to the same hook.
		self::$async_hooks[ $priority ][ $hook ] = array(
			'callback' => $callback,
			'params'   => $params,
		);

		// Ensure that we add the action only once.
		if ( ! has_action( 'shutdown', array( self::get_instance(), 'trigger_async_actions' ) ) ) {
			add_action( 'shutdown', array( self::get_instance(), 'trigger_async_actions' ) );
		}

	}

	/**
	 * Trigger all the registered async actions. Caution!!: as it's a remote post, some variables must be serialized (e.g. an object).
	 *
	 * @since 1.7.3
	 */
	public function trigger_async_actions() {

		// Avoid unending loops when the current request is already coming from an async action.
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) && self::get_async_request_user_agent() === $_SERVER['HTTP_USER_AGENT'] ) {
			return;
		}

		if ( ! empty( self::$async_hooks ) ) {

			ksort( self::$async_hooks, SORT_NUMERIC );

			// Make a synchronous request if the checker was not able to make a remote request.
			if ( ! self::check_async_request() ) {

				// The tasks must be executed with a clean ATUM cache (as if they're executed by a remote post).
				AtumCache::delete_all_atum_caches();

				foreach ( self::$async_hooks as $priority_group ) {

					foreach ( $priority_group as $hook_data ) {

						if ( is_callable( $hook_data['callback'] ) ) {
							call_user_func( $hook_data['callback'], ...$hook_data['params'] );
						}
					}

				}

			}
			else {

				$data = [
					'action'     => 'atum_async_hooks',
					'token'      => wp_create_nonce( 'atum_async_hooks' ),
					'atum_hooks' => self::$async_hooks,
				];

				if ( ! empty( $data ) ) {

					$cookies = array();
					foreach ( $_COOKIE as $name => $value ) {
						$cookies[] = "$name=" . rawurlencode( maybe_serialize( $value ) );
					}

					$request_args = array(
						'timeout'    => 0.01,
						'blocking'   => FALSE,
						'sslverify'  => apply_filters( 'https_local_ssl_verify', FALSE ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
						'body'       => $data,
						'headers'    => array(
							'cookie' => implode( '; ', $cookies ),
						),
						'user-agent' => self::get_async_request_user_agent(),
					);
					wp_remote_post( admin_url( 'admin-ajax.php' ), $request_args );

				}

			}

		}

	}

	/**
	 * Execute the async hooks called using Ajax by the trigger async actions function.
	 *
	 * @since 1.7.7
	 */
	public function handle_async_hooks() {

		check_ajax_referer( 'atum_async_hooks', 'token' );

		// Refresh the available async transient.
		AtumCache::set_transient( self::$async_available_transient, 1, DAY_IN_SECONDS, TRUE );

		if ( ! empty( $_POST['atum_hooks'] ) && is_array( $_POST['atum_hooks'] ) ) {

			foreach ( $_POST['atum_hooks'] as $priority_group ) {

				foreach ( $priority_group as $hook_data ) {

					// The class path comes with double slashes.
					if ( is_array( $hook_data['callback'] ) ) {
						$hook_data['callback'][0] = stripslashes( $hook_data['callback'][0] );
					}

					if ( is_callable( $hook_data['callback'] ) ) {
						call_user_func( $hook_data['callback'], ...$hook_data['params'] );
					}
				}

			}

		}

	}

	/**
	 * Check if remote post is available.
	 * NOTE: sometimes the site can be under a htpassword and we cannot perform async calls.
	 *
	 * @since 1.8.8
	 *
	 * @return bool
	 */
	public static function check_async_request() {

		$remote_available = apply_filters( 'atum/queues/check_async_request', AtumCache::get_transient( self::$async_available_transient, TRUE ) );

		if ( ! $remote_available ) {

			// Just try to get a simple file to get the response as faster as possible.
			$response = wp_remote_get( ATUM_URL . 'includes/marketing-popup-content.json', [
				'timeout'   => 20,
				'sslverify' => apply_filters( 'https_local_ssl_verify', FALSE ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			] );

			$remote_available = is_wp_error( $response ) || 200 === wp_remote_retrieve_response_code( $response );

		}

		return (bool) $remote_available;

	}

	/**
	 * Get the user agent used for async requests
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public static function get_async_request_user_agent() {
		return 'ATUM/' . ATUM_VERSION;
	}

	/**
	 * Check whether an async hook is currently running
	 *
	 * @since 1.9.4
	 *
	 * @return bool
	 */
	public static function is_running_async_hook() {
		return wp_doing_ajax() && ! empty( $_POST['action'] ) && 'atum_async_hooks' === $_POST['action'];
	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 *
	 * @since 1.5.8
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 *
	 * @since 1.5.8
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @since 1.5.8
	 *
	 * @return AtumQueues instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
