<?php
/**
 * Location generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class LocationGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'location';

	/**
	 * Transform location data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $location Raw location data
	 *
	 * @return array Prepared location data
	 */
	protected function prepare_data( array $location ): array {

		return array_merge( $this->get_base_fields(), [
			'id'          => (string) $location['id'],
			'name'        => $location['name'],
			'slug'        => $location['slug'],
			'description' => $location['description'] ?? '',
			'barcode'     => $location['barcode'] ?? '',
			'code'        => $location['code'] ?? '',
			'count'       => (int) ($location['count'] ?? 0),
			'parent'      => $this->prepare_ids( $location['parent'] ?? NULL ),
			'itemType'    => 'location',
		] );

	}

} 