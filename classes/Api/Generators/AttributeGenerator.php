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
			'id'           => (int) $attribute['id'],
			'name'         => $attribute['name'],
			'slug'         => $attribute['slug'],
			'type'         => $attribute['type'],
			'orderBy'      => $attribute['order_by'],
			'hasArchives'  => (bool) $attribute['has_archives'],
			'terms'		   => [],
		];

		// Handle terms array if present.
		if ( ! empty( $attribute['terms'] ) ) {

			$prepared_data['terms'] = array_map( function ( $term ) {

				$prepared_term = [
					'_id'         => 'term:' . $this->generate_uuid(),
					'id'          => (int) $term['id'],
					'name'        => $term['name'],
					'slug'        => $term['slug'],
					'description' => $term['description'] ?? NULL,
					'count'       => (int) ( $term['count'] ?? 0 ),
					'menuOrder'   => (int) ( $term['menu_order'] ?? 0 ),
					'bom'         => NULL,
					'value'       => (float) ( $term['value'] ?? 0 ),
				];

				// BOM object structure as per schema.
				if ( ! empty( $term['bom'] ) ) {
					$prepared_term['bom'] = [
						'id'     => (int) $term['bom']['id'],
						'name'   => $term['bom']['name'],
						'type'   => $term['bom']['type'],
						'qty'    => (float) $term['bom']['qty'],
						'delete' => (bool) ( $term['bom']['delete'] ?? FALSE ),
					];
				}

				return $prepared_term;

			}, $attribute['terms'] );

		}

		return array_merge( $this->get_base_fields(), $prepared_data );

	}

}
