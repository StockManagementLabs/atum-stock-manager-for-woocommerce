<?php
/**
 * The ATUM's API class
 *
 * @since       1.6.2
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     Atum\Api
 */

namespace Atum\Api;

defined( 'ABSPATH' ) || die;

//use Atum\Api\Controllers\V3\FullExportController;
use Atum\Api\Extenders\AtumProductData;
use Atum\Api\Extenders\Media;
use Atum\Api\Extenders\OrderNotes;
use Atum\Api\Extenders\Orders;
use Atum\Api\Extenders\ProductAttributes;
use Atum\Api\Extenders\ProductCategories;
//use Atum\Components\AtumCache;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\InventoryLogs;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;


class AtumApi {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumApi
	 */
	private static $instance;

	/**
	 * The CORS origin coming from the ATUM App
	 */
	const ATUM_APP_ORIGIN = 'capacitor://com.stockmanagementlabs.atum';

	/**
	 * The CORS origin coming from the ATUM QA App
	 */
	const ATUM_QA_APP_ORIGIN = 'capacitor://qa.stockmanagementlabs.atum';

	/**
	 * The CORS origin coming from the ATUM App running on a browser
	 */
	const ATUM_APP_BROWSER = 'https://com.stockmanagementlabs.atum';

	/**
	 * Max size limit for the API response pages.
	 */
	const PER_PAGE_LIMIT = 500;

	/**
	 * The ATUM API controllers
	 *
	 * @var array
	 */
	private $api_controllers = array(
		'atum-addons'                        => __NAMESPACE__ . '\Controllers\V3\AddonsController',
		'atum-dashboard'                     => __NAMESPACE__ . '\Controllers\V3\DashboardController',
		'atum-dashboard-current-stock-value' => __NAMESPACE__ . '\Controllers\V3\DashboardCurrentStockValueController',
		'atum-dashboard-lost-sales'          => __NAMESPACE__ . '\Controllers\V3\DashboardLostSalesController',
		'atum-dashboard-orders'              => __NAMESPACE__ . '\Controllers\V3\DashboardOrdersController',
		'atum-dashboard-promo-sales'         => __NAMESPACE__ . '\Controllers\V3\DashboardPromoSalesController',
		'atum-dashboard-sales'               => __NAMESPACE__ . '\Controllers\V3\DashboardSalesController',
		'atum-dashboard-statistics'          => __NAMESPACE__ . '\Controllers\V3\DashboardStatisticsController',
		'atum-dashboard-stock-control'       => __NAMESPACE__ . '\Controllers\V3\DashboardStockControlController',
		'atum-inbound-stock'                 => __NAMESPACE__ . '\Controllers\V3\InboundStockController',
		'atum-inventory-logs'                => __NAMESPACE__ . '\Controllers\V3\InventoryLogsController',
		'atum-inventory-log-notes'           => __NAMESPACE__ . '\Controllers\V3\InventoryLogNotesController',
		'atum-locations'                     => __NAMESPACE__ . '\Controllers\V3\ProductLocationsController',
		'atum-product-variations'            => __NAMESPACE__ . '\Controllers\V3\ProductVariationsController',
		'atum-purchase-orders'               => __NAMESPACE__ . '\Controllers\V3\PurchaseOrdersController',
		'atum-purchase-order-notes'          => __NAMESPACE__ . '\Controllers\V3\PurchaseOrderNotesController',
		'atum-settings'                      => __NAMESPACE__ . '\Controllers\V3\SettingsController',
		'atum-setting-options'               => __NAMESPACE__ . '\Controllers\V3\SettingOptionsController',
		'atum-suppliers'                     => __NAMESPACE__ . '\Controllers\V3\SuppliersController',
		'atum-tools'                         => __NAMESPACE__ . '\Controllers\V3\ToolsController',
		/* @deprecated ATUM Mobile App stuff */
		//'atum-full-export'                   => __NAMESPACE__ . '\Controllers\V3\FullExportController',
		'atum-order-refunds'                 => __NAMESPACE__ . '\Controllers\V3\AllOrderRefundsController',
	);

