<?php
/**
 * Tax Class generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class TaxClassGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'tax-class';

	/**
	 * Transform tax class data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $tax_class Raw tax class data
	 *
	 * @return array Prepared tax class data
	 */
	protected function prepare_data( array $tax_class ): array {

		return [
			'_id'          => 'tax-class:' . $this->generate_uuid(),
			'_rev'         => '1-' . $this->generate_revision_id(),
			'_deleted'     => false,
			'_meta'        => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments' => new \stdClass(),
			'slug'         => $tax_class['slug'],
			'name'         => $tax_class['name'],
			'conflict'     => false,
		];
	}

} 