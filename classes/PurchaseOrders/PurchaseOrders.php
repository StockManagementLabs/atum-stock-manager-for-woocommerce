<?php
/**
 * @package         Atum
 * @subpackage      PurchaseOrders
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.9
 *
 * Purchase Orders main class
 */

namespace Atum\PurchaseOrders;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Helpers;
use Atum\PurchaseOrders\Models\PurchaseOrder;


class PurchaseOrders extends AtumOrderPostType {

	/**
	 * The Purchase Order post type name
	 */
	const POST_TYPE = ATUM_PREFIX . 'purchase_order';
	
	/**
	 * The menu order
	 */
	const MENU_ORDER = 30;
	
	/**
	 * Will hold the current purchase order object
	 * @var PurchaseOrder
	 */
	private $po;

	/**
	 * The capabilities used when registering the post type
	 * @var array
	 */
	protected $capabilities = array(
		'edit_post'              => 'edit_purchase_order',
		'read_post'              => 'read_purchase_order',
		'delete_post'            => 'delete_purchase_order',
		'edit_posts'             => 'edit_purchase_orders',
		'edit_others_posts'      => 'edit_others_purchase_orders',
		'create_posts'           => 'create_purchase_orders',
		'delete_posts'           => 'delete_purchase_orders',
		'delete_other_posts'     => 'delete_other_purchase_orders'
	);


	/**
	 * PurchaseOrders constructor
	 *
	 * @since 1.2.9
	 */
	public function __construct() {

		// Set post type labels
		$this->labels = array(
			'name'                  => __( 'Purchase Orders', ATUM_TEXT_DOMAIN ),
			'singular_name'         => _x( 'Purchase Order', self::POST_TYPE . ' post type singular name', ATUM_TEXT_DOMAIN ),
			'add_new'               => __( 'Add New PO', ATUM_TEXT_DOMAIN ),
			'add_new_item'          => __( 'Add New PO', ATUM_TEXT_DOMAIN ),
			'edit'                  => __( 'Edit', ATUM_TEXT_DOMAIN ),
			'edit_item'             => __( 'Edit PO', ATUM_TEXT_DOMAIN ),
			'new_item'              => __( 'New PO', ATUM_TEXT_DOMAIN ),
			'view'                  => __( 'View PO', ATUM_TEXT_DOMAIN ),
			'view_item'             => __( 'View PO', ATUM_TEXT_DOMAIN ),
			'search_items'          => __( 'Search POs', ATUM_TEXT_DOMAIN ),
			'not_found'             => __( 'No purchase orders found', ATUM_TEXT_DOMAIN ),
			'not_found_in_trash'    => __( 'No purchase orders found in trash', ATUM_TEXT_DOMAIN ),
			'parent'                => __( 'Parent purchase order', ATUM_TEXT_DOMAIN ),
			'menu_name'             => _x( 'Purchase Orders', 'Admin menu name', ATUM_TEXT_DOMAIN ),
			'filter_items_list'     => __( 'Filter purchase orders', ATUM_TEXT_DOMAIN ),
			'items_list_navigation' => __( 'Purchase orders navigation', ATUM_TEXT_DOMAIN ),
			'items_list'            => __( 'Purchase orders list', ATUM_TEXT_DOMAIN ),
		);

		// Set meta box labels
		$this->metabox_labels = array(
			'data'    => __( 'PO Data', ATUM_TEXT_DOMAIN ),
			'notes'   => __( 'PO Notes', ATUM_TEXT_DOMAIN ),
			'actions' => __( 'PO Actions', ATUM_TEXT_DOMAIN )
		);

		// Initialize
		parent::__construct();

		// Add item order
		add_filter( 'atum/admin/menu_items_order', array( $this, 'add_item_order' ) );

		// Add the "Purchase Orders" link to the ATUM's admin bar menu
		add_filter( 'atum/admin/top_bar/menu_items', array( $this, 'add_admin_bar_link' ), 11 );

		// Add the help tab to PO list page
		add_action( 'load-edit.php', array( $this, 'add_help_tab' ) );

	}

	/**
	 * Displays the data meta box at Purchase Orders
	 *
	 * @since 1.2.9
	 *
	 * @param \WP_Post $post
	 */
	public function show_data_meta_box( $post ) {

		$atum_order = $this->get_current_atum_order($post->ID);

		if ( ! is_a( $atum_order, 'Atum\PurchaseOrders\Models\PurchaseOrder' ) ) {
			return;
		}

		$atum_order_post = $atum_order->get_post();
		$supplier        = $atum_order->get_supplier();
		$labels          = $this->labels;


		wp_nonce_field( 'atum_save_meta_data', 'atum_meta_nonce' );

		Helpers::load_view( 'meta-boxes/purchase-order/data', compact( 'atum_order', 'supplier', 'atum_order_post', 'labels' ) );

	}

