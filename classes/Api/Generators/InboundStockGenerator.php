<?php
/**
 * Inbound Stock generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
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
	 * @param array $inbound_stock Raw inbound stock data
	 *
	 * @return array Prepared inbound stock data
	 */
	protected function prepare_data( array $inbound_stock ): array {

		$prepared_data = [
			// Base schema fields
			'_id'             => 'inbound-stock:' . $this->generate_uuid(),
			'_rev'            => '1-' . $this->generate_revision_id(),
			'_deleted'        => FALSE,
			'_meta'           => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments'    => new \stdClass(),

			// Required fields from schema
			'id'              => (int) $inbound_stock['id'],
			'name'            => $inbound_stock['name'],
			'type'            => $inbound_stock['type'] ?? 'simple',
			'sku'             => $inbound_stock['sku'] ?? '',

			// Image handling
			'image'           => $this->prepare_image( $inbound_stock ),

			// Date fields with GMT variants
			'dateOrdered'     => $this->format_date( $inbound_stock['date_ordered'] ),
			'dateOrderedGMT'  => $this->format_date( $inbound_stock['date_ordered_gmt'] ),
			'dateExpected'    => $this->format_date( $inbound_stock['date_expected'] ?? '' ),
			'dateExpectedGMT' => $this->format_date( $inbound_stock['date_expected_gmt'] ?? '' ),

			// Item and Purchase Order references
			'item'            => [
				'id'  => (int) $inbound_stock['id'],
				'_id' => 'product:' . $this->generate_uuid(),
			],
			'inboundStock'    => (float) $inbound_stock['inbound_stock'],
			'purchaseOrder'   => [
				'id'  => (int) $inbound_stock['purchase_order'],
				'_id' => 'purchase-order:' . $this->generate_uuid(),
			],

			// Optional fields
			'itemType'        => $inbound_stock['type'] ?? '',
			'trash'           => FALSE,
		];

		return $prepared_data;
	}

	/**
	 * Prepare image data
	 *
	 * @since 1.9.44
	 *
	 * @param array $inbound_stock
	 *
	 * @return array
	 */
	private function prepare_image( array $inbound_stock ): array {

		// Default empty image object
		return [
			'id'    => 0,
			'src'   => '',
			'title' => '',
			'alt'   => '',
		];

	}

	/**
	 * Format date with fallback
	 *
	 * @since 1.9.44
	 *
	 * @param string $date_string
	 *
	 * @return string
	 */
	private function format_date( string $date_string ): string {

		if ( empty( $date_string ) ) {
			return '';
		}

		try {
			return ( new \DateTime( $date_string ) )->format( 'c' );
		} catch ( \Exception $e ) {
			return '';
		}
	}
}
