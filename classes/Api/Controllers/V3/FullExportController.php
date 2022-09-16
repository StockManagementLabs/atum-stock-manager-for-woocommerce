<?php
/**
 * REST ATUM API Full Export controller
 * Handles requests to the /atum/full-export endpoint.
 *
 * @since       1.9.19
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

use Atum\Api\AtumApi;
use Atum\Components\AtumCache;
use Atum\Components\AtumOrders\AtumComments;


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
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
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
			'context'  => $this->get_context_param( [ 'default' => 'view' ] ),
			'endpoint' => array(
				'description'       => __( 'Do the export only for the specified ATUM endpoint.', ATUM_TEXT_DOMAIN ),
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

		if ( ! empty( $this->get_pending_exports( $requested_endpoint ) ) ) {

			$response = array(
				'success' => FALSE,
				'code'    => 'running',
				'message' => __( 'The export is still running. Please try again later.', ATUM_TEXT_DOMAIN ),
			);

		}
		else {

			$upload_dir = self::get_full_export_upload_dir();

			if ( ! is_wp_error( $upload_dir ) ) {

				// Check if there are multiple endpoints separated by commas.
				$endpoints = $requested_endpoint ? explode( ',', $requested_endpoint ) : [ '' ]; // An empty array means all the endpoints.
				$files     = array();

				foreach ( $endpoints as $endpoint ) {
					$files = array_merge( $files, glob( $upload_dir . ( $endpoint ? str_replace( '/', '_', $endpoint ) . '*' : '*' ) ) );
				}

				if ( ! empty( $files ) ) {

					$data = [];

					foreach ( array_unique( $files ) as $file ) {

						if ( is_file( $file ) ) {

							$json = wp_json_file_decode( $file );

							if ( $json ) {
								$data[ basename( $file ) ] = $json;
							}

						}

					}

					$response = array(
						'success' => TRUE,
						'data'    => $data,
					);

				}
				else {

					$response = array(
						'success' => FALSE,
						'code'    => 'running',
						'message' => __( 'No exported files found. Please do run a new full export.', ATUM_TEXT_DOMAIN ),
					);

				}

			}
			else {

				$response = array(
					'success' => FALSE,
					'code'    => 'running',
					'message' => __( 'The export upload dir was not found.', ATUM_TEXT_DOMAIN ),
				);

			}

		}

		return rest_ensure_response( $this->prepare_item_for_response( $response, $request ) );

	}

	/**
	 * Get all the pending exports
	 *
	 * @param string|NULL $requested_endpoint
	 *
	 * @return string|NULL
	 */
	private function get_pending_exports( $requested_endpoint = NULL ) {

		// Create one transient per endpoint, so we can handle them separately.
		$requested_endpoints = $requested_endpoint ? explode( ',', $requested_endpoint ) : AtumApi::get_exportable_endpoints();

		foreach ( $requested_endpoints as $endpoint ) {

			$exported_endpoints_transient_key = AtumCache::get_transient_key( self::EXPORTED_ENDPOINTS_TRANSIENT . str_replace( '/', '_', $endpoint ) );
			$pending_export                   = AtumCache::get_transient( $exported_endpoints_transient_key, TRUE );

			if ( ! empty( $pending_export ) ) {
				return $pending_export; // If any of them is still pending, do not need to continue checking the rest.
			}

		}

		return NULL;

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
				'embeddable' => true,
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

			$exported_endpoint_transient_keys[ $endpoint ] = AtumCache::get_transient_key( self::EXPORTED_ENDPOINTS_TRANSIENT . str_replace( '/', '_', $endpoint ) );
			$exported_endpoint                             = AtumCache::get_transient( $exported_endpoint_transient_keys[ $endpoint ], TRUE );

			if ( ! empty( $exported_endpoint ) ) {

				return array(
					'success' => FALSE,
					'code'    => 'running',
					'message' => __( 'The export is still running. Please try again later.', ATUM_TEXT_DOMAIN ),
				);

			}

		}

		foreach ( $exportable_endpoints as $index => $endpoint ) {

			if ( ! in_array( $endpoint, $endpoints ) ) {
				continue;
			}

			AtumCache::set_transient( $exported_endpoint_transient_keys[ $endpoint ], $endpoint, HOUR_IN_SECONDS * 3, TRUE );

			$hook_name = "atum_api_export_endpoint_$index";

			if ( ! as_next_scheduled_action( $hook_name ) ) {
				$this->delete_old_export( $endpoint );
				as_schedule_single_action( gmdate( 'U' ), $hook_name, [ $endpoint, get_current_user_id() ] );
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
	 * @param int    $page     Optional. If passed, will export the specified page of results.
	 */
	public static function run_export( $endpoint, $user_id, $page = 1 ) {

		$pending_endpoint_transient_key = AtumCache::get_transient_key( self::EXPORTED_ENDPOINTS_TRANSIENT . str_replace( '/', '_', $endpoint ) );
		$pending_endpoint               = AtumCache::get_transient( $pending_endpoint_transient_key, TRUE );

		if ( $pending_endpoint ) {
			AtumCache::delete_transients( $pending_endpoint_transient_key );
		}

		$page_suffix    = '';
		$logged_in_user = get_current_user_id();

		// If this is reached through a cron job, there won't be any user logged in and all these endpoints need a user with permission to be logged in.
		if ( ! $logged_in_user || $logged_in_user !== $user_id ) {
			wp_set_current_user( $user_id );
		}

		// The /atum-order-notes endpoint is fake, so change the path to comments first.
		$endpoint_path = '/wc/v3/atum/atum-order-notes' === $endpoint ? '/wp/v2/comments' : $endpoint;
		$query_params  = [
			'page' => $page,
		];

		// Add extra params for some endpoints.
		switch ( $endpoint ) {
			case '/wp/v2/comments':
				$query_params['type']     = 'order_note';
				$query_params['per_page'] = 300;
				remove_filter( 'comments_clauses', array( AtumComments::get_instance(), 'exclude_atum_order_notes' ) ); // Do not add ATUM Orders type exclusions.
				remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) ); // Show the WC order notes in the WP comments API endpoint.
				break;

			case '/wc/v3/atum/atum-order-notes':
				$query_params['type']     = AtumComments::NOTES_KEY;
				$query_params['per_page'] = 300;
				remove_filter( 'comments_clauses', array( AtumComments::get_instance(), 'exclude_atum_order_notes' ) ); // Do not add ATUM Orders type exclusions.
				break;

			case '/wp/v2/media':
				$query_params['linked_post_type'] = 'atum_supplier,product';
				$query_params['per_page']         = 200;
				break;

			case '/wc/v3/customers':
				$query_params['role']     = 'all';
				$query_params['per_page'] = 100;
				break;

			default:
				$query_params['per_page'] = 100;
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
						as_schedule_single_action( gmdate( 'U' ), current_action(), [ $endpoint, $user_id, $page + 1 ] );

						// Re-add the endpoint transient again because is not fully exported yet.
						AtumCache::set_transient( $pending_endpoint_transient_key, $endpoint, HOUR_IN_SECONDS * 3, TRUE );
					}

				}

			}

			$results = array(
				'endpoint'    => $endpoint,
				'total_pages' => ! empty( $total_pages ) ? $total_pages : 1,
				'page'        => $page,
				'per_page'    => $query_params['per_page'],
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
		$file_path  = $upload_dir . str_replace( '/', '_', $endpoint ) . "$page_suffix.json";

		if ( ! is_wp_error( $upload_dir ) ) {

			// Clean Up the temporary directory, before adding the new export there.
			if ( is_file( $file_path ) ) {
				unlink( $file_path );
			}

			// Save the file.
			file_put_contents( $file_path, $json_results ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents

		}

		AtumCache::delete_transients( $pending_endpoint_transient_key );

		if ( empty( $pending_endpoint ) ) {

			// If, for some instance, the current user was logged in with another user, restore their log in.
			if ( $logged_in_user && get_current_user_id() !== $logged_in_user ) {
				wp_set_current_user( $logged_in_user );
			}
			// Or if wasn't logged in, close the session for security reasons.
			elseif ( ! $logged_in_user && is_user_logged_in() ) {
				wp_logout();
			}

			// Send a notification to the customer once the full export is completed.
			wp_remote_get( self::COMPLETED_FULL_EXPORT_NOTICE_URL, [
				'timeout'   => 0.01,
				'blocking'  => FALSE,
				'sslverify' => apply_filters( 'https_local_ssl_verify', FALSE ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			] );

		}

	}

	/**
	 * Delete an old export
	 *
	 * @since 1.9.19
	 *
	 * @param string $endpoint
	 */
	public function delete_old_export( $endpoint = '' ) {

		$upload_dir = self::get_full_export_upload_dir();

		if ( ! is_wp_error( $upload_dir ) ) {

			$files = glob( $upload_dir . ( $endpoint ? str_replace( '/', '_', $endpoint ) . '*' : '*' ) );

			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					unlink( $file );
				}
			}

		}

	}

}
