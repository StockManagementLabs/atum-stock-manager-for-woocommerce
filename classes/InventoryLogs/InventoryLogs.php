<?php
/**
 * @package         Atum
 * @subpackage      InventoryLogs
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * Inventory Logs main class
 */

namespace Atum\InventoryLogs;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\Models\Log;


class InventoryLogs extends AtumOrderPostType {

	/**
	 * The Inventory Log post type name
	 */
	const POST_TYPE = ATUM_PREFIX . 'inventory_log';

	/**
	 * The Inventory Log type taxonomy
	 */
	const TAXONOMY = ATUM_PREFIX . 'log_type';
	
	/**
	 * The menu order
	 */
	const MENU_ORDER = 25;

	/**
	 * Will hold the current log object
	 * @var Log
	 */
	private $log;

	/**
	 * The capabilities used when registering the post type
	 * @var array
	 */
	protected $capabilities = array(
		'edit_post'              => 'edit_inventory_log',
		'read_post'              => 'read_inventory_log',
		'delete_post'            => 'delete_inventory_log',
		'edit_posts'             => 'edit_inventory_logs',
		'edit_others_posts'      => 'edit_others_inventory_logs',
		'create_posts'           => 'create_inventory_logs',
		'delete_posts'           => 'delete_inventory_logs',
		'delete_other_posts'     => 'delete_other_inventory_logs'
	);


	/**
	 * InventoryLogs constructor
	 *
	 * @since 1.2.4
	 */
	public function __construct() {

		// Set post type labels
		$this->labels = array(
			'name'                  => __( 'Inventory Logs', ATUM_TEXT_DOMAIN ),
			'singular_name'         => _x( 'Inventory Log', self::POST_TYPE . ' post type singular name', ATUM_TEXT_DOMAIN ),
			'add_new'               => __( 'Add New Log', ATUM_TEXT_DOMAIN ),
			'add_new_item'          => __( 'Add New Log', ATUM_TEXT_DOMAIN ),
			'edit'                  => __( 'Edit', ATUM_TEXT_DOMAIN ),
			'edit_item'             => __( 'Edit Log', ATUM_TEXT_DOMAIN ),
			'new_item'              => __( 'New Log', ATUM_TEXT_DOMAIN ),
			'view'                  => __( 'View Log', ATUM_TEXT_DOMAIN ),
			'view_item'             => __( 'View Log', ATUM_TEXT_DOMAIN ),
			'search_items'          => __( 'Search Logs', ATUM_TEXT_DOMAIN ),
			'not_found'             => __( 'No inventory logs found', ATUM_TEXT_DOMAIN ),
			'not_found_in_trash'    => __( 'No inventory logs found in trash', ATUM_TEXT_DOMAIN ),
			'parent'                => __( 'Parent inventory log', ATUM_TEXT_DOMAIN ),
			'menu_name'             => _x( 'Inventory Logs', 'Admin menu name', ATUM_TEXT_DOMAIN ),
			'filter_items_list'     => __( 'Filter inventory logs', ATUM_TEXT_DOMAIN ),
			'items_list_navigation' => __( 'Inventory logs navigation', ATUM_TEXT_DOMAIN ),
			'items_list'            => __( 'Inventory logs list', ATUM_TEXT_DOMAIN ),
		);

		// Set meta box labels
		$this->metabox_labels = array(
			'data'    => __( 'Log Data', ATUM_TEXT_DOMAIN ),
			'notes'   => __( 'Log Notes', ATUM_TEXT_DOMAIN ),
			'actions' => __( 'Log Actions', ATUM_TEXT_DOMAIN )
		);

		// Initialize
		parent::__construct();

		// Add item order
		add_filter( 'atum/admin/menu_items_order', array( $this, 'add_item_order' ) );

		// Add the "Inventory Logs" link to the ATUM's admin bar menu
		add_filter( 'atum/admin/top_bar/menu_items', array( $this, 'add_admin_bar_link' ) );

		// Add the filters to the post type list table
		add_action( 'restrict_manage_posts', array($this, 'add_log_filters') );

		// Log search
		add_filter( 'get_search_query', array( $this, 'log_search_label' ) );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_action( 'parse_query', array( $this, 'log_search_custom_fields' ) );

		// Add the help tab to Inventory Logs' list page
		add_action( 'load-edit.php', array( $this, 'add_help_tab' ) );
		
	}

