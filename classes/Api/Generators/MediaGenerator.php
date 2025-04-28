<?php
/**
 * Media generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
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

		return array_merge( $this->get_base_fields(), [
			'id'              => (string) $media['id'],
			'name'            => $media['title']['rendered'] ?? '',
			'slug'            => $media['slug'] ?? NULL,
			'alt'             => $media['alt_text'] ?? NULL,
			'type'            => $media['media_type'] ?? NULL,
			'src'             => $media['source_url'] ?? '',
			'file'            => $media['media_details']['file'] ?? NULL,
			'dateCreated'     => $media['date'] ?? NULL,
			'dateCreatedGMT'  => $media['date_gmt'] ?? NULL,
			'dateModified'    => $media['modified'] ?? NULL,
			'dateModifiedGMT' => $media['modified_gmt'] ?? NULL,
			'uid'             => NULL,
			'itemType'        => 'media',
		] );

	}

} 