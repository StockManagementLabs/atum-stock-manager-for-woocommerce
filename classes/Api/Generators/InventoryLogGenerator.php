<?php
/**
 * Inventory Log generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class InventoryLogGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'inventory-log';

	/**
	 * Prepare inventory log data according to schema
	 *
	 * @since 1.9.44
	 *
	 * @param array $log Raw inventory log data
	 *
	 * @return array Prepared inventory log data
	 */
	protected function prepare_data( array $log ): array {

		$prepared_data = [
			'_id'             => 'inventory-log:' . $this->generate_uuid(),
			'_rev'            => '1-' . $this->generate_revision_id(),
			'_deleted'        => FALSE,
			'_meta'           => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments'    => new \stdClass(),
			'id'              => (int) $log['id'],
			'type'            => $log['type'],
			'status'          => $log['status'],
			'currency'        => $log['currency'],
			'description'     => $log['description'] ?? '',
			'shippingCompany' => $log['shipping_company'] ?? '',
			'customName'      => $log['custom_name'] ?? '',
			'total'           => (float) $log['total'],
			'totalTax'        => (float) $log['total_tax'],
			'discountTotal'   => (float) $log['discount_total'],
			'discountTax'     => (float) $log['discount_tax'],
			'shippingTotal'   => (float) $log['shipping_total'],
			'shippingTax'     => (float) $log['shipping_tax'],
			'trash'           => FALSE,
			'conflict'        => FALSE,
		];

		// Handle dates
		$this->handle_dates($log, $prepared_data);

		// Handle line items
		$prepared_data['lineItems'] = $this->prepare_line_items($log['line_items'] ?? []);

		// Handle tax lines
		$prepared_data['taxLines'] = $this->prepare_tax_lines($log['tax_lines'] ?? []);

		// Handle meta data
		$prepared_data['metaData'] = $this->prepare_meta_data($log['meta_data'] ?? []);

		// Handle order reference
		if ( ! empty( $log['order'] ) && $log['order'] !== FALSE ) {
			$prepared_data['order'] = [
				'id'  => (int) $log['order'],
					'_id' => 'order:' . $this->generate_uuid(),
			];
		}

		return $prepared_data;
	}

	private function handle_dates( array $log, array &$prepared_data ): void {
		// Standard dates
		$standard_dates = [
			'dateCreated'   => 'date_created',
			'dateModified'  => 'date_modified'
		];

		foreach ( $standard_dates as $schemaKey => $sourceKey ) {
			if ( ! empty( $log[ $sourceKey ] ) ) {
				$prepared_data[ $schemaKey ] = $log[ $sourceKey ];
				$prepared_data[ "{$schemaKey}GMT" ] = $log[ "{$sourceKey}_gmt" ] ?? '';
			}
		}

		// Special dates
		$special_dates = [
			'return'      => 'return_date',
			'reservation' => 'reservation_date',
			'damage'      => 'damage_date'
		];

		foreach ( $special_dates as $type => $sourceKey ) {
			if ( ! empty( $log[ $sourceKey ] ) ) {
				$prepared_data[ "date" . ucfirst( $type ) ] = $log[ $sourceKey ];
				$prepared_data[ "date" . ucfirst( $type ) . "GMT" ] = $log[ "{$sourceKey}_gmt" ] ?? '';
			}
		}
	}

	private function prepare_line_items( array $items ): array {
		return array_map( function ( $item ) {
			return [
				'id'       => (int) $item['id'],
				'name'     => $item['name'],
				'quantity' => (float) $item['quantity'],
				'subtotal' => (float) $item['subtotal'],
				'total'    => (float) $item['total'],
				'sku'      => $item['sku'] ?? ''
			];
		}, $items );
	}

	private function prepare_tax_lines( array $taxes ): array {
		return array_map( function ( $tax ) {
			return [
				'id'          => (int) $tax['id'],
				'rateCode'    => $tax['rate_code'] ?? '',
				'ratePercent' => (float) ( $tax['rate_percent'] ?? 0 )
			];
		}, $taxes );
	}

	private function prepare_meta_data( array $meta_data ): array {
		return array_map( function ( $meta ) {
			return [
				'id'    => (int) $meta['id'],
				'key'   => $meta['key'],
				'value' => $meta['value']
			];
		}, $meta_data );
	}

} 