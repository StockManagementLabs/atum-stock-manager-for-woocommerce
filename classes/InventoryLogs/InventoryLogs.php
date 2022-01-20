<?php
/**
 * Inventory Logs main class
 *
 * @package         Atum
 * @subpackage      InventoryLogs
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\InventoryLogs;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Helpers;
use Atum\InventoryLogs\Models\Log;


class InventoryLogs extends AtumOrderPostType {

	/**
	 * The singleton instance holder
	 *
	 * @var InventoryLogs
	 */
	private static $instance;

	/**
	 * The query var name used in list searches
	 *
	 * @var string
	 */
	protected $search_label = ATUM_PREFIX . 'log_search';

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
	const MENU_ORDER = 5;

	/**
	 * Will hold the current log object
	 *
	 * @var Log
	 */
	private $log;

	/**
	 * The capabilities used when registering the post type
	 *
	 * @var array
	 */
	protected $capabilities = array(
		'edit_post'          => 'edit_inventory_log',
		'read_post'          => 'read_inventory_log',
		'delete_post'        => 'delete_inventory_log',
		'edit_posts'         => 'edit_inventory_logs',
		'edit_others_posts'  => 'edit_others_inventory_logs',
		'read_private_posts' => 'read_private_inventory_logs',
		'publish_posts'      => 'publish_inventory_logs',
		'create_posts'       => 'create_inventory_logs',
		'delete_posts'       => 'delete_inventory_logs',
		'delete_other_posts' => 'delete_other_inventory_logs',
	);


	/**
	 * InventoryLogs singleton constructor
	 *
	 * @since 1.2.4
	 */
	private function __construct() {

		// Set post type labels.
		$this->labels = array(
			'name'                  => __( 'Inventory Logs', ATUM_TEXT_DOMAIN ),
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralContext
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

		// Set meta box labels.
		$this->metabox_labels = array(
			'data'    => __( 'Log Data', ATUM_TEXT_DOMAIN ),
			'notes'   => __( 'Log Notes', ATUM_TEXT_DOMAIN ),
			'actions' => __( 'Log Actions', ATUM_TEXT_DOMAIN ),
		);

		// Initialize.
		$this->init();

		// Add item order.
		add_filter( 'atum/admin/menu_items_order', array( $this, 'add_item_order' ) );

		// Add the "Inventory Logs" link to the ATUM's admin bar menu.
		add_filter( 'atum/admin/top_bar/menu_items', array( $this, 'add_admin_bar_link' ) );

		// Add the filters to the post type list table.
		add_action( 'restrict_manage_posts', array( $this, 'add_log_filters' ) );

		// Add the help tab to Inventory Logs' list page.
		add_action( 'load-edit.php', array( $this, 'add_help_tab' ) );

		// Add custom search for ILs.
		add_action( 'atum/' . self::POST_TYPE . '/search_results', array( $this, 'il_search' ), 10, 3 );
		add_filter( 'atum/' . self::POST_TYPE . '/search_fields', array( $this, 'search_fields' ) );

		// Add the buttons for increasing/decreasing the Log products' stock.
		add_action( 'atum/atum_order/item_bulk_controls', array( $this, 'add_stock_buttons' ) );
		
	}

	/**
	 * Displays the data meta box at Inventory Logs
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_Post $post
	 */
	public function show_data_meta_box( $post ) {

		$atum_order = $this->get_current_atum_order( $post->ID, TRUE );

		if ( ! $atum_order instanceof Log ) {
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
	 * @param int $po_id
	 */
	public function save_meta_boxes( $po_id ) {

		if ( ! isset( $_POST['atum_order_type'], $_POST['status'], $_POST['atum_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['atum_meta_nonce'], 'atum_save_meta_data' ) ) {
			return;
		}

		$log = $this->get_current_atum_order( $po_id, TRUE );

		if ( empty( $log ) ) {
			return;
		}

		// Avoid maximum function nesting on some cases.
		remove_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ) );

		$log_type = wc_clean( $_POST['atum_order_type'] );

		/**
		 * Set the log dates
		 *
		 * @var string $reservation_date
		 * @var string $return_date
		 * @var string $damage_date
		 */
		$log_timestamp = empty( $_POST['date'] ) ? Helpers::get_current_timestamp() : strtotime( $_POST['date'] . ' ' . (int) $_POST['date_hour'] . ':' . (int) $_POST['date_minute'] . ':00' );
		$log_date      = Helpers::date_format( $log_timestamp );

		foreach ( [ 'reservation_date', 'return_date', 'damage_date' ] as $date_field ) {
			${$date_field} = empty( $_POST[ $date_field ] ) ? '' : Helpers::date_format( strtotime( $_POST[ $date_field ] . ' ' . (int) $_POST[ "{$date_field}_hour" ] . ':' . (int) $_POST[ "{$date_field}_minute" ] . ':00' ) );
		}

		$log->set_props( array(
			'type'             => $log_type,
			'status'           => $_POST['status'],
			'order'            => ! empty( $_POST['wc_order'] ) ? $_POST['wc_order'] : '',
			'date_created'     => $log_date,
			'reservation_date' => $reservation_date,
			'return_date'      => $return_date,
			'damage_date'      => $damage_date,
			'shipping_company' => $_POST['shipping_company'],
			'custom_name'      => $_POST['custom_name'],
		) );

		// Add the Log post to the appropriate Log Type taxonomy.
		if ( in_array( $log_type, array_keys( Log::get_log_types() ) ) ) {
			wp_set_object_terms( $po_id, $log_type, self::TAXONOMY );
			
			// Update all Log counters.
			$get_terms_args = array(
				'taxonomy'   => self::TAXONOMY,
				'fields'     => 'ids',
				'hide_empty' => FALSE,
			);
			$update_terms   = get_terms( $get_terms_args );
			wp_update_term_count_now( $update_terms, self::TAXONOMY );
		}

		// Set the Log description as post content.
		$log->set_description( $_POST['description'] );

		// In case the user changed any order item and not used the "Save Items" button.
		$log->save_posted_order_items();

		$log->save();

	}

	/**
	 * Add the filtering dropdowns to the Inventory Logs post type table
	 *
	 * @since 1.2.4
	 */
	public function add_log_filters() {

		global $typenow;

		if ( self::POST_TYPE === $typenow ) {

			wp_dropdown_categories( array(
				'show_option_all' => __( 'Show All Log Types', ATUM_TEXT_DOMAIN ),
				'taxonomy'        => self::TAXONOMY,
				'name'            => self::TAXONOMY,
				'orderby'         => 'name',
				'selected'        => isset( $_GET[ self::TAXONOMY ] ) ? esc_attr( $_GET[ self::TAXONOMY ] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'hierarchical'    => FALSE,
				'show_count'      => TRUE,
				'hide_empty'      => FALSE,
				'value_field'     => 'slug',
			) );

		}

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
	public function add_columns( $existing_columns ) {

		return array(
			'cb'               => $existing_columns['cb'],
			'atum_order_title' => __( 'Log', ATUM_TEXT_DOMAIN ),
			'status'           => __( 'Status', ATUM_TEXT_DOMAIN ),
			'type'             => __( 'Type', ATUM_TEXT_DOMAIN ),
			'date_created'     => __( 'Created', ATUM_TEXT_DOMAIN ),
			'last_modified'    => __( 'Last Modified', ATUM_TEXT_DOMAIN ),
			'wc_order'         => __( 'Order', ATUM_TEXT_DOMAIN ),
			'total'            => __( 'Total', ATUM_TEXT_DOMAIN ),
			'actions'          => __( 'Actions', ATUM_TEXT_DOMAIN ),
		);

	}

	/**
	 * Output custom columns for ATUM Order's list table
	 *
	 * @since 1.2.4
	 *
	 * @param string $column
	 *
	 * @return void
	 */
	public function render_columns( $column ) {

		global $post;

		$rendered = parent::render_columns( $column );

		if ( $rendered ) {
			return;
		}

		$log = $this->get_current_atum_order( $post->ID, FALSE );

		switch ( $column ) {

			case 'type':
				$types    = Log::get_log_types();
				$log_type = $log->type;

				if ( in_array( $log_type, array_keys( $types ) ) ) {
					echo $types[ $log_type ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

					if ( 'other' === $log_type ) {
						$custom_name = $log->custom_name;

						if ( $custom_name ) {
							echo ': <small><strong>' . esc_html( $custom_name ) . '</strong></small>';
						}
					}
				}

				break;

			case 'wc_order':
				$log_order = $log->get_order();

				echo $log_order ? '<a href="' . admin_url( 'post.php?post=' . absint( $log_order->get_id() ) . '&action=edit' ) . '" target="_blank">' . esc_attr__( 'Order #', ATUM_TEXT_DOMAIN ) . $log_order->get_id() . '</a>' : '&ndash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

		}

	}

	/**
	 * Add sortable IL columns to the list
	 *
	 * @since 1.8.2
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function sortable_columns( $columns ) {

		$columns = parent::sortable_columns( $columns );

		$columns['type']     = 'type';
		$columns['wc_order'] = 'wc_order';

		return $columns;
	}

	/**
	 * Filters and sorting handler for IL columns
	 *
	 * @since 1.8.2
	 *
	 * @param  array $query_vars
	 *
	 * @return array
	 */
	public function request_query( $query_vars ) {

		global $typenow;

		$query_vars = parent::request_query( $query_vars );

		if ( self::POST_TYPE === $typenow ) {

			if ( isset( $query_vars['orderby'] ) ) {

				// Sort by "Type".
				if ( 'type' === $query_vars['orderby'] ) {

					$query_vars = array_merge( $query_vars, array(
						'meta_key' => '_type',
						'orderby'  => 'meta_value',
					) );

				}
				// Sort by "WC Order".
				elseif ( 'wc_order' === $query_vars['orderby'] ) {

					$query_vars = array_merge( $query_vars, array(
						'meta_key' => '_order',
						'orderby'  => 'meta_value',
					) );

				}

			}

		}

		return $query_vars;

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
	public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {

		$bulk_messages[ self::POST_TYPE ] = array(
			/* translators: the number of logs updated */
			'updated'   => _n( '%s log updated.', '%s logs updated.', $bulk_counts['updated'], ATUM_TEXT_DOMAIN ),
			/* translators: the number of logs locked */
			'locked'    => _n( '%s log not updated, somebody is editing it.', '%s logs not updated, somebody is editing them.', $bulk_counts['locked'], ATUM_TEXT_DOMAIN ),
			/* translators: the number of logs deleted */
			'deleted'   => _n( '%s log permanently deleted.', '%s logs permanently deleted.', $bulk_counts['deleted'], ATUM_TEXT_DOMAIN ),
			/* translators: the number of logs moved to the trash */
			'trashed'   => _n( '%s log moved to the Trash.', '%s logs moved to the Trash.', $bulk_counts['trashed'], ATUM_TEXT_DOMAIN ),
			/* translators: the number of restored from the trash */
			'untrashed' => _n( '%s log restored from the Trash.', '%s logs restored from the Trash.', $bulk_counts['untrashed'], ATUM_TEXT_DOMAIN ),
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
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Log updated.', ATUM_TEXT_DOMAIN ),
			2  => __( 'Custom field updated.', ATUM_TEXT_DOMAIN ),
			3  => __( 'Custom field deleted.', ATUM_TEXT_DOMAIN ),
			4  => __( 'Log updated.', ATUM_TEXT_DOMAIN ),
			/* translators: the revision name */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Log restored to revision from %s', ATUM_TEXT_DOMAIN ), wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			6  => __( 'Log updated.', ATUM_TEXT_DOMAIN ),
			7  => __( 'Log saved.', ATUM_TEXT_DOMAIN ),
			8  => __( 'Log submitted.', ATUM_TEXT_DOMAIN ),
			/* translators: the log's schedule date */
			9  => sprintf( __( 'Log scheduled for: <strong>%1$s</strong>.', ATUM_TEXT_DOMAIN ), date_i18n( __( 'M j, Y @ G:i', ATUM_TEXT_DOMAIN ), strtotime( $post->post_date ) ) ),
			10 => __( 'Log draft updated.', ATUM_TEXT_DOMAIN ),
			11 => __( 'Log updated and email sent.', ATUM_TEXT_DOMAIN ),
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
			'slug'       => ATUM_SHORT_NAME . '-inventory-logs',
			'title'      => $this->labels['menu_name'],
			'href'       => 'edit.php?post_type=' . self::POST_TYPE,
			'menu_order' => self::MENU_ORDER,
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
			'menu_order' => self::MENU_ORDER,
		);
		
		return $items_order;
		
	}

	/**
	 * Get the currently instantiated Log object (if any) or create a new one
	 *
	 * @since 1.2.4
	 *
	 * @param int  $post_id
	 * @param bool $read_items
	 *
	 * @return Log
	 */
	public function get_current_atum_order( $post_id, $read_items ) {

		if ( ! $this->log || $this->log->get_id() != $post_id ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$this->log = Helpers::get_atum_order_model( $post_id, $read_items, self::POST_TYPE );
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

		if ( $screen && FALSE !== strpos( $screen->id, self::POST_TYPE ) ) {

			$help_tabs = array(
				array(
					'name'  => 'columns',
					'title' => __( 'Columns', ATUM_TEXT_DOMAIN ),
				),
			);

			Helpers::add_help_tab( $help_tabs, $this );

		}

	}

	/**
	 * Display the help tabs' content
	 *
	 * @since 1.3.0
	 *
	 * @param \WP_Screen $screen    The current screen.
	 * @param array      $tab       The current help tab.
	 */
	public function help_tabs_content( $screen, $tab ) {

		Helpers::load_view( 'help-tabs/inventory-logs/' . $tab['name'] );
	}

	/**
	 * Set the custom fields that are available for searches within the IL's list
	 *
	 * @since 1.6.1
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function search_fields( $fields ) {

		// NOTE: For now we are going to support searches within the custom columns displayed on the ILs list.
		return array_merge( $fields, [ '_order', '_total', '_type', '_custom_name' ] );

	}

	/**
	 * Search within custom fields on IL searches
	 *
	 * @since 1.6.1
	 *
	 * @param array $atum_order_ids
	 * @param mixed $term
	 * @param array $search_fields
	 *
	 * @return array
	 */
	public function il_search( $atum_order_ids, $term, $search_fields ) {

		global $wpdb;

		// Dates: search in post_modified and date_expected dates.
		if ( strtotime( $term ) ) {

			// Format the date in MySQL format.
			$date = Helpers::date_format( strtotime( $term ), TRUE, TRUE );
			$term = "%$date%";

			$ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT ID FROM $wpdb->posts p
				WHERE post_date_gmt LIKE %s AND post_type = %s			
			", $term, self::POST_TYPE ) );

			$atum_order_ids = array_unique( array_merge( $atum_order_ids, $ids ) );

		}

		return $atum_order_ids;

	}

	/**
	 * Add the buttons for increasing/decreasing the Log products' stock
	 *
	 * @since 1.3.0
	 */
	public function add_stock_buttons() {
		?>
		<button type="button" class="button bulk-increase-stock"><?php esc_attr_e( 'Increase Stock', ATUM_TEXT_DOMAIN ); ?></button>
		<button type="button" class="button bulk-decrease-stock"><?php esc_attr_e( 'Reduce Stock', ATUM_TEXT_DOMAIN ); ?></button>
		<?php
	}

	/**
	 * Get the available Inventory Logs statuses
	 *
	 * @since 1.2.9
	 *
	 * @return array
	 */
	public static function get_statuses() {

		return (array) apply_filters( 'atum/inventory_logs/statuses', array(
			'atum_pending'   => _x( 'Pending', 'ATUM Inventory Log status', ATUM_TEXT_DOMAIN ),
			'atum_completed' => _x( 'Completed', 'ATUM Inventory Log status', ATUM_TEXT_DOMAIN ),
		) );

	}

	/**
	 * Get the colors for every Inventory Log status
	 *
	 * @since 1.8.2
	 *
	 * @return array
	 */
	public static function get_status_colors() {

		return (array) apply_filters( 'atum/inventory_logs/status_colors', array(
			'atum_pending'   => '#ff4848',
			'atum_completed' => '#69c61d',
		) );

	}

	/****************************
	 * Instance methods
	 ****************************/

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
	 * @return InventoryLogs instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
