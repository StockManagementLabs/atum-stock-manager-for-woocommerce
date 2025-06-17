<?php
/**
 * REST ATUM API Full Export controller
 * Handles requests to the /atum/full-export endpoint.
 *
 * @since       1.9.19
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

use Atum\Api\AtumApi;
use Atum\Api\Generators\Generator;
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
	const EXPORTED_ENDPOINTS_TRANSIENT = 'api_run_full_export_endpoints_';

	/**
	 * Transient key name for the list of App subscribers that initiated a full export.
	 */
	const SUBSCRIBER_IDS_TRANSIENT = 'api_run_full_export_subscribers';

	/**
	 * Transient key name for the SQLite dump config.
	 */
	const DUMP_CONFIG_TRANSIENT = 'api_run_full_export_dump_config';

	/**
	 * Cloud function to send a notification to the App user when the full export is completed.
	 */
	const COMPLETED_FULL_EXPORT_NOTICE_URL = 'https://us-central1-atum-app.cloudfunctions.net/completedFullExport';

	const DEBUG_MODE = FALSE;

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
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
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
			'format'             => array(
				'description'       => __( 'Firebase subscriber ID to notify after a successful export.', ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'enum'              => array(
					'json',       // Export to a single JSON file.
					'json_zip',   // Export to a ZIP file with all the JSON files.
					'sqlite',     // Export to SQLite dump file.
					//'mock',       // TODO: REMOVE THIS WHEN THE APP IS RELEASED.
				),
				'default'           => 'json',
				'required'          => FALSE,
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'storeId'           => array(
				'description'       => __( "The app's internal store ID.", ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'userId'           => array(
				'description'       => __( "The app's internal user ID.", ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'revision'           => array(
				'description'       => __( "The app's internal revision ID.", ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'storeSettingsId'           => array(
				'description'       => __( "The app's internal atum store settings ID.", ATUM_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

	}

	/**
	 * Check whether a given request has permission to view the full export status
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
	public function create_item_permissions_check( $request ) {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error( 'atum_rest_cannot_update', __( 'Sorry, you are not allowed to run a full export.', ATUM_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Return the full export status (if running) or data (if completed)
	 *
	 * @since 1.9.19
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {

		$requested_endpoint = $request['endpoint'] ?? '';
		$format             = $request['format'] ?? 'json';

		// Still running.
		if ( self::are_there_pending_exports() ) {

			$response = array(
				'success' => FALSE,
				'code'    => 'running',
				'message' => __( 'The export is still running. Please try again later.', ATUM_TEXT_DOMAIN ),
			);

		}
		// TODO: remove this when the App is released.
		// Return a mock zip file with all the JSON files for testing purposes.
		/*elseif ( 'mock' === $format && defined( 'ATUM_DEBUG' ) && TRUE === ATUM_DEBUG ) {

			$page     = ! empty( $request['page'] ) ? $request['page'] : '';
			$zip_name = self::get_full_export_upload_dir() . "mock{$page}.zip";

			if ( file_exists( $zip_name ) ) {

				$headers = [
					'Content-Type'              => 'application/zip',
					'Content-Transfer-Encoding' => 'Binary',
					'Content-Length'            => filesize( $zip_name ),
					'Content-Disposition'       => 'attachment; filename="' . basename( $zip_name ) . '"',
				];

				$server = rest_get_server();

				nocache_headers();

				foreach ( $headers as $header => $data ) {
					$server->send_header( $header, $data );
				}

				// Fix CORS issues through localhost requests.
				// NOTE: this shouldn't imply any security risk because when we reach this point, the authenticated request was previously passed.
				$server->send_header( 'Access-Control-Allow-Origin', '*' );

				readfile( $zip_name );
				die(); // We've already sent the file, so we can stop the execution here.

			}
			else {
				$response = array(
					'success' => FALSE,
					'code'    => 'no_results',
					'message' => __( 'Mock file not found.', ATUM_TEXT_DOMAIN ),
				);
			}

		}*/
		// Prepare a zip file with all the exported JSON files and return it.
		elseif ( 'json_zip' === $format ) {
			$response = $this->export_json_zip( $requested_endpoint );
		}
		elseif ( 'json' === $format ) {
			$response = $this->export_json_files( $requested_endpoint );
		}
		elseif ( 'sqlite' === $format ) {

			$user_app_id = $request['userId'] ?? '';

			if ( empty( $user_app_id ) ) {

				$response = array(
					'success' => FALSE,
					'code'    => 'error',
					'message' => __( "If you want to retrieve an SQLite dump file, please send the app's userId as param.", ATUM_TEXT_DOMAIN ),
				);

			}
			else {
				$response = $this->export_dump_file( $user_app_id, $request['schema'] ?? '' );
			}

		}
		else {

			$response = array(
				'success' => FALSE,
				'code'    => 'error',
				'message' => __( 'Invalid format requested.', ATUM_TEXT_DOMAIN ),
			);

		}

		return rest_ensure_response( $this->prepare_item_for_response( $response, $request ) );

	}

	/**
	 * Export the JSON files into a ZIP file
	 *
	 * @since 1.9.42
	 *
	 * @param string $requested_endpoint
	 *
	 * @return array|void
	 */
	private function export_json_zip( $requested_endpoint ) {

		$upload_dir = self::get_full_export_upload_dir();
		$files 		= self::get_exported_files( $requested_endpoint );

		if ( empty( $files ) ) {

			return array(
				'success' => FALSE,
				'code'    => 'no_results',
				'message' => __( 'No exported files found. Please run a new full export.', ATUM_TEXT_DOMAIN ),
			);

		}

		$zip      = new \ZipArchive();
		$zip_name = $upload_dir . 'atum-full-export.zip';

		if ( $zip->open( $zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) === TRUE ) {

			foreach ( $files as $file ) {

				// In the case there are multiple exports of the same endpoint with distinct filters.
				$file = is_array( $file ) ? $file : [ $file ];

				foreach ( $file as $f ) {
					if ( is_file( $f ) ) {
						$zip->addFile( $f, basename( $f ) );
					}
				}

			}

			$zip->close();

			$headers = [
				'Content-Type'              => 'application/zip',
				'Content-Transfer-Encoding' => 'Binary',
				'Content-Length'            => filesize( $zip_name ),
				'Content-Disposition'       => 'attachment; filename="' . basename( $zip_name ) . '"',
			];

			$server = rest_get_server();

			nocache_headers();

			foreach ( $headers as $header => $data ) {
				$server->send_header( $header, $data );
			}

			// Fix CORS issues through localhost requests.
			// NOTE: this shouldn't imply any security risk because when we reach this point, the authenticated request was previously passed.
			$server->send_header( 'Access-Control-Allow-Origin', '*' );

			readfile( $zip_name );
			unlink( $zip_name );
			die(); // We've already sent the file, so we can stop the execution here.

		}
		else {

			$response = array(
				'success' => FALSE,
				'code'    => 'error',
				'message' => __( 'The export files could not be zipped. Please, enable the PHP zip extension on your web server.', ATUM_TEXT_DOMAIN ),
			);

		}

		return $response;

	}

	/**
	 * Return the JSON files.
	 *
	 * @since 1.9.42
	 *
	 * @param string $requested_endpoint
	 *
	 * @return array
	 */
	private function export_json_files( $requested_endpoint ) {

		$exportable_endpoints = AtumApi::get_exportable_endpoints();

		// Check if there are multiple endpoints separated by commas.
		$endpoint_keys  = $requested_endpoint ? array_filter( array_unique( explode( ',', $requested_endpoint ) ) ) : array_keys( $exportable_endpoints );

		$files = self::get_exported_files( $requested_endpoint );

		if ( is_wp_error( $files ) ) {

			return array(
				'success' => FALSE,
				'code'    => 'error',
				'message' => $files->get_error_message(),
			);

		}

		$data = [];

		foreach ( $files as $file ) {

			// In the case there are multiple exports of the same endpoint with distinct filters or sub-endpoints.
			$file = is_array( $file ) ? Helpers::flat_array( $file ) : [ $file ];

			foreach ( $file as $f ) {

				if ( is_file( $f ) ) {

					$json = wp_json_file_decode( $f );

					if ( $json ) {

						// If some specific endpoints were requested, make sure, the file was exported from any of those endpoints.
						if ( ! empty( $endpoint_keys ) && ! empty( $json->schema ) && ! in_array( $json->schema, $endpoint_keys ) ) {
							continue;
						}

						$params = ( ! empty( $json->schema ) && ! empty( $request[ $json->schema ] ) ) ? $request[ $json->schema ] : '';

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
				'message' => __( 'No exported files found with the specified criteria. Please run a new full export or try to change filters.', ATUM_TEXT_DOMAIN ),
			);

		}
		else {

			$response = array(
				'success' => TRUE,
				'data'    => $data,
			);

		}

		return $response;

	}

	/**
	 * Export the generated SQL dump file
	 *
	 * @since 1.9.44
	 *
	 * @param string $user_app_id
	 * @param string $schema
	 *
	 * @return array|void
	 */
	private function export_dump_file( $user_app_id, $schema = '' ) {

		$upload_dir = self::get_full_export_upload_dir();
		$dump_files = glob( $upload_dir . "*$user_app_id.sql" );

		if ( ! $dump_files ) {
			return array(
				'success' => FALSE,
				'code'    => 'error',
				'message' => __( 'The dump was not found. Please, run the full export again.', ATUM_TEXT_DOMAIN ),
			);
		}
		else {

			$headers = [
				'Content-Type'        => 'text/sql',
				'Content-Disposition' => 'attachment; filename="atum_dump_' . $user_app_id . '.sql"',
			];

			$server = rest_get_server();

			nocache_headers();

			foreach ( $headers as $header => $data ) {
				$server->send_header( $header, $data );
			}

			// Fix CORS issues through localhost requests.
			// NOTE: this shouldn't imply any security risk because when we reach this point, the authenticated request was previously passed.
			$server->send_header( 'Access-Control-Allow-Origin', '*' );

			if ( $schema ) {

				// In case there are multiple schemas separated by commas.
				$schema_arr = array_map( 'trim', explode( ',', $schema ) );

				foreach ( $schema_arr as $s ) {

					$dump_file = $upload_dir . "atum_dump_{$s}_{$user_app_id}.sql";
					if ( file_exists( $dump_file ) ) {
						readfile( $dump_file );
					}
					else {
						return array(
							'success' => FALSE,
							'code'    => 'error',
							'message' => sprintf( __( "No dump found for schema '%s'.", ATUM_TEXT_DOMAIN ), $s ),
						);
					}

				}

			}
			else {

				// If no specific schema is passed, read all the dump files.
				foreach ( $dump_files as $dump_file ) {
					readfile( $dump_file );
				}

			}

			die(); // We've already sent the file, so we can stop the execution here.

		}

	}

	/**
	 * Get the exported files
	 *
	 * @since 1.9.44
	 *
	 * @param string|null $requested_endpoint
	 *
	 * @return array|\WP_Error
	 */
	private static function get_exported_files( $requested_endpoint = NULL ) {

		$exportable_endpoints = AtumApi::get_exportable_endpoints();

		// Check if there are multiple endpoints separated by commas.
		if ( $requested_endpoint ) {
			$endpoints  = self::find_exportable_endpoints( array_filter( array_unique( explode( ',', $requested_endpoint ) ) ) );
		}
		else {
			$endpoints = $exportable_endpoints;
		}

		$upload_dir = self::get_full_export_upload_dir();
		$files      = [];

		if ( is_wp_error( $upload_dir ) ) {
			return new \WP_Error( 'atum_rest_no_upload_dir', __( 'The export upload dir was not found.', ATUM_TEXT_DOMAIN ) );
		}

		foreach ( $endpoints as $schema => $endpoint ) {

			if ( is_array( $endpoint ) ) {

				foreach ( $endpoint as $sub_key => $sub_ep ) {

					$found_files = glob( $upload_dir . self::get_file_name( $sub_ep, $schema )  . '*.json' );
					natsort( $found_files );

					if ( $found_files ) {
						$files[ $schema ][ $sub_key ] = $found_files;
					}

				}

			}
			else {

				$found_files = glob( $upload_dir . self::get_file_name( $endpoint, $schema )  . '*.json' );
				natsort( $found_files );

				if ( $found_files ) {
					$files[ $schema ] = $found_files;
				}

			}

		}

		if ( empty( $files ) ) {
			return new \WP_Error( 'atum_rest_no_exported_files', __( 'No exported files found. Please run a new full export.', ATUM_TEXT_DOMAIN ) );
		}

		return $files;

	}

	/**
	 * Generate an SQL `dump` file with the ATUM App's SQLite structure
	 *
	 * @since 1.9.44
	 *
	 * @param string $endpoint    The endpoint to export.
	 * @param int    $user_id     The user ID.
	 * @param string $user_app_id The user's App ID.
	 *
	 * @return array|true
	 */
	public static function generate_sql_dump( $endpoint, $user_id, $user_app_id ) {

		$dump_data = '';
		$files     = self::get_exported_files( $endpoint );

		if ( is_wp_error( $files ) ) {

			error_log( $files->get_error_message() );

			return array(
				'success' => FALSE,
				'code'    => 'error',
				'message' => $files->get_error_message(),
			);

		}

		$transient_key = AtumCache::get_transient_key( self::DUMP_CONFIG_TRANSIENT, [ $user_app_id ] );
		$dump_config   = AtumCache::get_transient( $transient_key, TRUE );

		if ( empty( $dump_config ) ) {

			$dump_error_msg = __( 'The dump configuration transient was not found. Please, run the full export again.', ATUM_TEXT_DOMAIN );
			error_log( $dump_error_msg );

			return array(
				'success' => FALSE,
				'code'    => 'error',
				'message' => $dump_error_msg,
			);

		}

		// If this is reached through a cron job, there won't be any user logged in and all these endpoints need a user with permission to be logged in.
		self::prepare_cron_job_user( $user_id );

		// TODO: SHOULD WE DUMP EVERY SINGLE FILE SEPARATELY IN A CRON TO AVOID CRASHES (FOR CASES OF ENTITIES WITH MANY PAGES)?
		foreach ( $files as $file ) {

			// In case there are multiple exports of the same endpoint with distinct filters or sub-endpoints.
			$file = is_array( $file ) ? Helpers::flat_array( $file ) : [ $file ];
			natsort( $file );

			foreach ( $file as $f ) {

				if ( is_file( $f ) ) {

					$json = wp_json_file_decode( $f, [ 'associative' => TRUE ] );

					if ( $json ) {

						// Use the appropriate generator for the endpoint.
						try {

							if ( ! empty( $json['code'] ) && ! empty( $json['message'] ) ) {

								// There will be sites that have no premium add-ons, so avoid failing when exporting its settings.
								if ( 'atum_rest_setting_group_invalid' === $json['code'] ) {
									continue;
								}

								throw new \Exception( sprintf( __( "There is an error in the '$1%s' exported file: '$2%s'. Please run a new full export", ATUM_TEXT_DOMAIN ), basename( $f ), $json['message'] ) );

							}

							// Initialize the generator with table name components
							$generator = new Generator(
								$json['schema'],
								$dump_config,
								array( $json['page'], $json['total_pages'] )
							);

							Helpers::adjust_long_process_settings();

							// Generate SQL statements for the specific endpoint.
							$dump_data .= $generator->generate( $json );

						} catch ( \Throwable $e ) {

							$dump_error_msg =  $e->getMessage();
							error_log( 'ATUM Full export error: ' . $dump_error_msg );

							/*return array(
								'success' => FALSE,
								'code'    => 'error',
								'message' => $e->getMessage(),
							);*/

						}

					}

				}
			}
		}

		$dump_file = self::get_dump_file( $user_app_id, self::find_endpoint_schema( $endpoint ) );
		$written   = file_put_contents( $dump_file, $dump_data, FILE_APPEND );

		if ( $written !== FALSE ) {

			if ( file_exists( $dump_file ) ) {

				// Send the completed export notification once all the export tasks have been completed.
				if ( ! self::are_there_pending_exports() ) {
					self::notify_subscriber( $user_id );
				}

				return TRUE;

			}

			$dump_error_msg = __( "The dump file couldn't be generated.", ATUM_TEXT_DOMAIN );
			error_log( 'ATUM Full export error: ' . $dump_error_msg );

		}
		else {
			$dump_error_msg = __( "The data couldn't be added to the dump file.", ATUM_TEXT_DOMAIN );
			error_log( 'ATUM Full export error: ' . $dump_error_msg );
		}

		return array(
			'success' => FALSE,
			'code'    => 'error',
			'message' => $dump_error_msg,
		);

	}

	/**
	 * Check whether there are pending exports
	 *
	 * @since 1.9.23
	 *
	 * @return bool
	 */
	public static function are_there_pending_exports( $check_transients = TRUE ) {

		global $wpdb;

		$pending_transients = [];

		if ( $check_transients ) {
			$transient_name = '_transient_' . AtumCache::get_transient_key( self::EXPORTED_ENDPOINTS_TRANSIENT );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$pending_transients = $wpdb->get_col( "SELECT option_id FROM $wpdb->options WHERE option_name LIKE '{$transient_name}%'" );
		}

		$pending_actions = $wpdb->get_col( $wpdb->prepare( "
			SELECT action_id FROM {$wpdb->prefix}actionscheduler_actions 
			WHERE status IN (%s, %s)
			AND ( hook LIKE 'atum_api_dump_endpoint%' OR hook LIKE 'atum_api_export_endpoint_%' )
		", \ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_RUNNING ) );

		return ! empty( $pending_transients ) && ! empty( $pending_actions );

	}

	/**
	 * Create (run) a full export
	 *
	 * @since 1.9.19
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function create_item( $request ) {

		// It can be one or multiple endpoint keys separated by commas.
		$endpoint = $request['endpoint'] ?? '';

		$status = $this->schedule_export_queue( $endpoint, $request );

		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$this->maybe_save_subscriber_id( $request['subscriber_id'] );

		$format = $request['format'] ?? 'json';

		if ( 'sqlite' === $format ) {
			$saved_dump_config = $this->maybe_save_dump_config( $request );

			if ( is_wp_error( $saved_dump_config ) ) {
				return $saved_dump_config;
			}
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
		if ( $requested_endpoint ) {

			$requested_endpoint = array_filter( array_unique( explode( ',', $requested_endpoint ) ) );
			$endpoints          = [];

			foreach ( $requested_endpoint as $ep ) {

				$found = FALSE;

				foreach ( $exportable_endpoints as $schema => $endpoint ) {

					// Handle nested endpoints.
					if ( is_array( $endpoint ) && in_array( $ep, $endpoint ) ) {
						$endpoints[ $schema ][] = $ep;
						$found = TRUE;
						break;
					}
					elseif ( $endpoint === $ep ) {
						$endpoints[ $schema ] = $ep;
						$found = TRUE;
						break;
					}

				}

				if ( ! $found ) {
					// Endpoint not found.
					return new \WP_Error( 'atum_rest_invalid_endpoint', __( 'Invalid endpoint specified.', ATUM_TEXT_DOMAIN ) );
				}
			}
		}
		else {
			$endpoints = $exportable_endpoints;
		}

		$exported_endpoint_transient_keys = [];

		foreach ( $endpoints as $schema => $endpoint ) {

			$params            = ! empty( $request[ $schema ] ) ? $request[ $schema ] : '';
			$exported_endpoint = NULL;

			if ( is_array( $endpoint ) ) {

				foreach ( $endpoint as $sub_key => $sub_ep ) {
					$exported_endpoint_transient_keys[ "{$schema}.{$sub_key}" ] = AtumCache::get_transient_key( self::EXPORTED_ENDPOINTS_TRANSIENT . self::get_file_name( $sub_ep, $schema, $params ) );
					$exported_endpoint[] = AtumCache::get_transient( $exported_endpoint_transient_keys[ "{$schema}.{$sub_key}" ], TRUE );
				}

				$exported_endpoint = array_filter( $exported_endpoint );

			}
			else {
				$exported_endpoint_transient_keys[ $schema ] = AtumCache::get_transient_key( self::EXPORTED_ENDPOINTS_TRANSIENT . self::get_file_name( $endpoint, $schema, $params ) );
				$exported_endpoint                           = AtumCache::get_transient( $exported_endpoint_transient_keys[ $schema ], TRUE );
			}

			if ( ! empty( $exported_endpoint ) ) {
				return array(
					'success' => FALSE,
					'code'    => 'running',
					'message' => __( 'The export is still running. Please try again later.', ATUM_TEXT_DOMAIN ),
				);
			}

		}

		$format 	 = $request['format'] ?? 'json';
		$user_app_id = $request['userId'] ?? NULL;

		// Schedule the export for each endpoint.
		foreach ( $endpoints as $key => $endpoint ) {

			$params = ! empty( $request[ $key ] ) ? esc_attr( $request[ $key ] ) : '';

			// Nested endpoints.
			if ( is_array( $endpoint ) ) {

				foreach ( $endpoint as $sub_key => $sub_ep ) {

					AtumCache::set_transient( $exported_endpoint_transient_keys[ "{$key}.{$sub_key}" ], $sub_ep, WEEK_IN_SECONDS, TRUE );

					$hook_name = "atum_api_export_endpoint_{$key}_{$sub_key}";
					$hook_args = [ $sub_ep, self::get_admin_user(), $params, 1, $format, $user_app_id ];

					if ( ! as_next_scheduled_action( $hook_name, $hook_args, 'atum' ) ) {
						$this->delete_old_export( $sub_ep, $key, $params );

						/**
						 * Hook args: endpoint, user_id, param, page, format and user_app_id.
						 * NOTE: These are the parameters that are passed later to the run_export method.
						 */
						as_enqueue_async_action( $hook_name, $hook_args, 'atum' );
					}

				}

			}
			// Non-nested endpoints.
			else {

				AtumCache::set_transient( $exported_endpoint_transient_keys[ $key ], $endpoint, WEEK_IN_SECONDS, TRUE );

				$hook_name = "atum_api_export_endpoint_$key";
				$hook_args = [ $endpoint, self::get_admin_user(), $params, 1, $format, $user_app_id ];

				if ( ! as_next_scheduled_action( $hook_name, $hook_args, 'atum' ) ) {
					$this->delete_old_export( $endpoint, $key, $params );

					/**
					 * Hook args: endpoint, user_id, param, page, format and user_app_id.
					 * NOTE: These are the parameters that are passed later to the run_export method.
					 */
					as_enqueue_async_action( $hook_name, $hook_args, 'atum' );
				}

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

		$rel_path   = 'atum-api-full-export';
		$upload_dir = Helpers::get_atum_uploads_dir() . $rel_path;

		// Check if the ATUM Full Export upload directory already exists.
		if ( ! is_dir( $upload_dir ) ) {

			$success = mkdir( $upload_dir, 0775, TRUE );

			if ( ! $success ) {
				return new \WP_Error( 'atum_rest_upload_dir_creation_failed', __( 'Something failed when creating a temporary directory under the uploads folder, please check that you have the right permissions', ATUM_TEXT_DOMAIN ) );
			}

		}

		if ( 'path' === $type ) {
			return trailingslashit( $upload_dir );
		}

		return trailingslashit( Helpers::get_atum_uploads_dir( 'url' ) . $rel_path );

	}

	/**
	 * Get the dump file name
	 *
	 * @since 1.9.44
	 *
	 * @param string $user_app_id
	 * @param string $schema
	 *
	 * @return string
	 */
	public static function get_dump_file( $user_app_id, $schema ) {
		return self::get_full_export_upload_dir() . "atum_dump_{$schema}_{$user_app_id}.sql";
	}

	/**
	 * Export the specified endpoint through a cron job
	 *
	 * @since 1.9.19
	 *
	 * @param string $endpoint 	  The endpoint that is being exported.
	 * @param int    $user_id  	  The ID of the user that initialized the export.
	 * @param string $params   	  Optional. Any other params passed to the endpoint.
	 * @param int    $page     	  Optional. If passed, will export the specified page of results.
	 * @param string $format   	  Optional. The format of the export (json, json_zip, sqlite).
	 * @param string $user_app_id Optional. The app's internal user ID.
	 */
	public static function run_export( $endpoint, $user_id, $params = '', $page = 1, $format = 'json', $user_app_id = NULL ) {

		// Find the schema for the endpoint.
		$schema = self::find_endpoint_schema( $endpoint );

		if ( ! $schema ) {
			// Endpoint not found.
			return;
		}

		try {

			$pending_endpoint_transient_key = AtumCache::get_transient_key( self::EXPORTED_ENDPOINTS_TRANSIENT . self::get_file_name( $endpoint, $schema, $params ) );
			AtumCache::delete_transients( $pending_endpoint_transient_key );

			Helpers::adjust_long_process_settings();

			$page_suffix      = '';
			$has_missing_data = FALSE;
	
			// If this is reached through a cron job, there won't be any user logged in and all these endpoints need a user with permission to be logged in.
			self::prepare_cron_job_user( $user_id );
	
			// The /atum-order-notes endpoint is fake, so change the path to comments first.
			$endpoint_path = '/wc/v3/atum/atum-order-notes' === $endpoint ? '/wp/v2/comments' : $endpoint;
			$query_params  = [
				'page'     => $page,
				'per_page' => apply_filters( 'atum/api/full_export_endpoint/posts_per_page', 150 ),
			];
	
			// Add extra params for some endpoints.
			switch ( $endpoint ) {
				case '/wp/v2/comments':
					$query_params['type'] = 'order_note';
	
					// Export only notes after a given date?
					if ( $params ) {
						$query_params['after'] = $params;
					}
	
					remove_filter( 'comments_clauses', array( AtumComments::get_instance(), 'exclude_atum_order_notes' ) ); // Do not add ATUM Orders type exclusions.
					remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) ); // Show the WC order notes in the WP comments API endpoint.
					break;
	
				case '/wc/v3/atum/atum-order-notes':
					$query_params['type'] = AtumComments::NOTES_KEY;
	
					// Export only notes after a given date?
					if ( $params ) {
						$query_params['after'] = $params;
					}
	
					remove_filter( 'comments_clauses', array( AtumComments::get_instance(), 'exclude_atum_order_notes' ) ); // Do not add ATUM Orders type exclusions.
					break;
	
				case '/wp/v2/media':
					$query_params['linked_post_type'] = 'atum_supplier,product';
					break;
	
				case '/wc/v3/customers':
					$query_params['role'] 	  = 'all';
					$query_params['per_page'] = 100; // For now, we couldn't find a way to increase the per_page limit here.
					break;
	
				case '/wc/v3/products/attributes':
					$query_params['with_terms'] = 'yes';
					break;
	
				case '/wc/v3/products/categories':
				case '/wc/v3/products/tags':
					$query_params['hide_empty'] = TRUE;
					$query_params['per_page'] = 100; // For now, we couldn't find a way to increase the per_page limit here.
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
	
				case '/wc/v3/taxes':
				case '/wc/v3/taxes/classes':
					$query_params['per_page'] = 100; // Not need to increase the limit here.
					break;
	
			}
	
			// Trick to be able to increase the posts per page limit (check \Atum\Api\AtumApi::increase_posts_per_page).
			$_SERVER['HTTP_ORIGIN'] = 'com.stockmanagementlabs.atum';
	
			// Do the request to the API endpoint internally.
			$request = new \WP_REST_Request( 'GET', $endpoint_path );
			$request->set_query_params( $query_params );
	
			$server            = rest_get_server();
			$response          = rest_do_request( $request );
			$data              = $server->response_to_data( $response, FALSE );
			$current_hook_name = current_action();
	
			if ( 200 === $response->status ) {
	
				if ( isset( $response->headers['X-WP-TotalPages'] ) ) {
	
					$total_pages = absint( $response->headers['X-WP-TotalPages'] );
	
					if ( $total_pages > 1 ) {
	
						$page_suffix = "-{$page}_{$total_pages}";
	
						if ( $total_pages > $page ) {
	
							/**
							 * Hook args: endpoint, user_id, param, page, format and user_app_id.
							 * NOTE: These are the parameters that are passed later to the run_export method.
							 * NOTE2: This hook cannot be unique because the previous page schedule is still running here.
							 */
							$hook_args = [ $endpoint, $user_id, $params, $page + 1, $format, $user_app_id ];
							$scheduled = as_enqueue_async_action( $current_hook_name, $hook_args, 'atum' );
	
							if ( ! $scheduled ) {
									error_log( "ATUM: The next page of the endpoint $endpoint couldn't be scheduled: " );
									error_log( 'Hook args: ' . var_export( $hook_args, TRUE ) );
							}
	
							// Re-add the endpoint transient again because is not fully exported yet.
							AtumCache::set_transient( $pending_endpoint_transient_key, $endpoint, WEEK_IN_SECONDS, TRUE );
							$has_missing_data = TRUE; // Avoid removing the transient below.
	
						}
	
					}
	
				}
	
				$results = array(
					'endpoint'    => $endpoint,
					'schema'	  => $schema,
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
				error_log( "ATUM: The API request to the endpoint $endpoint failed." );
				$json_results = wp_json_encode( $data );
				$page_suffix  = '-error';
			}
	
			$upload_dir = self::get_full_export_upload_dir();
			$file_path  = $upload_dir . self::get_file_name( $endpoint, $schema, $params ) . "$page_suffix.json";
	
			if ( ! is_wp_error( $upload_dir ) ) {
	
				// Clean Up the temporary directory, before adding the new export there.
				if ( is_file( $file_path ) ) {
					unlink( $file_path );
				}
	
				// Save the file.
				file_put_contents( $file_path, $json_results ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
	
			}
	
			if ( ! $has_missing_data ) {
	
				// If the format is SQLite, add a final scheduled action to process the data and add the queries to the dump file.
				if ( 'sqlite' === $format )  {
	
					$hook_name = str_replace( 'atum_api_export_endpoint_', 'atum_api_dump_endpoint_', $current_hook_name );
					$hook_args = [ $endpoint, $user_id, $user_app_id ];
	
					if ( ! as_next_scheduled_action( $hook_name, $hook_args, 'atum' ) ) {
						as_enqueue_async_action( $hook_name, $hook_args, 'atum' );
					}
	
				}
	
				AtumCache::delete_transients( $pending_endpoint_transient_key );
	
			}
	
			// Send the completed export notification once all the export tasks have been completed.
			if ( ! self::are_there_pending_exports() && 'sqlite' !== $format ) {
				self::notify_subscriber( $user_id );
			}

		} catch ( \Throwable $e ) {

			error_log( 'ATUM Error: ' . $e->getMessage() );

		}

	}

	/**
	 * Find the schema for the endpoint
	 *
	 * @since 1.9.44
	 *
	 * @param string $endpoint
	 *
	 * @return false|string
	 */
	public static function find_endpoint_schema( $endpoint ) {

		$exportable_endpoints = AtumApi::get_exportable_endpoints();

		// Find the schema for the endpoint.
		$schema = FALSE;
		foreach ( $exportable_endpoints as $key => $ep ) {

			// Nested endpoints.
			if ( is_array( $ep ) && in_array( $endpoint, $ep ) ) {
				$schema = $key;
				break;
			}
			elseif ( $ep === $endpoint ) {
				$schema = $key;
				break;
			}

		}

		return $schema;

	}

	/**
	 * Find and validates exportable endpoints
	 *
	 * @since 1.9.44
	 *
	 * @param string[] $endpoints
	 *
	 * @return array
	 */
	private static function find_exportable_endpoints( $endpoints ) {

		$exportable_endpoints = AtumApi::get_exportable_endpoints();
		$found_endpoints      = [];

		// Find the exportable endpoint within the array.
		foreach ( $endpoints as $endpoint ) {

			foreach ( $exportable_endpoints as $schema => $ep ) {

				// Handle nested endpoints (like store-settings)
				if ( is_array( $ep ) ) {

					// Check if the endpoint exists in the nested array
					$nested_match = array_filter( $ep, function ( $sub_endpoint ) use ( $endpoint ) {

						return $sub_endpoint === $endpoint;
					} );

					if ( ! empty( $nested_match ) ) {
						// Find the key of the matched endpoint
						$matched_key                                = array_search( $endpoint, $ep );
						$found_endpoints[ $schema ][ $matched_key ] = $endpoint;
					}
				}
				// Handle non-nested endpoints
				elseif ( $ep === $endpoint ) {
					$found_endpoints[ $schema ] = $ep;
				}
			}
		}

		return array_filter( $found_endpoints );
	}

	/**
	 * Notify the subscriber that the full export has been completed
	 *
	 * @since 1.9.44
	 *
	 * @param int $user_id
	 */
	private static function notify_subscriber( $user_id ) {

		$subscribers_transient_key = AtumCache::get_transient_key( self::SUBSCRIBER_IDS_TRANSIENT );
		$saved_subscribers         = AtumCache::get_transient( $subscribers_transient_key, TRUE );

		// If, for some instance, there are no subscribers saved, do not send the notification.
		if ( ! empty( $saved_subscribers ) ) {

			// If the user was logged in with another user before the export process started, restore their log-in.
			if ( get_current_user_id() !== $user_id ) {
				wp_set_current_user( $user_id );
			}
			// Or if they weren't logged in, close the session for security reasons.
			elseif ( ! $user_id && is_user_logged_in() ) {
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

			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) < 300 ) {
				error_log( 'ATUM: Error sending the push notification to the app user: ' );
				error_log( is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_message( $response ) );
			}

			AtumCache::delete_transients( $subscribers_transient_key );

		}

	}

	/**
	 * Delete an old export
	 *
	 * @since 1.9.19
	 *
	 * @param string $endpoint
	 * @param string $schema
	 * @param string $params
	 */
	public function delete_old_export( $endpoint = '', $schema = '', $params = '' ) {

		$upload_dir = self::get_full_export_upload_dir();

		if ( ! is_wp_error( $upload_dir ) ) {

			$name_pattern = self::get_file_name( $endpoint, $schema, $params );
			$files        = glob( $upload_dir . ( $endpoint ? "$name_pattern*.json" : '*.json' ) );
			$files        = array_merge( $files, glob( $upload_dir . '*.{sql,zip}', \GLOB_BRACE ) );

			foreach ( $files as $file ) {
				// As there can be endpoints that share a part of the name pattern, make sure we remove the right ones.
				if ( is_file( $file ) ) {
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
	 * @param string $schema
	 * @param string $params
	 *
	 * @return string
	 */
	public static function get_file_name( $endpoint = '', $schema = '', $params = '' ) {

		$name_parts = [
			$schema,
			str_replace( '/', '_', ltrim( $endpoint, '/' ) ),
			str_replace( [ '-', ',', ':' ], '_', $params ),
		];

		return implode( '-', array_filter( $name_parts ) );

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

		AtumCache::set_transient( $transient_key, $saved_subscribers, WEEK_IN_SECONDS, TRUE );

	}

	/**
	 * Save the SQLite dump config
	 *
	 * @since 1.9.44
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|bool
	 */
	private function maybe_save_dump_config( $request ) {

		$store_app_id          = $request['storeId'] ?? '';
		$user_app_id           = $request['userId'] ?? '';
		$app_revision          = $request['revision'] ?? '';
		$store_settings_app_id = $request['storeSettingsId'] ?? '';

		if ( empty( $store_app_id ) || empty( $user_app_id ) || empty( $app_revision ) || empty( $store_settings_app_id ) ) {
			return new \WP_Error( 'atum_rest_malformed_request', __( 'If you want to retrieve an SQLite dump file, please send the storeId, userId, revision and storeSettingsId as params.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		// Save the transient and make sure it does not conflict with another possible user.
		$transient_key     = AtumCache::get_transient_key( self::DUMP_CONFIG_TRANSIENT, [ $user_app_id ] );
		$saved_dump_config = AtumCache::get_transient( $transient_key, TRUE );

		if ( empty( $saved_dump_config ) ) {

			$body = $request->get_body();

			if ( ! $body ) {
				return new \WP_Error( 'atum_rest_malformed_request', __( 'The request body is empty. It must have the app store settings JSON.', ATUM_TEXT_DOMAIN ), [ 'status' => 404 ] );
			}

			// Extract the file content from the $body variable.
			$store_app_settings = json_decode( $body, TRUE );

			$dump_config = array(
				'storeId'          => $store_app_id,
				'userId'           => $user_app_id,
				'revision'         => $app_revision,
				'storeSettingsId'  => $store_settings_app_id,
				'storeAppSettings' => $store_app_settings,
			);

			AtumCache::set_transient( $transient_key, $dump_config, WEEK_IN_SECONDS, TRUE );

		}

		return TRUE;

	}

	/**
	 * Get the admin user ID
	 *
	 * @since 1.9.44
	 *
	 * @return int|bool
	 */
	public static function get_admin_user() {

		// First, get the current user and check if it's an admin.
		if ( current_user_can( 'manage_options' ) ) {
			return get_current_user_id();
		}

		$admin_user = get_users( [
			'role'    => 'administrator',
			'orderby' => 'ID',
			'order'   => 'ASC',
			'number'  => 1,
		] );

		return ! empty( $admin_user ) ? absint( $admin_user[0]->ID ) : FALSE;

	}

	/**
	 * Prepare the user for the cron job
	 *
	 * @since 1.9.44
	 *
	 * @param int $user_id
	 */
	private static function prepare_cron_job_user( $user_id ) {

		$admin_user 	  = self::get_admin_user();
		$logged_in_user   = get_current_user_id();

		// If this is reached through a cron job, there won't be any user logged in and all these endpoints need a user with permission to be logged in.
		if ( ! $logged_in_user ) {

			if ( $admin_user && $user_id !== $admin_user ) {
				wp_set_current_user( $admin_user );
			}
			else {
				wp_set_current_user( $user_id );
			}

		}
		elseif ( $admin_user && $admin_user !== $user_id ) {
			wp_set_current_user( $admin_user );
		}

	}

}
