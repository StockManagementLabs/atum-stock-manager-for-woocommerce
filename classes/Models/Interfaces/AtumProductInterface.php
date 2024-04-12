<?php
/**
 * ATUM Product interface
 */

namespace Atum\Models\Interfaces;

defined( 'ABSPATH' ) || die;

interface AtumProductInterface {

	/**
	 * Returns the product's purchase price.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_purchase_price( $context = 'view' );

	/**
	 * Returns the product's supplier ID.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_supplier_id( $context = 'view' );

	/**
	 * Returns the product's supplier SKU.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_supplier_sku( $context = 'view' );

	/**
	 * Returns the ATUM's control status.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string 'yes' or 'no'
	 */
	public function get_atum_controlled( $context = 'view' );

	/**
	 * Returns the product's out of stock date.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_out_stock_date( $context = 'view' );

	/**
	 * Returns the product's out of stock threshold.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_out_stock_threshold( $context = 'view' );

	/**
	 * Returns the product's inheritable prop.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string 'yes' or 'no'
	 */
	public function get_inheritable( $context = 'view' );

	/**
	 * Returns the product's inbound stock.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_inbound_stock( $context = 'view' );

	/**
	 * Returns the product's stock on hold.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_stock_on_hold( $context = 'view' );

	/**
	 * Returns the product's sold today.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_sold_today( $context = 'view' );

	/**
	 * Returns the product's sales last days.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_sales_last_days( $context = 'view' );

	/**
	 * Returns the units of this product that were included in ILs with the "reserved stock" type.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_reserved_stock( $context = 'view' );

	/**
	 * Returns the units of this product that were included in ILs with the "customer returns" type.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_customer_returns( $context = 'view' );

	/**
	 * Returns the units of this product that were included in ILs with the "warehouse damage" type.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_warehouse_damage( $context = 'view' );

	/**
	 * Returns the units of this product that were included in ILs with the "lost in post" type.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_lost_in_post( $context = 'view' );

	/**
	 * Returns the units of this product that were included in ILs with the "other" type.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_other_logs( $context = 'view' );

	/**
	 * Returns the product's out of stock days.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int|null
	 */
	public function get_out_stock_days( $context = 'view' );

	/**
	 * Returns the product's lost sales.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_lost_sales( $context = 'view' );

	/**
	 * Checks whether the product has any linked location.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string|null 'yes' or 'no' or NULL
	 */
	public function get_has_location( $context = 'view' );

	/**
	 * Returns the product's update date.
	 *
	 * @since 1.5.8
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_update_date( $context = 'view' );

	/**
	 * Returns the ATUM stock status.
	 *
	 * @since 1.6.6
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_atum_stock_status( $context = 'view' );

	/**
	 * Returns the Restock Status indicator.
	 *
	 * @since 1.6.6
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string 'yes' or 'no'
	 */
	public function get_restock_status( $context = 'view' );

	/**
	 * Returns the sales props product's update date.
	 *
	 * @since 1.9.6.1
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_sales_update_date( $context = 'view' );

	/**
	 * Returns the barcode.
	 *
	 * @since 1.9.18
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string|NULL the barcode or null if it isn't set.
	 */
	public function get_barcode( $context = 'view' );

	/**
	 * Returns the fields names in ATUM data.
	 *
	 * @since 1.9.29.1
	 *
	 * @return string[]
	 */
	public function get_atum_data_column_names();

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
	public function get_minimum_threshold( $context = 'view' );

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
	public function get_available_to_purchase( $context = 'view' );

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
	public function get_selling_priority( $context = 'view' );

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
	public function get_calculated_stock( $context = 'view' );

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
	public function get_inventory_iteration( $context = 'view' );

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
	public function get_multi_inventory( $context = 'view' );

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
	public function get_inventory_sorting_mode( $context = 'view' );

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
	public function get_expirable_inventories( $context = 'view' );

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
	public function get_price_per_inventory( $context = 'view' );

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
	public function get_selectable_inventories( $context = 'view' );

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
	public function get_selectable_inventories_mode( $context = 'view' );

	/**
	 * Returns the product is BOM prop.
	 *
	 * @since 1.7.8
	 * @package Product Levels
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string 'yes' or 'no'
	 */
	public function get_is_bom( $context = 'view' );

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
	public function get_show_write_off_inventories( $context = 'view' );

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
	public function get_show_out_of_stock_inventories( $context = 'view' );

	/**
	 * Returns the product's committed_to_wc prop.
	 *
	 * @since   1.9.20.3
	 * @package SOnly
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int|float|null
	 */
	public function get_committed_to_wc( $context = 'view' );

	/**
	 * Returns the product's calculated backorders prop.
	 *
	 * @since   1.9.20.4
	 * @package SOnly
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int|float|null
	 */
	public function get_calc_backorders( $context = 'view' );

