<?php
/**
 * Refund generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class RefundGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'refund';

	/**
	 * Transform refund data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $refund Raw refund data
	 *
	 * @return array Prepared refund data
	 */
	protected function prepare_data( array $refund ): array {

		return array_merge( $this->get_base_fields(), [
			'id'              => (int) $refund['id'],
			'dateCreated'     => $refund['date_created'],
			'dateCreatedGMT'  => $refund['date_created_gmt'],
			'dateModified'    => $refund['date_modified'] ?? null,
			'dateModifiedGMT' => $refund['date_modified_gmt'] ?? null,
			'refundedBy'      => (string) $refund['refunded_by'],
			'reason'          => $refund['reason'],
			'amount'          => (float) $refund['amount'],
			'refundedPayment' => (bool) $refund['refunded_payment'],
			'trash'           => FALSE,
			'conflict'        => FALSE,
			'parent'          => $this->prepare_ids( $refund['parent_id'] ?? NULL ),
			'taxRate'         => $this->prepare_ids( $refund['tax_rate_id'] ?? NULL ),
			'taxClass'        => $this->prepare_ids( $refund['tax_class_id'] ?? NULL ),
			'lineItems'       => $this->prepare_line_items( $refund['line_items'] ?? [] ),
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
				'_id'      => 'refund-item:' . $this->generate_uuid(),
				'id'       => (int) $item['id'],
				'name'     => $item['name'],
				'quantity' => (float) $item['quantity'],
				'total'    => (float) $item['total'],
				'subtotal' => (float) $item['subtotal'],
				'_deleted' => FALSE,
			];

		}, $line_items );

	}

} 