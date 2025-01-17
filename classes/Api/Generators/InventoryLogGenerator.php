<?php
/**
 * Inventory Log generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
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

		$prepared_data = array_merge( $this->get_base_fields(), [
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
			'order'           => $this->prepare_ids( $log['order'] ?? NULL ),
			'lineItems'       => $this->prepare_line_items( $log['line_items'] ?? [] ),
			'taxLines'        => $this->prepare_tax_lines( $log['tax_lines'] ?? [] ),
			'metaData'        => $this->prepare_meta_data( $log['meta_data'] ?? [] ),
			'trash'           => FALSE,
			'conflict'        => FALSE,
		] );

		// Handle dates.
		$this->handle_dates( $log, $prepared_data );

		return $prepared_data;

	}

	/**
	 * Handle dates
	 *
	 * @since 1.9.44
	 *
	 * @param array $log
	 * @param array $prepared_data
	 */
	private function handle_dates( array $log, array &$prepared_data ) {

		$date_fields = [
			'dateCreated'     => 'date_created',
			'dateModified'    => 'date_modified',
			'dateReturn'      => 'return_date',
			'dateReservation' => 'reservation_date',
			'dateDamage'      => 'damage_date',
		];

		foreach ( $date_fields as $schema_key => $source_key ) {
			if ( ! empty( $log[ $source_key ] ) ) {
				$prepared_data[ $schema_key ]      = $log[ $source_key ];
				$prepared_data["{$schema_key}GMT"] = $log["{$source_key}_gmt"] ?? '';
			}
		}

	}

	/**
	 * Prepare line items
	 *
	 * @since 1.9.44
	 *
	 * @param array $items
	 *
	 * @return array
	 */
	private function prepare_line_items( array $items ): array {

		return array_map( function ( $item ) {

			return [
				'id'       => (int) $item['id'],
				'name'     => $item['name'],
				'quantity' => (float) $item['quantity'],
				'subtotal' => (float) $item['subtotal'],
				'total'    => (float) $item['total'],
				'sku'      => $item['sku'] ?? '',
			];

		}, $items );

	}

	/**
	 * Prepare tax lines
	 *
	 * @since 1.9.44
	 *
	 * @param array $taxes
	 *
	 * @return array
	 */
	private function prepare_tax_lines( array $taxes ): array {

		return array_map( function ( $tax ) {

			return [
				'id'          => (int) $tax['id'],
				'rateCode'    => $tax['rate_code'] ?? '',
				'ratePercent' => (float) ( $tax['rate_percent'] ?? 0 ),
			];

		}, $taxes );

	}

} 