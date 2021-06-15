<?php
/**
 * Shared trait for Atum Products
 *
 * @package         Atum\Models
 * @subpackage      Products
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\Products;

defined( 'ABSPATH' ) || die;

use Atum\Models\DataStores\AtumDataStoreCommonTrait;
use Atum\Models\DataStores\AtumDataStoreCPTTrait;
use Atum\Models\DataStores\AtumDataStoreCustomTableTrait;
use Atum\Suppliers\Suppliers;


trait AtumProductTrait {

	/**
	 * Add the ATUM data to the ATUM
	 *
	 * @var bool
	 */
	protected $atum_data = array(
		'purchase_price'                => '',
		'supplier_id'                   => NULL,
		'supplier_sku'                  => '',
		'atum_controlled'               => TRUE,
		// When a new product is created, the ATUM controlled should be enabled by default.
		'out_stock_date'                => NULL,
		'out_stock_threshold'           => '',
		'inheritable'                   => FALSE,
		'inbound_stock'                 => NULL,
		'stock_on_hold'                 => NULL,
		'sold_today'                    => NULL,
		'sales_last_days'               => NULL,
		'reserved_stock'                => NULL,
		'customer_returns'              => NULL,
		'warehouse_damage'              => NULL,
		'lost_in_post'                  => NULL,
		'other_logs'                    => NULL,
		'out_stock_days'                => NULL,
		'lost_sales'                    => NULL,
		'has_location'                  => NULL,
		'update_date'                   => NULL,
		'atum_stock_status'             => 'instock',
		'low_stock'                     => NULL,
		// Extra props (from ATUM add-ons).
		'minimum_threshold'             => NULL, // PL.
		'available_to_purchase'         => NULL, // PL.
		'selling_priority'              => NULL, // PL.
		'calculated_stock'              => NULL, // PL.
		'is_bom'                        => 0,    // PL.
		'multi_inventory'               => NULL, // MI.
		'inventory_iteration'           => NULL, // MI.
		'inventory_sorting_mode'        => NULL, // MI.
		'expirable_inventories'         => NULL, // MI.
		'price_per_inventory'           => NULL, // MI.
		'selectable_inventories'        => NULL, // MI.
		'selectable_inventories_mode'   => NULL, // MI.
		'show_write_off_inventories'    => NULL, // MI.
		'show_out_of_stock_inventories' => NULL, // MI.
	);


	/*
	|--------------------------------------------------
	| GETTERS
	|--------------------------------------------------
	|
	| Methods for getting data from the product object.
	*/

	/**
	 * Returns the product's purchase price.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_purchase_price( $context = 'view' ) {
		return $this->get_prop( 'purchase_price', $context );
	}

	/**
	 * Returns the product's supplier ID.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_supplier_id( $context = 'view' ) {
		return $this->get_prop( 'supplier_id', $context );
	}

	/**
	 * Returns the product's supplier SKU.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_supplier_sku( $context = 'view' ) {
		return $this->get_prop( 'supplier_sku', $context );
	}

	/**
	 * Returns the ATUM's control status.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string 'yes' or 'no'
	 */
	public function get_atum_controlled( $context = 'view' ) {
		return wc_bool_to_string( $this->get_prop( 'atum_controlled', $context ) );
	}

	/**
	 * Returns the product's out of stock date.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_out_stock_date( $context = 'view' ) {
		return $this->get_prop( 'out_stock_date', $context );
	}

	/**
	 * Returns the product's out of stock threshold.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_out_stock_threshold( $context = 'view' ) {
		return $this->get_prop( 'out_stock_threshold', $context );
	}

	/**
	 * Returns the product's inheritable prop.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string 'yes' or 'no'
	 */
	public function get_inheritable( $context = 'view' ) {
		return wc_bool_to_string( $this->get_prop( 'inheritable', $context ) );
	}

	/**
	 * Returns the product's inbound stock.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_inbound_stock( $context = 'view' ) {
		return $this->get_prop( 'inbound_stock', $context );
	}

	/**
	 * Returns the product's stock on hold.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_stock_on_hold( $context = 'view' ) {
		return $this->get_prop( 'stock_on_hold', $context );
	}

	/**
	 * Returns the product's sold today.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_sold_today( $context = 'view' ) {
		return $this->get_prop( 'sold_today', $context );
	}

	/**
	 * Returns the product's sales last days.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_sales_last_days( $context = 'view' ) {
		return $this->get_prop( 'sales_last_days', $context );
	}

	/**
	 * Returns the units of this product that were included in ILs with the "reserved stock" type.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_reserved_stock( $context = 'view' ) {
		return $this->get_prop( 'reserved_stock', $context );
	}

	/**
	 * Returns the units of this product that were included in ILs with the "customer returns" type.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_customer_returns( $context = 'view' ) {
		return $this->get_prop( 'customer_returns', $context );
	}

	/**
	 * Returns the units of this product that were included in ILs with the "warehouse damage" type.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_warehouse_damage( $context = 'view' ) {
		return $this->get_prop( 'warehouse_damage', $context );
	}

	/**
	 * Returns the units of this product that were included in ILs with the "lost in post" type.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_lost_in_post( $context = 'view' ) {
		return $this->get_prop( 'lost_in_post', $context );
	}

	/**
	 * Returns the units of this product that were included in ILs with the "other" type.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_other_logs( $context = 'view' ) {
		return $this->get_prop( 'other_logs', $context );
	}

	/**
	 * Returns the product's out of stock days.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int|null
	 */
	public function get_out_stock_days( $context = 'view' ) {
		return $this->get_prop( 'out_stock_days', $context );
	}

	/**
	 * Returns the product's lost sales.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_lost_sales( $context = 'view' ) {
		return $this->get_prop( 'lost_sales', $context );
	}

	/**
	 * Checks whether the product has any linked location.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string|null 'yes' or 'no' or NULL
	 */
	public function get_has_location( $context = 'view' ) {
		$has_location = $this->get_prop( 'has_location', $context );
		return is_null( $has_location ) ? $has_location : wc_bool_to_string( $has_location );
	}

	/**
	 * Returns the product's update date.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_update_date( $context = 'view' ) {
		return $this->get_prop( 'update_date', $context );
	}

	/**
	 * Returns the ATUM stock status.
	 *
	 * @since 1.6.6
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_atum_stock_status( $context = 'view' ) {
		return $this->get_prop( 'atum_stock_status', $context );
	}

	/**
	 * Returns the Low Stock indicator.
	 *
	 * @since 1.6.6
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return boolean
	 */
	public function get_low_stock( $context = 'view' ) {
		$low_stock = $this->get_prop( 'low_stock', $context );
		return wc_bool_to_string( $low_stock );
	}


	/****************************************
	 * EXTRA GETTERS USED BY PREMIUM ADD-ONS
	 ****************************************/


	/**
	 * Returns the product's minimum threshold prop.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_minimum_threshold( $context = 'view' ) {
		return $this->get_prop( 'minimum_threshold', $context );
	}

	/**
	 * Returns the product's available to purchase per user prop.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_available_to_purchase( $context = 'view' ) {
		return $this->get_prop( 'available_to_purchase', $context );
	}

	/**
	 * Returns the product's selling priority prop.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int|null
	 */
	public function get_selling_priority( $context = 'view' ) {
		return $this->get_prop( 'selling_priority', $context );
	}

	/**
	 * Returns the product's calculated stock prop.
	 *
	 * @since   1.5.8
	 * @package Product Levels
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int|float|null
	 */
	public function get_calculated_stock( $context = 'view' ) {
		return $this->get_prop( 'calculated_stock', $context );
	}

	/**
	 * Returns the product's inventory iteration prop.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_inventory_iteration( $context = 'view' ) {
		return $this->get_prop( 'inventory_iteration', $context );
	}

	/**
	 * Returns the product's multi inventory status prop.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_multi_inventory( $context = 'view' ) {
		
		$multi_inventory = $this->get_prop( 'multi_inventory', $context );
		
		if ( ! is_null( $multi_inventory ) ) {
			$multi_inventory = wc_bool_to_string( $multi_inventory );
		}
		
		return $multi_inventory;
	}

	/**
	 * Returns the product's inventory sorting mode prop.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_inventory_sorting_mode( $context = 'view' ) {
		return $this->get_prop( 'inventory_sorting_mode', $context );
	}

	/**
	 * Returns the product's inventory expiration prop.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_expirable_inventories( $context = 'view' ) {

		$expirable_inventories = $this->get_prop( 'expirable_inventories', $context );

		if ( ! is_null( $expirable_inventories ) ) {
			$expirable_inventories = wc_bool_to_string( $expirable_inventories );
		}

		return $expirable_inventories;
	}

	/**
	 * Returns the product's price per inventory prop.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_price_per_inventory( $context = 'view' ) {

		$price_per_inventory = $this->get_prop( 'price_per_inventory', $context );

		if ( ! is_null( $price_per_inventory ) ) {
			$price_per_inventory = wc_bool_to_string( $price_per_inventory );
		}

		return $price_per_inventory;

	}

	/**
	 * Returns the product's selectable inventories prop.
	 *
	 * @since   1.7.4
	 * @package Multi-Inventory
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_selectable_inventories( $context = 'view' ) {

		$selectable_inventories = $this->get_prop( 'selectable_inventories', $context );

		if ( ! is_null( $selectable_inventories ) ) {
			$selectable_inventories = wc_bool_to_string( $selectable_inventories );
		}

		return $selectable_inventories;
	}

	/**
	 * Returns the product's selectable inventories mode prop.
	 *
	 * @since   1.7.4
	 * @package Multi-Inventory
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_selectable_inventories_mode( $context = 'view' ) {
		return $this->get_prop( 'selectable_inventories_mode', $context );
	}

	/**
	 * Returns the product is BOM prop.
	 *
	 * @since 1.7.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string 'yes' or 'no'
	 */
	public function get_is_bom( $context = 'view' ) {

		$is_bom = $this->get_prop( 'is_bom', $context );

		if ( is_null( $is_bom ) ) {
			$is_bom = FALSE;
		}

		return wc_bool_to_string( $is_bom );

	}

	/**
	 * Returns the product's show_write_off_inventories prop.
	 *
	 * @since   1.8.9.1
	 * @package Multi-Inventory
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_show_write_off_inventories( $context = 'view' ) {

		$show_write_off_inventories = $this->get_prop( 'show_write_off_inventories', $context );

		if ( ! is_null( $show_write_off_inventories ) ) {
			$show_write_off_inventories = wc_bool_to_string( $show_write_off_inventories );
		}

		return $show_write_off_inventories;
	}

	/**
	 * Returns the product's show_out_of_stock_inventories prop.
	 *
	 * @since   1.9.0.1
	 * @package Multi-Inventory
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_show_out_of_stock_inventories( $context = 'view' ) {

		$show_out_of_stock_inventories = $this->get_prop( 'show_out_of_stock_inventories', $context );

		if ( ! is_null( $show_out_of_stock_inventories ) ) {
			$show_out_of_stock_inventories = wc_bool_to_string( $show_out_of_stock_inventories );
		}

		return $show_out_of_stock_inventories;
	}

	/*
	|----------------------------------------------------------------------------
	| SETTERS
	|----------------------------------------------------------------------------
	|
	| Functions for setting product data. These should not update anything in the
	| database itself and should only change what is stored in the class object.
	*/

	/**
	 * Set the product's purchase price.
	 *
	 * @since 1.5.0
	 *
	 * @param string $purchase_price
	 */
	public function set_purchase_price( $purchase_price ) {
		$this->set_prop( 'purchase_price', '' === $purchase_price ? '' : wc_format_decimal( $purchase_price ) );
	}

	/**
	 * Set the product's supplier ID.
	 *
	 * @since 1.5.0
	 *
	 * @param int $supplier_id
	 */
	public function set_supplier_id( $supplier_id ) {
		$this->set_prop( 'supplier_id', absint( $supplier_id ) ? absint( $supplier_id ) : NULL );
	}

	/**
	 * Set product supplier's SKU.
	 *
	 * @since 1.5.0
	 *
	 * @param string $supplier_sku
	 */
	public function set_supplier_sku( $supplier_sku ) {

		$supplier_sku = (string) $supplier_sku;

		if ( $supplier_sku ) {

			$supplier_sku_found = apply_filters( 'atum/model/product/supplier_sku_found', Suppliers::get_product_id_by_supplier_sku( $this->get_id(), $supplier_sku ), $supplier_sku, $this );

			if ( $this->get_object_read() && $supplier_sku_found ) {
				$this->error( 'product_invalid_supplier_sku', __( 'Invalid or duplicated Supplier SKU.', ATUM_TEXT_DOMAIN ), 400, array( 'resource_id' => $supplier_sku_found ) );
			}

		}

		$this->set_prop( 'supplier_sku', $supplier_sku );

	}

	/**
	 * Set if the product is controlled by ATUM.
	 *
	 * @since 1.5.0
	 *
	 * @param string|bool $atum_controlled Whether or not the ATUM control switch is enabled.
	 */
	public function set_atum_controlled( $atum_controlled ) {
		$this->set_prop( 'atum_controlled', wc_string_to_bool( $atum_controlled ) );
	}

	/**
	 * Set out of stock date.
	 *
	 * @since 1.5.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 *                                  If the DateTime string has no timezone or offset, WordPress site timezone will be assumed.
	 *                                  Null if there is no date.
	 */
	public function set_out_stock_date( $date = NULL ) {
		$this->set_date_prop( 'out_stock_date', $date );
	}

	/**
	 * Set out of stock threshold for the current product.
	 *
	 * @since 1.5.0
	 *
	 * @param int|string $out_stock_threshold Empty string if value not set.
	 */
	public function set_out_stock_threshold( $out_stock_threshold ) {
		$this->set_prop( 'out_stock_threshold', is_null( $out_stock_threshold ) || '' === $out_stock_threshold ? '' : wc_stock_amount( $out_stock_threshold ) );
	}

	/**
	 * Set if the product is from an inheriable type.
	 *
	 * @since 1.5.0
	 *
	 * @param string|bool $inheritable Whether or not the product is inheritable by others.
	 */
	public function set_inheritable( $inheritable ) {
		$this->set_prop( 'inheritable', wc_string_to_bool( $inheritable ) );
	}

	/**
	 * Set inbound stock for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $inbound_stock
	 */
	public function set_inbound_stock( $inbound_stock ) {
		$this->set_prop( 'inbound_stock', wc_stock_amount( $inbound_stock ) );
	}

	/**
	 * Set stock on hold for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $stock_on_hold
	 */
	public function set_stock_on_hold( $stock_on_hold ) {
		$this->set_prop( 'stock_on_hold', wc_stock_amount( $stock_on_hold ) );
	}

	/**
	 * Set sold today for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $sold_today
	 */
	public function set_sold_today( $sold_today ) {
		$this->set_prop( 'sold_today', wc_stock_amount( $sold_today ) );
	}

	/**
	 * Set sales last days for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $sales_last_days
	 */
	public function set_sales_last_days( $sales_last_days ) {
		$this->set_prop( 'sales_last_days', wc_stock_amount( $sales_last_days ) );
	}

	/**
	 * Set reserved stock for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $reserved_stock
	 */
	public function set_reserved_stock( $reserved_stock ) {
		$this->set_prop( 'reserved_stock', wc_stock_amount( $reserved_stock ) );
	}

	/**
	 * Set customer returns for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $customer_returns
	 */
	public function set_customer_returns( $customer_returns ) {
		$this->set_prop( 'customer_returns', wc_stock_amount( $customer_returns ) );
	}

	/**
	 * Set warehouse damages for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $warehouse_damage
	 */
	public function set_warehouse_damage( $warehouse_damage ) {
		$this->set_prop( 'warehouse_damage', wc_stock_amount( $warehouse_damage ) );
	}

	/**
	 * Set lost in post for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $lost_in_post
	 */
	public function set_lost_in_post( $lost_in_post ) {
		$this->set_prop( 'lost_in_post', wc_stock_amount( $lost_in_post ) );
	}

	/**
	 * Set other logs for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $other_logs
	 */
	public function set_other_logs( $other_logs ) {
		$this->set_prop( 'other_logs', wc_stock_amount( $other_logs ) );
	}

	/**
	 * Set out of stock days for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int $out_stock_days
	 */
	public function set_out_stock_days( $out_stock_days ) {
		$this->set_prop( 'out_stock_days', wc_stock_amount( $out_stock_days ) );
	}

	/**
	 * Set lost sales for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $lost_sales
	 */
	public function set_lost_sales( $lost_sales ) {
		$this->set_prop( 'lost_sales', wc_stock_amount( $lost_sales ) );
	}

	/**
	 * Set whether the product has any linked location.
	 *
	 * @since 1.5.8
	 *
	 * @param string|bool $has_location Whether or not the product is inheritable by others.
	 */
	public function set_has_location( $has_location ) {
		$this->set_prop( 'has_location', wc_string_to_bool( $has_location ) );
	}

	/**
	 * Set update date for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param string|integer|null $update_date UTC timestamp, or ISO 8601 DateTime.
	 *                                         If the DateTime string has no timezone or offset, WordPress site timezone will be assumed.
	 *                                         Null if there is no date.
	 */
	public function set_update_date( $update_date = NULL ) {
		$this->set_date_prop( 'update_date', $update_date );
	}

	/**
	 * Set the ATUM stock status for the current product.
	 *
	 * @since 1.6.6
	 *
	 * @param string $atum_stock_status
	 */
	public function set_atum_stock_status( $atum_stock_status = 'instock' ) {
		$valid_statuses = wc_get_product_stock_status_options();

		if ( isset( $valid_statuses[ $atum_stock_status ] ) ) {
			$this->set_prop( 'atum_stock_status', $atum_stock_status );
		}
		else {
			// Ensure instock is saved.
			$this->set_prop( 'atum_stock_status', '' );
			$this->changes['atum_stock_status'] = 'instock';
		}
	}

	/**
	 * Set whether the product has low stock or not.
	 *
	 * @since 1.6.6
	 *
	 * @param string|bool $low_stock
	 */
	public function set_low_stock( $low_stock ) {
		$this->set_prop( 'low_stock', wc_string_to_bool( $low_stock ) );
	}

	/****************************************
	 * EXTRA SETTERS USED BY PREMIUM ADD-ONS
	 ****************************************/

	/**
	 * Set minimum threshold for the current product.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param int|string $minimum_threshold Empty string if value not set.
	 */
	public function set_minimum_threshold( $minimum_threshold ) {
		$this->set_prop( 'minimum_threshold', is_null( $minimum_threshold ) || '' === $minimum_threshold ? '' : wc_stock_amount( $minimum_threshold ) );
	}

	/**
	 * Set available to purchase per user for the current product.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param int|string $available_to_purchase Empty string if value not set.
	 */
	public function set_available_to_purchase( $available_to_purchase ) {
		$this->set_prop( 'available_to_purchase', is_null( $available_to_purchase ) || '' === $available_to_purchase ? '' : wc_stock_amount( $available_to_purchase ) );
	}

	/**
	 * Set selling priority for the current product.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param int|string $selling_priority Empty string if value not set.
	 */
	public function set_selling_priority( $selling_priority ) {
		$this->set_prop( 'selling_priority', is_null( $selling_priority ) || '' === $selling_priority ? '' : absint( $selling_priority ) );
	}

	/**
	 * Set calculated stock for the current product.
	 *
	 * @since   1.5.8
	 * @package Product Levels
	 *
	 * @param int|float|string $calculated_stock Empty string if value not set.
	 */
	public function set_calculated_stock( $calculated_stock ) {
		$this->set_prop( 'calculated_stock', is_null( $calculated_stock ) || '' === $calculated_stock ? '' : wc_stock_amount( $calculated_stock ) );
	}

	/**
	 * Set the inventory iteration for the current product.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $inventory_iteration Allowed values: NULL, 'use_next' and 'out_of_stock'.
	 */
	public function set_inventory_iteration( $inventory_iteration ) {

		$valid_values = array(
			NULL, // NULL means 'global'.
			'use_next',
			'out_of_stock',
		);

		$inventory_iteration = in_array( $inventory_iteration, $valid_values, TRUE ) ? $inventory_iteration : NULL;
		$this->set_prop( 'inventory_iteration', $inventory_iteration );
	}

	/**
	 * Set the multi inventory status for the current product.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $multi_inventory Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_multi_inventory( $multi_inventory ) {

		$multi_inventory = ! is_null( $multi_inventory ) && 'global' !== $multi_inventory ? wc_string_to_bool( $multi_inventory ) : NULL;
		$this->set_prop( 'multi_inventory', $multi_inventory );
	}

	/**
	 * Set the inventory sorting mode for the current product.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $inventory_sorting_mode Allowed values: NULL, 'fifo', 'lifo', 'bbe' and 'manual'.
	 */
	public function set_inventory_sorting_mode( $inventory_sorting_mode ) {

		$valid_values = array(
			NULL, // NULL means 'global'.
			'fifo',
			'lifo',
			'bbe',
			'manual',
		);

		$inventory_sorting_mode = in_array( $inventory_sorting_mode, $valid_values, TRUE ) ? $inventory_sorting_mode : NULL;
		$this->set_prop( 'inventory_sorting_mode', $inventory_sorting_mode );
	}

	/**
	 * Set the inventory expiration mode for the current product.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $expirable_inventories Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_expirable_inventories( $expirable_inventories ) {

		$expirable_inventories = ! is_null( $expirable_inventories ) && 'global' !== $expirable_inventories ? wc_string_to_bool( $expirable_inventories ) : NULL;
		$this->set_prop( 'expirable_inventories', $expirable_inventories );
		
	}

	/**
	 * Set the price per inventory for the current product.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $price_per_inventory Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_price_per_inventory( $price_per_inventory ) {
		
		$price_per_inventory = ! is_null( $price_per_inventory ) && 'global' !== $price_per_inventory ? wc_string_to_bool( $price_per_inventory ) : NULL;
		$this->set_prop( 'price_per_inventory', $price_per_inventory );

	}

	/**
	 * Set the selectable inventories prop for the current product.
	 *
	 * @since   1.7.4
	 * @package Multi-Inventory
	 *
	 * @param string $selectable_inventories Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_selectable_inventories( $selectable_inventories ) {

		$selectable_inventories = ! is_null( $selectable_inventories ) && 'global' !== $selectable_inventories ? wc_string_to_bool( $selectable_inventories ) : NULL;
		$this->set_prop( 'selectable_inventories', $selectable_inventories );

	}

	/**
	 * Set the selectable inventories mode for the current product.
	 *
	 * @since   1.7.4
	 * @package Multi-Inventory
	 *
	 * @param string $selectable_inventories_mode Allowed values: NULL, 'dropdown', 'list'.
	 */
	public function set_selectable_inventories_mode( $selectable_inventories_mode ) {

		$valid_values = array(
			NULL, // NULL means 'global'.
			'dropdown',
			'list',
		);

		$selectable_inventories_mode = in_array( $selectable_inventories_mode, $valid_values, TRUE ) ? $selectable_inventories_mode : NULL;
		$this->set_prop( 'selectable_inventories_mode', $selectable_inventories_mode );
	}

	/**
	 * Set the product is BOM prop.
	 *
	 * @since 1.7.8
	 *
	 * @param string|bool $is_bom Whether or not the product is a BOM.
	 */
	public function set_is_bom( $is_bom ) {

		$is_bom = wc_string_to_bool( $is_bom );
		$this->set_prop( 'is_bom', $is_bom );
	}

	/**
	 * Set the Show "Write-Off" inventories option for the current product.
	 *
	 * @since   1.7.4
	 * @package Multi-Inventory
	 *
	 * @param string $show_write_off_inventories Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_show_write_off_inventories( $show_write_off_inventories ) {

		$show_write_off_inventories = ! is_null( $show_write_off_inventories ) && 'global' !== $show_write_off_inventories ? wc_string_to_bool( $show_write_off_inventories ) : NULL;
		$this->set_prop( 'show_write_off_inventories', $show_write_off_inventories );
	}

	/**
	 * Set the Show "No-Stock" inventories option for the current product.
	 *
	 * @since   1.9.0.1
	 * @package Multi-Inventory
	 *
	 * @param string $show_out_of_stock_inventories Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_show_out_of_stock_inventories( $show_out_of_stock_inventories ) {

		$show_out_of_stock_inventories = ! is_null( $show_out_of_stock_inventories ) && 'global' !== $show_out_of_stock_inventories ? wc_string_to_bool( $show_out_of_stock_inventories ) : NULL;
		$this->set_prop( 'show_out_of_stock_inventories', $show_out_of_stock_inventories );
	}


	/**
	 * Save the ATUM product data
	 *
	 * @since 1.5.0
	 */
	public function save_atum_data() {

		$data_store = $this->get_data_store();

		/**
		 * Variable definition
		 *
		 * @var AtumDataStoreCommonTrait|AtumDataStoreCPTTrait|AtumDataStoreCustomTableTrait $data_store
		 */
		$data_store->update_atum_product_data( $this );

	}

	/**
	 * Delete the ATUM product data
	 *
	 * @since 1.5.8.2
	 */
	public function delete_atum_data() {

		$data_store = $this->get_data_store();

		/**
		 * Variable definition
		 *
		 * @var AtumDataStoreCommonTrait|AtumDataStoreCPTTrait|AtumDataStoreCustomTableTrait $data_store
		 */
		$data_store->delete( $this, [
			'force_delete'   => TRUE,
			'delete_product' => FALSE,
		] );

	}

}
