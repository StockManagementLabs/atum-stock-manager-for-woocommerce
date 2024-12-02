<?php
/**
 * Purchase Order generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class PurchaseOrderGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'purchase-order';

	/**
	 * Transform purchase order data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $purchase_order Raw purchase order data
	 *
	 * @return array Prepared purchase order data
	 */
	protected function prepare_data( array $purchase_order ): array {

		$prepared_data = [
			'_id'               => $this->schema_name . ':' . $this->generate_uuid(),
			'_rev'              => $this->revision,
			'_deleted'          => false,
			'_meta'             => [
				'lwt' => $this->generate_timestamp()
			],
			'_attachments'      => new \stdClass(),
			'id'                => (int) $purchase_order['id'],
			'status'            => $purchase_order['status'],
			'currency'          => $purchase_order['currency'],
			'multipleSuppliers' => (bool) $purchase_order['multiple_suppliers'],
			'dateCreated'       => $purchase_order['date_created'],
			'dateCreatedGMT'    => $purchase_order['date_created_gmt'],
			'dateModified'      => $purchase_order['date_modified'],
			'dateModifiedGMT'   => $purchase_order['date_modified_gmt'],
			'dateCompleted'     => $purchase_order['date_completed'] ?? null,
			'dateCompletedGMT'  => $purchase_order['date_completed_gmt'] ?? null,
			'dateExpected'      => $purchase_order['date_expected'] ?? null,
			'dateExpectedGMT'   => $purchase_order['date_expected_gmt'] ?? null,
			'description'       => $purchase_order['description'] ?? '',
			'total'             => (float) ($purchase_order['total'] ?? 0),
			'totalTax'          => (float) ($purchase_order['total_tax'] ?? 0),
			'discountTotal'     => (float) ($purchase_order['discount_total'] ?? 0),
			'discountTax'       => (float) ($purchase_order['discount_tax'] ?? 0),
			'shippingTotal'     => (float) ($purchase_order['shipping_total'] ?? 0),
			'shippingTax'       => (float) ($purchase_order['shipping_tax'] ?? 0),
			'trash'             => false,
			'conflict'          => false
		];

		if (!empty($purchase_order['supplier'])) {
			$prepared_data['supplier'] = $this->prepare_supplier($purchase_order['supplier'] ?? null);
		}

		$prepared_data['lineItems'] = $this->prepare_line_items($purchase_order['line_items'] ?? []);
		$prepared_data['taxLines'] = $this->prepare_tax_lines($purchase_order['tax_lines'] ?? []);
		$prepared_data['shippingLines'] = $this->prepare_shipping_lines($purchase_order['shipping_lines'] ?? []);
		$prepared_data['feeLines'] = $this->prepare_fee_lines($purchase_order['fee_lines'] ?? []);
		$prepared_data['metaData'] = $this->prepare_meta_data($purchase_order['meta_data'] ?? []);

		return $prepared_data;
	}

	/**
	 * Prepare supplier data
	 *
	 * @since 1.9.44
	 *
	 * @param int|null $supplier_id Supplier ID
	 */
	private function prepare_supplier( ?int $supplier_id ): ?array {

		if ( ! $supplier_id ) {
			return NULL;
		}

		return [
			'id'  => $supplier_id,
			'_id' => 'supplier:' . $this->generate_uuid(),
		];
	}

	/**
	 * Prepare line items data
	 *
	 * @since 1.9.44
	 */
	private function prepare_line_items( array $line_items ): array {

		return array_map( function ( $item ) {

			return [
				'_id'         => 'purchase-order-item:' . $this->generate_uuid(),
				'id'          => (int) $item['id'],
				'name'        => $item['name'],
				'quantity'    => (int) $item['quantity'],
				'subtotal'    => (float) $item['subtotal'],
				'subtotalTax' => (float) $item['subtotal_tax'],
				'total'       => (float) $item['total'],
				'totalTax'    => (float) $item['total_tax'],
				'sku'         => $item['sku'] ?? '',
				'metaData'    => $this->prepare_meta_data( $item['meta_data'] ),
				'_deleted'    => FALSE,
			];
		}, $line_items );
	}

	/**
	 * Prepare tax lines data
	 *
	 * @since 1.9.44
	 */
	private function prepare_tax_lines( array $tax_lines ): array {

		return array_map( function ( $tax ) {

			return [
				'_id'              => 'purchase-order-tax:' . $this->generate_uuid(),
				'id'               => (int) $tax['id'],
				'rateCode'         => $tax['rate_code'] ?? '',
				'rateId'           => (string) ( $tax['rate_id'] ?? '' ),
				'label'            => $tax['label'] ?? '',
				'compound'         => (bool) ( $tax['compound'] ?? FALSE ),
				'taxTotal'         => (string) ( $tax['tax_total'] ?? '0' ),
				'shippingTaxTotal' => (string) ( $tax['shipping_tax_total'] ?? '0' ),
				'_deleted'         => FALSE,
			];
		}, $tax_lines );
	}

	/**
	 * Prepare shipping lines data
	 *
	 * @since 1.9.44
	 */
	private function prepare_shipping_lines( array $shipping_lines ): array {

		return array_map( function ( $shipping ) {

			return [
				'_id'      => 'purchase-order-shipping:' . $this->generate_uuid(),
				'id'       => (int) $shipping['id'],
				'name'     => $shipping['method_title'] ?? '',
				'total'    => (float) ( $shipping['total'] ?? 0 ),
				'totalTax' => (float) ( $shipping['total_tax'] ?? 0 ),
				'_deleted' => FALSE,
			];
		}, $shipping_lines );
	}

	/**
	 * Prepare fee lines data
	 *
	 * @since 1.9.44
	 */
	private function prepare_fee_lines( array $fee_lines ): array {

		return array_map( function ( $fee ) {

			return [
				'_id'      => 'purchase-order-fee:' . $this->generate_uuid(),
				'id'       => (int) $fee['id'],
				'name'     => $fee['name'] ?? '',
				'total'    => (float) ( $fee['total'] ?? 0 ),
				'totalTax' => (float) ( $fee['total_tax'] ?? 0 ),
				'_deleted' => FALSE,
			];
		}, $fee_lines );
	}

} 