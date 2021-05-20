<?php
/**
 * The model class for the Purchase Order objects
 *
 * @package         Atum\PurchaseOrders
 * @subpackage      Models
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\PurchaseOrders\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCalculatedProps;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals;
use Atum\PurchaseOrders\Items\POItemFee;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\Items\POItemShipping;
use Atum\PurchaseOrders\Items\POItemTax;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Supplier;


/**
 * Class PurchaseOrder
 *
 * Meta props available through the __get magic method:
 *
 * @property int    $supplier
 * @property string $multiple_suppliers
 * @property string $date_expected
 */
class PurchaseOrder extends AtumOrderModel {
	
	/**
	 * Whether the item's quantity will affect positively or negatively (or both) the stock
	 *
	 * @var string
	 */
	protected $action = 'add';

	/**
	 * The supplier assigned to the current PO (if any).
	 *
	 * @var Supplier
	 */
	protected $supplier_obj = NULL;
	
	/**
	 * PurchaseOrder constructor
	 *
	 * @since 1.2.9
	 *
	 * @param int  $id         Optional. The ATUM Order ID to initialize.
	 * @param bool $read_items Optional. Whether to read the inner items.
	 */
	public function __construct( $id = 0, $read_items = TRUE ) {

		// Add the PO's default custom meta.
		$this->meta = (array) apply_filters( 'atum/purchase_orders/po_meta', array_merge( $this->meta, array(
			'supplier'           => NULL,
			'multiple_suppliers' => 'no',
			'date_expected'      => '',
		) ) );

		parent::__construct( $id, $read_items );

		// Load the POs supplier.
		if ( $id ) {
			$this->get_supplier();
		}

		$this->block_message = __( 'Set the Supplier field above or allow Multiple Suppliers in order to add/edit items.', ATUM_TEXT_DOMAIN );

	}

	/**
	 * Recalculate the inbound stock for products within POs, every time a PO is saved.
	 *
	 * @since 1.5.8
	 *
	 * @param string $action
	 */
	public function after_save( $action ) {

		$items = $this->get_items();

		foreach ( $items as $item ) {

			/**
			 * Variable definition
			 *
			 * @var POItemProduct $item
			 */
			$product_id = $item->get_variation_id() ?: $item->get_product_id();
			AtumCalculatedProps::defer_update_atum_sales_calc_props( $product_id, Globals::get_order_type_table_id( $this->get_post_type() ) );

		}

		do_action( 'atum/purchase_orders/after_save', $this, $items );

	}

	/*********
	 * GETTERS
	 *********/

