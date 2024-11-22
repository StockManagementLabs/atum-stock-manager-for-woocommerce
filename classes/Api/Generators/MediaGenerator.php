<?php
/**
 * Media generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class MediaGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'media';

	/**
	 * Transform media data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $media Raw media data
	 *
	 * @return array Prepared media data
	 */
	protected function prepare_data( array $media ): array {

		$prepared_data = [
			'_id'          => 'media:' . $this->generate_uuid(),
			'_rev'         => '1-' . $this->generate_revision_id(),
			'_deleted'     => FALSE,
			'_meta'        => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments' => new \stdClass(),
			'id'           => (int) $media['id'],
			'name'         => $media['title']['rendered'] ?? '',
			'slug'         => $media['slug'] ?? '',
			'alt'          => $media['alt_text'] ?? '',
			'type'         => $media['media_type'] ?? '',
			'src'          => $media['source_url'] ?? '',
			'file'         => $media['media_details']['file'] ?? '',
		];

		// Handle dates
		if ( isset( $media['date'] ) ) {
			$prepared_data['dateCreated']    = $media['date'];
			$prepared_data['dateCreatedGMT'] = $media['date_gmt'];
		}

		if ( isset( $media['modified'] ) ) {
			$prepared_data['dateModified']    = $media['modified'];
			$prepared_data['dateModifiedGMT'] = $media['modified_gmt'];
		}

		return $prepared_data;
	}

} 