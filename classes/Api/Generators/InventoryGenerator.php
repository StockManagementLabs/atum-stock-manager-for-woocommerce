<?php
/**
 * Inventory generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class InventoryGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'inventory';

	/**
	 * Transform inventory data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $inventory Raw inventory data.
	 *
	 * @return array Prepared inventory data.
	 */
	protected function prepare_data( array $inventory ): array {

		// Prepare regions.
		$prepared_regions = [];
		if ( ! empty( $inventory['region'] ) && is_array( $inventory['region'] ) ) {
			foreach ( $inventory['region'] as $region_id => $region_name ) {
				$prepared_regions[] = $this->prepare_ids( $region_id );
			}
		}

		// Prepare locations.
		$prepared_locations = [];
		if ( ! empty( $inventory['location'] ) && is_array( $inventory['location'] ) ) {
			foreach ( $inventory['location'] as $location_id => $location_name ) {
				$prepared_locations[] = [
					'id'   => (int) $location_id,
					'name' => $location_name,
				];
			}
		}

		$prepared_data = [
			'type'              => 'inventory',
			'id'                => (string) $inventory['id'],
			'name'              => $inventory['name'],
			'priority'          => (int) $inventory['priority'],
			'isMain'            => (bool) $inventory['is_main'],
			'lot'               => $inventory['lot'],
			'writeOff'          => (bool) $inventory['write_off'],
			'region'            => $prepared_regions,
			'locations'         => $prepared_locations,
			'expiryDays'        => (int) ( $inventory['expiry_threshold'] ?? 0 ),
			'sku'               => $inventory['meta_data']['sku'] ?? '',
			'manageStock'       => (bool) ( $inventory['meta_data']['manage_stock'] ?? FALSE ),
			'stockQuantity'     => ( isset( $inventory['meta_data']['stock_quantity'] ) && ! $this->is_null_value( $inventory['meta_data']['stock_quantity'] ) ) ? (float) $inventory['meta_data']['stock_quantity'] : NULL,
			'backorders'        => $inventory['meta_data']['backorders'] ?? 'no',
			'stockStatus'       => $inventory['meta_data']['stock_status'] ?? '',
			'barcode'           => $inventory['meta_data']['barcode'] ?? '',
			'soldIndividually'  => (bool) ( $inventory['meta_data']['sold_individually'] ?? FALSE ),
			'outStockThreshold' => ( isset( $inventory['meta_data']['out_stock_threshold'] ) && ! $this->is_null_value( $inventory['meta_data']['out_stock_threshold'] ) ) ? (float) $inventory['meta_data']['out_stock_threshold'] : NULL,
			'purchasePrice'     => ( isset( $inventory['meta_data']['purchase_price'] ) && ! $this->is_null_value( $inventory['meta_data']['purchase_price'] ) ) ? (float) $inventory['meta_data']['purchase_price'] : NULL,
			'price'             => ( isset( $inventory['meta_data']['price'] ) && ! $this->is_null_value( $inventory['meta_data']['price'] ) ) ? (float) $inventory['meta_data']['price'] : NULL,
			'regularPrice'      => ( isset( $inventory['meta_data']['regular_price'] ) && ! $this->is_null_value( $inventory['meta_data']['regular_price'] ) ) ? (float) $inventory['meta_data']['regular_price'] : NULL,
			'salePrice'         => ( isset( $inventory['meta_data']['sale_price'] ) && ! $this->is_null_value( $inventory['meta_data']['sale_price'] ) ) ? (float) $inventory['meta_data']['sale_price'] : NULL,
			'supplier'          => $this->prepare_ids( $inventory['meta_data']['supplier_id'] ?? NULL ),
			'parent'            => $this->prepare_ids( $inventory['product_id'] ),
			// Stock numbers with proper type casting.
			'inboundStock'      => (float) ( $inventory['inbound_stock'] ?? 0 ),
			'stockOnHold'       => (float) ( $inventory['stock_on_hold'] ?? 0 ),
			'soldToday'         => (float) ( $inventory['sold_today'] ?? 0 ),
			'salesLastDays'     => (float) ( $inventory['sales_last_days'] ?? 0 ),
			'reservedStock'     => (float) ( $inventory['reserved_stock'] ?? 0 ),
			'customerReturns'   => (float) ( $inventory['customer_returns'] ?? 0 ),
			'warehouseDamage'   => (float) ( $inventory['warehouse_damage'] ?? 0 ),
			'lostInPost'        => (float) ( $inventory['lost_in_post'] ?? 0 ),
			'otherLogs'         => (float) ( $inventory['other_logs'] ?? 0 ),
			'outStockDays'      => (float) ( $inventory['out_stock_days'] ?? 0 ),
			'lostSales'         => (float) ( $inventory['lost_sales'] ?? 0 ),
			'calculatedStock'   => NULL,
			'expiredStock'      => $inventory['meta_data']['expired_stock'] ?? NULL,
			'shippingClass'     => NULL,
			'supplierSku'       => $inventory['meta_data']['supplier_sku'] ?? '',
			'categories'        => [],	
			'parentSku'         => NULL,
			'parentTaxClass'    => NULL,		
			'itemType'          => 'inventory',
		];

		// Handle dates with the proper format.
		$this->handle_dates( $inventory, $prepared_data );

		return array_merge( $this->get_base_fields(), $prepared_data );

	}

	/**
	 * Handle inventory dates.
	 *
	 * @since 1.9.44
	 *
	 * @param array $inventory	   The source inventory data.
	 * @param array $prepared_data The prepared inventory data.
	 */
	private function handle_dates( array $inventory, array &$prepared_data ) {

		$data_dates = [
			'inventoryDate' => 'inventory_date',
			'dateModified'  => 'update_date',
			'bbeDate'       => 'bbe_date',
		];

		$meta_data_dates = [
			'dateOnSaleFrom' => 'date_on_sale_from',
			'dateOnSaleTo'   => 'date_on_sale_to',
			'outStockDate'   => 'out_stock_date',
		];

		foreach ( array_merge( $data_dates, $meta_data_dates ) as $schema_key => $source_key ) {

			if ( array_key_exists( $schema_key, $meta_data_dates ) ) {
				$date_value = isset( $inventory['meta_data'][ $source_key ] ) ? $inventory['meta_data'][ $source_key ] : NULL;
			} 
			else {
				$date_value = isset( $inventory[ $source_key ] ) ? $inventory[ $source_key ] : NULL;
			}

			$prepared_data[ $schema_key ]      = $date_value;
			$prepared_data["{$schema_key}GMT"] = $date_value;
		}

	}

} 