<?php
/**
 * The model class for the Log objects
 *
 * @package         Atum\InventoryLogs
 * @subpackage      Models
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\InventoryLogs\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCalculatedProps;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals;
use Atum\InventoryLogs\InventoryLogs;
use Atum\InventoryLogs\Items\LogItemFee;
use Atum\InventoryLogs\Items\LogItemProduct;
use Atum\InventoryLogs\Items\LogItemShipping;
use Atum\InventoryLogs\Items\LogItemTax;


/**
 * Class Log
 *
 * Meta props available through the __get magic method:
 *
 * @property int    $order
 * @property string $type
 * @property string $reservation_date
 * @property string $damage_date
 * @property string $return_date
 * @property string $custom_name
 * @property string $shipping_company
 */
class Log extends AtumOrderModel {

	/**
	 * ATUM product data's DB columns associated to each log type
	 *
	 * @var array
	 */
	protected static $log_type_columns = array(
		'reserved-stock'   => 'reserved_stock',
		'customer-returns' => 'customer_returns',
		'warehouse-damage' => 'warehouse_damage',
		'lost-in-post'     => 'lost_in_post',
		'other'            => 'other_logs',
	);

	/**
	 * Log constructor
	 *
	 * @since 1.2.4
	 *
	 * @param int  $id         Optional. The ATUM Order ID to initialize.
	 * @param bool $read_items Optional. Whether to read the inner items.
	 */
	public function __construct( $id = 0, $read_items = TRUE ) {

		// Add the IL's default custom meta.
		$this->meta = (array) apply_filters( 'atum/inventory_logs/log_meta', array_merge( $this->meta, array(
			'order'            => NULL,
			'type'             => '',
			'reservation_date' => '',
			'damage_date'      => '',
			'return_date'      => '',
			'custom_name'      => '',
			'shipping_company' => '',
		) ) );

		// Make sure the post is already created before instantiating the ATUM Order model.
		// When creating the PO from the WP backend, the post is created automatically but there are cases that this doesn't happen.
		if ( ! $id ) {
			$id = wp_insert_post( [
				'post_type'   => InventoryLogs::POST_TYPE,
				'post_title'  => __( 'Auto Draft', ATUM_TEXT_DOMAIN ),
				'post_status' => 'auto-draft',
			] );
		}

		parent::__construct( $id, $read_items );

	}

	/**
	 * Recalculate the IL's data props every time a log is saved.
	 *
	 * @since 1.5.8
	 *
	 * @param string $action
	 */
	public function after_save( $action ) {

		if ( 'update' === $action ) {
			$this->load_post();
		}

		$items = $this->get_items();

		foreach ( $items as $item ) {

			/**
			 * Variable definition
			 *
			 * @var LogItemProduct $item
			 */
			$product_id = $item->get_variation_id() ?: $item->get_product_id();
			AtumCalculatedProps::defer_update_atum_sales_calc_props( $product_id, Globals::get_order_type_table_id( $this->get_post_type() ) );

		}

		do_action( 'atum/inventory_logs/after_save', $this, $items );

	}

	/**********
	 * GETTERS
	 **********/

	/**
	 * Get the title for the Log post
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function get_title() {

		if ( ! empty( $this->post->post_title ) && __( 'Auto Draft' ) !== $this->post->post_title ) { // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			$post_title = $this->post->post_title;
		}
		else {
			/* translators: the log name */
			$post_title = sprintf( __( 'Log &ndash; %s', ATUM_TEXT_DOMAIN ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Log date parsed by strftime', ATUM_TEXT_DOMAIN ), strtotime( $this->date_created ) ) ); // phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText
		}

		return apply_filters( 'atum/inventory_logs/log/title', $post_title );
	}

