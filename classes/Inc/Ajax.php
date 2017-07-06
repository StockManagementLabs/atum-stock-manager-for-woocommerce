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
use Atum\Components\AtumException;
use Atum\InventoryLogs\InventoryLogs;
use Atum\Settings\Settings;
use Atum\StockCentral\Inc\ListTable;
use Atum\InventoryLogs\Models\Log;


final class Ajax {
	
	/**
	 * The singleton instance holder
	 * @var Ajax
	 */
	private static $instance;
	
	private function __construct() {
		
		// Ajax callback for ListTable components
		add_action( 'wp_ajax_atum_fetch_stock_central_list', array( $this, 'fetch_stock_central_list' ) );
		
		// Ajax callback for Management Stock notice
		add_action( 'wp_ajax_atum_manage_stock_notice', array( $this, 'manage_stock_notice' ) );
		
		// Welcome notice dismissal
		add_action( 'wp_ajax_atum_welcome_notice', array( $this, 'welcome_notice' ) );

		// Save the rate link click on the ATUM pages footer
		add_action( 'wp_ajax_atum_rated', array($this, 'rated') );

		// Set the meta for items on ListTable components
		add_action( 'wp_ajax_atum_update_meta', array( $this, 'update_item_meta' ) );

		// Manage addon licenses
		add_action( 'wp_ajax_atum_validate_license', array($this, 'validate_license') );
		add_action( 'wp_ajax_atum_activate_license', array($this, 'activate_license') );
		add_action( 'wp_ajax_atum_deactivate_license', array($this, 'deactivate_license') );
		add_action( 'wp_ajax_atum_install_addon', array($this, 'install_addon') );

		// Search for WooCommerce orders from Inventory Logs
		add_action( 'wp_ajax_atum_json_search_orders', array( $this, 'search_wc_orders' ) );

		// Add and delete Inventory Log's notes
		add_action( 'wp_ajax_atum_add_log_note', array( $this, 'add_inventory_log_note' ) );
		add_action( 'wp_ajax_atum_delete_log_note', array( $this, 'delete_inventory_log_note' ) );

		// Inventory Log items meta box actions
		add_action( 'wp_ajax_atum_load_log_items', array( $this, 'load_log_items' ) );
		add_action( 'wp_ajax_atum_add_log_item', array( $this, 'add_log_item' ) );
		add_action( 'wp_ajax_atum_add_log_fee', array( $this, 'add_log_fee' ) );
		add_action( 'wp_ajax_atum_add_log_shipping', array( $this, 'add_log_shipping' ) );
		add_action( 'wp_ajax_atum_add_log_tax', array( $this, 'add_log_tax' ) );
		add_action( 'wp_ajax_atum_remove_log_item', array( $this, 'remove_log_item' ) );
		add_action( 'wp_ajax_atum_remove_log_tax', array( $this, 'remove_log_tax' ) );
		add_action( 'wp_ajax_atum_calc_line_taxes', array( $this, 'calc_line_taxes' ) );
		add_action( 'wp_ajax_atum_save_log_items', array( $this, 'save_log_items' ) );

		// Update the Inventory Log status
		add_action( 'wp_ajax_atum_mark_log_status', array( $this, 'mark_log_status' ) );

		// Import order items to Log
		add_action( 'wp_ajax_atum_import_order_items', array( $this, 'import_order_items' ) );

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
			'screen'   => 'toplevel_page-' . Globals::ATUM_UI_SLUG
		);
		
		do_action( 'atum/ajax/stock_central_list/before_fetch_stock', $this );
		
