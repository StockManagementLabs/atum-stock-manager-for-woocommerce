<?php
/**
 * @package         Atum\InventoryLogs
 * @subpackage      Models
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * The model class for the Log objects
 */

namespace Atum\InventoryLogs\Models;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumException;
use Atum\Components\AtumOrders\Models\AtumOrderModel;


class Log extends AtumOrderModel {

	/**
	 * Log constructor
	 *
	 * @inheritdoc
	 */
	public function __construct( $id = 0, $read_items = TRUE ) {

		// Add the buttons for increasing/decreasing the Log products' stock
		add_action('atum/atum_order/item_bulk_controls', array($this, 'add_stock_buttons') );

		parent::__construct($id, $read_items);

	}

	/**
	 * Add the buttons for increasing/decreasing the Log products' stock
	 *
	 * @since 1.3.0
	 */
	public function add_stock_buttons () {
		?><button type="button" class="button bulk-increase-stock"><?php _e( 'Increase Stock', ATUM_TEXT_DOMAIN ); ?></button>
		  <button type="button" class="button bulk-decrease-stock"><?php _e( 'Reduce Stock', ATUM_TEXT_DOMAIN ); ?></button><?php
	}

	//---------
	//
	// GETTERS
	//
	//---------

	/**
	 * Get the title for the Log post
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_title() {

		if ( ! empty($this->post->post_title) && $this->post->post_title != __('Auto Draft') ) {
			$post_title = $this->post->post_title;
		}
		else {
			$post_title = sprintf( __( 'Log &ndash; %s', ATUM_TEXT_DOMAIN ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Log date parsed by strftime', ATUM_TEXT_DOMAIN ), strtotime( $this->get_date() ) ) );
		}

		return apply_filters('atum/inventory_logs/log/title', $post_title);
	}

	/**
	 * Get the order associated to this log
	 *
	 * @since 1.2.4
	 *
	 * @return \WC_Order|bool
	 */
	public function get_order() {

		$order_id = $this->get_meta('_order');

		if ($order_id) {
			$order = wc_get_order($order_id);

			return $order;
		}

		return FALSE;

	}

	/**
	 * Getter for the Inventory Log types
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public static function get_types() {

		return (array) apply_filters( 'atum/inventory_logs/log/types', array(
			'reserved-stock'   => __( 'Reserved Stock', ATUM_TEXT_DOMAIN ),
			'customer-returns' => __( 'Customer Returns', ATUM_TEXT_DOMAIN ),
			'warehouse-damage' => __( 'Warehouse Damage', ATUM_TEXT_DOMAIN ),
			'lost-in-post'     => __( 'Lost in Post', ATUM_TEXT_DOMAIN ),
			'other'            => __( 'Other', ATUM_TEXT_DOMAIN )
		) );
	}

	/**
	 * Get the log type
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->get_meta('_type');
	}

	/**
	 * Get the log reservation date
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_reservation_date() {
		return $this->get_meta('_reservation_date');
	}

	/**
	 * Get the log damage date
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_damage_date() {
		return $this->get_meta('_damage_date');
	}

	/**
	 * Get the log return date
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_return_date() {
		return $this->get_meta('_return_date');
	}

	/**
	 * Get the custom log name (for "Other" type logs)
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_custom_name() {
		return $this->get_meta('_custom_name');
	}

	/**
	 * Get shipping company
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_shipping_company() {
		return $this->get_meta( '_shipping_company' );
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
			$items_namespace = '\\Atum\\InventoryLogs\\Items\\';

			switch ( $item_type ) {

				case 'line_item' :
				case 'product' :
					$classname = "{$items_namespace}LogItemProduct";
					break;

				case 'fee' :
					$classname = "{$items_namespace}LogItemFee";
					break;

				case 'shipping' :
					$classname = "{$items_namespace}LogItemShipping";
					break;

				case 'tax' :
					$classname = "{$items_namespace}LogItemTax";
					break;

				default :
					$classname = apply_filters( 'atum/inventory_logs/log_item/get_log_item_classname', $classname, $item_type, $id );
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
	 * @inheritdoc
	 */
	protected function get_items_key( $item ) {

		$items_namespace = '\\Atum\\InventoryLogs\\Items\\';

		if ( is_a( $item, "{$items_namespace}LogItemProduct" ) ) {
			return 'line_items';
		}
		elseif ( is_a( $item, "{$items_namespace}LogItemFee" ) ) {
			return 'fee_lines';
		}
		elseif ( is_a( $item, "{$items_namespace}LogItemShipping" ) ) {
			return 'shipping_lines';
		}
		elseif ( is_a( $item, "{$items_namespace}LogItemTax" ) ) {
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
				return '\\Atum\\InventoryLogs\\Items\\LogItemProduct';

			case 'fee_lines':
				return '\\Atum\\InventoryLogs\\Items\\LogItemFee';

			case 'shipping_lines':
				return '\\Atum\\InventoryLogs\\Items\\LogItemShipping';

			case 'tax_lines':
				return '\\Atum\\InventoryLogs\\Items\\LogItemTax';

			default:
				return '';
		}

	}

}