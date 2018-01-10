<?php
/**
 * @package         Atum\Components
 * @subpackage      AtumOrders
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.9
 *
 * The abstract class for the ATUM Order post types
 */

namespace Atum\Components\AtumOrders;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Helpers;
use Atum\Inc\Main;


abstract class AtumOrderPostType {

	/**
	 * The post type labels
	 * @var array
	 */
	protected $labels = array();

	/**
	 * The meta boxes' labels
	 * @var array
	 */
	protected $metabox_labels = array();

	/**
	 * The capabilities used when registering the post type
	 * @var array
	 */
	protected $capabilities = array();

	/**
	 * The ATUM Order items table name
	 */
	const ORDER_ITEMS_TABLE = ATUM_PREFIX . 'order_items';

	/**
	 * The ATUM Order item meta table name
	 */
	const ORDER_ITEM_META_TABLE = ATUM_PREFIX . 'order_itemmeta';


	/**
	 * AtumOrderPostType constructor
	 *
	 * @since 1.2.9
	 */
	public function __construct() {

		// Add the ATUM prefix to all the capabilities
		$this->capabilities = preg_filter('/^/', ATUM_PREFIX, $this->capabilities);

		// Add the ATUM Orders' meta table to wpdb
		global $wpdb;
		$wpdb->atum_order_itemmeta = $wpdb->prefix . self::ORDER_ITEM_META_TABLE;
		$wpdb->tables[] = self::ORDER_ITEM_META_TABLE;

		// Register the post type
		add_action( 'init', array($this, 'register_post_type') );

		if ( is_admin() ) {

			// Add the custom columns to the post type list table
			add_filter( 'manage_' . static::POST_TYPE . '_posts_columns', array( $this, 'add_columns' ) );
			add_action( 'manage_' . static::POST_TYPE . '_posts_custom_column', array( $this, 'render_columns' ), 2 );
			add_filter( 'post_row_actions', array( $this, 'row_actions' ), 2, 100 );
			add_filter( 'manage_edit-' . static::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
			add_filter( 'list_table_primary_column', array( $this, 'list_table_primary_column' ), 10, 2 );

			// Filters and sorts the ATUM Orders' list tables
			add_filter( 'request', array( $this, 'request_query' ) );

			// Add meta boxes to ATUM Order UI
			add_action( 'add_meta_boxes_' . static::POST_TYPE, array( $this, 'add_meta_boxes' ), 30 );

			// Save the meta boxes
			add_action( 'save_post_' . static::POST_TYPE, array( $this, 'save_meta_boxes' ) );

			// Disable post type view mode options
			add_filter( 'view_mode_post_types', array( $this, 'disable_view_mode_options' ) );

			// Disable Auto Save
			add_action( 'admin_print_scripts', array( $this, 'disable_autosave' ) );

			// Post update messages
			add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

			// Bulk actions
			add_filter( 'bulk_actions-edit-' . static::POST_TYPE, array( $this, 'add_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-' . static::POST_TYPE, array( $this, 'handle_bulk_actions' ), 10, 3 );
			add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );
			add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );

			// Enqueue scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_footer', array( $this, 'print_scripts' ) );

			// Make Atum orders not translatable
			if (class_exists('\woocommerce_wpml')) {
				add_action( 'admin_head', array( $this, 'hide_multilingual_content_setup_box'));
				add_action( 'init', array( $this, 'remove_language_switcher'), 12);
			}
			
		}

	}

