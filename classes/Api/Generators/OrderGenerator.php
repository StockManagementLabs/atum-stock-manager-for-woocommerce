<?php
/**
 * Order generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class OrderGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'order';

	/**
	 * Transform order data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $order Raw order data
	 *
	 * @return array Prepared order data
	 */
	protected function prepare_data( array $order ): array {

		$prepared_data = [
			'_id'              => $this->schema_name . ':' . $this->generate_uuid(),
			'_rev'             => $this->revision,
			'_deleted'         => FALSE,
			'_meta'            => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments'     => new \stdClass(),
			'id'               => (int) $order['id'],
			'status'           => $order['status'],
			'currency'         => $order['currency'],
			'dateCreated'      => $order['date_created'],
			'dateCreatedGMT'   => $order['date_created_gmt'],
			'dateModified'     => $order['date_modified'],
			'dateModifiedGMT'  => $order['date_modified_gmt'],
			'total'            => (float) $order['total'],
			'totalTax'         => (float) $order['total_tax'],
			'discountTotal'    => (float) $order['discount_total'],
			'discountTax'      => (float) $order['discount_tax'],
			'shippingTotal'    => (float) $order['shipping_total'],
			'shippingTax'      => (float) $order['shipping_tax'],
			'pricesIncludeTax' => (bool) $order['prices_include_tax'],
			'trash'            => FALSE,
			'conflict'         => FALSE
		];

		// Handle customer
		if ( ! empty( $order['customer_id'] ) ) {
			$prepared_data['customer'] = [
				'id'  => (int) $order['customer_id'],
				'_id' => 'customer:' . $this->generate_uuid(),
			];
		}

		// Handle line items with proper schema validation
		$prepared_data['lineItems'] = $this->prepare_line_items( $order['line_items'] ?? [] );

		// Handle refunds (required by schema)
		$prepared_data['refunds'] = $this->prepare_refunds( $order['refunds'] ?? [] );

		// Handle other lines
		$prepared_data['taxLines'] = $this->prepare_tax_lines( $order['tax_lines'] ?? [] );
		$prepared_data['couponLines'] = $this->prepare_coupon_lines( $order['coupon_lines'] ?? [] );
		$prepared_data['paymentLines'] = $this->prepare_payment_lines( $order );

		return $prepared_data;
	}

	/**
	 * Prepare line items data
	 *
	 * @since 1.9.44
	 *
	 * @param array $line_items Raw line items data
	 */
	private function prepare_line_items( array $line_items ): array {

		return array_map( function ( $item ) {

			$prepared_item = [
				'_id'      => 'order-item:' . $this->generate_uuid(),
				'id'       => (int) $item['id'],
				'name'     => $item['name'],
				'quantity' => (int) $item['quantity'],
				'metaData' => $this->prepare_meta_data( $item['meta_data'] ),
			];

			if ( ! empty( $item['product_id'] ) ) {
				$prepared_item['product'] = [
					'id'  => (int) $item['product_id'],
					'_id' => 'product:' . $this->generate_uuid(),
				];
			}

			if ( ! empty( $item['variation_id'] ) ) {
				$prepared_item['variation'] = [
					'id'  => (int) $item['variation_id'],
					'_id' => 'product-variation:' . $this->generate_uuid(),
				];
			}

			return $prepared_item;
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
				'_id'      => 'order-tax:' . $this->generate_uuid(),
				'id'       => (int) $tax['id'],
				'label'    => $tax['label'],
				'taxTotal' => (float) $tax['tax_total'],
				'rate'     => (float) $tax['rate_percent'],
			];
		}, $tax_lines );
	}

	/**
	 * Prepare coupon lines data
	 *
	 * @since 1.9.44
	 */
	private function prepare_coupon_lines( array $coupon_lines ): array {

		return array_map( function ( $coupon ) {

			return [
				'_id'         => 'order-coupon:' . $this->generate_uuid(),
				'id'          => (int) $coupon['id'],
				'code'        => $coupon['code'],
				'discount'    => (float) $coupon['discount'],
				'discountTax' => (float) $coupon['discount_tax'],
			];
		}, $coupon_lines );
	}

	/**
	 * Prepare refunds data
	 *
	 * @since 1.9.44
	 */
	private function prepare_refunds( array $refunds ): array {

		return array_map( function ( $refund ) {

			return [
				'reason' => $refund['reason'] ?? '',
				'amount' => (float) str_replace( '-', '', $refund['total'] ?? 0 ),
			];
		}, $refunds );
	}

	/**
	 * Prepare payment lines data
	 *
	 * @since 1.9.44
	 */
	private function prepare_payment_lines( array $order ): array {

		$payment_lines = [];

		if ( ! empty( $order['payment_method'] ) && ! empty( $order['total'] ) ) {
			$payment_lines[] = [
				'_id'    => 'order-payment:' . $this->generate_uuid(),
				'method' => $order['payment_method'],
				'amount' => (float) $order['total']
			];
		}

		return $payment_lines;
	}

} 