	/**
	 * Collection of paginable collections to which we need to increase the per_page limit
	 *
	 * @var string[]
	 */
	private $paginable_collections = [
		'attachment',
		'comment',
		'inventories', // ATUM Multi Inventory hack.
		'product',
		'product_attributes',
		'product_cat',
		'product_tag',
		'product_variation',
		'shop_coupon',
		'shop_order',
		'shop_order_refund',
	];

	/**
	 * All the exportable endpoint paths
	 *
	 * NOTE: We are using the schema names as endpoint keys for compatibility.
	 *
	 * @var string[]
	 * @deprecated ATUM Mobile App stuff
	 */
	/*private static $exportable_endpoints = array(
		'attribute'       => '/wc/v3/products/attributes',
		'category'        => '/wc/v3/products/categories',
		'comment'         => array(
			'atum-order-notes' => '/wc/v3/atum/atum-order-notes', // TODO: Is this needed? Is not enough with the comments export?
			'comments'         => '/wp/v2/comments',
		),
		'coupon'          => '/wc/v3/coupons',
		'customer'        => '/wc/v3/customers',
		//'dashboard'      => '/wc/v3/atum/dashboard', TODO: Commented for now. I cannot see any table in the SQLite DB for the dashboard.
		'inbound-stock'   => '/wc/v3/atum/inbound-stock',
		'inventory-log'   => '/wc/v3/atum/inventory-logs',
		'location'        => '/wc/v3/products/atum-locations',
		'media'           => '/wp/v2/media',
		'order'           => '/wc/v3/orders',
		'payment-method'  => '/wc/v3/payment_gateways',
		'product'         => '/wc/v3/products',
		'purchase-order'  => '/wc/v3/atum/purchase-orders',
		'refund'          => '/wc/v3/atum/order-refunds',
		'shipping-method' => '/wc/v3/shipping_methods',
		'store-settings'  => array(
			'wc.general'          => '/wc/v3/settings/general',
			'wc.admin'            => '/wc/v3/settings/wc_admin',
			'wc.products'         => '/wc/v3/settings/products',
			'wc.tax'              => '/wc/v3/settings/tax',
			'atum.general'        => '/wc/v3/atum/settings/general',
			'atum.storeDetails'   => '/wc/v3/atum/settings/store_details',
			'atum.moduleManager'  => '/wc/v3/atum/settings/module_manager',
		),
		'supplier'        => '/wc/v3/atum/suppliers',
		'tag'             => '/wc/v3/products/tags',
		'tax-class'       => '/wc/v3/taxes/classes',
		'tax-rate'        => '/wc/v3/taxes',
		'variation'       => '/wc/v3/atum/product-variations',
	);*/

