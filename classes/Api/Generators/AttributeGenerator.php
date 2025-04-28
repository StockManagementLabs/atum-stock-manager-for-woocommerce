<?php
/**
 * Attribute generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class AttributeGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'attribute';

	/**
	 * Prepare attribute data according to schema
	 *
	 * @since 1.9.44
	 *
	 * @param array $attribute Raw attribute data.
	 *
	 * @return array Prepared attribute data.
	 */
	protected function prepare_data( array $attribute ): array {

		$prepared_data = [
			'id'          => (string) $attribute['id'],
			'name'        => $attribute['name'],
			'slug'        => $attribute['slug'] ?? NULL,
			'type'        => $attribute['type'] ?? NULL,
			'orderBy'     => $attribute['order_by'] ?? NULL,
			'hasArchives' => (bool) ( $attribute['has_archives'] ?? FALSE ),
			'itemType'    => $attribute['itemType'] ?? 'attribute',
			'terms'       => [],
			'bom'         => NULL,
		];

		// Handle terms array if present
		if ( ! empty( $attribute['terms'] ) ) {
			$prepared_data['terms'] = array_map( function ( $term ) {
				$prepared_term = [
					'_id'         => 'term:' . $this->generate_uuid(),
					'id'          => (int) $term['id'],
					'name'        => $term['name'],
					'slug'        => $term['slug'] ?? NULL,
					'description' => $term['description'] ?? NULL,
					'count'       => (int) ($term['count'] ?? 0),
					'menuOrder'   => (int) ($term['menu_order'] ?? 0),
					'deleted'     => (bool) ($term['deleted'] ?? false),
					'bom'         => NULL,
					'value'       => (float) ($term['value'] ?? 0),
					'_deleted'    => (bool) ($term['_deleted'] ?? false),
					'_rev'        => $term['_rev'] ?? NULL
				];

				// BOM object structure as per schema
				if ( ! empty( $term['bom'] ) ) {
					$prepared_term['bom'] = [
						'id'     => (string) $term['bom']['id'],
						'name'   => $term['bom']['name'],
						'type'   => $term['bom']['type'],
						'qty'    => (float) $term['bom']['qty'],
						'delete' => (bool) ($term['bom']['delete'] ?? false),
					];
				}

				return $prepared_term;
			}, $attribute['terms'] );
		}

		// Handle attribute level BOM if present
		if ( ! empty( $attribute['bom'] ) ) {
			$prepared_data['bom'] = [
				'id'     => (string) $attribute['bom']['id'],
				'name'   => $attribute['bom']['name'],
				'type'   => $attribute['bom']['type'],
				'qty'    => (float) $attribute['bom']['qty'],
				'delete' => (bool) ($attribute['bom']['delete'] ?? false),
			];
		}

		return array_merge( $this->get_base_fields(), $prepared_data );

	}

}
