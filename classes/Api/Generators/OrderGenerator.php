<?php
/**
 * Order generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
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

		return array_merge( $this->get_base_fields(), [
			'id'               => (string) $order['id'],
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
			'feeTotal'         => (float) ( $order['fee_total'] ?? 0 ),
			'feeTax'           => (float) ( $order['fee_tax'] ?? 0 ),
			'pricesIncludeTax' => (bool) $order['prices_include_tax'],
			'slug'             => $order['slug'] ?? NULL,
			'uid'              => $order['uid'] ?? NULL,
			'itemType'         => 'order',
			'name'             => $order['name'] ?? NULL,
			'customer'         => $this->prepare_ids( $order['customer_id'] ?? NULL ),
			'billing'          => $this->prepare_billing( $order['billing'] ?? [] ),
			'shipping'         => $this->prepare_shipping( $order['shipping'] ?? [] ),
			'lineItems'        => $this->prepare_line_items( $order['line_items'] ?? [] ),
			'feeLines'         => $this->prepare_fee_lines( $order['fee_lines'] ?? [] ),
			'shippingLines'    => $this->prepare_shipping_lines( $order['shipping_lines'] ?? [] ),
			'taxLines'         => $this->prepare_tax_lines( $order['tax_lines'] ?? [] ),
			'couponLines'      => $this->prepare_coupon_lines( $order['coupon_lines'] ?? [] ),
			'paymentLines'     => $this->prepare_payment_lines( $order ),
			'refunds'          => $this->prepare_refunds( $order['refunds'] ?? [] ),
			'notes'            => $this->prepare_notes( $order['notes'] ?? [] ),
			'inventoryLogs'    => $order['inventory_logs'] ?? [],
			'pickingPack'      => $order['picking_pack'] ?? NULL,
			'metaData'         => $this->prepare_meta_data( $order['meta_data'] ?? [] ),
		] );

	}

	/**
	 * Prepare billing data
	 *
	 * @since 1.9.44
	 *
	 * @param array $billing Raw billing data.
	 *
	 * @return array Prepared billing data.
	 */
	private function prepare_billing( array $billing ): array {
		return [
			'firstName'      => $billing['first_name'] ?? null,
			'lastName'       => $billing['last_name'] ?? null,
			'company'        => $billing['company'] ?? null,
			'address1'       => $billing['address_1'] ?? null,
			'address2'       => $billing['address_2'] ?? null,
			'city'           => $billing['city'] ?? null,
			'postcode'       => $billing['postcode'] ?? null,
			'country'        => $billing['country'] ?? null,
			'state'          => $billing['state'] ?? null,
			'email'          => $billing['email'] ?? null,
			'phone'          => $billing['phone'] ?? null,
			'paymentMethod'  => $billing['payment_method'] ?? null,
			'transactionId'  => $billing['transaction_id'] ?? null,
		];
	}

	/**
	 * Prepare shipping data
	 *
	 * @since 1.9.44
	 *
	 * @param array $shipping Raw shipping data.
	 *
	 * @return array Prepared shipping data.
	 */
	private function prepare_shipping( array $shipping ): array {
		return [
			'firstName'     => $shipping['first_name'] ?? null,
			'lastName'      => $shipping['last_name'] ?? null,
			'company'       => $shipping['company'] ?? null,
			'address1'      => $shipping['address_1'] ?? null,
			'address2'      => $shipping['address_2'] ?? null,
			'city'          => $shipping['city'] ?? null,
			'postcode'      => $shipping['postcode'] ?? null,
			'country'       => $shipping['country'] ?? null,
			'state'         => $shipping['state'] ?? null,
			'customerNote'  => $shipping['customer_note'] ?? null,
		];
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
				'_id'         => 'order-item:' . $this->generate_uuid(),
				'id'          => (string) $item['id'],
				'name'        => $item['name'],
				'quantity'    => (int) $item['quantity'],
				'metaData'    => $this->prepare_meta_data( $item['meta_data'] ?? [] ),
				'product'     => $this->prepare_ids( $item['product_id'] ?? NULL ),
				'variation'   => $this->prepare_ids( $item['variation_id'] ?? NULL ),
				'subtotal'    => (float) ( $item['subtotal'] ?? 0 ),
				'subtotalTax' => (float) ( $item['subtotal_tax'] ?? 0 ),
				'total'       => (float) ( $item['total'] ?? 0 ),
				'totalTax'    => (float) ( $item['total_tax'] ?? 0 ),
				'price'       => (float) ( $item['price'] ?? 0 ),
				'sku'         => $item['sku'] ?? '',
				'taxes'       => $item['taxes'] ?? [],
				'stock'       => $item['stock'] ?? NULL,
				'inventories' => $item['mi_inventories'] ?? [],
				'bomItems'    => $item['bom_items'] ?? [],
				'taxClass'    => $this->prepare_tax_class( $item['tax_class'] ?? NULL ),
				'parent'      => $this->prepare_ids( $item['parent_name'] ?? NULL ),
			];

		}, $line_items );
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
				'_id'       => 'order-fee:' . $this->generate_uuid(),
				'id'        => (string) $fee['id'],
				'name'      => $fee['name'] ?? '',
				'total'     => (float) ( $fee['total'] ?? 0 ),
				'totalTax'  => (float) ( $fee['total_tax'] ?? 0 ),
				'taxStatus' => $fee['tax_status'] ?? '',
				'taxClass'  => $this->prepare_tax_class( $fee['tax_class'] ?? NULL ),
			];

		}, $fee_lines );

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
				'_id'      => 'order-shipping:' . $this->generate_uuid(),
				'id'       => (string) $shipping['id'],
				'name'     => $shipping['name'] ?? '',
				'total'    => (float) ( $shipping['total'] ?? 0 ),
				'totalTax' => (float) ( $shipping['total_tax'] ?? 0 ),
			];

		}, $shipping_lines );

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
				'_id'              => 'order-tax:' . $this->generate_uuid(),
				'id'               => (string) $tax['id'],
				'label'            => $tax['label'] ?? '',
				'taxTotal'         => (float) $tax['tax_total'],
				'rate'             => (float) $tax['rate_percent'],
				'shippingTaxTotal' => (float) ( $tax['shipping_tax_total'] ?? 0 ),
				'taxClass'         => $this->prepare_tax_class( $tax['tax_class'] ?? NULL ),
			];

		}, $tax_lines );

	}

	/**
	 * Prepare coupon lines data
	 *
	 * @since 1.9.44
	 *
	 * @param array $coupon_lines Raw coupon lines data.
	 *
	 * @return array Prepared coupon lines data.
	 */
	private function prepare_coupon_lines( array $coupon_lines ): array {

		return array_map( function ( $coupon ) {

			return [
				'_id'         => 'order-coupon:' . $this->generate_uuid(),
				'id'          => (string) $coupon['id'],
				'code'        => $coupon['code'] ?? '',
				'discount'    => (float) $coupon['discount'],
				'discountTax' => (float) $coupon['discount_tax'],
			];

		}, $coupon_lines );

	}

	/**
	 * Prepare refunds data
	 *
	 * @since 1.9.44
	 *
	 * @param array $refunds Raw refunds data.
	 *
	 * @return array Prepared refunds data.
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
	 *
	 * @param array $order Raw order data.
	 *
	 * @return array Prepared payment lines data.
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

	/**
	 * Prepare notes data
	 *
	 * @since 1.9.44
	 *
	 * @param array $notes Raw notes data.
	 *
	 * @return array Prepared notes data.
	 */
	private function prepare_notes( array $notes ): array {

		return array_map( function ( $note ) {

			return [
				'_id'            => 'order-note:' . $this->generate_uuid(),
				'id'             => (string) $note['id'],
				'author'         => $note['author'] ?? '',
				'dateCreated'    => $note['date_created'] ?? '',
				'dateCreatedGMT' => $note['date_created_gmt'] ?? '',
				'note'           => $note['note'] ?? '',
				'customerNote'   => (bool) ( $note['customer_note'] ?? FALSE ),
			];

		}, $notes );

	}

} 