	/**
	 * Returns the product's barcode type prop.
	 *
	 * @since   1.9.30
	 * @package Barcodes PRO
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_barcode_type( $context = 'view' );

	/**
	 * Returns the product's UOM status prop.
	 *
	 * @since   1.9.34
	 * @package Units of Measure
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_uom_status( $context = 'view' );

	/**
	 * Returns the product's measure type prop.
	 *
	 * @since   1.9.34
	 * @package Units of Measure
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_measure_type( $context = 'view' );

	/**
	 * Returns the product's measure unit prop.
	 *
	 * @since   1.9.34
	 * @package Units of Measure
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_measure_unit( $context = 'view' );

	/**
	 * Returns the product's low_stock_threshold_by_inventory prop.
	 *
	 * @since   1.9.33
	 * @package Multi-Inventory
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_low_stock_threshold_by_inventory( $context = 'view' );

	/**
	 * Set the product's purchase price.
	 *
	 * @since 1.5.0
	 *
	 * @param string $purchase_price
	 */
	public function set_purchase_price( $purchase_price );

	/**
	 * Set the product's supplier ID.
	 *
	 * @since 1.5.0
	 *
	 * @param int $supplier_id
	 */
	public function set_supplier_id( $supplier_id );

	/**
	 * Set product supplier's SKU.
	 *
	 * @since 1.5.0
	 *
	 * @param string $supplier_sku
	 */
	public function set_supplier_sku( $supplier_sku );

	/**
	 * Set if the product is controlled by ATUM.
	 *
	 * @since 1.5.0
	 *
	 * @param string|bool $atum_controlled Whether the ATUM control switch is enabled.
	 */
	public function set_atum_controlled( $atum_controlled );

	/**
	 * Set out of stock date.
	 *
	 * @since 1.5.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 *                                  If the DateTime string has no timezone or offset, WordPress site timezone will be assumed.
	 *                                  Null if there is no date.
	 */
	public function set_out_stock_date( $date = NULL );

	/**
	 * Set out of stock threshold for the current product.
	 *
	 * @since 1.5.0
	 *
	 * @param int|string $out_stock_threshold Empty string if value not set.
	 */
	public function set_out_stock_threshold( $out_stock_threshold );

	/**
	 * Set if the product is from an inheritable type.
	 *
	 * @since 1.5.0
	 *
	 * @param string|bool $inheritable Whether the product is inheritable by others or not.
	 */
	public function set_inheritable( $inheritable );

	/**
	 * Set inbound stock for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $inbound_stock
	 */
	public function set_inbound_stock( $inbound_stock );

	/**
	 * Set stock on hold for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $stock_on_hold
	 */
	public function set_stock_on_hold( $stock_on_hold );

	/**
	 * Set sold today for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $sold_today
	 */
	public function set_sold_today( $sold_today );

	/**
	 * Set sales last days for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $sales_last_days
	 */
	public function set_sales_last_days( $sales_last_days );

	/**
	 * Set reserved stock for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $reserved_stock
	 */
	public function set_reserved_stock( $reserved_stock );

	/**
	 * Set customer returns for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $customer_returns
	 */
	public function set_customer_returns( $customer_returns );

	/**
	 * Set warehouse damages for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $warehouse_damage
	 */
	public function set_warehouse_damage( $warehouse_damage );

	/**
	 * Set lost in post for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $lost_in_post
	 */
	public function set_lost_in_post( $lost_in_post );

	/**
	 * Set other logs for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $other_logs
	 */
	public function set_other_logs( $other_logs );

	/**
	 * Set out of stock days for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int $out_stock_days
	 */
	public function set_out_stock_days( $out_stock_days );

	/**
	 * Set lost sales for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param int|float $lost_sales
	 */
	public function set_lost_sales( $lost_sales );

	/**
	 * Set whether the product has any linked location.
	 *
	 * @since 1.5.8
	 *
	 * @param string|bool $has_location Whether the product is inheritable by others.
	 */
	public function set_has_location( $has_location );

	/**
	 * Set update date for the current product.
	 *
	 * @since 1.5.8
	 *
	 * @param string|integer|null $update_date UTC timestamp, or ISO 8601 DateTime.
	 *                                         If the DateTime string has no timezone or offset, WordPress site timezone will be assumed.
	 *                                         Null if there is no date.
	 */
	public function set_update_date( $update_date = NULL );

	/**
	 * Set the ATUM stock status for the current product.
	 *
	 * @since 1.6.6
	 *
	 * @param string $atum_stock_status
	 */
	public function set_atum_stock_status( $atum_stock_status = 'instock' );

	/**
	 * Set whether the product is on restock status.
	 *
	 * @since 1.6.6
	 *
	 * @param string|bool $restock_status
	 */
	public function set_restock_status( $restock_status );

	/**
	 * Set sales properties update date for the current product.
	 *
	 * @since 1.9.6.1
	 *
	 * @param string|integer|null $sales_update_date UTC timestamp, or ISO 8601 DateTime.
	 *                                               If the DateTime string has no timezone or offset, WordPress site timezone will be assumed.
	 *                                               Null if there is no date.
	 */
	public function set_sales_update_date( $sales_update_date = NULL );

	/**
	 * Set the product's barcode.
	 *
	 * @since 1.9.18
	 *
	 * @param string $barcode
	 */
	public function set_barcode( $barcode );

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
	public function set_minimum_threshold( $minimum_threshold );

