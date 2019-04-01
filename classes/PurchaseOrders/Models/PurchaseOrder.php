<?php
/**
 * The model class for the Purchase Order objects
 *
 * @package         Atum\PurchaseOrders
 * @subpackage      Models
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.2.9
 */

namespace Atum\PurchaseOrders\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Suppliers\Suppliers;


class PurchaseOrder extends AtumOrderModel {
	
	/**
	 * Whether the item's quantity will affect positively or negatively (or both) the stock
	 *
	 * @var string
	 */
	protected $action = 'add';
	
	/**
	 * PurchaseOrder constructor
	 *
	 * @since 1.2.9
	 *
	 * @param int  $id         Optional. The ATUM Order ID to initialize.
	 * @param bool $read_items Optional. Whether to read the inner items.
	 */
	public function __construct( $id = 0, $read_items = TRUE ) {
		
		if ( version_compare( wc()->version, '3.5.0', '<' ) ) {
			// Add the button for adding the inbound stock products to the WC stock.
			add_action( 'atum/atum_order/item_bulk_controls', array( $this, 'add_stock_button' ) );
		}
		
		// Add the button for setting the purchase price to products within POs.
		add_action( 'atum/atum_order/item_meta_controls', array( $this, 'set_purchase_price_button' ) );
		
		// Add message before the PO product search.
		add_action( 'atum/atum_order/before_product_search_modal', array( $this, 'product_search_message' ) );

		// Use the purchase price when adding products to a PO.
		add_filter( 'atum/order/add_product/price', array( $this, 'use_purchase_price' ), 10, 3 );
		
		// Maybe change product stock when order status change.
		add_action( 'atum/orders/status_received', array( $this, 'maybe_increase_stock_levels' ), 10, 2 );
		add_action( 'atum/orders/status_changed', array( $this, 'maybe_decrease_stock_levels' ), 10, 4 );

		parent::__construct( $id, $read_items );
		
		$this->block_message = __( 'Set the Supplier field above in order to add/edit items.', ATUM_TEXT_DOMAIN );

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
	 * @since 1.3.0
	 *
	 * @param \WC_Product $item
	 */
	public function set_purchase_price_button( $item ) {

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

		$supplier = $po->get_supplier();

		if ( $supplier ) {
			/* translators: the supplier title */
			echo '<em class="alert"><i class="atmi-info"></i> ' . sprintf( esc_attr__( "Only products linked to '%s' supplier can be searched.", ATUM_TEXT_DOMAIN ), esc_attr( $supplier->post_title ) ) . '</em>';
		}
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

		// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
		if ( ! empty( $this->post->post_title ) && __( 'Auto Draft', ATUM_TEXT_DOMAIN ) !== $this->post->post_title ) {
			$post_title = $this->post->post_title;
		}
		else {
			/* translators: the purchase order date */
			$post_title = sprintf( __( 'PO &ndash; %s', ATUM_TEXT_DOMAIN ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'PO date parsed by strftime', ATUM_TEXT_DOMAIN ), strtotime( $this->get_date() ) ) ); // phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText
		}

		return apply_filters( 'atum/purchase_orders/po/title', $post_title );
	}

	/**
	 * Get the supplier associated to this PO
	 *
	 * @since 1.2.9
	 *
	 * @param string $return    Optional. The type of object to return. Possible values 'id' or 'post'.
	 *
	 * @return \WP_Post|int|bool
	 */
	public function get_supplier( $return = 'post' ) {

		$supplier_id = $this->get_meta( Suppliers::SUPPLIER_META_KEY );

		if ( $supplier_id ) {

			if ( 'id' === $return ) {
				return $supplier_id;
			}
			else {
				$supplier = get_post( $supplier_id );

				return $supplier;
			}

		}

		return FALSE;

	}

	/**
	 * Check whether this PO allows products from multiple suppliers
	 *
	 * @since 1.4.2
	 *
	 * @return bool
	 */
	public function has_multiple_suppliers() {
		return 'yes' === $this->get_meta( '_multiple_suppliers' );

	}
	
	/**
	 * Get the Order's type
	 *
	 * @since 1.4.16
	 *
	 * @return string
	 */
	public function get_type() {
		return ATUM_PREFIX . 'purchase_order';
	}

	/**
	 * Get an ATUM Order item
	 *
	 * @since 1.2.9
	 *
	 * @param object $item
	 *
	 * @return \WC_Order_Item|false if not found
	 */
	public function get_atum_order_item( $item = NULL ) {

		if ( is_a( $item, '\WC_Order_Item' ) ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item $item
			 */
			$item_type = $item->get_type();
			$id        = $item->get_id();
		}
		elseif ( is_object( $item ) && ! empty( $item->order_item_type ) ) {
			/* @noinspection PhpUndefinedFieldInspection */
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
	 * Get the expected at location date
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public function get_expected_at_location_date() {
		return $this->get_meta( '_expected_at_location_date' );
	}

	/**
	 * Use the purchase price for the products added to POs
	 *
	 * @since 1.3.0
	 *
	 * @param float       $price
	 * @param float       $qty
	 * @param \WC_Product $product
	 *
	 * @return float|mixed|string
	 */
	public function use_purchase_price( $price, $qty, $product ) {
		
		// Get the purchase price (if set).
		/* @noinspection PhpUndefinedMethodInspection */
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
			$price = $price * $qty;
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
		
		if ( 'received' === $new_status ) {
			return;
		}
		
		// Any status !== finished is like pending, so reduce stock.
		if ( $order && 'received' === $old_status && $old_status !== $new_status && apply_filters( 'atum/purchase_orders/can_reduce_order_stock', TRUE, $order ) ) {
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
					
					$order->add_note( $note );
					$atum_order_item->update_meta_data( '_stock_changed', TRUE );
					$atum_order_item->save();
				}
				
			}
		}
		
	}

}
