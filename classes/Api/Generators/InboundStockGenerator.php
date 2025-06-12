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
			'id'              => (string) $inbound_stock['id'],
			'name'            => $inbound_stock['name'] ?? NULL,
			'type'            => $inbound_stock['type'] ?? 'simple',
			'sku'             => $inbound_stock['sku'] ?? NULL,

			// Date fields
			'dateOrdered'     => $inbound_stock['date_ordered'] ?? NULL,
			'dateOrderedGmt'  => $inbound_stock['date_ordered_gmt'] ?? NULL,
			'dateExpected'    => $inbound_stock['date_expected'] ?? NULL,
			'dateExpectedGmt' => $inbound_stock['date_expected_gmt'] ?? NULL,

			// Numeric fields
			'inboundStock'    => (float) ( $inbound_stock['inbound_stock'] ?? 0 ),
			'purchaseOrder'   => isset( $inbound_stock['purchase_order'] ) ? (int) $inbound_stock['purchase_order'] : NULL,
		] );

	}

}
