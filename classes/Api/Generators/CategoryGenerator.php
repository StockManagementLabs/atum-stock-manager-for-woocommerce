<?php
/**
 * Category generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class CategoryGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'category';

	/**
	 * Prepare category data according to schema
	 *
	 * @since 1.9.44
	 *
	 * @param array $category Raw category data
	 *
	 * @return array Prepared category data
	 */
	protected function prepare_data( array $category ): array {

		// Prepare image data if exists.
		$image = NULL;
		if ( ! empty( $category['image'] ) ) {
			$image = [
				'id'    => (int) $category['image']['id'],
				'src'   => $category['image']['src'] ?? '',
				'title' => $category['image']['title'] ?? '',
				'alt'   => $category['image']['alt'] ?? '',
			];
		}

		return array_merge( $this->get_base_fields(), [
			'id'            => (string) $category['id'],
			'name'          => $category['name'],
			'slug'          => $category['slug'],
			'description'   => $category['description'] ?? NULL,
			'menuOrder'     => (int) ( $category['menu_order'] ?? 0 ),
			'parent'        => $this->prepare_ids( $category['parent'] ?? 0 ),
			'display'       => $category['display'] ?? 'default',
			'barcode'       => $category['barcode'] ?? '',
			'count'         => (int) ( $category['count'] ?? 0 ),
			'countChildren' => 0,
			'children'      => 0,
			'image'         => $image,
			'isDefault'     => wc_string_to_bool( $category['is_default'] ?? 'no' ),
			'itemType'      => 'category',
			'uid'           => NULL,
		] );

	}

} 