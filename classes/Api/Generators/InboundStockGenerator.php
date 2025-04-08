<?php
/**
 * Inbound Stock generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class InboundStockGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'inbound-stock';

	/**
	 * Transform inbound stock data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $inbound_stock Raw inbound stock data.
	 *
	 * @return array Prepared inbound stock data.
	 */
	protected function prepare_data( array $inbound_stock ): array {

		return array_merge( $this->get_base_fields(), [
			// Required fields from schema.
			'id'              => (int) $inbound_stock['id'],
			'name'            => $inbound_stock['name'] ?? null,
			'itemType'        => $inbound_stock['item_type'] ?? null,
			'type'            => $inbound_stock['type'] ?? 'simple',
			'sku'             => $inbound_stock['sku'] ?? null,
			
			// Image object (null if not provided)
			'image'           => isset($inbound_stock['image']) ? [
				'id'    => $inbound_stock['image']['id'] ?? null,
				'src'   => $inbound_stock['image']['src'] ?? null,
				'alt'   => $inbound_stock['image']['alt'] ?? null,
				'title' => $inbound_stock['image']['title'] ?? null,
			] : null,

			// Date fields
			'dateOrdered'     => $inbound_stock['date_ordered'] ?? null,
			'dateOrderedGmt'  => $inbound_stock['date_ordered_gmt'] ?? null,
			'dateExpected'    => $inbound_stock['date_expected'] ?? null,
			'dateExpectedGmt' => $inbound_stock['date_expected_gmt'] ?? null,
			
			// Item reference
			'item'            => $this->prepare_ids($inbound_stock['id'] ?? null),
			
			// Numeric fields
			'inboundStock'    => (float) ($inbound_stock['inbound_stock'] ?? 0),
			'purchaseOrder'   => isset($inbound_stock['purchase_order']) ? (int) $inbound_stock['purchase_order'] : null,

			// Boolean fields
			'trash'           => false,
			'deleted'         => false,
		]);
	}

}
