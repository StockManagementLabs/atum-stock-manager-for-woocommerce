<?php
/**
 * Attribute generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
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
	 * @param array $attribute Raw attribute data
	 *
	 * @return array Prepared attribute data
	 */
	protected function prepare_data( array $attribute ): array {

		$prepared_data = [
			'_id'          => $this->schema_name . ':' . $this->generate_uuid(),
			'_rev'         => $this->revision,
			'_deleted'     => FALSE,
			'_meta'        => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments' => new \stdClass(),
			'id'           => (int) $attribute['id'],
			'name'         => $attribute['name'],
			'slug'         => $attribute['slug'],
			'type'         => $attribute['type'],
			'orderBy'      => $attribute['order_by'],
			'hasArchives'  => (bool) $attribute['has_archives'],
		];

		// Handle terms array if present
		if ( ! empty( $attribute['terms'] ) ) {

			$prepared_data['terms'] = array_map( function ( $term ) {

				$prepared_term = [
					'_id'  => 'term:' . $this->generate_uuid(),
					'id'   => (int) $term['id'],
					'name' => $term['name'],
					'slug' => $term['slug'],
				];

				// Optional term properties
				if ( isset( $term['description'] ) ) {
					$prepared_term['description'] = $term['description'];
				}

				if ( isset( $term['count'] ) ) {
					$prepared_term['count'] = (int) $term['count'];
				}

				if ( isset( $term['menu_order'] ) ) {
					$prepared_term['menuOrder'] = (int) $term['menu_order'];
				}

				// BOM object structure as per schema
				if ( isset( $term['bom'] ) ) {
					$prepared_term['bom'] = [
						'id'     => $term['bom']['id'],
						'name'   => $term['bom']['name'],
						'type'   => $term['bom']['type'],
						'qty'    => (float) $term['bom']['qty'],
						'delete' => (bool) $term['bom']['delete']
					];
				}

				if ( isset( $term['value'] ) ) {
					$prepared_term['value'] = (float) $term['value'];
				}

				return $prepared_term;

			}, $attribute['terms'] );

		}
		else {
			$prepared_data['terms'] = [];
		}

		return $prepared_data;
	}

}
