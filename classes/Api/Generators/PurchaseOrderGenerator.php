<?php
/**
 * Purchase Order generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
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

		return array_merge( $this->get_base_fields(), [
			'id'                => (string) $purchase_order['id'],
			'uid'               => null,
			'name'              => null,
			'slug'              => null,
			'itemType'          => 'purchase-order',
			'status'            => $purchase_order['status'],
			'currency'          => $purchase_order['currency'],
			'multipleSuppliers' => (bool) $purchase_order['multiple_suppliers'],
			'supplier'          => $this->prepare_ids( $purchase_order['supplier'] ?? NULL ),
			'dateCreated'       => $purchase_order['date_created'] ?? '',
			'dateCreatedGMT'    => $purchase_order['date_created_gmt'] ?? '',
			'dateModified'      => $purchase_order['date_modified'] ?? '',
			'dateModifiedGMT'   => $purchase_order['date_modified_gmt'] ?? '',
			'dateCompleted'     => $purchase_order['date_completed'] ?? '',
			'dateCompletedGMT'  => $purchase_order['date_completed_gmt'] ?? '',
			'dateExpected'      => $purchase_order['date_expected'] ?? '',
			'dateExpectedGMT'   => $purchase_order['date_expected_gmt'] ?? '',
			'description'       => $purchase_order['description'] ?? null,
			'total'             => (float) ( $purchase_order['total'] ?? 0 ),
			'totalTax'          => (float) ( $purchase_order['total_tax'] ?? 0 ),
			'discountTotal'     => (float) ( $purchase_order['discount_total'] ?? 0 ),
			'discountTax'       => (float) ( $purchase_order['discount_tax'] ?? 0 ),
			'shippingTotal'     => (float) ( $purchase_order['shipping_total'] ?? 0 ),
			'shippingTax'       => (float) ( $purchase_order['shipping_tax'] ?? 0 ),
			'feeTotal'          => (float) 0,
			'feeTax'            => (float) 0,
			'lineItems'         => $this->prepare_line_items( $purchase_order['line_items'] ?? [] ),
			'taxLines'          => $this->prepare_tax_lines( $purchase_order['tax_lines'] ?? [] ),
			'shippingLines'     => $this->prepare_shipping_lines( $purchase_order['shipping_lines'] ?? [] ),
			'feeLines'          => $this->prepare_fee_lines( $purchase_order['fee_lines'] ?? [] ),
			'notes'             => [],
			'metaData'          => $this->prepare_meta_data( $purchase_order['meta_data'] ?? [] ),
		] );

	}

	/**
	 * Prepare line items data
	 *
	 * @since 1.9.44
	 *
	 * @param array $line_items Raw line items data.
	 *
	 * @return array Prepared line items data.
	 */
	private function prepare_line_items( array $line_items ): array {

		return array_map( function ( $item ) {

			return [
				'_id'         => isset( $item['id'] ) ? 'purchase-order-item:' . $this->generate_uuid() : NULL,
				'id'          => (string) $item['id'],
				'name'        => $item['name'],
				'quantity'    => (int) $item['quantity'],
				'subtotal'    => (float) $item['subtotal'],
				'subtotalTax' => (float) $item['subtotal_tax'],
				'total'       => (float) $item['total'],
				'totalTax'    => (float) $item['total_tax'],
				'sku'         => $item['sku'] ?? NULL,
				'price'       => (float) ( $item['price'] ?? 0 ),
				'product'     => NULL,
				'variation'   => NULL,
				'inventories' => [],
				'bomItems'    => [],
				'taxClass'    => $this->prepare_tax_class( $item['tax_class'] ?? NULL ),
				'taxes'       => [],
				/*'order'       => [
					'itemType' => 'purchase-order',
					'id'       => $this->prepare_ids( $item['product_id'] ?? NULL ),
					'uid'      => NULL,
				],*/
				// TODO: THIS MUST BE FIXED FOR ALL ORDERS
				'stock'       => [
					'action'       => 'reduceStock',
					'changedStock' => isset( $item['stock_changed'] ) && $item['stock_changed'] === 'yes',
					'quantity'     => (int) $item['quantity'],
				],
				'metaData'    => $this->prepare_meta_data( $item['meta_data'] ?? [] ),
				'_deleted'    => FALSE,
				'deleted'     => FALSE,
				'parent'      => NULL,
			];

		}, $line_items );

	}

	/**
	 * Prepare tax lines data
	 *
	 * @since 1.9.44
	 *
	 * @param array $tax_lines Raw tax lines data.
	 *
	 * @return array Prepared tax lines data.
	 */
	private function prepare_tax_lines( array $tax_lines ): array {

		return array_map( function ( $tax ) {

			return [
				'_id'              => 'purchase-order-tax:' . $this->generate_uuid(),
				'id'               => (string) $tax['id'],
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
	 *
	 * @param array $shipping_lines Raw shipping lines data.
	 *
	 * @return array Prepared shipping lines data.
	 */
	private function prepare_shipping_lines( array $shipping_lines ): array {

		return array_map( function ( $shipping ) {

			return [
				'_id'      => 'purchase-order-shipping:' . $this->generate_uuid(),
				'id'       => (string) $shipping['id'],
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
	 *
	 * @param array $fee_lines Raw fee lines data.
	 *
	 * @return array Prepared fee lines data.
	 */
	private function prepare_fee_lines( array $fee_lines ): array {

		return array_map( function ( $fee ) {

			return [
				'_id'      => 'purchase-order-fee:' . $this->generate_uuid(),
				'id'       => (string) $fee['id'],
				'name'     => $fee['name'] ?? '',
				'total'    => (float) ( $fee['total'] ?? 0 ),
				'totalTax' => (float) ( $fee['total_tax'] ?? 0 ),
				'_deleted' => FALSE,
			];

		}, $fee_lines );

	}

} 