	/**
	 * @inheritdoc
	 */
	public function save_meta_boxes($log_id) {

		if ( ! isset( $_POST['status'], $_POST['atum_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['atum_meta_nonce'], 'atum_save_meta_data' ) ) {
			return;
		}

		$log = $this->get_current_atum_order($log_id);

		if ( empty($log) ) {
			return;
		}

		$po_date = ( empty( $_POST['date'] ) ) ? current_time( 'timestamp', TRUE ) : strtotime( $_POST['date'] . ' ' . (int) $_POST['date_hour'] . ':' . (int) $_POST['date_minute'] . ':00' );
		$po_date = date_i18n('Y-m-d H:i:s', $po_date);

		$expected_at_location_date = ( empty( $_POST['expected_at_location_date'] ) ) ? current_time( 'timestamp', TRUE ) : strtotime( $_POST['expected_at_location_date'] . ' ' . (int) $_POST['expected_at_location_date_hour'] . ':' . (int) $_POST['expected_at_location_date_minute'] . ':00' );
		$expected_at_location_date = date_i18n( 'Y-m-d H:i:s', $expected_at_location_date);

		$log->save_meta( array(
			'_status'                    => esc_attr( $_POST['status'] ),
			'_date_created'              => $po_date,
			'_supplier'                  => absint( $_POST['supplier'] ),
			'_expected_at_location_date' => $expected_at_location_date,
		) );

		// Set the Log description as post content
		$log->set_description( $_POST['description'] );

		$log->save();

	}

	/**
	 * @inheritdoc
	 */
	public function add_columns($existing_columns) {

		$columns = array(
			'cb'               => $existing_columns['cb'],
			'status'           => '<span class="status_head tips" data-tip="' . esc_attr__( 'PO Status', ATUM_TEXT_DOMAIN ) . '">' . esc_attr__( 'Status', ATUM_TEXT_DOMAIN ) . '</span>',
			'atum_order_title' => __( 'PO', ATUM_TEXT_DOMAIN ),
			'supplier'         => __( 'Supplier', ATUM_TEXT_DOMAIN ),
			'notes'            => '<span class="notes_head tips" data-tip="' . esc_attr__( 'PO Notes', ATUM_TEXT_DOMAIN ) . '">' . esc_attr__( 'Notes', ATUM_TEXT_DOMAIN ) . '</span>',
			'date'             => __( 'Date', ATUM_TEXT_DOMAIN ),
			'expected_date'    => __( 'Date Expected', ATUM_TEXT_DOMAIN ),
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

		$po = $this->get_current_atum_order($post->ID);

		switch ( $column ) {

			case 'supplier':

				$supplier = $po->get_supplier();

				if ($supplier) {
					echo $supplier->post_title;
				}
				break;

			case 'expected_date':

				echo $po->get_expected_at_location_date();
				break;

		}

	}

	/**
	 * Specify custom bulk actions messages for the PO post type
	 *
	 * @since 1.2.9
	 *
	 * @param  array $bulk_messages
	 * @param  array $bulk_counts
	 *
	 * @return array
	 */
	public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {

		$bulk_messages[ self::POST_TYPE ] = array(
			'updated'   => _n( '%s PO updated.', '%s logs updated.', $bulk_counts['updated'], ATUM_TEXT_DOMAIN ),
			'locked'    => _n( '%s PO not updated, somebody is editing it.', '%s POs not updated, somebody is editing them.', $bulk_counts['locked'], ATUM_TEXT_DOMAIN ),
			'deleted'   => _n( '%s PO permanently deleted.', '%s POs permanently deleted.', $bulk_counts['deleted'], ATUM_TEXT_DOMAIN ),
			'trashed'   => _n( '%s PO moved to the Trash.', '%s POs moved to the Trash.', $bulk_counts['trashed'], ATUM_TEXT_DOMAIN ),
			'untrashed' => _n( '%s PO restored from the Trash.', '%s POs restored from the Trash.', $bulk_counts['untrashed'], ATUM_TEXT_DOMAIN )
		);

		return $bulk_messages;
	}

	/**
	 * Change messages when a PO post type is updated
	 *
	 * @since 1.2.9
	 *
	 * @param  array $messages
	 *
	 * @return array
	 */
	public function post_updated_messages( $messages ) {

		global $post;

		$messages[ self::POST_TYPE ] = array(
			0  => '', // Unused. Messages start at index 1
			1  => __( 'PO updated.', ATUM_TEXT_DOMAIN ),
			2  => __( 'Custom field updated.', ATUM_TEXT_DOMAIN ),
			3  => __( 'Custom field deleted.', ATUM_TEXT_DOMAIN ),
			4  => __( 'PO updated.', ATUM_TEXT_DOMAIN ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'PO restored to revision from %s', ATUM_TEXT_DOMAIN ), wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE,
			6  => __( 'PO updated.', ATUM_TEXT_DOMAIN ),
			7  => __( 'PO saved.', ATUM_TEXT_DOMAIN ),
			8  => __( 'PO submitted.', ATUM_TEXT_DOMAIN ),
			9  => sprintf( __( 'PO scheduled for: <strong>%1$s</strong>.', ATUM_TEXT_DOMAIN ), date_i18n( __( 'M j, Y @ G:i', ATUM_TEXT_DOMAIN ), strtotime( $post->post_date ) ) ),
			10 => __( 'PO draft updated.', ATUM_TEXT_DOMAIN ),
			11 => __( 'PO updated and email sent.', ATUM_TEXT_DOMAIN )
		);

		return $messages;
	}

	/**
	 * Add the Purchase Orders link to the ATUM's admin bar menu
	 *
	 * @since 1.2.9
	 *
	 * @param array $atum_menus
	 *
	 * @return array
	 */
	public function add_admin_bar_link($atum_menus) {

		$atum_menus['purchase-orders'] = array(
			'slug'       => ATUM_TEXT_DOMAIN . '-purchase-orders',
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
	 * Get the currently instantiated PO object (if any) or create a new one
	 *
	 * @since 1.2.9
	 *
	 * @param int $post_id
	 *
	 * @return PurchaseOrder
	 */
	protected function get_current_atum_order($post_id) {

		if ( ! $this->po || $this->po->get_id() != $post_id  ) {
			$this->po = new PurchaseOrder( $post_id );
		}

		return $this->po;

	}

	/**
	 * Add the help tab to the PO list page
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

		Helpers::load_view( 'help-tabs/purchase-orders/' . $tab['name'] );
	}

}