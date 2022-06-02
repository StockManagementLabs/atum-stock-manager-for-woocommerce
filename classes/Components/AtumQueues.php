<?php
/**
 * Handles the ATUM queues and recurring jobs
 * It uses the WC_Queue that inherits from Action Scheduler: https://actionscheduler.org/
 *
 * @package     Components
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @since       1.5.8
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

use Atum\Api\Controllers\V3\FullExportController;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\InventoryLogs;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\PurchaseOrders;


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
		'atum/clean_up_tmp_folders'          => [
			'time'     => 'now',
			'interval' => WEEK_IN_SECONDS,
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

		add_action( 'init', array( $this, 'check_queues' ), PHP_INT_MAX );

		// Add the ATUM's recurring hooks.
		add_action( 'atum/update_expiring_product_props', array( $this, 'update_expiring_product_props_action' ) );

		// Add the sales calc recurring hook.
		add_action( 'atum/cron_update_sales_calc_props', array( $this, 'update_last_sales_calc_props' ) );

		// Add the tmp folders clean up hook.
		add_action( 'atum/clean_up_tmp_folders', array( $this, 'clean_up_tmp_folders' ) );

		// Add the ATUM Queues async hooks listeners.
		add_action( 'wp_ajax_atum_async_hooks', array( $this, 'handle_async_hooks' ) );
		add_action( 'wp_ajax_nopriv_atum_async_hooks', array( $this, 'handle_async_hooks' ) );

		// Cancel the calc sales properties cron if settings changed.
		add_action( 'update_option', array( $this, 'maybe_cancel_sales_cron' ), 10, 3 );

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

		// Add calculated properties cron if set.
		if ( 'yes' === Helpers::get_option( 'calc_prop_cron' ) ) {

			$multiplier = 'hours' === Helpers::get_option( 'calc_prop_cron_type' ) ? 3600 : 60;

			$this->recurring_hooks['atum/cron_update_sales_calc_props'] = [
				'time'     => Helpers::get_utc_time( Helpers::get_option( 'calc_prop_cron_start' ) ),
				'interval' => round( Helpers::get_option( 'calc_prop_cron_interval' ) * $multiplier ),
			];
		}

		// Allow registering queues externally.
		$this->recurring_hooks = apply_filters( 'atum/queues/recurring_hooks', $this->recurring_hooks );

		foreach ( $this->recurring_hooks as $hook_name => $hook_data ) {

			// Search for duplicated actions.
			$actions       = $wc_queue->search( [ 'hook' => $hook_name ] );
			$schedule_args = isset( $hook_data['args'] ) && is_array( $hook_data['args'] ) ? $hook_data['args'] : [];

			foreach ( $actions as $index => $action ) {
				/**
				 * Variable definition
				 *
				 * @var \ActionScheduler_Action $action
				 */
				if ( $action->is_finished() ) {
					unset( $actions[ $index ] );
				}
			}
			if ( count( $actions ) > 1 ) {
				// Remove actions if duplicated.
				$wc_queue->cancel_all( $hook_name );
			}

			$next_scheduled_date = $wc_queue->get_next( $hook_name, $schedule_args );

			if ( is_null( $next_scheduled_date ) && ! as_next_scheduled_action( $hook_name, $schedule_args ) ) {
				$wc_queue->cancel_all( $hook_name, $schedule_args ); // Ensure all the actions are cancelled before adding a new one.
				$wc_queue->schedule_recurring( strtotime( $hook_data['time'] ), $hook_data['interval'], $hook_name, $schedule_args );
			}

		}

	}

	/**
	 * Recalculate the expiring props for all the products
	 *
	 * @param string $time Optional. Previous dates will be filtered.
	 *
	 * @since 1.5.8
	 */
	public function update_expiring_product_props_action( $time = '3 hours ago' ) {

		// Get all the products that weren't updated during the last 3 hours.
		global $wpdb;

		$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		// phpcs:disable
		$sql = "SELECT product_id FROM $atum_product_data_table";

		if ( $time ) {
			$date_max = Helpers::date_format( strtotime( $time ), TRUE, TRUE );
			$sql     .= $wpdb->prepare( ' WHERE update_date <= %s OR update_date IS NULL', $date_max );
		}

		$sql .= ' ORDER BY update_date';

		$outdated_products = $wpdb->get_col( $sql );
		// phpcs:enable

		// TODO: WHAT ABOUT ILs AND PLs PROPS? IS UPDATING THEM ALSO?
		foreach ( $outdated_products as $product_id ) {
			AtumCalculatedProps::defer_update_atum_sales_calc_props( $product_id );
		}

	}

	/**
	 * Update sales calculated product properties for products sold since the last cron execution.
	 *
	 * @since 1.9.7
	 */
	public function update_last_sales_calc_props() {

		global $wpdb;

		$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$last_executed           = get_option( ATUM_PREFIX . 'last_sales_calc' );

		if ( FALSE === $last_executed ) {
			$last_executed = $wpdb->get_var( "SELECT MAX(sales_update_date) FROM $atum_product_data_table;" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$date_clause = $last_executed ? $wpdb->prepare( 'AND o.post_modified_gmt >= %s', $last_executed ) : '';

		$str_sql = "
			SELECT DISTINCT IF( 0 = IFNULL( oimv.meta_value, 0), oimp.meta_value, oimv.meta_value) product_id
 				FROM `{$wpdb->posts}` o
					INNER JOIN order_item_table oi ON o.ID = oi.order_id
					LEFT JOIN order_itemmeta_table oimp ON oi.order_item_id = oimp.order_item_id AND oimp.meta_key = '_product_id'
					LEFT JOIN order_itemmeta_table oimv ON oi.order_item_id = oimv.order_item_id AND oimv.meta_key = '_variation_id'
					INNER JOIN $atum_product_data_table apd ON 	IF( 0 = IFNULL( oimv.meta_value, 0), oimp.meta_value, oimv.meta_value) = apd.product_id			
				WHERE o.post_type = '%s' AND IF( 0 = IFNULL( oimv.meta_value, 0), oimp.meta_value, oimv.meta_value) IS NOT NULL
					$date_clause AND ( apd.sales_update_date < '$last_executed' OR apd.sales_update_date IS NULL );
		";

		$order_items_table    = "{$wpdb->prefix}atum_order_items";
		$order_itemmeta_table = "{$wpdb->prefix}atum_order_itemmeta";

		$atum_orders_str = str_replace( 'order_itemmeta_table', $order_itemmeta_table, str_replace( 'order_item_table', $order_items_table, $str_sql ) );

		if ( ModuleManager::is_module_active( 'purchase_orders' ) ) {

			// phpcs:ignore: WordPress.DB.PreparedSQL.NotPrepared
			$products = $wpdb->get_col( $wpdb->prepare( $atum_orders_str, PurchaseOrders::POST_TYPE ) );

			foreach ( $products as $product_id ) {

				$product = Helpers::get_atum_product( $product_id );
				AtumCalculatedProps::update_atum_sales_calc_props_cli_call( $product, 2 );

			}
		}

		if ( ModuleManager::is_module_active( 'inventory_logs' ) ) {

			// phpcs:ignore: WordPress.DB.PreparedSQL.NotPrepared
			$products = $wpdb->get_col( $wpdb->prepare( $atum_orders_str, InventoryLogs::POST_TYPE ) );

			foreach ( $products as $product_id ) {

				$product = Helpers::get_atum_product( $product_id );
				AtumCalculatedProps::update_atum_sales_calc_props_cli_call( $product, 3 );

			}
		}

		// Update wc orders at last to prevent updating sales update data before finishing the process.
		$order_items_table    = "{$wpdb->prefix}woocommerce_order_items";
		$order_itemmeta_table = "{$wpdb->prefix}woocommerce_order_itemmeta";
		$post_type            = 'shop_order';

		$str_sql = str_replace( 'order_itemmeta_table', $order_itemmeta_table, str_replace( 'order_item_table', $order_items_table, $str_sql ) );

		// phpcs:ignore: WordPress.DB.PreparedSQL.NotPrepared
		$products = $wpdb->get_col( $wpdb->prepare( $str_sql, $post_type ) );

		foreach ( $products as $product_id ) {
			$product = Helpers::get_atum_product( $product_id );
			AtumCalculatedProps::update_atum_sales_calc_props_cli_call( $product, 1 );

		}

		// Wait until finished.
		update_option( ATUM_PREFIX . 'last_sales_calc', Helpers::date_format( '', TRUE, TRUE ) );

	}

	/**
	 * Clean up ATUM temporary folders
	 *
	 * @since 1.9.19
	 */
	public function clean_up_tmp_folders() {

		// Clean up any old full API exportation older than 7 days.
		$full_export_dir = FullExportController::get_full_export_upload_dir();

		if ( ! is_wp_error( $full_export_dir ) ) {

			$files = glob( $full_export_dir . '*' );

			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {

					$file_time = filemtime( $file );

					if ( $file_time + WEEK_IN_SECONDS < time() ) {
						unlink( $file );
					}

				}
			}

		}

	}

	/**
	 * Defer an action to run one time on the WP's 'shutdown' action.
	 *
	 * @since 1.7.3
	 *
	 * @param string   $hook     The hook name to call after shutdown.
	 * @param callable $callback A callable function or method. IMPORTANT: It must be a static method called through the class name or it won't work.
	 * @param array    $params   Optional. Any params that need to be passed to the async action. These params will be unpacked with the spread operator.
	 * @param int      $priority Default to 10.
	 */
	public static function add_async_action( $hook, $callback, $params = [], $priority = 10 ) {

		// Avoid unending loops when the current request is already coming from an async action.
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) && self::get_async_request_user_agent() === $_SERVER['HTTP_USER_AGENT'] ) {
			return;
		}

		try {

			if ( ! is_string( $callback ) && ! ( is_array( $callback ) && is_string( $callback[0] ) ) ) {
				throw new AtumException( 'invalid_callback', __METHOD__ . ' :: ' . __( "The callback must be called statically, \$this isn't allowed within async actions.", ATUM_TEXT_DOMAIN ) );
			}

			// NOTE: For now we only allow unique calls to the same hook.
			self::$async_hooks[ $priority ][ $hook ] = array(
				'callback' => $callback,
				'params'   => $params,
			);

			// Ensure that we add the action only once.
			if ( ! has_action( 'shutdown', array( self::get_instance(), 'trigger_async_actions' ) ) ) {
				add_action( 'shutdown', array( self::get_instance(), 'trigger_async_actions' ) );
			}

		} catch ( AtumException $e ) {
			error_log( $e->getMessage() );
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

						if ( ! empty( $hook_data['callback'] ) && is_callable( $hook_data['callback'] ) ) {

							if ( isset( $hook_data['params'] ) && is_array( $hook_data['params'] ) ) {
								call_user_func( $hook_data['callback'], ...$hook_data['params'] );
							}
							else {
								call_user_func( $hook_data['callback'] );
							}

						}
					}

				}

			}
			else {

				$data = [
					'action'     => 'atum_async_hooks',
					'atum_hooks' => self::$async_hooks,
				];

				$headers = $cookies = array();

				// When doing an async action during an API request, make sure we add the logged in cookie header to preserve the auth, etc.
				if ( Helpers::is_rest_request() && is_user_logged_in() ) {

					$user_id    = get_current_user_id();
					$expiration = time() + ( 2 * DAY_IN_SECONDS );
					$_COOKIE    = array();

					$logged_in_cookie          = new \WP_Http_Cookie( LOGGED_IN_COOKIE );
					$logged_in_cookie->name    = LOGGED_IN_COOKIE;
					$logged_in_cookie->value   = wp_generate_auth_cookie( $user_id, $expiration, 'logged_in' );
					$logged_in_cookie->expires = $expiration;
					$logged_in_cookie->path    = COOKIEPATH;
					$logged_in_cookie->domain  = COOKIE_DOMAIN;

					$cookies[]                   = $logged_in_cookie->getHeaderValue();
					$_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in_cookie->value;

				}
				else {

					// Pass any possible cookie coming on the current request through the new request.
					if ( isset( $_COOKIE ) ) {
						foreach ( $_COOKIE as $name => $value ) {
							$cookies[] = "$name=" . rawurlencode( maybe_serialize( $value ) );
						}
					}

				}

				if ( ! empty( $cookies ) ) {
					$headers['cookie'] = implode( '; ', $cookies );
				}

				// NOTE: The nonce must be added after the logged in cookie has been set.
				$data['security'] = wp_create_nonce( 'atum_async_hooks' );

				$request_args = array(
					'timeout'    => 0.01,
					'blocking'   => FALSE,
					'sslverify'  => apply_filters( 'https_local_ssl_verify', FALSE ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					'body'       => $data,
					'headers'    => $headers,
					'user-agent' => self::get_async_request_user_agent(),
				);
				wp_remote_post( admin_url( 'admin-ajax.php' ), $request_args );

			}

		}

	}

	/**
	 * Execute the async hooks called using Ajax by the trigger async actions function.
	 *
	 * @since 1.7.7
	 */
	public function handle_async_hooks() {

		check_ajax_referer( 'atum_async_hooks', 'security' );

		// Refresh the available async transient.
		AtumCache::set_transient( self::$async_available_transient, 1, DAY_IN_SECONDS, TRUE );

		if ( ! empty( $_POST['atum_hooks'] ) && is_array( $_POST['atum_hooks'] ) ) {

			foreach ( $_POST['atum_hooks'] as $priority_group ) {

				foreach ( $priority_group as $hook_data ) {

					if ( empty( $hook_data['callback'] ) ) {
						continue;
					}

					// The class path comes with double slashes.
					if ( is_array( $hook_data['callback'] ) && is_string( $hook_data['callback'][0] ) ) {
						$hook_data['callback'][0] = stripslashes( $hook_data['callback'][0] );
					}

					if ( is_callable( $hook_data['callback'] ) ) {

						if ( isset( $hook_data['params'] ) && is_array( $hook_data['params'] ) ) {
							call_user_func( $hook_data['callback'], ...$hook_data['params'] );
						}
						else {
							call_user_func( $hook_data['callback'] );
						}

					}

				}

			}

		}

	}

	/**
	 * Check for ATUM settings and cancel the sales calc properties cron is changed.
	 *
	 * @since 1.9.7
	 *
	 * @param string $option_name   Name of the updated option.
	 * @param mixed  $old_value     The old option value.
	 * @param mixed  $value         The new option value.
	 */
	public function maybe_cancel_sales_cron( $option_name, $old_value, $value ) {

		if ( 'atum_settings' === $option_name ) {

			// Cancel anyway.
			if (
				empty( $value['calc_prop_cron'] ) || 'no' === $value['calc_prop_cron'] ||
				$old_value['calc_prop_cron_interval'] !== $value['calc_prop_cron_interval'] ||
				$old_value['calc_prop_cron_type'] !== $value['calc_prop_cron_type'] ||
				$old_value['calc_prop_cron_start'] !== $value['calc_prop_cron_start']
			) {

				$wc = WC();

				// Ensure that the current WC version supports queues.
				if ( ! is_callable( array( $wc, 'queue' ) ) ) {
					return;
				}

				$wc_queue = $wc->queue();
				$wc_queue->cancel_all( 'atum/cron_update_sales_calc_props' );
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
