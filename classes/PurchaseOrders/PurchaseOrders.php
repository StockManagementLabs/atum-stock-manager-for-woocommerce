<?php
/**
 * Purchase Orders main class
 *
 * @package         Atum
 * @subpackage      PurchaseOrders
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\PurchaseOrders;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\PurchaseOrders\Exports\POExport;
use Atum\Inc\Helpers;
use Atum\Inc\Hooks;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\Suppliers\Suppliers;
use Mpdf\Mpdf;
use Mpdf\MpdfException;


class PurchaseOrders extends AtumOrderPostType {

	/**
	 * The query var name used in list searches
	 *
	 * @var string
	 */
	protected $search_label = ATUM_PREFIX . 'po_search';

	/**
	 * The Purchase Order post type name
	 */
	const POST_TYPE = ATUM_PREFIX . 'purchase_order';
	
	/**
	 * The menu order
	 */
	const MENU_ORDER = 3;
	
	/**
	 * Will hold the current purchase order object
	 *
	 * @var PurchaseOrder
	 */
	private $po;

	/**
	 * The capabilities used when registering the post type
	 *
	 * @var array
	 */
	protected $capabilities = array(
		'edit_post'          => 'edit_purchase_order',
		'read_post'          => 'read_purchase_order',
		'delete_post'        => 'delete_purchase_order',
		'edit_posts'         => 'edit_purchase_orders',
		'edit_others_posts'  => 'edit_others_purchase_orders',
		'create_posts'       => 'create_purchase_orders',
		'delete_posts'       => 'delete_purchase_orders',
		'delete_other_posts' => 'delete_other_purchase_orders',
	);
	
	/**
	 * PurchaseOrders constructor
	 *
	 * @since 1.2.9
	 */
	public function __construct() {

		// Set post type labels.
		$this->labels = array(
			'name'                  => __( 'Purchase Orders', ATUM_TEXT_DOMAIN ),
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralContext
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

		// Set meta box labels.
		$this->metabox_labels = array(
			'data'    => __( 'PO Data', ATUM_TEXT_DOMAIN ),
			'notes'   => __( 'PO Notes', ATUM_TEXT_DOMAIN ),
			'actions' => __( 'PO Actions', ATUM_TEXT_DOMAIN ),
		);

		// Initialize.
		parent::__construct();

		// Add item order.
		add_filter( 'atum/admin/menu_items_order', array( $this, 'add_item_order' ) );

		// Add the "Purchase Orders" link to the ATUM's admin bar menu.
		add_filter( 'atum/admin/top_bar/menu_items', array( $this, 'add_admin_bar_link' ), 11 );

		// Add the help tab to PO list page.
		add_action( 'load-edit.php', array( $this, 'add_help_tab' ) );
		
		// Add pdf Purchase Order print.
		add_filter( 'atum/' . self::POST_TYPE . '/admin_order_actions', array( $this, 'add_generate_pdf' ), 10, 2 );

		// Generate Purchase Order's PDF.
		add_action( 'wp_ajax_atum_order_pdf', array( $this, 'generate_order_pdf' ) );

		// Add the hooks for the Purchase Price field.
		Hooks::get_instance()->purchase_price_hooks();

	}

	/**
	 * Displays the data meta box at Purchase Orders
	 *
	 * @since 1.2.9
	 *
	 * @param \WP_Post $post
	 */
	public function show_data_meta_box( $post ) {

		$atum_order = $this->get_current_atum_order( $post->ID );

		if ( ! is_a( $atum_order, 'Atum\PurchaseOrders\Models\PurchaseOrder' ) ) {
			return;
		}

		$atum_order_post        = $atum_order->get_post();
		$supplier               = $atum_order->get_supplier();
		$has_multiple_suppliers = $atum_order->has_multiple_suppliers();
		$labels                 = $this->labels;

		wp_nonce_field( 'atum_save_meta_data', 'atum_meta_nonce' );

		Helpers::load_view( 'meta-boxes/purchase-order/data', compact( 'atum_order', 'supplier', 'has_multiple_suppliers', 'atum_order_post', 'labels' ) );

	}

	/**
	 * Save the ATUM Order meta boxes
	 *
	 * @since 1.2.9
	 *
	 * @param int $log_id
	 */
	public function save_meta_boxes( $log_id ) {

		if ( ! isset( $_POST['status'], $_POST['atum_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['atum_meta_nonce'], 'atum_save_meta_data' ) ) {
			return;
		}

		$log = $this->get_current_atum_order( $log_id );

		if ( empty( $log ) ) {
			return;
		}

		$po_date = empty( $_POST['date'] ) ? current_time( 'timestamp', TRUE ) : strtotime( $_POST['date'] . ' ' . (int) $_POST['date_hour'] . ':' . (int) $_POST['date_minute'] . ':00' );
		$po_date = date( 'Y-m-d H:i:s', $po_date );

		$expected_at_location_date = empty( $_POST['expected_at_location_date'] ) ? current_time( 'timestamp', TRUE ) : strtotime( $_POST['expected_at_location_date'] . ' ' . (int) $_POST['expected_at_location_date_hour'] . ':' . (int) $_POST['expected_at_location_date_minute'] . ':00' );
		$expected_at_location_date = date( 'Y-m-d H:i:s', $expected_at_location_date );

		$multiple_suppliers = ( isset( $_POST['multiple_suppliers'] ) && 'yes' === $_POST['multiple_suppliers'] ) ? 'yes' : 'no';

		$log->save_meta( array(
			'_status'                    => esc_attr( $_POST['status'] ),
			'_date_created'              => $po_date,
			Suppliers::SUPPLIER_META_KEY => 'no' === $multiple_suppliers ? absint( $_POST['supplier'] ) : '',
			'_multiple_suppliers'        => $multiple_suppliers,
			'_expected_at_location_date' => $expected_at_location_date,
		) );

		// Set the Log description as post content.
		$log->set_description( $_POST['description'] );

		$log->save();

	}

	/**
	 * Customize the columns used in the ATUM Order's list table
	 *
	 * @since 1.2.9
	 *
	 * @param array $existing_columns
	 *
	 * @return array
	 */
	public function add_columns( $existing_columns ) {

		$columns = array(
			'cb'               => $existing_columns['cb'],
			'atum_order_title' => __( 'PO', ATUM_TEXT_DOMAIN ),
			'date'             => __( 'Date', ATUM_TEXT_DOMAIN ),
			'status'           => __( 'Status', ATUM_TEXT_DOMAIN ),
			'supplier'         => __( 'Supplier', ATUM_TEXT_DOMAIN ),
			'expected_date'    => __( 'Date Expected', ATUM_TEXT_DOMAIN ),
			'total'            => __( 'Total', ATUM_TEXT_DOMAIN ),
			'actions'          => __( 'Actions', ATUM_TEXT_DOMAIN ),
		);

		return $columns;

	}

	/**
	 * Output custom columns for ATUM Order's list table
	 *
	 * @since 1.2.9
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

		$po = $this->get_current_atum_order( $post->ID );

		switch ( $column ) {

			case 'supplier':
				$supplier = $po->get_supplier();
				
				if ( $supplier ) {
					echo esc_attr( $supplier->post_title );
				}
				break;

			case 'expected_date':
				echo esc_attr( $po->get_expected_at_location_date() );
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
			/* translators: the number of purchase orders updated */
			'updated'   => _n( '%s PO updated.', '%s POs updated.', $bulk_counts['updated'], ATUM_TEXT_DOMAIN ),
			/* translators: the number of purchase orders locked */
			'locked'    => _n( '%s PO not updated, somebody is editing it.', '%s POs not updated, somebody is editing them.', $bulk_counts['locked'], ATUM_TEXT_DOMAIN ),
			/* translators: the number of purchase orders deleted */
			'deleted'   => _n( '%s PO permanently deleted.', '%s POs permanently deleted.', $bulk_counts['deleted'], ATUM_TEXT_DOMAIN ),
			/* translators: the number of purchase orders moved to the trash */
			'trashed'   => _n( '%s PO moved to the Trash.', '%s POs moved to the Trash.', $bulk_counts['trashed'], ATUM_TEXT_DOMAIN ),
			/* translators: the number of purchase orders restored from the trash */
			'untrashed' => _n( '%s PO restored from the Trash.', '%s POs restored from the Trash.', $bulk_counts['untrashed'], ATUM_TEXT_DOMAIN ),
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
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'PO updated.', ATUM_TEXT_DOMAIN ),
			2  => __( 'Custom field updated.', ATUM_TEXT_DOMAIN ),
			3  => __( 'Custom field deleted.', ATUM_TEXT_DOMAIN ),
			4  => __( 'PO updated.', ATUM_TEXT_DOMAIN ),
			/* translators: the PO's revision title */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'PO restored to revision from %s', ATUM_TEXT_DOMAIN ), wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE, // WPCS: CSRF ok.
			6  => __( 'PO updated.', ATUM_TEXT_DOMAIN ),
			7  => __( 'PO saved.', ATUM_TEXT_DOMAIN ),
			8  => __( 'PO submitted.', ATUM_TEXT_DOMAIN ),
			/* translators: the purchase order's schedule date */
			9  => sprintf( __( 'PO scheduled for: <strong>%1$s</strong>.', ATUM_TEXT_DOMAIN ), date_i18n( __( 'M j, Y @ G:i', ATUM_TEXT_DOMAIN ), strtotime( $post->post_date ) ) ),
			10 => __( 'PO draft updated.', ATUM_TEXT_DOMAIN ),
			11 => __( 'PO updated and email sent.', ATUM_TEXT_DOMAIN ),
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
	public function add_admin_bar_link( $atum_menus ) {

		$atum_menus['purchase-orders'] = array(
			'slug'       => ATUM_SHORT_NAME . '-purchase-orders',
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
	 * Get the currently instantiated PO object (if any) or create a new one
	 *
	 * @since 1.2.9
	 *
	 * @param int $post_id
	 *
	 * @return PurchaseOrder
	 */
	protected function get_current_atum_order( $post_id ) {

		if ( ! $this->po || $this->po->get_id() != $post_id ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
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

		Helpers::load_view( 'help-tabs/purchase-orders/' . $tab['name'] );
	}
	
	/**
	 * Add generate pdf action to purchase orders column actions if user can export data
	 *
	 * @since 1.3.9
	 *
	 * @param array         $actions
	 * @param PurchaseOrder $purchase_order
	 *
	 * @return mixed
	 */
	public function add_generate_pdf( $actions, $purchase_order ) {
		
		if ( AtumCapabilities::current_user_can( 'export_data' ) && ModuleManager::is_module_active( 'data_export' ) ) {
			$actions['pdf'] = array(
				'url'    => wp_nonce_url( admin_url( "admin-ajax.php?action=atum_order_pdf&atum_order_id={$purchase_order->get_id()}" ), 'atum-order-pdf' ),
				'name'   => __( 'Generate PDF', ATUM_TEXT_DOMAIN ),
				'action' => 'pdf',
				'target' => '_blank',
			);
		}
		
		return $actions;
	}

	/**
	 * Generate an Atum order pdf
	 *
	 * @since        1.3.9
	 */
	public function generate_order_pdf() {

		$atum_order_id = absint( $_GET['atum_order_id'] );

		if ( AtumCapabilities::current_user_can( 'export_data' ) && check_admin_referer( 'atum-order-pdf' ) && $atum_order_id ) {

			$po_export = new POExport( $atum_order_id );

			try {

				$mpdf = new Mpdf( [
					'mode'   => 'utf-8',
					'format' => 'A4',
				] );

				// Add support for non-Latin languages.
				$mpdf->useAdobeCJK      = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				$mpdf->autoScriptToLang = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				$mpdf->autoLangToFont   = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

				$mpdf->SetTitle( __( 'Purchase Order', ATUM_TEXT_DOMAIN ) );

				$mpdf->default_available_fonts = $mpdf->available_unifonts;

				$css = $po_export->get_stylesheets();

				foreach ( $css as $file ) {
					$stylesheet = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					$mpdf->WriteHTML( $stylesheet, 1 );
				}

				$mpdf->WriteHTML( $po_export->get_content() );

				// Output a PDF file directly to the browser.
				$mpdf->Output( "po-{$po_export->get_id()}.pdf", 'I' );

			} catch ( MpdfException $e ) {

				if ( ATUM_DEBUG ) {
					error_log( __METHOD__ . '::' . $e->getCode() . '::' . $e->getMessage() );
				}

			}

		}

	}

}
