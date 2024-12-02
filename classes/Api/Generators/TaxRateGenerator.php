<?php
/**
 * Tax Rate generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class TaxRateGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'tax-rate';

	/**
	 * Transform tax rate data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $tax_rate Raw tax rate data
	 *
	 * @return array Prepared tax rate data
	 */
	protected function prepare_data( array $tax_rate ): array {

		return [
			'_id'          => $this->schema_name . ':' . $this->generate_uuid(),
			'_rev'         => $this->revision,
			'_deleted'     => FALSE,
			'_meta'        => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments' => new \stdClass(),
			'id'           => (int) $tax_rate['id'],
			'country'      => $tax_rate['country'],
			'state'        => $tax_rate['state'],
			'postcode'     => $tax_rate['postcode'],
			'city'         => $tax_rate['city'],
			'rate'         => (float) $tax_rate['rate'],
			'name'         => $tax_rate['name'],
			'priority'     => (int) $tax_rate['priority'],
			'compound'     => (bool) $tax_rate['compound'],
			'shipping'     => (bool) $tax_rate['shipping'],
			'taxClass'     => [
				'_id'   => '',
				'slug' => $tax_rate['class'] ?? 'standard',
			],
			'conflict'     => FALSE,
		];
	}

} 