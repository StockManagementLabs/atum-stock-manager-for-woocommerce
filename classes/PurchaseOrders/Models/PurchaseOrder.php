<?php
/**
 * @package         Atum\PurchaseOrders
 * @subpackage      Models
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.9
 *
 * The model class for the Purchase Order objects
 */

namespace Atum\PurchaseOrders\Models;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumException;
use Atum\Components\AtumOrders\Models\AtumOrderModel;


class PurchaseOrder extends AtumOrderModel {

	/**
	 * PurchaseOrder constructor
	 *
	 * @inheritdoc
	 */
	public function __construct( $id = 0, $read_items = TRUE ) {

		// Add the button for adding the inbound stock products to the WC stock
		add_action('atum/atum_order/item_bulk_controls', array($this, 'add_stock_button') );

		// Add the button for setting the purchase price to products within POs
		add_action('atum/atum_order/item_meta_controls', array($this, 'set_purchase_price_button') );

		parent::__construct($id, $read_items);

	}

	/**
	 * Add the button for adding the inbound stock products to the WC stock
	 *
	 * @since 1.3.0
	 */
	public function add_stock_button () {
		?><button type="button" class="button bulk-increase-stock"><?php _e( 'Add to Stock', ATUM_TEXT_DOMAIN ); ?></button><?php
	}

	/**
	 * Add the button for setting the purchase price to products within POs
	 *
	 * @since 1.3.0
	 */
	public function set_purchase_price_button () {
		?><button type="button" class="button set-purchase-price"><?php _e( 'Set purchase price', ATUM_TEXT_DOMAIN ); ?></button><?php
	}

	//---------
	//
	// GETTERS
	//
	//---------

	/**
	 * Get the title for the PO post
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public function get_title() {

		if ( ! empty($this->post->post_title) && $this->post->post_title != __('Auto Draft') ) {
			$post_title = $this->post->post_title;
		}
		else {
			$post_title = sprintf( __( 'PO &ndash; %s', ATUM_TEXT_DOMAIN ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'PO date parsed by strftime', ATUM_TEXT_DOMAIN ), strtotime( $this->get_date() ) ) );
		}

		return apply_filters('atum/purchase_orders/po/title', $post_title);
	}

	/**
	 * Get the supplier associated to this PO
	 *
	 * @since 1.2.9
	 *
	 * @return \WP_Post|bool
	 */
	public function get_supplier() {

		$supplier_id = $this->get_meta('_supplier');

		if ($supplier_id) {
			$supplier = get_post($supplier_id);

			return $supplier;
		}

		return FALSE;

	}

	/**
	 * @inheritdoc
	 */
	public function get_atum_order_item( $item = NULL ) {

		if ( is_a( $item, '\WC_Order_Item' ) ) {
			$item_type = $item->get_type();
			$id        = $item->get_id();
		}
		elseif ( is_object( $item ) && ! empty( $item->order_item_type ) ) {
			$id        = $item->order_item_id;
			$item_type = $item->order_item_type;
		}
		elseif ( is_numeric($item) && ! empty($this->items) ) {
			$id = $item;

			foreach ($this->items as $group => $group_items) {

				foreach ($group_items as $item_id => $stored_item) {
					if ($id == $item_id) {
						$item_type = $this->group_to_type($group);
						break 2;
					}
				}

			}

		}
		else {
			$item_type = FALSE;
			$id        = FALSE;
		}

		if ( $id && $item_type ) {

			$classname = FALSE;
			$items_namespace = '\\Atum\\PurchaseOrders\\Items\\';

			switch ( $item_type ) {

				case 'line_item' :
				case 'product' :
					$classname = "{$items_namespace}POItemProduct";
					break;

				case 'fee' :
					$classname = "{$items_namespace}POItemFee";
					break;

				case 'shipping' :
					$classname = "{$items_namespace}POItemShipping";
					break;

				case 'tax' :
					$classname = "{$items_namespace}POItemTax";
					break;

				default :
					$classname = apply_filters( 'atum/purchase_orders/po/get_po_item_classname', $classname, $item_type, $id );
					break;

			}

			if ( $classname && class_exists( $classname ) ) {

				try {
					return new $classname( $id );
				} catch ( AtumException $e ) {
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
	 * @param  \WC_Order_Item $item  PO item object (product, shipping, fee, tax)
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
	 * @inheritdoc
	 */
	protected function get_items_class($items_key) {

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
	 * Get the expected at location date
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public function get_expected_at_location_date() {
		return $this->get_meta('_expected_at_location_date');
	}

}