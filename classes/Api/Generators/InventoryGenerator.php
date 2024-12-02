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
	 * @param array $inventory Raw inventory data
	 *
	 * @return array Prepared inventory data
	 */
	protected function prepare_data( array $inventory ): array {

		$prepared_data = [
			'_id'             => $this->schema_name . ':' . $this->generate_uuid(),
			'_rev'            => $this->revision,
			'_deleted'        => FALSE,
			'_meta'           => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments'    => new \stdClass(),
			'type'            => 'inventory',
			'id'              => (int) $inventory['id'],
			'name'            => $inventory['name'],
			'priority'        => (int) $inventory['priority'],
			'isMain'          => (bool) $inventory['is_main'],
			'lot'             => $inventory['lot'],
			'writeOff'        => (bool) $inventory['write_off'],
			'region'          => [], // Initialize as empty array per schema
			'locations'       => [], // Initialize as empty array per schema
			'expiryDays'      => (int) ($inventory['expiry_threshold'] ?? 0),

			// Stock numbers with proper type casting
			'inboundStock'    => (float) $inventory['inbound_stock'],
			'stockOnHold'     => (float) $inventory['stock_on_hold'],
			'soldToday'       => (float) $inventory['sold_today'],
			'salesLastDays'   => (float) $inventory['sales_last_days'],
			'reservedStock'   => (float) $inventory['reserved_stock'],
			'customerReturns' => (float) $inventory['customer_returns'],
			'warehouseDamage' => (float) $inventory['warehouse_damage'],
			'lostInPost'      => (float) $inventory['lost_in_post'],
			'otherLogs'       => (float) $inventory['other_logs'],
			'outStockDays'    => (float) $inventory['out_stock_days'],
			'lostSales'       => (float) $inventory['lost_sales']
		];

		// Handle dates with proper format
		$this->handle_dates($inventory, $prepared_data);
		
		// Handle meta data
		if (!empty($inventory['meta_data'])) {
			$this->handle_meta_data($inventory['meta_data'], $prepared_data);
		}

		// Handle parent product reference
		if (!empty($inventory['product_id'])) {
			$prepared_data['parent'] = [
				'id'  => (int) $inventory['product_id'],
				'_id' => 'product:' . $this->generate_uuid()
			];
		}

		return $prepared_data;
	}

	private function handle_dates(array $inventory, array &$prepared_data): void {
		$dates = [
			'inventoryDate' => 'inventory_date',
			'updateDate'    => 'update_date',
			'bbeDate'       => 'bbe_date'
		];

		foreach ($dates as $schemaKey => $sourceKey) {
			if (!empty($inventory[$sourceKey])) {
				$prepared_data[$schemaKey] = $inventory[$sourceKey];
				$prepared_data["{$schemaKey}GMT"] = str_replace('T', ' ', $inventory["{$sourceKey}_gmt"] ?? '');
			}
		}
	}

	private function handle_meta_data(array $meta, array &$prepared_data): void {
		// Basic meta fields
		$prepared_data += [
			'sku'               => $meta['sku'] ?? '',
			'manageStock'       => (bool) ($meta['manage_stock'] ?? false),
			'stockQuantity'     => $meta['stock_quantity'] !== null ? (float) $meta['stock_quantity'] : null,
			'backorders'        => $meta['backorders'] ?? 'no',
			'stockStatus'       => $meta['stock_status'] ?? '',
			'barcode'           => $meta['barcode'] ?? '',
			'soldIndividually'  => (bool) ($meta['sold_individually'] ?? false),
			'outStockThreshold' => $meta['out_stock_threshold'] ? (float) $meta['out_stock_threshold'] : null,
			'purchasePrice'     => $meta['purchase_price'] ? (float) $meta['purchase_price'] : null,
			'price'             => $meta['price'] ? (float) $meta['price'] : null,
			'regularPrice'      => $meta['regular_price'] ? (float) $meta['regular_price'] : null,
			'salePrice'         => $meta['sale_price'] ? (float) $meta['sale_price'] : null
		];

		// Handle supplier
		if (!empty($meta['supplier_id'])) {
			$prepared_data['supplier'] = [
				'id'  => (int) $meta['supplier_id'],
				'_id' => 'supplier:' . $this->generate_uuid()
			];
			$prepared_data['supplierSku'] = $meta['supplier_sku'] ?? '';
		}

		// Handle sale dates
		$this->handle_sale_dates($meta, $prepared_data);
	}

	private function handle_sale_dates(array $meta, array &$prepared_data): void {
		$sale_dates = [
			'dateOnSaleFrom' => 'date_on_sale_from',
			'dateOnSaleTo'   => 'date_on_sale_to',
			'outStockDate'   => 'out_stock_date'
		];

		foreach ($sale_dates as $schemaKey => $sourceKey) {
			if (isset($meta[$sourceKey]) && $meta[$sourceKey] !== null) {
				$prepared_data[$schemaKey] = $meta[$sourceKey];
				if (isset($meta["{$sourceKey}_gmt"])) {
					$prepared_data["{$schemaKey}GMT"] = $meta["{$sourceKey}_gmt"];
				}
			}
		}
	}

} 