	/**
	 * Get the order associated to this log
	 *
	 * @since 1.2.4
	 *
	 * @return \WC_Order|bool
	 */
	public function get_order() {

		$order_id = $this->get_meta( 'order' ); // NOTE: Using the __get magic method within a getter is not allowed.

		if ( $order_id ) {
			return wc_get_order( $order_id );
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
	public static function get_log_types() {

		return (array) apply_filters( 'atum/inventory_logs/log/types', array(
			'reserved-stock'   => __( 'Reserved Stock', ATUM_TEXT_DOMAIN ),
			'customer-returns' => __( 'Customer Returns', ATUM_TEXT_DOMAIN ),
			'warehouse-damage' => __( 'Warehouse Damage', ATUM_TEXT_DOMAIN ),
			'lost-in-post'     => __( 'Lost in Post', ATUM_TEXT_DOMAIN ),
			'other'            => __( 'Other', ATUM_TEXT_DOMAIN ),
		) );

	}

	/**
	 * Getter for the log_type_columns prop
	 *
	 * @since 1.5.8
	 *
	 * @return array
	 */
	public static function get_log_type_columns() {
		return self::$log_type_columns;
	}
	
	/**
	 * Get the Inventory Log's type
	 *
	 * @since 1.4.16
	 *
	 * @return string
	 */
	public function get_post_type() {
		return InventoryLogs::POST_TYPE;
	}

	/**
	 * Get an ATUM Order item
	 *
	 * @since 1.2.9
	 *
	 * @param \WC_Order_Item|object|int $item
	 *
	 * @return \WC_Order_Item|LogItemFee|LogItemProduct|LogItemShipping|LogItemTax|false
	 */
	public function get_atum_order_item( $item = NULL ) {

		$item_type = $id = FALSE;

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

		if ( $id && $item_type ) {

			$classname       = FALSE;
			$items_namespace = '\\Atum\\InventoryLogs\\Items\\';

			switch ( $item_type ) {

				case 'line_item':
				case 'product':
					$classname = "{$items_namespace}LogItemProduct";
					break;

				case 'fee':
					$classname = "{$items_namespace}LogItemFee";
					break;

				case 'shipping':
					$classname = "{$items_namespace}LogItemShipping";
					break;

				case 'tax':
					$classname = "{$items_namespace}LogItemTax";
					break;

				default:
					$classname = apply_filters( 'atum/inventory_logs/log_item/get_log_item_classname', $classname, $item_type, $id );
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
	 * @param  \WC_Order_Item $item  ATUM Order item object (product, shipping, fee, tax).
	 *
	 * @return string
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

	/**
	 * Getter to collect all the Inventory Log data within an array
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_data() {

		// Prepare the data array based on the WC_Order_Data structure.
		$data = parent::get_data();

		$log_data = array(
			'type'             => $this->type,
			'order'            => $this->get_order(),
			'reservation_date' => $this->reservation_date ? wc_string_to_datetime( $this->reservation_date ) : '',
			'return_date'      => $this->return_date ? wc_string_to_datetime( $this->return_date ) : '',
			'damage_date'      => $this->damage_date ? wc_string_to_datetime( $this->damage_date ) : '',
			'shipping_company' => $this->shipping_company,
			'custom_name'      => $this->custom_name,
		);

		return array_merge( $data, $log_data );

	}

	/**********
	 * SETTERS
	 **********/

	/**
	 * Setter for the related Order ID
	 *
	 * @since 1.6.2
	 *
	 * @param int  $order_id
	 * @param bool $skip_change
	 */
	public function set_order( $order_id, $skip_change = FALSE ) {

		$order_id = absint( $order_id );

		if ( absint( $this->order ) !== $order_id ) {

			if ( ! $skip_change ) {
				$this->register_change( 'order' );
			}

			$this->set_meta( 'order', $order_id );
		}

	}

	/**
	 * Setter for the Log Type
	 *
	 * @since 1.6.2
	 *
	 * @param string $log_type
	 * @param bool   $skip_change
	 */
	public function set_type( $log_type, $skip_change = FALSE ) {

		$log_type = in_array( $log_type, array_keys( self::get_log_types() ), TRUE ) ? $log_type : '';

		if ( $log_type !== $this->type ) {

			if ( ! $skip_change ) {
				$this->register_change( 'type' );
			}

			$this->set_meta( 'type', $log_type );
		}

	}

	/**
	 * Setter for the reservation date
	 *
	 * @since 1.6.2
	 *
	 * @param string|\WC_DateTime $reservation_date
	 * @param bool                $skip_change
	 */
	public function set_reservation_date( $reservation_date, $skip_change = FALSE ) {

		$reservation_date = $reservation_date instanceof \WC_DateTime ? $reservation_date->date_i18n( 'Y-m-d H:i:s' ) : wc_clean( $reservation_date );

		if ( $reservation_date !== $this->reservation_date ) {

			if ( ! $skip_change ) {
				$this->register_change( 'reservation_date' );
			}

			$this->set_meta( 'reservation_date', $reservation_date );
		}

	}

	/**
	 * Setter for the warehouse damage date
	 *
	 * @since 1.6.2
	 *
	 * @param string|\WC_DateTime $damage_date
	 * @param bool                $skip_change
	 */
	public function set_damage_date( $damage_date, $skip_change = FALSE ) {

		$damage_date = $damage_date instanceof \WC_DateTime ? $damage_date->date( 'Y-m-d H:i:s' ) : wc_clean( $damage_date );

		if ( $damage_date !== $this->damage_date ) {

			if ( ! $skip_change ) {
				$this->register_change( 'damage_date' );
			}

			$this->set_meta( 'damage_date', $damage_date );
		}

	}

	/**
	 * Setter for the customer returns date
	 *
	 * @since 1.6.2
	 *
	 * @param string|\WC_DateTime $return_date
	 * @param bool                $skip_change
	 */
	public function set_return_date( $return_date, $skip_change = FALSE ) {

		$return_date = $return_date instanceof \WC_DateTime ? $return_date->date( 'Y-m-d H:i:s' ) : wc_clean( $return_date );

		if ( $return_date !== $this->return_date ) {

			if ( ! $skip_change ) {
				$this->register_change( 'return_date' );
			}

			$this->set_meta( 'return_date', $return_date );
		}

	}

	/**
	 * Setter for the custom type name
	 *
	 * @since 1.6.2
	 *
	 * @param string $custom_name
	 * @param bool   $skip_change
	 */
	public function set_custom_name( $custom_name, $skip_change = FALSE ) {

		$custom_name = wc_clean( $custom_name );

		if ( $custom_name !== $this->custom_name ) {

			if ( ! $skip_change ) {
				$this->register_change( 'custom_name' );
			}

			$this->set_meta( 'custom_name', $custom_name );
		}

	}

	/**
	 * Setter for the Lost in Post's shipping company
	 *
	 * @since 1.6.2
	 *
	 * @param string $shipping_company
	 * @param bool   $skip_change
	 */
	public function set_shipping_company( $shipping_company, $skip_change = FALSE ) {

		$shipping_company = wc_clean( $shipping_company );

		if ( $shipping_company !== $this->shipping_company ) {

			if ( ! $skip_change ) {
				$this->register_change( 'shipping_company' );
			}

			$this->set_meta( 'shipping_company', $shipping_company );
		}

	}

}
