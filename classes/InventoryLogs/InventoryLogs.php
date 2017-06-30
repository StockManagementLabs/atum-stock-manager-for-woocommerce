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

use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\Models\Log;


class InventoryLogs {

	/**
	 * The singleton instance holder
	 *
	 * @var InventoryLogs
	 */
	private static $instance;

	/**
	 * The Inventory Log post type name
	 */
	const POST_TYPE = ATUM_PREFIX . 'inventory_log';

	/**
	 * The Inventory Log type taxonomy
	 */
	const TAXONOMY = ATUM_PREFIX . 'log_type';

	/**
	 * The post type labels
	 * @var array
	 */
	protected $labels = array();

	/**
	 * The customer's shipping fields
	 * @var array
	 */
	protected $shipping_fields = array();

	/**
	 * The customer's billing fields
	 * @var array
	 */
	protected $billing_fields = array();

	/**
	 * Will hold the current log object
	 * @var Log
	 */
	private $log;


	/**
	 * InventoryLogs singleton constructor
	 *
	 * @since 1.2.4
	 */
	private function __construct() {

		// Initialize
		$this->register_post_type();
		$this->register_post_status();

		// Add the Inventory Logs' meta table to wpdb
		global $wpdb;
		$wpdb->log_itemmeta = $wpdb->prefix . ATUM_PREFIX . 'log_itemmeta';
		$wpdb->tables[] = ATUM_PREFIX . 'log_itemmeta';

		// Add meta boxes to Inventory Logs
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_meta_boxes' ), 30 );

		// Save the meta boxes
		add_action( 'save_post_' . self::POST_TYPE , array( $this, 'save_meta_boxes' ) );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_footer', array($this, 'print_scripts') );

		// Add the "Inventory Logs" link to the ATUM's admin bar menu
		add_filter( 'atum/admin/top_bar/menu_items', array( $this, 'add_admin_bar_link' ) );

		// Add the filters to the post type list table
		add_action( 'restrict_manage_posts', array($this, 'add_log_filters') );
		add_filter( 'request', array( $this, 'request_query' ) );

		// Log search
		add_filter( 'get_search_query', array( $this, 'log_search_label' ) );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_action( 'parse_query', array( $this, 'log_search_custom_fields' ) );

		// Add the custom columns to the post type list table
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_log_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_log_columns' ), 2 );
		add_filter( 'list_table_primary_column', array( $this, 'log_table_primary_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 2, 100 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );

		// Disable post type view mode options
		add_filter( 'view_mode_post_types', array( $this, 'disable_view_mode_options' ) );

		// Disable Auto Save
		add_action( 'admin_print_scripts', array( $this, 'disable_autosave' ) );

		// Bulk actions
		add_filter( 'bulk_actions-edit-' . self::POST_TYPE, array( $this, 'add_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-' . self::POST_TYPE, array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );

		// Post update messages
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );

	}

	/**
	 * Register the Inventory Logs post type
	 *
	 * @since 1.2.4
	 */
	private function register_post_type() {

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
			'not_found'             => __( 'No Inventory Logs found', ATUM_TEXT_DOMAIN ),
			'not_found_in_trash'    => __( 'No Inventory Logs found in trash', ATUM_TEXT_DOMAIN ),
			'parent'                => __( 'Parent Inventory Logs', ATUM_TEXT_DOMAIN ),
			'menu_name'             => _x( 'Inventory Logs', 'Admin menu name', ATUM_TEXT_DOMAIN ),
			'filter_items_list'     => __( 'Filter Inventory Logs', ATUM_TEXT_DOMAIN ),
			'items_list_navigation' => __( 'Inventory Logs Navigation', ATUM_TEXT_DOMAIN ),
			'items_list'            => __( 'Inventory Logs List', ATUM_TEXT_DOMAIN ),
		);

		// Minimum capability required
		$is_user_allowed = current_user_can( 'manage_woocommerce' );

		// Register the Inventory Log post type
		register_post_type(
			self::POST_TYPE,
			apply_filters( 'atum/inventory_logs/register_post_type',
				array(
					'labels'              => $this->labels,
					'description'         => __( 'This is where inventory logs are stored.', ATUM_TEXT_DOMAIN ),
					'public'              => FALSE,
					'show_ui'             => $is_user_allowed,
					'publicly_queryable'  => FALSE,
					'exclude_from_search' => TRUE,
					'show_in_menu'        => $is_user_allowed ? Globals::ATUM_UI_SLUG : FALSE,
					'hierarchical'        => FALSE,
					'show_in_nav_menus'   => FALSE,
					'rewrite'             => FALSE,
					'query_var'           => is_admin(),
					'supports'            => array( 'title', 'comments', 'custom-fields' ),
					'has_archive'         => FALSE
				)
			)
		);

		// Register the hidden log type taxonomy
		register_taxonomy(
			self::TAXONOMY,
			array( self::POST_TYPE ),
			apply_filters( 'atum/inventory_logs/taxonomy_args_log_type', array(
				'hierarchical'          => FALSE,
				'show_ui'               => FALSE,
				'show_in_nav_menus'     => FALSE,
				'query_var'             => is_admin(),
				'rewrite'               => FALSE,
				'public'                => FALSE,
				'update_count_callback' => array( $this, 'log_term_recount' ),
			) )
		);

	}

	/**
	 * Register our custom post statuses, used for log status
	 *
	 * @since 1.2.4
	 */
	private function register_post_status() {

		$log_statuses = (array) apply_filters( 'atum/inventory_logs/register_log_post_statuses',
			array(
				ATUM_PREFIX . 'pending'   => array(
					'label'                     => _x( 'Pending payment', 'Log status', ATUM_TEXT_DOMAIN ),
					'public'                    => FALSE,
					'exclude_from_search'       => FALSE,
					'show_in_admin_all_list'    => TRUE,
					'show_in_admin_status_list' => TRUE,
					'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', ATUM_TEXT_DOMAIN )
				),
				ATUM_PREFIX . 'completed' => array(
					'label'                     => _x( 'Completed', 'Log status', ATUM_TEXT_DOMAIN ),
					'public'                    => FALSE,
					'exclude_from_search'       => FALSE,
					'show_in_admin_all_list'    => TRUE,
					'show_in_admin_status_list' => TRUE,
					'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', ATUM_TEXT_DOMAIN )
				)
			)
		);

