<?php
/**
 * REST ATUM API Full Export controller
 * Handles requests to the /atum/full-export endpoint.
 *
 * @since       1.9.19
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

use Atum\Api\AtumApi;
use Atum\Components\AtumCache;
use Atum\Components\AtumOrders\AtumComments;
use Atum\Inc\Helpers;


class FullExportController extends \WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/full-export';

	/**
	 * Transient key name for exported endpoints.
	 */
	const EXPORTED_ENDPOINTS_TRANSIENT = 'api_run_full_export_endpoints';

	/**
	 * Transient key name for the list of App subscribers that initiated a full export.
	 */
	const SUBSCRIBER_IDS_TRANSIENT = 'api_run_full_export_subscribers';

	/**
	 * Cloud function to send notification to the App user when the full export is completed.
	 */
	const COMPLETED_FULL_EXPORT_NOTICE_URL = 'https://us-central1-atum-app.cloudfunctions.net/completedFullExport';

	/**
	 * Register the routes for tools
	 *
	 * @since 1.9.19
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @since 1.9.19
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-full-export',
			'type'       => 'object',
			'properties' => array(
				'success' => array(
					'description' => __( 'Did the export run successfully?', ATUM_TEXT_DOMAIN ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'code'    => array(
					'description' => __( 'The actual status code.', ATUM_TEXT_DOMAIN ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'message' => array(
					'description' => __( 'Export return message.', ATUM_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'data'    => array(
					'description' => __( 'Exported data.', ATUM_TEXT_DOMAIN ),
					'type'        => 'array',
					'context'     => array( 'view' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );

	}

	/**
	 * Get the query params for collections
	 *
	 * @since 1.9.19
	 *
	 * @return array
	 */
	public function get_collection_params() {

		return array(
			'context'            => $this->get_context_param( [ 'default' => 'view' ] ),
			'subscriber_id'      => array(
				'description'       => __( 'Firebase subscriber ID to notify after a successful export.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'required'          => TRUE,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'endpoint'           => array(
				'description'       => __( 'Do the export only for the specified ATUM endpoint.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'orders'             => array(
				'description'       => __( 'Filter the WC orders to export after a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'format'            => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'order-refunds'      => array(
				'description'       => __( 'Filter the WC order refunds to export after a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'format'            => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'inventory-logs'     => array(
				'description'       => __( 'Filter the inventory logs to export after a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'format'            => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'purchase-orders'    => array(
				'description'       => __( 'Filter the purchase orders to export after a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'format'            => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'comments'           => array(
				'description'       => __( 'Filter the WC order notes to export after a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'format'            => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'atum-order-notes'   => array(
				'description'       => __( 'Filter the ATUM order notes to export after a given ISO8601 compliant date.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'format'            => 'date-time',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'products'           => array(
				'description'       => __( 'Filter the products to export of a given status (or statuses separated by commas).', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'product-variations' => array(
				'description'       => __( 'Filter the variation products to export of a given status (or statuses separated by commas).', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'suppliers'          => array(
				'description'       => __( 'Filter the suppliers to export of a given status (or statuses separated by commas).', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

	}

	/**
	 * Check whether a given request has permission to view a the full export status
	 *
	 * @since 1.9.19
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error( 'atum_rest_cannot_view', __( 'Sorry, you cannot view the full export status.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check whether a given request has permission to run the full export
	 *
	 * @since 1.9.19
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error( 'atum_rest_cannot_update', __( 'Sorry, you are not allowed to run a full export.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Return the full export status (if running)
	 *
	 * @since 1.9.19
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {

		$requested_endpoint = $request['endpoint'] ?? '';

		if ( self::are_there_pending_exports() ) {

			$response = array(
				'success' => FALSE,
				'code'    => 'running',
				'message' => __( 'The export is still running. Please try again later.', ATUM_TEXT_DOMAIN ),
			);

		}
		else {

			$exportable_endpoints = AtumApi::get_exportable_endpoints();

			// Check if there are multiple endpoints separated by commas.
			$endpoints  = $requested_endpoint ? explode( ',', $requested_endpoint ) : $exportable_endpoints;
			$upload_dir = self::get_full_export_upload_dir();

			if ( ! is_wp_error( $upload_dir ) ) {

				$files = array();

				foreach ( $endpoints as $endpoint ) {

					if ( $endpoint ) {
						$files[ array_search( $endpoint, $exportable_endpoints ) ] = glob( $upload_dir . self::get_file_name( $endpoint ) . '*' );
					}
					else {
						$files = array_merge( $files, glob( $upload_dir . '*' ) );
					}

				}

				if ( ! empty( $files ) ) {

					$data = [];

					foreach ( $files as $endpoint_key => $file ) {

						// In the case there are multiple exports of the same endpoint with distinct filters.
						$file = is_array( $file ) ? $file : [ $file ];

						foreach ( $file as $f ) {

							if ( is_file( $f ) ) {

								$json = wp_json_file_decode( $f );

								if ( $json ) {

									// If some specific endpoints were requested, make sure, the file was exported from any of those endpoints.
									if ( ! empty( $endpoints ) && ! empty( $json->endpoint ) && ! in_array( $json->endpoint, $endpoints ) ) {
										continue;
									}

									$params = ! empty( $request[ $endpoint_key ] ) ? $request[ $endpoint_key ] : '';

									// If the file was created with a filter, require the same filter here too.
									if ( ! $params && ! empty( $json->params ) ) {
										continue;
									}

									// If a filtered file is being requested, don't return an unfiltered full export file.
									if ( $params && empty( $json->params ) ) {
										continue;
									}

									// If a filtered file is being requested, only return the file with the same exact filter.
									if ( $params && ! empty( $json->params ) && $params !== $json->params ) {
										continue;
									}

									$data[ basename( $f ) ] = $json;

								}

							}

						}

					}

					if ( empty( $data ) ) {

						$response = array(
							'success' => FALSE,
							'code'    => 'no_results',
							'message' => __( 'No exported files found with the specified criteria. Please do run a new full export or try to change filters.', ATUM_TEXT_DOMAIN ),
						);

					}
					else {

						$response = array(
							'success' => TRUE,
							'data'    => $data,
						);

					}

				}
				else {

					$response = array(
						'success' => FALSE,
						'code'    => 'no_results',
						'message' => __( 'No exported files found. Please do run a new full export.', ATUM_TEXT_DOMAIN ),
					);

				}

			}
			else {

				$response = array(
					'success' => FALSE,
					'code'    => 'error',
					'message' => __( 'The export upload dir was not found.', ATUM_TEXT_DOMAIN ),
				);

			}

		}

		return rest_ensure_response( $this->prepare_item_for_response( $response, $request ) );

	}

	/**
	 * Check whether there are pending exports
	 *
	 * @since 1.9.23
	 *
	 * @return bool
	 */
	private static function are_there_pending_exports() {

		global $wpdb;

		$transient_name = '_transient_' . AtumCache::get_transient_key( self::EXPORTED_ENDPOINTS_TRANSIENT );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ! empty( $wpdb->get_col( "SELECT option_id FROM $wpdb->options WHERE option_name LIKE '{$transient_name}%'" ) );

	}

	/**
	 * Update (run) a full export
	 *
	 * @since 1.9.19
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {

		$endpoint = $request['endpoint'] ?? '';

		$status = $this->schedule_export_queue( $endpoint, $request );

		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$this->maybe_save_subscriber_id( $request['subscriber_id'] );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $status, $request );

		return rest_ensure_response( $response );

	}

	/**
	 * Prepare the export status for serialization
	 *
	 * @since 1.9.19
	 *
	 * @param  array            $item     Object.
	 * @param  \WP_REST_Request $request  Request object.
	 *
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {

		$context = empty( $request['context'] ) ? 'view' : $request['context'];
		$data    = $this->filter_response_by_context( $item, $context );

		return rest_ensure_response( $data );

	}

	/**
	 * Prepare links for the request
	 *
	 * @since 1.9.19
	 *
	 * @param string $id ID.
	 *
	 * @return array
	 */
	protected function prepare_links( $id ) {

		$base = '/' . $this->namespace . '/' . $this->rest_base;

		return array(
			'item' => array(
				'href'       => rest_url( trailingslashit( $base ) . $id ),
				'embeddable' => TRUE,
			),
		);

	}

	/**
	 * Schedule the full export queue
	 *
	 * @since 1.9.19
	 *
	 * @param string|NULL      $requested_endpoint The endpoint to export. Should be empty or NULL to export all the ATUM endpoints.
	 * @param \WP_REST_Request $request            The request.
	 *
	 * @return array|\WP_Error
	 */
	public function schedule_export_queue( $requested_endpoint, $request ) {

		$exportable_endpoints = AtumApi::get_exportable_endpoints();

		// Check if there are multiple endpoints separated by commas.
		$endpoints = $requested_endpoint ? explode( ',', $requested_endpoint ) : $exportable_endpoints;

		$exported_endpoint_transient_keys = [];

		foreach ( $endpoints as $endpoint ) {

			// Check that all the endpoints passed (if any) are valid.
			if ( $endpoint && ! in_array( $endpoint, $exportable_endpoints ) ) {
				/* translators: the endpoint path */
				return new \WP_Error( 'atum_rest_endpoint_not_found', sprintf( __( "The endpoint '%s' wasn't found.", ATUM_TEXT_DOMAIN ), $endpoint ) );
			}

			$endpoint_key = array_search( $endpoint, $exportable_endpoints );
			$params       = ! empty( $request[ $endpoint_key ] ) ? $request[ $endpoint_key ] : '';

			$exported_endpoint_transient_keys[ $endpoint ] = AtumCache::get_transient_key( self::EXPORTED_ENDPOINTS_TRANSIENT . self::get_file_name( $endpoint, $params ) );
			$exported_endpoint                             = AtumCache::get_transient( $exported_endpoint_transient_keys[ $endpoint ], TRUE );

			if ( ! empty( $exported_endpoint ) ) {

				return array(
					'success' => FALSE,
					'code'    => 'running',
					'message' => __( 'The export is still running. Please try again later.', ATUM_TEXT_DOMAIN ),
				);

			}

		}

		foreach ( $exportable_endpoints as $key => $endpoint ) {

			if ( ! in_array( $endpoint, $endpoints ) ) {
				continue;
			}

			AtumCache::set_transient( $exported_endpoint_transient_keys[ $endpoint ], $endpoint, DAY_IN_SECONDS, TRUE );

			$hook_name = "atum_api_export_endpoint_$key";

			if ( ! as_next_scheduled_action( $hook_name ) ) {
				$params = ! empty( $request[ $key ] ) ? esc_attr( $request[ $key ] ) : '';
				$this->delete_old_export( $endpoint, $params );
				as_schedule_single_action( gmdate( 'U' ), $hook_name, [ $endpoint, get_current_user_id(), $params ] );
			}

		}

		return array(
			'success' => FALSE,
			'code'    => 'started',
			'message' => __( 'The export has been started.', ATUM_TEXT_DOMAIN ),
		);

	}

	/**
	 * Get the upload dir for saving full export files
	 *
	 * @since 1.9.19
	 *
	 * @param string $type Optional. The return type (path or url).
	 *
	 * @return string|\WP_Error
	 */
	public static function get_full_export_upload_dir( $type = 'path' ) {

		$rel_path = 'atum/atum-api-full-export';

		// Define upload path & dir.
		$upload_info = wp_upload_dir();
		$upload_dir  = trailingslashit( $upload_info['basedir'] ) . $rel_path;
		$upload_url  = trailingslashit( $upload_info['baseurl'] ) . $rel_path;

		// Check if the ATUM Export upload directory already exists.
		if ( ! is_dir( $upload_dir ) ) {
			$created_dir = mkdir( $upload_dir, 0777, TRUE );

			if ( ! $created_dir ) {
				return new \WP_Error( 'atum_rest_file_system_error', __( 'Something failed when creating a temporary directory under the uploads folder, please check that you have the right permissions', ATUM_TEXT_DOMAIN ) );
			}
		}

		return trailingslashit( 'path' === $type ? $upload_dir : $upload_url );

	}

	/**
	 * Export the specified endpoint through a cron job
	 *
	 * @since 1.9.19
	 *
	 * @param string $endpoint The endpoint that is being exported.
	 * @param int    $user_id  The user ID doing that initialized the export.
	 * @param string $params   Optional. Any other params passed to the endpoint.
	 * @param int    $page     Optional. If passed, will export the specified page of results.
	 */
	public static function run_export( $endpoint, $user_id, $params = '', $page = 1 ) {

		$pending_endpoint_transient_key = AtumCache::get_transient_key( self::EXPORTED_ENDPOINTS_TRANSIENT . self::get_file_name( $endpoint, $params ) );
		$pending_endpoint               = AtumCache::get_transient( $pending_endpoint_transient_key, TRUE );

		Helpers::adjust_long_process_settings();

		if ( $pending_endpoint ) {
			AtumCache::delete_transients( $pending_endpoint_transient_key );
		}

		$page_suffix      = '';
		$logged_in_user   = get_current_user_id();
		$delete_transient = TRUE;

		// If this is reached through a cron job, there won't be any user logged in and all these endpoints need a user with permission to be logged in.
		if ( ! $logged_in_user || $logged_in_user !== $user_id ) {
			wp_set_current_user( $user_id );
		}

		// The /atum-order-notes endpoint is fake, so change the path to comments first.
		$endpoint_path = '/wc/v3/atum/atum-order-notes' === $endpoint ? '/wp/v2/comments' : $endpoint;
		$query_params  = [
			'page'     => $page,
			'per_page' => 100,
		];

		// Add extra params for some endpoints.
		switch ( $endpoint ) {
			case '/wp/v2/comments':
				$query_params['type']     = 'order_note';
				$query_params['per_page'] = 300;

				// Export only notes after a given date?
				if ( $params ) {
					$query_params['after'] = $params;
				}

				remove_filter( 'comments_clauses', array( AtumComments::get_instance(), 'exclude_atum_order_notes' ) ); // Do not add ATUM Orders type exclusions.
				remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) ); // Show the WC order notes in the WP comments API endpoint.
				break;

			case '/wc/v3/atum/atum-order-notes':
				$query_params['type']     = AtumComments::NOTES_KEY;
				$query_params['per_page'] = 300;

				// Export only notes after a given date?
				if ( $params ) {
					$query_params['after'] = $params;
				}

				remove_filter( 'comments_clauses', array( AtumComments::get_instance(), 'exclude_atum_order_notes' ) ); // Do not add ATUM Orders type exclusions.
				break;

			case '/wp/v2/media':
				$query_params['linked_post_type'] = 'atum_supplier,product';
				$query_params['per_page']         = 200;
				break;

			case '/wc/v3/customers':
				$query_params['role'] = 'all';
				break;

			case '/wc/v3/products/categories':
			case '/wc/v3/products/tags':
				$query_params['hide_empty'] = TRUE;
				break;

			case '/wc/v3/products':
				// Allow exporting products of given status(es).
				if ( $params ) {
					$query_params['atum_post_status'] = $params; // Special param.
				}
				break;

			case '/wc/v3/atum/suppliers':
			case '/wc/v3/atum/product-variations':
				// Allow exporting suppliers of given status(es).
				if ( $params ) {
					$query_params['status'] = $params;
				}
				break;

			case '/wc/v3/atum/inventory-logs':
			case '/wc/v3/orders':
			case '/wc/v3/atum/order-refunds':
			case '/wc/v3/atum/purchase-orders':
				// Allow exporting only orders after a given date?
				if ( $params ) {
					$query_params['after'] = wc_rest_prepare_date_response( $params );
				}
				break;

		}

		// Trick to be able to increase the posts per page limit (check \Atum\Api\AtumApi::increase_posts_per_page).
		$_SERVER['HTTP_ORIGIN'] = 'com.stockmanagementlabs.atum';

		// Do the request to the endpoint internally.
		$request = new \WP_REST_Request( 'GET', $endpoint_path );
		$request->set_query_params( $query_params );

		$server   = rest_get_server();
		$response = rest_do_request( $request );
		$data     = $server->response_to_data( $response, FALSE );

		if ( 200 === $response->status ) {

			if ( isset( $response->headers['X-WP-TotalPages'] ) ) {

				$total_pages = absint( $response->headers['X-WP-TotalPages'] );

				if ( $total_pages > 1 ) {

					$page_suffix = "-{$page}_{$total_pages}";

					if ( $total_pages > $page ) {
						as_schedule_single_action( gmdate( 'U' ), current_action(), [ $endpoint, $user_id, $params, $page + 1 ] );

						// Re-add the endpoint transient again because is not fully exported yet.
						AtumCache::set_transient( $pending_endpoint_transient_key, $endpoint, DAY_IN_SECONDS, TRUE );
						$delete_transient = FALSE; // Avoid removing the transient below.
					}

				}

			}

			$results = array(
				'endpoint'    => $endpoint,
				'total_pages' => ! empty( $total_pages ) ? $total_pages : 1,
				'page'        => $page,
				'per_page'    => $query_params['per_page'],
				'params'      => $params,
				'date'        => wc_rest_prepare_date_response( gmdate( 'Y-m-d H:i:s' ) ),
				'results'     => $data,
			);

			$json_results = wp_json_encode( $results );

		}
		else {
			$json_results = wp_json_encode( $data );
			$page_suffix  = '-error';
		}

		$upload_dir = self::get_full_export_upload_dir();
		$file_path  = $upload_dir . self::get_file_name( $endpoint, $params ) . "$page_suffix.json";

		if ( ! is_wp_error( $upload_dir ) ) {

			// Clean Up the temporary directory, before adding the new export there.
			if ( is_file( $file_path ) ) {
				unlink( $file_path );
			}

			// Save the file.
			file_put_contents( $file_path, $json_results ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents

		}

		if ( $delete_transient ) {
			AtumCache::delete_transients( $pending_endpoint_transient_key );
		}

		// Send the completed export notification once all the export tasks have been completed.
		if ( ! self::are_there_pending_exports() ) {

			$subscribers_transient_key = AtumCache::get_transient_key( self::SUBSCRIBER_IDS_TRANSIENT );
			$saved_subscribers         = AtumCache::get_transient( $subscribers_transient_key, TRUE );

			// If, for some instance, there are no subscribers saved, do not send the notification.
			if ( ! empty( $saved_subscribers ) ) {

				// If, for some instance, the current user was logged in with another user, restore their log-in.
				if ( $logged_in_user && get_current_user_id() !== $logged_in_user ) {
					wp_set_current_user( $logged_in_user );
				}
				// Or if wasn't logged in, close the session for security reasons.
				elseif ( ! $logged_in_user && is_user_logged_in() ) {
					wp_logout();
				}

				// Send a notification to the user(s) once the full export is completed.
				$response = wp_remote_post( self::COMPLETED_FULL_EXPORT_NOTICE_URL, [
					'timeout'   => 0.01,
					'blocking'  => FALSE,
					'sslverify' => apply_filters( 'https_local_ssl_verify', FALSE ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					'headers'   => array(
						'Origin' => home_url( '/' ),
					),
					'body'      => array(
						'subscribers' => $saved_subscribers,
					),
				] );

				if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 300 ) {
					AtumCache::delete_transients( $subscribers_transient_key );
				}

			}

		}

	}

	/**
	 * Delete an old export
	 *
	 * @since 1.9.19
	 *
	 * @param string $endpoint
	 * @param string $params
	 */
	public function delete_old_export( $endpoint = '', $params = '' ) {

		$upload_dir = self::get_full_export_upload_dir();

		if ( ! is_wp_error( $upload_dir ) ) {

			$name_pattern = self::get_file_name( $endpoint, $params );
			$files        = glob( $upload_dir . ( $endpoint ? $name_pattern . '*' : '*' ) );

			foreach ( $files as $file ) {
				// As there can be endpoints that share a part of the name pattern, make sure we remove the right ones.
				if ( is_file( $file ) && preg_match( '/' . $name_pattern . '(-\d+_\d+)?.json/', $file ) ) {
					unlink( $file );
				}
			}

		}

	}

	/**
	 * Generate a file name based on the passed endpoint and params
	 *
	 * @since 1.9.22
	 *
	 * @param string $endpoint
	 * @param string $params
	 *
	 * @return string
	 */
	private static function get_file_name( $endpoint = '', $params = '' ) {
		return str_replace( '/', '_', $endpoint ) . str_replace( [ '-', ',', ':' ], '_', $params );
	}

	/**
	 * Save the subscriber ID that initiated a full export
	 *
	 * @since 1.9.22
	 *
	 * @param string $subscriber_id
	 */
	private function maybe_save_subscriber_id( $subscriber_id ) {

		$transient_key     = AtumCache::get_transient_key( self::SUBSCRIBER_IDS_TRANSIENT );
		$saved_subscribers = AtumCache::get_transient( $transient_key, TRUE );

		if ( ! is_array( $saved_subscribers ) ) {
			$saved_subscribers = [];
		}

		if ( ! in_array( $subscriber_id, $saved_subscribers ) ) {
			$saved_subscribers[] = $subscriber_id;
		}

		AtumCache::set_transient( $transient_key, $saved_subscribers, 0, TRUE );

	}

}
