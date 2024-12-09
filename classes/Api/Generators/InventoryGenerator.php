<?php
/**
 * Inventory generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
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
		if ( ! empty( $inventory['region'] ) ) {
			foreach ( $inventory['region'] as $region_id => $region_name ) {
				$prepared_regions[] = $this->prepare_ids( $region_id );
			}
		}

		// Prepare locations.
		$prepared_locations = [];
		if ( ! empty( $inventory['location'] ) ) {
			foreach ( $inventory['location'] as $location_id => $location_name ) {
				$prepared_locations[] = [
					'id'   => (int) $location_id,
					'name' => $location_name,
				];
			}
		}

		$prepared_data = [
			'type'              => 'inventory',
			'id'                => (int) $inventory['id'],
			'name'              => $inventory['name'],
			'priority'          => (int) $inventory['priority'],
			'isMain'            => (bool) $inventory['is_main'],
			'lot'               => $inventory['lot'],
			'writeOff'          => (bool) $inventory['write_off'],
			'region'            => $prepared_regions,
			'locations'         => $prepared_locations,
			'expiryDays'        => (int) ( $inventory['expiry_threshold'] ?? 0 ),
			'sku'               => $inventory['sku'] ?? '',
			'manageStock'       => (bool) ( $inventory['manage_stock'] ?? FALSE ),
			'stockQuantity'     => $inventory['stock_quantity'] !== NULL ? (float) $inventory['stock_quantity'] : NULL,
			'backorders'        => $inventory['backorders'] ?? 'no',
			'stockStatus'       => $inventory['stock_status'] ?? '',
			'barcode'           => $inventory['barcode'] ?? '',
			'soldIndividually'  => (bool) ( $inventory['sold_individually'] ?? FALSE ),
			'outStockThreshold' => isset( $inventory['out_stock_threshold'] ) ? (float) $inventory['out_stock_threshold'] : NULL,
			'purchasePrice'     => isset( $inventory['purchase_price'] ) ? (float) $inventory['purchase_price'] : NULL,
			'price'             => isset( $inventory['price'] ) ? (float) $inventory['price'] : NULL,
			'regularPrice'      => isset( $inventory['regular_price'] ) ? (float) $inventory['regular_price'] : NULL,
			'salePrice'         => isset( $inventory['sale_price'] ) ? (float) $inventory['sale_price'] : NULL,
			'supplier'          => $this->prepare_ids( $inventory['supplier_id'] ?? NULL ),
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
		];

		// Handle dates with proper format.
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

		$dates = [
			'inventoryDate'  => 'inventory_date',
			'updateDate'     => 'update_date',
			'bbeDate'        => 'bbe_date',
			'dateOnSaleFrom' => 'date_on_sale_from',
			'dateOnSaleTo'   => 'date_on_sale_to',
			'outStockDate'   => 'out_stock_date',
		];

		foreach ( $dates as $schemaKey => $sourceKey ) {
			if ( ! empty( $inventory[ $sourceKey ] ) ) {
				$prepared_data[ $schemaKey ]      = $inventory[ $sourceKey ];
				$prepared_data["{$schemaKey}GMT"] = str_replace( 'T', ' ', $inventory["{$sourceKey}_gmt"] ?? '' );
			}
		}

	}

} 