	/**
	 * AtumApi constructor
	 *
	 * @since 1.6.2
	 */
	private function __construct() {

		// Pre-filter the available endpoints according to currently-enabled modules.
		if ( ! ModuleManager::is_module_active( 'inventory_logs' ) ) {
			unset(
				$this->api_controllers['atum-inventory-logs'],
				$this->api_controllers['atum-inventory-log-notes']
			);

			/* @deprecated ATUM Mobile App stuff */
			/*unset(
				self::$exportable_endpoints['inventory-log'],
			);*/
		}

		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) ) {
			unset(
				$this->api_controllers['atum-purchase-orders'],
				$this->api_controllers['atum-purchase-order-notes'],
				$this->api_controllers['atum-suppliers'],
				$this->api_controllers['atum-locations'],
				$this->api_controllers['atum-inbound-stock']
			);

			/* @deprecated ATUM Mobile App stuff */
			/*unset(
				self::$exportable_endpoints['purchase-order'],
				self::$exportable_endpoints['supplier'],
				self::$exportable_endpoints['location'],
				self::$exportable_endpoints['inbound-stock']
			);*/
		}

		/* @deprecated ATUM Mobile App stuff */
		/*if ( ! wc_coupons_enabled() ) {
			unset(
				self::$exportable_endpoints['coupon']
			);
		}*/

		// Add the ATUM controllers to the WooCommerce API (/wp-json/wc/v3).
		add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this, 'register_api_controllers' ) );

		foreach ( array_keys( $this->api_controllers ) as $endpoint ) {

			switch ( $endpoint ) {
				case 'atum-inventory-logs':
					$this->paginable_collections[] = InventoryLogs::POST_TYPE;
					break;

				case 'atum-locations':
					$this->paginable_collections[] = Globals::PRODUCT_LOCATION_TAXONOMY;
					break;

				case 'atum-purchase-orders':
					$this->paginable_collections[] = PurchaseOrders::POST_TYPE;
					break;

				case 'atum-suppliers':
					$this->paginable_collections[] = Suppliers::POST_TYPE;
					break;
			}

		}

		foreach ( $this->paginable_collections as $collection ) {
			add_filter( "rest_{$collection}_collection_params", array( $this, 'increase_posts_per_page' ) );
		}

		// Fix the CORS issue when connecting through Ionic's Capacitor to our API.
		add_action( 'rest_api_init', array( $this, 'add_cors_hooks' ), 15 );

		/* @deprecated ATUM Mobile App stuff */
		// Add the exportable endpoint hooks.
		//add_action( 'init', array( $this, 'add_exportable_endpoints_hooks' ), 11 );

		/* @deprecated ATUM Mobile App stuff */
		// Add the healthcheck CRON job for the full export.
		//add_filter( 'atum/queues/recurring_hooks', array( $this, 'add_healthcheck_cron' ) );
		//add_action( 'atum/api_healthcheck', array( $this, 'action_healthcheck' ) );

		$this->load_extenders();

	}

	/**
	 * Register the ATUM API controllers
	 *
	 * @since 1.6.2
	 *
	 * @param array $api_controllers
	 *
	 * @return array
	 */
	public function register_api_controllers( $api_controllers ) {

		if ( ! empty( $api_controllers['wc/v3'] ) ) {
			$api_controllers['wc/v3'] = array_merge( $api_controllers['wc/v3'], apply_filters( 'atum/api/registered_controllers', $this->api_controllers ) );
		}

		return $api_controllers;

	}

	/**
	 * Load the ATUM API extenders (all those that are extending an existing WC endpoint)
	 *
	 * @since 1.6.2
	 */
	public function load_extenders() {

		Orders::get_instance();
		OrderNotes::get_instance();
		AtumProductData::get_instance();
		ProductAttributes::get_instance();
		ProductCategories::get_instance();
		Media::get_instance();

		// Allow ATUM plugins to load their extenders.
		do_action( 'atum/api/load_extenders' );

	}

	/**
	 * Add hooks for fixing CORS rules
	 *
	 * @since 1.8.8
	 */
	public function add_cors_hooks() {

		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter( 'rest_pre_serve_request', array( $this, 'fix_cors' ), PHP_INT_MAX );

	}

	/**
	 * Fix CORS issue when connecting through Ionic's Capacitor to our API.
	 *
	 * @since 1.8.8
	 *
	 * @param mixed $value Response data.
	 *
	 * @return mixed
	 */
	public function fix_cors( $value ) {

		$origin = get_http_origin();

		if ( $origin ) {

			// Requests from file:// and data: URLs send "Origin: null".
			if (
				self::ATUM_APP_ORIGIN !== $origin && self::ATUM_QA_APP_ORIGIN !== $origin &&
				self::ATUM_APP_BROWSER !== $origin && 'null' !== $origin
			) {
				$origin = esc_url_raw( $origin );
			}

			// Allow 3rd parties to add any extra allowed origins.
			$origin = apply_filters( 'atum/api/cors_origin', $origin );

			header( 'Access-Control-Allow-Origin: ' . $origin );
			header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
			header( 'Access-Control-Allow-Credentials: true' );
			header( 'Vary: Origin', FALSE );

		}
		elseif ( ! headers_sent() && 'GET' === $_SERVER['REQUEST_METHOD'] && ! is_user_logged_in() ) {
			header( 'Vary: Origin', FALSE );
		}

		return $value;

	}

	/**
	 * Increase the posts per page limit (that is set to 100 by WP) when syncing through the ATUM App or running a full export
	 *
	 * @since 1.9.4
	 *
	 * @param array $query_params
	 *
	 * @return array
	 */
	public function increase_posts_per_page( $query_params ) {

		$origin = get_http_origin();

		// Only alter the limit if the request is coming from the ATUM App.
		if ( ! str_contains( $origin, 'com.stockmanagementlabs.atum' ) && ! str_contains( $origin, 'qa.stockmanagementlabs.atum' ) && ! Helpers::is_running_cli() ) {
			return $query_params;
		}

		if ( is_array( $query_params['per_page'] ) && isset( $query_params['per_page']['maximum'] ) ) {
			$query_params['per_page']['maximum'] = self::PER_PAGE_LIMIT;
		}

		return $query_params;

	}

	/**
	 * Add the hooks for the exportable endpoints
	 *
	 * @since 1.9.19
	 * @deprecated ATUM Mobile App stuff
	 */
	/*public function add_exportable_endpoints_hooks() {

		// Exportable endpoints hooks.
		foreach ( self::get_exportable_endpoints() as $schema => $exportable_endpoint ) {

			if ( is_array( $exportable_endpoint ) ) {

				foreach ( $exportable_endpoint as $sub_key => $sub_endpoint ) {
					add_action( "atum_api_export_endpoint_{$schema}_{$sub_key}", array( '\Atum\Api\Controllers\V3\FullExportController', 'run_export' ), 10, 6 );
					add_action( "atum_api_dump_endpoint_{$schema}_{$sub_key}", array( '\Atum\Api\Controllers\V3\FullExportController', 'generate_sql_dump' ), 10, 3 );
				}

			}
			else {
				add_action( "atum_api_export_endpoint_$schema", array( '\Atum\Api\Controllers\V3\FullExportController', 'run_export' ), 10, 6 );
				add_action( "atum_api_dump_endpoint_$schema", array( '\Atum\Api\Controllers\V3\FullExportController', 'generate_sql_dump' ), 10, 3 );
			}
		}

	}*/

	/**
	 * Getter for the exportable endpoints
	 *
	 * @since 1.9.19
	 *
	 * @return string[]
	 * @deprecated ATUM Mobile App stuff
	 */
	/*public static function get_exportable_endpoints() {
		return apply_filters( 'atum/api/exportable_endpoints', self::$exportable_endpoints );
	}*/

	/**
	 * Special validation for the the post status param (allowing multiple statuses at once)
	 *
	 * @since 1.9.22
	 *
	 * @param mixed            $value
	 * @param \WP_REST_Request $request
	 * @param string           $param
	 *
	 * @return true|\WP_Error
	 */
	public function validate_status_param( $value, $request, $param ) {

		if ( ! str_contains( $value, ',' ) ) {
			return rest_validate_request_arg( $value, $request, $param );
		}

		$attributes = $request->get_attributes();
		if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
			return TRUE;
		}
		$args = $attributes['args'][ $param ];

		$statuses = explode( ',', $value );

		foreach ( $statuses as $status ) {

			$valid_status = rest_validate_value_from_schema( $status, $args, $param );

			if ( ! $valid_status || is_wp_error( $valid_status ) ) {
				return $valid_status;
			}

		}

		return TRUE;

	}

	/**
	 * Add the healthcheck CRON for the full export
	 *
	 * @since 1.9.49
	 *
	 * @param array $recurring_hooks
	 *
	 * @return array
	 * @deprecated ATUM Mobile App stuff
	 */
	/*public function add_healthcheck_cron( $recurring_hooks ) {

		$recurring_hooks['atum/api_healthcheck'] = array(
			'time'     => 'now',
			'interval' => HOUR_IN_SECONDS,
		);

		return $recurring_hooks;

	}*/

	/**
	 * Healthcheck tasks
	 *
	 * @since 1.9.49
	 * @deprecated ATUM Mobile App stuff
	 */
	/*public function action_healthcheck() {

		// Check if there is any hang process from the full export queue.
		global $wpdb;

		$full_export_transients = $wpdb->get_results( "
			SELECT option_name, option_value FROM $wpdb->options 
		 	WHERE option_name LIKE '_transient_" . AtumCache::get_transient_key( FullExportController::EXPORTED_ENDPOINTS_TRANSIENT ) . "%'
		" );

		if ( ! empty( $full_export_transients ) ) {

			// If there are no pending actions, it means there are hang processes.
			if ( ! FullExportController::are_there_pending_exports( FALSE ) ) {

				$full_export_dir = FullExportController::get_full_export_upload_dir();

				foreach ( $full_export_transients as $transient ) {

					$endpoint  = $transient->option_value;
					$schema    = FullExportController::find_endpoint_schema( $endpoint );
					$file_name = FullExportController::get_file_name( $endpoint, $schema );

					// Find the last JSON file created for this endpoint.
					$endpoint_files = glob( $full_export_dir . $file_name . '*' );

					if ( empty( $endpoint_files ) ) {
						continue; // TODO: WHAT IF THERE ARE NO FILES BECAUSE FAILED THE VERY FIRST IMPORT?
					}

					self::maybe_reschedule_full_export_action( $endpoint_files, $endpoint, $schema );

				}

			}

		}
		// We've seen cases when there are no transients nor actions, but there are entities that are still not completed.
		else {

			$full_export_dir = FullExportController::get_full_export_upload_dir();
			$exported_files  = glob( $full_export_dir . '*.json' );

			if ( ! empty( $exported_files ) ) {

				$exportable_endpoints = self::get_exportable_endpoints();
				$rescheduled          = FALSE;

				// First, check if all the JSON files have been exported for each endpoint.
				foreach ( $exportable_endpoints as $schema => $endpoint ) {

					$endpoint = is_array( $endpoint ) ? $endpoint : [ $endpoint ];

					foreach ( $endpoint as $sub_endpoint ) {

						$file_name = FullExportController::get_file_name( $sub_endpoint, $schema );
						$endpoint_files = array_filter( $exported_files, function( $file ) use ( $file_name ) {
							return str_contains( $file, $file_name );
						} );

						if ( ! empty( $endpoint_files ) ) {
							$success = self::maybe_reschedule_full_export_action( $endpoint_files, $sub_endpoint, $schema );

							if ( $success ) {
								$rescheduled = TRUE;
							}
						}

					}

				}

				// Once done, also check if all the dump files have been created for each endpoint.
				if ( ! $rescheduled ) {

					$exported_dumps = glob( $full_export_dir . '*.sql' );

					if ( ! empty( $exported_dumps ) ) {

						$user_id = FullExportController::get_admin_user();

						// Get the dump transient key.
						$dump_config = $wpdb->get_var( "
							SELECT option_value FROM $wpdb->options 
							WHERE option_name LIKE '_transient_" . AtumCache::get_transient_key( FullExportController::DUMP_CONFIG_TRANSIENT ) . "%'
						" );

						if ( ! $dump_config ) {
							return;
						}

						$dump_config = maybe_unserialize( $dump_config );

						foreach ( $exportable_endpoints as $schema => $endpoint ) {

							$dump_files = array_filter( $exported_dumps, function( $file ) use ( $schema ) {
								return str_contains( $file, "atum_dump_$schema" );
							} );

							if ( empty( $dump_files ) ) {

								if ( FullExportController::DEBUG_MODE ) {
									error_log( "ATUM API Healthcheck: Generating missing dump for the schema $schema..." );
								}

								$dump_generated = FullExportController::generate_sql_dump( $endpoint, $user_id, $dump_config['userId'] ?? '' );

								if ( is_array( $dump_generated ) && FALSE === $dump_generated['success'] ) {
									error_log( "ATUM API Healthcheck error: The dump for the schema $schema could not be generated. Error: {$dump_generated['message']}" );
								}

							}

						}
					}

				}

			}

		}

	}*/

	/**
	 * Reschedule the full export action if it was not completed.
	 *
	 * @since 1.9.49
	 *
	 * @param array  $endpoint_files The endpoint files.
	 * @param string $endpoint       The endpoint name.
	 * @param string $schema         The schema name.
	 *
	 * @return bool
	 */
	/*private static function maybe_reschedule_full_export_action( $endpoint_files, $endpoint, $schema ) {

		global $wpdb;

		natsort( $endpoint_files );
		$last_file = array_pop( $endpoint_files );

		// Get the page number from the file name.
		$file_name  = str_replace( '.json', '', basename( $last_file ) );
		$name_parts = explode( '-', $file_name );
		$pagination = explode( '_', array_pop( $name_parts ) );

		list( $last_page, $total_pages ) = count( $pagination ) > 1 ? $pagination : [ 1, 1 ];

		if ( is_numeric( $last_page ) && is_numeric( $total_pages ) && (int) $total_pages > (int) $last_page ) {

			$user_id = FullExportController::get_admin_user();

			// Get the dump transient key.
			$dump_config = $wpdb->get_var( "
				SELECT option_value FROM $wpdb->options 
				WHERE option_name LIKE '_transient_" . AtumCache::get_transient_key( FullExportController::DUMP_CONFIG_TRANSIENT ) . "%'
			" );

			if ( $dump_config ) {
				$dump_config = maybe_unserialize( $dump_config );
			}

			//
			// Hook args: endpoint, user_id, params, page, format and user_app_id.
			// NOTE: These are the parameters that are passed later to the run_export method.
			// NOTE2: This hook cannot be unique because the previous page schedule is still running here.
			//
			$hook_args = [ $endpoint, $user_id, $json['params'] ?? '', $last_page + 1, 'sqlite', $dump_config['userId'] ?? '' ];
			$hook_name = "atum_api_export_endpoint_$schema";

			// If it is a sub-endpoint, we need to add its key to the hook name as well.
			$exportable_endpoints = self::get_exportable_endpoints();

			if ( is_array( $exportable_endpoints[ $schema ] ) ) {
				$hook_name .= '_' . array_search( $endpoint, $exportable_endpoints[ $schema ], TRUE );
			}

			$scheduled = as_enqueue_async_action( $hook_name, $hook_args, 'atum' );

			if ( ! $scheduled ) {
				error_log( "ATUM API Healthcheck error: The next page of the endpoint $endpoint could not be scheduled: " );
				error_log( 'Hook args: ' . var_export( $hook_args, TRUE ) );
			}

			// Re-add the endpoint transient again because is not fully exported yet.
			// TODO: Do we need this? It is supposed that the transients were there.
			$pending_endpoint_transient_key = AtumCache::get_transient_key( FullExportController::EXPORTED_ENDPOINTS_TRANSIENT . FullExportController::get_file_name( $endpoint, $schema, $json['params'] ?? '' ) );
			AtumCache::set_transient( $pending_endpoint_transient_key, $endpoint, WEEK_IN_SECONDS, TRUE );

			return TRUE;

		}

		return FALSE;

	}*/


	/****************************
	 * Instance methods
	 ****************************/

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
	 * @return AtumApi instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