	/**
	 * Displays the data meta box at Inventory Logs
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_Post $post
	 */
	public function show_data_meta_box( $post ) {

		$atum_order = $this->get_current_atum_order($post->ID);

		if ( ! is_a( $atum_order, 'Atum\InventoryLogs\Models\Log' ) ) {
			return;
		}

		$atum_order_post = $atum_order->get_post();
		$wc_order        = $atum_order->get_order();
		$labels          = $this->labels;

		wp_nonce_field( 'atum_save_meta_data', 'atum_meta_nonce' );

		Helpers::load_view( 'meta-boxes/inventory-log/data', compact( 'atum_order', 'atum_order_post', 'labels', 'wc_order' ) );

	}

	/**
	 * Save the Inventory Logs meta boxes
	 *
	 * @since 1.2.4
	 *
	 * @param int $log_id
	 */
	public function save_meta_boxes($log_id) {

		if ( ! isset( $_POST['atum_order_type'], $_POST['status'], $_POST['atum_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['atum_meta_nonce'], 'atum_save_meta_data' ) ) {
			return;
		}

		$log = $this->get_current_atum_order($log_id);

		if ( empty($log) ) {
			return;
		}

		$log_type = esc_attr( $_POST['atum_order_type'] );

		/**
		 * Set the log dates
		 *
		 * @var string $reservation_date
		 * @var string $return_date
		 * @var string $damage_date
		 */
		$log_date = ( empty( $_POST['date'] ) ) ? current_time( 'timestamp', TRUE ) : strtotime( $_POST['date'] . ' ' . (int) $_POST['date_hour'] . ':' . (int) $_POST['date_minute'] . ':00' );
		$log_date = date_i18n( 'Y-m-d H:i:s', $log_date);

		foreach ( ['reservation_date', 'return_date', 'damage_date'] as $date_field ) {
			${$date_field} = ( empty( $_POST[$date_field] ) ) ? '' : date_i18n( 'Y-m-d H:i:s', strtotime( $_POST[$date_field] . ' ' . (int) $_POST["{$date_field}_hour"] . ':' . (int) $_POST["{$date_field}_minute"] . ':00' ) );
		}

		$log->save_meta( array(
			'_type'             => $log_type,
			'_status'           => esc_attr( $_POST['status'] ),
			'_order'            => ! empty( $_POST['wc_order'] ) ? absint( $_POST['wc_order'] ) : '',
			'_date_created'     => $log_date,
			'_reservation_date' => $reservation_date,
			'_return_date'      => $return_date,
			'_damage_date'      => $damage_date,
			'_shipping_company' => esc_attr( $_POST['shipping_company'] ),
			'_custom_name'      => esc_attr( $_POST['custom_name'] )
		) );

		// Add the Log post to the appropriate Log Type taxonomy
		if ( in_array( $log_type, array_keys( Log::get_types() ) ) ) {
			wp_set_object_terms($log_id, $log_type, self::TAXONOMY);
		}

		// Set the Log description as post content
		$log->set_description( $_POST['description'] );

		$log->save();

	}

	/**
	 * Add the filtering dropdowns to the Inventory Logs post type table
	 *
	 * @since 1.2.4
	 */
	public function add_log_filters() {

		global $typenow;

		if ($typenow == self::POST_TYPE) {

			wp_dropdown_categories( array(
				'show_option_all' => __( "Show All Log Types", ATUM_TEXT_DOMAIN ),
				'taxonomy'        => self::TAXONOMY,
				'name'            => self::TAXONOMY,
				'orderby'         => 'name',
				'selected'        => isset( $_GET[ self::TAXONOMY ] ) ? $_GET[ self::TAXONOMY ] : '',
				'hierarchical'    => FALSE,
				'show_count'      => TRUE,
				'hide_empty'      => FALSE,
				'value_field'     => 'slug'
			) );

		}

	}

	/**
	 * Change the label when searching logs
	 *
	 * @since 1.2.4
	 *
	 * @param mixed $query
	 *
	 * @return string
	 */
	public function log_search_label( $query ) {

		global $pagenow, $typenow;

		if ( 'edit.php' != $pagenow ) {
			return $query;
		}

		if ( self::POST_TYPE != $typenow ) {
			return $query;
		}

		if ( ! get_query_var( 'log_search' ) ) {
			return $query;
		}

		return wp_unslash( $_GET['s'] );

	}

	/**
	 * Query vars for Log's custom searches
	 *
	 * @since 1.2.4
	 *
	 * @param mixed $public_query_vars
	 *
	 * @return array
	 */
	public function add_custom_query_var( $public_query_vars ) {
		$public_query_vars[] = 'log_search';

		return $public_query_vars;
	}

	/**
	 * Search custom fields as well as content
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_Query $query
	 */
	public function log_search_custom_fields( $query ) {

		global $pagenow, $wpdb;

		if ( 'edit.php' != $pagenow || empty( $query->query_vars['s'] ) || self::POST_TYPE != $query->query_vars['post_type'] ) {
			return;
		}

		// Remove non-needed strings from search terms
		$term = str_replace(
			array(
				__( 'Order #', ATUM_TEXT_DOMAIN ),
				'Order #',
				__( 'Log #', ATUM_TEXT_DOMAIN ),
				'Log #',
				'#'
			),
			'',
			wc_clean( $_GET['s'] )
		);

		// Searches on meta data can be slow - this lets you choose what fields to search
		$search_fields = array_map( 'wc_clean', apply_filters( 'atum/inventory_logs/log_search_fields', array('_order') ) );
		$log_ids = array();

		if ( is_numeric( $term ) ) {
			$log_ids[] = absint( $term );
		}

		if ( ! empty( $search_fields ) ) {

			$log_ids = array_unique( array_merge(
				$log_ids,
				$wpdb->get_col(
					$wpdb->prepare( "SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1 WHERE p1.meta_value LIKE '%%%s%%'", $wpdb->esc_like($term) ) .
					" AND p1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $search_fields ) ) . "')"
				),
				$wpdb->get_col(
					$wpdb->prepare( "
						SELECT order_id
						FROM {$wpdb->prefix}" . self::ORDER_ITEMS_TABLE . " as log_items
						WHERE order_item_name LIKE '%%%s%%'
						",
						$wpdb->esc_like($term)
					)
				)
			) );

		}

		$log_ids = apply_filters( 'atum/inventory_logs/log_search_results', $log_ids, $term, $search_fields );

		if ( ! empty( $log_ids ) ) {
			// Remove "s" - we don't want to search log names
			unset( $query->query_vars['s'] );

			// So we know we're doing this
			$query->query_vars['log_search'] = true;

			// Search by found posts
			$query->query_vars['post__in'] = array_merge( $log_ids, array( 0 ) );
		}

	}

	/**
	 * @inheritdoc
	 */
	public function add_columns($existing_columns) {

		$columns = array(
			'cb'               => $existing_columns['cb'],
			'status'           => '<span class="status_head tips" data-tip="' . esc_attr__( 'Log Status', ATUM_TEXT_DOMAIN ) . '">' . esc_attr__( 'Status', ATUM_TEXT_DOMAIN ) . '</span>',
			'atum_order_title' => __( 'Log', ATUM_TEXT_DOMAIN ),
			'type'             => __( 'Type', ATUM_TEXT_DOMAIN ),
			'wc_order'         => __( 'Order', ATUM_TEXT_DOMAIN ),
			'notes'            => '<span class="notes_head tips" data-tip="' . esc_attr__( 'Log Notes', ATUM_TEXT_DOMAIN ) . '">' . esc_attr__( 'Notes', ATUM_TEXT_DOMAIN ) . '</span>',
			'date'             => __( 'Date', ATUM_TEXT_DOMAIN ),
			'total'            => __( 'Total', ATUM_TEXT_DOMAIN ),
			'actions'          => __( 'Actions', ATUM_TEXT_DOMAIN )
		);

		return $columns;

	}

	/**
	 * @inheritdoc
	 */
	public function render_columns( $column ) {

		global $post;

		$rendered = parent::render_columns($column);

		if ($rendered) {
			return;
		}

		$log = $this->get_current_atum_order($post->ID);

		switch ( $column ) {

			case 'type':

				$types = Log::get_types();
				$log_type = $log->get_type();

				if ( in_array( $log_type, array_keys($types) ) ) {
					echo $types[$log_type];
				}

				break;

			case 'wc_order':

				$log_order = $log->get_order();

				echo ($log_order) ? '<a href="' . admin_url( 'post.php?post=' . absint( $log_order->get_id() ) . '&action=edit' ) . '" target="_blank">' . __('Order #', ATUM_TEXT_DOMAIN) . $log_order->get_id() . '</a>' : '&ndash;';
				break;

		}

	}

	/**
	 * @inheritdoc
	 */
	public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {

		$bulk_messages[ self::POST_TYPE ] = array(
			'updated'   => _n( '%s log updated.', '%s logs updated.', $bulk_counts['updated'], ATUM_TEXT_DOMAIN ),
			'locked'    => _n( '%s log not updated, somebody is editing it.', '%s logs not updated, somebody is editing them.', $bulk_counts['locked'], ATUM_TEXT_DOMAIN ),
			'deleted'   => _n( '%s log permanently deleted.', '%s logs permanently deleted.', $bulk_counts['deleted'], ATUM_TEXT_DOMAIN ),
			'trashed'   => _n( '%s log moved to the Trash.', '%s logs moved to the Trash.', $bulk_counts['trashed'], ATUM_TEXT_DOMAIN ),
			'untrashed' => _n( '%s log restored from the Trash.', '%s logs restored from the Trash.', $bulk_counts['untrashed'], ATUM_TEXT_DOMAIN )
		);

		return $bulk_messages;
	}

	/**
	 * Change messages when a Log post type is updated
	 *
	 * @since 1.2.4
	 *
	 * @param  array $messages
	 *
	 * @return array
	 */
	public function post_updated_messages( $messages ) {

		global $post;

		$messages[ self::POST_TYPE ] = array(
			0  => '', // Unused. Messages start at index 1
			1  => __( 'Log updated.', ATUM_TEXT_DOMAIN ),
			2  => __( 'Custom field updated.', ATUM_TEXT_DOMAIN ),
			3  => __( 'Custom field deleted.', ATUM_TEXT_DOMAIN ),
			4  => __( 'Log updated.', ATUM_TEXT_DOMAIN ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Log restored to revision from %s', ATUM_TEXT_DOMAIN ), wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE,
			6  => __( 'Log updated.', ATUM_TEXT_DOMAIN ),
			7  => __( 'Log saved.', ATUM_TEXT_DOMAIN ),
			8  => __( 'Log submitted.', ATUM_TEXT_DOMAIN ),
			9  => sprintf( __( 'Log scheduled for: <strong>%1$s</strong>.', ATUM_TEXT_DOMAIN ), date_i18n( __( 'M j, Y @ G:i', ATUM_TEXT_DOMAIN ), strtotime( $post->post_date ) ) ),
			10 => __( 'Log draft updated.', ATUM_TEXT_DOMAIN ),
			11 => __( 'Log updated and email sent.', ATUM_TEXT_DOMAIN )
		);

		return $messages;
	}

	/**
	 * Add the Inventory Logs link to the ATUM's admin bar menu
	 *
	 * @since 1.2.4
	 *
	 * @param array $atum_menus
	 *
	 * @return array
	 */
	public function add_admin_bar_link( $atum_menus ) {

		$atum_menus['inventory-logs'] = array(
			'slug'       => ATUM_TEXT_DOMAIN . '-inventory-logs',
			'title'      => $this->labels['menu_name'],
			'href'       => 'edit.php?post_type=' . self::POST_TYPE,
			'menu_order' => self::MENU_ORDER
		);

		return $atum_menus;
	}
	
	/**
	 * Add the current item menu order
	 *
	 * @param array $items_order
	 *
	 * @return array
	 */
	public function add_item_order( $items_order ) {

		$items_order[] = array(
			'slug'       => 'edit.php?post_type=' . self::POST_TYPE,
			'menu_order' => self::MENU_ORDER
		);
		
		return $items_order;
		
	}

	/**
	 * Get the currently instantiated Log object (if any) or create a new one
	 *
	 * @since 1.2.4
	 *
	 * @param int $post_id
	 *
	 * @return Log
	 */
	protected function get_current_atum_order($post_id) {

		if ( ! $this->log || $this->log->get_id() != $post_id ) {
			$this->log = new Log( $post_id );
		}

		return $this->log;

	}

	/**
	 * Add the help tab to the Inventory Logs' list page
	 *
	 * @since 1.3.0
	 */
	public function add_help_tab() {

		$screen = get_current_screen();

		if ($screen && strpos($screen->id, self::POST_TYPE) !== FALSE) {

			$help_tabs = array(
				array(
					'name'  => 'columns',
					'title' => __( 'Columns', ATUM_TEXT_DOMAIN ),
				)
			);

			Helpers::add_help_tab($help_tabs, $this);

		}

	}

	/**
	 * Display the help tabs' content
	 *
	 * @since 1.3.0
	 *
	 * @param \WP_Screen $screen    The current screen
	 * @param array      $tab       The current help tab
	 */
	public function help_tabs_content( $screen, $tab ) {

		Helpers::load_view( 'help-tabs/inventory-logs/' . $tab['name'] );
	}

}