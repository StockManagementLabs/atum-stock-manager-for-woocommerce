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
			'id'              => (string) $log['id'],
			'uid'             => null,
			'itemType'        => 'inventory-log',
			'name'            => null,
			'slug'            => null,
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
			'shippingLines'   => $this->prepare_shipping_lines( $log['shipping_lines'] ?? [] ),
			'feeLines'        => $this->prepare_fee_lines( $log['fee_lines'] ?? [] ),
			'notes'           => $this->prepare_notes( $log['notes'] ?? [] ),
			'metaData'        => $this->prepare_meta_data( $log['meta_data'] ?? [] ),
			'feeTotal'        => (float) ( $log['fee_total'] ?? 0 ),
			'feeTax'          => (float) ( $log['fee_tax'] ?? 0 ),
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
			'dateCompleted'   => 'date_completed',
		];

		foreach ( $date_fields as $schema_key => $source_key ) {
			if ( ! empty( $log[ $source_key ] ) ) {
				$prepared_data[ $schema_key ]      = $log[ $source_key ];
				$prepared_data["{$schema_key}GMT"] = $log["{$source_key}_gmt"] ?? '';
			}
			else {
				// Preserve null values for date fields not present in API response
				$prepared_data[ $schema_key ]      = null;
				$prepared_data["{$schema_key}GMT"] = null;
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

			return array(
				'id'          => (string) $item['id'],
				'name'        => $item['name'],
				'quantity'    => (float) $item['quantity'],
				'subtotal'    => (float) $item['subtotal'],
				'subtotalTax' => (float) ( $item['subtotal_tax'] ?? 0 ),
				'total'       => (float) $item['total'],
				'totalTax'    => (float) ( $item['total_tax'] ?? 0 ),
				'sku'         => $item['sku'] ?? '',
				'price'       => (float) ( $item['price'] ?? 0 ),
				'taxes'       => $item['taxes'] ?? [],
				'product'     => $this->prepare_ids( $item['product_id'] ??  NULL ),
				'variation'   => $this->prepare_ids( $item['variation_id'] ??  NULL ),
				'inventories' => $item['mi_inventories'] ?? [],
				'bomItems'    => $item['bom_items'] ?? [],
				'metaData'    => $this->prepare_meta_data( $item['meta_data'] ?? [] ),
				'stock'       => [
					'action'       => 'reduceStock',
					'changedStock' => isset( $item['stock_changed'] ) && $item['stock_changed'] === 'yes',
					'quantity'     => (float) $item['quantity'],
				],
				'_id'         => NULL,
				'deleted'     => FALSE,
				'_deleted'    => FALSE,
				'parent'      => NULL,
				'taxClass'    => $this->prepare_tax_class( $item['tax_class'] ?? NULL ),
			);

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
				'id'          => (string) $tax['id'],
				'rateCode'    => $tax['rate_code'] ?? '',
				'ratePercent' => (float) ( $tax['rate_percent'] ?? 0 ),
			];

		}, $taxes );

	}

	/**
	 * Prepare shipping lines
	 *
	 * @since 1.9.44
	 *
	 * @param array $shipping
	 *
	 * @return array
	 */
	private function prepare_shipping_lines( array $shipping ): array {

		return array_map( function ( $ship ) {

			return [
				'id'          => (string) $ship['id'],
				'methodTitle' => $ship['method_title'] ?? '',
				'total'       => (float) ( $ship['total'] ?? 0 ),
			];

		}, $shipping );

	}

	/**
	 * Prepare fee lines
	 *
	 * @since 1.9.44
	 *
	 * @param array $fees
	 *
	 * @return array
	 */
	private function prepare_fee_lines( array $fees ): array {

		return array_map( function ( $fee ) {

			return [
				'id'    => (string) $fee['id'],
				'name'  => $fee['name'] ?? '',
				'total' => (float) ( $fee['total'] ?? 0 ),
			];

		}, $fees );

	}

	/**
	 * Prepare notes
	 *
	 * @since 1.9.44
	 *
	 * @param array $notes
	 *
	 * @return array
	 */
	private function prepare_notes( array $notes ): array {

		return array_map( function ( $note ) {

			return [
				'id'      => (string) $note['id'],
				'content' => $note['content'] ?? '',
				'date'    => $note['date'] ?? '',
			];

		}, $notes );

	}

} 