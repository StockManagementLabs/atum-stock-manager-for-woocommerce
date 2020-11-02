<?php
/**
 * Handles the ATUM queues and recurring jobs
 * It uses the WC_Queue that inherits from Action Scheduler: https://actionscheduler.org/
 *
 * @package     Components
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2020 Stock Management Labs™
 *
 * @since       1.5.8
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;

class AtumQueues {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumQueues
	 */
	private static $instance;

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

		foreach ( $outdated_products as $product_id ) {

			$product = Helpers::get_atum_product( $product_id );

			if ( $product instanceof \WC_Product ) {
				$product->set_update_date( gmdate( 'Y-m-d H:i:s' ) );
				Helpers::update_atum_sales_calc_props( $product );
			}

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
	 */
	public static function add_async_action( $hook, $callback, $params = array() ) {

		// NOTE: For now we only allow unique calls to the same hook.
		self::$async_hooks[ $hook ] = array(
			'callback' => $callback,
			'params'   => $params,
		);

		// Ensure that we add the action only once.
		if ( ! has_action( 'shutdown', array( get_class(), 'trigger_async_actions' ) ) ) {
			add_action( 'shutdown', array( get_class(), 'trigger_async_actions' ) );
		}

	}

	/**
	 * Trigger all the registered async actions. Caution!!: as it's a remote post, some variables must be serialized (e.g. an object).
	 *
	 * @since 1.7.3
	 */
	public static function trigger_async_actions() {

		if ( ! empty( self::$async_hooks ) ) {

			$data = [
				'action' => 'atum_async_hooks',
				'token'  => wp_create_nonce( 'atum_async_hooks' ),
				'hooks'  => self::$async_hooks,
			];

			if ( ! empty( $data ) ) {

				$cookies = array();
				foreach ( $_COOKIE as $name => $value ) {
					$cookies[] = "$name=" . urlencode( is_array( $value ) ? serialize( $value ) : $value );
				}

				$request_args = array(
					'timeout'   => 0.01,
					'blocking'  => FALSE,
					'sslverify' => apply_filters( 'https_local_ssl_verify', TRUE ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					'body'      => $data,
					'headers'   => array(
						'cookie' => implode( '; ', $cookies ),
					),
				);

				$url = admin_url( 'admin-ajax.php' );

				wp_remote_post( $url, $request_args );
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

		$hooks = $_POST['hooks'];

		foreach ( $hooks as $async_hook => $hook_data ) {

			// The class path comes with double slash.
			$hook_data['callback'][0] = stripslashes( $hook_data['callback'][0] );

			if ( is_callable( $hook_data['callback'] ) ) {
				call_user_func( $hook_data['callback'], ...$hook_data['params'] );
			}

		}

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