		foreach ( $log_statuses as $log_status => $values ) {
			register_post_status( $log_status, $values );
		}
	}

	/**
	 * Method for recounting log terms (types)
	 *
	 * @since 1.2.4
	 *
	 * @param array  $terms
	 * @param string $taxonomy
	 */
	public function log_term_recount( $terms, $taxonomy ) {

		global $wpdb;

		// Custom version of the WP "_update_post_term_count()" function
		$object_types = (array) $taxonomy->object_type;

		foreach ( $object_types as &$object_type ) {
			list( $object_type ) = explode( ':', $object_type );
		}

		$object_types = array_unique( $object_types );

		if ( $object_types ) {
			$object_types = esc_sql( array_filter( $object_types, 'post_type_exists' ) );
		}

		foreach ( (array) $terms as $term ) {
			$count = 0;

			// Count all the logs with one of the log statuses (atum_pending or atum_completed)
			if ( $object_types ) {
				$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status IN ('atum_pending', 'atum_completed') AND post_type IN ('" . implode( "', '", $object_types ) . "') AND term_taxonomy_id = %d", $term ) );
			}

			/** This action is documented in wp-includes/taxonomy.php */
			do_action( 'edit_term_taxonomy', $term, $taxonomy->name );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

			/** This action is documented in wp-includes/taxonomy.php */
			do_action( 'edited_term_taxonomy', $term, $taxonomy->name );
		}

	}

	/**
	 * Add the Inventory Logs' meta boxes
	 *
	 * @since 1.2.4
	 */
	public function add_meta_boxes () {

		// Inventory Log data
		add_meta_box(
			self::POST_TYPE . '_data',
			__( 'Log Data', ATUM_TEXT_DOMAIN ),
			array($this, 'show_data_meta_box'),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Inventory Log items
		add_meta_box(
			self::POST_TYPE . '_items',
			__( 'Items', ATUM_TEXT_DOMAIN ),
			array($this, 'show_items_meta_box'),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Inventory Log notes
		add_meta_box(
			self::POST_TYPE . '_notes',
			__( 'Log Notes', ATUM_TEXT_DOMAIN ),
			array($this, 'show_notes_meta_box'),
			self::POST_TYPE,
			'side',
			'default'
		);

		// Inventory Log actions
		add_meta_box(
			self::POST_TYPE . '_actions',
			__( 'Log Actions', ATUM_TEXT_DOMAIN ),
			array($this, 'show_actions_meta_box'),
			self::POST_TYPE,
			'side',
			'high'
		);

		// Remove unneeded WP meta boxes
		remove_meta_box( 'commentsdiv', self::POST_TYPE, 'normal' );
		remove_meta_box( 'commentstatusdiv', self::POST_TYPE, 'normal' );
		remove_meta_box( 'slugdiv', self::POST_TYPE, 'normal' );
		remove_meta_box( 'submitdiv', self::POST_TYPE, 'side' );

	}

	/**
	 * Displays the data meta box at Inventory Log posts
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_Post $post
	 */
	public function show_data_meta_box( $post ) {

		$log = $this->get_current_log($post->ID);

		if ( ! is_a( $log, 'Atum\InventoryLogs\Models\Log' ) ) {
			return;
		}

		$log_post = $log->get_post();
		$order    = $log->get_order();
		$labels   = $this->labels;

		wp_nonce_field( 'atum_save_meta_data', 'atum_meta_nonce' );

		Helpers::load_view( 'meta-boxes/inventory-logs/data', compact( 'log', 'log_post', 'labels', 'order' ) );

	}

	/**
	 * Displays the items meta box at Inventory Log posts
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_Post $post
	 */
	public function show_items_meta_box( $post ) {

		$log = $this->get_current_log($post->ID);
		$order = $log->get_order();

		Helpers::load_view( 'meta-boxes/inventory-logs/items', compact('log', 'order') );
	}

	/**
	 * Displays the notes meta box at Inventory Log posts
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_Post $post
	 */
	public function show_notes_meta_box( $post ) {
		Helpers::load_view( 'meta-boxes/inventory-logs/notes', compact('post') );
	}

	/**
	 * Displays the actions meta box at Inventory Log posts
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_Post $post
	 */
	public function show_actions_meta_box( $post ) {
		Helpers::load_view( 'meta-boxes/inventory-logs/actions', compact('post') );
	}

	/**
	 * Save the Inventory Logs meta boxes
	 *
	 * @since 1.2.4
	 *
	 * @param int $log_id
	 */
	public function save_meta_boxes($log_id) {

		if ( ! isset( $_POST['log_type'], $_POST['log_status'], $_POST['atum_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['atum_meta_nonce'], 'atum_save_meta_data' ) ) {
			return;
		}

		$log = $this->get_current_log($log_id);

		if ( empty($log) ) {
			return;
		}

		$log_type = esc_attr( $_POST['log_type'] );

		/**
		 * Set the log dates
		 *
		 * @var string $log_reservation_date
		 * @var string $log_return_date
		 * @var string $log_damage_date
		 */
		$log_date = ( empty( $_POST['log_date'] ) ) ? current_time( 'timestamp', TRUE ) : gmdate( 'Y-m-d H:i:s', strtotime( $_POST['log_date'] . ' ' . (int) $_POST['log_date_hour'] . ':' . (int) $_POST['log_date_minute'] . ':00' ) );
		foreach ( ['log_reservation_date', 'log_return_date', 'log_damage_date'] as $date_field ) {
			${$date_field} = ( empty( $_POST[$date_field] ) ) ? '' : gmdate( 'Y-m-d H:i:s', strtotime( $_POST[$date_field] . ' ' . (int) $_POST["{$date_field}_hour"] . ':' . (int) $_POST["{$date_field}_minute"] . ':00' ) );
		}

		$log->save_meta( array(
			'_type'             => $log_type,
			'_status'           => esc_attr( $_POST['log_status'] ),
			'_order'            => ! empty( $_POST['log_order'] ) ? absint( $_POST['log_order'] ) : '',
			'_date_created'     => $log_date,
			'_reservation_date' => $log_reservation_date,
			'_return_date'      => $log_return_date,
			'_damage_date'      => $log_damage_date,
			'_shipping_company' => esc_attr( $_POST['log_shipping_company'] ),
			'_custom_name'      => esc_attr( $_POST['log_custom_name'] )
		) );

		// Add the Log post to the appropriate Log Type taxonomy
		if ( in_array( $log_type, array_keys( Log::get_types() ) ) ) {
			wp_set_object_terms($log_id, $log_type, self::TAXONOMY);
		}

		// Set the Log description as post content
		$log->set_description( $_POST['log_description'] );

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
	 * Filters and sorting handler
	 *
	 * @since 1.2.4
	 *
	 * @param  array $vars
	 *
	 * @return array
	 */
	public function request_query( $vars ) {

		global $typenow, $wp_post_statuses;

		if ( $typenow == self::POST_TYPE ) {

			// Sorting
			if ( isset( $vars['orderby'] ) && 'log_total' == $vars['orderby'] ) {

				$vars = array_merge( $vars, array(
					'meta_key'  => '_total',
					'orderby'   => 'meta_value_num',
				) );

			}

			// Status
			if ( ! isset( $vars['post_status'] ) ) {

				// All the Log posts must have the custom statuses creted for them
				$log_statuses = array(ATUM_PREFIX . 'pending', ATUM_PREFIX . 'completed');

				foreach ( $log_statuses as $key => $status ) {
					if ( isset( $wp_post_statuses[ $status ] ) && FALSE === $wp_post_statuses[ $status ]->show_in_admin_all_list ) {
						unset( $log_statuses[ $key ] );
					}
				}

				$vars['post_status'] =  $log_statuses;

			}

		}

		return $vars;
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

		if ( self::POST_TYPE !== $typenow ) {
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

		if ( 'edit.php' != $pagenow || empty( $query->query_vars['s'] ) || self::POST_TYPE !== $query->query_vars['post_type'] ) {
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
						SELECT log_id
						FROM {$wpdb->prefix}atum_log_items as log_items
						WHERE log_item_name LIKE '%%%s%%'
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
	 * Customize the columns used in the Log's post type list table
	 *
	 * @since 1.2.4
	 *
	 * @param array $existing_columns
	 *
	 * @return array
	 */
	public function add_log_columns($existing_columns) {

		$columns = array(
			'cb'          => $existing_columns['cb'],
			'log_status'  => '<span class="status_head tips" data-tip="' . esc_attr__( 'Log Status', ATUM_TEXT_DOMAIN ) . '">' . esc_attr__( 'Status', ATUM_TEXT_DOMAIN ) . '</span>',
			'log_title'   => __( 'Log', ATUM_TEXT_DOMAIN ),
			'log_type'    => __( 'Type', ATUM_TEXT_DOMAIN ),
			'log_order'   => __( 'Order', ATUM_TEXT_DOMAIN ),
			'log_notes'   => '<span class="log-notes_head tips" data-tip="' . esc_attr__( 'Log Notes', ATUM_TEXT_DOMAIN ) . '">' . esc_attr__( 'Notes', ATUM_TEXT_DOMAIN ) . '</span>',
			'log_date'    => __( 'Date', ATUM_TEXT_DOMAIN ),
			'log_total'   => __( 'Total', ATUM_TEXT_DOMAIN ),
			'log_actions' => __( 'Actions', ATUM_TEXT_DOMAIN )
		);

		return $columns;

	}

	/**
	 * Output custom columns for Log's post type table
	 *
	 * @since 1.2.4
	 *
	 * @param string $column
	 */
	public function render_log_columns( $column ) {

		global $post;

		$log = new Log( get_the_ID() );

		switch ( $column ) {

			case 'log_status':

				$statuses = Log::get_statuses();
				$log_status = $log->get_status();
				$log_status_name = isset($statuses[ $log_status ]) ? $statuses[ $log_status ] : '';

				printf( '<mark class="%s tips" data-tip="%s">%s</mark>', esc_attr( sanitize_html_class($log_status) ), esc_attr($log_status_name), esc_html( $log_status_name ) );
				break;

			case 'log_title':

				$author = $post->post_author;

				if ( $author ) {

					$user     = get_user_by( 'id', $author );
					$username = '<a href="user-edit.php?user_id=' . absint( $author ) . '">';
					$username .= esc_html( ucwords( $user->display_name ) );
					$username .= '</a>';

				}
				else {
					$username = 'ATUM';
				}

				printf(
					__( '%1$s by %2$s', ATUM_TEXT_DOMAIN ),
					'<a href="' . admin_url( 'post.php?post=' . absint( $post->ID ) . '&action=edit' ) . '" class="row-title"><strong>#' . esc_attr( $post->ID ) . '</strong></a>',
					$username
				);

				/*if ( $the_order->get_billing_email() ) {
					echo '<small class="meta email"><a href="' . esc_url( 'mailto:' . $the_order->get_billing_email() ) . '">' . esc_html( $the_order->get_billing_email() ) . '</a></small>';
				}*/

				echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', ATUM_TEXT_DOMAIN ) . '</span></button>';

				break;

			case 'log_type':

				$types = Log::get_types();
				$log_type = $log->get_type();

				if ( in_array( $log_type, array_keys($types) ) ) {
					echo $types[$log_type];
				}

				break;

			case 'log_order':

				$log_order = $log->get_order();

				echo ($log_order) ? '<a href="' . admin_url( 'post.php?post=' . absint( $log_order->get_id() ) . '&action=edit' ) . '" target="_blank">' . __('Order #', ATUM_TEXT_DOMAIN) . $log_order->get_id() . '</a>' : '&ndash;';
				break;

			case 'log_date':

				printf( '<time>%s</time>', date_i18n('Y-m-d', strtotime($post->post_date)) );
				break;

			case 'log_notes':

				if ( $post->comment_count ) {

					// check the status of the post
					$status = ( 'trash' !== $post->post_status ) ? '' : 'post-trashed';

					$latest_notes = get_comments( array(
						'post_id'   => $post->ID,
						'number'    => 1,
						'status'    => $status,
					) );

					$latest_note = current( $latest_notes );

					if ( isset( $latest_note->comment_content ) && 1 == $post->comment_count ) {
						echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $latest_note->comment_content ) . '">' . __( 'Yes', ATUM_TEXT_DOMAIN ) . '</span>';
					}
					elseif ( isset( $latest_note->comment_content ) ) {
						echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( $latest_note->comment_content . '<br/><small style="display:block">' . sprintf( _n( 'plus %d other note', 'plus %d other notes', ( $post->comment_count - 1 ), ATUM_TEXT_DOMAIN ), $post->comment_count - 1 ) . '</small>' ) . '">' . __( 'Yes', ATUM_TEXT_DOMAIN ) . '</span>';
					}
					else {
						echo '<span class="note-on tips" data-tip="' . wc_sanitize_tooltip( sprintf( _n( '%d note', '%d notes', $post->comment_count, ATUM_TEXT_DOMAIN ), $post->comment_count ) ) . '">' . __( 'Yes', ATUM_TEXT_DOMAIN ) . '</span>';
					}
				}
				else {
					echo '<span class="na">&ndash;</span>';
				}

				break;

			case 'log_total' :

				echo $log->get_formatted_total();

				/*if ( $the_order->get_payment_method_title() ) {
					echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_payment_method_title() ) . '</small>';
				}*/

				break;

			case 'log_actions':

				?><p><?php

				do_action( 'atum/inventory_logs/admin_log_actions_start', $log );

				$actions = array();
				$log_status = $log->get_status();

				if ( $log_status == 'completed' ) {
					$actions['pending'] = array(
						'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=atum_mark_log_status&status=pending&log_id=' . $post->ID ), 'atum-mark-log-status' ),
						'name'      => __( 'Mark as Pending', ATUM_TEXT_DOMAIN ),
						'action'    => 'pending',
					);
				}
				elseif ( $log_status == 'pending' ) {
					$actions['complete'] = array(
						'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=atum_mark_log_status&status=completed&log_id=' . $post->ID ), 'atum-mark-log-status' ),
						'name'      => __( 'Mark as Completed', ATUM_TEXT_DOMAIN ),
						'action'    => 'complete',
					);
				}

				$actions['view'] = array(
					'url'       => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
					'name'      => __( 'View', ATUM_TEXT_DOMAIN ),
					'action'    => 'view',
				);

				$actions = apply_filters( 'atum/inventory_logs/admin_order_actions', $actions, $log );

				foreach ( $actions as $action ) {
					printf( '<a class="button %s tips" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
				}

				do_action( 'atum/inventory_logs/admin_log_actions_end', $log ); ?>
				</p><?php

				break;

		}

	}

	/**
	 * Set the primary column for the Log table list
	 *
	 * @since 1.2.4
	 *
	 * @param string $default
	 * @param string $screen_id
	 *
	 * @return string
	 */
	public function log_table_primary_column($default, $screen_id) {

		if ( 'edit-' . self::POST_TYPE === $screen_id ) {
			return 'log_title';
		}

		return $default;

	}

	/**
	 * Set row actions for Log items' table list
	 *
	 * @since 1.2.4
	 *
	 * @param  array $actions
	 * @param  \WP_Post $post
	 *
	 * @return array
	 */
	public function row_actions( $actions, $post ) {

		if ( self::POST_TYPE === $post->post_type && isset( $actions['inline hide-if-no-js'] ) ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}

	/**
	 * Make columns sortable - https://gist.github.com/906872
	 *
	 * @since 1.2.4
	 *
	 * @param  array $columns
	 *
	 * @return array
	 */
	public function sortable_columns( $columns ) {

		$custom = array(
			'log_title' => 'ID',
			'log_total' => 'log_total',
			'log_date'  => 'date',
		);

		unset( $columns['comments'] );

		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Get the currently instantiated log object (if any) or create a new one
	 *
	 * @since 1.2.4
	 *
	 * @param int $post_id
	 *
	 * @return Log
	 */
	private function get_current_log($post_id) {

		if ( ! $this->log ) {
			$this->log = new Log( $post_id );
		}

		return $this->log;

	}

	/**
	 * Enqueue the scripts
	 *
	 * @since 1.2.4
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts($hook) {

		global $post_type;

		if ($post_type == self::POST_TYPE) {

			global $wp_scripts, $post;

			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.11.4';
			wp_register_style( 'jquery-ui-style', "//code.jquery.com/ui/$jquery_version/themes/smoothness/jquery-ui.min.css", array(), $jquery_version );
			wp_register_style( 'atum-inventory-logs', ATUM_URL . 'assets/css/atum-inventory-logs.css', array('jquery-ui-style'), ATUM_VERSION );

			if ( in_array( $hook, ['post-new.php', 'post.php'] ) ) {

				wp_enqueue_style( 'atum-inventory-logs' );

				// Enqueue the script with the required WooCommerce dependencies
				$wc_dependencies = (array) apply_filters('atum/inventory_logs/scripts/woocommerce_dependencies', array(
					'wc-enhanced-select',
					'wc-backbone-modal',
					'jquery-blockui',
					'jquery-ui-datepicker',
					'stupidtable',
					'accounting'
				));

				wp_register_script( 'atum-inventory-logs', ATUM_URL . 'assets/js/atum.inventory.logs.js', $wc_dependencies, ATUM_VERSION, TRUE );

				wp_localize_script( 'atum-inventory-logs', 'atumInventoryLogs', array(
					'add_log_note_nonce'       => wp_create_nonce( 'add-log-note' ),
					'delete_log_note_nonce'    => wp_create_nonce( 'delete-log-note' ),
					'delete_note'              => __( 'Are you sure you wish to delete this note? This action cannot be undone.', ATUM_TEXT_DOMAIN ),
					'post_id'                  => isset( $post->ID ) ? $post->ID : '',
					'log_item_nonce'           => wp_create_nonce( 'log-item' ),
					'rounding_precision'       => wc_get_rounding_precision(),
					'mon_decimal_point'        => wc_get_price_decimal_separator(),
					'remove_item_notice'       => __( 'Are you sure you want to remove this item?', ATUM_TEXT_DOMAIN ),
					'delete_tax_notice'        => __( 'Are you sure you wish to delete this tax column? This action cannot be undone.', ATUM_TEXT_DOMAIN ),
					'calc_totals'              => __( 'Recalculate totals? This will calculate taxes based on the store base country and update totals.', ATUM_TEXT_DOMAIN ),
					'calc_totals_nonce'        => wp_create_nonce( 'calc-totals' ),
					'tax_based_on'             => esc_attr( get_option( 'woocommerce_tax_based_on' ) ),
					'remove_item_meta'         => __( 'Remove this item meta?', ATUM_TEXT_DOMAIN ),
					'tax_rate_already_exists'  => __( 'You cannot add the same tax rate twice!', ATUM_TEXT_DOMAIN ),
					'placeholder_name'         => esc_attr__( 'Name (required)', ATUM_TEXT_DOMAIN ),
					'placeholder_value'        => esc_attr__( 'Value (required)', ATUM_TEXT_DOMAIN ),
					'import_order_items'       => __( 'Do you want to import all the items within the selected order into this Log?', ATUM_TEXT_DOMAIN ),
					'import_order_items_nonce' => wp_create_nonce( 'import-order-items' )
				) );

				wp_enqueue_script( 'atum-inventory-logs' );

			}
			elseif ($hook == 'edit.php') {
				wp_enqueue_style( 'atum-inventory-logs' );
				wp_enqueue_script('jquery-tiptip'); // WooCommerce's jQuery TipTip
			}

		}

	}

	/**
	 * Print the scripts to the admin page footer
	 *
	 * @since 1.2.4
	 */
	public function print_scripts() {

		global $post_type, $pagenow;

		if ($post_type == self::POST_TYPE && $pagenow == 'edit.php') {

			?>
			<script type="text/javascript">
				jQuery(function($){

					$('.tips').tipTip({
						'attribute': 'data-tip',
						'fadeIn'   : 50,
						'fadeOut'  : 50,
						'delay'    : 200
					});

				});
			</script>
			<?php

		}

	}

	/**
	 * Removes logs from the list of post types that support "View Mode" switching.
	 * View mode is seen on posts where you can switch between list or excerpt. Our post types don't support
	 * it, so we want to hide the useless UI from the screen options tab.
	 *
	 * @since 1.2.4
	 *
	 * @param  array $post_types Post types supporting view mode
	 *
	 * @return array
	 */
	public function disable_view_mode_options( $post_types ) {
		unset( $post_types[ self::POST_TYPE ] );
		return $post_types;
	}

	/**
	 * Disable the WP auto-save functionality for Logs
	 *
	 * @since 1.2.4
	 */
	public function disable_autosave() {
		global $post;

		if ( $post && get_post_type( $post->ID ) == self::POST_TYPE ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	/**
	 * Manipulate Log bulk actions
	 *
	 * @since 1.2.4
	 *
	 * @param  array $actions List of actions
	 *
	 * @return array
	 */
	public function add_bulk_actions( $actions ) {
		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		$actions['mark_pending'] = __( 'Mark as Pending', ATUM_TEXT_DOMAIN );
		$actions['mark_completed']  = __( 'Mark as Completed', ATUM_TEXT_DOMAIN );

		return $actions;
	}

	/**
	 * Handle Log bulk actions
	 *
	 * @since  1.2.4
	 *
	 * @param  string $redirect_to URL to redirect to
	 * @param  string $action      Action name
	 * @param  array  $ids         List of ids
	 *
	 * @return string
	 */
	public function handle_bulk_actions( $redirect_to, $action, $ids ) {

		// Bail out if this is not a status-changing action
		if ( FALSE === strpos( $action, 'mark_' ) ) {
			return $redirect_to;
		}

		$log_statuses = Log::get_statuses();
		$new_status     = substr( $action, 5 ); // Get the status name from action
		$report_action  = 'marked_' . $new_status;

		// Sanity check: bail out if this is actually not a status, or is not a registered status
		if ( ! isset( $log_statuses[ $new_status ] ) ) {
			return $redirect_to;
		}

		$changed = 0;
		$ids = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {
			$log = new Log( $id );
			$log->update_status( $new_status, __( 'Log status changed by bulk edit:', ATUM_TEXT_DOMAIN ), TRUE );
			do_action( 'atum/inventory_logs/edit_status', $id, $new_status );
			$changed++;
		}

		$redirect_to = add_query_arg( array(
			'post_type'    => self::POST_TYPE,
			$report_action => TRUE,
			'changed'      => $changed,
			'ids'          => join( ',', $ids ),
		), $redirect_to );

		return esc_url_raw( $redirect_to );

	}

	/**
	 * Show confirmation message that Log status changed for number of orders
	 *
	 * @since 1.2.4
	 */
	public function bulk_admin_notices() {

		global $post_type, $pagenow;

		// Bail out if not on Log list page
		if ( 'edit.php' !== $pagenow || self::POST_TYPE !== $post_type ) {
			return;
		}

		$log_statuses = Log::get_statuses();

		// Check if any status changes happened
		foreach ( $log_statuses as $slug => $name ) {

			if ( isset( $_REQUEST[ 'marked_' . str_replace( ATUM_PREFIX, '', $slug ) ] ) ) {

				$number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
				$message = sprintf( _n( 'Log status changed.', '%s log statuses changed.', $number, ATUM_TEXT_DOMAIN ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . $message . '</p></div>';

				break;
			}
		}

	}

	/**
	 * Specify custom bulk actions messages for the Log post type
	 *
	 * @since 1.2.4
	 *
	 * @param  array $bulk_messages
	 * @param  array $bulk_counts
	 *
	 * @return array
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
			0 => '', // Unused. Messages start at index 1
			1 => __( 'Log updated.', ATUM_TEXT_DOMAIN ),
			2 => __( 'Custom field updated.', ATUM_TEXT_DOMAIN ),
			3 => __( 'Custom field deleted.', ATUM_TEXT_DOMAIN ),
			4 => __( 'Log updated.', ATUM_TEXT_DOMAIN ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Log restored to revision from %s', ATUM_TEXT_DOMAIN ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Log updated.', ATUM_TEXT_DOMAIN ),
			7 => __( 'Log saved.', ATUM_TEXT_DOMAIN ),
			8 => __( 'Log submitted.', ATUM_TEXT_DOMAIN ),
			9 => sprintf( __( 'Log scheduled for: <strong>%1$s</strong>.', ATUM_TEXT_DOMAIN ), date_i18n( __( 'M j, Y @ G:i', ATUM_TEXT_DOMAIN ), strtotime( $post->post_date ) ) ),
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
	public function add_admin_bar_link($atum_menus) {

		$atum_menus['inventory-logs'] = array(
			'slug'  => 'inventory-logs',
			'title' => $this->labels['menu_name'],
			'href'  => 'edit.php?post_type=' . self::POST_TYPE
		);

		return $atum_menus;

	}

	/**
	 * Getter for the Inventory Logs post type property
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public static function get_post_type() {
		return self::POST_TYPE;
	}

	/**
	 * Getter for the Inventory Logs taxonomy propery
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public static function get_type_taxonomy() {
		return self::TAXONOMY;
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
	 * @return InventoryLogs instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}