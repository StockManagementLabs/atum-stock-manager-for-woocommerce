<?php
/**
 * Location generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
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

		$prepared_data = [
			'_id'          => $this->schema_name . ':' . $this->generate_uuid(),
			'_rev'         => $this->revision,
			'_deleted'     => FALSE,
			'_meta'        => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments' => new \stdClass(),
			'id'           => (int) $location['id'],
			'name'         => $location['name'],
			'slug'         => $location['slug'],
			'description'  => $location['description'] ?? '',
			'barcode'      => $location['barcode'] ?? '',
			'code'         => $location['code'] ?? '',
			'count'        => (int) $location['count'],
			'conflict'     => FALSE,
		];

		// Handle parent if it exists and is not 0
		if ( ! empty( $location['parent'] ) && $location['parent'] !== 0 ) {
			$prepared_data['parent'] = [
				'id'  => (int) $location['parent'],
				'_id' => 'location:' . $this->generate_uuid(),
			];
		}

		return $prepared_data;
	}

} 