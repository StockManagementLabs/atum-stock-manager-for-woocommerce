<?php
/**
 * Purchase Orders main class
 *
 * @package         Atum
 * @subpackage      PurchaseOrders
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2020 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\PurchaseOrders;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\MetaBoxes\ProductDataMetaBoxes;
use Atum\Models\Products\AtumProductTrait;
use Atum\PurchaseOrders\Exports\POExport;
use Atum\Inc\Helpers;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\Suppliers\Suppliers;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;

class PurchaseOrders extends AtumOrderPostType {

	/**
	 * The singleton instance holder
	 *
	 * @var PurchaseOrders
	 */
	private static $instance;

	/**
	 * The query var name used in list searches
	 *
	 * @var string
	 */
	protected $search_label = ATUM_PREFIX . 'po_search';
	
	/**
	 * Status that means an ATUM Order is finished
	 */
	const FINISHED = 'atum_received';

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
		'read_private_posts' => 'read_private_purchase_orders',
		'publish_posts'      => 'publish_purchase_orders',
		'create_posts'       => 'create_purchase_orders',
		'delete_posts'       => 'delete_purchase_orders',
		'delete_other_posts' => 'delete_other_purchase_orders',
	);
	
	/**
	 * PurchaseOrders singleton constructor
	 *
	 * @since 1.2.9
	 */
	private function __construct() {

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
		$this->init();

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
		ProductDataMetaBoxes::get_instance()->purchase_price_hooks();

		// Use the purchase price when adding products to a PO.
		add_filter( 'atum/order/add_product/price', array( $this, 'use_purchase_price' ), 10, 3 );

		// Add custom search for POs.
		add_action( 'atum/' . self::POST_TYPE . '/search_results', array( $this, 'po_search' ), 10, 3 );
		add_filter( 'atum/' . self::POST_TYPE . '/search_fields', array( $this, 'search_fields' ) );

		// Add message before the PO product search.
		add_action( 'atum/atum_order/before_product_search_modal', array( $this, 'product_search_message' ) );

		if ( version_compare( WC()->version, '3.5.0', '<' ) ) {
			// Add the button for adding the inbound stock products to the WC stock.
			add_action( 'atum/atum_order/item_bulk_controls', array( $this, 'add_stock_button' ) );
		}

		// Add the button for setting the purchase price to products within POs.
		add_action( 'atum/atum_order/item_meta_controls', array( $this, 'set_purchase_price_button' ), 10, 2 );

		// Maybe change product stock when order status change.
		add_action( 'atum/orders/status_atum_received', array( $this, 'maybe_increase_stock_levels' ), 10, 2 );
		add_action( 'atum/orders/status_changed', array( $this, 'maybe_decrease_stock_levels' ), 10, 4 );

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

		if ( ! $atum_order instanceof PurchaseOrder ) {
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
	 * Save the Purchase Order meta boxes
	 *
	 * @since 1.2.9
	 *
	 * @param int $po_id
	 */
	public function save_meta_boxes( $po_id ) {

		if ( empty( $_POST['status'] ) || empty( $_POST['atum_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['atum_meta_nonce'], 'atum_save_meta_data' ) ) {
			return;
		}

		$po = $this->get_current_atum_order( $po_id );

		if ( empty( $po ) ) {
			return;
		}

		$timestamp = function_exists( 'wp_date' ) ? wp_date( 'U' ) : current_time( 'timestamp', TRUE ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$po_date   = empty( $_POST['date'] ) ? $timestamp : strtotime( $_POST['date'] . ' ' . (int) $_POST['date_hour'] . ':' . (int) $_POST['date_minute'] . ':00' );
		$po_date   = date_i18n( 'Y-m-d H:i:s', $po_date );

		$date_expected = empty( $_POST['date_expected'] ) ? $timestamp : strtotime( $_POST['date_expected'] . ' ' . (int) $_POST['date_expected_hour'] . ':' . (int) $_POST['date_expected_minute'] . ':00' );
		$date_expected = date_i18n( 'Y-m-d H:i:s', $date_expected );

		$multiple_suppliers = ( isset( $_POST['multiple_suppliers'] ) && 'yes' === $_POST['multiple_suppliers'] ) ? 'yes' : 'no';

		$po->set_props( array(
			'status'             => $_POST['status'],
			'date_created'       => $po_date,
			'supplier'           => 'no' === $multiple_suppliers && isset( $_POST['supplier'] ) ? $_POST['supplier'] : '',
			'multiple_suppliers' => $multiple_suppliers,
			'date_expected'      => $date_expected,
		) );

		// Set the PO description as post content.
		$po->set_description( $_POST['description'] );

		$po->save();

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

		return array(
			'cb'               => $existing_columns['cb'],
			'atum_order_title' => __( 'PO', ATUM_TEXT_DOMAIN ),
			'date'             => __( 'Date', ATUM_TEXT_DOMAIN ),
			'status'           => __( 'Status', ATUM_TEXT_DOMAIN ),
			'supplier'         => __( 'Supplier', ATUM_TEXT_DOMAIN ),
			'date_expected'    => __( 'Date Expected', ATUM_TEXT_DOMAIN ),
			'total'            => __( 'Total', ATUM_TEXT_DOMAIN ),
			'actions'          => __( 'Actions', ATUM_TEXT_DOMAIN ),
		);

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
				
				if ( $supplier && $supplier->name ) {
					echo esc_html( $supplier->name );
				}
				break;

			case 'date_expected':
				$date_expected = $po->date_expected;

				if ( $date_expected ) {
					$date_expected = '<abbr title="' . gmdate( 'Y/m/d H:i:s', strtotime( $date_expected ) ) . '">' . gmdate( 'Y/m/d', strtotime( $date_expected ) ) . '</abbr>';
				}

				echo $date_expected; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'PO restored to revision from %s', ATUM_TEXT_DOMAIN ), wp_post_revision_title( absint( $_GET['revision'] ), FALSE ) ) : FALSE,
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
	public function get_current_atum_order( $post_id ) {

		if ( ! $this->po || $this->po->get_id() != $post_id ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$this->po = Helpers::get_atum_order_model( $post_id, self::POST_TYPE );
		}

		return $this->po;

	}
	
	/**
	 * Get the available ATUM Order statuses
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public static function get_statuses() {

		return (array) apply_filters( 'atum/purchase_orders/statuses', array(
			'atum_pending'    => _x( 'Pending', 'ATUM Purchase Order status', ATUM_TEXT_DOMAIN ),
			'atum_ordered'    => _x( 'Ordered', 'ATUM Purchase Order status', ATUM_TEXT_DOMAIN ),
			'atum_onthewayin' => _x( 'On the Way In', 'ATUM Purchase Order status', ATUM_TEXT_DOMAIN ),
			'atum_receiving'  => _x( 'Receiving', 'ATUM Purchase Order status', ATUM_TEXT_DOMAIN ),
			'atum_received'   => _x( 'Received', 'ATUM Purchase Order status', ATUM_TEXT_DOMAIN ),
		) );
		
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

			$actions = array_merge( array(
				'pdf' => array(
					'url'    => self::get_pdf_generation_link( $purchase_order->get_id() ),
					'name'   => __( 'Generate PDF', ATUM_TEXT_DOMAIN ),
					'action' => 'pdf',
					'target' => '_blank',
					'icon'   => '<i class="atum-icon atmi-pdf"></i>',
				),
			), $actions );

		}
		
		return $actions;

	}

	/**
	 * Get the direct link for the PO's PDF generation
	 *
	 * @since 1.6.6
	 *
	 * @param int $po_id
	 *
	 * @return string
	 */
	public static function get_pdf_generation_link( $po_id ) {
		return wp_nonce_url( admin_url( "admin-ajax.php?action=atum_order_pdf&atum_order_id={$po_id}" ), 'atum-order-pdf' );
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
				
				$uploads = wp_upload_dir();
				
				$temp_dir = $uploads['basedir'] . apply_filters( 'atum/purchase_orders/pdf_folder', '/atum' );
				
				if ( ! is_dir( $temp_dir ) ) {
					
					// Try to create it.
					$success = mkdir( $temp_dir, 0777, TRUE );
					
					// If can't create it, use default uploads folder.
					if ( ! $success || ! is_writable( $temp_dir ) ) {
						$temp_dir = $uploads['basedir'];
					}
					
				}

				$mpdf = new Mpdf( [
					'mode'    => 'utf-8',
					'format'  => 'A4',
					'tempDir' => $temp_dir,
				] );

				// Add support for non-Latin languages.
				$mpdf->useAdobeCJK      = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$mpdf->autoScriptToLang = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$mpdf->autoLangToFont   = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

				$mpdf->SetTitle( __( 'Purchase Order', ATUM_TEXT_DOMAIN ) );

				$mpdf->default_available_fonts = $mpdf->available_unifonts;

				$css = $po_export->get_stylesheets();

				foreach ( $css as $file ) {
					$stylesheet = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					$mpdf->WriteHTML( $stylesheet, 1 );
				}

				$mpdf->WriteHTML( $po_export->get_content() );

				// Output a PDF file directly to the browser.
				wp_die( $mpdf->Output( "po-{$po_export->get_id()}.pdf", Destination::INLINE ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			} catch ( MpdfException $e ) {
				wp_die( $e->getMessage() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

		}

	}

	/**
	 * Set the custom fields that are available for searches within the PO's list
	 *
	 * @since 1.6.1
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function search_fields( $fields ) {

		// NOTE: For now we are going to support searches within the custom columns displayed on the ILs list.
		return array_merge( $fields, [ '_total' ] );

	}

	/**
	 * Search within custom fields on PO searches
	 *
	 * @since 1.6.1
	 *
	 * @param array $atum_order_ids
	 * @param mixed $term
	 * @param array $search_fields
	 *
	 * @return array
	 */
	public function po_search( $atum_order_ids, $term, $search_fields ) {

		global $wpdb;

		// NOTE: For now we are going to support searches within the custom columns displayed on the POs list.

		// Dates: search in post_modified and date_expected dates.
		if ( ! is_numeric( $term ) && strtotime( $term ) ) {

			// Format the date in MySQL format.
			$date = gmdate( 'Y-m-d', strtotime( $term ) );
			$term = "%$date%";

			$ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT ID FROM $wpdb->posts p
				LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND meta_key = '_date_expected')
				WHERE (meta_value LIKE %s OR post_date_gmt LIKE %s)
				AND post_type = %s			
			", $term, $term, self::POST_TYPE ) );

		}
		// Strings: search in supplier names or user names.
		else {

			$sup_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT ID FROM $wpdb->posts p
				LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND meta_key = %s)
				WHERE meta_value IN (
					SELECT ID FROM $wpdb->posts
					WHERE post_title LIKE %s AND post_type = %s
				)	
				AND post_type = %s",
				Suppliers::SUPPLIER_META_KEY,
				'%' . $wpdb->esc_like( $term ) . '%',
				Suppliers::POST_TYPE,
				self::POST_TYPE
			) );

			$usr_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT p.ID FROM $wpdb->posts p
				LEFT JOIN $wpdb->users u ON (p.post_author = u.ID)
				WHERE user_login LIKE %s AND post_type = %s",
				'%' . $wpdb->esc_like( $term ) . '%',
				self::POST_TYPE
			) );

			$ids = array_merge( $sup_ids, $usr_ids );

		}

		return array_unique( array_merge( $atum_order_ids, $ids ) );

	}

	/**
	 * Add the button for adding the inbound stock products to the WC stock
	 *
	 * @since 1.3.0
	 */
	public function add_stock_button() {
		?>
		<button type="button" class="button bulk-increase-stock"><?php esc_attr_e( 'Add to Stock', ATUM_TEXT_DOMAIN ); ?></button>
		<?php
	}

	/**
	 * Add the button for setting the purchase price to products within POs
	 *
	 * @param AtumOrderItemProduct $item
	 * @param AtumOrderModel       $atum_order
	 *
	 * @since 1.3.0
	 */
	public function set_purchase_price_button( $item, $atum_order ) {

		if ( ! $atum_order instanceof PurchaseOrder ) {
			return;
		}

		if ( 'line_item' === $item->get_type() ) : ?>
			<button type="button" class="button set-purchase-price"><?php esc_attr_e( 'Set purchase price', ATUM_TEXT_DOMAIN ); ?></button>
		<?php endif;
	}

	/**
	 * Add message before the PO product search
	 *
	 * @since 1.3.0
	 *
	 * @param PurchaseOrder $po
	 */
	public function product_search_message( $po ) {

		if ( ! $po instanceof PurchaseOrder ) {
			return;
		}

		$supplier = $po->get_supplier();

		if ( $supplier && $supplier->id ) {
			/* translators: the supplier title */
			echo '<em class="alert"><i class="atmi-info"></i> ' . sprintf( esc_attr__( "Only products linked to '%s' supplier can be searched.", ATUM_TEXT_DOMAIN ), esc_attr( $supplier->name ) ) . '</em>';
		}
	}

	/**
	 * Use the purchase price for the products added to POs
	 *
	 * @since 1.3.0
	 *
	 * @param float                        $price
	 * @param float                        $qty
	 * @param \WC_Product|AtumProductTrait $product
	 *
	 * @return float|mixed|string
	 */
	public function use_purchase_price( $price, $qty, $product ) {

		// Get the purchase price (if set).
		$price = $product->get_purchase_price();

		if ( ! $price ) {
			return '';
		}
		elseif ( empty( $qty ) ) {
			return 0.0;
		}

		if ( $product->is_taxable() && wc_prices_include_tax() ) {
			$tax_rates = \WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
			$taxes     = \WC_Tax::calc_tax( $price * $qty, $tax_rates, TRUE );
			$price     = \WC_Tax::round( $price * $qty - array_sum( $taxes ) );
		}
		else {
			$price *= $qty;
		}

		return $price;

	}

	/**
	 * Maybe increase stock Levels
	 *
	 * @since 1.5.0
	 *
	 * @param int           $order_id
	 * @param string        $old_status
	 * @param string        $new_status
	 * @param PurchaseOrder $order
	 */
	public function maybe_decrease_stock_levels( $order_id, $old_status, $new_status, $order ) {

		if ( self::FINISHED === $new_status ) {
			return;
		}

		// Any status !== finished is like pending, so reduce stock.
		if ( $order && self::FINISHED === $old_status && $old_status !== $new_status && apply_filters( 'atum/purchase_orders/can_reduce_order_stock', TRUE, $order ) ) {
			$this->change_stock_levels( $order, 'decrease' );
			do_action( 'atum/purchase_orders/po/after_decrease_stock_levels', $order );
		}

	}

	/**
	 * Maybe decrease stock Levels
	 *
	 * @since 1.5.0
	 *
	 * @param int           $order_id
	 * @param PurchaseOrder $order
	 */
	public function maybe_increase_stock_levels( $order_id, $order ) {

		if ( $order && apply_filters( 'atum/purchase_orders/can_restore_order_stock', TRUE, $order ) ) {
			$this->change_stock_levels( $order, 'increase' );
			do_action( 'atum/purchase_orders/po/after_increase_stock_levels', $order );
		}

	}

	/**
	 * Change product stock from items
	 *
	 * @since 1.5.0
	 *
	 * @param PurchaseOrder $order
	 * @param string        $action
	 */
	public function change_stock_levels( $order, $action ) {

		$atum_order_items = $order->get_items();

		if ( ! empty( $atum_order_items ) ) {
			foreach ( $atum_order_items as $item_id => $atum_order_item ) {

				$product = $atum_order_item->get_product();

				/**
				 * Variable definition
				 *
				 * @var \WC_Product $product
				 */

				if ( $product && $product->exists() && $product->managing_stock() ) {

					$old_stock = $product->get_stock_quantity();

					// if stock is null but WC is managing stock.
					if ( is_null( $old_stock ) ) {
						$old_stock = 0;
						wc_update_product_stock( $product, $old_stock );
					}

					$stock_change = apply_filters( 'atum/purchase_orders/po/restore_atum_order_stock_quantity', $atum_order_item->get_quantity(), $item_id );
					$new_quantity = wc_update_product_stock( $product, $stock_change, $action );

					$old_stock_note = 'increase' === $action ? $new_quantity - $stock_change : $new_quantity + $stock_change;

					$item_name = $product->get_sku() ? $product->get_sku() : $product->get_id();
					$note      = sprintf(
					/* translators: first is the item name, second is the action, third is the old stock and forth is the new stock */
						__( 'Item %1$s stock %2$s from %3$s to %4$s.', ATUM_TEXT_DOMAIN ),
						$item_name,
						'increase' === $action ? __( 'increased', ATUM_TEXT_DOMAIN ) : __( 'decreased', ATUM_TEXT_DOMAIN ),
						$old_stock_note,
						$new_quantity
					);

					$order->add_order_note( $note );
					$atum_order_item->update_meta_data( '_stock_changed', TRUE );
					$atum_order_item->save();
				}

			}
		}

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
	 * @return PurchaseOrders instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