	/**
	 * Get the title for the PO post
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public function get_title() {

		if ( ! empty( $this->post->post_title ) && __( 'Auto Draft', ATUM_TEXT_DOMAIN ) !== $this->post->post_title ) {
			$post_title = $this->post->post_title;
		}
		else {
			/* translators: the purchase order date */
			$post_title = sprintf( __( 'PO &ndash; %s', ATUM_TEXT_DOMAIN ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'PO date parsed by strftime', ATUM_TEXT_DOMAIN ), strtotime( $this->date_created ) ) ); // phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText
		}

		return apply_filters( 'atum/purchase_orders/po/title', $post_title );
	}

	/**
	 * Get the supplier associated to this PO
	 *
	 * @since 1.2.9
	 *
	 * @param string $return    Optional. The type of object to return. Possible values 'id' or 'object'.
	 *
	 * @return Supplier|int|NULL
	 */
	public function get_supplier( $return = 'object' ) {

		if ( is_null( $this->supplier_obj ) ) {

			$supplier_id = $this->get_meta( 'supplier' );

			if ( $supplier_id ) {
				$this->supplier_obj = new Supplier( $supplier_id );
			}

		}

		if ( ! is_null( $this->supplier_obj ) && $this->supplier_obj->id ) {

			if ( 'id' === $return ) {
				return $this->supplier_obj->id;
			}
			else {
				return $this->supplier_obj;
			}

		}

		return NULL;

	}

	/**
	 * Check whether this PO allows products from multiple suppliers
	 *
	 * @since 1.4.2
	 *
	 * @return bool
	 */
	public function has_multiple_suppliers() {
		return 'yes' === wc_bool_to_string( $this->multiple_suppliers );
	}
	
	/**
	 * Get the Purchase Order's type
	 *
	 * @since 1.4.16
	 *
	 * @return string
	 */
	public function get_post_type() {
		return PurchaseOrders::POST_TYPE;
	}

	/**
	 * Get an ATUM Order item
	 *
	 * @since 1.2.9
	 *
	 * @param \WC_Order_Item|object|int $item
	 *
	 * @return \WC_Order_Item|POItemFee|POItemProduct|POItemShipping|POItemTax|false
	 */
	public function get_atum_order_item( $item = NULL ) {

		if ( $item instanceof \WC_Order_Item ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item $item
			 */
			$item_type = $item->get_type();
			$id        = $item->get_id();
		}
		elseif ( is_object( $item ) && ! empty( $item->order_item_type ) ) {
			$id        = $item->order_item_id;
			$item_type = $item->order_item_type;
		}
		elseif ( is_numeric( $item ) && ! empty( $this->items ) ) {
			$id = $item;

			foreach ( $this->items as $group => $group_items ) {

				foreach ( $group_items as $item_id => $stored_item ) {
					// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					if ( $id == $item_id ) {
						$item_type = $this->group_to_type( $group );
						break 2;
					}
				}

			}

		}
		else {
			$item_type = FALSE;
			$id        = FALSE;
		}

		if ( $id && isset( $item_type ) && $item_type ) {

			$classname       = FALSE;
			$items_namespace = '\\Atum\\PurchaseOrders\\Items\\';

			switch ( $item_type ) {

				case 'line_item':
				case 'product':
					$classname = "{$items_namespace}POItemProduct";
					break;

				case 'fee':
					$classname = "{$items_namespace}POItemFee";
					break;

				case 'shipping':
					$classname = "{$items_namespace}POItemShipping";
					break;

				case 'tax':
					$classname = "{$items_namespace}POItemTax";
					break;

				default:
					$classname = apply_filters( 'atum/purchase_orders/po/get_po_item_classname', $classname, $item_type, $id );
					break;

			}

			if ( $classname && class_exists( $classname ) ) {

				try {
					return new $classname( $id );
				} catch ( \Exception $e ) {
					return FALSE;
				}

			}

		}

		return FALSE;

	}

	/**
	 * Get key for where a certain item type is stored in items prop
	 *
	 * @since  1.2.9
	 *
	 * @param  \WC_Order_Item $item  PO item object (product, shipping, fee, tax).
	 *
	 * @return string
	 */
	protected function get_items_key( $item ) {

		$items_namespace = '\\Atum\\PurchaseOrders\\Items\\';

		if ( is_a( $item, "{$items_namespace}POItemProduct" ) ) {
			return 'line_items';
		}
		elseif ( is_a( $item, "{$items_namespace}POItemFee" ) ) {
			return 'fee_lines';
		}
		elseif ( is_a( $item, "{$items_namespace}POItemShipping" ) ) {
			return 'shipping_lines';
		}
		elseif ( is_a( $item, "{$items_namespace}POItemTax" ) ) {
			return 'tax_lines';
		}
		else {
			return '';
		}

	}

	/**
	 * This method is the inverse of the get_items_key method
	 * Gets the ATUM Order item's class given its key
	 *
	 * @since 1.2.9
	 *
	 * @param string $items_key The items key.
	 *
	 * @return string
	 */
	protected function get_items_class( $items_key ) {

		switch ( $items_key ) {
			case 'line_items':
				return '\\Atum\\PurchaseOrders\\Items\\POItemProduct';

			case 'fee_lines':
				return '\\Atum\\PurchaseOrders\\Items\\POItemFee';

			case 'shipping_lines':
				return '\\Atum\\PurchaseOrders\\Items\\POItemShipping';

			case 'tax_lines':
				return '\\Atum\\PurchaseOrders\\Items\\POItemTax';

			default:
				return '';
		}

	}

	/**
	 * Getter to collect all the Purchase Order data within an array
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_data() {

		// Prepare the data array based on the WC_Order_Data structure.
		$data = parent::get_data();

		$po_data = array(
			'supplier'           => $this->get_supplier( 'id' ),
			'multiple_suppliers' => $this->has_multiple_suppliers(),
			'date_expected'      => $this->date_expected ? wc_string_to_datetime( $this->date_expected ) : '',
		);

		return array_merge( $data, $po_data );

	}

	/*********
	 * SETTERS
	 *********/

	/**
	 * Setter for the PO's supplier ID
	 *
	 * @since 1.6.2
	 *
	 * @param int  $supplier_id
	 * @param bool $skip_change
	 */
	public function set_supplier( $supplier_id, $skip_change = FALSE ) {

		$supplier_id = absint( $supplier_id );

		if ( is_null( $this->supplier_obj ) || $this->supplier_obj->id !== $supplier_id ) {

			if ( ! $skip_change ) {
				$this->register_change( 'supplier' );
			}

			$this->set_meta( 'supplier', $supplier_id );
			$this->supplier_obj = $supplier_id ? new Supplier( $supplier_id ) : NULL;

		}
	}

	/**
	 * Setter for the multiple suppliers meta
	 *
	 * @since 1.6.2
	 *
	 * @param string|bool $multiple_suppliers
	 * @param bool        $skip_change
	 */
	public function set_multiple_suppliers( $multiple_suppliers, $skip_change = FALSE ) {

		$multiple_suppliers = wc_bool_to_string( $multiple_suppliers );

		if ( $multiple_suppliers !== $this->multiple_suppliers ) {

			if ( ! $skip_change ) {
				$this->register_change( 'multiple_suppliers' );
			}

			$this->set_meta( 'multiple_suppliers', $multiple_suppliers );
		}

	}

	/**
	 * Setter for the expected at location date
	 *
	 * @since 1.6.2
	 *
	 * @param string|\WC_DateTime $date_expected
	 * @param bool                $skip_change
	 */
	public function set_date_expected( $date_expected, $skip_change = FALSE ) {

		$date_expected = $date_expected instanceof \WC_DateTime ? $date_expected->date_i18n( 'Y-m-d H:i:s' ) : wc_clean( $date_expected );

		if ( $date_expected !== $this->date_expected ) {

			if ( ! $skip_change ) {
				$this->register_change( 'date_expected' );
			}

			$this->set_meta( 'date_expected', $date_expected );
		}

	}

}
