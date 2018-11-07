<?php
/**
 * Ajax callbacks
 *
 * @package        Atum
 * @subpackage     Inc
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2018 Stock Management Labs™
 *
 * @since          0.0.1
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumException;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Dashboard\Dashboard;
use Atum\Dashboard\WidgetHelpers;
use Atum\Dashboard\Widgets\Videos;
use Atum\InboundStock\Lists\ListTable as InboundStockListTable;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\Settings\Settings;
use Atum\InventoryLogs\Models\Log;
use Atum\StockCentral\Lists\ListTable;
use Atum\Suppliers\Suppliers;


final class Ajax {
	
	/**
	 * The singleton instance holder
	 *
	 * @var Ajax
	 */
	private static $instance;


	/**
	 * Ajax singleton constructor.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		// Save ATUM Dashboard widgets' layout.
		add_action( 'wp_ajax_atum_dashboard_save_layout', array( $this, 'save_dashboard_layout' ) );

		// Restore ATUM Dashboard widgets' default layout.
		add_action( 'wp_ajax_atum_dashboard_restore_layout', array( $this, 'restore_dashboard_layout' ) );

		// Add widgets to the ATUM Dashboard.
		add_action( 'wp_ajax_atum_dashboard_add_widget', array( $this, 'add_new_widget' ) );

		// Change the Statistics widget chart data.
		add_action( 'wp_ajax_atum_statistics_widget_chart', array( $this, 'statistics_widget_chart' ) );

		// Sort the videos within the Videos Widget.
		add_action( 'wp_ajax_atum_videos_widget_sorting', array( $this, 'videos_widget_sorting' ) );

		// Ajax callback for Stock Central ListTable.
		add_action( 'wp_ajax_atum_fetch_stock_central_list', array( $this, 'fetch_stock_central_list' ) );

		// Ajax callback for Inbound Stock ListTable.
		add_action( 'wp_ajax_atum_fetch_inbound_stock_list', array( $this, 'fetch_inbound_stock_list' ) );

		// Save the rate link click on the ATUM pages footer.
		add_action( 'wp_ajax_atum_rated', array( $this, 'rated' ) );

		// Save the edited meta data for items on ListTable components.
		add_action( 'wp_ajax_atum_update_data', array( $this, 'update_list_data' ) );

		// Apply bulk actions on ListTable components.
		add_action( 'wp_ajax_atum_apply_bulk_action', array( $this, 'apply_bulk_action' ) );

		// Control all products' button.
		add_action( 'wp_ajax_atum_control_all_products', array( $this, 'control_all_products' ) );

		// Manage addon licenses.
		add_action( 'wp_ajax_atum_validate_license', array( $this, 'validate_license' ) );
		add_action( 'wp_ajax_atum_activate_license', array( $this, 'activate_license' ) );
		add_action( 'wp_ajax_atum_deactivate_license', array( $this, 'deactivate_license' ) );
		add_action( 'wp_ajax_atum_install_addon', array( $this, 'install_addon' ) );

		// ATUM notice dismissals.
		add_action( 'wp_ajax_atum_dismiss_notice', array( $this, 'dismiss_notice' ) );

		// Search for products from enhanced selects.
		add_action( 'wp_ajax_atum_json_search_products', array( $this, 'search_products' ) );

		// Search for WooCommerce orders from enhanced selects.
		add_action( 'wp_ajax_atum_json_search_orders', array( $this, 'search_wc_orders' ) );

		// Search for Suppliers from enhanced selects.
		add_action( 'wp_ajax_atum_json_search_suppliers', array( $this, 'search_suppliers' ) );

		// Add and delete ATUM Order notes.
		add_action( 'wp_ajax_atum_order_add_note', array( $this, 'add_atum_order_note' ) );
		add_action( 'wp_ajax_atum_order_delete_note', array( $this, 'delete_atum_order_note' ) );

		// ATUM Order items' meta box actions.
		add_action( 'wp_ajax_atum_order_load_items', array( $this, 'load_atum_order_items' ) );
		add_action( 'wp_ajax_atum_order_add_item', array( $this, 'add_atum_order_item' ) );
		add_action( 'wp_ajax_atum_order_add_fee', array( $this, 'add_atum_order_fee' ) );
		add_action( 'wp_ajax_atum_order_add_shipping', array( $this, 'add_atum_order_shipping' ) );
		add_action( 'wp_ajax_atum_order_add_tax', array( $this, 'add_atum_order_tax' ) );
		add_action( 'wp_ajax_atum_order_remove_item', array( $this, 'remove_atum_order_item' ) );
		add_action( 'wp_ajax_atum_order_remove_tax', array( $this, 'remove_atum_order_tax' ) );
		add_action( 'wp_ajax_atum_order_calc_line_taxes', array( $this, 'calc_atum_order_line_taxes' ) );
		add_action( 'wp_ajax_atum_order_save_items', array( $this, 'save_atum_order_items' ) );
		add_action( 'wp_ajax_atum_order_increase_items_stock', array( $this, 'increase_atum_order_items_stock' ) );
		add_action( 'wp_ajax_atum_order_decrease_items_stock', array( $this, 'decrease_atum_order_items_stock' ) );
		add_action( 'wp_ajax_atum_order_change_purchase_price', array( $this, 'change_atum_order_item_purchase_price' ) );

		// Update the ATUM Order status.
		add_action( 'wp_ajax_atum_order_mark_status', array( $this, 'mark_atum_order_status' ) );

		// Import WC order items to an Inventory Log.
		add_action( 'wp_ajax_atum_order_import_items', array( $this, 'import_wc_order_items' ) );

		// Set the ATUM control switch status to all variations at once.
		add_action( 'wp_ajax_atum_set_variations_control_status', array( $this, 'set_variations_control_status' ) );

		// Get the product locations tree.
		add_action( 'wp_ajax_atum_get_locations_tree', array( $this, 'get_locations_tree' ) );
		add_action( 'wp_ajax_atum_set_locations_tree', array( $this, 'set_locations_tree' ) );

		// Run scripts from Tools section.
		add_action( 'wp_ajax_atum_tool_manage_stock', array( $this, 'change_manage_stock' ) );
		add_action( 'wp_ajax_atum_tool_control_stock', array( $this, 'change_control_stock' ) );
		add_action( 'wp_ajax_atum_tool_clear_out_stock_threshold', array( $this, 'clear_out_stock_threshold' ) );

	}

	/**
	 * Save the ATUM Dashboard layout as user meta
	 *
	 * @package Dashboard
	 *
	 * @since 1.4.0
	 */
	public function save_dashboard_layout() {

		check_ajax_referer( 'atum-dashboard-widgets', 'token' );

		$layout  = ! empty( $_POST['layout'] ) ? $_POST['layout'] : array();
		$user_id = get_current_user_id();
		Dashboard::save_user_widgets_layout( $user_id, $layout );

		wp_die();

	}

	/**
	 * Restore the default layout for the ATUM Dashboard
	 *
	 * @package Dashboard
	 *
	 * @since 1.4.0
	 */
	public function restore_dashboard_layout() {

		check_ajax_referer( 'atum-dashboard-widgets', 'token' );

		$user_id = get_current_user_id();
		Dashboard::restore_user_widgets_layout( $user_id );

		wp_die();

	}

	/**
	 * Add a widget to the ATUM Dashboard
	 *
	 * @package Dashboard
	 *
	 * @since 1.4.0
	 */
	public function add_new_widget() {

		check_ajax_referer( 'atum-dashboard-widgets', 'token' );

		if ( empty( $_POST['widget'] ) ) {
			wp_send_json_error( __( 'Invalid widget', ATUM_TEXT_DOMAIN ) );
		}

		$widget_id           = esc_attr( $_POST['widget'] );
		$dashboard           = Dashboard::get_instance();
		$user_widgets_layout = Dashboard::get_user_widgets_layout();

		// If the widget is already present in the user's dashboard, do not continue.
		if ( in_array( $widget_id, array_keys( $user_widgets_layout ) ) ) {
			wp_send_json_error( __( 'That widget was already added to your dashboard', ATUM_TEXT_DOMAIN ) );
		}

		$dashboard->load_widgets();
		$available_widgets = $dashboard->get_widgets();

		// If there is no widget with such name, do not continue.
		if ( ! in_array( $widget_id, array_keys( $available_widgets ) ) ) {
			wp_send_json_error( __( 'That widget is not available', ATUM_TEXT_DOMAIN ) );
		}

		$widget = $available_widgets[ $widget_id ];

		if ( ! is_a( $widget, '\Atum\Components\AtumWidget' ) ) {
			wp_die( esc_attr__( 'Invalid widget', ATUM_TEXT_DOMAIN ) );
		}

		ob_start();

		$grid_item_settings = $dashboard->get_widget_grid_item_defaults( $widget_id );
		$dashboard->add_widget( $widget, $grid_item_settings );
		$default_widgets_layout = Dashboard::get_default_widgets_layout();

		$widget_data = array(
			'layout' => $default_widgets_layout[ $widget_id ],
			'widget' => ob_get_clean(),
		);

		wp_send_json_success( $widget_data );

	}

	/**
	 * Sort the videos within the Videos Widget
	 *
	 * @package    Dashboard
	 * @subpackage Videos Widget
	 *
	 * @since 1.4.0
	 */
	public function videos_widget_sorting() {

		check_ajax_referer( 'atum-dashboard-widgets', 'token' );

		if ( empty( $_POST['sortby'] ) ) {
			wp_die( - 1 );
		}

		ob_start();
		Helpers::load_view( 'widgets/videos', Videos::get_filtered_videos( esc_attr( $_POST['sortby'] ) ) );

		wp_die( ob_get_clean() );

	}

	/**
	 * Change the Statistics widget chart data
	 *
	 * @package    Dashboard
	 * @subpackage Statistics Widget
	 *
	 * @since 1.4.0
	 */
	public function statistics_widget_chart() {

		check_ajax_referer( 'atum-dashboard-widgets', 'token' );

		if ( empty( $_POST['chart_data'] ) || empty( $_POST['chart_period'] ) ) {
			wp_send_json_error();
		}

		$chart_data   = esc_attr( $_POST['chart_data'] );
		$chart_period = esc_attr( $_POST['chart_period'] );

		switch ( $chart_data ) {
			case 'sales':
				$dataset = WidgetHelpers::get_sales_chart_data( $chart_period );
				$legends = array(
					'value'    => __( 'Sales', ATUM_TEXT_DOMAIN ),
					'products' => __( 'Products', ATUM_TEXT_DOMAIN ),
				);
				break;

			case 'lost-sales':
				$dataset = WidgetHelpers::get_sales_chart_data( $chart_period, [ 'lost_sales' ] );
				$legends = array(
					'value'    => __( 'Lost Sales', ATUM_TEXT_DOMAIN ),
					'products' => __( 'Products', ATUM_TEXT_DOMAIN ),
				);
				break;

			case 'promo-sales':
				$dataset = WidgetHelpers::get_promo_sales_chart_data( $chart_period );
				$legends = array(
					'value'    => __( 'Sales', ATUM_TEXT_DOMAIN ),
					'products' => __( 'Products', ATUM_TEXT_DOMAIN ),
				);
				break;

			case 'orders':
				$dataset = WidgetHelpers::get_orders_chart_data( $chart_period );
				$legends = array(
					'value'    => __( 'Value', ATUM_TEXT_DOMAIN ),
					'products' => __( 'Orders', ATUM_TEXT_DOMAIN ),
				);
				break;

			default:
				wp_send_json_error();
				break;
		}

		if ( strpos( $chart_period, 'year' ) !== FALSE ) {
			$period = 'month';
		}
		elseif ( strpos( $chart_period, 'month' ) !== FALSE ) {
			$period = 'monthDay';
		}
		else {
			$period = 'weekDay';
		}

		wp_send_json_success( compact( 'dataset', 'period', 'legends' ) );

	}
	
	/**
	 * Loads the Stock Central ListTable class and calls ajax_response method
	 *
	 * @package Stock Central
	 *
	 * @since 0.0.1
	 */
	public function fetch_stock_central_list() {
		
		check_ajax_referer( 'atum-list-table-nonce', 'token' );

		$args = array(
			'per_page'        => ! empty( $_REQUEST['per_page'] ) ? absint( $_REQUEST['per_page'] ) : Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE ),
			'show_cb'         => ! empty( $_REQUEST['show_cb'] ) ? (bool) $_REQUEST['show_cb'] : FALSE,
			'show_controlled' => ! empty( $_REQUEST['show_controlled'] ) ? (bool) $_REQUEST['show_controlled'] : FALSE,
			'screen'          => esc_attr( $_REQUEST['screen'] ),
		);
		
		do_action( 'atum/ajax/stock_central_list/before_fetch_list' );

		$namespace  = '\Atum\StockCentral\Lists';
		$list_class = $args['show_controlled'] ? "$namespace\ListTable" : "$namespace\UncontrolledListTable";

		/**
		 * Variable deifinition
		 *
		 * @var ListTable $list
		 */
		$list = new $list_class( $args );
		$list->ajax_response();
		
	}

	/**
	 * Loads the Inbound Stock ListTable class and calls ajax_response method
	 *
	 * @package Inbound Stock
	 *
	 * @since 1.3.0
	 */
	public function fetch_inbound_stock_list() {

		check_ajax_referer( 'atum-list-table-nonce', 'token' );

		$args = array(
			'per_page' => ! empty( $_REQUEST['per_page'] ) ? absint( $_REQUEST['per_page'] ) : Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE ),
			'screen'   => $_REQUEST['screen'],
		);

		do_action( 'atum/ajax/inbound_stock/before_fetch_list' );

		$list = new InboundStockListTable( $args );
		$list->ajax_response();

	}

	/**
	 * Triggered when clicking the rating footer
	 *
	 * @package Main
	 *
	 * @since 1.2.0
	 */
	public function rated() {
		update_option( 'atum_admin_footer_text_rated', 1 );
		wp_die();
	}
	
	/**
	 * Update the meta values for the edited ListTable columns
	 *
	 * @package ATUM List Tables
	 *
	 * @since   1.1.2
	 */
	public function update_list_data() {
		
		check_ajax_referer( 'atum-list-table-nonce', 'token' );
		
		if ( empty( $_POST['data'] ) ) {
			wp_send_json_error( __( 'Error saving the table data.', ATUM_TEXT_DOMAIN ) );
		}
		
		$data = json_decode( stripslashes( $_POST['data'] ), TRUE );
		
		if ( empty( $data ) ) {
			wp_send_json_error( __( 'Error saving the table data.', ATUM_TEXT_DOMAIN ) );
		}
		
		$data = apply_filters( 'atum/ajax/before_update_product_meta', $data );
		
		foreach ( $data as $product_id => &$product_meta ) {
			
			Helpers::update_product_meta( $product_id, $product_meta );
			
		}
		
		// If the first edit notice was already shown, save it as user meta.
		if ( ! empty( $_POST['first_edit_key'] ) ) {
			update_user_meta( get_current_user_id(), esc_attr( $_POST['first_edit_key'] ), 1 );
		}
		
		wp_send_json_success( __( 'Data saved.', ATUM_TEXT_DOMAIN ) );
		
	}

	/**
	 * Apply actions in bulk to the selected ListTable rows
	 *
	 * @package ATUM List Tables
	 *
	 * @since 1.4.1
	 */
	public function apply_bulk_action() {

		check_ajax_referer( 'atum-list-table-nonce', 'token' );

		if ( empty( $_POST['ids'] ) ) {
			wp_send_json_error( __( 'No Items Selected.', ATUM_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['bulk_action'] ) ) {
			wp_send_json_error( __( 'Invalid bulk action.', ATUM_TEXT_DOMAIN ) );
		}

		$ids = array_map( 'absint', $_POST['ids'] );

		switch ( $_POST['bulk_action'] ) {
			case 'uncontrol_stock':
				foreach ( $ids as $id ) {
					Helpers::disable_atum_control( $id );
				}

				break;

			case 'control_stock':
				foreach ( $ids as $id ) {
					Helpers::enable_atum_control( $id );
				}

				break;

			case 'unmanage_stock':
				foreach ( $ids as $id ) {
					Helpers::disable_wc_manage_stock( $id );
				}

				break;

			case 'manage_stock':
				foreach ( $ids as $id ) {
					Helpers::enable_wc_manage_stock( $id );
				}

				break;
		}

		wp_send_json_success( __( 'Action applied to the selected products successfully.', ATUM_TEXT_DOMAIN ) );

	}

	/**
	 * Control all the shop products at once from the List Tables' button
	 *
	 * @package ATUM List Tables
	 *
	 * @since 1.4.10
	 */
	public function control_all_products() {

		check_ajax_referer( 'atum-control-all-products-nonce', 'token' );
		$this->change_status_meta( Globals::ATUM_CONTROL_STOCK_KEY, 'yes' );

	}

	/**
	 * Validate an addon license key through API
	 *
	 * @package Add-ons
	 *
	 * @since 1.2.0
	 */
	public function validate_license() {

		$this->check_license_post_data();

		$addon_name = esc_attr( $_POST['addon'] ); // WPCS: CSRF ok.
		$key        = esc_attr( $_POST['key'] ); // WPCS: CSRF ok.

		if ( ! $addon_name || ! $key ) {
			wp_send_json_error( __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN ) );
		}

		$error_message = __( 'This license is not valid.', ATUM_TEXT_DOMAIN );

		// Validate the license through API.
		$response = Addons::check_license( $addon_name, $key );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( __( 'ATUM API error', ATUM_TEXT_DOMAIN ) );
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		switch ( $license_data->license ) {

			case 'valid':
				// Save the valid license.
				Addons::update_key( $addon_name, array(
					'key'    => $key,
					'status' => 'valid',
				) );

				// Delete status transient.
				Addons::delete_status_transient( $addon_name );

				wp_send_json_success( __( 'Your add-on license was saved.', ATUM_TEXT_DOMAIN ) );
				break;

			case 'inactive':
			case 'site_inactive':
				Addons::update_key( $addon_name, array(
					'key'    => $key,
					'status' => 'inactive',
				) );

				// Delete status transient.
				Addons::delete_status_transient( $addon_name );

				if ( $license_data->activations_left < 1 ) {
					wp_send_json_error( __( "You've reached your license activation limit for this add-on.<br>Please contact the Stock Management Labs support team.", ATUM_TEXT_DOMAIN ) );
				}

				$licenses_after_activation = $license_data->activations_left - 1;

				wp_send_json( array(
					'success' => 'activate',
					'data'    => sprintf(
						/* translators: the number of remaininig licenses */
						_n(
							'Your license is valid.<br>After the activation you will have %s remaining license.<br>Please, click the button to activate.',
							'Your license is valid.<br>After the activation you will have %s remaining licenses.<br>Please, click the button to activate.',
							$licenses_after_activation,
							ATUM_TEXT_DOMAIN
						),
						$licenses_after_activation
					),
				) );
				break;

			case 'expired':
				$error_message = sprintf(
					/* translators: the expiration date */
					__( 'Your license key expired on %s.', ATUM_TEXT_DOMAIN ),
					date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
				);

				break;

			case 'disabled':
				$error_message = __( 'This license has been disabled', ATUM_TEXT_DOMAIN );
				break;

		}

		Addons::update_key( $addon_name, array(
			'key'    => $key,
			'status' => 'invalid',
		) );

		// Delete status transient.
		Addons::delete_status_transient( $addon_name );

		wp_send_json_error( $error_message );

	}

	/**
	 * First check before validating|activating|deactivating an addon license
	 *
	 * @package Add-ons
	 *
	 * @since 1.2.0
	 */
	private function check_license_post_data() {

		check_ajax_referer( ATUM_PREFIX . 'manage_license', 'token' );

		if ( empty( $_POST['addon'] ) ) {
			wp_send_json_error( __( 'No addon name provided', ATUM_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['key'] ) ) {
			wp_send_json_error( __( 'Please enter a valid addon license key', ATUM_TEXT_DOMAIN ) );
		}
	}

	/**
	 * Activate an addon license key through API
	 *
	 * @package Add-ons
	 *
	 * @since 1.2.0
	 */
	public function activate_license() {

		$this->check_license_post_data();

		$addon_name    = esc_attr( $_POST['addon'] ); // WPCS: CSRF ok.
		$key           = esc_attr( $_POST['key'] ); // WPCS: CSRF ok.
		$default_error = __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN );

		if ( ! $addon_name || ! $key ) {
			wp_send_json_error( $default_error );
		}

		$response = Addons::activate_license( $addon_name, $key );

		// Make sure the response came back okay.
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			$message = is_wp_error( $response ) ? $response->get_error_message() : $default_error;

		}
		else {

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( FALSE === $license_data->success ) {

				switch ( $license_data->error ) {

					case 'expired':
						$message = sprintf(
							/* translators: the expiration date */
							__( 'Your license key expired on %s.', ATUM_TEXT_DOMAIN ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;

					case 'revoked':
						$message = __( 'Your license key has been disabled.', ATUM_TEXT_DOMAIN );
						break;

					case 'missing':
						$message = __( 'Invalid license.', ATUM_TEXT_DOMAIN );
						break;

					case 'invalid':
					case 'site_inactive':
						$message = __( 'Your license is not active for this URL.', ATUM_TEXT_DOMAIN );
						break;

					case 'item_name_mismatch':
						/* translators: the add-on name */
						$message = sprintf( __( 'This appears to be an invalid license key for %s.', ATUM_TEXT_DOMAIN ), $addon_name );
						break;

					case 'no_activations_left':
						$message = __( 'Your license key has reached its activation limit.', ATUM_TEXT_DOMAIN );
						break;

					default:
						$message = $default_error;
						break;
				}

			}

		}

		if ( ! empty( $message ) ) {
			wp_send_json_error( $message );
		}

		// Update the key in database.
		if ( ! empty( $license_data ) ) {

			Addons::update_key( $addon_name, array(
				'key'    => $key,
				'status' => $license_data->license,
			) );

			// Delete status transient.
			Addons::delete_status_transient( $addon_name );

			if ( 'valid' === $license_data->license ) {
				wp_send_json_success( __( 'Your license has been activated.', ATUM_TEXT_DOMAIN ) );
			}

		}

		wp_send_json_error( $default_error );

	}

	/**
	 * Deactivate an addon license key through API
	 *
	 * @package Add-ons
	 *
	 * @since 1.2.0
	 */
	public function deactivate_license() {

		$this->check_license_post_data();

		$addon_name    = esc_attr( $_POST['addon'] ); // WPCS: CSRF ok.
		$key           = esc_attr( $_POST['key'] ); // WPCS: CSRF ok.
		$default_error = __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN );

		if ( ! $addon_name || ! $key ) {
			wp_send_json_error( $default_error );
		}

		$response = Addons::deactivate_license( $addon_name, $key );

		// Make sure the response came back okay.
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = ( is_wp_error( $response ) ) ? $response->get_error_message() : $default_error;
			wp_send_json_error( $message );
		}

		// Decode the license data.
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed" or "limit_reached".
		if ( 'deactivated' === $license_data->license ) {

			// Update the key status.
			Addons::update_key( $addon_name, array(
				'key'    => $key,
				'status' => 'inactive',
			) );

			// Delete status transient.
			Addons::delete_status_transient( $addon_name );

			wp_send_json_success( __( 'Your license has been deactivated.', ATUM_TEXT_DOMAIN ) );

		}
		elseif ( 'limit_reached' === $license_data->license ) {

			wp_send_json_error( sprintf(
				/* translators: first one is the Ticksy link and the second is the link closing tag */
				__( "You've reached the limit of allowed deactivations for this license. Please %1\$sopen a support ticket%2\$s to request the deactivation.", ATUM_TEXT_DOMAIN ),
				'<a href="https://stockmanagementlabs.ticksy.com/" target="_blank">',
				'</a>'
			) );

		}

		wp_send_json_error( $default_error );

	}

	/**
	 * Install an addon from the addons page
	 *
	 * @package Add-ons
	 *
	 * @since 1.2.0
	 */
	public function install_addon() {

		$this->check_license_post_data();

		$addon_name    = esc_attr( $_POST['addon'] ); // WPCS: CSRF ok.
		$addon_slug    = esc_attr( $_POST['slug'] ); // WPCS: CSRF ok.
		$key           = esc_attr( $_POST['key'] ); // WPCS: CSRF ok.
		$default_error = __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN );

		if ( ! $addon_name || ! $addon_slug || ! $key ) {
			wp_send_json_error( $default_error );
		}

		$response = Addons::get_version( $addon_name, $key, '0.0' );

		// Make sure the response came back okay.
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = ( is_wp_error( $response ) ) ? $response->get_error_message() : $default_error;
			wp_send_json_error( $message );
		}

		// Decode the license data.
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $license_data->download_link ) {

			Addons::delete_status_transient( $addon_name );
			/* @noinspection PhpUnhandledExceptionInspection */
			$result = Addons::install_addon( $addon_name, $addon_slug, $license_data->download_link );
			wp_send_json( $result );
		}

		wp_send_json_error( $default_error );

	}

	/**
	 * Dismiss the ATUM notices
	 *
	 * @package Helpers
	 *
	 * @since 1.4.4
	 */
	public function dismiss_notice() {
		check_ajax_referer( 'dismiss-atum-notice', 'token' );

		if ( ! empty( $_POST['key'] ) ) {
			Helpers::dismiss_notice( esc_attr( $_POST['key'] ) );
		}

		wp_die();
	}

	/**
	 * Seach for products from enhanced selects
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.3.7
	 */
	public function search_products() {

		check_ajax_referer( 'search-products', 'security' );

		ob_start();

		$term = stripslashes( $_GET['term'] );

		if ( empty( $term ) ) {
			wp_die();
		}

		global $wpdb;

		$like_term     = '%%' . $wpdb->esc_like( $term ) . '%%';
		$post_types    = apply_filters( 'atum/ajax/search_products/searched_post_types', [ 'product', 'product_variation' ] );
		$post_statuses = current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ];
		$meta_join     = $meta_where = array();
		$type_where    = '';
		$join_counter  = 1;

		// Search by meta keys.
		$searched_metas = array_map( 'wc_clean', apply_filters( 'atum/ajax/search_products/searched_meta_keys', [ '_sku' ] ) );

		foreach ( $searched_metas as $searched_meta ) {
			$meta_join[]  = "LEFT JOIN {$wpdb->postmeta} pm{$join_counter} ON posts.ID = pm{$join_counter}.post_id";
			$meta_where[] = $wpdb->prepare( "OR ( pm{$join_counter}.meta_key = %s AND pm{$join_counter}.meta_value LIKE %s )", $searched_meta, $like_term ); // WPCS: unprepared SQL ok.
			$join_counter ++;
		}

		// Exclude variable products from results.
		$excluded_types = (array) apply_filters( 'atum/ajax/search_products/excluded_product_types', array_diff( Globals::get_inheritable_product_types(), [ 'grouped' ] ) );

		if ( ! empty( $excluded_types ) ) {

			$excluded_type_terms = array();

			foreach ( $excluded_types as $excluded_type ) {
				$excluded_type_terms[] = get_term_by( 'slug', $excluded_type, 'product_type' );
			}

			$excluded_type_terms = wp_list_pluck( array_filter( $excluded_type_terms ), 'term_taxonomy_id' );

			$type_where = "AND posts.ID NOT IN (
				SELECT p.ID FROM $wpdb->posts p
				LEFT JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id
				WHERE p.post_type IN ('" . implode( "','", $post_types ) . "')
				AND p.post_status IN ('" . implode( "','", $post_statuses ) . "')
				AND tr.term_taxonomy_id IN (" . implode( ',', $excluded_type_terms ) . ')
			)';

		}

		$query = $wpdb->prepare( "
			SELECT DISTINCT posts.ID FROM $wpdb->posts posts
			" . implode( "\n", $meta_join ) . '
			WHERE (
				posts.post_title LIKE %s
				OR posts.post_content LIKE %s
				' . implode( "\n", $meta_where ) . "
			)
			AND posts.post_type IN ('" . implode( "','", $post_types ) . "')
			AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "')
			" . $type_where . '
			ORDER BY posts.post_parent ASC, posts.post_title ASC
			',
			$like_term,
			$like_term
		); // WPCS: unprepared SQL ok.

		$product_ids = $wpdb->get_col( $query ); // WPCS: unprepared SQL ok.

		if ( is_numeric( $term ) ) {

			$post_id   = absint( $term );
			$post_type = get_post_type( $post_id );

			if ( 'product_variation' === $post_type ) {
				$product_ids[] = $post_id;
			}
			elseif ( 'product' === $post_type ) {
				$product_ids[] = $post_id;
			}

			$product_ids[] = wp_get_post_parent_id( $post_id );

		}

		$ids = wp_parse_id_list( $product_ids );

		if ( ! empty( $_GET['exclude'] ) ) {
			$ids = array_diff( $ids, (array) $_GET['exclude'] );
		}

		$included = ! empty( $_GET['include'] ) ? array_map( 'absint', (array) $_GET['include'] ) : array();
		$url      = wp_parse_url( wp_get_referer() );
		parse_str( $url['query'], $url_query );

		if ( ! empty( $url_query['post'] ) ) {

			/**
			 * Variable definition
			 *
			 * @var PurchaseOrder $po
			 */
			$po = Helpers::get_atum_order_model( absint( $url_query['post'] ) );

			// The Purchase Orders only should allow products from the current PO's supplier (if such PO only allows 1 supplier).
			if ( is_a( $po, '\Atum\PurchaseOrders\Models\PurchaseOrder' ) && ! $po->has_multiple_suppliers() ) {

				$supplier_products = apply_filters( 'atum/ajax/search_products/included_search_products', Suppliers::get_supplier_products( $po->get_supplier( 'id' ), [ 'product', 'product_variation' ], FALSE ) );

				// If the PO supplier has no linked products, it must return an empty array.
				if ( empty( $supplier_products ) ) {
					$ids = $included = array();
				}
				else {
					$included = array_merge( $included, $supplier_products );
				}

			}

		}

		if ( ! empty( $included ) ) {
			$ids = array_intersect( $ids, $included );
		}

		if ( ! empty( $_GET['limit'] ) ) {
			$ids = array_slice( $ids, 0, absint( $_GET['limit'] ) );
		}

		$product_objects = array_filter( array_map( 'wc_get_product', $ids ), 'wc_products_array_filter_editable' );
		$products        = array();

		foreach ( $product_objects as $product_object ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Product $product_object
			 */
			$products[ $product_object->get_id() ] = rawurldecode( $product_object->get_formatted_name() );
		}

		wp_send_json( apply_filters( 'atum/ajax/search_products/json_search_found_products', $products ) );

	}

	/**
	 * Seach for WooCommerce orders from enhanced selects
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 */
	public function search_wc_orders() {

		check_ajax_referer( 'search-products', 'security' );

		ob_start();

		$order_id = absint( $_GET['term'] );

		if ( empty( $order_id ) ) {
			wp_die();
		}

		// Get all the orders with IDs starting with the provided number.
		global $wpdb;
		$max_results = absint( apply_filters( 'atum/ajax/search_wc_orders/max_results', 10 ) );

		$query = $wpdb->prepare(
			"SELECT DISTINCT ID from {$wpdb->posts} WHERE post_type = 'shop_order' 
			AND post_status IN ('" . implode( "','", array_keys( wc_get_order_statuses() ) ) . "') 
			AND ID LIKE %s LIMIT %d",
			"$order_id%",
			$max_results
		); // WPCS: unprepared SQL ok.

		$order_ids = $wpdb->get_col( $query ); // WPCS: unprepared SQL ok.

		if ( empty( $order_ids ) ) {
			wp_die();
		}

		$order_results = array();
		foreach ( $order_ids as $order_id ) {
			$order_results[ $order_id ] = __( 'Order #', ATUM_TEXT_DOMAIN ) . $order_id;
		}

		wp_send_json( $order_results );

	}

	/**
	 * Seach for Suppliers from enhanced selects
	 *
	 * @package Suppliers
	 *
	 * @since 1.2.9
	 */
	public function search_suppliers() {

		check_ajax_referer( 'search-products', 'security' );

		global $wpdb;
		ob_start();
		$where = '';

		if ( is_numeric( $_GET['term'] ) ) {

			$supplier_id = absint( $_GET['term'] );
			$where       = "AND ID LIKE $supplier_id";

		}
		elseif ( ! empty( $_GET['term'] ) ) {

			$supplier_name = $wpdb->esc_like( $_GET['term'] );
			$where         = "AND post_title LIKE '%%{$supplier_name}%%'";

		}
		else {
			wp_die();
		}

		// Get all the orders with IDs starting with the provided number.
		$max_results   = absint( apply_filters( 'atum/ajax/search_suppliers/max_results', 10 ) );
		$post_statuses = AtumCapabilities::current_user_can( 'edit_private_suppliers' ) ? [ 'private', 'publish' ] : [ 'publish' ];

		$query = $wpdb->prepare(
			"SELECT DISTINCT ID, post_title from $wpdb->posts 
			 WHERE post_type = %s $where
			 AND post_status IN ('" . implode( "','", $post_statuses ) . "')
			 LIMIT %d",
			Suppliers::POST_TYPE,
			$max_results
		); // WPCS: unprepared SQL ok.

		$suppliers = $wpdb->get_results( $query ); // WPCS: unprepared SQL ok.

		if ( empty( $suppliers ) ) {
			wp_die();
		}

		$supplier_results = array();
		foreach ( $suppliers as $supplier ) {
			$supplier_results[ $supplier->ID ] = $supplier->post_title;
		}

		wp_send_json( $supplier_results );

	}

	/**
	 * Add a note to an ATUM Order
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 */
	public function add_atum_order_note() {

		check_ajax_referer( 'add-atum-order-note', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'create_order_notes' ) ) {
			wp_die( -1 );
		}

		$post_id = absint( $_POST['post_id'] );
		$note    = wp_kses_post( trim( stripslashes( $_POST['note'] ) ) );

		if ( $post_id ) {

			$atum_order = Helpers::get_atum_order_model( $post_id );

			if ( ! is_wp_error( $atum_order ) ) {

				$comment_id = $atum_order->add_note( $note );

				?>
				<li rel="<?php echo esc_attr( $comment_id ) ?>" class="note">
					<div class="note_content">
						<?php echo wpautop( wptexturize( $note ) ); // WPCS: XSS ok. ?>
					</div>

					<p class="meta">
						<a href="#" class="delete_note"><?php esc_attr_e( 'Delete note', ATUM_TEXT_DOMAIN ) ?></a>
					</p>
				</li>
				<?php

			}

		}

		wp_die();

	}

	/**
	 * Delete a note from an ATUM Order
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 */
	public function delete_atum_order_note() {

		check_ajax_referer( 'delete-atum-order-note', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'delete_order_notes' ) ) {
			wp_die( -1 );
		}

		$note_id = absint( $_POST['note_id'] );

		if ( $note_id ) {
			wp_delete_comment( $note_id );
		}

		wp_die();

	}

	/**
	 * Load ATUM Order items
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 */
	public function load_atum_order_items() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$atum_order_id = absint( $_POST['atum_order_id'] );
		$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

		if ( is_wp_error( $atum_order ) ) {
			wp_die( -1 );
		}

		Helpers::load_view( 'meta-boxes/atum-order/items', compact( 'atum_order' ) );

		wp_die();

	}

	/* @noinspection PhpDocRedundantThrowsInspection */
	/**
	 * Add ATUM Order item
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 *
	 * @throws AtumException
	 */
	public function add_atum_order_item() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['atum_order_id'] ) ) {
			wp_die( -1 );
		}

		try {

			$atum_order_id = absint( $_POST['atum_order_id'] );
			$post_type     = get_post_type( $atum_order_id );
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error( $atum_order ) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			if ( ! $atum_order ) {
				$message = ATUM_PREFIX . 'inventory_log' === $post_type ? __( 'Invalid log', ATUM_TEXT_DOMAIN ) : __( 'Invalid purchase order', ATUM_TEXT_DOMAIN );
				throw new AtumException( 'invalid_atum_order', $message );
			}

			$items_to_add = wp_parse_id_list( is_array( $_POST['item_to_add'] ) ? $_POST['item_to_add'] : array( $_POST['item_to_add'] ) );
			$html         = '';

			foreach ( $items_to_add as $item_to_add ) {

				if ( ! in_array( get_post_type( $item_to_add ), array( 'product', 'product_variation' ) ) ) {
					continue;
				}

				// Add the product to the ATUM Order.
				$item    = $atum_order->add_product( wc_get_product( $item_to_add ) );
				$item_id = $item->get_id();
				$class   = 'new_row';
				
				do_action( 'atum/atum_order/add_order_item_meta', $item_id, $item, $atum_order );
				// Load template.
				$html .= Helpers::load_view_to_string( 'meta-boxes/atum-order/item', compact( 'atum_order', 'item', 'item_id', 'class' ) );

			}

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

	}

	/* @noinspection PhpDocRedundantThrowsInspection */
	/**
	 * Add ATUM Order fee
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 *
	 * @throws AtumException
	 */
	public function add_atum_order_fee() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['atum_order_id'] ) ) {
			wp_die( - 1 );
		}

		try {

			$atum_order_id = absint( $_POST['atum_order_id'] );
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error( $atum_order ) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			// Add a fee line item.
			$item    = $atum_order->add_fee();
			$item_id = $item->get_id();

			// Load template.
			$html = Helpers::load_view_to_string( 'meta-boxes/atum-order/item-fee', compact( 'atum_order', 'item', 'item_id' ) );

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}
	}

	/* @noinspection PhpDocRedundantThrowsInspection */
	/**
	 * Add ATUM Order shipping cost
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 *
	 * @throws AtumException
	 */
	public function add_atum_order_shipping() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['atum_order_id'] ) ) {
			wp_die( -1 );
		}

		try {

			$atum_order_id = absint( $_POST['atum_order_id'] );
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error( $atum_order ) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			$shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();

			// Add new shipping cost line item.
			$item    = $atum_order->add_shipping_cost();
			$item_id = $item->get_id();

			// Load template.
			$html = Helpers::load_view_to_string( 'meta-boxes/atum-order/item-shipping', compact( 'atum_order', 'item', 'item_id', 'shipping_methods' ) );

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

	}

	/* @noinspection PhpDocRedundantThrowsInspection */
	/**
	 * Add ATUM Order tax
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 *
	 * @throws AtumException
	 */
	public function add_atum_order_tax() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['atum_order_id'] ) ) {
			wp_die( -1 );
		}

		try {

			$atum_order_id = absint( $_POST['atum_order_id'] );
			$rate_id       = absint( $_POST['rate_id'] );
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error( $atum_order ) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			// Add new tax.
			$atum_order->add_tax( array( 'rate_id' => $rate_id ) );

			// Load template.
			$html = Helpers::load_view_to_string( 'meta-boxes/atum-order/items', compact( 'atum_order' ) );

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

	}

	/**
	 * Remove an ATUM Order item
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 */
	public function remove_atum_order_item() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$atum_order_id       = absint( $_POST['atum_order_id'] );
		$atum_order_item_ids = $_POST['atum_order_item_ids'];

		if ( ! is_array( $atum_order_item_ids ) && is_numeric( $atum_order_item_ids ) ) {
			$atum_order_item_ids = array( $atum_order_item_ids );
		}

		$atum_order_item_ids = array_unique( array_filter( array_map( 'absint', $atum_order_item_ids ) ) );

		if ( ! empty( $atum_order_item_ids ) ) {

			$atum_order = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error( $atum_order ) ) {
				wp_die( - 1 );
			}

			foreach ( $atum_order_item_ids as $id ) {
				$atum_order->remove_item( absint( $id ) );
				do_action( 'atum/atum_order/delete_order_item', absint( $id ) );
			}

			$atum_order->save_items();
		}

		wp_die();

	}

	/**
	 * Remove an ATUM Order tax
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 */
	public function remove_atum_order_tax() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$atum_order_id = absint( $_POST['atum_order_id'] );
		$rate_id       = absint( $_POST['rate_id'] );
		$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

		if ( is_wp_error( $atum_order ) ) {
			wp_die( - 1 );
		}

		$atum_order->remove_item( $rate_id );
		$atum_order->save_items();

		// Load template.
		Helpers::load_view( 'meta-boxes/atum-order/items', compact( 'atum_order' ) );

		wp_die();

	}

	/**
	 * Calc ATUM Order line taxes
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 */
	public function calc_atum_order_line_taxes() {

		check_ajax_referer( 'calc-totals', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$atum_order_id = absint( $_POST['atum_order_id'] );

		// Parse the jQuery serialized items.
		$items = array();
		parse_str( $_POST['items'], $items );

		$atum_order = Helpers::get_atum_order_model( $atum_order_id );

		if ( is_wp_error( $atum_order ) ) {
			wp_die( - 1 );
		}

		// Grab the order and recalc taxes.
		$atum_order->save_order_items( $items );
		$atum_order->calculate_taxes();
		$atum_order->calculate_totals( FALSE );

		// Load template.
		Helpers::load_view( 'meta-boxes/atum-order/items', compact( 'atum_order' ) );

		wp_die();

	}

	/**
	 * Save ATUM Order items
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 */
	public function save_atum_order_items() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		if ( isset( $_POST['atum_order_id'], $_POST['items'] ) ) {

			$atum_order_id = absint( $_POST['atum_order_id'] );
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error( $atum_order ) ) {
				wp_die( - 1 );
			}

			// Parse the jQuery serialized items.
			$items = array();
			parse_str( $_POST['items'], $items );

			// Save order items.
			$atum_order->save_order_items( $items );

			// Return HTML items.
			Helpers::load_view( 'meta-boxes/atum-order/items', compact( 'atum_order' ) );

		}

		wp_die();

	}

	/**
	 * Increase the ATUM order products' stock by their quantity amount
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.3.0
	 */
	public function increase_atum_order_items_stock() {
		$this->bulk_change_atum_order_items_stock( 'increase' );
	}

	/**
	 * Decrease the ATUM order products' stock by their quantity amount
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.3.0
	 */
	public function decrease_atum_order_items_stock() {
		$this->bulk_change_atum_order_items_stock( 'decrease' );
	}

	/**
	 * Change the ATUM order products' stock by their quantity amount
	 *
	 * @package ATUM Orders
	 *
	 * @since   1.3.0
	 *
	 * @param string $action
	 */
	private function bulk_change_atum_order_items_stock( $action ) {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to do this', ATUM_TEXT_DOMAIN ) );
		}

		if ( ! isset( $_POST['atum_order_id'], $_POST['atum_order_item_ids'], $_POST['quantities'] ) ) {
			wp_send_json_error( __( 'Invalid data provided', ATUM_TEXT_DOMAIN ) );
		}

		$atum_order_id       = absint( $_POST['atum_order_id'] );
		$atum_order_item_ids = array_map( 'absint', $_POST['atum_order_item_ids'] );
		$quantities          = array_map( 'wc_stock_amount', $_POST['quantities'] );

		$atum_order       = Helpers::get_atum_order_model( $atum_order_id );
		$atum_order_items = $atum_order->get_items();
		$return           = array();

		if ( $atum_order && ! empty( $atum_order_items ) && count( $atum_order_item_ids ) > 0 ) {

			foreach ( $atum_order_items as $item_id => $atum_order_item ) {

				/**
				 * Variable definition
				 *
				 * @var \WC_Order_Item_Product $atum_order_item
				 */

				// Only increase the stock for selected items.
				if ( ! in_array( $item_id, $atum_order_item_ids ) ) {
					continue;
				}

				$product = $atum_order_item->get_product();

				if ( $product && $product->exists() && $product->managing_stock() && isset( $quantities[ $item_id ] ) && $quantities[ $item_id ] > 0 ) {

					$old_stock    = $product->get_stock_quantity();
					$stock_change = apply_filters( 'atum/ajax/restore_atum_order_stock_quantity', $quantities[ $item_id ], $item_id );
					$new_quantity = wc_update_product_stock( $product, $stock_change, $action );
					$item_name    = $product->get_sku() ? $product->get_sku() : $product->get_id();
					$note         = sprintf(
						/* translators: first is the item name, second is the action, third is the old stock and forth is the new stock */
						__( 'Item %1$s stock %2$s from %3$s to %4$s.', ATUM_TEXT_DOMAIN ),
						$item_name,
						'increase' === $action ? __( 'increased', ATUM_TEXT_DOMAIN ) : __( 'decreased', ATUM_TEXT_DOMAIN ),
						$old_stock,
						$new_quantity
					);

					$return[] = $note;

					$atum_order->add_note( $note );
					$atum_order_item->update_meta_data( '_stock_changed', TRUE );
					$atum_order_item->save();

				}
			}

			do_action( "atum/ajax/{$action}_atum_order_stock", $atum_order );

			if ( empty( $return ) ) {

				wp_send_json_error( sprintf(
					/* translators: the action performed */
					__( 'No products had their stock %s - they may not have stock management enabled.', ATUM_TEXT_DOMAIN ),
					'increase' === $action ? __( 'increased', ATUM_TEXT_DOMAIN ) : __( 'decreased', ATUM_TEXT_DOMAIN )
				) );

			}

		}

		wp_send_json_success();

	}

	/**
	 * Change the purchase price of a product within a PO
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.3.0
	 */
	public function change_atum_order_item_purchase_price() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to do this', ATUM_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['atum_order_id'] ) || empty( $_POST['atum_order_item_id'] ) || empty( $_POST[ Globals::PURCHASE_PRICE_KEY ] ) ) {
			wp_send_json_error( __( 'Invalid data provided', ATUM_TEXT_DOMAIN ) );
		}

		$atum_order      = Helpers::get_atum_order_model( absint( $_POST['atum_order_id'] ) );
		$atum_order_item = $atum_order->get_item( absint( $_POST['atum_order_item_id'] ) );

		/* @noinspection PhpUndefinedMethodInspection */
		$product_id = $atum_order_item->get_variation_id() ?: $atum_order_item->get_product_id();
		$product    = wc_get_product( $product_id );

		if ( ! is_a( $product, '\WC_Product' ) ) {
			wp_send_json_error( __( 'Product not found', ATUM_TEXT_DOMAIN ) );
		}

		update_post_meta( $product_id, Globals::PURCHASE_PRICE_KEY, wc_format_decimal( $_POST[ Globals::PURCHASE_PRICE_KEY ] ) );

		wp_send_json_success();

	}

	/**
	 * Import the WC order items to the current ATUM Order after linking an order
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 */
	public function import_wc_order_items() {

		check_ajax_referer( 'import-order-items', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! AtumCapabilities::current_user_can( 'edit_inventory_log' ) ) {
			wp_die( -1 );
		}

		if ( isset( $_POST['atum_order_id'], $_POST['wc_order_id'] ) ) {

			$atum_order_id = absint( $_POST['atum_order_id'] );
			$wc_order_id   = absint( $_POST['wc_order_id'] );
			$wc_order      = wc_get_order( $wc_order_id );

			if ( ! empty( $wc_order ) ) {

				$items = $wc_order->get_items( array( 'line_item', 'fee', 'shipping', 'tax' ) );

				if ( ! empty( $items ) ) {

					// *** NOTE: FOR NOW THIS IS ONLY USED ON LOGS, IF NEEDS TO BE COMPATIBLE WITH OTHER
					// ATUM ORDERS IN THE FUTURE, THIS WILL NEED REFACTORY ***
					$atum_order = new Log( $atum_order_id );

					try {

						// The log only can have one tax applied, so check if already has one.
						$current_tax = $atum_order->get_items( 'tax' );

						foreach ( $items as $item ) {

							if ( is_a( $item, '\WC_Order_Item_Product' ) ) {
								/**
								 * Variable definition
								 *
								 * @var \WC_Order_Item_Product $item
								 */
								$log_item = $atum_order->add_product( $item->get_product(), $item->get_quantity() );
							}
							elseif ( is_a( $item, '\WC_Order_Item_Fee' ) ) {
								/**
								 * Variable definition
								 *
								 * @var \WC_Order_Item_Fee $item
								 */
								$log_item = $atum_order->add_fee( $item );
							}
							elseif ( is_a( $item, '\WC_Order_Item_Shipping' ) ) {
								/**
								 * Variable definition
								 *
								 * @var \WC_Order_Item_Shipping $item
								 */
								$log_item = $atum_order->add_shipping_cost( $item );
							}
							elseif ( empty( $current_tax ) && is_a( $item, '\WC_Order_Item_Tax' ) ) {
								/**
								 * Variable definition
								 *
								 * @var \WC_Order_Item_Tax $item
								 */
								$log_item = $atum_order->add_tax( array( 'rate_id' => $item->get_rate_id() ), $item );
							}

							// Add the order ID as item's custom meta.
							if ( ! empty( $log_item ) ) {
								$log_item->add_meta_data( '_order_id', $wc_order_id, TRUE );
								$log_item->save_meta_data();
							}

						}

						// Load template.
						$html = Helpers::load_view_to_string( 'meta-boxes/atum-order/items', compact( 'atum_order' ) );

						wp_send_json_success( array( 'html' => $html ) );

					} catch ( \Exception $e ) {
						wp_send_json_error( array( 'error' => $e->getMessage() ) );
					}

				}

			}

		}

		wp_die( - 1 );

	}

	/**
	 * Mark an ATUM Order with a status
	 * NOTE: This callback is not being triggered through an Ajax request, just a normal HTTP request
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.2.4
	 */
	public function mark_atum_order_status() {

		$atum_order_id = absint( $_GET['atum_order_id'] );
		$post_type     = get_post_type( $atum_order_id );

		if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'atum-order-mark-status' ) ) {

			$status     = sanitize_text_field( $_GET['status'] );
			$atum_order = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error( $atum_order ) ) {
				wp_die( - 1 );
			}

			if ( $atum_order && in_array( $status, array_keys( AtumOrderPostType::get_statuses() ) ) ) {
				$atum_order->update_status( $status );
				do_action( 'atum/atum_orders/edit_status', $atum_order->get_id(), $status );
			}

		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( "edit.php?post_type=$post_type" ) );
		exit;

	}

	/**
	 * Set the ATUM control switch status to all variations at once
	 *
	 * @package Product Data
	 *
	 * @since 1.4.1
	 */
	public function set_variations_control_status() {

		check_ajax_referer( 'atum-product-data-nonce', 'security' );

		if ( empty( $_POST['parent_id'] ) ) {
			wp_send_json_error( __( 'No parent ID specified', ATUM_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['status'] ) ) {
			wp_send_json_error( __( 'No status specified', ATUM_TEXT_DOMAIN ) );
		}

		$product = wc_get_product( absint( $_POST['parent_id'] ) );

		if ( ! is_a( $product, '\WC_Product' ) ) {
			wp_send_json_error( __( 'Invalid parent product', ATUM_TEXT_DOMAIN ) );
		}

		$status     = esc_attr( $_POST['status'] );
		$variations = $product->get_children();

		foreach ( $variations as $variation_id ) {

			if ( 'uncontrolled' === $status ) {
				Helpers::disable_atum_control( $variation_id );
			}
			else {
				Helpers::enable_atum_control( $variation_id );
			}

		}

		wp_send_json_success( __( 'All the variations were updated successfully', ATUM_TEXT_DOMAIN ) );

	}

	/**
	 * Get the the Locations tree for a specific product
	 *
	 * @package ATUM List Tables
	 *
	 * @since 1.4.2
	 */
	public function get_locations_tree() {

		check_ajax_referer( 'atum-list-table-nonce', 'token' );

		$locations_tree = '';

		if ( empty( $_POST['product_id'] ) ) {
			wp_send_json_error( __( 'No valid product ID provided', ATUM_TEXT_DOMAIN ) );
		}

		$product_id = intval( $_POST['product_id'] );

		if ( $product_id > 0 ) {

			$locations  = wc_get_product_terms( $product_id, Globals::PRODUCT_LOCATION_TAXONOMY );

			if ( empty( $locations ) ) {
				wp_send_json_success( '<div class="alert alert-warning no-locations-set">' . __( 'No Locations were set for this product', ATUM_TEXT_DOMAIN ) . '</div>' );
			}
			else {

				$locations_tree = wp_list_categories( array(
					'taxonomy' => Globals::PRODUCT_LOCATION_TAXONOMY,
					'include'  => wp_list_pluck( $locations, 'term_id' ),
					'title_li' => '',
					'echo'     => FALSE,
				) );

				// Fix the list URLs to show the list of products within a location.
				$locations_tree = str_replace( home_url( '/?' ), admin_url( '/edit.php?post_type=product&' ), $locations_tree );
				$locations_tree = str_replace( '<a href', '<a target="_blank" href', $locations_tree );

			}

		}
		elseif ( -1 === $product_id ) {

			// Prepare all (used on set_locations_tree view). We don't care here of the urls... because they are disabled on this view.
			$locations_tree = wp_list_categories( array(
				'taxonomy' => Globals::PRODUCT_LOCATION_TAXONOMY,
				'title_li' => '',
				'echo'     => FALSE,
			) );

		}

		wp_send_json_success( "<ul>$locations_tree</ul>" );

	}

	/**
	 * Set the the Locations from tree for a specific product
	 *
	 * @package ATUM List Tables
	 *
	 * @since 1.4.11
	 */
	public function set_locations_tree() {

		check_ajax_referer( 'atum-list-table-nonce', 'token' );

		if ( empty( $_POST['product_id'] ) ) {
			wp_send_json_error( __( 'No valid product ID provided', ATUM_TEXT_DOMAIN ) );
		}

		$product_id      = absint( $_POST['product_id'] );
		$sanitized_terms = array_map( 'absint', $_POST['terms'] );

		wp_set_post_terms( $product_id, $sanitized_terms, Globals::PRODUCT_LOCATION_TAXONOMY, FALSE );

		wp_send_json_success( 'ok' );

	}

	/**
	 * Change the WC's manage stock status for all products from Tools section
	 *
	 * @package    Settings
	 * @subpackage Tools
	 *
	 * @since 1.4.5
	 */
	public function change_manage_stock() {

		check_ajax_referer( 'atum-script-runner-nonce', 'token' );

		if ( empty( $_POST['option'] ) ) {
			wp_send_json_error( __( 'Please select an option from the dropdown', ATUM_TEXT_DOMAIN ) );
		}

		$option = esc_attr( $_POST['option'] );

		if ( in_array( $option, [ 'manage', 'unmanage' ] ) ) {
			$manage_status = 'manage' === $option ? 'yes' : 'no';
			$this->change_status_meta( '_manage_stock', $manage_status );
		}

		wp_send_json_error( __( 'Something failed changing the Manage Stock option', ATUM_TEXT_DOMAIN ) );

	}

	/**
	 * Change the ATUM's control stock status for all products from Tools section
	 *
	 * @package    Settings
	 * @subpackage Tools
	 *
	 * @since 1.4.5
	 */
	public function change_control_stock() {

		check_ajax_referer( 'atum-script-runner-nonce', 'token' );

		if ( empty( $_POST['option'] ) ) {
			wp_send_json_error( __( 'Please select an option from the dropdown', ATUM_TEXT_DOMAIN ) );
		}

		$option = esc_attr( $_POST['option'] );

		if ( in_array( $option, [ 'control', 'uncontrol' ] ) ) {
			$control_status = 'control' === $option ? 'yes' : 'no';
			$this->change_status_meta( Globals::ATUM_CONTROL_STOCK_KEY, $control_status );
		}

		wp_send_json_error( __( 'Something failed changing the Control Stock option', ATUM_TEXT_DOMAIN ) );

	}


	/**
	 * Clear all Out Stock Threshold values that have been set
	 *
	 * @package    Settings
	 * @subpackage Tools
	 *
	 * @since 1.4.10
	 */
	public function clear_out_stock_threshold() {

		check_ajax_referer( 'atum-script-runner-nonce', 'token' );

		$this->clear_out_stock_threshold_meta();

		wp_send_json_error( __( 'Something failed clearing the Out of Stock Threshold values', ATUM_TEXT_DOMAIN ) );

	}

	/**
	 * Clear all the postmeta with OUT_STOCK_THRESHOLD_KEY, and rebuild all the _stock_status if
	 * required to comeback to the $woocommerce_notify_no_stock_amount
	 *
	 * @since 1.4.10
	 */
	private function clear_out_stock_threshold_meta() {

		Helpers::force_rebuild_stock_status( $product = NULL, $clean_meta = TRUE, $all = TRUE );

		if ( FALSE === Helpers::is_any_out_stock_threshold_set() ) {
			wp_send_json_success( __( 'All your previously saved values were cleared successfully.', ATUM_TEXT_DOMAIN ) );
		}

	}

	/**
	 * Change the value of a meta key for all products at once
	 *
	 * @since 1.4.5
	 *
	 * @param string $meta_key
	 * @param string $status
	 */
	private function change_status_meta( $meta_key, $status ) {

		global $wpdb;
		$wpdb->hide_errors();

		// If there are products without the manage_stock meta key, insert it for them.
		$insert_success = $wpdb->query( $wpdb->prepare("
			INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
			SELECT DISTINCT posts.ID, %s, %s FROM $wpdb->posts as posts
            LEFT JOIN $wpdb->postmeta as pm ON posts.ID = pm.post_id
            WHERE posts.post_type IN ('product', 'product_variation')
            AND posts.ID NOT IN (
                SELECT DISTINCT post_id FROM $wpdb->postmeta
                WHERE meta_key = %s
            )",
			$meta_key,
			$status,
			$meta_key
		) );

		// For the rest, just update those that don't have the right status.
		$update_success = $wpdb->query( $wpdb->prepare("
			UPDATE $wpdb->postmeta SET meta_value = %s		        		
            WHERE meta_key = %s 
            AND meta_value != %s",
			$status,
			$meta_key,
			$status
		) );

		if ( FALSE !== $insert_success && FALSE !== $update_success ) {
			wp_send_json_success( __( 'All your products were updated successfully', ATUM_TEXT_DOMAIN ) );
		}

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
	 * @return Ajax instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}