		$list = new ListTable( $args );
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
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die(-1);
		}

		update_option( 'atum_admin_footer_text_rated', 1 );
		wp_die();
	}

	/**
	 * Update the meta values for any product within the ListTable components
	 *
	 * @since 1.1.2
	 */
	public function update_item_meta () {

		check_ajax_referer( 'atum-list-table-nonce', 'token' );

		if ( empty($_POST['item']) || ! isset($_POST['value']) || empty($_POST['meta']) ) {
			wp_send_json_error( __('Error updating the column value.', ATUM_TEXT_DOMAIN) );
		}

		$product_id = absint($_POST['item']);
		$product = wc_get_product($product_id);

		if ( !$product || ! is_a($product, '\WC_Product') ) {
			wp_send_json_error( __('No valid product.', ATUM_TEXT_DOMAIN) );
		}

		$meta = esc_attr($_POST['meta']);

		switch ( $meta ) {
			case 'stock':

				$stock = intval($_POST['value']);
				wc_update_product_stock($product_id, $stock);
		        break;

			case 'regular_price':
			case 'purchase_price':

				$price = wc_format_decimal($_POST['value']);
				update_post_meta( $product_id, '_' . $meta , $price );
				break;

			case 'sale_price':

				$sale_price = wc_format_decimal($_POST['value']);
				$regular_price = $product->get_regular_price();

				if ($regular_price < $sale_price) {
					wp_send_json_error( __('Please enter in a value lower than the regular price.', ATUM_TEXT_DOMAIN) );
				}

				update_post_meta( $product_id, '_sale_price', $sale_price );

				// Check for sale dates
				if ( ! empty($_POST['extraMeta']) ) {

					$extra_meta = array_map('wc_clean', $_POST['extraMeta']);
					$date_from = $extra_meta['_sale_price_dates_from'];
					$date_to = $extra_meta['_sale_price_dates_to'];

					update_post_meta( $product_id, '_sale_price_dates_from', $date_from ? strtotime( $date_from ) : '' );
					update_post_meta( $product_id, '_sale_price_dates_to', $date_to ? strtotime( $date_to ) : '' );

					if ( $date_to && ! $date_from ) {
						$date_from = date( 'Y-m-d' );
						update_post_meta( $product_id, '_sale_price_dates_from', strtotime( $date_from ) );
					}

					// Update price if on sale
					if ( '' !== $sale_price && $date_to && $date_from ) {
						update_post_meta( $product_id, '_price', wc_format_decimal( $sale_price ) );
					}
					elseif ( '' !== $sale_price && $date_from && strtotime( $date_from ) <= strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
						update_post_meta( $product_id, '_price', wc_format_decimal( $sale_price ) );
					}
					else {
						update_post_meta( $product_id, '_price', '' === $regular_price ? '' : wc_format_decimal( $regular_price ) );
					}

					if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
						update_post_meta( $product_id, '_price', '' === $regular_price ? '' : wc_format_decimal( $regular_price ) );
						update_post_meta( $product_id, '_sale_price_dates_from', '' );
						update_post_meta( $product_id, '_sale_price_dates_to', '' );
					}
				}

				break;

			// Any other text meta
			default:

				$meta_value = esc_attr($_POST['value']);
				update_post_meta( $product_id, '_' . $meta , $meta_value );
				break;
		}

		wp_send_json_success( __('Value saved.', ATUM_TEXT_DOMAIN) );

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
	 * Seach for WooCommerce orders through Inventory Logs' enhanced select
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
			"SELECT ID from {$wpdb->posts} WHERE post_type = 'shop_order' 
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
	 * Add a note to an Inventory Log
	 *
	 * @since 1.2.4
	 */
	public function add_inventory_log_note() {

		check_ajax_referer( 'add-log-note', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$post_id   = absint( $_POST['post_id'] );
		$note      = wp_kses_post( trim( stripslashes( $_POST['note'] ) ) );

		if ( $post_id ) {

			$log = new Log($post_id);
			$comment_id = $log->add_note( $note );

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

		wp_die();

	}

	/**
	 * Delete a note from an Inventory Log
	 *
	 * @since 1.2.4
	 */
	public function delete_inventory_log_note() {

		check_ajax_referer( 'delete-log-note', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$note_id = absint( $_POST['note_id'] );

		if ( $note_id ) {
			wp_delete_comment( $note_id );
		}

		wp_die();

	}

	/**
	 * Load inventory log items
	 *
	 * @since 1.2.4
	 */
	public function load_log_items() {

		check_ajax_referer( 'log-item', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$log_id = absint( $_POST['log_id'] );
		$log    = new Log( $log_id );

		Helpers::load_view( 'meta-boxes/inventory-logs/items', compact('log') );

		wp_die();

	}

	/**
	 * Add inventory log item
	 *
	 * @since 1.2.4
	 *
	 * @throws AtumException
	 */
	public function add_log_item() {

		check_ajax_referer( 'log-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['log_id'] )  ) {
			wp_die( -1 );
		}

		try {

			$log_id       = absint( $_POST['log_id'] );
			$log          = new Log( $log_id );
			$items_to_add = wp_parse_id_list( is_array( $_POST['item_to_add'] ) ? $_POST['item_to_add'] : array( $_POST['item_to_add'] ) );
			$html         = '';

			if ( ! $log ) {
				throw new AtumException( 'invalid_log', __( 'Invalid log', ATUM_TEXT_DOMAIN ) );
			}

			foreach ( $items_to_add as $item_to_add ) {

				if ( ! in_array( get_post_type( $item_to_add ), array( 'product', 'product_variation' ) ) ) {
					continue;
				}

				// Add the product to the Log
				$item = $log->add_product( wc_get_product( $item_to_add ) );
				$item_id = $item->get_id();
				$class = 'new_row';

				// Load template
				$html .= Helpers::load_view_to_string( 'meta-boxes/inventory-logs/item', compact('log', 'item', 'item_id', 'class') );

			}

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

	}

	/**
	 * Add inventory log fee
	 *
	 * @since 1.2.4
	 *
	 * @throws AtumException
	 */
	public function add_log_fee() {

		check_ajax_referer( 'log-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['log_id'] ) ) {
			wp_die( -1 );
		}

		try {

			$log_id  = absint( $_POST['log_id'] );
			$log     = new Log( $log_id );

			// Add a fee line item
			$item    = $log->add_fee();
			$item_id = $item->get_id();

			// Load template
			$html = Helpers::load_view_to_string( 'meta-boxes/inventory-logs/item-fee', compact('log', 'item', 'item_id') );

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Add inventory log shipping cost
	 *
	 * @since 1.2.4
	 *
	 * @throws AtumException
	 */
	public function add_log_shipping() {

		check_ajax_referer( 'log-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['log_id'] )  ) {
			wp_die( -1 );
		}

		try {

			$log_id = absint( $_POST['log_id'] );
			$log    = new Log( $log_id );

			$shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();

			// Add new shipping cost line item
			$item    = $log->add_shipping_cost();
			$item_id = $item->get_id();

			// Load template
			$html = Helpers::load_view_to_string( 'meta-boxes/inventory-logs/item-shipping', compact('log', 'item', 'item_id', 'shipping_methods') );

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

	}

	/**
	 * Add inventory log tax
	 *
	 * @since 1.2.4
	 *
	 * @throws AtumException
	 */
	public function add_log_tax() {

		check_ajax_referer( 'log-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['log_id'] )  ) {
			wp_die( -1 );
		}

		try {

			$log_id  = absint( $_POST['log_id'] );
			$log     = new Log( $log_id );
			$rate_id = absint( $_POST['rate_id'] );

			// Add new tax
			$log->add_tax($rate_id);

			// Load template
			$html = Helpers::load_view_to_string( 'meta-boxes/inventory-logs/items', compact('log') );

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

	}

	/**
	 * Remove a inventory log item
	 *
	 * @since 1.2.4
	 */
	public function remove_log_item() {

		check_ajax_referer( 'log-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$log_id  = absint( $_POST['log_id'] );
		$log_item_ids = $_POST['log_item_ids'];

		if ( ! is_array( $log_item_ids ) && is_numeric( $log_item_ids ) ) {
			$log_item_ids = array( $log_item_ids );
		}

		if ( ! empty($log_item_ids) ) {

			$log = new Log( $log_id );

			foreach ( $log_item_ids as $id ) {
				$log->remove_item( absint($id) );
			}

			$log->save_items();
		}

		wp_die();

	}

	/**
	 * Remove a inventory log tax
	 *
	 * @since 1.2.4
	 */
	public function remove_log_tax() {

		check_ajax_referer( 'log-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$log_id  = absint( $_POST['log_id'] );
		$rate_id = absint( $_POST['rate_id'] );
		$log = new Log( $log_id );
		$log->remove_item($rate_id);
		$log->save_items();

		// Load template
		Helpers::load_view( 'meta-boxes/inventory-logs/items', compact('log') );

		wp_die();

	}

	/**
	 * Calc inventory logs line taxes
	 *
	 * @since 1.2.4
	 */
	public function calc_line_taxes() {

		check_ajax_referer( 'calc-totals', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$log_id = absint( $_POST['log_id'] );

		/*$calculate_tax_args = array(
			'country'  => strtoupper( wc_clean( $_POST['country'] ) ),
			'state'    => strtoupper( wc_clean( $_POST['state'] ) ),
			'postcode' => strtoupper( wc_clean( $_POST['postcode'] ) ),
			'city'     => strtoupper( wc_clean( $_POST['city'] ) ),
		);*/

		// Parse the jQuery serialized items
		$items = array();
		parse_str( $_POST['items'], $items );

		// Grab the order and recalc taxes
		$log = new Log($log_id);
		$log->save_log_items($items);
		$log->calculate_taxes();
		$log->calculate_totals( FALSE );

		// Load template
		Helpers::load_view( 'meta-boxes/inventory-logs/items', compact('log') );

		wp_die();

	}

	/**
	 * Save inventory log items
	 *
	 * @since 1.2.4
	 */
	public function save_log_items() {

		check_ajax_referer( 'log-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		if ( isset( $_POST['log_id'], $_POST['items'] ) ) {

			$log_id = absint( $_POST['log_id'] );

			// Parse the jQuery serialized items
			$items = array();
			parse_str( $_POST['items'], $items );

			// Save order items
			$log = new Log( $log_id );
			$log->save_log_items($items);

			// Return HTML items
			Helpers::load_view( 'meta-boxes/inventory-logs/items', compact('log') );

		}

		wp_die();

	}

	/**
	 * Import the order items to the current Log after linking an order
	 *
	 * @since 1.2.4
	 */
	public function import_order_items() {

		check_ajax_referer( 'import-order-items', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		if ( isset( $_POST['log_id'], $_POST['order_id'] ) ) {

			$log_id = absint( $_POST['log_id'] );
			$order_id = absint( $_POST['order_id'] );

			$order = wc_get_order($order_id);

			if ( ! empty($order) ) {

				$items = $order->get_items( array( 'line_item', 'fee', 'shipping', 'tax' ) );

				if ( ! empty($items) ) {

					$log = new Log( $log_id );

					try {

						// The log only can have one tax applied, so check if already has one
						$current_tax = $log->get_items('tax');

						foreach ($items as $item) {

							if ( is_a($item, '\WC_Order_Item_Product') ) {
								$log_item = $log->add_product( wc_get_product( $item->get_product_id() ), $item->get_quantity() );
							}
							elseif ( is_a($item, '\WC_Order_Item_Fee') ) {
								$log_item = $log->add_fee($item);
							}
							elseif ( is_a($item, '\WC_Order_Item_Shipping') ) {
								$log_item = $log->add_shipping_cost($item);
							}
							elseif ( empty($current_tax) && is_a($item, '\WC_Order_Item_Tax') ) {
								$log_item = $log->add_tax( array( 'rate_id' => $item->get_rate_id() ), $item );
							}

							// Add the order ID as item's custom meta
							$log_item->add_meta_data('_order_id', $order_id, TRUE);
							$log_item->save_meta_data();

						}

						// Load template
						$html = Helpers::load_view_to_string( 'meta-boxes/inventory-logs/items', compact('log') );

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
	 * Mark an inventory log with a status
	 * NOTE: This callback is not being triggered through an Ajax request, just a normal HTTP request
	 *
	 * @since 1.2.4
	 */
	public function mark_log_status() {

		if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'atum-mark-log-status' ) ) {

			$status = sanitize_text_field( $_GET['status'] );
			$log  = new Log( absint( $_GET['log_id'] ) );

			if ( $log && in_array($status, array_keys( Log::get_statuses() ) ) ) {
				$log->update_status( $status );
				do_action( 'atum/inventory_logs/log_edit_status', $log->get_id(), $status );
			}

		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=' . InventoryLogs::POST_TYPE ) );
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