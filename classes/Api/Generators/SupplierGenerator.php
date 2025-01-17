<?php
/**
 * Supplier generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class SupplierGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'supplier';

	/**
	 * Transform supplier data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $supplier Raw supplier data
	 *
	 * @return array Prepared supplier data
	 */
	protected function prepare_data( array $supplier ): array {

		return array_merge( $this->get_base_fields(), [
			'id'                => (string) $supplier['id'],
			'name'              => $supplier['name'] ?? '',
			'slug'              => $supplier['slug'] ?? '',
			'permalink'         => $supplier['permalink'] ?? '',
			'date_created'      => $supplier['date_created'] ?? '',
			'date_created_gmt'  => $supplier['date_created_gmt'] ?? '',
			'date_modified'     => $supplier['date_modified'] ?? '',
			'date_modified_gmt' => $supplier['date_modified_gmt'] ?? '',
			'status'            => $supplier['status'] ?? 'publish',
			'type'              => 'supplier',
		] );

	}

} 