	/**
	 * Register the ATUM Order post type
	 *
	 * @param array $args
	 *
	 * @since 1.2.9
	 */
	public function register_post_type( $args = array() ) {

		// Minimum capability required
		$read_capability = ( isset($this->capabilities['read_post']) ) ? $this->capabilities['read_post'] : 'manage_woocommerce';
		$is_user_allowed = current_user_can($read_capability);
		$main_menu_item  = Main::get_main_menu_item();

		$args = apply_filters( 'atum/order_post_type/post_type_args', wp_parse_args( array(
			'labels'              => $this->labels,
			'description'         => __( 'This is where ATUM orders are stored.', ATUM_TEXT_DOMAIN ),
			'public'              => FALSE,
			'show_ui'             => $is_user_allowed,
			'publicly_queryable'  => FALSE,
			'exclude_from_search' => TRUE,
			'show_in_menu'        => $is_user_allowed ? $main_menu_item['slug'] : FALSE,
			'hierarchical'        => FALSE,
			'show_in_nav_menus'   => FALSE,
			'rewrite'             => FALSE,
			'query_var'           => is_admin(),
			'supports'            => array( 'title', 'comments', 'custom-fields' ),
			'has_archive'         => FALSE,
			'capabilities'        => $this->capabilities
		), $args ));

		// Register the ATUM Order post type
		register_post_type( static::POST_TYPE, $args );


		// Register the post statuses
		$atum_statuses = (array) apply_filters( 'atum/order_post_type/register_post_statuses',
			array(
				ATUM_PREFIX . 'pending'   => array(
					'label'                     => _x( 'Pending', 'ATUM Order status', ATUM_TEXT_DOMAIN ),
					'public'                    => FALSE,
					'exclude_from_search'       => FALSE,
					'show_in_admin_all_list'    => TRUE,
					'show_in_admin_status_list' => TRUE,
					'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', ATUM_TEXT_DOMAIN )
				),
				ATUM_PREFIX . 'completed' => array(
					'label'                     => _x( 'Completed', 'ATUM Order status', ATUM_TEXT_DOMAIN ),
					'public'                    => FALSE,
					'exclude_from_search'       => FALSE,
					'show_in_admin_all_list'    => TRUE,
					'show_in_admin_status_list' => TRUE,
					'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', ATUM_TEXT_DOMAIN )
				)
			)
		);

		foreach ( $atum_statuses as $atum_status => $values ) {
			register_post_status( $atum_status, $values );
		}


		// Register the taxomony only if needed by the custom post type
		if ( defined('static::TAXONOMY') && ! empty(static::TAXONOMY) ) {

			$args = apply_filters( 'atum/order_post_type/taxonomy_args', wp_parse_args( array(
				'hierarchical'          => FALSE,
				'show_ui'               => FALSE,
				'show_in_nav_menus'     => FALSE,
				'query_var'             => is_admin(),
				'rewrite'               => FALSE,
				'public'                => FALSE,
				'update_count_callback' => array( $this, 'order_term_recount' ),
			), $args ));

			// Register the hidden order type taxonomy (if used)
			register_taxonomy( static::TAXONOMY, array( static::POST_TYPE ), $args );

		}

	}

	/**
	 * Method for recounting order terms (types)
	 *
	 * @since 1.2.9
	 *
	 * @param array  $terms
	 * @param string $taxonomy
	 */
	public function order_term_recount( $terms, $taxonomy ) {

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

			// Count all the orders with one of the ATUM Order's statuses (atum_pending or atum_completed)
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

		if ( $typenow == static::POST_TYPE ) {

			// Sorting
			if ( isset( $vars['orderby'] ) && 'total' == $vars['orderby'] ) {

				$vars = array_merge( $vars, array(
					'meta_key'  => '_total',
					'orderby'   => 'meta_value_num',
				) );

			}

			// Status
			if ( ! isset( $vars['post_status'] ) ) {

				// All the ATUM Order posts must have the custom statuses created for them
				$statuses = array(ATUM_PREFIX . 'pending', ATUM_PREFIX . 'completed');

				foreach ( $statuses as $key => $status ) {
					if ( isset( $wp_post_statuses[ $status ] ) && FALSE === $wp_post_statuses[ $status ]->show_in_admin_all_list ) {
						unset( $statuses[ $key ] );
					}
				}

				$vars['post_status'] =  $statuses;

			}

		}

		return $vars;
	}

	/**
	 * Customize the columns used in the ATUM Order's list table
	 *
	 * @since 1.2.4
	 *
	 * @param array $existing_columns
	 *
	 * @return array
	 */
	abstract public function add_columns($existing_columns);

	/**
	 * Output custom columns for ATUM Order's list table
	 *
	 * @since 1.2.4
	 *
	 * @param string $column
	 *
	 * @return bool True if the column is rendered or False if not
	 */
	public function render_columns($column) {

		global $post;
		$rendered = FALSE;

		switch ( $column ) {

			case 'status':

				$statuses = self::get_statuses();
				$atum_order = Helpers::get_atum_order_model( $post->ID );

				if (! is_wp_error($atum_order) ) {

					$status      = $atum_order->get_status();
					$status_name = isset( $statuses[ $status ] ) ? $statuses[ $status ] : '';

					printf( '<mark class="%s tips" data-tip="%s">%s</mark>', esc_attr( sanitize_html_class( $status ) ), esc_attr( $status_name ), esc_html( $status_name ) );

				}

				break;

			case 'atum_order_title':

				$author = $post->post_author;

				if ( $author ) {

					$user = get_user_by( 'id', $author );

					if ( is_a($user, '\WP_User') ) {
						$username = '<a href="user-edit.php?user_id=' . absint( $author ) . '">';
						$username .= esc_html( ucwords( $user->display_name ) );
						$username .= '</a>';
					}
					else {
						$username = __('User not found', ATUM_TEXT_DOMAIN);
					}

				}
				else {
					$username = 'ATUM';
				}

				printf(
					__( '%1$s by %2$s', ATUM_TEXT_DOMAIN ),
					'<a href="' . admin_url( 'post.php?post=' . absint( $post->ID ) . '&action=edit' ) . '" class="row-title"><strong>#' . esc_attr( $post->ID ) . '</strong></a>',
					$username
				);

				echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', ATUM_TEXT_DOMAIN ) . '</span></button>';
				$rendered = TRUE;

				break;

			case 'date':

				printf( '<time>%s</time>', date_i18n('Y-m-d', strtotime($post->post_date)) );
				$rendered = TRUE;

				break;

			case 'notes':

				if ( $post->comment_count && AtumCapabilities::current_user_can('read_order_notes') ) {

					// check the status of the post
					$status = ( 'trash' != $post->post_status ) ? '' : 'post-trashed';

					$latest_notes = get_comments( array(
						'post_id' => $post->ID,
						'number'  => 1,
						'status'  => $status,
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

				$rendered = TRUE;

				break;

			case 'total' :

				$atum_order = Helpers::get_atum_order_model( $post->ID );

				if ( ! is_wp_error($atum_order) ) {
					echo $atum_order->get_formatted_total();
				}

				break;

			case 'actions':

				$atum_order = Helpers::get_atum_order_model( $post->ID );

				if ( is_wp_error($atum_order) ) {
					break;
				}

				?><p><?php

				do_action( 'atum/order_post_type/admin_actions_start', $atum_order );

				$actions = array();
				$status = $atum_order->get_status();

				if ( $status == 'completed' ) {

					$actions['pending'] = array(
						'url'    => wp_nonce_url( admin_url( "admin-ajax.php?action=atum_order_mark_status&status=pending&atum_order_id=$post->ID" ), 'atum-order-mark-status' ),
						'name'   => __( 'Mark as Pending', ATUM_TEXT_DOMAIN ),
						'action' => 'pending',
					);

				}
				elseif ( $status == 'pending' ) {

					$actions['complete'] = array(
						'url'    => wp_nonce_url( admin_url( "admin-ajax.php?action=atum_order_mark_status&status=completed&atum_order_id=$post->ID" ), 'atum-order-mark-status' ),
						'name'   => __( 'Mark as Completed', ATUM_TEXT_DOMAIN ),
						'action' => 'complete',
					);

				}

				$actions['view'] = array(
					'url'    => admin_url( "post.php?post={$post->ID}&action=edit" ),
					'name'   => __( 'View', ATUM_TEXT_DOMAIN ),
					'action' => 'view',
				);

				$actions = apply_filters( 'atum/inventory_logs/admin_order_actions', $actions, $atum_order );

				foreach ( $actions as $action ) {
					printf( '<a class="button %s tips" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
				}

				do_action( 'atum/order_post_type/admin_actions_end', $atum_order ); ?>
				</p><?php

				break;

		}

		return $rendered;

	}

	/**
	 * Set row actions for ATUM Order's list table
	 *
	 * @since 1.2.4
	 *
	 * @param  array $actions
	 * @param  \WP_Post $post
	 *
	 * @return array
	 */
	public function row_actions( $actions, $post ) {

		if ( static::POST_TYPE === $post->post_type && isset( $actions['inline hide-if-no-js'] ) ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}

	/**
	 * Set the primary column for the ATUM Orders' table list
	 *
	 * @since 1.2.0
	 *
	 * @param string $default
	 * @param string $screen_id
	 *
	 * @return string
	 */
	public function list_table_primary_column($default, $screen_id) {

		if ( 'edit-' . static::POST_TYPE === $screen_id ) {
			return 'atum_order_title';
		}

		return $default;

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
			'atum_order_title' => 'ID',
			'total'            => 'total',
			'date'             => 'date',
		);

		unset( $columns['comments'] );

		return wp_parse_args( $custom, $columns );

	}

	/**
	 * Add the ATUM Order's meta boxes
	 *
	 * @since 1.2.9
	 */
	public function add_meta_boxes () {

		// Data meta box
		add_meta_box(
			'atum_order_data',
			! empty( $this->metabox_labels['data'] ) ? $this->metabox_labels['data'] : __('Data', ATUM_TEXT_DOMAIN),
			array($this, 'show_data_meta_box'),
			static::POST_TYPE,
			'normal',
			'high'
		);

		// Items meta box
		add_meta_box(
			'atum_order_items',
			! empty( $this->metabox_labels['items'] ) ? $this->metabox_labels['items'] : __('Items', ATUM_TEXT_DOMAIN),
			array($this, 'show_items_meta_box'),
			static::POST_TYPE,
			'normal',
			'high'
		);

		// Notes meta box
		if ( AtumCapabilities::current_user_can('read_order_notes') ) {

			add_meta_box(
				'atum_order_notes',
				! empty( $this->metabox_labels['notes'] ) ? $this->metabox_labels['notes'] : __( 'Notes', ATUM_TEXT_DOMAIN ),
				array( $this, 'show_notes_meta_box' ),
				static::POST_TYPE,
				'side',
				'default'
			);

		}

		// Actions meta box
		add_meta_box(
			'atum_order_actions',
			! empty( $this->metabox_labels['actions'] ) ? $this->metabox_labels['actions'] : __('Actions', ATUM_TEXT_DOMAIN),
			array($this, 'show_actions_meta_box'),
			static::POST_TYPE,
			'side',
			'high'
		);

		// Remove unneeded WP meta boxes
		remove_meta_box( 'commentsdiv', static::POST_TYPE, 'normal' );
		remove_meta_box( 'commentstatusdiv', static::POST_TYPE, 'normal' );
		remove_meta_box( 'slugdiv', static::POST_TYPE, 'normal' );
		remove_meta_box( 'submitdiv', static::POST_TYPE, 'side' );

	}

	/**
	 * Displays the Data meta box at ATUM Order posts
	 *
	 * @since 1.2.9
	 *
	 * @param \WP_Post $post
	 */
	abstract public function show_data_meta_box($post);

	/**
	 * Displays the Items meta box at ATUM Order posts
	 *
	 * @since 1.2.9
	 *
	 * @param \WP_Post $post
	 */
	public function show_items_meta_box( $post ) {

		$atum_order = $this->get_current_atum_order($post->ID);
		Helpers::load_view( 'meta-boxes/atum-order/items', compact('atum_order') );

	}

	/**
	 * Displays the Notes meta box at ATUM Order posts
	 *
	 * @since 1.2.9
	 *
	 * @param \WP_Post $post
	 */
	public function show_notes_meta_box( $post ) {
		Helpers::load_view( 'meta-boxes/atum-order/notes', compact('post') );
	}

	/**
	 * Displays the Actions meta box at ATUM Order posts
	 *
	 * @since 1.2.9
	 *
	 * @param \WP_Post $post
	 */
	public function show_actions_meta_box( $post ) {
		Helpers::load_view( 'meta-boxes/atum-order/actions', compact('post') );
	}

	/**
	 * Save the ATUM Order meta boxes
	 *
	 * @since 1.2.9
	 *
	 * @param int $atum_order_id
	 */
	abstract public function save_meta_boxes($atum_order_id);

	/**
	 * Removes ATUM Orders from the list of post types that support "View Mode" switching.
	 * View mode is seen on posts where you can switch between list or excerpt. Our post types don't support
	 * it, so we want to hide the useless UI from the screen options tab.
	 *
	 * @since 1.2.9
	 *
	 * @param  array $post_types Post types supporting view mode
	 *
	 * @return array
	 */
	public function disable_view_mode_options( $post_types ) {
		unset( $post_types[ static::POST_TYPE ] );
		return $post_types;
	}

	/**
	 * Disable the WP auto-save functionality for ATUM Orders
	 *
	 * @since 1.2.4
	 */
	public function disable_autosave() {
		global $post;

		if ( $post && get_post_type( $post->ID ) == static::POST_TYPE ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	/**
	 * Manipulate ATUM Orders' bulk actions
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

		$actions['mark_pending']   = __( 'Mark as Pending', ATUM_TEXT_DOMAIN );
		$actions['mark_completed'] = __( 'Mark as Completed', ATUM_TEXT_DOMAIN );

		return $actions;

	}

	/**
	 * Handle ATUM Orders' bulk actions
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
		if ( FALSE === strpos( $action, 'atum_order_mark_' ) ) {
			return $redirect_to;
		}

		$statuses      = self::get_statuses();
		$new_status    = substr( $action, 5 ); // Get the status name from action
		$report_action = 'marked_' . $new_status;

		// Sanity check: bail out if this is actually not a status, or is not a registered status
		if ( ! isset( $statuses[ $new_status ] ) ) {
			return $redirect_to;
		}

		$changed = 0;
		$ids = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {
			$atum_order = Helpers::get_atum_order_model( $id );
			$atum_order->update_status( $new_status, sprintf( __( '%s status changed by bulk edit:', ATUM_TEXT_DOMAIN ), $this->labels['singular_name']), TRUE );
			do_action( 'atum/order_post_type/edit_status', $id, $new_status );
			$changed++;
		}

		$redirect_to = add_query_arg( array(
			'post_type'    => static::POST_TYPE,
			$report_action => TRUE,
			'changed'      => $changed,
			'ids'          => join( ',', $ids ),
		), $redirect_to );

		return esc_url_raw( $redirect_to );

	}

	/**
	 * Show confirmation message that ATUM Order status changed for number of orders
	 *
	 * @since 1.2.4
	 */
	public function bulk_admin_notices() {

		global $post_type, $pagenow;

		// Bail out if not on Log list page
		if ( 'edit.php' != $pagenow || static::POST_TYPE != $post_type ) {
			return;
		}

		$statuses = self::get_statuses();

		// Check if any status changes happened
		foreach ( $statuses as $slug => $name ) {

			if ( isset( $_REQUEST[ 'marked_' . str_replace( ATUM_PREFIX, '', $slug ) ] ) ) {

				$number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
				$message = sprintf( _n( 'Status changed.', '%s statuses changed.', $number, ATUM_TEXT_DOMAIN ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . $message . '</p></div>';

				break;
			}
		}

	}

	/**
	 * Specify custom bulk actions messages for the ATUM Order post type
	 *
	 * @since 1.2.4
	 *
	 * @param  array $bulk_messages
	 * @param  array $bulk_counts
	 *
	 * @return array
	 */
	abstract public function bulk_post_updated_messages( $bulk_messages, $bulk_counts );

	/**
	 * Change messages when an ATUM Order post type is updated
	 *
	 * @since 1.2.9
	 *
	 * @param  array $messages
	 *
	 * @return array
	 */
	abstract public function post_updated_messages($messages);

	/**
	 * Enqueue the scripts
	 *
	 * @since 1.2.4
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts($hook) {

		global $post_type;

		if ($post_type == static::POST_TYPE) {

			global $wp_scripts, $post;

			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.11.4';
			wp_register_style( 'jquery-ui-style', "//code.jquery.com/ui/$jquery_version/themes/smoothness/jquery-ui.min.css", array(), $jquery_version );
			wp_register_style( 'atum-orders', ATUM_URL . 'assets/css/atum-orders.css', array('jquery-ui-style'), ATUM_VERSION );

			if ( in_array( $hook, ['post-new.php', 'post.php'] ) ) {

				wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', FALSE, ATUM_VERSION );
				wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', FALSE, ATUM_VERSION );
				Helpers::maybe_es6_promise();

				wp_enqueue_style( 'sweetalert2' );
				wp_enqueue_style( 'atum-orders' );

				if ( wp_script_is('es6-promise', 'registered') ) {
					wp_enqueue_script( 'es6-promise' );
				}

				// Enqueue the script with the required WooCommerce dependencies
				$wc_dependencies = (array) apply_filters('atum/order_post_type/scripts/woocommerce_dependencies', array(
					'wc-enhanced-select',
					'wc-backbone-modal',
					'jquery-blockui',
					'jquery-ui-datepicker',
					'stupidtable',
					'accounting',
					'sweetalert2'
				));

				wp_register_script( 'atum-orders', ATUM_URL . 'assets/js/atum.orders.js', $wc_dependencies, ATUM_VERSION, TRUE );

				wp_localize_script( 'atum-orders', 'atumOrder', array(
					'add_note_nonce'           => wp_create_nonce( 'add-atum-order-note' ),
					'delete_note_nonce'        => wp_create_nonce( 'delete-atum-order-note' ),
					'delete_note'              => __( 'Are you sure you wish to delete this note? This action cannot be undone.', ATUM_TEXT_DOMAIN ),
					'post_id'                  => isset( $post->ID ) ? $post->ID : '',
					'atum_order_item_nonce'    => wp_create_nonce( 'atum-order-item' ),
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
					'import_order_items_nonce' => wp_create_nonce( 'import-order-items' ),
					'are_you_sure'             => __( 'Are you sure?', ATUM_TEXT_DOMAIN ),
					'increase_stock_msg'       => __( 'This will increase the stock of the selected products by their quantity amount.', ATUM_TEXT_DOMAIN ),
					'decrease_stock_msg'       => __( 'This will decrease the stock of the selected products by their quantity amount.', ATUM_TEXT_DOMAIN ),
					'stock_increased'          => __( 'The stock was increased successfully', ATUM_TEXT_DOMAIN),
					'stock_decreased'          => __( 'The stock was decreased successfully', ATUM_TEXT_DOMAIN),
					'confirm_purchase_price'   => __( 'Do you want to set the purchase price of this product to {{number}}?', ATUM_TEXT_DOMAIN),
					'purchase_price_changed'   => __( 'The purchase price was changed successfully', ATUM_TEXT_DOMAIN),
					'remove_all_items_notice'  => __( 'This will remove all the items previously added to this order', ATUM_TEXT_DOMAIN ),
					'continue'                 => __( 'Continue', ATUM_TEXT_DOMAIN ),
					'cancel'                   => __( 'Cancel', ATUM_TEXT_DOMAIN ),
					'ok'                       => __( 'OK', ATUM_TEXT_DOMAIN ),
					'done'                     => __( 'Done!', ATUM_TEXT_DOMAIN ),
					'error'                    => __( 'Error!', ATUM_TEXT_DOMAIN )
				) );

				wp_enqueue_script( 'atum-orders' );

			}
			elseif ($hook == 'edit.php') {
				wp_enqueue_style( 'atum-orders' );
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

		if ($post_type == static::POST_TYPE && $pagenow == 'edit.php') {

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
	 * Remove WPML post type content setup box
	 *
	 * @since 1.3.7.1
	 */
	public function hide_multilingual_content_setup_box() {
		remove_meta_box('icl_div_config', convert_to_screen(static::POST_TYPE), 'normal');
	}
	
	/**
	 * Remove WPML language switcher if current one is an Atum Order post type screen
	 *
	 * @since 1.3.7.1
	 */
	public function remove_language_switcher() {
		
		global $sitepress, $pagenow;
		
		$is_order_post_type = (isset( $_GET['post_type']) &&   $_GET['post_type'] == static::POST_TYPE) ? TRUE : FALSE;
		$get_post      = isset( $_GET['post'] ) ? $_GET['post'] : false;
		$is_order_edit = $get_post && $pagenow == 'post.php' && get_post_type( $get_post ) == static::POST_TYPE ;

		if ( $is_order_post_type || $is_order_edit) {
			remove_action( 'wp_before_admin_bar_render', array($sitepress, 'admin_language_switcher') );
		}
		
	}

	/**
	 * Get the currently instantiated ATUM Order object (if any) or create a new one
	 *
	 * @since 1.2.9
	 *
	 * @param int $post_id
	 *
	 * @return AtumOrderModel
	 */
	abstract protected function get_current_atum_order($post_id);

	/**
	 * Getter for the ATUM Order post type name
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public static function get_post_type() {
		return static::POST_TYPE;
	}

	/**
	 * Getter for the ATUM Order taxonomy name
	 *
	 * @since 1.2.9
	 *
	 * @return string|bool
	 */
	public static function get_type_taxonomy() {
		return ! empty( static::TAXONOMY ) ? static::TAXONOMY : FALSE;
	}

	/**
	 * Get the available Inventory Logs statuses
	 *
	 * @since 1.2.9
	 *
	 * @return array
	 */
	public static function get_statuses() {

		return (array) apply_filters( 'atum/order_post_type/statuses', array(
			'pending'   => __( 'Pending', ATUM_TEXT_DOMAIN ),
			'completed' => __( 'Completed', ATUM_TEXT_DOMAIN )
		) );

	}

}