	/**
	 * Set available to purchase per user for the current product.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param int|string $available_to_purchase Empty string if value not set.
	 */
	public function set_available_to_purchase( $available_to_purchase );

	/**
	 * Set selling priority for the current product.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param int|string $selling_priority Empty string if value not set.
	 */
	public function set_selling_priority( $selling_priority );

	/**
	 * Set calculated stock for the current product.
	 *
	 * @since   1.5.8
	 * @package Product Levels
	 *
	 * @param int|float|string $calculated_stock Empty string if value not set.
	 */
	public function set_calculated_stock( $calculated_stock );

	/**
	 * Set the product is BOM prop.
	 *
	 * @since 1.7.8
	 * @package Product Levels
	 *
	 * @param string|bool $is_bom Whether the product is a BOM.
	 */
	public function set_is_bom( $is_bom );

	/**
	 * Set the inventory iteration for the current product.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $inventory_iteration Allowed values: NULL, 'use_next' and 'out_of_stock'.
	 */
	public function set_inventory_iteration( $inventory_iteration );

	/**
	 * Set the multi inventory status for the current product.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $multi_inventory Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_multi_inventory( $multi_inventory );

	/**
	 * Set the inventory sorting mode for the current product.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $inventory_sorting_mode Allowed values: NULL, 'fifo', 'lifo', 'bbe' and 'manual'.
	 */
	public function set_inventory_sorting_mode( $inventory_sorting_mode );

	/**
	 * Set the inventory expiration mode for the current product.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $expirable_inventories Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_expirable_inventories( $expirable_inventories );

	/**
	 * Set the price per inventory for the current product.
	 *
	 * @since   1.7.1
	 * @package Multi-Inventory
	 *
	 * @param string $price_per_inventory Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_price_per_inventory( $price_per_inventory );

	/**
	 * Set the selectable inventories prop for the current product.
	 *
	 * @since   1.7.4
	 * @package Multi-Inventory
	 *
	 * @param string $selectable_inventories Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_selectable_inventories( $selectable_inventories );

	/**
	 * Set the selectable inventories mode for the current product.
	 *
	 * @since   1.7.4
	 * @package Multi-Inventory
	 *
	 * @param string $selectable_inventories_mode Allowed values: NULL, 'dropdown', 'list'.
	 */
	public function set_selectable_inventories_mode( $selectable_inventories_mode );

	/**
	 * Set the Show "Write-Off" inventories option for the current product.
	 *
	 * @since   1.7.4
	 * @package Multi-Inventory
	 *
	 * @param string $show_write_off_inventories Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_show_write_off_inventories( $show_write_off_inventories );

	/**
	 * Set the Show "No-Stock" inventories option for the current product.
	 *
	 * @since   1.9.0.1
	 * @package Multi-Inventory
	 *
	 * @param string $show_out_of_stock_inventories Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_show_out_of_stock_inventories( $show_out_of_stock_inventories );

	/**
	 * Set committed stock to WC Orders fpr the current product
	 *
	 * @since   1.9.20.3
	 * @package SOnly
	 *
	 * @param int|float|string|NULL $committed_to_wc
	 */
	public function set_committed_to_wc( $committed_to_wc );

	/**
	 * Set calculated backorders for the current product
	 *
	 * @since   1.9.20.4
	 * @package Product Levels
	 *
	 * @param int|float|string|NULL $calc_backorders
	 */
	public function set_calc_backorders( $calc_backorders );

	/**
	 * Set barcode type for the current product
	 *
	 * @since   1.9.30
	 * @package Barcodes PRO
	 *
	 * @param string|NULL $barcode_type For "global", set it to NULL.
	 */
	public function set_barcode_type( $barcode_type );

	/**
	 * Set UOM status for the current product
	 *
	 * @since   1.9.34
	 * @package Units of Measure
	 *
	 * @param string|NULL $uom_status For "global", set it to NULL.
	 */
	public function set_uom_status( $uom_status );

	/**
	 * Set measure type for the current product
	 *
	 * @since   1.9.34
	 * @package Units of Measure
	 *
	 * @param string $measure_type
	 */
	public function set_measure_type( $measure_type );

	/**
	 * Set measure unit for the current product
	 *
	 * @since   1.9.34
	 * @package Units of Measure
	 *
	 * @param string $measure_unit
	 */
	public function set_measure_unit( $measure_unit );

	/**
	 * Set the Low Stock Threshold By Inventory option for the current product.
	 *
	 * @since   1.9.33
	 * @package Multi-Inventory
	 *
	 * @param string $low_stock_threshold_by_inventory Allowed values: NULL, 'yes' and 'no'.
	 */
	public function set_low_stock_threshold_by_inventory( $low_stock_threshold_by_inventory );

	/**
	 * Save the ATUM product data
	 *
	 * @since 1.5.0
	 */
	public function save_atum_data();

	/**
	 * Delete the ATUM product data
	 *
	 * @since 1.5.8.2
	 */
	public function delete_atum_data();

}