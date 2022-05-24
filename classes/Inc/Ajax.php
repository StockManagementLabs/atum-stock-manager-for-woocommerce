<?php
/**
 * Ajax callbacks
 *
 * @package        Atum
 * @subpackage     Inc
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2022 Stock Management Labs™
 *
 * @since          0.0.1
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCache;
use Atum\Components\AtumCalculatedProps;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumColors;
use Atum\Components\AtumException;
use Atum\Components\AtumHelpGuide;
use Atum\Components\AtumMarketingPopup;
use Atum\Components\AtumWidget;
use Atum\Dashboard\Dashboard;
use Atum\Dashboard\WidgetHelpers;
use Atum\Dashboard\Widgets\Videos;
use Atum\InboundStock\Lists\ListTable as InboundStockListTable;
use Atum\Legacy\AjaxLegacyTrait;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Settings\Settings;
use Atum\InventoryLogs\Models\Log;
use Atum\InventoryLogs\InventoryLogs;
use Atum\StockCentral\Lists\ListTable;
use Atum\Suppliers\Supplier;
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

		// Filter current stock values within the Dashboard widget.
		add_action( 'wp_ajax_atum_current_stock_values', array( $this, 'current_stock_values' ) );

		// Load sales data on Dashboard widgets.
		add_action( 'wp_ajax_atum_dashboard_load_sales', array( $this, 'load_sales_data' ) );

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
		add_action( 'wp_ajax_atum_remove_license', array( $this, 'remove_license' ) );

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
		add_action( 'wp_ajax_atum_order_change_purchase_price', array( $this, 'change_atum_order_item_purchase_price' ) );

		// Only for Inventory Logs.
		add_action( 'wp_ajax_atum_order_increase_items_stock', array( $this, 'increase_atum_order_items_stock' ) );
		add_action( 'wp_ajax_atum_order_decrease_items_stock', array( $this, 'decrease_atum_order_items_stock' ) );

		// Update the ATUM Order status.
		add_action( 'wp_ajax_atum_order_mark_status', array( $this, 'mark_atum_order_status' ) );

		// Import WC order items to an Inventory Log.
		add_action( 'wp_ajax_atum_order_import_items', array( $this, 'import_wc_order_items' ) );

		// Set the ATUM control switch status to all variations at once.
		add_action( 'wp_ajax_atum_set_variations_control_status', array( $this, 'set_variations_control_status' ) );

		// Set the supplier to all variations at once.
		add_action( 'wp_ajax_atum_set_variations_supplier', array( $this, 'set_variations_supplier' ) );

		// Get the product locations tree.
		add_action( 'wp_ajax_atum_get_locations_tree', array( $this, 'get_locations_tree' ) );
		add_action( 'wp_ajax_atum_set_locations_tree', array( $this, 'set_locations_tree' ) );

		// Run scripts from Tools section.
		add_action( 'wp_ajax_atum_tool_manage_stock', array( $this, 'change_manage_stock' ) );
		add_action( 'wp_ajax_atum_tool_control_stock', array( $this, 'change_control_stock' ) );
		add_action( 'wp_ajax_atum_tool_clear_out_stock_threshold', array( $this, 'clear_out_stock_threshold' ) );
		add_action( 'wp_ajax_atum_tool_update_calc_props', array( $this, 'update_calc_props' ) );
		add_action( 'wp_ajax_atum_tool_clear_out_atum_transients', array( $this, 'clear_out_atum_transients' ) );

		// Change sticky columns settting.
		add_action( 'wp_ajax_atum_change_table_style_setting', array( $this, 'change_table_style_user_meta' ) );

		// Get marketing popup info.
		add_action( 'wp_ajax_atum_get_marketing_popup_info', array( $this, 'get_marketing_popup_info' ) );

		// Hide marketing popup.
		add_action( 'wp_ajax_atum_hide_marketing_popup', array( $this, 'marketing_popup_state' ) );

		// Hide marketing dashboard.
		add_action( 'wp_ajax_atum_hide_marketing_dashboard', array( $this, 'marketing_dashboard_state' ) );

		// Get color scheme.
		add_action( 'wp_ajax_atum_get_color_scheme', array( $this, 'get_color_scheme' ) );

		// Save PO Supplier on change.
		add_action( 'wp_ajax_atum_save_po_supplier', array( $this, 'save_purchase_order_supplier' ) );

		// Save PO Multiple Suppliers.
		add_action( 'wp_ajax_atum_save_po_multiple_supplier', array( $this, 'save_purchase_order_multiple_suppliers' ) );

		// Create a new supplier from the "Create Supplier" modal.
		add_action( 'wp_ajax_atum_create_supplier', array( $this, 'create_supplier' ) );

		// Get any help guide steps.
		add_action( 'wp_ajax_atum_get_help_guide_steps', array( $this, 'get_help_guide_steps' ) );

		// Save the closed state for an auto-guide on any screen.
		add_action( 'wp_ajax_atum_save_closed_auto_guide', array( $this, 'save_closed_auto_guide' ) );

	}

	/**
	 * Save the ATUM Dashboard layout as user meta
	 *
	 * @package Dashboard
	 *
	 * @since 1.4.0
	 */
	public function save_dashboard_layout() {

		check_ajax_referer( 'atum-dashboard-widgets', 'security' );

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

		check_ajax_referer( 'atum-dashboard-widgets', 'security' );

		$user_id = get_current_user_id();
		Dashboard::restore_user_widgets_layout( $user_id );
		wp_send_json_success();

	}

	/**
	 * Add a widget to the ATUM Dashboard
	 *
	 * @package Dashboard
	 *
	 * @since 1.4.0
	 */
	public function add_new_widget() {

		check_ajax_referer( 'atum-dashboard-widgets', 'security' );

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

		if ( ! $widget instanceof AtumWidget ) {
			wp_die( esc_attr__( 'Invalid widget', ATUM_TEXT_DOMAIN ) );
		}

		ob_start();

		$grid_item_settings = $dashboard->get_widget_grid_item_defaults( $widget_id );

		$dashboard->add_widget( $widget, $grid_item_settings, TRUE );

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

		check_ajax_referer( 'atum-dashboard-widgets', 'security' );

		if ( empty( $_POST['sortby'] ) ) {
			wp_die( - 1 );
		}

		ob_start();
		Helpers::load_view( 'widgets/videos', Videos::get_filtered_videos( esc_attr( $_POST['sortby'] ) ) );

		wp_die( ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Filter values within current stock values widget
	 *
	 * @package    Dashboard
	 * @subpackage Current Stock Values Widget
	 *
	 * @since 1.5.0
	 */
	public function current_stock_values() {

		check_ajax_referer( 'atum-dashboard-widgets', 'security' );

		$current_stock_values = WidgetHelpers::get_items_in_stock( $_POST['categorySelected'], $_POST['productTypeSelected'] );
		$current_stock_values = array_map( 'strval', $current_stock_values ); // Avoid issues with decimals when encoding to JSON.

		wp_send_json_success( compact( 'current_stock_values' ) );

	}

	/**
	 * Load sales data within the Sales widgets
	 *
	 * @package    Dashboard
	 * @subpackage Sales Widgets
	 *
	 * @since 1.7.1
	 */
	public function load_sales_data() {

		check_ajax_referer( 'atum-dashboard-widgets', 'security' );

		if ( empty( $_POST['widget'] ) || empty( $_POST['filter'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_TEXT_DOMAIN ) );
		}

		$widget = wc_clean( $_POST['widget'] );
		$filter = wc_clean( $_POST['filter'] );

		switch ( $widget ) {
			case 'sales':
				// Only 'today' and 'month' are available.
				$stats = WidgetHelpers::get_sales_stats( array(
					'types'      => [ 'sales' ],
					'date_start' => 'today' === $filter ? 'today midnight' : 'first day of this month midnight',
				) );

				break;

			case 'lost_sales':
				$args = array(
					'types' => [ 'lost_sales' ],
				);

				// Today.
				if ( 'today' === $filter ) {
					$args['days']       = 1;
					$args['date_start'] = 'midnight today';
				}
				// This month.
				else {
					$args['date_start'] = 'first day of this month midnight';
				}

				$stats = WidgetHelpers::get_sales_stats( $args );

				break;

			case 'orders':
			case 'promo_sales':
				$order_status = (array) apply_filters( 'atum/dashboard/orders_widget/order_status', [
					'wc-processing',
					'wc-completed',
				] );

				$args = array(
					'status' => $order_status,
				);

				switch ( $filter ) {
					case 'today':
						$args['date_start'] = 'today midnight';
						break;

					case 'this_month':
						$args['date_start'] = 'first day of this month midnight';
						break;

					case 'previous_month':
						$args['date_start'] = 'first day of last month midnight';
						$args['date_end']   = 'last day of last month 23:59:59';
						break;

					case 'this_week':
						$args['date_start'] = 'this week midnight';
						break;
				}

				$stats = 'orders' === $widget ? WidgetHelpers::get_orders_stats( $args ) : WidgetHelpers::get_promo_sales_stats( $args );

				break;

		}

		if ( ! isset( $stats ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_TEXT_DOMAIN ) );
		}

		wp_send_json_success( $stats );

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

		check_ajax_referer( 'atum-dashboard-widgets', 'security' );

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
	 *
	 * @param bool $return_data Optional. Whether to return the data or send the JSON to the browser.
	 *
	 * @return array|void
	 */
	public function fetch_stock_central_list( $return_data = FALSE ) {

		check_ajax_referer( 'atum-list-table-nonce', 'security' );

		$args = array(
			'per_page'        => ! empty( $_REQUEST['per_page'] ) ? absint( $_REQUEST['per_page'] ) : Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE ),
			'show_cb'         => ! empty( $_REQUEST['show_cb'] ) ? (bool) $_REQUEST['show_cb'] : FALSE,
			'show_controlled' => ! empty( $_REQUEST['show_controlled'] ) ? (bool) $_REQUEST['show_controlled'] : FALSE,
			'screen'          => esc_attr( $_REQUEST['screen'] ),
		);

		do_action( 'atum/ajax/stock_central_list/before_fetch_list' );

		if ( ! empty( $_REQUEST['view'] ) && 'all_stock' === $_REQUEST['view'] ) {
			$_REQUEST['view'] = '';
		}

		$namespace  = '\Atum\StockCentral\Lists';
		$list_class = $args['show_controlled'] ? "$namespace\ListTable" : "$namespace\UncontrolledListTable";

		/**
		 * Variable deifinition
		 *
		 * @var ListTable $list
		 */
		$list = new $list_class( $args );

		return $list->ajax_response( $return_data );

	}

	/**
	 * Loads the Inbound Stock ListTable class and calls ajax_response method
	 *
	 * @package Inbound Stock
	 *
	 * @since 1.3.0
	 */
	public function fetch_inbound_stock_list() {

		check_ajax_referer( 'atum-list-table-nonce', 'security' );

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
		update_user_meta( get_current_user_id(), 'atum_admin_footer_text_rated', 1 );
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

		check_ajax_referer( 'atum-list-table-nonce', 'security' );

		if ( empty( $_POST['data'] ) ) {
			wp_send_json_error( __( 'Error saving the table data.', ATUM_TEXT_DOMAIN ) );
		}

		try {

			// Disable cache to avoid saving the wrong data.
			$was_cache_disabled = AtumCache::is_cache_disabled();
			if ( ! $was_cache_disabled ) {
				AtumCache::disable_cache();
			}

			$data = json_decode( stripslashes( $_POST['data'] ), TRUE );

			if ( empty( $data ) ) {
				wp_send_json_error( __( 'Error saving the table data.', ATUM_TEXT_DOMAIN ) );
			}

			$data = apply_filters( 'atum/ajax/before_update_product_meta', $data );

			foreach ( $data as $product_id => &$product_meta ) {
				Helpers::update_product_data( $product_id, $product_meta );
			}

			// If the first edit notice was already shown, save it as user meta.
			if ( ! empty( $_POST['first_edit_key'] ) ) {
				update_user_meta( get_current_user_id(), esc_attr( $_POST['first_edit_key'] ), 1 );
			}

			do_action( 'atum/ajax/after_update_list_data', $data );

			if ( ! $was_cache_disabled ) {
				AtumCache::enable_cache();
			}

			// Ensure all the data is read from the DB after updating.
			AtumCache::delete_all_atum_caches();

			// If we aren't in SC, we will have to fetch the data from elsewhere.
			$table_data = apply_filters( 'atum/ajax/update_list_data/fetch_table_data', NULL );

			if ( ! $table_data ) {
				$table_data = $this->fetch_stock_central_list( TRUE );
			}

			wp_send_json_success( [
				'notice'    => __( 'Data saved.', ATUM_TEXT_DOMAIN ),
				'tableData' => $table_data,
			] );

		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}

	}

	/**
	 * Apply actions in bulk to the selected ListTable rows
	 *
	 * @package ATUM List Tables
	 *
	 * @since 1.4.1
	 */
	public function apply_bulk_action() {

		check_ajax_referer( 'atum-list-table-nonce', 'security' );

		if ( empty( $_POST['ids'] ) || ! is_array( $_POST['ids'] ) ) {
			wp_send_json_error( __( 'No Items Selected.', ATUM_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['bulk_action'] ) ) {
			wp_send_json_error( __( 'Invalid bulk action.', ATUM_TEXT_DOMAIN ) );
		}

		$ids         = array_unique( $_POST['ids'] );
		$bulk_action = esc_attr( $_POST['bulk_action'] );

		switch ( $bulk_action ) {
			case 'uncontrol_stock':
				foreach ( $ids as $id ) {

					// Support non numeric values (for MI items, for example) that will be treated later.
					if ( ! is_numeric( $id ) ) {
						continue;
					}

					Helpers::update_atum_control( absint( $id ), 'disable' );

				}

				break;

			case 'control_stock':
				foreach ( $ids as $id ) {

					// Support non numeric values (for MI items, for example) that will be treated later.
					if ( ! is_numeric( $id ) ) {
						continue;
					}

					Helpers::update_atum_control( absint( $id ) );

				}

				break;

			case 'unmanage_stock':
				foreach ( $ids as $id ) {

					// Support non numeric values (for MI items, for example) that will be treated later.
					if ( ! is_numeric( $id ) ) {
						continue;
					}

					Helpers::update_wc_manage_stock( absint( $id ), 'disable' );

				}

				break;

			case 'manage_stock':
				foreach ( $ids as $id ) {

					// Support non numeric values (for MI items, for example) that will be treated later.
					if ( ! is_numeric( $id ) ) {
						continue;
					}

					Helpers::update_wc_manage_stock( absint( $id ) );

				}

				break;
		}

		$args = [ $bulk_action, $ids ];

		if ( ! empty( $_POST['extra_data'] ) ) {
			$args[] = $_POST['extra_data'];
		}

		do_action_ref_array( 'atum/ajax/list_table/bulk_action_applied', $args );

		wp_send_json_success( __( 'Action applied to the selected items successfully.', ATUM_TEXT_DOMAIN ) );

	}

	/**
	 * Control all the shop products at once from the List Tables' button
	 *
	 * @package ATUM List Tables
	 *
	 * @since 1.4.10
	 */
	public function control_all_products() {

		check_ajax_referer( 'atum-control-all-products-nonce', 'security' );
		Helpers::change_status_meta( Globals::ATUM_CONTROL_STOCK_KEY, 'yes' );

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

		$addon_name = esc_attr( $_POST['addon'] );
		$key        = esc_attr( $_POST['key'] );

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

				// The staging sites doesn't compute as a new activation.
				if ( Addons::is_local_url() ) {

					wp_send_json( array(
						'success' => 'activate',
						'data'    => __( "Your license is valid.<br>This site has been recognised as a staging site and won't compute as a new activation.<br>Please, click the button to activate.", ATUM_TEXT_DOMAIN ),
					) );

				}
				else {

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

				}

				break;

			case 'expired':
				$timestamp     = Helpers::get_current_timestamp();
				$error_message = sprintf(
					/* translators: the expiration date */
					__( 'Your license key expired on %s.', ATUM_TEXT_DOMAIN ),
					date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, $timestamp ) )
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

		check_ajax_referer( ATUM_PREFIX . 'manage_license', 'security' );

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

		$addon_name    = esc_attr( $_POST['addon'] );
		$key           = esc_attr( $_POST['key'] );
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
						$timestamp = Helpers::get_current_timestamp();
						$message   = sprintf(
							/* translators: the expiration date */
							__( 'Your license key expired on %s.', ATUM_TEXT_DOMAIN ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, $timestamp ) )
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

		$addon_name    = esc_attr( $_POST['addon'] );
		$key           = esc_attr( $_POST['key'] );
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

		$addon_name    = esc_attr( $_POST['addon'] );
		$addon_slug    = esc_attr( $_POST['slug'] );
		$key           = esc_attr( $_POST['key'] );
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
	 * Remove any invalid license key from the add-ons page
	 *
	 * @package Add-ons
	 *
	 * @since 1.8.8
	 */
	public function remove_license() {

		check_ajax_referer( ATUM_PREFIX . 'manage_license', 'security' );

		if ( empty( $_POST['addon'] ) ) {
			wp_send_json_error( __( 'Add-on name not provided', ATUM_TEXT_DOMAIN ) );
		}

		// Clear the key.
		$addon_name = esc_attr( $_POST['addon'] );
		Addons::update_key( $addon_name, '' );

		// Delete the transient.
		Addons::delete_status_transient( $addon_name );

		wp_send_json_success();

	}

	/**
	 * If the site is not using the new tables, use the legacy methods
	 *
	 * @since 1.5.0
	 * @deprecated Only for backwards compatibility and will be removed in a future version.
	 */
	use AjaxLegacyTrait;

	/**
	 * Seach for products from enhanced selects
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.3.7
	 */
	public function search_products() {

		/**
		 * If the site is not using the new tables, use the legacy method
		 *
		 * @since 1.5.0
		 * @deprecated Only for backwards compatibility and will be removed in a future version.
		 */
		if ( ! Helpers::is_using_new_wc_tables() ) {
			$this->search_products_legacy();
			return;
		}

		check_ajax_referer( 'search-products', 'security' );

		ob_start();

		$term = stripslashes( $_GET['term'] );

		$post_id = isset( $_GET['limit'] ) ? intval( $_GET['limit'] ) : 0;

		if ( empty( $term ) ) {
			wp_die( [] );
		}

		global $wpdb;

		$like_term     = '%%' . $wpdb->esc_like( $term ) . '%%';
		$post_types    = apply_filters( 'atum/ajax/search_products/searched_post_types', [ 'product', 'product_variation' ] );
		$post_statuses = Globals::get_queryable_product_statuses();
		$meta_join     = $meta_where = array();
		$type_where    = '';

		// Search by SKU.
		$meta_join[]  = "LEFT JOIN {$wpdb->prefix}wc_products wcd ON posts.ID = wcd.product_id";
		$meta_where[] = $wpdb->prepare( 'OR wcd.sku LIKE %s', $like_term );

		// Search by Supplier SKU.
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$meta_join[]     = "LEFT JOIN $atum_data_table apd ON posts.ID = apd.product_id";
		$meta_where[]    = $wpdb->prepare( 'OR apd.supplier_sku LIKE %s', $like_term );

		// Exclude variable products from results.
		$excluded_types = (array) apply_filters( 'atum/ajax/search_products/excluded_product_types', array_diff( Globals::get_inheritable_product_types(), [ 'grouped', 'bundle' ] ) );

		if ( ! empty( $excluded_types ) ) {

			$type_where = "AND posts.ID NOT IN (
				SELECT wpd1.product_id FROM {$wpdb->prefix}wc_products wpd1		
				WHERE wpd1.type IN ('" . implode( "','", $excluded_types ) . "')
			)";

		}

		$query_select = "SELECT DISTINCT posts.ID FROM $wpdb->posts posts " . implode( "\n", $meta_join ) . ' ';

		// phpcs:disable
		$where_clause = $wpdb->prepare( '
			WHERE (
				posts.post_title LIKE %s
				OR posts.post_content LIKE %s
				' . implode( "\n", $meta_where ) . "
			)
			AND posts.post_type IN ('" . implode( "','", $post_types ) . "')
			AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "')
			" . $type_where . ' ',
			$like_term,
			$like_term
		);
		// phpcs:enable

		$query_select = apply_filters( 'atum/ajax/search_products/query_select', $query_select );
		$where_clause = apply_filters( 'atum/ajax/search_products/query_where', $where_clause );

		$query = "
			$query_select $where_clause
			ORDER BY posts.post_parent ASC, posts.post_title ASC
		";

		$product_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

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
			$post_id = absint( $url_query['post'] );
		}

		if ( $post_id ) {

			/**
			 * Variable definition
			 *
			 * @var PurchaseOrder $po
			 */
			$po = Helpers::get_atum_order_model( $post_id, FALSE );

			// The Purchase Orders only should allow products from the current PO's supplier (if such PO only allows 1 supplier).
			if ( $po instanceof PurchaseOrder && ! $po->has_multiple_suppliers() ) {

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
			wp_die( [] );
		}

		// Get all the orders with IDs starting with the provided number.
		global $wpdb;
		$max_results = absint( apply_filters( 'atum/ajax/search_wc_orders/max_results', 10 ) );

		// phpcs:disable
		$query = $wpdb->prepare(
			"SELECT DISTINCT ID from {$wpdb->posts} WHERE post_type = 'shop_order' 
			AND post_status IN ('" . implode( "','", array_keys( wc_get_order_statuses() ) ) . "') 
			AND ID LIKE %s LIMIT %d",
			"$order_id%",
			$max_results
		);
		// phpcs:enable

		$order_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $order_ids ) ) {
			wp_die( [] );
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
			wp_die( [] );
		}

		// Get all the orders with IDs starting with the provided number.
		$max_results   = absint( apply_filters( 'atum/ajax/search_suppliers/max_results', 10 ) );
		$post_statuses = AtumCapabilities::current_user_can( 'edit_private_suppliers' ) ? [ 'private', 'publish' ] : [ 'publish' ];

		// phpcs:disable WordPress.DB.PreparedSQL
		$query = $wpdb->prepare(
			"SELECT DISTINCT ID, post_title from $wpdb->posts 
			WHERE post_type = %s $where
			AND post_status IN ('" . implode( "','", $post_statuses ) . "')
			ORDER by post_title ASC
			LIMIT %d",
			Suppliers::POST_TYPE,
			$max_results
		);
		// phpcs:enable

		$suppliers = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $suppliers ) ) {
			wp_die( [] );
		}

		$supplier_results = array();
		foreach ( $suppliers as $supplier ) {
			$supplier_results[ $supplier->ID ] = $supplier->post_title;
		}

		wp_send_json( $supplier_results );

	}

	/**
	 * Add a custom note to an ATUM Order
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

			$atum_order = Helpers::get_atum_order_model( $post_id, FALSE );

			if ( ! is_wp_error( $atum_order ) ) {

				$comment_id = $atum_order->add_order_note( $note, TRUE );
				Helpers::save_order_note_meta( $comment_id, [ 'action' => 'ajax_note' ] );
				$note_comment = get_comment( $comment_id );

				do_action( 'atum/ajax/atum_order/note_added', $atum_order, $comment_id );

				Helpers::load_view( 'meta-boxes/atum-order/note', compact( 'note_comment' ) );

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

		do_action( 'atum/ajax/atum_order/before_remove_note', $note_id );

		if ( $note_id ) {
			wc_delete_order_note( $note_id );
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
		$atum_order    = Helpers::get_atum_order_model( $atum_order_id, TRUE );

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
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id, TRUE );

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
				$product = Helpers::get_atum_product( $item_to_add );
				$item    = $atum_order->add_product( $product );
				$item_id = $item->get_id();
				$class   = 'new_row';

				// Both hooks are needed to replicate WC ajax ones ( woocommerce_ajax_order_item & woocommerce_ajax_order_items_added )
				// to simplify used hooks (e.g. see MI Hooks).
				$item = apply_filters( 'atum/atum_order/order_item', $item, $item_id, $atum_order, $product );
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
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id, TRUE );

			if ( is_wp_error( $atum_order ) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			// Add a fee line item.
			$item    = $atum_order->add_fee();
			$item_id = $item->get_id();

			do_action( 'atum/ajax/atum_order/fee_added', $atum_order, $item );

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
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id, TRUE );

			if ( is_wp_error( $atum_order ) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			$shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();

			// Add new shipping cost line item.
			$item    = $atum_order->add_shipping_cost();
			$item_id = $item->get_id();

			do_action( 'atum/ajax/atum_order/shipping_cost_added', $atum_order, $item );

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
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id, TRUE );

			if ( is_wp_error( $atum_order ) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			// Add new tax.
			$atum_order->add_tax( array( 'rate_id' => $rate_id ) );

			do_action( 'atum/ajax/atum_order/tax_added', $atum_order, $rate_id );

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
			wp_send_json_error( __( "You aren't allowed to edit shop orders", ATUM_TEXT_DOMAIN ) );
		}

		$atum_order_id       = absint( $_POST['atum_order_id'] );
		$atum_order_item_ids = $_POST['atum_order_item_ids'];

		if ( ! is_array( $atum_order_item_ids ) && is_numeric( $atum_order_item_ids ) ) {
			$atum_order_item_ids = [ $atum_order_item_ids ];
		}

		$atum_order_item_ids = array_unique( array_filter( array_map( 'absint', $atum_order_item_ids ) ) );

		if ( ! empty( $atum_order_item_ids ) ) {

			$atum_order = Helpers::get_atum_order_model( $atum_order_id, TRUE );

			if ( is_wp_error( $atum_order ) ) {
				wp_send_json_error( 'Something failed while reading the order. Please, save and try again.', ATUM_TEXT_DOMAIN );
			}

			do_action( 'atum/ajax/atum_order/before_remove_order_items', $atum_order, $atum_order_item_ids );

			foreach ( $atum_order_item_ids as $id ) {
				$atum_order->remove_item( $id );
				do_action( 'atum/atum_order/delete_order_item', $id );
			}

			$atum_order->save_items();

		}
		else {
			wp_send_json_error( __( "The item couldn't be removed: invalid or missing data", ATUM_TEXT_DOMAIN ) );
		}

		wp_send_json_success();

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
		$atum_order    = Helpers::get_atum_order_model( $atum_order_id, TRUE );

		if ( is_wp_error( $atum_order ) ) {
			wp_die( - 1 );
		}

		do_action( 'atum/ajax/atum_order/before_remove_order_items', $atum_order, $rate_id );

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

		$atum_order = Helpers::get_atum_order_model( $atum_order_id, TRUE );

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
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id, TRUE );

			if ( is_wp_error( $atum_order ) ) {
				wp_die( - 1 );
			}

			$atum_order->save_posted_order_items();

			// Return HTML items.
			Helpers::load_view( 'meta-boxes/atum-order/items', compact( 'atum_order' ) );

		}

		wp_die();

	}

	/**
	 * Increase the ATUM Inventory Logs order products' stock by their quantity amount
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.3.0
	 */
	public function increase_atum_order_items_stock() {
		$this->bulk_change_atum_order_items_stock( 'increase' );
	}

	/**
	 * Decrease the ATUM Inventory Logs order products' stock by their quantity amount
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.3.0
	 */
	public function decrease_atum_order_items_stock() {
		$this->bulk_change_atum_order_items_stock( 'decrease' );
	}

	/**
	 * Change the ATUM order products' stock by their quantity amount. Only used for IL
	 *
	 * @package ATUM Orders
	 *
	 * @since 1.3.0
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
		$mode                = wc_clean( wp_unslash( $_POST['mode'] ) );

		$atum_order       = Helpers::get_atum_order_model( $atum_order_id, TRUE );
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

				if ( $product instanceof \WC_Product && $product->exists() && $product->managing_stock() && isset( $quantities[ $item_id ] ) && $quantities[ $item_id ] > 0 ) {

					$old_stock = $product->get_stock_quantity();

					// if stock is null but WC is managing stock.
					if ( is_null( $old_stock ) ) {
						$old_stock = 0;
						wc_update_product_stock( $product, $old_stock );
					}

					$stock_change = apply_filters( 'atum/ajax/restore_atum_order_stock_quantity', $quantities[ $item_id ], $item_id );
					$new_stock    = wc_update_product_stock( $product, $stock_change, $action );
					$item_name    = $product->get_formatted_name();

					if ( 'increase' === $action ) {
						$note = __( 'Stock levels increased:', ATUM_TEXT_DOMAIN );
					}
					else {
						$note = __( 'Stock levels reduced:', ATUM_TEXT_DOMAIN );
					}

					$note .= ' ' . $item_name . ' ' . $old_stock . '&rarr;' . $new_stock;

					$note     = apply_filters( 'atum/atum_order/add_stock_change_note', $note, $product, $action, $stock_change );
					$return[] = $note;

					$note_id = $atum_order->add_order_note( $note );

					// Only inventory logs should execute this function.
					Helpers::save_order_note_meta( $note_id, [
						'action'        => "{$action}_stock",
						'item_name'     => $atum_order_item->get_name(),
						'product_id'    => $product->get_id(),
						'old_stock'     => $old_stock,
						'new_stock'     => $new_stock,
						'stock_change'  => $stock_change,
						'order_type'    => 3,
						'order_item_id' => $item_id,
					] );

					$atum_order_item->set_stock_changed( TRUE );
					$atum_order_item->save();

				}
			}

			do_action( "atum/ajax/{$action}_atum_order_stock", $atum_order, $mode );

			if ( empty( $return ) ) {

				wp_send_json_error( sprintf(
					/* translators: the action performed */
					__( 'No products had their stock %s - they may not have stock management enabled.', ATUM_TEXT_DOMAIN ),
					'increase' === $action ? __( 'increased', ATUM_TEXT_DOMAIN ) : __( 'decreased', ATUM_TEXT_DOMAIN )
				) );

			}

		}

		if ( InventoryLogs::POST_TYPE === $atum_order->get_post_type() ) {
			$post_id = $atum_order->get_id();
			wp_send_json_success( Helpers::load_view_to_string( 'meta-boxes/atum-order/notes', compact( 'post_id' ) ) );

		} else {
			wp_send_json_success();
		}

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

		$atum_order      = Helpers::get_atum_order_model( absint( $_POST['atum_order_id'] ), TRUE, PurchaseOrders::POST_TYPE );
		$atum_order_item = $atum_order->get_item( absint( $_POST['atum_order_item_id'] ) );

		/**
		 * Variable definition
		 *
		 * @var \WC_Order_Item_Product $atum_order_item
		 */
		$product_id = $atum_order_item->get_variation_id() ?: $atum_order_item->get_product_id();
		$product    = Helpers::get_atum_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			wp_send_json_error( __( 'Product not found', ATUM_TEXT_DOMAIN ) );
		}

		do_action( 'atum/ajax/atum_order/before_set_purchase_price', $atum_order, $atum_order_item, $_POST[ Globals::PURCHASE_PRICE_KEY ] );

		$product->set_purchase_price( $_POST[ Globals::PURCHASE_PRICE_KEY ] );
		$product->save_atum_data();

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

					$atum_order_type = get_post_type( $atum_order_id );

					// *** NOTE: FOR NOW THIS IS ONLY USED ON LOGS, IF NEEDS TO BE COMPATIBLE WITH OTHER
					// ATUM ORDERS IN THE FUTURE, THIS WILL NEED REFACTORY ***
					$atum_order = apply_filters( 'atum/ajax/get_atum_order_import_items', new Log( $atum_order_id ), $atum_order_id );

					try {

						// The log only can have one tax applied, so check if already has one.
						$current_tax = $atum_order->get_items( 'tax' );

						foreach ( $items as $item ) {

							if ( $item instanceof \WC_Order_Item_Product ) {
								/**
								 * Variable definition
								 *
								 * @var \WC_Order_Item_Product $item
								 */
								if ( PurchaseOrders::get_post_type() === $atum_order_type ) {
									$imported_item = FALSE;

									foreach ( $atum_order->get_items( 'line_item' ) as $atum_order_item ) {
										/**
										 * Variable definition
										 *
										 * @var \WC_Order_Item_Product $atum_order_item
										 */
										if ( $item->get_product_id() === $atum_order_item->get_product_id() ) {
											$imported_item = TRUE;
											break;
										}
									}
									if ( $imported_item ) {
										continue;
									}
								}
								$product  = Helpers::get_atum_product( $item->get_product() );
								$log_item = $atum_order->add_product( $product, $item->get_quantity() );

								do_action( 'atum/atum_order/import_order_item', $log_item, $atum_order, $item, $wc_order );
							}
							elseif ( $item instanceof \WC_Order_Item_Fee ) {
								/**
								 * Variable definition
								 *
								 * @var \WC_Order_Item_Fee $item
								 */
								if ( PurchaseOrders::get_post_type() === $atum_order_type ) {
									$imported_item = FALSE;

									foreach ( $atum_order->get_items( 'fee' ) as $atum_order_item ) {
										/**
										 * Variable definition
										 *
										 * @var \WC_Order_Item_Fee $atum_order_item
										 */
										if ( $item->get_name() === $atum_order_item->get_name() && $item->get_amount() === $atum_order_item->get_amount() ) {
											$imported_item = TRUE;
											break;
										}
									}
									if ( $imported_item ) {
										continue;
									}
								}
								$log_item = $atum_order->add_fee( $item );
							}
							elseif ( $item instanceof \WC_Order_Item_Shipping ) {
								/**
								 * Variable definition
								 *
								 * @var \WC_Order_Item_Shipping $item
								 */
								if ( PurchaseOrders::get_post_type() === $atum_order_type ) {
									$imported_item = FALSE;

									foreach ( $atum_order->get_items( 'shipping' ) as $atum_order_item ) {
										/**
										 * Variable definition
										 *
										 * @var \WC_Order_Item_Shipping $atum_order_item
										 */
										if ( $item->get_name() === $atum_order_item->get_name() && $item->get_quantity() === $atum_order_item->get_quantity() ) {
											$imported_item = TRUE;
											break;
										}
									}
									if ( $imported_item ) {
										continue;
									}
								}
								$log_item = $atum_order->add_shipping_cost( $item );
							}
							elseif ( empty( $current_tax ) && $item instanceof \WC_Order_Item_Tax ) {
								/**
								 * Variable definition
								 *
								 * @var \WC_Order_Item_Tax $item
								 */
								if ( PurchaseOrders::get_post_type() === $atum_order_type ) {
									continue;
								}
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
				else {
					wp_send_json_error( array( 'error' => __( 'The order is empty', ATUM_TEXT_DOMAIN ) ) );
				}

			}
			else {
				wp_send_json_error( array( 'error' => __( 'The order doesn\t exist', ATUM_TEXT_DOMAIN ) ) );
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
			$atum_order = Helpers::get_atum_order_model( $atum_order_id, TRUE );

			if ( is_wp_error( $atum_order ) ) {
				wp_die( - 1 );
			}

			if ( $atum_order ) {
				$atum_order->update_status( $status );
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

		if ( empty( $_POST['value'] ) ) {
			wp_send_json_error( __( 'Please, choose a status from the dropdown', ATUM_TEXT_DOMAIN ) );
		}

		$product = Helpers::get_atum_product( absint( $_POST['parent_id'] ) );

		if ( ! $product instanceof \WC_Product ) {
			wp_send_json_error( __( 'Invalid parent product', ATUM_TEXT_DOMAIN ) );
		}

		$status     = esc_attr( $_POST['value'] );
		$variations = $product->get_children();

		foreach ( $variations as $variation_id ) {
			Helpers::update_atum_control( $variation_id, ( 'uncontrolled' === $status ? 'disable' : 'enable' ) );
		}

		wp_send_json_success( __( 'All the variations were updated successfully', ATUM_TEXT_DOMAIN ) );

	}

	/**
	 * Set the supplier to all variations at once
	 *
	 * @package Product Data
	 *
	 * @since 1.6.7
	 */
	public function set_variations_supplier() {

		check_ajax_referer( 'atum-product-data-nonce', 'security' );

		if ( empty( $_POST['parent_id'] ) ) {
			wp_send_json_error( __( 'No parent ID specified', ATUM_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['value'] ) ) {
			wp_send_json_error( __( 'Please specify a valid supplier', ATUM_TEXT_DOMAIN ) );
		}

		$product = Helpers::get_atum_product( absint( $_POST['parent_id'] ) );

		if ( ! $product instanceof \WC_Product ) {
			wp_send_json_error( __( 'Invalid parent product', ATUM_TEXT_DOMAIN ) );
		}

		$supplier_id = absint( $_POST['value'] );
		$variations  = $product->get_children();

		foreach ( $variations as $variation_id ) {
			$variation = Helpers::get_atum_product( $variation_id );
			$variation->set_supplier_id( $supplier_id );
			$variation->save_atum_data();
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

		check_ajax_referer( 'atum-list-table-nonce', 'security' );

		$locations_tree = '';

		if ( empty( $_POST['product_id'] ) ) {
			wp_send_json_error( __( 'No valid product ID provided', ATUM_TEXT_DOMAIN ) );
		}

		do_action( 'atum/ajax/stock_central_list/get_locations_tree' );

		$product_id = intval( $_POST['product_id'] ); // Not using absint because it could be -1.

		if ( $product_id > 0 ) {

			$locations = wc_get_product_terms( $product_id, Globals::PRODUCT_LOCATION_TAXONOMY );

			if ( empty( $locations ) ) {
				wp_send_json_success( '<div class="alert alert-warning no-locations-set">' . __( 'No locations were set for this product', ATUM_TEXT_DOMAIN ) . '</div>' );
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
		// Prepare all (used on set_locations_tree view). We don't care here of the urls... because they are disabled on this view.
		elseif ( -1 === $product_id ) {

			$locations_tree = wp_list_categories( array(
				'taxonomy'   => Globals::PRODUCT_LOCATION_TAXONOMY,
				'title_li'   => '',
				'echo'       => FALSE,
				'hide_empty' => FALSE,
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

		check_ajax_referer( 'atum-list-table-nonce', 'security' );

		if ( empty( $_POST['product_id'] ) ) {
			wp_send_json_error( __( 'No valid product ID provided', ATUM_TEXT_DOMAIN ) );
		}

		$terms = empty( $_POST['terms'] ) ? [] : $_POST['terms'];

		$product_id      = absint( $_POST['product_id'] );
		$sanitized_terms = array_map( 'absint', $terms );

		do_action( 'atum/ajax/stock_central_list/before_set_locations', $product_id, $sanitized_terms );

		wp_set_post_terms( $product_id, $sanitized_terms, Globals::PRODUCT_LOCATION_TAXONOMY, FALSE );

		$product = Helpers::get_atum_product( $product_id );
		$product->set_has_location( ! empty( $terms ) );
		$product->save();

		do_action( 'atum/ajax/stock_central_list/after_set_locations', $product_id );

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

		check_ajax_referer( 'atum-script-runner-nonce', 'security' );

		if ( empty( $_POST['option'] ) ) {
			wp_send_json_error( __( 'Please select an option from the dropdown', ATUM_TEXT_DOMAIN ) );
		}

		$option = esc_attr( $_POST['option'] );

		if ( in_array( $option, [ 'manage', 'unmanage' ] ) ) {
			$manage_status = 'manage' === $option ? 'yes' : 'no';
			do_action( 'atum/ajax/tool_change_manage_stock' );
			Helpers::change_status_meta( '_manage_stock', $manage_status );
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

		check_ajax_referer( 'atum-script-runner-nonce', 'security' );

		if ( empty( $_POST['option'] ) ) {
			wp_send_json_error( __( 'Please select an option from the dropdown', ATUM_TEXT_DOMAIN ) );
		}

		$option = esc_attr( $_POST['option'] );

		if ( in_array( $option, [ 'control', 'uncontrol' ] ) ) {
			$control_status = 'control' === $option ? 'yes' : 'no';
			do_action( 'atum/ajax/tool_change_control_stock' );
			Helpers::change_status_meta( Globals::ATUM_CONTROL_STOCK_KEY, $control_status );
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

		check_ajax_referer( 'atum-script-runner-nonce', 'security' );

		Helpers::force_rebuild_stock_status( NULL, TRUE, TRUE );

		if ( FALSE === Helpers::is_any_out_stock_threshold_set() ) {
			do_action( 'atum/ajax/tool_clear_out_stock_threshold' );
			wp_send_json_success( __( 'All your previously saved values were cleared successfully.', ATUM_TEXT_DOMAIN ) );
		}

		wp_send_json_error( __( 'Something failed clearing the Out of Stock Threshold values', ATUM_TEXT_DOMAIN ) );

	}

	/**
	 * Change the List Table style status for the current user
	 *
	 * @package ATUM List Tables
	 *
	 * @since 1.5.0
	 */
	public function change_table_style_user_meta() {

		check_ajax_referer( 'atum-list-table-style', 'security' );

		if ( ! isset( $_POST['enabled'], $_POST['feature'] ) ) {
			wp_die( -1 );
		}

		$value = wc_bool_to_string( $_POST['enabled'] );
		$key   = 'sticky-columns' === $_POST['feature'] ? 'enabled_sc_sticky_columns' : 'enabled_sc_sticky_header';

		Helpers::set_atum_user_meta( $key, $value );

		wp_die();

	}

	/**
	 * Get marketing popup info
	 *
	 * @package ATUM Marketing Popup
	 *
	 * @since 1.5.3
	 */
	public function get_marketing_popup_info() {

		check_ajax_referer( 'atum-marketing-popup-nonce', 'security' );

		if ( Helpers::show_marketing_popup() ) {

			$marketing_popup = AtumMarketingPopup::get_instance();

			$marketing_popup_data = [
				'background'    => $marketing_popup->get_background(),
				'title'         => $marketing_popup->get_title(),
				'description'   => $marketing_popup->get_description(),
				'version'       => $marketing_popup->get_version(),
				'buttons'       => $marketing_popup->get_buttons(),
				'hoverButtons'  => $marketing_popup->get_buttons_hover_style_block(),
				'images'        => $marketing_popup->get_images(),
				'footerNotice'  => $marketing_popup->get_footer_notice(),
				'transient_key' => $marketing_popup->get_transient_key(),
			];

			// Send marketing popup content.
			wp_send_json_success( $marketing_popup_data );

		}

		wp_die();

	}

	/**
	 * Hide marketing popup
	 *
	 * @package ATUM Marketing Popup
	 *
	 * @since 1.5.3
	 */
	public function marketing_popup_state() {

		check_ajax_referer( 'atum-marketing-popup-nonce', 'security' );

		if ( ! isset( $_POST['transientKey'] ) ) {
			wp_die();
		}

		$transient_key = esc_attr( $_POST['transientKey'] );

		update_user_meta( get_current_user_id(), 'atum-marketing-popup', $transient_key );
		AtumCache::set_transient( 'atum-marketing-popup', $transient_key, WEEK_IN_SECONDS, TRUE );

		wp_die();

	}

	/**
	 * Hide marketing dashboard
	 *
	 * @package ATUM Dashboard
	 *
	 * @since 1.7.6
	 */
	public function marketing_dashboard_state() {

		check_ajax_referer( 'atum-dashboard-widgets', 'security' );

		if ( ! isset( $_POST['transientKey'] ) ) {
			wp_die();
		}

		$transient_key = esc_attr( $_POST['transientKey'] );

		update_user_meta( get_current_user_id(), 'atum-marketing-dash', $transient_key );
		AtumCache::set_transient( 'atum-marketing-dash', $transient_key, WEEK_IN_SECONDS, TRUE );

		wp_die();

	}

	/**
	 * Settings - Get Color Scheme
	 *
	 * @package ATUM Settings
	 *
	 * @since 1.5.9
	 */
	public function get_color_scheme() {

		check_ajax_referer( 'atum-color-scheme-nonce', 'security' );

		$custom_settings = $settings = [];

		if ( 0 === absint( $_POST['reset'] ) ) {

			foreach ( AtumColors::DEFAULT_COLOR_SCHEMES as $dset => $dval ) {
				$val = Helpers::get_color_value( $dset );

				if ( $val && $val !== $dval ) {
					$custom_settings[ $dset ] = $val;
				}

			}

			if ( count( $custom_settings ) > 0 ) {
				foreach ( $custom_settings as $cset => $cval ) {
					$settings[ $cset ] = $cval;
				}
			}

		}

		wp_send_json_success( $settings );

	}

	/**
	 * Save PO Supplier
	 *
	 * @package ATUM Purchase Orders
	 *
	 * @since 1.7.6
	 */
	public function save_purchase_order_supplier() {

		check_ajax_referer( 'atum-order-item', 'security' );

		$atum_order_id = absint( $_POST['atum_order_id'] );
		$supplier      = absint( $_POST['supplier'] );

		/**
		 * Variable definition
		 *
		 * @var PurchaseOrder $atum_order
		 */
		$atum_order = Helpers::get_atum_order_model( $atum_order_id, FALSE );

		if ( PurchaseOrders::POST_TYPE !== $atum_order->get_post_type() ) {
			wp_send_json_error();
		}

		// Set the Supplier.
		$atum_order->set_supplier( $supplier );
		$atum_order->save_meta();

		wp_send_json_success( [
			'atum_order_id' => $atum_order_id,
			'supplier'      => $supplier,
		] );

	}

	/**
	 * Save PO Multiple Suppliers
	 *
	 * @package ATUM Purchase Orders
	 *
	 * @since 1.7.6
	 */
	public function save_purchase_order_multiple_suppliers() {

		check_ajax_referer( 'atum-order-item', 'security' );

		$atum_order_id = absint( $_POST['atum_order_id'] );
		$multiple      = stripslashes( $_POST['multiple'] );

		/**
		 * Variable definition
		 *
		 * @var PurchaseOrder $atum_order
		 */
		$atum_order = Helpers::get_atum_order_model( $atum_order_id, FALSE );

		if ( PurchaseOrders::POST_TYPE !== $atum_order->get_post_type() ) {
			wp_send_json_error();
		}

		$atum_order->set_multiple_suppliers( $multiple );
		$atum_order->save_meta();

		wp_send_json_success( [
			'atum_order_id' => $atum_order_id,
			'multiple'      => $multiple,
		] );

	}

	/**
	 * Change the ATUM's control stock status for all products from Tools section
	 *
	 * @package    Settings
	 * @subpackage Tools
	 *
	 * @since 1.4.5
	 */
	public function update_calc_props() {

		check_ajax_referer( 'atum-script-runner-nonce', 'security' );

		if ( empty( $_POST['option'] ) ) {
			wp_send_json_error( __( 'Please enter the number of products you want to process per AJAX call', ATUM_TEXT_DOMAIN ) );
		}

		global $wpdb;
		$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		$total  = $wpdb->get_var( "SELECT COUNT(*) FROM $atum_product_data_table;" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$step   = isset( $_POST['option'] ) ? (int) $_POST['option'] : 400;
		$offset = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;

		$products = $wpdb->get_col( $wpdb->prepare( "SELECT product_id FROM $atum_product_data_table LIMIT %d, %d", $offset, $step ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( $products as $product_id ) {
			AtumCalculatedProps::defer_update_atum_sales_calc_props( $product_id );
		}

		if ( $offset + $step >= $total ) {
			$return = [
				'finished' => TRUE,
				'limit'    => $total,
				'total'    => $total,
			];
		}
		else {
			$return = [
				'finished' => FALSE,
				'limit'    => $offset + $step,
				'total'    => $total,
			];
		}
		
		wp_send_json_success( $return );

	}

	/**
	 * Remove the ATUM's transients from Tools section
	 *
	 * @package    Settings
	 * @subpackage Tools
	 *
	 * @since 1.9.1
	 */
	public function clear_out_atum_transients() {

		check_ajax_referer( 'atum-script-runner-nonce', 'security' );

		AtumCache::delete_transients();

		do_action( 'atum/ajax/tool_clear_out_atum_transients' );
		wp_send_json_success( __( 'All your saved temporary data were cleared successfully.', ATUM_TEXT_DOMAIN ) );

	}

	/**
	 * Create a new supplier from the "Create Supplier" modal.
	 *
	 * @package Stock Central
	 *
	 * @since 1.9.6
	 */
	public function create_supplier() {

		check_ajax_referer( 'create-supplier-nonce', 'security' );

		if ( empty( $_POST['supplier_data'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_TEXT_DOMAIN ) );
		}

		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) ) {
			wp_send_json_error( __( 'Purchase Orders module isn\'t active.', ATUM_TEXT_DOMAIN ) );
		}

		if ( ! AtumCapabilities::current_user_can( 'create_suppliers' ) ) {
			wp_send_json_error( __( 'You don\'t have enough privileges.', ATUM_TEXT_DOMAIN ) );
		}

		parse_str( $_POST['supplier_data'], $posted_data );

		if ( empty( $posted_data['name'] ) ) {
			wp_send_json_error( __( 'The Supplier name is required', ATUM_TEXT_DOMAIN ) );
		}

		$supplier = new Supplier();
		$supplier->set_data( $posted_data );
		$supplier_id = $supplier->save();

		if ( ! $supplier_id ) {
			wp_send_json_error( __( 'Something failed when creating the supplier.', ATUM_TEXT_DOMAIN ) );
		}

		wp_send_json_success( [
			'message'       => __( 'Supplier Created', ATUM_TEXT_DOMAIN ),
			'text_link'     => __( 'Complete supplier details', ATUM_TEXT_DOMAIN ),
			'supplier_link' => get_edit_post_link( $supplier_id ),
			'supplier_id'   => $supplier_id,
			'supplier_name' => $supplier->name,
		] );

	}

	/**
	 * Get any help guide steps from a JSON file
	 *
	 * @package ATUM Help Guides
	 *
	 * @since 1.9.10
	 */
	public function get_help_guide_steps() {

		check_ajax_referer( 'help-guide-nonce', 'security' );

		if ( empty( $_POST['guide'] ) ) {
			wp_send_json_error( __( 'The guide name is required', ATUM_TEXT_DOMAIN ) );
		}

		if ( ! empty( $_POST['path'] ) ) {
			$guide_path = esc_attr( $_POST['path'] );
		}
		else {
			$guide_path = apply_filters( 'atum/ajax/get_help_guide_steps', ATUM_PATH . 'help-guides' );
		}

		$help_guide  = new AtumHelpGuide( $guide_path );
		$guide_steps = $help_guide->get_guide_steps( esc_attr( $_POST['guide'] ) );

		if ( empty( $guide_steps ) ) {
			wp_send_json_error( __( 'Guide not found', ATUM_TEXT_DOMAIN ) );
		}

		wp_send_json_success( $guide_steps );

	}

	/**
	 * Save the closed state for any auto-guide on any screen
	 *
	 * @package ATUM Help Guides
	 *
	 * @since 1.9.11
	 */
	public function save_closed_auto_guide() {

		check_ajax_referer( 'help-guide-nonce', 'security' );

		if ( ! empty( $_POST['screen'] ) ) {
			AtumHelpGuide::save_closed_auto_guide( get_current_user_id(), esc_attr( $_POST['screen'] ) );
		}

		wp_die();

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
