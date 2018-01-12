<?php
/**
 * @package        Atum
 * @subpackage     Inc
 * @author         Salva Machí and Jose Piera - https://sispixels.com
 * @copyright      ©2017 Stock Management Labs™
 *
 * @since          0.0.1
 *
 * Ajax callbacks
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Addons\Addons;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumException;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\InboundStock\InboundStock;
use Atum\InboundStock\Inc\ListTable as InboundStockListTable;
use Atum\Settings\Settings;
use Atum\StockCentral\Inc\ListTable as StockCentralListTable;
use Atum\InventoryLogs\Models\Log;
use Atum\StockCentral\StockCentral;
use Atum\Suppliers\Suppliers;


final class Ajax {
	
	/**
	 * The singleton instance holder
	 * @var Ajax
	 */
	private static $instance;
	
	private function __construct() {

		// Ajax callback for Stock Central ListTable
		add_action( 'wp_ajax_atum_fetch_stock_central_list', array( $this, 'fetch_stock_central_list' ) );

		// Ajax callback for Inbound Stock ListTable
		add_action( 'wp_ajax_atum_fetch_inbound_stock_list', array( $this, 'fetch_inbound_stock_list' ) );
		
		// Ajax callback for Management Stock notice
		add_action( 'wp_ajax_atum_manage_stock_notice', array( $this, 'manage_stock_notice' ) );
		
		// Welcome notice dismissal
		add_action( 'wp_ajax_atum_welcome_notice', array( $this, 'welcome_notice' ) );

		// Save the rate link click on the ATUM pages footer
		add_action( 'wp_ajax_atum_rated', array($this, 'rated') );

		// Set the edited meta data for items on ListTable components
		add_action( 'wp_ajax_atum_update_data', array( $this, 'update_list_data' ) );

		// Manage addon licenses
		add_action( 'wp_ajax_atum_validate_license', array($this, 'validate_license') );
		add_action( 'wp_ajax_atum_activate_license', array($this, 'activate_license') );
		add_action( 'wp_ajax_atum_deactivate_license', array($this, 'deactivate_license') );
		add_action( 'wp_ajax_atum_install_addon', array($this, 'install_addon') );

		// Search for products from enhanced selects
		add_action( 'wp_ajax_atum_json_search_products', array( $this, 'search_products' ) );

		// Search for WooCommerce orders from enhanced selects
		add_action( 'wp_ajax_atum_json_search_orders', array( $this, 'search_wc_orders' ) );

		// Search for Suppliers from enhanced selects
		add_action( 'wp_ajax_atum_json_search_suppliers', array( $this, 'search_suppliers' ) );

		// Add and delete ATUM Order notes
		add_action( 'wp_ajax_atum_order_add_note', array( $this, 'add_atum_order_note' ) );
		add_action( 'wp_ajax_atum_order_delete_note', array( $this, 'delete_atum_order_note' ) );

		// ATUM Order items' meta box actions
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

		// Update the ATUM Order status
		add_action( 'wp_ajax_atum_order_mark_status', array( $this, 'mark_atum_order_status' ) );

		// Import WC order items to an Inventory Log
		add_action( 'wp_ajax_atum_order_import_items', array( $this, 'import_wc_order_items' ) );

	}
	
	/**
	 * Loads the Stock Central ListTable class and calls ajax_response method
	 *
	 * @since 0.0.1
	 */
	public function fetch_stock_central_list() {
		
		check_ajax_referer( 'atum-list-table-nonce', 'token' );

		$args = array(
			'per_page' => ( ! empty( $_REQUEST['per_page'] ) ) ? absint( $_REQUEST['per_page'] ) : Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE ),
			'screen'   => 'toplevel_page_' . StockCentral::UI_SLUG
		);
		
		do_action( 'atum/ajax/stock_central_list/before_fetch_list' );
		
		$list = new StockCentralListTable( $args );
		$list->ajax_response();
		
	}

	/**
	 * Loads the Inbound Stock ListTable class and calls ajax_response method
	 *
	 * @since 1.3.0
	 */
	public function fetch_inbound_stock_list() {

		check_ajax_referer( 'atum-list-table-nonce', 'token' );

		$args = array(
			'per_page' => ( ! empty( $_REQUEST['per_page'] ) ) ? absint( $_REQUEST['per_page'] ) : Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE ),
			'screen'   => 'toplevel_page_' . InboundStock::UI_SLUG
		);

		do_action( 'atum/ajax/inbound_stock/before_fetch_list' );

		$list = new InboundStockListTable( $args );
		$list->ajax_response();

	}
	
	/**
	 * Handle the ajax requests sent by the Atum's "Manage Stock" notice
	 *
	 * @since 0.1.0
	 */
	public function manage_stock_notice() {
		
		check_ajax_referer( ATUM_PREFIX . 'manage-stock-notice', 'token' );
		
		$action = ( ! empty($_POST['data']) ) ? $_POST['data'] : '';
		
		// Enable stock management
		if ($action == 'manage') {
			Helpers::update_option('manage_stock', 'yes');
		}
		// Dismiss the notice permanently
		elseif ( $action == 'dismiss') {
			Helpers::dismiss_notice('manage_stock');
		}
		
		wp_die();
		
	}
	
	/**
	 * Handle the ajax requests sent by the Atum's "Welcome" notice
	 *
	 * @since 1.1.1
	 */
	public function welcome_notice() {
		check_ajax_referer( 'dismiss-welcome-notice', 'token' );
		Helpers::dismiss_notice('welcome');
		wp_die();
	}

	/**
	 * Triggered when clicking the rating footer
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
	 * @since 1.1.2
	 */
	public function update_list_data () {

		check_ajax_referer( 'atum-list-table-nonce', 'token' );

		if ( empty($_POST['data']) ) {
			wp_send_json_error( __('Error saving the table data.', ATUM_TEXT_DOMAIN) );
		}

		$data = json_decode( stripslashes($_POST['data']), TRUE );

		if ( empty($data) ) {
			wp_send_json_error( __('Error saving the table data.', ATUM_TEXT_DOMAIN) );
		}

		foreach ($data as $product_id => &$product_meta) {
		
			Helpers::update_product_meta( $product_id, $product_meta);
			Helpers::maybe_synchronize_translations_wpml( $product_id, $product_meta );

		}

		// If the first edit notice was already shown, save it as user meta
		if ( ! empty($_POST['first_edit_key']) ) {
			update_user_meta( get_current_user_id(), esc_attr($_POST['first_edit_key']), 1 );
		}

		wp_send_json_success( __('Data saved.', ATUM_TEXT_DOMAIN) );

	}

	/**
	 * Validate an addon license key through API
	 *
	 * @since 1.2.0
	 */
	public function validate_license () {

		$this->check_license_post_data();

		$addon_name = esc_attr( $_POST['addon'] );
		$key = esc_attr( $_POST['key'] );

		if (!$addon_name || !$key) {
			wp_send_json_error( __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN ) );
		}

		$error_message = __('This license is not valid.', ATUM_TEXT_DOMAIN);

		// Validate the license through API
		$response = Addons::check_license($addon_name, $key);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( __('ATUM API error', ATUM_TEXT_DOMAIN) );
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		switch ( $license_data->license ) {

			case 'valid':

				// Save the valid license
				Addons::update_key( $addon_name, array(
					'key'    => $key,
					'status' => 'valid'
				) );

				wp_send_json_success( __('Your add-on license was saved.', ATUM_TEXT_DOMAIN) );
		        break;

			case 'inactive':
			case 'site_inactive':

				Addons::update_key( $addon_name, array(
					'key'    => $key,
					'status' => 'inactive'
				) );

				if ($license_data->activations_left < 1) {
					wp_send_json_error( __("You've reached your license activation limit for this add-on.<br>Please contact the Stock Management Labs support team.", ATUM_TEXT_DOMAIN) );
				}

				$licenses_after_activation = $license_data->activations_left - 1;

				wp_send_json( array(
					'success' => 'activate',
					'data' => sprintf(
						_n(
							'Your license is valid.<br>After the activation you will have %s remaining license.<br>Please, click the button to activate.',
							'Your license is valid.<br>After the activation you will have %s remaining licenses.<br>Please, click the button to activate.',
							$licenses_after_activation,
							ATUM_TEXT_DOMAIN
						),
						$licenses_after_activation
					)
				) );
				break;

			case 'expired':

				$error_message = sprintf(
					__( 'Your license key expired on %s.', ATUM_TEXT_DOMAIN ),
					date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
				);

				break;

			case 'disabled':

				$error_message = __('This license has been disabled', ATUM_TEXT_DOMAIN);
				break;

		}

		Addons::update_key( $addon_name, array(
			'key'    => $key,
			'status' => 'invalid'
		) );

		wp_send_json_error($error_message);

	}

	/**
	 * First check before validating|activating|deactivating an addon license
	 *
	 * @since 1.2.0
	 */
	private function check_license_post_data () {
		check_ajax_referer(ATUM_PREFIX . 'manage_license', 'token');

		if ( empty( $_POST['addon'] ) ) {
			wp_send_json_error( __('No addon name provided', ATUM_TEXT_DOMAIN) );
		}

		if ( empty( $_POST['key'] ) ) {
			wp_send_json_error( __('Please enter a valid addon license key', ATUM_TEXT_DOMAIN) );
		}
	}

	/**
	 * Activate an addon license key through API
	 *
	 * @since 1.2.0
	 */
	public function activate_license () {

		$this->check_license_post_data();

		$addon_name = esc_attr( $_POST['addon'] );
		$key = esc_attr( $_POST['key'] );
		$default_error = __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN );

		if (!$addon_name || !$key) {
			wp_send_json_error($default_error);
		}

		$response = Addons::activate_license($addon_name, $key);

		// Make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			$message = ( is_wp_error( $response ) ) ? $response->get_error_message() : $default_error;

		}
		else {

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {

				switch( $license_data->error ) {

					case 'expired':

						$message = sprintf(
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

						$message = sprintf( __( 'This appears to be an invalid license key for %s.', ATUM_TEXT_DOMAIN ), $addon_name );
						break;

					case 'no_activations_left':

						$message = __( 'Your license key has reached its activation limit.', ATUM_TEXT_DOMAIN );
						break;

					default :

						$message = $default_error;
						break;
				}

			}

		}

		if ( ! empty( $message ) ) {
			wp_send_json_error($message);
		}

		// Update the key in database
		Addons::update_key( $addon_name, array(
			'key'    => $key,
			'status' => $license_data->license
		) );

		if ($license_data->license == 'valid') {
			wp_send_json_success( __('Your license has been activated.', ATUM_TEXT_DOMAIN) );
		}

		wp_send_json_error($default_error);

	}

	/**
	 * Deactivate an addon license key through API
	 *
	 * @since 1.2.0
	 */
	public function deactivate_license () {

		$this->check_license_post_data();

		$addon_name = esc_attr( $_POST['addon'] );
		$key = esc_attr( $_POST['key'] );
		$default_error = __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN );

		if (!$addon_name || !$key) {
			wp_send_json_error($default_error);
		}

		$response = Addons::deactivate_license($addon_name, $key);

		// Make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = ( is_wp_error( $response ) ) ? $response->get_error_message() : $default_error;
			wp_send_json_error($message);
		}

		// Decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed" or "limit_reached"
		if( $license_data->license == 'deactivated' ) {

			// Update the key status
			Addons::update_key( $addon_name, array(
				'key'    => $key,
				'status' => 'inactive'
			) );

			wp_send_json_success( __('Your license has been deactivated.', ATUM_TEXT_DOMAIN) );

		}
		elseif ( $license_data->license == 'limit_reached' ) {

			wp_send_json_error( sprintf(
				__("You've reached the limit of allowed deactivations for this license. Please %sopen a support ticket%s to request the deactivation.", ATUM_TEXT_DOMAIN),
				'<a href="https://stockmanagementlabs.ticksy.com/" target="_blank">',
				'</a>'
			) );

		}

		wp_send_json_error($default_error);

	}

	/**
	 * Install an addon from the addons page
	 *
	 * @since 1.2.0
	 */
	public function install_addon () {

		$this->check_license_post_data();

		$addon_name = esc_attr( $_POST['addon'] );
		$addon_slug = esc_attr( $_POST['slug'] );
		$key = esc_attr( $_POST['key'] );
		$default_error =  __( 'An error occurred, please try again later.', ATUM_TEXT_DOMAIN );

		if (!$addon_name || !$addon_slug || !$key) {
			wp_send_json_error($default_error);
		}

		$response = Addons::get_version($addon_name, $key, '0.0');

		// Make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = ( is_wp_error( $response ) ) ? $response->get_error_message() : $default_error;
			wp_send_json_error($message);
		}

		// Decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ($license_data->download_link) {
			$result = Addons::install_addon($addon_name, $addon_slug, $license_data->download_link);
			wp_send_json($result);
		}

		wp_send_json_error($default_error);

	}

	/**
	 * Seach for products from enhanced selects
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
		$post_types    = apply_filters( 'atum/ajax/search_products/searched_post_types', array( 'product', 'product_variation' ) );
		$post_statuses = current_user_can( 'edit_private_products' ) ? array( 'private', 'publish' ) : array( 'publish' );
		$type_join     = $type_where = $meta_join = $meta_where = array();
		$join_counter  = 1;

		// Search by meta keys
		$searched_metas = array_map( 'wc_clean', apply_filters( 'atum/ajax/search_products/searched_meta_keys', array( '_sku', ) ) );

		if ( AtumCapabilities::current_user_can('read_supplier') ) {
			$searched_metas[] = '_supplier_sku';
		}

		foreach ($searched_metas as $searched_meta) {
			$meta_join[]  = "LEFT JOIN {$wpdb->postmeta} pm{$join_counter} ON posts.ID = pm{$join_counter}.post_id";
			$meta_where[] = $wpdb->prepare("OR ( pm{$join_counter}.meta_key = %s AND pm{$join_counter}.meta_value LIKE %s )", $searched_meta, $like_term);
			$join_counter++;
		}

		// Allow searching for other product types if needed
		$searched_types = array_map( 'wc_clean', apply_filters( 'atum/ajax/search_products/searched_product_types', array() ) );
		if ( ! empty($searched_types) ) {

			foreach ($searched_types as $searched_type) {
				$type_join[]  = "LEFT JOIN {$wpdb->postmeta} pm{$join_counter} ON posts.ID = pm{$join_counter}.post_id";
				$type_where[] = "AND ( pm{$join_counter}.meta_key = '{$searched_type}' AND pm{$join_counter}.meta_value = 'yes' )";
				$join_counter++;
			}

		}

		$query = $wpdb->prepare( "
			SELECT DISTINCT posts.ID FROM {$wpdb->posts} posts
			" . implode("\n", $meta_join) . "
			" . implode("\n", $type_join) . "
			WHERE (
				posts.post_title LIKE %s
				OR posts.post_content LIKE %s
				" . implode("\n", $meta_where) . "
			)
			AND posts.post_type IN ('" . implode( "','", $post_types ) . "')
			AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "')
			" . implode("\n", $type_where) . "
			ORDER BY posts.post_parent ASC, posts.post_title ASC
			",
			$like_term,
			$like_term
		);
		$product_ids = $wpdb->get_col($query);

		if ( is_numeric( $term ) ) {

			$post_id   = absint( $term );
			$post_type = get_post_type( $post_id );

			if ( 'product_variation' == $post_type ) {
				$product_ids[] = $post_id;
			}
			elseif ( 'product' == $post_type ) {
				$product_ids[] = $post_id;
			}

			$product_ids[] = wp_get_post_parent_id( $post_id );

		}

		$ids = wp_parse_id_list( $product_ids );

		if ( ! empty( $_GET['exclude'] ) ) {
			$ids = array_diff( $ids, (array) $_GET['exclude'] );
		}

		$included = ( ! empty( $_GET['include'] ) ) ? array_map( 'absint', (array) $_GET['include'] ) : array();
		$url      = parse_url( wp_get_referer() );
		parse_str( $url['query'], $url_query );

		if ( ! empty($url_query['post']) ) {

			$po = Helpers::get_atum_order_model( absint( $url_query['post'] ) );

			// The Purchase Orders only should allow products from the current PO's supplier
			if ( is_a($po, '\Atum\PurchaseOrders\Models\PurchaseOrder') ) {

				$supplier_products = apply_filters( 'atum/ajax/search_products/included_search_products', Suppliers::get_supplier_products( $po->get_supplier('id'), 'ids' ) );

				// If the PO supplier has no linked products, it must return an empty array
				if ( empty($supplier_products) ) {
					$ids = $included = array();
				}
				else {
					$included = array_merge($included, $supplier_products);
				}

			}

		}

		if ( ! empty($included) ) {
			$ids = array_intersect( $ids, $included );
		}

		if ( ! empty( $_GET['limit'] ) ) {
			$ids = array_slice( $ids, 0, absint( $_GET['limit'] ) );
		}

		$product_objects = array_filter( array_map( 'wc_get_product', $ids ), 'wc_products_array_filter_editable' );
		$products        = array();

		foreach ( $product_objects as $product_object ) {
			$products[ $product_object->get_id() ] = rawurldecode( $product_object->get_formatted_name() );
		}

		wp_send_json( apply_filters( 'atum/ajax/search_products/json_search_found_products', $products ) );

	}

	/**
	 * Seach for WooCommerce orders from enhanced selects
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

		// Get all the orders with IDs starting with the provided number
		global $wpdb;
		$max_results = absint( apply_filters('atum/ajax/search_wc_orders/max_results', 10) );

		$query = $wpdb->prepare(
			"SELECT DISTINCT ID from {$wpdb->posts} WHERE post_type = 'shop_order' 
			AND post_status IN ('" . implode( "','", array_keys( wc_get_order_statuses() ) ) . "') 
			AND ID LIKE %s LIMIT %d",
			"$order_id%",
			$max_results
		);

		$order_ids = $wpdb->get_col($query);

		if ( empty( $order_ids ) ) {
			wp_die();
		}

		$order_results = array();
		foreach ($order_ids as $order_id) {
			$order_results[$order_id] = __('Order #', ATUM_TEXT_DOMAIN) . $order_id;
		}

		wp_send_json( $order_results );

	}

	/**
	 * Seach for Suppliers from enhanced selects
	 *
	 * @since 1.2.9
	 */
	public function search_suppliers() {

		check_ajax_referer( 'search-products', 'security' );

		global $wpdb;
		ob_start();

		if ( is_numeric( $_GET['term'] ) ) {

			$supplier_id = absint( $_GET['term'] );
			$where = "ID LIKE $supplier_id";

		}
		elseif ( ! empty( $_GET['term'] ) ) {

			$supplier_name = $wpdb->esc_like( $_GET['term'] );
			$where = "post_title LIKE '%%{$supplier_name}%%'";
		}
		else {
			wp_die();
		}

		// Get all the orders with IDs starting with the provided number
		$max_results   = absint( apply_filters('atum/ajax/search_suppliers/max_results', 10) );
		$post_statuses = AtumCapabilities::current_user_can('edit_private_suppliers' ) ? array( 'private', 'publish' ) : array( 'publish' );

		$query = $wpdb->prepare(
			"SELECT DISTINCT ID, post_title from $wpdb->posts 
			 WHERE post_type = %s AND $where
			 AND post_status IN ('" . implode( "','", $post_statuses ) . "')
			 LIMIT %d",
			Suppliers::POST_TYPE,
			$max_results
		);

		$suppliers = $wpdb->get_results($query);

		if ( empty( $suppliers ) ) {
			wp_die();
		}

		$supplier_results = array();
		foreach ($suppliers as $supplier) {
			$supplier_results[$supplier->ID] = $supplier->post_title;
		}

		wp_send_json( $supplier_results );

	}

	/**
	 * Add a note to an ATUM Order
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

			$atum_order = Helpers::get_atum_order_model($post_id);

			if ( ! is_wp_error($atum_order) ) {

				$comment_id = $atum_order->add_note( $note );

				?>
				<li rel="<?php echo esc_attr( $comment_id ) ?>" class="note">
					<div class="note_content">
						<?php echo wpautop( wptexturize( $note ) ) ?>
					</div>

					<p class="meta">
						<a href="#" class="delete_note"><?php _e( 'Delete note', ATUM_TEXT_DOMAIN ) ?></a>
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
	 * @since 1.2.4
	 */
	public function load_atum_order_items() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$atum_order_id = absint( $_POST['atum_order_id'] );
		$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

		if ( is_wp_error($atum_order) ) {
			wp_die( -1 );
		}

		Helpers::load_view( 'meta-boxes/atum-order/items', compact('atum_order') );

		wp_die();

	}

	/**
	 * Add ATUM Order item
	 *
	 * @since 1.2.4
	 */
	public function add_atum_order_item() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['atum_order_id'] )  ) {
			wp_die( -1 );
		}

		try {

			$atum_order_id = absint( $_POST['atum_order_id'] );
			$post_type     = get_post_type( $atum_order_id );
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error($atum_order) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			if ( ! $atum_order ) {
				$message = ($post_type == ATUM_PREFIX . 'inventory_log') ? __( 'Invalid log', ATUM_TEXT_DOMAIN ) : __( 'Invalid purchase order', ATUM_TEXT_DOMAIN );
				throw new AtumException( 'invalid_atum_order', $message );
			}

			$items_to_add = wp_parse_id_list( is_array( $_POST['item_to_add'] ) ? $_POST['item_to_add'] : array( $_POST['item_to_add'] ) );
			$html         = '';

			foreach ( $items_to_add as $item_to_add ) {

				if ( ! in_array( get_post_type( $item_to_add ), array( 'product', 'product_variation' ) ) ) {
					continue;
				}

				// Add the product to the ATUM Order
				$item    = $atum_order->add_product( wc_get_product( $item_to_add ) );
				$item_id = $item->get_id();
				$class   = 'new_row';

				// Load template
				$html .= Helpers::load_view_to_string( 'meta-boxes/atum-order/item', compact('atum_order', 'item', 'item_id', 'class') );

			}

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

	}

	/**
	 * Add ATUM Order fee
	 *
	 * @since 1.2.4
	 */
	public function add_atum_order_fee() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['atum_order_id'] ) ) {
			wp_die( -1 );
		}

		try {

			$atum_order_id = absint( $_POST['atum_order_id'] );
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error($atum_order) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			// Add a fee line item
			$item    = $atum_order->add_fee();
			$item_id = $item->get_id();

			// Load template
			$html = Helpers::load_view_to_string( 'meta-boxes/atum-order/item-fee', compact('atum_order', 'item', 'item_id') );

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Add ATUM Order shipping cost
	 *
	 * @since 1.2.4
	 */
	public function add_atum_order_shipping() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['atum_order_id'] )  ) {
			wp_die( -1 );
		}

		try {

			$atum_order_id = absint( $_POST['atum_order_id'] );
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error($atum_order) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			$shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();

			// Add new shipping cost line item
			$item    = $atum_order->add_shipping_cost();
			$item_id = $item->get_id();

			// Load template
			$html = Helpers::load_view_to_string( 'meta-boxes/atum-order/item-shipping', compact('atum_order', 'item', 'item_id', 'shipping_methods') );

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

	}

	/**
	 * Add ATUM Order tax
	 *
	 * @since 1.2.4
	 */
	public function add_atum_order_tax() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['atum_order_id'] )  ) {
			wp_die( -1 );
		}

		try {

			$atum_order_id = absint( $_POST['atum_order_id'] );
			$rate_id       = absint( $_POST['rate_id'] );
			$atum_order    = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error($atum_order) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			// Add new tax
			$atum_order->add_tax( array( 'rate_id' => $rate_id) );

			// Load template
			$html = Helpers::load_view_to_string( 'meta-boxes/atum-order/items', compact('atum_order') );

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

	}

	/**
	 * Remove an ATUM Order item
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

		$atum_order_item_ids = array_unique( array_filter( array_map('absint', $atum_order_item_ids) ) );

		if ( ! empty($atum_order_item_ids) ) {

			$atum_order = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error($atum_order) ) {
				wp_die( -1 );
			}

			foreach ( $atum_order_item_ids as $id ) {
				$atum_order->remove_item( absint($id) );
			}

			$atum_order->save_items();
		}

		wp_die();

	}

	/**
	 * Remove an ATUM Order tax
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

		if ( is_wp_error($atum_order) ) {
			wp_die( -1 );
		}

		$atum_order->remove_item($rate_id);
		$atum_order->save_items();

		// Load template
		Helpers::load_view( 'meta-boxes/atum-order/items', compact('atum_order') );

		wp_die();

	}

	/**
	 * Calc ATUM Order line taxes
	 *
	 * @since 1.2.4
	 */
	public function calc_atum_order_line_taxes() {

		check_ajax_referer( 'calc-totals', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$atum_order_id = absint( $_POST['atum_order_id'] );

		/*$calculate_tax_args = array(
			'country'  => strtoupper( wc_clean( $_POST['country'] ) ),
			'state'    => strtoupper( wc_clean( $_POST['state'] ) ),
			'postcode' => strtoupper( wc_clean( $_POST['postcode'] ) ),
			'city'     => strtoupper( wc_clean( $_POST['city'] ) ),
		);*/

		// Parse the jQuery serialized items
		$items = array();
		parse_str( $_POST['items'], $items );

		$atum_order = Helpers::get_atum_order_model( $atum_order_id );

		if ( is_wp_error($atum_order) ) {
			wp_die( -1 );
		}

		// Grab the order and recalc taxes
		$atum_order->save_order_items($items);
		$atum_order->calculate_taxes();
		$atum_order->calculate_totals( FALSE );

		// Load template
		Helpers::load_view( 'meta-boxes/atum-order/items', compact('atum_order') );

		wp_die();

	}

	/**
	 * Save ATUM Order items
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

			if ( is_wp_error($atum_order) ) {
				wp_die( -1 );
			}

			// Parse the jQuery serialized items
			$items = array();
			parse_str( $_POST['items'], $items );

			// Save order items
			$atum_order->save_order_items($items);

			// Return HTML items
			Helpers::load_view( 'meta-boxes/atum-order/items', compact('atum_order') );

		}

		wp_die();

	}

	/**
	 * Increase the ATUM order products' stock by their quantity amount
	 *
	 * @since 1.3.0
	 */
	public function increase_atum_order_items_stock () {
		$this->bulk_change_atum_order_items_stock('increase');
	}

	/**
	 * Decrease the ATUM order products' stock by their quantity amount
	 *
	 * @since 1.3.0
	 */
	public function decrease_atum_order_items_stock () {
		$this->bulk_change_atum_order_items_stock('decrease');
	}

	/**
	 * Change the ATUM order products' stock by their quantity amount
	 *
	 * @since 1.3.0
	 */
	private function bulk_change_atum_order_items_stock ($action) {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( __('You are not allowed to do this', ATUM_TEXT_DOMAIN) );
		}

		if ( ! isset($_POST['atum_order_id'], $_POST['atum_order_item_ids'], $_POST['quantities']) ) {
			wp_send_json_error( __('Invalid data provided', ATUM_TEXT_DOMAIN) );
		}

		$atum_order_id       = absint( $_POST['atum_order_id'] );
		$atum_order_item_ids = array_map( 'absint', $_POST['atum_order_item_ids'] );
		$quantities          = array_map( 'wc_stock_amount', $_POST['quantities'] );

		$atum_order       = Helpers::get_atum_order_model( $atum_order_id );
		$atum_order_items = $atum_order->get_items();
		$return           = array();

		if ( $atum_order && ! empty( $atum_order_items ) && sizeof( $atum_order_item_ids ) > 0 ) {

			foreach ( $atum_order_items as $item_id => $atum_order_item ) {

				// Only increase the stock for selected items
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
						__( 'Item %1$s stock %2$s from %3$s to %4$s.', ATUM_TEXT_DOMAIN ),
						$item_name,
						($action == 'increase') ? __('increased', ATUM_TEXT_DOMAIN) : __('decreased', ATUM_TEXT_DOMAIN),
						$old_stock,
						$new_quantity
					);
					$return[]     = $note;

					$atum_order->add_note( $note );

				}
			}

			do_action( "atum/ajax/{$action}_atum_order_stock", $atum_order );

			if ( empty( $return ) ) {

				wp_send_json_error( sprintf(
					__( 'No products had their stock %s - they may not have stock management enabled.', ATUM_TEXT_DOMAIN ),
					($action == 'increase') ? __('increased', ATUM_TEXT_DOMAIN) : __('decreased', ATUM_TEXT_DOMAIN)  )
				);

			}

		}

		wp_send_json_success();

	}

	/**
	 * Change the purchase price of a product within a PO
	 *
	 * @since 1.3.0
	 */
	public function change_atum_order_item_purchase_price() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( __('You are not allowed to do this', ATUM_TEXT_DOMAIN) );
		}

		if ( empty($_POST['atum_order_id']) || empty($_POST['atum_order_item_id']) || empty($_POST['purchase_price']) ) {
			wp_send_json_error( __('Invalid data provided', ATUM_TEXT_DOMAIN) );
		}

		$atum_order = Helpers::get_atum_order_model( absint($_POST['atum_order_id']) );
		$atum_order_item = $atum_order->get_item( absint($_POST['atum_order_item_id']) );

		$product_id = ( $atum_order_item->get_variation_id() ) ? $atum_order_item->get_variation_id() : $atum_order_item->get_product_id();
		$product = wc_get_product($product_id);

		if ( ! is_a($product, '\WC_Product') ) {
			wp_send_json_error( __('Product not found', ATUM_TEXT_DOMAIN) );
		}

		update_post_meta( $product_id, '_purchase_price', floatval( $_POST['purchase_price'] ) );

		wp_send_json_success();

	}

	/**
	 * Import the WC order items to the current ATUM Order after linking an order
	 *
	 * @since 1.2.4
	 */
	public function import_wc_order_items() {

		check_ajax_referer( 'import-order-items', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		if ( isset( $_POST['atum_order_id'], $_POST['wc_order_id'] ) ) {

			$atum_order_id = absint( $_POST['atum_order_id'] );
			$wc_order_id   = absint( $_POST['wc_order_id'] );
			$wc_order      = wc_get_order( $wc_order_id );

			if ( ! empty($wc_order) ) {

				$items = $wc_order->get_items( array( 'line_item', 'fee', 'shipping', 'tax' ) );

				if ( ! empty($items) ) {

					// *** NOTE: FOR NOW THIS IS ONLY USED ON LOGS, IF NEEDS TO BE COMPATIBLE WITH OTHER
					// ATUM ORDERS IN THE FUTURE, THIS WILL NEED REFACTORY ***
					$atum_order = new Log( $atum_order_id );

					try {

						// The log only can have one tax applied, so check if already has one
						$current_tax = $atum_order->get_items('tax');

						foreach ($items as $item) {

							if ( is_a($item, '\WC_Order_Item_Product') ) {
								$log_item = $atum_order->add_product( wc_get_product( $item->get_product_id() ), $item->get_quantity() );
							}
							elseif ( is_a($item, '\WC_Order_Item_Fee') ) {
								$log_item = $atum_order->add_fee($item);
							}
							elseif ( is_a($item, '\WC_Order_Item_Shipping') ) {
								$log_item = $atum_order->add_shipping_cost($item);
							}
							elseif ( empty($current_tax) && is_a($item, '\WC_Order_Item_Tax') ) {
								$log_item = $atum_order->add_tax( array( 'rate_id' => $item->get_rate_id() ), $item );
							}

							// Add the order ID as item's custom meta
							$log_item->add_meta_data('_order_id', $wc_order_id, TRUE);
							$log_item->save_meta_data();

						}

						// Load template
						$html = Helpers::load_view_to_string( 'meta-boxes/atum-order/items', compact('atum_order') );

						wp_send_json_success( array( 'html' => $html ) );

					} catch ( Exception $e ) {
						wp_send_json_error( array( 'error' => $e->getMessage() ) );
					}

				}

			}

		}

		wp_die(-1);

	}

	/**
	 * Mark an ATUM Order with a status
	 * NOTE: This callback is not being triggered through an Ajax request, just a normal HTTP request
	 *
	 * @since 1.2.4
	 */
	public function mark_atum_order_status() {

		$atum_order_id = absint( $_GET['atum_order_id'] );
		$post_type     = get_post_type( $atum_order_id );

		if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'atum-order-mark-status' ) ) {

			$status     = sanitize_text_field( $_GET['status'] );
			$atum_order = Helpers::get_atum_order_model( $atum_order_id );

			if ( is_wp_error($atum_order) ) {
				wp_die( -1 );
			}

			if ( $atum_order && in_array($status, array_keys( AtumOrderPostType::get_statuses() ) ) ) {
				$atum_order->update_status( $status );
				do_action( 'atum/atum_orders/edit_status', $atum_order->get_id(), $status );
			}

		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( "edit.php?post_type=$post_type" ) );
		exit;

	}

	
	/****************************
	 * Instance methods
	 ****************************/
	public function __clone() {
		
		// cannot be cloned
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}
	
	public function __sleep() {
		
		// cannot be serialized
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
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