<?php
/**
 * Payment Method generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class PaymentMethodGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'payment-method';

	/**
	 * Transform payment method data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $payment_method Raw payment method data
	 *
	 * @return array Prepared payment method data
	 */
	protected function prepare_data( array $payment_method ): array {

		return [
			'_id'          => 'payment-method:' . $this->generate_uuid(),
			'_rev'         => '1-' . $this->generate_revision_id(),
			'_deleted'     => FALSE,
			'_meta'        => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments' => new \stdClass(),
			'id'           => (int) $payment_method['id'],
			'slug'         => sanitize_title( $payment_method['method_title'] ),
			'name'         => $payment_method['method_title'],
			'enabled'      => (bool) $payment_method['enabled'],
			'default'      => FALSE,
		];
	}

} 