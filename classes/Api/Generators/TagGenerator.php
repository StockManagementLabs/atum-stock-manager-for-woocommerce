<?php
/**
 * Tag generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class TagGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'tag';

	/**
	 * Transform tag data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $tag Raw tag data
	 *
	 * @return array Prepared tag data
	 */
	protected function prepare_data( array $tag ): array {

		return [
			'_id'          => $this->schema_name . ':' . $this->generate_uuid(),
			'_rev'         => $this->revision,
			'_deleted'     => FALSE,
			'_meta'        => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments' => new \stdClass(),
			'id'           => (int) $tag['id'],
			'name'         => $tag['name'],
			'slug'         => $tag['slug'],
			'description'  => $tag['description'] ?? '',
			'count'        => (int) ($tag['count'] ?? 0),
			'conflict'     => FALSE,
		];
	}

} 